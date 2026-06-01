<?php

namespace App\Events;

use App\Models\Client;
use App\Models\Bill;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdvanceUsed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public ?Bill $bill;
    public float $amountUsed;
    public string $source;
    public ?int $triggeredBy;

    public function __construct(
        Client $client, 
        float $amountUsed, 
        ?Bill $bill = null, 
        string $source = 'system',
        ?int $triggeredBy = null
    ) {
        $this->client = $client;
        $this->amountUsed = $amountUsed;
        $this->bill = $bill;
        $this->source = $source;
        $this->triggeredBy = $triggeredBy ?? auth()->id();
    }
}
