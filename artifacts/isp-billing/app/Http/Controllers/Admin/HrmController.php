<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Attendance;
use App\Models\EmployeeSelfieAttendance;
use App\Models\Salary;
use App\Models\User;
use App\Models\MenuPermission;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodPerm;
use App\Services\MenuPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Permission\Models\Role;

class HrmController extends Controller
{
    public function employees(Request $request)
    {
        $query = Employee::with(['department', 'designation', 'user.roles']);

        if ($request->filled('role_filter')) {
            $roleFilter = $request->role_filter;
            if ($roleFilter === 'no_account') {
                $query->whereNull('user_id');
            } else {
                $query->whereHas('user.roles', function ($q) use ($roleFilter) {
                    $q->where('name', $roleFilter);
                });
            }
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('employee_id', 'ilike', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20)->appends($request->query());
        $departments = Department::withoutGlobalScopes()->where('is_active', true)->where('company_id', auth()->user()->company_id ?? 1)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->get();
        $user = auth()->user();
        $roles = Role::whereNotIn('name', ['Super Admin', 'client'])->orderBy('name')->get();
        $allRoles = Role::whereNotIn('name', ['Super Admin', 'client'])->orderBy('name')->get();
        $companyIdForZones = auth()->user()->company_id;
        $zones = \App\Models\Zone::withoutGlobalScope('tenant')->where('is_active', true)->where('company_id', $companyIdForZones)->whereNull('reseller_id')->orderBy('name')->get();
        return view('admin.hrm.employees', compact('employees', 'departments', 'designations', 'roles', 'allRoles', 'zones'));
    }

    public function storeEmployee(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'joining_date' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:5120',
            'cv_file' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'nid_photo_front' => 'nullable|image|max:5120',
            'nid_photo_back' => 'nullable|image|max:5120',
        ];

        if ($request->create_account) {
            $rules['login_email'] = 'required|email|unique:users,email';
            $rules['password'] = 'required|string|min:6';
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $userId = null;

            // Resolve company_id early — super admin has company_id=NULL, fall back to first company
            $resolvedCompanyId = auth()->user()->company_id
                ?? \DB::table('companies')->orderBy('id')->value('id')
                ?? 1;

            if ($request->create_account && auth()->user() && auth()->user()->hasRole('Super Admin')) {
                $userType = 'employee';
                if ($request->role_id) {
                    $roleCheck = Role::find($request->role_id);
                    if ($roleCheck) {
                        $roleUserTypeMap = [
                            'technician'        => 'technician',
                            'billman'           => 'billman',
                            'manager'           => 'manager',
                            'reseller'          => 'reseller',
                            'sub-reseller'      => 'sub_reseller',
                            'card-distributor'  => 'card_distributor',
                            'admin'             => 'admin',
                            'client'            => 'client',
                        ];
                        $userType = $roleUserTypeMap[strtolower($roleCheck->name)] ?? 'employee';
                    }
                }

                $user = User::create([
                    'name'              => $request->name,
                    'email'             => $request->login_email,
                    'password'          => Hash::make($request->password),
                    'phone'             => $request->phone,
                    'user_type'         => $userType,
                    'is_active'         => true,
                    'company_id'        => $resolvedCompanyId,
                    'email_verified_at' => now(),
                ]);
                $userId = $user->id;

                if ($request->role_id) {
                    $role = Role::find($request->role_id);
                    if ($role && $role->name !== 'Super Admin') {
                        $user->assignRole($role);

                        if (strtolower($role->name) === 'technician') {
                            $defaultPermissions = [
                                'technician.dashboard', 'technician.clients',
                                'technician.due_report', 'technician.collection',
                                'technician.tickets', 'technician.new_connection',
                            ];
                            foreach ($defaultPermissions as $permName) {
                                \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                            }
                            $user->syncPermissions($defaultPermissions);
                        }

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
                        
                        $canManage = auth()->user()->hasRole('Super Admin') || auth()->user()->user_type === 'admin';
                        if ($request->has('menu_permissions') && $canManage) {
                            $this->saveMenuPermissions($role->id, $request->input('menu_permissions', []));
                        }
                    }
                }
            }

            $data = [
                'user_id' => $userId,
                'name' => $request->name,
                'email' => $request->email ?? $request->login_email,
                'phone' => $request->phone,
                'address' => $request->address,
                'nid' => $request->nid,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'joining_date' => $request->joining_date ?? now(),
                'basic_salary' => $request->basic_salary ?? 0,
                'bank_account' => $request->bank_account,
                'bank_name' => $request->bank_name,
                'status' => $request->status ?? 'active',
                'company_id' => $resolvedCompanyId,
            ];

            if ($request->has('zone_ids') && is_array($request->zone_ids)) {
                $data['zone_ids'] = array_map('intval', $request->zone_ids);
            }

            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
            }
            if ($request->hasFile('cv_file')) {
                $data['cv_file'] = $request->file('cv_file')->store('employees/cv', 'public');
            }
            if ($request->hasFile('nid_photo_front')) {
                $data['nid_photo_front'] = $request->file('nid_photo_front')->store('employees/nid', 'public');
            }
            if ($request->hasFile('nid_photo_back')) {
                $data['nid_photo_back'] = $request->file('nid_photo_back')->store('employees/nid', 'public');
            }

            Employee::create($data);

            DB::commit();
            $message = $userId ? 'Employee added with login access.' : 'Employee added successfully.';
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to add employee: ' . $e->getMessage());
        }
    }

    private function saveMenuPermissions(int $roleId, array $menuPerms): void
    {
        $menus = config('menu');
        foreach ($menus as $menuKey => $menu) {
            $menuAllowed = in_array($menuKey, $menuPerms);
            MenuPermission::withoutGlobalScopes()->updateOrCreate(
                ['role_id' => $roleId, 'menu_key' => $menuKey, 'submenu_key' => null, 'action_key' => null],
                ['allowed' => $menuAllowed]
            );
            if (!empty($menu['submenus'])) {
                foreach ($menu['submenus'] as $subKey => $sub) {
                    $subAllowed = in_array($menuKey . '.' . $subKey, $menuPerms);
                    MenuPermission::withoutGlobalScopes()->updateOrCreate(
                        ['role_id' => $roleId, 'menu_key' => $menuKey, 'submenu_key' => $subKey, 'action_key' => null],
                        ['allowed' => $subAllowed]
                    );
                }
            }
        }
        MenuPermissionService::clearCache($roleId);
    }

    private function getMenuPermMap(int $roleId): array
    {
        $perms = MenuPermission::withoutGlobalScopes()->where('role_id', $roleId)->get();
        $map = [];
        // First pass: action_key entries (e.g. dashboard cards stored in legacy format)
        foreach ($perms as $perm) {
            if ($perm->action_key) {
                $map[$perm->menu_key . '.' . $perm->action_key] = $perm->allowed;
            } elseif (!$perm->submenu_key) {
                $map[$perm->menu_key] = $perm->allowed;
            }
        }
        // Second pass: submenu_key entries take priority over action_key entries
        foreach ($perms as $perm) {
            if ($perm->submenu_key) {
                $map[$perm->menu_key . '.' . $perm->submenu_key] = $perm->allowed;
            }
        }
        return $map;
    }

    public function editEmployee(Employee $employee)
    {
        $departments = Department::withoutGlobalScopes()->where('is_active', true)->where('company_id', auth()->user()->company_id ?? 1)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->get();
        $roles = \Spatie\Permission\Models\Role::whereNotIn('name', ['Super Admin', 'client'])->orderBy('name')->get();
        $zoneCompanyId = $employee->company_id ?? auth()->user()->company_id;
        $zones = \App\Models\Zone::withoutGlobalScope('tenant')->where('is_active', true)->where('company_id', $zoneCompanyId)->whereNull('reseller_id')->orderBy('name')->get();
        $employee->load('user');
        
        $menuPermMap = [];
        $userEffectivePerms = [];
        $roleCodeDefaultMap = [];
        if ($employee->user && $employee->user->roles->count()) {
            $role = $employee->user->roles->first();
            $menuPermMap = $this->getMenuPermMap($role->id);
            $userEffectivePerms = MenuPermissionService::getEffectivePermMap($employee->user);
            // For '*' roles always expand to full access (never use DB perms as baseline)
            $codeDefaults = MenuPermissionService::getRoleDefaults($role->name);
            if ($codeDefaults === '*') {
                $roleCodeDefaultMap = [];
                foreach (config('menu') as $mk => $m) {
                    $roleCodeDefaultMap[$mk] = true;
                    foreach (($m['submenus'] ?? []) as $sk => $s) {
                        $roleCodeDefaultMap[$mk . '.' . $sk] = true;
                    }
                }
            } else {
                $roleDbPerms = MenuPermissionService::getPermissionsForRole($role->id);
                $roleCodeDefaultMap = !empty($roleDbPerms)
                    ? $roleDbPerms
                    : MenuPermissionService::getDefaultPermMap($role->name);
            }
        }

        $allPaymentMethods = PaymentMethod::where('is_active', true)
            ->when(auth()->user()->company_id, fn($q) => $q->where('company_id', auth()->user()->company_id))
            ->orderBy('name')->get();
        $userPaymentPerms = $employee->user
            ? PaymentMethodPerm::where('user_id', $employee->user->id)->pluck('payment_method_id')->toArray()
            : [];

        return view('admin.hrm.employee-edit', compact('employee', 'departments', 'designations', 'roles', 'menuPermMap', 'zones', 'userEffectivePerms', 'roleCodeDefaultMap', 'allPaymentMethods', 'userPaymentPerms'));
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'basic_salary' => 'required|numeric|min:0',
            'photo' => 'nullable|image|max:5120',
            'cv_file' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'nid_photo_front' => 'nullable|image|max:5120',
            'nid_photo_back' => 'nullable|image|max:5120',
        ]);

        $data = $request->only([
            'name', 'email', 'phone', 'address', 'nid', 'date_of_birth', 'gender',
            'department_id', 'designation_id', 'basic_salary',
            'bank_account', 'bank_name', 'status'
        ]);

        // Convert empty integer fields to null to prevent PostgreSQL type errors
        foreach (['department_id', 'designation_id'] as $intField) {
            if (array_key_exists($intField, $data) && $data[$intField] === '') {
                $data[$intField] = null;
            }
        }

        // Only update joining_date if provided, otherwise keep existing
        if ($request->filled('joining_date')) {
            $data['joining_date'] = $request->joining_date;
        }

        // Handle file uploads
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employees/photos', 'public');
            $data['photo'] = $path;
        }

        if ($request->hasFile('cv_file')) {
            $path = $request->file('cv_file')->store('employees/cv', 'public');
            $data['cv_file'] = $path;
        }

        if ($request->hasFile('nid_photo_front')) {
            $path = $request->file('nid_photo_front')->store('employees/nid', 'public');
            $data['nid_photo_front'] = $path;
        }

        if ($request->hasFile('nid_photo_back')) {
            $path = $request->file('nid_photo_back')->store('employees/nid', 'public');
            $data['nid_photo_back'] = $path;
        }

        if ($request->has('zone_ids') && is_array($request->zone_ids)) {
            $data['zone_ids'] = array_map('intval', $request->zone_ids);
        } elseif ($request->has('zone_ids')) {
            $data['zone_ids'] = [];
        }

        $employee->update($data);

        if ($employee->user && isset($data['name'])) {
            $employee->user->update(['name' => $data['name']]);
        }

        $canManagePerms = auth()->user()->hasRole('Super Admin') || auth()->user()->user_type === 'admin';

        if ($employee->user && $canManagePerms) {
            if ($request->has('menu_permissions')) {
                $role = $employee->user->roles->first();
                if ($role) {
                    $this->saveMenuPermissions($role->id, $request->input('menu_permissions', []));
                }
            }

            if ($request->has('user_menu_overrides')) {
                $overrides = $request->input('user_menu_overrides', []);
                MenuPermissionService::saveUserOverrides($employee->user->id, $overrides);
            }
        }

        return redirect()->route('admin.hrm.employees')->with('success', 'Employee updated successfully.');
    }

    public function updateEmployeeCredentials(Request $request, Employee $employee)
    {
        $authUser = auth()->user();
        if (!$authUser->hasRole('Super Admin') && $authUser->user_type !== 'admin') {
            return back()->with('error', 'Unauthorized.');
        }

        $employeeUser = $employee->user;
        if (!$employeeUser) {
            return back()->with('error', 'This employee has no login account.');
        }

        $rules = [
            'login_email' => 'required|email|unique:users,email,' . $employeeUser->id,
        ];

        if ($request->filled('new_password')) {
            $rules['new_password']              = 'min:8|confirmed';
            $rules['new_password_confirmation'] = 'required';
        }

        $request->validate($rules);

        $employeeUser->email = $request->login_email;

        if ($request->filled('new_password')) {
            $employeeUser->password = Hash::make($request->new_password);
        }

        $employeeUser->save();

        return back()->with('success', 'Login credentials updated successfully.');
    }

    public function toggleLoginAccess(Request $request, Employee $employee)
    {
        $authUser = auth()->user();
        if (!$authUser->hasRole('Super Admin') && $authUser->user_type !== 'admin') {
            return back()->with('error', 'Unauthorized.');
        }

        $employeeUser = $employee->user;
        if (!$employeeUser) {
            return back()->with('error', 'This employee has no login account.');
        }

        if ($employeeUser->id === $authUser->id) {
            return back()->with('error', 'You cannot disable your own login access.');
        }

        $employeeUser->is_active = !$employeeUser->is_active;
        $employeeUser->save();

        $status = $employeeUser->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Login access {$status} for {$employee->name}.");
    }

    public function destroyEmployee(Employee $employee)
    {
        DB::beginTransaction();
        try {
            if ($employee->user_id) {
                $user = User::find($employee->user_id);
                if ($user && !$user->hasRole('Super Admin')) {
                    $user->delete();
                }
            }
            $employee->delete();
            DB::commit();
            return redirect()->route('admin.hrm.employees')->with('success', 'Employee and login account deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete employee: ' . $e->getMessage());
        }
    }

    public function saveEmployeePermissions(Request $request, Employee $employee)
    {
        $authUser = auth()->user();
        if (!$authUser->hasRole('Super Admin') && $authUser->user_type !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $employeeUser = $employee->user;
        if (!$employeeUser) {
            return response()->json(['success' => false, 'error' => 'Employee has no login account'], 422);
        }

        $checkedMenus = $request->input('menu_permissions', []);
        $allMenuKeys  = $request->input('all_menu_keys', []);

        $role = $employeeUser->roles->first();
        $roleDefaults = [];

        if ($role) {
            $defaults = MenuPermissionService::getRoleDefaults($role->name);
            // Always use code-level defaults for '*' roles — never let DB perms override wildcard
            if ($defaults === '*') {
                foreach (config('menu') as $menuKey => $menu) {
                    $roleDefaults[$menuKey] = true;
                    foreach (($menu['submenus'] ?? []) as $subKey => $sub) {
                        $roleDefaults[$menuKey . '.' . $subKey] = true;
                    }
                }
            } else {
                $dbPerms = MenuPermissionService::getPermissionsForRole($role->id);
                if (!empty($dbPerms)) {
                    $roleDefaults = $dbPerms;
                } else {
                    $roleDefaults = MenuPermissionService::getDefaultPermMap($role->name);
                }
            }
        }

        $overrides = [];
        foreach ($allMenuKeys as $key) {
            $checked     = in_array($key, $checkedMenus);
            $roleDefault = $roleDefaults[$key] ?? false;

            if ($checked && !$roleDefault) {
                // User checked something the role doesn't have by default → grant
                $overrides[$key] = 'grant';
            } elseif (!$checked && $roleDefault) {
                // User unchecked something the role normally allows → revoke
                $overrides[$key] = 'revoke';
            }
            // If both match (checked+has, or unchecked+hasn't) → no override needed
        }

        MenuPermissionService::saveUserOverrides($employeeUser->id, $overrides);

        return response()->json(['success' => true, 'message' => 'Permissions saved successfully!']);
    }

    public function saveEmployeePaymentMethodPerms(Request $request, Employee $employee)
    {
        $employee->load('user');
        if (!$employee->user) {
            return back()->with('error', 'Employee has no login account.');
        }

        $userId = $employee->user->id;
        $empCompanyId = $employee->user->company_id ?? $employee->company_id ?? 1;
        $allowed = $request->input('payment_methods', []);
        $allMethods = PaymentMethod::where('is_active', true)
            ->when($empCompanyId, fn($q) => $q->where('company_id', $empCompanyId))
            ->pluck('id');

        \DB::table('payment_method_perms')->where('user_id', $userId)->whereNull('role_id')->delete();

        if (!empty($allowed) && $allowed[0] !== 'all') {
            foreach ($allowed as $methodId) {
                if ($allMethods->contains((int)$methodId)) {
                    \DB::table('payment_method_perms')->insert([
                        'user_id'           => $userId,
                        'role_id'           => null,
                        'payment_method_id' => (int)$methodId,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }
        }

        return back()->with('success', 'Payment method permissions updated.');
    }

    public function updateEmployeeRole(Request $request, Employee $employee)
    {
        $authUser = auth()->user();
        if (!$authUser->hasRole('Super Admin') && $authUser->user_type !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $employeeUser = $employee->user;
        if (!$employeeUser) {
            return response()->json(['success' => false, 'error' => 'Employee has no login account'], 422);
        }

        $roleId = $request->input('role_id');
        if (!$roleId) {
            $employeeUser->syncRoles([]);
            return response()->json(['success' => true, 'message' => 'Role removed.']);
        }

        $role = Role::find($roleId);
        if (!$role || $role->name === 'Super Admin') {
            return response()->json(['success' => false, 'error' => 'Invalid role'], 422);
        }

        $roleUserTypeMap = [
            'admin' => 'admin', 'technician' => 'technician',
            'billman' => 'employee', 'manager' => 'employee',
            'supervisor' => 'employee', 'collection agent' => 'employee',
            'network operator' => 'employee', 'support staff' => 'employee',
        ];
        $newUserType = $roleUserTypeMap[strtolower($role->name)] ?? 'employee';

        $employeeUser->syncRoles([$role]);
        $employeeUser->update(['user_type' => $newUserType]);

        MenuPermissionService::clearUserCache($employeeUser->id);

        return response()->json(['success' => true, 'message' => 'Role updated to ' . $role->name]);
    }

    public function createEmployeeAccount(Request $request, Employee $employee)
    {
        $authUser = auth()->user();
        if (!$authUser->hasRole('Super Admin') && $authUser->user_type !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        if ($employee->user_id) {
            return response()->json(['success' => false, 'error' => 'Employee already has a login account'], 422);
        }

        $request->validate([
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        DB::beginTransaction();
        try {
            $roleUserTypeMap = [
                'admin'             => 'admin',
                'technician'        => 'technician',
                'billman'           => 'billman',
                'manager'           => 'manager',
                'reseller'          => 'reseller',
                'sub-reseller'      => 'sub_reseller',
                'card-distributor'  => 'card_distributor',
            ];

            $role = $request->role_id ? Role::find($request->role_id) : null;
            $userType = $role ? ($roleUserTypeMap[strtolower($role->name)] ?? 'employee') : 'employee';

            $resolvedCompanyId = $authUser->company_id
                ?? \DB::table('companies')->orderBy('id')->value('id')
                ?? 1;

            $user = User::create([
                'name'              => $employee->name,
                'email'             => $request->email,
                'password'          => Hash::make($request->password),
                'user_type'         => $userType,
                'is_active'         => true,
                'company_id'        => $resolvedCompanyId,
                'email_verified_at' => now(),
            ]);

            if ($role && $role->name !== 'Super Admin') {
                $user->assignRole($role);
            }

            $employee->update(['user_id' => $user->id]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Login account created successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function attendance(Request $request)
    {
        $date = $request->date ?? today()->format('Y-m-d');
        $companyId = auth()->user()->company_id;
        $employees = Employee::where('status', 'active')
            ->where('company_id', $companyId)
            ->with('department', 'designation')
            ->orderBy('name')
            ->get();
        $attendances = Attendance::whereDate('attendance_date', $date)
            ->where('company_id', $companyId)
            ->get()->keyBy('employee_id');

        $parsedDate = \Carbon\Carbon::parse($date);
        $month = $parsedDate->month;
        $year = $parsedDate->year;

        $monthlyRecords = Attendance::whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $monthlySummary = [];
        foreach ($employees as $emp) {
            $records = $monthlyRecords[$emp->id] ?? collect();
            $monthlySummary[$emp->id] = [
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'late' => $records->where('status', 'late')->count(),
                'leave' => $records->where('status', 'leave')->count(),
                'half_day' => $records->where('status', 'half_day')->count(),
                'remote' => $records->where('status', 'remote')->count(),
                'total_hours' => $records->sum('total_work_hours'),
                'total_overtime' => $records->sum('overtime_hours'),
                'total_late_min' => $records->sum('late_minutes'),
            ];
        }

        $todaySummary = [
            'total' => $employees->count(),
            'present' => $attendances->where('status', 'present')->count() + $attendances->where('status', 'late')->count() + $attendances->where('status', 'remote')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
            'not_marked' => $employees->count() - $attendances->count(),
        ];

        $userIds = $employees->pluck('user_id')->filter()->values();
        $selfieRecords = EmployeeSelfieAttendance::whereDate('attendance_date', $date)
            ->whereIn('user_id', $userIds)
            ->orderBy('check_in_time', 'asc')
            ->get()
            ->groupBy('user_id');

        return view('admin.hrm.attendance', compact('employees', 'attendances', 'date', 'monthlySummary', 'todaySummary', 'selfieRecords'));
    }

    public function storeAttendance(Request $request)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'in:present,absent,late,half_day,leave,remote,holiday,weekend',
        ]);

        $standardWorkHours = config('hrm.standard_work_hours', 8);
        $workableStatuses = ['present', 'late', 'remote', 'half_day'];

        foreach ($request->attendance as $employeeId => $status) {
            $checkIn = $request->check_in[$employeeId] ?? null;
            $checkOut = $request->check_out[$employeeId] ?? null;
            $lunchStart = $request->lunch_start[$employeeId] ?? null;
            $lunchEnd = $request->lunch_end[$employeeId] ?? null;
            $remarks = $request->remarks[$employeeId] ?? null;

            $totalWorkHours = 0;
            $overtimeHours = 0;
            $lateMinutes = 0;
            $earlyLeaveMinutes = 0;

            if (!in_array($status, $workableStatuses)) {
                $checkIn = null;
                $checkOut = null;
                $lunchStart = null;
                $lunchEnd = null;
            }

            if ($checkIn && $checkOut && in_array($status, $workableStatuses)) {
                $in = \Carbon\Carbon::parse($checkIn);
                $out = \Carbon\Carbon::parse($checkOut);

                if ($out->lte($in)) {
                    $out = $in->copy();
                }

                $totalMinutes = $in->diffInMinutes($out);

                if ($lunchStart && $lunchEnd) {
                    $ls = \Carbon\Carbon::parse($lunchStart);
                    $le = \Carbon\Carbon::parse($lunchEnd);
                    if ($le->gt($ls)) {
                        $totalMinutes -= $ls->diffInMinutes($le);
                    } else {
                        $lunchStart = null;
                        $lunchEnd = null;
                    }
                }

                $totalWorkHours = round(max(0, $totalMinutes) / 60, 2);
                $overtimeHours = max(0, round($totalWorkHours - $standardWorkHours, 2));

                $scheduleStart = config('hrm.work_start', '09:00');
                $scheduleEnd = config('hrm.work_end', '17:00');
                $scheduledStart = \Carbon\Carbon::parse($scheduleStart);
                if ($in->gt($scheduledStart)) {
                    $lateMinutes = $scheduledStart->diffInMinutes($in);
                }

                $scheduledEnd = \Carbon\Carbon::parse($scheduleEnd);
                if ($out->lt($scheduledEnd)) {
                    $earlyLeaveMinutes = $out->diffInMinutes($scheduledEnd);
                }
            }

            Attendance::updateOrCreate(
                ['employee_id' => $employeeId, 'attendance_date' => $request->attendance_date],
                [
                    'company_id' => auth()->user()->company_id,
                    'status' => $status,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'lunch_start' => $lunchStart,
                    'lunch_end' => $lunchEnd,
                    'total_work_hours' => $totalWorkHours,
                    'overtime_hours' => $overtimeHours,
                    'late_minutes' => $lateMinutes,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                    'remarks' => $remarks,
                ]
            );
        }

        return back()->with('success', 'Attendance saved successfully.');
    }

    public function attendanceReport(Request $request)
    {
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        $employeeId = $request->employee_id;

        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        $query = Attendance::with('employee')
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $records = $query->orderBy('attendance_date')->get();

        $reportData = [];
        $groupedByEmployee = $records->groupBy('employee_id');
        foreach ($groupedByEmployee as $empId => $empRecords) {
            $emp = $employees->find($empId);
            if (!$emp) continue;

            $fullDays = $empRecords->whereIn('status', ['present', 'late', 'remote'])->count();
            $halfDayCount = $empRecords->where('status', 'half_day')->count();
            $effectiveDays = $fullDays + ($halfDayCount * 0.5);

            $reportData[$empId] = [
                'employee' => $emp,
                'records' => $empRecords,
                'present' => $fullDays,
                'effective_days' => $effectiveDays,
                'absent' => $empRecords->where('status', 'absent')->count(),
                'late' => $empRecords->where('status', 'late')->count(),
                'leave' => $empRecords->where('status', 'leave')->count(),
                'half_day' => $halfDayCount,
                'remote' => $empRecords->where('status', 'remote')->count(),
                'total_hours' => $empRecords->sum('total_work_hours'),
                'overtime_hours' => $empRecords->sum('overtime_hours'),
                'total_late_min' => $empRecords->sum('late_minutes'),
            ];
        }

        $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;

        return view('admin.hrm.attendance-report', compact('reportData', 'employees', 'month', 'year', 'employeeId', 'daysInMonth'));
    }

    public function salary(Request $request)
    {
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        $salaries = Salary::with(['employee.designation'])
            ->where('month', $month)
            ->where('year', $year)
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $employees = Employee::where('status', 'active')->get();
        return view('admin.hrm.salary', compact('salaries', 'employees', 'month', 'year'));
    }
    
    public function processSalary(Request $request)
    {
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        $employees = Employee::where('status', 'active')->get();

        $allAttendance = Attendance::whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $processed = 0;
        foreach ($employees as $employee) {
            $records = $allAttendance[$employee->id] ?? collect();

            $fullDays = $records->whereIn('status', ['present', 'late', 'remote'])->count();
            $halfDayCount = $records->where('status', 'half_day')->count();
            $workingDays = $fullDays + ($halfDayCount * 0.5);
            $totalOvertimeHours = $records->sum(fn($r) => (float)($r->overtime_hours ?? 0));

            $existing = Salary::where('employee_id', $employee->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            $allowances = $existing ? ($existing->allowances ?? 0) : 0;
            $bonus = $existing ? ($existing->bonus ?? 0) : 0;
            $deductions = $existing ? ($existing->deductions ?? 0) : 0;
            $advanceDeduction = $existing ? ($existing->advance_deduction ?? 0) : 0;
            $existingStatus = $existing ? ($existing->status ?? 'pending') : 'pending';

            $basicSalary = (float) ($employee->basic_salary ?? 0);
            $netSalary = $basicSalary + $allowances + $bonus - $deductions - $advanceDeduction;

            Salary::updateOrCreate(
                ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
                [
                    'basic_salary' => $basicSalary,
                    'overtime' => $totalOvertimeHours,
                    'allowances' => $allowances,
                    'bonus' => $bonus,
                    'deductions' => $deductions,
                    'advance_deduction' => $advanceDeduction,
                    'net_salary' => $netSalary,
                    'status' => $existingStatus,
                ]
            );
            $processed++;
        }

        return back()->with('success', "Salaries calculated for {$processed} employees.");
    }

    public function editSalary(Salary $salary)
    {
        $salary->load('employee');
        return view('admin.hrm.salary-edit', compact('salary'));
    }

    public function updateSalary(Request $request, Salary $salary)
    {
        $request->validate([
            'basic_salary' => 'required|numeric|min:0',
            'net_salary' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,partial',
        ]);

        $basic = (float) $request->basic_salary;
        $allowances = (float) ($request->allowances ?? 0);
        $bonus = (float) ($request->bonus ?? 0);
        $deductions = (float) ($request->deductions ?? 0);
        $advanceDeduction = (float) ($request->advance_deduction ?? 0);
        $netSalary = $basic + $allowances + $bonus - $deductions - $advanceDeduction;

        $salary->update([
            'basic_salary' => $basic,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'bonus' => $bonus,
            'advance_deduction' => $advanceDeduction,
            'net_salary' => $netSalary,
            'status' => $request->status,
            'payment_date' => $request->status == 'paid' ? now() : null,
        ]);

        return redirect()->route('admin.hrm.salary')->with('success', 'Salary updated successfully.');
    }

    public function destroySalary(Salary $salary)
    {
        $salary->delete();
        return redirect()->route('admin.hrm.salary')->with('success', 'Salary record deleted successfully.');
    }
    
    public function showEmployee(Employee $employee)
    {
        $employee->load(['department', 'designation', 'user', 'attendances' => function($q) {
            $q->orderBy('attendance_date', 'desc')->limit(30);
        }]);
        $salaryHistory = Salary::where('employee_id', $employee->id)
            ->orderByDesc('year')->orderByDesc('month')
            ->limit(12)
            ->get();
        return view('admin.hrm.employee-show', compact('employee', 'salaryHistory'));
    }
    
    public function employeeCv(Employee $employee)
    {
        $employee->load(['department', 'designation']);
        $pdf = Pdf::loadView('admin.hrm.employee-cv', compact('employee'));
        return $pdf->download($employee->name . '-CV.pdf');
    }
    
    public function departments()
    {
        $departments = Department::withCount('employees')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        return view('admin.hrm.departments', compact('departments'));
    }
    
    public function storeDepartment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        Department::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);
        
        return back()->with('success', 'Department created.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Department updated.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete department with assigned employees.');
        }

        $department->delete();
        return back()->with('success', 'Department deleted.');
    }

    public function designations()
    {
        $designations = Designation::withCount('employees')->with('department')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        $departments = Department::withoutGlobalScopes()->where('is_active', true)->where('company_id', auth()->user()->company_id ?? 1)->orderBy('name')->get();
        return view('admin.hrm.designations', compact('designations', 'departments'));
    }

    public function storeDesignation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        Designation::create([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Designation created.');
    }

    public function updateDesignation(Request $request, Designation $designation)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $designation->update([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Designation updated.');
    }

    public function destroyDesignation(Designation $designation)
    {
        if ($designation->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete designation with assigned employees.');
        }

        $designation->delete();
        return back()->with('success', 'Designation deleted.');
    }

    public function leave(Request $request)
    {
        $query = \App\Models\Leave::with(['employee', 'approvedBy']);

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20)->appends($request->query());
        $employees = Employee::where('status', 'active')->get();
        return view('admin.hrm.leave', compact('leaves', 'employees'));
    }
    
    public function storeLeave(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $days = $startDate->diffInDays($endDate) + 1;

        \App\Models\Leave::create([
            'employee_id' => $request->employee_id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $days,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Leave request submitted.');
    }

    public function approveLeave(\App\Models\Leave $leave)
    {
        $leave->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);
        return back()->with('success', 'Leave approved successfully.');
    }

    public function rejectLeave(Request $request, \App\Models\Leave $leave)
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'admin_remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Leave rejected.');
    }
    
    public function staffDues()
    {
        $companyId = auth()->user()->company_id;

        // Use StaffDue model as authoritative source (scoped by TenantAware)
        $allDues = \App\Models\StaffDue::with('user')
            ->orderByDesc('current_due')
            ->get();

        $staffDues = [];
        $trackedIds = [];
        foreach ($allDues as $due) {
            $user = $due->user;
            if (!$user) continue;
            $trackedIds[] = $user->id;
            $staffDues[] = [
                'id'              => $user->id,
                'name'            => $user->name,
                'user_type'       => $user->user_type ?? 'staff',
                'phone'           => $user->phone ?? '',
                'total_collected' => (float) $due->total_collected,
                'receipted'       => (float) $due->total_submitted,
                'current_due'     => (float) $due->current_due,
            ];
        }

        // Also show collectors not yet in staff_dues table but with unsubmitted payments
        $untracked = \App\Models\Payment::select('collected_by')
            ->whereNotNull('collected_by')
            ->where('submitted_to_admin', false)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereNotIn('collected_by', $trackedIds)
            ->distinct()->pluck('collected_by');

        foreach ($untracked as $uid) {
            $user = \App\Models\User::find($uid);
            if (!$user) continue;
            $due = \App\Models\Payment::where('collected_by', $uid)
                ->where('submitted_to_admin', false)
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->sum('amount');
            if ($due <= 0) continue;
            $staffDues[] = [
                'id'              => $user->id,
                'name'            => $user->name,
                'user_type'       => $user->user_type ?? 'staff',
                'phone'           => $user->phone ?? '',
                'total_collected' => (float) $due,
                'receipted'       => 0,
                'current_due'     => (float) $due,
            ];
        }

        usort($staffDues, fn($a, $b) => $b['current_due'] <=> $a['current_due']);

        // Recent receipts: use StaffSubmission (the authoritative receipt log)
        $receipts = \App\Models\StaffSubmission::with(['staff', 'receiver'])
            ->where('status', 'received')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($s) => (object)[
                'staff_name'   => $s->staff->name ?? 'Unknown',
                'receipt_no'   => $s->submission_no,
                'amount'       => $s->amount,
                'receipt_date' => $s->submission_date,
                'receiver_name'=> $s->receiver->name ?? 'Admin',
            ]);

        return view('admin.hrm.staff-dues', compact('staffDues', 'receipts'));
    }
    
    public function receiveDues(\App\Models\User $user)
    {
        // Use StaffDue model as authoritative source for accurate dues
        $staffDue = \App\Models\StaffDue::where('user_id', $user->id)->first();
        $totalCollected = $staffDue ? (float) $staffDue->total_collected : 0;
        $receipted      = $staffDue ? (float) $staffDue->total_submitted : 0;
        $currentDue     = $staffDue ? (float) $staffDue->current_due    : 0;
        
        $recentPayments = \App\Models\Payment::where('collected_by', $user->id)
            ->with('client')
            ->orderByDesc('payment_date')
            ->limit(10)
            ->get();
        
        return view('admin.hrm.receive-dues', compact('user', 'totalCollected', 'receipted', 'currentDue', 'recentPayments'));
    }
    
    public function storeReceipt(Request $request, \App\Models\User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'receipt_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);
        
        $receiptNo = 'RCP-' . date('Ymd') . '-' . str_pad(\Illuminate\Support\Facades\DB::table('staff_receipts')->max('id') + 1, 5, '0', STR_PAD_LEFT);
        
        \Illuminate\Support\Facades\DB::table('staff_receipts')->insert([
            'receipt_no' => $receiptNo,
            'staff_id' => $user->id,
            'received_by' => auth()->id(),
            'amount' => $request->amount,
            'receipt_date' => $request->receipt_date,
            'payment_method' => $request->payment_method,
            'remarks' => $request->remarks,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('admin.hrm.staff-dues')->with('success', "Receipt of ৳{$request->amount} received from {$user->name}.");
    }
}
