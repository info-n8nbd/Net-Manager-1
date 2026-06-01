<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use App\Models\SmsLog;

class TestAllSmsTemplates extends Command
{
    protected $signature = 'sms:test-all-templates {phone : Phone number to send test SMS to}';
    protected $description = 'Send all active SMS templates as test messages to a specific phone number';

    public function handle()
    {
        $phone = $this->argument('phone');
        $this->info("Sending all active SMS templates to: {$phone}");
        $this->newLine();

        $smsService = app(SmsService::class);

        $templates = SmsTemplate::where('is_active', true)
            ->orderBy('code')
            ->get();

        if ($templates->isEmpty()) {
            $this->error('No active SMS templates found!');
            return 1;
        }

        $this->info("Found {$templates->count()} active templates.");
        $this->newLine();

        $sampleVars = [
            '{client_name}' => 'Test Customer',
            '{pppoe_id}' => 'TEST001',
            '{pppoe_password}' => 'test@pass123',
            '{username}' => 'TEST001',
            '{client_id}' => '1001',
            '{client_code}' => '1001',
            '{phone}' => $phone,
            '{package}' => 'Gold 10Mbps',
            '{package_name}' => 'Gold 10Mbps',
            '{monthly_fee}' => '500.00',
            '{amount}' => '500.00',
            '{bill_amount}' => '500.00',
            '{due_amount}' => '250.00',
            '{advance_balance}' => '1,000.00',
            '{advance_used}' => '500.00',
            '{credit}' => '1,000.00',
            '{due_date}' => date('d-m-y', strtotime('+10 days')),
            '{bill_month}' => date('F Y'),
            '{month}' => date('F'),
            '{bill_no}' => 'BILL-' . date('Ym') . '-0001',
            '{payment_no}' => 'PAY-' . date('Ymd') . '-0001',
            '{payment_link}' => 'https://example.com/pay/test123',
            '{balance}' => '250.00',
            '{company_name}' => \App\Models\SystemSetting::get('company_name', 'SOFTYNEX'),
            '{password}' => 'test@pass123',
            '{reason}' => 'Due payment overdue',
            '{ticket_no}' => 'TKT-00123',
            '{status}' => 'Active',
            '{corrections}' => 'Please update your NID number',
        ];

        $sent = 0;
        $failed = 0;

        foreach ($templates as $index => $template) {
            $num = $index + 1;
            $this->info("[{$num}/{$templates->count()}] Sending: {$template->name} (code: {$template->code})");

            $message = str_replace(
                array_keys($sampleVars),
                array_values($sampleVars),
                $template->content
            );

            $message = "[TEST #{$num}] " . $message;

            $this->line("  Message: " . substr($message, 0, 120) . (strlen($message) > 120 ? '...' : ''));

            try {
                $response = $smsService->sendWithPermissionCheck(
                    $phone,
                    $message,
                    $template->code,
                    SmsService::RECIPIENT_CLIENT,
                    null,
                    null,
                    true
                );

                if ($response && $response->status === 'sent') {
                    $this->info("  ✓ SENT successfully (Log ID: {$response->id})");
                    $sent++;
                } elseif ($response && $response->status === 'skipped') {
                    $this->warn("  ⚠ SKIPPED: {$response->error_message}");
                    $failed++;
                } else {
                    $statusMsg = $response ? "Status: {$response->status}" : 'No response';
                    $errorMsg = $response && $response->error_message ? " - {$response->error_message}" : '';
                    $this->warn("  ⚠ {$statusMsg}{$errorMsg}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("  ✗ ERROR: " . $e->getMessage());
                $failed++;
            }

            if ($index < $templates->count() - 1) {
                sleep(2);
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info("=== Test Complete ===");
        $this->info("Total: {$templates->count()} | Sent: {$sent} | Failed/Skipped: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
