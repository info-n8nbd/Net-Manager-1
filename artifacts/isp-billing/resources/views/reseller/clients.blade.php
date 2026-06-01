@extends('layouts.reseller')

@section('title', 'My Clients')
@section('breadcrumb')
<li class="breadcrumb-item active">Clients</li>
@endsection

@push('styles')
<style>
/* Detail popup triggers */
td.pop-trigger { cursor: pointer; transition: background .12s; }
td.pop-trigger:hover { background: rgba(0,123,255,0.06) !important; }
.popover { max-width: 320px !important; font-size: 13px !important; }
.popover-body { line-height: 1.9; padding: 10px 14px !important; }
.popover-body div { padding: 1px 0; }
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
    padding: 5px 10px;
    font-size: 12px;
}
.table thead th {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
    color: white !important;
    font-weight: 500;
    font-size: 14px;
    white-space: nowrap;
    border-color: #1e3c72 !important;
}
.table td {
    font-size: 14px;
    vertical-align: middle;
}
.action-btns .btn {
    width: 29px;
    height: 29px;
    padding: 0;
    margin: 1px;
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
}
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
    margin-top: 10px;
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
        display: none !important;
    }
}
.mk-toggle {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 18px;
    vertical-align: middle;
    cursor: pointer;
}
.mk-toggle input { opacity: 0; width: 0; height: 0; }
.mk-slider {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #dc3545;
    border-radius: 18px;
    transition: .3s;
}
.mk-slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    border-radius: 50%;
    transition: .3s;
}
.mk-toggle input:checked + .mk-slider { background-color: #28a745; }
.mk-toggle input:checked + .mk-slider:before { transform: translateX(18px); }
.mk-toggle input:disabled + .mk-slider { opacity: 0.5; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="card-title"><i class="fas fa-users mr-2"></i>My Clients</h3>
            <div class="filter-buttons">
                <a href="{{ route('reseller.clients') }}" class="btn btn-{{ !request('status') ? 'primary' : 'outline-primary' }} btn-sm">
                    <i class="fas fa-list mr-1"></i>All
                </a>
                <a href="{{ route('reseller.clients', ['status' => 'active']) }}" class="btn btn-{{ request('status') == 'active' ? 'success' : 'outline-success' }} btn-sm">
                    Active
                </a>
                <a href="{{ route('reseller.clients', ['status' => 'inactive']) }}" class="btn btn-{{ request('status') == 'inactive' ? 'danger' : 'outline-danger' }} btn-sm">
                    Inactive
                </a>
                <a href="{{ route('reseller.clients', ['status' => 'due']) }}" class="btn btn-{{ request('status') == 'due' ? 'warning' : 'outline-warning' }} btn-sm">
                    Due ({{ $dueClients ?? 0 }})
                </a>
                @can('reseller.client_restore')
                <a href="{{ route('reseller.clients', ['status' => 'deleted']) }}" class="btn btn-{{ request('status') == 'deleted' ? 'dark' : 'outline-dark' }} btn-sm">
                    <i class="fas fa-trash-alt mr-1"></i>Deleted @if(($deletedClients ?? 0) > 0)<span class="badge badge-danger">{{ $deletedClients }}</span>@endif
                </a>
                @endcan
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="stats-summary">
            <div class="stat-item">
                <span class="stat-label">Total:</span>
                <span class="stat-value text-primary">{{ $totalClients ?? $clients->total() }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Active:</span>
                <span class="stat-value text-success">{{ $activeClients ?? 0 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Inactive:</span>
                <span class="stat-value text-danger">{{ $inactiveClients ?? 0 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Due:</span>
                <span class="stat-value text-warning">{{ $dueClients ?? 0 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Online:</span>
                <span class="stat-value text-success">{{ $onlineClients ?? 0 }}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Offline:</span>
                <span class="stat-value text-danger">{{ $offlineClients ?? 0 }}</span>
            </div>
        </div>

        <div class="search-form-mobile">
            <div class="mb-2">
                <div class="input-group mb-2">
                    <input type="text" id="resellerMobileQuickSearch" class="form-control" placeholder="Quick search: name, ID, phone, package...">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>

            <div class="mb-2">
                <button class="btn btn-outline-primary btn-sm btn-block" type="button" data-toggle="collapse" data-target="#resellerMobileAdvancedFilters">
                    <i class="fas fa-filter mr-1"></i>Advanced Filters
                    @php
                        $mobileFilterCount = collect(['zone_id','area_id','package_id','connection_type','connection_medium','mikrotik_device_id','billing_status','payment_status','client_type','due_filter','billing_date','mac_filter','free_filter','onu_filter','expire_from','expire_to'])->filter(fn($f) => request($f))->count();
                    @endphp
                    @if($mobileFilterCount > 0)
                    <span class="badge badge-light ml-1">{{ $mobileFilterCount }}</span>
                    @endif
                </button>
            </div>

            <div class="collapse {{ $mobileFilterCount > 0 ? 'show' : '' }}" id="resellerMobileAdvancedFilters">
                <form method="GET" action="{{ route('reseller.clients') }}" class="mb-2" style="background: #f0f4f8; padding: 10px; border-radius: 8px; border: 1px solid #d2dae2;">
                    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                    @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                    <div class="row mb-2">
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">MIKROTIK</label>
                            <select name="mikrotik_device_id" class="form-control form-control-sm">
                                <option value="">All Routers</option>
                                @foreach($mikrotikDevices ?? [] as $device)
                                <option value="{{ $device->id }}" {{ request('mikrotik_device_id') == $device->id ? 'selected' : '' }}>{{ $device->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">PROTOCOL</label>
                            <select name="connection_type" class="form-control form-control-sm">
                                <option value="">All Types</option>
                                <option value="pppoe" {{ request('connection_type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="hotspot" {{ request('connection_type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                                <option value="static" {{ request('connection_type') == 'static' ? 'selected' : '' }}>Static</option>
                                <option value="dhcp" {{ request('connection_type') == 'dhcp' ? 'selected' : '' }}>DHCP</option>
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">PACKAGE</label>
                            <select name="package_id" class="form-control form-control-sm">
                                <option value="">All</option>
                                @foreach($packages ?? [] as $package)
                                <option value="{{ $package->id }}" {{ request('package_id') == $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">ZONE</label>
                            <select name="zone_id" class="form-control form-control-sm" onchange="loadResellerMobileFilterAreas(this.value)">
                                <option value="">All Zones</option>
                                @foreach($zones ?? [] as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">SUB ZONE</label>
                            <select name="area_id" id="resellerMobileAreaSelect" class="form-control form-control-sm">
                                <option value="">All Areas</option>
                                @if(request('zone_id') && isset($areas))
                                    @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">BILL DATE</label>
                            <select name="billing_date" class="form-control form-control-sm">
                                <option value="">All</option>
                                @for($d = 1; $d <= 28; $d++)
                                <option value="{{ $d }}" {{ request('billing_date') == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">BILLING</label>
                            <select name="billing_status" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="generated" {{ request('billing_status') == 'generated' ? 'selected' : '' }}>Generated</option>
                                <option value="not_generated" {{ request('billing_status') == 'not_generated' ? 'selected' : '' }}>Not Gen.</option>
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">PAYMENT</label>
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
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">CABLE</label>
                            <select name="connection_medium" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="fiber" {{ request('connection_medium') == 'fiber' ? 'selected' : '' }}>Fiber</option>
                                <option value="utp" {{ request('connection_medium') == 'utp' ? 'selected' : '' }}>UTP</option>
                                <option value="wireless" {{ request('connection_medium') == 'wireless' ? 'selected' : '' }}>Wireless</option>
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">CLIENT TYPE</label>
                            <select name="client_type" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="regular" {{ request('client_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="corporate" {{ request('client_type') == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                <option value="home" {{ request('client_type') == 'home' ? 'selected' : '' }}>Home</option>
                                <option value="business" {{ request('client_type') == 'business' ? 'selected' : '' }}>Business</option>
                                <option value="vip" {{ request('client_type') == 'vip' ? 'selected' : '' }}>VIP</option>
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">DUE</label>
                            <select name="due_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="with_due" {{ request('due_filter') == 'with_due' ? 'selected' : '' }}>With Due</option>
                                <option value="no_due" {{ request('due_filter') == 'no_due' ? 'selected' : '' }}>No Due</option>
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">FREE</label>
                            <select name="free_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="free" {{ request('free_filter') == 'free' ? 'selected' : '' }}>Free</option>
                                <option value="paid" {{ request('free_filter') == 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">EXPIRE FROM</label>
                            <input type="date" name="expire_from" class="form-control form-control-sm" value="{{ request('expire_from') }}">
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">EXPIRE TO</label>
                            <input type="date" name="expire_to" class="form-control form-control-sm" value="{{ request('expire_to') }}">
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">MAC</label>
                            <select name="mac_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="with_mac" {{ request('mac_filter') == 'with_mac' ? 'selected' : '' }}>Has</option>
                                <option value="no_mac" {{ request('mac_filter') == 'no_mac' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-6 pl-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">ONU SRC</label>
                            <select name="onu_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="company" {{ request('onu_filter') == 'company' ? 'selected' : '' }}>Company</option>
                                <option value="client" {{ request('onu_filter') == 'client' ? 'selected' : '' }}>Client</option>
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">SHOW</label>
                            <select name="per_page" class="form-control form-control-sm">
                                <option value="20" {{ ($perPage ?? 20) == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100</option>
                                <option value="200" {{ ($perPage ?? 20) == 200 ? 'selected' : '' }}>200</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex" style="gap:6px;">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter mr-1"></i>Apply Filters</button>
                        <a href="{{ route('reseller.clients', request('status') ? ['status' => request('status')] : []) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo"></i> Reset</a>
                    </div>
                </form>
            </div>

            @if(($mobileFilterCount ?? 0) > 0)
            <div class="alert alert-info py-2 px-3 mb-2 d-flex align-items-center" style="font-size: 12px;">
                <i class="fas fa-search mr-2"></i>
                <strong>{{ $clients->total() }}</strong>&nbsp;clients found
                <a href="{{ route('reseller.clients', request('status') ? ['status' => request('status')] : []) }}" class="ml-auto btn btn-outline-info btn-sm py-0 px-2" style="font-size: 10px;"><i class="fas fa-times mr-1"></i>Clear</a>
            </div>
            @endif

            <div class="text-center small text-muted mb-2" id="resellerMobileFilterCount">
                {{ $clients->total() }} clients @if($clients->lastPage() > 1) | Page {{ $clients->currentPage() }}/{{ $clients->lastPage() }} @endif
            </div>
        </div>

        <div class="search-form-desktop mb-3">
            <form method="GET" id="resellerFilterForm">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                @php
                    $allFilterKeys = ['zone_id','area_id','package_id','connection_type','connection_medium','mikrotik_device_id','billing_status','payment_status','client_type','billing_date','due_filter','mac_filter','free_filter','onu_filter','expire_from','expire_to','per_page'];
                    $activeFilterCount = collect($allFilterKeys)->filter(fn($f) => request($f))->count();
                @endphp

                <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <input type="text" name="search" class="form-control" placeholder="Name, ID, Phone, Address..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="var f=document.getElementById('resellerDesktopFilterPanel'); f.style.display=f.style.display==='none'?'block':'none';">
                        <i class="fas fa-filter mr-1"></i>Filters
                        @if($activeFilterCount > 0)
                        <span class="badge badge-light ml-1">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                    @if($activeFilterCount > 0)
                    <a href="{{ route('reseller.clients', request('status') ? ['status' => request('status')] : []) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times mr-1"></i>Clear All</a>
                    @endif
                    <span class="text-muted ml-auto small">{{ $clients->total() }} results</span>
                </div>

                <div id="resellerDesktopFilterPanel" style="background: #f0f4f8; padding: 12px; border-radius: 8px; border: 1px solid #d2dae2; display: {{ $activeFilterCount > 0 ? 'block' : 'none' }};">
                    <div class="row mb-2">
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Mikrotik</label>
                            <select name="mikrotik_device_id" class="form-control form-control-sm">
                                <option value="">Select</option>
                                @foreach($mikrotikDevices ?? [] as $device)
                                <option value="{{ $device->id }}" {{ request('mikrotik_device_id') == $device->id ? 'selected' : '' }}>{{ $device->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Protocol</label>
                            <select name="connection_type" class="form-control form-control-sm">
                                <option value="">Select</option>
                                <option value="pppoe" {{ request('connection_type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="hotspot" {{ request('connection_type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                                <option value="static" {{ request('connection_type') == 'static' ? 'selected' : '' }}>Static IP</option>
                                <option value="dhcp" {{ request('connection_type') == 'dhcp' ? 'selected' : '' }}>DHCP</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Package</label>
                            <select name="package_id" class="form-control form-control-sm">
                                <option value="">Select</option>
                                @foreach($packages ?? [] as $package)
                                <option value="{{ $package->id }}" {{ request('package_id') == $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Zone</label>
                            <select name="zone_id" class="form-control form-control-sm" onchange="loadResellerFilterAreas(this.value)">
                                <option value="">Select</option>
                                @foreach($zones ?? [] as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Sub Zone</label>
                            <select name="area_id" class="form-control form-control-sm" id="resellerAreaFilter">
                                <option value="">Select</option>
                                @foreach($areas ?? [] as $area)
                                <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Bill Date</label>
                            <select name="billing_date" class="form-control form-control-sm">
                                <option value="">Select</option>
                                @for($d = 1; $d <= 28; $d++)
                                <option value="{{ $d }}" {{ request('billing_date') == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Billing Status</label>
                            <select name="billing_status" class="form-control form-control-sm">
                                <option value="">Select</option>
                                <option value="generated" {{ request('billing_status') == 'generated' ? 'selected' : '' }}>Generated</option>
                                <option value="not_generated" {{ request('billing_status') == 'not_generated' ? 'selected' : '' }}>Not Generated</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Payment Status</label>
                            <select name="payment_status" class="form-control form-control-sm">
                                <option value="">Select</option>
                                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="advance" {{ request('payment_status') == 'advance' ? 'selected' : '' }}>Advance</option>
                                <option value="overdue" {{ request('payment_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Cable Type</label>
                            <select name="connection_medium" class="form-control form-control-sm">
                                <option value="">Select</option>
                                <option value="fiber" {{ request('connection_medium') == 'fiber' ? 'selected' : '' }}>Fiber</option>
                                <option value="utp" {{ request('connection_medium') == 'utp' ? 'selected' : '' }}>UTP</option>
                                <option value="wireless" {{ request('connection_medium') == 'wireless' ? 'selected' : '' }}>Wireless</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Client Type</label>
                            <select name="client_type" class="form-control form-control-sm">
                                <option value="">Select</option>
                                <option value="regular" {{ request('client_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="corporate" {{ request('client_type') == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                <option value="home" {{ request('client_type') == 'home' ? 'selected' : '' }}>Home</option>
                                <option value="business" {{ request('client_type') == 'business' ? 'selected' : '' }}>Business</option>
                                <option value="vip" {{ request('client_type') == 'vip' ? 'selected' : '' }}>VIP</option>
                            </select>
                        </div>
                        <div class="col-md-1 col-sm-3 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Due</label>
                            <select name="due_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="with_due" {{ request('due_filter') == 'with_due' ? 'selected' : '' }}>With Due</option>
                                <option value="no_due" {{ request('due_filter') == 'no_due' ? 'selected' : '' }}>No Due</option>
                            </select>
                        </div>
                        <div class="col-md-1 col-sm-3 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Free</label>
                            <select name="free_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="free" {{ request('free_filter') == 'free' ? 'selected' : '' }}>Free</option>
                                <option value="paid" {{ request('free_filter') == 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Expire From</label>
                            <input type="date" name="expire_from" class="form-control form-control-sm" value="{{ request('expire_from') }}">
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Expire To</label>
                            <input type="date" name="expire_to" class="form-control form-control-sm" value="{{ request('expire_to') }}">
                        </div>
                        <div class="col-md-1 col-sm-3 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">MAC</label>
                            <select name="mac_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="with_mac" {{ request('mac_filter') == 'with_mac' ? 'selected' : '' }}>Has</option>
                                <option value="no_mac" {{ request('mac_filter') == 'no_mac' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">ONU Source</label>
                            <select name="onu_filter" class="form-control form-control-sm">
                                <option value="">All</option>
                                <option value="company" {{ request('onu_filter') == 'company' ? 'selected' : '' }}>Company ONU</option>
                                <option value="client" {{ request('onu_filter') == 'client' ? 'selected' : '' }}>Client ONU</option>
                            </select>
                        </div>
                        <div class="col-md-1 col-sm-3 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Show</label>
                            <select name="per_page" class="form-control form-control-sm">
                                <option value="20" {{ ($perPage ?? 20) == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100</option>
                                <option value="200" {{ ($perPage ?? 20) == 200 ? 'selected' : '' }}>200</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end" style="gap: 6px;">
                        <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-filter mr-1"></i>Apply</button>
                        <a href="{{ route('reseller.clients', request('status') ? ['status' => request('status')] : []) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo mr-1"></i>Reset</a>
                    </div>
                </div>
            </form>
            @if(($activeFilterCount ?? 0) > 0)
            <div class="alert alert-info py-2 px-3 mb-2 mt-2 d-flex align-items-center" style="font-size: 13px;">
                <i class="fas fa-search mr-2"></i>
                <strong>{{ $filteredCount ?? $clients->total() }}</strong>&nbsp;clients found
                <a href="{{ route('reseller.clients', request('status') ? ['status' => request('status')] : []) }}" class="ml-auto btn btn-outline-info btn-sm py-0 px-2" style="font-size: 11px;"><i class="fas fa-times mr-1"></i>Clear Filters</a>
            </div>
            @endif
        </div>

        <div class="mobile-cards" id="resellerMobileClientCards">
            @forelse($clients as $client)
            @php $effectiveDue = (float)($client->due_amount ?? 0); @endphp
            <div class="client-card" data-status="{{ $client->status }}" data-due="{{ $effectiveDue > 0 ? 'with_due' : 'no_due' }}" data-search="{{ strtolower($client->name.' '.$client->client_id.' '.($client->username ?? '').' '.$client->phone.' '.($client->package->name ?? '').' '.$client->address.' '.($client->zone->name ?? '').' '.($client->area->name ?? '')) }}">
                <div class="client-header">
                    <div>
                        @php
                            $__aiDay = min($client->payment_deadline ?? 15, 28);
                            $__nowD = \Carbon\Carbon::now('Asia/Dhaka')->startOfDay();
                            $__billingDt = $__nowD->copy()->setDay($__aiDay);
                            if ($__billingDt->lt($__nowD)) { $__billingDt->addMonthNoOverflow(); }
                            $__expDate___daysLeftRM = $client->temp_deadline_date
                                ? \Carbon\Carbon::parse($client->temp_deadline_date)->startOfDay()
                                : $__billingDt;
                            $__daysLeftRM = $__expDate___daysLeftRM ? (int) now()->startOfDay()->diffInDays($__expDate___daysLeftRM, false) : null;
                        @endphp
                        <div class="client-name">{{ $client->name }}@if($client->is_free) <span class="badge badge-info" style="font-size:10px;">FREE</span>@endif</div>
                        @if($__daysLeftRM !== null && $__daysLeftRM <= 3 && $client->status === 'active')
                            @if($__daysLeftRM <= 0)
                                <span class="expiry-badge exp-today">EXPIRED</span>
                            @elseif($__daysLeftRM == 1)
                                <span class="expiry-badge exp-warning">EXP TOMORROW</span>
                            @else
                                <span class="expiry-badge exp-soon">EXP {{ $__daysLeftRM }}d</span>
                            @endif
                        @endif
                        <div class="client-id">{{ $client->username ?? $client->client_id }} | {{ $client->phone }}</div>
                    </div>
                    <div class="text-right">
                        @if($client->status == 'active')
                            <span class="badge badge-success status-badge">Active</span>
                        @elseif($client->status == 'inactive')
                            <span class="badge badge-danger status-badge">Inactive</span>
                        @else
                            <span class="badge badge-secondary status-badge">{{ ucfirst($client->status) }}</span>
                        @endif
                        <br>
                        @if($client->connection_status == 'online')
                            <span class="badge badge-primary" style="font-size:10px;">ONLINE</span>
                        @else
                            <span class="badge badge-secondary" style="font-size:10px;">OFFLINE</span>
                        @endif
                    </div>
                </div>
                <div class="client-info">
                    <strong>Zone:</strong> {{ $client->zone->name ?? '-' }}{{ $client->area ? ' / '.$client->area->name : '' }}{{ $client->box ? ' / TJ: '.$client->box->name : '' }} | 
                    <strong>Package:</strong> {{ $client->package->name ?? '-' }}{{ $client->package && $client->package->bandwidth ? ' ('.$client->package->bandwidth.')' : '' }}<br>
                    @php $mRDays = $client->expire_date ? max(0, (int)\Carbon\Carbon::now()->startOfDay()->diffInDays($client->expire_date->startOfDay(), false)) : '-'; @endphp
                    <strong>Monthly:</strong> ৳{{ number_format($client->monthly_fee ?? 0, 0) }} | 
                    <strong>R.Days:</strong> <span class="{{ is_numeric($mRDays) && $mRDays <= 3 ? 'text-danger font-weight-bold' : (is_numeric($mRDays) && $mRDays <= 7 ? 'text-warning font-weight-bold' : 'text-success') }}">{{ $mRDays }}</span> |
                    <strong>Due:</strong> <span class="{{ $effectiveDue > 0 ? 'text-danger' : 'text-success' }}">৳{{ number_format($effectiveDue ?? 0, 0) }}</span><br>
                    <strong>Billing:</strong> {{ $client->billing_date ?? 1 }} | <strong>Auto-Inactive:</strong> {{ $client->payment_deadline ?? 15 }} of month
                    @if($client->expire_date) | <strong>Conn. Expiry:</strong> <span class="{{ $client->expire_date->isPast() ? 'text-danger' : 'text-warning' }}">{{ $client->expire_date->format('d-M-Y') }}</span>@endif
                    @if($client->ip_address)<br><strong>IP:</strong> {{ $client->ip_address }}@endif
                </div>
                <div class="client-actions">
                    @if($client->status === 'deleted')
                    @if(isset($resObj) && $resObj->hasFeature('client_delete_restore'))
                    <form action="{{ route('reseller.clients.restore', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this client? All info will be restored and MikroTik secret will be re-created.')">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-undo mr-1"></i> Restore</button>
                    </form>
                    @endif
                    @else
                    <a href="{{ route('reseller.clients.show', $client) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> View</a>
                    <a href="{{ route('reseller.clients.edit', $client) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#rechargeModal{{ $client->id }}">
                        <i class="fas fa-bolt"></i> Recharge
                    </button>
                    @if($effectiveDue > 0)
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#paymentModal{{ $client->id }}">
                        <i class="fas fa-money-bill"></i> Pay
                    </button>
                    @endif
                    @if($client->status == 'active')
                    <form action="{{ route('reseller.clients.disable', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Disable?')">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-ban"></i></button>
                    </form>
                    @else
                    <form action="{{ route('reseller.clients.enable', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Enable?')">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check-circle"></i></button>
                    </form>
                    @endif
@if($client->recharge_blocked)
                    <span class="badge badge-warning" title="Recharge blocked by admin" style="font-size:11px;padding:4px 7px;">
                        <i class="fas fa-ban"></i> Recharge Blocked
                    </span>
                    @endif
                    @if(isset($resObj) && $resObj->hasFeature('client_delete_restore'))
                    <form action="{{ route('reseller.clients.delete', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this client?\n\nMikroTik secret will be removed.\nYou can restore from the Deleted tab with full information.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                    @endif
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No clients found</div>
            @endforelse
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm" id="clientsTable">
                <thead>
                    <tr>
                        <th style="width:35px;text-align:center;"><input type="checkbox" id="selectAllClients" title="Select All"></th>
                        <th>ComID</th>
                        <th>ID/Name/Cell</th>
                        <th>Zone/Address</th>
                        <th>Package</th>
                        <th class="text-center">R.Days</th>
                        <th>Due</th>
                        <th style="white-space:nowrap;">Dates</th>
                        <th>Online Info</th>
                        <th>Status</th>
                        <th>MikroTik</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    @php $effectiveDue = (float)($client->due_amount ?? 0); @endphp
                    <tr data-search="{{ strtolower($client->name.' '.($client->client_id ?? '').' '.($client->username ?? '').' '.($client->phone ?? '').' '.($client->address ?? '').' '.($client->package->name ?? '').' '.($client->zone->name ?? '').' '.($client->area->name ?? '').' '.($client->mac_address ?? '').' '.($client->ip_address ?? '')) }}">
                        <td class="text-center">
                            @if($client->status !== 'deleted')
                            <input type="checkbox" class="client-select-cb" value="{{ $client->id }}" data-name="{{ $client->name }}" data-username="{{ $client->username ?? '-' }}" data-package="{{ $client->package->name ?? 'N/A' }}" data-price="{{ $client->package->price ?? $client->monthly_fee ?? 0 }}" data-packageid="{{ $client->package_id ?? '' }}">
                            @endif
                        </td>
                        <td>{{ $client->client_id ?? $client->id }}</td>
                        <td class="pop-trigger" data-pop-target="pop-usr-{{ $client->id }}" style="cursor:pointer;" title="Click for details">
                                                    @php
                            $__aiDay = min($client->payment_deadline ?? 15, 28);
                            $__nowD = \Carbon\Carbon::now('Asia/Dhaka')->startOfDay();
                            $__billingDt = $__nowD->copy()->setDay($__aiDay);
                            if ($__billingDt->lt($__nowD)) { $__billingDt->addMonthNoOverflow(); }
                            $__expDate___daysLeftRD = $client->temp_deadline_date
                                ? \Carbon\Carbon::parse($client->temp_deadline_date)->startOfDay()
                                : $__billingDt;
                            $__daysLeftRD = $__expDate___daysLeftRD ? (int) now()->startOfDay()->diffInDays($__expDate___daysLeftRD, false) : null;
                        @endphp
                            <strong>{{ $client->username ?? '-' }}</strong>@if($client->is_free) <span class="badge badge-info" style="font-size:11px;">FREE</span>@endif
                        @if($__daysLeftRD !== null && $__daysLeftRD <= 3 && $client->status === 'active')
                            @if($__daysLeftRD <= 0)
                                <span class="expiry-badge exp-today">EXPIRED</span>
                            @elseif($__daysLeftRD == 1)
                                <span class="expiry-badge exp-warning">EXP TOMORROW</span>
                            @else
                                <span class="expiry-badge exp-soon">EXP {{ $__daysLeftRD }}d</span>
                            @endif
                        @endif
                            <br>{{ $client->name }}
                            <br><span class="text-primary">{{ $client->phone ?? '' }}</span>
                            <div id="pop-usr-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-user mr-1 text-info"></i><strong>{{ $client->username ?? $client->name }}</strong></div>
                                <div class="text-muted mt-1"><i class="fas fa-phone mr-1 text-success"></i>{{ $client->phone ?? '-' }}</div>
                                @if($client->email)<div class="text-muted mt-1"><i class="fas fa-envelope mr-1"></i>{{ $client->email }}</div>@endif
                                @if($client->nid)<div class="text-muted mt-1"><i class="fas fa-id-card mr-1"></i>NID: {{ $client->nid }}</div>@endif
                            </div>
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-addr-{{ $client->id }}" style="cursor:pointer;" title="Click for address">
                            <strong class="text-primary">{{ $client->zone->name ?? '-' }}</strong>
                            @if($client->area)
                            <br><span class="text-muted" style="font-size:11px;">Sub: {{ $client->area->name }}</span>
                            @endif
                            <div id="pop-addr-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-map-marker-alt mr-1 text-primary"></i><strong>{{ $client->zone->name ?? '-' }}</strong></div>
                                @if($client->area)<div class="text-muted mt-1"><i class="fas fa-layer-group mr-1 text-secondary" style="font-size:11px;"></i>Sub: {{ $client->area->name }}</div>@endif
                                @if($client->box)<div class="text-muted"><i class="fas fa-project-diagram mr-1 text-secondary" style="font-size:11px;"></i>TJ: {{ $client->box->name }}</div>@endif
                                @if($client->address)<div class="mt-1">{{ $client->address }}</div>@endif
                            </div>
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-pkg-{{ $client->id }}" style="cursor:pointer;" title="Click for package details">
                            {{ $client->package->name ?? '-' }}
                            @if($client->package && $client->package->bandwidth)
                            <br><span class="text-info"><i class="fas fa-tachometer-alt"></i> {{ $client->package->bandwidth }}</span>
                            @endif
                            @php $rMkProfile = $client->mikrotik_profile_name ?: ($client->package->mikrotik_profile ?? null); @endphp
                            <div id="pop-pkg-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-box mr-1 text-info"></i><strong>{{ $client->package->name ?? '-' }}</strong></div>
                                @if($client->package && $client->package->bandwidth)<div class="text-info mt-1"><i class="fas fa-tachometer-alt mr-1"></i>{{ $client->package->bandwidth }}</div>@endif
                                @if($rMkProfile)<div class="mt-1"><i class="fas fa-network-wired mr-1 text-muted"></i>Profile: <strong>{{ $rMkProfile }}</strong></div>@endif
                                @if($client->monthly_fee)<div class="mt-1"><span style="font-weight:700;font-size:11px;color:#1b5e20;"><i class="fas fa-tag mr-1"></i>৳{{ number_format($client->monthly_fee, 0) }}/mo</span></div>@endif
                            </div>
                        </td>
                        <td class="text-center">
                            @php
                                $rDays = $client->expire_date ? max(0, (int)\Carbon\Carbon::now()->startOfDay()->diffInDays($client->expire_date->startOfDay(), false)) : '-';
                            @endphp
                            @if($rDays === '-')
                                <span class="text-muted">-</span>
                            @elseif($rDays <= 3)
                                <span class="badge badge-danger" style="font-size:12px;">{{ $rDays }}</span>
                            @elseif($rDays <= 7)
                                <span class="badge badge-warning" style="font-size:12px;">{{ $rDays }}</span>
                            @else
                                <span class="badge badge-success" style="font-size:12px;">{{ $rDays }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <strong class="{{ $effectiveDue > 0 ? 'text-danger' : 'text-success' }}">৳{{ number_format($effectiveDue ?? 0, 0) }}</strong>
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-dates-{{ $client->id }}" style="cursor:pointer;white-space:nowrap;font-size:12px;" title="Click for all dates">
                            @php
                                $listAiDay = min($client->payment_deadline ?? 15, 28);
                                $listNow = \Carbon\Carbon::now('Asia/Dhaka');
                                $listAiDate = $listNow->day <= $listAiDay
                                    ? \Carbon\Carbon::create($listNow->year, $listNow->month, $listAiDay, 0, 0, 0, 'Asia/Dhaka')
                                    : \Carbon\Carbon::create($listNow->year, $listNow->month, $listAiDay, 0, 0, 0, 'Asia/Dhaka')->addMonthNoOverflow();
                                $listAiShow = $client->temp_deadline_date
                                    ? \Carbon\Carbon::parse($client->temp_deadline_date, 'Asia/Dhaka')
                                    : $listAiDate;
                            @endphp
                            <span class="text-muted" style="font-size:11px;">Auto-Inactive:</span><br><span class="text-warning font-weight-bold">{{ $listAiShow->format('d-M-Y') }}</span>
                            @if($client->temp_deadline_date)
                            <br><span class="text-info small" style="white-space:nowrap;"><i class="fas fa-clock mr-1"></i>Extended</span>
                            @endif
                            <div id="pop-dates-{{ $client->id }}" class="d-none" style="min-width:260px;">
                                <div><i class="fas fa-calendar mr-1 text-muted"></i>Billing Day: <strong>{{ $client->billing_date ?? 1 }}</strong></div>
                                <div class="mt-1"><i class="fas fa-ban mr-1 text-warning"></i>Auto-Inactive: <strong class="text-warning">{{ $listAiShow->format('d-M-Y') }}</strong> <small class="text-muted">(day {{ $client->payment_deadline ?? 15 }})</small></div>
                                @if($client->expire_date)<div class="mt-1"><i class="fas fa-hourglass-end mr-1 text-muted"></i>Expiry: <strong class="{{ $client->expire_date->isPast() ? 'text-danger' : 'text-muted' }}">{{ $client->expire_date->format('d-M-Y') }}</strong></div>@endif
                                @if($client->temp_deadline_date)<div class="mt-1"><i class="fas fa-clock mr-1 text-info"></i>Extended: <strong class="text-info">{{ \Carbon\Carbon::parse($client->temp_deadline_date)->format('d-M-Y') }}</strong></div>@endif
                                @if($client->connection_date)<div class="mt-1"><i class="fas fa-plug mr-1 text-success"></i>Joined: <strong>{{ \Carbon\Carbon::parse($client->connection_date)->format('d-M-Y') }}</strong></div>@endif
                            </div>
                        </td>
                        <td>
                            @php
                                $displayIp = $client->session_ip ?: $client->ip_address;
                                $displayMac = $client->session_mac ?: $client->mac_address;
                            @endphp
                            @if($displayIp)
                            <strong>{{ $displayIp }}</strong>
                            @endif
                            @if($displayMac)
                            <br><span class="text-muted" style="font-size:11px;">{{ $displayMac }}</span>
                            @endif
                            @if($client->connection_status == 'online' && $client->session_uptime)
                            @php
                                $rUptimeStr = $client->session_uptime;
                                $rSecs = 0;
                                if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $rUptimeStr, $hms)) {
                                    $rSecs = $hms[1]*3600 + $hms[2]*60 + $hms[3];
                                } else {
                                    if(preg_match('/(\d+)w/', $rUptimeStr, $m)) $rSecs += $m[1]*604800;
                                    if(preg_match('/(\d+)d/', $rUptimeStr, $m)) $rSecs += $m[1]*86400;
                                    if(preg_match('/(\d+)h/', $rUptimeStr, $m)) $rSecs += $m[1]*3600;
                                    if(preg_match('/(\d+)m/', $rUptimeStr, $m)) $rSecs += $m[1]*60;
                                    if(preg_match('/(\d+)s/', $rUptimeStr, $m)) $rSecs += $m[1];
                                }
                                if ($client->last_online) {
                                    $rSecs += abs((int) \Carbon\Carbon::parse($client->last_online)->diffInSeconds(now()));
                                }
                            @endphp
                            <br><span style="color:#00c853;font-size:11px;"><i class="fas fa-clock mr-1" style="font-size:10px;"></i><strong class="live-uptime" data-uptime-seconds="{{ $rSecs }}"></strong></span>
                            @elseif($client->last_online)
                            <br><span style="color:#999;font-size:11px;"><i class="fas fa-clock mr-1" style="font-size:10px;"></i>Last seen: {{ \Carbon\Carbon::parse($client->last_online)->setTimezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span>
                            @endif
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
                            <div style="margin-top: 4px;">
                            @if($client->connection_status == 'online')
                                <span class="badge status-badge" style="border: 1.5px solid #007bff; color: #007bff; background: transparent; font-weight: 600; padding: 4px 12px;">ONLINE</span>
                            @else
                                <span class="badge status-badge" style="border: 1.5px solid #dc3545; color: #dc3545; background: transparent; font-weight: 600; padding: 4px 12px;">OFFLINE</span>
                            @endif
                            </div>
                        </td>
                        <td class="text-center">
                            @php
                                $rMkProfile = $client->mikrotik_profile_name ?: ($client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null);
                                $rIsMkActive = $client->status === 'active' && $rMkProfile && strtolower($rMkProfile) !== 'inactive';
                            @endphp
                            <label class="mk-toggle mb-0" title="{{ $rIsMkActive ? 'ON: '.$rMkProfile : 'OFF' }}">
                                <input type="checkbox" {{ $rIsMkActive ? 'checked' : '' }} disabled>
                                <span class="mk-slider"></span>
                            </label>
                            <span class="{{ $rIsMkActive ? 'text-success' : 'text-danger' }}" style="font-size:13px;font-weight:bold;display:inline-block;vertical-align:middle;margin-left:3px;">{{ $rIsMkActive ? 'ON' : 'OFF' }}</span>
                            @if($client->mikrotikDevice)
                            <br><small class="text-muted">{{ $client->mikrotikDevice->name }}</small>
                            @if($client->mikrotikDevice->model ?? false)
                            <br><small class="text-muted" style="font-size:10px;">{{ $client->mikrotikDevice->model }}</small>
                            @endif
                            @endif
                        </td>
                        <td class="action-btns">
                            @if($client->status === 'deleted')
                            @if(isset($resObj) && $resObj->hasFeature('client_delete_restore'))
                            <div class="btn-group">
                                <form action="{{ route('reseller.clients.restore', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this client? All info will be restored and MikroTik secret will be re-created.')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" title="Restore Client"><i class="fas fa-undo"></i></button>
                                </form>
                            </div>
                            @endif
                            @else
                            <div class="btn-group">
                                <a href="{{ route('reseller.clients.show', $client) }}" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('reseller.clients.edit', $client) }}" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-primary btn-sm" title="Recharge" data-toggle="modal" data-target="#rechargeModal{{ $client->id }}">
                                    <i class="fas fa-bolt"></i>
                                </button>
                                @if($effectiveDue > 0)
                                <button type="button" class="btn btn-success btn-sm" title="Collect Payment" data-toggle="modal" data-target="#paymentModal{{ $client->id }}">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                                @endif
                                @if($client->status == 'active')
                                <form action="{{ route('reseller.clients.disable', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Disable this client? MikroTik profile will switch to Inactive.')">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Disable"><i class="fas fa-ban"></i></button>
                                </form>
                                @else
                                <form action="{{ route('reseller.clients.enable', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Enable this client?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" title="Enable"><i class="fas fa-check-circle"></i></button>
                                </form>
                                @endif
                                @if(isset($resObj) && $resObj->hasFeature('client_delete_restore'))
                                <form action="{{ route('reseller.clients.delete', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this client?\n\nMikroTik secret will be removed.\nYou can restore from the Deleted tab with full information.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Client"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="12" class="text-center">No clients found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
            <div class="mb-2">
                Showing {{ $clients->firstItem() ?? 0 }}-{{ $clients->lastItem() ?? 0 }} of {{ $clients->total() }}
            </div>
            {{ $clients->withQueryString()->links('vendor.pagination.bootstrap-4') }}
        </div>
    </div>
</div>

@foreach($clients as $client)
@php $effectiveDue = (float)($client->due_amount ?? 0); @endphp
@if($effectiveDue > 0)
<div class="modal fade" id="paymentModal{{ $client->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reseller.collect-payment', $client) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Collect Payment</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Client:</strong> {{ $client->name }}<br>
                        <strong>ID:</strong> {{ $client->client_id }}<br>
                        <strong>Net Payable:</strong> <span class="text-danger font-weight-bold">৳{{ number_format($effectiveDue ?? 0, 2) }}</span>
                        @if($client->getAvailableAdvance() > 0)
                        <br><small class="text-success">Advance: ৳{{ number_format($client->getAvailableAdvance(), 2) }} (already offset)</small>
                        @endif
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-money-bill mr-1"></i> Amount to Collect</label>
                        <input type="number" name="amount" class="form-control form-control-lg reseller-amount-input" value="{{ $effectiveDue }}" min="1" required
                               data-due="{{ $effectiveDue }}" data-client="{{ $client->id }}"
                               oninput="checkResellerClientAmountDiff(this, {{ $client->id }})" onchange="checkResellerClientAmountDiff(this, {{ $client->id }})">
                        <small class="text-warning reseller-amount-warning-{{ $client->id }}" style="display: none;"><i class="fas fa-exclamation-triangle mr-1"></i>Amount differs from due - Reason required!</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-credit-card mr-1"></i> Payment Method</label>
                        <select name="payment_method_id" class="form-control payment-method-select" required>
                            @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}" data-name="{{ strtolower($pm->name) }}">{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- MFS Fields (bKash, Nagad, Rocket, etc.) --}}
                    <div class="mfs-fields" style="display: none;">
                        <div class="form-group">
                            <label><i class="fas fa-mobile-alt mr-1"></i> <span class="mfs-label">bKash</span> Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile_payment_number" class="form-control mfs-number-input" placeholder="Enter mobile payment number" maxlength="15">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-receipt mr-1"></i> Transaction ID <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_id" class="form-control mfs-trx-input" placeholder="Enter transaction ID">
                        </div>
                    </div>
                    {{-- Non-MFS Transaction ID --}}
                    <div class="nonmfs-fields">
                        <div class="form-group nonmfs-trx-row">
                            <label><i class="fas fa-receipt mr-1"></i> Transaction ID <small class="text-muted">(Optional)</small></label>
                            <input type="text" name="transaction_id" class="form-control nonmfs-trx-input" placeholder="Optional transaction ID">
                        </div>
                        <div class="form-group cash-receipt-field" style="display: none;">
                            <label><i class="fas fa-file-alt mr-1"></i> Money Receipt No.</label>
                            <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                        </div>
                    </div>
                    <div class="form-group reason-group-reseller-{{ $client->id }}" style="display: none;">
                        <label><i class="fas fa-comment-alt mr-1"></i> Reason <span class="text-danger">*</span></label>
                        <input type="text" name="notes_reason" class="form-control reason-input-reseller-{{ $client->id }}" placeholder="Why is the amount different from due?">
                        <small class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i> Required when amount differs from due</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-comment mr-1"></i> Notes <small class="text-muted">(Optional)</small></label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" class="custom-control-input" id="sendSms{{ $client->id }}" name="send_sms" value="1" checked>
                        <label class="custom-control-label" for="sendSms{{ $client->id }}"><i class="fas fa-sms mr-1"></i> Send SMS notification</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i> Collect Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

@php
    $reseller = \App\Models\Reseller::where('user_id', Auth::id())->first();
@endphp

@php
    $resellerPackagesMap = \App\Models\ResellerPackage::where("reseller_id", $reseller->id)
        ->where("is_active", true)
        ->with("package")
        ->get()
        ->keyBy("package_id");
@endphp
@foreach($clients as $client)
@php
$rSinglePkg = $resellerPackagesMap->get($client->package_id);
$rSinglePrice = $rSinglePkg ? (float)$rSinglePkg->reseller_price : (float)($client->package->price ?? $client->monthly_fee ?? 0);
@endphp
<div class="modal fade" id="rechargeModal{{ $client->id }}" tabindex="-1" data-reseller-price="{{ $rSinglePrice }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reseller.recharge-client') }}" method="POST">
                @csrf
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title"><i class="fas fa-bolt mr-2"></i>Recharge Client</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Client</small><br>
                                <strong>{{ $client->name }}</strong><br>
                                <small>{{ $client->username ?? '-' }}</small>
                            </div>
                            <div class="col-6 text-right">
                                <small class="text-muted">Package</small><br>
                                <strong>{{ $client->package->name ?? 'N/A' }}</strong><br>
                                <small>৳{{ number_format($client->package->price ?? $client->monthly_fee ?? 0) }}/month</small>
                            </div>
                        </div>
                        @if($reseller && $reseller->billing_type === 'prepaid')
                        <hr class="my-2">
                        <div class="text-center">
                            <small class="text-muted">Wallet Balance:</small>
                            <strong class="{{ $reseller->wallet_balance > 0 ? 'text-success' : 'text-danger' }}">৳{{ number_format($reseller->wallet_balance, 2) }}</strong>
                        </div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Days</label>
                                <input type="number" name="days" class="form-control" placeholder="Auto ({{ $reseller->minimum_recharge_days ?? 30 }})" min="1" max="365">
                                <small class="text-muted">Leave empty for default</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">Per Day Charge</label>
                                <input type="text" class="form-control single-per-day-charge" readonly style="background:#f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">Creditable Amount</label>
                                <input type="text" class="form-control single-creditable-amount" readonly style="background:#f8f9fa;">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">Selected Clients</label>
                                <input type="text" class="form-control" value="1" readonly style="background:#f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">Total Creditable Amount</label>
                                <input type="text" class="form-control font-weight-bold text-success single-total-creditable" readonly style="background:#f8f9fa; font-size:16px;">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Remarks <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional note...">
                    </div>
                    @if($reseller && $reseller->billing_type === 'postpaid')
                    <div class="alert alert-warning py-2 mb-0">
                        <small><i class="fas fa-info-circle mr-1"></i>Postpaid: This recharge amount will be added to your monthly dues.</small>
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-bolt mr-1"></i>Recharge Now</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<div id="bulkActionBar" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:1050; background:linear-gradient(135deg,#1e3c72,#2a5298); color:#fff; padding:12px 24px; box-shadow:0 -4px 20px rgba(0,0,0,0.3); border-top:3px solid #ffc107;">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <i class="fas fa-check-square mr-2"></i>
            <strong id="bulkSelectedCount">0</strong> client(s) selected
        </div>
        <div>
            <button type="button" class="btn btn-warning btn-sm mr-2" id="bulkRechargeBtn" data-toggle="modal" data-target="#bulkRechargeModal">
                <i class="fas fa-bolt mr-1"></i>Bulk Recharge
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="bulkClearBtn">
                <i class="fas fa-times mr-1"></i>Clear Selection
            </button>
        </div>
    </div>
</div>

@php
    $resellerPackagesMap = \App\Models\ResellerPackage::where('reseller_id', $reseller->id)
        ->where('is_active', true)
        ->with('package')
        ->get()
        ->keyBy('package_id');
@endphp
<div class="modal fade" id="bulkRechargeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('reseller.bulk-recharge-clients') }}" method="POST" id="bulkRechargeForm">
                @csrf
                <div id="bulkRechargeClientInputs"></div>
                <div class="modal-header" style="background:linear-gradient(135deg,#1e3c72,#2a5298); color:#fff; padding:10px 20px;">
                    <h5 class="modal-title"><i class="fas fa-bolt mr-2"></i>Client Validity Extend/Renewal <span id="bulkRechargeCount" class="badge badge-warning ml-2">0</span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Selected Clients</small>
                                <div id="bulkRechargeClientList" style="max-height:120px; overflow-y:auto; font-size:13px;" class="mt-1"></div>
                            </div>
                            <div class="col-6 text-right">
                                @if($reseller && $reseller->billing_type === 'prepaid')
                                <small class="text-muted">Wallet Balance</small><br>
                                <strong class="{{ $reseller->wallet_balance > 0 ? 'text-success' : 'text-danger' }}" style="font-size:18px;">৳{{ number_format($reseller->wallet_balance, 2) }}</strong>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold text-uppercase" style="font-size:12px;">Package ({{ $reseller->company->name ?? 'Reseller' }}) <span class="text-danger">*</span></label>
                        <select class="form-control" id="bulkRechargePackage" disabled>
                            <option value="">-- Mixed Packages --</option>
                            @foreach($packages as $pkg)
                            @php $rPkg = $resellerPackagesMap->get($pkg->id); @endphp
                            <option value="{{ $pkg->id }}" data-price="{{ $rPkg ? $rPkg->reseller_price : $pkg->price }}" data-name="{{ $pkg->name }}">{{ $pkg->name }}-{{ number_format($rPkg ? $rPkg->selling_price : $pkg->price) }}TK</option>
                            @endforeach
                        </select>
                        <small class="text-muted" id="bulkPackageNote">Package determined per client</small>
                    </div>

                    <div id="bulkPackageBreakdown" style="display:none;" class="mb-3">
                        <label class="font-weight-bold text-uppercase" style="font-size:12px;"><i class="fas fa-layer-group mr-1"></i>Package-wise Breakdown</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
                                <thead style="background:#f1f3f5;">
                                    <tr>
                                        <th>Package</th>
                                        <th class="text-center">Clients</th>
                                        <th class="text-right">Per Day</th>
                                        <th class="text-right">Creditable/Client</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="bulkPackageBreakdownBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">New Renewal Days <span class="text-danger">*</span></label>
                                <input type="number" name="days" class="form-control" id="bulkRechargeDays" placeholder="Auto ({{ $reseller->minimum_recharge_days ?? 30 }})" min="1" max="365" value="{{ $reseller->minimum_recharge_days ?? 30 }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">Per Day Charge</label>
                                <input type="text" class="form-control" id="bulkPerDayCharge" readonly style="background:#f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">Creditable Amount</label>
                                <input type="text" class="form-control" id="bulkCreditableAmount" readonly style="background:#f8f9fa;">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">Selected Clients</label>
                                <input type="text" class="form-control" id="bulkSelectedClientsCount" readonly style="background:#f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-uppercase" style="font-size:12px;">Total Creditable Amount</label>
                                <input type="text" class="form-control font-weight-bold text-success" id="bulkTotalCreditableAmount" readonly style="background:#f8f9fa; font-size:16px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Remarks <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="remarks" class="form-control" placeholder="Bulk recharge note...">
                    </div>
                    @if($reseller && $reseller->billing_type === 'postpaid')
                    <div class="alert alert-warning py-2 mb-0">
                        <small><i class="fas fa-info-circle mr-1"></i>Postpaid: Recharge amounts will be added to your monthly dues.</small>
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4"><i class="fas fa-bolt mr-1"></i>Recharge/Renew</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function checkResellerClientAmountDiff(input, clientId) {
    var due = parseFloat(input.dataset.due) || 0;
    var amount = parseFloat(input.value) || 0;
    var isDifferent = Math.abs(amount - due) > 0.01;
    $('.reseller-amount-warning-' + clientId).toggle(isDifferent);
    var rg = $('.reason-group-reseller-' + clientId);
    var ri = $('.reason-input-reseller-' + clientId);
    rg.toggle(isDifferent);
    ri.prop('required', isDifferent);
    if (!isDifferent) ri.val('');
}

$(document).ready(function() {
    $('#quickSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#clientsTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    function resellerMobileFilter() {
        var search = $('#resellerMobileQuickSearch').val().toLowerCase();
        var visibleCount = 0;
        $('#resellerMobileClientCards .client-card').each(function() {
            var show = true;
            if (search && !String($(this).data('search')).includes(search)) show = false;
            $(this).toggle(show);
            if (show) visibleCount++;
        });
    }
    $('#resellerMobileQuickSearch').on('input', resellerMobileFilter);

    var MFS_KEYWORDS_LIST = ['bkash', 'nagad', 'rocket', 'upay', 'tap', 'mcash', 'mfs', 'mobile'];

    function checkClientPaymentMethod($select) {
        var modal      = $select.closest('.modal');
        var $opt       = $select.find('option:selected');
        var methodName = ($opt.data('name') || $opt.text() || '').toLowerCase();
        var isMfs      = MFS_KEYWORDS_LIST.some(function(k) { return methodName.indexOf(k) !== -1; });
        var isCash     = methodName.indexOf('cash') !== -1;

        if (isMfs) {
            // MFS (bKash/Nagad/Rocket): show mobile number + TrxID, hide others
            modal.find('.mfs-fields').show();
            modal.find('.nonmfs-fields').hide();
            modal.find('.mfs-label').text($opt.text().trim());
            modal.find('.mfs-number-input, .mfs-trx-input').prop('disabled', false).prop('required', true);
            modal.find('.nonmfs-trx-input').prop('disabled', true);
        } else if (isCash) {
            // Cash: show Money Receipt No. only — hide TrxID
            modal.find('.mfs-fields').hide();
            modal.find('.mfs-number-input, .mfs-trx-input').prop('disabled', true).prop('required', false).val('');
            modal.find('.nonmfs-fields').show();
            modal.find('.nonmfs-trx-row').hide();
            modal.find('.nonmfs-trx-input').prop('disabled', true).val('');
            modal.find('.cash-receipt-field').show();
        } else {
            // Bank / Other: show TrxID (optional), no mobile number, no receipt
            modal.find('.mfs-fields').hide();
            modal.find('.mfs-number-input, .mfs-trx-input').prop('disabled', true).prop('required', false).val('');
            modal.find('.nonmfs-fields').show();
            modal.find('.nonmfs-trx-row').show();
            modal.find('.nonmfs-trx-input').prop('disabled', false);
            modal.find('.cash-receipt-field').hide().find('input').val('');
        }
    }

    $(document).on('change', '.payment-method-select', function() {
        checkClientPaymentMethod($(this));
    });

    // Trigger on modal open
    $(document).on('shown.bs.modal', '[id^="paymentModal"]', function() {
        var $select = $(this).find('.payment-method-select');
        if ($select.length) checkClientPaymentMethod($select);
    });
});

function loadResellerFilterAreas(zoneId) {
    var select = document.getElementById('resellerAreaFilter');
    select.innerHTML = '<option value="">Select</option>';
    if (!zoneId) return;
    fetch('/admin/api/areas-by-zone/' + zoneId)
        .then(r => r.json())
        .then(data => {
            data.forEach(a => {
                select.innerHTML += '<option value="'+a.id+'">'+a.name+'</option>';
            });
        });
}

function loadResellerMobileFilterAreas(zoneId) {
    var select = document.getElementById('resellerMobileAreaSelect');
    select.innerHTML = '<option value="">All Areas</option>';
    if (!zoneId) return;
    fetch('/admin/api/areas-by-zone/' + zoneId)
        .then(r => r.json())
        .then(data => {
            data.forEach(a => {
                select.innerHTML += '<option value="'+a.id+'">'+a.name+'</option>';
            });
        });
}

// Bulk selection logic
(function() {
    var $bar = $('#bulkActionBar');
    var $count = $('#bulkSelectedCount');
    var $selectAll = $('#selectAllClients');

    function getSelected() {
        return $('.client-select-cb:checked');
    }

    function updateBar() {
        var sel = getSelected();
        var n = sel.length;
        $count.text(n);
        if (n > 0) {
            $bar.slideDown(200);
        } else {
            $bar.slideUp(200);
        }
        $selectAll.prop('indeterminate', n > 0 && n < $('.client-select-cb').length);
        $selectAll.prop('checked', n > 0 && n === $('.client-select-cb').length);
    }

    $(document).on('change', '.client-select-cb', updateBar);

    $selectAll.on('change', function() {
        var checked = $(this).prop('checked');
        $('.client-select-cb:visible').prop('checked', checked);
        updateBar();
    });

    $('#bulkClearBtn').on('click', function() {
        $('.client-select-cb').prop('checked', false);
        $selectAll.prop('checked', false).prop('indeterminate', false);
        updateBar();
    });

    var resellerPackagePrices = {!! json_encode($resellerPackagesMap->mapWithKeys(function($rp) { return [$rp->package_id => (float)$rp->reseller_price]; })) !!};
    var packagePrices = {!! json_encode($packages->pluck('price', 'id')->map(function($p) { return (float)$p; })) !!};
    var defaultDays = {{ $reseller->minimum_recharge_days ?? 30 }};

    function getBulkPrice(packageId) {
        if (resellerPackagePrices[packageId] !== undefined) return resellerPackagePrices[packageId];
        if (packagePrices[packageId] !== undefined) return packagePrices[packageId];
        return 0;
    }

    var packageNames = {};
    $('#bulkRechargePackage option').each(function() {
        if ($(this).val()) packageNames[$(this).val()] = $(this).data('name') || $(this).text();
    });

    function recalcBulkAmounts() {
        var sel = getSelected();
        var days = parseInt($('#bulkRechargeDays').val()) || defaultDays;
        var totalPerDay = 0;
        var totalCreditable = 0;
        var clientCount = sel.length;
        var pkgGroups = {};

        sel.each(function() {
            var pid = $(this).data('packageid') || 0;
            var price = parseFloat($(this).data('price')) || 0;
            var resellerPrice = getBulkPrice(pid);
            var usePrice = resellerPrice || price;
            var perDay = usePrice / 30;
            totalPerDay += perDay;
            totalCreditable += perDay * days;

            if (!pkgGroups[pid]) {
                pkgGroups[pid] = { count: 0, price: usePrice, perDay: perDay, name: packageNames[pid] || $(this).data('package') || 'Unknown' };
            }
            pkgGroups[pid].count++;
        });

        var pkgKeys = Object.keys(pkgGroups);
        var $pkgSelect = $('#bulkRechargePackage');
        var $pkgNote = $('#bulkPackageNote');
        var $breakdown = $('#bulkPackageBreakdown');
        var $breakdownBody = $('#bulkPackageBreakdownBody');

        if (pkgKeys.length === 1) {
            var g = pkgGroups[pkgKeys[0]];
            $pkgSelect.val(pkgKeys[0]);
            $pkgNote.text('All selected clients have the same package');
            $('#bulkPerDayCharge').val(g.perDay.toFixed(2));
            $('#bulkCreditableAmount').val((g.perDay * days).toFixed(2));
            $breakdown.hide();
        } else if (pkgKeys.length > 1) {
            $pkgSelect.val('');
            $pkgNote.text('Multiple packages selected — see breakdown below');

            $breakdownBody.empty();
            pkgKeys.forEach(function(pid) {
                var g = pkgGroups[pid];
                var credPerClient = g.perDay * days;
                var subtotal = credPerClient * g.count;
                var $tr = $('<tr>');
                $tr.append($('<td>').text(g.name));
                $tr.append($('<td>').addClass('text-center').text(g.count));
                $tr.append($('<td>').addClass('text-right').text(g.perDay.toFixed(2)));
                $tr.append($('<td>').addClass('text-right').text(credPerClient.toFixed(2)));
                $tr.append($('<td>').addClass('text-right font-weight-bold').text(subtotal.toFixed(2)));
                $breakdownBody.append($tr);
            });
            var $totalRow = $('<tr>').css('background', '#e9f7ef');
            $totalRow.append($('<td>').addClass('font-weight-bold').text('Total'));
            $totalRow.append($('<td>').addClass('text-center font-weight-bold').text(clientCount));
            $totalRow.append($('<td>').text(''));
            $totalRow.append($('<td>').text(''));
            $totalRow.append($('<td>').addClass('text-right font-weight-bold text-success').text(totalCreditable.toFixed(2)));
            $breakdownBody.append($totalRow);
            $breakdown.show();

            var avgPerDay = totalPerDay / clientCount;
            var avgCreditable = totalCreditable / clientCount;
            $('#bulkPerDayCharge').val(avgPerDay.toFixed(2) + ' (avg)');
            $('#bulkCreditableAmount').val(avgCreditable.toFixed(2) + ' (avg)');
        } else {
            $('#bulkPerDayCharge').val('0.00');
            $('#bulkCreditableAmount').val('0.00');
            $breakdown.hide();
        }

        $('#bulkSelectedClientsCount').val(clientCount);
        $('#bulkTotalCreditableAmount').val(totalCreditable.toFixed(2));
    }

    $('#bulkRechargeBtn').on('click', function(e) {
        if (getSelected().length === 0) {
            e.stopPropagation();
            smartAlert('Please select at least one client.', 'warning'); return;
            return false;
        }
    });

    $(document).on('show.bs.modal', '#bulkRechargeModal', function() {
        var sel = getSelected();
        var $inputs = $('#bulkRechargeClientInputs');
        var $list = $('#bulkRechargeClientList');
        var $rCount = $('#bulkRechargeCount');
        $inputs.empty();
        $list.empty();
        $rCount.text(sel.length);

        sel.each(function() {
            var id = $(this).val();
            var $hidden = $('<input>').attr({type:'hidden', name:'client_ids[]'}).val(id);
            $inputs.append($hidden);

            var $row = $('<div>').addClass('py-1 border-bottom');
            $row.append($('<strong>').text($(this).data('username')));
            $row.append(document.createTextNode(' — '));
            $row.append(document.createTextNode($(this).data('name') + ' '));
            $row.append($('<small>').addClass('text-muted').text('(' + $(this).data('package') + ')'));
            $list.append($row);
        });

        recalcBulkAmounts();
    });

    $(document).on('input', '#bulkRechargeDays', recalcBulkAmounts);
})();

// Single client recharge modal - calculate amounts
var singleRechargeDefaultDays = {{ $reseller->minimum_recharge_days ?? 30 }};
function updateSingleRechargeAmounts($modal) {
    var resellerPrice = parseFloat($modal.data("reseller-price")) || 0;
    var daysInput = $modal.find('input[name="days"]');
    var days = parseInt(daysInput.val()) || singleRechargeDefaultDays;
    var perDay = resellerPrice / 30;
    var creditable = perDay * days;
    $modal.find(".single-per-day-charge").val(perDay.toFixed(2));
    $modal.find(".single-creditable-amount").val(creditable.toFixed(2));
    $modal.find(".single-total-creditable").val(creditable.toFixed(2));
}
$(document).on("show.bs.modal", "[id^='rechargeModal']", function() {
    updateSingleRechargeAmounts($(this));
});
$(document).on("input change", "[id^='rechargeModal'] input[name='days']", function() {
    updateSingleRechargeAmounts($(this).closest(".modal"));
});

// Live uptime counter
function formatUptime(secs) {
    secs = Math.floor(secs);
    var h = Math.floor(secs/3600), m = Math.floor((secs%3600)/60), s = secs%60;
    return (h>0 ? h+'h' : '') + (h>0||m>0 ? (m<10&&h>0?'0':'')+m+'m' : '') + (s<10&&(h>0||m>0)?'0':'')+s+'s';
}
document.querySelectorAll('.live-uptime[data-uptime-seconds]').forEach(function(el) {
    var secs = parseInt(el.getAttribute('data-uptime-seconds')) || 0;
    el.textContent = formatUptime(secs);
    setInterval(function() { secs++; el.textContent = formatUptime(secs); }, 1000);
});

// ── Detail Popup (click-triggered popover) ─────────────────────────────────
$(document).ready(function () {
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.pop-trigger, .popover').length) {
            $('.pop-trigger').each(function () {
                var $el = $(this);
                if ($el.data('bs.popover')) $el.popover('hide');
            });
        }
    });
    $(document).on('click', '.pop-trigger', function (e) {
        e.stopPropagation();
        var $trigger = $(this);
        var targetId = $trigger.data('pop-target');
        var $contentDiv = $('#' + targetId);
        if (!$contentDiv.length) return;
        $('.pop-trigger').not($trigger).each(function () {
            if ($(this).data('bs.popover')) $(this).popover('hide');
        });
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
@endpush