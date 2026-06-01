@extends('layouts.admin')

@section('title', 'Edit Company - ' . $company->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">Companies</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<form action="{{ route('superadmin.companies.update', $company) }}" method="POST">
    @csrf
    @method('PUT')

    {{-- ══════════════════════════════════════════════════
         ROW 1 : Company Info  |  Admin Credentials
    ══════════════════════════════════════════════════ --}}
    <div class="row">
        {{-- Left: Company Info --}}
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-building mr-2"></i>Company Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $company->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Company Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $company->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $company->phone) }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="address">Address</label>
                        <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $company->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Admin Credentials + Save --}}
        <div class="col-md-4">
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-shield mr-2"></i>Admin Login Credentials</h3>
                </div>
                <div class="card-body">
                    @if($adminUser)
                    <p class="text-muted small mb-3">Current admin: <strong>{{ $adminUser->email }}</strong> — Only fill in fields you wish to change; leave the rest blank.</p>
                    <div class="form-group">
                        <label>Admin Name</label>
                        <input type="text" name="admin_name" class="form-control" value="{{ old('admin_name', $adminUser->name) }}" placeholder="Admin full name">
                    </div>
                    <div class="form-group">
                        <label>Login Email</label>
                        <input type="email" name="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email', $adminUser->email) }}" placeholder="admin@example.com">
                        @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label>New Password <small class="text-muted">(leave blank to keep unchanged)</small></label>
                        <input type="password" name="admin_password" class="form-control @error('admin_password') is-invalid @enderror" placeholder="Enter new password">
                        @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @else
                    <div class="alert alert-warning py-2 mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i> No admin user found. Fill in below to create one.
                    </div>
                    <div class="form-group">
                        <label>Admin Name <span class="text-danger">*</span></label>
                        <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" placeholder="Admin full name">
                        @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Login Email <span class="text-danger">*</span></label>
                        <input type="email" name="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" placeholder="admin@example.com">
                        @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label>Password <span class="text-danger">*</span></label>
                        <input type="password" name="admin_password" class="form-control @error('admin_password') is-invalid @enderror" placeholder="Minimum 8 characters">
                        @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-save mr-2"></i>Update Company
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         Subscription Settings — compact horizontal strip
    ══════════════════════════════════════════════════ --}}
    <div class="card card-success card-outline mt-3">
        <div class="card-header py-2">
            <h3 class="card-title" style="font-size:13px;"><i class="fas fa-cog mr-2"></i>Subscription Settings</h3>
        </div>
        <div class="card-body py-2">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label class="small mb-1" for="max_clients">Max Clients <span class="text-muted">(0 = unlimited)</span></label>
                        <input type="number" name="max_clients" id="max_clients" class="form-control form-control-sm" value="{{ old('max_clients', $company->max_clients) }}" min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label class="small mb-1" for="subscription_start">Subscription Start</label>
                        <input type="date" name="subscription_start" id="subscription_start" class="form-control form-control-sm" value="{{ old('subscription_start', $company->subscription_start ? $company->subscription_start->format('Y-m-d') : '') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label class="small mb-1" for="subscription_end">Subscription End <span class="text-muted">(blank = unlimited)</span></label>
                        <input type="date" name="subscription_end" id="subscription_end" class="form-control form-control-sm" value="{{ old('subscription_end', $company->subscription_end ? $company->subscription_end->format('Y-m-d') : '') }}">
                    </div>
                </div>
                <div class="col-md-3 text-muted" style="font-size:12px;">
                    @if($company->subscription_end)
                        @php $daysLeft = now()->diffInDays($company->subscription_end, false); @endphp
                        @if($daysLeft < 0)
                            <span class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i>Expired {{ abs($daysLeft) }} days ago</span>
                        @elseif($daysLeft <= 7)
                            <span class="text-warning"><i class="fas fa-clock mr-1"></i>Expires in {{ $daysLeft }} days</span>
                        @else
                            <span class="text-success"><i class="fas fa-check-circle mr-1"></i>{{ $daysLeft }} days remaining</span>
                        @endif
                    @else
                        <span class="text-info"><i class="fas fa-infinity mr-1"></i>No expiry</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         ROW 2 : Menu Permissions  |  Page Feature Permissions
    ══════════════════════════════════════════════════ --}}
    <div class="row mt-3">

        {{-- Left: Menu Permissions --}}
        <div class="col-md-6">
            @php
                $companyMenus = $company->allowed_menus ?? [];
                $hasNoMenuRestriction = empty($companyMenus);
            @endphp
            <div class="card card-warning card-outline h-100">
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
                                <input type="checkbox" class="custom-control-input child-menu-cb" id="menu_{{ str_replace('.', '_', $itemKey) }}" name="allowed_menus[]" value="{{ $itemKey }}" data-parent="{{ $parentKey }}" {{ $hasNoMenuRestriction || in_array($itemKey, $companyMenus) ? 'checked' : '' }} onchange="updateCounts()">
                                <label class="custom-control-label" for="menu_{{ str_replace('.', '_', $itemKey) }}">{{ $itemLabel }}</label>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <input type="checkbox" name="allowed_menus[]" value="{{ $parentKey }}" class="child-menu-cb d-none" data-parent="{{ $parentKey }}" {{ $hasNoMenuRestriction || in_array($parentKey, $companyMenus) ? 'checked' : '' }}>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: Page Feature Permissions --}}
        <div class="col-md-6">
            @if(isset($pageFeatures))
            <div class="card card-info card-outline h-100">
                <div class="card-header">
                    <h3 class="card-title" style="font-size:13px;"><i class="fas fa-sliders-h mr-2"></i>Page Feature Permissions
                        <span class="badge badge-info ml-2" id="pfCountBadge">0</span>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool text-success" onclick="toggleAllPageFeatures(true)"><i class="fas fa-check-double"></i> Select All</button>
                        <button type="button" class="btn btn-tool text-danger" onclick="toggleAllPageFeatures(false)"><i class="fas fa-times"></i> None</button>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @php $companyPageFeatures = $company->page_features ?? []; $hasNoPFRestriction = empty($companyPageFeatures); @endphp

                    @foreach($pageFeatures as $parentKey => $parentGroup)
                    <div class="pf-parent-group border-bottom">
                        <div class="d-flex align-items-center px-3 py-2" style="background:#f4f6f9;cursor:pointer;" onclick="togglePFParentGroup('{{ $parentKey }}')">
                            <div class="custom-control custom-checkbox mr-2" onclick="event.stopPropagation()">
                                <input type="checkbox" class="custom-control-input pf-top-cb" id="pftop_{{ $parentKey }}" data-pftop="{{ $parentKey }}"
                                    onchange="togglePFTopParent('{{ $parentKey }}', this.checked)">
                                <label class="custom-control-label" for="pftop_{{ $parentKey }}">&nbsp;</label>
                            </div>
                            <i class="{{ $parentGroup['icon'] }} mr-2 text-muted" style="width:16px;text-align:center;"></i>
                            <strong style="font-size:13px;">{{ $parentGroup['label'] }}</strong>
                            <span class="badge badge-secondary ml-auto pf-top-count" id="pftopcount_{{ $parentKey }}">0/0</span>
                            <i class="fas fa-chevron-down ml-2 text-muted" id="pftopicon_{{ $parentKey }}" style="font-size:11px;"></i>
                        </div>
                        <div id="pftopitems_{{ $parentKey }}" style="display:none;background:#fafbfc;">
                            @foreach($parentGroup['pages'] as $pageKey => $pageGroup)
                            <div class="pf-page-group border-top" style="border-color:#eee!important;">
                                <div class="d-flex align-items-center px-4 py-2" style="background:#fafbfc;cursor:pointer;" onclick="togglePFPageGroup('{{ $parentKey }}_{{ str_replace('.','_',$pageKey) }}')">
                                    <div class="custom-control custom-checkbox mr-2" onclick="event.stopPropagation()">
                                        <input type="checkbox" class="custom-control-input pf-page-cb" id="pfpage_{{ str_replace('.','_',$pageKey) }}"
                                            data-pftop="{{ $parentKey }}" data-pfpage="{{ str_replace('.','_',$pageKey) }}"
                                            onchange="togglePFPageParent('{{ str_replace('.','_',$pageKey) }}', this.checked)">
                                        <label class="custom-control-label" for="pfpage_{{ str_replace('.','_',$pageKey) }}">&nbsp;</label>
                                    </div>
                                    <i class="fas fa-file-alt mr-2 text-info" style="font-size:11px;width:14px;text-align:center;"></i>
                                    <span style="font-size:12.5px;font-weight:600;color:#444;">{{ $pageGroup['label'] }}</span>
                                    <span class="badge badge-secondary ml-auto pf-page-count" id="pfpagecount_{{ str_replace('.','_',$pageKey) }}">0/{{ count($pageGroup['features']) }}</span>
                                    <i class="fas fa-chevron-down ml-2 text-muted" id="pfpageicon_{{ str_replace('.','_',$pageKey) }}" style="font-size:10px;"></i>
                                </div>
                                <div id="pfpageitems_{{ $parentKey }}_{{ str_replace('.','_',$pageKey) }}" style="display:none;">
                                    <div class="row pl-5 pr-3 py-2">
                                        @foreach($pageGroup['features'] as $featureKey => $featureLabel)
                                        <div class="col-md-6 col-sm-6">
                                            <div class="custom-control custom-checkbox mb-1">
                                                <input type="checkbox"
                                                    class="custom-control-input pf-feat-cb"
                                                    id="pff_{{ str_replace('.','_',$featureKey) }}"
                                                    name="page_features[]"
                                                    value="{{ $featureKey }}"
                                                    data-pftop="{{ $parentKey }}"
                                                    data-pfpage="{{ str_replace('.','_',$pageKey) }}"
                                                    {{ $hasNoPFRestriction || in_array($featureKey, $companyPageFeatures) ? 'checked' : '' }}
                                                    onchange="updatePFCounts()">
                                                <label class="custom-control-label" for="pff_{{ str_replace('.','_',$featureKey) }}" style="font-size:11.5px;">{{ $featureLabel }}</label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>{{-- end row 2 --}}
    {{-- ══════════════════════════════════════════════════
         ROW 3 : Enabled Features / Modules
    ══════════════════════════════════════════════════ --}}
    <div class="card card-success card-outline mt-3">
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
                $companyFeatures = $company->enabled_features ?? [];
                $hasNoRestriction = empty($companyFeatures);
            @endphp
            @foreach($featureGroups as $groupName => $groupKeys)
            <div class="border-bottom">
                <div class="px-3 py-2 bg-light d-flex align-items-center">
                    <div class="custom-control custom-checkbox mr-2">
                        <input type="checkbox" class="custom-control-input group-feature-cb" id="fgrp_{{ Str::slug($groupName) }}" data-group="{{ Str::slug($groupName) }}" onchange="toggleFeatureGroup('{{ Str::slug($groupName) }}', this.checked)">
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
                                <input type="checkbox" class="custom-control-input feature-checkbox fgrp-{{ Str::slug($groupName) }}" id="feature_{{ $key }}" name="enabled_features[]" value="{{ $key }}" {{ ($hasNoRestriction || in_array($key, $companyFeatures)) ? 'checked' : '' }} onchange="updateGroupCheckboxes()">
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


