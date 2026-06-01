<?php

namespace App\Jobs;

use App\Models\Client;
use App\Services\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\TenantContext;

class GenerateDailyBillsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(BillingService $billingService): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;
        $now = Carbon::now('Asia/Dhaka');
        $todayDay = $now->day;

        try {
            // Multi-tenant: load all companies' automation settings
            $allSettings = DB::table('automation_settings')->get();

            // Find companies whose bill_generate_time has passed today
            $readyCompanyIds = [];
            foreach ($allSettings as $s) {
                if (!$s->bill_generate_time) continue;
                $configuredTime = Carbon::parse($s->bill_generate_time, 'Asia/Dhaka');
                $scheduledToday = $now->copy()->startOfDay()
                    ->addHours((int) $configuredTime->format('H'))
                    ->addMinutes((int) $configuredTime->format('i'));
                if ($now->gte($scheduledToday)) {
                    $readyCompanyIds[] = $s->company_id;
                }
            }

            // If no company's bill time has passed yet, exit
            if (empty($readyCompanyIds)) {
                return;
            }

            Log::info('DailyBills: Ready companies: ' . implode(',', $readyCompanyIds) . ' at ' . $now->format('H:i'));

            $lastDayOfMonth = $now->copy()->endOfMonth()->day;
            $isLastDay = ($todayDay === $lastDayOfMonth);

            $currentYM = $now->year * 100 + $now->month; // e.g. 202604

            $clientQuery = Client::whereIn('company_id', $readyCompanyIds)
                ->where('status', 'active')
                ->where('monthly_fee', '>', 0)
                ->where(function($q) {
                    $q->where('is_free', false)->orWhereNull('is_free');
                })
                ->where(function($q) use ($now, $currentYM) {
                    // Only include clients whose billing_starts_at is null OR on/before current month
                    $q->whereNull('billing_starts_at')
                      ->orWhereRaw('(EXTRACT(YEAR FROM billing_starts_at) * 100 + EXTRACT(MONTH FROM billing_starts_at)) <= ?', [$currentYM]);
                })
                ->whereDoesntHave('bills', function ($q) use ($now) {
                    $q->where('billing_month', $now->month)
                      ->where('billing_year', $now->year)
                      ->where('is_cancelled', false);
                })
                ->with('package');

            if ($isLastDay) {
                $clientQuery->where('billing_date', '>=', $todayDay);
            } else {
                $clientQuery->where('billing_date', $todayDay);
            }

            $clients = $clientQuery->get();

            if ($clients->isEmpty()) {
                return;
            }

            $this->logCronStart();

            Log::info("Daily bill generation: Found {$clients->count()} clients with billing_date={$todayDay} to bill.");

            foreach ($clients as $client) {
                try {
                    $billingService->generateBill($client, $now->month, $now->year);
                    $recordsProcessed++;
                    Log::info("Bill generated for client {$client->client_id} ({$client->name})");
                } catch (\Exception $e) {
                    Log::error("Failed to generate bill for client {$client->client_id}: " . $e->getMessage());
                }
            }

            $this->logCronComplete($startTime, $recordsProcessed);
            Log::info("Daily bill generation completed. {$recordsProcessed} bills generated.");

        } catch (\Exception $e) {
            Log::error("Daily bill generation failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
            throw $e;
        }
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'generate_daily_bills',
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
