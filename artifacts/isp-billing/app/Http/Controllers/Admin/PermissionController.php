<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuPermission;
use App\Models\UserMenuPermission;
use App\Services\MenuPermissionService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionController extends Controller
{
    private static array $adminRoles = ['Super Admin', 'Administrator', 'Admin Support'];

    private static array $roleOrder = [
        'Administrator', 'Admin Support', 'Assistant Manager', 'Branch Manager', 'Supervisor',
        'Field Technician', 'Billing Officer', 'Collection Officer', 'Network Engineer', 'Support Executive',
        'Card Agent', 'Reseller', 'Sub Reseller',
    ];

    public function index()
    {
        $orderMap = array_flip(self::$roleOrder);

        $roles = Role::whereNotIn('name', ['Super Admin', 'client'])
            ->get()
            ->sortBy(fn($r) => $orderMap[$r->name] ?? 99)
            ->values()
            ->map(function ($role) {
                $defaults = MenuPermissionService::getRoleDefaults($role->name);
                $role->is_wildcard = ($defaults === '*');
                $role->employee_count = \DB::table('model_has_roles')
                    ->where('role_id', $role->id)
                    ->count();
                return $role;
            });

        return view('admin.permissions.index', compact('roles'));
    }

    public function getRoleData($roleId)
    {
        $role = Role::findOrFail($roleId);
        $menus = config('menu');
        $pagePerms = config('page_permissions');

        $codeMenuMap    = MenuPermissionService::getDefaultPermMap($role->name);
        $codeActionMap  = MenuPermissionService::getActionDefaults($role->name);
        $isWildcard     = ($codeMenuMap === '*') || ($codeActionMap === '*');

        $dbMenuPerms = MenuPermission::withoutGlobalScopes()->where('role_id', $roleId)
            ->whereNull('action_key')
            ->get()
            ->keyBy(fn($p) => $p->submenu_key ? $p->menu_key . '.' . $p->submenu_key : $p->menu_key);

        $dbActionPerms = MenuPermission::withoutGlobalScopes()->where('role_id', $roleId)
            ->whereNotNull('action_key')
            ->get()
            ->keyBy(fn($p) => $p->menu_key . '.' . $p->action_key);

        $menuData = [];
        foreach ($menus as $menuKey => $menu) {
            $codeDefault = $isWildcard ? true : (is_array($codeMenuMap) ? ($codeMenuMap[$menuKey] ?? false) : false);
            $dbRow       = $dbMenuPerms->get($menuKey);
            // DB is authoritative when a row exists; fall back to code defaults only if absent
            $dbValue     = $dbRow ? $dbRow->allowed : null;
            $effective   = $isWildcard ? true : ($dbValue !== null ? (bool)$dbValue : $codeDefault);

            $submenus = [];
            foreach (($menu['submenus'] ?? []) as $subKey => $sub) {
                $fullKey    = $menuKey . '.' . $subKey;
                $subDefault = $isWildcard ? true : (is_array($codeMenuMap) ? ($codeMenuMap[$fullKey] ?? $codeDefault) : false);
                $dbSub      = $dbMenuPerms->get($fullKey);
                $subDbVal   = $dbSub ? $dbSub->allowed : null;
                $subEff     = $isWildcard ? true : ($subDbVal !== null ? (bool)$subDbVal : $subDefault);

                $submenus[$subKey] = [
                    'label'    => $sub['label'] ?? $subKey,
                    'allowed'  => $subEff,
                    'default'  => $subDefault,
                ];
            }

            $menuData[$menuKey] = [
                'label'    => $menu['label'] ?? $menuKey,
                'icon'     => $menu['icon'] ?? null,
                'allowed'  => $effective,
                'default'  => $codeDefault,
                'submenus' => $submenus,
            ];
        }

        $actionData = [];
        foreach ($pagePerms as $pageKey => $page) {
            $actionData[$pageKey] = [
                'label'   => $page['label'] ?? ucwords(str_replace('_', ' ', $pageKey)),
                'actions' => [],
            ];
            foreach ($page['actions'] as $actionKey => $actionLabel) {
                $fullKey       = $pageKey . '.' . $actionKey;
                $actionDefault = $isWildcard ? true : (is_array($codeActionMap) ? ($codeActionMap[$fullKey] ?? false) : false);
                $dbAction      = $dbActionPerms->get($fullKey);
                $actionDbVal   = $dbAction ? $dbAction->allowed : null;
                $actionEff     = $isWildcard ? true : ($actionDbVal !== null ? (bool)$actionDbVal : $actionDefault);

                $actionData[$pageKey]['actions'][$actionKey] = [
                    'label'   => is_string($actionLabel) ? $actionLabel : ($actionLabel['label'] ?? ucwords(str_replace('_', ' ', $actionKey))),
                    'allowed' => $actionEff,
                    'default' => $actionDefault,
                ];
            }
        }

        return response()->json([
            'role'        => [
                'id'         => $role->id,
                'name'       => $role->name,
                'is_wildcard'=> $isWildcard,
            ],
            'menus'       => $menuData,
            'actions'     => $actionData,
            'has_db_data' => $dbMenuPerms->isNotEmpty() || $dbActionPerms->isNotEmpty(),
        ]);
    }

    public function saveRoleMenus(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        if (MenuPermissionService::getRoleDefaults($role->name) === '*') {
            return response()->json(['success' => false, 'message' => 'Wildcard roles cannot be modified.']);
        }

        $menus      = config('menu');
        $menuInput  = $request->input('menus', []);

        foreach ($menus as $menuKey => $menu) {
            $allowed = isset($menuInput[$menuKey]) ? (bool)$menuInput[$menuKey] : false;
            MenuPermission::withoutGlobalScopes()->updateOrCreate(
                ['role_id' => $roleId, 'menu_key' => $menuKey, 'submenu_key' => null, 'action_key' => null],
                ['allowed' => $allowed]
            );

            foreach (($menu['submenus'] ?? []) as $subKey => $sub) {
                $fullKey    = $menuKey . '.' . $subKey;
                $subAllowed = isset($menuInput[$fullKey]) ? (bool)$menuInput[$fullKey] : false;
                MenuPermission::withoutGlobalScopes()->updateOrCreate(
                    ['role_id' => $roleId, 'menu_key' => $menuKey, 'submenu_key' => $subKey, 'action_key' => null],
                    ['allowed' => $subAllowed]
                );
            }
        }

        MenuPermissionService::clearCache($roleId);
        return response()->json(['success' => true, 'message' => 'Menu permissions saved!']);
    }

    public function saveRoleActions(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        if (MenuPermissionService::getRoleDefaults($role->name) === '*') {
            return response()->json(['success' => false, 'message' => 'Wildcard roles cannot be modified.']);
        }

        $pagePerms    = config('page_permissions');
        $actionInput  = $request->input('actions', []);

        foreach ($pagePerms as $pageKey => $page) {
            foreach ($page['actions'] as $actionKey => $actionLabel) {
                $fullKey = $pageKey . '.' . $actionKey;
                $allowed = isset($actionInput[$fullKey]) ? (bool)$actionInput[$fullKey] : false;
                MenuPermission::withoutGlobalScopes()->updateOrCreate(
                    ['role_id' => $roleId, 'menu_key' => $pageKey, 'submenu_key' => null, 'action_key' => $actionKey],
                    ['allowed' => $allowed]
                );
            }
        }

        MenuPermissionService::clearCache($roleId);
        return response()->json(['success' => true, 'message' => 'Action permissions saved!']);
    }

    public function resetRole($roleId)
    {
        $role = Role::findOrFail($roleId);
        MenuPermission::withoutGlobalScopes()->where('role_id', $roleId)->delete();
        MenuPermissionService::clearCache($roleId);

        return response()->json([
            'success' => true,
            'message' => "Role '{$role->name}' permissions reset to code defaults.",
        ]);
    }

    public function getUserData($userId)
    {
        $authUser = auth()->user();
        $user = User::findOrFail($userId);
        if (!$authUser->hasRole('Super Admin') && $authUser->company_id !== $user->company_id) {
            abort(403, 'Cannot access permissions for users in other companies.');
        }

        $role = $user->roles->first();

        if (!$role) {
            return response()->json(['error' => 'No role assigned'], 422);
        }

        $menus     = config('menu');
        $pagePerms = config('page_permissions');

        $codeMenuMap   = MenuPermissionService::getDefaultPermMap($role->name);
        $codeActionMap = MenuPermissionService::getActionDefaults($role->name);
        $isWildcard    = ($codeMenuMap === '*');

        $dbMenuPerms = MenuPermission::withoutGlobalScopes()->where('role_id', $role->id)
            ->whereNull('action_key')
            ->get()
            ->keyBy(fn($p) => $p->submenu_key ? $p->menu_key . '.' . $p->submenu_key : $p->menu_key);

        $dbActionPerms = MenuPermission::withoutGlobalScopes()->where('role_id', $role->id)
            ->whereNotNull('action_key')
            ->get()
            ->keyBy(fn($p) => $p->menu_key . '.' . $p->action_key);

        $userMenuOverrides   = UserMenuPermission::withoutGlobalScopes()->where('user_id', $userId)->whereNull('action_key')
            ->get()
            ->keyBy(fn($o) => $o->submenu_key ? $o->menu_key . '.' . $o->submenu_key : $o->menu_key);

        $userActionOverrides = UserMenuPermission::withoutGlobalScopes()->where('user_id', $userId)->whereNotNull('action_key')
            ->get()
            ->keyBy(fn($o) => $o->menu_key . '.' . $o->action_key);

        $menuData = [];
        foreach ($menus as $menuKey => $menu) {
            $codeDefault  = $isWildcard ? true : (is_array($codeMenuMap) ? ($codeMenuMap[$menuKey] ?? false) : false);
            $dbRow        = $dbMenuPerms->get($menuKey);
            // DB is authoritative when row exists; code defaults only as fallback when absent
            $roleAllowed  = $isWildcard ? true : ($dbRow ? (bool)$dbRow->allowed : $codeDefault);
            $userOverride = $userMenuOverrides->get($menuKey);

            $submenus = [];
            foreach (($menu['submenus'] ?? []) as $subKey => $sub) {
                $fullKey      = $menuKey . '.' . $subKey;
                $subDefault   = $isWildcard ? true : (is_array($codeMenuMap) ? ($codeMenuMap[$fullKey] ?? $codeDefault) : false);
                $dbSub        = $dbMenuPerms->get($fullKey);
                $subRole      = $isWildcard ? true : ($dbSub ? (bool)$dbSub->allowed : $subDefault);
                $subOverride  = $userMenuOverrides->get($fullKey);

                $submenus[$subKey] = [
                    'label'        => $sub['label'] ?? $subKey,
                    'role_allowed' => $subRole,
                    'override'     => $subOverride ? $subOverride->override : null,
                    'effective'    => $subOverride
                        ? ($subOverride->override === 'grant')
                        : $subRole,
                ];
            }

            $menuData[$menuKey] = [
                'label'        => $menu['label'] ?? $menuKey,
                'icon'         => $menu['icon'] ?? null,
                'role_allowed' => $roleAllowed,
                'override'     => $userOverride ? $userOverride->override : null,
                'effective'    => $userOverride ? ($userOverride->override === 'grant') : $roleAllowed,
                'submenus'     => $submenus,
            ];
        }

        $actionData = [];
        foreach ($pagePerms as $pageKey => $page) {
            $actionData[$pageKey] = ['label' => $page['label'] ?? ucwords(str_replace('_', ' ', $pageKey)), 'actions' => []];
            foreach ($page['actions'] as $actionKey => $actionLabel) {
                $fullKey       = $pageKey . '.' . $actionKey;
                $actionDefault = $isWildcard ? true : (is_array($codeActionMap) ? ($codeActionMap[$fullKey] ?? false) : false);
                $dbAction      = $dbActionPerms->get($fullKey);
                $roleAction    = $isWildcard ? true : ($dbAction ? (bool)$dbAction->allowed : $actionDefault);
                $userAct       = $userActionOverrides->get($fullKey);

                $actionData[$pageKey]['actions'][$actionKey] = [
                    'label'        => is_string($actionLabel) ? $actionLabel : ($actionLabel['label'] ?? ucwords(str_replace('_', ' ', $actionKey))),
                    'role_allowed' => $roleAction,
                    'override'     => $userAct ? $userAct->override : null,
                    'effective'    => $userAct ? ($userAct->override === 'grant') : $roleAction,
                ];
            }
        }

        return response()->json([
            'user'    => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $role->name,
            ],
            'menus'   => $menuData,
            'actions' => $actionData,
        ]);
    }

    public function saveUserOverrides(Request $request, $userId)
    {
        $authUser = auth()->user();
        $user = User::findOrFail($userId);
        if (!$authUser->hasRole('Super Admin') && $authUser->company_id !== $user->company_id) {
            abort(403, 'Cannot modify permissions for users in other companies.');
        }

        $menus     = config('menu');
        $pagePerms = config('page_permissions');

        $menuOverrides   = $request->input('menus', []);
        $actionOverrides = $request->input('actions', []);

        UserMenuPermission::withoutGlobalScopes()->where('user_id', $userId)->whereNull('action_key')->delete();
        UserMenuPermission::withoutGlobalScopes()->where('user_id', $userId)->whereNotNull('action_key')->delete();

        foreach ($menus as $menuKey => $menu) {
            $this->saveMenuOverride($userId, $menuKey, null, $menuOverrides[$menuKey] ?? null);
            foreach (($menu['submenus'] ?? []) as $subKey => $sub) {
                $fullKey = $menuKey . '.' . $subKey;
                $this->saveMenuOverride($userId, $menuKey, $subKey, $menuOverrides[$fullKey] ?? null);
            }
        }

        foreach ($pagePerms as $pageKey => $page) {
            foreach ($page['actions'] as $actionKey => $actionLabel) {
                $fullKey  = $pageKey . '.' . $actionKey;
                $override = $actionOverrides[$fullKey] ?? null;
                if ($override === 'grant' || $override === 'revoke') {
                    UserMenuPermission::create([
                        'user_id'     => $userId,
                        'menu_key'    => $pageKey,
                        'submenu_key' => null,
                        'action_key'  => $actionKey,
                        'override'    => $override,
                    ]);
                }
            }
        }

        MenuPermissionService::clearUserCache($userId);

        return response()->json(['success' => true, 'message' => 'User permissions saved!']);
    }

    public function clearUserOverrides($userId)
    {
        $authUser = auth()->user();
        $user = User::findOrFail($userId);
        if (!$authUser->hasRole('Super Admin') && $authUser->company_id !== $user->company_id) {
            abort(403, 'Cannot modify permissions for users in other companies.');
        }

        UserMenuPermission::withoutGlobalScopes()->where('user_id', $userId)->delete();
        MenuPermissionService::clearUserCache($userId);
        return response()->json(['success' => true, 'message' => 'User overrides cleared.']);
    }

    private function saveMenuOverride(int $userId, string $menuKey, ?string $subKey, ?string $override): void
    {
        if ($override !== 'grant' && $override !== 'revoke') {
            return;
        }
        UserMenuPermission::create([
            'user_id'     => $userId,
            'menu_key'    => $menuKey,
            'submenu_key' => $subKey,
            'action_key'  => null,
            'override'    => $override,
        ]);
    }
}
