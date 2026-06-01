<?php

namespace App\Services;

use App\Models\MenuPermission;
use App\Models\UserMenuPermission;
use Illuminate\Support\Facades\Cache;

class MenuPermissionService
{
    public static function canAccessMenu($user, string $menuKey, ?string $submenuKey = null): bool
    {
        if (!$user) return false;

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Check wildcard roles (Administrator, Admin Support) BEFORE company menu restrictions.
        // Wildcard roles have unconditional full access and must not be blocked by allowed_menus.
        // User-level overrides (grant/revoke) still apply even for wildcard roles.
        $userOverrides = self::getUserOverrides($user->id);
        $overrideKey = $submenuKey ? $menuKey . '.' . $submenuKey : $menuKey;

        $roles = $user->roles;
        $isWildcard = false;
        foreach ($roles as $role) {
            if (self::getRoleDefaults($role->name) === '*') {
                $isWildcard = true;
                break;
            }
        }

        if ($isWildcard) {
            // Wildcard roles still respect company allowed_menus when explicitly set (non-empty).
            // An empty allowed_menus means "no restriction" (all allowed).
            $fullKeyCheck = $submenuKey ? $menuKey . '.' . $submenuKey : $menuKey;
            $company = $user->company ?? null;
            $companyMenus = $company ? ($company->allowed_menus ?? []) : [];
            if (!empty($companyMenus) && !self::companyAllowsMenu($user, $fullKeyCheck)) {
                return false;
            }

            if (isset($userOverrides[$overrideKey])) {
                if ($userOverrides[$overrideKey] === 'revoke') return false;
                if ($userOverrides[$overrideKey] === 'grant') return true;
            }
            if ($submenuKey && isset($userOverrides[$menuKey])) {
                if ($userOverrides[$menuKey] === 'revoke') return false;
            }
            return true;
        }

        // Non-wildcard roles: apply company menu restrictions
        $fullKey = $submenuKey ? $menuKey . '.' . $submenuKey : $menuKey;
        if (!self::companyAllowsMenu($user, $fullKey)) {
            return false;
        }

        // Check exact key override first, then parent key (so parent revoke cascades to all submenus)
        if (isset($userOverrides[$overrideKey])) {
            if ($userOverrides[$overrideKey] === 'grant') return true;
            if ($userOverrides[$overrideKey] === 'revoke') return false;
        }
        // If submenu key specified but no exact override found, check parent menu key cascade
        if ($submenuKey && isset($userOverrides[$menuKey])) {
            if ($userOverrides[$menuKey] === 'revoke') return false;
            if ($userOverrides[$menuKey] === 'grant') return true;
        }

        foreach ($roles as $role) {
            $defaults = self::getRoleDefaults($role->name);

            // Non-wildcard role logic continues below

            $permissions = self::getPermissionsForRole($role->id);
            $codeMap     = self::getDefaultPermMap($role->name);

            if (empty($permissions)) {
                // No DB rows yet — fall back to role defaults
                if (empty($codeMap)) {
                    return false;
                }
                $permissions = $codeMap;
            }

            // DB rows exist — DB is authoritative. Code defaults are ONLY used as fallback
            // when a key has NO DB entry yet (avoids silently ignoring admin-saved denials).
            if ($submenuKey) {
                $key = $menuKey . '.' . $submenuKey;
                if (isset($permissions[$key])) {
                    // DB has explicit entry — honour it (true OR false)
                    if ($permissions[$key]) return true;
                    // Explicitly denied in DB — skip code defaults for this key
                } else {
                    // Key absent from DB — use code defaults as fallback
                    if ($codeMap[$key] ?? false) return true;
                    // Check parent menu key grant (DB-level inheritance, no code fallback)
                    if (isset($permissions[$menuKey]) && $permissions[$menuKey]) return true;
                }
            } else {
                if (isset($permissions[$menuKey])) {
                    if ($permissions[$menuKey]) return true;
                    // Explicitly denied in DB — skip code defaults
                } else {
                    if ($codeMap[$menuKey] ?? false) return true;
                }
            }
        }

        return false;
    }

    public static function companyAllowsMenu($user, string $fullKey): bool
    {
        if (!$user || !$user->company_id) return true;

        $company = $user->company;
        if (!$company) return true;

        return $company->hasMenuAccess($fullKey);
    }

    public static function hasAnySubmenuAccess($user, string $menuKey): bool
    {
        if (!$user) return false;
        if ($user->hasRole('Super Admin')) return true;

        $isWildcard = false;
        foreach ($user->roles as $role) {
            if (self::getRoleDefaults($role->name) === '*') {
                $isWildcard = true;
                break;
            }
        }

        // Wildcard roles also respect company allowed_menus when explicitly set
        $companyChk = $user->company ?? null;
        $companyMenusChk = $companyChk ? ($companyChk->allowed_menus ?? []) : [];
        $shouldCheckCompany = $isWildcard ? !empty($companyMenusChk) : true;

        if ($shouldCheckCompany && !self::companyHasAnyChild($user, $menuKey)) {
            return false;
        }

        $menuConfig = config('menu.' . $menuKey);
        if (!$menuConfig) {
            // Handle parent key aliases (sidebar uses old keys like 'reseller',
            // but menu config has new keys like 'mac_reseller', 'bw_reseller')
            static $parentKeyAliases = [
                'reseller' => ['mac_reseller', 'bw_reseller'],
            ];
            foreach ($parentKeyAliases[$menuKey] ?? [] as $aliasKey) {
                if (self::hasAnySubmenuAccess($user, $aliasKey)) {
                    return true;
                }
            }
            return false;
        }

        if (empty($menuConfig['submenus'])) {
            return self::canAccessMenu($user, $menuKey);
        }

        if (self::canAccessMenu($user, $menuKey)) {
            return true;
        }

        foreach ($menuConfig['submenus'] as $subKey => $sub) {
            if (self::canAccessMenu($user, $menuKey, $subKey)) {
                return true;
            }
        }

        return false;
    }

