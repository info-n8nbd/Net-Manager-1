<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpPool;
use App\Models\IpAllocation;
use App\Models\Branch;
use App\Models\Zone;
use App\Models\Pop;
use App\Models\Client;
use App\Services\IpamService;
use Illuminate\Http\Request;

class IpamController extends Controller
{
    protected IpamService $ipamService;

    public function __construct(IpamService $ipamService)
    {
        $this->ipamService = $ipamService;
    }

    public function index()
    {
        $pools = IpPool::with(['branch', 'zone', 'pop'])
            ->withCount([
                'allocations as assigned_count' => fn($q) => $q->where('status', 'assigned'),
                'allocations as reserved_count' => fn($q) => $q->where('status', 'reserved'),
            ])
            ->latest()
            ->get();

        $ipv4Pools = $pools->where('ip_version', 'ipv4');
        $ipv6Pools = $pools->where('ip_version', 'ipv6');

        return view('admin.ipam.index', compact('pools', 'ipv4Pools', 'ipv6Pools'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $zones = Zone::all();
        $pops = Pop::where('status', 'active')->get();
        return view('admin.ipam.create', compact('branches', 'zones', 'pops'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'network_address' => 'required|string',
            'cidr_prefix' => 'required|integer|min:1|max:128',
            'ip_version' => 'required|in:ipv4,ipv6',
            'pool_type' => 'required|in:pppoe,hotspot,static,dhcp,management',
        ]);

        try {
            $this->ipamService->createPool($request->only([
                'name', 'network_address', 'subnet_mask', 'cidr_prefix', 'ip_version',
                'gateway', 'dns_primary', 'dns_secondary', 'pool_type',
                'branch_id', 'zone_id', 'pop_id', 'mikrotik_device_id', 'description',
            ]));

            return redirect()->route('admin.ipam.index')->with('success', 'IP Pool created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(IpPool $pool)
    {
        $pool->load(['branch', 'zone', 'pop']);
        $utilization = $this->ipamService->getPoolUtilization($pool->id);
        $allocations = IpAllocation::where('ip_pool_id', $pool->id)
            ->with('client')
            ->orderBy('ip_address')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        return view('admin.ipam.show', compact('pool', 'utilization', 'allocations'));
    }

    public function allocate(Request $request, IpPool $pool)
    {
        $request->validate([
            'ip_address' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        try {
            $allocation = $this->ipamService->allocateIp($pool->id, $request->client_id, $request->ip_address);
            return back()->with('success', "IP {$allocation->ip_address} allocated successfully");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function release(IpAllocation $allocation)
    {
        try {
            $this->ipamService->releaseIp($allocation->id);
            return back()->with('success', 'IP released successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(IpPool $pool)
    {
        $branches = Branch::where('is_active', true)->get();
        $zones = Zone::all();
        $pops = Pop::where('status', 'active')->get();
        return view('admin.ipam.edit', compact('pool', 'branches', 'zones', 'pops'));
    }

    public function update(Request $request, IpPool $pool)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $pool->update($request->only([
            'name', 'gateway', 'dns_primary', 'dns_secondary', 'pool_type',
            'branch_id', 'zone_id', 'pop_id', 'description', 'is_active',
        ]));

        return redirect()->route('admin.ipam.index')->with('success', 'IP Pool updated successfully');
    }

    public function destroy(IpPool $pool)
    {
        if ($pool->allocations()->where('status', 'assigned')->count() > 0) {
            return back()->with('error', 'Cannot delete pool with active allocations');
        }
        $pool->delete();
        return redirect()->route('admin.ipam.index')->with('success', 'IP Pool deleted successfully');
    }
}
