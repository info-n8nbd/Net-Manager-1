<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\ClientStatusLog;
use App\Models\MikrotikDevice;
use App\Services\MikroTikService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Services\TenantContext;

class AutoExpireClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $lockKey = 'auto_expire_clients_lock';
        $lock = Cache::lock($lockKey, 1800);

        if (!$lock->get()) {
            Log::info("AutoExpire: Skipped - another instance is already running");
            return;
        }

        $startTime = microtime(true);
        $expiredCount = 0;
        $mikrotikSuccessCount = 0;
        $mikrotikFailCount = 0;
        $smsCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $lastErrors = [];

        try {
            $settings = DB::table('automation_settings')->first();
            $disconnectTime = $settings->disconnect_time ?? '23:59';
            $now = Carbon::now('Asia/Dhaka');
            $currentTime = $now->format('H:i');
            $today = $now->toDateString();

            if ($currentTime < substr($disconnectTime, 0, 5)) {
                return;
            }

            $baseQuery = Client::where('status', 'active')
                ->whereNotNull('expire_date')
                ->where('expire_date', '<=', $today)
                ->where(function ($q) {
                    // ISP direct clients: expire only when bill is due
                    // Reseller clients: expire by date alone (recharge-based, due_amount stays 0)
                    $q->where('due_amount', '>', 0)
                      ->orWhereNotNull('reseller_id');
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('temp_deadline_date')
                      ->orWhere('temp_deadline_date', '<=', $today);
                });

            $totalClients = (clone $baseQuery)->count();

            if ($totalClients === 0) {
                $this->logJobExecution('AutoExpireClientsJob', 'success', 0, 0, null);
                return;
            }

            Log::info("AutoExpire: Starting batch expire for {$totalClients} clients");

            $defaultDevice = MikrotikDevice::where('is_active', true)->first();
            $mikrotikConnections = [];
            $smsService = app(SmsService::class);

            $eligibleIds = (clone $baseQuery)
                ->pluck('id')
                ->toArray();

            $processedMap = [];

            foreach (array_chunk($eligibleIds, 200) as $chunkIndex => $chunkIds) {
                $clients = Client::whereIn('id', $chunkIds)->with('package')->get();
                $logBatch = [];
                $statusLogBatch = [];

                $freshStatuses = DB::table('clients')
                    ->whereIn('id', $chunkIds)
                    ->pluck('status', 'id')
                    ->toArray();

                foreach ($clients as $client) {
                    try {
                        if (isset($processedMap[$client->id])) {
                            $skippedCount++;
                            continue;
                        }

                        $freshStatus = $freshStatuses[$client->id] ?? null;
                        if ($freshStatus !== 'active') {
                            continue;
                        }

                        $processedMap[$client->id] = true;
                        $mikrotikSuccess = false;
                        $profileShifted = false;

                        if ($client->username) {
                            $deviceId = $client->mikrotik_device_id ?? ($defaultDevice ? $defaultDevice->id : null);

                            if ($deviceId) {
                                try {
                                    $mk = $this->getOrCreateConnection($mikrotikConnections, $deviceId, $defaultDevice);
                                    if ($mk) {
                                        $profileShifted = $mk->changePPPoEProfile($client->username, 'Inactive');
                                        if (!$profileShifted) {
                                            $mk->disablePPPoEUser($client->username);
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
                                    Log::warning("AutoExpire: MikroTik error client {$client->id}: " . $e->getMessage());
                                }
                            }
                        }

                        $previousStatus = $client->status;
                        $currentMkProfile = $client->mikrotik_profile_name
                            ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);

                        $expireUpdate = [
                            'status' => 'inactive',
                            'connection_status' => 'offline',
                            'last_inactive_date' => now(),
                            'auto_inactive_note' => "Inactive for due payment",
                            'temp_deadline_date' => null,
                            'temp_deadline_month' => null,
                            'temp_deadline_reason' => null,
                            'temp_deadline_applied_by' => null,
                            'temp_deadline_applied_at' => null,
                        ];
                        if ($currentMkProfile && strtolower($currentMkProfile) !== 'inactive') {
                            $expireUpdate['previous_mikrotik_profile'] = $currentMkProfile;
                            $expireUpdate['mikrotik_profile_name'] = 'Inactive';
                        }

                        DB::table('clients')->where('id', $client->id)->update($expireUpdate);

                        $logBatch[] = [
                            'client_id' => $client->id,
                            'action' => 'expired',
                            'previous_status' => $previousStatus,
                            'new_status' => 'inactive',
                            'expire_date' => $client->expire_date,
                            'mikrotik_success' => $mikrotikSuccess,
                            'profile_shifted' => $profileShifted,
                            'notes' => "Auto-expired: service date passed",
                            'created_at' => now(),
                        ];

                        $statusLogBatch[] = [
                            'client_id' => $client->id,
                            'old_status' => $previousStatus,
                            'new_status' => 'inactive',
                            'source' => 'auto',
                            'reason' => "Inactive for due payment",
                            'changed_by' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $expiredCount++;

                        try {
                            $smsResult = $smsService->sendExpiryNotification($client);
                            if ($smsResult) {
                                $smsCount++;
                            }
                        } catch (\Throwable $e) {
                            Log::warning("AutoExpire: SMS failed client {$client->id}: " . $e->getMessage());
                        }

                    } catch (\Throwable $e) {
                        $errorCount++;
                        if (count($lastErrors) < 10) {
                            $lastErrors[] = "Client {$client->id}: " . substr($e->getMessage(), 0, 100);
                        }
                        Log::error("AutoExpire: Failed client {$client->id}: " . $e->getMessage());
                    }
                }

                if (!empty($logBatch)) {
                    try {
                        DB::table('client_expiry_logs')->insert($logBatch);
                    } catch (\Throwable $e) {
                        Log::error("AutoExpire: Batch log insert failed: " . $e->getMessage());
                        foreach ($logBatch as $log) {
                            try { DB::table('client_expiry_logs')->insert($log); } catch (\Throwable $e2) {}
                        }
                    }
                }

                if (!empty($statusLogBatch)) {
                    try {
                        DB::table('client_status_logs')->insert($statusLogBatch);
                    } catch (\Throwable $e) {
                        Log::error("AutoExpire: Status log batch insert failed: " . $e->getMessage());
                        foreach ($statusLogBatch as $log) {
                            try { DB::table('client_status_logs')->insert($log); } catch (\Throwable $e2) {}
                        }
                    }
                }

                if (($chunkIndex + 1) % 5 === 0 || $expiredCount >= 1000) {
                    $elapsed = round(microtime(true) - $startTime, 1);
                    Log::info("AutoExpire: Progress {$expiredCount}/{$totalClients} clients expired, {$elapsed}s elapsed");
                }
            }

            foreach ($mikrotikConnections as $conn) {
                try { $conn->disconnect(); } catch (\Throwable $e) {}
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info("AutoExpire: Done - Expired:{$expiredCount}, MikroTikOK:{$mikrotikSuccessCount}, MikroTikFail:{$mikrotikFailCount}, SMS:{$smsCount}, Skipped:{$skippedCount}, Errors:{$errorCount}, Time:" . round($executionTime) . "ms");

            $this->logJobExecution('AutoExpireClientsJob',
                $errorCount === 0 ? 'success' : 'partial',
                $executionTime, $expiredCount,
                implode('; ', $lastErrors));

        } catch (\Throwable $e) {
            Log::error('AutoExpireClientsJob fatal: ' . $e->getMessage());
            $this->logJobExecution('AutoExpireClientsJob', 'failed',
                (microtime(true) - $startTime) * 1000, $expiredCount, $e->getMessage());
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
            Log::warning("AutoExpire: Cannot connect to device {$deviceId}: " . $e->getMessage());
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
