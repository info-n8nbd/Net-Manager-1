<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\ClientStatusLog;
use App\Models\MikrotikDevice;
use App\Services\MikroTikService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\TenantContext;

class AutoEnableAfterPaymentJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $lockKey = 'auto_enable_after_payment_lock';
        $lock = Cache::lock($lockKey, 1800);

        if (!$lock->get()) {
            Log::info("AutoEnable: Skipped - another instance is already running");
            return;
        }

        $startTime = microtime(true);
        $enabled = 0;
        $mikrotikSuccessCount = 0;
        $mikrotikFailCount = 0;
        $errorCount = 0;
        $lastErrors = [];

        try {
            $today = Carbon::now('Asia/Dhaka')->toDateString();

            $baseQuery = Client::where('status', 'inactive')
                ->where(function ($q) {
                    $q->where('due_amount', '<=', 0)
                      ->orWhereNull('due_amount');
                })
                ->where(function ($q) {
                    $q->where('auto_inactive_note', 'LIKE', '%due payment%')
                      ->orWhere('auto_inactive_note', 'LIKE', '%Auto-expired%');
                });

            $totalClients = (clone $baseQuery)->count();

            if ($totalClients === 0) {
                $this->logJobExecution('AutoEnableAfterPaymentJob', 'success', 0, 0, null);
                return;
            }

            Log::info("AutoEnable: Starting batch enable for {$totalClients} clients");

            $alreadyEnabledToday = DB::table('client_expiry_logs')
                ->where('action', 'reactivated')
                ->whereDate('created_at', $today)
                ->pluck('client_id')
                ->toArray();
            $alreadyEnabledMap = array_flip($alreadyEnabledToday);

            $defaultDevice = MikrotikDevice::where('is_active', true)->first();
            $mikrotikConnections = [];
            $smsService = app(SmsService::class);

            $eligibleIds = (clone $baseQuery)
                ->whereNotIn('id', $alreadyEnabledToday)
                ->pluck('id')
                ->toArray();

            $processedMap = [];

            foreach (array_chunk($eligibleIds, 200) as $chunkIndex => $chunkIds) {
                $clients = Client::whereIn('id', $chunkIds)->with('package')->get();
                $logBatch = [];
                $statusLogBatch = [];
                $newExpireDate = Carbon::now('Asia/Dhaka')->addMonthNoOverflow()->toDateString();

                $freshStatuses = DB::table('clients')
                    ->whereIn('id', $chunkIds)
                    ->pluck('status', 'id')
                    ->toArray();

                foreach ($clients as $client) {
                    try {
                        if (isset($processedMap[$client->id])) {
                            continue;
                        }

                        $freshStatus = $freshStatuses[$client->id] ?? null;
                        if ($freshStatus !== 'inactive') {
                            continue;
                        }

                        $processedMap[$client->id] = true;
                        $previousProfile = null;
                        $mikrotikSuccess = false;
                        $profileRestored = false;
                        $restoredToProfile = null;

                        if ($client->username) {
                            $deviceId = $client->mikrotik_device_id ?? ($defaultDevice ? $defaultDevice->id : null);

                            if ($deviceId) {
                                try {
                                    $mk = $this->getOrCreateConnection($mikrotikConnections, $deviceId, $defaultDevice);
                                    if ($mk) {
                                        $previousProfile = $mk->getPPPoEUserProfile($client->username);
                                        $originalProfile = $client->previous_mikrotik_profile
                                            ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);

                                        if ($originalProfile) {
                                            if ($previousProfile !== $originalProfile) {
                                                $profileRestored = (bool) $mk->changePPPoEProfile($client->username, $originalProfile);
                                                $restoredToProfile = $originalProfile;
                                            } else {
                                                $profileRestored = true;
                                                $restoredToProfile = $originalProfile;
                                            }
                                            $mk->enablePPPoEUser($client->username);
                                        } else {
                                            $mk->enablePPPoEUser($client->username);
                                        }
                                        $mikrotikSuccess = true;
                                        $mikrotikSuccessCount++;
                                    }
                                } catch (\Throwable $e) {
                                    $mikrotikFailCount++;
                                    unset($mikrotikConnections[$deviceId]);
                                    $errorCount++;
                                    if (count($lastErrors) < 10) {
                                        $lastErrors[] = "MikroTik client {$client->id}: " . substr($e->getMessage(), 0, 100);
                                    }
                                    Log::warning("AutoEnable: MikroTik error client {$client->id}: " . $e->getMessage());
                                }
                            }
                        }

                        $restoreProfile = $client->previous_mikrotik_profile
                            ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);

                        $updateData = [
                            'status' => 'active',
                            'last_active_date' => now(),
                            'last_status_change' => now(),
                            'expire_date' => $newExpireDate,
                            'auto_inactive_note' => null,
                        ];
                        if ($restoreProfile) {
                            $updateData['mikrotik_profile_name'] = $restoreProfile;
                            $updateData['previous_mikrotik_profile'] = null;
                        }

                        DB::table('clients')->where('id', $client->id)->update($updateData);

                        $logBatch[] = [
                            'client_id' => $client->id,
                            'action' => 'reactivated',
                            'previous_status' => 'inactive',
                            'new_status' => 'active',
                            'expire_date' => $client->expire_date,
                            'new_expire_date' => $newExpireDate,
                            'mikrotik_success' => $mikrotikSuccess,
                            'profile_shifted' => $profileRestored,
                            'previous_profile' => $previousProfile,
                            'new_profile' => $restoredToProfile,
                            'notes' => "Active - Payment cleared",
                            'created_at' => now(),
                        ];

                        $statusLogBatch[] = [
                            'client_id' => $client->id,
                            'company_id' => $client->company_id,
                            'old_status' => 'inactive',
                            'new_status' => 'active',
                            'source' => 'auto',
                            'reason' => "Active - Payment cleared",
                            'changed_by' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        try {
                            $smsResult = $smsService->sendReactivationNotification($client);
                        } catch (\Throwable $e) {
                            Log::warning("AutoEnable: SMS failed client {$client->id}: " . $e->getMessage());
                        }

                        $enabled++;

                    } catch (\Throwable $e) {
                        $errorCount++;
                        if (count($lastErrors) < 10) {
                            $lastErrors[] = "Client {$client->id}: " . substr($e->getMessage(), 0, 100);
                        }
                        Log::error("AutoEnable: Failed client {$client->id}: " . $e->getMessage());
                    }
                }

                if (!empty($logBatch)) {
                    try {
                        DB::table('client_expiry_logs')->insert($logBatch);
                    } catch (\Throwable $e) {
                        Log::error("AutoEnable: Batch log insert failed: " . $e->getMessage());
                        foreach ($logBatch as $log) {
                            try { DB::table('client_expiry_logs')->insert($log); } catch (\Throwable $e2) {}
                        }
                    }
                }

                if (!empty($statusLogBatch)) {
                    try {
                        DB::table('client_status_logs')->insert($statusLogBatch);
                    } catch (\Throwable $e) {
                        Log::error("AutoEnable: Status log batch insert failed: " . $e->getMessage());
                        foreach ($statusLogBatch as $log) {
                            try { DB::table('client_status_logs')->insert($log); } catch (\Throwable $e2) {}
                        }
                    }
                }

                if (($chunkIndex + 1) % 5 === 0 || $enabled >= 1000) {
                    $elapsed = round(microtime(true) - $startTime, 1);
                    Log::info("AutoEnable: Progress {$enabled}/{$totalClients} clients enabled, {$elapsed}s elapsed");
                }
            }

            foreach ($mikrotikConnections as $conn) {
                try { $conn->disconnect(); } catch (\Throwable $e) {}
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info("AutoEnable: Done - Enabled:{$enabled}, MikroTikOK:{$mikrotikSuccessCount}, MikroTikFail:{$mikrotikFailCount}, Errors:{$errorCount}, Time:" . round($executionTime) . "ms");

            $this->logJobExecution('AutoEnableAfterPaymentJob',
                $errorCount === 0 ? 'success' : 'partial',
                $executionTime, $enabled,
                implode('; ', $lastErrors));

        } catch (\Throwable $e) {
            $this->logJobExecution('AutoEnableAfterPaymentJob', 'failed',
                (microtime(true) - $startTime) * 1000, $enabled, $e->getMessage());
            Log::error("AutoEnableAfterPaymentJob fatal: " . $e->getMessage());
        } finally {
            $lock->release();
        }
    }

    protected function getOrCreateConnection(array &$connections, int $deviceId, ?MikrotikDevice $defaultDevice): ?MikroTikService
    {
        if (isset($connections[$deviceId]) && $connections[$deviceId]) {
            return $connections[$deviceId];
        }

        $device = ($defaultDevice && $defaultDevice->id === $deviceId)
            ? $defaultDevice
            : MikrotikDevice::find($deviceId);

        if (!$device) {
            return null;
        }

        try {
            $mk = new MikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
            $connections[$deviceId] = $mk;
            return $mk;
        } catch (\Throwable $e) {
            Log::warning("AutoEnable: Cannot connect to device {$deviceId}: " . $e->getMessage());
            return null;
        }
    }

    protected function logJobExecution(string $jobName, string $status, float $executionTimeMs, int $recordsProcessed, ?string $error): void
    {
        try {
            DB::table('cron_health_logs')->insert([
                'job_name' => $jobName,
                'status' => $status,
                'execution_time_ms' => (int) $executionTimeMs,
                'records_processed' => $recordsProcessed,
                'error_message' => $error ? substr($error, 0, 500) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to log job execution: " . $e->getMessage());
        }
    }
}