    public static function companyHasAnyChild($user, string $parentKey): bool
    {
        if (!$user || !$user->company_id) return true;

        $company = $user->company;
        if (!$company) return true;

        return $company->hasAnyChildAccess($parentKey);
    }

    public static function getPermissionsForRole(int $roleId): array
    {
        return Cache::remember("menu_permissions_role_{$roleId}", 300, function () use ($roleId) {
            $perms = MenuPermission::withoutGlobalScopes()->where('role_id', $roleId)->whereNull('action_key')->get();
            $result = [];
            foreach ($perms as $perm) {
                $key = $perm->submenu_key ? $perm->menu_key . '.' . $perm->submenu_key : $perm->menu_key;
                $result[$key] = $perm->allowed;
            }
            return $result;
        });
    }

    public static function getUserOverrides(int $userId): array
    {
        return Cache::remember("user_menu_overrides_{$userId}", 300, function () use ($userId) {
            $overrides = UserMenuPermission::withoutGlobalScopes()->where('user_id', $userId)->whereNull('action_key')->get();
            $result = [];
            foreach ($overrides as $o) {
                $key = $o->submenu_key ? $o->menu_key . '.' . $o->submenu_key : $o->menu_key;
                $result[$key] = $o->override;
            }
            return $result;
        });
    }

    public static function getUserActionOverrides(int $userId): array
    {
        return Cache::remember("user_action_overrides_{$userId}", 300, function () use ($userId) {
            $overrides = UserMenuPermission::withoutGlobalScopes()->where('user_id', $userId)->whereNotNull('action_key')->get();
            $result = [];
            foreach ($overrides as $o) {
                $key = $o->menu_key . '.' . $o->action_key;
                $result[$key] = $o->override;
            }
            return $result;
        });
    }

    public static function canAction($user, string $pageKey, string $actionKey): bool
    {
        if (!$user) return false;
        if ($user->hasRole('Super Admin')) return true;

        $userOverrides = self::getUserActionOverrides($user->id);
        $overrideKey = $pageKey . '.' . $actionKey;
        if (isset($userOverrides[$overrideKey])) {
            if ($userOverrides[$overrideKey] === 'grant') return true;
            if ($userOverrides[$overrideKey] === 'revoke') return false;
        }

        $roles = $user->roles;
        foreach ($roles as $role) {
            $defaults = self::getRoleDefaults($role->name);

            // '*' means unconditional full access — user-level overrides above still apply.
            if ($defaults === '*') return true;

            // Check menu permissions first (saved via HRM panel dashboard card checkboxes)
            $menuPerms = self::getPermissionsForRole($role->id);
            $menuSubKey = $pageKey . '.' . $actionKey;
            if (array_key_exists($menuSubKey, $menuPerms)) {
                if ($menuPerms[$menuSubKey]) return true;
                // Explicitly disabled in menu permissions — skip action defaults for this key
                continue;
            }

            $actionPerms    = self::getActionPermissionsForRole($role->id);
            $actionDefaults = self::getActionDefaults($role->name);
            $actionCodeMap  = ($actionDefaults === '*') ? [] : $actionDefaults;

            if (empty($actionPerms)) {
                if ($actionDefaults === '*') return true;
                $key = $pageKey . '.' . $actionKey;
                if ($actionCodeMap[$key] ?? false) return true;
                continue;
            }

            // DB rows exist — DB is authoritative. Code defaults used only when key absent.
            $key = $pageKey . '.' . $actionKey;
            if (isset($actionPerms[$key])) {
                if ($actionPerms[$key]) return true;
                // Explicitly denied in DB — respect it, skip code defaults
                continue;
            }
            // Key not in DB yet → use code defaults as fallback
            if ($actionCodeMap[$key] ?? false) return true;
        }

        return false;
    }

