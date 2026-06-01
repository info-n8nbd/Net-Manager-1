@extends('layouts.admin')

@section('title', 'Payment Methods')
@section('breadcrumb')
<li class="breadcrumb-item active">Payment Methods</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payment Methods</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display: none;" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($methods as $method)
                        <tr>
                            <td><input type="checkbox" name="bulk_ids[]" value="{{ $method->id }}" class="item-checkbox" onclick="updateSelection()" {{ ($method->payments_count ?? 0) > 0 ? 'disabled' : '' }}></td>
                            <td>{{ $method->name }}</td>
                            <td>{{ $method->code }}</td>
                            <td><span class="badge badge-{{ $method->is_active ? 'success' : 'danger' }}">{{ $method->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editMethodModal{{ $method->id }}"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSingle({{ $method->id }})"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No payment methods</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $methods->appends(request()->query())->links() }}
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
</script>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add Payment Method</h3></div>
            <form action="{{ route('admin.settings.payment-methods.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Code</label><input type="text" name="code" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" id="add_active" checked><label class="form-check-label" for="add_active">Active</label></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Add Method</button></div>
            </form>
        </div>
    </div>
</div>

{{-- Role Permission Matrix --}}
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-warning">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-user-shield mr-2 text-warning"></i>Role-level Payment Method Access</h3>
                <div class="card-tools"><small class="text-muted">Controls which methods technicians &amp; employees can use</small></div>
            </div>
            <form action="{{ route('admin.settings.payment-methods.role-perms') }}" method="POST">
                @csrf
                <div class="card-body p-0">
                    @if(session('success') && str_contains(session('success'), 'permissions'))
                    <div class="alert alert-success py-1 px-3 m-2">{{ session('success') }}</div>
                    @endif
                    <p class="text-muted small px-3 pt-2 mb-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Check the methods each role is allowed to use. Unchecked = denied. If a role has <strong>no rows saved</strong> (all unchecked and saved), it gets access to <strong>all active methods</strong>.
                        Individual employee overrides can be set from the <a href="{{ route('admin.hrm.employees') }}">HRM → Employee edit page</a>.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="min-width:130px;">Role</th>
                                    @foreach($allMethods as $method)
                                    <th class="text-center" style="min-width:90px; font-size:12px;">{{ $method->name }}</th>
                                    @endforeach
                                    <th class="text-center" style="min-width:70px; font-size:11px; color:#888;">Select All</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                @php
                                    $permsForRole = $rolePerms->get($role->id, collect())->pluck('payment_method_id')->toArray();
                                    $hasAny = count($permsForRole) > 0;
                                @endphp
                                <tr>
                                    <td class="font-weight-bold" style="font-size:13px;">{{ $role->name }}</td>
                                    @foreach($allMethods as $method)
                                    <td class="text-center">
                                        <input type="checkbox"
                                               name="role_method[{{ $role->id }}][]"
                                               value="{{ $method->id }}"
                                               class="role-method-cb cb-role-{{ $role->id }}"
                                               {{ ($hasAny && in_array($method->id, $permsForRole)) ? 'checked' : (!$hasAny ? 'checked' : '') }}>
                                    </td>
                                    @endforeach
                                    <td class="text-center">
                                        <input type="checkbox" class="role-all-cb"
                                               data-role="{{ $role->id }}"
                                               title="Toggle all for {{ $role->name }}"
                                               {{ (!$hasAny || count($permsForRole) === $allMethods->count()) ? 'checked' : '' }}
                                               onchange="toggleRoleAll(this)">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer py-2">
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-save mr-1"></i>Save Role Permissions
                    </button>
                    <small class="text-muted ml-2">Changes apply immediately to all employees with that role.</small>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="bulkForm" action="{{ route('admin.settings.payment-methods.bulk-delete') }}" method="POST" style="display:none;">
    @csrf
    <div id="bulkIdsContainer"></div>
</form>

<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

@foreach($methods as $method)
<div class="modal fade" id="editMethodModal{{ $method->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.settings.payment-methods.update', $method) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white">Edit Payment Method</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $method->name }}" required>
                    </div>
                    <div class="form-group">
                        <label>Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{ $method->code }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $method->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active_{{ $method->id }}" name="is_active" {{ $method->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active_{{ $method->id }}">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<script>
function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.item-checkbox:not(:disabled)');
    checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
    updateSelection();
}

function updateSelection() {
    var checkboxes = document.querySelectorAll('.item-checkbox:checked');
    var count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';
    
    var allBoxes = document.querySelectorAll('.item-checkbox');
    document.getElementById('selectAll').checked = allBoxes.length > 0 && allBoxes.length === count;
}

function bulkDelete() {
    var checkboxes = document.querySelectorAll('.item-checkbox:checked');
    var count = checkboxes.length;
    if (count === 0) return;
    
    if (confirm('Delete ' + count + ' selected payment method(s)?')) {
        var container = document.getElementById('bulkIdsContainer');
        container.innerHTML = '';
        checkboxes.forEach(function(cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('bulkForm').submit();
    }
}

function deleteSingle(id) {
    if (confirm('Delete this payment method?')) {
        var form = document.getElementById('singleDeleteForm');
        form.action = '{{ url("admin/settings/payment-methods") }}/' + id;
        form.submit();
    }
}

function toggleRoleAll(masterCb) {
    var roleId = masterCb.getAttribute('data-role');
    var checked = masterCb.checked;
    document.querySelectorAll('.cb-role-' + roleId).forEach(function(cb) {
        cb.checked = checked;
    });
}

document.querySelectorAll('.role-method-cb').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var roleId = this.className.match(/cb-role-(\d+)/)[1];
        var allCbs = document.querySelectorAll('.cb-role-' + roleId);
        var allChecked = Array.from(allCbs).every(c => c.checked);
        var masterCb = document.querySelector('.role-all-cb[data-role="' + roleId + '"]');
        if (masterCb) masterCb.checked = allChecked;
    });
});
</script>
@endsection
