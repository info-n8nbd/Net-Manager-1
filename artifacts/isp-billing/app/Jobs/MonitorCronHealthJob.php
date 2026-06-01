<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorCronHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $criticalJobs = [
        'sync-mikrotik-sessions',
        'poll-olt-onu-status',
        'auto-disable-overdue-clients',
        'generate-daily-bills',
    ];

    public function handle(): void
    {
        $startTime = microtime(true);
        $issues = [];

        try {
            DB::table('cron_health_logs')->insert([
                'job_name' => 'cron_health_monitor',
                'status' => 'started',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($this->criticalJobs as $jobName) {
                $lastRun = DB::table('cron_job_logs')
                    ->where('job_name', $jobName)
                    ->where('status', 'completed')
                    ->orderByDesc('completed_at')
                    ->first();

                if (!$lastRun) {
                    $issues[] = ['job' => $jobName, 'issue' => 'Never run'];
                    continue;
                }

                $lastRunTime = \Carbon\Carbon::parse($lastRun->completed_at);
                $maxInterval = $this->getMaxInterval($jobName);

                if ($lastRunTime->diffInMinutes(now()) > $maxInterval) {
                    $issues[] = [
                        'job' => $jobName,
                        'issue' => 'Overdue',
                        'last_run' => $lastRunTime->toIso8601String(),
                        'minutes_since' => $lastRunTime->diffInMinutes(now()),
                    ];
                }

                $recentFailures = DB::table('cron_job_logs')
                    ->where('job_name', $jobName)
                    ->where('status', 'failed')
                    ->where('created_at', '>=', now()->subHours(1))
                    ->count();

                if ($recentFailures >= 3) {
                    $issues[] = [
                        'job' => $jobName,
                        'issue' => 'Multiple failures',
                        'failure_count' => $recentFailures,
                    ];
                }
            }

            if (!empty($issues)) {
                $this->createCronHealthAlert($issues);
            }

            $this->retryFailedJobs();

            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            DB::table('cron_health_logs')
                ->where('job_name', 'cron_health_monitor')
                ->where('status', 'started')
                ->orderByDesc('id')
                ->limit(1)
                ->update([
                    'status' => 'completed',
                    'execution_time_ms' => $executionTime,
                    'records_processed' => count($issues),
                    'updated_at' => now(),
                ]);

        } catch (\Exception $e) {
            Log::error("Cron health monitoring failed: " . $e->getMessage());
        }
    }

    protected function getMaxInterval(string $jobName): int
    {
        return match ($jobName) {
            'sync_mikrotik_sessions' => 5,
            'poll_olt_onu_status' => 15,
            'detect_area_outage' => 15,
            default => 120,
        };
    }

    protected function createCronHealthAlert(array $issues): void
    {
        DB::table('network_event_logs')->insert([
            'event_type' => 'cron_health_issue',
            'source' => 'cron_monitor',
            'message' => 'Cron job health issues detected: ' . json_encode($issues),
            'severity' => 'warning',
            'metadata' => json_encode($issues),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::warning("Cron health issues: " . json_encode($issues));
    }

    protected function retryFailedJobs(): void
    {
        $failedJobs = DB::table('failed_job_retries')
            ->where('status', 'pending')
            ->where('attempt_count', '<', DB::raw('max_attempts'))
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                  ->orWhere('next_retry_at', '<=', now());
            })
            ->limit(10)
            ->get();

        foreach ($failedJobs as $job) {
            try {
                $jobClass = $job->job_class;
                if (class_exists($jobClass)) {
                    dispatch(new $jobClass());

                    DB::table('failed_job_retries')
                        ->where('id', $job->id)
                        ->update([
                            'attempt_count' => $job->attempt_count + 1,
                            'status' => 'retrying',
                            'updated_at' => now(),
                        ]);
                }
            } catch (\Exception $e) {
                DB::table('failed_job_retries')
                    ->where('id', $job->id)
                    ->update([
                        'attempt_count' => $job->attempt_count + 1,
                        'last_error' => $e->getMessage(),
                        'next_retry_at' => now()->addMinutes(5 * ($job->attempt_count + 1)),
                        'status' => $job->attempt_count + 1 >= $job->max_attempts ? 'failed' : 'pending',
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}
