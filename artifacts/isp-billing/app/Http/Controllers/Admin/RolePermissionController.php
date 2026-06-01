<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    private array $adminRoleNames = ['administrator', 'billing officer', 'supervisor', 'collection officer', 'network engineer', 'support executive'];

    private function authorizeSuperAdmin()
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Only Super Admin can manage role permissions.');
        }
    }

    public function index()
    {
        $this->authorizeSuperAdmin();

        $roles = Role::whereNotIn('name', ['Super Admin', 'client'])
            ->orderBy('name')
            ->get();

        $allPermissions = Permission::orderBy('name')->get();

        $resellerPermissions = $allPermissions->filter(fn($p) => str_starts_with($p->name, 'reseller.'))->values();
        $technicianPermissions = $allPermissions->filter(fn($p) => str_starts_with($p->name, 'technician.'))->values();

        $rolePermissions = [];
        foreach ($roles as $role) {
            $rolePermissions[$role->id] = $role->permissions->pluck('name')->toArray();
        }

        $resellerActionLabels = [
            'reseller.dashboard'       => ['icon' => 'fa-tachometer-alt text-primary',  'label' => 'Dashboard'],
            'reseller.clients'         => ['icon' => 'fa-users text-info',               'label' => 'View Clients'],
            'reseller.client_edit'     => ['icon' => 'fa-user-edit text-warning',        'label' => 'Edit Clients'],
            'reseller.client_restore'  => ['icon' => 'fa-undo text-success',             'label' => 'Restore Deleted Clients'],
            'reseller.device_control'  => ['icon' => 'fa-server text-secondary',         'label' => 'Enable / Disable on MikroTik'],
            'reseller.wallet'          => ['icon' => 'fa-wallet text-success',            'label' => 'Wallet Balance & Transactions'],
            'reseller.packages'        => ['icon' => 'fa-box text-info',                 'label' => 'View My Packages'],
            'reseller.collection'      => ['icon' => 'fa-chart-line text-primary',       'label' => 'Collection Report'],
            'reseller.due_report'      => ['icon' => 'fa-exclamation-triangle text-danger', 'label' => 'Due Report'],
            'reseller.tickets'         => ['icon' => 'fa-ticket-alt text-muted',         'label' => 'Support Tickets'],
        ];

        $technicianActionLabels = [
            'technician.dashboard'         => ['icon' => 'fa-tachometer-alt text-primary',     'label' => 'Dashboard'],
            'technician.clients'           => ['icon' => 'fa-users text-info',                  'label' => 'View Clients'],
            'technician.client.create'     => ['icon' => 'fa-user-plus text-success',           'label' => 'Add New Client'],
            'technician.client.edit'       => ['icon' => 'fa-user-edit text-warning',           'label' => 'Edit Client Info'],
            'technician.device_control'    => ['icon' => 'fa-server text-secondary',            'label' => 'Sync / Disconnect on MikroTik'],
            'technician.activate_client'   => ['icon' => 'fa-toggle-on text-success',           'label' => 'Activate / Deactivate Client'],
            'technician.collection'        => ['icon' => 'fa-money-bill-wave text-success',     'label' => 'Collect Payments (Monthly Bill)'],
            'technician.pay_installation'  => ['icon' => 'fa-tools text-warning',              'label' => 'Pay Installation Charge'],
            'technician.extend_deadline'   => ['icon' => 'fa-calendar-plus text-dark',         'label' => 'Extend Payment Deadline'],
            'technician.send_sms'          => ['icon' => 'fa-sms text-primary',                'label' => 'Send SMS to Client'],
            'technician.generate_bill'     => ['icon' => 'fa-file-invoice text-secondary',     'label' => 'Generate Bill'],
            'technician.new_connection'    => ['icon' => 'fa-plug text-success',               'label' => 'New Connection Requests'],
            'technician.due_report'        => ['icon' => 'fa-exclamation-triangle text-danger','label' => 'Due Report'],
            'technician.tickets'           => ['icon' => 'fa-ticket-alt text-muted',           'label' => 'Support Tickets'],
        ];

        // Auto-create any missing permissions so they appear in the settings UI
        $allLabelKeys = array_keys($technicianActionLabels);
        foreach ($allLabelKeys as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $adminRoleIds = $roles->filter(fn($r) => $this->isAdminRole($r->name))->pluck('id')->toArray();

        return view('admin.settings.role-permissions', compact(
            'roles', 'rolePermissions', 'adminRoleIds',
            'resellerPermissions', 'technicianPermissions',
            'resellerActionLabels', 'technicianActionLabels'
        ));
    }

    public function update(Request $request)
    {
        $this->authorizeSuperAdmin();

        $permissions = $request->input('permissions', []);

        $roles = Role::whereNotIn('name', ['Super Admin', 'client'])->get();

        $syncedUserCount = 0;

        foreach ($roles as $role) {
            $rolePerms = $permissions[$role->id] ?? [];
            $grantedPermissions = [];
            foreach ($rolePerms as $permName => $value) {
                if ($value) {
                    $grantedPermissions[] = $permName;
                }
            }
            $role->syncPermissions($grantedPermissions);

            // Auto-sync all existing users of this role so user-level overrides
            // don't shadow the role-level toggle changes
            if (in_array($role->name, ['Field Technician', 'Reseller'])) {
                $roleUsers = User::whereHas('roles', fn($q) => $q->where('roles.id', $role->id))->get();
                foreach ($roleUsers as $roleUser) {
                    $roleUser->syncPermissions($grantedPermissions);
                    $syncedUserCount++;
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $msg = 'Permissions saved and applied to all existing technicians/resellers.';
        if ($syncedUserCount > 0) {
            $msg .= " ({$syncedUserCount} user(s) updated)";
        }

        return redirect()->route('admin.settings.role-permissions')
            ->with('success', $msg);
    }

    public function applyToAll(Request $request)
    {
        $this->authorizeSuperAdmin();

        $roleName = $request->input('role');
        if (!$roleName || $this->isAdminRole($roleName)) {
            return back()->with('error', 'Invalid role for this action.');
        }

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return back()->with('error', 'Role not found.');
        }

        $permNames = $role->permissions->pluck('name')->toArray();
        $userType = strtolower($roleName);
        $users = User::where('user_type', $userType)->get();

        foreach ($users as $user) {
            $user->syncPermissions($permNames);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success',
            'Permissions applied to all ' . $users->count() . ' existing ' . ucfirst($roleName) . '(s). Individual customizations have been overridden.'
        );
    }

    public function isAdminRole(string $roleName): bool
    {
        return in_array(strtolower($roleName), $this->adminRoleNames);
    }
}
