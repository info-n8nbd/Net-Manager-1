<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\Zone;
use App\Services\SimpleMikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MacBindingController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::query()
            ->with('zone', 'package')
            ->whereNotNull('mac_address')
            ->where('mac_address', '!=', '')
            ->when($request->zone_id, fn($q) => $q->where('zone_id', $request->zone_id))
            ->when($request->binding_status, function ($q, $status) {
                if ($status === 'bound') {
                    $q->where('mac_binding_enabled', true);
                }
                if ($status === 'unbound') {
                    $q->where(function ($q2) {
                        $q2->where('mac_binding_enabled', false)->orWhereNull('mac_binding_enabled');
                    });
                }
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'ilike', "%{$search}%")
                       ->orWhere('client_id', 'ilike', "%{$search}%")
                       ->orWhere('mac_address', 'ilike', "%{$search}%")
                       ->orWhere('ip_address', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        $zones = Zone::orderBy('name')->get();

        return view('admin.network.mac-bindings', compact('clients', 'zones'));
    }

    public function toggleBinding(Request $request, Client $client)
    {
        $client->update(['mac_binding_enabled' => !$client->mac_binding_enabled]);

        if ($client->mac_binding_enabled && $client->mac_address) {
            $this->syncMacBinding($client);
        } else {
            $this->removeMacBinding($client);
        }

        return response()->json(['success' => true, 'bound' => $client->mac_binding_enabled]);
    }

    public function updateMac(Request $request, Client $client)
    {
        $request->validate(['mac_address' => 'required|regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/']);
        $client->update(['mac_address' => strtoupper($request->mac_address)]);

        if ($client->mac_binding_enabled) {
            $this->syncMacBinding($client);
        }

        return response()->json(['success' => true, 'message' => 'MAC address updated']);
    }

    public function bulkBind(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $count = 0;
        foreach ($request->client_ids as $clientId) {
            $client = Client::find($clientId);
            if ($client && $client->mac_address) {
                $client->update(['mac_binding_enabled' => true]);
                $this->syncMacBinding($client);
                $count++;
            }
        }

        return response()->json(['success' => true, 'message' => "{$count} clients bound successfully"]);
    }

    protected function syncMacBinding(Client $client)
    {
        try {
            $device = $client->mikrotik_device_id
                ? MikrotikDevice::find($client->mikrotik_device_id)
                : MikrotikDevice::where('is_active', true)->first();

            if (!$device) {
                return;
            }

            $mikrotik = new SimpleMikroTikService();
            if (!$mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))) {
                return;
            }

            if ($client->ip_address && $client->mac_address) {
                $mikrotik->sendCommand('/ip/arp/add', [
                    'address' => $client->ip_address,
                    'mac-address' => $client->mac_address,
                    'interface' => 'ether1',
                    'comment' => "MAC-Bind: {$client->name} ({$client->client_id})",
                ]);
            }
        } catch (\Exception $e) {
            Log::error("MAC binding sync failed for client {$client->id}: " . $e->getMessage());
        }
    }

    protected function removeMacBinding(Client $client)
    {
        try {
            $device = $client->mikrotik_device_id
                ? MikrotikDevice::find($client->mikrotik_device_id)
                : MikrotikDevice::where('is_active', true)->first();

            if (!$device) {
                return;
            }

            $mikrotik = new SimpleMikroTikService();
            if (!$mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728))) {
                return;
            }

            if ($client->ip_address) {
                $entries = $mikrotik->sendCommand('/ip/arp/print', [
                    '?address' => $client->ip_address,
                ]);

                if (!empty($entries) && is_array($entries)) {
                    foreach ($entries as $entry) {
                        if (isset($entry['.id'])) {
                            $mikrotik->sendCommand('/ip/arp/remove', [
                                '.id' => $entry['.id'],
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("MAC binding removal failed for client {$client->id}: " . $e->getMessage());
        }
    }
}
