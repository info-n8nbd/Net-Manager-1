<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikDevice;
use App\Services\SimpleMikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FirewallController extends Controller
{
    public function index(MikrotikDevice $device)
    {
        $rules = DB::table('firewall_rules')
            ->where('mikrotik_device_id', $device->id)
            ->orderBy('position')
            ->get();
        $devices = MikrotikDevice::where('is_active', true)->get();
        return view('admin.network.firewall', compact('device', 'devices', 'rules'));
    }

    public function syncFromRouter(MikrotikDevice $device)
    {
        try {
            $mikrotik = new SimpleMikroTikService();
            $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

            if (!$connected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to router',
                    'data' => [],
                ]);
            }

            $response = $mikrotik->sendCommand('/ip/firewall/filter/print', []);
            $rules = is_array($response) ? $response : [];

            DB::table('firewall_rules')->where('mikrotik_device_id', $device->id)->delete();

            $count = 0;
            foreach ($rules as $index => $rule) {
                if (!is_array($rule) || isset($rule['!trap'])) continue;

                DB::table('firewall_rules')->insert([
                    'mikrotik_device_id' => $device->id,
                    'router_id' => $rule['.id'] ?? null,
                    'chain' => $rule['chain'] ?? 'forward',
                    'action' => $rule['action'] ?? 'accept',
                    'protocol' => $rule['protocol'] ?? null,
                    'src_address' => $rule['src-address'] ?? null,
                    'dst_address' => $rule['dst-address'] ?? null,
                    'src_port' => $rule['src-port'] ?? null,
                    'dst_port' => $rule['dst-port'] ?? null,
                    'in_interface' => $rule['in-interface'] ?? null,
                    'out_interface' => $rule['out-interface'] ?? null,
                    'comment' => $rule['comment'] ?? null,
                    'is_enabled' => ($rule['disabled'] ?? 'false') === 'false',
                    'position' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$count} firewall rules from router.",
                'data' => ['count' => $count],
            ]);
        } catch (\Exception $e) {
            Log::error('Firewall sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function store(Request $request, MikrotikDevice $device)
    {
        $validated = $request->validate([
            'chain' => 'required|in:input,forward,output',
            'action' => 'required|in:accept,drop,reject,log',
            'protocol' => 'nullable|string|max:20',
            'src_address' => 'nullable|string|max:50',
            'dst_address' => 'nullable|string|max:50',
            'src_port' => 'nullable|string|max:20',
            'dst_port' => 'nullable|string|max:20',
            'comment' => 'nullable|string|max:255',
        ]);

        try {
            $mikrotik = new SimpleMikroTikService();
            $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

            if (!$connected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to router',
                    'data' => [],
                ]);
            }

            $params = [
                'chain' => $validated['chain'],
                'action' => $validated['action'],
            ];

            if (!empty($validated['protocol'])) $params['protocol'] = $validated['protocol'];
            if (!empty($validated['src_address'])) $params['src-address'] = $validated['src_address'];
            if (!empty($validated['dst_address'])) $params['dst-address'] = $validated['dst_address'];
            if (!empty($validated['src_port'])) $params['src-port'] = $validated['src_port'];
            if (!empty($validated['dst_port'])) $params['dst-port'] = $validated['dst_port'];
            if (!empty($validated['comment'])) $params['comment'] = $validated['comment'];

            $response = $mikrotik->sendCommand('/ip/firewall/filter/add', $params);

            $maxPos = DB::table('firewall_rules')
                ->where('mikrotik_device_id', $device->id)
                ->max('position') ?? -1;

            DB::table('firewall_rules')->insert([
                'mikrotik_device_id' => $device->id,
                'router_id' => is_array($response) ? ($response[0]['ret'] ?? null) : null,
                'chain' => $validated['chain'],
                'action' => $validated['action'],
                'protocol' => $validated['protocol'] ?? null,
                'src_address' => $validated['src_address'] ?? null,
                'dst_address' => $validated['dst_address'] ?? null,
                'src_port' => $validated['src_port'] ?? null,
                'dst_port' => $validated['dst_port'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'is_enabled' => true,
                'position' => $maxPos + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Firewall rule created successfully.',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            Log::error('Firewall store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create rule: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function destroy(MikrotikDevice $device, $ruleId)
    {
        try {
            $rule = DB::table('firewall_rules')->where('id', $ruleId)->where('mikrotik_device_id', $device->id)->first();
            if (!$rule) {
                return response()->json(['success' => false, 'message' => 'Rule not found', 'data' => []]);
            }

            if ($rule->router_id) {
                $mikrotik = new SimpleMikroTikService();
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                if ($connected) {
                    $mikrotik->sendCommand('/ip/firewall/filter/remove', ['.id' => $rule->router_id]);
                }
            }

            DB::table('firewall_rules')->where('id', $ruleId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Firewall rule deleted successfully.',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            Log::error('Firewall delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rule: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function toggleRule(MikrotikDevice $device, $ruleId)
    {
        try {
            $rule = DB::table('firewall_rules')->where('id', $ruleId)->where('mikrotik_device_id', $device->id)->first();
            if (!$rule) {
                return response()->json(['success' => false, 'message' => 'Rule not found', 'data' => []]);
            }

            $newState = !$rule->is_enabled;

            if ($rule->router_id) {
                $mikrotik = new SimpleMikroTikService();
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                if ($connected) {
                    $cmd = $newState ? '/ip/firewall/filter/enable' : '/ip/firewall/filter/disable';
                    $mikrotik->sendCommand($cmd, ['.id' => $rule->router_id]);
                }
            }

            DB::table('firewall_rules')->where('id', $ruleId)->update([
                'is_enabled' => $newState,
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $newState ? 'Rule enabled.' : 'Rule disabled.',
                'data' => ['is_enabled' => $newState],
            ]);
        } catch (\Exception $e) {
            Log::error('Firewall toggle error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle rule: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function natIndex(MikrotikDevice $device)
    {
        $rules = DB::table('nat_rules')
            ->where('mikrotik_device_id', $device->id)
            ->orderBy('position')
            ->get();
        $devices = MikrotikDevice::where('is_active', true)->get();
        return view('admin.network.nat-rules', compact('device', 'devices', 'rules'));
    }

    public function syncNatFromRouter(MikrotikDevice $device)
    {
        try {
            $mikrotik = new SimpleMikroTikService();
            $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

            if (!$connected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to router',
                    'data' => [],
                ]);
            }

            $response = $mikrotik->sendCommand('/ip/firewall/nat/print', []);
            $rules = is_array($response) ? $response : [];

            DB::table('nat_rules')->where('mikrotik_device_id', $device->id)->delete();

            $count = 0;
            foreach ($rules as $index => $rule) {
                if (!is_array($rule) || isset($rule['!trap'])) continue;

                DB::table('nat_rules')->insert([
                    'mikrotik_device_id' => $device->id,
                    'router_id' => $rule['.id'] ?? null,
                    'chain' => $rule['chain'] ?? 'srcnat',
                    'action' => $rule['action'] ?? 'masquerade',
                    'protocol' => $rule['protocol'] ?? null,
                    'src_address' => $rule['src-address'] ?? null,
                    'dst_address' => $rule['dst-address'] ?? null,
                    'src_port' => $rule['src-port'] ?? null,
                    'dst_port' => $rule['dst-port'] ?? null,
                    'to_address' => $rule['to-addresses'] ?? null,
                    'to_port' => $rule['to-ports'] ?? null,
                    'in_interface' => $rule['in-interface'] ?? null,
                    'out_interface' => $rule['out-interface'] ?? null,
                    'comment' => $rule['comment'] ?? null,
                    'is_enabled' => ($rule['disabled'] ?? 'false') === 'false',
                    'position' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$count} NAT rules from router.",
                'data' => ['count' => $count],
            ]);
        } catch (\Exception $e) {
            Log::error('NAT sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function storeNat(Request $request, MikrotikDevice $device)
    {
        $validated = $request->validate([
            'chain' => 'required|in:srcnat,dstnat',
            'action' => 'required|in:masquerade,dst-nat,src-nat,accept,drop',
            'protocol' => 'nullable|string|max:20',
            'src_address' => 'nullable|string|max:50',
            'dst_address' => 'nullable|string|max:50',
            'src_port' => 'nullable|string|max:20',
            'dst_port' => 'nullable|string|max:20',
            'to_address' => 'nullable|string|max:50',
            'to_port' => 'nullable|string|max:20',
            'comment' => 'nullable|string|max:255',
        ]);

        try {
            $mikrotik = new SimpleMikroTikService();
            $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));

            if (!$connected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to router',
                    'data' => [],
                ]);
            }

            $params = [
                'chain' => $validated['chain'],
                'action' => $validated['action'],
            ];

            if (!empty($validated['protocol'])) $params['protocol'] = $validated['protocol'];
            if (!empty($validated['src_address'])) $params['src-address'] = $validated['src_address'];
            if (!empty($validated['dst_address'])) $params['dst-address'] = $validated['dst_address'];
            if (!empty($validated['src_port'])) $params['src-port'] = $validated['src_port'];
            if (!empty($validated['dst_port'])) $params['dst-port'] = $validated['dst_port'];
            if (!empty($validated['to_address'])) $params['to-addresses'] = $validated['to_address'];
            if (!empty($validated['to_port'])) $params['to-ports'] = $validated['to_port'];
            if (!empty($validated['comment'])) $params['comment'] = $validated['comment'];

            $response = $mikrotik->sendCommand('/ip/firewall/nat/add', $params);

            $maxPos = DB::table('nat_rules')
                ->where('mikrotik_device_id', $device->id)
                ->max('position') ?? -1;

            DB::table('nat_rules')->insert([
                'mikrotik_device_id' => $device->id,
                'router_id' => is_array($response) ? ($response[0]['ret'] ?? null) : null,
                'chain' => $validated['chain'],
                'action' => $validated['action'],
                'protocol' => $validated['protocol'] ?? null,
                'src_address' => $validated['src_address'] ?? null,
                'dst_address' => $validated['dst_address'] ?? null,
                'src_port' => $validated['src_port'] ?? null,
                'dst_port' => $validated['dst_port'] ?? null,
                'to_address' => $validated['to_address'] ?? null,
                'to_port' => $validated['to_port'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'is_enabled' => true,
                'position' => $maxPos + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'NAT rule created successfully.',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            Log::error('NAT store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create NAT rule: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function destroyNat(MikrotikDevice $device, $ruleId)
    {
        try {
            $rule = DB::table('nat_rules')->where('id', $ruleId)->where('mikrotik_device_id', $device->id)->first();
            if (!$rule) {
                return response()->json(['success' => false, 'message' => 'Rule not found', 'data' => []]);
            }

            if ($rule->router_id) {
                $mikrotik = new SimpleMikroTikService();
                $connected = $mikrotik->connect($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                if ($connected) {
                    $mikrotik->sendCommand('/ip/firewall/nat/remove', ['.id' => $rule->router_id]);
                }
            }

            DB::table('nat_rules')->where('id', $ruleId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'NAT rule deleted successfully.',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            Log::error('NAT delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete NAT rule: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }
}
