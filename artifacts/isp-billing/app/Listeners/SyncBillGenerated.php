<?php

namespace App\Listeners;

use App\Events\BillGenerated;
use App\Services\SyncService;
use Illuminate\Support\Facades\Log;

class SyncBillGenerated
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function handle(BillGenerated $event): void
    {
        try {
            $this->syncService->logBillGenerated(
                $event->bill,
                $event->source,
                $event->triggeredBy
            );

            Log::info("BillGenerated event synced", [
                'bill_id' => $event->bill->id,
                'client_id' => $event->client->id,
                'source' => $event->source
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync BillGenerated event", [
                'bill_id' => $event->bill->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
