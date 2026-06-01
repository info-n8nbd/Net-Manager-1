@extends('layouts.admin')

@section('title', 'Create Company')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">Companies</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<form action="{{ route('superadmin.companies.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-building mr-2"></i>Company Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Company Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-shield mr-2"></i>Admin Account</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="admin_name">Admin Name <span class="text-danger">*</span></label>
                        <input type="text" name="admin_name" id="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required>
                        @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admin_email">Admin Email <span class="text-danger">*</span></label>
                                <input type="email" name="admin_email" id="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" required>
                                @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admin_password">Admin Password <span class="text-danger">*</span></label>
                                <input type="password" name="admin_password" id="admin_password" class="form-control @error('admin_password') is-invalid @enderror" required>
                                @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>Menu Permissions <span class="badge badge-info ml-2" id="menuCountBadge">0</span></h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool text-success" onclick="toggleAllMenus(true)"><i class="fas fa-check-double"></i> Select All</button>
                        <button type="button" class="btn btn-tool text-danger" onclick="toggleAllMenus(false)"><i class="fas fa-times"></i> None</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @foreach($menuStructure as $parentKey => $group)
                    <div class="menu-group border-bottom">
                        <div class="d-flex align-items-center px-3 py-2 bg-light" style="cursor:pointer" onclick="toggleGroup('{{ $parentKey }}')">
                            <div class="custom-control custom-checkbox mr-2" onclick="event.stopPropagation()">
                                <input type="checkbox" class="custom-control-input parent-menu-cb" id="menu_{{ $parentKey }}" data-parent="{{ $parentKey }}" onchange="toggleParent('{{ $parentKey }}', this.checked)">
                                <label class="custom-control-label" for="menu_{{ $parentKey }}">&nbsp;</label>
                            </div>
                            <i class="{{ $group['icon'] }} mr-2 text-muted"></i>
                            <strong>{{ $group['label'] }}</strong>
                            <span class="badge badge-secondary ml-auto group-count" id="count_{{ $parentKey }}">0/{{ count($group['items']) > 0 ? count($group['items']) : 1 }}</span>
                            <i class="fas fa-chevron-down ml-2 toggle-icon" id="icon_{{ $parentKey }}"></i>
                        </div>
                        @if(count($group['items']) > 0)
                        <div class="menu-items pl-5 pr-3 py-2" id="items_{{ $parentKey }}" style="display:none">
                            @foreach($group['items'] as $itemKey => $itemLabel)
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input child-menu-cb" id="menu_{{ str_replace('.', '_', $itemKey) }}" name="allowed_menus[]" value="{{ $itemKey }}" data-parent="{{ $parentKey }}" checked onchange="updateCounts()">
                                <label class="custom-control-label" for="menu_{{ str_replace('.', '_', $itemKey) }}">{{ $itemLabel }}</label>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <input type="checkbox" name="allowed_menus[]" value="{{ $parentKey }}" class="child-menu-cb d-none" data-parent="{{ $parentKey }}" checked>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Subscription Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="max_clients">Max Clients <small class="text-muted">(0 = unlimited)</small></label>
                        <input type="number" name="max_clients" id="max_clients" class="form-control" value="{{ old('max_clients', 0) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label for="subscription_start">Subscription Start</label>
                        <input type="date" name="subscription_start" id="subscription_start" class="form-control" value="{{ old('subscription_start', date('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label for="subscription_end">Subscription End <small class="text-muted">(blank = unlimited)</small></label>
                        <input type="date" name="subscription_end" id="subscription_end" class="form-control" value="{{ old('subscription_end') }}">
                    </div>
                </div>
            </div>

            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-puzzle-piece mr-2"></i>Enabled Features / Modules</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool text-success" onclick="toggleAllFeatures(true)" title="Enable All"><i class="fas fa-check-double"></i> All</button>
                        <button type="button" class="btn btn-tool text-danger" onclick="toggleAllFeatures(false)" title="Disable All"><i class="fas fa-times"></i> None</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @php
                        $featureGroups = \App\Models\Company::$featureGroups;
                        $allFeatureLabels = \App\Models\Company::$allFeatures;
                    @endphp
                    @foreach($featureGroups as $groupName => $groupKeys)
                    <div class="border-bottom">
                        <div class="px-3 py-2 bg-light d-flex align-items-center">
                            <div class="custom-control custom-checkbox mr-2">
                                <input type="checkbox" class="custom-control-input group-feature-cb" id="fgrp_{{ Str::slug($groupName) }}" data-group="{{ Str::slug($groupName) }}" checked onchange="toggleFeatureGroup('{{ Str::slug($groupName) }}', this.checked)">
                                <label class="custom-control-label" for="fgrp_{{ Str::slug($groupName) }}">&nbsp;</label>
                            </div>
                            <strong class="text-sm text-muted text-uppercase" style="font-size:0.75rem;letter-spacing:0.05em">{{ $groupName }}</strong>
                        </div>
                        <div class="px-3 py-2">
                            <div class="row">
                                @foreach($groupKeys as $key)
                                @if(isset($allFeatureLabels[$key]))
                                <div class="col-md-6">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input feature-checkbox fgrp-{{ Str::slug($groupName) }}" id="feature_{{ $key }}" name="enabled_features[]" value="{{ $key }}" checked onchange="updateGroupCheckboxes()">
                                        <label class="custom-control-label" for="feature_{{ $key }}">
                                            {{ $allFeatureLabels[$key] }}
                                            @if(str_contains($allFeatureLabels[$key], '(Premium)'))
                                            <span class="badge badge-warning badge-sm ml-1" style="font-size:0.65rem">Premium</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-plus mr-2"></i>Create Company
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
function toggleAllFeatures(checked) {
    document.querySelectorAll('.feature-checkbox').forEach(cb => cb.checked = checked);
    updateGroupCheckboxes();
}

function toggleFeatureGroup(group, checked) {
    document.querySelectorAll('.fgrp-' + group).forEach(cb => cb.checked = checked);
}

function updateGroupCheckboxes() {
    document.querySelectorAll('.group-feature-cb').forEach(function(grpCb) {
        const group = grpCb.dataset.group;
        const children = document.querySelectorAll('.fgrp-' + group);
        const total = children.length;
        const checked = Array.from(children).filter(cb => cb.checked).length;
        grpCb.checked = checked > 0;
        grpCb.indeterminate = checked > 0 && checked < total;
    });
}

function toggleAllMenus(checked) {
    document.querySelectorAll('.child-menu-cb').forEach(cb => cb.checked = checked);
    document.querySelectorAll('.parent-menu-cb').forEach(cb => cb.checked = checked);
    updateCounts();
}

function toggleParent(parentKey, checked) {
    document.querySelectorAll('.child-menu-cb[data-parent="' + parentKey + '"]').forEach(cb => cb.checked = checked);
    updateCounts();
}

function toggleGroup(parentKey) {
    const items = document.getElementById('items_' + parentKey);
    const icon = document.getElementById('icon_' + parentKey);
    if (items) {
        items.style.display = items.style.display === 'none' ? 'block' : 'none';
        icon.classList.toggle('fa-chevron-down');
        icon.classList.toggle('fa-chevron-up');
    }
}

function updateCounts() {
    let totalChecked = 0;
    let totalAll = 0;
    const parents = new Set();
    document.querySelectorAll('.child-menu-cb').forEach(cb => parents.add(cb.dataset.parent));

    parents.forEach(parentKey => {
        const children = document.querySelectorAll('.child-menu-cb[data-parent="' + parentKey + '"]');
        const checked = Array.from(children).filter(cb => cb.checked).length;
        const total = children.length;
        const countEl = document.getElementById('count_' + parentKey);
        if (countEl) {
            countEl.textContent = checked + '/' + total;
            countEl.className = 'badge ml-auto group-count ' + (checked === total ? 'badge-success' : checked > 0 ? 'badge-warning' : 'badge-secondary');
        }
        const parentCb = document.getElementById('menu_' + parentKey);
        if (parentCb) {
            parentCb.checked = checked > 0;
            parentCb.indeterminate = checked > 0 && checked < total;
        }
        totalChecked += checked;
        totalAll += total;
    });

    document.getElementById('menuCountBadge').textContent = totalChecked + '/' + totalAll;
}

document.addEventListener('DOMContentLoaded', function() {
    updateCounts();
});
</script>
@endpush
@endsection
