<?php

namespace App\Listeners;

use App\Events\BillGenerated;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SmsBillGenerated
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(BillGenerated $event): void
    {
        try {
            if ($event->suppressSms) {
                Log::info("SMS skipped for BillGenerated (suppressed for new client)", [
                    'bill_id' => $event->bill->id,
                    'client_id' => $event->client->id,
                ]);
                return;
            }

            $client = $event->client;
            $bill = $event->bill;

            if (!$client->phone) {
                return;
            }

            $alreadySent = SmsLog::where('client_id', $client->id)
                ->where('event_type', SmsService::EVENT_MONTHLY_BILL)
                ->where('status', 'sent')
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();

            if ($alreadySent) {
                Log::info("SMS skipped for BillGenerated (duplicate detected)", [
                    'bill_id' => $bill->id,
                    'client_id' => $client->id,
                ]);
                return;
            }

            $this->smsService->sendBillNotification($client, $bill);
        } catch (\Exception $e) {
            Log::error("SMS listener failed for BillGenerated: " . $e->getMessage(), [
                'bill_id' => $event->bill->id,
                'client_id' => $event->client->id,
            ]);
        }
    }
}
