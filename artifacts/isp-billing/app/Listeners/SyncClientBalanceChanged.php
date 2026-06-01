<?php

namespace App\Listeners;

use App\Events\ClientBalanceChanged;
use App\Services\SyncService;
use Illuminate\Support\Facades\Log;

class SyncClientBalanceChanged
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function handle(ClientBalanceChanged $event): void
    {
        try {
            $description = match($event->changeType) {
                'bill_generated' => "Due increased by ৳" . number_format($event->newDue - $event->previousDue, 2),
                'payment_received' => "Due decreased by ৳" . number_format($event->previousDue - $event->newDue, 2),
                'advance_received' => "Advance increased by ৳" . number_format($event->newAdvance - $event->previousAdvance, 2),
                'advance_used' => "Advance decreased by ৳" . number_format($event->previousAdvance - $event->newAdvance, 2),
                default => "Balance updated"
            };

            $this->syncService->logBillingActivity(
                $event->client,
                'balance_change',
                $description,
                [
                    'previous_due' => $event->previousDue,
                    'new_due' => $event->newDue,
                    'previous_advance' => $event->previousAdvance,
                    'new_advance' => $event->newAdvance,
                    'change_type' => $event->changeType,
                ],
                $event->source,
                $event->triggeredBy
            );

            $this->syncService->clearClientCache($event->client->id);
            $this->syncService->clearDashboardCache();

            Log::info("ClientBalanceChanged event synced", [
                'client_id' => $event->client->id,
                'change_type' => $event->changeType,
                'source' => $event->source
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync ClientBalanceChanged event", [
                'client_id' => $event->client->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
