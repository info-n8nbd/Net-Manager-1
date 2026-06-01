<?php

namespace App\Console\Commands;

use App\Models\MikrotikActionLog;
use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditMikroTikRestore extends Command
{
    protected $signature = 'audit:mikrotik-restore
                            {--days=7 : Number of days to look back}
                            {--fix : Attempt to re-queue failed restores for active-paid clients}';

    protected $description = 'Audit MikroTik restore/enable operations for failures, connectivity issues, and retry patterns';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $fix = $this->option('fix');
        $since = now()->subDays($days);

        $this->info("╔══════════════════════════════════════════════════════════╗");
        $this->info("║  MikroTik Restore Operations Audit                     ║");
        $this->info("║  Period: Last {$days} days                                    ║");
        $this->info("╚══════════════════════════════════════════════════════════╝");
        $this->newLine();

        $totalActions = MikrotikActionLog::where('created_at', '>=', $since)->count();
        $enableActions = MikrotikActionLog::where('action', MikrotikActionLog::ACTION_ENABLE)
            ->where('created_at', '>=', $since)->count();
        $failedEnables = MikrotikActionLog::where('action', MikrotikActionLog::ACTION_ENABLE)
            ->where('status', MikrotikActionLog::STATUS_FAILED)
            ->where('created_at', '>=', $since)->count();
        $retriedActions = MikrotikActionLog::where('action', MikrotikActionLog::ACTION_ENABLE)
            ->where('retry_count', '>', 0)
            ->where('created_at', '>=', $since)->count();
        $successEnables = MikrotikActionLog::where('action', MikrotikActionLog::ACTION_ENABLE)
            ->where('status', MikrotikActionLog::STATUS_SUCCESS)
            ->where('created_at', '>=', $since)->count();

        $this->info("━━━ Overview ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total MikroTik actions', $totalActions],
                ['Enable/Restore actions', $enableActions],
                ['Successful enables', $successEnables],
                ['Failed enables', $failedEnables],
                ['Actions that needed retries', $retriedActions],
                ['Success rate', $enableActions > 0 ? round(($successEnables / $enableActions) * 100, 1) . '%' : 'N/A'],
            ]
        );
        $this->newLine();

        $this->info("━━━ Failed Restore Details ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $failedLogs = MikrotikActionLog::where('action', MikrotikActionLog::ACTION_ENABLE)
            ->where('status', MikrotikActionLog::STATUS_FAILED)
            ->where('created_at', '>=', $since)
            ->with(['client', 'device'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        if ($failedLogs->isEmpty()) {
            $this->info("  No failed restore operations found. All restores succeeded.");
        } else {
            $rows = [];
            foreach ($failedLogs as $log) {
                $rows[] = [
                    $log->id,
                    $log->client?->client_id ?? $log->client_id,
                    $log->username ?? '-',
                    $log->device?->name ?? ($log->router_ip ?? 'Unknown'),
                    $log->trigger_source ?? '-',
                    substr($log->error_message ?? 'No error message', 0, 50),
                    $log->retry_count,
                    $log->created_at->format('Y-m-d H:i'),
                ];
            }
            $this->table(
                ['Log ID', 'Client', 'Username', 'Router', 'Trigger', 'Error', 'Retries', 'Time'],
                $rows
            );
        }
        $this->newLine();

        $this->info("━━━ Connectivity Error Patterns ━━━━━━━━━━━━━━━━━━━━━━━━");
        $errorPatterns = MikrotikActionLog::where('status', MikrotikActionLog::STATUS_FAILED)
            ->where('created_at', '>=', $since)
            ->whereNotNull('error_message')
            ->select(DB::raw("SUBSTRING(error_message, 1, 80) as error_snippet"), DB::raw('COUNT(*) as count'))
            ->groupBy('error_snippet')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        if ($errorPatterns->isEmpty()) {
            $this->info("  No error patterns found.");
        } else {
            $this->table(
                ['Error Pattern', 'Occurrences'],
                $errorPatterns->map(fn($p) => [$p->error_snippet, $p->count])->toArray()
            );
        }
        $this->newLine();

        $this->info("━━━ Per-Router Failure Rate ━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $routerStats = MikrotikActionLog::where('action', MikrotikActionLog::ACTION_ENABLE)
            ->where('created_at', '>=', $since)
            ->whereNotNull('device_id')
            ->select(
                'device_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"),
                DB::raw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success")
            )
            ->groupBy('device_id')
            ->having(DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END)"), '>', 0)
            ->orderByDesc('failed')
            ->limit(10)
            ->get();

        if ($routerStats->isEmpty()) {
            $this->info("  No per-router failures found.");
        } else {
            $rows = [];
            foreach ($routerStats as $stat) {
                $device = \App\Models\MikrotikDevice::find($stat->device_id);
                $failRate = $stat->total > 0 ? round(($stat->failed / $stat->total) * 100, 1) : 0;
                $rows[] = [
                    $device->name ?? "Device #{$stat->device_id}",
                    $device->host ?? '-',
                    $stat->total,
                    $stat->success,
                    $stat->failed,
                    $failRate . '%',
                    $failRate > 50 ? 'CRITICAL' : ($failRate > 20 ? 'WARNING' : 'OK'),
                ];
            }
            $this->table(
                ['Router', 'IP', 'Total', 'Success', 'Failed', 'Fail Rate', 'Status'],
                $rows
            );
        }
        $this->newLine();

        $this->info("━━━ Orphaned Clients (Paid but still disabled) ━━━━━━━━");
        $billingService = app(\App\Services\BillingService::class);
        $potentialOrphans = Client::where('status', '!=', 'active')
            ->whereNotNull('mikrotik_device_id')
            ->whereNotNull('username')
            ->whereNotIn('status', ['expired', 'deleted', 'left'])
            ->get();

        $orphanedClients = collect();
        foreach ($potentialOrphans as $client) {
            $billingService->recalculateClientBalance($client);
            $client->refresh();
            if ($client->due_amount <= 0) {
                $orphanedClients->push($client);
            }
        }

        if ($orphanedClients->isEmpty()) {
            $this->info("  No orphaned clients found. All paid clients are active.");
        } else {
            $this->warn("  Found {$orphanedClients->count()} clients with zero/negative due but not active:");
            $rows = [];
            foreach ($orphanedClients->take(20) as $client) {
                $lastRestore = MikrotikActionLog::where('client_id', $client->id)
                    ->where('action', MikrotikActionLog::ACTION_ENABLE)
                    ->latest()
                    ->first();
                $rows[] = [
                    $client->client_id,
                    $client->username,
                    $client->status,
                    '৳' . number_format($client->due_amount, 2),
                    $lastRestore ? $lastRestore->status : 'No restore attempted',
                    $lastRestore?->error_message ? substr($lastRestore->error_message, 0, 40) : '-',
                ];
            }
            $this->table(
                ['Client ID', 'Username', 'Status', 'Due', 'Last Restore', 'Error'],
                $rows
            );

            if ($fix) {
                $this->warn("  Attempting to re-queue restore for orphaned clients...");
                $restored = 0;
                $mikrotikService = app(\App\Services\MikroTikService::class);
                foreach ($orphanedClients as $client) {
                    try {
                        $result = $mikrotikService->restoreClient($client, 'audit_fix', null, false);
                        if ($result['success']) {
                            $restored++;
                            $this->line("    ✓ Restored: {$client->client_id}");
                        } else {
                            $this->error("    ✗ Failed: {$client->client_id} - " . ($result['error'] ?? 'Unknown'));
                        }
                    } catch (\Throwable $e) {
                        $this->error("    ✗ Error: {$client->client_id} - {$e->getMessage()}");
                    }
                }
                $this->info("  Restored {$restored}/{$orphanedClients->count()} clients.");
            } else {
                $this->comment("  Run with --fix to attempt automatic restoration.");
            }
        }
        $this->newLine();

        $this->info("━━━ Pending/Stuck Actions ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $stuckActions = MikrotikActionLog::whereIn('status', [MikrotikActionLog::STATUS_PENDING, MikrotikActionLog::STATUS_RETRYING])
            ->where('created_at', '<', now()->subHours(1))
            ->count();

        if ($stuckActions > 0) {
            $this->warn("  {$stuckActions} actions stuck in pending/retrying for over 1 hour");
        } else {
            $this->info("  No stuck actions found.");
        }

        $this->newLine();
        $severity = 'OK';
        if ($failedEnables > 0 && $enableActions > 0 && ($failedEnables / $enableActions) > 0.1) {
            $severity = 'WARNING';
        }
        if (!$orphanedClients->isEmpty()) {
            $severity = 'CRITICAL';
        }

        $color = match($severity) {
            'CRITICAL' => 'error',
            'WARNING' => 'warn',
            default => 'info',
        };
        $this->$color("Overall Status: {$severity}");

        Log::channel('daily')->info("MikroTik restore audit completed", [
            'period_days' => $days,
            'total_enables' => $enableActions,
            'failed_enables' => $failedEnables,
            'orphaned_clients' => $orphanedClients->count(),
            'status' => $severity,
        ]);

        return $severity === 'CRITICAL' ? self::FAILURE : self::SUCCESS;
    }
}
