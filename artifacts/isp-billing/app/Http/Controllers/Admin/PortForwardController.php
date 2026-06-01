<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\PortForward;
use App\Services\PortForwardService;
use Illuminate\Http\Request;

class PortForwardController extends Controller
{
    protected PortForwardService $portForwardService;

    public function __construct(PortForwardService $portForwardService)
    {
        $this->portForwardService = $portForwardService;
    }

    public function index(Request $request)
    {
        $query = PortForward::with(['client', 'mikrotikDevice', 'creator']);

        if ($request->filled('protocol')) {
            $query->where('protocol', $request->protocol);
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('internal_ip', 'ILIKE', "%{$search}%")
                  ->orWhere('external_port', 'ILIKE', "%{$search}%");
            });
        }

        $rules = $query->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.port-forward.index', compact('rules'));
    }

    public function create()
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $mikrotikDevices = MikrotikDevice::orderBy('name')->get();

        return view('admin.port-forward.create', compact('clients', 'mikrotikDevices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'name' => 'required|string|max:255',
            'protocol' => 'required|string|in:tcp,udp,tcp+udp',
            'external_port' => 'required|string|max:20',
            'internal_ip' => 'required|ip',
            'internal_port' => 'required|string|max:20',
            'wan_interface' => 'nullable|string|max:50',
            'mikrotik_device_id' => 'nullable|exists:mikrotik_devices,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->portForwardService->createRule($request->only([
            'client_id', 'name', 'protocol', 'external_port',
            'internal_ip', 'internal_port', 'wan_interface',
            'mikrotik_device_id', 'notes',
        ]));

        return redirect()->route('admin.port-forward.index')
            ->with('success', 'Port forwarding rule created successfully.');
    }

    public function edit(PortForward $portForward)
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $mikrotikDevices = MikrotikDevice::orderBy('name')->get();

        return view('admin.port-forward.edit', compact('portForward', 'clients', 'mikrotikDevices'));
    }

    public function update(Request $request, PortForward $portForward)
    {
        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'name' => 'required|string|max:255',
            'protocol' => 'required|string|in:tcp,udp,tcp+udp',
            'external_port' => 'required|string|max:20',
            'internal_ip' => 'required|ip',
            'internal_port' => 'required|string|max:20',
            'wan_interface' => 'nullable|string|max:50',
            'mikrotik_device_id' => 'nullable|exists:mikrotik_devices,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->portForwardService->updateRule($portForward, $request->only([
            'client_id', 'name', 'protocol', 'external_port',
            'internal_ip', 'internal_port', 'wan_interface',
            'mikrotik_device_id', 'notes',
        ]));

        return back()->with('success', 'Port forwarding rule updated successfully.');
    }

    public function destroy(PortForward $portForward)
    {
        $this->portForwardService->deleteRule($portForward);

        return redirect()->route('admin.port-forward.index')
            ->with('success', 'Port forwarding rule deleted successfully.');
    }

    public function toggle(PortForward $portForward)
    {
        $this->portForwardService->toggleStatus($portForward);
        $status = $portForward->fresh()->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "Port forwarding rule {$status} successfully.");
    }
}
