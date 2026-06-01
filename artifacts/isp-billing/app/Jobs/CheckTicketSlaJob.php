<?php

namespace App\Jobs;

use App\Services\TicketSlaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckTicketSlaJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
    }

    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            $slaService = app(TicketSlaService::class);
            $escalated = $slaService->checkAndEscalate();

            $this->logJobExecution('CheckTicketSlaJob', 'success',
                (microtime(true) - $startTime) * 1000, count($escalated), null);

            if (count($escalated) > 0) {
                Log::info("Escalated " . count($escalated) . " tickets due to SLA breach");
            }

        } catch (\Exception $e) {
            $this->logJobExecution('CheckTicketSlaJob', 'failed',
                (microtime(true) - $startTime) * 1000, 0, $e->getMessage());
            Log::error("CheckTicketSlaJob failed: " . $e->getMessage());
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
