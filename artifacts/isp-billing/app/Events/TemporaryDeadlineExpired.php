<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TemporaryDeadlineExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public string $expiredDate;
    public string $billingMonth;
    public string $source;

    public function __construct(Client $client, string $expiredDate, string $billingMonth, string $source = 'scheduler')
    {
        $this->client = $client;
        $this->expiredDate = $expiredDate;
        $this->billingMonth = $billingMonth;
        $this->source = $source;
    }
}
