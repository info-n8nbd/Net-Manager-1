<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Services\SimpleMikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConnectivityTestController extends Controller
{
    public function index()
    {
        return view('admin.network.connectivity-test');
    }

    public function pingFromRouter(Request $request)
    {
        $request->validate(['client_id' => 'required|exists:clients,id']);
        $client = Client::find($request->client_id);

        if (!$client->ip_address) {
            return response()->json(['success' => false, 'message' => 'Client has no IP address assigned']);
        }

        $device = $this->findDevice($client);
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'No MikroTik device found for this client']);
        }

        try {
            $mikrotik = new SimpleMikroTikService();
            if (!$mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))) {
                return response()->json(['success' => false, 'message' => 'Cannot connect to router']);
            }

            $result = $mikrotik->sendCommand('/ping', [
                'address' => $client->ip_address,
                'count' => '4'
            ]);

            return response()->json(['success' => true, 'data' => $result, 'message' => 'Ping completed']);
        } catch (\Exception $e) {
            Log::error("Ping test failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ping failed: ' . $e->getMessage()]);
        }
    }

    public function traceroute(Request $request)
    {
        $request->validate(['client_id' => 'required|exists:clients,id']);
        $client = Client::find($request->client_id);

        if (!$client->ip_address) {
            return response()->json(['success' => false, 'message' => 'Client has no IP address assigned']);
        }

        $device = $this->findDevice($client);
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'No MikroTik device found for this client']);
        }

        try {
            $mikrotik = new SimpleMikroTikService();
            if (!$mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))) {
                return response()->json(['success' => false, 'message' => 'Cannot connect to router']);
            }

            $result = $mikrotik->sendCommand('/tool/traceroute', [
                'address' => $client->ip_address,
                'count' => '1',
                'timeout' => '3s'
            ]);

            return response()->json(['success' => true, 'data' => $result, 'message' => 'Traceroute completed']);
        } catch (\Exception $e) {
            Log::error("Traceroute test failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Traceroute failed: ' . $e->getMessage()]);
        }
    }

    public function checkSession(Request $request)
    {
        $request->validate(['client_id' => 'required|exists:clients,id']);
        $client = Client::find($request->client_id);

        $device = $this->findDevice($client);
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'No MikroTik device found for this client']);
        }

        try {
            $mikrotik = new SimpleMikroTikService();
            if (!$mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))) {
                return response()->json(['success' => false, 'message' => 'Cannot connect to router']);
            }

            $username = $client->username;
            $connectionType = strtolower($client->connection_type ?? 'pppoe');
            $sessionData = null;

            if ($connectionType === 'pppoe') {
                $sessionData = $mikrotik->sendCommand('/ppp/active/print', [
                    '?name' => $username
                ]);
            } elseif ($connectionType === 'hotspot') {
                $sessionData = $mikrotik->sendCommand('/ip/hotspot/active/print', [
                    '?user' => $username
                ]);
            } else {
                $sessionData = $mikrotik->sendCommand('/queue/simple/print', [
                    '?name' => $username
                ]);
            }

            $hasSession = !empty($sessionData) && is_array($sessionData) && count($sessionData) > 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'active' => $hasSession,
                    'session' => $hasSession ? $sessionData[0] : null,
                    'connection_type' => $connectionType,
                ],
                'message' => $hasSession ? 'Active session found' : 'No active session'
            ]);
        } catch (\Exception $e) {
            Log::error("Session check failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Session check failed: ' . $e->getMessage()]);
        }
    }

    public function dnsLookup(Request $request)
    {
        $request->validate(['domain' => 'required|string']);

        $device = MikrotikDevice::where('is_active', true)->first();
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'No MikroTik device found']);
        }

        try {
            $mikrotik = new SimpleMikroTikService();
            if (!$mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))) {
                return response()->json(['success' => false, 'message' => 'Cannot connect to router']);
            }

            $result = $mikrotik->sendCommand('/tool/dns-resolve', [
                'name' => $request->domain
            ]);

            return response()->json(['success' => true, 'data' => $result, 'message' => 'DNS lookup completed']);
        } catch (\Exception $e) {
            Log::error("DNS lookup failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'DNS lookup failed: ' . $e->getMessage()]);
        }
    }

    protected function findDevice(Client $client): ?MikrotikDevice
    {
        if ($client->mikrotik_device_id) {
            return MikrotikDevice::find($client->mikrotik_device_id);
        }

        return MikrotikDevice::where('is_active', true)->first();
    }
}
