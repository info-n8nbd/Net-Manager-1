<?php

namespace App\Events;

use App\Models\Bill;
use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillDueReminder
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public float $dueAmount;
    public int $daysUntilDue;
    public string $source;

    public function __construct(Client $client, float $dueAmount, int $daysUntilDue, string $source = 'scheduler')
    {
        $this->client = $client;
        $this->dueAmount = $dueAmount;
        $this->daysUntilDue = $daysUntilDue;
        $this->source = $source;
    }
}
