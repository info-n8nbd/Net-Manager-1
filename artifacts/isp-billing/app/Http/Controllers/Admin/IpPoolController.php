<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikDevice;
use App\Services\IpPoolSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IpPoolController extends Controller
{
    public function index()
    {
        $pools = DB::table('ip_pools')
            ->leftJoin('mikrotik_devices', 'ip_pools.mikrotik_device_id', '=', 'mikrotik_devices.id')
            ->select('ip_pools.*', 'mikrotik_devices.name as router_name')
            ->orderBy('ip_pools.name')
            ->get();

        foreach ($pools as $pool) {
            $pool->total_ips = DB::table('ip_addresses')->where('ip_pool_id', $pool->id)->count();
            $pool->used_ips = DB::table('ip_addresses')->where('ip_pool_id', $pool->id)->where('status', 'assigned')->count();
            $pool->available_ips = $pool->total_ips - $pool->used_ips;
        }

        $devices = MikrotikDevice::where('is_active', true)->get();
        return view('admin.network.ip-pools', compact('pools', 'devices'));
    }

    public function show($poolId)
    {
        $pool = DB::table('ip_pools')
            ->leftJoin('mikrotik_devices', 'ip_pools.mikrotik_device_id', '=', 'mikrotik_devices.id')
            ->select('ip_pools.*', 'mikrotik_devices.name as router_name')
            ->where('ip_pools.id', $poolId)
            ->first();

        if (!$pool) {
            return redirect()->route('admin.network.ip-pools')->with('error', 'Pool not found.');
        }

        $addresses = DB::table('ip_addresses')
            ->leftJoin('clients', 'ip_addresses.client_id', '=', 'clients.id')
            ->select('ip_addresses.*', 'clients.name as client_name', 'clients.username as client_username')
            ->where('ip_addresses.ip_pool_id', $poolId)
            ->orderBy('ip_addresses.ip_address')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        $clients = DB::table('clients')
            ->where('status', 'active')
            ->whereNull('ip_address')
            ->orWhere('ip_address', '')
            ->select('id', 'name', 'username')
            ->orderBy('name')
            ->limit(500)
            ->get();

        return view('admin.network.ip-pool-detail', compact('pool', 'addresses', 'clients'));
    }

    public function syncToRouter($poolId)
    {
        $service = new IpPoolSyncService();
        $result = $service->syncPoolToRouter($poolId);
        return response()->json($result);
    }

    public function syncFromRouter(Request $request)
    {
        $request->validate(['device_id' => 'required|exists:mikrotik_devices,id']);

        $device = MikrotikDevice::findOrFail($request->device_id);
        $service = new IpPoolSyncService();
        $result = $service->syncPoolFromRouter($device);
        return response()->json($result);
    }

    public function assignIp(Request $request)
    {
        $request->validate([
            'pool_id' => 'required|integer',
            'client_id' => 'required|integer',
        ]);

        $service = new IpPoolSyncService();
        $result = $service->assignIpToClient($request->pool_id, $request->client_id);
        return response()->json($result);
    }

    public function releaseIp($addressId)
    {
        $address = DB::table('ip_addresses')->where('id', $addressId)->first();
        if (!$address || !$address->client_id) {
            return response()->json(['success' => false, 'message' => 'Address not found or not assigned', 'data' => []]);
        }

        $service = new IpPoolSyncService();
        $result = $service->releaseClientIp($address->client_id);
        return response()->json($result);
    }
}
