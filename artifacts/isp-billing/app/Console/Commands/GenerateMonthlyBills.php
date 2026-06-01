<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class GenerateMonthlyBills extends Command
{
    protected $signature = 'billing:generate-monthly {--month= : Month to generate bills for} {--year= : Year to generate bills for}';
    protected $description = 'Generate monthly bills for all active clients';

    public function handle(BillingService $billingService): int
    {
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;

        $this->info("Generating bills for {$month}/{$year}...");

        $result = $billingService->generateMonthlyBillsForAllClients((int)$month, (int)$year);

        $this->info("Bills generated: {$result['generated']}");
        $this->info("Bills skipped (already exist): {$result['skipped']}");
        
        if (count($result['errors']) > 0) {
            $this->error("Errors: " . count($result['errors']));
            foreach ($result['errors'] as $error) {
                $this->warn("  - Client #{$error['client_id']} ({$error['client_name']}): {$error['error']}");
            }
        }

        return Command::SUCCESS;
    }
}
