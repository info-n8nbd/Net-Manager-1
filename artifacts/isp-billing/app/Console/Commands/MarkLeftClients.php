<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Events\ClientStatusChanged;
use Illuminate\Support\Facades\Log;

class MarkLeftClients extends Command
{
    protected $signature = 'clients:mark-left {--days=60 : Days of being inactive to mark as left}';
    protected $description = 'Mark clients as LEFT (inactive) if they have been inactive for 2+ months';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);
        
        $this->info("Checking for clients inactive for more than {$days} days...");

        $clients = Client::where('status', 'inactive')
            ->where(function ($query) use ($cutoffDate) {
                $query->where('last_inactive_date', '<=', $cutoffDate)
                    ->orWhere(function ($q) use ($cutoffDate) {
                        $q->whereNull('last_inactive_date')
                          ->where('updated_at', '<=', $cutoffDate);
                    });
            })
            ->get();

        $markedCount = 0;

        foreach ($clients as $client) {
            $inactiveDate = $client->last_inactive_date ?? $client->updated_at;
            $daysInactive = $inactiveDate->diffInDays(now());

            if ($daysInactive < $days) {
                continue;
            }

            $oldStatus = $client->status;

            $client->status = 'inactive';
            $client->connection_status = 'offline';
            $client->auto_inactive_note = "Auto-marked as LEFT: Inactive for {$daysInactive} days (since: " . 
                $inactiveDate->format('d-m-Y') . ")";
            $client->save();

            event(new ClientStatusChanged(
                $client,
                $oldStatus,
                'inactive',
                "Auto-marked as LEFT: Inactive for {$daysInactive} days",
                'system'
            ));

            $this->line("Marked LEFT: {$client->name} ({$client->client_id}) - inactive for {$daysInactive} days");
            
            Log::info("Client marked as left", [
                'client_id' => $client->client_id,
                'name' => $client->name,
                'days_inactive' => $daysInactive,
                'inactive_since' => $inactiveDate->toDateString()
            ]);

            $markedCount++;
        }

        $this->info("Completed: {$markedCount} clients marked as LEFT");
        
        return 0;
    }
}
