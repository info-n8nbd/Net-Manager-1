<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OltDevice;
use App\Services\OltService;
use App\Services\OnuProvisioningService;
use App\Services\OnuManagementService;
use App\Services\OLT\SNMPClient;
use App\Services\OLT\MonitoringService;
use App\Services\OLT\BdcomTelnetPoller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OltController extends Controller
{
    public function index()
    {
        $olts = OltDevice::orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        $oltIds = $olts->pluck('id')->toArray();
        $onuCounts = DB::table('onus')
            ->whereIn('olt_device_id', $oltIds)
            ->selectRaw('olt_device_id, COUNT(*) as total, SUM(CASE WHEN status = \'online\' THEN 1 ELSE 0 END) as online_count, SUM(CASE WHEN status = \'offline\' THEN 1 ELSE 0 END) as offline_count')
            ->groupBy('olt_device_id')
            ->get()
            ->keyBy('olt_device_id');

        return view('admin.network.olt-devices', compact('olts', 'onuCounts'));
    }

    public function create()
    {
        $vendors = OltDevice::VENDORS;
        return view('admin.network.olt-create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $isSNMP = $request->connection_type === 'snmp';
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => $isSNMP ? 'nullable|string|max:255' : 'required|string|max:255',
            'password' => $isSNMP ? 'nullable|string|max:255' : 'required|string|max:255',
            'vendor' => 'required|string',
            'pon_type' => 'nullable|string|in:epon,gpon',
            'connection_type' => 'required|string|in:telnet,ssh,http,snmp',
            'model' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'total_ports' => 'nullable|integer|min:1',
            'snmp_community' => $isSNMP ? 'required|string|max:255' : 'nullable|string|max:255',
            'snmp_write_community' => 'nullable|string|max:255',
            'snmp_port' => 'nullable|integer|min:1|max:65535',
        ]);

        $data = $request->only([
            'name', 'ip_address', 'port', 'username', 'password', 'vendor',
            'pon_type', 'connection_type', 'model', 'location', 'total_ports',
            'snmp_community', 'snmp_write_community', 'snmp_port',
        ]);
        // Route port value to the right specific column based on connection type
        $portVal = $request->filled('port') ? (int)$request->port : 23;
        $data['port'] = $portVal;
        $connType = $request->connection_type;
        // Default total_ports to 1 for SNMP OLTs (auto-detected on first sync)
        if (empty($data['total_ports'])) { $data['total_ports'] = 1; }
        $data['telnet_port'] = $connType === 'telnet' ? $portVal : null;
        $data['ssh_port']    = $connType === 'ssh'    ? $portVal : null;
        $data['http_port']   = $connType === 'http'   ? $portVal : null;
        $data['is_active'] = $request->has('is_active');
        $data['username'] = $data['username'] ?? '';
        $data['password'] = $data['password'] ?? '';
        $data['snmp_enabled'] = $request->has('snmp_enabled') || $connType === 'snmp';
        if ($data['snmp_enabled'] && empty($data['snmp_port'])) {
            $data['snmp_port'] = $connType === 'snmp' ? $portVal : 161;
        }

        if (empty($data['pon_type'])) {
            $data['pon_type'] = 'epon';
        }

        $newOlt = OltDevice::create($data);

        // Auto-detect PON type from the live device (SNMP only, non-blocking)
        if ($newOlt->snmp_enabled && $newOlt->ip_address) {
            try {
                $snmpDet = \App\Services\OLT\SNMPClient::fromDevice($newOlt);
                $detectedPon = \App\Services\OLT\OIDMapping::detectPonType($snmpDet, $newOlt->vendor ?? '');
                $newOlt->update(['pon_type' => $detectedPon]);
                $ponMsg = " PON type auto-detected: " . strtoupper($detectedPon) . ".";
            } catch (\Exception $e) {
                $ponMsg = '';
            }
        } else {
            $ponMsg = '';
        }

                // Auto-sync ONUs immediately after OLT creation (non-blocking best-effort)
        $syncMsg = '';
        try {
            $newOlt->refresh();
            $monitor = new \App\Services\OLT\MonitoringService($newOlt);
            $result  = $monitor->pollOnuStatus();
            if (isset($result['total']) && $result['total'] > 0) {
                $syncMsg = ' ' . $result['total'] . ' ONU(s) found (' . ($result['online'] ?? 0) . ' online).';
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::info('OLT store auto-sync failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.olt.index')->with('success', 'OLT device added successfully.' . ($ponMsg ?? '') . $syncMsg);
    }

    public function edit(OltDevice $olt)
    {
        $vendors = OltDevice::VENDORS;
        return view('admin.network.olt-edit', compact('olt', 'vendors'));
    }

    public function update(Request $request, OltDevice $olt)
    {
        $isSNMP = $request->connection_type === 'snmp';
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => $isSNMP ? 'nullable|string|max:255' : 'required|string|max:255',
            'vendor' => 'required|string',
            'pon_type' => 'nullable|string|in:epon,gpon',
            'connection_type' => 'required|string|in:telnet,ssh,http,snmp',
            'model' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'total_ports' => 'nullable|integer|min:1',
            'snmp_community' => $isSNMP ? 'required|string|max:255' : 'nullable|string|max:255',
            'snmp_write_community' => 'nullable|string|max:255',
            'snmp_port' => 'nullable|integer|min:1|max:65535',
        ]);

        $data = $request->only([
            'name', 'ip_address', 'port', 'username', 'vendor',
            'pon_type', 'connection_type', 'model', 'location', 'total_ports',
            'snmp_community', 'snmp_write_community', 'snmp_port',
        ]);
        // Route port value to the right specific column based on connection type
        $portVal = $request->filled('port') ? (int)$request->port : 23;
        $data['port'] = $portVal;
        $connType = $request->connection_type;
        $data['telnet_port'] = $connType === 'telnet' ? $portVal : null;
        $data['ssh_port']    = $connType === 'ssh'    ? $portVal : null;
        $data['http_port']   = $connType === 'http'   ? $portVal : null;
        $data['is_active'] = $request->has('is_active');
        $data['username'] = $data['username'] ?? '';
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }
        $data['snmp_enabled'] = $request->has('snmp_enabled') || $request->connection_type === 'snmp';
        if ($data['snmp_enabled'] && empty($data['snmp_port'])) {
            $data['snmp_port'] = $connType === 'snmp' ? $portVal : 161;
        }

        // Preserve existing total_ports if not provided (prevent NOT NULL violation)
        if (empty($data['total_ports'])) { unset($data['total_ports']); }
        $olt->update($data);
        return redirect()->route('admin.olt.index')->with('success', 'OLT device updated successfully.');
    }

    public function destroy(OltDevice $olt)
    {
        $olt->delete();
        return redirect()->route('admin.olt.index')->with('success', 'OLT device deleted successfully.');
    }


    /**
     * AJAX endpoint: detect PON type from given SNMP credentials (before OLT is saved).
     * POST /admin/network/olt/detect-pon-type
     */
    public function detectPonType(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'ip_address'     => 'required|ip',
            'snmp_port'      => 'nullable|integer|min:1|max:65535',
            'snmp_community' => 'nullable|string|max:255',
            'vendor'         => 'nullable|string',
        ]);

        try {
            $client = new \App\Services\OLT\SNMPClient(
                $request->ip_address,
                (int)($request->snmp_port ?? 161),
                $request->snmp_community ?? 'public',
                'private',
                8,
                1
            );

            $ponType = \App\Services\OLT\OIDMapping::detectPonType($client, $request->vendor ?? '');

            // Also grab system description for extra info
            try { $sysDescr = $client->get(\App\Services\OLT\OIDMapping::SYSTEM_DESCR); } catch (\Exception $e) { $sysDescr = null; }

            return response()->json([
                'success'          => true,
                'pon_type'         => $ponType,
                'pon_type_label'   => strtoupper($ponType),
                'system_description' => $sysDescr,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not connect to device: ' . $e->getMessage(),
            ]);
        }
    }

    public function testConnection(OltDevice $olt)
    {
        try {
            if ($olt->connection_type === 'snmp' || $olt->snmp_enabled) {
                $snmpClient = SNMPClient::fromDevice($olt);
                $result = $snmpClient->testConnection();

                if ($result['success']) {
                    // Auto-detect EPON port count for BDCOM OLTs via SNMP
                    // Use EPON port ONU-count table (.1.3.6.1.4.1.3320.101.12.1.1.5):
                    // this table has exactly one row per physical EPON port, so count() gives the true port count.
                    if (strtolower($olt->vendor ?? '') === 'bdcom' && ($olt->pon_type ?? '') !== 'gpon') {
                        try {
                            $snmpForPorts = \App\Services\OLT\SNMPClient::fromDevice($olt);
                            $epPortOid = '.1.3.6.1.4.1.3320.101.12.1.1.5';
                            $epPortData = $snmpForPorts->walk($epPortOid);
                            if (!empty($epPortData)) {
                                $detectedPorts = count($epPortData);
                                if ($detectedPorts > 0) $olt->update(['total_ports' => $detectedPorts]);
                            }
                        } catch (\Exception $ex) {
                            // Port detection failed — not critical
                        }
                    }
                    // Auto-detect PON type (GPON vs EPON) from the live device
                    $detectedPonType = null;
                    try {
                        $snmpForPon = \App\Services\OLT\SNMPClient::fromDevice($olt);
                        $detectedPonType = \App\Services\OLT\OIDMapping::detectPonType($snmpForPon, $olt->vendor ?? '');
                        $olt->update([
                            'status' => 'active',
                            'pon_type' => $detectedPonType,
                            'system_description' => $result['system_description'] ?? null,
                            'last_sync' => now(),
                        ]);
                    } catch (\Exception $ponEx) {
                        $olt->update([
                            'status' => 'active',
                            'system_description' => $result['system_description'] ?? null,
                            'last_sync' => now(),
                        ]);
                    }
                    $usedPort = $result['snmp_port'] ?? $olt->snmp_port ?? 161;
                    return response()->json([
                        'success' => true,
                        'message' => "Connected to {$olt->name} via SNMP ({$olt->ip_address}:{$usedPort})",
                        'connection_method' => $result['method'],
                        'system_description' => $result['system_description'] ?? null,
                        'system_name' => $result['system_name'] ?? null,
                        'uptime' => $result['uptime'] ?? null,
                        'location' => $result['location'] ?? null,
                        'latency_ms' => $result['latency_ms'] ?? null,
                        'detected_pon_type' => $detectedPonType,
                    ]);
                }

                if ($olt->connection_type !== 'snmp') {
                    return $this->testNonSnmpConnection($olt);
                }

                $olt->update(['status' => 'inactive']);
                $errorType = $snmpClient->getErrorType();
                $errorMsg = match($errorType) {
                    'timeout' => "SNMP timeout — device {$olt->ip_address} did not respond. Check IP address and ensure SNMP is enabled on the OLT.",
                    'unreachable' => "Device {$olt->ip_address} is unreachable. Check network connectivity.",
                    'auth_error' => "SNMP authentication failed. Check the community string.",
                    default => $result['error'] ?? 'SNMP connection failed.',
                };

                return response()->json(['success' => false, 'message' => $errorMsg, 'error_type' => $errorType]);
            }

            return $this->testNonSnmpConnection($olt);
        } catch (\Exception $e) {
            $olt->update(['status' => 'inactive']);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    protected function testNonSnmpConnection(OltDevice $olt)
    {
        $api = OltService::fromDevice($olt);
        if ($api->connect()) {
            $method = $api->getConnectionMethod();
            $api->disconnect();
            $olt->update(['status' => 'active']);
            return response()->json([
                'success' => true,
                'message' => "Connected to {$olt->name} via {$method} ({$olt->ip_address}:{$olt->port})",
                'connection_method' => $method,
            ]);
        }
        $error = $api->getLastError();
        $olt->update(['status' => 'inactive']);
        return response()->json([
            'success' => false,
            'message' => $error ?: "Connection failed to {$olt->ip_address}:{$olt->port}. Check IP, port, username and password.",
        ]);
    }

    public function getOnuInfo(Request $request, OltDevice $olt)
    {
        $request->validate(['serial' => 'required|string']);

        try {
            $api = OltService::fromDevice($olt);
            if ($api->connect()) {
                $info = $api->getOnuInfo($request->serial);
                $api->disconnect();

                try {
                    $clientName = $info['description'] ?? $info['client_name'] ?? 'Unknown';
                    $onuSN = $request->serial;
                    $status = $info['status'] ?? 'Unknown';
                    $signal = $info['rx_power'] ?? $info['signal'] ?? null;
                    $oltName = $olt->name;
                    $pppoeUser = $info['pppoe_user'] ?? $info['username'] ?? '';
                    if (!$pppoeUser && ($info['client_id'] ?? null)) {
                        $onuClient = \App\Models\Client::where('client_id', $info['client_id'])->first();
                        if ($onuClient) $pppoeUser = $onuClient->username ?? '';
                    }
                    (new \App\Services\TelegramService())->sendOnuPingNotification(
                        $info['client_id'] ?? $onuSN,
                        $clientName,
                        $onuSN,
                        $status,
                        $signal,
                        $oltName,
                        $pppoeUser
                    );
                } catch (\Throwable $e) {
                }

                return response()->json(['success' => true, 'data' => $info]);
            }
            return response()->json(['success' => false, 'message' => 'Failed to connect to OLT.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function users(Request $request)
    {
        $query = DB::table('clients')
            ->leftJoin('olt_devices', 'clients.olt_device_id', '=', 'olt_devices.id')
            ->where(function ($q) {
                $q->whereNotNull('clients.onu_serial')
                  ->orWhereNotNull('clients.olt_device_id');
            })
            ->select(
                'clients.id',
                'clients.name as client_name',
                'clients.username as client_username',
                'clients.client_id as client_code',
                'clients.onu_serial',
                'clients.onu_mac',
                'clients.olt_port',
                'clients.onu_status',
                'clients.onu_rx_power',
                'clients.onu_model',
                'clients.status as client_status',
                'olt_devices.name as olt_name',
                'olt_devices.ip_address as olt_ip'
            );

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('clients.name', 'like', "%$s%")
                  ->orWhere('clients.client_id', 'like', "%$s%")
                  ->orWhere('clients.onu_serial', 'like', "%$s%");
            });
        }

        if ($request->filled('onu_status')) {
            $query->where('clients.onu_status', $request->onu_status);
        }

        if ($request->filled('olt_device_id')) {
            $query->where('clients.olt_device_id', $request->olt_device_id);
        }

        $users = $query->orderBy('clients.name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:30);
        $oltDevices = OltDevice::orderBy('name')->get();

        $stats = [
            'total'   => DB::table('clients')->where(function ($q) { $q->whereNotNull('onu_serial')->orWhereNotNull('olt_device_id'); })->count(),
            'online'  => DB::table('clients')->where('onu_status', 'online')->count(),
            'offline' => DB::table('clients')->where('onu_status', 'offline')->count(),
        ];

        return view('admin.olt.users', compact('users', 'oltDevices', 'stats'));
    }

    public function onuStatusRedirect()
    {
        $companyId = auth()->user()->company_id;
        $olt = \App\Models\OltDevice::when($companyId, fn($q) => $q->where("company_id", $companyId))
            ->orderBy("id")->first();
        if (!$olt) {
            return redirect()->route("admin.olt.index")
                ->with("info", "Please add an OLT device first.");
        }
        return redirect()->route("admin.olt.onus", $olt);
    }

    public function onuList()
    {
        $companyId = auth()->user()->company_id;

        $query = DB::table('onus')
            ->join('olt_devices', 'onus.olt_device_id', '=', 'olt_devices.id')
            ->leftJoin('clients', 'onus.client_id', '=', 'clients.id');

        if ($companyId) {
            $query->where('onus.company_id', $companyId);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'online' => (clone $query)->where('onus.status', 'online')->count(),
            'offline' => (clone $query)->where('onus.status', 'offline')->count(),
            'assigned' => (clone $query)->whereNotNull('onus.client_id')->count(),
        ];

        // ── Search & Filter ──────────────────────────────────────────────
        if (request('search')) {
            $s = trim(request('search'));
            $query->where(function ($q) use ($s) {
                $q->where('onus.serial_number', 'ilike', "%{$s}%")
                  ->orWhere('onus.mac_address', 'ilike', "%{$s}%")
                  ->orWhere('clients.username', 'ilike', "%{$s}%")
                  ->orWhere('clients.name',     'ilike', "%{$s}%")
                  ->orWhere('clients.client_id','ilike', "%{$s}%");
            });
        }
        if (request('olt_id')) {
            $query->where('onus.olt_device_id', (int)request('olt_id'));
        }
        if (request('pon_port')) {
            $query->where('onus.pon_port', 'ilike', '%'.trim(request('pon_port')).'%');
        }
        if (request('status')) {
            $query->where('onus.status', request('status'));
        }

        $onus = $query->select(
                'onus.*',
                'olt_devices.name as olt_name',
                'olt_devices.vendor as olt_vendor',
                'clients.name as client_name',
                'clients.client_id as client_code',
                'clients.username as client_username'
            )
            ->orderBy('onus.olt_device_id')
            ->orderByRaw("CAST(NULLIF(SPLIT_PART(onus.pon_port, '/', 2), '') AS INTEGER) ASC NULLS LAST")
            ->orderBy('onus.onu_number')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        $olts = OltDevice::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        // AJAX request — return JSON for client-side rendering
        if (request()->ajax()) {
            return response()->json([
                'data'         => $onus->items(),
                'total'        => $onus->total(),
                'per_page'     => $onus->perPage(),
                'current_page' => $onus->currentPage(),
                'last_page'    => $onus->lastPage(),
                'from'         => $onus->firstItem() ?? 0,
                'filtered'     => request()->hasAny(['search', 'olt_id', 'pon_port', 'status']),
            ]);
        }

        return view('admin.olt.onu-list', compact('onus', 'olts', 'stats'));
    }

    public function dashboard(OltDevice $olt)
    {
        $mgmtService = app(OnuManagementService::class);

        $totalOnus = DB::table('onus')->where('olt_device_id', $olt->id)->count();
        $onlineOnus = DB::table('onus')->where('olt_device_id', $olt->id)->where('status', 'online')->count();
        $offlineOnus = DB::table('onus')->where('olt_device_id', $olt->id)->where('status', 'offline')->count();
        $alarmCount = DB::table('onu_alarm_logs')->where('olt_device_id', $olt->id)->where('is_resolved', false)->count();

        $portSummary = $mgmtService->getOltPortSummary($olt->id, [
            'pon_type'    => $olt->pon_type ?? 'epon',
            'total_ports' => $olt->total_ports ?? 0,
        ]);

        // Fetch PON port descriptions via 2 snmpbulkget calls (ifName + ifAlias)
        // BDCOM EPON port ifIndexes are in range 64-96 (ONU sub-ifs start at 97+)
        $portDescriptions = [];
        $idxToPort = [];
        if ($olt->snmp_enabled) {
            try {
                $snmpPort  = $olt->snmp_port ?? ($olt->port ?? 161);
                $community = escapeshellarg($olt->snmp_community ?? 'public');
                $host      = escapeshellarg($olt->ip_address . ':' . $snmpPort);
                $opts      = "-v2c -c {$community} -t 5 -r 1 -Cn0 -Cr35 -OQn";

                // Call 1: get ifName for indices 64–98
                $nameOut = @shell_exec("snmpbulkget {$opts} {$host} .1.3.6.1.2.1.31.1.1.1.1.63 2>/dev/null") ?? '';
                foreach (explode("\n", $nameOut) as $line) {
                    if (preg_match('/\.1\.3\.6\.1\.2\.1\.31\.1\.1\.1\.1\.(\d+)\s*=\s*"?([^"\n]+)"?/', $line, $m)) {
                        $idx = (int)$m[1];
                        $portName = strtoupper(trim($m[2]));
                        // Only PON port-level interfaces (no colon = not ONU sub-interface)
                        if (preg_match('/^(E|G)PON\d+\/\d+$/', $portName)) {
                            $idxToPort[$idx] = $portName;
                        }
                    }
                }

                // Call 2: get ifAlias for same range
                $aliasOut = @shell_exec("snmpbulkget {$opts} {$host} .1.3.6.1.2.1.31.1.1.1.18.63 2>/dev/null") ?? '';
                foreach (explode("\n", $aliasOut) as $line) {
                    if (preg_match('/\.1\.3\.6\.1\.2\.1\.31\.1\.1\.1\.18\.(\d+)\s*=\s*"?([^"\n]+)"?/', $line, $m)) {
                        $idx  = (int)$m[1];
                        $desc = trim($m[2]);
                        if (isset($idxToPort[$idx]) && $desc !== '') {
                            $portDescriptions[$idxToPort[$idx]] = $desc;
                        }
                    }
                }
            } catch (\Exception $e) {}
        }

        $signalDistribution = DB::table('onus')
            ->where('olt_device_id', $olt->id)
            ->whereNotNull('rx_power')
            ->select(
                DB::raw("SUM(CASE WHEN rx_power >= -15 THEN 1 ELSE 0 END) as excellent"),
                DB::raw("SUM(CASE WHEN rx_power >= -20 AND rx_power < -15 THEN 1 ELSE 0 END) as good"),
                DB::raw("SUM(CASE WHEN rx_power >= -25 AND rx_power < -20 THEN 1 ELSE 0 END) as fair"),
                DB::raw("SUM(CASE WHEN rx_power < -25 THEN 1 ELSE 0 END) as poor")
            )
            ->first();

        $recentAlarms = DB::table('onu_alarm_logs')
            ->where('olt_device_id', $olt->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $avgRxPower = DB::table('onus')
            ->where('olt_device_id', $olt->id)
            ->whereNotNull('rx_power')
            ->whereBetween('rx_power', [-50.0, 5.0])
            ->avg('rx_power');
        $avgRxPower = $avgRxPower ? round($avgRxPower, 2) : null;

        $latestSnmpStat = DB::table('olt_snmp_stats')
            ->where('olt_device_id', $olt->id)
            ->orderByDesc('polled_at')
            ->first();

        // Only physical EPON/GPON ports (ifIndex 64-96); skip ONU sub-interfaces (97+)
        $trafficStats = DB::table('olt_traffic_stats')
            ->where('olt_device_id', $olt->id)
            ->whereBetween('port_no', [64, 96])
            ->whereIn('id', function ($q) use ($olt) {
                $q->selectRaw('MAX(id)')
                    ->from('olt_traffic_stats')
                    ->where('olt_device_id', $olt->id)
                    ->whereBetween('port_no', [64, 96])
                    ->groupBy('port_no');
            })
            ->orderBy('port_no')
            ->get();

        $snmpHistory = DB::table('olt_snmp_stats')
            ->where('olt_device_id', $olt->id)
            ->orderByDesc('polled_at')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();

        $signalHistory = DB::table('olt_snmp_stats')
            ->where('olt_device_id', $olt->id)
            ->whereRaw('(excellent_onu + good_onu + fair_onu + poor_onu) > 0')
            ->orderByDesc('polled_at')
            ->limit(20)
            ->get(['polled_at','excellent_onu','good_onu','fair_onu','poor_onu'])
            ->reverse()
            ->values();


        // PON port-level optical metrics (TX power from OLT PON module)
        $portMetrics = DB::table("olt_pon_port_metrics")
            ->where("olt_device_id", $olt->id)
            ->get()
            ->keyBy(fn($r) => strtoupper($r->pon_port));

        return view('admin.network.olt-dashboard', compact(
            'olt', 'totalOnus', 'onlineOnus', 'offlineOnus', 'alarmCount',
            'portSummary', 'signalDistribution', 'recentAlarms',
            'avgRxPower', 'latestSnmpStat', 'trafficStats', 'snmpHistory',
            'portDescriptions', 'idxToPort', 'signalHistory', 'portMetrics'
        ));
    }

    public function onuManagement(OltDevice $olt)
    {
        $provisioningService = app(OnuProvisioningService::class);
        $filters = request()->only(['status', 'port', 'assigned', 'search', 'signal_quality']);
        $onus = $provisioningService->getOnuList($olt->id, $filters);

        // Get existing ports to detect the format prefix (e.g. "EPON0/")
        $existingPorts = DB::table('onus')
            ->where('olt_device_id', $olt->id)
            ->distinct()
            ->pluck('pon_port')
            ->filter()
            ->toArray();

        $portPrefix = '';
        $maxPortFromDb = 0;
        foreach ($existingPorts as $p) {
            if (preg_match('/^(.*\/)(\d+)$/', $p, $m)) {
                if (empty($portPrefix)) $portPrefix = $m[1];
                $maxPortFromDb = max($maxPortFromDb, (int) $m[2]);
            } elseif (preg_match('/^(\d+)$/', $p, $m)) {
                $maxPortFromDb = max($maxPortFromDb, (int) $m[1]);
            }
        }

        $totalPorts = (int)($olt->total_ports ?? 8);

        if (!empty($portPrefix)) {
            $ports = [];
            for ($i = 1; $i <= $totalPorts; $i++) {
                $ports[] = $portPrefix . $i;
            }
        } else {
            $ports = collect($existingPorts)
                ->sortBy(function ($port) {
                    preg_match('/(\d+)$/', $port, $m);
                    return (int) ($m[1] ?? 0);
                })
                ->values()
                ->toArray();
        }

        $totalOnus = DB::table('onus')->where('olt_device_id', $olt->id)->count();
        $onlineOnus = DB::table('onus')->where('olt_device_id', $olt->id)->where('status', 'online')->count();
        $offlineOnus = DB::table('onus')->where('olt_device_id', $olt->id)->where('status', 'offline')->count();

        $allOlts = OltDevice::orderBy('name')->get();

        $onuSignalDistribution = DB::table('onus')
            ->where('olt_device_id', $olt->id)
            ->whereNotNull('rx_power')
            ->selectRaw('SUM(CASE WHEN rx_power >= -15 THEN 1 ELSE 0 END) as excellent, SUM(CASE WHEN rx_power >= -20 AND rx_power < -15 THEN 1 ELSE 0 END) as good, SUM(CASE WHEN rx_power >= -25 AND rx_power < -20 THEN 1 ELSE 0 END) as fair, SUM(CASE WHEN rx_power < -25 THEN 1 ELSE 0 END) as poor')
            ->first();

        $onuWithSignalCount = DB::table('onus')->where('olt_device_id', $olt->id)->whereNotNull('rx_power')->count();

        return view('admin.network.onu-management', compact(
            'olt', 'onus', 'ports', 'filters', 'totalOnus', 'onlineOnus', 'offlineOnus', 'allOlts',
            'onuSignalDistribution', 'onuWithSignalCount'
        ));
    }

    public function discoverOnus(OltDevice $olt)
    {
        $provisioningService = app(OnuProvisioningService::class);
        $result = $provisioningService->discoverUnregistered($olt->id);
        return response()->json($result);
    }

    public function syncOnus(OltDevice $olt)
    {
        try {
            $connType = strtolower($olt->connection_type ?? '');
            $vendor   = strtolower($olt->vendor ?? '');

            // ── Route by connection type ─────────────────────────────────────
            if ($vendor === 'bdcom' && $connType === 'telnet') {
                $ponType = strtolower($olt->pon_type ?? 'epon');

                if ($ponType === 'gpon') {
                    // BDCOM GPON Telnet: use GponTelnetCollector (GP3600 series)
                    $collector = new \App\Services\OLT\GponTelnetCollector($olt);
                    $onus = $collector->collectAll();
                } else {
                    // BDCOM EPON Telnet: dedicated poller
                    $poller = new BdcomTelnetPoller($olt);
                    $onus   = $poller->getOnuStatuses();
                }

                if (empty($onus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No ONUs found via Telnet. Check OLT IP, credentials, and pon_type setting.',
                    ]);
                }

                // Persist ONUs to DB (same logic as before)
                $synced = 0; $updated = 0;
                foreach ($onus as $onuData) {
                    // Normalize pon_port: "GPON0/1/1" or "GPON0/1/8" → "GPON0/1" or "GPON0/8"
                    $rawPort = $onuData['pon_port'] ?? $onuData['port'] ?? null;
                    if ($rawPort && preg_match('/GPON\d+\/\d+\/(\d+)/i', $rawPort, $pm)) {
                        $normalizedPort = 'GPON0/' . $pm[1];
                    } elseif ($rawPort && preg_match('/GPON\d+\/(\d+)/i', $rawPort, $pm)) {
                        $normalizedPort = 'GPON0/' . $pm[1];
                    } else {
                        $normalizedPort = $rawPort;
                    }
                    $onuNumber = $onuData['onu_number'] ?? null;

                    // Match by olt + normalized pon_port + onu_number (prevents duplicates on re-sync)
                    $existing = \DB::table('onus')
                        ->where('olt_device_id', $olt->id)
                        ->where('pon_port', $normalizedPort)
                        ->where('onu_number', $onuNumber)
                        ->first();
                    // Fall back to serial match if no port/onu match found
                    if (!$existing && !empty($onuData['serial_number'])) {
                        $existing = \DB::table('onus')
                            ->where('serial_number', $onuData['serial_number'])
                            ->first();
                    }

                    $rawStatus   = $onuData['status'] ?? 'unknown';
                    $normalized  = \App\Services\OltService::normalizeOnuStatus($rawStatus);
                    $finalStatus = $normalized['status'];
                    $downReason  = $onuData['down_reason'] ?? $normalized['down_reason'];
                    if ($finalStatus === 'online') $downReason = null;

                    // ── Build update record (data-safe: never overwrite with null/empty) ──
                    $record = [
                        'olt_device_id' => $olt->id,
                        'pon_port'      => $normalizedPort,
                        'onu_number'    => $onuNumber,
                        'status'        => $finalStatus,
                        'down_reason'   => $downReason,
                        'company_id'    => $olt->company_id,
                    ];

                    // Serial: only write if non-empty and no UNIQUE conflict with a DIFFERENT ONU
                    $newSerial = !empty($onuData['serial_number']) ? strtoupper(trim($onuData['serial_number'])) : null;
                    if ($newSerial && substr($newSerial, 0, 4) !== 'SYN-') {
                        $serialConflict = \DB::table('onus')
                            ->where('serial_number', $newSerial)
                            ->when($existing, fn($q) => $q->where('id', '!=', $existing->id))
                            ->exists();
                        if (!$serialConflict) {
                            $record['serial_number'] = $newSerial;
                        }
                    }

                    // Optical fields: only update if OLT returned a non-null value
                    foreach (['rx_power','tx_power','temperature','distance','tx_bias','voltage'] as $_col) {
                        if (isset($onuData[$_col]) && $onuData[$_col] !== null) {
                            $record[$_col] = $onuData[$_col];
                        }
                    }

                    // Detail fields: only update if new value is non-empty
                    if (!empty($onuData['model']))       $record['model']       = $onuData['model'];
                    if (!empty($onuData['mac_address'])) $record['mac_address'] = $onuData['mac_address'];
                    if (!empty($onuData['vendor']))      $record['vendor']      = $onuData['vendor'];
                    if (!empty($onuData['description'])) $record['description'] = $onuData['description'];

                    if ($finalStatus === 'offline' && $downReason) {
                        $record['last_down_reason'] = $downReason;
                        $record['last_offline_at']  = now();
                    }

                    // Auto-link client by serial or MAC
                    $lookupSerial = $newSerial ?? ($existing->serial_number ?? null);
                    $matchedClient = \DB::table('clients')
                        ->where(function ($q) use ($lookupSerial, $onuData) {
                            if ($lookupSerial) $q->where('onu_serial', $lookupSerial);
                            if (!empty($onuData['mac_address'])) {
                                $q->orWhere('onu_mac', $onuData['mac_address']);
                            }
                        })
                        ->first();
                    if ($matchedClient) { $record['client_id'] = $matchedClient->id; }

                    if ($existing) {
                        \DB::table('onus')->where('id', $existing->id)->update(array_merge($record, ['updated_at' => now()]));
                        $updated++;
                    } else {
                        // New ONU — serial is required; generate SYN- if missing
                        if (empty($record['serial_number'])) {
                            $record['serial_number'] = 'SYN-OLT' . $olt->id . '-P' .
                                str_pad(preg_replace('/\D/', '', $normalizedPort), 2, '0', STR_PAD_LEFT) .
                                'O' . $onuNumber;
                        }
                        try {
                            \DB::table('onus')->insert(array_merge($record, [
                                'is_provisioned' => true,
                                'provisioned_at' => now(),
                                'created_at'     => now(),
                                'updated_at'     => now(),
                            ]));
                            $synced++;
                        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                            // Race condition / duplicate: another process already inserted this ONU.
                            // Fall back to update by olt+port+onu_number (the UNIQUE key).
                            unset($record['serial_number']); // avoid serial conflict on update
                            \DB::table('onus')
                                ->where('olt_device_id', $olt->id)
                                ->where('pon_port',      $normalizedPort)
                                ->where('onu_number',    $onuNumber)
                                ->update(array_merge($record, ['updated_at' => now()]));
                            $updated++;
                        }
                    }
                }

            } elseif ($connType === 'snmp' || $olt->snmp_enabled) {
                if ($vendor === 'bdcom' && strtolower($olt->pon_type ?? '') === 'gpon') {
                    // ── BDCOM GPON via SNMP: IF-MIB + DDM collector ──────────
                    $collector = new \App\Services\OLT\BdcomGponSnmpCollector($olt);
                    $onus = $collector->collectAll();

                    if (empty($onus)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No ONUs found via SNMP (BDCOM GPON). Check SNMP community/port.',
                        ]);
                    }

                    $synced = 0; $updated = 0;
                    foreach ($onus as $onuData) {
                        $existing = \DB::table('onus')
                            ->where('serial_number', $onuData['serial_number'])
                            ->first();

                        $rawStatus   = $onuData['status'] ?? 'unknown';
                        $normalized  = \App\Services\OltService::normalizeOnuStatus($rawStatus);
                        $finalStatus = $normalized['status'];
                        $downReason  = $onuData['down_reason'] ?? $normalized['down_reason'];
                        if ($finalStatus === 'online') $downReason = null;

                        $record = [
                            'olt_device_id' => $olt->id,
                            'serial_number' => $onuData['serial_number'],
                            'pon_port'      => $onuData['pon_port'] ?? null,
                            'onu_number'    => $onuData['onu_number'] ?? null,
                            'model'         => $onuData['model'] ?? null,
                            'mac_address'   => $onuData['mac_address'] ?? null,
                            'status'        => $finalStatus,
                            'down_reason'   => $downReason,
                            'rx_power'      => $onuData['rx_power'] ?? null,
                            'tx_power'      => $onuData['tx_power'] ?? null,
                            'temperature'   => $onuData['temperature'] ?? null,
                            'distance'      => $onuData['distance'] ?? null,
                            'tx_bias'       => $onuData['tx_bias'] ?? null,
                            'voltage'       => $onuData['voltage'] ?? null,
                            'vendor'        => $onuData['vendor'] ?? null,
                            'company_id'    => $olt->company_id,
                        ];
                        if ($finalStatus === 'offline' && $downReason) {
                            $record['last_down_reason'] = $downReason;
                            $record['last_offline_at']  = now();
                        }
                        $matchedClient = \DB::table('clients')
                            ->where(function ($q) use ($onuData) {
                                $q->where('onu_serial', $onuData['serial_number']);
                                if (!empty($onuData['mac_address'])) {
                                    $q->orWhere('onu_mac', $onuData['mac_address']);
                                }
                            })
                            ->first();
                        if ($matchedClient) { $record['client_id'] = $matchedClient->id; }

                        if ($existing) {
                            \DB::table('onus')->where('id', $existing->id)->update(array_merge($record, ['updated_at' => now()]));
                            $updated++;
                        } else {
                            \DB::table('onus')->insert(array_merge($record, [
                                'is_provisioned' => true,
                                'provisioned_at' => now(),
                                'created_at'     => now(),
                                'updated_at'     => now(),
                            ]));
                            $synced++;
                        }
                    }

                    \DB::statement("
                        UPDATE clients
                        SET onu_status   = onus.status,
                            onu_rx_power = onus.rx_power,
                            onu_id       = onus.id,
                            olt_port     = onus.pon_port
                        FROM onus
                        WHERE clients.onu_serial = onus.serial_number
                           OR clients.onu_mac    = onus.mac_address
                    ");

                    $olt->update(['last_sync' => now()]);

                } else {
                    // ── General SNMP OLT: MonitoringService (EPON & others) ──
                    $monitor = new \App\Services\OLT\MonitoringService($olt);
                    $result  = $monitor->pollOnuStatus();

                    $synced  = 0;
                    $updated = (int)($result['total'] ?? 0);

                    \DB::statement("
                        UPDATE clients
                        SET onu_status   = onus.status,
                            onu_rx_power = onus.rx_power,
                            onu_id       = onus.id,
                            olt_port     = onus.pon_port
                        FROM onus
                        WHERE clients.onu_serial = onus.serial_number
                           OR clients.onu_mac    = onus.mac_address
                    ");

                    $olt->update(['last_sync' => now()]);
                }

            } else {
                // Fallback: generic OltService (SSH / HTTP)
                $api = OltService::fromDevice($olt);
                if (!$api->connect()) {
                    return response()->json([
                        'success' => false,
                        'message' => $api->getLastError() ?: 'Failed to connect to OLT',
                    ]);
                }
                $onus = $api->getAllOnus();
                $api->disconnect();

                if (empty($onus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No ONUs found on OLT.',
                    ]);
                }
                $synced = 0; $updated = 0;
                foreach ($onus as $onuData) {
                    $existing = \DB::table('onus')->where('serial_number', $onuData['serial_number'])->first();
                    $record = array_merge($onuData, ['olt_device_id' => $olt->id, 'company_id' => $olt->company_id]);
                    if ($existing) {
                        \DB::table('onus')->where('id', $existing->id)->update(array_merge($record, ['updated_at' => now()]));
                        $updated++;
                    } else {
                        \DB::table('onus')->insert(array_merge($record, ['created_at' => now(), 'updated_at' => now()]));
                        $synced++;
                    }
                }
            }

            // ── Return fresh ONU list from DB ────────────────────────────────
            $allOnus = DB::table('onus')
                ->leftJoin('clients', 'onus.client_id', '=', 'clients.id')
                ->leftJoin('olt_devices', 'onus.olt_device_id', '=', 'olt_devices.id')
                ->select(
                    'onus.id', 'onus.serial_number', 'onus.mac_address', 'onus.pon_port',
                    'onus.onu_number', 'onus.model', 'onus.status',
                    'onus.rx_power', 'onus.client_id', 'onus.olt_device_id',
                    'clients.name as client_name',
                    'clients.username as client_username',
                    'clients.client_id as client_code',
                    'olt_devices.name as olt_name'
                )
                ->where('onus.olt_device_id', $olt->id)
                ->orderByRaw("CASE WHEN onus.status = 'online' THEN 0 ELSE 1 END")
                ->orderByRaw("CAST(NULLIF(SPLIT_PART(onus.pon_port, '/', 2), '') AS INTEGER) ASC NULLS LAST")
                ->orderBy('onus.onu_number')
                ->limit(500)
                ->get();

            $olt->update(['last_sync' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Sync complete. ' . $allOnus->count() . ' ONUs loaded (' . $allOnus->where('status', 'online')->count() . ' online).',
                'total'   => $allOnus->count(),
                'new'     => $synced,
                'updated' => $updated,
                'onus'    => $allOnus,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync error: ' . $e->getMessage(),
            ]);
        }
    }
    public function provisionOnu(Request $request, OltDevice $olt)
    {
        $request->validate([
            'serial' => 'required|string|max:255',
            'port' => 'required|string|max:50',
            'line_profile' => 'nullable|string|max:255',
            'service_profile' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'vlan' => 'nullable|string|max:50',
            'client_id' => 'nullable|integer|exists:clients,id',
        ]);

        $provisioningService = app(OnuProvisioningService::class);
        $result = $provisioningService->provision($olt->id, $request->serial, $request->port, [
            'line_profile' => $request->line_profile ?? 'default',
            'service_profile' => $request->service_profile ?? 'default',
            'description' => $request->description,
            'vlan' => $request->vlan,
            'user_id' => auth()->id(),
        ]);

        if ($result['success'] && $request->client_id && isset($result['data']['onu_id'])) {
            $provisioningService->assignToClient($result['data']['onu_id'], $request->client_id);
        }

        return response()->json($result);
    }

    public function decommissionOnu(Request $request, $onuId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $provisioningService = app(OnuProvisioningService::class);
        $result = $provisioningService->decommission((int) $onuId, auth()->id(), $request->reason ?? '');
        return response()->json($result);
    }

    public function assignOnu(Request $request, $onuId)
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $provisioningService = app(OnuProvisioningService::class);
        $result = $provisioningService->assignToClient((int) $onuId, $request->client_id);
        return response()->json($result);
    }

    public function onuDetail($onuId)
    {
        $onu = DB::table('onus')
            ->leftJoin('clients', 'onus.client_id', '=', 'clients.id')
            ->leftJoin('olt_devices', 'onus.olt_device_id', '=', 'olt_devices.id')
            ->where('onus.id', $onuId)
            ->select(
                'onus.*',
                'clients.name as client_name',
                'clients.client_id as client_code',
                'clients.phone as client_phone',
                'clients.email as client_email',
                'olt_devices.name as olt_name',
                'olt_devices.vendor as olt_vendor',
                'olt_devices.ip_address as olt_ip'
            )
            ->first();

        if (!$onu) {
            abort(404, 'ONU not found');
        }

        $olt = OltDevice::find($onu->olt_device_id);

        $provisionLogs = DB::table('onu_provision_logs')
            ->leftJoin('users', 'onu_provision_logs.performed_by', '=', 'users.id')
            ->where('onu_provision_logs.onu_id', $onuId)
            ->select('onu_provision_logs.*', 'users.name as performed_by_name')
            ->orderByDesc('onu_provision_logs.created_at')
            ->limit(20)
            ->get();

        $rebootHistory = DB::table('onu_reboot_history')
            ->where('onu_id', $onuId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $alarms = DB::table('onu_alarm_logs')
            ->where('onu_id', $onuId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.network.onu-detail', compact('onu', 'olt', 'provisionLogs', 'rebootHistory', 'alarms'));
    }

    public function setPortDescription(OltDevice $olt, \Illuminate\Http\Request $request)
    {
        if (!$olt->snmp_enabled) {
            return response()->json(['success' => false, 'message' => 'SNMP is not enabled on this OLT device.']);
        }

        $portName   = strtoupper(trim($request->input('port', '')));
        $description = trim($request->input('description', ''));

        if (empty($portName)) {
            return response()->json(['success' => false, 'message' => 'Port name is required.']);
        }

        $snmpPort  = $olt->snmp_port ?? 161;
        $community = escapeshellarg($olt->snmp_community ?? 'public');
        $host      = escapeshellarg($olt->ip_address . ':' . $snmpPort);
        $opts      = "-v2c -c {$community} -t 10 -r 2 -OQn";

        // Walk ifName (.1.3.6.1.2.1.31.1.1.1.1) to find ifIndex for given port
        $nameOut   = @shell_exec("snmpwalk {$opts} {$host} .1.3.6.1.2.1.31.1.1.1.1 2>/dev/null") ?? '';
        $targetIdx = null;
        foreach (explode("
", $nameOut) as $line) {
            if (preg_match('/\.1\.3\.6\.1\.2\.1\.31\.1\.1\.1\.1\.(\d+)\s*=\s*"?([^"
]+)"?/', $line, $m)) {
                if (strtoupper(trim($m[2])) === $portName) {
                    $targetIdx = (int)$m[1];
                    break;
                }
            }
        }

        if ($targetIdx === null) {
            return response()->json(['success' => false, 'message' => "Port {$portName} not found via SNMP. Check SNMP connectivity and port name."]);
        }

        // SET ifAlias (.1.3.6.1.2.1.31.1.1.1.18.<ifIndex>)
        $oid  = ".1.3.6.1.2.1.31.1.1.1.18.{$targetIdx}";
        $desc = escapeshellarg($description);
        $cmd  = "snmpset {$opts} {$host} {$oid} s {$desc} 2>&1";
        $out  = @shell_exec($cmd) ?? '';

        if (stripos($out, 'Error') !== false || (empty(trim($out)) && $description !== '')) {
            return response()->json(['success' => false, 'message' => 'SNMP SET failed: ' . trim($out)]);
        }

        return response()->json([
            'success'     => true,
            'message'     => "Description updated for {$portName}",
            'port'        => $portName,
            'description' => $description,
            'ifIndex'     => $targetIdx,
        ]);
    }

    public function portUtilization(OltDevice $olt)
    {
        $mgmtService = app(OnuManagementService::class);
        $portSummary = $mgmtService->getOltPortSummary($olt->id);

        $portData = [];
        for ($i = 1; $i <= $olt->total_ports; $i++) {
            $existing = collect($portSummary)->firstWhere('pon_port', (string) $i);
            $portData[] = [
                'port' => $i,
                'total' => $existing->total ?? 0,
                'online' => $existing->online ?? 0,
                'offline' => $existing->offline ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Port utilization data',
            'data' => $portData,
        ]);
    }

    public function alarms(OltDevice $olt)
    {
        $alarms = DB::table('onu_alarm_logs')
            ->leftJoin('onus', 'onu_alarm_logs.onu_id', '=', 'onus.id')
            ->where('onu_alarm_logs.olt_device_id', $olt->id)
            ->select('onu_alarm_logs.*', 'onus.pon_port', 'onus.onu_number', 'onus.description as onu_description')
            ->orderByDesc('onu_alarm_logs.created_at')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25);

        return view('admin.network.olt-dashboard', compact('olt', 'alarms'))
            ->with('showAlarms', true);
    }

    public function resolveAlarm(Request $request, $alarmId)
    {
        $mgmtService = app(OnuManagementService::class);
        $mgmtService->resolveOnuAlarm((int) $alarmId);
        return response()->json(['success' => true, 'message' => 'Alarm resolved successfully']);
    }

    public function provisionLogs(OltDevice $olt)
    {
        $provisioningService = app(OnuProvisioningService::class);
        $logs = $provisioningService->getProvisionLogs($olt->id, 100);
        return view('admin.network.olt-dashboard', compact('olt', 'logs'))
            ->with('showLogs', true);
    }

    public function checkAllOnuPower(OltDevice $olt)
    {
        set_time_limit(600);
        try {
            $api = OltService::fromDevice($olt);
            if (!$api->connect()) {
                return response()->json([
                    'success' => false,
                    'message' => $api->getLastError() ?: 'Failed to connect to OLT',
                ]);
            }

            if ($olt->vendor === 'bdcom' && $olt->connection_type === 'http') {
                $onus = DB::table('onus')
                    ->where('olt_device_id', $olt->id)
                    ->whereNotNull('pon_port')
                    ->whereNotNull('onu_number')
                    ->select('id', 'pon_port', 'onu_number')
                    ->get();

                $total = $onus->count();

                return response()->stream(function () use ($api, $onus, $total) {
                    $updated = 0;
                    $failCount = 0;
                    $current = 0;

                    echo "data: " . json_encode(['type' => 'start', 'total' => $total]) . "\n\n";
                    ob_flush();
                    flush();

                    foreach ($onus as $onu) {
                        $current++;

                        if ($failCount >= 5) {
                            echo "data: " . json_encode(['type' => 'error', 'message' => 'Too many failures, stopping']) . "\n\n";
                            ob_flush();
                            flush();
                            break;
                        }

                        $intfName = $onu->pon_port . ':' . $onu->onu_number;
                        $page = $api->httpGet('onuintfbasicinfo.asp?intfName=' . urlencode($intfName));

                        if (!$page) {
                            $failCount++;
                            continue;
                        }
                        $failCount = 0;

                        $updateData = ['updated_at' => now()];
                        $rxPower = null;

                        if (preg_match('/received\s+power\s*\(D?Bm\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                            $updateData['rx_power'] = $rxPower = (float) $m[1];
                        }
                        if (preg_match('/transmitted\s+power\s*\(D?Bm\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                            $updateData['tx_power'] = (float) $m[1];
                        }
                        if (preg_match('/operating\s+temperature\s*\(degree\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                            $updateData['temperature'] = (float) $m[1];
                        }
                        if (preg_match('/supply\s+voltage\s*\(V\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                            $updateData['voltage'] = (float) $m[1];
                        }
                        if (preg_match('/bias\s+current\s*\(mA\)\s*:\s*([-\d.]+)/i', $page, $m)) {
                            $updateData['tx_bias'] = (float) $m[1];
                        }

                        if (count($updateData) > 1) {
                            DB::table('onus')->where('id', $onu->id)->update($updateData);
                            $updated++;
                        }

                        if ($current % 5 === 0 || $current === $total) {
                            echo "data: " . json_encode([
                                'type' => 'progress',
                                'current' => $current,
                                'total' => $total,
                                'updated' => $updated,
                                'last_onu' => $intfName,
                                'last_rx' => $rxPower,
                            ]) . "\n\n";
                            ob_flush();
                            flush();
                        }
                    }

                    $api->disconnect();

                    echo "data: " . json_encode([
                        'type' => 'complete',
                        'updated' => $updated,
                        'total' => $total,
                        'message' => "Power check complete. Updated {$updated} of {$total} ONUs.",
                    ]) . "\n\n";
                    ob_flush();
                    flush();
                }, 200, [
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'keep-alive',
                    'X-Accel-Buffering' => 'no',
                ]);
            }

            $onus = DB::table('onus')
                ->where('olt_device_id', $olt->id)
                ->where('status', 'online')
                ->whereNotNull('serial_number')
                ->get();

            $updated = 0;
            foreach ($onus as $onu) {
                try {
                    $info = $api->getOnuInfo($onu->serial_number);
                    if ($info && !empty($info)) {
                        $updateData = ['updated_at' => now()];
                        if (isset($info['signal'])) $updateData['rx_power'] = $info['signal'];
                        if (isset($info['rx_power'])) $updateData['rx_power'] = $info['rx_power'];
                        if (isset($info['tx_power'])) $updateData['tx_power'] = $info['tx_power'];
                        if (isset($info['temperature'])) $updateData['temperature'] = $info['temperature'];
                        if (isset($info['voltage'])) $updateData['voltage'] = $info['voltage'];
                        if (isset($info['tx_bias'])) $updateData['tx_bias'] = $info['tx_bias'];
                        if (isset($info['distance'])) $updateData['distance'] = $info['distance'];
                        if (isset($info['status'])) $updateData['status'] = $info['status'];

                        DB::table('onus')->where('id', $onu->id)->update($updateData);
                        $updated++;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            $api->disconnect();

            return response()->json([
                'success' => true,
                'message' => "Power check complete. Updated {$updated} of {$onus->count()} online ONUs.",
                'updated' => $updated,
                'total' => $onus->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function onuPickerList(Request $request)
    {
        try {
            $companyId = auth()->check() ? (auth()->user()->company_id ?? null) : null;

            $query = DB::table('onus')
                ->leftJoin('clients', 'onus.client_id', '=', 'clients.id')
                ->leftJoin('olt_devices', 'onus.olt_device_id', '=', 'olt_devices.id');

            if ($companyId) {
                $query->where('onus.company_id', $companyId);
            }

            if ($request->filled('search')) {
                $s = '%' . $request->search . '%';
                $query->where(function($q) use ($s) {
                    $q->where(DB::raw('onus.serial_number::text'), 'LIKE', $s)
                      ->orWhere(DB::raw('onus.mac_address::text'), 'LIKE', $s)
                      ->orWhere(DB::raw('onus.pon_port::text'), 'LIKE', $s)
                      ->orWhere(DB::raw('onus.model::text'), 'LIKE', $s);
                });
            }

            if ($request->filled('unassigned_only') && $request->unassigned_only == '1') {
                $query->whereNull('onus.client_id');
            }

            if ($request->filled('olt_device_id')) {
                $query->where('onus.olt_device_id', $request->olt_device_id);
            }

            $onus = $query->select(
                'onus.id', 'onus.serial_number', 'onus.mac_address', 'onus.pon_port',
                'onus.onu_number', 'onus.model', 'onus.status',
                'onus.rx_power', 'onus.client_id',
                'onus.olt_device_id',
                'clients.name as client_name', 'clients.username as client_username', 'clients.client_id as client_code',
                'olt_devices.name as olt_name'
            )
            ->orderByRaw("CASE WHEN onus.status = 'online' THEN 0 ELSE 1 END")
            ->orderByRaw("CAST(NULLIF(SPLIT_PART(onus.pon_port, '/', 2), '') AS INTEGER) ASC NULLS LAST")
            ->orderBy('onus.onu_number')
            ->limit(200)
            ->get();

            return response()->json([
                'success' => true,
                'data'    => $onus,
            ]);
        } catch (\Throwable $e) {
            \Log::error('onuPickerList error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'DB error: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Sync OLT PON port TX power data manually (called from dashboard Sync button).
     */
    public function syncPonTx(OltDevice $olt)
    {
        set_time_limit(300);
        $ponType = strtolower($olt->pon_type ?? '');
        $connType = strtolower($olt->connection_type ?? '');
        $vendor   = strtolower($olt->vendor ?? '');

        // ── EPON SNMP: OLT port TX not available via SNMP ────────────────
        if ($ponType === 'epon' && $connType === 'snmp') {
            return response()->json([
                "success" => false,
                "message" => "OLT port TX power requires Telnet access. Add Telnet credentials to this OLT to enable this feature.",
            ]);
        }

        try {
            $portData = [];

            // ── GPON Telnet ───────────────────────────────────────────────
            if ($ponType === 'gpon' && $connType === 'telnet') {
                $collector = new \App\Services\OLT\GponTelnetCollector($olt);
                $portData  = $collector->getPonPortTxPowers();
            }
            // ── EPON Telnet (BDCOM) ───────────────────────────────────────
            elseif ($ponType === 'epon' && $connType === 'telnet' && $vendor === 'bdcom') {
                $poller  = new \App\Services\OLT\BdcomTelnetPoller($olt);
                $onuList = $poller->getRxPowers();
                foreach ($onuList as $data) {
                    $port = strtoupper($data['pon_port'] ?? '');
                    if (!$port || isset($portData[$port])) continue;
                    if ($data['tx_power'] === null && $data['temperature'] === null) continue;
                    $portData[$port] = [
                        'tx_power'    => $data['tx_power']    ?? null,
                        'temperature' => $data['temperature'] ?? null,
                        'voltage'     => $data['voltage']     ?? null,
                        'tx_bias'     => $data['tx_bias']     ?? null,
                    ];
                }
            } else {
                return response()->json([
                    "success" => false,
                    "message" => "Sync not supported for this OLT type ({$vendor} {$ponType} {$connType}).",
                ]);
            }

            // ── Save to DB ────────────────────────────────────────────────
            $saved = 0;
            $now   = now();
            foreach ($portData as $port => $metrics) {
                DB::table("olt_pon_port_metrics")->updateOrInsert(
                    ["olt_device_id" => $olt->id, "pon_port" => strtoupper($port)],
                    [
                        "tx_power"    => $metrics["tx_power"]    ?? null,
                        "temperature" => $metrics["temperature"] ?? null,
                        "voltage"     => $metrics["voltage"]     ?? null,
                        "tx_bias"     => $metrics["tx_bias"]     ?? null,
                        "polled_at"   => $now,
                        "updated_at"  => $now,
                    ]
                );
                $saved++;
            }

            // ── Return updated metrics ────────────────────────────────────
            $dbMetrics = DB::table("olt_pon_port_metrics")
                ->where("olt_device_id", $olt->id)
                ->get()
                ->keyBy(fn($r) => strtoupper($r->pon_port))
                ->map(fn($r) => [
                    "tx_power"    => $r->tx_power,
                    "temperature" => $r->temperature,
                    "voltage"     => $r->voltage,
                    "tx_bias"     => $r->tx_bias,
                ]);

            return response()->json([
                "success" => true,
                "ports"   => $saved,
                "metrics" => $dbMetrics,
                "message" => "Synced {$saved} port(s) OK",
            ]);

        } catch (\Exception $e) {
            Log::error("syncPonTx [{$olt->name}]: " . $e->getMessage());
            return response()->json(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function liveRefresh(OltDevice $olt)
    {
        try {
            $api = OltService::fromDevice($olt);
            if (!$api->connect()) {
                return response()->json(['success' => false, 'message' => $api->getLastError() ?: 'Connection failed']);
            }

            $onus = $api->getAllOnus();
            $api->disconnect();

            if (empty($onus)) {
                return response()->json(['success' => false, 'message' => 'No ONUs found']);
            }

            foreach ($onus as $onuData) {
                $existing = \DB::table("onus")
                    ->where("olt_device_id", $olt->id)
                    ->where(function ($q) use ($onuData) {
                        if (!empty($onuData["serial_number"])) {
                            $q->where("serial_number", $onuData["serial_number"]);
                        } elseif (!empty($onuData["mac_address"])) {
                            $q->where("mac_address", $onuData["mac_address"])
                              ->orWhere("serial_number", $onuData["mac_address"]);
                        }
                    })->first();
                $rawStatus  = $onuData['status'] ?? 'unknown';
                $normalized = OltService::normalizeOnuStatus($rawStatus);
                $finalStatus = $normalized['status'];
                $downReason = $onuData['down_reason'] ?? $normalized['down_reason'];
                if ($finalStatus === 'online') $downReason = null;

                $record = [
                    'status'      => $finalStatus,
                    'down_reason' => $downReason,
                    'rx_power'    => $onuData['rx_power'] ?? null,
                    'tx_power'    => $onuData['tx_power'] ?? null,
                    'temperature' => $onuData['temperature'] ?? null,
                    'voltage'     => $onuData['voltage'] ?? null,
                    'tx_bias'     => $onuData['tx_bias'] ?? null,
                    'distance'    => $onuData['distance'] ?? null,
                    'updated_at'  => now(),
                ];

                if (!empty($onuData['description'])) {
                    $record['description'] = $onuData['description'];
                }

                $matchedClient = \DB::table('clients')
                    ->where(function ($q) use ($onuData) {
                        $q->where('onu_serial', $onuData['serial_number']);
                        if (!empty($onuData['mac_address'])) {
                            $q->orWhere('onu_mac', $onuData['mac_address']);
                        }
                    })->first();
                if ($matchedClient) {
                    $record['client_id'] = $matchedClient->id;
                }

                if ($finalStatus === 'offline' && $downReason) {
                    $record['last_down_reason'] = $downReason;
                    $record['last_offline_at']  = now();
                }
                if ($finalStatus === 'online' && $existing) {
                    $record['last_online'] = now();
                }

                if ($existing) {
                    \DB::table('onus')->where('id', $existing->id)->update($record);
                }
            }

            $allOnus = DB::table('onus')
                ->where('onus.olt_device_id', $olt->id)
                ->leftJoin('clients', 'onus.client_id', '=', 'clients.id')
                ->select(
                    'onus.id', 'onus.serial_number', 'onus.mac_address', 'onus.pon_port',
                    'onus.onu_number', 'onus.model', 'onus.status', 'onus.description',
                    'onus.rx_power', 'onus.tx_power', 'onus.temperature', 'onus.voltage',
                    'onus.tx_bias', 'onus.distance', 'onus.down_reason', 'onus.client_id',
                    'clients.name as client_name',
                    'clients.username as client_username',
                    'clients.client_id as client_code'
                )
                ->orderByRaw("CAST(NULLIF(SPLIT_PART(onus.pon_port, '/', 2), '') AS INTEGER) ASC NULLS LAST")
                ->orderBy('onus.onu_number')
                ->get();

            return response()->json(['success' => true, 'onus' => $allOnus]);
        } catch (\Exception $e) {
            \Log::error("Live refresh failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Live refresh error: ' . $e->getMessage()]);
        }
    }

    public function getOnuLiveInfo(int $onuId)
    {
        $onu = DB::table('onus')
            ->leftJoin('clients', 'onus.client_id', '=', 'clients.id')
            ->where('onus.id', $onuId)
            ->select('onus.*', 'clients.name as client_name', 'clients.client_id as client_code', 'clients.phone as client_phone', 'clients.username as client_username')
            ->first();

        if (!$onu) {
            return response()->json(['success' => false, 'message' => 'ONU not found']);
        }

        $olt = OltDevice::find($onu->olt_device_id);
        if (!$olt) {
            return response()->json(['success' => false, 'message' => 'OLT not found']);
        }

        // Build cached data from DB
        $data = [
            'onu_id'           => $onu->id,
            'serial'           => $onu->serial_number,
            'mac'              => $onu->mac_address,
            'port'             => $onu->pon_port,
            'onu_number'       => $onu->onu_number,
            'model'            => $onu->model,
            'vendor'           => $onu->vendor ?? null,
            'status'           => $onu->status,
            'down_reason'      => $onu->down_reason,
            'last_down_reason' => $onu->last_down_reason,
            'last_offline_at'  => $onu->last_offline_at,
            'rx_power'         => $onu->rx_power,
            'tx_power'         => $onu->tx_power,
            'temperature'      => $onu->temperature,
            'voltage'          => $onu->voltage,
            'tx_bias'          => $onu->tx_bias,
            'distance'         => $onu->distance,
            'client_name'      => $onu->client_name,
            'client_code'      => $onu->client_code,
            'client_phone'     => $onu->client_phone,
            'client_username'  => $onu->client_username ?? null,
            'olt_name'    => $olt->name,
            'olt_ip'      => $olt->ip_address,
            'source'      => 'cached',
            'polled_at'   => $onu->updated_at,
        ];

        // Try live fetch — BDCOM Telnet uses dedicated poller, others use OltService
        try {
            $isBdcomTelnet = strtolower($olt->vendor ?? '') === 'bdcom'
                          && strtolower($olt->connection_type ?? '') === 'telnet';

            $info = null;
            if ($isBdcomTelnet) {
                $poller = new BdcomTelnetPoller($olt);
                $info   = $poller->getOnuLiveStatus($onuId);
            } else {
                $api = OltService::fromDevice($olt);
                if ($api->connect()) {
                    $info = $api->getOnuInfo($onu->serial_number);
                    $api->disconnect();
                }
            }

            if ($info) {
                    $now = now();
                    $prevStatus          = $data['status'];
                    $data['source']      = 'live';
                    $data['polled_at']   = $now;
                    $data['status']      = (isset($info['status']) && $info['status'] !== 'unknown') ? $info['status'] : $data['status'];
                    $data['rx_power']    = $info['rx_power']    ?? $info['signal'] ?? $data['rx_power'];
                    $data['tx_power']    = $info['tx_power']    ?? $data['tx_power'];
                    $data['temperature'] = $info['temperature'] ?? $data['temperature'];
                    $data['voltage']     = $info['voltage']     ?? $data['voltage'];
                    $data['tx_bias']     = $info['tx_bias']     ?? $data['tx_bias'];
                    $data['distance']    = $info['distance']    ?? $data['distance'];
                    $data['port']        = $info['port']        ?? $data['port'];
                    $data['model']       = $info['model']       ?? $data['model'];

                    $updateArr = ['updated_at' => $now, 'status' => $data['status']];
                    foreach (['rx_power','tx_power','temperature','voltage','tx_bias','distance'] as $f) {
                        if ($data[$f] !== null) $updateArr[$f] = $data[$f];
                    }

                    if ($data['status'] === 'online') {
                        $data['down_reason']  = null;
                        $updateArr['down_reason'] = null;
                        if ($prevStatus === 'offline') {
                            $updateArr['last_online'] = $now;
                            $ldr = $onu->last_down_reason ?? $onu->down_reason ?? null;
                            if ($ldr) {
                                $updateArr['last_down_reason'] = $ldr;
                                $data['last_down_reason'] = $ldr;
                            }
                            $updateArr['last_offline_at'] = $now;
                            $data['last_offline_at'] = $now->format('Y-m-d H:i:s');
                        }
                        if (!empty($info['down_reason'])) {
                            $updateArr['last_down_reason'] = $info['down_reason'];
                            $updateArr['last_offline_at']  = $now;
                            $data['last_down_reason'] = $info['down_reason'];
                            $data['last_offline_at'] = $now->format('Y-m-d H:i:s');
                        }
                    } else {
                        $newReason = $info['down_reason'] ?? $data['down_reason'];
                        $data['down_reason']      = $newReason;
                        $data['last_down_reason'] = $newReason ?? $data['last_down_reason'];
                        $updateArr['down_reason']      = $newReason;
                        $updateArr['last_down_reason'] = $data['last_down_reason'];
                        $updateArr['last_offline_at']  = $now;
                        $data['last_offline_at'] = $now->format('Y-m-d H:i:s');
                        if ($prevStatus === 'online') {
                            $updateArr['last_offline'] = $now;
                        }
                    }

                    DB::table('onus')->where('id', $onuId)->update($updateArr);

                    // Sync clients table with latest ONU status and RX power
                    if ($onu->serial_number || $onu->mac_address) {
                        DB::table('clients')
                            ->where(function($q) use ($onu) {
                                if ($onu->serial_number) {
                                    $q->where('onu_serial', $onu->serial_number);
                                }
                                if ($onu->mac_address) {
                                    $q->orWhere('onu_mac', $onu->mac_address);
                                }
                            })
                            ->update([
                                'onu_status'   => $data['status'],
                                'onu_rx_power' => $data['rx_power'] ?? null,
                                'updated_at'   => now(),
                            ]);
                    }
                }
        } catch (\Exception $e) {
            $data['live_error'] = $e->getMessage();
        }

        // Compute mW values
        if ($data['rx_power'] !== null) {
            $data['rx_power_mw']    = round(pow(10, $data['rx_power'] / 10), 4);
            $rxP = $data['rx_power'];
            $data['rx_power_color'] = $rxP >= -15 ? '#28a745' : ($rxP >= -20 ? '#17a2b8' : ($rxP >= -25 ? '#ffc107' : '#dc3545'));
            $data['rx_quality']     = $rxP >= -15 ? 'Excellent' : ($rxP >= -20 ? 'Good' : ($rxP >= -25 ? 'Fair' : 'Poor'));
        }
        if ($data['tx_power'] !== null) {
            $data['tx_power_mw'] = round(pow(10, $data['tx_power'] / 10), 4);
        }

        return response()->json(['success' => true, 'data' => $data])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function pollDashboard(OltDevice $olt)
    {
        try {
            $api = OltService::fromDevice($olt);
            if (!$api->connect()) {
                return response()->json(['success' => false, 'message' => $api->getLastError() ?: 'OLT connect failed']);
            }

            $data = $api->pollDashboardData();
            $api->disconnect();

            $now = now();

            // Compute signal distribution from onus table at poll time
            $sigDist = \DB::table('onus')
                ->where('olt_device_id', $olt->id)
                ->whereNotNull('rx_power')
                ->selectRaw('SUM(CASE WHEN rx_power >= -15 THEN 1 ELSE 0 END) as excellent, SUM(CASE WHEN rx_power >= -20 AND rx_power < -15 THEN 1 ELSE 0 END) as good, SUM(CASE WHEN rx_power >= -25 AND rx_power < -20 THEN 1 ELSE 0 END) as fair, SUM(CASE WHEN rx_power < -25 THEN 1 ELSE 0 END) as poor')
                ->first();

            DB::table('olt_snmp_stats')->insert([
                'olt_device_id'  => $olt->id,
                'company_id'     => $olt->company_id,
                'cpu_usage'      => $data['cpu_usage'],
                'memory_usage'   => $data['memory_usage'],
                'temperature'    => $data['temperature'],
                'uptime_seconds' => $data['uptime_seconds'],
                'total_onu'      => $data['total_onu'],
                'online_onu'     => $data['online_onu'],
                'offline_onu'    => $data['offline_onu'],
                'avg_rx_power'   => $data['avg_rx_power'],
                'excellent_onu'  => (int)($sigDist->excellent ?? 0),
                'good_onu'       => (int)($sigDist->good ?? 0),
                'fair_onu'       => (int)($sigDist->fair ?? 0),
                'poor_onu'       => (int)($sigDist->poor ?? 0),
                'polled_at'      => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            // Update ONU optical data (rx_power, temperature, voltage, tx_bias)
            foreach ($data['optical_data'] ?? [] as $key => $optData) {
                DB::table('onus')
                    ->where('olt_device_id', $olt->id)
                    ->where('pon_port', $optData['port'])
                    ->where('onu_number', $optData['onu_number'])
                    ->update([
                        'rx_power'    => $optData['rx_power'],
                        'tx_power'    => $optData['tx_power'],
                        'temperature' => $optData['temperature'],
                        'voltage'     => $optData['voltage'],
                        'tx_bias'     => $optData['tx_bias'],
                        'updated_at'  => $now,
                    ]);
            }


            // Also save port-level TX power to olt_pon_port_metrics
            $portSeenPoll = [];
            foreach ($data['optical_data'] ?? [] as $_key => $optPoll) {
                $pollPort = strtoupper($optPoll['port'] ?? '');
                if (!$pollPort || isset($portSeenPoll[$pollPort])) continue;
                if (($optPoll['tx_power'] ?? null) === null && ($optPoll['temperature'] ?? null) === null) continue;
                $portSeenPoll[$pollPort] = true;
                DB::table('olt_pon_port_metrics')->updateOrInsert(
                    ['olt_device_id' => $olt->id, 'pon_port' => $pollPort],
                    [
                        'tx_power'    => $optPoll['tx_power']    ?? null,
                        'temperature' => $optPoll['temperature'] ?? null,
                        'voltage'     => $optPoll['voltage']     ?? null,
                        'tx_bias'     => $optPoll['tx_bias']     ?? null,
                        'polled_at'   => $now,
                        'updated_at'  => $now,
                    ]
                );
            }

            foreach ($data['port_traffic'] as $portNo => $traffic) {
                DB::table('olt_traffic_stats')->insert([
                    'olt_device_id' => $olt->id,
                    'company_id'    => $olt->company_id,
                    'port_no'       => $portNo,
                    'in_octets'     => $traffic['in_octets'],
                    'out_octets'    => $traffic['out_octets'],
                    'in_rate_bps'   => $traffic['in_rate_bps'],
                    'out_rate_bps'  => $traffic['out_rate_bps'],
                    'total_onu'     => $data['total_onu'],
                    'online_onu'    => $data['online_onu'],
                    'polled_at'     => $now,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            $oltUpdateData = [
                'status'    => 'online',
                'last_sync' => $now,
            ];
            if ($data['cpu_usage'] !== null)    $oltUpdateData['cpu_usage']    = $data['cpu_usage'];
            if ($data['memory_usage'] !== null)  $oltUpdateData['memory_usage']  = $data['memory_usage'];
            if ($data['temperature'] !== null)   $oltUpdateData['temperature']   = $data['temperature'];
            if (!empty($data['uptime_seconds'])) $oltUpdateData['uptime_seconds'] = $data['uptime_seconds'];
            $olt->update($oltUpdateData);

            return response()->json([
                'success'     => true,
                'message'     => 'Dashboard data refreshed successfully.',
                'cpu'         => $data['cpu_usage'],
                'temperature' => $data['temperature'],
                'uptime'      => $data['uptime_seconds'],
                'total_onu'   => $data['total_onu'],
                'online_onu'  => $data['online_onu'],
                'offline_onu' => $data['offline_onu'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function scanAndMatch(OltDevice $olt)
    {
        try {
            /** @var \App\Services\OnuClientMappingService $svc */
            $svc    = app(\App\Services\OnuClientMappingService::class);
            $result = $svc->mapForOlt($olt);

            $summary = [];
            foreach ($result["log"] as $entry) {
                if ($entry["level"] === "success") {
                    $summary[] = $entry["msg"];
                }
            }

            \App\Services\OnuClientMappingService::clearMismatchCache();

            return response()->json([
                "success" => true,
                "mapped"  => $result["mapped"],
                "skipped" => $result["skipped"],
                "failed"  => $result["failed"],
                "log"     => $result["log"],
                "message" => $result["mapped"] > 0
                    ? $result["mapped"] . " client(s) successfully matched to ONUs on " . $olt->name
                    : "No new matches found on " . $olt->name . ". Check that clients are registered and ONUs are online.",
            ]);
        } catch (\Exception $e) {
            return response()->json(["success" => false, "message" => "Scan failed: " . $e->getMessage()]);
        }
    }


    public function onuMismatches(\Illuminate\Http\Request $request)
    {
        $oltDevices = \App\Models\OltDevice::where("is_active", true)->orderBy("name")->get(["id", "name"]);

        $oltIds = [];
        if ($request->filled("olt_id")) {
            $oltIds = [(int)$request->olt_id];
        }

        /** @var \App\Services\OnuClientMappingService $svc */
        $svc = app(\App\Services\OnuClientMappingService::class);
        $mismatches = $svc->getMismatches($oltIds);

        // Load OLT pon_type map: [olt_id => 'epon'|'gpon']
        $oltPonTypeMap = \App\Models\OltDevice::whereIn('id', $oltDevices->pluck('id'))
            ->pluck('pon_type', 'id')
            ->toArray();

        // Build PON ports list from onus table (covers OLTs with 0 mismatches too)
        $ponPortsByOlt = [];
        $onuPorts = \Illuminate\Support\Facades\DB::table('onus')
            ->whereNotNull('pon_port')
            ->select('olt_device_id', 'pon_port')
            ->distinct()
            ->get();
        foreach ($onuPorts as $row) {
            $oid      = $row->olt_device_id;
            $pp       = $row->pon_port;
            $oltType  = strtolower($oltPonTypeMap[$oid] ?? 'epon');
            $portType = strtolower(substr($pp, 0, 4)); // 'epon' or 'gpon'
            if ($portType === $oltType) {
                $ponPortsByOlt[$oid][] = $pp;
            }
        }
        // Also include any ports found in mismatch data (SNMP-only, not in DB yet)
        $allMismatches = $oltIds ? $svc->getMismatches([]) : $mismatches;
        foreach ($allMismatches as $m) {
            $oid      = $m["olt_id"];
            $pp       = $m["pon_port"] ?? null;
            $oltType  = strtolower($oltPonTypeMap[$oid] ?? 'epon');
            $portType = strtolower(substr($pp ?? '', 0, 4));
            if ($pp && $portType === $oltType && !in_array($pp, $ponPortsByOlt[$oid] ?? [])) {
                $ponPortsByOlt[$oid][] = $pp;
            }
        }
        foreach ($ponPortsByOlt as &$ports) {
            natsort($ports);
            $ports = array_values($ports);
        }
        unset($ports);

        // Filter by PON port
        if ($request->filled("pon_port")) {
            $filterPon = strtoupper($request->pon_port);
            $mismatches = array_values(array_filter($mismatches, fn($m) =>
                strtoupper($m["pon_port"] ?? "") === $filterPon
            ));
        }

        // Filter by username/mac search
        if ($request->filled("search")) {
            $search = strtolower($request->search);
            $mismatches = array_values(array_filter($mismatches, fn($m) =>
                str_contains(strtolower($m["username"] ?? ""), $search) ||
                str_contains(strtolower($m["caller_mac"] ?? ""), $search) ||
                str_contains(strtolower($m["onu_serial"] ?? ""), $search) ||
                str_contains(strtolower($m["onu_mac"] ?? ""), $search)
            ));
        }

        $totalCount = count($mismatches);
        $perPage = in_array((int)$request->per_page, [20, 50, 100]) ? (int)$request->per_page : 20;
        $page = max(1, (int)$request->get("page", 1));
        $paged = array_slice($mismatches, ($page - 1) * $perPage, $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paged, $totalCount, $perPage, $page,
            ["path" => $request->url(), "query" => $request->query()]
        );

        $zones    = \App\Models\Zone::with('areas')->where('is_active', true)->get();
        $packages = \App\Models\Package::where('is_active', true)->orderBy('name')->get();
        $boxes    = \App\Models\Box::where('status', 'active')->orderBy('name')->get();

        return view("admin.olt.onu-mismatches", [
            "mismatches"     => $paginator,
            "oltDevices"     => $oltDevices,
            "totalCount"     => $totalCount,
            "ponPortsByOlt"  => $ponPortsByOlt,
            "zones"          => $zones,
            "packages"       => $packages,
            "boxes"          => $boxes,
        ]);
    }

    public function importMismatchUser(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'last_name'    => 'nullable|string|max:255',
            'phone'        => 'required|string|max:20',
            'username'     => 'required|string|unique:clients,username',
            'zone_id'      => 'required|exists:zones,id',
            'package_id'   => 'required|exists:packages,id',
            'address'      => 'nullable|string',
            'area_id'      => 'nullable|exists:areas,id',
            'box_id'       => 'nullable|exists:boxes,id',
            'pppoe_password' => 'nullable|string',
            'monthly_fee'  => 'nullable|numeric|min:0',
            'status'       => 'required|in:active,inactive',
            'onu_serial'   => 'nullable|string|max:255',
            'onu_mac'      => 'nullable|string|max:50',
            'olt_device_id' => 'nullable|exists:olt_devices,id',
            'olt_port'     => 'nullable|string|max:50',
            'caller_mac'   => 'nullable|string|max:50',
            'install_charge_status'      => 'nullable|in:free,due,paid',
            'install_charge_amount'      => 'nullable|numeric|min:0',
            'install_payment_method'     => 'nullable|string|max:50',
            'install_account_number'     => 'nullable|string|max:100',
            'install_transaction_number' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $package        = \App\Models\Package::findOrFail($request->package_id);
            $connectionDate = now();
            $installStatus  = $request->install_charge_status ?? 'free';
            $installAmount  = (float) ($request->install_charge_amount ?? 0);
            $isFree         = ($installStatus === 'free');
            $isPaid         = ($installStatus === 'paid');

            $client = \App\Models\Client::create([
                'company_id'        => auth()->user()->company_id ?? null,
                'name'              => $request->name,
                'last_name'         => $request->last_name,
                'phone'             => $request->phone,
                'address'           => $request->address ?? '',
                'username'          => $request->username,
                'pppoe_password'    => $request->pppoe_password,
                'connection_type'   => 'pppoe',
                'zone_id'           => $request->zone_id,
                'area_id'           => $request->area_id,
                'box_id'            => $request->box_id,
                'package_id'        => $request->package_id,
                'mikrotik_profile_name' => $package->name,
                'connection_date'   => $connectionDate,
                'expire_date'       => ($package->validity_days && $package->validity_days != 30)
                    ? $connectionDate->copy()->addDays($package->validity_days)
                    : $connectionDate->copy()->addMonthNoOverflow(),
                'billing_date'      => 1,
                'monthly_fee'       => $request->monthly_fee ?? $package->price,
                'status'            => $request->status,
                'connection_status' => 'offline',
                'balance'           => 0,
                'due_amount'        => 0,
                'installation_charge' => $isFree ? 0 : $installAmount,
                'is_free'           => $isFree,
                'signup_fee_paid'   => $isPaid,
                'mac_address'       => $request->caller_mac,
                'onu_serial'        => $request->onu_serial,
                'onu_mac'           => $request->onu_mac,
                'olt_id'            => $request->olt_device_id,
                'olt_port'          => $request->olt_port,
                'remarks'           => 'Imported from ONU Mismatches',
            ]);

            // Link ONU record if exists
            if ($request->onu_serial || $request->onu_mac) {
                $onu = DB::table('onus')
                    ->where('serial_number', $request->onu_serial)
                    ->orWhere('mac_address', $request->onu_mac)
                    ->first();
                if ($onu) {
                    DB::table('onus')->where('id', $onu->id)->update([
                        'client_id'  => $client->id,
                        'updated_at' => now(),
                    ]);
                    $client->update([
                        'onu_id'     => $onu->id,
                        'olt_port'   => $onu->pon_port,
                        'onu_status' => $onu->status,
                    ]);
                }
            }

            // Installation charge
            if (!$isFree && $installAmount > 0) {
                $chargeData = [
                    'client_id'   => $client->id,
                    'charge_type' => 'Installation Charge',
                    'amount'      => $installAmount,
                    'description' => 'Installation charge (imported from ONU Mismatches)',
                ];
                if ($isPaid) {
                    $chargeData['paid_by']        = auth()->id();
                    $chargeData['payment_method'] = $request->install_payment_method ?? 'cash';
                    $chargeData['paid_at']        = now();
                    if ($request->install_payment_method && $request->install_payment_method !== 'cash') {
                        $chargeData['sender_number']  = $request->install_account_number ?? null;
                        $chargeData['transaction_id'] = $request->install_transaction_number ?? null;
                    }
                }
                \App\Models\ClientServiceCharge::create($chargeData);
            }

            // Auto-generate first bill
            try {
                $billingService = app(\App\Services\BillingService::class);
                $billingService->generateBill($client, now()->month, now()->year, suppressSms: true);
            } catch (\Exception $e) {}

            DB::commit();

            return redirect()->route('admin.olt.onu-mismatches')
                ->with('success', "User '{$request->username}' imported successfully as client '{$client->name}'.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.olt.onu-mismatches')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }



    public function opticalLossCalculator()
    {
        return view('admin.network.optical-loss-calculator');
    }

}
