<?php

namespace App\Jobs;

use App\Models\MikrotikDevice;
use App\Services\MikroTikSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContext;

class MikrotikSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(
        protected int $deviceId,
        protected string $mode = 'dry_run',
        protected ?int $initiatedBy = null
    ) {}

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $device = MikrotikDevice::find($this->deviceId);
        if (!$device || !$device->is_active) {
            Log::info("MikroTik sync skipped for device {$this->deviceId}: inactive or not found");
            return;
        }

        Log::info("MikroTik sync job started for {$device->name} ({$device->host}) in {$this->mode} mode");

        $service = new MikroTikSyncService();
        $run = $service->runSync($device, $this->mode, $this->initiatedBy);

        if ($run->status === 'completed') {
            Log::info("MikroTik sync completed for {$device->name}: " .
                "synced={$run->synced_count}, ghost={$run->only_on_router_count}, " .
                "billing_only={$run->only_in_billing_count}, mismatches={$run->mismatch_count}");
        } else {
            Log::error("MikroTik sync failed for {$device->name}: {$run->error_message}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("MikroTik sync job failed for device {$this->deviceId}: " . $exception->getMessage());
    }
}
