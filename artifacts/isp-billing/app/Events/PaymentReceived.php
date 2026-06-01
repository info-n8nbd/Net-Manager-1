<?php

namespace App\Events;

use App\Models\Payment;
use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Payment $payment;
    public Client $client;
    public string $source;
    public ?int $triggeredBy;
    public bool $isAdvance;

    public function __construct(Payment $payment, string $source = 'admin', ?int $triggeredBy = null, bool $isAdvance = false)
    {
        $this->payment = $payment;
        $this->client = $payment->client;
        $this->source = $source;
        $this->triggeredBy = $triggeredBy ?? auth()->id();
        $this->isAdvance = $isAdvance;
    }
}
