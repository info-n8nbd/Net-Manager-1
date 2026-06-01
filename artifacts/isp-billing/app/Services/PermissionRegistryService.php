<?php

namespace App\Services;

use App\Models\SystemPermission;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionRegistryService
{
    const CACHE_KEY = 'system_permissions_registry';
    const CACHE_TTL = 3600;

    protected array $menus = [];
    protected array $actions = [];

    public function __construct()
    {
        $this->registerAllMenus();
        $this->registerAllActions();
    }

    protected function registerAllMenus(): void
    {
        $this->menus = [
            'dashboard' => ['label' => 'Dashboard', 'parent' => null, 'order' => 1],
            
            'clients' => ['label' => 'Clients', 'parent' => null, 'order' => 10],
            'clients.list' => ['label' => 'Client List', 'parent' => 'clients', 'order' => 11],
            'clients.add' => ['label' => 'Add Client', 'parent' => 'clients', 'order' => 12],
            'clients.import' => ['label' => 'Import Clients', 'parent' => 'clients', 'order' => 13],
            'clients.export' => ['label' => 'Export Clients', 'parent' => 'clients', 'order' => 14],
            
            'billing' => ['label' => 'Billing', 'parent' => null, 'order' => 20],
            'billing.generate' => ['label' => 'Generate Bills', 'parent' => 'billing', 'order' => 21],
            'billing.list' => ['label' => 'Bill List', 'parent' => 'billing', 'order' => 22],
            'billing.payments' => ['label' => 'Payments', 'parent' => 'billing', 'order' => 23],
            'billing.accounting' => ['label' => 'Accounting', 'parent' => 'billing', 'order' => 24],
            
            'network' => ['label' => 'Network', 'parent' => null, 'order' => 30],
            'network.mikrotik' => ['label' => 'MikroTik', 'parent' => 'network', 'order' => 31],
            'network.olt' => ['label' => 'OLT Management', 'parent' => 'network', 'order' => 32],
            'network.pops' => ['label' => 'POPs', 'parent' => 'network', 'order' => 33],
            
            'hrm' => ['label' => 'HRM', 'parent' => null, 'order' => 40],
            'hrm.employees' => ['label' => 'Employees', 'parent' => 'hrm', 'order' => 41],
            'hrm.technicians' => ['label' => 'Technicians', 'parent' => 'hrm', 'order' => 42],
            'hrm.attendance' => ['label' => 'Attendance', 'parent' => 'hrm', 'order' => 43],
            
            'support' => ['label' => 'Support', 'parent' => null, 'order' => 50],
            'support.tickets' => ['label' => 'Tickets', 'parent' => 'support', 'order' => 51],
            'support.categories' => ['label' => 'Categories', 'parent' => 'support', 'order' => 52],
            
            'reseller' => ['label' => 'Reseller', 'parent' => null, 'order' => 60],
            'reseller.list' => ['label' => 'Reseller List', 'parent' => 'reseller', 'order' => 61],
            'reseller.commissions' => ['label' => 'Commissions', 'parent' => 'reseller', 'order' => 62],
            
            'reports' => ['label' => 'Reports', 'parent' => null, 'order' => 70],
            'reports.collection' => ['label' => 'Collection Report', 'parent' => 'reports', 'order' => 71],
            'reports.due' => ['label' => 'Due Report', 'parent' => 'reports', 'order' => 72],
            'reports.technician' => ['label' => 'Technician Report', 'parent' => 'reports', 'order' => 73],
            'reports.zone' => ['label' => 'Zone Report', 'parent' => 'reports', 'order' => 74],
            
            'sms' => ['label' => 'SMS', 'parent' => null, 'order' => 80],
            'sms.templates' => ['label' => 'Templates', 'parent' => 'sms', 'order' => 81],
            'sms.send' => ['label' => 'Send SMS', 'parent' => 'sms', 'order' => 82],
            'sms.history' => ['label' => 'SMS History', 'parent' => 'sms', 'order' => 83],
            
            'settings' => ['label' => 'Settings', 'parent' => null, 'order' => 90],
            'settings.company' => ['label' => 'Company', 'parent' => 'settings', 'order' => 91],
            'settings.packages' => ['label' => 'Packages', 'parent' => 'settings', 'order' => 92],
            'settings.zones' => ['label' => 'Zones', 'parent' => 'settings', 'order' => 93],
            'settings.payment_methods' => ['label' => 'Payment Methods', 'parent' => 'settings', 'order' => 94],
            'settings.automation' => ['label' => 'Automation', 'parent' => 'settings', 'order' => 95],
            
            'access_control' => ['label' => 'Access Control', 'parent' => null, 'order' => 100],
            'access_control.users' => ['label' => 'Users', 'parent' => 'access_control', 'order' => 101],
            'access_control.roles' => ['label' => 'Roles', 'parent' => 'access_control', 'order' => 102],
            'access_control.permissions' => ['label' => 'Permissions', 'parent' => 'access_control', 'order' => 103],
            'access_control.audit' => ['label' => 'Audit Log', 'parent' => 'access_control', 'order' => 104],
            
            'overrides' => ['label' => 'Overrides', 'parent' => null, 'order' => 110],
            'overrides.technician' => ['label' => 'Technician Actions', 'parent' => 'overrides', 'order' => 111],
            'overrides.history' => ['label' => 'Override History', 'parent' => 'overrides', 'order' => 112],
            
            'events' => ['label' => 'Events & Holidays', 'parent' => null, 'order' => 115],
            'events.list' => ['label' => 'Events List', 'parent' => 'events', 'order' => 116],
            'events.holidays' => ['label' => 'Holidays', 'parent' => 'events', 'order' => 117],

            'tasks' => ['label' => 'Task Management', 'parent' => null, 'order' => 118],
            'tasks.all' => ['label' => 'All Tasks', 'parent' => 'tasks', 'order' => 119],
            'tasks.create' => ['label' => 'Create Task', 'parent' => 'tasks', 'order' => 120],
            'tasks.calendar' => ['label' => 'Task Calendar', 'parent' => 'tasks', 'order' => 121],

            'system' => ['label' => 'System', 'parent' => null, 'order' => 130],
            'system.health' => ['label' => 'System Health', 'parent' => 'system', 'order' => 131],
            'vps.monitor' => ['label' => 'VPS Monitor', 'parent' => 'settings', 'order' => 132],
            'app.info' => ['label' => 'App Info', 'parent' => 'settings', 'order' => 133],
            'system.logs' => ['label' => 'System Logs', 'parent' => 'system', 'order' => 132],
            'system.backup' => ['label' => 'Backup', 'parent' => 'system', 'order' => 133],
        ];
    }

    protected function registerAllActions(): void
    {
        $actionTypes = ['view', 'create', 'edit', 'delete', 'approve', 'override'];
        
        $entities = [
            'client', 'bill', 'payment', 'package', 'zone', 'subzone', 'pop',
            'technician', 'billman', 'employee', 'reseller', 'ticket',
            'sms_template', 'automation_rule', 'network_rule', 'cash_settlement',
            'user', 'role', 'permission', 'report', 'mikrotik_device', 'olt_device',
            'event', 'task'
        ];

        foreach ($entities as $entity) {
            foreach ($actionTypes as $action) {
                $this->actions["{$entity}.{$action}"] = [
                    'entity' => $entity,
                    'action' => $action,
                    'label' => ucfirst($action) . ' ' . str_replace('_', ' ', ucfirst($entity)),
                ];
            }
        }
    }

    public function getAllMenus(): array
    {
        return $this->menus;
    }

    public function getAllActions(): array
    {
        return $this->actions;
    }

    public function syncPermissionsToDatabase(): array
    {
        $created = 0;
        $existing = 0;

        foreach ($this->menus as $key => $menu) {
            $permission = SystemPermission::firstOrCreate(
                ['key' => "menu.{$key}"],
                [
                    'type' => 'menu',
                    'label' => $menu['label'],
                    'parent_key' => $menu['parent'] ? "menu.{$menu['parent']}" : null,
                    'order' => $menu['order'],
                    'is_active' => true,
                ]
            );
            $permission->wasRecentlyCreated ? $created++ : $existing++;
        }

        foreach ($this->actions as $key => $action) {
            $permission = SystemPermission::firstOrCreate(
                ['key' => "action.{$key}"],
                [
                    'type' => 'action',
                    'label' => $action['label'],
                    'entity' => $action['entity'],
                    'action' => $action['action'],
                    'is_active' => true,
                ]
            );
            $permission->wasRecentlyCreated ? $created++ : $existing++;
        }

        Cache::forget(self::CACHE_KEY);

        return ['created' => $created, 'existing' => $existing];
    }

    public function getPermissionsForRole(int $roleId): array
    {
        return Cache::remember("role_permissions_{$roleId}", self::CACHE_TTL, function () use ($roleId) {
            return RolePermission::where('role_id', $roleId)
                ->where('is_granted', true)
                ->pluck('permission_key')
                ->toArray();
        });
    }

    public function hasPermission(int $roleId, string $permissionKey): bool
    {
        $role = Role::find($roleId);
        
        if ($role && $role->name === 'Super Admin') {
            return true;
        }

        $permissions = $this->getPermissionsForRole($roleId);
        return in_array($permissionKey, $permissions);
    }

    public function userHasPermission(int $userId, string $permissionKey): bool
    {
        $user = \App\Models\User::find($userId);
        if (!$user) return false;

        foreach ($user->roles as $role) {
            if ($role->name === 'Super Admin') {
                return true;
            }
            if ($this->hasPermission($role->id, $permissionKey)) {
                return true;
            }
        }

        return false;
    }

    public function getMenusForUser(int $userId): array
    {
        $user = \App\Models\User::find($userId);
        if (!$user) return [];

        $isSuperAdmin = $user->hasRole('Super Admin');
        
        if ($isSuperAdmin) {
            return array_keys($this->menus);
        }

        $allowedMenus = [];
        foreach ($user->roles as $role) {
            $permissions = $this->getPermissionsForRole($role->id);
            foreach ($permissions as $perm) {
                if (str_starts_with($perm, 'menu.')) {
                    $allowedMenus[] = str_replace('menu.', '', $perm);
                }
            }
        }

        return array_unique($allowedMenus);
    }

    public function grantPermission(int $roleId, string $permissionKey, int $grantedBy, ?string $reason = null): bool
    {
        RolePermission::updateOrCreate(
            ['role_id' => $roleId, 'permission_key' => $permissionKey],
            [
                'is_granted' => true,
                'granted_by' => $grantedBy,
                'granted_at' => now(),
                'reason' => $reason,
            ]
        );

        Cache::forget("role_permissions_{$roleId}");
        return true;
    }

    public function revokePermission(int $roleId, string $permissionKey, int $revokedBy, ?string $reason = null): bool
    {
        RolePermission::updateOrCreate(
            ['role_id' => $roleId, 'permission_key' => $permissionKey],
            [
                'is_granted' => false,
                'revoked_by' => $revokedBy,
                'revoked_at' => now(),
                'reason' => $reason,
            ]
        );

        Cache::forget("role_permissions_{$roleId}");
        return true;
    }

    public function bulkUpdatePermissions(int $roleId, array $permissions, int $updatedBy): int
    {
        $count = 0;
        foreach ($permissions as $key => $granted) {
            if ($granted) {
                $this->grantPermission($roleId, $key, $updatedBy);
            } else {
                $this->revokePermission($roleId, $key, $updatedBy);
            }
            $count++;
        }
        return $count;
    }

    public function getFullPermissionMatrix(): array
    {
        $roles = Role::all();
        $permissions = SystemPermission::where('is_active', true)->orderBy('order')->get();

        $matrix = [];
        foreach ($roles as $role) {
            $rolePermissions = $this->getPermissionsForRole($role->id);
            $matrix[$role->id] = [
                'role' => $role,
                'permissions' => $rolePermissions,
                'is_super_admin' => $role->name === 'Super Admin',
            ];
        }

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'matrix' => $matrix,
        ];
    }
}
