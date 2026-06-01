<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikDevice;
use App\Models\MikrotikUser;
use App\Models\BandwidthQueue;
use App\Models\OltDevice;
use App\Models\Zone;
use App\Models\Client;
use App\Models\Box;
use App\Models\Area;
use App\Models\Package;
use App\Models\ActivityLog;
use App\Services\NetworkIntegrationService;
use App\Services\ClientActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NetworkController extends Controller
{
    public function devices()
    {
        $devices = MikrotikDevice::with(['zone', 'vpnAccount'])->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $zones = Zone::where('is_active', true)->get();
        return view('admin.network.devices', compact('devices', 'zones'));
    }

    public function createDevice()
    {
        $zones = Zone::where('is_active', true)->get();
        $vpnAccounts = \App\Models\VpnAccount::where('is_active', true)->get();
        return view('admin.network.device-create', compact('zones', 'vpnAccounts'));
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'host' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'www_port' => 'required|integer|min:1|max:65535',
            'vpn_account_id' => 'nullable|exists:vpn_accounts,id',
            'overwrite_ppp_comment' => 'required|in:0,1',
        ]);

        $data = $request->only([
            'name', 'host', 'port', 'www_port', 'username', 'password',
            'zone_id', 'description', 'vpn_account_id', 'router_type',
            'model', 'latitude', 'longitude',
        ]);
        $data['overwrite_ppp_comment'] = (bool) $request->input('overwrite_ppp_comment');
        $data['is_active'] = $request->has('is_active');
        $data['company_id'] = auth()->user()->company_id ?? 1;

        MikrotikDevice::create($data);
        return redirect()->route('admin.network.devices')->with('success', 'MikroTik device added successfully.');
    }

    /**
     * Bulk import MikroTik devices
     * Format: name,host,port,username,password (one per line)
     * OR: name,host,username,password (port defaults to 8728)
     */
    public function storeBulkDevices(Request $request)
    {
        $request->validate([
            'bulk_data' => 'required|string',
        ]);

        $lines = array_filter(array_map('trim', explode("\n", $request->bulk_data)));
        $created = 0;
        $errors = [];
        $defaultPort = 8728;

        foreach ($lines as $index => $line) {
            $lineNum = $index + 1;
            
            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse CSV: name,host,port,username,password OR name,host,username,password
            $parts = array_map('trim', str_getcsv($line));
            
            if (count($parts) < 4) {
                $errors[] = "Line {$lineNum}: Not enough fields (need: name,host,username,password)";
                continue;
            }

            // Determine format: 5 fields = with port, 4 fields = without port
            if (count($parts) >= 5) {
                [$name, $host, $port, $username, $password] = $parts;
                $port = (int) $port ?: $defaultPort;
            } else {
                [$name, $host, $username, $password] = $parts;
                $port = $defaultPort;
            }

            // Validate required fields
            if (empty($name) || empty($host) || empty($username) || empty($password)) {
                $errors[] = "Line {$lineNum}: Missing required field (name, host, username, or password)";
                continue;
            }

            // Check if device with same host already exists
            if (MikrotikDevice::where('host', $host)->exists()) {
                $errors[] = "Line {$lineNum}: Router with host '{$host}' already exists";
                continue;
            }

            try {
                MikrotikDevice::create([
                    'name' => $name,
                    'host' => $host,
                    'port' => $port,
                    'username' => $username,
                    'password' => $password,
                    'is_active' => true,
                    'zone_id' => $request->zone_id,
                ]);
                $created++;
            } catch (\Exception $e) {
                $errors[] = "Line {$lineNum}: " . $e->getMessage();
            }
        }

        $message = "{$created} router(s) added successfully.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " ...and " . (count($errors) - 5) . " more.";
            }
        }

        return back()->with($created > 0 ? 'success' : 'error', $message);
    }

    public function editDevice(MikrotikDevice $device)
    {
        $zones = Zone::where('is_active', true)->get();
        $vpnAccounts = \App\Models\VpnAccount::where('is_active', true)->get();
        return view('admin.network.device-edit', compact('device', 'zones', 'vpnAccounts'));
    }

    public function updateDevice(Request $request, MikrotikDevice $device)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'host' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'port' => 'required|integer|min:1|max:65535',
            'www_port' => 'required|integer|min:1|max:65535',
            'vpn_account_id' => 'nullable|exists:vpn_accounts,id',
            'overwrite_ppp_comment' => 'required|in:0,1',
        ]);

        $data = $request->only([
            'name', 'host', 'port', 'www_port', 'username', 'zone_id',
            'description', 'model', 'latitude', 'longitude',
            'vpn_account_id', 'router_type',
        ]);
        $data['overwrite_ppp_comment'] = (bool) $request->input('overwrite_ppp_comment');
        $data['is_active'] = $request->has('is_active');
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }
        $device->update($data);
        return redirect()->route('admin.network.devices')->with('success', 'MikroTik device updated successfully.');
    }

    public function destroyDevice(MikrotikDevice $device)
    {
        $device->delete();
        return back()->with('success', 'MikroTik device deleted successfully.');
    }

    public function testDevice(MikrotikDevice $device)
    {
        try {
            $api = \App\Services\MikroTikService::fromDevice($device);

            if (!$api->connect()) {
                $device->update(['status' => 'offline', 'last_checked_at' => now()]);
                return response()->json([
                    'success' => false,
                    'api_status' => 'FAIL',
                    'message' => 'API: FAIL - Could not connect to router.',
                ]);
            }

            $resource = $api->getSystemResources();
            $identity = $api->getSystemIdentity();
            $api->disconnect();

            $updateData = [
                'status' => 'online',
                'last_checked_at' => now(),
            ];

            if ($identity) {
                $updateData['system_identity'] = $identity;
            }

            if (is_array($resource)) {
                if (isset($resource['uptime'])) $updateData['uptime'] = $resource['uptime'];
                if (isset($resource['cpu-load'])) $updateData['cpu_load'] = (int) $resource['cpu-load'];
                if (isset($resource['version'])) $updateData['version'] = $resource['version'];
            }

            $device->update($updateData);

            $identityMatch = true;
            $identityWarning = '';
            if ($identity && $device->name !== $identity && $device->name !== 'Simulator') {
                $identityMatch = false;
                $identityWarning = " (Warning: System identity \"{$identity}\" differs from device name \"{$device->name}\")";
            }

            $message = "API: OK";
            if ($identity) {
                $message .= " | Identity: {$identity}";
            }
            if (is_array($resource) && isset($resource['uptime'])) {
                $message .= " | Uptime: {$resource['uptime']}";
            }
            $message .= $identityWarning;

            if ($device->name === 'Simulator' || $device->host === '127.0.0.1') {
                $userCount = \App\Models\Client::whereNotNull('username')->count();
                $message = "API: OK | Simulator connected! {$userCount} PPPoE users loaded.";
            }

            return response()->json([
                'success' => true,
                'api_status' => 'OK',
                'identity' => $identity,
                'identity_match' => $identityMatch,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            $device->update(['status' => 'offline', 'last_checked_at' => now()]);
            return response()->json([
                'success' => false,
                'api_status' => 'FAIL',
                'message' => 'API: FAIL - ' . $e->getMessage(),
            ]);
        }
    }

    public function connections()
    {
        $users = MikrotikUser::with(['client', 'device'])->orderBy('created_at', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.network.connections', compact('users'));
    }

    public function queues()
    {
        $queues = BandwidthQueue::with('device')->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.network.queues', compact('queues'));
    }
    
    public function diagram()
    {
        $hasStatusColumn = Schema::hasColumn('mikrotik_devices', 'status');
        
        $mikrotikDevices = MikrotikDevice::with('zone')->where('is_active', true)->get()->map(function($d) {
            return [
                'id' => 'mk_' . $d->id,
                'name' => $d->name,
                'type' => 'mikrotik',
                'ip' => $d->host,
                'status' => $d->status ?? 'online',
                'zone' => $d->zone->name ?? 'Unknown'
            ];
        });
        
        $oltDevices = OltDevice::where('is_active', true)->get()->map(function($d) {
            return [
                'id' => 'olt_' . $d->id,
                'name' => $d->name,
                'type' => 'olt',
                'ip' => $d->ip_address,
                'status' => 'online',
                'vendor' => $d->vendor_name
            ];
        });
        
        $devices = $mikrotikDevices->merge($oltDevices);
        
        $totalDevices = MikrotikDevice::count() + OltDevice::count();
        $onlineDevices = $hasStatusColumn ? MikrotikDevice::where('status', 'online')->count() : MikrotikDevice::where('is_active', true)->count();
        $onlineDevices += OltDevice::where('is_active', true)->count();
        $offlineDevices = $hasStatusColumn ? MikrotikDevice::where('status', 'offline')->count() : 0;
        $warningDevices = $hasStatusColumn ? MikrotikDevice::where('status', 'warning')->count() : 0;
        $recentAlerts = [];
        
        return view('admin.network.diagram', compact('devices', 'totalDevices', 'onlineDevices', 'offlineDevices', 'warningDevices', 'recentAlerts'));
    }
    
    public function monitoring()
    {
        $hasStatusColumn = Schema::hasColumn('mikrotik_devices', 'status');
        
        $mikrotikDevices = MikrotikDevice::with('zone')->get()->map(function($device) {
            return [
                'id' => $device->id,
                'name' => $device->name,
                'type' => 'MikroTik',
                'ip' => $device->host,
                'location' => $device->zone->name ?? 'Unknown',
                'status' => $device->status ?? 'unknown',
                'last_seen' => $device->last_checked_at ? $device->last_checked_at->diffForHumans() : 'Never',
                'uptime' => $device->uptime ?? 'N/A',
            ];
        });
        
        $oltDevices = OltDevice::get()->map(function($device) {
            return [
                'id' => $device->id,
                'name' => $device->name,
                'type' => $device->vendor_name,
                'ip' => $device->ip_address,
                'location' => $device->location ?? 'Unknown',
                'status' => $device->is_active ? 'online' : 'offline',
                'last_seen' => $device->updated_at ? $device->updated_at->diffForHumans() : 'Never',
                'uptime' => 'N/A',
            ];
        });
        
        $devices = $mikrotikDevices->merge($oltDevices);
        
        $totalDevices = MikrotikDevice::count() + OltDevice::count();
        $onlineDevices = $hasStatusColumn ? MikrotikDevice::where('status', 'online')->count() : MikrotikDevice::where('is_active', true)->count();
        $onlineDevices += OltDevice::where('is_active', true)->count();
        $offlineDevices = $hasStatusColumn ? MikrotikDevice::where('status', 'offline')->count() : 0;
        
        return view('admin.network.monitoring', compact('devices', 'totalDevices', 'onlineDevices', 'offlineDevices'));
    }

    public function distributedItems()
    {
        return view('admin.network.distributed-items');
    }

    public function networkMap()
    {
        $devices   = MikrotikDevice::with('zone')->where('is_active', true)->get();
        $olts      = OltDevice::where('is_active', true)->get();
        $splitters = \App\Models\Splitter::where('is_active', true)->get();
        $mapMarkers = [];

        foreach ($devices as $device) {
            if ($device->latitude && $device->longitude) {
                $mapMarkers[] = [
                    'type'             => 'router',
                    'id'               => $device->id,
                    'name'             => $device->name,
                    'lat'              => (float) $device->latitude,
                    'lng'              => (float) $device->longitude,
                    'status'           => $device->status ?? 'unknown',
                    'is_active'        => $device->is_active ?? false,
                    'router_type'      => $device->router_type ?? 'core',
                    'zone'             => $device->zone->name ?? 'N/A',
                    'host'             => $device->host,
                    'uptime'           => $device->uptime ?? null,
                    'uptime_seconds'   => $device->last_uptime_seconds ?? null,
                    'cpu'              => $device->cpu_load ?? $device->last_cpu_load ?? null,
                    'memory'           => $device->memory_usage ?? $device->last_memory_usage ?? null,
                    'active_users'     => $device->active_users ?? 0,
                    'total_clients'    => $device->total_clients ?? 0,
                    'last_up_at'       => $device->last_up_at ? $device->last_up_at->diffForHumans() : null,
                    'last_down_at'     => $device->last_down_at ? $device->last_down_at->diffForHumans() : null,
                    'last_checked_at'  => $device->last_checked_at ? $device->last_checked_at->diffForHumans() : null,
                    'detail_url'       => route('admin.network.devices.detail', $device),
                ];
            }
        }

        foreach ($olts as $olt) {
            if ($olt->latitude && $olt->longitude) {
                $mapMarkers[] = [
                    'type'          => 'olt',
                    'id'            => $olt->id,
                    'name'          => $olt->name,
                    'lat'           => (float) $olt->latitude,
                    'lng'           => (float) $olt->longitude,
                    'status'        => $olt->status ?? 'unknown',
                    'is_active'     => $olt->is_active ?? false,
                    'vendor'        => $olt->vendor ?? '',
                    'ip_address'    => $olt->ip_address,
                    'pon_type'      => $olt->pon_type ?? 'epon',
                    'total_ports'   => $olt->total_ports ?? 0,
                    'model'         => $olt->model ?? '',
                    'firmware'      => $olt->firmware_version ?? '',
                    'cpu'           => $olt->cpu_usage ?? null,
                    'memory'        => $olt->memory_usage ?? null,
                    'temperature'   => $olt->temperature ?? null,
                    'uptime_seconds'=> $olt->uptime_seconds ?? null,
                    'last_sync'     => $olt->last_sync ? $olt->last_sync->diffForHumans() : null,
                    'detail_url'    => route('admin.olt.dashboard', $olt),
                ];
            }
        }

        foreach ($splitters as $splitter) {
            if ($splitter->latitude && $splitter->longitude) {
                $mapMarkers[] = [
                    'type'               => 'splitter',
                    'id'                 => $splitter->id,
                    'name'               => $splitter->name,
                    'lat'                => (float) $splitter->latitude,
                    'lng'                => (float) $splitter->longitude,
                    'location'           => $splitter->location ?? '',
                    'stype'              => $splitter->type ?? '1:8',
                    'olt_id'             => $splitter->olt_device_id ?? null,
                    'parent_splitter_id' => $splitter->parent_splitter_id ?? null,
                ];
            }
        }

        // Build lookup maps for uplink chain resolution
        $splitterMap = $splitters->keyBy('id');
        $oltMap      = $olts->keyBy('id');
        $deviceMap   = $devices->keyBy('id');

        $clients = Client::with(['zone', 'area'])
            ->whereNotNull('google_map_lat')
            ->whereNotNull('google_map_lng')
            ->where('status', '!=', 'deleted')
            ->get();

        $clientCount = 0;
        foreach ($clients as $client) {
            if ($client->google_map_lat && $client->google_map_lng) {
                $mapMarkers[] = [
                    'type'              => 'client',
                    'id'                => $client->id,
                    'name'              => $client->name,
                    'lat'               => (float) $client->google_map_lat,
                    'lng'               => (float) $client->google_map_lng,
                    'status'            => $client->status ?? 'active',
                    'connection_status' => $client->connection_status ?? 'unknown',
                    'zone'              => $client->zone->name ?? 'N/A',
                    'area'              => $client->area->name ?? 'N/A',
                    'phone'             => $client->phone ?? '',
                    'username'          => $client->username ?? '',
                    'expire_date'       => $client->expire_date ? \Carbon\Carbon::parse($client->expire_date)->format('d M Y') : null,
                    'session_uptime'    => $client->session_uptime ?? $client->uptime ?? null,
                    'uptime_seconds'    => $client->session_uptime_seconds ?? null,
                    'last_seen_at'      => $client->last_seen_at ? \Carbon\Carbon::parse($client->last_seen_at)->diffForHumans() : ($client->last_online ? \Carbon\Carbon::parse($client->last_online)->diffForHumans() : null),
                    'rx_speed'          => $client->current_rx_speed ?? null,
                    'tx_speed'          => $client->current_tx_speed ?? null,
                    'today_usage'       => $client->today_usage_bytes ?? null,
                    'router_id'         => $client->mikrotik_device_id ?? null,
                    'olt_id'            => $client->olt_id ?? $client->olt_device_id ?? null,
                    'splitter_id'       => $client->splitter_id ?? null,
                    'uplink_chain'      => $this->resolveUplinkChain(
                                              $client->splitter_id,
                                              $client->olt_id ?? $client->olt_device_id,
                                              $client->mikrotik_device_id,
                                              $splitterMap, $oltMap, $deviceMap
                                          ),
                    'detail_url'        => route('admin.clients.show', $client->id),
                ];
                $clientCount++;
            }
        }

        $stats = [
            'total_clients'  => $clientCount,
            'online_clients' => collect($mapMarkers)->where('type', 'client')->where('connection_status', 'online')->count(),
            'routers'        => collect($mapMarkers)->where('type', 'router')->count(),
            'olts'           => collect($mapMarkers)->where('type', 'olt')->count(),
            'splitters'      => collect($mapMarkers)->where('type', 'splitter')->count(),
        ];

        $hasCoordinates = count($mapMarkers) > 0;
        return view('admin.network.map', compact('devices', 'olts', 'splitters', 'mapMarkers', 'hasCoordinates', 'clientCount', 'stats'));
    }

    public function clientDiagram()
    {
        $zones = Zone::with(['areas' => function($q) {
            $q->withCount([
                'clients as total_clients',
                'clients as active_clients' => function($q) { $q->where('status', 'active'); },
                'clients as online_clients' => function($q) { $q->where('connection_status', 'online'); }
            ]);
        }])
            ->withCount([
                'clients as total_clients',
                'clients as active_clients' => function($q) { $q->where('status', 'active'); },
                'clients as online_clients' => function($q) { $q->where('connection_status', 'online'); }
            ])
            ->orderBy('name')->get();

        $boxes = Box::with(['zone:id,name', 'area:id,name', 'clients' => function($q) {
            $q->select('id', 'name', 'username', 'status', 'connection_status', 'box_id')->orderBy('name');
        }])
            ->withCount([
                'clients as total_clients',
                'clients as active_clients' => function($q) { $q->where('status', 'active'); }
            ])
            ->orderBy('name')->get();

        $totalClients = Client::where('status', '!=', 'deleted')->count();
        $activeClients = Client::where('status', 'active')->count();
        $onlineClients = Client::where('connection_status', 'online')->count();
        $offlineClients = Client::where('status', 'active')->where('connection_status', '!=', 'online')->count();

        return view('admin.network.client-diagram', compact('zones', 'boxes', 'totalClients', 'activeClients', 'onlineClients', 'offlineClients'));
    }

    public function routerDetail(MikrotikDevice $device)
    {
        $integrationService = app(NetworkIntegrationService::class);
        $routerStatus = $integrationService->getRouterStatus($device->id);

        $device->refresh();

        $clientCount = Client::where('mikrotik_device_id', $device->id)->count();
        $activeClientCount = Client::where('mikrotik_device_id', $device->id)->where('status', 'active')->count();

        return view('admin.network.router-detail', compact('device', 'routerStatus', 'clientCount', 'activeClientCount'));
    }

    public function routerDetailApi(MikrotikDevice $device)
    {
        $integrationService = app(NetworkIntegrationService::class);
        return response()->json($integrationService->getRouterStatus($device->id));
    }

    public function pppUsers(MikrotikDevice $device)
    {
        $integrationService = app(NetworkIntegrationService::class);
        $secrets = $integrationService->getPPPSecrets($device->id);

        $activeUsers = [];
        try {
            $api = \App\Services\MikroTikService::fromDevice($device);
            if ($api->connect()) {
                $activeList = $api->getPPPoEActiveUsers();
                $api->disconnect();
                foreach ($activeList as $au) {
                    $activeUsers[$au['name'] ?? ''] = $au;
                }
            }
        } catch (\Exception $e) {}

        $billingUsers = Client::whereNotNull('username')
            ->where('status', '!=', 'deleted')
            ->where('connection_type', 'pppoe')
            ->pluck('username')
            ->toArray();

        $clientMap = Client::whereNotNull('username')
            ->where('status', '!=', 'deleted')
            ->where('connection_type', 'pppoe')
            ->with(['zone', 'package'])
            ->get()
            ->keyBy('username');

        $billingCount = Client::whereNotNull('username')
            ->where('status', '!=', 'deleted')
            ->where('connection_type', 'pppoe')
            ->count();

        foreach ($secrets as &$secret) {
            $username = $secret['name'] ?? '';
            if (isset($clientMap[$username])) {
                $client = $clientMap[$username];
                $secret['billing_client'] = $client;
            }
            if (isset($activeUsers[$username])) {
                $secret['active_session'] = $activeUsers[$username];
            }
        }
        unset($secret);

        $zones = Zone::with('areas')->where('is_active', true)->get();
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $boxes = Box::where('status', 'active')->orderBy('name')->get();
        $districts = \App\Models\District::orderBy('name')->get();

        return view('admin.network.ppp-users', compact('device', 'secrets', 'billingUsers', 'billingCount', 'activeUsers', 'clientMap', 'zones', 'packages', 'boxes', 'districts'));
    }

    public function syncPPPUsers(MikrotikDevice $device)
    {
        $integrationService = app(NetworkIntegrationService::class);
        $result = $integrationService->syncPPPUsers($device->id);
        return response()->json($result);
    }

    public function syncPackagesToDevice(MikrotikDevice $device)
    {
        try {
            $syncService = app(\App\Services\PackageProfileSyncService::class);
            $packages = Package::where('is_active', true)->get();
            $mikrotik = new \App\Services\SimpleMikroTikService();

            $connected = $mikrotik->connect(
                $device->getRawOriginal('host') ?? $device->host,
                $device->username,
                $device->password,
                (int)($device->getRawOriginal('port') ?? (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))
            );

            if (!$connected) {
                return response()->json(['success' => false, 'message' => 'Cannot connect to router ' . $device->name]);
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($packages as $package) {
                $profileName = $package->mikrotik_profile ?: $package->code ?: $package->name;
                try {
                    if ($mikrotik->profileExists($profileName)) {
                        $updated++;
                    } else {
                        $rateLimit = '';
                        $dl = $package->download_speed ?? 0;
                        $ul = $package->upload_speed ?? 0;
                        $unit = $package->speed_unit ?? 'M';
                        if ($dl > 0 || $ul > 0) {
                            $rateLimit = "{$ul}{$unit}/{$dl}{$unit}";
                        }

                        if ($rateLimit) {
                            $result = $mikrotik->sendCommand('/ppp/profile/add', [
                                'name' => $profileName,
                                'rate-limit' => $rateLimit,
                                'local-address' => $package->local_address ?? '',
                            ]);
                            $created++;
                        } else {
                            $skipped++;
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "{$profileName}: {$e->getMessage()}";
                }
            }

            $mikrotik->disconnect();

            return response()->json([
                'success' => true,
                'message' => "Sync complete: {$created} created, {$updated} already exist, {$skipped} skipped",
                'details' => compact('created', 'updated', 'skipped', 'errors')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function importPPPUser(Request $request, MikrotikDevice $device)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'username' => 'required|string|unique:clients,username',
            'zone_id' => 'required|exists:zones,id',
            'package_id' => 'required|exists:packages,id',
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'alt_phone' => 'nullable|string|max:20',
            'father_husband_name' => 'nullable|string|max:255',
            'nid' => 'nullable|string|max:50',
            'area_id' => 'nullable|exists:areas,id',
            'box_id' => 'nullable|exists:boxes,id',
            'district_id' => 'nullable|exists:districts,id',
            'upazila_id' => 'nullable|exists:upazilas,id',
            'pppoe_password' => 'nullable|string',
            'connection_medium' => 'nullable|string|in:fiber,utp,wireless,vlan',
            'monthly_fee' => 'nullable|numeric|min:0',
            'billing_date' => 'nullable|integer|min:1|max:28',
            'connection_date' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'remarks' => 'nullable|string|max:500',
            'install_charge_status' => 'nullable|in:free,due,paid',
            'install_charge_amount' => 'nullable|numeric|min:0',
            'install_payment_method' => 'nullable|string|max:50',
            'install_account_number'    => 'nullable|string|max:100',
            'install_transaction_number' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $package = Package::findOrFail($request->package_id);
            $connectionDate = $request->connection_date ? \Carbon\Carbon::parse($request->connection_date) : now();

            $installStatus = $request->install_charge_status ?? 'free';
            $installAmount = (float) ($request->install_charge_amount ?? 0);
            $isFree        = ($installStatus === 'free');
            $isPaid        = ($installStatus === 'paid');

            $client = Client::create([
                'company_id' => auth()->user()->company_id ?? null,
                'name' => $request->name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'alt_phone' => $request->alt_phone,
                'father_husband_name' => $request->father_husband_name,
                'nid' => $request->nid,
                'address' => $request->address ?? '',
                'username' => $request->username,
                'pppoe_password' => $request->pppoe_password,
                'connection_type' => 'pppoe',
                'connection_medium' => $request->connection_medium ?? 'utp',
                'zone_id' => $request->zone_id,
                'area_id' => $request->area_id,
                'box_id' => $request->box_id,
                'district_id' => $request->district_id,
                'upazila_id' => $request->upazila_id,
                'package_id' => $request->package_id,
                'mikrotik_device_id' => $device->id,
                'mikrotik_profile_name' => $package->name,
                'connection_date' => $connectionDate,
                'expire_date' => ($package->validity_days && $package->validity_days != 30)
                    ? $connectionDate->copy()->addDays($package->validity_days)
                    : $connectionDate->copy()->addMonthNoOverflow(),
                'billing_date' => $request->billing_date ?? 1,
                'monthly_fee' => $request->monthly_fee ?? $package->price,
                'status' => $request->status,
                'connection_status' => 'offline',
                'balance' => 0,
                'due_amount' => 0,
                'installation_charge' => $isFree ? 0 : $installAmount,
                'is_free' => $isFree,
                'signup_fee_paid' => $isPaid,
                'remarks' => $request->remarks ?? 'Imported from MikroTik router: ' . $device->name,
            ]);

            // Create ClientServiceCharge record for installation
            if (!$isFree && $installAmount > 0) {
                $chargeData = [
                    'client_id'   => $client->id,
                    'charge_type' => 'Installation Charge',
                    'amount'      => $installAmount,
                    'description' => 'Installation charge (imported from MikroTik)',
                ];
                if ($isPaid) {
                    $chargeData['paid_by']         = auth()->id();
                    $chargeData['payment_method']  = $request->install_payment_method ?? 'cash';
                    $chargeData['paid_at']         = now();
                    if ($request->install_payment_method && $request->install_payment_method !== 'cash') {
                        $chargeData['sender_number']  = $request->install_account_number ?? null;
                        $chargeData['transaction_id'] = $request->install_transaction_number ?? null;
                    }
                }
                \App\Models\ClientServiceCharge::create($chargeData);
            }

            ActivityLog::log('create', 'clients', "Imported PPP user '{$request->username}' from router {$device->name} as client: {$client->name}");

            if ($client->status === 'active') {
                ClientActivityService::logConnection($client, 'Imported from MikroTik router and activated');
            }

            $billGenerated = false;
            try {
                $billingService = app(\App\Services\BillingService::class);
                $billingService->generateBill($client, now()->month, now()->year, suppressSms: true);
                \Log::info("Auto-generated first bill for imported PPP user: {$client->name}");
                $billGenerated = true;
            } catch (\Exception $billError) {
                \Log::warning("Failed to auto-generate bill for imported PPP user {$client->name}: " . $billError->getMessage());
            }

            DB::commit();

            $msg = "User '{$request->username}' has been successfully imported to billing as client '{$client->name}' (ID: {$client->client_id}).";
            if ($billGenerated) {
                $msg .= " First bill has been generated.";
            }

            return redirect()->route('admin.network.devices.ppp-users', $device)
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.network.devices.ppp-users', $device)
                ->with('error', 'Failed to import user: ' . $e->getMessage());
        }
    }

    private function resolveUplinkChain($splitterId, $oltId, $routerId, $splitterMap, $oltMap, $deviceMap): array
    {
        $chain    = [];
        $maxDepth = 10;

        // ── Walk splitter chain upward ──────────────────────────────────
        $curId = $splitterId;
        $depth = 0;
        $inferredOltId = $oltId;

        while ($curId && $depth < $maxDepth) {
            $spl = $splitterMap->get($curId);
            if (!$spl) break;

            $chain[] = [
                'type'      => 'splitter',
                'id'        => $spl->id,
                'name'      => $spl->name,
                'lat'       => $spl->latitude  ? (float)$spl->latitude  : null,
                'lng'       => $spl->longitude ? (float)$spl->longitude : null,
                'has_coords'=> !empty($spl->latitude) && !empty($spl->longitude),
                'stype'     => $spl->type ?? '1:8',
                'link_type' => $depth === 0 ? 'last_mile' : 'distribution',
            ];

            // Inherit OLT from deepest splitter that has one
            if (empty($inferredOltId) && !empty($spl->olt_device_id)) {
                $inferredOltId = $spl->olt_device_id;
            }

            $curId = $spl->parent_splitter_id ?? null;
            $depth++;
        }

        // ── Add OLT ─────────────────────────────────────────────────────
        if ($inferredOltId) {
            $olt = $oltMap->get($inferredOltId);
            if ($olt) {
                $chain[] = [
                    'type'      => 'olt',
                    'id'        => $olt->id,
                    'name'      => $olt->name,
                    'lat'       => $olt->latitude  ? (float)$olt->latitude  : null,
                    'lng'       => $olt->longitude ? (float)$olt->longitude : null,
                    'has_coords'=> !empty($olt->latitude) && !empty($olt->longitude),
                    'ip'        => $olt->ip_address ?? '',
                    'link_type' => count($chain) === 0 ? 'direct_olt' : 'feeder',
                ];
            }
        }

        // ── Add Router ──────────────────────────────────────────────────
        if ($routerId) {
            $router = $deviceMap->get($routerId);
            if ($router) {
                $chain[] = [
                    'type'      => 'router',
                    'id'        => $router->id,
                    'name'      => $router->name,
                    'lat'       => $router->latitude  ? (float)$router->latitude  : null,
                    'lng'       => $router->longitude ? (float)$router->longitude : null,
                    'has_coords'=> !empty($router->latitude) && !empty($router->longitude),
                    'host'      => $router->host ?? '',
                    'link_type' => 'backbone',
                ];
            }
        }

        return $chain;
    }

        public function updateMapLocation(Request $request)
    {
        $request->validate([
            'type' => 'required|in:router,olt,splitter,client',
            'id'   => 'required|integer',
            'lat'  => 'required|numeric|between:-90,90',
            'lng'  => 'required|numeric|between:-180,180',
        ]);

        $type = $request->type;
        $id   = $request->id;
        $lat  = $request->lat;
        $lng  = $request->lng;

        switch ($type) {
            case 'router':
                $device = MikrotikDevice::findOrFail($id);
                $device->update(['latitude' => $lat, 'longitude' => $lng]);
                $name = $device->name;
                break;
            case 'olt':
                $olt = OltDevice::findOrFail($id);
                $olt->update(['latitude' => $lat, 'longitude' => $lng]);
                $name = $olt->name;
                break;
            case 'splitter':
                $splitter = \App\Models\Splitter::findOrFail($id);
                $splitter->update(['latitude' => $lat, 'longitude' => $lng]);
                $name = $splitter->name;
                break;
            case 'client':
                $client = Client::findOrFail($id);
                $client->update(['google_map_lat' => $lat, 'google_map_lng' => $lng]);
                $name = $client->name;
                break;
        }

        return response()->json([
            'ok'   => true,
            'name' => $name ?? 'Device',
            'lat'  => $lat,
            'lng'  => $lng,
        ]);
    }

        public function mapLiveStatus()
    {
        $routers = MikrotikDevice::select(
                'id','status','is_active','cpu_load','memory_usage',
                'active_users','total_clients','uptime','last_uptime_seconds',
                'last_up_at','last_down_at','last_checked_at'
            )->where('is_active', true)->get()
            ->map(function ($d) {
                return [
                    'id'            => $d->id,
                    'status'        => $d->status ?? 'unknown',
                    'cpu'           => $d->cpu_load,
                    'memory'        => $d->memory_usage,
                    'active_users'  => $d->active_users ?? 0,
                    'total_clients' => $d->total_clients ?? 0,
                    'uptime'        => $d->uptime,
                    'uptime_seconds'=> $d->last_uptime_seconds,
                    'last_up_at'    => $d->last_up_at    ? $d->last_up_at->diffForHumans()    : null,
                    'last_down_at'  => $d->last_down_at  ? $d->last_down_at->diffForHumans()  : null,
                    'last_checked'  => $d->last_checked_at ? $d->last_checked_at->diffForHumans() : null,
                ];
            })->keyBy('id');

        $olts = OltDevice::select(
                'id','status','is_active','cpu_usage','memory_usage',
                'temperature','uptime_seconds','last_sync'
            )->where('is_active', true)->get()
            ->map(function ($o) {
                return [
                    'id'             => $o->id,
                    'status'         => $o->status ?? 'unknown',
                    'cpu'            => $o->cpu_usage,
                    'memory'         => $o->memory_usage,
                    'temperature'    => $o->temperature,
                    'uptime_seconds' => $o->uptime_seconds,
                    'last_sync'      => $o->last_sync ? $o->last_sync->diffForHumans() : null,
                ];
            })->keyBy('id');

        $onlineClients  = Client::where('connection_status', 'online')->count();
        $offlineClients = Client::where('status', 'active')
                                ->where('connection_status', '!=', 'online')->count();
        $totalOnMap     = Client::whereNotNull('google_map_lat')->whereNotNull('google_map_lng')
                                ->where('status', '!=', 'deleted')->count();

        $routersOnline  = MikrotikDevice::where('is_active', true)->where('status', 'online')->count();
        $routersOffline = MikrotikDevice::where('is_active', true)->where('status', 'offline')->count();

        return response()->json([
            'ok'              => true,
            'timestamp'       => now()->format('H:i:s'),
            'routers'         => $routers,
            'olts'            => $olts,
            'online_clients'  => $onlineClients,
            'offline_clients' => $offlineClients,
            'total_on_map'    => $totalOnMap,
            'routers_online'  => $routersOnline,
            'routers_offline' => $routersOffline,
        ]);
    }


    public function networkDashboard()
    {
        $integrationService = app(NetworkIntegrationService::class);
        $summary = $integrationService->getNetworkSummary();

        $routers = MikrotikDevice::with(['zone', 'vpnAccount'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $olts = OltDevice::where('is_active', true)->orderBy('name')->get();

        return view('admin.network.network-dashboard', compact('summary', 'routers', 'olts'));
    }
}
