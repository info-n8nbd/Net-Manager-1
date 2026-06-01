<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Payment;
use App\Models\WebhookPaymentLog;
use App\Models\SystemSetting;
use App\Events\PaymentReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookPaymentService
{
    protected BillingService $billingService;
    protected PaymentGatewayService $gatewayService;
    protected SmsService $smsService;

    public function __construct(
        BillingService $billingService,
        PaymentGatewayService $gatewayService,
        SmsService $smsService
    ) {
        $this->billingService = $billingService;
        $this->gatewayService = $gatewayService;
        $this->smsService = $smsService;
    }

    public function processWebhookPayment(string $gateway, array $payload, ?string $ipAddress = null): array
    {
        $extracted = $this->extractPaymentData($gateway, $payload);

        if (!$extracted['trx_id'] || !$extracted['amount']) {
            return $this->logAndFail($gateway, $payload, $ipAddress, $extracted, 'Missing transaction ID or amount');
        }

        if ($extracted['amount'] <= 0 || $extracted['amount'] > 999999) {
            return $this->logAndFail($gateway, $payload, $ipAddress, $extracted, 'Invalid payment amount: ' . $extracted['amount']);
        }

        $existingPayment = Payment::where('transaction_id', $extracted['trx_id'])->first();
        if ($existingPayment) {
            return $this->logAndFail($gateway, $payload, $ipAddress, $extracted, 'Duplicate - Payment with this TrxID already exists', 'duplicate');
        }

        $existing = WebhookPaymentLog::where('gateway', $gateway)
            ->where('trx_id', $extracted['trx_id'])
            ->whereIn('status', ['matched', 'applied'])
            ->first();

        if ($existing) {
            WebhookPaymentLog::create([
                'gateway' => $gateway,
                'trx_id' => $extracted['trx_id'],
                'sender_number' => $extracted['sender'],
                'reference' => $extracted['reference'],
                'amount' => $extracted['amount'],
                'client_id' => $existing->client_id,
                'company_id' => $existing->company_id,
                'status' => 'duplicate',
                'failure_reason' => 'Duplicate TrxID - already processed',
                'raw_payload' => $payload,
                'ip_address' => $ipAddress,
            ]);
            return ['success' => false, 'message' => 'Duplicate transaction'];
        }

        $reference = $extracted['reference'];
        if (!$reference) {
            return $this->logAndFail($gateway, $payload, $ipAddress, $extracted, 'No reference/PPPoE ID found in payment');
        }

        $reference = trim($reference);

        $client = Client::where('username', $reference)->first();

        if (!$client && is_numeric($reference)) {
            $client = Client::where('client_id', $reference)->first();
        }

        if (!$client) {
            $client = Client::whereRaw('LOWER(username) = ?', [strtolower($reference)])->first();
        }

        if (!$client) {
            return $this->logAndFail($gateway, $payload, $ipAddress, $extracted, "Client not found for reference: {$reference}");
        }

        return DB::transaction(function () use ($gateway, $payload, $ipAddress, $extracted, $client) {
            $log = WebhookPaymentLog::create([
                'gateway' => $gateway,
                'trx_id' => $extracted['trx_id'],
                'sender_number' => $extracted['sender'],
                'reference' => $extracted['reference'],
                'amount' => $extracted['amount'],
                'client_id' => $client->id,
                'company_id' => $client->company_id,
                'status' => 'matched',
                'raw_payload' => $payload,
                'ip_address' => $ipAddress,
            ]);

            $paymentMethodId = $this->gatewayService->getPaymentMethodIdPublic($gateway);

            $payment = Payment::create([
                'client_id' => $client->id,
                'payment_method_id' => $paymentMethodId,
                'amount' => $extracted['amount'],
                'payment_date' => now(),
                'transaction_id' => $extracted['trx_id'],
                'status' => 'approved',
                'source' => 'online',
                'notes' => 'Auto-verified via ' . strtoupper($gateway) . ' webhook. Sender: ' . ($extracted['sender'] ?? 'N/A'),
                'company_id' => $client->company_id,
            ]);

            $log->update([
                'payment_id' => $payment->id,
                'status' => 'applied',
            ]);

            event(new PaymentReceived($payment, 'online', null));

            $this->billingService->reapplyAllPaymentsToBills($client->fresh());
            $this->billingService->recalculateClientBalance($client->fresh());

            $client->refresh();

            if ($client->status === 'inactive' && $client->due_amount <= 0) {
                try {
                    $mikrotikService = app(MikroTikService::class);
                    $result = $mikrotikService->restoreClient($client, 'online_payment', null, true);
                    if ($result['success']) {
                        Log::info("Webhook auto-reconnected client {$client->client_id} after {$gateway} payment");
                    } else {
                        $client->update([
                            'status' => 'active',
                            'last_active_date' => now(),
                        ]);
                    }
                } catch (\Exception $e) {
                    $client->update([
                        'status' => 'active',
                        'last_active_date' => now(),
                    ]);
                    Log::warning("Webhook MikroTik reconnect failed: " . $e->getMessage());
                }
            }

            $billing = BillingCalculatorService::calculate($client);

            $this->sendWebhookPaymentSms($client, $gateway, $extracted, $billing);

            try {
                $client->refresh();
                (new \App\Services\TelegramService())->sendWebhookPaymentNotification(
                    $client->client_id,
                    $client->name,
                    $extracted['amount'],
                    strtoupper($gateway),
                    $extracted['trx_id'] ?? 'N/A',
                    $client->username ?? '',
                    (float) ($client->due_amount ?? 0),
                    $client->package?->name ?? ''
                );
            } catch (\Throwable $e) {
            }

            Log::info("Webhook payment processed", [
                'gateway' => $gateway,
                'trx_id' => $extracted['trx_id'],
                'client_id' => $client->client_id,
                'amount' => $extracted['amount'],
                'payment_id' => $payment->id,
            ]);

            return [
                'success' => true,
                'message' => 'Payment verified and applied',
                'client_id' => $client->client_id,
                'payment_id' => $payment->id,
                'due_remaining' => $billing['due'],
            ];
        });
    }

    protected function extractPaymentData(string $gateway, array $payload): array
    {
        return match($gateway) {
            'bkash' => [
                'trx_id' => $payload['trxID'] ?? $payload['transactionId'] ?? $payload['trx_id'] ?? null,
                'amount' => (float) ($payload['amount'] ?? $payload['Amount'] ?? 0),
                'sender' => $payload['customerMsisdn'] ?? $payload['sender'] ?? $payload['senderNumber'] ?? null,
                'reference' => $payload['accountReference'] ?? $payload['reference'] ?? $payload['payerReference'] ?? $payload['merchantInvoiceNumber'] ?? null,
            ],
            'nagad' => [
                'trx_id' => $payload['trx_id'] ?? $payload['payment_ref_id'] ?? $payload['transactionId'] ?? null,
                'amount' => (float) ($payload['amount'] ?? $payload['Amount'] ?? 0),
                'sender' => $payload['sender'] ?? $payload['customer_account'] ?? null,
                'reference' => $payload['order_id'] ?? $payload['reference'] ?? $payload['merchant_order_id'] ?? null,
            ],
            'rocket' => [
                'trx_id' => $payload['trx_id'] ?? $payload['transactionId'] ?? null,
                'amount' => (float) ($payload['amount'] ?? $payload['Amount'] ?? 0),
                'sender' => $payload['sender'] ?? $payload['customer_account'] ?? null,
                'reference' => $payload['reference'] ?? $payload['order_id'] ?? null,
            ],
            default => [
                'trx_id' => $payload['trx_id'] ?? $payload['transaction_id'] ?? null,
                'amount' => (float) ($payload['amount'] ?? 0),
                'sender' => $payload['sender'] ?? null,
                'reference' => $payload['reference'] ?? null,
            ],
        };
    }

    protected function logAndFail(string $gateway, array $payload, ?string $ipAddress, array $extracted, string $reason, string $status = 'failed'): array
    {
        WebhookPaymentLog::create([
            'gateway' => $gateway,
            'trx_id' => $extracted['trx_id'] ?? null,
            'sender_number' => $extracted['sender'] ?? null,
            'reference' => $extracted['reference'] ?? null,
            'amount' => $extracted['amount'] ?? 0,
            'status' => $status,
            'failure_reason' => $reason,
            'raw_payload' => $payload,
            'ip_address' => $ipAddress,
        ]);

        Log::warning("Webhook payment failed", [
            'gateway' => $gateway,
            'reason' => $reason,
            'trx_id' => $extracted['trx_id'] ?? null,
            'reference' => $extracted['reference'] ?? null,
        ]);

        return ['success' => false, 'message' => $reason];
    }

    protected function sendWebhookPaymentSms(Client $client, string $gateway, array $extracted, array $billing): void
    {
        if (!$client->phone) return;

        $gwName = strtoupper($gateway);
        $amount = number_format($extracted['amount'], 2);
        $trxId = $extracted['trx_id'] ?? 'N/A';
        $due = number_format($billing['due'], 2);

        $message = "Dear {$client->name}, your {$gwName} payment of BDT {$amount} has been received successfully. TrxID: {$trxId}. Due: BDT {$due}. Thank you!";

        try {
            $this->smsService->sendSms($client->phone, $message, 'payment_confirmation', $client->id);
        } catch (\Exception $e) {
            Log::warning("Webhook SMS failed for client {$client->client_id}: " . $e->getMessage());
        }
    }

    public function verifyWebhookSecret(string $gateway, ?string $providedSecret): bool
    {
        $savedSecret = SystemSetting::get($gateway . '_webhook_secret', 'payment_webhook');
        if (!$savedSecret) return true;
        return $providedSecret === $savedSecret;
    }

    public function isWebhookEnabled(string $gateway): bool
    {
        $enabled = SystemSetting::get($gateway . '_webhook_enabled', 'payment_webhook');
        return $enabled === '1';
    }
}
