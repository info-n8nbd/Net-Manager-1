<?php

namespace App\Events;

use App\Models\Client;
use App\Models\SmsLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SMSDispatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?Client $client;
    public string $phone;
    public string $eventType;
    public bool $success;
    public ?SmsLog $smsLog;
    public string $source;

    public function __construct(
        ?Client $client,
        string $phone,
        string $eventType,
        bool $success,
        ?SmsLog $smsLog = null,
        string $source = 'system'
    ) {
        $this->client = $client;
        $this->phone = $phone;
        $this->eventType = $eventType;
        $this->success = $success;
        $this->smsLog = $smsLog;
        $this->source = $source;
    }
}
