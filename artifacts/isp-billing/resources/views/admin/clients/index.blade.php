@extends('layouts.admin')

@section('title', 'Client List')
@section('breadcrumb')
<li class="breadcrumb-item active">Clients</li>
@endsection

@push('styles')
<style>
/* ── Client list footer: force identical font on both sides ── */
#client-tbl-footer,
#client-tbl-footer span,
#client-tbl-footer div {
  font-size: 13px !important;
  font-weight: 400 !important;
  color: #6c757d !important;
}
.mk-toggle {
    position: relative;
    display: inline-block;
    width: 30px;
    height: 15px;
    vertical-align: middle;
    cursor: pointer;
}
.mk-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.mk-slider {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #dc3545;
    border-radius: 15px;
    transition: .3s;
}
.mk-slider:before {
    position: absolute;
    content: "";
    height: 11px;
    width: 11px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    border-radius: 50%;
    transition: .3s;
}
.mk-toggle input:checked + .mk-slider {
    background-color: #28a745;
}
.mk-toggle input:checked + .mk-slider:before {
    transform: translateX(15px);
}
.mk-toggle input:disabled + .mk-slider {
    opacity: 0.5;
    cursor: not-allowed;
}
.mk-label {
    font-size: 11px;
    font-weight: bold;
    display: inline-block;
    vertical-align: middle;
    margin-left: 3px;
}
.stats-summary {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}
.stats-summary .stat-item {
    font-size: 13px;
}
.stats-summary .stat-label {
    font-weight: 600;
}
.stats-summary .stat-value {
    font-weight: bold;
}
.filter-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.filter-buttons .btn {
    padding: 0 10px;
    font-size: 12px;
    height: 31px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.table thead th {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
    color: white !important;
    font-weight: 500;
    font-size: 14px;
    white-space: nowrap;
    border-color: #1e3c72 !important;
}
.sortable-th { cursor: pointer; user-select: none; }
.sortable-th:hover { background: linear-gradient(135deg, #16305e 0%, #1e3c72 100%) !important; }
.sort-arrow { margin-left: 4px; opacity: 0.3; font-size: 11px; }
.active-sort { opacity: 1 !important; color: #ffd700 !important; }
.table td {
    font-size: 14px;
    vertical-align: middle;
}
.action-btns .btn {
    width: 29px;
    height: 29px;
    padding: 0;
    margin: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 14px;
    font-weight: 500;
    min-width: 75px;
    display: inline-block;
    text-align: center;
}
.client-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.client-card .client-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}
.client-card .client-name {
    font-weight: 700;
    font-size: 16px;
    color: #1a202c;
}
.client-card .client-id {
    font-size: 12px;
    color: #718096;
    margin-top: 3px;
}
.client-card .client-info {
    font-size: 13px;
    color: #4a5568;
    line-height: 1.7;
    margin-bottom: 12px;
}
.client-card .client-info strong {
    color: #2d3748;
    font-weight: 600;
}
.client-card .client-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.client-card .client-actions .btn {
    flex: 1;
    min-width: 70px;
    font-size: 11px;
    padding: 6px 8px;
}
.mobile-cards {
    display: none;
}
.search-form-mobile {
    display: none;
}
.table-responsive {
    overflow-x: auto;
    overflow-y: visible;
}
@media (max-width: 991px) {
    .table-responsive {
        display: none;
    }
    .mobile-cards {
        display: block;
    }
    .search-form-mobile {
        display: block;
        margin-bottom: 15px;
    }
    .search-form-desktop {
        display: none;
    }
    .filter-buttons {
        width: 100%;
        justify-content: flex-start;
    }
    .filter-buttons .btn {
        flex: 1;
        min-width: auto;
        text-align: center;
    }
    .card-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .card-header .card-title {
        margin-bottom: 10px;
    }
}
/* Detail popup triggers */
td.pop-trigger { cursor: pointer; transition: background .12s; }
#scrollTopBtn {
    position: fixed;
    bottom: 32px;
    right: 32px;
    z-index: 9999;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #2a5298;
    color: #fff;
    border: none;
    box-shadow: 0 4px 16px rgba(42,82,152,0.35);
    font-size: 18px;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    transition: opacity .2s, transform .2s;
    opacity: 0.92;
}
#scrollTopBtn:hover { background: #1a3a7a; opacity: 1; transform: translateY(-2px); }
#clientsTable tbody tr:hover td { background-color: #eef4ff !important; transition: background-color .1s; }

/* Action dropdown box highlight */

.client-action-menu {
    border-top: 3px solid #2a5298 !important;
    box-shadow: 0 10px 36px rgba(42,82,152,0.28), 0 3px 10px rgba(0,0,0,0.14) !important;
    border-radius: 0 0 10px 10px !important;
    border: 1px solid rgba(42,82,152,0.15) !important;
    border-top: 3px solid #2a5298 !important;
    background: #fff !important;
}
.client-action-menu .dropdown-item {
    transition: background-color .12s ease, border-left .12s ease, color .12s ease;
    border-left: 3px solid transparent !important;
    padding: 3px 8px 3px 8px !important;
    font-size: 11px !important;
    color: #2c3e50 !important;
    background-color: transparent !important;
    cursor: pointer;
}
.client-action-menu .dropdown-item:hover,
.client-action-menu .dropdown-item:focus,
.client-action-menu a.dropdown-item:hover,
.client-action-menu button.dropdown-item:hover {
    background: linear-gradient(90deg, #dbeafe 0%, #eff6ff 100%) !important;
    border-left: 3px solid #2a5298 !important;
    color: #1a3a7a !important;
    padding-left: 11px !important;
    outline: none !important;
}
.client-action-menu .dropdown-item.text-danger,
.client-action-menu a.dropdown-item.text-danger {
    color: #dc3545 !important;
}
.client-action-menu .dropdown-item.text-danger:hover,
.client-action-menu a.dropdown-item.text-danger:hover {
    background: linear-gradient(90deg, #fee2e2 0%, #fff5f5 100%) !important;
    border-left: 3px solid #dc3545 !important;
    color: #b91c1c !important;
}
#clientsTable .dropdown.show .dropdown-toggle {
    background: #1a3a7a !important;
    color: #fff !important;
    box-shadow: 0 0 0 3px rgba(42,82,152,0.35) !important;
    border-radius: 6px !important;
}
.status-dot {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    margin-right: 5px;
    vertical-align: middle;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(255,255,255,0.8);
}
.status-dot.dot-active   { background: #28a745; box-shadow: 0 0 0 2px rgba(40,167,69,0.25); }  /* Online */
.status-dot.dot-due      { background: #dc3545; box-shadow: 0 0 0 2px rgba(220,53,69,0.25); }  /* Active+Offline */
.status-dot.dot-inactive { background: #fd7e14; box-shadow: 0 0 0 2px rgba(253,126,20,0.25); }  /* Inactive */
.status-dot.dot-other    { background: #6c757d; }
.expiry-badge {
    display: inline-block;
    font-size: 9px;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 4px;
    margin-left: 5px;
    vertical-align: middle;
    letter-spacing: .3px;
    white-space: nowrap;
}
.expiry-badge.exp-today   { background: #dc3545; color: #fff; }
.expiry-badge.exp-warning { background: #fd7e14; color: #fff; }
.expiry-badge.exp-soon    { background: #ffc107; color: #212529; }
td.pop-trigger:hover { background: rgba(0,123,255,0.06) !important; }
.popover { max-width: 320px !important; font-size: 13px !important; }
.popover-body { line-height: 1.9; padding: 10px 14px !important; }
.popover-body div { padding: 1px 0; }
table td { vertical-align: middle !important; }
</style>

@endpush

@section('content')
@php
    $__company = \App\Services\TenantContext::get();
    $__maxClients = $__company ? $__company->max_clients : 0;
    $__clientCount = $__company ? $__company->clientCount() : 0;
    $__canAdd = $__company ? $__company->canAddMoreClients() : true;
    $__hasLimit = $__maxClients > 0;
@endphp

@if($__hasLimit)
<div class="alert {{ $__canAdd ? 'alert-info' : 'alert-danger' }} d-flex align-items-center mb-3" style="border-radius: 8px; border: none; {{ $__canAdd ? 'background: linear-gradient(135deg, #e3f2fd, #bbdefb);' : 'background: linear-gradient(135deg, #ffebee, #ffcdd2);' }}">
    <i class="fas {{ $__canAdd ? 'fa-info-circle text-primary' : 'fa-exclamation-triangle text-danger' }} mr-3" style="font-size: 24px;"></i>
    <div>
        <strong>Client Limit:</strong> {{ $__clientCount }} / {{ $__maxClients }} clients used
        @if(!$__canAdd)
            <span class="text-danger ml-2">- Limit reached! You cannot add new clients. Please contact support to upgrade your plan.</span>
        @else
            <span class="text-muted ml-2">- {{ $__maxClients - $__clientCount }} remaining</span>
        @endif
    </div>
    <div class="ml-auto">
        <div class="progress" style="width: 120px; height: 8px; border-radius: 4px;">
            <div class="progress-bar {{ $__clientCount >= $__maxClients ? 'bg-danger' : ($__clientCount >= $__maxClients * 0.8 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $__maxClients > 0 ? min(($__clientCount / $__maxClients) * 100, 100) : 0 }}%; border-radius: 4px;"></div>
        </div>
    </div>
</div>
@endif

<div style="margin-bottom:0;">
    <div class="d-flex align-items-center justify-content-center mb-2" style="cursor:pointer;" onclick="var c=document.getElementById('summaryCardsCollapse');var ic=document.getElementById('summaryToggleIcon');if(c.style.display==='none'){c.style.display='';ic.textContent='▲';}else{c.style.display='none';ic.textContent='▼';}" title="Show/Hide summary">
        <i class="fas fa-users text-primary mr-2"></i>
        <span class="text-muted small font-weight-bold mr-2">Client Summary</span>
        <span id="summaryToggleIcon" class="text-muted small">▼</span>
    </div>
    <div id="summaryCardsCollapse" style="display:none;">
<div class="row">
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="sb-online">{{ $onlineClients ?? 0 }}</h3>
                <p>Online</p>
            </div>
            <div class="icon"><i class="fas fa-wifi"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3 id="sb-offline">{{ $offlineClients ?? 0 }}</h3>
                <p>Offline</p>
            </div>
            <div class="icon"><i class="fas fa-plug"></i></div>
            
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="sb-active">{{ $activeClients ?? 0 }}</h3>
                <p>Active</p>
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="sb-inactive">{{ $inactiveClients ?? 0 }}</h3>
                <p>Inactive</p>
            </div>
            <div class="icon"><i class="fas fa-user-xmark"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3 id="sb-monthly-new">{{ $monthlyNewClients ?? 0 }}</h3>
                <p>New This Month</p>
            </div>
            <div class="icon"><i class="fas fa-user-plus"></i></div>
            
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="sb-monthly-inactive">{{ $monthlyInactiveClients ?? 0 }}</h3>
                <p>Inactive This Month</p>
            </div>
            <div class="icon"><i class="fas fa-user-slash"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box" style="background:#6f42c1;">
            <div class="inner">
                <h3 id="sb-today-inactive" style="color:#fff;">{{ $todayInactiveClients ?? 0 }}</h3>
                <p style="color:#e8d5ff;">Today's Inactive</p>
            </div>
            <div class="icon"><i class="fas fa-user-minus" style="color:rgba(255,255,255,0.3);"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box" style="background:#20c997;">
            <div class="inner">
                <h3 id="sb-today-active" style="color:#fff;">{{ $todayActiveClients ?? 0 }}</h3>
                <p style="color:#d4f5eb;">Today's Active</p>
            </div>
            <div class="icon"><i class="fas fa-user-check" style="color:rgba(255,255,255,0.3);"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box" style="background:#fd7e14;">
            <div class="inner">
                <h3 id="sb-today-new" style="color:#fff;">{{ $todayNewClients ?? 0 }}</h3>
                <p style="color:#ffe8cc;">Today's New</p>
            </div>
            <div class="icon"><i class="fas fa-user-plus" style="color:rgba(255,255,255,0.3);"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-3">
        <div class="small-box" style="background:#343a40;">
            <div class="inner">
                <h3 id="sb-total" style="color:#fff;">{{ $totalClients ?? 0 }}</h3>
                <p style="color:#adb5bd;">Total Clients</p>
            </div>
            <div class="icon"><i class="fas fa-users" style="color:rgba(255,255,255,0.3);"></i></div>
            
        </div>
    </div>
</div>

    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <div class="d-flex align-items-center mb-2" style="gap:10px;">
            <h3 class="card-title mb-0">
                <i class="fas fa-users mr-2"></i>Clients
            </h3>
        </div>
        <div class="filter-buttons">
            @if($__canAction('client_list', 'add_new'))
            @if($__canAdd)
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Add</span>
            </a>
            @else
            <button class="btn btn-secondary btn-sm" disabled title="Client limit reached ({{ $__clientCount }}/{{ $__maxClients }})">
                <i class="fas fa-lock"></i> <span class="d-none d-md-inline">Limit Reached</span>
            </button>
            @endif
            @endif
            @php
                $__companyCtx = array_filter(['company_id' => request('company_id'), 'all_co' => request('all_co')]);
            @endphp
            <a href="{{ route('admin.clients.index', $__companyCtx) }}" class="btn btn-info btn-sm {{ !request('status') && !request('inst_due') && !request('free_filter') && !request('free_monthly') ? 'active' : '' }}">
                All
            </a>
            <a href="{{ route('admin.clients.index', array_merge($__companyCtx, ['status' => 'active'])) }}" class="btn btn-success btn-sm {{ request('status') == 'active' ? 'active' : '' }}">
                Active
            </a>
            <a href="{{ route('admin.clients.index', array_merge($__companyCtx, ['status' => 'inactive'])) }}" class="btn btn-danger btn-sm {{ request('status') == 'inactive' ? 'active' : '' }}">
                Inactive
            </a>
            <a href="{{ route('admin.clients.index', array_merge($__companyCtx, ['status' => 'due'])) }}" class="btn btn-warning btn-sm {{ request('status') == 'due' ? 'active' : '' }}">
                Due
            </a>
            <a href="{{ route('admin.clients.index', array_merge($__companyCtx, ['inst_due' => '1'])) }}" class="btn btn-sm {{ request('inst_due') == '1' ? 'active' : '' }}" style="background-color:#c0392b;color:#fff;border-color:#c0392b;">
                <i class="fas fa-tools mr-1"></i>Inst Due
            </a>
            <a href="{{ route('admin.clients.index', array_merge($__companyCtx, ['free_filter' => 'free'])) }}" class="btn btn-sm {{ request('free_filter') == 'free' ? 'active' : '' }}" style="background-color:#27ae60;color:#fff;border-color:#27ae60;">
                <i class="fas fa-gift mr-1"></i>INST FREE
            </a>
            <a href="{{ route('admin.clients.index', array_merge($__companyCtx, ['free_monthly' => '1'])) }}" class="btn btn-sm {{ request('free_monthly') == '1' ? 'active' : '' }}" style="background-color:#1a9e4a;color:#fff;border-color:#1a9e4a;">
                <i class="fas fa-hand-holding-heart mr-1"></i>Free Bill
            </a>
            @if(!empty($isSuperAdmin))
            <span style="display:inline-block;width:1px;height:22px;background:rgba(0,0,0,0.18);margin:0 5px;vertical-align:middle;"></span>
            @php
                $__cParams = array_filter(['status' => request('status'), 'inst_due' => request('inst_due'), 'free_filter' => request('free_filter'), 'free_monthly' => request('free_monthly')]);
                $__isSuperAdminView = !request('company_id') && !request('all_co');
                if (request('all_co')) {
                    $__dropLabel = 'All Companies';
                    $__dropIcon  = 'fa-th-large';
                } elseif (request('company_id') && isset($companies)) {
                    $__dropLabel = $companies->firstWhere('id', request('company_id'))->name ?? 'Company';
                    $__dropIcon  = 'fa-building';
                } else {
                    $__dropLabel = 'Super Admin';
                    $__dropIcon  = 'fa-user-shield';
                }
            @endphp
            {{-- Single company/super-admin dropdown — only for super admin --}}
            <div class="dropdown d-inline-block" style="vertical-align:middle;">
                <button class="btn btn-sm dropdown-toggle"
                    type="button" id="companyDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    style="background:#8e44ad;color:#fff;border-color:#8e44ad;font-weight:700;padding:.25rem .6rem;line-height:1.5;height:auto;">
                    <i class="fas {{ $__dropIcon }} mr-1"></i>{{ $__dropLabel }}
                </button>
                <div class="dropdown-menu" aria-labelledby="companyDropdown" style="min-width:170px;">
                    {{-- Super Admin (default) --}}
                    <a class="dropdown-item company-switch-btn {{ $__isSuperAdminView ? 'active' : '' }}"
                       href="{{ route('admin.clients.index', $__cParams) }}"
                       data-company-id="" data-all-co="" data-label="Super Admin" data-icon="fa-user-shield">
                        <i class="fas fa-user-shield mr-1 text-danger"></i>Super Admin
                    </a>
                    @if(isset($companies) && $companies->count() > 0)
                    <div class="dropdown-divider"></div>
                    <span class="dropdown-header" style="font-size:11px;padding:4px 16px;color:#888;">Companies</span>
                    <a class="dropdown-item company-switch-btn {{ request('all_co') ? 'active' : '' }}"
                       href="{{ route('admin.clients.index', array_merge($__cParams, ['all_co' => '1'])) }}"
                       data-company-id="" data-all-co="1" data-label="All Companies" data-icon="fa-th-large">
                        <i class="fas fa-th-large mr-1 text-secondary"></i>All Companies
                    </a>
                    @foreach($companies as $__co)
                    <a class="dropdown-item company-switch-btn {{ request('company_id') == $__co->id ? 'active' : '' }}"
                       href="{{ route('admin.clients.index', array_merge($__cParams, ['company_id' => $__co->id])) }}"
                       data-company-id="{{ $__co->id }}" data-all-co="" data-label="{{ $__co->name }}" data-icon="fa-building">
                        <i class="fas fa-building mr-1 text-secondary"></i>{{ $__co->name }}
                    </a>
                    @endforeach
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="stats-summary">
            <div class="stat-item">
                <span class="stat-label">Total:</span>
                <span class="stat-value text-primary" id="stat-total">{{ $totalClients ?? $clients->total() }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Active:</span>
                <span class="stat-value text-success" id="stat-active">{{ $activeClients ?? 0 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Inactive:</span>
                <span class="stat-value text-danger" id="stat-inactive">{{ $inactiveClients ?? 0 }}</span>
            </div>
            <div class="stat-item" style="cursor:pointer;" onclick="window.location='{{ route('admin.clients.index', ['status' => 'due']) }}'">
                <span class="stat-label" style="color:#e6820e;">Due:</span>
                <span class="stat-value text-warning" id="stat-due" style="text-decoration:underline dotted;">{{ $dueClients ?? 0 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Online:</span>
                <span class="stat-value text-success stat-online-count">{{ $onlineClients ?? 0 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Offline:</span>
                <span class="stat-value text-danger stat-offline-count">{{ $offlineClients ?? 0 }}</span>
            </div>
            <div class="stat-item" id="stat-inst-free-wrap" style="cursor:pointer;{{ ($instFreeClients ?? 0) == 0 ? 'display:none;' : '' }}" onclick="window.location='{{ route('admin.clients.index', ['free_filter' => 'free']) }}'">
                <span class="stat-label" style="color:#27ae60;">INST FREE:</span>
                <span class="stat-value" id="stat-inst-free" style="color:#27ae60;text-decoration:underline dotted;">{{ $instFreeClients ?? 0 }}</span>
            </div>
            <div class="stat-item" id="stat-inst-due-wrap" style="cursor:pointer;{{ ($instDueClients ?? 0) == 0 ? 'display:none;' : '' }}" onclick="window.location='{{ route('admin.clients.index', ['inst_due' => '1']) }}'">
                <span class="stat-label" style="color:#c0392b;">INST Due:</span>
                <span class="stat-value" id="stat-inst-due" style="color:#c0392b;text-decoration:underline dotted;">{{ $instDueClients ?? 0 }}</span>
            </div>
        </div>

        <div class="search-form-mobile">
            <div class="mb-2" style="position: relative;">
                <form id="mobileSearchForm" method="GET" action="{{ route('admin.clients.index') }}" style="margin:0;">
                    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                    <div class="input-group">
                        <input type="text" id="mobileQuickSearch" name="search" class="form-control" placeholder="Name, ID, Phone, Username, MAC..." value="{{ request('search') }}" autocomplete="off">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" id="mobileSearchBtn"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
                <div id="mobileSuggestions" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:1050; background:#fff; border:1px solid #ddd; border-top:none; border-radius:0 0 8px 8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); max-height:300px; overflow-y:auto;"></div>
            </div>

            @if($__canAction('client_list', 'filters'))
            <div class="mb-2">
                <button class="btn btn-outline-primary btn-sm btn-block" type="button" data-toggle="collapse" data-target="#mobileAdvancedFilters">
                    <i class="fas fa-filter mr-1"></i>Advanced Filters
                    @php
                        $mobileFilterCount = collect(['zone_id','area_id','package_id','connection_type','connection_medium','mikrotik_device_id','billing_status','payment_status','mikrotik_status','client_type','billing_date','assigned_to','reseller_id','expire_from','expire_to','inst_due'])->filter(fn($f) => request($f))->count();
                    @endphp
                    @if($mobileFilterCount > 0)
                    <span class="badge badge-light ml-1">{{ $mobileFilterCount }}</span>
                    @endif
                </button>
            </div>

            <div class="collapse {{ $mobileFilterCount > 0 ? 'show' : '' }}" id="mobileAdvancedFilters">
                <form method="GET" action="{{ route('admin.clients.index') }}" class="mb-2" style="background: #f0f4f8; padding: 10px; border-radius: 8px; border: 1px solid #d2dae2;">
                    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                    @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                    <div class="row mb-2">
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">MIKROTIK</label>
                            <select name="mikrotik_device_id" class="form-control form-control-sm">
                                <option value="">All Routers</option>
                                @foreach($mikrotikDevices ?? [] as $device)
                                <option value="{{ $device->id }}" {{ request('mikrotik_device_id') == $device->id ? 'selected' : '' }}>{{ $device->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">PROTOCOL</label>
                            <select name="connection_type" class="form-control form-control-sm">
                                <option value="">All Types</option>
                                <option value="pppoe" {{ request('connection_type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="hotspot" {{ request('connection_type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                                <option value="static" {{ request('connection_type') == 'static' ? 'selected' : '' }}>Static</option>
                                <option value="dhcp" {{ request('connection_type') == 'dhcp' ? 'selected' : '' }}>DHCP</option>
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">PACKAGE</label>
                            <select name="package_id" class="form-control form-control-sm">
                                <option value="">All</option>
                                @foreach($packages ?? [] as $package)
                                <option value="{{ $package->id }}" {{ request('package_id') == $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">ZONE</label>
                            <select name="zone_id" class="form-control form-control-sm" onchange="loadMobileFilterAreas(this.value)">
                                <option value="">All Zones</option>
                                @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">SUB ZONE</label>
                            <select name="area_id" id="mobileFilterAreaSelect" class="form-control form-control-sm">
                                <option value="">All Areas</option>
                                @if(request('zone_id') && isset($areas))
                                    @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">BILL DATE</label>
                            <select name="billing_date" class="form-control form-control-sm">
                                <option value="">All</option>
                                @for($d = 1; $d <= 28; $d++)
                                <option value="{{ $d }}" {{ request('billing_date') == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">BILLING</label>
                            <select name="billing_status" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="generated" {{ request('billing_status') == 'generated' ? 'selected' : '' }}>Generated</option>
                                <option value="not_generated" {{ request('billing_status') == 'not_generated' ? 'selected' : '' }}>Not Gen.</option>
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">PAYMENT</label>
                            <select name="payment_status" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="advance" {{ request('payment_status') == 'advance' ? 'selected' : '' }}>Advance</option>
                                <option value="overdue" {{ request('payment_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">MK STATUS</label>
                            <select name="mikrotik_status" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="enabled" {{ request('mikrotik_status') == 'enabled' ? 'selected' : '' }}>Enabled</option>
                                <option value="disabled" {{ request('mikrotik_status') == 'disabled' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">CABLE</label>
                            <select name="connection_medium" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="fiber" {{ request('connection_medium') == 'fiber' ? 'selected' : '' }}>Fiber</option>
                                <option value="utp" {{ request('connection_medium') == 'utp' ? 'selected' : '' }}>UTP</option>
                                <option value="wireless" {{ request('connection_medium') == 'wireless' ? 'selected' : '' }}>Wireless</option>
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">CLIENT TYPE</label>
                            <select name="client_type" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="regular" {{ request('client_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="corporate" {{ request('client_type') == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                <option value="home" {{ request('client_type') == 'home' ? 'selected' : '' }}>Home</option>
                                <option value="business" {{ request('client_type') == 'business' ? 'selected' : '' }}>Business</option>
                                <option value="vip" {{ request('client_type') == 'vip' ? 'selected' : '' }}>VIP</option>
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">DUE</label>
                            <select name="due_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="with_due" {{ request('status') == 'due' ? 'selected' : '' }}>With Due</option>
                                <option value="no_due" {{ request('due_filter') == 'no_due' ? 'selected' : '' }}>No Due</option>
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">EXPIRE FROM</label>
                            <input type="date" name="expire_from" class="form-control form-control-sm" value="{{ request('expire_from') }}">
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">EXPIRE TO</label>
                            <input type="date" name="expire_to" class="form-control form-control-sm" value="{{ request('expire_to') }}">
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">STAFF</label>
                            <select name="assigned_to" class="form-control form-control-sm">
                                <option value="">All</option>
                                @foreach($staffUsers ?? [] as $staff)
                                <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:15px;">RESELLER</label>
                            <select name="reseller_id" class="form-control form-control-sm">
                                <option value="">All</option>
                                @foreach($resellerUsers ?? [] as $reseller)
                                <option value="{{ $reseller->id }}" {{ request('reseller_id') == $reseller->id ? 'selected' : '' }}>{{ $reseller->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="d-flex" style="gap:6px;">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter mr-1"></i>Apply Filters</button>
                        <a href="{{ route('admin.clients.index', request('status') ? ['status' => request('status')] : []) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo"></i> Reset</a>
                    </div>
                </form>
            </div>
            @endif
        </div>
        
        <div class="search-form-desktop mb-3">
            <form method="GET" id="filterForm">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="search" id="filterFormSearchHidden" value="{{ request('search') }}">
                @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                @php
                    $allFilterKeys = ['zone_id','area_id','box_id','package_id','connection_type','connection_medium','mikrotik_device_id','billing_status','payment_status','mikrotik_status','client_type','billing_date','assigned_to','expire_from','expire_to','reseller_id','inst_due','company_id','no_company','all_co'];
                    $hasAnyAdvanced = request()->hasAny(array_diff($allFilterKeys, ['zone_id','area_id','box_id','package_id']));
                    $activeFilterCount = collect($allFilterKeys)->filter(fn($f) => request($f))->count();
                @endphp

                {{-- ── TOP BAR: Search + Filter Toggle + Sort ── --}}
                <div class="d-flex align-items-center mb-2" style="gap:6px; flex-wrap:wrap;">
                    {{-- Search --}}
                    <div style="position:relative; flex:1; min-width:180px; max-width:340px;">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="background:#f8f9fa; border-right:none;">
                                    <i class="fas fa-search text-muted" style="font-size:11px;"></i>
                                </span>
                            </div>
                            <input type="text" id="quickSearch" name="search" class="form-control form-control-sm" placeholder="Name, Phone, ID, MAC..." value="{{ request('search') }}" autocomplete="off" style="border-left:none; font-size:12px;">
                        </div>
                        <div id="searchSuggestions" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:1050; background:#fff; border:1px solid #ddd; border-top:none; border-radius:0 0 6px 6px; box-shadow:0 6px 20px rgba(0,0,0,0.12); max-height:320px; overflow-y:auto;"></div>
                    </div>

                    @if($__canAction('client_list', 'filters'))
                    {{-- Filter toggle --}}
                    <button type="button" id="filterToggleBtn"
                        onclick="var p=document.getElementById('desktopFilterPanel'); var open=p.style.display!=='none'; p.style.display=open?'none':'block'; this.classList.toggle('btn-primary',!open); this.classList.toggle('btn-outline-secondary',open);"
                        class="btn btn-sm {{ $activeFilterCount > 0 ? 'btn-primary' : 'btn-outline-secondary' }}"
                        style="white-space:nowrap; font-size:12px;">
                        <i class="fas fa-sliders-h mr-1"></i>Filters
                        @if($activeFilterCount > 0)
                        <span class="badge badge-light ml-1" style="color:#1a73e8; font-weight:700;">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                    @if($activeFilterCount > 0)
                    <a href="{{ route('admin.clients.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="btn btn-sm btn-outline-danger no-new-tab" title="Clear all filters" style="font-size:12px; white-space:nowrap;">
                        <i class="fas fa-times mr-1"></i>Clear
                    </a>
                    @endif
                    @endif

                    {{-- Sort --}}
                    <div class="ml-auto d-flex align-items-center" style="gap:4px;">
                        <select id="quickSort" class="form-control form-control-sm"
                            style="width:auto;font-size:12px;border-radius:6px;border-color:#ced4da;cursor:pointer;"
                            onchange="(function(sel){var url=new URL(window.location.href);var dirs={'name':'asc','due_amount':'desc','monthly_fee':'desc','connection_date':'desc','expire_date':'asc','billing_date':'asc'};if(sel.value){url.searchParams.set('sort_by',sel.value);url.searchParams.set('sort_dir',dirs[sel.value]||'desc');}else{url.searchParams.delete('sort_by');url.searchParams.delete('sort_dir');}url.searchParams.delete('page');window.location.href=url.toString();})(this);">
                            <option value="">&#8645; Default Order</option>
                            <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>&#8593; Name A-Z</option>
                            <option value="name_desc" {{ request('sort_by') == 'name_desc' ? 'selected' : '' }}>&#8595; Name Z-A</option>
                            <option value="due_amount" {{ request('sort_by') == 'due_amount' ? 'selected' : '' }}>&#8595; Highest Due</option>
                            <option value="monthly_fee" {{ request('sort_by') == 'monthly_fee' ? 'selected' : '' }}>&#8595; Monthly Fee (High)</option>
                            <option value="connection_date" {{ request('sort_by') == 'connection_date' ? 'selected' : '' }}>&#128197; Newest Connection</option>
                            <option value="expire_date" {{ request('sort_by') == 'expire_date' ? 'selected' : '' }}>&#8987; Expiry Soonest</option>
                        </select>
                    </div>
                </div>

                @if($__canAction('client_list', 'filters'))
                {{-- ── COMPACT FILTER PANEL ── --}}
                <div id="desktopFilterPanel" style="display:{{ $activeFilterCount > 0 ? 'block' : 'none' }}; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px 8px; margin-bottom:6px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">

                    <style>
                        .cf-label { display:block; font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:2px; }
                        .cf-item { flex:1; min-width:120px; max-width:180px; }
                        .cf-item .form-control-sm { font-size:12px; height:28px; padding:2px 6px; border-color:#e2e8f0; border-radius:5px; }
                        .cf-item input.form-control-sm { height:28px; }
                        .cf-sep { width:1px; background:#e2e8f0; align-self:stretch; margin:0 6px; flex-shrink:0; }
                        .cf-row { display:flex; flex-wrap:wrap; gap:6px; align-items:flex-end; }
                        .cf-section-label { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.8px; color:#cbd5e1; padding:0 4px 3px 0; white-space:nowrap; align-self:flex-end; padding-bottom:6px; }
                    </style>

                    {{-- Row 1: Location + Connection --}}
                    <div class="cf-row mb-2">
                        <span class="cf-section-label"><i class="fas fa-map-marker-alt mr-1"></i>Location</span>
                        <div class="cf-item">
                            <label class="cf-label">Zone</label>
                            <select name="zone_id" class="form-control form-control-sm" id="zoneFilter" onchange="loadFilterAreas(this.value)">
                                <option value="">All Zones</option>
                                @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">Sub Zone</label>
                            <select name="area_id" class="form-control form-control-sm" id="areaFilter" onchange="loadFilterBoxes(this.value)">
                                <option value="">All Areas</option>
                                @foreach($selectedAreas ?? [] as $a)
                                <option value="{{ $a->id }}" {{ request('area_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">TJ / Box</label>
                            <select name="box_id" class="form-control form-control-sm" id="boxFilter">
                                <option value="">All Boxes</option>
                                @foreach($selectedBoxes ?? [] as $b)
                                <option value="{{ $b->id }}" {{ request('box_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cf-sep"></div>
                        <span class="cf-section-label"><i class="fas fa-network-wired mr-1"></i>Connection</span>
                        <div class="cf-item">
                            <label class="cf-label">Mikrotik</label>
                            <select name="mikrotik_device_id" class="form-control form-control-sm">
                                <option value="">All Devices</option>
                                @foreach($mikrotikDevices ?? [] as $device)
                                <option value="{{ $device->id }}" {{ request('mikrotik_device_id') == $device->id ? 'selected' : '' }}>{{ $device->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">Protocol</label>
                            <select name="connection_type" class="form-control form-control-sm">
                                <option value="">All Types</option>
                                <option value="pppoe" {{ request('connection_type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="hotspot" {{ request('connection_type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                                <option value="static" {{ request('connection_type') == 'static' ? 'selected' : '' }}>Static IP</option>
                                <option value="dhcp" {{ request('connection_type') == 'dhcp' ? 'selected' : '' }}>DHCP</option>
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">Package</label>
                            <select name="package_id" class="form-control form-control-sm">
                                <option value="">All Packages</option>
                                @foreach($packages ?? [] as $package)
                                <option value="{{ $package->id }}" {{ request('package_id') == $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">Cable</label>
                            <select name="connection_medium" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="fiber" {{ request('connection_medium') == 'fiber' ? 'selected' : '' }}>Fiber</option>
                                <option value="utp" {{ request('connection_medium') == 'utp' ? 'selected' : '' }}>UTP</option>
                                <option value="wireless" {{ request('connection_medium') == 'wireless' ? 'selected' : '' }}>Wireless</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Billing + Client + Date --}}
                    <div class="cf-row mb-2">
                        <span class="cf-section-label"><i class="fas fa-file-invoice-dollar mr-1"></i>Billing</span>
                        <div class="cf-item">
                            <label class="cf-label">Bill Date</label>
                            <select name="billing_date" class="form-control form-control-sm">
                                <option value="">Any Day</option>
                                @for($d = 1; $d <= 28; $d++)
                                <option value="{{ $d }}" {{ request('billing_date') == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">Bill Status</label>
                            <select name="billing_status" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="generated" {{ request('billing_status') == 'generated' ? 'selected' : '' }}>Generated</option>
                                <option value="not_generated" {{ request('billing_status') == 'not_generated' ? 'selected' : '' }}>Not Generated</option>
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">Payment</label>
                            <select name="payment_status" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="advance" {{ request('payment_status') == 'advance' ? 'selected' : '' }}>Advance</option>
                                <option value="overdue" {{ request('payment_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">MT Status</label>
                            <select name="mikrotik_status" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="enabled" {{ request('mikrotik_status') == 'enabled' ? 'selected' : '' }}>Enabled</option>
                                <option value="disabled" {{ request('mikrotik_status') == 'disabled' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>

                        <div class="cf-sep"></div>
                        <span class="cf-section-label"><i class="fas fa-user mr-1"></i>Client</span>
                        <div class="cf-item">
                            <label class="cf-label">Type</label>
                            <select name="client_type" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="regular" {{ request('client_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="corporate" {{ request('client_type') == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                <option value="home" {{ request('client_type') == 'home' ? 'selected' : '' }}>Home</option>
                                <option value="business" {{ request('client_type') == 'business' ? 'selected' : '' }}>Business</option>
                                <option value="government" {{ request('client_type') == 'government' ? 'selected' : '' }}>Government</option>
                                <option value="vip" {{ request('client_type') == 'vip' ? 'selected' : '' }}>VIP</option>
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">Staff</label>
                            <select name="assigned_to" class="form-control form-control-sm">
                                <option value="">All Staff</option>
                                @foreach($staffUsers ?? [] as $staff)
                                <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">Reseller</label>
                            <select name="reseller_id" class="form-control form-control-sm">
                                <option value="">All</option>
                                @foreach($resellerUsers ?? [] as $reseller)
                                <option value="{{ $reseller->id }}" {{ request('reseller_id') == $reseller->id ? 'selected' : '' }}>{{ $reseller->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cf-sep"></div>
                        <span class="cf-section-label"><i class="fas fa-calendar mr-1"></i>Expiry</span>
                        <div class="cf-item">
                            <label class="cf-label">From</label>
                            <input type="date" name="expire_from" class="form-control form-control-sm" value="{{ request('expire_from') }}">
                        </div>
                        <div class="cf-item">
                            <label class="cf-label">To</label>
                            <input type="date" name="expire_to" class="form-control form-control-sm" value="{{ request('expire_to') }}">
                        </div>
                    </div>

                    {{-- Action bar --}}
                    <div class="d-flex align-items-center justify-content-between" style="border-top:1px solid #f1f5f9; padding-top:8px; margin-top:2px;">
                        <a href="{{ route('admin.clients.export-filtered', request()->query()) }}"
                           class="btn btn-sm btn-outline-success" style="font-size:12px;">
                            <i class="fas fa-file-csv mr-1"></i>Export CSV
                        </a>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('admin.clients.index', request('status') ? ['status' => request('status')] : []) }}"
                               class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
                                <i class="fas fa-undo mr-1"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-sm btn-primary" style="font-size:12px; padding:4px 16px;">
                                <i class="fas fa-check mr-1"></i>Apply
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Active filter result banner --}}
                @if($activeFilterCount > 0)
                <div class="d-flex align-items-center mb-2" style="font-size:12px; color:#64748b; gap:6px;">
                    <i class="fas fa-info-circle text-primary" style="font-size:11px;"></i>
                    Showing <strong class="text-dark mx-1">{{ $filteredCount ?? 0 }}</strong> clients for {{ $activeFilterCount }} active filter{{ $activeFilterCount > 1 ? 's' : '' }}
                    <a href="{{ route('admin.clients.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="ml-auto btn btn-sm btn-outline-danger py-0 no-new-tab" style="font-size:11px;">
                        <i class="fas fa-times mr-1"></i>Clear All Filters
                    </a>
                </div>
                @endif
                @endif
            </form>

            {{-- Hidden search suggestions form (keeps quickSearch working independently) --}}
            <form id="quickSearchForm" method="GET" action="{{ route('admin.clients.index') }}" style="display:none;">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                @if(request('zone_id'))<input type="hidden" name="zone_id" value="{{ request('zone_id') }}">@endif
                @if(request('package_id'))<input type="hidden" name="package_id" value="{{ request('package_id') }}">@endif
                @if(request('sort_by'))<input type="hidden" name="sort_by" value="{{ request('sort_by') }}">@endif
                @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                <input type="text" name="search" id="quickSearchHiddenInput">
            </form>
        </div>

        <div class="mobile-cards" id="mobileClientCards">
            @forelse($clients as $client)
            @php
    $__billingCalc = \App\Services\BillingCalculatorService::calculate($client);
    $effectiveDue   = $__billingCalc['due'];
    $__advanceLive  = $__billingCalc['advance'];
@endphp
            <div class="client-card" 
                 data-status="{{ $client->status }}" 
                 data-zone="{{ strtolower($client->zone->name ?? '') }}" 
                 data-due="{{ $effectiveDue > 0 ? 'with-due' : 'no-due' }}"
                 data-search="{{ strtolower($client->name.' '.$client->client_id.' '.$client->phone.' '.($client->zone->name ?? '').' '.($client->area->name ?? '').' '.($client->address ?? '').' '.($client->package->name ?? '')) }}">
                <div class="client-header">
                    <div>
                        @php
                            $__aiDay = min($client->payment_deadline ?? 15, 28);
                            $__nowD = \Carbon\Carbon::now('Asia/Dhaka')->startOfDay();
                            $__billingDt = $__nowD->copy()->setDay($__aiDay);
                            if ($__billingDt->lt($__nowD)) { $__billingDt->addMonthNoOverflow(); }
                            $__expDate___daysLeftM = $client->temp_deadline_date
                                ? \Carbon\Carbon::parse($client->temp_deadline_date)->startOfDay()
                                : $__billingDt;
                            $__daysLeftM = $__expDate___daysLeftM ? (int) now()->startOfDay()->diffInDays($__expDate___daysLeftM, false) : null;
                        @endphp
                        <div class="client-name">{{ $client->username ?? $client->client_id }}</div>
                        @if($__daysLeftM !== null && $__daysLeftM <= 3 && $client->status === 'active' && $__advanceLive <= 0)
                            @if($__daysLeftM <= 0)
                                <span class="expiry-badge exp-today">EXPIRED</span>
                            @elseif($__daysLeftM == 1)
                                <span class="expiry-badge exp-warning">EXP TOMORROW</span>
                            @else
                                <span class="expiry-badge exp-soon">EXP {{ $__daysLeftM }}d</span>
                            @endif
                        @endif
                        <div class="client-id">{{ $client->name }} &bull; {{ $client->phone }}</div>
                        @if($client->is_free) <div><span style="font-size:9px;color:#27ae60;font-weight:700;">INST FREE</span></div>
                        @elseif(!$client->signup_fee_paid && ($client->installation_charge ?? 0) > 0) <div><span style="font-size:9px;color:#c0392b;font-weight:700;">INST DUE ৳{{ number_format($client->installation_charge ?? 0, 0) }}</span></div>
                        @endif
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;">
                    @if($client->status == 'active')
                        <span class="mobile-status-badge active">Active</span>
                    @elseif($client->status == 'inactive')
                        <span class="mobile-status-badge inactive">Inactive</span>
                    @elseif($client->status == 'pending')
                        <span class="mobile-status-badge pending">Pending</span>
                    @else
                        <span class="mobile-status-badge secondary">{{ ucfirst($client->status) }}</span>
                    @endif
                    @if($client->connection_status == 'online')
                        <span class="badge" style="background:#00c853;color:#fff;font-size:9px;padding:2px 5px;"><i class="fas fa-circle mr-1" style="font-size:6px;"></i>Online</span>
                    @elseif($client->status == 'active')
                        <span class="badge" style="background:#bdbdbd;color:#fff;font-size:9px;padding:2px 5px;"><i class="fas fa-circle mr-1" style="font-size:6px;"></i>Offline</span>
                    @endif
                    </div>
                </div>
                <div class="client-info">
                    <strong>Package:</strong> {{ $client->package->name ?? '-' }}
                    @if($client->package && $client->package->bandwidth)
                    <small class="text-info"><i class="fas fa-tachometer-alt" style="font-size:9px;"></i> {{ $client->package->bandwidth }}</small>
                    @endif
                    |
                    <strong>Zone:</strong> {{ $client->zone->name ?? '-' }}{{ $client->area ? ' / '.$client->area->name : '' }}{{ $client->box ? ' / TJ: '.$client->box->name : '' }} | 
                    <strong>Net Due:</strong> <span class="{{ $effectiveDue > 0 ? 'text-danger' : 'text-success' }}" style="font-weight: 700;">৳{{ number_format($effectiveDue ?? 0, 0) }}</span>@if($__advanceLive > 0) <span style="color:#16a34a;font-weight:700;font-size:11px;"><i class="fas fa-wallet" style="font-size:9px;"></i> Adv: ৳{{ number_format($__advanceLive, 0) }}</span>@endif
                    @php
                        $cardAiDay = min($client->payment_deadline ?? 15, 28);
                        $cardNow = \Carbon\Carbon::now('Asia/Dhaka');
                        if ($client->status === 'inactive' && $client->last_inactive_date) {
                            $cardAiShow = \Carbon\Carbon::parse($client->last_inactive_date, 'Asia/Dhaka');
                            $cardAiIsInactive = true;
                        } else {
                            $cardAiIsInactive = false;
                            $cardAiDate = $cardNow->day <= $cardAiDay
                                ? \Carbon\Carbon::create($cardNow->year, $cardNow->month, $cardAiDay, 0, 0, 0, 'Asia/Dhaka')
                                : \Carbon\Carbon::create($cardNow->year, $cardNow->month, $cardAiDay, 0, 0, 0, 'Asia/Dhaka')->addMonthNoOverflow();
                            $cardAiShow = $client->temp_deadline_date
                                ? \Carbon\Carbon::parse($client->temp_deadline_date, 'Asia/Dhaka')
                                : $cardAiDate;
                        }
                    @endphp
                    @if($cardAiIsInactive)
                    | <strong class="text-danger">Inactive Since:</strong> <span class="text-danger" style="font-weight: 600;">{{ $cardAiShow->format('d-M-Y') }}</span>
                    @else
                    | <strong>Auto-Inactive:</strong> <span class="text-warning" style="font-weight: 600;">{{ $cardAiShow->format('d-M-Y') }}</span>
                    @if($client->temp_deadline_date)
                    <span class="text-info">(Ext: {{ \Carbon\Carbon::parse($client->temp_deadline_date)->format('d M Y') }})</span>
                    @endif
                    @endif
                    @if($client->expire_date)
                    | <strong>Expiry:</strong> <span class="{{ $client->expire_date->isPast() ? 'text-danger' : 'text-muted' }}">{{ $client->expire_date->format('d M Y') }}</span>
                    @endif
                    @if($__canAction('client_list', 'mikrotik_toggle'))
                    @php
                        $mMkProfile = $client->mikrotik_profile_name ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                        $mMkActive = $client->status === 'active' && $mMkProfile && strtolower($mMkProfile) !== 'inactive';
                    @endphp
                    <br>
                    <label class="mk-toggle mb-0" style="vertical-align: middle;" title="{{ $mMkActive ? 'Active: ' . $mMkProfile : ($client->status !== 'active' ? 'Client Inactive' : 'Inactive Profile') }}">
                        <input type="checkbox" class="mikrotik-toggle" data-client-id="{{ $client->id }}" {{ $mMkActive ? 'checked' : '' }}>
                        <span class="mk-slider"></span>
                    </label>
                    <span class="mk-label {{ $mMkActive ? 'text-success' : 'text-danger' }}" id="mk-label-m-{{ $client->id }}">{{ $mMkActive ? 'ON' : 'OFF' }}</span>
                    @if($client->mikrotikDevice)
                    <small class="text-muted ml-1" style="display:inline-block;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:bottom;" title="{{ $client->mikrotikDevice->name }}">{{ $client->mikrotikDevice->name }}</small>
                    @endif
                    @endif
                    @php
                        $mobileIp = $client->session_ip ?: $client->ip_address;
                        $mobileMac = $client->session_mac ?: $client->mac_address;
                    @endphp
                    @if($client->connection_status == 'online' && $client->session_uptime)
                    @php
                        $uptimeStr2 = $client->session_uptime; $secs2 = 0;
                        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $uptimeStr2, $hms2)) {
                            $secs2 = $hms2[1]*3600 + $hms2[2]*60 + $hms2[3];
                        } else {
                            if(preg_match('/(\d+)w/', $uptimeStr2, $m2)) $secs2 += $m2[1]*604800;
                            if(preg_match('/(\d+)d/', $uptimeStr2, $m2)) $secs2 += $m2[1]*86400;
                            if(preg_match('/(\d+)h/', $uptimeStr2, $m2)) $secs2 += $m2[1]*3600;
                            if(preg_match('/(\d+)m/', $uptimeStr2, $m2)) $secs2 += $m2[1]*60;
                            if(preg_match('/(\d+)s/', $uptimeStr2, $m2)) $secs2 += $m2[1];
                        }
                        if ($client->last_online) { $secs2 += abs((int) \Carbon\Carbon::parse($client->last_online)->diffInSeconds(now())); }
                    @endphp
                    <br><span style="color:#00c853;font-size:11px;"><i class="fas fa-clock mr-1" style="font-size:10px;"></i><strong class="live-uptime" data-uptime-seconds="{{ $secs2 }}"></strong></span>
                    @elseif($client->last_online)
                    <br><span style="color:#999;font-size:11px;"><i class="fas fa-clock mr-1" style="font-size:10px;"></i>Last seen: {{ \Carbon\Carbon::parse($client->last_online)->setTimezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span>
                    @endif
                </div>
                <div class="mobile-action-buttons">
                    @if($__canAction('client_list', 'view_profile'))
                    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-view"><i class="fas fa-eye"></i> <span>View</span></a>
                    @endif
                    @if($__canAction('client_list', 'edit'))
                    <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-edit"><i class="fas fa-edit"></i> <span>Edit</span></a>
                    @endif
                    @if($__canAction('client_list', 'delete'))
                    <a href="{{ route('admin.clients.confirm-delete', $client) }}" class="btn btn-danger btn-sm" style="font-size: 11px; padding: 6px 8px; border-radius: 6px; flex: 1;">
                        <i class="fas fa-trash"></i> <span>Delete</span>
                    </a>
                    @endif
                    @if($__canAction('client_list', 'quick_pay'))
                    <button type="button" class="btn btn-pay quick-pay-open" data-id="{{ $client->id }}" data-name="{{ $client->name }}" data-client-id="{{ $client->client_id }}" data-due="{{ $client->due_amount ?? 0 }}"><i class="fas fa-money-bill"></i> <span>Pay</span></button>
                    @endif
                    @if($__canAction('client_list', 'change_package'))
                    <button type="button" class="btn btn-success btn-sm change-pkg-open" style="font-size: 11px; padding: 6px 8px; border-radius: 6px; flex: 1;" data-id="{{ $client->id }}" data-name="{{ $client->name }}" data-client-id="{{ $client->client_id }}" data-package-id="{{ $client->package_id }}" data-package-name="{{ $client->package->name ?? '-' }}" data-monthly-fee="{{ $client->monthly_fee ?? 0 }}" data-ip="{{ $client->ip_address ?? '' }}" data-profile="{{ $client->mikrotik_profile_name ?? '' }}"><i class="fas fa-exchange-alt"></i> <span>Package</span></button>
                    @endif
                    @if($__canAction('client_list', 'extend_deadline'))
                    @php
                        $mDeadlineDay = min($client->payment_deadline ?? 15, 28);
                        $mBillingDeadline = \Carbon\Carbon::create(now()->year, now()->month, $mDeadlineDay);
                    @endphp
                    <button type="button" class="btn {{ $client->temp_deadline_date ? 'btn-outline-dark' : 'btn-dark' }} btn-sm temp-deadline-btn" style="font-size: 11px; padding: 6px 8px; border-radius: 6px; flex: 1;" data-client-id="{{ $client->id }}" data-client-name="{{ $client->name }}" data-client-status="{{ $client->status }}" data-current-deadline="{{ $client->expire_date ? $client->expire_date->format('Y-m-d') : '' }}" data-temp-deadline="{{ $client->temp_deadline_date ? \Carbon\Carbon::parse($client->temp_deadline_date)->format('Y-m-d') : '' }}" data-billing-deadline="{{ $mBillingDeadline->format('Y-m-d') }}" data-billing-day="{{ $client->billing_date ?? 1 }}">
                        <i class="fas fa-calendar-plus"></i> <span>Extend</span>
                    </button>
                    @endif
                </div>
                @if($__canAction('client_list', 'ledger'))
                <a href="{{ route('admin.clients.ledger', $client) }}" class="btn mobile-action-full"><i class="fas fa-file-alt"></i> Ledger</a>
                @endif
            </div>
            @empty
            <div class="text-center text-muted py-4" id="noClientsMessage">
                <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                <p>No clients found</p>
            </div>
            @endforelse
        </div>

        <div id="clientsTableWrapper">
        <div id="bulkActionBar" class="mb-3 p-2 border rounded d-flex align-items-center flex-wrap" style="display: none !important; background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-color: #90caf9 !important;">
            <span class="mr-3 font-weight-bold"><i class="fas fa-check-square text-primary"></i> <span id="selectedCount">0</span> selected</span>
            <button type="button" class="btn btn-sm btn-success mr-2" id="btnBulkActivate"><i class="fas fa-check-circle"></i> Activate</button>
            <button type="button" class="btn btn-sm btn-danger mr-2" id="btnBulkDeactivate"><i class="fas fa-ban"></i> Deactivate</button>
            <div class="d-inline-flex align-items-center mr-2">
                <select id="bulkPackageSelect" class="form-control form-control-sm mr-1" style="width: 180px;">
                    <option value="">Package</option>
                    @foreach($packages as $pkg)
                    <option value="{{ $pkg->id }}">{{ $pkg->name }} — ৳{{ number_format($pkg->price) }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-info" id="btnBulkChangePackage" title="Change Package"><i class="fas fa-exchange-alt"></i></button>
            </div>
            <div class="d-inline-flex align-items-center mr-2">
                <select id="bulkZoneSelect" class="form-control form-control-sm mr-1" style="width: 140px;" onchange="loadBulkAreas(this.value)">
                    <option value="">Zone</option>
                    @foreach($zones as $z)
                    <option value="{{ $z->id }}">{{ $z->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-secondary" id="btnBulkChangeZone" title="Change Zone"><i class="fas fa-map-marker-alt"></i></button>
            </div>
            <div class="d-inline-flex align-items-center mr-2">
                <select id="bulkAreaSelect" class="form-control form-control-sm mr-1" style="width: 140px;" onchange="loadBulkBoxes(this.value)">
                    <option value="">Sub Zone</option>
                </select>
                <button type="button" class="btn btn-sm btn-secondary" id="btnBulkChangeArea" title="Change Sub Zone"><i class="fas fa-map-pin"></i></button>
            </div>
            <div class="d-inline-flex align-items-center mr-2">
                <select id="bulkBoxSelect" class="form-control form-control-sm mr-1" style="width: 140px;">
                    <option value="">TJ/Box</option>
                </select>
                <button type="button" class="btn btn-sm btn-secondary" id="btnBulkChangeBox" title="Change TJ/Box"><i class="fas fa-box"></i></button>
            </div>
            <div class="d-inline-flex align-items-center mr-2" style="border-left: 2px solid #90caf9; padding-left: 8px;">
                <span class="text-muted mr-1" style="font-size: 11px;">MikroTik:</span>
                <button type="button" class="btn btn-sm btn-outline-success mr-1" id="btnBulkMikrotikEnable" title="MikroTik Enable - Restore all profiles"><i class="fas fa-power-off"></i> Enable</button>
                <button type="button" class="btn btn-sm btn-outline-danger mr-1" id="btnBulkMikrotikDisable" title="MikroTik Disable - Set Inactive profile"><i class="fas fa-power-off"></i> Disable</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnBulkMikrotikSync" title="Sync DB profile to MikroTik router"><i class="fas fa-sync-alt"></i> Sync</button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnBulkExport"><i class="fas fa-file-csv"></i> Export</button>
            <button type="button" class="btn btn-sm btn-outline-dark ml-auto" id="btnClearSelection"><i class="fas fa-times"></i> Clear</button>
        </div>

        <form id="bulkForm" method="POST" action="{{ route('admin.clients.bulk-action') }}">
            @csrf
            <input type="hidden" name="action" id="bulkActionInput">
            <input type="hidden" name="package_id" id="bulkPackageInput">
            <input type="hidden" name="zone_id" id="bulkZoneInput">
            <input type="hidden" name="area_id" id="bulkAreaInput">
            <input type="hidden" name="box_id" id="bulkBoxInput">
        </form>
        <form id="exportForm" method="POST" action="{{ route('admin.clients.export') }}">
            @csrf
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm" id="clientsTable" style="min-width:900px;">
                <thead>
                    @php
                        $__sb = request('sort_by','');
                        $__sd = request('sort_dir','');
                        $__sortIcon = function(string $col, string $def) use ($__sb,$__sd): string {
                            if ($__sb !== $col) return '<i class="fas fa-sort sort-arrow"></i>';
                            $dir = $__sd ?: $def;
                            return '<i class="fas fa-sort-'.($dir==='asc'?'up':'down').' sort-arrow active-sort"></i>';
                        };
                    @endphp
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" id="selectAll" title="Select All"></th>
                        <th class="sortable-th" onclick="colSort('client_id','asc')">ComID {!! $__sortIcon('client_id','asc') !!}</th>
                        <th class="sortable-th" onclick="colSort('name','asc')">PPPOE/Name/Cell {!! $__sortIcon('name','asc') !!}</th>
                        <th class="sortable-th" onclick="colSort('zone_id','asc')" style="max-width:160px;">Zone/Address {!! $__sortIcon('zone_id','asc') !!}</th>
                        <th class="sortable-th" onclick="colSort('monthly_fee','desc')">Package {!! $__sortIcon('monthly_fee','desc') !!}</th>
                        <th class="sortable-th" onclick="colSort('due_amount','desc')" style="width:120px;min-width:120px;">Due {!! $__sortIcon('due_amount','desc') !!}</th>
                        <th class="sortable-th" onclick="colSort('expire_date','asc')" style="white-space:nowrap;">Dates {!! $__sortIcon('expire_date','asc') !!}</th>
                        <th class="sortable-th" onclick="colSort('last_online','desc')">Online Info {!! $__sortIcon('last_online','desc') !!}</th>
                        <th class="sortable-th" onclick="colSort('status','asc')">Status {!! $__sortIcon('status','asc') !!}</th>
                        <th style="width:85px;min-width:85px;text-align:center;">MikroTik</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="clientTableBody">
                    @forelse($clients as $client)
                    @php
    $__billingCalc = \App\Services\BillingCalculatorService::calculate($client);
    $effectiveDue   = $__billingCalc['due'];
    $__advanceLive  = $__billingCalc['advance'];
@endphp
                    <tr data-search="{{ strtolower($client->name.' '.($client->client_id ?? '').' '.($client->username ?? '').' '.($client->phone ?? '').' '.($client->address ?? '').' '.($client->package->name ?? '').' '.($client->zone->name ?? '').' '.($client->area->name ?? '').' '.($client->mac_address ?? '').' '.($client->ip_address ?? '').' '.($client->email ?? '').' '.($client->nid ?? '')) }}">
                        <td><input type="checkbox" class="client-checkbox" value="{{ $client->id }}"></td>
                        <td>{{ $client->client_id ?? $client->id }}</td>
                        <td class="pop-trigger" data-pop-target="pop-usr-{{ $client->id }}" style="cursor:pointer;" title="Click for details">
                            @php
                                $__dotClass = 'dot-inactive';
                                $__dotTitle = 'Inactive';
                                if ($client->connection_status === 'online') {
                                    $__dotClass = 'dot-active';
                                    $__dotTitle = 'Online';
                                } elseif ($client->status === 'active') {
                                    $__dotClass = 'dot-due';
                                    $__dotTitle = 'Active (Offline)';
                                }
                            @endphp
                            @php
                                $__aiDay = min($client->payment_deadline ?? 15, 28);
                                $__nowD = \Carbon\Carbon::now('Asia/Dhaka')->startOfDay();
                                $__billingDt = $__nowD->copy()->setDay($__aiDay);
                                if ($__billingDt->lt($__nowD)) { $__billingDt->addMonthNoOverflow(); }
                                $__expDate = $client->temp_deadline_date
                                    ? \Carbon\Carbon::parse($client->temp_deadline_date)->startOfDay()
                                    : $__billingDt;
                                $__daysLeft = $__expDate ? (int) now()->startOfDay()->diffInDays($__expDate, false) : null;
                            @endphp
                            <strong style="font-size:13px;display:inline-flex;align-items:center;flex-wrap:wrap;gap:2px;" title="{{ $__dotTitle }}">
                                <span class="status-dot {{ $__dotClass }}" title="{{ $__dotTitle }}" data-dot-id="{{ $client->id }}" data-client-active="{{ $client->status === 'active' ? '1' : '0' }}"></span>{{ $client->username ?? $client->client_id }}
                                @if($__daysLeft !== null && $__daysLeft <= 3 && $client->status === 'active' && $__advanceLive <= 0)
                                    @if($__daysLeft <= 0)
                                        <span class="expiry-badge exp-today">EXPIRED</span>
                                    @elseif($__daysLeft == 1)
                                        <span class="expiry-badge exp-warning">EXP TOMORROW</span>
                                    @else
                                        <span class="expiry-badge exp-soon">EXP {{ $__daysLeft }}d</span>
                                    @endif
                                @endif
                            </strong>
                            <br><span class="text-primary small">{{ $client->phone ?? '' }}</span>
                            @php $instRemaining = ($client->installation_charge ?? 0); @endphp
                            <div id="pop-usr-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-user-circle mr-1 text-primary"></i><strong>{{ $client->name }}</strong>@if($client->is_free) <span style="font-size:9px;margin-left:4px;color:#27ae60;font-weight:700;">INST FREE</span>@elseif(!$client->signup_fee_paid && ($client->installation_charge ?? 0) > 0) <span style="font-size:9px;margin-left:4px;color:#c0392b;font-weight:700;">INST DUE ৳{{ number_format($client->installation_charge ?? 0, 0) }}</span>@endif</div>
                                <div class="text-muted mt-1"><i class="fas fa-id-badge mr-1 text-info"></i>{{ $client->username ?? $client->client_id }}</div>
                                <div class="text-muted mt-1"><i class="fas fa-phone mr-1 text-success"></i>{{ $client->phone ?? '-' }}</div>
                                @if($client->email)<div class="text-muted mt-1"><i class="fas fa-envelope mr-1"></i>{{ $client->email }}</div>@endif
                                @if($client->nid)<div class="text-muted mt-1"><i class="fas fa-id-card mr-1"></i>NID: {{ $client->nid }}</div>@endif
                            </div>
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-addr-{{ $client->id }}" style="cursor:pointer;max-width:160px;" title="Click for address details">
                            <strong class="text-primary">{{ $client->zone->name ?? '-' }}</strong>
                            @if($client->address)<br><span class="text-muted small">{{ $client->address }}</span>@endif
                            <div id="pop-addr-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-map-marker-alt mr-1 text-primary"></i><strong>{{ $client->zone->name ?? '-' }}</strong></div>
                                @if($client->area)<div class="text-muted mt-1"><i class="fas fa-layer-group mr-1 text-secondary" style="font-size:11px;"></i>Sub: {{ $client->area->name }}</div>@endif
                                @if($client->box)<div class="text-muted"><i class="fas fa-project-diagram mr-1 text-secondary" style="font-size:11px;"></i>TJ: {{ $client->box->name }}</div>@endif
                                @if($client->address)<div class="mt-1">{{ $client->address }}</div>@endif
                                @if($client->reseller)<div class="font-weight-bold text-info mt-1"><i class="fas fa-user-tie mr-1"></i>{{ $client->reseller->name ?? '' }}</div>@endif
                            </div>
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-pkg-{{ $client->id }}" style="cursor:pointer;" title="Click for package details">
                            @php $mkProfile = $client->mikrotik_profile_name ?: ($client->package->mikrotik_profile ?? null); @endphp
                            <span class="text-nowrap">{{ $client->package->name ?? '-' }}</span>
                            @if($client->package && $client->package->bandwidth)
                            <br><span class="text-info small"><i class="fas fa-tachometer-alt"></i> {{ $client->package->bandwidth }}</span>
                            @endif
                            @if($client->monthly_fee)
                            <br><span style="font-weight:700;font-size:11px;color:#1b5e20;"><i class="fas fa-tag" style="font-size:9px;"></i> ৳{{ number_format($client->monthly_fee, 0) }}/mo</span>
                            @endif
                            <div id="pop-pkg-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-box mr-1 text-info"></i><strong>{{ $client->package->name ?? '-' }}</strong></div>
                                @if($client->package && $client->package->bandwidth)<div class="text-info mt-1"><i class="fas fa-tachometer-alt mr-1"></i>{{ $client->package->bandwidth }}</div>@endif
                                @if($mkProfile)<div class="mt-1"><i class="fas fa-network-wired mr-1 text-muted"></i>Profile: <strong>{{ $mkProfile }}</strong></div>@endif
                                @if($client->monthly_fee)<div class="mt-1"><span style="font-weight:700;font-size:11px;color:#1b5e20;"><i class="fas fa-tag mr-1"></i>৳{{ number_format($client->monthly_fee, 0) }}/mo</span></div>@endif
                            </div>
                        </td>
                        <td class="text-center">
                            @if($client->is_free)
                                <span style="display:inline-block;font-size:10px;font-weight:700;color:#27ae60;padding:2px 8px;">Free Client</span>
                                <br><span style="font-size:9px;color:#27ae60;font-weight:700;"><i class="fas fa-gift" style="font-size:8px;"></i> INST FREE</span>
                            @else
                                <strong class="{{ $effectiveDue > 0 ? 'text-danger' : 'text-success' }}">৳{{ number_format($effectiveDue ?? 0, 0) }}</strong>
                                @if(!$client->signup_fee_paid && ($client->installation_charge ?? 0) > 0)
                                <br><span style="font-size:9px;color:#c0392b;font-weight:700;"><i class="fas fa-tools" style="font-size:8px;"></i> INST DUE ৳{{ number_format($client->installation_charge ?? 0, 0) }}</span>
                                @endif
                            @endif
                            @if($__advanceLive > 0)
                            <br><small style="color:#16a34a;font-weight:700;font-size:11px;"><i class="fas fa-wallet" style="font-size:9px;"></i> Adv: ৳{{ number_format($__advanceLive, 0) }}</small>
                            @endif
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-dates-{{ $client->id }}" style="cursor:pointer;font-size:12px;" title="Click for all dates">
                            @php
                                $aiDay = min($client->payment_deadline ?? 15, 28);
                                $nowDhaka = \Carbon\Carbon::now('Asia/Dhaka');
                                // For INACTIVE clients: show when they became inactive
                                // For ACTIVE clients: show upcoming auto-inactive date
                                if ($client->status === 'inactive' && $client->last_inactive_date) {
                                    $aiLabel    = 'Inactive Since:';
                                    $aiLabelCls = 'text-danger';
                                    $aiShowDate = \Carbon\Carbon::parse($client->last_inactive_date, 'Asia/Dhaka');
                                    $aiIsInactive = true;
                                } else {
                                    $aiLabel    = 'Auto-Inactive:';
                                    $aiLabelCls = 'text-muted';
                                    $aiIsInactive = false;
                                    // Upcoming: this month if deadline not yet passed, else next month
                                    $aiDate = $nowDhaka->day <= $aiDay
                                        ? \Carbon\Carbon::create($nowDhaka->year, $nowDhaka->month, $aiDay, 0, 0, 0, 'Asia/Dhaka')
                                        : \Carbon\Carbon::create($nowDhaka->year, $nowDhaka->month, $aiDay, 0, 0, 0, 'Asia/Dhaka')->addMonthNoOverflow();
                                    $aiShowDate = $client->temp_deadline_date
                                        ? \Carbon\Carbon::parse($client->temp_deadline_date, 'Asia/Dhaka')
                                        : $aiDate;
                                }
                            @endphp
                            <span class="{{ $aiLabelCls }}" style="font-size:11px;">{{ $aiLabel }}</span><br>
                            <span class="{{ $aiIsInactive ? 'text-danger' : 'text-warning' }} font-weight-bold">{{ $aiShowDate->format('d-M-Y') }}</span>
                            @if(!$aiIsInactive && $client->temp_deadline_date)
                            <br><span class="text-info small" style="white-space:nowrap;"><i class="fas fa-clock mr-1"></i>Extended</span>
                            @endif
                            @if($client->connection_date)
                            <br><span style="font-size:10px;color:#6c757d;"><i class="fas fa-plug mr-1" style="font-size:9px;"></i>Joined: {{ \Carbon\Carbon::parse($client->connection_date)->format("d-M-Y") }}</span>
                            @endif
                            <div id="pop-dates-{{ $client->id }}" class="d-none" style="min-width:260px;">
                                <div><i class="fas fa-calendar mr-1 text-muted"></i>Billing Day: <strong>{{ $client->billing_date ?? 1 }}</strong></div>
                                @if($aiIsInactive)
                                <div class="mt-1"><i class="fas fa-ban mr-1 text-danger"></i>Inactive Since: <strong class="text-danger">{{ $aiShowDate->format('d-M-Y') }}</strong></div>
                                <div class="mt-1"><i class="fas fa-calendar-alt mr-1 text-warning"></i>Next Auto-Inactive: <strong class="text-warning">
                                    @php
                                        $nextAi = \Carbon\Carbon::create($nowDhaka->year, $nowDhaka->month, $aiDay, 0, 0, 0, 'Asia/Dhaka');
                                        if ($nextAi->isPast()) $nextAi->addMonthNoOverflow();
                                    @endphp
                                    {{ $nextAi->format('d-M-Y') }}
                                </strong> <small class="text-muted">(day {{ $client->payment_deadline ?? 15 }})</small></div>
                                @else
                                <div class="mt-1"><i class="fas fa-ban mr-1 text-warning"></i>Auto-Inactive: <strong class="text-warning">{{ $aiShowDate->format('d-M-Y') }}</strong> <small class="text-muted">(day {{ $client->payment_deadline ?? 15 }})</small></div>
                                @endif
                                @if($client->expire_date)<div class="mt-1"><i class="fas fa-hourglass-end mr-1 text-muted"></i>Expiry: <strong class="{{ $client->expire_date->isPast() ? 'text-danger' : 'text-muted' }}">{{ $client->expire_date->format('d-M-Y') }}</strong></div>@endif
                                @if(!$aiIsInactive && $client->temp_deadline_date)<div class="mt-1"><i class="fas fa-clock mr-1 text-info"></i>Extended: <strong class="text-info">{{ \Carbon\Carbon::parse($client->temp_deadline_date)->format('d-M-Y') }}</strong></div>@endif
                                @if($client->connection_date)<div class="mt-1"><i class="fas fa-plug mr-1 text-success"></i>Joined: <strong>{{ \Carbon\Carbon::parse($client->connection_date)->format('d-M-Y') }}</strong></div>@endif
                            </div>
                        </td>
                        <td id="net-cell-{{ $client->id }}">
                            @php
                                $displayIp = $client->session_ip ?: $client->ip_address;
                                $displayMac = $client->session_mac ?: $client->mac_address;
                            @endphp
                            <div class="net-info-content" id="net-info-{{ $client->id }}">
                            @if($client->connection_status == 'online')
                            @if($displayIp)
                            <span class="text-dark small" style="font-weight:600;">{{ $displayIp }}</span>
                            @endif
                            @if($displayMac)
                            <br><span class="text-muted small">{{ $displayMac }}</span>
                            @endif
                            @if($client->session_uptime)
                            @php
                                $uptimeStr = $client->session_uptime; $secs = 0;
                                if (preg_match("/^(\\d{1,2}):(\\d{2}):(\\d{2})$/", $uptimeStr, $hms)) {
                                    $secs = $hms[1]*3600 + $hms[2]*60 + $hms[3];
                                } else {
                                    if(preg_match("/(\\d+)w/", $uptimeStr, $m)) $secs += $m[1]*604800;
                                    if(preg_match("/(\\d+)d/", $uptimeStr, $m)) $secs += $m[1]*86400;
                                    if(preg_match("/(\\d+)h/", $uptimeStr, $m)) $secs += $m[1]*3600;
                                    if(preg_match("/(\\d+)m/", $uptimeStr, $m)) $secs += $m[1]*60;
                                    if(preg_match("/(\\d+)s/", $uptimeStr, $m)) $secs += $m[1];
                                }
                                if ($client->last_online) { $secs += abs((int) \Carbon\Carbon::parse($client->last_online)->diffInSeconds(now())); }
                            @endphp
                            <br><strong class="text-success live-uptime" data-uptime-seconds="{{ $secs }}"></strong>
                            @endif
                            @if(!$displayIp && !$displayMac)
                            <span class="text-muted small">-</span>
                            @endif
                            @else
                            @if($client->last_logout)
                            <span class="text-muted small"><i class="fas fa-power-off mr-1 text-danger"></i>Last Offline:</span>
                            <br><span class="text-danger small" style="font-weight:600;">{{ $client->last_logout->format('d-M-Y') }}</span>
                            <br><span class="text-muted small">{{ $client->last_logout->format('h:i A') }}</span>
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                            @endif
                            </div>
                        </td>
                        <td class="text-center">
                            <div>
                            @if($client->status == 'active')
                                <span class="badge badge-outline-success status-badge" style="border: 1.5px solid #28a745; color: #28a745; background: transparent; font-weight: 600; padding: 4px 12px;">ACTIVE</span>
                            @elseif($client->status == 'inactive')
                                <span class="badge badge-outline-danger status-badge" style="border: 1.5px solid #dc3545; color: #dc3545; background: transparent; font-weight: 600; padding: 4px 12px;">INACTIVE</span>
                            @else
                                <span class="badge badge-outline-secondary status-badge" style="border: 1.5px solid #6c757d; color: #6c757d; background: transparent; font-weight: 600; padding: 4px 12px;">{{ strtoupper($client->status) }}</span>
                            @endif
                            </div>
                            <div data-client-id-status="{{ $client->id }}" style="margin-top: 4px;">
                            @if($client->connection_status == 'online')
                                <span class="badge status-badge" style="border: 1.5px solid #007bff; color: #007bff; background: transparent; font-weight: 600; padding: 4px 12px;">ONLINE</span>
                            @else
                                <span class="badge status-badge" style="border: 1.5px solid #dc3545; color: #dc3545; background: transparent; font-weight: 600; padding: 4px 12px;">OFFLINE</span>
                            @endif
                            </div>
                        </td>
                        <td class="text-center">
                            @if($__canAction('client_list', 'mikrotik_toggle'))
                            @php
                                $currentMkProfile = $client->mikrotik_profile_name ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                                $isMkActive = $client->status === 'active' && $currentMkProfile && strtolower($currentMkProfile) !== 'inactive';
                            @endphp
                            <label class="mk-toggle mb-0" title="{{ $isMkActive ? 'Active: ' . $currentMkProfile : ($client->status !== 'active' ? 'Client Inactive' : 'Inactive Profile') }}">
                                <input type="checkbox" class="mikrotik-toggle" data-client-id="{{ $client->id }}" {{ $isMkActive ? 'checked' : '' }}>
                                <span class="mk-slider"></span>
                            </label>
                            <span class="mk-label {{ $isMkActive ? 'text-success' : 'text-danger' }}" id="mk-label-{{ $client->id }}">{{ $isMkActive ? 'ON' : 'OFF' }}</span>
                            @if($client->mikrotikDevice)
                                <br><small class="text-muted" style="display:inline-block;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $client->mikrotikDevice->name }}">{{ $client->mikrotikDevice->name }}</small>
                            @endif
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="action-btns" style="white-space:nowrap;text-align:center;vertical-align:middle;">
                            <div class="dropdown">
                                <button type="button" class="btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background:transparent;border:none;color:#2a5298;padding:4px 10px;box-shadow:none;font-size:18px;line-height:1;">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right client-action-menu" style="min-width:155px;padding:1px 0;box-shadow:0 4px 18px rgba(0,0,0,0.18);border-radius:8px;border:none;">
                                    @if($__canAction('client_list', 'view_profile'))
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.clients.show', $client) }}" style="padding:2px 8px;font-size:10.5px;gap:5px;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:4px;background:#17a2b8;flex-shrink:0;"><i class="fas fa-eye text-white" style="font-size:10px;"></i></span>
                                        <span>Profile</span>
                                    </a>
                                    @endif
                                    @if($__canAction('client_list', 'edit'))
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.clients.edit', $client) }}" style="padding:2px 8px;font-size:10.5px;gap:5px;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:4px;background:#6f42c1;flex-shrink:0;"><i class="fas fa-edit text-white" style="font-size:10px;"></i></span>
                                        <span>Edit</span>
                                    </a>
                                    @endif
                                    @if($__canAction('client_list', 'quick_pay') || $__canAction('client_list', 'change_package'))
                                    <div class="dropdown-divider" style="margin:2px 0;"></div>
                                    @endif
                                    @if($__canAction('client_list', 'quick_pay'))
                                    <button type="button" class="dropdown-item d-flex align-items-center quick-pay-open"
                                        data-id="{{ $client->id }}" data-name="{{ $client->name }}"
                                        data-client-id="{{ $client->client_id }}" data-due="{{ $client->due_amount ?? 0 }}"
                                        style="padding:2px 8px;font-size:10.5px;gap:5px;background:none;border:none;width:100%;text-align:left;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:4px;background:#28a745;flex-shrink:0;"><i class="fas fa-money-bill-wave text-white" style="font-size:10px;"></i></span>
                                        <span>Cash Payment</span>
                                    </button>
                                    @endif
                                    @if($__canAction('client_list', 'change_package'))
                                    <button type="button" class="dropdown-item d-flex align-items-center change-pkg-open"
                                        data-id="{{ $client->id }}" data-name="{{ $client->name }}"
                                        data-client-id="{{ $client->client_id }}" data-package-id="{{ $client->package_id }}"
                                        data-package-name="{{ $client->package->name ?? '-' }}" data-monthly-fee="{{ $client->monthly_fee ?? 0 }}"
                                        data-ip="{{ $client->ip_address ?? '' }}" data-profile="{{ $client->mikrotik_profile_name ?? '' }}"
                                        style="padding:2px 8px;font-size:10.5px;gap:5px;background:none;border:none;width:100%;text-align:left;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:4px;background:#fd7e14;flex-shrink:0;"><i class="fas fa-exchange-alt text-white" style="font-size:10px;"></i></span>
                                        <span>Change Package</span>
                                    </button>
                                    @endif
                                    @if($__canAction('client_list', 'extend_deadline'))
                                    @php
                                        $deadlineDay2 = min($client->payment_deadline ?? 15, 28);
                                        $currentBillingDeadline = \Carbon\Carbon::create(now()->year, now()->month, $deadlineDay2);
                                    @endphp
                                    <button type="button" class="dropdown-item d-flex align-items-center temp-deadline-btn"
                                        data-client-id="{{ $client->id }}" data-client-name="{{ $client->name }}"
                                        data-client-status="{{ $client->status }}"
                                        data-current-deadline="{{ $client->expire_date ? $client->expire_date->format('Y-m-d') : '' }}"
                                        data-temp-deadline="{{ $client->temp_deadline_date ? \Carbon\Carbon::parse($client->temp_deadline_date)->format('Y-m-d') : '' }}"
                                        data-billing-deadline="{{ $currentBillingDeadline->format('Y-m-d') }}"
                                        data-billing-day="{{ $client->billing_date ?? 1 }}"
                                        style="padding:2px 8px;font-size:10.5px;gap:5px;background:none;border:none;width:100%;text-align:left;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:4px;background:{{ $client->temp_deadline_date ? '#6c757d' : '#343a40' }};flex-shrink:0;"><i class="fas fa-calendar-plus text-white" style="font-size:10px;"></i></span>
                                        <span>{{ $client->temp_deadline_date ? 'Extended ('.\Carbon\Carbon::parse($client->temp_deadline_date)->format('d/m').')' : 'Extend Deadline' }}</span>
                                    </button>
                                    @endif
                                    @if($__canAction('client_list', 'ledger'))
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.clients.ledger', $client) }}" style="padding:2px 8px;font-size:10.5px;gap:5px;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:4px;background:#0d6efd;flex-shrink:0;"><i class="fas fa-file-alt text-white" style="font-size:10px;"></i></span>
                                        <span>Ledger</span>
                                    </a>
                                    @endif
                                    @if($__canAction('client_list', 'delete'))
                                    <div class="dropdown-divider" style="margin:2px 0;"></div>
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.clients.confirm-delete', $client) }}" style="padding:2px 8px;font-size:10.5px;gap:5px;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:4px;background:#dc3545;flex-shrink:0;"><i class="fas fa-trash text-white" style="font-size:10px;"></i></span>
                                        <span class="text-danger" style="font-weight:600;">Delete</span>
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center">No clients found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── TABLE FOOTER: per-page selector + pagination ── --}}
        <div id="client-tbl-footer" class="d-flex justify-content-between align-items-center mt-3 flex-wrap" style="gap:10px;padding:10px 4px 4px;">

            {{-- Left: SHOW X ENTRIES --}}
            <div class="d-flex align-items-center" style="gap:8px;font-size:13px !important;color:#6c757d;font-weight:400;">
                <span style="font-size:13px !important;color:#6c757d;font-weight:400;">SHOW</span>
                <select id="perPageSelect"
                    class="form-control form-control-sm"
                    style="width:70px;display:inline-block;font-size:13px !important;padding:2px 4px;height:26px;color:#6c757d;"
                    onchange="(function(v){
                        var url = new URL(window.location.href);
                        url.searchParams.set('per_page', v);
                        url.searchParams.delete('page');
                        window.location.href = url.toString();
                    })(this.value)">
                    @foreach([20, 50, 100, 200] as $opt)
                        <option value="{{ $opt }}" {{ ($perPage ?? 20) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <span style="font-size:13px !important;color:#6c757d;font-weight:400;">ENTRIES</span>
            </div>

            {{-- Right: Showing X-Y of Z + pagination --}}
            <div class="d-flex align-items-center flex-wrap" style="gap:10px;font-size:13px !important;color:#6c757d;font-weight:400;">
                <span style="font-size:13px !important;color:#6c757d;font-weight:400;white-space:nowrap;">
                    Showing {{ $clients->firstItem() ?? 0 }}&ndash;{{ $clients->lastItem() ?? 0 }}
                    of {{ $clients->total() }}
                </span>
                {{ $clients->withQueryString()->links('vendor.pagination.bootstrap-4') }}
            </div>

        </div>

</div>

<div class="modal fade" id="tempDeadlineModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-plus mr-2"></i>Extend Deadline</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="tempDeadlineForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Client:</strong> <span id="deadlineClientName" class="text-primary"></span>
                        <span id="deadlineStatusBadge" class="ml-2"></span>
                    </div>
                    <div class="alert alert-light border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar text-secondary mr-1"></i> <span style="font-size: 13px;">Permanent Billing Date:</span>
                                <span id="permanentBillingDay" class="text-dark ml-1" style="font-size: 13px;"></span>
                                <span class="mx-2 text-muted">|</span>
                                <i class="fas fa-clock text-secondary mr-1"></i> <span style="font-size: 13px;">Current Deadline:</span>
                                <span id="currentDeadlineDate" class="text-dark font-weight-bold ml-1"></span>
                            </div>
                        </div>
                    </div>
                    <div id="autoActivateNotice" class="alert alert-warning d-none mb-3">
                        <i class="fas fa-bolt mr-1"></i> This client is <strong id="currentClientStatus"></strong>. Extending deadline will <strong>auto-activate</strong> the connection.
                    </div>
                    <div class="form-group">
                        <label for="deadlineDate"><strong>New Deadline Date</strong></label>
                        <input type="date" class="form-control" id="deadlineDate" name="deadline_date" required>
                    </div>
                    <div class="form-group">
                        <label for="deadlineReason"><strong>Reason</strong></label>
                        <select class="form-control mb-2" id="deadlineReasonPreset">
                            <option value="">-- Select reason --</option>
                            @foreach($deadlineReasons as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                            @endforeach
                        </select>
                        <input type="text" class="form-control" id="deadlineReason" name="reason" placeholder="Enter reason..." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm clear-deadline-btn" id="clearDeadlineBtn" style="display:none; margin-right:auto;">
                        <i class="fas fa-times mr-1"></i> Clear Extended Date
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark" id="submitDeadlineBtn">
                        <i class="fas fa-calendar-check mr-1"></i> Extend Deadline
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quickPayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 14px 20px;">
                <h5 class="modal-title" style="font-weight: 700;"><i class="fas fa-money-bill-wave mr-2"></i>Quick Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="quickPayForm">
                <div class="modal-body" style="padding: 20px;">
                    <div style="background: #f8f9fa; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong style="font-size: 14px;" id="qp_client_name"></strong>
                                <div style="font-size: 12px; color: #8898aa;" id="qp_client_id"></div>
                            </div>
                            <div class="text-right">
                                <div style="font-size: 12px; color: #8898aa;">Due</div>
                                <strong style="font-size: 18px;" id="qp_due_amount"></strong>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="qp_id">
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-money-bill mr-1"></i>Amount (৳)</label>
                        <input type="number" id="qp_amount" class="form-control form-control-lg text-center" required min="1" step="0.01" style="border-radius: 10px; font-weight: 700; font-size: 20px;" oninput="checkQpAmountDiff()" onchange="checkQpAmountDiff()">
                        <small id="qp_amount_warning" class="text-warning mt-1" style="display: none;"><i class="fas fa-exclamation-triangle mr-1"></i>Amount differs from due - Reason required!</small>
                    </div>
                    <div class="form-group" id="qp_reason_group" style="display: none;">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-comment-alt mr-1"></i>Reason <span class="text-danger">*</span></label>
                        @if(!empty($paymentReasons))
                        <select class="form-control mb-2" id="qp_reason_preset" style="border-radius: 8px;" onchange="if(this.value){document.getElementById('qp_reason').value=this.value;}">
                            <option value="">-- Select reason --</option>
                            @foreach($paymentReasons as $reason)
                            <option value="{{ $reason }}">{{ $reason }}</option>
                            @endforeach
                        </select>
                        @endif
                        <input type="text" id="qp_reason" class="form-control" placeholder="Why is the amount different from due?" style="border-radius: 8px;">
                        <small class="text-muted" style="font-size: 11px;">Required when amount differs from due</small>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-credit-card mr-1"></i>Payment Method</label>
                        <select id="qp_method" class="form-control" required style="border-radius: 8px;" onchange="toggleQpOnlineFields()">
                            @foreach(auth()->user()->allowedPaymentMethods() as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="qp_online_fields" style="display: none;">
                        <div class="form-group">
                            <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-hashtag mr-1"></i>Transaction ID</label>
                            <input type="text" id="qp_trx_id" class="form-control" placeholder="Enter transaction ID" style="border-radius: 8px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-phone mr-1"></i><span id="qp_number_label">bKash Number</span></label>
                            <input type="text" id="qp_sender" class="form-control" style="border-radius: 8px;">
                        </div>
                    </div>
                    <div class="form-group" id="qp_cash_receipt_field" style="display: none;">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-receipt mr-1"></i>Money Receipt No.</label>
                        <input type="text" id="qp_money_receipt" class="form-control" placeholder="Enter money receipt number" style="border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-sticky-note mr-1"></i>Notes</label>
                        <textarea id="qp_notes" class="form-control" rows="2" placeholder="Payment notes..." style="border-radius: 8px;"></textarea>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="qp_send_sms" checked>
                        <label class="custom-control-label" for="qp_send_sms" style="font-size: 13px;"><i class="fas fa-sms mr-1"></i>Send SMS notification</label>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; border-top: 1px solid #f1f3f5;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-success" id="qp_submit_btn" style="border-radius: 8px;"><i class="fas fa-check mr-1"></i>Submit Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="changePackageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 14px 20px;">
                <h5 class="modal-title" style="font-weight: 700;"><i class="fas fa-exchange-alt mr-2"></i>Change Package</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="changePackageForm">
                <div class="modal-body" style="padding: 20px;">
                    <div style="background: #f8f9fa; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong style="font-size: 14px;" id="cp_client_name"></strong>
                                <div style="font-size: 12px; color: #8898aa;" id="cp_client_id"></div>
                            </div>
                            <div class="text-right">
                                <div style="font-size: 12px; color: #8898aa;">Current Package</div>
                                <strong style="font-size: 14px;" id="cp_current_package"></strong>
                                <div style="font-size: 12px; color: #17a2b8;" id="cp_current_fee"></div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="cp_id">
                    <input type="hidden" id="cp_billing_day_val" value="1">
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-cube mr-1"></i>New Package <span class="text-danger">*</span></label>
                        <select id="cp_package_id" class="form-control" required style="border-radius: 8px;">
                            <option value="">-- Select Package --</option>
                            @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" data-price="{{ $pkg->price }}" data-bandwidth="{{ $pkg->bandwidth ?? '' }}" data-profile="{{ $pkg->mikrotik_profile ?? '' }}">{{ $pkg->name }} - ৳{{ number_format($pkg->price) }}{{ $pkg->bandwidth ? ' ('.$pkg->bandwidth.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-server mr-1"></i>MikroTik Profile <span class="text-danger">*</span></label>
                        <select id="cp_mikrotik_profile" class="form-control" required style="border-radius: 8px;">
                            <option value="">-- Select Profile --</option>
                            @foreach($availableProfiles as $profile)
                            <option value="{{ $profile }}">{{ $profile }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Current: <strong id="cp_current_profile_text">-</strong></small>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-network-wired mr-1"></i>IP Address</label>
                        <input type="text" id="cp_ip_address" class="form-control" placeholder="e.g. 192.168.1.100" style="border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-calculator mr-1"></i>Billing Option <span class="text-danger">*</span></label>
                        <div style="background: #f8f9fa; border-radius: 10px; padding: 12px;">
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" class="custom-control-input" id="cp_billing_day" name="cp_billing_option" value="day_calculation" checked>
                                <label class="custom-control-label" for="cp_billing_day" style="font-size: 13px; cursor: pointer;">
                                    <strong>Day Calculation</strong>
                                    <div style="font-size: 11px; color: #8898aa;">Calculate prorated amount for remaining days of current month</div>
                                </label>
                            </div>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" class="custom-control-input" id="cp_billing_set" name="cp_billing_option" value="set_new_price">
                                <label class="custom-control-label" for="cp_billing_set" style="font-size: 13px; cursor: pointer;">
                                    <strong>Set New Package Price</strong>
                                    <div style="font-size: 11px; color: #8898aa;">Manually set a custom monthly fee</div>
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="cp_billing_none" name="cp_billing_option" value="no_change">
                                <label class="custom-control-label" for="cp_billing_none" style="font-size: 13px; cursor: pointer;">
                                    <strong>No Change</strong>
                                    <div style="font-size: 11px; color: #8898aa;">Change package without any billing adjustment</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="cp_new_price_group" style="display: none;">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-tag mr-1"></i>New Monthly Fee (৳)</label>
                        <input type="number" id="cp_new_price" class="form-control" min="0" step="0.01" placeholder="Enter custom price" style="border-radius: 8px;">
                    </div>
                    <div class="form-group" id="cp_day_calc_preview" style="display:none;background:#e8f5e9;border-radius:10px;padding:12px;border-left:4px solid #28a745;">
                        <div style="font-size:12px;font-weight:700;color:#155724;margin-bottom:8px;"><i class="fas fa-calculator mr-1"></i>Day Calculation Preview</div>
                        <div id="cp_day_calc_details" style="font-size:12px;color:#333;"></div>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-sticky-note mr-1"></i>Note</label>
                        <textarea id="cp_note" class="form-control" rows="2" placeholder="Optional note..." style="border-radius: 8px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; border-top: 1px solid #f1f3f5;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-info" id="cp_submit_btn" style="border-radius: 8px;"><i class="fas fa-exchange-alt mr-1"></i>Change Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mikrotikToggleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-power-off mr-2"></i><span id="mtm_title">MikroTik Toggle</span></h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="mikrotikToggleForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Client:</strong> <span id="mtm_client_name" class="text-primary"></span>
                    </div>
                    <div class="alert mb-3" id="mtm_alert">
                        <i class="fas fa-info-circle mr-1"></i> <span id="mtm_action_text"></span>
                    </div>
                    <div class="form-group">
                        <label for="mtm_reason"><strong>Reason / Note <span class="text-danger">*</span></strong></label>
                        <select class="form-control mb-2" id="mtm_reason_preset">
                            <option value="">-- Select reason --</option>
                        </select>
                        <input type="text" class="form-control" id="mtm_reason" name="reason" placeholder="Enter reason..." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark" id="mtm_submit_btn">
                        <i class="fas fa-check mr-1"></i> Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quickPayReceiptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius: 12px; border: none; text-align: center; padding: 30px 20px;">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5 style="font-weight: 700;" id="qpr_message">Payment Successful!</h5>
            <p style="font-size: 13px; color: #8898aa;" id="qpr_details"></p>
            <div class="mt-3">
                <a href="#" id="qpr_thermal_link" class="btn btn-secondary btn-sm mr-1" target="_blank" style="border-radius: 6px;"><i class="fas fa-print mr-1"></i>Print</a>
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal" style="border-radius: 6px;">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

<button id="scrollTopBtn" title="Scroll to top"><i class="fas fa-chevron-up"></i></button>

@push('scripts')
<script>
window.mtmDisableReasons = @json($disableReasons);
window.mtmEnableReasons  = @json($enableReasons);
</script>
<script>
$(document).ready(function() {
    // Show receipt links if page was reloaded after a quick payment
    var lastReceiptUrl = sessionStorage.getItem('lastReceiptUrl');
    if (lastReceiptUrl) {
        var lastMsg = sessionStorage.getItem('lastPaymentMsg') || 'Payment recorded successfully!';
        var lastPrintUrl = sessionStorage.getItem('lastPrintUrl') || '';
        sessionStorage.removeItem('lastReceiptUrl');
        sessionStorage.removeItem('lastPrintUrl');
        sessionStorage.removeItem('lastPaymentMsg');
        toastr.success(
            lastMsg + '<br><a href="' + lastReceiptUrl + '" target="_blank" class="btn btn-sm btn-light mt-1 mr-1"><i class="fas fa-file-invoice mr-1"></i>Receipt</a>' +
            (lastPrintUrl ? '<a href="' + lastPrintUrl + '" target="_blank" class="btn btn-sm btn-light mt-1"><i class="fas fa-print mr-1"></i>Print</a>' : ''),
            'Payment Successful',
            {timeOut: 8000, extendedTimeOut: 3000, enableHtml: true, closeButton: true}
        );
    }
    var suggestTimer = null;
    var suggestXhr = null;
    var selectedIndex = -1;

    function performQuickSearch() {
        var value = $('#quickSearch').val().trim();
        if (searchSubmitTimer) clearTimeout(searchSubmitTimer);
        if (suggestTimer) clearTimeout(suggestTimer);
        $('#searchSuggestions').hide();
        $('#quickSearchBtn').html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        document.getElementById('quickSearchForm').submit();
    }

    function showSuggestions(data) {
        var $box = $('#searchSuggestions');
        if (!data || data.length === 0) {
            $box.hide();
            return;
        }

        var html = '';
        data.forEach(function(c, i) {
            var statusColor = c.status === 'active' ? '#28a745' : '#dc3545';
            var statusLabel = c.status === 'active' ? 'Active' : 'Inactive';
            html += '<a href="' + c.url + '" class="suggest-item d-flex align-items-center px-3 py-2" data-index="' + i + '" style="text-decoration:none; color:#333; border-bottom:1px solid #f0f0f0; cursor:pointer;">'
                + '<div style="flex:1; min-width:0;">'
                + '<div style="font-weight:600; font-size:13px;" class="text-truncate">' + $('<span>').text(c.name).html() + '</div>'
                + '<div style="font-size:11px; color:#888;">'
                + '<span class="mr-2"><i class="fas fa-id-badge"></i> ' + $('<span>').text(c.client_id || '-').html() + '</span>'
                + '<span class="mr-2"><i class="fas fa-user"></i> ' + $('<span>').text(c.username || '-').html() + '</span>'
                + '<span><i class="fas fa-phone"></i> ' + $('<span>').text(c.phone || '-').html() + '</span>'
                + '</div>'
                + '</div>'
                + '<div class="text-right ml-2" style="white-space:nowrap;">'
                + '<span class="badge" style="background:' + statusColor + '; color:#fff; font-size:10px;">' + statusLabel + '</span>'
                + '<div style="font-size:11px; font-weight:600; color:#333; margin-top:2px;">' + c.due + '</div>'
                + '</div>'
                + '</a>';
        });

        html += '<a href="#" class="suggest-item d-block text-center py-2" style="font-size:12px; color:#007bff; text-decoration:none; background:#f8f9fa;" id="searchAllLink">'
            + '<i class="fas fa-search mr-1"></i>Search all for "' + $('<span>').text($('#quickSearch').val()).html() + '"'
            + '</a>';

        $box.html(html).show();
        selectedIndex = -1;
    }

    function highlightItem(idx) {
        var items = $('#searchSuggestions .suggest-item');
        items.css('background', '');
        selectedIndex = idx;
        if (idx >= 0 && idx < items.length) {
            $(items[idx]).css('background', '#e9ecef');
        }
    }

    var searchSubmitTimer = null;
    var lastSearched = '{{ request("search") }}';
    var ajaxSearchXhr = null;

    // ── AJAX instant search — queries ALL clients across all pages ────────────
    function ajaxSearch(value) {
        if (ajaxSearchXhr) { ajaxSearchXhr.abort(); }

        var $btn = $('#quickSearchBtn');
        var $tbody = $('#clientTableBody');

        if (value === '') {
            // Reload without search param
            $btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            window.location.href = '{{ route("admin.clients.index") }}' + window.location.search.replace(/[?&]search=[^&]*/g, '').replace(/^&/, '?');
            return;
        }

        $btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        // Build request URL preserving other filters (status etc) but setting search + large per_page
        var params = new URLSearchParams(window.location.search);
        params.set('search', value);
        params.set('per_page', '500');
        params.delete('page');

        ajaxSearchXhr = $.ajax({
            url: '{{ route("admin.clients.index") }}?' + params.toString(),
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(html) {
                var $doc = $($.parseHTML(html, document, true));
                var newRows = $doc.find('#clientTableBody').html();
                if (newRows !== undefined) {
                    $tbody.html(newRows);
                    // Re-init popovers on new rows
                    if (typeof initPopovers === 'function') initPopovers();
                    $('[data-toggle="popover"]').popover({ html: true, trigger: 'manual', container: 'body' });
                }
                // Update result count text if any
                var summaryText = $doc.find('.clients-summary-text').first().text();
                if (summaryText) $('.clients-summary-text').first().text(summaryText);
            },
            error: function(xhr) {
                if (xhr.statusText !== 'abort') {
                    $tbody.html('<tr><td colspan="11" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle mr-2"></i>Search error. Try again.</td></tr>');
                }
            },
            complete: function() {
                $btn.html('<i class="fas fa-search"></i>').prop('disabled', false);
            }
        });
    }

    $('#quickSearch').on('input', function() {
        var value = $(this).val().trim();

        if (suggestTimer) clearTimeout(suggestTimer);
        if (suggestXhr) { suggestXhr.abort(); }
        if (searchSubmitTimer) clearTimeout(searchSubmitTimer);

        // Debounced AJAX search (300ms) — searches ALL clients server-side
        searchSubmitTimer = setTimeout(function() {
            ajaxSearch(value);
        }, 300);

        // Autocomplete suggestions (unchanged)
        if (value.length >= 2) {
            suggestTimer = setTimeout(function() {
                suggestXhr = $.ajax({
                    url: '{{ route("admin.clients.autocomplete") }}',
                    type: 'GET',
                    data: { q: value },
                    dataType: 'json',
                    success: function(data) {
                        showSuggestions(data);
                    },
                    error: function() {
                        $('#searchSuggestions').hide();
                    }
                });
            }, 200);
        } else {
            $('#searchSuggestions').hide();
        }
    });

    $('#quickSearch').on('keydown', function(e) {
        var items = $('#searchSuggestions .suggest-item');
        var suggestionsVisible = $('#searchSuggestions').is(':visible') && items.length > 0;

        if (e.keyCode === 40 && suggestionsVisible) {
            e.preventDefault();
            highlightItem(Math.min(selectedIndex + 1, items.length - 1));
            return;
        }
        if (e.keyCode === 38 && suggestionsVisible) {
            e.preventDefault();
            highlightItem(Math.max(selectedIndex - 1, -1));
            return;
        }
        if (e.keyCode === 27) {
            $('#searchSuggestions').hide();
            return;
        }
        if (e.keyCode === 13) {
            e.preventDefault();
            if (searchSubmitTimer) clearTimeout(searchSubmitTimer);
            if (suggestionsVisible && selectedIndex >= 0 && selectedIndex < items.length) {
                var href = $(items[selectedIndex]).attr('href');
                if (href && href !== '#') {
                    window.location.href = href;
                } else {
                    performQuickSearch();
                }
            } else {
                performQuickSearch();
            }
            return;
        }
    });

    $(document).on('mouseenter', '.suggest-item', function() {
        highlightItem($(this).data('index') !== undefined ? $(this).data('index') : -1);
    });

    $(document).on('click', '#searchAllLink', function(e) {
        e.preventDefault();
        performQuickSearch();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#quickSearch, #searchSuggestions').length) {
            $('#searchSuggestions').hide();
        }
    });

    $('#quickSearch').on('focus', function() {
        if ($('#searchSuggestions').children().length > 0 && $(this).val().trim().length >= 2) {
            $('#searchSuggestions').show();
        }
    });

    $('#quickSearchForm').on('submit', function(e) {
        if (suggestTimer) clearTimeout(suggestTimer);
        $('#searchSuggestions').hide();
    });

    $('#quickSearchBtn').on('click', function(e) {
        if (suggestTimer) clearTimeout(suggestTimer);
        $('#searchSuggestions').hide();
        document.getElementById('quickSearchForm').submit();
    });

    var mobileSearchTimer = null;
    var mobileSuggestTimer = null;
    var mobileSuggestXhr = null;
    var mobileLastSearched = '{{ request("search") }}';

    function performMobileSearch() {
        if (mobileSearchTimer) clearTimeout(mobileSearchTimer);
        if (mobileSuggestTimer) clearTimeout(mobileSuggestTimer);
        $('#mobileSuggestions').hide();
        $('#mobileSearchBtn').html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        document.getElementById('mobileSearchForm').submit();
    }

    var mobileLastValue = '{{ request("search") }}';
    
    function handleMobileSearchInput() {
        var value = $('#mobileQuickSearch').val().trim();
        if (value === mobileLastValue) return;
        mobileLastValue = value;

        if (mobileSuggestTimer) clearTimeout(mobileSuggestTimer);
        if (mobileSuggestXhr) { mobileSuggestXhr.abort(); }
        if (mobileSearchTimer) clearTimeout(mobileSearchTimer);

        if (value.length < 1) {
            $('#mobileSuggestions').hide();
            if (mobileLastSearched) {
                mobileSearchTimer = setTimeout(function() {
                    performMobileSearch();
                }, 600);
            }
            return;
        }

        if (value.length >= 2) {
            mobileSuggestTimer = setTimeout(function() {
                mobileSuggestXhr = $.ajax({
                    url: '{{ route("admin.clients.autocomplete") }}',
                    type: 'GET',
                    data: { q: value },
                    dataType: 'json',
                    success: function(data) {
                        showMobileSuggestions(data, value);
                    },
                    error: function() {
                        $('#mobileSuggestions').hide();
                    }
                });
            }, 150);
        }

        mobileSearchTimer = setTimeout(function() {
            if (value !== mobileLastSearched) {
                performMobileSearch();
            }
        }, 600);
    }

    $('#mobileQuickSearch').on('input keyup change paste', function() {
        handleMobileSearchInput();
    });

    $('#mobileQuickSearch').on('keydown', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            if (mobileSearchTimer) clearTimeout(mobileSearchTimer);
            if (mobileSuggestTimer) clearTimeout(mobileSuggestTimer);
            performMobileSearch();
        }
        if (e.keyCode === 27) {
            $('#mobileSuggestions').hide();
        }
    });

    $('#mobileQuickSearch').on('focus', function() {
        if ($('#mobileSuggestions').children().length > 0 && $(this).val().trim().length >= 2) {
            $('#mobileSuggestions').show();
        }
    });

    function showMobileSuggestions(data, query) {
        var $box = $('#mobileSuggestions');
        if (!data || data.length === 0) { $box.hide(); return; }
        var html = '';
        data.forEach(function(c) {
            var statusColor = c.status === 'active' ? '#28a745' : '#dc3545';
            var statusLabel = c.status === 'active' ? 'Active' : 'Inactive';
            html += '<a href="' + c.url + '" class="d-flex align-items-center px-3 py-2" style="text-decoration:none; color:#333; border-bottom:1px solid #f0f0f0;">'
                + '<div style="flex:1; min-width:0;">'
                + '<div style="font-weight:600; font-size:14px;" class="text-truncate">' + $('<span>').text(c.name).html() + '</div>'
                + '<div style="font-size:12px; color:#888;">'
                + '<span class="mr-2"><i class="fas fa-id-badge"></i> ' + $('<span>').text(c.client_id || '-').html() + '</span>'
                + '<span><i class="fas fa-phone"></i> ' + $('<span>').text(c.phone || '-').html() + '</span>'
                + '</div>'
                + '</div>'
                + '<div class="text-right ml-2">'
                + '<span class="badge" style="background:' + statusColor + '; color:#fff; font-size:10px;">' + statusLabel + '</span>'
                + '<div style="font-size:12px; font-weight:600; margin-top:2px;">' + c.due + '</div>'
                + '</div>'
                + '</a>';
        });
        html += '<a href="#" class="d-block text-center py-2 mobile-search-all-link" style="font-size:13px; color:#007bff; text-decoration:none; background:#f8f9fa;">'
            + '<i class="fas fa-search mr-1"></i>Search all for "' + $('<span>').text(query).html() + '"</a>';
        $box.html(html).show();
    }

    $(document).on('click', '.mobile-search-all-link', function(e) {
        e.preventDefault();
        performMobileSearch();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#mobileQuickSearch, #mobileSuggestions').length) {
            $('#mobileSuggestions').hide();
        }
    });
    
    // Temporary Deadline Modal
    var currentDeadlineClientId = null;
    
    $('.temp-deadline-btn').on('click', function() {
        var btn = $(this);
        currentDeadlineClientId = btn.data('client-id');
        var clientName = btn.data('client-name');
        var clientStatus = btn.data('client-status');
        var currentDeadline = btn.data('current-deadline');
        var tempDeadline = btn.data('temp-deadline');
        var billingDeadline = btn.data('billing-deadline');
        var billingDay = btn.data('billing-day');
        
        $('#deadlineClientName').text(clientName);
        $('#deadlineDate').val('');
        $('#deadlineReason').val('');
        $('#deadlineReasonPreset').val('');
        
        $('#permanentBillingDay').text(billingDay ? (billingDay + ' of every month') : 'N/A');
        // Show billing deadline as current deadline (not expire_date which is connection expiry)
        var _displayDeadline = tempDeadline || billingDeadline || currentDeadline || 'N/A';
        $('#currentDeadlineDate').text(_displayDeadline);
        
        if (tempDeadline) {
            var parts = tempDeadline.split('-');
            var formatted = parts[2] + '-' + parts[1] + '-' + parts[0];
            $('#currentDeadlineDate').append(' <span class="badge badge-warning">Extended: ' + formatted + '</span>');
            $('#clearDeadlineBtn').show();
        } else {
            $('#clearDeadlineBtn').hide();
        }

        if (clientStatus === 'inactive') {
            $('#autoActivateNotice').removeClass('d-none');
            $('#currentClientStatus').text(clientStatus);
            $('#deadlineStatusBadge').html('<span class="badge badge-danger">' + clientStatus.charAt(0).toUpperCase() + clientStatus.slice(1) + '</span>');
        } else {
            $('#autoActivateNotice').addClass('d-none');
            $('#deadlineStatusBadge').html('<span class="badge badge-success">Active</span>');
        }
        
        // Set min date = day after current effective billing deadline
        // Only use tempDeadline and billingDeadline (NOT expire_date which is connection expiry)
        var _dates = [tempDeadline, billingDeadline].filter(Boolean);
        var _effectiveDeadline = _dates.length ? _dates.reduce(function(a,b){ return a > b ? a : b; }) : null;
        if (_effectiveDeadline) {
            var _d = new Date(_effectiveDeadline + 'T00:00:00');
            _d.setDate(_d.getDate() + 1);
            window._deadlineMinDate = _d.getFullYear() + '-' +
                String(_d.getMonth()+1).padStart(2,'0') + '-' +
                String(_d.getDate()).padStart(2,'0');
            window._deadlineMinLabel = _effectiveDeadline;
        } else {
            var _t = new Date(); _t.setDate(_t.getDate()+1);
            window._deadlineMinDate = _t.toISOString().split('T')[0];
            window._deadlineMinLabel = new Date().toISOString().split('T')[0];
        }
        $('#deadlineDate').attr('min', window._deadlineMinDate);

        $('#tempDeadlineModal').modal('show');
    });
    
    $('#deadlineReasonPreset').on('change', function() {
        var val = $(this).val();
        if (val) {
            $('#deadlineReason').val(val);
        }
    });
    
    $('#tempDeadlineForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#submitDeadlineBtn');
        var deadlineVal = $('#deadlineDate').val();
        var reasonVal = $('#deadlineReason').val();

        if (!deadlineVal || !reasonVal) {
            Swal.fire('Error', 'Please fill in all fields', 'error');
            return;
        }

        // Validate date is after current effective deadline
        if (window._deadlineMinDate && deadlineVal < window._deadlineMinDate) {
            Swal.fire('Invalid Date', 'New deadline must be after <strong>' + window._deadlineMinLabel + '</strong>. You cannot set a date earlier than or equal to the current deadline.', 'error');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Applying...');
        
        $.ajax({
            url: '/admin/clients/' + currentDeadlineClientId + '/temp-deadline',
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                deadline_date: deadlineVal,
                reason: reasonVal
            },
            success: function(data) {
                if (data.success) {
                    Swal.fire({icon: 'success', title: 'Success', text: data.message || 'Deadline extended successfully', timer: 2000, showConfirmButton: false}).then(function() {
                        window.location.reload();
                    });
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i> Extend Deadline');
                    Swal.fire('Error', data.message || 'Failed to apply deadline', 'error');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i> Extend Deadline');
                var msg = 'Failed to apply deadline';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    msg = Object.values(errors).flat().join(', ');
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    $(document).on('click', '.clear-deadline-btn', function(e) {
        e.preventDefault();
        smartConfirm({message:'Clear the extended deadline?',confirmText:'Clear'}, function() {
            $.ajax({
                url: '/admin/clients/' + currentDeadlineClientId + '/clear-temp-deadline',
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(data) {
                    $('#tempDeadlineModal').modal('hide');
                    Swal.fire({icon: 'success', title: 'Success', text: data.message || 'Deadline cleared', timer: 2000, showConfirmButton: false}).then(function() {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    var msg = 'Failed to clear deadline';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });

});

function loadMobileFilterAreas(zoneId) {
    var areaSelect = document.getElementById('mobileFilterAreaSelect');
    areaSelect.innerHTML = '<option value="">All Areas</option>';
    if (!zoneId) return;
    
    fetch('/admin/api/areas-by-zone/' + zoneId)
        .then(function(r) { return r.json(); })
        .then(function(areas) {
            areas.forEach(function(area) {
                var opt = document.createElement('option');
                opt.value = area.id;
                opt.textContent = area.name;
                areaSelect.appendChild(opt);
            });
        }).catch(function() {});
}

function loadFilterAreas(zoneId) {
    var areaSelect = document.getElementById('areaFilter');
    var boxSelect = document.getElementById('boxFilter');
    areaSelect.innerHTML = '<option value="">All Areas</option>';
    boxSelect.innerHTML = '<option value="">All TJ</option>';
    if (!zoneId) return;
    
    fetch('/admin/api/areas-by-zone/' + zoneId)
        .then(function(r) { return r.json(); })
        .then(function(areas) {
            areas.forEach(function(area) {
                var opt = document.createElement('option');
                opt.value = area.id;
                opt.textContent = area.name;
                areaSelect.appendChild(opt);
            });
        }).catch(function() {});
}

function loadFilterBoxes(areaId) {
    var boxSelect = document.getElementById('boxFilter');
    boxSelect.innerHTML = '<option value="">All TJ</option>';
    if (!areaId) return;
    
    fetch('/admin/api/boxes-by-area/' + areaId)
        .then(function(r) { return r.json(); })
        .then(function(boxes) {
            boxes.forEach(function(box) {
                var opt = document.createElement('option');
                opt.value = box.id;
                opt.textContent = box.name;
                boxSelect.appendChild(opt);
            });
        }).catch(function() {});
}

function loadBulkAreas(zoneId) {
    var areaSelect = document.getElementById('bulkAreaSelect');
    var boxSelect = document.getElementById('bulkBoxSelect');
    areaSelect.innerHTML = '<option value="">Sub Zone</option>';
    boxSelect.innerHTML = '<option value="">TJ/Box</option>';
    if (!zoneId) return;
    fetch('/admin/api/areas-by-zone/' + zoneId)
        .then(function(r) { return r.json(); })
        .then(function(areas) {
            areas.forEach(function(area) {
                var opt = document.createElement('option');
                opt.value = area.id;
                opt.textContent = area.name;
                areaSelect.appendChild(opt);
            });
        }).catch(function() {});
}

function loadBulkBoxes(areaId) {
    var boxSelect = document.getElementById('bulkBoxSelect');
    boxSelect.innerHTML = '<option value="">TJ/Box</option>';
    if (!areaId) return;
    fetch('/admin/api/boxes-by-area/' + areaId)
        .then(function(r) { return r.json(); })
        .then(function(boxes) {
            boxes.forEach(function(box) {
                var opt = document.createElement('option');
                opt.value = box.id;
                opt.textContent = box.name;
                boxSelect.appendChild(opt);
            });
        }).catch(function() {});
}

function formatUptime(totalSeconds) {
    var weeks = Math.floor(totalSeconds / 604800);
    totalSeconds %= 604800;
    var days = Math.floor(totalSeconds / 86400);
    totalSeconds %= 86400;
    var hours = Math.floor(totalSeconds / 3600);
    totalSeconds %= 3600;
    var minutes = Math.floor(totalSeconds / 60);
    var seconds = totalSeconds % 60;
    var parts = '';
    if (weeks > 0) parts += weeks + 'w';
    if (days > 0) parts += days + 'd';
    if (hours > 0) parts += hours + 'h';
    if (minutes > 0) parts += minutes + 'm';
    parts += seconds + 's';
    return parts;
}

function tickUptime(increment) {
    var els = document.querySelectorAll('.live-uptime');
    els.forEach(function(el) {
        var sec = parseInt(el.getAttribute('data-uptime-seconds'), 10);
        if (!isNaN(sec)) {
            sec += increment;
            el.setAttribute('data-uptime-seconds', sec);
            el.textContent = formatUptime(sec);
        }
    });
}

tickUptime(0);
setInterval(function() { tickUptime(1); }, 1000);

$(document).ready(function() {
    // Live Ping Check
    $('.ping-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        var clientId = btn.data('client-id');
        var ip = btn.data('ip');
        var clientName = btn.data('client-name') || '';
        
        if (!ip) {
            toastr.warning('No IP address configured for this client');
            return;
        }
        
        openPingModal(clientId, ip, clientName);
    });

    var pendingToggle = null;

    $(document).on('change', '.mikrotik-toggle', function(e) {
        var $toggle = $(this);
        var clientId = $toggle.data('client-id');
        var isNowOn = $toggle.prop('checked');

        $toggle.prop('disabled', true);
        pendingToggle = { clientId: clientId, element: $toggle };

        $.ajax({
            url: '/admin/clients/' + clientId + '/toggle-mikrotik',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: { reason: isNowOn ? 'Activated by admin' : 'Deactivated by admin' },
            success: function(resp) {
                if (resp.success && pendingToggle) {
                    var cid = pendingToggle.clientId;
                    var isOn = resp.status === 'on';
                    $('.mikrotik-toggle[data-client-id="' + cid + '"]').prop('checked', isOn).prop('disabled', false);
                    $('#mk-label-' + cid + ', #mk-label-m-' + cid).text(isOn ? 'ON' : 'OFF').removeClass('text-success text-danger').addClass(isOn ? 'text-success' : 'text-danger');
                    var $statusCell = $('tr').find('.mikrotik-toggle[data-client-id="' + cid + '"]').closest('tr').find('td').eq(7);
                    if ($statusCell.length) {
                        var statusBadge = $statusCell.find('div:first .status-badge');
                        if (isOn) { statusBadge.text('ACTIVE').css({'border-color': '#28a745', 'color': '#28a745'}); }
                        else { statusBadge.text('INACTIVE').css({'border-color': '#dc3545', 'color': '#dc3545'}); }
                    }
                    var $mobileCard = $('.client-card .mikrotik-toggle[data-client-id="' + cid + '"]').closest('.client-card');
                    if ($mobileCard.length) {
                        var mBadge = $mobileCard.find('.mobile-status-badge');
                        if (isOn) { mBadge.text('Active').removeClass('inactive').addClass('active'); }
                        else { mBadge.text('Inactive').removeClass('active').addClass('inactive'); }
                    }
                    toastr.success(resp.message || (isOn ? 'MikroTik Activated' : 'MikroTik Deactivated'));
                } else {
                    $toggle.prop('checked', !isNowOn).prop('disabled', false);
                    toastr.warning(resp.message || 'Toggle failed');
                }
            },
            error: function(xhr) {
                $toggle.prop('checked', !isNowOn).prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'MikroTik toggle failed. Please try again.';
                toastr.error(msg);
            },
            complete: function() {
                $toggle.prop('disabled', false);
                pendingToggle = null;
            }
        });
    });

    $('#mtm_reason_preset').on('change', function() {
        var v = $(this).val();
        if (v) $('#mtm_reason').val(v);
    });

    $('#mikrotikToggleModal').on('hidden.bs.modal', function() {
        pendingToggle = null;
    });

    $('#mikrotikToggleForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#mtm_submit_btn');
        var actionUrl = $form.attr('action');
        var reason = $('#mtm_reason').val();

        if (!reason) {
            $('#mtm_reason').focus();
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');

        $.ajax({
            url: actionUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: { reason: reason },
            success: function(resp) {
                $('#mikrotikToggleModal').modal('hide');
                if (resp.success && pendingToggle) {
                    var cid = pendingToggle.clientId;
                    var isOn = resp.status === 'on';
                    $('.mikrotik-toggle[data-client-id="' + cid + '"]').prop('checked', isOn);
                    $('#mk-label-' + cid + ', #mk-label-m-' + cid).text(isOn ? 'ON' : 'OFF').removeClass('text-success text-danger').addClass(isOn ? 'text-success' : 'text-danger');
                    var $statusCell = $('tr').find('.mikrotik-toggle[data-client-id="' + cid + '"]').closest('tr').find('td').eq(7);
                    if ($statusCell.length) {
                        var statusBadge = $statusCell.find('div:first .status-badge');
                        if (isOn) {
                            statusBadge.text('ACTIVE').css({'border-color': '#28a745', 'color': '#28a745'});
                        } else {
                            statusBadge.text('INACTIVE').css({'border-color': '#dc3545', 'color': '#dc3545'});
                        }
                    }
                    var $mobileCard = $('.client-card .mikrotik-toggle[data-client-id="' + cid + '"]').closest('.client-card');
                    if ($mobileCard.length) {
                        var mBadge = $mobileCard.find('.mobile-status-badge');
                        if (isOn) {
                            mBadge.text('Active').removeClass('inactive').addClass('active');
                        } else {
                            mBadge.text('Inactive').removeClass('active').addClass('inactive');
                        }
                    }
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({icon: 'success', title: resp.message || 'Done', timer: 2000, showConfirmButton: false});
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Toggle failed. Please try again.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({icon: 'error', title: 'Error', text: msg});
                } else {
                    smartAlert(msg, 'warning');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Confirm');
                pendingToggle = null;
            }
        });
    });
});

function checkQpAmountDiff() {
    var due = parseFloat($('#qp_amount').data('due')) || 0;
    var amount = parseFloat($('#qp_amount').val()) || 0;
    var isDifferent = Math.abs(amount - due) > 0.01;
    $('#qp_amount_warning').toggle(isDifferent);
    $('#qp_reason_group').toggle(isDifferent);
    $('#qp_reason').prop('required', isDifferent);
}

function toggleQpOnlineFields() {
    var sel = document.getElementById('qp_method');
    var name = sel.options[sel.selectedIndex].getAttribute('data-name') || '';
    var isCash = name.indexOf('cash') !== -1;
    var isOnline = name.includes('bkash') || name.includes('nagad') || name.includes('rocket') || name.includes('upay') || name.includes('mobile');
    document.getElementById('qp_online_fields').style.display = isOnline ? '' : 'none';
    document.getElementById('qp_cash_receipt_field').style.display = isCash ? '' : 'none';
    if (!isCash) document.getElementById('qp_money_receipt').value = '';
    if (name.includes('bkash')) document.getElementById('qp_number_label').textContent = 'bKash Number';
    else if (name.includes('nagad')) document.getElementById('qp_number_label').textContent = 'Nagad Number';
    else if (name.includes('rocket')) document.getElementById('qp_number_label').textContent = 'Rocket Number';
    else document.getElementById('qp_number_label').textContent = 'Mobile Number';
}

$(document).on('click', '.quick-pay-open', function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    var clientId = $(this).data('client-id');
    var due = parseFloat($(this).data('due')) || 0;
    $('#qp_id').val(id);
    $('#qp_client_name').text(name);
    $('#qp_client_id').text(clientId || '');
    $('#qp_due_amount').text('৳' + due.toFixed(2)).css('color', due > 0 ? '#dc3545' : '#28a745');
    $('#qp_amount').val(Math.max(1, due)).data('due', due);
    $('#qp_trx_id').val('');
    $('#qp_sender').val('');
    $('#qp_money_receipt').val('');
    $('#qp_notes').val('');
    $('#qp_reason').val('').prop('required', false);
    $('#qp_reason_group').hide();
    $('#qp_amount_warning').hide();
    $('#qp_send_sms').prop('checked', true);
    $('#qp_method').val($('#qp_method option:first').val());
    toggleQpOnlineFields();
    $('#quickPayModal').modal('show');
});

$(document).on('click', '.change-pkg-open', function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    var clientId = $(this).data('client-id');
    var pkgId = $(this).data('package-id');
    var pkgName = $(this).data('package-name');
    var monthlyFee = parseFloat($(this).data('monthly-fee')) || 0;
    var ip = $(this).data('ip') || '';
    var profile = $(this).data('profile') || '';
    $('#cp_id').val(id);
    $('#cp_billing_day_val').val(parseInt($(this).data('billing-day')) || 1);
    $('#cp_client_name').text(name);
    $('#cp_client_id').text(clientId || '');
    $('#cp_current_package').text(pkgName);
    $('#cp_current_fee').text('৳' + monthlyFee.toLocaleString());
    $('#cp_ip_address').val(ip);
    $('#cp_mikrotik_profile').val(profile);
    $('#cp_current_profile_text').text(profile || '-');
    $('#cp_package_id').val('');
    $('#cp_note').val('');
    $('#cp_new_price').val('');
    $('#cp_new_price_group').hide();
    $('input[name="cp_billing_option"][value="day_calculation"]').prop('checked', true);
    $('#changePackageModal').modal('show');
});

$('#cp_package_id').on('change', function() {
    var opt = $(this).find('option:selected');
    var pkgProfile = opt.data('profile') || '';
    if (pkgProfile) {
        var $profileSelect = $('#cp_mikrotik_profile');
        if ($profileSelect.find('option[value="' + pkgProfile + '"]').length) {
            $profileSelect.val(pkgProfile);
        }
    }
});

$('input[name="cp_billing_option"]').on('change', function() {
    var val = $(this).val();
    if (val === 'set_new_price') {
        $('#cp_new_price_group').show();
        $('#cp_day_calc_preview').hide();
        var selectedOpt = $('#cp_package_id option:selected');
        if (selectedOpt.val()) { $('#cp_new_price').val(selectedOpt.data('price')); }
    } else {
        $('#cp_new_price_group').hide();
        if (val === 'day_calculation') { updateDayCalcPreview(); }
        else { $('#cp_day_calc_preview').hide(); }
    }
});

$('#cp_package_id').off('change.daycalc').on('change.daycalc', function() {
    if ($('input[name="cp_billing_option"]:checked').val() === 'day_calculation') { updateDayCalcPreview(); }
    if ($('input[name="cp_billing_option"]:checked').val() === 'set_new_price') {
        var p = $(this).find('option:selected').data('price');
        if (p) $('#cp_new_price').val(p);
    }
});

function updateDayCalcPreview() {
    var pkgOpt = $('#cp_package_id option:selected');
    if (!$('#cp_package_id').val()) { $('#cp_day_calc_preview').hide(); return; }
    var oldFee = parseFloat($('#cp_current_fee').text().replace(/[^0-9.]/g,'')) || 0;
    var newFee = parseFloat(pkgOpt.data('price')) || 0;
    var billingDay = parseInt($('#cp_billing_day_val').val()) || 1;
    var today = new Date();
    var cycleStart = new Date(today.getFullYear(), today.getMonth(), billingDay);
    if (cycleStart > today) { cycleStart.setMonth(cycleStart.getMonth() - 1); }
    var nextCycle = new Date(cycleStart.getFullYear(), cycleStart.getMonth() + 1, billingDay);
    var totalDays = Math.round((nextCycle - cycleStart) / 86400000);
    var remainingDays = Math.max(Math.round((nextCycle - today) / 86400000), 0);
    var diff = totalDays > 0 ? Math.round(((newFee - oldFee) / totalDays) * remainingDays * 100) / 100 : 0;
    var html = '<table style="width:100%;border-collapse:collapse;">'+
        '<tr><td style="color:#555;padding:2px 0;">Billing cycle:</td><td style="text-align:right;font-weight:600;">'+totalDays+' days</td></tr>'+
        '<tr><td style="color:#555;padding:2px 0;">Remaining days:</td><td style="text-align:right;font-weight:600;">'+remainingDays+' days</td></tr>'+
        '<tr><td style="color:#555;padding:2px 0;">Old fee:</td><td style="text-align:right;">&#2547;'+oldFee.toLocaleString()+'</td></tr>'+
        '<tr><td style="color:#555;padding:2px 0;">New fee:</td><td style="text-align:right;">&#2547;'+newFee.toLocaleString()+'</td></tr>';
    if (oldFee === newFee) {
        html += '<tr><td colspan="2" style="color:#6c757d;text-align:center;padding-top:6px;">Same price — no adjustment.</td></tr>';
    } else if (diff > 0) {
        html += '<tr style="border-top:1px solid #a3d7a3;"><td style="color:#155724;font-weight:700;padding-top:6px;">Upgrade bill:</td><td style="text-align:right;color:#155724;font-weight:700;padding-top:6px;">&#2547;'+diff.toLocaleString()+'</td></tr>';
    } else {
        html += '<tr style="border-top:1px solid #a3d7a3;"><td style="color:#856404;font-weight:700;padding-top:6px;">Credit added:</td><td style="text-align:right;color:#856404;font-weight:700;padding-top:6px;">&#2547;'+Math.abs(diff).toLocaleString()+'</td></tr>';
    }
    html += '</table>';
    $('#cp_day_calc_details').html(html);
    $('#cp_day_calc_preview').show();
}

$('#changePackageForm').on('submit', function(e) {
    e.preventDefault();
    var btn = $('#cp_submit_btn');
    var pkgVal = $('#cp_package_id').val();
    var profileVal = $('#cp_mikrotik_profile').val();
    if (!pkgVal) { toastr.error('Please select a new package.'); return; }
    if (!profileVal) { toastr.error('Please select a MikroTik profile.'); return; }
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');
    var clientId = $('#cp_id').val();
    var billingOption = $('input[name="cp_billing_option"]:checked').val();
    $.ajax({
        url: '/admin/clients/' + clientId + '/change-package',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        data: {
            package_id: pkgVal,
            billing_option: billingOption,
            mikrotik_profile: profileVal,
            new_price: $('#cp_new_price').val() || null,
            ip_address: $('#cp_ip_address').val() || null,
            note: $('#cp_note').val() || null
        },
        success: function(res) {
            if (res.success) {
                $('#changePackageModal').modal('hide');
                toastr.success(res.message || 'Package changed successfully.');
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                toastr.error(res.message || 'Failed to change package.');
            }
        },
        error: function(xhr) {
            var msg = 'Failed to change package.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                } else if (xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
            }
            toastr.error(msg);
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-exchange-alt mr-1"></i>Change Package');
        }
    });
});

$('#quickPayForm').on('submit', function(e) {
    e.preventDefault();
    var btn = $('#qp_submit_btn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');
    var clientId = $('#qp_id').val();
    $.ajax({
        url: '/admin/clients/' + clientId + '/quick-payment',
        timeout: 30000,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
        data: {
            amount: $('#qp_amount').val(),
            payment_method_id: $('#qp_method').val(),
            transaction_id: $('#qp_trx_id').val(),
            sender_number: $('#qp_sender').val(),
            money_receipt_no: $('#qp_money_receipt').val(),
            notes: $('#qp_notes').val(),
            notes_reason: $('#qp_reason').val(),
            send_sms: $('#qp_send_sms').is(':checked') ? 1 : 0
        },
        success: function(res) {
            if (res.success) {
                var qpModal = '#quickPayModal';
                var rcModal = '#quickPayReceiptModal';
                $(qpModal).modal('hide');
                $('#qpr_message').text(res.message || 'Payment Successful!');
                $('#qpr_details').text('Payment #' + (res.payment_no || ''));
                if (res.thermal_url) {
                    $('#qpr_thermal_link').attr('href', res.thermal_url).show();
                } else {
                    $('#qpr_thermal_link').hide();
                }
                $(rcModal).modal('show').on('hidden.bs.modal', function() { location.reload(); });
                toastr.success(res.message || 'Payment recorded successfully!');
            } else {
                $('#quickPayModal').modal('hide');
                toastr.error(res.message || 'Payment failed.');
            }
        },
        error: function(xhr) {
            var msg = 'Payment failed.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                } else if (xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
            }
            toastr.error(msg);
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Submit Payment');
        }
    });
});
</script>

<script>
(function() {
    $.ajax({
        url: '{{ route("admin.api.instant-sync-status") }}',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        data: { company_id: '{{ request('company_id') }}', all_co: '{{ request('all_co') }}' },
        timeout: 30000,
        success: function(res) {
            if (res.success) {
                var statuses = res.client_statuses || {};
                for (var cid in statuses) {
                    var s = statuses[cid];
                    var badges = $('[data-client-id-status="' + cid + '"]');
                    if (badges.length) {
                        if (s.status === 'online') {
                            badges.html('<span class="badge status-badge" style="border: 1.5px solid #007bff; color: #007bff; background: transparent; font-weight: 600; padding: 4px 12px;">ONLINE</span>');
                        } else {
                            badges.html('<span class="badge status-badge" style="border: 1.5px solid #dc3545; color: #dc3545; background: transparent; font-weight: 600; padding: 4px 12px;">OFFLINE</span>');
                        }
                        // Update status dot
                        var dot = $("[data-dot-id=\"" + cid + "\"]");
                        if (dot.length) {
                            dot.removeClass('dot-active dot-due dot-inactive dot-other');
                            if (s.status === 'online') {
                                dot.addClass('dot-active').attr('title', 'Online');
                            } else if (dot.data('clientActive') == '1') {
                                dot.addClass('dot-due').attr('title', 'Active (Offline)');
                            } else {
                                dot.addClass('dot-inactive').attr('title', 'Inactive');
                            }
                        }
                    }
                }
                $('.stat-online-count').text(res.total_online);
                $('#sb-online').text(res.total_online);
                $('#sb-offline').text(res.total_offline);
                $('.stat-offline-count').text(res.total_offline);
            }
        }
    });
})();

(function() {
    var selectAll = document.getElementById('selectAll');
    var bulkActionBar = document.getElementById('bulkActionBar');
    var selectedCountEl = document.getElementById('selectedCount');

    function getCheckboxes() {
        return document.querySelectorAll('.client-checkbox');
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.client-checkbox:checked')).map(function(cb) { return cb.value; });
    }

    function updateBulkBar() {
        var ids = getSelectedIds();
        var checkboxes = getCheckboxes();
        selectedCountEl.textContent = ids.length;
        if (ids.length > 0) {
            bulkActionBar.style.display = '';
            bulkActionBar.style.cssText = 'background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-color: #90caf9 !important;';
        } else {
            bulkActionBar.style.display = 'none';
            bulkActionBar.style.cssText = 'display: none !important;';
        }
        selectAll.checked = checkboxes.length > 0 && ids.length === checkboxes.length;
        selectAll.indeterminate = ids.length > 0 && ids.length < checkboxes.length;
    }

    selectAll.addEventListener('change', function() {
        var checked = this.checked;
        getCheckboxes().forEach(function(cb) { cb.checked = checked; });
        updateBulkBar();
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('client-checkbox')) updateBulkBar();
    });

    document.getElementById('btnClearSelection').addEventListener('click', function() {
        getCheckboxes().forEach(function(cb) { cb.checked = false; });
        selectAll.checked = false;
        selectAll.indeterminate = false;
        updateBulkBar();
    });

    function submitBulkAction(action, extraData) {
        var ids = getSelectedIds();
        if (ids.length === 0) { alert('No clients selected.'); return; }

        var form = document.getElementById('bulkForm');
        document.getElementById('bulkActionInput').value = action;
        if (extraData && extraData.package_id) {
            document.getElementById('bulkPackageInput').value = extraData.package_id;
        }
        form.querySelectorAll('.bulk-id-input').forEach(function(el) { el.remove(); });
        ids.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = 'client_ids[]'; input.value = id;
            input.className = 'bulk-id-input';
            form.appendChild(input);
        });

        var msg = '';
        if (action === 'activate') msg = ids.length + ' client(s) activate korben?';
        else if (action === 'deactivate') msg = ids.length + ' client(s) deactivate korben?';
        else if (action === 'change_package') msg = ids.length + ' client(s) er package change korben?';
        else if (action === 'change_zone') msg = ids.length + ' client(s) er zone change korben?';
        else if (action === 'change_area') msg = ids.length + ' client(s) er sub zone change korben?';
        else if (action === 'change_box') msg = ids.length + ' client(s) er TJ/Box change korben?';
        else if (action === 'mikrotik_enable') msg = ids.length + ' client(s) er MikroTik profile enable (restore) korben?';
        else if (action === 'mikrotik_disable') msg = ids.length + ' client(s) er MikroTik profile Inactive set korben?';

        smartConfirm({message:msg,isDelete:/delete|disable/i.test(msg)},function(){form.submit();});
    }

    document.getElementById('btnBulkActivate').addEventListener('click', function() { submitBulkAction('activate'); });
    document.getElementById('btnBulkDeactivate').addEventListener('click', function() { submitBulkAction('deactivate'); });
    document.getElementById('btnBulkChangePackage').addEventListener('click', function() {
        var pkgId = document.getElementById('bulkPackageSelect').value;
        if (!pkgId) { alert('Please select a package first.'); return; }
        submitBulkAction('change_package', { package_id: pkgId });
    });
    document.getElementById('btnBulkChangeZone').addEventListener('click', function() {
        var zoneId = document.getElementById('bulkZoneSelect').value;
        if (!zoneId) { alert('Please select a zone first.'); return; }
        document.getElementById('bulkZoneInput').value = zoneId;
        submitBulkAction('change_zone');
    });
    document.getElementById('btnBulkChangeArea').addEventListener('click', function() {
        var areaId = document.getElementById('bulkAreaSelect').value;
        if (!areaId) { alert('Please select a sub zone first.'); return; }
        document.getElementById('bulkAreaInput').value = areaId;
        submitBulkAction('change_area');
    });
    document.getElementById('btnBulkChangeBox').addEventListener('click', function() {
        var boxId = document.getElementById('bulkBoxSelect').value;
        if (!boxId) { alert('Please select a TJ/Box first.'); return; }
        document.getElementById('bulkBoxInput').value = boxId;
        submitBulkAction('change_box');
    });

    document.getElementById('btnBulkMikrotikEnable').addEventListener('click', function() {
        submitBulkAction('mikrotik_enable');
    });
    document.getElementById('btnBulkMikrotikDisable').addEventListener('click', function() {
        submitBulkAction('mikrotik_disable');
    });

    document.getElementById('btnBulkMikrotikSync').addEventListener('click', function() {
        smartConfirm({message:'Sync selected clients DB profile to MikroTik?',confirmText:'Sync'}, function() {
            submitBulkAction('mikrotik_sync');
        });
    });

    document.getElementById('btnBulkExport').addEventListener('click', function() {
        var ids = getSelectedIds();
        if (ids.length === 0) { alert('No clients selected.'); return; }
        var form = document.getElementById('exportForm');
        form.querySelectorAll('.export-id-input').forEach(function(el) { el.remove(); });
        ids.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = 'client_ids[]'; input.value = id;
            input.className = 'export-id-input';
            form.appendChild(input);
        });
        form.submit();
    });
})();
</script>

<script>
// ── Detail Popup (click-triggered popover) ─────────────────────────────────
$(document).ready(function () {
    // Close any open popover when clicking elsewhere
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.pop-trigger, .popover').length) {
            $('.pop-trigger').each(function () {
                var $el = $(this);
                if ($el.data('bs.popover')) $el.popover('hide');
            });
        }
    });

    // Pop trigger click
    $(document).on('click', '.pop-trigger', function (e) {
        e.stopPropagation();
        var $trigger = $(this);
        var targetId = $trigger.data('pop-target');
        var $contentDiv = $('#' + targetId);

        if (!$contentDiv.length) return;

        // Hide all other open popovers first
        $('.pop-trigger').not($trigger).each(function () {
            if ($(this).data('bs.popover')) $(this).popover('hide');
        });

        // Init popover if not done yet
        if (!$trigger.data('bs.popover')) {
            $trigger.popover({
                html: true,
                trigger: 'manual',
                container: 'body',
                placement: 'auto',
                content: function () { return $('#' + $trigger.data('pop-target')).html(); },
                template: '<div class="popover shadow border" role="tooltip" style="max-width:320px;font-size:13px;">' +
                          '<div class="arrow"></div>' +
                          '<div class="popover-body p-3" style="line-height:1.9;"></div>' +
                          '</div>',
            });
        }
        $trigger.popover('toggle');
    });
});
</script>



<script>
// Scroll to top button (AdminLTE uses .content-wrapper as scroll container)
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('scrollTopBtn');
    if (!btn) return;
    var scroller = document.querySelector('.content-wrapper') || window;
    var getScrollTop = function() {
        return scroller === window ? window.scrollY : scroller.scrollTop;
    };
    scroller.addEventListener('scroll', function() {
        if (getScrollTop() > 300) {
            btn.style.display = 'flex';
        } else {
            btn.style.display = 'none';
        }
    });
    btn.addEventListener('click', function() {
        if (scroller === window) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            scroller.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
});

</script>


<script>
// ── Column Sort Helper ──────────────────────────────────────────────────────
function colSort(col, defaultDir) {
    var url = new URL(window.location.href);
    var currentSort = url.searchParams.get('sort_by') || '';
    var currentDir  = url.searchParams.get('sort_dir') || defaultDir;
    var newDir;
    if (currentSort === col) {
        newDir = (currentDir === 'asc') ? 'desc' : 'asc';
    } else {
        newDir = defaultDir;
    }
    url.searchParams.set('sort_by', col);
    url.searchParams.set('sort_dir', newDir);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// ── Dynamic Company Stats (Super Admin only) ──────────────────────────────────────────────────────
(function() {
    var statsUrl = '{{ route("admin.api.company-stats") }}';
    var currentCompanyId = '{{ request("company_id") }}';
    var currentAllCo = '{{ request("all_co") }}';

    function updateAllStats(data) {
        // Stat bar
        var el = function(id) { return document.getElementById(id); };
        if (el('stat-total'))   el('stat-total').textContent   = data.total;
        if (el('stat-active'))  el('stat-active').textContent  = data.active;
        if (el('stat-inactive'))el('stat-inactive').textContent= data.inactive;
        if (el('stat-due'))     el('stat-due').textContent     = data.due;
        document.querySelectorAll('.stat-online-count').forEach(function(e){ e.textContent = data.online; });
        document.querySelectorAll('.stat-offline-count').forEach(function(e){ e.textContent = data.offline; });

        // INST FREE / INST Due show/hide
        var freeWrap = el('stat-inst-free-wrap');
        var dueWrap  = el('stat-inst-due-wrap');
        if (freeWrap) { freeWrap.style.display = data.inst_free > 0 ? '' : 'none'; }
        if (el('stat-inst-free')) el('stat-inst-free').textContent = data.inst_free;
        if (dueWrap)  { dueWrap.style.display  = data.inst_due  > 0 ? '' : 'none'; }
        if (el('stat-inst-due'))  el('stat-inst-due').textContent  = data.inst_due;

        // Summary cards
        if (el('sb-online'))           el('sb-online').textContent           = data.online;
        if (el('sb-offline'))          el('sb-offline').textContent          = data.offline;
        if (el('sb-active'))           el('sb-active').textContent           = data.active;
        if (el('sb-inactive'))         el('sb-inactive').textContent         = data.inactive;
        if (el('sb-monthly-new'))      el('sb-monthly-new').textContent      = data.monthly_new;
        if (el('sb-monthly-inactive')) el('sb-monthly-inactive').textContent = data.monthly_inactive;
        if (el('sb-today-inactive'))   el('sb-today-inactive').textContent   = data.today_inactive;
        if (el('sb-today-active'))     el('sb-today-active').textContent     = data.today_active;
        if (el('sb-today-new'))        el('sb-today-new').textContent        = data.today_new;
        if (el('sb-total'))            el('sb-total').textContent            = data.total;
    }

    function fetchStats(companyId, allCo) {
        var params = '';
        if (companyId) params = '?company_id=' + companyId;
        else if (allCo) params = '?all_co=1';

        $.ajax({
            url: statsUrl + params,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(data) { updateAllStats(data); },
            error: function() {}
        });
    }

    // Intercept company dropdown clicks
    $(document).on('click', '.company-switch-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var companyId = $btn.data('company-id') || '';
        var allCo     = $btn.data('all-co') || '';
        var label     = $btn.data('label') || 'Super Admin';
        var icon      = $btn.data('icon') || 'fa-user-shield';

        // Update dropdown button label
        $('#companyDropdown').html('<i class="fas ' + icon + ' mr-1"></i>' + label + ' <span class="caret"></span>');

        // Update active class
        $('.company-switch-btn').removeClass('active');
        $btn.addClass('active');

        // Navigate to new URL so client list also reloads
        var url = new URL(window.location.href);
        url.searchParams.delete('company_id');
        url.searchParams.delete('all_co');
        url.searchParams.delete('page');
        if (companyId) url.searchParams.set('company_id', companyId);
        if (allCo)     url.searchParams.set('all_co', allCo);
        window.location.href = url.toString();
    });
})();

// ── Action dropdown portal fix ──────────────────────────────────────────────
// Moves the dropdown menu to <body> on open so overflow:hidden parents
// (card, table-responsive) cannot clip it.
(function () {
    var $activeMenu = null;
    var $originalParent = null;

    $(document).on("show.bs.dropdown", "#clientsTable .action-btns .dropdown", function () {
        var $toggle = $(this).find(".dropdown-toggle");
        var $menu   = $(this).find(".dropdown-menu");

        // Store original parent so we can restore on hide
        $originalParent = $menu.parent();

        // Append to body
        $menu.appendTo("body");
        $activeMenu = $menu;

        // Position under the toggle button
        function positionMenu() {
            var rect = $toggle[0].getBoundingClientRect();
            var menuW = $menu.outerWidth();
            var top   = rect.bottom + window.scrollY + 2;
            var left  = rect.right + window.scrollX - menuW;
            $menu.css({
                position:  "absolute",
                top:        top + "px",
                left:       left + "px",
                display:    "block",
                zIndex:     99999
            });
        }

        positionMenu();

        // Reposition if window scrolls
        $(window).on("scroll.actionDropdown resize.actionDropdown", positionMenu);
    });

    $(document).on("hide.bs.dropdown", "#clientsTable .action-btns .dropdown", function () {
        if ($activeMenu && $originalParent) {
            $activeMenu.css({ display: "", position: "", top: "", left: "", zIndex: "" });
            $activeMenu.appendTo($originalParent);
            $activeMenu = null;
            $originalParent = null;
        }
        $(window).off("scroll.actionDropdown resize.actionDropdown");
    });
})();
</script>

@endpush