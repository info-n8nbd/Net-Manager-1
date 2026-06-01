<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Zone;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TechnicianAssignment;
use App\Models\MenuPermission;
use App\Services\MenuPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TechnicianController extends Controller
{
    public function index()
    {
        $techRoles = array_filter(['technician', 'Field Technician', 'Network Engineer'],
            fn($r) => \Spatie\Permission\Models\Role::where('name', $r)->where('guard_name', 'web')->exists()
        );
        $technicians = empty($techRoles)
            ? User::whereHas('roles', fn($q) => $q->whereIn('name', ['technician', 'Field Technician', 'Network Engineer']))->with(['employee'])->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20)
            : User::role(array_values($techRoles))->with(['employee'])->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        
        $zones = Zone::where('is_active', true)->orderBy('name')->get();
        $departments = Department::withoutGlobalScopes()->where('is_active', true)->where('company_id', auth()->user()->company_id ?? 1)->orderBy('name')->get();
        
        return view('admin.hrm.technicians', compact('technicians', 'zones', 'departments'));
    }

    private function getMenuPermMap(int $roleId): array
    {
        $perms = MenuPermission::where('role_id', $roleId)->get();
        $map = [];
        foreach ($perms as $perm) {
            $key = $perm->submenu_key ? $perm->menu_key . '.' . $perm->submenu_key : $perm->menu_key;
            $map[$key] = $perm->allowed;
        }
        return $map;
    }

    private function saveMenuPermissions(int $roleId, array $menuPerms): void
    {
        $menus = config('menu');
        foreach ($menus as $menuKey => $menu) {
            $menuAllowed = in_array($menuKey, $menuPerms);
            MenuPermission::updateOrCreate(
                ['role_id' => $roleId, 'menu_key' => $menuKey, 'submenu_key' => null],
                ['allowed' => $menuAllowed]
            );
            if (!empty($menu['submenus'])) {
                foreach ($menu['submenus'] as $subKey => $sub) {
                    $subAllowed = in_array($menuKey . '.' . $subKey, $menuPerms);
                    MenuPermission::updateOrCreate(
                        ['role_id' => $roleId, 'menu_key' => $menuKey, 'submenu_key' => $subKey],
                        ['allowed' => $subAllowed]
                    );
                }
            }
        }
        MenuPermissionService::clearCache($roleId);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'exists:zones,id',
            'nid' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:5120',
            'nid_photo_front' => 'nullable|image|max:5120',
            'nid_photo_back' => 'nullable|image|max:5120',
            'cv_file' => 'nullable|mimes:pdf,doc,docx|max:5120',
        ]);
        
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_type' => 'technician',
                'is_active' => $request->has('is_active'),
                'company_id' => auth()->user()->company_id,
            ]);
            
            // Assign Spatie role for RBAC compatibility
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $technicianRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'technician', 'guard_name' => 'web']);
                $user->assignRole($technicianRole);
                
                // Auto-assign all technician permissions
                $defaultPermissions = [
                    'technician.dashboard',
                    'technician.clients',
                    'technician.due_report',
                    'technician.collection',
                    'technician.tickets',
                    'technician.new_connection',
                ];
                
                // Create permissions if they don't exist and assign them
                foreach ($defaultPermissions as $permName) {
                    \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                }
                $user->syncPermissions($defaultPermissions);
            }
            
            $employeeData = [
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'nid' => $request->nid,
                'department_id' => $request->department_id,
                'joining_date' => $request->joining_date ?? now(),
                'basic_salary' => $request->basic_salary ?? 0,
                'status' => $request->has('is_active') ? 'active' : 'inactive',
                'zone_ids' => array_map('intval', $request->zone_ids),
            ];
            
            // Handle file uploads
            if ($request->hasFile('photo')) {
                $employeeData['photo'] = $request->file('photo')->store('employees/photos', 'public');
            }
            if ($request->hasFile('nid_photo_front')) {
                $employeeData['nid_photo_front'] = $request->file('nid_photo_front')->store('employees/nid', 'public');
            }
            if ($request->hasFile('nid_photo_back')) {
                $employeeData['nid_photo_back'] = $request->file('nid_photo_back')->store('employees/nid', 'public');
            }
            if ($request->hasFile('cv_file')) {
                $employeeData['cv_file'] = $request->file('cv_file')->store('employees/cv', 'public');
            }
            
            Employee::create($employeeData);

            if ($request->has('menu_permissions') && auth()->user()->hasRole('Super Admin')) {
                $technicianRole = \Spatie\Permission\Models\Role::where('name', 'technician')->first();
                if ($technicianRole) {
                    $this->saveMenuPermissions($technicianRole->id, $request->input('menu_permissions', []));
                }
            }
        });
        
        return back()->with('success', 'Technician account created successfully.');
    }
    
    public function edit(User $technician)
    {
        $technician->load('employee');
        // Use technician's company zones (not admin's) to prevent cross-tenant zone mismatch
        $techCompanyId = $technician->company_id ?? auth()->user()->company_id ?? 1;
        $zones = Zone::withoutGlobalScopes()->where('company_id', $techCompanyId)->where('is_active', true)->orderBy('name')->get();
        $departments = Department::withoutGlobalScopes()->where('is_active', true)->where('company_id', $techCompanyId)->orderBy('name')->get();
        
        $menuPermMap = [];
        $userEffectivePerms = [];
        $techRole = \Spatie\Permission\Models\Role::where('name', 'technician')->first();
        if ($techRole) {
            $menuPermMap = $this->getMenuPermMap($techRole->id);
        }
        $userEffectivePerms = \App\Services\MenuPermissionService::getEffectivePermMap($technician);
        
        $employee = $technician->employee;
        
        return view('admin.hrm.technician-edit', compact('technician', 'zones', 'departments', 'menuPermMap', 'userEffectivePerms', 'employee'));
    }
    
    public function update(Request $request, User $technician)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $technician->id,
            'phone' => 'required|string|max:20',
            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'exists:zones,id',
            'nid' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:5120',
            'nid_photo_front' => 'nullable|image|max:5120',
            'nid_photo_back' => 'nullable|image|max:5120',
            'cv_file' => 'nullable|mimes:pdf,doc,docx|max:5120',
        ]);
        
        DB::transaction(function () use ($request, $technician) {
            $technician->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'is_active' => $request->has('is_active'),
            ]);
            
            if ($request->password) {
                $technician->update(['password' => Hash::make($request->password)]);
            }
            
            $employee = Employee::withoutGlobalScopes()->where('user_id', $technician->id)->first();
            $employeeData = [
                'user_id'       => $technician->id,
                'name'          => $request->name,
                'email'         => $request->email,
                'phone'         => $request->phone,
                'address'       => $request->address,
                'nid'           => $request->nid,
                'department_id' => $request->department_id,
                'joining_date'  => $request->joining_date ?? ($employee?->joining_date ?? now()->toDateString()),
                'basic_salary'  => $request->basic_salary ?? 0,
                'status'        => $request->has('is_active') ? 'active' : 'inactive',
                'zone_ids'      => array_map('intval', $request->zone_ids),
                'company_id'    => $technician->company_id ?? auth()->user()->company_id ?? 1,
            ];

            // Handle file uploads
            if ($request->hasFile('photo')) {
                $employeeData['photo'] = $request->file('photo')->store('employees/photos', 'public');
            }
            if ($request->hasFile('nid_photo_front')) {
                $employeeData['nid_photo_front'] = $request->file('nid_photo_front')->store('employees/nid', 'public');
            }
            if ($request->hasFile('nid_photo_back')) {
                $employeeData['nid_photo_back'] = $request->file('nid_photo_back')->store('employees/nid', 'public');
            }
            if ($request->hasFile('cv_file')) {
                $employeeData['cv_file'] = $request->file('cv_file')->store('employees/cv', 'public');
            }

            if ($employee) {
                $employee->updateQuietly($employeeData);
            } else {
                // Auto-create Employee record if it's missing
                Employee::create($employeeData);
            }
            
            $permissions = $request->input('permissions', []);
            
            foreach ($permissions as $permName) {
                \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }
            
            $technician->syncPermissions($permissions);

            if ($request->has('menu_permissions') && auth()->user()->hasRole('Super Admin')) {
                $techRole = \Spatie\Permission\Models\Role::where('name', 'technician')->first();
                if ($techRole) {
                    $this->saveMenuPermissions($techRole->id, $request->input('menu_permissions', []));
                }
            }

            if ($request->has('user_menu_overrides') && auth()->user()->hasRole('Super Admin')) {
                $overrides = $request->input('user_menu_overrides', []);
                \App\Services\MenuPermissionService::saveUserOverrides($technician->id, $overrides);
            }
        });
        
        return redirect()->route('admin.hrm.technicians')->with('success', 'Technician updated successfully.');
    }
    
    public function destroy(User $technician)
    {
        if ($technician->employee) {
            $technician->employee->delete();
        }
        $technician->delete();
        
        return back()->with('success', 'Technician deleted successfully.');
    }
    
    public function show(User $technician)
    {
        $technician->load(['employee']);
        
        $assignedZones = [];
        if ($technician->employee && $technician->employee->zone_ids) {
            $assignedZones = Zone::whereIn('id', $technician->employee->zone_ids)->get();
        }
        
        $thisMonthCollection = Payment::where('collected_by', $technician->id)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');
        
        $totalCollection = Payment::where('collected_by', $technician->id)->sum('amount');
        
        $completedTickets = TechnicianAssignment::where('technician_id', $technician->id)
            ->where('status', 'completed')
            ->count();
        
        $pendingTickets = TechnicianAssignment::where('technician_id', $technician->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->count();
        
        $recentPayments = Payment::with(['client'])
            ->where('collected_by', $technician->id)
            ->latest('payment_date')
            ->limit(10)
            ->get();
        
        $recentTickets = Ticket::with(['client', 'category'])
            ->where('technician_id', $technician->id)
            ->latest()
            ->limit(10)
            ->get();
        
        return view('admin.hrm.technician-show', compact(
            'technician', 'assignedZones', 'thisMonthCollection', 'totalCollection',
            'completedTickets', 'pendingTickets', 'recentPayments', 'recentTickets'
        ));
    }
    
    public function performanceReport(Request $request)
    {
        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : now()->endOfMonth();
        
        $techRoles = array_filter(['technician', 'Field Technician', 'Network Engineer'],
            fn($r) => \Spatie\Permission\Models\Role::where('name', $r)->where('guard_name', 'web')->exists()
        );
        $technicians = (empty($techRoles)
            ? User::whereHas('roles', fn($q) => $q->whereIn('name', ['technician', 'Field Technician', 'Network Engineer']))->with(['employee'])
            : User::role(array_values($techRoles))->with(['employee'])
        )->get()->map(function ($tech) use ($startDate, $endDate) {
                $tech->collection = Payment::where('collected_by', $tech->id)
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->sum('amount');
                
                $tech->tickets_completed = TechnicianAssignment::where('technician_id', $tech->id)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$startDate, $endDate])
                    ->count();
                
                $tech->tickets_assigned = TechnicianAssignment::where('technician_id', $tech->id)
                    ->whereBetween('assigned_at', [$startDate, $endDate])
                    ->count();
                
                $avgTime = TechnicianAssignment::where('technician_id', $tech->id)
                    ->where('status', 'completed')
                    ->whereNotNull('started_at')
                    ->whereNotNull('completed_at')
                    ->whereBetween('completed_at', [$startDate, $endDate])
                    ->get()
                    ->avg(function ($a) {
                        return $a->started_at->diffInMinutes($a->completed_at);
                    });
                
                $tech->avg_resolution_time = round($avgTime ?? 0);
                
                return $tech;
            });
        
        return view('admin.hrm.technician-performance', compact('technicians', 'startDate', 'endDate'));
    }
}
