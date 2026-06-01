<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientRecoveryLog;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientRecoveryService
{
    public function softDeleteClient(Client $client, string $reason, ?int $deletedBy = null): void
    {
        $client->update([
            'deleted_reason' => $reason,
            'deleted_by' => $deletedBy ?? auth()->id(),
            'deleted_at_custom' => now(),
            'status' => 'deleted',
        ]);
    }

    public function recoverClient(Client $client, ?string $notes = null): Client
    {
        return DB::transaction(function () use ($client, $notes) {
            $previousStatus = $client->status;

            // Resolve the correct MikroTik profile before saving to DB.
            // mikrotik_profile_name might be 'Inactive' if client was deleted while inactive.
            // Priority: previous_mikrotik_profile → mikrotik_profile_name (if not 'Inactive') → package profile
            $resolvedProfile = null;
            if (!empty($client->previous_mikrotik_profile) && strtolower($client->previous_mikrotik_profile) !== 'inactive') {
                $resolvedProfile = $client->previous_mikrotik_profile;
            } elseif (!empty($client->mikrotik_profile_name) && strtolower($client->mikrotik_profile_name) !== 'inactive') {
                $resolvedProfile = $client->mikrotik_profile_name;
            } else {
                $client->loadMissing('package');
                if ($client->package) {
                    $resolvedProfile = $client->package->mikrotik_profile ?? $client->package->code ?? $client->package->name;
                }
            }

            $updateData = [
                'status' => 'active',
                'deleted_reason' => null,
                'deleted_by' => null,
                'deleted_at_custom' => null,
                'previous_mikrotik_profile' => null,
            ];
            if ($resolvedProfile) {
                $updateData['mikrotik_profile_name'] = $resolvedProfile;
            }

            $client->update($updateData);

            if ($client->username && $client->connection_type === 'pppoe') {
                $this->recreateMikroTikSecret($client);
            }

            ClientRecoveryLog::create([
                'client_id' => $client->id,
                'recovered_by' => auth()->id(),
                'previous_status' => $previousStatus,
                'notes' => $notes,
            ]);

            return $client->fresh();
        });
    }

    protected function recreateMikroTikSecret(Client $client): void
    {
        try {
            $deviceId = $client->mikrotik_device_id;
            $device = $deviceId ? MikrotikDevice::find($deviceId) : MikrotikDevice::where('is_active', true)->first();

            if (!$device) {
                Log::warning("Client restore: No MikroTik device found for client '{$client->username}'");
                return;
            }

            $mk = new SimpleMikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

            // Use previous_mikrotik_profile first (saved before disable/expire).
            // mikrotik_profile_name may be 'Inactive' if client was deleted while inactive — never use that as restore profile.
            $profile = null;
            if (!empty($client->previous_mikrotik_profile) && strtolower($client->previous_mikrotik_profile) !== 'inactive') {
                $profile = $client->previous_mikrotik_profile;
            }
            if (empty($profile)) {
                $raw = $client->mikrotik_profile_name;
                if (!empty($raw) && strtolower($raw) !== 'inactive') {
                    $profile = $raw;
                }
            }
            if (empty($profile)) {
                $client->loadMissing('package');
                if ($client->package) {
                    $profile = $client->package->mikrotik_profile ?? $client->package->code ?? $client->package->name;
                }
            }

            if (empty($profile)) {
                Log::warning("Client restore: No MikroTik profile found for client '{$client->username}'");
                return;
            }

            $password = $client->pppoe_password ?? $client->username;
            $comment = "Client: {$client->client_id} - {$client->name}";

            $result = $mk->createPPPoESecret($client->username, $password, $profile, $comment, false);

            if (!empty($result['success'])) {
                Log::info("Client restore: MikroTik PPPoE secret '{$client->username}' created on {$device->name} with profile '{$profile}'");
            } else {
                $existingCheck = $result['message'] ?? '';
                if (str_contains($existingCheck, 'already exists')) {
                    // Secret exists (inactive client restore): change profile back from 'Inactive' to real profile, then enable
                    $mk->changePPPoEProfile($client->username, $profile);
                    $mk->enablePPPoESecret($client->username);
                    Log::info("Client restore: MikroTik PPPoE secret '{$client->username}' already exists — profile changed to '{$profile}' and enabled on {$device->name}");
                } else {
                    Log::warning("Client restore: Failed to create MikroTik PPPoE secret '{$client->username}': " . ($result['message'] ?? 'Unknown error'));
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Client restore: MikroTik error for '{$client->username}': " . $e->getMessage());
        }
    }

    public function getDeletedClients(array $filters = [])
    {
        $query = Client::withoutGlobalScope('exclude_deleted')
            ->where('status', 'deleted')
            ->with(['zone', 'package', 'deletedBy']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('client_id', 'ILIKE', "%{$search}%")
                  ->orWhere('phone', 'ILIKE', "%{$search}%")
                  ->orWhere('username', 'ILIKE', "%{$search}%");
            });
        }

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }

        return $query->orderBy('deleted_at_custom', 'desc')->paginate(20);
    }

    public function getRecoveryHistory(int $clientId)
    {
        return ClientRecoveryLog::where('client_id', $clientId)
            ->with('recoveredByUser')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
