<?php

namespace App\Events;

use App\Models\Bill;
use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Bill $bill;
    public Client $client;
    public string $source;
    public ?int $triggeredBy;
    public bool $suppressSms;

    public function __construct(Bill $bill, string $source = 'system', ?int $triggeredBy = null, bool $suppressSms = false)
    {
        $this->bill = $bill;
        $this->client = $bill->client;
        $this->source = $source;
        $this->triggeredBy = $triggeredBy ?? auth()->id();
        $this->suppressSms = $suppressSms;
    }
}