function toggleAllPageFeatures(checked) {
    document.querySelectorAll('.pf-feat-cb').forEach(cb => cb.checked = checked);
    updatePFCounts();
}

function togglePFTopParent(topKey, checked) {
    document.querySelectorAll('.pf-feat-cb[data-pftop="' + topKey + '"]').forEach(cb => cb.checked = checked);
    document.querySelectorAll('.pf-page-cb[data-pftop="' + topKey + '"]').forEach(cb => { cb.checked = checked; cb.indeterminate = false; });
    updatePFCounts();
}

function togglePFPageParent(pageKey, checked) {
    document.querySelectorAll('.pf-feat-cb[data-pfpage="' + pageKey + '"]').forEach(cb => cb.checked = checked);
    updatePFCounts();
}

function togglePFParentGroup(topKey) {
    const el   = document.getElementById('pftopitems_' + topKey);
    const icon = document.getElementById('pftopicon_' + topKey);
    if (!el) return;
    const open = el.style.display === 'block';
    el.style.display = open ? 'none' : 'block';
    icon.classList.toggle('fa-chevron-down', open);
    icon.classList.toggle('fa-chevron-up', !open);
}

function togglePFPageGroup(compositeKey) {
    // compositeKey = topKey + '_' + pageKey (dots replaced by _)
    const parts  = compositeKey.split('_');
    // Find items div
    const itemsEl = document.getElementById('pfpageitems_' + compositeKey);
    const iconEl  = document.getElementById('pfpageicon_' + compositeKey.replace(/^[^_]+_/, ''));
    if (!itemsEl) return;
    const open = itemsEl.style.display === 'block';
    itemsEl.style.display = open ? 'none' : 'block';
    if (iconEl) { iconEl.classList.toggle('fa-chevron-down', open); iconEl.classList.toggle('fa-chevron-up', !open); }
}

