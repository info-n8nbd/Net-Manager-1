<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeviceAlertService
{
    public function notifyDeviceDown(
        string $deviceName,
        string $deviceType,
        string $ip,
        int $affectedClients = 0,
        string $location = ''
    ): void {
        $email = SystemSetting::get('device_alert_email');
        $phone = SystemSetting::get('device_alert_phone');

        if (!$email && !$phone) {
            return;
        }

        $time = now()->timezone('Asia/Dhaka')->format('d-m-Y h:i A');
        $companyName = SystemSetting::get('company_name', 'SOFTYNEX');

        if ($email) {
            $subject = "[ALERT] {$deviceType} Down: {$deviceName}";
            $body  = "DEVICE DOWN ALERT\n";
            $body .= str_repeat('-', 40) . "\n";
            $body .= "Device Name : {$deviceName}\n";
            $body .= "Device Type : {$deviceType}\n";
            $body .= "IP Address  : {$ip}\n";
            if ($location) $body .= "Location    : {$location}\n";
            if ($affectedClients > 0) $body .= "Affected    : {$affectedClients} clients\n";
            $body .= "Time        : {$time}\n";
            $body .= str_repeat('-', 40) . "\n";
            $body .= "Please check the device immediately.\n\n-- {$companyName}";
            $this->sendEmail($email, $subject, $body);
        }

        if ($phone) {
            $sms = "{$companyName}: ALERT! {$deviceType} DOWN - {$deviceName} ({$ip})";
            if ($affectedClients > 0) $sms .= " ({$affectedClients} clients affected)";
            $sms .= ". Time: {$time}";
            $this->sendSmsViaGateway($phone, $sms, 'device_down');
        }
    }

    public function notifyDeviceUp(
        string $deviceName,
        string $deviceType,
        string $ip,
        int $affectedClients = 0,
        string $location = '',
        string $downtime = ''
    ): void {
        $email = SystemSetting::get('device_alert_email');
        $phone = SystemSetting::get('device_alert_phone');

        if (!$email && !$phone) {
            return;
        }

        $time = now()->timezone('Asia/Dhaka')->format('d-m-Y h:i A');
        $companyName = SystemSetting::get('company_name', 'SOFTYNEX');

        if ($email) {
            $subject = "[RESOLVED] {$deviceType} Back Online: {$deviceName}";
            $body  = "DEVICE RESTORED - BACK ONLINE\n";
            $body .= str_repeat('-', 40) . "\n";
            $body .= "Device Name : {$deviceName}\n";
            $body .= "Device Type : {$deviceType}\n";
            $body .= "IP Address  : {$ip}\n";
            if ($location) $body .= "Location    : {$location}\n";
            if ($downtime) $body .= "Downtime    : {$downtime}\n";
            if ($affectedClients > 0) $body .= "Affected    : {$affectedClients} clients\n";
            $body .= "Restored At : {$time}\n";
            $body .= str_repeat('-', 40) . "\n";
            $body .= "-- {$companyName}";
            $this->sendEmail($email, $subject, $body);
        }

        if ($phone) {
            $sms = "{$companyName}: RESTORED! {$deviceType} ONLINE - {$deviceName} ({$ip})";
            if ($downtime) $sms .= " Downtime: {$downtime}.";
            $sms .= " Time: {$time}";
            $this->sendSmsViaGateway($phone, $sms, 'device_up');
        }
    }

    protected function sendEmail(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, function ($msg) use ($to, $subject) {
                $msg->to($to)->subject($subject);
            });
            Log::info("[DeviceAlert] Email sent to {$to}: {$subject}");
        } catch (\Throwable $e) {
            Log::error('[DeviceAlert] Email failed to ' . $to . ': ' . $e->getMessage());
        }
    }

    protected function sendSmsViaGateway(string $phone, string $message, string $type): void
    {
        try {
            $apiKey   = SystemSetting::get('sms_api_key');
            $senderId = SystemSetting::get('sms_sender_id', 'ALERT');
            $provider = SystemSetting::get('sms_provider', 'bulksmspro');

            if (!$apiKey) {
                Log::warning('[DeviceAlert] SMS skipped - no SMS API key configured');
                return;
            }

            $response = match ($provider) {
                'greenweb' => Http::timeout(15)->get('http://api.greenweb.com.bd/api.php', [
                    'token'   => $apiKey,
                    'to'      => $phone,
                    'message' => $message,
                ]),
                'bulksms_bd' => Http::timeout(15)->asForm()->post('https://bulksmsbd.net/api/smsapi', [
                    'api_key' => $apiKey,
                    'type'    => 'text',
                    'number'  => $phone,
                    'message' => $message,
                ]),
                default => Http::withOptions(['verify' => false])->timeout(15)->get('https://bulksmspro.net/api/sms-send', [
                    'api_key'       => $apiKey,
                    'sender_id'     => $senderId,
                    'phone_numbers' => $phone,
                    'message'       => $message,
                    'type'          => 'text',
                ]),
            };

            DB::table('sms_logs')->insert([
                'phone'          => $phone,
                'message'        => $message,
                'type'           => $type,
                'status'         => 'sent',
                'event_type'     => $type,
                'recipient_type' => 'admin',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            Log::info("[DeviceAlert] SMS sent to {$phone} via {$provider}: " . substr($response->body(), 0, 200));
        } catch (\Throwable $e) {
            Log::error('[DeviceAlert] SMS failed to ' . $phone . ': ' . $e->getMessage());
        }
    }
}
