<?php
/**
 * Auto Payment Reminder SMS Command
 * 
 * Sends SMS reminders to clients before their payment due date
 * Run via cron: * * * * * php artisan schedule:run
 * 
 * Schedule in app/Console/Kernel.php:
 * $schedule->command('sms:payment-reminders')->dailyAt('10:00');
 */

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Bill;
use App\Models\SystemSetting;
use App\Services\SmsService;
use App\Events\BillDueReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendPaymentReminders extends Command
{
    protected $signature = 'sms:payment-reminders 
                            {--days=3 : Days before due date to send reminder}
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send payment reminder SMS to clients before due date';

    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    public function handle(): int
    {
        $daysBeforeDue = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Payment Reminder SMS - {$daysBeforeDue} days before due date");

        // Check if SMS is enabled
        if (!SystemSetting::get('sms_global_enabled', false)) {
            $this->warn('SMS is disabled globally. Enable it in Admin > Settings > SMS.');
            return 0;
        }

        // Check if payment reminders are enabled
        if (!SystemSetting::get('sms_payment_reminder_enabled', true)) {
            $this->warn('Payment reminder SMS is disabled.');
            return 0;
        }

        // Get reminder days from settings (can be multiple: 3,1 means 3 days and 1 day before)
        $reminderDays = SystemSetting::get('sms_reminder_days', '3,1');
        $reminderDaysArray = array_map('intval', explode(',', $reminderDays));

        // Find clients with upcoming due dates
        $targetDate = Carbon::today()->addDays($daysBeforeDue);
        
        $clients = Client::with(['package'])
            ->where('status', 'active')
            ->whereNotNull('phone')
            ->get()
            ->filter(function ($client) use ($targetDate) {
                // Calculate due date for this client
                $billingDate = $client->billing_date ?? 1;
                $paymentDeadline = $client->payment_deadline ?? 10;
                
                $dueDate = Carbon::today()
                    ->startOfMonth()
                    ->addDays($billingDate - 1)
                    ->addDays($paymentDeadline);
                
                // If due date is in the past this month, use next month
                if ($dueDate->isPast()) {
                    $dueDate = $dueDate->addMonthNoOverflow();
                }
                
                return $dueDate->isSameDay($targetDate);
            });

        $this->info("Found {$clients->count()} clients with due date in {$daysBeforeDue} days");

        $sent = 0;
        $failed = 0;

        foreach ($clients as $client) {
            // Check if client has unpaid bills
            $unpaidAmount = Bill::where('client_id', $client->id)
                ->where('status', 'unpaid')
                ->sum('total_amount');

            if ($unpaidAmount <= 0) {
                continue;
            }

            $message = $this->buildReminderMessage($client, $unpaidAmount, $daysBeforeDue);

            if ($dryRun) {
                $this->line("[DRY RUN] {$client->phone}: {$message}");
                $sent++;
                continue;
            }

            try {
                event(new BillDueReminder($client, $unpaidAmount, $daysBeforeDue, 'scheduler'));

                $smsLog = $this->smsService->sendWithPermissionCheck(
                    $client->phone,
                    $message,
                    SmsService::EVENT_MONTHLY_BILL,
                    SmsService::RECIPIENT_CLIENT,
                    $client->id,
                    null,
                    true
                );
                
                if ($smsLog && $smsLog->status === 'sent') {
                    $sent++;
                    $this->line("[SENT] {$client->client_id}: {$client->phone}");
                } elseif ($smsLog && $smsLog->skip_reason) {
                    $this->line("[SKIPPED] {$client->client_id}: {$smsLog->skip_reason}");
                } else {
                    $failed++;
                    $this->error("[FAILED] {$client->client_id}: " . ($smsLog->response ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error("SMS reminder failed for {$client->id}: " . $e->getMessage());
                $this->error("[ERROR] {$client->client_id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent: {$sent}, Failed: {$failed}");

        return 0;
    }

    protected function buildReminderMessage(Client $client, float $amount, int $daysLeft): string
    {
        $template = SystemSetting::get('sms_reminder_template', 
            'Dear {name}, your internet bill of Tk {amount} is due in {days} days. Please pay to avoid disconnection. - {company}');

        $companyName = SystemSetting::get('company_name', 'ISP Company');

        return str_replace(
            ['{name}', '{amount}', '{days}', '{company}', '{client_id}'],
            [$client->name, number_format($amount, 0), $daysLeft, $companyName, $client->client_id],
            $template
        );
    }
}
