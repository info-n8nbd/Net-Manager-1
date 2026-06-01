<?php

namespace App\Events;

use App\Models\Client;
use App\Models\MikrotikDevice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MikroTikActionExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;
    public ?Client $client;
    public ?MikrotikDevice $device;
    public bool $success;
    public string $details;
    public string $trigger;
    public ?int $triggeredBy;

    public function __construct(
        string $action,
        ?Client $client,
        ?MikrotikDevice $device,
        bool $success,
        string $details = '',
        string $trigger = 'manual',
        ?int $triggeredBy = null
    ) {
        $this->action = $action;
        $this->client = $client;
        $this->device = $device;
        $this->success = $success;
        $this->details = $details;
        $this->trigger = $trigger;
        $this->triggeredBy = $triggeredBy ?? auth()->id();
    }
}
