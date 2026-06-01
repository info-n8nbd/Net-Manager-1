<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SyncService;
use App\Models\Client;

class VerifyDataSync extends Command
{
    protected $signature = 'sync:verify {--limit=100 : Number of clients to check} {--client= : Specific client ID to check}';
    protected $description = 'Verify and repair data synchronization across all panels';

    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle(): int
    {
        $this->info('Starting data synchronization verification...');

        if ($clientId = $this->option('client')) {
            $client = Client::find($clientId);
            
            if (!$client) {
                $this->error("Client not found: {$clientId}");
                return 1;
            }

            $result = $this->syncService->verifySyncIntegrity($client);
            $this->displayClientResult($result);
            
            return 0;
        }

        $limit = (int) $this->option('limit');
        $this->info("Checking up to {$limit} clients...");

        $results = $this->syncService->runFullSyncCheck($limit);

        $this->newLine();
        $this->info("=== SYNC VERIFICATION SUMMARY ===");
        $this->info("Clients checked: {$results['checked']}");
        $this->info("Issues found: {$results['issues_found']}");
        $this->info("Total fixed: {$results['total_fixed']}");

        if (count($results['clients_with_issues']) > 0) {
            $this->newLine();
            $this->warn("Clients with issues:");
            foreach ($results['clients_with_issues'] as $clientResult) {
                $this->displayClientResult($clientResult);
            }
        } else {
            $this->newLine();
            $this->info("All checked clients are in sync!");
        }

        return 0;
    }

    protected function displayClientResult(array $result): void
    {
        $this->line("  Client ID: {$result['client_id']}");
        $this->line("  Issues: {$result['issues_found']}");
        
        if (!empty($result['issues'])) {
            foreach ($result['issues'] as $issue) {
                $this->warn("    - {$issue['type']}");
            }
        }
        
        if (!empty($result['fixed'])) {
            $this->info("    Fixed: " . implode(', ', $result['fixed']));
        }
        
        $this->newLine();
    }
}
