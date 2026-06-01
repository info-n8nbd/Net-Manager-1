<?php

namespace App\Listeners;

use App\Events\ClientStatusChanged;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SmsClientStatusChanged
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(ClientStatusChanged $event): void
    {
        try {
            $client = $event->client;

            if (!$client->phone) {
                return;
            }

            if ($event->newStatus === 'inactive' && $event->oldStatus === 'active') {
                $alreadySent = SmsLog::where('client_id', $client->id)
                    ->where('event_type', SmsService::EVENT_SERVICE_SUSPENSION)
                    ->where('status', 'sent')
                    ->where('created_at', '>=', now()->subMinutes(2))
                    ->exists();

                if (!$alreadySent) {
                    $this->smsService->sendDisconnectNotice($client);
                }
            }

            if ($event->newStatus === 'active' && $event->oldStatus === 'inactive') {
                $alreadySent = SmsLog::where('client_id', $client->id)
                    ->where('event_type', SmsService::EVENT_SERVICE_RESTORATION)
                    ->where('status', 'sent')
                    ->where('created_at', '>=', now()->subMinutes(2))
                    ->exists();

                if (!$alreadySent) {
                    $this->smsService->sendReconnectNotice($client);
                }
            }
        } catch (\Exception $e) {
            Log::error("SMS listener failed for ClientStatusChanged: " . $e->getMessage(), [
                'client_id' => $event->client->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ]);
        }
    }
}
