@php
    $__canManagePerms = auth()->user() && (
        auth()->user()->hasRole('Super Admin') ||
        auth()->user()->user_type === 'admin'
    );
    $__overrideUser = null;
    $__overrideName = '';
    $__employeeId = null;
    if (isset($employee) && $employee) {
        if ($employee instanceof \App\Models\User) {
            $__overrideUser = $employee;
            $__overrideName = $employee->name;
        } elseif ($employee->user) {
            $__overrideUser = $employee->user;
            $__overrideName = $employee->name;
        }
        $__employeeId = $employee->id;
    }
@endphp

@if($__canManagePerms && $__overrideUser && $__overrideUser->roles->count())
@php
    $menus = config('menu');
    $effectivePerms = $userEffectivePerms ?? [];
    $codeDefaults = $roleCodeDefaultMap ?? [];
    $roleName = $__overrideUser->roles->first()->name ?? 'Unknown';
@endphp

<div class="card card-outline card-success mt-3" id="employeePermCard">
    <div class="card-header py-2" style="cursor:pointer;"
         onclick="$('#empPermBody').slideToggle(); $(this).find('.fa-chevron-down,.fa-chevron-up').toggleClass('fa-chevron-down fa-chevron-up');">
        <h3 class="card-title">
            <i class="fas fa-user-shield mr-2 text-success"></i>
            Individual Permission for this Employee
            <small class="text-muted ml-1">— {{ $__overrideName }}</small>
        </h3>
        <div class="card-tools">
            <span class="badge badge-info mr-2">Role: {{ $roleName }}</span>
            <i class="fas fa-chevron-up"></i>
        </div>
    </div>
    <div class="card-body p-3" id="empPermBody">

        <div class="alert alert-info border py-2 px-3 mb-3" style="font-size:13px;">
            <div class="mb-1">
                <i class="fas fa-user-shield text-success mr-1"></i>
                <strong>This section is only for {{ $__overrideName }}.</strong> Changes here will not affect any other employee.
            </div>
            <div>
                <span class="badge badge-success" style="font-size:10px;">R</span>
                Items marked with R follow the <strong>Role Default</strong> above.
                Setting an item individually will override the Role Default.
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-success mr-1" onclick="setAllEmpPerms(true)">
                <i class="fas fa-check-double mr-1"></i>Allow All
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger mr-1" onclick="setAllEmpPerms(false)">
                <i class="fas fa-times mr-1"></i>Deny All
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetToRoleDefaults()">
                <i class="fas fa-undo mr-1"></i>Reset to Role Default
            </button>
        </div>

        <style>
        .epc-row { display: flex; flex-wrap: wrap; gap: 4px; }
        .epc-col { flex: 0 0 calc(33.333% - 3px); max-width: calc(33.333% - 3px); }
        @media(max-width:768px){ .epc-col { flex: 0 0 calc(50% - 2px); max-width: calc(50% - 2px); } }
        .epc-card { border: 2px solid #e2e6ea; border-radius: 5px; background: #f9fafb; padding: 5px 8px; height: 100%; }
        .epc-card.active-card { border-color: #28a745; background: #f0fff4; }
        .epc-label { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: #333; cursor: pointer; margin: 0; line-height: 1.3; user-select: none; }
        .epc-label input[type=checkbox] { width: 12px; height: 12px; margin: 0; flex-shrink: 0; cursor: pointer; accent-color: #28a745; }
        .epc-label.is-role-default { color: #155724; }
        .epc-sub-list { margin: 3px 0 0 16px; border-left: 2px solid #dee2e6; padding-left: 5px; }
        .epc-sub-label { display: flex; align-items: center; gap: 4px; font-size: 11px; color: #444; cursor: pointer; margin: 1px 0; line-height: 1.3; user-select: none; }
        .epc-sub-label input[type=checkbox] { width: 11px; height: 11px; margin: 0; flex-shrink: 0; cursor: pointer; accent-color: #28a745; }
        .epc-sub-label.is-role-default { color: #155724; background: #e8f5e9; border-radius: 3px; padding: 0 2px; }
        .epc-rbadge { font-size: 8px; padding: 1px 3px; border-radius: 3px; background: #28a745; color: #fff; flex-shrink: 0; }
        </style>

        <div class="epc-row" id="empPermCheckboxes">
            @foreach($menus as $menuKey => $menu)
            @php
                $menuData    = $effectivePerms[$menuKey] ?? ['role' => false, 'override' => null, 'effective' => false];
                $isEffective = $menuData['effective'] ?? false;
                $isRoleDef   = $codeDefaults[$menuKey] ?? false;
            @endphp
            <div class="epc-col">
                <div class="epc-card {{ $isEffective ? 'active-card' : '' }}" id="epg_{{ $menuKey }}">
                    <label class="epc-label {{ $isRoleDef ? 'is-role-default' : '' }}">
                        <input type="checkbox"
                               class="emp-menu-cb emp-parent-cb"
                               id="ep_{{ $menuKey }}"
                               data-menu-key="{{ $menuKey }}"
                               data-role-default="{{ $isRoleDef ? '1' : '0' }}"
                               {{ $isEffective ? 'checked' : '' }}
                               onchange="toggleEmpSubmenus(this, '{{ $menuKey }}'); refreshEmpCard('{{ $menuKey }}')">
                        <i class="{{ $menu['icon'] ?? 'fas fa-circle' }} text-primary" style="width:11px; font-size:10px; flex-shrink:0;"></i>
                        <span>{{ $menu['label'] }}</span>
                        @if($isRoleDef)<span class="epc-rbadge">R</span>@endif
                    </label>
                    @if(!empty($menu['submenus']))
                    <div class="epc-sub-list" id="esub_{{ $menuKey }}">
                        @foreach($menu['submenus'] as $subKey => $sub)
                        @php
                            $fullKey    = $menuKey . '.' . $subKey;
                            $subData    = $effectivePerms[$fullKey] ?? ['role' => false, 'override' => null, 'effective' => false];
                            $subEff     = $subData['effective'] ?? false;
                            $subRoleDef = $codeDefaults[$fullKey] ?? false;
                        @endphp
                        <label class="epc-sub-label {{ $subRoleDef ? 'is-role-default' : '' }}">
                            <input type="checkbox"
                                   class="emp-menu-cb emp-sub-cb-{{ $menuKey }}"
                                   id="ep_{{ $menuKey }}_{{ $subKey }}"
                                   data-menu-key="{{ $fullKey }}"
                                   data-role-default="{{ $subRoleDef ? '1' : '0' }}"
                                   {{ $subEff ? 'checked' : '' }}
                                   onchange="syncEmpParent('{{ $menuKey }}'); refreshEmpCard('{{ $menuKey }}')">
                            <span>{{ $sub['label'] }}</span>
                            @if($subRoleDef)<span class="epc-rbadge">R</span>@endif
                        </label>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="d-flex align-items-center mt-3 pt-3 border-top">
            <button type="button" class="btn btn-success px-4" id="saveEmpPermBtn"
                    onclick="saveEmpPermissions({{ $__employeeId }})">
                <i class="fas fa-save mr-1"></i>Save Permissions
            </button>
            <span id="empPermStatus" class="ml-3 small"></span>
        </div>
    </div>
</div>

<script>

function toggleEmpSubmenus(parentCb, menuKey) {
    var checked = parentCb.checked;
    document.querySelectorAll('.emp-sub-cb-' + menuKey).forEach(function(cb) {
        cb.checked = checked;
    });
}

function syncEmpParent(menuKey) {
    var subCbs  = Array.from(document.querySelectorAll('.emp-sub-cb-' + menuKey));
    var parentCb = document.getElementById('ep_' + menuKey);
    if (!parentCb || subCbs.length === 0) return;
    var anyChecked = subCbs.some(function(cb) { return cb.checked; });
    var allChecked = subCbs.every(function(cb) { return cb.checked; });
    parentCb.checked = anyChecked;
    parentCb.indeterminate = anyChecked && !allChecked;
}

function refreshEmpCard(menuKey) {
    var parentCb = document.getElementById('ep_' + menuKey);
    var card = document.getElementById('epg_' + menuKey);
    if (!card || !parentCb) return;
    if (parentCb.checked || parentCb.indeterminate) {
        card.classList.add('active-card');
    } else {
        card.classList.remove('active-card');
    }
}

function setAllEmpPerms(checked) {
    document.querySelectorAll('.emp-menu-cb').forEach(function(cb) {
        cb.checked = checked;
        cb.indeterminate = false;
    });
    document.querySelectorAll('.emp-parent-cb').forEach(function(cb) {
        refreshEmpCard(cb.getAttribute('data-menu-key'));
    });
}

function resetToRoleDefaults() {
    document.querySelectorAll('.emp-menu-cb').forEach(function(cb) {
        var key = cb.getAttribute('data-menu-key');
        var isDefault = cb.getAttribute('data-role-default') === '1';
        cb.checked = isDefault;
        cb.indeterminate = false;
    });
    document.querySelectorAll('.emp-parent-cb').forEach(function(cb) {
        refreshEmpCard(cb.getAttribute('data-menu-key'));
    });
}

function saveEmpPermissions(employeeId) {
    var btn    = document.getElementById('saveEmpPermBtn');
    var status = document.getElementById('empPermStatus');

    var checkedMenus = [];
    var allMenuKeys  = [];

    document.querySelectorAll('.emp-menu-cb').forEach(function(cb) {
        var key = cb.getAttribute('data-menu-key');
        allMenuKeys.push(key);
        if (cb.checked) checkedMenus.push(key);
    });

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving...';
    status.textContent = '';

    fetch('/admin/hrm/employees/' + employeeId + '/permissions', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ menu_permissions: checkedMenus, all_menu_keys: allMenuKeys })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check mr-1"></i>Saved!';
            btn.className = 'btn btn-success px-4';
            status.innerHTML = '<i class="fas fa-check-circle text-success mr-1"></i>' + (data.message || 'Permissions updated!');
            setTimeout(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Permissions';
                btn.className = 'btn btn-success px-4';
            }, 2500);
        } else {
            throw new Error(data.error || 'Save failed');
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Permissions';
        status.innerHTML = '<i class="fas fa-times-circle text-danger mr-1"></i>' + err.message;
    });
}

// Init: refresh card colors on load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.emp-parent-cb').forEach(function(cb) {
        refreshEmpCard(cb.getAttribute('data-menu-key'));
    });
});
</script>
@endif
