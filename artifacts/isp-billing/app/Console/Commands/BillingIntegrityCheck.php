<?php

namespace App\Console\Commands;

use App\Services\BillingIntegrityService;
use Illuminate\Console\Command;

class BillingIntegrityCheck extends Command
{
    protected $signature = 'billing:integrity-check 
                            {--no-auto-correct : Disable automatic correction of safe issues}
                            {--quick : Run quick health check only}';
    
    protected $description = 'Run billing system integrity check to detect and optionally correct discrepancies';

    public function handle(BillingIntegrityService $service): int
    {
        $this->info('Starting Billing Integrity Check...');
        $this->newLine();
        
        try {
            if ($this->option('quick')) {
                $result = $service->runQuickHealthCheck();
                $this->displayQuickResults($result);
            } else {
                $autoCorrect = !$this->option('no-auto-correct');
                $result = $service->runFullIntegrityCheck($autoCorrect);
                $this->displayFullResults($result);
            }
            
            return $result['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Integrity check failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
    
    protected function displayQuickResults(array $result): void
    {
        $status = $result['status'] === 'healthy' ? '<fg=green>HEALTHY</>' : '<fg=red>ISSUES DETECTED</>';
        
        $this->info("Status: {$status}");
        $this->table(
            ['Check', 'Count'],
            [
                ['Client Balance Mismatches', $result['client_balance_mismatches']],
                ['Duplicate Bills', $result['duplicate_bills']],
            ]
        );
    }
    
    protected function displayFullResults(array $result): void
    {
        $status = $result['status'] === 'healthy' ? '<fg=green>HEALTHY</>' : '<fg=yellow>ISSUES FOUND</>';
        
        $this->info("Status: {$status}");
        $this->newLine();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Duration', $result['duration_seconds'] . 's'],
                ['Clients Checked', $result['clients_checked']],
                ['Bills Checked', $result['bills_checked']],
                ['Issues Found', $result['issues_found']],
                ['Auto-Corrected', $result['auto_corrected']],
                ['Requires Review', $result['requires_review']],
            ]
        );
        
        if (count($result['auto_corrected_details']) > 0) {
            $this->newLine();
            $this->info('Auto-Corrected Issues:');
            foreach ($result['auto_corrected_details'] as $issue) {
                $this->line("  - [{$issue['type']}] {$issue['correction']}");
            }
        }
        
        if (count($result['requires_review_details']) > 0) {
            $this->newLine();
            $this->warn('Issues Requiring Super Admin Review:');
            foreach ($result['requires_review_details'] as $issue) {
                $severity = $issue['severity'] ?? 'medium';
                $color = $severity === 'critical' ? 'red' : ($severity === 'high' ? 'yellow' : 'white');
                $this->line("  <fg={$color}>- [{$issue['type']}] Client ID: " . ($issue['client_id'] ?? 'N/A') . "</>");
            }
        }
    }
}
