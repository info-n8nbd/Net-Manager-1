<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public string $oldStatus;
    public string $newStatus;
    public string $source;
    public ?string $reason;
    public ?int $triggeredBy;

    public function __construct(
        Client $client, 
        string $oldStatus, 
        string $newStatus, 
        string $source = 'manual', 
        ?string $reason = null,
        ?int $triggeredBy = null
    ) {
        $this->client = $client;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->source = $source;
        $this->reason = $reason;
        $this->triggeredBy = $triggeredBy ?? auth()->id();
    }
}
