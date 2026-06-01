@extends('layouts.admin')

@section('title', 'Role Permissions')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Settings</a></li>
<li class="breadcrumb-item active">Role Permissions</li>
@endsection

@section('content')

{{-- Fixed Info Banner --}}
<div class="alert alert-info mb-3" style="border-left: 4px solid #17a2b8;" data-permanent="true">
    <h6 class="alert-heading mb-1"><i class="fas fa-info-circle mr-1"></i> What does this page control?</h6>
    <p class="mb-1">Feature access for the <strong>Reseller</strong> and <strong>Technician</strong> portals is managed here.</p>
    <hr class="my-1">
    <p class="mb-0"><i class="fas fa-arrow-right mr-1"></i> For admin employee access (Supervisor, Billman, etc.), use <a href="{{ route('admin.menu-permissions.index') }}" class="alert-link">Menu Access Control</a>.</p>
</div>

<form action="{{ route('admin.settings.role-permissions.update') }}" method="POST" id="permissionsForm">
    @csrf
    @method('PUT')

    <div class="card card-outline card-primary">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0"><i class="fas fa-user-shield mr-2"></i>Role Permissions</h3>
            <div>
                <span id="changeCounter" class="badge badge-warning mr-2" style="display:none;"></span>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save mr-1"></i>Save Permissions
                </button>
            </div>
        </div>

        <div class="card-body p-3">

            {{-- Role Tabs --}}
            <div class="mb-2">
                <span class="font-weight-bold text-muted mr-2" style="font-size:13px;">Select Role:</span>
                @foreach($roles as $i => $role)
                @php $isAdmin = in_array($role->id, $adminRoleIds); @endphp
                <button type="button"
                    class="btn btn-sm {{ $i === 0 ? ($isAdmin ? 'btn-secondary' : 'btn-primary') : 'btn-outline-secondary' }} role-tab mr-1 mb-1"
                    data-role="{{ $role->id }}"
                    data-is-admin="{{ $isAdmin ? '1' : '0' }}">
                    <i class="fas {{ $isAdmin ? 'fa-desktop' : 'fa-key' }} mr-1"></i>{{ ucfirst($role->name) }}
                </button>
                @endforeach
            </div>
            <small class="text-muted d-block mb-3">
                <i class="fas fa-desktop mr-1"></i> Admin panel role &nbsp;|&nbsp;
                <i class="fas fa-key mr-1"></i> Portal role (managed here)
            </small>

            {{-- Role Panels --}}
            @foreach($roles as $i => $role)
            @php $isAdmin = in_array($role->id, $adminRoleIds); @endphp

            <div class="role-panel" id="rolePanel_{{ $role->id }}" style="{{ $i !== 0 ? 'display:none;' : '' }}">

                @if($isAdmin)
                <div class="card card-outline card-secondary">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-desktop fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">{{ ucfirst($role->name) }} is an Admin Panel Role</h5>
                        <p class="text-muted mb-3">
                            Access for admin panel roles cannot be controlled from this page.<br>
                            Please manage it from Menu Access Control.
                        </p>
                        <a href="{{ route('admin.menu-permissions.index') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-sliders-h mr-1"></i> Go to Menu Access Control
                        </a>
                    </div>
                </div>

                @elseif(strtolower($role->name) === 'reseller')
                <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Default for new Resellers.</strong>
                    Saving will only apply these permissions to new resellers — existing ones are unaffected.
                    To apply to all at once, use the <strong>"Apply to All"</strong> button below.
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 text-primary"><i class="fas fa-user-tie mr-1"></i> Reseller Portal Permissions</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success role-check-all" data-role="{{ $role->id }}"><i class="fas fa-check-double mr-1"></i>All</button>
                        <button type="button" class="btn btn-sm btn-outline-danger role-uncheck-all" data-role="{{ $role->id }}"><i class="fas fa-times mr-1"></i>None</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-outline card-primary mb-0">
                            <div class="card-header py-2"><span class="font-weight-bold">Features</span></div>
                            <div class="card-body py-2 px-3">
                                @foreach($resellerPermissions as $perm)
                                @php
                                    $inputId = 'perm_' . $role->id . '_' . str_replace(['.', '-'], '_', $perm->name);
                                    $isChecked = in_array($perm->name, $rolePermissions[$role->id] ?? []);
                                    $meta = $resellerActionLabels[$perm->name] ?? ['icon' => 'fa-circle', 'label' => $perm->name];
                                @endphp
                                <div class="custom-control custom-switch mb-2">
                                    <input type="hidden" name="permissions[{{ $role->id }}][{{ $perm->name }}]" value="0">
                                    <input type="checkbox" class="custom-control-input perm-checkbox"
                                        id="{{ $inputId }}"
                                        name="permissions[{{ $role->id }}][{{ $perm->name }}]"
                                        value="1" data-role="{{ $role->id }}"
                                        {{ $isChecked ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="{{ $inputId }}">
                                        <i class="fas {{ $meta['icon'] }} mr-1" style="width:14px;"></i>{{ $meta['label'] }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-outline card-info mb-0 h-100">
                            <div class="card-header py-2"><span class="font-weight-bold">How it works</span></div>
                            <div class="card-body py-3 px-3" style="font-size:13px;">
                                <p class="mb-1"><strong>Save Permissions</strong> → Sets default for new resellers only.</p>
                                <p class="mb-1"><strong>Individual customize</strong> →
                                    <a href="{{ route('admin.reseller.index') }}">Resellers → Edit</a></p>
                                <hr class="my-2">
                                <p class="mb-1 text-danger"><strong>Apply to all at once:</strong></p>
                                <button type="button" class="btn btn-sm btn-danger"
                                    data-toggle="modal" data-target="#applyModal_{{ $role->id }}">
                                    <i class="fas fa-broadcast-tower mr-1"></i>Apply to All Resellers
                                </button>
                                <br><small class="text-muted">Individual settings will be overridden.</small>
                            </div>
                        </div>
                    </div>
                </div>

                @elseif($role->name === 'Field Technician')
                <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Default for new Technicians.</strong>
                    Saving will only apply these permissions to new technicians — existing ones are unaffected.
                    To apply to all at once, use the <strong>"Apply to All"</strong> button below.
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 text-success"><i class="fas fa-tools mr-1"></i> Technician Portal Permissions</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success role-check-all" data-role="{{ $role->id }}"><i class="fas fa-check-double mr-1"></i>All</button>
                        <button type="button" class="btn btn-sm btn-outline-danger role-uncheck-all" data-role="{{ $role->id }}"><i class="fas fa-times mr-1"></i>None</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-outline card-success mb-0">
                            <div class="card-header py-2"><span class="font-weight-bold">Features</span></div>
                            <div class="card-body py-2 px-3">
                                @foreach($technicianPermissions as $perm)
                                @php
                                    $inputId = 'perm_' . $role->id . '_' . str_replace(['.', '-'], '_', $perm->name);
                                    $isChecked = in_array($perm->name, $rolePermissions[$role->id] ?? []);
                                    $meta = $technicianActionLabels[$perm->name] ?? ['icon' => 'fa-circle', 'label' => $perm->name];
                                @endphp
                                <div class="custom-control custom-switch mb-2">
                                    <input type="hidden" name="permissions[{{ $role->id }}][{{ $perm->name }}]" value="0">
                                    <input type="checkbox" class="custom-control-input perm-checkbox"
                                        id="{{ $inputId }}"
                                        name="permissions[{{ $role->id }}][{{ $perm->name }}]"
                                        value="1" data-role="{{ $role->id }}"
                                        {{ $isChecked ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="{{ $inputId }}">
                                        <i class="fas {{ $meta['icon'] }} mr-1" style="width:14px;"></i>{{ $meta['label'] }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-outline card-info mb-0 h-100">
                            <div class="card-header py-2"><span class="font-weight-bold">How it works</span></div>
                            <div class="card-body py-3 px-3" style="font-size:13px;">
                                <p class="mb-1"><strong>Save Permissions</strong> → Sets default for new technicians only.</p>
                                <hr class="my-2">
                                <p class="mb-1 text-danger"><strong>Apply to all at once:</strong></p>
                                <button type="button" class="btn btn-sm btn-danger"
                                    data-toggle="modal" data-target="#applyModal_{{ $role->id }}">
                                    <i class="fas fa-broadcast-tower mr-1"></i>Apply to All Technicians
                                </button>
                                <br><small class="text-muted">Individual settings will be overridden.</small>
                            </div>
                        </div>
                    </div>
                </div>

                @else
                <div class="alert alert-secondary">No specific permissions configured for this role.</div>
                @endif

            </div>
            @endforeach

        </div>

        <div class="card-footer text-right">
            <span id="changeCounter2" class="badge badge-warning mr-2" style="display:none;"></span>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>Save All Permissions
            </button>
        </div>
    </div>
</form>

{{-- Apply to All Confirmation Modals --}}
@foreach($roles as $role)
@if(!in_array($role->id, $adminRoleIds))
<div class="modal fade" id="applyModal_{{ $role->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i>Apply to All {{ ucfirst($role->name) }}s?</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>This will override permissions for all existing <strong>{{ ucfirst($role->name) }}</strong>s.</p>
                <div class="alert alert-warning py-2 mb-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    All individually customized settings will be erased.
                </div>
                <p class="text-muted small mb-0">Please <strong>Save Permissions</strong> first, then click this button.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.settings.role-permissions.apply-to-all') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role->name }}">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-broadcast-tower mr-1"></i>Yes, Apply to All
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach

@endsection

@push('scripts')
<script>
$(function() {
    var initialState = {};
    $('.perm-checkbox').each(function() { initialState[this.id] = this.checked; });

    function updateCounter() {
        var n = 0;
        $('.perm-checkbox').each(function() { if (initialState[this.id] !== this.checked) n++; });
        if (n > 0) {
            $('#changeCounter, #changeCounter2').show().text(n + ' unsaved change' + (n > 1 ? 's' : ''));
        } else {
            $('#changeCounter, #changeCounter2').hide();
        }
    }

    $('.perm-checkbox').on('change', updateCounter);

    $('.role-tab').on('click', function() {
        var roleId = $(this).data('role');
        var isAdmin = $(this).data('is-admin') == '1';
        $('.role-tab').removeClass('btn-primary btn-secondary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass(isAdmin ? 'btn-secondary' : 'btn-primary');
        $('.role-panel').hide();
        $('#rolePanel_' + roleId).show();
    });

    $('.role-check-all').on('click', function() {
        var roleId = $(this).data('role');
        $('#rolePanel_' + roleId + ' .perm-checkbox').prop('checked', true);
        updateCounter();
    });

    $('.role-uncheck-all').on('click', function() {
        var roleId = $(this).data('role');
        $('#rolePanel_' + roleId + ' .perm-checkbox').prop('checked', false);
        updateCounter();
    });

    window.onbeforeunload = function() {
        var n = 0;
        $('.perm-checkbox').each(function() { if (initialState[this.id] !== this.checked) n++; });
        if (n > 0) return 'You have unsaved changes.';
    };
    $('#permissionsForm').on('submit', function() { window.onbeforeunload = null; });
});
</script>
@endpush
