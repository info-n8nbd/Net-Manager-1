<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuPermission;
use App\Services\MenuPermissionService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class MenuPermissionController extends Controller
{
    public function index()
    {
        $menus = config('menu');
        $pagePermissions = config('page_permissions');
        $roles = Role::where('name', '!=', 'Super Admin')->orderBy('name')->get();

        $permissions = MenuPermission::whereNull('action_key')->get()->groupBy('role_id');
        $actionPermissions = MenuPermission::whereNotNull('action_key')->get()->groupBy('role_id');

        $permMap = [];
        foreach ($permissions as $roleId => $perms) {
            foreach ($perms as $perm) {
                $key = $perm->submenu_key
                    ? $perm->menu_key . '.' . $perm->submenu_key
                    : $perm->menu_key;
                $permMap[$roleId][$key] = $perm->allowed;
            }
        }

        $actionPermMap = [];
        foreach ($actionPermissions as $roleId => $perms) {
            foreach ($perms as $perm) {
                $key = $perm->menu_key . '.' . $perm->action_key;
                $actionPermMap[$roleId][$key] = $perm->allowed;
            }
        }

        // Build code-level defaults for every role as fallback
        $codeDefaultMap = [];
        foreach ($roles as $role) {
            $codeDefaultMap[$role->id] = MenuPermissionService::getDefaultPermMap($role->name);
        }

        $activeTab = request('tab', 'menu');

        return view('admin.menu-permissions.index', compact(
            'menus', 'pagePermissions', 'roles', 'permMap', 'actionPermMap', 'activeTab', 'codeDefaultMap'
        ));
    }

    public function update(Request $request)
    {
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        $menus = config('menu');
        $permsInput = $request->input('permissions', []);

        foreach ($roles as $role) {
            // Never overwrite '*' (wildcard) roles via this page — they always have full access
            $roleDefaults = MenuPermissionService::getRoleDefaults($role->name);
            if ($roleDefaults === '*') {
                continue;
            }

            foreach ($menus as $menuKey => $menu) {
                $menuAllowed = isset($permsInput[$role->id][$menuKey]);

                MenuPermission::updateOrCreate(
                    ['role_id' => $role->id, 'menu_key' => $menuKey, 'submenu_key' => null, 'action_key' => null],
                    ['allowed' => $menuAllowed]
                );

                if (!empty($menu['submenus'])) {
                    foreach ($menu['submenus'] as $subKey => $sub) {
                        $subAllowed = isset($permsInput[$role->id][$menuKey . '.' . $subKey]);

                        MenuPermission::updateOrCreate(
                            ['role_id' => $role->id, 'menu_key' => $menuKey, 'submenu_key' => $subKey, 'action_key' => null],
                            ['allowed' => $subAllowed]
                        );
                    }
                }
            }

            MenuPermissionService::clearCache($role->id);
        }

        return redirect()->route('admin.menu-permissions.index')
            ->with('success', 'Menu permissions updated successfully!');
    }

    public function updateActions(Request $request)
    {
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        $pagePermissions = config('page_permissions');
        $permsInput = $request->input('action_permissions', []);

        foreach ($roles as $role) {
            foreach ($pagePermissions as $pageKey => $page) {
                foreach ($page['actions'] as $actionKey => $actionLabel) {
                    $allowed = isset($permsInput[$role->id][$pageKey . '.' . $actionKey]);

                    MenuPermission::updateOrCreate(
                        ['role_id' => $role->id, 'menu_key' => $pageKey, 'submenu_key' => null, 'action_key' => $actionKey],
                        ['allowed' => $allowed]
                    );
                }
            }

            MenuPermissionService::clearCache($role->id);
        }

        return redirect()->route('admin.menu-permissions.index', ['tab' => 'actions'])
            ->with('success', 'Page action permissions updated successfully!');
    }
}
