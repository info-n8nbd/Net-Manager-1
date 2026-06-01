@if(auth()->user() && (auth()->user()->hasRole('Super Admin') || auth()->user()->user_type === 'admin'))
@php
    $menus = config('menu');
    $currentPerms = $menuPermMap ?? [];
    $hasExistingPerms = !empty($currentPerms);
@endphp

<div class="card card-outline card-warning mt-3" id="menuAccessCard" style="{{ isset($showMenuAccess) && $showMenuAccess ? '' : 'display:none;' }}">
    <div class="card-header py-2" style="cursor:pointer;" onclick="$('#menuAccessBody').slideToggle(); $(this).find('.fa-chevron-down,.fa-chevron-up').toggleClass('fa-chevron-down fa-chevron-up');">
        <h3 class="card-title"><i class="fas fa-users mr-2 text-warning"></i>Role Default Menu Access <small class="text-muted">(applies to all employees in this role)</small></h3>
        <div class="card-tools">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
    <div class="card-body p-2" id="menuAccessBody">
        <div class="alert alert-warning py-2 px-3 mb-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>This is the Role-level Default Setting</strong> — changes here will affect <strong>all employees</strong> in this role.
            To grant a different access to a single employee, use the <strong class="text-success">"Individual Employee Permission"</strong> section below.
        </div>
        <p class="text-muted small mb-2">Select which menus and submenus this role can access. Unchecked items will be hidden from the sidebar.</p>
        <div class="mb-2">
            <button type="button" class="btn btn-xs btn-outline-success" onclick="$('.menu-perm-cb').prop('checked', true);">
                <i class="fas fa-check-double mr-1"></i>Select All
            </button>
            <button type="button" class="btn btn-xs btn-outline-danger" onclick="$('.menu-perm-cb').prop('checked', false);">
                <i class="fas fa-times mr-1"></i>Deselect All
            </button>
            <button type="button" class="btn btn-xs btn-outline-primary" onclick="loadRoleDefaults();">
                <i class="fas fa-undo mr-1"></i>Reset to Default
            </button>
        </div>

        <div class="row" id="menuPermCheckboxes">
            @foreach($menus as $menuKey => $menu)
            <div class="col-md-6 col-lg-4 mb-2">
                <div class="border rounded p-2" style="background:#f8f9fa;">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input menu-perm-cb menu-parent-cb"
                               id="mp_{{ $menuKey }}" name="menu_permissions[]" value="{{ $menuKey }}"
                               data-menu-key="{{ $menuKey }}"
                               {{ $hasExistingPerms ? (($currentPerms[$menuKey] ?? false) ? 'checked' : '') : '' }}
                               onchange="toggleSubmenus(this, '{{ $menuKey }}')">
                        <label class="custom-control-label font-weight-bold" for="mp_{{ $menuKey }}">
                            <i class="{{ $menu['icon'] ?? 'fas fa-circle' }} mr-1"></i>{{ $menu['label'] }}
                        </label>
                    </div>
                    @if(!empty($menu['submenus']))
                    <div class="ml-3 mt-1 submenu-group-{{ $menuKey }}">
                        @foreach($menu['submenus'] as $subKey => $sub)
                        <div class="custom-control custom-checkbox custom-control-sm">
                            <input type="checkbox" class="custom-control-input menu-perm-cb submenu-cb-{{ $menuKey }}"
                                   id="mp_{{ $menuKey }}_{{ $subKey }}" name="menu_permissions[]" value="{{ $menuKey }}.{{ $subKey }}"
                                   data-menu-key="{{ $menuKey }}.{{ $subKey }}"
                                   {{ $hasExistingPerms ? (($currentPerms[$menuKey . '.' . $subKey] ?? false) ? 'checked' : '') : '' }}>
                            <label class="custom-control-label" for="mp_{{ $menuKey }}_{{ $subKey }}" style="font-size:12px;">
                                {{ $sub['label'] }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
var roleMenuDefaults = @json(\App\Services\MenuPermissionService::getRoleDefaults('__all__') ?: []);
@php
    $allDefaults = [];
    foreach (\Spatie\Permission\Models\Role::all() as $r) {
        $allDefaults[$r->name] = \App\Services\MenuPermissionService::getDefaultPermMap($r->name);
    }
@endphp
var roleDefaultPerms = @json($allDefaults);

function toggleSubmenus(parentCb, menuKey) {
    var checked = parentCb.checked;
    document.querySelectorAll('.submenu-cb-' + menuKey).forEach(function(cb) {
        cb.checked = checked;
    });
}

function applyRoleDefaults(roleName) {
    var defaults = roleDefaultPerms[roleName];
    if (!defaults || Object.keys(defaults).length === 0) {
        if (roleName === 'admin') {
            $('.menu-perm-cb').prop('checked', true);
        } else {
            $('.menu-perm-cb').prop('checked', false);
        }
        return;
    }
    document.querySelectorAll('.menu-perm-cb').forEach(function(cb) {
        var key = cb.getAttribute('data-menu-key');
        cb.checked = defaults[key] === true;
    });
}

function loadRoleDefaults() {
    var roleSelect = document.getElementById('empRoleSelect') || document.getElementById('userRole');
    if (roleSelect) {
        var selectedOption = roleSelect.options[roleSelect.selectedIndex];
        var roleName = selectedOption ? (selectedOption.getAttribute('data-role-name') || selectedOption.textContent.trim()) : '';
        if (roleName) {
            applyRoleDefaults(roleName);
        }
    }
}
</script>
@endif
