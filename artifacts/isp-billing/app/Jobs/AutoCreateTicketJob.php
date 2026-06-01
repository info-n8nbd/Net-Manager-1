<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContext;

class AutoCreateTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $offlineMinutes;

    public function __construct(int $offlineMinutes = 30)
    {
        $this->offlineMinutes = $offlineMinutes;
    }

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $startTime = microtime(true);
        $recordsProcessed = 0;

        try {
            $this->logCronStart();

            $offlineOnus = DB::table('onu_devices')
                ->where('status', 'offline')
                ->where('last_down_at', '<=', now()->subMinutes($this->offlineMinutes))
                ->whereNotNull('client_id')
                ->get();

            foreach ($offlineOnus as $onu) {
                $existingTicket = Ticket::where('client_id', $onu->client_id)
                    ->whereIn('status', ['open', 'in_progress'])
                    ->where('subject', 'like', '%Auto: ONU Offline%')
                    ->exists();

                if (!$existingTicket) {
                    $client = Client::find($onu->client_id);
                    if ($client) {
                        $companyId = $client->company_id ?? \DB::table('companies')->value('id');
                    Ticket::create([
                            'client_id'   => $client->id,
                            'company_id'  => $companyId,
                            'subject'     => "Auto: ONU Offline - {$onu->onu_serial}",
                            'description' => "Client's ONU ({$onu->onu_serial}) has been offline for more than {$this->offlineMinutes} minutes. Last seen: {$onu->last_seen}. Automatic ticket created.",
                            'priority'    => 'high',
                            'status'      => 'open',
                            'category_id' => $this->getNetworkCategoryId(),
                            'created_by'  => null,
                        ]);

                        $this->logNetworkEvent($client, "Auto-ticket created for ONU {$onu->onu_serial} offline");
                        $recordsProcessed++;
                    }
                }
            }

            $offlineClients = Client::where('connection_status', 'offline')
                ->where('status', 'active')
                ->where('last_online', '<=', now()->subMinutes($this->offlineMinutes))
                ->get();

            foreach ($offlineClients as $client) {
                $existingTicket = Ticket::where('client_id', $client->id)
                    ->whereIn('status', ['open', 'in_progress'])
                    ->where('subject', 'like', '%Auto: Connection Offline%')
                    ->exists();

                if (!$existingTicket) {
                    $companyId2 = $client->company_id ?? \DB::table('companies')->value('id');
                    Ticket::create([
                        'client_id'   => $client->id,
                        'company_id'  => $companyId2,
                        'subject'     => "Auto: Connection Offline - {$client->pppoe_username}",
                        'description' => "Client's PPPoE connection ({$client->pppoe_username}) has been offline for more than {$this->offlineMinutes} minutes. Last online: {$client->last_online}. Automatic ticket created.",
                        'priority'    => 'high',
                        'status'      => 'open',
                        'category_id' => $this->getNetworkCategoryId(),
                        'created_by'  => null,
                    ]);

                    $this->logNetworkEvent($client, "Auto-ticket created for PPPoE offline");
                    $recordsProcessed++;
                }
            }

            $this->logCronComplete($startTime, $recordsProcessed);

        } catch (\Exception $e) {
            Log::error("Auto-ticket creation failed: " . $e->getMessage());
            $this->logCronFailed($startTime, $e->getMessage());
        }
    }

    protected function getNetworkCategoryId(): ?int
    {
        return DB::table('ticket_categories')
            ->where('name', 'like', '%network%')
            ->orWhere('name', 'like', '%connection%')
            ->value('id');
    }

    protected function logNetworkEvent($client, string $message): void
    {
        DB::table('network_event_logs')->insert([
            'event_type' => 'alert',
            'source' => 'auto_ticket',
            'client_id' => $client->id,
            'message' => $message,
            'severity' => 'info',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function logCronStart(): void
    {
        DB::table('cron_job_logs')->insert([
            'job_name' => 'auto_create_ticket',
            'job_class' => self::class,
            'status' => 'started',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function logCronComplete(float $startTime, int $records): void
    {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        DB::table('cron_job_logs')
            ->where('job_class', self::class)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'status' => 'completed',
                'records_processed' => $records,
                'execution_time_ms' => $executionTime,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function logCronFailed(float $startTime, string $message): void
    {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);
        DB::table('cron_job_logs')
            ->where('job_class', self::class)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'status' => 'failed',
                'message' => $message,
                'execution_time_ms' => $executionTime,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
