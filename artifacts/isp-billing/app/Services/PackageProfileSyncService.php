<?php

namespace App\Services;

use App\Models\Package;
use App\Models\MikrotikDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageProfileSyncService
{
    protected SimpleMikroTikService $mikrotik;

    public function __construct()
    {
        $this->mikrotik = new SimpleMikroTikService();
    }

    public function syncProfileOnCreate(Package $package): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => [], 'skipped' => 0];
        $devices = MikrotikDevice::where('is_active', true)->get();

        if ($devices->isEmpty()) {
            $results['errors'][] = 'No active MikroTik devices found';
            return $results;
        }

        foreach ($devices as $device) {
            try {
                $connected = $this->mikrotik->connect(
                    $device->getRawOriginal('host') ?? $device->host,
                    $device->username,
                    $device->password,
                    (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
                );

                if (!$connected) {
                    $results['failed']++;
                    $results['errors'][] = "Cannot connect to {$device->name}";
                    continue;
                }

                $profileName = $package->mikrotik_profile ?: $package->code ?: $package->name;

                if ($this->mikrotik->profileExists($profileName)) {
                    $updated = $this->updateProfileOnDevice($package, $profileName);
                    if ($updated) {
                        $results['success']++;
                        $this->logAudit($device, $package, 'profile_updated', "Updated PPP profile '{$profileName}' speeds to {$package->download_speed}/{$package->upload_speed} {$package->speed_unit}");
                    } else {
                        $results['skipped']++;
                        $this->logAudit($device, $package, 'profile_update_failed', "Failed to update profile '{$profileName}'");
                    }
                } else {
                    $created = $this->createProfileOnDevice($package);
                    if ($created) {
                        $results['success']++;
                        $this->logAudit($device, $package, 'profile_created', "Created PPP profile '{$profileName}' with {$package->download_speed}/{$package->upload_speed} {$package->speed_unit}");
                    } else {
                        $results['failed']++;
                        $this->logAudit($device, $package, 'profile_create_failed', "Failed to create profile '{$profileName}'");
                    }
                }

                $this->mikrotik->disconnect();
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$device->name}: {$e->getMessage()}";
                Log::error("Profile sync failed for device {$device->name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    public function syncProfileOnUpdate(Package $package, ?string $oldName = null): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        $devices = MikrotikDevice::where('is_active', true)->get();

        foreach ($devices as $device) {
            try {
                $connected = $this->mikrotik->connect(
                    $device->getRawOriginal('host') ?? $device->host,
                    $device->username,
                    $device->password,
                    (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
                );

                if (!$connected) {
                    $results['failed']++;
                    $results['errors'][] = "Cannot connect to {$device->name}";
                    continue;
                }

                $profileName = $package->mikrotik_profile ?: $package->code ?: $package->name;

                if ($this->mikrotik->profileExists($profileName)) {
                    $this->updateProfileOnDevice($package, $profileName);
                    $results['success']++;
                    $this->logAudit($device, $package, 'profile_updated', "Updated PPP profile '{$profileName}' speeds to {$package->download_speed}/{$package->upload_speed} {$package->speed_unit}");
                } else {
                    $this->createProfileOnDevice($package);
                    $results['success']++;
                    $this->logAudit($device, $package, 'profile_created', "Created PPP profile '{$profileName}' (was missing)");
                }

                $this->mikrotik->disconnect();
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$device->name}: {$e->getMessage()}";
                Log::error("Profile update sync failed for device {$device->name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    protected function createProfileOnDevice(Package $package): bool
    {
        $speedUnit = strtolower($package->speed_unit ?? 'Mbps');
        $suffix = $speedUnit === 'kbps' ? 'k' : 'M';

        $rateLimit = "{$package->upload_speed}{$suffix}/{$package->download_speed}{$suffix}";

        $remoteAddress = $this->detectRemoteAddress();

        $profileName = $package->mikrotik_profile ?: $package->code ?: $package->name;

        $params = [
            'name' => $profileName,
            'local-address' => '10.10.10.1',
            'remote-address' => $remoteAddress,
            'rate-limit' => $rateLimit,
        ];

        $response = $this->mikrotik->sendCommand('/ppp/profile/add', $params);
        return $this->isSuccess($response);
    }

    protected function updateProfileOnDevice(Package $package, ?string $profileName = null): bool
    {
        $name = $profileName ?? $package->name;
        $speedUnit = strtolower($package->speed_unit ?? 'Mbps');
        $suffix = $speedUnit === 'kbps' ? 'k' : 'M';

        $rateLimit = "{$package->upload_speed}{$suffix}/{$package->download_speed}{$suffix}";

        $response = $this->mikrotik->sendCommand('/ppp/profile/print', ['?name' => $name]);
        $profiles = $this->mikrotik->parseResponse($response);

        if (empty($profiles)) {
            return $this->createProfileOnDevice($package);
        }

        $id = $profiles[0]['.id'] ?? null;
        if (!$id) return false;

        $params = ['.id' => $id, 'rate-limit' => $rateLimit];

        $response = $this->mikrotik->sendCommand('/ppp/profile/set', $params);
        return $this->isSuccess($response);
    }

    public function syncAllPackages(): array
    {
        $results = ['synced' => 0, 'created' => 0, 'updated' => 0, 'errors' => []];
        $packages = Package::where('is_active', true)->get();
        $devices = MikrotikDevice::where('is_active', true)->get();

        foreach ($devices as $device) {
            try {
                $connected = $this->mikrotik->connect(
                    $device->getRawOriginal('host') ?? $device->host,
                    $device->username,
                    $device->password,
                    (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
                );

                if (!$connected) {
                    $results['errors'][] = "Cannot connect to {$device->name}";
                    continue;
                }

                foreach ($packages as $package) {
                    $profileName = $package->mikrotik_profile ?: $package->code ?: $package->name;
                    if ($this->mikrotik->profileExists($profileName)) {
                        $this->updateProfileOnDevice($package, $profileName);
                        $results['updated']++;
                    } else {
                        $this->createProfileOnDevice($package);
                        $results['created']++;
                    }
                    $results['synced']++;
                }

                $this->mikrotik->disconnect();
            } catch (\Exception $e) {
                $results['errors'][] = "{$device->name}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    public function importProfilesFromRouters(): array
    {
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'updated' => 0,
            'errors' => [],
            'profiles_found' => [],
        ];

        $devices = MikrotikDevice::where('is_active', true)->get();

        if ($devices->isEmpty()) {
            $results['errors'][] = 'No active MikroTik devices found';
            return $results;
        }

        $allProfiles = [];

        foreach ($devices as $device) {
            try {
                $connected = $this->mikrotik->connect(
                    $device->getRawOriginal('host') ?? $device->host,
                    $device->username,
                    $device->password,
                    (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
                );

                if (!$connected) {
                    $results['errors'][] = "Cannot connect to {$device->name}";
                    continue;
                }

                $profiles = $this->mikrotik->getProfiles();

                foreach ($profiles as $profile) {
                    $name = $profile['name'] ?? '';
                    if (empty($name) || $name === 'default' || $name === 'default-encryption') {
                        continue;
                    }

                    if (!isset($allProfiles[$name])) {
                        $allProfiles[$name] = $profile;
                        $allProfiles[$name]['_source_device'] = $device->name;
                    }
                }

                $this->mikrotik->disconnect();
            } catch (\Exception $e) {
                $results['errors'][] = "{$device->name}: {$e->getMessage()}";
                Log::error("Profile import failed for device {$device->name}: " . $e->getMessage());
            }
        }

        $routerProfileNames = array_keys($allProfiles);

        foreach ($allProfiles as $name => $profile) {
            $results['profiles_found'][] = $name;

            $rateLimit = $profile['rate-limit'] ?? '';
            $downloadSpeed = 0;
            $uploadSpeed = 0;
            $speedUnit = 'Mbps';

            if (!empty($rateLimit)) {
                $parsed = $this->parseRateLimit($rateLimit);
                $uploadSpeed = $parsed['upload'];
                $downloadSpeed = $parsed['download'];
                $speedUnit = $parsed['unit'];
            }

            $existing = Package::where('mikrotik_profile', $name)
                ->orWhere('code', $name)
                ->orWhere('name', $name)
                ->first();

            if ($existing) {
                $changed = false;
                if ($downloadSpeed > 0 && $existing->download_speed != $downloadSpeed) {
                    $existing->download_speed = $downloadSpeed;
                    $changed = true;
                }
                if ($uploadSpeed > 0 && $existing->upload_speed != $uploadSpeed) {
                    $existing->upload_speed = $uploadSpeed;
                    $changed = true;
                }
                if (!$existing->is_active) {
                    $existing->is_active = true;
                    $changed = true;
                }
                if ($changed) {
                    $existing->bandwidth = "{$downloadSpeed}/{$uploadSpeed} {$speedUnit}";
                    $existing->speed_unit = $speedUnit;
                    $existing->save();
                    $results['updated']++;
                } else {
                    $results['skipped']++;
                }
            } else {
                $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name));
                $codeBase = $code;
                $counter = 1;
                while (Package::where('code', $code)->exists()) {
                    $code = $codeBase . '_' . $counter;
                    $counter++;
                }

                Package::create([
                    'name' => $name,
                    'code' => $code,
                    'mikrotik_profile' => $name,
                    'price' => 0,
                    'bandwidth' => $downloadSpeed > 0 ? "{$downloadSpeed}/{$uploadSpeed} {$speedUnit}" : 'From Router',
                    'download_speed' => $downloadSpeed,
                    'upload_speed' => $uploadSpeed,
                    'speed_unit' => $speedUnit,
                    'connection_type' => 'PPPoE',
                    'validity_days' => 30,
                    'description' => "Imported from MikroTik router ({$profile['_source_device']})",
                    'is_active' => true,
                ]);
                $results['imported']++;
            }
        }

        if (!empty($routerProfileNames)) {
            $results['removed'] = 0;
            $removedPackages = Package::where('is_active', true)
                ->whereNotIn('mikrotik_profile', $routerProfileNames)
                ->where(function ($q) use ($routerProfileNames) {
                    $q->whereNotIn('code', $routerProfileNames)
                      ->whereNotIn('name', $routerProfileNames);
                })
                ->whereDoesntHave('clients', function ($q) {
                    $q->whereIn('status', ['active', 'inactive']);
                })
                ->get();

            foreach ($removedPackages as $pkg) {
                $pkg->delete();
                $results['removed']++;
            }

            $deactivated = Package::where('is_active', true)
                ->whereNotIn('mikrotik_profile', $routerProfileNames)
                ->where(function ($q) use ($routerProfileNames) {
                    $q->whereNotIn('code', $routerProfileNames)
                      ->whereNotIn('name', $routerProfileNames);
                })
                ->whereHas('clients', function ($q) {
                    $q->whereIn('status', ['active', 'inactive']);
                })
                ->update(['is_active' => false]);

            if ($deactivated > 0) {
                $results['deactivated'] = $deactivated;
            }
        }

        return $results;
    }

    protected function parseRateLimit(string $rateLimit): array
    {
        $parts = explode('/', $rateLimit);
        $upload = 0;
        $download = 0;
        $unit = 'Mbps';

        if (count($parts) >= 2) {
            $uploadRaw = trim($parts[0]);
            $downloadRaw = trim($parts[1]);

            $upload = $this->parseSpeedValue($uploadRaw);
            $download = $this->parseSpeedValue($downloadRaw);

            if (stripos($uploadRaw, 'k') !== false || stripos($downloadRaw, 'k') !== false) {
                if ($upload < 1024 && $download < 1024) {
                    $unit = 'Kbps';
                } else {
                    $upload = round($upload / 1024, 1);
                    $download = round($download / 1024, 1);
                    $unit = 'Mbps';
                }
            }
        } elseif (count($parts) === 1) {
            $download = $this->parseSpeedValue(trim($parts[0]));
            $upload = $download;
        }

        return ['upload' => (int) $upload, 'download' => (int) $download, 'unit' => $unit];
    }

    protected function parseSpeedValue(string $value): float
    {
        $value = trim($value);
        if (preg_match('/([\d.]+)\s*(M|k|G)?/i', $value, $matches)) {
            $num = (float) $matches[1];
            $suffix = strtolower($matches[2] ?? 'M');
            return match($suffix) {
                'g' => $num * 1024,
                'm' => $num,
                'k' => $num / 1024,
                default => $num,
            };
        }
        return (float) $value;
    }

    protected function detectRemoteAddress(): string
    {
        try {
            $response = $this->mikrotik->sendCommand('/ppp/profile/print', []);
            $profiles = $this->mikrotik->parseResponse($response);

            foreach ($profiles as $profile) {
                $name = $profile['name'] ?? '';
                if ($name === 'default' || $name === 'default-encryption') {
                    continue;
                }
                $remoteAddr = $profile['remote-address'] ?? '';
                if (!empty($remoteAddr)) {
                    return $remoteAddr;
                }
            }

            foreach ($profiles as $profile) {
                $remoteAddr = $profile['remote-address'] ?? '';
                if (!empty($remoteAddr)) {
                    return $remoteAddr;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Could not detect remote-address from existing profiles: " . $e->getMessage());
        }

        return 'pool';
    }

    protected function isSuccess(array $response): bool
    {
        return in_array('!done', $response);
    }

    protected function logAudit(MikrotikDevice $device, Package $package, string $action, string $message): void
    {
        try {
            DB::table('mikrotik_action_logs')->insert([
                'device_id' => $device->id,
                'action' => '/ppp/profile',
                'action_type' => $action,
                'username' => $package->name,
                'router_ip' => $device->getRawOriginal('host') ?? $device->host,
                'trigger_source' => 'profile_sync',
                'triggered_by' => auth()->id() ?? 1,
                'status' => 'success',
                'request_data' => json_encode(['package_id' => $package->id, 'name' => $package->name]),
                'response_data' => json_encode(['message' => $message]),
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log mikrotik action: ' . $e->getMessage());
        }
    }
}
