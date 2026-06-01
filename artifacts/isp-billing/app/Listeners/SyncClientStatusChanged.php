<?php

namespace App\Listeners;

use App\Events\ClientStatusChanged;
use App\Services\SyncService;
use Illuminate\Support\Facades\Log;

class SyncClientStatusChanged
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function handle(ClientStatusChanged $event): void
    {
        try {
            $this->syncService->logStatusChange(
                $event->client,
                $event->oldStatus,
                $event->newStatus,
                $event->source,
                $event->reason,
                $event->triggeredBy
            );

            Log::info("ClientStatusChanged event synced", [
                'client_id' => $event->client->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'source' => $event->source
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync ClientStatusChanged event", [
                'client_id' => $event->client->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
