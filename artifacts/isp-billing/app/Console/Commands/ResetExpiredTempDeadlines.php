<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetExpiredTempDeadlines extends Command
{
    protected $signature = 'billing:reset-temp-deadlines {--dry-run : Show what would be reset without actually resetting}';
    protected $description = 'Reset expired temporary billing deadlines (runs at month end)';

    public function handle(BillingService $billingService): int
    {
        $dryRun = $this->option('dry-run');
        $currentMonth = now()->format('Y-m');
        $today = now()->toDateString();

        $clients = Client::whereNotNull('temp_deadline_date')
            ->where(function ($q) use ($currentMonth, $today) {
                $q->where('temp_deadline_month', '<', $currentMonth)
                  ->orWhere('temp_deadline_date', '<', $today);
            })
            ->get();

        $this->info("Found {$clients->count()} clients with expired temporary deadlines");

        $reset = 0;
        foreach ($clients as $client) {
            if ($dryRun) {
                $this->line("[DRY RUN] Would reset: {$client->client_id} ({$client->name}) - Deadline: {$client->temp_deadline_date} ({$client->temp_deadline_month})");
                $reset++;
                continue;
            }

            try {
                $billingService->clearTemporaryDeadline($client, 'scheduler');
                $reset++;
                $this->line("Reset: {$client->client_id} ({$client->name})");
            } catch (\Exception $e) {
                $this->error("Failed to reset {$client->client_id}: " . $e->getMessage());
                Log::error("Temp deadline reset failed for client {$client->id}: " . $e->getMessage());
            }
        }

        $this->info("Reset {$reset} temporary deadlines");
        Log::info("Temp deadline reset completed", ['reset' => $reset, 'dry_run' => $dryRun]);

        return 0;
    }
}
