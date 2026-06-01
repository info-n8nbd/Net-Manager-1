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
use App\Models\AutoInactiveSkipDate;

class AutoDisableOverdueClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $lockKey = 'auto_disable_overdue_lock';
        $lock = Cache::lock($lockKey, 1800);

        if (!$lock->get()) {
            Log::info("AutoDisable: Skipped - another instance is already running");
            return;
        }

        $startTime = microtime(true);
        $disconnectedCount = 0;
        $mikrotikSuccessCount = 0;
        $mikrotikFailCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $lastErrors = [];

        try {
            $now = Carbon::now('Asia/Dhaka');
            $currentTime = $now->format('H:i');
            $today = $now->toDateString();
            $currentDay = (int) $now->format('d');

            // Multi-tenant: load settings for ALL companies
            $allSettings = DB::table('automation_settings')->get();
            if ($allSettings->isEmpty()) {
                Log::warning('AutoDisable: Automation settings not found for any company.');
                return;
            }

            $settingsByCompany = $allSettings->keyBy('company_id');

            // Find companies whose auto-inactive time has already passed
            $readyCompanyIds = $settingsByCompany
                ->filter(fn($s) => $currentTime >= substr($s->disconnect_time ?? '23:59', 0, 5))
                ->keys()
                ->toArray();

            if (empty($readyCompanyIds)) {
                return; // No company's disconnect time has passed yet
            }

            Log::info('AutoDisable: Ready companies: ' . implode(',', $readyCompanyIds) . ' at ' . $currentTime);

            // Per-company skip date check — company A's skip date must NOT affect company B
            $processableCompanyIds = [];
            foreach ($readyCompanyIds as $cid) {
                $hasSkipDate = DB::table('auto_inactive_skip_dates')
                    ->where('company_id', $cid)
                    ->whereDate('skip_date', $today)
                    ->exists();
                if ($hasSkipDate) {
                    Log::info("AutoDisable: Company {$cid} skipped — today ({$today}) is their skip date.");
                } else {
                    $processableCompanyIds[] = $cid;
                }
            }

            if (empty($processableCompanyIds)) {
                $this->logJobExecution('AutoDisableOverdueClientsJob', 'skipped_date', 0, 0, "All ready companies have skip date: {$today}");
                return;
            }

            $baseQuery = Client::whereIn('company_id', $processableCompanyIds)
                ->where('status', 'active')
                ->whereNull('reseller_id')
                ->where('due_amount', '>', 0)
                ->whereNotNull('payment_deadline')
                ->where('payment_deadline', '<=', $currentDay)
                ->where(function ($q) {
                    // Billing cycle must have started:
                    // 1) Client joined in a PREVIOUS month (billing has started)
                    // 2) Client joined in the CURRENT month ON or BEFORE their billing day
                    // (if joined AFTER billing day, first billing is next month → skip)
                    $q->whereRaw("DATE_TRUNC('month', connection_date) < DATE_TRUNC('month', NOW())")
                      ->orWhereRaw("DATE_TRUNC('month', connection_date) = DATE_TRUNC('month', NOW()) AND EXTRACT(DAY FROM connection_date)::int <= payment_deadline");
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('temp_deadline_date')
                      ->orWhere('temp_deadline_date', '<=', $today);
                });

            $resellerQuery = Client::whereIn('company_id', $processableCompanyIds)
                ->where('status', 'active')
                ->whereNotNull('reseller_id')
                ->whereNotNull('expire_date')
                ->where('expire_date', '<=', $today);

            $regularCount = (clone $baseQuery)->count();
            $resellerCount = (clone $resellerQuery)->count();
            $totalClients = $regularCount + $resellerCount;

            if ($totalClients === 0) {
                return;
            }

            Log::info("AutoDisable: Starting batch disable for {$totalClients} clients");

            $defaultDevice = MikrotikDevice::where('is_active', true)->first();
            $mikrotikConnections = [];
            $smsService = app(SmsService::class);

            $regularIds = (clone $baseQuery)->pluck('id')->toArray();
            $resellerIds = (clone $resellerQuery)->pluck('id')->toArray();
            $eligibleIds = array_merge($regularIds, $resellerIds);

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

                        // Per-company minimum due check
                        $companyMinDue = (float)(($settingsByCompany[$client->company_id]->min_due_for_inactive ?? 0));
                        if ($companyMinDue > 0 && $client->due_amount <= $companyMinDue) {
                            $skippedCount++;
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

                                        try {
                                            $mk->disconnectActiveSession($client->username);
                                        } catch (\Throwable $e) {}

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
                                    Log::warning("AutoDisable: MikroTik error client {$client->id}: " . $e->getMessage());
                                }
                            }
                        }

                        $currentMkProfile = $client->mikrotik_profile_name
                            ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);

                        $isResellerClient = !empty($client->reseller_id);
                        $disableReason = $isResellerClient
                            ? "Recharge expired"
                            : "Inactive for due payment";

                        $disableUpdate = [
                            'status' => 'inactive',
                            'connection_status' => 'offline',
                            'last_inactive_date' => now(),
                            'last_status_change' => now(),
                            'auto_inactive_note' => $disableReason,
                        ];
                        if ($currentMkProfile && strtolower($currentMkProfile) !== 'inactive') {
                            $disableUpdate['previous_mikrotik_profile'] = $currentMkProfile;
                            $disableUpdate['mikrotik_profile_name'] = 'Inactive';
                        }

                        DB::table('clients')->where('id', $client->id)->update($disableUpdate);

                        $logBatch[] = [
                            'client_id' => $client->id,
                            'action' => 'deadline_disconnected',
                            'previous_status' => 'active',
                            'new_status' => 'inactive',
                            'expire_date' => $client->expire_date,
                            'mikrotik_success' => $mikrotikSuccess,
                            'profile_shifted' => $profileShifted,
                            'notes' => $disableReason,
                            'created_at' => now(),
                        ];

                        $statusLogBatch[] = [
                            'client_id' => $client->id,
                            'company_id' => $client->company_id,
                            'old_status' => 'active',
                            'new_status' => 'inactive',
                            'source' => 'auto',
                            'reason' => $disableReason,
                            'changed_by' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        try {
                            $smsResult = $smsService->sendAutoInactiveNotice($client);
                        } catch (\Throwable $e) {
                            Log::warning("AutoDisable: SMS failed client {$client->id}: " . $e->getMessage());
                        }

                        $disconnectedCount++;

                    } catch (\Throwable $e) {
                        $errorCount++;
                        if (count($lastErrors) < 10) {
                            $lastErrors[] = "Client {$client->id}: " . substr($e->getMessage(), 0, 100);
                        }
                        Log::error("AutoDisable: Failed client {$client->id}: " . $e->getMessage());
                    }
                }

                if (!empty($logBatch)) {
                    try {
                        DB::table('client_expiry_logs')->insert($logBatch);
                    } catch (\Throwable $e) {
                        Log::error("AutoDisable: Batch log insert failed: " . $e->getMessage());
                        foreach ($logBatch as $log) {
                            try { DB::table('client_expiry_logs')->insert($log); } catch (\Throwable $e2) {}
                        }
                    }
                }

                if (!empty($statusLogBatch)) {
                    try {
                        DB::table('client_status_logs')->insert($statusLogBatch);
                    } catch (\Throwable $e) {
                        Log::error("AutoDisable: Status log batch insert failed: " . $e->getMessage());
                        foreach ($statusLogBatch as $log) {
                            try { DB::table('client_status_logs')->insert($log); } catch (\Throwable $e2) {}
                        }
                    }
                }

                if (($chunkIndex + 1) % 5 === 0 || $disconnectedCount >= 1000) {
                    $elapsed = round(microtime(true) - $startTime, 1);
                    Log::info("AutoDisable: Progress {$disconnectedCount}/{$totalClients} clients disabled, {$elapsed}s elapsed");
                }
            }

            foreach ($mikrotikConnections as $conn) {
                try { $conn->disconnect(); } catch (\Throwable $e) {}
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info("AutoDisable: Done - Disconnected:{$disconnectedCount}, MikroTikOK:{$mikrotikSuccessCount}, MikroTikFail:{$mikrotikFailCount}, Skipped:{$skippedCount}, Errors:{$errorCount}, Time:" . round($executionTime) . "ms");

            $this->logJobExecution('AutoDisableOverdueClientsJob',
                $errorCount === 0 ? 'success' : 'partial',
                $executionTime, $disconnectedCount,
                implode('; ', $lastErrors));

        } catch (\Throwable $e) {
            Log::error('AutoDisableOverdueClientsJob fatal: ' . $e->getMessage());
            $this->logJobExecution('AutoDisableOverdueClientsJob', 'failed',
                (microtime(true) - $startTime) * 1000, $disconnectedCount, $e->getMessage());
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
            Log::warning("AutoDisable: Cannot connect to device {$deviceId}: " . $e->getMessage());
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
