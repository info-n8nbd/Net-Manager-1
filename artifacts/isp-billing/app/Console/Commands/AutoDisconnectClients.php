<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\ClientStatusLog;
use App\Services\SmsService;
use App\Services\MikroTikService;
use App\Events\ClientStatusChanged;
use App\Events\BillOverdue;
use Illuminate\Support\Facades\Log;

class AutoDisconnectClients extends Command
{
    protected $signature = 'clients:auto-disconnect {--days=30 : Days after due date to disconnect}';
    protected $description = 'Automatically disconnect clients with overdue bills (updates database and MikroTik)';

    public function handle(SmsService $smsService)
    {
        $days = $this->option('days');

        $clients = Client::where('status', 'active')
            ->where('due_amount', '>', 0)
            ->whereHas('bills', function ($q) use ($days) {
                $q->where('status', 'unpaid')
                    ->whereDate('due_date', '<=', now()->subDays($days));
            })
            ->with(['mikrotikDevice', 'zone.mikrotikDevice'])
            ->get();

        $disconnectedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        $billingService = app(\App\Services\BillingService::class);

        foreach ($clients as $client) {
            if ($billingService->hasActiveTemporaryDeadline($client)) {
                $this->line("Skipped: {$client->name} ({$client->client_id}) - Temporary deadline active until {$client->temp_deadline_date}");
                $skippedCount++;
                continue;
            }

            $advanceBalance = $client->getAvailableAdvance();
            $totalDue = $client->due_amount ?? 0;
            
            if ($advanceBalance >= $totalDue && $totalDue > 0) {
                $this->line("Skipped: {$client->name} ({$client->client_id}) - Advance ৳{$advanceBalance} covers due ৳{$totalDue}");
                $skippedCount++;
                continue;
            }
            
            $oldStatus = $client->status;
            
            // Try to disable on MikroTik router
            $mikrotikSuccess = $this->disableOnMikrotik($client);
            
            if (!$mikrotikSuccess) {
                $this->warn("MikroTik disable failed for {$client->name} ({$client->client_id}) - updating database only");
                $failedCount++;
            }
            
            // Always update database status
            $client->status = 'inactive';
            $client->connection_status = 'offline';
            $client->last_inactive_date = now();
            $client->auto_inactive_note = "Auto-disconnected: {$days}+ days overdue";
            $client->save();

            event(new ClientStatusChanged(
                $client,
                $oldStatus,
                'inactive',
                'auto',
                "Auto-disconnected due to {$days}+ days overdue bills (Due: ৳{$totalDue})"
            ));

            event(new BillOverdue($client, $totalDue, (int) $days, 'inactive', 'scheduler'));

            $smsService->sendDisconnectNotice($client);

            $disconnectedCount++;
            $this->line("Disconnected: {$client->name} ({$client->client_id}) - Due: ৳{$totalDue}");
        }

        $this->info("Auto-disconnect completed:");
        $this->info("  - Disconnected: {$disconnectedCount}");
        $this->info("  - Skipped (advance): {$skippedCount}");
        $this->info("  - MikroTik failures: {$failedCount}");
        
        Log::info("Auto-disconnect job completed", [
            'days' => $days,
            'disconnected' => $disconnectedCount,
            'skipped' => $skippedCount,
            'mikrotik_failures' => $failedCount
        ]);
    }
    
    protected function disableOnMikrotik(Client $client): bool
    {
        if (!$client->username) {
            return false;
        }
        
        $username = $client->username;
        
        // Get MikroTik device
        $device = $client->mikrotikDevice 
            ?? ($client->zone ? $client->zone->mikrotikDevice : null)
            ?? \App\Models\MikrotikDevice::where('is_active', true)->first();
        
        if (!$device) {
            Log::warning("No MikroTik device found for client {$client->client_id}");
            return false;
        }
        
        try {
            $mikrotik = MikroTikService::fromDevice($device);
            $connection = $mikrotik->connect();
            
            if ($connection === false) {
                Log::error("Failed to connect to MikroTik {$device->name} for client {$client->client_id}");
                return false;
            }
            
            $result = $mikrotik->disablePPPoEUser($username);
            $mikrotik->disconnect();
            
            if ($result) {
                Log::info("MikroTik: Disabled PPPoE user {$username} on {$device->name}");
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("MikroTik disable error for {$client->client_id}: " . $e->getMessage());
            return false;
        }
    }
}
