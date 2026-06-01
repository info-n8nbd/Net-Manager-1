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

class CheckRevenueLeakageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $unpaidOnlineClients = Client::where('connection_status', 'online')
                ->where('status', 'active')
                ->whereHas('bills', function ($q) {
                    $q->where('status', 'unpaid')
                      ->where('due_date', '<', now());
                })
                ->with(['bills' => function ($q) {
                    $q->where('status', 'unpaid')->where('due_date', '<', now());
                }])
                ->get();

            foreach ($unpaidOnlineClients as $client) {
                $totalDue = $client->bills->sum('due_amount');

                $exists = DB::table('revenue_leakage_alerts')
                    ->where('client_id', $client->id)
                    ->where('alert_type', 'unpaid_online')
                    ->where('is_resolved', false)
                    ->exists();

                if (!$exists && $totalDue > 0) {
                    DB::table('revenue_leakage_alerts')->insert([
                        'client_id'       => $client->id,
                        'alert_type'      => 'unpaid_online',
                        'expected_amount' => $totalDue,
                        'actual_amount'   => 0,
                        'difference'      => $totalDue,
                        'details'         => "Client {$client->name} ({$client->client_id}) is online but has overdue bills totaling {$totalDue} BDT",
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    $this->logNetworkEvent($client, "Revenue leakage detected: {$totalDue} BDT overdue");
                    $recordsProcessed++;
                }
            }

            $expiredOnlineClients = Client::where('connection_status', 'online')
                ->where('status', '!=', 'active')
                ->get();

            foreach ($expiredOnlineClients as $client) {
                $exists = DB::table('revenue_leakage_alerts')
                    ->where('client_id', $client->id)
                    ->where('alert_type', 'expired_online')
                    ->where('is_resolved', false)
                    ->exists();

                if (!$exists) {
                    $monthlyFee = $client->monthly_fee ?? 0;
                    DB::table('revenue_leakage_alerts')->insert([
                        'client_id'       => $client->id,
                        'alert_type'      => 'expired_online',
                        'expected_amount' => 0,
                        'actual_amount'   => $monthlyFee,
                        'difference'      => $monthlyFee,
                        'details'         => "Client {$client->name} ({$client->client_id}) is online but account status is {$client->status}",
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    $this->logNetworkEvent($client, "Expired account still online");
                    $recordsProcessed++;
                }
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            Log::error("Revenue leakage check failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
            throw $e;
        }
    }

    protected function logNetworkEvent($client, string $message): void
    {
        DB::table('network_event_logs')->insert([
            'event_type' => 'alert',
            'source'     => 'revenue_leakage',
            'client_id'  => $client->id,
            'message'    => $message,
            'severity'   => 'warning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name'   => 'check_revenue_leakage',
            'job_class'  => self::class,
            'status'     => 'started',
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
                'status'              => 'completed',
                'records_processed'   => $records,
                'execution_time_ms'   => $executionTime,
                'completed_at'        => now(),
                'updated_at'          => now(),
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
                'status'            => 'failed',
                'message'           => $message,
                'execution_time_ms' => $executionTime,
                'completed_at'      => now(),
                'updated_at'        => now(),
            ]);
    }
}
