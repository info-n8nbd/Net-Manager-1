<?php

namespace App\Services;

use App\Models\PortForward;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\Log;

class PortForwardService
{
    public function createRule(array $data): PortForward
    {
        $data['created_by'] = auth()->id();
        $rule = PortForward::create($data);

        if ($rule->mikrotik_device_id) {
            $this->syncToRouter($rule);
        }

        return $rule;
    }

    public function updateRule(PortForward $rule, array $data): PortForward
    {
        $rule->update($data);

        if ($rule->mikrotik_device_id) {
            $this->syncToRouter($rule);
        }

        return $rule;
    }

    public function deleteRule(PortForward $rule): void
    {
        if ($rule->mikrotik_device_id && $rule->mikrotik_rule_id) {
            $this->removeFromRouter($rule);
        }
        $rule->delete();
    }

    public function toggleStatus(PortForward $rule): void
    {
        $rule->update(['is_active' => !$rule->is_active]);

        if ($rule->mikrotik_device_id) {
            $this->syncToRouter($rule);
        }
    }

    protected function syncToRouter(PortForward $rule): bool
    {
        try {
            $device = $rule->mikrotikDevice;
            if (!$device) return false;

            Log::info("Port forward sync: {$rule->protocol} {$rule->external_port} -> {$rule->internal_ip}:{$rule->internal_port} on {$device->name}");
            return true;
        } catch (\Exception $e) {
            Log::error("Port forward sync failed: " . $e->getMessage());
            return false;
        }
    }

    protected function removeFromRouter(PortForward $rule): bool
    {
        try {
            Log::info("Port forward remove: rule #{$rule->id} from router");
            return true;
        } catch (\Exception $e) {
            Log::error("Port forward remove failed: " . $e->getMessage());
            return false;
        }
    }

    public function getClientRules(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return PortForward::where('client_id', $clientId)->orderBy('name')->get();
    }
}
