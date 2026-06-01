<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SmsPaymentReceived
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(PaymentReceived $event): void
    {
        try {
            $client = $event->client;
            $payment = $event->payment;

            if (!$client->phone) {
                return;
            }

            $eventType = $event->isAdvance
                ? SmsService::EVENT_ADVANCE_PAYMENT
                : SmsService::EVENT_PAYMENT_CONFIRMATION;

            $alreadySent = SmsLog::where('client_id', $client->id)
                ->where('event_type', $eventType)
                ->where('status', 'sent')
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();

            if ($alreadySent) {
                Log::info("SMS skipped for PaymentReceived (duplicate detected)", [
                    'payment_id' => $payment->id,
                    'client_id' => $client->id,
                    'is_advance' => $event->isAdvance,
                ]);
                return;
            }

            if ($event->isAdvance) {
                $this->smsService->sendAdvancePaymentConfirmation($client, (float)$payment->amount);
            } else {
                $this->smsService->sendPaymentConfirmation($client, (float)$payment->amount, $payment->payment_no ?? '');
            }
        } catch (\Exception $e) {
            Log::error("SMS listener failed for PaymentReceived: " . $e->getMessage(), [
                'payment_id' => $event->payment->id,
                'client_id' => $event->client->id,
            ]);
        }
    }
}
