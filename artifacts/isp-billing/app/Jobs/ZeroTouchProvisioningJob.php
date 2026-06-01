<?php

namespace App\Jobs;

use App\Models\Client;
use App\Services\ClientAutoProvisionService;
use App\Services\OnuProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZeroTouchProvisioningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(
        protected int $clientId,
        protected string $triggeredBy = 'new_client'
    ) {}

    public function handle(): void
    {
        $client = Client::find($this->clientId);
        if (!$client) {
            Log::warning('[ZTP] Client #' . $this->clientId . ' not found, skipping.');
            return;
        }

        if ($client->provision_status === 'success') {
            Log::info('[ZTP] Client #' . $client->id . ' already provisioned, skipping.');
            return;
        }

        $client->update(['provision_status' => 'provisioning', 'provision_attempted_at' => now()]);

        $logs = [];
        $overallSuccess = true;

        // Step 1: MikroTik Provisioning
        if ($client->connection_type) {
            try {
                $svc = app(ClientAutoProvisionService::class);
                $result = $svc->provisionNewClient($client);
                $logs[] = 'MikroTik: ' . $result['message'];
                if (!$result['success']) {
                    $overallSuccess = false;
                }
                Log::info('[ZTP] MikroTik result for #' . $client->id . ': ' . $result['message']);
            } catch (\Exception $e) {
                $logs[] = 'MikroTik ERROR: ' . $e->getMessage();
                $overallSuccess = false;
                Log::error('[ZTP] MikroTik exception for #' . $client->id . ': ' . $e->getMessage());
            }
        } else {
            $logs[] = 'MikroTik: Skipped (no connection type configured)';
        }

        // Step 2: ONU/OLT Provisioning
        if ($client->olt_device_id && $client->onu_serial) {
            try {
                $onuSvc = app(OnuProvisioningService::class);
                $existingOnu = DB::table('onus')
                    ->where('serial_number', $client->onu_serial)
                    ->where('olt_device_id', $client->olt_device_id)
                    ->first();

                if ($existingOnu && $existingOnu->is_provisioned) {
                    if ($existingOnu->client_id != $client->id) {
                        $onuSvc->assignToClient($existingOnu->id, $client->id);
                    }
                    $logs[] = 'ONU: Already provisioned — assigned to client';
                } else {
                    $port = $client->olt_port ?? $client->onu_port ?? '0/1';
                    $res = $onuSvc->provision($client->olt_device_id, $client->onu_serial, $port, [
                        'description' => $client->name . ' (' . $client->client_id . ')',
                        'user_id'     => null,
                    ]);
                    if ($res['success']) {
                        if (!empty($res['data']['onu_id'])) {
                            $onuSvc->assignToClient($res['data']['onu_id'], $client->id);
                        }
                        $logs[] = 'ONU: Provisioned — ' . $res['message'];
                    } else {
                        $logs[] = 'ONU WARNING: ' . $res['message'];
                        $overallSuccess = false;
                    }
                    Log::info('[ZTP] ONU result for #' . $client->id . ': ' . $res['message']);
                }
            } catch (\Exception $e) {
                $logs[] = 'ONU ERROR: ' . $e->getMessage();
                $overallSuccess = false;
                Log::error('[ZTP] ONU exception for #' . $client->id . ': ' . $e->getMessage());
            }
        } else {
            $logs[] = 'ONU: Skipped (no OLT device or ONU serial)';
        }

        $status = $overallSuccess ? 'success' : 'partial';
        $client->update([
            'provision_status'       => $status,
            'provision_log'          => implode("\n", $logs),
            'provision_attempted_at' => now(),
        ]);

        Log::info('[ZTP] Completed for #' . $client->id . '. Status: ' . $status);
    }

    public function failed(\Throwable $exception): void
    {
        $client = Client::find($this->clientId);
        if ($client) {
            $client->update([
                'provision_status'       => 'failed',
                'provision_log'          => 'Job permanently failed: ' . $exception->getMessage(),
                'provision_attempted_at' => now(),
            ]);
        }
        Log::error('[ZTP] Permanently failed for #' . $this->clientId . ': ' . $exception->getMessage());
    }
}
