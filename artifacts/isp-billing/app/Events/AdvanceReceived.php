<?php

namespace App\Events;

use App\Models\AdvancePayment;
use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdvanceReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AdvancePayment $advancePayment;
    public Client $client;
    public float $amount;
    public string $source;
    public ?int $triggeredBy;

    public function __construct(AdvancePayment $advancePayment, string $source = 'admin', ?int $triggeredBy = null)
    {
        $this->advancePayment = $advancePayment;
        $this->client = $advancePayment->client;
        $this->amount = $advancePayment->amount;
        $this->source = $source;
        $this->triggeredBy = $triggeredBy ?? auth()->id();
    }
}
