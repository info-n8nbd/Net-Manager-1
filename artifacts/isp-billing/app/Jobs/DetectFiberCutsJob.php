<?php

namespace App\Jobs;

use App\Services\OnuManagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetectFiberCutsJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
    }

    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            $onuService = app(OnuManagementService::class);
            $suspected = $onuService->detectFiberCuts();

            $this->logJobExecution('DetectFiberCutsJob', 'success',
                (microtime(true) - $startTime) * 1000, count($suspected), null);

            if (count($suspected) > 0) {
                Log::warning("Detected " . count($suspected) . " suspected fiber cuts");
            }

        } catch (\Exception $e) {
            $this->logJobExecution('DetectFiberCutsJob', 'failed',
                (microtime(true) - $startTime) * 1000, 0, $e->getMessage());
            Log::error("DetectFiberCutsJob failed: " . $e->getMessage());
        }
    }

    protected function logJobExecution(string $jobName, string $status, float $executionTimeMs, int $recordsProcessed, ?string $error): void
    {
        DB::table('cron_health_logs')->insert([
            'job_name' => $jobName,
            'status' => $status,
            'execution_time_ms' => (int) $executionTimeMs,
            'records_processed' => $recordsProcessed,
            'error_message' => $error,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