function updatePFCounts() {
    let grandTotal = 0, grandChecked = 0;

    // Collect unique top-level keys
    const topKeys = new Set();
    document.querySelectorAll('.pf-feat-cb').forEach(cb => topKeys.add(cb.dataset.pftop));

    topKeys.forEach(function(topKey) {
        let topTotal = 0, topChecked = 0;

        // Collect page keys under this top
        const pageKeys = new Set();
        document.querySelectorAll('.pf-feat-cb[data-pftop="' + topKey + '"]').forEach(cb => pageKeys.add(cb.dataset.pfpage));

        pageKeys.forEach(function(pageKey) {
            const feats   = document.querySelectorAll('.pf-feat-cb[data-pfpage="' + pageKey + '"]');
            const checked = Array.from(feats).filter(cb => cb.checked).length;
            const total   = feats.length;

            // Update page-level badge
            const pageBadge = document.getElementById('pfpagecount_' + pageKey);
            if (pageBadge) {
                pageBadge.textContent = checked + '/' + total;
                pageBadge.className = 'badge ml-auto pf-page-count ' + (checked === total ? 'badge-success' : checked > 0 ? 'badge-warning' : 'badge-secondary');
            }

            // Update page-level checkbox
            const pageCb = document.getElementById('pfpage_' + pageKey);
            if (pageCb) { pageCb.checked = checked > 0; pageCb.indeterminate = checked > 0 && checked < total; }

            topTotal   += total;
            topChecked += checked;
        });

        // Update top-level badge
        const topBadge = document.getElementById('pftopcount_' + topKey);
        if (topBadge) {
            topBadge.textContent = topChecked + '/' + topTotal;
            topBadge.className = 'badge ml-auto pf-top-count ' + (topChecked === topTotal ? 'badge-success' : topChecked > 0 ? 'badge-warning' : 'badge-secondary');
        }

        // Update top-level checkbox
        const topCb = document.getElementById('pftop_' + topKey);
        if (topCb) { topCb.checked = topChecked > 0; topCb.indeterminate = topChecked > 0 && topChecked < topTotal; }

        grandTotal   += topTotal;
        grandChecked += topChecked;
    });

    const badge = document.getElementById('pfCountBadge');
    if (badge) badge.textContent = grandChecked + '/' + grandTotal;
}

document.addEventListener('DOMContentLoaded', function() {
    updateCounts();
    updateGroupCheckboxes();
    updatePFCounts();
});
</script>
@endpush

@endsection
