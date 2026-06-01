<?php

namespace App\Listeners;

use App\Events\AdvanceReceived;
use App\Services\SyncService;
use Illuminate\Support\Facades\Log;

class SyncAdvanceReceived
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function handle(AdvanceReceived $event): void
    {
        try {
            $this->syncService->logAdvanceReceived(
                $event->advancePayment,
                $event->source,
                $event->triggeredBy
            );

            Log::info("AdvanceReceived event synced", [
                'advance_id' => $event->advancePayment->id,
                'client_id' => $event->client->id,
                'amount' => $event->amount,
                'source' => $event->source
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync AdvanceReceived event", [
                'advance_id' => $event->advancePayment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
