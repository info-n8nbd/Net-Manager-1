<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MikrotikDevice;
use App\Models\SystemSetting;
use App\Models\VpnAccount;
use App\Services\VpnService;
use Illuminate\Http\Request;

class VpnController extends Controller
{
    protected VpnService $vpnService;

    public function __construct(VpnService $vpnService)
    {
        $this->vpnService = $vpnService;
    }

    public function index(Request $request)
    {
        $query = VpnAccount::with(['client', 'mikrotikDevice', 'linkedRouters']);

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->filled('vpn_type')) {
            $query->where('vpn_type', $request->vpn_type);
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('username', 'ILIKE', "%{$search}%")
                  ->orWhere('local_address', 'ILIKE', "%{$search}%")
                  ->orWhere('remote_address', 'ILIKE', "%{$search}%")
                  ->orWhere('server_address', 'ILIKE', "%{$search}%");
            });
        }

        $accounts = $query->latest()->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $vpnTypes = VpnAccount::getVpnTypes();
        $mainCount = VpnAccount::where('account_type', 'main_router')->count();
        $branchCount = VpnAccount::where('account_type', 'branch_router')->count();
        $activeCount = VpnAccount::where('is_active', true)->count();

        return view('admin.vpn.index', compact('accounts', 'vpnTypes', 'mainCount', 'branchCount', 'activeCount'));
    }

    public function create()
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $mikrotikDevices = MikrotikDevice::orderBy('name')->get();
        $vpnTypes = VpnAccount::getVpnTypes();
        $accountTypes = VpnAccount::getAccountTypes();

        return view('admin.vpn.create', compact('clients', 'mikrotikDevices', 'vpnTypes', 'accountTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_type' => 'required|string|in:main_router,branch_router,client',
            'vpn_type' => 'required|string|in:l2tp,pptp,sstp,ovpn',
            'username' => 'nullable|string|max:255|unique:vpn_accounts,username',
            'password' => 'nullable|string|min:4|max:255',
            'server_address' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'local_address' => 'nullable|string|max:255',
            'remote_address' => 'nullable|string|max:255',
            'dns_server' => 'nullable|string|max:255',
            'profile' => 'nullable|string|max:255',
            'mikrotik_device_id' => 'nullable|exists:mikrotik_devices,id',
            'monthly_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'auto_generate' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'name', 'account_type', 'client_id', 'vpn_type', 'username', 'password',
            'server_address', 'local_address', 'remote_address', 'dns_server', 'profile',
            'service', 'mikrotik_device_id', 'monthly_fee', 'notes',
        ]);

        if ($request->input('auto_generate') || empty($data['username'])) {
            $creds = VpnAccount::generateRandomCredentials();
            $data['username'] = $creds['username'];
            $data['password'] = $creds['password'];
        }

        if (empty($data['remote_address'])) {
            $data['remote_address'] = VpnAccount::getNextAvailableIp();
        }

        if (empty($data['local_address'])) {
            $data['local_address'] = SystemSetting::get('vpn_local_gateway', '100.127.224.1');
        }

        if (empty($data['server_address'])) {
            $data['server_address'] = SystemSetting::get('vpn_server_domain', '');
        }

        $account = $this->vpnService->createAccount($data);

        return redirect()->route('admin.vpn.show', $account)
            ->with('success', 'VPN account created successfully. Copy the MikroTik commands below.');
    }

    public function show(VpnAccount $vpnAccount)
    {
        $vpnAccount->load('linkedRouters', 'client', 'mikrotikDevice');
        $commands = $vpnAccount->generateMikrotikCommands();

        return view('admin.vpn.show', compact('vpnAccount', 'commands'));
    }

    public function edit(VpnAccount $vpnAccount)
    {
        $vpnAccount->load('linkedRouters');
        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $mikrotikDevices = MikrotikDevice::orderBy('name')->get();
        $vpnTypes = VpnAccount::getVpnTypes();
        $accountTypes = VpnAccount::getAccountTypes();

        return view('admin.vpn.edit', compact('vpnAccount', 'clients', 'mikrotikDevices', 'vpnTypes', 'accountTypes'));
    }

    public function update(Request $request, VpnAccount $vpnAccount)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_type' => 'required|string|in:main_router,branch_router,client',
            'vpn_type' => 'required|string|in:l2tp,pptp,sstp,ovpn',
            'username' => 'required|string|max:255|unique:vpn_accounts,username,' . $vpnAccount->id,
            'password' => 'nullable|string|min:4|max:255',
            'server_address' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'local_address' => 'nullable|string|max:255',
            'remote_address' => 'nullable|string|max:255',
            'dns_server' => 'nullable|string|max:255',
            'profile' => 'nullable|string|max:255',
            'mikrotik_device_id' => 'nullable|exists:mikrotik_devices,id',
            'monthly_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data = $request->only([
            'name', 'account_type', 'client_id', 'vpn_type', 'username',
            'server_address', 'local_address', 'remote_address', 'dns_server', 'profile',
            'service', 'mikrotik_device_id', 'monthly_fee', 'notes',
        ]);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $data['is_active'] = $request->has('is_active');

        $this->vpnService->updateAccount($vpnAccount, $data);

        return back()->with('success', 'VPN account updated successfully.');
    }

    public function destroy(VpnAccount $vpnAccount)
    {
        $linkedCount = $vpnAccount->linkedRouters()->count();
        if ($linkedCount > 0) {
            return back()->with('error', "Cannot delete: {$linkedCount} router(s) are linked to this VPN account. Unlink them first.");
        }

        $this->vpnService->deleteAccount($vpnAccount);

        return redirect()->route('admin.vpn.index')
            ->with('success', 'VPN account deleted successfully.');
    }

    public function toggle(VpnAccount $vpnAccount)
    {
        $this->vpnService->toggleStatus($vpnAccount);
        $status = $vpnAccount->fresh()->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "VPN account {$status} successfully.");
    }

    public function settings()
    {
        $settings = [
            'vpn_server_domain' => SystemSetting::get('vpn_server_domain', ''),
            'vpn_server_port' => SystemSetting::get('vpn_server_port', '443'),
            'vpn_network_range' => SystemSetting::get('vpn_network_range', '100.127.240.0/20'),
            'vpn_local_gateway' => SystemSetting::get('vpn_local_gateway', '100.127.224.1'),
            'vpn_ip_start_octet3' => SystemSetting::get('vpn_ip_start_octet3', '230'),
            'vpn_ip_start_octet4' => SystemSetting::get('vpn_ip_start_octet4', '1'),
        ];

        return view('admin.vpn.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'vpn_server_domain' => 'required|string|max:255',
            'vpn_server_port' => 'required|string|max:10',
            'vpn_network_range' => 'required|string|max:50',
            'vpn_local_gateway' => 'required|string|max:50',
            'vpn_ip_start_octet3' => 'required|integer|min:0|max:255',
            'vpn_ip_start_octet4' => 'required|integer|min:1|max:254',
        ]);

        foreach (['vpn_server_domain', 'vpn_server_port', 'vpn_network_range', 'vpn_local_gateway', 'vpn_ip_start_octet3', 'vpn_ip_start_octet4'] as $key) {
            SystemSetting::set($key, $request->input($key), 'vpn');
        }

        return back()->with('success', 'VPN server settings saved successfully.');
    }
}
