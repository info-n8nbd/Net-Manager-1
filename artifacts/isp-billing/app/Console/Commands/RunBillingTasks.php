<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class RunBillingTasks extends Command
{
    protected $signature = 'billing:run-tasks';
    protected $description = 'Run daily billing tasks: auto-disconnect, auto-restore, integrity check';

    public function handle(BillingService $billingService): int
    {
        $this->info("Running daily billing tasks...");
        
        $this->info("1. Auto-disconnecting overdue clients...");
        $disconnect = $billingService->autoDisconnectOverdueClients();
        $this->info("   - Clients disconnected: {$disconnect['disconnected']}");
        if (isset($disconnect['mikrotik_failed']) && $disconnect['mikrotik_failed'] > 0) {
            $this->warn("   - MikroTik sync failed: {$disconnect['mikrotik_failed']}");
        }
        
        $this->info("2. Auto-restoring paid clients...");
        $restore = $billingService->autoRestoreClients();
        $this->info("   - Clients restored: {$restore['restored']}");
        if (isset($restore['mikrotik_failed']) && $restore['mikrotik_failed'] > 0) {
            $this->warn("   - MikroTik sync failed: {$restore['mikrotik_failed']}");
        }
        
        $this->info("3. Running integrity check...");
        $mismatches = $billingService->runDailyIntegrityCheck();
        $this->info("   - Mismatches found and corrected: " . count($mismatches));
        
        $this->info("Daily billing tasks completed.");

        return Command::SUCCESS;
    }
}
