<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\MikrotikSyncRun;
use App\Models\MikrotikSyncItem;
use App\Models\MikrotikActionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MikroTikSyncService
{
    protected ?SimpleMikroTikService $mikrotik = null;
    protected ?MikrotikDevice $device = null;

    public function runSync(MikrotikDevice $device, string $mode = 'dry_run', ?int $initiatedBy = null): MikrotikSyncRun
    {
        $this->device = $device;

        $run = MikrotikSyncRun::create([
            'device_id' => $device->id,
            'status' => 'running',
            'mode' => $mode,
            'initiated_by' => $initiatedBy,
            'started_at' => now(),
        ]);

        try {
            $this->mikrotik = new SimpleMikroTikService();
            $connected = $this->mikrotik->connectFromDevice($device);

            if (!$connected) {
                $run->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => 'Failed to connect to router ' . ($device->host ?? $device->ip_address),
                ]);
                return $run;
            }

            $routerUsers = $this->getRouterPPPSecrets();
            $billingUsers = $this->getBillingUsers($device);

            $run->update([
                'router_user_count' => count($routerUsers),
                'billing_user_count' => count($billingUsers),
            ]);

            $this->computeDiffs($run, $routerUsers, $billingUsers);

            if ($mode === 'apply') {
                $this->applyActions($run);
            }

            $counts = $this->refreshCounts($run);
            $run->update(array_merge($counts, [
                'status' => 'completed',
                'completed_at' => now(),
            ]));

            $this->mikrotik->disconnect();

        } catch (\Exception $e) {
            Log::error('MikroTik sync failed: ' . $e->getMessage(), [
                'device_id' => $device->id,
                'run_id' => $run->id,
            ]);
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }

        return $run->fresh(['items']);
    }

    protected function getRouterPPPSecrets(): array
    {
        $mtService = MikroTikService::fromDevice($this->device);
        $secrets = $mtService->getAllPPPoESecrets();

        $indexed = [];
        foreach ($secrets as $secret) {
            $username = $secret['name'] ?? null;
            if ($username) {
                $indexed[$username] = [
                    'profile' => $secret['profile'] ?? 'default',
                    'disabled' => ($secret['disabled'] ?? 'false') === 'true',
                    'service' => $secret['service'] ?? 'pppoe',
                    'comment' => $secret['comment'] ?? '',
                ];
            }
        }
        return $indexed;
    }

    protected function getBillingUsers(MikrotikDevice $device): array
    {
        $query = Client::where('status', '!=', 'deleted')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->where(function ($q) {
                $q->where('connection_type', 'pppoe')
                    ->orWhereNull('connection_type')
                    ->orWhere('connection_type', '');
            });

        if ($device->id) {
            $query->where(function ($q) use ($device) {
                $q->where('mikrotik_device_id', $device->id)
                    ->orWhereNull('mikrotik_device_id');
            });
        }

        $clients = $query->with('package')->get();

        $indexed = [];
        foreach ($clients as $client) {
            $username = $client->username;
            if ($username && !isset($indexed[$username])) {
                $indexed[$username] = [
                    'client_id' => $client->id,
                    'client_code' => $client->client_id ?? '',
                    'name' => $client->name ?? '',
                    'profile' => $client->package ? $client->package->name : '',
                    'status' => $client->status ?? 'active',
                    'connection_status' => $client->connection_status ?? 'active',
                    'monthly_fee' => $client->monthly_fee ?? 0,
                    'pppoe_password' => $client->pppoe_password ?? '',
                ];
            }
        }
        return $indexed;
    }

    protected function computeDiffs(MikrotikSyncRun $run, array $routerUsers, array $billingUsers): void
    {
        $allUsernames = array_unique(array_merge(
            array_keys($routerUsers),
            array_keys($billingUsers)
        ));

        $items = [];
        foreach ($allUsernames as $username) {
            $onRouter = isset($routerUsers[$username]);
            $inBilling = isset($billingUsers[$username]);

            if ($onRouter && $inBilling) {
                $router = $routerUsers[$username];
                $billing = $billingUsers[$username];

                $profileMatch = strtolower($router['profile']) === strtolower($billing['profile']);
                $statusMatch = $this->isStatusConsistent($router['disabled'], $billing['status'], $billing['connection_status']);

                if ($profileMatch && $statusMatch) {
                    $items[] = [
                        'sync_run_id' => $run->id,
                        'username' => $username,
                        'client_id' => $billing['client_id'],
                        'diff_type' => 'synced',
                        'router_profile' => $router['profile'],
                        'billing_profile' => $billing['profile'],
                        'router_service' => $router['service'],
                        'router_disabled' => $router['disabled'],
                        'billing_status' => $billing['status'],
                        'client_name' => $billing['name'],
                        'client_code' => $billing['client_code'],
                        'proposed_action' => null,
                        'action_status' => 'synced',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } else {
                    $action = $this->determineMismatchAction($router, $billing);
                    $items[] = [
                        'sync_run_id' => $run->id,
                        'username' => $username,
                        'client_id' => $billing['client_id'],
                        'diff_type' => 'mismatch',
                        'router_profile' => $router['profile'],
                        'billing_profile' => $billing['profile'],
                        'router_service' => $router['service'],
                        'router_disabled' => $router['disabled'],
                        'billing_status' => $billing['status'],
                        'client_name' => $billing['name'],
                        'client_code' => $billing['client_code'],
                        'proposed_action' => $action,
                        'action_status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            } elseif ($onRouter && !$inBilling) {
                $router = $routerUsers[$username];
                $items[] = [
                    'sync_run_id' => $run->id,
                    'username' => $username,
                    'client_id' => null,
                    'diff_type' => 'only_on_router',
                    'router_profile' => $router['profile'],
                    'billing_profile' => null,
                    'router_service' => $router['service'],
                    'router_disabled' => $router['disabled'],
                    'billing_status' => null,
                    'client_name' => $router['comment'] ?: null,
                    'client_code' => null,
                    'proposed_action' => 'review',
                    'action_status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
                $billing = $billingUsers[$username];
                $action = 'create_on_router';
                if (in_array($billing['status'], ['inactive', 'expired'])) {
                    $action = 'create_disabled_on_router';
                }
                $items[] = [
                    'sync_run_id' => $run->id,
                    'username' => $username,
                    'client_id' => $billing['client_id'],
                    'diff_type' => 'only_in_billing',
                    'router_profile' => null,
                    'billing_profile' => $billing['profile'],
                    'router_service' => null,
                    'router_disabled' => false,
                    'billing_status' => $billing['status'],
                    'client_name' => $billing['name'],
                    'client_code' => $billing['client_code'],
                    'proposed_action' => $action,
                    'action_status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($items, 500) as $chunk) {
            MikrotikSyncItem::insert($chunk);
        }
    }

    protected function isStatusConsistent(bool $routerDisabled, string $billingStatus, string $connectionStatus): bool
    {
        $shouldBeDisabled = in_array($billingStatus, ['inactive', 'expired'])
            || $connectionStatus === 'inactive';
        return $routerDisabled === $shouldBeDisabled;
    }

    protected function determineMismatchAction(array $router, array $billing): string
    {
        $profileMatch = strtolower($router['profile']) === strtolower($billing['profile']);
        $shouldBeDisabled = in_array($billing['status'], ['inactive', 'expired'])
            || ($billing['connection_status'] ?? '') === 'inactive';

        if (!$profileMatch && $router['disabled'] !== $shouldBeDisabled) {
            return 'update_profile_and_status';
        }
        if (!$profileMatch) {
            return 'update_profile';
        }
        if ($router['disabled'] && !$shouldBeDisabled) {
            return 'enable_on_router';
        }
        if (!$router['disabled'] && $shouldBeDisabled) {
            return 'disable_on_router';
        }
        return 'review';
    }

    public function applyActions(MikrotikSyncRun $run): array
    {
        $results = ['applied' => 0, 'failed' => 0, 'skipped' => 0];
        $items = $run->items()->where('action_status', 'pending')->whereNotNull('proposed_action')->get();

        foreach ($items as $item) {
            try {
                $result = $this->applyItemAction($item);
                if ($result === 'applied') {
                    $item->update([
                        'action_status' => 'applied',
                        'action_message' => 'Action applied successfully',
                        'action_applied_at' => now(),
                    ]);
                    $results['applied']++;
                } elseif ($result === 'skipped') {
                    $item->update([
                        'action_status' => 'skipped',
                        'action_message' => 'Action requires manual review',
                    ]);
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $item->update([
                    'action_status' => 'failed',
                    'action_message' => $e->getMessage(),
                ]);
                $results['failed']++;
                Log::warning("Sync action failed for {$item->username}: " . $e->getMessage());
            }
        }

        $run->update([
            'actions_applied' => $results['applied'],
            'actions_failed' => $results['failed'],
        ]);

        return $results;
    }

    protected function applyItemAction(MikrotikSyncItem $item): string
    {
        switch ($item->proposed_action) {
            case 'create_on_router':
                return $this->createOnRouter($item);

            case 'create_disabled_on_router':
                return $this->createDisabledOnRouter($item);

            case 'update_profile':
                return $this->updateProfile($item);

            case 'enable_on_router':
                return $this->enableOnRouter($item);

            case 'disable_on_router':
                return $this->disableOnRouter($item);

            case 'update_profile_and_status':
                $this->updateProfile($item);
                $shouldDisable = in_array($item->billing_status, ['inactive', 'expired']);
                if ($shouldDisable) {
                    $this->disableOnRouter($item);
                } else {
                    $this->enableOnRouter($item);
                }
                return 'applied';

            case 'review':
                return 'skipped';

            default:
                return 'skipped';
        }
    }

    protected function createOnRouter(MikrotikSyncItem $item): string
    {
        $client = $item->client_id ? Client::find($item->client_id) : null;
        $password = $client ? ($client->pppoe_password ?? $item->username) : $item->username;
        $profile = $item->billing_profile ?: 'default';
        $comment = $item->client_name ? "{$item->client_code} - {$item->client_name}" : '';

        $result = $this->mikrotik->createPPPoESecret($item->username, $password, $profile, $comment);

        $this->logAction('create_ppp_secret', $item->username, $result);
        return ($result['success'] ?? false) ? 'applied' : throw new \Exception($result['error'] ?? 'Create failed');
    }

    protected function createDisabledOnRouter(MikrotikSyncItem $item): string
    {
        $client = $item->client_id ? Client::find($item->client_id) : null;
        $password = $client ? ($client->pppoe_password ?? $item->username) : $item->username;
        $profile = $item->billing_profile ?: 'default';
        $comment = $item->client_name ? "{$item->client_code} - {$item->client_name}" : '';

        $result = $this->mikrotik->createPPPoESecret($item->username, $password, $profile, $comment, true);

        $this->logAction('create_ppp_secret_disabled', $item->username, $result);
        return ($result['success'] ?? false) ? 'applied' : throw new \Exception($result['error'] ?? 'Create disabled failed');
    }

    protected function updateProfile(MikrotikSyncItem $item): string
    {
        $result = $this->mikrotik->updatePPPoEProfile($item->username, $item->billing_profile);

        $this->logAction('update_ppp_profile', $item->username, $result);
        return ($result['success'] ?? false) ? 'applied' : throw new \Exception($result['error'] ?? 'Profile update failed');
    }

    protected function enableOnRouter(MikrotikSyncItem $item): string
    {
        $result = $this->mikrotik->enablePPPoESecret($item->username);

        $this->logAction('enable_ppp_secret', $item->username, $result);
        return ($result['success'] ?? false) ? 'applied' : throw new \Exception($result['error'] ?? 'Enable failed');
    }

    protected function disableOnRouter(MikrotikSyncItem $item): string
    {
        $result = $this->mikrotik->disablePPPoESecret($item->username);

        $this->logAction('disable_ppp_secret', $item->username, $result);
        return ($result['success'] ?? false) ? 'applied' : throw new \Exception($result['error'] ?? 'Disable failed');
    }

    public function applySingleAction(MikrotikSyncItem $item, ?string $overrideAction = null): bool
    {
        $run = $item->syncRun;
        $device = $run->device;

        $this->device = $device;
        $this->mikrotik = new SimpleMikroTikService();
        $connected = $this->mikrotik->connectFromDevice($device);

        if (!$connected) {
            $item->update([
                'action_status' => 'failed',
                'action_message' => 'Failed to connect to router',
            ]);
            return false;
        }

        try {
            if ($overrideAction) {
                $item->proposed_action = $overrideAction;
            }

            $result = $this->applyItemAction($item);
            if ($result === 'applied') {
                $item->update([
                    'action_status' => 'applied',
                    'action_message' => 'Action applied successfully',
                    'action_applied_at' => now(),
                ]);
                $this->updateRunCounts($run);
                return true;
            }

            $item->update([
                'action_status' => 'skipped',
                'action_message' => 'Action skipped or requires review',
            ]);
            return false;

        } catch (\Exception $e) {
            $item->update([
                'action_status' => 'failed',
                'action_message' => $e->getMessage(),
            ]);
            return false;
        } finally {
            $this->mikrotik->disconnect();
        }
    }

    public function skipItem(MikrotikSyncItem $item, string $reason = ''): void
    {
        $item->update([
            'action_status' => 'skipped',
            'action_message' => $reason ?: 'Manually skipped by admin',
        ]);
        $this->updateRunCounts($item->syncRun);
    }

    protected function updateRunCounts(MikrotikSyncRun $run): void
    {
        $counts = $this->refreshCounts($run);
        $run->update($counts);
    }

    protected function refreshCounts(MikrotikSyncRun $run): array
    {
        return [
            'synced_count' => $run->items()->where('diff_type', 'synced')->count(),
            'only_on_router_count' => $run->items()->where('diff_type', 'only_on_router')->count(),
            'only_in_billing_count' => $run->items()->where('diff_type', 'only_in_billing')->count(),
            'mismatch_count' => $run->items()->where('diff_type', 'mismatch')->count(),
            'actions_applied' => $run->items()->where('action_status', 'applied')->count(),
            'actions_failed' => $run->items()->where('action_status', 'failed')->count(),
        ];
    }

    protected function logAction(string $action, string $username, array $result): void
    {
        try {
            if ($this->device && class_exists(MikrotikActionLog::class)) {
                MikrotikActionLog::create([
                    'device_id' => $this->device->id,
                    'action' => $action,
                    'target' => $username,
                    'result' => json_encode($result),
                    'status' => ($result['success'] ?? false) ? 'success' : 'failed',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to log MikroTik action: " . $e->getMessage());
        }
    }
}
