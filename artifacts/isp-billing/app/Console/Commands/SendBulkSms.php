<?php
/**
 * Bulk SMS Command
 * 
 * Send SMS to multiple clients based on filters
 * 
 * Usage:
 * php artisan sms:bulk --zone=1 --message="Your custom message"
 * php artisan sms:bulk --status=active --message="Service update"
 * php artisan sms:bulk --due=true --message="Please pay your due amount"
 */

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\SystemSetting;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBulkSms extends Command
{
    protected $signature = 'sms:bulk 
                            {--zone= : Filter by zone ID}
                            {--area= : Filter by area ID}
                            {--status= : Filter by status (active, inactive)}
                            {--due : Only clients with due amount}
                            {--package= : Filter by package ID}
                            {--message= : SMS message to send}
                            {--template= : Use saved template by name}
                            {--dry-run : Show what would be sent}
                            {--limit= : Limit number of recipients}';

    protected $description = 'Send bulk SMS to clients based on filters';

    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    public function handle(): int
    {
        $message = $this->option('message');
        $templateName = $this->option('template');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        if (!$message && !$templateName) {
            $this->error('Please provide --message or --template');
            return 1;
        }

        // Check if SMS is enabled
        if (!SystemSetting::get('sms_global_enabled', false)) {
            $this->warn('SMS is disabled globally. Enable it in Admin > Settings > SMS.');
            return 0;
        }

        // Build query
        $query = Client::whereNotNull('phone');

        if ($this->option('zone')) {
            $query->where('zone_id', $this->option('zone'));
        }

        if ($this->option('area')) {
            $query->where('area_id', $this->option('area'));
        }

        if ($this->option('status')) {
            $query->where('status', $this->option('status'));
        }

        if ($this->option('due')) {
            $query->where('due_amount', '>', 0);
        }

        if ($this->option('package')) {
            $query->where('package_id', $this->option('package'));
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $clients = $query->get();

        $this->info("Found {$clients->count()} clients matching filters");

        if ($clients->isEmpty()) {
            $this->warn('No clients found matching the criteria');
            return 0;
        }

        if (!$this->confirm("Send SMS to {$clients->count()} clients?")) {
            return 0;
        }

        $sent = 0;
        $failed = 0;
        $companyName = SystemSetting::get('company_name', 'ISP Company');

        $progressBar = $this->output->createProgressBar($clients->count());
        $progressBar->start();

        foreach ($clients as $client) {
            $personalizedMessage = str_replace(
                ['{name}', '{client_id}', '{due_amount}', '{company}', '{phone}'],
                [$client->name, $client->client_id, number_format($client->due_amount ?? 0, 0), $companyName, $client->phone],
                $message
            );

            if ($dryRun) {
                $progressBar->advance();
                $sent++;
                continue;
            }

            try {
                $result = $this->smsService->sendSms($client->phone, $personalizedMessage);
                
                if ($result['success']) {
                    $sent++;
                } else {
                    $failed++;
                    Log::warning("Bulk SMS failed for {$client->id}: {$result['message']}");
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error("Bulk SMS error for {$client->id}: " . $e->getMessage());
            }

            $progressBar->advance();

            // Rate limiting - wait 100ms between messages
            usleep(100000);
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Bulk SMS Complete:");
        $this->info("  Sent: {$sent}");
        $this->info("  Failed: {$failed}");

        if ($dryRun) {
            $this->warn('This was a dry run. No SMS was actually sent.');
        }

        return 0;
    }
}
