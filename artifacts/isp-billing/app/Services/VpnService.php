<?php

namespace App\Services;

use App\Models\VpnAccount;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\Log;

class VpnService
{
    public function createAccount(array $data): VpnAccount
    {
        $account = VpnAccount::create($data);

        if ($account->mikrotik_device_id) {
            $this->syncToRouter($account);
        }

        return $account;
    }

    public function updateAccount(VpnAccount $account, array $data): VpnAccount
    {
        $oldDeviceId = $account->mikrotik_device_id;
        $account->update($data);

        if ($account->mikrotik_device_id) {
            $this->syncToRouter($account);
        }

        return $account;
    }

    public function deleteAccount(VpnAccount $account): void
    {
        if ($account->mikrotik_device_id && $account->mikrotik_id) {
            $this->removeFromRouter($account);
        }
        $account->delete();
    }

    public function toggleStatus(VpnAccount $account): void
    {
        $account->update(['is_active' => !$account->is_active]);

        if ($account->mikrotik_device_id) {
            $this->syncToRouter($account);
        }
    }

    protected function syncToRouter(VpnAccount $account): bool
    {
        try {
            $device = $account->mikrotikDevice;
            if (!$device) return false;

            Log::info("VPN sync: {$account->vpn_type} account '{$account->username}' to router {$device->name}");
            return true;
        } catch (\Exception $e) {
            Log::error("VPN sync failed: " . $e->getMessage());
            return false;
        }
    }

    protected function removeFromRouter(VpnAccount $account): bool
    {
        try {
            $device = $account->mikrotikDevice;
            if (!$device) return false;

            Log::info("VPN remove: account '{$account->username}' from router {$device->name}");
            return true;
        } catch (\Exception $e) {
            Log::error("VPN remove failed: " . $e->getMessage());
            return false;
        }
    }

    public static function getVpnTypes(): array
    {
        return VpnAccount::getVpnTypes();
    }
}
