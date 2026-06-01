<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikProfile;
use App\Models\MikrotikDevice;
use App\Services\SimpleMikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MikrotikProfileController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.settings.packages', ['tab' => 'profiles']);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('mikrotik_profiles')->where('company_id', $companyId),
            ],
        ]);

        $profile = MikrotikProfile::create([
            'name' => $request->name,
            'download_speed' => 0,
            'upload_speed' => 0,
            'speed_unit' => 'Mbps',
            'rate_limit' => '',
            'local_address' => null,
            'remote_address' => null,
            'profile_type' => 'ppp',
            'is_active' => true,
        ]);

        $routerIds = $request->input('router_ids', []);
        if (in_array('all', $routerIds)) {
            $routerIds = [];
        }
        $routerMsg = $this->createProfileOnRouters($profile, $routerIds);

        $msg = 'Profile created successfully.';
        if ($routerMsg) {
            $msg .= ' ' . $routerMsg;
        }

        return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('success', $msg);
    }

    public function update(Request $request, MikrotikProfile $mikrotikProfile)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('mikrotik_profiles')->where('company_id', $companyId)->ignore($mikrotikProfile->id),
            ],
        ]);

        $oldName = $mikrotikProfile->name;
        $newName = $request->name;
        $nameChanged = ($oldName !== $newName);

        if ($nameChanged) {
            \App\Models\Client::where('mikrotik_profile_name', $oldName)->update(['mikrotik_profile_name' => $newName]);
            \App\Models\Package::where('mikrotik_profile', $oldName)->update(['mikrotik_profile' => $newName]);
        }

        $mikrotikProfile->update([
            'name' => $newName,
            'is_active' => $request->has('is_active'),
        ]);

        $routerIds = $request->input('router_ids', []);
        if (in_array('all', $routerIds)) {
            $routerIds = [];
        }
        $routerMsg = $this->syncSingleProfileToRouters($oldName, $mikrotikProfile, $routerIds);

        $msg = 'Profile updated successfully.';
        if ($routerMsg) {
            $msg .= ' ' . $routerMsg;
        }

        return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('success', $msg);
    }

    public function destroy(MikrotikProfile $mikrotikProfile)
    {
        $clientCount = $mikrotikProfile->clients()->count();
        if ($clientCount > 0) {
            return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('error', "Cannot delete profile with {$clientCount} assigned client(s).");
        }
        $mikrotikProfile->delete();
        return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('success', 'Profile deleted successfully.');
    }

    public function syncFromRouter()
    {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'renamed' => 0,
            'skipped' => 0,
            'removed' => 0,
            'errors' => [],
            'profiles_found' => [],
            'rename_details' => [],
        ];

        $devices = MikrotikDevice::where('is_active', true)->get();

        if ($devices->isEmpty()) {
            return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('error', 'No active MikroTik devices found. Please add a router first in Network → Routers.');
        }

        $mikrotikService = new SimpleMikroTikService();
        $allProfiles = [];

        foreach ($devices as $device) {
            try {
                $connected = $mikrotikService->connect(
                    $device->getRawOriginal('host') ?? $device->host,
                    $device->username,
                    $device->password,
                    (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
                );

                if (!$connected) {
                    $results['errors'][] = "Cannot connect to {$device->name}";
                    continue;
                }

                $profiles = $mikrotikService->getProfiles();

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

                $mikrotikService->disconnect();
            } catch (\Exception $e) {
                $results['errors'][] = "{$device->name}: {$e->getMessage()}";
                Log::error("Profile sync from router failed for {$device->name}: " . $e->getMessage());
            }
        }

        $routerProfileNames = array_keys($allProfiles);
        $results['profiles_found'] = $routerProfileNames;

        $dbProfiles = MikrotikProfile::all();
        $matchedDbIds = [];
        $newRouterProfiles = [];

        foreach ($allProfiles as $name => $profile) {
            $existing = $dbProfiles->where('name', $name)->first();
            if ($existing) {
                $matchedDbIds[] = $existing->id;
            } else {
                $newRouterProfiles[$name] = $profile;
            }
        }

        $unmatchedDbProfiles = $dbProfiles->filter(function ($p) use ($matchedDbIds) {
            return !in_array($p->id, $matchedDbIds);
        });

        foreach ($newRouterProfiles as $name => $profile) {
            $rateLimit = $profile['rate-limit'] ?? '';
            $downloadSpeed = 0;
            $uploadSpeed = 0;
            $speedUnit = 'Mbps';
            $localAddress = $profile['local-address'] ?? '';
            $remoteAddress = $profile['remote-address'] ?? '';

            if (!empty($rateLimit)) {
                $parsed = $this->parseRateLimit($rateLimit);
                $uploadSpeed = $parsed['upload'];
                $downloadSpeed = $parsed['download'];
                $speedUnit = $parsed['unit'];
            }

            $renamedFrom = null;
            if ($unmatchedDbProfiles->isNotEmpty()) {
                $renamedFrom = $this->findRenamedProfile($unmatchedDbProfiles, $rateLimit, $localAddress, $remoteAddress, $downloadSpeed, $uploadSpeed);
            }

            if ($renamedFrom) {
                $oldName = $renamedFrom->name;
                \App\Models\Client::where('mikrotik_profile_name', $oldName)
                    ->update(['mikrotik_profile_name' => $name]);
                \App\Models\Package::where('mikrotik_profile', $oldName)
                    ->update(['mikrotik_profile' => $name]);

                $renamedFrom->update([
                    'name' => $name,
                    'rate_limit' => !empty($rateLimit) ? $rateLimit : $renamedFrom->rate_limit,
                    'download_speed' => $downloadSpeed > 0 ? $downloadSpeed : $renamedFrom->download_speed,
                    'upload_speed' => $uploadSpeed > 0 ? $uploadSpeed : $renamedFrom->upload_speed,
                    'speed_unit' => $speedUnit,
                    'local_address' => !empty($localAddress) ? $localAddress : $renamedFrom->local_address,
                    'remote_address' => !empty($remoteAddress) ? $remoteAddress : $renamedFrom->remote_address,
                    'source_device' => $profile['_source_device'] ?? $renamedFrom->source_device,
                    'is_active' => true,
                ]);

                $unmatchedDbProfiles = $unmatchedDbProfiles->reject(function ($p) use ($renamedFrom) {
                    return $p->id === $renamedFrom->id;
                });
                $matchedDbIds[] = $renamedFrom->id;

                $results['renamed']++;
                $results['rename_details'][] = "{$oldName} → {$name}";
            } else {
                MikrotikProfile::create([
                    'name' => $name,
                    'rate_limit' => $rateLimit,
                    'download_speed' => $downloadSpeed,
                    'upload_speed' => $uploadSpeed,
                    'speed_unit' => $speedUnit,
                    'local_address' => $localAddress,
                    'remote_address' => $remoteAddress,
                    'source_device' => $profile['_source_device'] ?? null,
                    'profile_type' => 'ppp',
                    'is_active' => true,
                ]);
                $results['imported']++;
            }
        }

        foreach ($allProfiles as $name => $profile) {
            $existing = MikrotikProfile::where('name', $name)->first();
            if (!$existing) continue;

            $rateLimit = $profile['rate-limit'] ?? '';
            $localAddress = $profile['local-address'] ?? '';
            $remoteAddress = $profile['remote-address'] ?? '';
            $downloadSpeed = 0;
            $uploadSpeed = 0;
            $speedUnit = 'Mbps';

            if (!empty($rateLimit)) {
                $parsed = $this->parseRateLimit($rateLimit);
                $uploadSpeed = $parsed['upload'];
                $downloadSpeed = $parsed['download'];
                $speedUnit = $parsed['unit'];
            }

            $changed = false;
            if ($downloadSpeed > 0 && $existing->download_speed != $downloadSpeed) {
                $existing->download_speed = $downloadSpeed;
                $changed = true;
            }
            if ($uploadSpeed > 0 && $existing->upload_speed != $uploadSpeed) {
                $existing->upload_speed = $uploadSpeed;
                $changed = true;
            }
            if (!empty($rateLimit) && $existing->rate_limit !== $rateLimit) {
                $existing->rate_limit = $rateLimit;
                $changed = true;
            }
            if (!empty($localAddress) && $existing->local_address !== $localAddress) {
                $existing->local_address = $localAddress;
                $changed = true;
            }
            if (!empty($remoteAddress) && $existing->remote_address !== $remoteAddress) {
                $existing->remote_address = $remoteAddress;
                $changed = true;
            }
            if ($changed) {
                $existing->speed_unit = $speedUnit;
                $existing->is_active = true;
                $existing->save();
                $results['updated']++;
            } else {
                $results['skipped']++;
            }
        }

        $stillUnmatched = MikrotikProfile::whereNotIn('name', $routerProfileNames)->get();
        foreach ($stillUnmatched as $orphan) {
            if ($orphan->clients()->count() > 0) {
                $orphan->update(['is_active' => false]);
                $results['removed']++;
                $results['errors'][] = "'{$orphan->name}' deactivated (has clients assigned)";
            } else {
                $orphan->delete();
                $results['removed']++;
            }
        }

        $msg = "Sync complete:";
        $parts = [];
        if ($results['renamed'] > 0) {
            $parts[] = "{$results['renamed']} renamed (" . implode(', ', $results['rename_details']) . ")";
        }
        if ($results['imported'] > 0) {
            $parts[] = "{$results['imported']} new imported";
        }
        if ($results['updated'] > 0) {
            $parts[] = "{$results['updated']} updated";
        }
        if ($results['skipped'] > 0) {
            $parts[] = "{$results['skipped']} unchanged";
        }
        if ($results['removed'] > 0) {
            $parts[] = "{$results['removed']} removed/deactivated (not on router)";
        }
        $msg .= ' ' . (empty($parts) ? 'no changes' : implode(', ', $parts));
        if (!empty($results['errors'])) {
            $msg .= ". Warnings: " . implode('; ', $results['errors']);
        }
        if (!empty($results['profiles_found'])) {
            $msg .= ". Router profiles: " . implode(', ', $results['profiles_found']);
        }

        return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('success', $msg);
    }

    protected function findRenamedProfile($unmatchedProfiles, string $rateLimit, string $localAddress, string $remoteAddress, int $downloadSpeed, int $uploadSpeed)
    {
        $bestMatch = null;
        $bestScore = 0;
        $secondBestScore = 0;

        foreach ($unmatchedProfiles as $dbProfile) {
            $score = 0;
            $matchedFields = 0;

            if (!empty($rateLimit) && !empty($dbProfile->rate_limit) && $dbProfile->rate_limit === $rateLimit) {
                $score += 5;
                $matchedFields++;
            }

            if ($downloadSpeed > 0 && $dbProfile->download_speed == $downloadSpeed) {
                $score += 3;
                $matchedFields++;
            }
            if ($uploadSpeed > 0 && $dbProfile->upload_speed == $uploadSpeed) {
                $score += 3;
                $matchedFields++;
            }

            if (!empty($localAddress) && !empty($dbProfile->local_address) && $dbProfile->local_address === $localAddress) {
                $score += 2;
                $matchedFields++;
            }
            if (!empty($remoteAddress) && !empty($dbProfile->remote_address) && $dbProfile->remote_address === $remoteAddress) {
                $score += 2;
                $matchedFields++;
            }

            if ($matchedFields < 2) {
                continue;
            }

            if ($score > $bestScore) {
                $secondBestScore = $bestScore;
                $bestScore = $score;
                $bestMatch = $dbProfile;
            } elseif ($score > $secondBestScore) {
                $secondBestScore = $score;
            }
        }

        if ($bestScore >= 5 && $bestScore > $secondBestScore) {
            return $bestMatch;
        }

        return null;
    }

    public function pushToRouter()
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        $profiles = MikrotikProfile::where('is_active', true)->get();
        $devices = MikrotikDevice::where('is_active', true)->get();

        if ($devices->isEmpty()) {
            return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('error', 'No active MikroTik devices found.');
        }
        if ($profiles->isEmpty()) {
            return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('error', 'No active profiles to push.');
        }

        $mikrotikService = new SimpleMikroTikService();

        foreach ($devices as $device) {
            try {
                $connected = $mikrotikService->connect(
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

                foreach ($profiles as $profile) {
                    try {
                        $suffix = strtolower($profile->speed_unit) === 'kbps' ? 'k' : 'M';
                        $rateLimit = "{$profile->upload_speed}{$suffix}/{$profile->download_speed}{$suffix}";

                        if ($mikrotikService->profileExists($profile->name)) {
                            $response = $mikrotikService->sendCommand('/ppp/profile/print', ['?name' => $profile->name]);
                            $existingProfiles = $mikrotikService->parseResponse($response);
                            if (!empty($existingProfiles)) {
                                $id = $existingProfiles[0]['.id'] ?? null;
                                if ($id) {
                                    $mikrotikService->sendCommand('/ppp/profile/set', [
                                        '.id' => $id,
                                        'rate-limit' => $rateLimit,
                                    ]);
                                }
                            }
                        } else {
                            $params = [
                                'name' => $profile->name,
                                'rate-limit' => $rateLimit,
                            ];
                            if (!empty($profile->local_address)) {
                                $params['local-address'] = $profile->local_address;
                            }
                            if (!empty($profile->remote_address)) {
                                $params['remote-address'] = $profile->remote_address;
                            }
                            $mikrotikService->sendCommand('/ppp/profile/add', $params);
                        }
                        $results['success']++;
                    } catch (\Exception $e) {
                        $results['errors'][] = "{$device->name}/{$profile->name}: {$e->getMessage()}";
                    }
                }

                $mikrotikService->disconnect();
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$device->name}: {$e->getMessage()}";
            }
        }

        $msg = "Push complete: {$results['success']} profile(s) synced to router(s)";
        if (!empty($results['errors'])) {
            $msg .= ". Warnings: " . implode('; ', $results['errors']);
        }

        return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('success', $msg);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('error', 'No profiles selected.');
        }

        $deleted = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $profile = MikrotikProfile::find($id);
            if ($profile) {
                if ($profile->clients()->count() > 0) {
                    $skipped++;
                } else {
                    $profile->delete();
                    $deleted++;
                }
            }
        }

        $msg = "$deleted profile(s) deleted.";
        if ($skipped > 0) $msg .= " $skipped skipped (have clients).";
        return redirect()->route('admin.settings.packages', ['tab' => 'profiles'])->with('success', $msg);
    }

    protected function createProfileOnRouters(MikrotikProfile $profile, array $routerIds = []): string
    {
        if (!empty($routerIds)) {
            $devices = MikrotikDevice::whereIn('id', $routerIds)->where('is_active', true)->get();
        } else {
            $devices = MikrotikDevice::where('is_active', true)->get();
        }
        if ($devices->isEmpty()) {
            return 'Router sync skipped: no active routers selected.';
        }

        $mikrotikService = new SimpleMikroTikService();
        $created = 0;
        $errors = [];

        $suffix = strtolower($profile->speed_unit) === 'kbps' ? 'k' : 'M';
        $rateLimit = "{$profile->upload_speed}{$suffix}/{$profile->download_speed}{$suffix}";

        foreach ($devices as $device) {
            try {
                $connected = $mikrotikService->connect(
                    $device->getRawOriginal('host') ?? $device->host,
                    $device->username,
                    $device->password,
                    (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
                );

                if (!$connected) {
                    $errors[] = "Cannot connect to {$device->name}";
                    continue;
                }

                if ($mikrotikService->profileExists($profile->name)) {
                    $response = $mikrotikService->sendCommand('/ppp/profile/print', ['?name' => $profile->name]);
                    $existingProfiles = $mikrotikService->parseResponse($response);
                    $id = $existingProfiles[0]['.id'] ?? null;
                    if ($id) {
                        $setParams = ['.id' => $id, 'rate-limit' => $rateLimit];
                        if (!empty($profile->local_address)) {
                            $setParams['local-address'] = $profile->local_address;
                        }
                        if (!empty($profile->remote_address)) {
                            $setParams['remote-address'] = $profile->remote_address;
                        }
                        $mikrotikService->sendCommand('/ppp/profile/set', $setParams);
                        $created++;
                    }
                } else {
                    $params = [
                        'name' => $profile->name,
                        'rate-limit' => $rateLimit,
                    ];
                    if (!empty($profile->local_address)) {
                        $params['local-address'] = $profile->local_address;
                    }
                    if (!empty($profile->remote_address)) {
                        $params['remote-address'] = $profile->remote_address;
                    }
                    $mikrotikService->sendCommand('/ppp/profile/add', $params);
                    $created++;
                }

                $mikrotikService->disconnect();
            } catch (\Exception $e) {
                $errors[] = "{$device->name}: {$e->getMessage()}";
                Log::error("Profile create on router failed for {$device->name}: " . $e->getMessage());
            }
        }

        $msg = "Router: created on {$created} device(s).";
        if (!empty($errors)) {
            $msg .= ' Warnings: ' . implode('; ', $errors);
        }
        return $msg;
    }

    protected function syncSingleProfileToRouters(string $oldName, MikrotikProfile $profile, array $routerIds = []): string
    {
        if (!empty($routerIds)) {
            $devices = MikrotikDevice::whereIn('id', $routerIds)->where('is_active', true)->get();
        } else {
            $devices = MikrotikDevice::where('is_active', true)->get();
        }
        if ($devices->isEmpty()) {
            return 'Router sync skipped: no active routers selected.';
        }

        $mikrotikService = new SimpleMikroTikService();
        $synced = 0;
        $errors = [];
        $nameChanged = ($oldName !== $profile->name);

        $suffix = strtolower($profile->speed_unit) === 'kbps' ? 'k' : 'M';
        $rateLimit = "{$profile->upload_speed}{$suffix}/{$profile->download_speed}{$suffix}";

        foreach ($devices as $device) {
            try {
                $connected = $mikrotikService->connect(
                    $device->getRawOriginal('host') ?? $device->host,
                    $device->username,
                    $device->password,
                    (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
                );

                if (!$connected) {
                    $errors[] = "Cannot connect to {$device->name}";
                    continue;
                }

                $oldExists = $mikrotikService->profileExists($oldName);
                $newExists = $nameChanged ? $mikrotikService->profileExists($profile->name) : false;

                $setParams = [
                    'rate-limit' => $rateLimit,
                ];
                if (!empty($profile->local_address)) {
                    $setParams['local-address'] = $profile->local_address;
                }
                if (!empty($profile->remote_address)) {
                    $setParams['remote-address'] = $profile->remote_address;
                }

                if ($oldExists && $nameChanged && $newExists) {
                    $response = $mikrotikService->sendCommand('/ppp/profile/print', ['?name' => $profile->name]);
                    $existingProfiles = $mikrotikService->parseResponse($response);
                    $id = $existingProfiles[0]['.id'] ?? null;
                    if ($id) {
                        $mikrotikService->sendCommand('/ppp/profile/set', array_merge(['.id' => $id], $setParams));
                        $synced++;
                    }
                    $oldResponse = $mikrotikService->sendCommand('/ppp/profile/print', ['?name' => $oldName]);
                    $oldProfiles = $mikrotikService->parseResponse($oldResponse);
                    $oldId = $oldProfiles[0]['.id'] ?? null;
                    if ($oldId) {
                        try {
                            $mikrotikService->sendCommand('/ppp/profile/remove', ['.id' => $oldId]);
                        } catch (\Exception $e) {
                            $errors[] = "{$device->name}: could not remove old profile '{$oldName}': {$e->getMessage()}";
                        }
                    }
                } elseif ($oldExists) {
                    $response = $mikrotikService->sendCommand('/ppp/profile/print', ['?name' => $oldName]);
                    $existingProfiles = $mikrotikService->parseResponse($response);
                    $id = $existingProfiles[0]['.id'] ?? null;
                    if ($id) {
                        if ($nameChanged) {
                            $setParams['name'] = $profile->name;
                        }
                        $mikrotikService->sendCommand('/ppp/profile/set', array_merge(['.id' => $id], $setParams));
                        $synced++;
                    } else {
                        $errors[] = "{$device->name}: old profile '{$oldName}' found but no .id returned";
                    }
                } elseif ($newExists) {
                    $response = $mikrotikService->sendCommand('/ppp/profile/print', ['?name' => $profile->name]);
                    $existingProfiles = $mikrotikService->parseResponse($response);
                    $id = $existingProfiles[0]['.id'] ?? null;
                    if ($id) {
                        $mikrotikService->sendCommand('/ppp/profile/set', array_merge(['.id' => $id], $setParams));
                        $synced++;
                    } else {
                        $errors[] = "{$device->name}: profile '{$profile->name}' found but no .id returned";
                    }
                } else {
                    $addParams = array_merge(['name' => $profile->name], $setParams);
                    $mikrotikService->sendCommand('/ppp/profile/add', $addParams);
                    $synced++;
                }

                $mikrotikService->disconnect();
            } catch (\Exception $e) {
                $errors[] = "{$device->name}: {$e->getMessage()}";
                Log::error("Profile sync to router failed for {$device->name}: " . $e->getMessage());
            }
        }

        $msg = "Router: {$synced} device(s) synced.";
        if (!empty($errors)) {
            $msg .= ' Warnings: ' . implode('; ', $errors);
        }
        return $msg;
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
}
