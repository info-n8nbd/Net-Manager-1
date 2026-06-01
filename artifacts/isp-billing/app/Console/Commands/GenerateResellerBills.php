<?php

namespace App\Console\Commands;

use App\Jobs\GenerateResellerBillsJob;
use Illuminate\Console\Command;

class GenerateResellerBills extends Command
{
    protected $signature = 'generate-reseller-bills';
    protected $description = 'Generate monthly bills for all active resellers';

    public function handle(): int
    {
        $this->info('Starting reseller bill generation...');

        try {
            $job = app(GenerateResellerBillsJob::class);
            $job->handle();
            $this->info('Reseller bill generation completed successfully.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
