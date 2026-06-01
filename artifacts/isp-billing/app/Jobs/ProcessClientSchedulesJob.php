<?php

namespace App\Jobs;

use App\Models\ClientSchedule;
use App\Models\Package;
use App\Models\MikrotikDevice;
use App\Services\DeviceControlService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessClientSchedulesJob
{
    public function handle(): void
    {
        $schedules = ClientSchedule::with(['client', 'client.package', 'client.zone', 'package'])
            ->due()
            ->orderBy('scheduled_at')
            ->limit(50)
            ->get();

        foreach ($schedules as $schedule) {
            try {
                $this->processSchedule($schedule);
            } catch (\Throwable $e) {
                $schedule->update([
                    'status' => 'failed',
                    'executed_at' => now(),
                    'execution_result' => 'Error: ' . $e->getMessage(),
                ]);
                Log::error("ClientSchedule #{$schedule->id} failed: " . $e->getMessage());
            }
        }
    }

    protected function processSchedule(ClientSchedule $schedule): void
    {
        $client = $schedule->client;
        if (!$client) {
            $schedule->update([
                'status' => 'failed',
                'executed_at' => now(),
                'execution_result' => 'Client not found',
            ]);
            return;
        }

        $result = '';

        switch ($schedule->action) {
            case 'enable':
                $result = $this->enableClient($client);
                break;

            case 'disable':
                $result = $this->disableClient($client);
                break;

            case 'change_package':
                $result = $this->changePackage($client, $schedule);
                break;

            case 'change_profile':
                $result = $this->changeProfile($client, $schedule);
                break;

            default:
                $schedule->update([
                    'status' => 'failed',
                    'executed_at' => now(),
                    'execution_result' => 'Unknown action: ' . $schedule->action,
                ]);
                return;
        }

        $schedule->update([
            'status' => 'completed',
            'executed_at' => now(),
            'execution_result' => $result,
        ]);
    }

    protected function enableClient($client): string
    {
        $mikrotikResult = false;
        $device = $this->getDevice($client);

        if ($device && $client->username) {
            try {
                $mikrotik = \App\Services\MikroTikService::fromDevice($device);
                $connection = $mikrotik->connect();
                if ($connection !== false) {
                    $originalProfile = $client->previous_mikrotik_profile
                        ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);

                    if ($originalProfile) {
                        $mikrotik->changePPPoEProfile($client->username, $originalProfile);
                        $client->mikrotik_profile_name = $originalProfile;
                    }
                    $mikrotik->enablePPPoEUser($client->username);
                    $mikrotik->disconnect();
                    $mikrotikResult = true;
                }
            } catch (\Exception $e) {
                Log::error("Scheduler enable MikroTik failed: " . $e->getMessage());
            }
        }

        $client->status = 'active';
        $client->previous_mikrotik_profile = null;
        $client->save();

        \App\Services\ClientActivityService::logStatusChange(
            $client, 'inactive', 'active',
            'Enabled by scheduler', 'auto'
        );

        return $mikrotikResult ? 'Enabled successfully (MikroTik synced)' : 'Enabled locally' . ($client->username ? ' (MikroTik sync failed)' : '');
    }

    protected function disableClient($client): string
    {
        $mikrotikResult = false;
        $device = $this->getDevice($client);

        if ($device && $client->username) {
            try {
                $mikrotik = \App\Services\MikroTikService::fromDevice($device);
                $connection = $mikrotik->connect();
                if ($connection !== false) {
                    $currentProfile = $client->mikrotik_profile_name
                        ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                    $client->previous_mikrotik_profile = $currentProfile;
                    $mikrotik->changePPPoEProfile($client->username, 'Inactive');
                    $client->mikrotik_profile_name = 'Inactive';
                    $mikrotik->disconnect();
                    $mikrotikResult = true;
                }
            } catch (\Exception $e) {
                Log::error("Scheduler disable MikroTik failed: " . $e->getMessage());
            }
        }

        $client->status = 'inactive';
        $client->save();

        \App\Services\ClientActivityService::logStatusChange(
            $client, 'active', 'inactive',
            'Disabled by scheduler', 'auto'
        );

        return $mikrotikResult ? 'Disabled successfully (MikroTik synced)' : 'Disabled locally' . ($client->username ? ' (MikroTik sync failed)' : '');
    }

    protected function changePackage($client, ClientSchedule $schedule): string
    {
        $newPackage = $schedule->package;
        if (!$newPackage) {
            $schedule->update(['status' => 'failed', 'executed_at' => now(), 'execution_result' => 'Package not found']);
            throw new \RuntimeException('Package not found for schedule #' . $schedule->id);
        }

        $oldPackageName = $client->package->name ?? 'N/A';
        $mikrotikProfile = $schedule->mikrotik_profile ?: ($newPackage->mikrotik_profile ?: $newPackage->name);

        DB::transaction(function () use ($client, $newPackage, $mikrotikProfile) {
            $client->package_id = $newPackage->id;
            $client->monthly_fee = $newPackage->price;
            $client->mikrotik_profile_name = $mikrotikProfile;
            $client->save();
        });

        app(\App\Services\BillingService::class)->updateCurrentBillForPackageChange($client, $newPackage, 'scheduler');

        $mikrotikResult = $this->syncMikrotikProfile($client, $mikrotikProfile);

        \App\Services\ClientActivityService::log(
            $client, 'package_changed', $oldPackageName, $newPackage->name,
            'Package changed by scheduler to ' . $newPackage->name . ' (Profile: ' . $mikrotikProfile . ')',
            'scheduler'
        );

        \App\Models\ClientStatusLog::logStatusChange(
            client: $client,
            oldStatus: $oldPackageName,
            newStatus: $newPackage->name,
            reason: 'Package changed by scheduler',
            source: \App\Models\ClientStatusLog::SOURCE_AUTO
        );

        return 'Package changed to ' . $newPackage->name . ($mikrotikResult ? ' (MikroTik synced)' : ' (MikroTik sync pending)');
    }

    protected function changeProfile($client, ClientSchedule $schedule): string
    {
        $newProfile = $schedule->mikrotik_profile;
        if (!$newProfile) {
            $schedule->update(['status' => 'failed', 'executed_at' => now(), 'execution_result' => 'No profile specified']);
            throw new \RuntimeException('No profile specified for schedule #' . $schedule->id);
        }

        $oldProfile = $client->mikrotik_profile_name ?? '-';
        $client->mikrotik_profile_name = $newProfile;
        $client->save();

        $mikrotikResult = $this->syncMikrotikProfile($client, $newProfile);

        \App\Services\ClientActivityService::log(
            $client, 'profile_changed', $oldProfile, $newProfile,
            'MikroTik profile changed by scheduler from ' . $oldProfile . ' to ' . $newProfile,
            'scheduler'
        );

        return 'Profile changed to ' . $newProfile . ($mikrotikResult ? ' (MikroTik synced)' : ' (MikroTik sync pending)');
    }

    protected function syncMikrotikProfile($client, string $profile): bool
    {
        $device = $this->getDevice($client);
        if (!$device || !$client->username) {
            return false;
        }

        try {
            $mikrotik = \App\Services\MikroTikService::fromDevice($device);
            $connection = $mikrotik->connect();
            if ($connection !== false) {
                $mikrotik->changePPPoEProfile($client->username, $profile);
                $mikrotik->disconnect();
                return true;
            }
        } catch (\Exception $e) {
            Log::error("Scheduler MikroTik profile sync failed: " . $e->getMessage());
        }

        return false;
    }

    protected function getDevice($client): ?MikrotikDevice
    {
        if ($client->mikrotik_device_id) {
            return MikrotikDevice::find($client->mikrotik_device_id);
        }
        if ($client->zone && $client->zone->mikrotik_device_id) {
            return MikrotikDevice::find($client->zone->mikrotik_device_id);
        }
        return MikrotikDevice::where('is_active', true)->first();
    }
}
