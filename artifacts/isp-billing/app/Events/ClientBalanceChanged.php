<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientBalanceChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public float $previousDue;
    public float $newDue;
    public float $previousAdvance;
    public float $newAdvance;
    public string $changeType;
    public string $source;
    public ?int $triggeredBy;

    public function __construct(
        Client $client,
        float $previousDue,
        float $newDue,
        float $previousAdvance,
        float $newAdvance,
        string $changeType,
        string $source = 'system',
        ?int $triggeredBy = null
    ) {
        $this->client = $client;
        $this->previousDue = $previousDue;
        $this->newDue = $newDue;
        $this->previousAdvance = $previousAdvance;
        $this->newAdvance = $newAdvance;
        $this->changeType = $changeType;
        $this->source = $source;
        $this->triggeredBy = $triggeredBy ?? auth()->id();
    }
}
