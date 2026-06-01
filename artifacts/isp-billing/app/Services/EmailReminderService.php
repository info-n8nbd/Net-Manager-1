<?php

namespace App\Services;

use App\Models\Client;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailReminderService
{
    protected ?EmailSetting $settings = null;

    public function getSettings(): ?EmailSetting
    {
        if (!$this->settings) {
            $this->settings = EmailSetting::first();
        }
        return $this->settings;
    }

    protected function configureMailer(): bool
    {
        $s = $this->getSettings();
        if (!$s || !$s->is_active || empty($s->host) || empty($s->username) || empty($s->password)) {
            return false;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $s->host);
        Config::set('mail.mailers.smtp.port', $s->port);
        Config::set('mail.mailers.smtp.username', $s->username);
        Config::set('mail.mailers.smtp.password', $s->password);
        Config::set('mail.mailers.smtp.encryption', $s->encryption);
        Config::set('mail.from.address', $s->from_address);
        Config::set('mail.from.name', $s->from_name);

        return true;
    }

    public function sendEmail(string $to, string $toName, string $subject, string $body, ?int $clientId = null, string $template = 'custom'): bool
    {
        if (!$this->configureMailer()) {
            $this->log($clientId, $to, $toName, $subject, $template, 'failed', 'Email not configured or inactive');
            return false;
        }

        try {
            Mail::html($body, function ($msg) use ($to, $toName, $subject) {
                $msg->to($to, $toName)->subject($subject);
            });

            $this->log($clientId, $to, $toName, $subject, $template, 'sent');
            return true;
        } catch (\Exception $e) {
            Log::error('Email send failed', ['to' => $to, 'error' => $e->getMessage()]);
            $this->log($clientId, $to, $toName, $subject, $template, 'failed', $e->getMessage());
            return false;
        }
    }

    protected function log(?int $clientId, string $to, string $toName, string $subject, string $template, string $status, ?string $error = null): void
    {
        EmailLog::create([
            'client_id'     => $clientId,
            'to_email'      => $to,
            'to_name'       => $toName,
            'subject'       => $subject,
            'template'      => $template,
            'status'        => $status,
            'error_message' => $error,
            'sent_at'       => $status === 'sent' ? now() : null,
        ]);
    }

    protected function companyName(): string
    {
        return SystemSetting::where('key', 'company_name')->value('value') ?? config('app.name', 'SOFTYNEX');
    }

    protected function companyPhone(): string
    {
        return SystemSetting::where('key', 'company_phone')->value('value') ?? '';
    }

    protected function buildEmailHtml(string $headerBg, string $headerText, string $body): string
    {
        $company = $this->companyName();
        $phone   = $this->companyPhone();

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.08);">
  <tr><td style="background:{$headerBg};padding:28px 30px;text-align:center;">
    <h2 style="margin:0;color:#fff;font-size:22px;">{$company}</h2>
    <p style="margin:6px 0 0;color:rgba(255,255,255,.85);font-size:14px;">{$headerText}</p>
  </td></tr>
  <tr><td style="padding:30px;">{$body}</td></tr>
  <tr><td style="background:#f8f9fa;padding:18px 30px;text-align:center;border-top:1px solid #eee;">
    <p style="margin:0;font-size:12px;color:#888;">{$company} &mdash; {$phone}</p>
    <p style="margin:4px 0 0;font-size:11px;color:#aaa;">This email was sent automatically. Please do not reply directly.</p>
  </td></tr>
</table>
</td></tr>
</table>
</body></html>
HTML;
    }

    public function sendBillReminder(Client $client): bool
    {
        if (empty($client->email)) return false;

        $bill = $client->bills()->where('status', 'unpaid')->orderBy('created_at', 'desc')->first();
        $amount = $bill ? number_format($bill->amount ?? 0) : '0';
        $dueDate = $bill ? Carbon::parse($bill->due_date)->format('d M Y') : '';

        $body = <<<HTML
<p style="color:#333;font-size:16px;">Dear <strong>{$client->name}</strong>,</p>
<p style="color:#555;">Your internet bill payment is now due. Please find your bill details below:</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0;">
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;width:40%;">Client Name</td><td style="padding:12px;border:1px solid #e0e0e0;">{$client->name}</td></tr>
  <tr><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Mobile</td><td style="padding:12px;border:1px solid #e0e0e0;">{$client->phone}</td></tr>
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Due Amount</td><td style="padding:12px;border:1px solid #e0e0e0;color:#dc3545;font-size:18px;font-weight:bold;">৳ {$amount}</td></tr>
  <tr><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Due Date</td><td style="padding:12px;border:1px solid #e0e0e0;">{$dueDate}</td></tr>
</table>
<p style="color:#dc3545;font-weight:bold;">⚠ If payment is not made by the due date, your internet connection may be suspended.</p>
<p style="color:#555;">Please pay your bill promptly to enjoy uninterrupted internet service.</p>
<p style="color:#555;">Thank you.</p>
HTML;

        $html    = $this->buildEmailHtml('#e74c3c', 'Bill Payment Reminder', $body);
        $subject = $this->companyName() . ' - Bill Payment Reminder (Tk ' . $amount . ')';

        return $this->sendEmail($client->email, $client->name, $subject, $html, $client->id, 'bill_reminder');
    }

    public function sendExpiryWarning(Client $client): bool
    {
        if (empty($client->email)) return false;

        $expiry = $client->expire_date ? Carbon::parse($client->expire_date)->format('d M Y') : 'N/A';
        $daysLeft = $client->expire_date ? Carbon::now()->diffInDays(Carbon::parse($client->expire_date), false) : 0;

        $body = <<<HTML
<p style="color:#333;font-size:16px;">Dear <strong>{$client->name}</strong>,</p>
<p style="color:#555;">Your internet package is about to expire. Please see the details below:</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0;">
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;width:40%;">Client Name</td><td style="padding:12px;border:1px solid #e0e0e0;">{$client->name}</td></tr>
  <tr><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Expiry Date</td><td style="padding:12px;border:1px solid #e0e0e0;color:#e67e22;font-weight:bold;">{$expiry}</td></tr>
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Days Remaining</td><td style="padding:12px;border:1px solid #e0e0e0;color:#e67e22;font-size:18px;font-weight:bold;">{$daysLeft} days</td></tr>
</table>
<p style="color:#e67e22;font-weight:bold;">⚠ If not renewed in time, your internet connection will be suspended.</p>
<p style="color:#555;">Please renew your package now to continue enjoying uninterrupted internet service.</p>
<p style="color:#555;">Thank you.</p>
HTML;

        $html    = $this->buildEmailHtml('#e67e22', 'Package Expiry Warning', $body);
        $subject = $this->companyName() . ' - Your package expires in ' . $daysLeft . ' day(s)';

        return $this->sendEmail($client->email, $client->name, $subject, $html, $client->id, 'expiry_warning');
    }

    public function sendPaymentConfirmation(Client $client, float $amount, string $transactionId = ''): bool
    {
        if (empty($client->email)) return false;

        $formatted = number_format($amount);
        $txnRow = $transactionId ? "<tr><td style='padding:12px;border:1px solid #e0e0e0;font-weight:bold;'>Transaction ID</td><td style='padding:12px;border:1px solid #e0e0e0;'>{$transactionId}</td></tr>" : '';

        $body = <<<HTML
<p style="color:#333;font-size:16px;">Dear <strong>{$client->name}</strong>,</p>
<p style="color:#555;">Your payment has been received successfully. Thank you!</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0;">
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;width:40%;">Client Name</td><td style="padding:12px;border:1px solid #e0e0e0;">{$client->name}</td></tr>
  <tr><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Payment Amount</td><td style="padding:12px;border:1px solid #e0e0e0;color:#27ae60;font-size:18px;font-weight:bold;">৳ {$formatted}</td></tr>
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Date</td><td style="padding:12px;border:1px solid #e0e0e0;">{$this->currentDate()}</td></tr>
  {$txnRow}
</table>
<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:6px;padding:15px;margin:20px 0;">
  <p style="margin:0;color:#155724;font-weight:bold;">✅ Your internet connection has been activated.</p>
</div>
<p style="color:#555;">Thank you.</p>
HTML;

        $html    = $this->buildEmailHtml('#27ae60', 'Payment Confirmation', $body);
        $subject = $this->companyName() . ' - Payment Confirmation (Tk ' . $formatted . ')';

        return $this->sendEmail($client->email, $client->name, $subject, $html, $client->id, 'payment_confirmation');
    }

    public function sendSuspensionNotice(Client $client, float $dueAmount): bool
    {
        if (empty($client->email)) return false;

        $formatted = number_format($dueAmount);

        $body = <<<HTML
<p style="color:#333;font-size:16px;">Dear <strong>{$client->name}</strong>,</p>
<p style="color:#555;">Your internet connection has been temporarily suspended due to an outstanding bill.</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0;">
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;width:40%;">Client Name</td><td style="padding:12px;border:1px solid #e0e0e0;">{$client->name}</td></tr>
  <tr><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Due Amount</td><td style="padding:12px;border:1px solid #e0e0e0;color:#dc3545;font-size:18px;font-weight:bold;">৳ {$formatted}</td></tr>
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Suspension Date</td><td style="padding:12px;border:1px solid #e0e0e0;">{$this->currentDate()}</td></tr>
</table>
<div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;padding:15px;margin:20px 0;">
  <p style="margin:0;color:#721c24;font-weight:bold;">🚫 Please pay your bill now to restore your connection.</p>
</div>
<p style="color:#555;">Please contact us promptly and pay your bill to restore your service.</p>
<p style="color:#555;">Thank you.</p>
HTML;

        $html    = $this->buildEmailHtml('#dc3545', 'Connection Suspended Notice', $body);
        $subject = $this->companyName() . ' - Your connection has been suspended';

        return $this->sendEmail($client->email, $client->name, $subject, $html, $client->id, 'suspension_notice');
    }

    public function sendWelcome(Client $client, string $password = ''): bool
    {
        if (empty($client->email)) return false;

        $passwordRow = $password ? "<tr><td style='padding:12px;border:1px solid #e0e0e0;font-weight:bold;'>Password</td><td style='padding:12px;border:1px solid #e0e0e0;'>{$password}</td></tr>" : '';

        $body = <<<HTML
<p style="color:#333;font-size:16px;">Dear <strong>{$client->name}</strong>,</p>
<p style="color:#555;">Welcome to SOFTYNEX! Your internet connection has been activated.</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0;">
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;width:40%;">Client Name</td><td style="padding:12px;border:1px solid #e0e0e0;">{$client->name}</td></tr>
  <tr><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Mobile</td><td style="padding:12px;border:1px solid #e0e0e0;">{$client->phone}</td></tr>
  {$passwordRow}
  <tr style="background:#f8f9fa;"><td style="padding:12px;border:1px solid #e0e0e0;font-weight:bold;">Connection Date</td><td style="padding:12px;border:1px solid #e0e0e0;">{$this->currentDate()}</td></tr>
</table>
<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:6px;padding:15px;margin:20px 0;">
  <p style="margin:0;color:#155724;font-weight:bold;">✅ Your internet connection is now active!</p>
</div>
<p style="color:#555;">Contact us anytime if you need assistance.</p>
<p style="color:#555;">Thank you.</p>
HTML;

        $html    = $this->buildEmailHtml('#3498db', 'Welcome - Internet Connection Activated', $body);
        $subject = $this->companyName() . ' - Welcome! Your internet connection is now active';

        return $this->sendEmail($client->email, $client->name, $subject, $html, $client->id, 'welcome');
    }

    protected function currentDate(): string
    {
        return Carbon::now()->format('d M Y, h:i A');
    }

    public function runBillReminderBatch(): array
    {
        $s = $this->getSettings();
        if (!$s || !$s->is_active || !$s->auto_bill_reminder) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'disabled'];
        }

        $daysBeforeDue = $s->bill_reminder_days_before;
        $targetDate = Carbon::now()->addDays($daysBeforeDue)->toDateString();

        $clients = Client::where('status', 'active')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereHas('bills', function ($q) use ($targetDate) {
                $q->where('status', 'unpaid')->whereDate('due_date', '<=', $targetDate);
            })->get();

        $sent = $failed = 0;
        foreach ($clients as $client) {
            $this->sendBillReminder($client) ? $sent++ : $failed++;
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => $clients->count()];
    }

    public function runExpiryWarningBatch(): array
    {
        $s = $this->getSettings();
        if (!$s || !$s->is_active || !$s->auto_expiry_warning) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'disabled'];
        }

        $days       = $s->expiry_warning_days_before;
        $targetDate = Carbon::now()->addDays($days)->toDateString();

        $clients = Client::where('status', 'active')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereDate('expire_date', '<=', $targetDate)
            ->whereDate('expire_date', '>=', Carbon::now()->toDateString())
            ->get();

        $sent = $failed = 0;
        foreach ($clients as $client) {
            $this->sendExpiryWarning($client) ? $sent++ : $failed++;
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => $clients->count()];
    }
}
