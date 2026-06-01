@extends('layouts.admin')

@section('title', 'Menu Access Control')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Menu Access Control</li>
@endsection

@push('styles')
<style>
.perm-table-wrapper {
    max-height: 75vh;
    overflow: auto;
    position: relative;
}
.perm-table-wrapper table thead tr th {
    position: sticky;
    top: 0;
    z-index: 10;
}
.perm-table-wrapper table thead tr th:first-child {
    z-index: 12;
    left: 0;
}
.perm-table-wrapper table tbody td:first-child {
    position: sticky;
    left: 0;
    min-width: 280px;
    width: 280px;
}
.perm-table-wrapper table tbody tr.menu-parent-row td:first-child,
.perm-table-wrapper table tbody tr.action-parent-row td:first-child {
    z-index: 3;
}
.perm-table-wrapper table tbody tr.submenu-row td:first-child,
.perm-table-wrapper table tbody tr.action-child-row td:first-child {
    z-index: 2;
}

.perm-table-wrapper table tbody tr.menu-parent-row td:first-child {
    background: #c5d5ea !important;
    font-weight: 600;
}
.perm-table-wrapper table tbody tr.menu-parent-row td {
    border-top: 2px solid #8fadd1;
    border-bottom: 2px solid #8fadd1;
    font-size: 14px;
}
.perm-table-wrapper table tbody tr.submenu-row td:first-child {
    background: #dfe7f3 !important;
}
.perm-table-wrapper table tbody tr.submenu-row td {
    border-left: 4px solid #5b8ec9;
}

.perm-table-wrapper table tbody tr.action-parent-row td:first-child {
    background: #b8dfc5 !important;
    font-weight: 600;
}
.perm-table-wrapper table tbody tr.action-parent-row td {
    border-top: 2px solid #7cc99a;
    border-bottom: 2px solid #7cc99a;
    font-size: 14px;
}
.perm-table-wrapper table tbody tr.action-child-row td:first-child {
    background: #d5ebde !important;
}
.perm-table-wrapper table tbody tr.action-child-row td {
    border-left: 4px solid #5bb57d;
}
</style>
@endpush

@section('content')

{{-- Fixed Info Banner --}}
<div class="alert alert-info mb-3" style="border-left: 4px solid #17a2b8;" data-permanent="true">
    <h6 class="alert-heading mb-2"><i class="fas fa-info-circle mr-1"></i> What does this page control?</h6>
    <div class="row">
        <div class="col-md-6">
            <p class="mb-1">
                <i class="fas fa-bars text-primary mr-1"></i>
                <strong>Sidebar Menu Access</strong> — Controls which sidebar menu items admin employees can see.
            </p>
            <p class="mb-0">
                <i class="fas fa-hand-pointer text-success mr-1"></i>
                <strong>Page Actions / Features</strong> — Controls which buttons and features are available on each page.
            </p>
        </div>
        <div class="col-md-6">
            <p class="mb-0">
                <i class="fas fa-user-shield text-warning mr-1"></i>
                If a <strong>Super Admin</strong> <strong>allows</strong> access, that Admin and anyone they grant permission to will be able to access it. <strong>If not allowed</strong>, no one will have access.
            </p>
        </div>
    </div>
</div>

