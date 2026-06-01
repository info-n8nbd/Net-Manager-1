<?php

namespace App\Services;

use App\Models\PaymentLink;
use App\Models\SystemSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentLinkService
{
    public function getExpiryHours(): int
    {
        return (int) SystemSetting::get('payment_link_expiry_hours', '72');
    }

    public function getBaseUrl(): string
    {
        $baseUrl = SystemSetting::get('payment_link_base_url', '');

        if (!empty($baseUrl)) {
            return rtrim($baseUrl, '/');
        }

        $domain = env('REPLIT_DOMAINS', env('REPLIT_DEV_DOMAIN', ''));
        if ($domain) {
            return 'https://' . $domain;
        }

        return rtrim(config('app.url', 'http://localhost'), '/');
    }

    public function getPaymentUrl(PaymentLink $link): string
    {
        return $this->getBaseUrl() . '/pay/' . $link->token;
    }

    public function generateLink(array $data): PaymentLink
    {
        $expiryHours = $this->getExpiryHours();

        return PaymentLink::create([
            'token' => Str::random(32),
            'client_id' => $data['client_id'],
            'bill_id' => $data['bill_id'] ?? null,
            'amount' => $data['amount'],
            'description' => $data['description'] ?? 'Payment',
            'status' => 'active',
            'expires_at' => $data['expires_at'] ?? now()->addHours($expiryHours),
            'created_by' => auth()->id(),
            'sent_via' => $data['sent_via'] ?? null,
        ]);
    }

    public function getByToken(string $token): ?PaymentLink
    {
        $link = PaymentLink::where('token', $token)->first();
        if ($link) {
            $link->increment('view_count');
        }
        return $link;
    }

    public function processPayment(string $token, array $paymentData): PaymentLink
    {
        return DB::transaction(function () use ($token, $paymentData) {
            $link = PaymentLink::where('token', $token)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->firstOrFail();

            $link->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $paymentData['payment_method'] ?? 'online',
                'payment_id' => $paymentData['payment_id'] ?? null,
            ]);

            return $link->fresh();
        });
    }

    public function sendViaSms(PaymentLink $link): bool
    {
        try {
            $client = $link->client;
            $smsService = app(SmsService::class);
            $url = $this->getPaymentUrl($link);
            $amount = number_format($link->amount, 0);
            $clientId = $client->username ?? $client->client_id ?? '';
            $message = "ID: {$clientId}, due BDT {$amount}. Pay now: {$url} - " . SystemSetting::get('company_name', 'SOFTYNEX');
            $smsService->sendSms($client->phone, $message);
            $link->update(['sent_via' => 'sms']);
            return true;
        } catch (\Exception $e) {
            Log::error("Payment link SMS failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendViaWhatsApp(PaymentLink $link): bool
    {
        try {
            $client = $link->client;
            $whatsAppService = app(WhatsAppService::class);
            $url = $this->getPaymentUrl($link);
            $amount = number_format($link->amount, 0);
            $message = "Payment of BDT {$amount} is due. Pay now: {$url}";
            $whatsAppService->sendMessage($client->phone, $message);
            $link->update(['sent_via' => 'whatsapp']);
            return true;
        } catch (\Exception $e) {
            Log::error("Payment link WhatsApp failed: " . $e->getMessage());
            return false;
        }
    }

    public function expireLinks(): int
    {
        return PaymentLink::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    public function cancelLink(int $linkId): bool
    {
        return PaymentLink::where('id', $linkId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']) > 0;
    }
}