    public static function getActionDefaults(string $roleName): array|string
    {
        // ── Dashboard stat cards (all true) ──────────────────────────────────
        $dashAll = array_fill_keys([
            'dashboard.total_clients', 'dashboard.online_clients',
            'dashboard.total_active', 'dashboard.total_inactive',
            'dashboard.monthly_collection', 'dashboard.today_collection',
            'dashboard.running_month_bills', 'dashboard.month_bills_collected',
            'dashboard.prev_month_due', 'dashboard.this_month_due',
            'dashboard.total_dues', 'dashboard.overdue_clients',
            'dashboard.prev_month_paid', 'dashboard.this_month_paid',
            'dashboard.today_expense', 'dashboard.monthly_expense',
            'dashboard.unpaid_clients', 'dashboard.billing_clients',
            'dashboard.today_auto_inactive', 'dashboard.pending_tickets',
            'dashboard.installation_charge', 'dashboard.reseller_collection',
            'dashboard.other_collection', 'dashboard.total_pops',
            'dashboard.live_online_chart', 'dashboard.expiring_soon',
            'dashboard.recent_auto_inactive', 'dashboard.recent_online_payments',
            'dashboard.recent_payments', 'dashboard.recent_tickets',
            'dashboard.staff_dues', 'dashboard.zone_inactive_clients',
            'dashboard.chart_30d_clients', 'dashboard.chart_30d_collection',
            'dashboard.chart_30d_auto_inactive', 'dashboard.chart_due_bills',
            'dashboard.chart_reseller_expense', 'dashboard.chart_zone_clients',
            'dashboard.chart_area_clients', 'dashboard.chart_180d_collection',
            'dashboard.chart_monthly_revenue',
        ], true);

        // ── Billing-focused dashboard (no expense/financial charts) ──────────
        $dashBilling = array_merge($dashAll, [
            'dashboard.today_expense'           => false,
            'dashboard.monthly_expense'         => false,
            'dashboard.installation_charge'     => false,
            'dashboard.total_pops'              => false,
            'dashboard.live_online_chart'       => false,
            'dashboard.chart_30d_clients'       => false,
            'dashboard.chart_30d_auto_inactive' => false,
            'dashboard.chart_zone_clients'      => false,
            'dashboard.chart_area_clients'      => false,
            'dashboard.zone_inactive_clients'   => false,
            'dashboard.recent_tickets'          => false,
            'dashboard.staff_dues'              => false,
        ]);

        // ── Collection Agent dashboard ────────────────────────────────────────
        $dashCollection = [
            'dashboard.total_clients'           => true,
            'dashboard.total_active'            => true,
            'dashboard.total_inactive'          => true,
            'dashboard.today_collection'        => true,
            'dashboard.this_month_due'          => true,
            'dashboard.total_dues'              => true,
            'dashboard.overdue_clients'         => true,
            'dashboard.unpaid_clients'          => true,
            'dashboard.today_auto_inactive'     => true,
            'dashboard.expiring_soon'           => true,
            'dashboard.recent_online_payments'  => true,
            'dashboard.recent_payments'         => true,
        ];

        // ── Network Operator dashboard ────────────────────────────────────────
        $dashNetwork = [
            'dashboard.total_clients'           => true,
            'dashboard.online_clients'          => true,
            'dashboard.total_active'            => true,
            'dashboard.today_auto_inactive'     => true,
            'dashboard.pending_tickets'         => true,
            'dashboard.total_pops'              => true,
            'dashboard.live_online_chart'       => true,
            'dashboard.chart_30d_auto_inactive' => true,
            'dashboard.chart_zone_clients'      => true,
            'dashboard.chart_area_clients'      => true,
            'dashboard.zone_inactive_clients'   => true,
            'dashboard.recent_tickets'          => true,
        ];

        // ── Support Staff dashboard ───────────────────────────────────────────
        $dashSupport = [
            'dashboard.total_clients'       => true,
            'dashboard.total_active'        => true,
            'dashboard.total_inactive'      => true,
            'dashboard.unpaid_clients'      => true,
            'dashboard.today_auto_inactive' => true,
            'dashboard.pending_tickets'     => true,
            'dashboard.expiring_soon'       => true,
            'dashboard.recent_tickets'      => true,
        ];

        // ── Page action helpers ───────────────────────────────────────────────
        // Client list page
        $clFull  = ['client_list.add_new'=>true,'client_list.view_profile'=>true,'client_list.edit'=>true,'client_list.delete'=>true,'client_list.quick_pay'=>true,'client_list.change_package'=>true,'client_list.extend_deadline'=>true,'client_list.ledger'=>true,'client_list.mikrotik_toggle'=>true,'client_list.filters'=>true,'client_list.export'=>true];
        $clView  = ['client_list.view_profile'=>true,'client_list.quick_pay'=>true,'client_list.change_package'=>false,'client_list.extend_deadline'=>false,'client_list.ledger'=>true,'client_list.mikrotik_toggle'=>false,'client_list.filters'=>true];
        $clBill  = ['client_list.view_profile'=>true,'client_list.quick_pay'=>true,'client_list.change_package'=>false,'client_list.extend_deadline'=>true,'client_list.ledger'=>true,'client_list.mikrotik_toggle'=>false,'client_list.filters'=>true,'client_list.export'=>false];
        $clAgent = ['client_list.view_profile'=>true,'client_list.quick_pay'=>true,'client_list.change_package'=>false,'client_list.extend_deadline'=>false,'client_list.ledger'=>false,'client_list.mikrotik_toggle'=>false];
        $clSuper = ['client_list.add_new'=>true,'client_list.view_profile'=>true,'client_list.edit'=>true,'client_list.quick_pay'=>true,'client_list.change_package'=>true,'client_list.extend_deadline'=>true,'client_list.ledger'=>true,'client_list.mikrotik_toggle'=>true,'client_list.filters'=>true,'client_list.export'=>true];
        $clTech  = ['client_list.add_new'=>true,'client_list.view_profile'=>true,'client_list.edit'=>true,'client_list.quick_pay'=>true,'client_list.change_package'=>true,'client_list.extend_deadline'=>false,'client_list.ledger'=>true,'client_list.mikrotik_toggle'=>true,'client_list.filters'=>true];
        $clNet   = ['client_list.view_profile'=>true,'client_list.change_package'=>false,'client_list.extend_deadline'=>false,'client_list.ledger'=>false,'client_list.mikrotik_toggle'=>false,'client_list.filters'=>true];

        // Client profile page
        $cpFull = [
            'client_profile.edit'=>true,'client_profile.delete'=>true,'client_profile.pay'=>true,
            'client_profile.add_payment'=>true,'client_profile.ledger'=>true,'client_profile.map'=>true,
            'client_profile.sync_client'=>true,'client_profile.activate_client'=>true,'client_profile.device_control'=>true,
            'client_profile.send_sms'=>true,'client_profile.billing_summary'=>true,'client_profile.generate_bill'=>true,
            'client_profile.pay_installation'=>true,'client_profile.bill_submit'=>true,'client_profile.extend_deadline'=>true,
            'client_profile.remarks'=>true,'client_profile.personal_contact'=>true,'client_profile.connection_details'=>true,
            'client_profile.installation_details'=>true,'client_profile.billing_history'=>true,
            'client_profile.assigned_products'=>true,'client_profile.status_history'=>true,
            'client_profile.ticket_history'=>true,'client_profile.live_monitoring'=>true,
            'client_profile.edit_bill'=>true,'client_profile.delete_bill'=>true,
            'client_profile.edit_payment'=>true,'client_profile.delete_payment'=>true,
        ];
        $cpSupervisor = array_merge($cpFull, [
            'client_profile.delete'=>false,'client_profile.delete_bill'=>false,'client_profile.delete_payment'=>false,
            'client_profile.edit'=>false,'client_profile.device_control'=>false,
            'client_profile.generate_bill'=>false,'client_profile.extend_deadline'=>false,
            'client_profile.edit_bill'=>false,'client_profile.edit_payment'=>false,
        ]);
        $cpBillman = [
            'client_profile.pay'=>true,'client_profile.add_payment'=>true,'client_profile.ledger'=>true,
            'client_profile.billing_summary'=>true,'client_profile.bill_submit'=>true,
            'client_profile.send_sms'=>true,'client_profile.personal_contact'=>true,
            'client_profile.connection_details'=>true,'client_profile.billing_history'=>true,
            'client_profile.status_history'=>true,'client_profile.ticket_history'=>true,'client_profile.remarks'=>true,
        ];
        $cpAgent = [
            'client_profile.pay'=>true,'client_profile.bill_submit'=>true,'client_profile.billing_summary'=>true,
            'client_profile.billing_history'=>true,'client_profile.personal_contact'=>true,'client_profile.connection_details'=>true,
        ];
        $cpTech = [
            'client_profile.pay'=>true,'client_profile.bill_submit'=>true,
            'client_profile.pay_installation'=>true,
            'client_profile.remarks'=>true,'client_profile.personal_contact'=>true,
            'client_profile.connection_details'=>true,'client_profile.installation_details'=>true,
            'client_profile.status_history'=>true,'client_profile.ticket_history'=>true,
            'client_profile.live_monitoring'=>true,'client_profile.assigned_products'=>true,'client_profile.map'=>true,
        ];
        $cpNetwork = [
            'client_profile.connection_details'=>true,'client_profile.assigned_products'=>true,
            'client_profile.live_monitoring'=>true,'client_profile.map'=>true,
            'client_profile.ticket_history'=>true,'client_profile.status_history'=>true,'client_profile.personal_contact'=>true,
        ];
        $cpSupport = [
            'client_profile.personal_contact'=>true,'client_profile.connection_details'=>true,
            'client_profile.remarks'=>true,'client_profile.status_history'=>true,
            'client_profile.ticket_history'=>true,'client_profile.send_sms'=>true,
        ];
        $cpReseller = [
            'client_profile.edit'=>true,'client_profile.pay'=>true,'client_profile.bill_submit'=>true,
            'client_profile.billing_summary'=>true,'client_profile.billing_history'=>true,'client_profile.ledger'=>true,
            'client_profile.personal_contact'=>true,'client_profile.connection_details'=>true,
            'client_profile.status_history'=>true,'client_profile.ticket_history'=>true,
            'client_profile.activate_client'=>true,'client_profile.device_control'=>true,
            'client_profile.generate_bill'=>true,'client_profile.extend_deadline'=>true,
            'client_profile.remarks'=>true,'client_profile.send_sms'=>true,
        ];

        // Billing page
        $bilFull = [
            'billing.generate_bills'=>true,'billing.create_payment'=>true,'billing.edit_payment'=>true,
            'billing.delete_payment'=>true,'billing.approve_payment'=>true,'billing.edit_bill'=>true,
            'billing.delete_bill'=>true,'billing.collection_transfer'=>true,'billing.print_receipt'=>true,'billing.export'=>true,
        ];
        $bilSuper = array_merge($bilFull, ['billing.delete_payment'=>false,'billing.delete_bill'=>false,'billing.edit_payment'=>false,'billing.edit_bill'=>false]);
        $bilBill  = ['billing.create_payment'=>true,'billing.approve_payment'=>true,'billing.collection_transfer'=>true,'billing.print_receipt'=>true,'billing.export'=>true];
        $bilAgent = ['billing.create_payment'=>true,'billing.collection_transfer'=>true,'billing.print_receipt'=>true];
        $bilTech  = ['billing.create_payment'=>true,'billing.print_receipt'=>true,'billing.collection_transfer'=>true];

        // Due list page
        $dueFull = ['due_list.quick_pay'=>true,'due_list.send_sms'=>true,'due_list.bulk_sms'=>true,'due_list.export'=>true];
        $dueBill = ['due_list.quick_pay'=>true,'due_list.send_sms'=>true,'due_list.export'=>true];
        $dueAgent= ['due_list.quick_pay'=>true];
        $dueTech = ['due_list.quick_pay'=>true,'due_list.send_sms'=>true];

        // Packages
        $pkgFull = ['packages.add'=>true,'packages.edit'=>true,'packages.delete'=>true,'packages.toggle_status'=>true,'packages.toggle_public'=>true];

        // Inventory
        $invFull = [
            'inventory.add_product'=>true,'inventory.edit_product'=>true,'inventory.delete_product'=>true,
            'inventory.create_purchase'=>true,'inventory.edit_purchase'=>true,'inventory.delete_purchase'=>true,
            'inventory.add_vendor'=>true,'inventory.edit_vendor'=>true,'inventory.delete_vendor'=>true,
            'inventory.assign_product'=>true,'inventory.manage_fiber'=>true,'inventory.manage_instruments'=>true,
        ];
        $invAssign = ['inventory.assign_product'=>true];

        // Accounts
        $accFull  = ['accounts.add_income'=>true,'accounts.add_expense'=>true,'accounts.edit_transaction'=>true,'accounts.delete_transaction'=>true,'accounts.manage_categories'=>true,'accounts.view_reports'=>true];
        $accView  = ['accounts.view_reports'=>true];

        // ── Reseller page actions ─────────────────────────────────────────
        $resellerFull = ['mac_reseller.add'=>true,'mac_reseller.edit'=>true,'mac_reseller.delete'=>true,'mac_reseller.view_clients'=>true,'mac_reseller.toggle_status'=>true,'mac_reseller.manage_funding'=>true,'mac_reseller.view_collection_report'=>true,'bw_reseller.add'=>true,'bw_reseller.edit'=>true,'bw_reseller.delete'=>true,'bw_reseller.view_clients'=>true,'bw_reseller.toggle_status'=>true,'bw_reseller.manage_bw_funding'=>true,'bw_reseller.view_bw_collection_report'=>true,'bw_reseller.add_bw_package'=>true,'bw_reseller.edit_bw_package'=>true];
        $resellerMgr  = ['mac_reseller.add'=>true,'mac_reseller.edit'=>true,'mac_reseller.delete'=>false,'mac_reseller.view_clients'=>true,'mac_reseller.toggle_status'=>true,'mac_reseller.manage_funding'=>true,'mac_reseller.view_collection_report'=>true,'bw_reseller.add'=>true,'bw_reseller.edit'=>true,'bw_reseller.delete'=>false,'bw_reseller.view_clients'=>true,'bw_reseller.toggle_status'=>true,'bw_reseller.manage_bw_funding'=>true,'bw_reseller.view_bw_collection_report'=>true,'bw_reseller.add_bw_package'=>true,'bw_reseller.edit_bw_package'=>true];
        $resellerView = ['mac_reseller.view_clients'=>true,'mac_reseller.view_collection_report'=>true,'bw_reseller.view_clients'=>true,'bw_reseller.view_bw_collection_report'=>true];

        // ── Agent (Referral Partner) page actions ─────────────────────────
        $agentFull = ['agent.add_agent'=>true,'agent.edit_agent'=>true,'agent.delete_agent'=>true,'agent.view_ledger'=>true,'agent.add_commission'=>true,'agent.export'=>true];
        $agentMgr  = ['agent.add_agent'=>true,'agent.edit_agent'=>true,'agent.delete_agent'=>false,'agent.view_ledger'=>true,'agent.add_commission'=>true,'agent.export'=>true];
        $agentView = ['agent.view_ledger'=>true,'agent.export'=>false];

        // ── Branch & POP page actions ─────────────────────────────────────
        $branchFull = ['branches.add_branch'=>true,'branches.edit_branch'=>true,'branches.delete_branch'=>true,'branches.add_pop'=>true,'branches.edit_pop'=>true,'branches.delete_pop'=>true];
        $branchMgr  = ['branches.add_branch'=>true,'branches.edit_branch'=>true,'branches.delete_branch'=>false,'branches.add_pop'=>true,'branches.edit_pop'=>true,'branches.delete_pop'=>false];
        $branchNet  = ['branches.add_branch'=>false,'branches.edit_branch'=>false,'branches.delete_branch'=>false,'branches.add_pop'=>true,'branches.edit_pop'=>true,'branches.delete_pop'=>false];

        // ── Configuration page actions ────────────────────────────────────
        $configFull = ['configuration.manage_areas'=>true,'configuration.manage_zones'=>true,'configuration.manage_packages'=>true,'configuration.manage_connection_types'=>true,'configuration.manage_dropdown_options'=>true,'configuration.manage_districts'=>true,'configuration.manage_client_types'=>true,'configuration.manage_protocol_types'=>true];
        $configMgr  = ['configuration.manage_areas'=>true,'configuration.manage_zones'=>true,'configuration.manage_packages'=>true,'configuration.manage_connection_types'=>false,'configuration.manage_dropdown_options'=>true,'configuration.manage_districts'=>false,'configuration.manage_client_types'=>false,'configuration.manage_protocol_types'=>false];

        // ── Task Management page actions ──────────────────────────────────
        $taskFull = ['tasks.add_task'=>true,'tasks.edit_task'=>true,'tasks.delete_task'=>true,'tasks.assign_task'=>true,'tasks.complete_task'=>true];
        $taskMgr  = ['tasks.add_task'=>true,'tasks.edit_task'=>true,'tasks.delete_task'=>false,'tasks.assign_task'=>true,'tasks.complete_task'=>true];
        $taskTech = ['tasks.add_task'=>true,'tasks.edit_task'=>false,'tasks.delete_task'=>false,'tasks.assign_task'=>false,'tasks.complete_task'=>true];

        // ── Events & Holidays page actions ────────────────────────────────
        $eventFull = ['events.add_event'=>true,'events.edit_event'=>true,'events.delete_event'=>true,'events.add_holiday'=>true,'events.delete_holiday'=>true];
        $eventMgr  = ['events.add_event'=>true,'events.edit_event'=>true,'events.delete_event'=>false,'events.add_holiday'=>true,'events.delete_holiday'=>false];

        // ── SMS Service page actions ──────────────────────────────────────
        $smsFull = ['sms.send_individual'=>true,'sms.send_bulk'=>true,'sms.manage_templates'=>true,'sms.manage_gateway'=>true];
        $smsMgr  = ['sms.send_individual'=>true,'sms.send_bulk'=>true,'sms.manage_templates'=>true,'sms.manage_gateway'=>false];
        $smsBill = ['sms.send_individual'=>true,'sms.send_bulk'=>false,'sms.manage_templates'=>false,'sms.manage_gateway'=>false];

        // ── OLT Management page actions ───────────────────────────────────
        $oltFull = ['olt.add_olt'=>true,'olt.edit_olt'=>true,'olt.delete_olt'=>true,'olt.manage_onu'=>true,'olt.manage_users'=>true];
        $oltNet  = ['olt.add_olt'=>false,'olt.edit_olt'=>true,'olt.delete_olt'=>false,'olt.manage_onu'=>true,'olt.manage_users'=>true];

        // ── Card Billing page actions ─────────────────────────────────────
        $cardFull  = ['card_billing.create_batch'=>true,'card_billing.generate_cards'=>true,'card_billing.distribute'=>true,'card_billing.redeem_card'=>true,'card_billing.manage_distributors'=>true];
        $cardAgent = ['card_billing.create_batch'=>false,'card_billing.generate_cards'=>false,'card_billing.distribute'=>true,'card_billing.redeem_card'=>true,'card_billing.manage_distributors'=>false];

        // ── IPAM page actions ─────────────────────────────────────────────
        $ipamFull = ['ipam.add_pool'=>true,'ipam.edit_pool'=>true,'ipam.delete_pool'=>true,'ipam.assign_ip'=>true];
        $ipamNet  = ['ipam.add_pool'=>false,'ipam.edit_pool'=>true,'ipam.delete_pool'=>false,'ipam.assign_ip'=>true];
        $ipamMgr  = ['ipam.add_pool'=>false,'ipam.edit_pool'=>false,'ipam.delete_pool'=>false,'ipam.assign_ip'=>true];

        // ── Data Transfer page actions ────────────────────────────────────
        $dataFull = ['data_transfer.import_data'=>true,'data_transfer.export_data'=>true,'data_transfer.run_migration'=>true];
        $dataMgr  = ['data_transfer.import_data'=>false,'data_transfer.export_data'=>true,'data_transfer.run_migration'=>false];

        // ── Assistant Manager action defaults ────────────────────────────────
        $cpAsstMgr = array_merge($cpFull, [
            'client_profile.delete' => false,
            'client_profile.edit' => false,
            'client_profile.device_control' => false,
            'client_profile.generate_bill' => false,
            'client_profile.extend_deadline' => false,
            'client_profile.edit_bill' => false,
            'client_profile.delete_bill' => false,
            'client_profile.edit_payment' => false,
            'client_profile.delete_payment' => false,
        ]);
        $bilAsstMgr = array_merge($bilFull, [
            'billing.delete_payment' => false,
            'billing.delete_bill'    => false,
            'billing.edit_payment'   => false,
            'billing.edit_bill'      => false,
        ]);

        // ── Role permission maps ──────────────────────────────────────────────
        $defaults = [
            'Administrator' => '*',
            'Admin Support' => '*',

            'Assistant Manager' => array_merge(
                $dashAll, $clSuper, $cpAsstMgr, $bilAsstMgr, $dueFull, $invAssign, $accView,
                $resellerMgr, $agentMgr, $branchMgr, $configMgr, $taskMgr, $eventMgr, $smsMgr, $dataMgr
            ),

            'Supervisor' => array_merge(
                $dashAll, $clSuper, $cpSupervisor, $bilSuper, $dueFull, $invAssign, $accView,
                $resellerView, $agentView, $taskMgr, $eventMgr, $smsMgr
            ),

            'Field Technician' => array_merge(
                ['dashboard.total_active'=>true,'dashboard.total_inactive'=>true,'dashboard.today_collection'=>true,'dashboard.total_dues'=>true,'dashboard.pending_tickets'=>true,'dashboard.expiring_soon'=>true,'dashboard.recent_payments'=>true],
                $clTech, $cpTech, $bilTech, $dueTech, $invAssign,
                $taskTech
            ),

            'Billing Officer' => array_merge(
                $dashBilling, $clBill, $cpBillman, $bilBill, $dueBill, $smsBill, $resellerView
            ),

            'Collection Officer' => array_merge(
                $dashCollection, $clAgent, $cpAgent, $bilAgent, $dueAgent
            ),

            'Network Engineer' => array_merge(
                $dashNetwork, $clNet, $cpNetwork, $invAssign,
                $oltNet, $branchNet, $ipamNet
            ),

            'Support Executive' => array_merge(
                $dashSupport, $clNet, $cpSupport,
                ['due_list.send_sms'=>true]
            ),

            'Sub Reseller' => array_merge(
                ['dashboard.total_active'=>true,'dashboard.total_inactive'=>true,'dashboard.today_collection'=>true,'dashboard.total_dues'=>true,'dashboard.expiring_soon'=>true,'dashboard.recent_payments'=>true],
                $clTech, $cpReseller, $bilTech, $dueTech
            ),

            'Card Agent' => array_merge(
                ['dashboard.total_clients'=>true,'dashboard.total_active'=>true],
                $clNet, $cpSupport, $cardAgent
            ),

            'Branch Manager' => array_merge(
                $dashAll, $clFull, $cpAsstMgr, $bilAsstMgr, $dueFull, $invAssign, $accView,
                $resellerMgr, $agentMgr, $branchFull, $configMgr, $taskMgr, $eventMgr, $smsMgr, $dataMgr
            ),

            'Reseller' => array_merge(
                $dashAll, $clFull, $cpReseller, $bilTech, $dueTech
            ),
        ];

        if (isset($defaults[$roleName])) return $defaults[$roleName];
        $lower = strtolower($roleName);
        foreach ($defaults as $key => $val) {
            if (strtolower($key) === $lower) return $val;
        }
        return [];
    }

