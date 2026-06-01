@extends('layouts.admin')

@section('title', 'Employees')
@section('breadcrumb')
<li class="breadcrumb-item active">Employees</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <form method="GET" action="{{ route('admin.hrm.employees') }}" class="card card-body p-2">
            <div class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, phone, email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="role_filter" class="form-control form-control-sm">
                        <option value="">All Roles</option>
                        @foreach($allRoles ?? [] as $r)
                        <option value="{{ $r->name }}" {{ request('role_filter') == $r->name ? 'selected' : '' }}>{{ ucfirst($r->name) }}</option>
                        @endforeach
                        <option value="no_account" {{ request('role_filter') == 'no_account' ? 'selected' : '' }}>No Login Account</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="status_filter" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status_filter') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status_filter') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('admin.hrm.employees') }}" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header p-2">
                <h3 class="card-title" style="font-size:14px;">
                    Employees
                    @if(request('role_filter'))
                        <span class="badge badge-info ml-1">{{ ucfirst(request('role_filter')) }}</span>
                    @endif
                    <span class="badge badge-secondary ml-1">{{ $employees->total() }} total</span>
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead><tr><th>Name</th><th>Phone</th><th>Department</th><th>Role</th><th>Zones</th><th>Docs</th><th>Salary (&#x09F3;)</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($employees as $emp)
                        <tr>
                            <td>
                                {{ $emp->name }}
                                @if($emp->user_id)
                                @if($emp->user && !$emp->user->is_active)
                                <br><small class="text-danger"><i class="fas fa-ban"></i> Login Blocked</small>
                                @else
                                <br><small class="text-success"><i class="fas fa-check-circle"></i> Has Login</small>
                                @endif
                                @endif
                            </td>
                            <td>{{ $emp->phone }}</td>
                            <td>{{ $emp->department->name ?? 'N/A' }}</td>
                            <td>
                                @if($emp->user && $emp->user->roles->count())
                                    @foreach($emp->user->roles as $role)
                                    <span class="badge badge-{{ $role->name === 'Field Technician' ? 'warning' : ($role->name === 'Administrator' ? 'primary' : 'info') }}">{{ $role->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php $zoneIds = $emp->zone_ids ?? []; @endphp
                                @if(count($zoneIds))
                                    <small>{{ count($zoneIds) }} zone(s)</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($emp->photo)<i class="fas fa-user-circle text-primary" title="Photo"></i>@endif
                                @if($emp->nid_photo_front || $emp->nid_photo_back)<i class="fas fa-id-card text-success" title="NID"></i>@endif
                                @if($emp->cv_file)<i class="fas fa-file-pdf text-danger" title="CV"></i>@endif
                                @if(!$emp->photo && !$emp->nid_photo_front && !$emp->cv_file)<span class="text-muted">-</span>@endif
                            </td>
                            <td>{{ number_format($emp->basic_salary, 2) }}</td>
                            <td><span class="badge badge-{{ $emp->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($emp->status) }}</span></td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('admin.hrm.employees.show', $emp) }}" class="btn btn-xs btn-info" title="View"><i class="fas fa-eye"></i></a>
                                @if($__canAction('hrm', 'edit_employee'))
                                <a href="{{ route('admin.hrm.employees.edit', $emp) }}" class="btn btn-xs btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                @endif
                                @if($emp->user_id)
                                <form action="{{ route('admin.impersonate', $emp->user_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Login as {{ addslashes($emp->name) }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-success" title="Login As"><i class="fas fa-sign-in-alt"></i></button>
                                </form>
                                @endif
                                @if($__canAction('hrm', 'delete_employee'))
                                <form action="{{ route('admin.hrm.employees.destroy', $emp) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this employee{{ $emp->user_id ? ' and their login account' : '' }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center">No employees found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                <div class="d-md-none">
                    @forelse($employees as $emp)
                    <div style="border-bottom:1px solid #e2e8f0; padding:10px 12px;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <strong style="font-size:13px;">{{ $emp->name }}</strong>
                                @if($emp->user_id)
                                @if($emp->user && !$emp->user->is_active)<small class="text-danger ml-1"><i class="fas fa-ban"></i></small>@else<small class="text-success ml-1"><i class="fas fa-check-circle"></i></small>@endif
                                @endif
                                <div style="font-size:11px; color:#666;">{{ $emp->phone }} &middot; {{ $emp->department->name ?? 'N/A' }}</div>
                            </div>
                            <span class="badge badge-{{ $emp->status == 'active' ? 'success' : 'danger' }}" style="font-size:10px;">{{ ucfirst($emp->status) }}</span>
                        </div>
                        <div style="font-size:11px; color:#555; margin-bottom:6px;">
                            @if($emp->user && $emp->user->roles->count())
                                @foreach($emp->user->roles as $role)
                                <span class="badge badge-{{ $role->name === 'Field Technician' ? 'warning' : ($role->name === 'Administrator' ? 'primary' : 'info') }}" style="font-size:9px;">{{ $role->name }}</span>
                                @endforeach
                            @endif
                            <span class="text-muted">&#x09F3;{{ number_format($emp->basic_salary, 0) }}</span>
                        </div>
                        <div class="d-flex flex-wrap" style="gap:4px;">
                            <a href="{{ route('admin.hrm.employees.show', $emp) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i> View</a>
                            @if($__canAction('hrm', 'edit_employee'))
                            <a href="{{ route('admin.hrm.employees.edit', $emp) }}" class="btn btn-xs btn-primary"><i class="fas fa-edit"></i> Edit</a>
                            @endif
                            @if($emp->user_id)
                            <form action="{{ route('admin.impersonate', $emp->user_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Login as {{ addslashes($emp->name) }}?')">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-success"><i class="fas fa-sign-in-alt"></i> Login</button>
                            </form>
                            @endif
                            @if($__canAction('hrm', 'delete_employee'))
                            <form action="{{ route('admin.hrm.employees.destroy', $emp) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center p-3 text-muted">No employees found</div>
                    @endforelse
                </div>

                <div class="p-2"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $employees->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script></div>
            </div>
        </div>
    </div>
    @if($__canAction('hrm', 'add_employee'))
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add Employee</h3></div>
            <form action="{{ route('admin.hrm.employees.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
                    <div class="form-group"><label>Phone *</label><input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}"></div>
                    <div class="form-group"><label>NID Number</label><input type="text" name="nid" class="form-control" value="{{ old('nid') }}" placeholder="National ID Number"></div>
                    <div class="form-group employee-fields"><label>Department</label>
                        <select name="department_id" id="add_department_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group employee-fields"><label>Designation</label>
                        <select name="designation_id" id="add_designation_id" class="form-control">
                            <option value="">Select Department First</option>
                        </select>
                    </div>
                    <div class="form-group employee-fields"><label>Joining Date</label><input type="date" name="joining_date" class="form-control" value="{{ old('joining_date') }}"></div>
                    <div class="form-group employee-fields"><label>Basic Salary (&#x09F3;)</label><input type="number" name="basic_salary" class="form-control" step="0.01" value="{{ old('basic_salary') }}"></div>

                    <div class="form-group" id="zoneAssignGroup" style="display:none;">
                        <label>Assigned Zones *</label>

                        <input type="hidden" name="zone_ids" value="">
                        <select name="zone_ids[]" id="createEmpZoneHidden" multiple style="display:none;">
                            @foreach($zones ?? [] as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="card card-outline card-secondary mb-0">
                                    <div class="card-header p-2 text-center"><strong>Available Zones</strong></div>
                                    <div class="card-body p-0">
                                        <ul id="createEmpZoneAvailable" class="list-group list-group-flush create-emp-zone-list" style="min-height:100px;max-height:160px;overflow-y:auto;cursor:pointer;">
                                            @foreach($zones ?? [] as $zone)
                                                <li class="list-group-item list-group-item-action py-1 px-2" data-id="{{ $zone->id }}" data-name="{{ $zone->name }}">{{ $zone->name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center" style="gap:8px;">
                                <button type="button" id="createEmpBtnAdd" class="btn btn-sm btn-primary">&#x276F;</button>
                                <button type="button" id="createEmpBtnRemove" class="btn btn-sm btn-danger">&#x276E;</button>
                            </div>
                            <div class="col-md-5">
                                <div class="card card-outline card-primary mb-0">
                                    <div class="card-header p-2 text-center"><strong>Assigned Zones</strong></div>
                                    <div class="card-body p-0">
                                        <ul id="createEmpZoneAssigned" class="list-group list-group-flush create-emp-zone-list" style="min-height:100px;max-height:160px;overflow-y:auto;cursor:pointer;">
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Click to select, then use arrows. Double-click to move instantly.</small>
                    </div>

                    <hr>
                    <h6 class="text-info"><i class="fas fa-file-image mr-1"></i> Documents & Photos</h6>
                    <div class="form-group"><label>Employee Photo</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
                    <div class="form-group"><label>CV / Resume</label><input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx"></div>
                    <div class="form-group"><label>NID Photo (Front)</label><input type="file" name="nid_photo_front" class="form-control" accept="image/*"></div>
                    <div class="form-group"><label>NID Photo (Back)</label><input type="file" name="nid_photo_back" class="form-control" accept="image/*"></div>

                    <hr>
                    <h6 class="text-primary"><i class="fas fa-shield-alt mr-1"></i> System Access & Role</h6>
                    <div class="form-group">
                        <label>Role / Access Level <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-control" id="empRoleSelect">
                            <option value="">-- Select Role --</option>
                            @foreach($roles ?? [] as $role)
                                @if($role->name !== 'Super Admin' && $role->name !== 'client')
                                <option value="{{ $role->id }}" data-role-name="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                @endif
                            @endforeach
                        </select>
                        <small class="text-muted">Select the access level for this employee</small>
                    </div>
                    @if(auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('Administrator') || auth()->user()->hasRole('Admin Support'))
                    <div class="form-group">
                        <label>Create Login Account</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_account" name="create_account" value="1">
                            <label class="custom-control-label" for="create_account">Allow this employee to login to the system</label>
                        </div>
                    </div>
                    <div id="accountFields" style="display:none;">
                        <div class="form-group">
                            <label>Login Email *</label>
                            <input type="email" name="login_email" class="form-control" placeholder="employee@company.com" value="{{ old('login_email') }}">
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" class="form-control" placeholder="Min 6 characters">
                        </div>
                        @include('admin.partials.menu-access-control', ['showMenuAccess' => false, 'menuPermMap' => []])
                    </div>
                    @endif
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Add Employee</button></div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function createEmpSyncHidden() {
        $('#createEmpZoneAssigned li').each(function() {
            $('#createEmpZoneHidden option[value="' + $(this).data('id') + '"]').prop('selected', true);
        });
        $('#createEmpZoneAvailable li').each(function() {
            $('#createEmpZoneHidden option[value="' + $(this).data('id') + '"]').prop('selected', false);
        });
    }
    function createEmpMove(fromList, toList, isAssigning) {
        fromList.find('li.active').each(function() {
            var $li = $(this).removeClass('active');
            isAssigning ? $li.addClass('list-group-item-primary') : $li.removeClass('list-group-item-primary');
            var name = $li.data('name').toLowerCase();
            var inserted = false;
            toList.find('li').each(function() {
                if ($(this).data('name').toLowerCase() > name) { $li.insertBefore($(this)); inserted = true; return false; }
            });
            if (!inserted) toList.append($li);
        });
        createEmpSyncHidden();
    }
    $(document).on('click', '.create-emp-zone-list li', function() { $(this).toggleClass('active'); });
    $(document).on('dblclick', '#createEmpZoneAvailable li', function() { $(this).addClass('active'); createEmpMove($('#createEmpZoneAvailable'), $('#createEmpZoneAssigned'), true); });
    $(document).on('dblclick', '#createEmpZoneAssigned li', function() { $(this).addClass('active'); createEmpMove($('#createEmpZoneAssigned'), $('#createEmpZoneAvailable'), false); });
    $('#createEmpBtnAdd').on('click', function() { createEmpMove($('#createEmpZoneAvailable'), $('#createEmpZoneAssigned'), true); });
    $('#createEmpBtnRemove').on('click', function() { createEmpMove($('#createEmpZoneAssigned'), $('#createEmpZoneAvailable'), false); });

    // Create account toggle
    $('#create_account').on('change', function() {
        document.getElementById('accountFields').style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            var mac = document.getElementById('menuAccessCard');
            if (mac) mac.style.display = 'none';
        }
    });
    // Role select -> show zone group for Field Technician
    $('#empRoleSelect').on('change', function() {
    var mac = document.getElementById('menuAccessCard');
    if (mac) mac.style.display = this.value ? '' : 'none';
    var selectedOption = this.options[this.selectedIndex];
    var roleName = selectedOption ? selectedOption.getAttribute('data-role-name') : '';
    var zoneGroup = document.getElementById('zoneAssignGroup');
    if (zoneGroup) {
        zoneGroup.style.display = (roleName === 'Field Technician') ? 'block' : 'none';
    }
    if (roleName && typeof applyRoleDefaults === 'function') {
        applyRoleDefaults(roleName);
    }
    });

    $('#add_department_id').on('change', function() {
        var deptId = $(this).val();
        var $desigSelect = $('#add_designation_id');
        $desigSelect.html('<option value="">Loading...</option>');
        if (!deptId) {
            $desigSelect.html('<option value="">Select Department First</option>');
            return;
        }
        $.get('/admin/hrm/api/designations-by-department/' + deptId, function(data) {
            var html = '<option value="">Select</option>';
            data.forEach(function(item) {
                html += '<option value="' + item.id + '">' + item.name + '</option>';
            });
            if (data.length === 0) {
                html = '<option value="">No designations for this department</option>';
            }
            $desigSelect.html(html);
        }).fail(function() {
            $desigSelect.html('<option value="">Failed to load</option>');
        });
    });
});
</script>
@endpush
