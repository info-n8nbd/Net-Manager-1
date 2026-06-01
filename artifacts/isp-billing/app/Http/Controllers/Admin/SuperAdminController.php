<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemPermission;
use App\Models\RolePermission;
use App\Models\PermissionAuditLog;
use App\Services\PermissionRegistryService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminController extends Controller
{
    protected PermissionRegistryService $permissionService;

    public function __construct(PermissionRegistryService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    protected function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            abort(403, 'Only Super Admin can access this panel');
        }
    }

    public function accessPanel(Request $request)
    {
        $this->ensureSuperAdmin();

        $roles = Role::orderBy('name')->get();
        $menuCount = SystemPermission::menus()->active()->count();
        $actionCount = SystemPermission::actions()->active()->count();
        
        $rolePermissionCounts = [];
        foreach ($roles as $role) {
            if ($role->name !== 'Super Admin') {
                $rolePermissionCounts[$role->id] = RolePermission::where('role_id', $role->id)->count();
            }
        }
        
        $permissionsByCategory = [];
        $permissions = SystemPermission::active()->orderBy('entity')->orderBy('order')->get();
        foreach ($permissions as $perm) {
            $category = $perm->entity ?? 'general';
            if (!isset($permissionsByCategory[$category])) {
                $permissionsByCategory[$category] = [];
            }
            $permissionsByCategory[$category][] = [
                'key' => $perm->key,
                'name' => $perm->label ?? $perm->key,
                'type' => $perm->type,
            ];
        }
        
        $recentAuditLogs = PermissionAuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'roles' => $roles,
                'menuCount' => $menuCount,
                'actionCount' => $actionCount,
                'permissionsByCategory' => $permissionsByCategory,
            ]);
        }

        return view('admin.super-admin.access-panel', compact(
            'roles', 'menuCount', 'actionCount', 'rolePermissionCounts', 
            'permissionsByCategory', 'recentAuditLogs'
        ));
    }

    public function syncPermissions()
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $result = $this->permissionService->syncPermissionsToDatabase();

        $this->logAudit('sync_permissions', null, null, $result);

        return response()->json([
            'success' => true,
            'message' => "Synced permissions: {$result['created']} created, {$result['existing']} existing",
            'created' => $result['created'],
            'existing' => $result['existing'],
        ]);
    }

    public function listRoles()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions->count(),
                'users_count' => $role->users()->count(),
                'is_super_admin' => $role->name === 'Super Admin',
            ];
        });

        return response()->json([
            'success' => true,
            'roles' => $roles,
        ]);
    }

    public function createRole(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        if ($request->permissions) {
            foreach ($request->permissions as $permKey => $granted) {
                if ($granted) {
                    $this->permissionService->grantPermission($role->id, $permKey, auth()->id());
                }
            }
        }

        $this->logAudit('create_role', 'role', $role->id, ['name' => $role->name]);

        return response()->json([
            'success' => true,
            'role' => $role,
        ]);
    }

    public function editRole(Request $request, int $roleId)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $role = Role::findOrFail($roleId);

        if ($role->name === 'Super Admin') {
            return response()->json(['success' => false, 'message' => 'Cannot edit Super Admin role'], 400);
        }

        $request->validate([
            'name' => 'nullable|string|unique:roles,name,' . $roleId,
        ]);

        $oldName = $role->name;
        if ($request->name) {
            $role->update(['name' => $request->name]);
        }

        $this->logAudit('edit_role', 'role', $role->id, [
            'old_name' => $oldName,
            'new_name' => $role->name,
        ]);

        return response()->json([
            'success' => true,
            'role' => $role,
        ]);
    }

    public function deleteRole(Request $request, int $roleId)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $role = Role::findOrFail($roleId);

        if (in_array($role->name, ['Super Admin', 'Admin', 'Technician', 'Billman'])) {
            return response()->json(['success' => false, 'message' => 'Cannot delete system roles'], 400);
        }

        $roleName = $role->name;
        $role->delete();

        $this->logAudit('delete_role', 'role', $roleId, ['name' => $roleName]);

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    public function getRolePermissions(int $roleId)
    {
        $role = Role::findOrFail($roleId);
        
        if ($role->name === 'Super Admin') {
            return response()->json([
                'success' => true,
                'role' => $role,
                'is_super_admin' => true,
                'permissions' => [],
            ]);
        }
        
        $permissions = RolePermission::where('role_id', $roleId)
            ->where('is_granted', true)
            ->pluck('permission_key')
            ->toArray();

        return response()->json([
            'success' => true,
            'role' => $role,
            'is_super_admin' => false,
            'permissions' => $permissions,
        ]);
    }

    public function updateRolePermissions(Request $request, int $roleId)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $role = Role::findOrFail($roleId);

        if ($role->name === 'Super Admin') {
            return response()->json(['success' => false, 'message' => 'Cannot modify Super Admin permissions'], 400);
        }

        $request->validate([
            'permissions' => 'required|array',
            'reason' => 'nullable|string',
        ]);

        $oldPermissions = RolePermission::where('role_id', $roleId)
            ->where('is_granted', true)
            ->pluck('permission_key')
            ->toArray();

        $count = $this->permissionService->bulkUpdatePermissions(
            $roleId,
            $request->permissions,
            auth()->id()
        );

        $newPermissions = array_keys(array_filter($request->permissions));

        $this->logAudit('update_permissions', 'role', $roleId, [
            'old_permissions' => $oldPermissions,
            'new_permissions' => $newPermissions,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Updated {$count} permissions",
        ]);
    }

    public function grantSinglePermission(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_key' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $role = Role::findOrFail($request->role_id);
        if ($role->name === 'Super Admin') {
            return response()->json(['success' => false, 'message' => 'Cannot modify Super Admin'], 400);
        }

        $this->permissionService->grantPermission(
            $request->role_id,
            $request->permission_key,
            auth()->id(),
            $request->reason
        );

        $this->logAudit('grant_permission', 'permission', null, [
            'role_id' => $request->role_id,
            'permission_key' => $request->permission_key,
            'reason' => $request->reason,
        ]);

        return response()->json(['success' => true, 'message' => 'Permission granted']);
    }

    public function revokeSinglePermission(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_key' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $role = Role::findOrFail($request->role_id);
        if ($role->name === 'Super Admin') {
            return response()->json(['success' => false, 'message' => 'Cannot modify Super Admin'], 400);
        }

        $this->permissionService->revokePermission(
            $request->role_id,
            $request->permission_key,
            auth()->id(),
            $request->reason
        );

        $this->logAudit('revoke_permission', 'permission', null, [
            'role_id' => $request->role_id,
            'permission_key' => $request->permission_key,
            'reason' => $request->reason,
        ]);

        return response()->json(['success' => true, 'message' => 'Permission revoked']);
    }

    public function auditLog(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Only Super Admin can access this page');
        }

        $query = PermissionAuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('permission_key', 'like', "%{$request->search}%")
                  ->orWhere('role_name', 'like', "%{$request->search}%")
                  ->orWhere('reason', 'like', "%{$request->search}%");
            });
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);
        }

        return view('admin.super-admin.audit-log', compact('logs'));
    }

    public function getUserPermissions(int $userId)
    {
        $authUser = auth()->user();
        if (!$authUser || !$authUser->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = \App\Models\User::with('roles')->findOrFail($userId);
        $isSuperAdmin = $user->hasRole('Super Admin');
        $role = $user->roles->first();

        $menuConfig = config('menu');
        $menuItems = [];
        $allowedKeys = [];

        $company = $user->company;
        $companyAllowedMenus = $company ? ($company->allowed_menus ?? []) : [];
        $hasCompanyRestriction = !empty($companyAllowedMenus);

        if ($role) {
            $rolePerms = \App\Models\MenuPermission::where('role_id', $role->id)->get();
            $permMap = [];
            foreach ($rolePerms as $perm) {
                $key = $perm->submenu_key ? $perm->menu_key . '.' . $perm->submenu_key : $perm->menu_key;
                $permMap[$key] = $perm->allowed;
            }

            $hasExplicitPerms = !empty($permMap);

            if (!$hasExplicitPerms) {
                $defaults = \App\Services\MenuPermissionService::getRoleDefaults($role->name);
                if ($defaults === '*') {
                    $permMap = [];
                    foreach ($menuConfig as $menuKey => $menu) {
                        $permMap[$menuKey] = true;
                        if (!empty($menu['submenus'])) {
                            foreach ($menu['submenus'] as $subKey => $sub) {
                                $permMap[$menuKey . '.' . $subKey] = true;
                            }
                        }
                    }
                } else {
                    $permMap = \App\Services\MenuPermissionService::getDefaultPermMap($role->name);
                }
            }

            foreach ($permMap as $key => $allowed) {
                if ($allowed) {
                    if ($hasCompanyRestriction && !in_array($key, $companyAllowedMenus)) {
                        continue;
                    }
                    $allowedKeys[] = $key;
                }
            }
        }

        foreach ($menuConfig as $menuKey => $menu) {
            if (!empty($menu['submenus'])) {
                foreach ($menu['submenus'] as $subKey => $sub) {
                    $fullKey = $menuKey . '.' . $subKey;
                    if ($hasCompanyRestriction && !in_array($fullKey, $companyAllowedMenus)) {
                        continue;
                    }
                    $menuItems[] = [
                        'key' => $fullKey,
                        'label' => $sub['label'],
                        'entity' => $menu['label'],
                        'menu_key' => $menuKey,
                        'submenu_key' => $subKey,
                    ];
                }
            } else {
                if ($hasCompanyRestriction && !in_array($menuKey, $companyAllowedMenus)) {
                    continue;
                }
                $menuItems[] = [
                    'key' => $menuKey,
                    'label' => $menu['label'],
                    'entity' => $menu['label'],
                    'menu_key' => $menuKey,
                    'submenu_key' => null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'user' => $user,
            'is_super_admin' => $isSuperAdmin,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $menuItems,
            'user_permissions' => $allowedKeys,
        ]);
    }

    public function assignRoleToUser(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        if ($role->name === 'Super Admin') {
            if (!\App\Services\SuperAdminGuardService::canAssignRole(auth()->user(), 'Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Super Admin can assign the Super Admin role',
                ], 403);
            }
        }

        $user->assignRole($role);

        \App\Services\MenuPermissionService::clearUserCache($user->id);

        $this->logAudit('assign_role', 'user', $user->id, [
            'role_id' => $role->id,
            'role_name' => $role->name,
        ]);

        \App\Models\AuditLog::log('assign_role', 'user', $user->id, $user->name, null, [
            'role' => $role->name,
            'assigned_by' => auth()->user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Role '{$role->name}' assigned to user",
        ]);
    }

    public function removeRoleFromUser(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        if ($role->name === 'Super Admin') {
            if (!\App\Services\SuperAdminGuardService::canRevokeRole(auth()->user(), $user, 'Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove Super Admin role. At least one Super Admin must exist.',
                ], 403);
            }
        }

        $user->removeRole($role);

        $this->logAudit('remove_role', 'user', $user->id, [
            'role_id' => $role->id,
            'role_name' => $role->name,
        ]);

        \App\Models\AuditLog::log('remove_role', 'user', $user->id, $user->name, [
            'role' => $role->name,
        ], null);

        return response()->json([
            'success' => true,
            'message' => "Role '{$role->name}' removed from user",
        ]);
    }

    protected function logAudit(string $action, ?string $entityType, $entityId, array $data): void
    {
        PermissionAuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'data' => $data,
            'ip_address' => request()->ip(),
        ]);
    }

    public function createEmployeeAccount(Request $request, $employeeId)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $employee = \App\Models\Employee::findOrFail($employeeId);

        if ($employee->user_id) {
            return response()->json(['success' => false, 'message' => 'Employee already has an account'], 400);
        }

        $userType = 'employee';
        if ($request->role_id) {
            $roleCheck = Role::find($request->role_id);
            if ($roleCheck) {
                $roleUserTypeMap = [
                    'field technician' => 'technician',
                    'billing officer' => 'billman',
                    'branch manager' => 'manager',
                    'reseller' => 'reseller',
                    'sub reseller' => 'sub_reseller',
                    'card agent' => 'card_distributor',
                    'administrator' => 'admin',
                    'client' => 'client',
                ];
                $userType = $roleUserTypeMap[strtolower($roleCheck->name)] ?? 'employee';
            }
        }

        $user = \App\Models\User::create([
            'name' => $employee->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $employee->phone,
            'user_type' => $userType,
            'is_active' => true,
            'company_id' => auth()->user()->company_id,
        ]);

        $employee->update(['user_id' => $user->id]);

        if ($request->role_id) {
            $role = Role::find($request->role_id);
            if ($role && $role->name !== 'Super Admin') {
                $user->assignRole($role);

                if (strtolower($role->name) === 'manager') {
                    $branch = \App\Models\Branch::firstOrCreate(
                        ['company_id' => $user->company_id ?? 1, 'code' => 'MAIN'],
                        ['name' => 'Main Branch', 'address' => 'Main Branch', 'is_active' => true]
                    );
                    \App\Models\ManagerAssignment::firstOrCreate(
                        ['user_id' => $user->id],
                        ['branch_id' => $branch->id, 'assigned_by' => auth()->id()]
                    );
                }
            }
        }

        $this->logAudit('create_employee_account', 'user', $user->id, [
            'employee_id' => $employee->id,
            'email' => $request->email,
            'role_id' => $request->role_id,
        ]);

        \App\Models\AuditLog::logCreate('user', $user->id, $user->name, [
            'email' => $user->email,
            'created_for_employee' => $employee->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Account created successfully', 'user_id' => $user->id]);
    }

    public function updateUserPermissions(Request $request, $userId)
    {
        $authUser = auth()->user();
        if (!$authUser || !$authUser->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user = \App\Models\User::findOrFail($userId);
        $permissions = $request->input('permissions', []);

        $role = $user->roles->first();
        if (!$role) {
            $role = Role::firstOrCreate(['name' => 'Custom_' . $user->id, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        if ($role->name === 'Super Admin') {
            return response()->json(['success' => false, 'message' => 'Cannot modify Super Admin permissions'], 403);
        }

        $company = $user->company;
        $companyAllowedMenus = $company ? ($company->allowed_menus ?? []) : [];
        $hasCompanyRestriction = !empty($companyAllowedMenus);

        if ($hasCompanyRestriction) {
            $permissions = array_intersect($permissions, $companyAllowedMenus);
        }

        \App\Models\MenuPermission::where('role_id', $role->id)->delete();

        $menuConfig = config('menu');
        foreach ($menuConfig as $menuKey => $menu) {
            $menuAllowed = in_array($menuKey, $permissions);

            if (!empty($menu['submenus'])) {
                $hasAnySub = false;
                foreach ($menu['submenus'] as $subKey => $sub) {
                    $fullKey = $menuKey . '.' . $subKey;
                    if ($hasCompanyRestriction && !in_array($fullKey, $companyAllowedMenus)) {
                        continue;
                    }
                    $subAllowed = in_array($fullKey, $permissions);
                    if ($subAllowed) $hasAnySub = true;
                    \App\Models\MenuPermission::create([
                        'role_id' => $role->id,
                        'menu_key' => $menuKey,
                        'submenu_key' => $subKey,
                        'allowed' => $subAllowed,
                    ]);
                }
                \App\Models\MenuPermission::create([
                    'role_id' => $role->id,
                    'menu_key' => $menuKey,
                    'submenu_key' => null,
                    'allowed' => $hasAnySub || $menuAllowed,
                ]);
            } else {
                if ($hasCompanyRestriction && !in_array($menuKey, $companyAllowedMenus)) {
                    continue;
                }
                \App\Models\MenuPermission::create([
                    'role_id' => $role->id,
                    'menu_key' => $menuKey,
                    'submenu_key' => null,
                    'allowed' => $menuAllowed,
                ]);
            }
        }

        \App\Services\MenuPermissionService::clearCache($role->id);

        $this->logAudit('update_user_permissions', 'user', $userId, [
            'permissions_count' => count($permissions),
        ]);

        return response()->json(['success' => true, 'message' => 'Permissions updated']);
    }

    public function assignRoleToUserById(Request $request, $userId)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $user = \App\Models\User::findOrFail($userId);

        $user->roles()->detach();

        if ($request->role_id) {
            $role = Role::findOrFail($request->role_id);
            
            if ($role->name === 'Super Admin') {
                if (!\App\Services\SuperAdminGuardService::canAssignRole(auth()->user(), 'Super Admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only Super Admin can assign Super Admin role',
                    ], 403);
                }
            }
            
            $user->assignRole($role);

            $roleUserTypeMap = [
                'field technician' => 'technician',
                'billing officer' => 'billman',
                'branch manager' => 'manager',
                'reseller' => 'reseller',
                'sub reseller' => 'sub_reseller',
                'card agent' => 'card_distributor',
                'administrator' => 'admin',
                'client' => 'client',
            ];
            $newUserType = $roleUserTypeMap[strtolower($role->name)] ?? 'employee';
            $user->update(['user_type' => $newUserType]);

            if (strtolower($role->name) === 'branch manager') {
                $branch = \App\Models\Branch::firstOrCreate(
                    ['company_id' => $user->company_id ?? 1, 'code' => 'MAIN'],
                    ['name' => 'Main Branch', 'address' => 'Main Branch', 'is_active' => true]
                );
                \App\Models\ManagerAssignment::firstOrCreate(
                    ['user_id' => $user->id],
                    ['branch_id' => $branch->id, 'assigned_by' => auth()->id()]
                );
            }

            \App\Services\MenuPermissionService::clearCache($role->id);

            $this->logAudit('assign_role_by_id', 'user', $userId, [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'user_type' => $newUserType,
            ]);
        }

        \App\Services\MenuPermissionService::clearCache();

        return response()->json(['success' => true, 'message' => 'Role updated successfully']);
    }

    public function integrityDashboard()
    {
        $this->ensureSuperAdmin();

        $integrityService = app(\App\Services\BillingIntegrityService::class);
        
        $lastCheck = $integrityService->getLastCheckStatus();
        $pendingIssues = $integrityService->getPendingIssues();
        
        $recentChecks = \App\Models\IntegrityCheckLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $healthStatus = $integrityService->runQuickHealthCheck();

        return view('admin.super-admin.integrity-dashboard', compact(
            'lastCheck', 'pendingIssues', 'recentChecks', 'healthStatus'
        ));
    }

    public function runIntegrityCheck(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $autoCorrect = $request->boolean('auto_correct', true);
        
        try {
            $integrityService = app(\App\Services\BillingIntegrityService::class);
            $result = $integrityService->runFullIntegrityCheck($autoCorrect);
            
            return response()->json([
                'success' => true,
                'message' => 'Integrity check completed',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Integrity check failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function integrityCheckHistory(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $logs = \App\Models\IntegrityCheckLog::with('runBy')
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'logs' => $logs]);
        }

        return view('admin.super-admin.integrity-history', compact('logs'));
    }

    public function integrityCheckDetails(int $logId)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $log = \App\Models\IntegrityCheckLog::findOrFail($logId);
        $details = json_decode($log->details, true);

        return response()->json([
            'success' => true,
            'log' => $log,
            'details' => $details,
        ]);
    }



    public function footerSettings()
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && $user->user_type !== 'super_admin')) {
            abort(403);
        }
        $footerText = \App\Models\SystemSetting::getGlobal('global_footer_text', '');
        return view('superadmin.footer-settings', compact('footerText'));
    }

    public function updateFooterSettings(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && $user->user_type !== 'super_admin')) {
            abort(403);
        }
        $request->validate(['footer_text' => 'required|string|max:500']);
        \App\Models\SystemSetting::setGlobal('global_footer_text', $request->footer_text);
        return back()->with('success', 'Footer successfully updated! All company panels will now show this footer.');
    }
}
