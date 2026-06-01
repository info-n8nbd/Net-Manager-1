<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\SyncService;
use Illuminate\Support\Facades\Log;

class SyncPaymentReceived
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function handle(PaymentReceived $event): void
    {
        try {
            $this->syncService->logPaymentReceived(
                $event->payment,
                $event->source,
                $event->triggeredBy
            );

            Log::info("PaymentReceived event synced", [
                'payment_id' => $event->payment->id,
                'client_id' => $event->client->id,
                'amount' => $event->payment->amount,
                'source' => $event->source
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync PaymentReceived event", [
                'payment_id' => $event->payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
