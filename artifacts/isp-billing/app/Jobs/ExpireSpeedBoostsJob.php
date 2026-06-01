<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Services\MikroTikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContext;

class ExpireSpeedBoostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $expiredBoosts = DB::table('speed_boost_requests')
                ->where('status', 'active')
                ->where('ends_at', '<=', now())
                ->get();

            foreach ($expiredBoosts as $boost) {
                try {
                    $client = Client::find($boost->client_id);
                    if (!$client) continue;

                    $originalSpeed = $client->package?->speed ?? 10;

                    if ($client->mikrotik_device_id) {
                        $device = MikrotikDevice::find($client->mikrotik_device_id);
                        if ($device) {
                            $mikrotikService = app(MikroTikService::class);
                            $api = $mikrotikService->connect($device);

                            if ($api) {
                                $queues = $api->comm('/queue/simple/print', ['?name' => $client->pppoe_username]);

                                if (!empty($queues)) {
                                    $maxLimit = "{$originalSpeed}M/{$originalSpeed}M";
                                    $api->comm('/queue/simple/set', [
                                        '.id' => $queues[0]['.id'],
                                        'max-limit' => $maxLimit,
                                    ]);
                                }

                                $mikrotikService->disconnect();
                            }
                        }
                    }

                    DB::table('speed_boost_requests')
                        ->where('id', $boost->id)
                        ->update([
                            'status' => 'expired',
                            'updated_at' => now(),
                        ]);

                    $recordsProcessed++;

                } catch (\Exception $e) {
                    Log::warning("Failed to expire speed boost {$boost->id}: " . $e->getMessage());
                }
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            Log::error("Speed boost expiration failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
        }
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'expire_speed_boosts',
            'job_class' => self::class,
            'status' => 'started',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function logCronComplete(float $startTime, int $records): void
    {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        DB::table('cron_job_logs')
            ->where('job_class', self::class)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'status' => 'completed',
                'records_processed' => $records,
                'execution_time_ms' => $executionTime,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function logCronFailed(float $startTime, string $message): void
    {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        DB::table('cron_job_logs')
            ->where('job_class', self::class)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'status' => 'failed',
                'message' => $message,
                'execution_time_ms' => $executionTime,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
