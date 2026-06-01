<?php

namespace App\Jobs;

use App\Models\Client;
use App\Services\AdvancedAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContext;

class UpdateChurnPredictionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $analyticsService = app(AdvancedAnalyticsService::class);

            $clients = Client::where('status', 'active')
                ->inRandomOrder()
                ->limit(200)
                ->get();

            foreach ($clients as $client) {
                try {
                    $analyticsService->predictChurn($client->id);
                    $recordsProcessed++;
                } catch (\Exception $e) {
                    Log::warning("Churn prediction failed for client {$client->id}: " . $e->getMessage());
                }
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            Log::error("Churn predictions update failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
        }
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'update_churn_predictions',
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