    public static function getActionPermissionsForRole(int $roleId): array
    {
        return Cache::remember("action_permissions_role_{$roleId}", 300, function () use ($roleId) {
            $perms = MenuPermission::withoutGlobalScopes()->where('role_id', $roleId)->whereNotNull('action_key')->get();
            $result = [];
            foreach ($perms as $perm) {
                $key = $perm->menu_key . '.' . $perm->action_key;
                $result[$key] = $perm->allowed;
            }
            return $result;
        });
    }

    public static function clearCache(?int $roleId = null): void
    {
        if ($roleId) {
            Cache::forget("menu_permissions_role_{$roleId}");
            Cache::forget("action_permissions_role_{$roleId}");
        } else {
            $roles = \Spatie\Permission\Models\Role::all();
            foreach ($roles as $role) {
                Cache::forget("menu_permissions_role_{$role->id}");
                Cache::forget("action_permissions_role_{$role->id}");
            }
        }
    }

    public static function clearUserCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget("user_menu_overrides_{$userId}");
            Cache::forget("user_action_overrides_{$userId}");
        }
    }

    public static function clearCompanyUsersCache(int $companyId): void
    {
        $userIds = \App\Models\User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->pluck('id');

        foreach ($userIds as $userId) {
            Cache::forget("user_menu_overrides_{$userId}");
            Cache::forget("user_action_overrides_{$userId}");
        }
    }

    public static function getEffectivePermMap($user): array
    {
        if (!$user) return [];

        $menus = config('menu');
        $rolePerms = [];
        $roles = $user->roles;

        foreach ($roles as $role) {
            $defaults = self::getRoleDefaults($role->name);

            // '*' = unconditional full access — always expand to all menus regardless of DB
            if ($defaults === '*') {
                foreach ($menus as $menuKey => $menu) {
                    $rolePerms[$menuKey] = true;
                    if (!empty($menu['submenus'])) {
                        foreach ($menu['submenus'] as $subKey => $sub) {
                            $rolePerms[$menuKey . '.' . $subKey] = true;
                        }
                    }
                }
                break;
            }

            $permissions = self::getPermissionsForRole($role->id);

            if (empty($permissions)) {
                $permissions = self::getDefaultPermMap($role->name);
            }

            // DB rows exist → use actual configured values
            foreach ($permissions as $k => $v) {
                if ($v) $rolePerms[$k] = true;
            }
        }

        $userOverrides = self::getUserOverrides($user->id);

        $result = [];
        foreach ($menus as $menuKey => $menu) {
            $roleVal = $rolePerms[$menuKey] ?? false;
            $companyAllowed = self::companyAllowsMenu($user, $menuKey);
            if (!$companyAllowed) $roleVal = false;
            $override = $userOverrides[$menuKey] ?? null;
            $effective = $override === 'grant' ? true : ($override === 'revoke' ? false : $roleVal);
            if (!$companyAllowed) $effective = false;
            $result[$menuKey] = [
                'role' => $roleVal,
                'override' => $override,
                'effective' => $effective,
            ];

            if (!empty($menu['submenus'])) {
                foreach ($menu['submenus'] as $subKey => $sub) {
                    $key = $menuKey . '.' . $subKey;
                    $subRoleVal = $rolePerms[$key] ?? false;
                    $subCompanyAllowed = self::companyAllowsMenu($user, $key);
                    if (!$subCompanyAllowed) $subRoleVal = false;
                    $subOverride = $userOverrides[$key] ?? null;
                    // Cascade: if no exact override, inherit parent override
                    if ($subOverride === null && isset($userOverrides[$menuKey])) {
                        $subOverride = $userOverrides[$menuKey];
                    }
                    $subEffective = $subOverride === 'grant' ? true : ($subOverride === 'revoke' ? false : $subRoleVal);
                    if (!$subCompanyAllowed) $subEffective = false;
                    $result[$key] = [
                        'role' => $subRoleVal,
                        'override' => $subOverride,
                        'effective' => $subEffective,
                    ];
                }
            }
        }

        return $result;
    }

    public static function saveUserOverrides(int $userId, array $overrides): void
    {
        UserMenuPermission::withoutGlobalScopes()->where('user_id', $userId)->whereNull('action_key')->delete();

        foreach ($overrides as $key => $override) {
            if (!in_array($override, ['grant', 'revoke'])) continue;

            $parts = explode('.', $key, 2);
            $menuKey = $parts[0];
            $submenuKey = $parts[1] ?? null;

            UserMenuPermission::create([
                'user_id' => $userId,
                'menu_key' => $menuKey,
                'submenu_key' => $submenuKey,
                'override' => $override,
            ]);
        }

        self::clearUserCache($userId);
    }

    public static function getRoleDefaults(string $roleName): array|string
    {
        $defaults = [
            'Administrator' => '*',
            'Admin Support' => '*',

            'Assistant Manager' => [
                'dashboard',
                'reports'  => ['daily', 'monthly', 'due', 'zone', 'client_ledger', 'employee_collection', 'bandwidth', 'income_statement', 'profit_loss', 'stock', 'btrc'],
                'client'   => ['new_request', 'submit_application', 'my_applications', 'client_list', 'left_client', 'add_new', 'change_request', 'scheduler', 'client_restore'],
                'billing'  => ['generate', 'bill_list', 'due', 'payments', 'pending_payments', 'collection_transfer', 'advance_collection', 'online_payments', 'corrections', 'staff_dues', 'daily_collections', 'collection_report', 'android_sms'],
                'events'   => ['list', 'holidays'],
                'support'  => ['tickets', 'categories', 'sla', 'technician_efficiency'],
                'tasks'    => ['create', 'all', 'calendar'],
                'sms'      => ['individual', 'bulk', 'templates', 'logs'],
                'hrm'      => ['employees', 'departments', 'designations', 'attendance', 'selfie_attendance', 'selfie_attendance_report', 'salary', 'leave', 'staff_dues'],
                'accounts' => ['income', 'expense', 'banks', 'cash'],
                'branches' => ['list', 'pops'],
                'payment_links',
                'daily_billing',
                'inventory' => ['dashboard', 'products', 'product_categories', 'stock_availability', 'vendors', 'instruments', 'fiber', 'damaged', 'serial', 'returned_equipments'],
            ],

            'Field Technician' => [
                'dashboard' => ['total_active', 'total_inactive', 'today_collection', 'total_dues', 'pending_tickets', 'recent_tickets'],
                'client' => ['client_list', 'add_new'],
                'billing' => ['due', 'payments', 'collection_transfer', 'daily_collections', 'collection_report'],
                'support' => ['tickets'],
            ],

            'Billing Officer' => [
                'dashboard',
                'client' => ['client_list'],
                'billing' => ['bill_list', 'due', 'payments', 'pending_payments', 'collection_transfer', 'staff_dues', 'daily_collections', 'collection_report'],
            ],

            'Reseller' => [
                'dashboard',
                'client' => ['add_new', 'client_list'],
                'billing' => ['bill_list', 'due', 'payments'],
                'reports' => ['daily', 'monthly', 'due'],
                'mac_reseller' => ['list', 'clients', 'funding', 'collection_report'],
                'bw_reseller' => ['list', 'bw_categories', 'bw_billing', 'bw_collection_report'],
            ],

            'Sub Reseller' => [
                'dashboard',
                'client' => ['add_new', 'client_list'],
                'billing' => ['bill_list', 'due', 'payments', 'collection_transfer'],
            ],

            'Supervisor' => [
                'dashboard',
                'client' => ['new_request', 'submit_application', 'my_applications', 'client_list', 'left_client', 'add_new'],
                'billing' => ['bill_list', 'due', 'payments', 'pending_payments', 'collection_transfer', 'staff_dues', 'daily_collections', 'collection_report'],
                'events' => ['list', 'holidays'],
                'support' => ['tickets', 'categories'],
                'tasks'  => ['all', 'create', 'calendar'],
                'reports' => ['income_statement', 'daily', 'monthly', 'due', 'zone', 'employee_collection'],
                'hrm' => ['employees', 'technicians', 'attendance', 'selfie_attendance', 'selfie_attendance_report'],
            ],

            'Collection Officer' => [
                'dashboard',
                'client' => ['client_list'],
                'billing' => ['due', 'payments', 'collection_transfer', 'staff_dues', 'daily_collections', 'collection_report'],
            ],

            'Network Engineer' => [
                'dashboard',
                'client' => ['client_list'],
                'mikrotik' => ['router_list', 'firewall', 'nat', 'bulk_operations', 'connectivity_test'],
                'network' => ['pop', 'device_diagram', 'topology', 'radius_settings'],
                'olt' => ['devices', 'users', 'onu_list'],
                'support' => ['tickets'],
            ],

            'Support Executive' => [
                'dashboard',
                'client' => ['new_request', 'submit_application', 'my_applications', 'client_list'],
                'support' => ['tickets', 'categories'],
                'billing' => ['bill_list', 'due'],
            ],

            'Card Agent' => [
                'dashboard',
                'client' => ['client_list'],
            ],
        ];

        if (isset($defaults[$roleName])) {
            return $defaults[$roleName];
        }
        $lowerName = strtolower($roleName);
        foreach ($defaults as $key => $value) {
            if (strtolower($key) === $lowerName) {
                return $value;
            }
        }
        return [];
    }

    public static function getDefaultPermMap(string $roleName): array
    {
        $roleDefaults = self::getRoleDefaults($roleName);

        if ($roleDefaults === '*') {
            $menus = config('menu');
            $permMap = [];
            foreach ($menus as $menuKey => $menu) {
                $permMap[$menuKey] = true;
                if (!empty($menu['submenus'])) {
                    foreach ($menu['submenus'] as $subKey => $sub) {
                        $permMap[$menuKey . '.' . $subKey] = true;
                    }
                }
            }
            return $permMap;
        }

        $menus = config('menu');
        $permMap = [];

        foreach ($menus as $menuKey => $menu) {
            $allowed = false;
            $subDefaults = [];

            if (in_array($menuKey, $roleDefaults)) {
                $allowed = true;
            } elseif (isset($roleDefaults[$menuKey])) {
                $allowed = true;
                $subDefaults = $roleDefaults[$menuKey];
            }

            $permMap[$menuKey] = $allowed;

            if (!empty($menu['submenus'])) {
                foreach ($menu['submenus'] as $subKey => $sub) {
                    if ($allowed && empty($subDefaults)) {
                        $permMap[$menuKey . '.' . $subKey] = true;
                    } elseif (!empty($subDefaults)) {
                        $permMap[$menuKey . '.' . $subKey] = in_array($subKey, $subDefaults);
                    } else {
                        $permMap[$menuKey . '.' . $subKey] = false;
                    }
                }
            }
        }

        return $permMap;
    }

    public static function seedRoleDefaults(int $roleId, string $roleName): void
    {
        $permMap = self::getDefaultPermMap($roleName);

        if (empty($permMap)) {
            return;
        }

        $menus = config('menu');
        foreach ($menus as $menuKey => $menu) {
            MenuPermission::withoutGlobalScopes()->updateOrCreate(
                ['role_id' => $roleId, 'menu_key' => $menuKey, 'submenu_key' => null, 'action_key' => null],
                ['allowed' => $permMap[$menuKey] ?? false]
            );

            if (!empty($menu['submenus'])) {
                foreach ($menu['submenus'] as $subKey => $sub) {
                    MenuPermission::withoutGlobalScopes()->updateOrCreate(
                        ['role_id' => $roleId, 'menu_key' => $menuKey, 'submenu_key' => $subKey, 'action_key' => null],
                        ['allowed' => $permMap[$menuKey . '.' . $subKey] ?? false]
                    );
                }
            }
        }

        self::clearCache($roleId);
    }
}
