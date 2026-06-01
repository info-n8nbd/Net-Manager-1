<?php

namespace App\Listeners;

use App\Events\AdvanceUsed;
use App\Services\SyncService;
use Illuminate\Support\Facades\Log;

class SyncAdvanceUsed
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function handle(AdvanceUsed $event): void
    {
        try {
            $this->syncService->logAdvanceUsed(
                $event->client,
                $event->amountUsed,
                $event->bill,
                $event->source
            );

            Log::info("AdvanceUsed event synced", [
                'client_id' => $event->client->id,
                'amount_used' => $event->amountUsed,
                'bill_id' => $event->bill?->id,
                'source' => $event->source
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync AdvanceUsed event", [
                'client_id' => $event->client->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
