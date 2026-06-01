<?php

namespace App\Jobs;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContext;

class DetectAreaOutageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $offlineThreshold;

    public function __construct(int $offlineThreshold = 50)
    {
        $this->offlineThreshold = $offlineThreshold;
    }

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $zones = DB::table('zones')->get();

            foreach ($zones as $zone) {
                $totalClients = Client::where('zone_id', $zone->id)
                    ->where('status', 'active')
                    ->count();

                if ($totalClients < 5) continue;

                $offlineClients = Client::where('zone_id', $zone->id)
                    ->where('status', 'active')
                    ->where('connection_status', 'offline')
                    ->count();

                $offlinePercent = ($offlineClients / $totalClients) * 100;

                if ($offlinePercent >= $this->offlineThreshold) {
                    $existingOutage = DB::table('area_outage_logs')
                        ->where('zone_id', $zone->id)
                        ->whereNull('resolved_at')
                        ->exists();

                    if (!$existingOutage) {
                        DB::table('area_outage_logs')->insert([
                            'zone_id' => $zone->id,
                            'affected_clients' => $offlineClients,
                            'outage_type' => 'network',
                            'description' => "{$offlinePercent}% clients offline in {$zone->name}",
                            'started_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('network_event_logs')->insert([
                            'event_type' => 'area_outage',
                            'source' => 'outage_detection',
                            'message' => "Area outage detected in {$zone->name}: {$offlineClients} of {$totalClients} clients offline",
                            'severity' => 'critical',
                            'metadata' => json_encode([
                                'zone_id' => $zone->id,
                                'zone_name' => $zone->name,
                                'offline_count' => $offlineClients,
                                'total_clients' => $totalClients,
                                'offline_percent' => round($offlinePercent, 1),
                            ]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $recordsProcessed++;
                    }
                } else {
                    $activeOutage = DB::table('area_outage_logs')
                        ->where('zone_id', $zone->id)
                        ->whereNull('resolved_at')
                        ->first();

                    if ($activeOutage && $offlinePercent < 20) {
                        DB::table('area_outage_logs')
                            ->where('id', $activeOutage->id)
                            ->update([
                                'resolved_at' => now(),
                                'resolution_notes' => "Auto-resolved: Offline dropped to {$offlinePercent}%",
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            Log::error("Area outage detection failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
        }
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'detect_area_outage',
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
