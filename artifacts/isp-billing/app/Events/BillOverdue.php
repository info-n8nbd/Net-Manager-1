<?php

namespace App\Events;

use App\Models\Bill;
use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillOverdue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public float $overdueAmount;
    public int $daysOverdue;
    public string $action;
    public string $source;

    public function __construct(Client $client, float $overdueAmount, int $daysOverdue, string $action = 'inactive', string $source = 'scheduler')
    {
        $this->client = $client;
        $this->overdueAmount = $overdueAmount;
        $this->daysOverdue = $daysOverdue;
        $this->action = $action;
        $this->source = $source;
    }
}
