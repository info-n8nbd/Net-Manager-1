<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;

class CheckSlaBreach extends Command
{
    protected $signature = 'tickets:check-sla';
    protected $description = 'Check for SLA breaches on open tickets';

    public function handle()
    {
        $tickets = Ticket::whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('sla_deadline')
            ->where('sla_breached', false)
            ->where('sla_deadline', '<', now())
            ->get();

        $breachedCount = 0;

        foreach ($tickets as $ticket) {
            $ticket->sla_breached = true;
            $ticket->save();
            $breachedCount++;
            $this->line("Ticket {$ticket->ticket_no} marked as SLA breached");
        }

        $this->info("SLA check completed. {$breachedCount} tickets marked as breached.");
    }
}