<div class="card card-primary card-outline">
    <div class="card-header p-0">
        <ul class="nav nav-tabs" id="permTabs">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'menu' ? 'active' : '' }}" data-toggle="tab" href="#tabMenu">
                    <i class="fas fa-bars mr-1"></i> Sidebar Menu Access
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'actions' ? 'active' : '' }}" data-toggle="tab" href="#tabActions">
                    <i class="fas fa-hand-pointer mr-1"></i> Page Actions / Features
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content">
    <div class="tab-pane {{ $activeTab === 'menu' ? 'active show' : '' }}" id="tabMenu">
        <form method="POST" action="{{ route('admin.menu-permissions.update') }}" id="menuPermForm">
            @csrf
            @method('PUT')
            <div class="card card-outline card-primary">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Sidebar Menu Permissions</h3>
                    <div class="ml-auto d-flex align-items-center">
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-success btn-sm menu-check-all" title="Check All">
                                <i class="fas fa-check-double mr-1"></i> Check All
                            </button>
                            <button type="button" class="btn btn-warning btn-sm menu-uncheck-all" title="Uncheck All">
                                <i class="fas fa-times mr-1"></i> Uncheck All
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save mr-1"></i> Save Menu Permissions
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="perm-table-wrapper">
                        <table class="table table-bordered table-hover mb-0" id="menuPermTable">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: #fff;">
                                    <th style="min-width: 280px; width: 280px; position: sticky; left: 0; z-index: 3; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: #fff; font-weight: 700; font-size: 14px;">
                                        Menu / Submenu
                                    </th>
                                    @foreach($roles as $role)
                                    <th class="text-center" style="min-width: 130px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: #fff; font-weight: 600;">
                                        {{ $role->name }}
                                        <div class="mt-1">
                                            <a href="#" class="badge badge-light toggle-role-col" data-role="{{ $role->id }}" data-action="check" data-scope="menu" title="Check all for {{ $role->name }}">
                                                <i class="fas fa-check text-success"></i> All
                                            </a>
                                            <a href="#" class="badge badge-light toggle-role-col" data-role="{{ $role->id }}" data-action="uncheck" data-scope="menu" title="Uncheck all for {{ $role->name }}">
                                                <i class="fas fa-times text-danger"></i> None
                                            </a>
                                        </div>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($menus as $menuKey => $menu)
                                <tr class="menu-parent-row" data-menu="{{ $menuKey }}">
                                    <td style="position: sticky; left: 0; z-index: 2; font-weight: 600;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>
                                                <i class="{{ $menu['icon'] }} mr-2 text-primary"></i>
                                                {{ $menu['label'] }}
                                            </span>
                                            <span class="d-flex align-items-center">
                                                @if(!empty($menu['submenus']))
                                                <a href="#" class="btn btn-xs btn-outline-success mr-1 toggle-menu-row" data-menu="{{ $menuKey }}" data-action="check" title="Check entire row">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="#" class="btn btn-xs btn-outline-danger mr-1 toggle-menu-row" data-menu="{{ $menuKey }}" data-action="uncheck" title="Uncheck entire row">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <button type="button" class="btn btn-xs btn-outline-secondary toggle-submenu" data-menu="{{ $menuKey }}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                                @else
                                                <a href="#" class="btn btn-xs btn-outline-success mr-1 toggle-menu-row" data-menu="{{ $menuKey }}" data-action="check" title="Check entire row">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="#" class="btn btn-xs btn-outline-danger toggle-menu-row" data-menu="{{ $menuKey }}" data-action="uncheck" title="Uncheck entire row">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    @foreach($roles as $role)
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-checkbox d-flex justify-content-center">
                                            <input type="checkbox"
                                                class="custom-control-input menu-checkbox role-{{ $role->id }}-cb menu-{{ $menuKey }}-cb parent-cb-{{ $menuKey }}"
                                                id="perm_{{ $role->id }}_{{ $menuKey }}"
                                                name="permissions[{{ $role->id }}][{{ $menuKey }}]"
                                                value="1"
                                                data-role="{{ $role->id }}"
                                                data-menu="{{ $menuKey }}"
                                                {{ isset($permMap[$role->id][$menuKey]) ? ($permMap[$role->id][$menuKey] ? 'checked' : '') : (($codeDefaultMap[$role->id][$menuKey] ?? false) ? 'checked' : '') }}>
                                            <label class="custom-control-label" for="perm_{{ $role->id }}_{{ $menuKey }}">&nbsp;</label>
                                        </div>
                                    </td>
                                    @endforeach
                                </tr>
                                @if(!empty($menu['submenus']))
                                    @foreach($menu['submenus'] as $subKey => $sub)
                                    <tr class="submenu-row submenu-{{ $menuKey }}">
                                        <td style="padding-left: 40px; position: sticky; left: 0; z-index: 1;">
                                            <i class="far fa-circle nav-icon text-muted mr-1" style="font-size: 8px;"></i>
                                            {{ $sub['label'] }}
                                        </td>
                                        @foreach($roles as $role)
                                        <td class="text-center align-middle">
                                            <div class="custom-control custom-checkbox d-flex justify-content-center">
                                                <input type="checkbox"
                                                    class="custom-control-input menu-checkbox role-{{ $role->id }}-cb menu-{{ $menuKey }}-cb child-cb-{{ $menuKey }}"
                                                    id="perm_{{ $role->id }}_{{ $menuKey }}_{{ $subKey }}"
                                                    name="permissions[{{ $role->id }}][{{ $menuKey }}.{{ $subKey }}]"
                                                    value="1"
                                                    data-role="{{ $role->id }}"
                                                    data-menu="{{ $menuKey }}"
                                                    data-submenu="{{ $subKey }}"
                                                    {{ isset($permMap[$role->id][$menuKey . '.' . $subKey]) ? ($permMap[$role->id][$menuKey . '.' . $subKey] ? 'checked' : '') : (($codeDefaultMap[$role->id][$menuKey . '.' . $subKey] ?? false) ? 'checked' : '') }}>
                                                <label class="custom-control-label" for="perm_{{ $role->id }}_{{ $menuKey }}_{{ $subKey }}">&nbsp;</label>
                                            </div>
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Menu Permissions
                        </button>
                        <span class="text-muted ml-3"><i class="fas fa-info-circle mr-1"></i> Super Admin always has full access.</span>
                    </div>
                    <div>
                        <span class="text-muted" id="menuChangeCounter"></span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="tab-pane {{ $activeTab === 'actions' ? 'active show' : '' }}" id="tabActions">
        <form method="POST" action="{{ route('admin.menu-permissions.update-actions') }}" id="actionPermForm">
            @csrf
            @method('PUT')
            <div class="card card-outline card-info">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title"><i class="fas fa-hand-pointer mr-2"></i>Page Actions & Features Permissions</h3>
                    <div class="ml-auto d-flex align-items-center">
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-success btn-sm action-check-all" title="Check All">
                                <i class="fas fa-check-double mr-1"></i> Check All
                            </button>
                            <button type="button" class="btn btn-warning btn-sm action-uncheck-all" title="Uncheck All">
                                <i class="fas fa-times mr-1"></i> Uncheck All
                            </button>
                        </div>
                        <button type="submit" class="btn btn-info btn-sm">
                            <i class="fas fa-save mr-1"></i> Save Action Permissions
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="perm-table-wrapper">
                        <table class="table table-bordered table-hover mb-0" id="actionPermTable">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #1e6b4f 0%, #1a9670 100%); color: #fff;">
                                    <th style="min-width: 280px; position: sticky; left: 0; z-index: 3; background: linear-gradient(135deg, #1e6b4f 0%, #1a9670 100%); color: #fff; font-weight: 700; font-size: 14px;">
                                        Page / Action
                                    </th>
                                    @foreach($roles as $role)
                                    <th class="text-center" style="min-width: 130px; background: linear-gradient(135deg, #1e6b4f 0%, #1a9670 100%); color: #fff; font-weight: 600;">
                                        {{ $role->name }}
                                        <div class="mt-1">
                                            <a href="#" class="badge badge-light toggle-role-col" data-role="{{ $role->id }}" data-action="check" data-scope="action" title="Check all for {{ $role->name }}">
                                                <i class="fas fa-check text-success"></i> All
                                            </a>
                                            <a href="#" class="badge badge-light toggle-role-col" data-role="{{ $role->id }}" data-action="uncheck" data-scope="action" title="Uncheck all for {{ $role->name }}">
                                                <i class="fas fa-times text-danger"></i> None
                                            </a>
                                        </div>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pagePermissions as $pageKey => $page)
                                <tr class="action-parent-row" data-page="{{ $pageKey }}">
                                    <td style="position: sticky; left: 0; z-index: 2; font-weight: 600;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>
                                                <i class="{{ $page['icon'] }} mr-2 text-success"></i>
                                                {{ $page['label'] }}
                                            </span>
                                            <span class="d-flex align-items-center">
                                                <a href="#" class="btn btn-xs btn-outline-success mr-1 toggle-action-page" data-page="{{ $pageKey }}" data-action="check" title="Check entire section">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="#" class="btn btn-xs btn-outline-danger mr-1 toggle-action-page" data-page="{{ $pageKey }}" data-action="uncheck" title="Uncheck entire section">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <button type="button" class="btn btn-xs btn-outline-secondary toggle-action-children" data-page="{{ $pageKey }}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </td>
                                    @foreach($roles as $role)
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-checkbox d-flex justify-content-center">
                                            <input type="checkbox"
                                                class="custom-control-input action-parent-checkbox action-checkbox action-role-{{ $role->id }}-cb action-page-{{ $pageKey }}-cb"
                                                id="actperm_parent_{{ $role->id }}_{{ $pageKey }}"
                                                data-role="{{ $role->id }}"
                                                data-page="{{ $pageKey }}"
                                                data-is-parent="1"
                                                {{ collect($page['actions'])->keys()->every(fn($ak) => !isset($actionPermMap[$role->id][$pageKey . '.' . $ak]) || $actionPermMap[$role->id][$pageKey . '.' . $ak]) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="actperm_parent_{{ $role->id }}_{{ $pageKey }}">&nbsp;</label>
                                        </div>
                                    </td>
                                    @endforeach
                                </tr>
                                @foreach($page['actions'] as $actionKey => $actionLabel)
                                <tr class="action-child-row action-children-{{ $pageKey }}">
                                    <td style="padding-left: 40px; position: sticky; left: 0; z-index: 1;">
                                        <i class="far fa-dot-circle text-muted mr-1" style="font-size: 8px;"></i>
                                        {{ $actionLabel }}
                                    </td>
                                    @foreach($roles as $role)
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-checkbox d-flex justify-content-center">
                                            <input type="checkbox"
                                                class="custom-control-input action-checkbox action-role-{{ $role->id }}-cb action-page-{{ $pageKey }}-cb action-child-{{ $pageKey }}"
                                                id="actperm_{{ $role->id }}_{{ $pageKey }}_{{ $actionKey }}"
                                                name="action_permissions[{{ $role->id }}][{{ $pageKey }}.{{ $actionKey }}]"
                                                value="1"
                                                data-role="{{ $role->id }}"
                                                data-page="{{ $pageKey }}"
                                                data-actionkey="{{ $actionKey }}"
                                                {{ isset($actionPermMap[$role->id][$pageKey . '.' . $actionKey]) ? ($actionPermMap[$role->id][$pageKey . '.' . $actionKey] ? 'checked' : '') : 'checked' }}>
                                            <label class="custom-control-label" for="actperm_{{ $role->id }}_{{ $pageKey }}_{{ $actionKey }}">&nbsp;</label>
                                        </div>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save mr-1"></i> Save Action Permissions
                        </button>
                        <span class="text-muted ml-3"><i class="fas fa-info-circle mr-1"></i> Super Admin always has full access. Unchecked = hidden from that role.</span>
                    </div>
                    <div>
                        <span class="text-muted" id="actionChangeCounter"></span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var menuChanges = 0, actionChanges = 0;

    function updateMenuCounter() {
        menuChanges++;
        $('#menuChangeCounter').html('<span class="badge badge-info">' + menuChanges + ' changes (unsaved)</span>');
    }
    function updateActionCounter() {
        actionChanges++;
        $('#actionChangeCounter').html('<span class="badge badge-info">' + actionChanges + ' changes (unsaved)</span>');
    }

    $('.toggle-submenu').click(function(e) {
        e.preventDefault();
        var menuKey = $(this).data('menu');
        $('.submenu-' + menuKey).toggle();
        $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
    });

    $('.toggle-action-children').click(function(e) {
        e.preventDefault();
        var pageKey = $(this).data('page');
        $('.action-children-' + pageKey).toggle();
        $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
    });

    $(document).on('change', '.menu-checkbox', function() {
        var menuKey = $(this).data('menu');
        var roleId = $(this).data('role');
        var submenu = $(this).data('submenu');
        if (!submenu && submenu !== 0) {
            var isChecked = $(this).prop('checked');
            $('.child-cb-' + menuKey + '.role-' + roleId + '-cb').prop('checked', isChecked);
        }
        updateMenuCounter();
    });

    $(document).on('change', '.action-checkbox[data-is-parent="1"]', function() {
        var pageKey = $(this).data('page');
        var roleId = $(this).data('role');
        var isChecked = $(this).prop('checked');
        $('.action-child-' + pageKey + '.action-role-' + roleId + '-cb').prop('checked', isChecked);
        updateActionCounter();
    });

    $(document).on('change', '.action-checkbox:not([data-is-parent])', function() {
        updateActionCounter();
    });

    $('.toggle-role-col').click(function(e) {
        e.preventDefault();
        var roleId = $(this).data('role');
        var action = $(this).data('action');
        var scope = $(this).data('scope');
        var isChecked = (action === 'check');
        if (scope === 'action') {
            $('.action-role-' + roleId + '-cb').prop('checked', isChecked);
            updateActionCounter();
        } else {
            $('.role-' + roleId + '-cb').prop('checked', isChecked);
            updateMenuCounter();
        }
    });

    $('.toggle-menu-row').click(function(e) {
        e.preventDefault();
        var menuKey = $(this).data('menu');
        var action = $(this).data('action');
        var isChecked = (action === 'check');
        $('.menu-' + menuKey + '-cb').prop('checked', isChecked);
        updateMenuCounter();
    });

    $('.toggle-action-page').click(function(e) {
        e.preventDefault();
        var pageKey = $(this).data('page');
        var action = $(this).data('action');
        var isChecked = (action === 'check');
        $('.action-page-' + pageKey + '-cb').prop('checked', isChecked);
        updateActionCounter();
    });

    $('.menu-check-all').click(function() { $('.menu-checkbox').prop('checked', true); updateMenuCounter(); });
    $('.menu-uncheck-all').click(function() { $('.menu-checkbox').prop('checked', false); updateMenuCounter(); });
    $('.action-check-all').click(function() { $('.action-checkbox').prop('checked', true); updateActionCounter(); });
    $('.action-uncheck-all').click(function() { $('.action-checkbox').prop('checked', false); updateActionCounter(); });

    var formChanged = false;
    $(document).on('change', '.menu-checkbox, .action-checkbox', function() { formChanged = true; });
    $(window).on('beforeunload', function() { if (formChanged) return 'You have unsaved changes.'; });
    $('#menuPermForm, #actionPermForm').on('submit', function() { formChanged = false; });
});
</script>
@endpush
