<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TemporaryDeadlineApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public string $deadlineDate;
    public string $reason;
    public ?int $appliedBy;
    public string $billingMonth;

    public function __construct(Client $client, string $deadlineDate, string $reason, ?int $appliedBy = null, ?string $billingMonth = null)
    {
        $this->client = $client;
        $this->deadlineDate = $deadlineDate;
        $this->reason = $reason;
        $this->appliedBy = $appliedBy ?? auth()->id();
        $this->billingMonth = $billingMonth ?? now()->format('Y-m');
    }
}
