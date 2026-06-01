@extends('layouts.technician')

@section('title', 'My Clients')
@section('breadcrumb')
<li class="breadcrumb-item active">Clients</li>
@endsection

@push('styles')
<style>
.mobile-quick-search { position: relative; }
.mobile-search-results {
    position: absolute;
    width: 100%;
    max-height: 400px;
    overflow-y: auto;
    background: white;
    z-index: 9999;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    border-radius: 8px;
    margin-top: 5px;
    border: 1px solid #ddd;
}
.search-result-item {
    display: block;
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    color: #333;
    text-decoration: none !important;
}
.search-result-item:hover { background-color: #f0f7ff; color: #007bff; }
.result-avatar {
    width: 40px; height: 40px; background: #1e3c72; color: white;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-weight: bold; font-size: 16px; margin-right: 12px;
}
.result-info { flex: 1; }
.result-name { font-weight: 600; font-size: 14px; margin-bottom: 2px; }
.result-meta { font-size: 11px; color: #666; display: flex; flex-wrap: wrap; gap: 8px; }
.status-badges { display: flex; gap: 4px; margin-top: 4px; }
.badge-xs { padding: 2px 5px; font-size: 10px; }

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
@media (max-width: 575px) {
    .client-list-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 6px !important;
    }
    .filter-buttons {
        width: 100%;
    }
    .filter-buttons .btn {
        font-size: 11px;
        padding: 4px 7px;
    }
}
.table thead th {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
    color: white !important;
    font-weight: 500;
    font-size: 12px;
    white-space: nowrap;
    border-color: #1e3c72 !important;
}
.table td {
    font-size: 13px;
    vertical-align: middle;
}
.action-btns .btn {
    width: 26px;
    height: 26px;
    padding: 0;
    margin: 1px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}
.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
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
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.client-card .client-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}
.client-card .client-name {
    font-weight: 600;
    font-size: 14px;
    color: #333;
}
.client-card .client-id {
    font-size: 11px;
    color: #666;
}
.client-card .client-info {
    font-size: 12px;
    color: #555;
    line-height: 1.6;
}
.client-card .client-info strong {
    color: #333;
}
.client-card .client-actions {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: nowrap;
}
.client-card .client-actions .btn {
    flex: 1 1 0;
    font-size: 12px;
    padding: 7px 6px;
    white-space: nowrap;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
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

/* Detail popup triggers */
td.pop-trigger { cursor: pointer; transition: background .12s; }
td.pop-trigger:hover { background: rgba(0,123,255,0.06) !important; }
.popover { max-width: 320px !important; font-size: 13px !important; }
.popover-body { line-height: 1.9; padding: 10px 14px !important; }
.popover-body div { padding: 1px 0; }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap client-list-header">
            <h3 class="card-title mb-0"><i class="fas fa-users mr-2"></i>My Area Clients</h3>
            <div class="filter-buttons">
                <a href="{{ route('technician.clients') }}" class="btn btn-{{ !request('status') && !request('search') ? 'primary' : 'outline-primary' }} btn-sm">
                    <i class="fas fa-map-marker-alt mr-1"></i>My Zone ({{ $totalClients ?? 0 }})
                </a>
                <a href="{{ route('technician.clients', ['status' => 'active']) }}" class="btn btn-{{ request('status') == 'active' ? 'success' : 'outline-success' }} btn-sm">
                    Active ({{ $activeClients ?? 0 }})
                </a>
                <a href="{{ route('technician.clients', ['status' => 'inactive']) }}" class="btn btn-{{ request('status') == 'inactive' ? 'danger' : 'outline-danger' }} btn-sm">
                    Inactive ({{ $inactiveClients ?? 0 }})
                </a>
                <a href="{{ route('technician.clients', ['status' => 'due']) }}" class="btn btn-{{ request('status') == 'due' ? 'warning' : 'outline-warning' }} btn-sm">
                    Due ({{ $dueClients ?? 0 }})
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="search-form-mobile">
            <div class="mb-2" style="position: relative;">
                <div class="input-group mb-2">
                    <input type="text" id="mobileClientSearch" class="form-control" placeholder="Search all zones: name, ID, phone...">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="mobile-search-results" id="mobileSearchResults" style="display: none;"></div>
            </div>

            <div class="mb-2">
                <button class="btn btn-outline-primary btn-sm btn-block" type="button" data-toggle="collapse" data-target="#techMobileAdvancedFilters">
                    <i class="fas fa-filter mr-1"></i>Advanced Filters
                    @php
                        $mobileFilterCount = collect(['zone_id','area_id','package_id','connection_type','connection_medium','mikrotik_device_id','billing_status','payment_status','client_type','due_filter','billing_date','mac_filter','free_filter','onu_filter','expire_from','expire_to'])->filter(fn($f) => request($f))->count();
                    @endphp
                    @if($mobileFilterCount > 0)
                    <span class="badge badge-light ml-1">{{ $mobileFilterCount }}</span>
                    @endif
                </button>
            </div>

            <div class="collapse {{ $mobileFilterCount > 0 ? 'show' : '' }}" id="techMobileAdvancedFilters">
                <form method="GET" action="{{ route('technician.clients') }}" class="mb-2" style="background: #f0f4f8; padding: 10px; border-radius: 8px; border: 1px solid #d2dae2;">
                    @if(request('filter'))<input type="hidden" name="filter" value="{{ request('filter') }}">@endif
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
                            <select name="zone_id" class="form-control form-control-sm" onchange="loadTechMobileFilterAreas(this.value)">
                                <option value="">All Zones</option>
                                @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 pr-1 mb-2">
                            <label class="small font-weight-bold text-muted mb-1" style="font-size:10px;">SUB ZONE</label>
                            <select name="area_id" id="techMobileAreaSelect" class="form-control form-control-sm">
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
                        <a href="{{ route('technician.clients', array_filter(['filter' => request('filter'), 'status' => request('status')])) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo"></i> Reset</a>
                    </div>
                </form>
            </div>

            @if(($mobileFilterCount ?? 0) > 0)
            <div class="alert alert-info py-2 px-3 mb-2 d-flex align-items-center" style="font-size: 12px;">
                <i class="fas fa-search mr-2"></i>
                <strong>{{ $clients->total() }}</strong>&nbsp;clients found
                <a href="{{ route('technician.clients', array_filter(['filter' => request('filter'), 'status' => request('status')])) }}" class="ml-auto btn btn-outline-info btn-sm py-0 px-2" style="font-size: 10px;"><i class="fas fa-times mr-1"></i>Clear</a>
            </div>
            @endif

            <div class="text-center small text-muted mb-2">
                {{ $clients->total() }} clients @if($clients->lastPage() > 1) | Page {{ $clients->currentPage() }}/{{ $clients->lastPage() }} @endif
            </div>
        </div>

        <div class="search-form-desktop mb-3">
            <form method="GET" id="techFilterForm">
                @if(request('filter'))<input type="hidden" name="filter" value="{{ request('filter') }}">@endif
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                @php
                    $allFilterKeys = ['zone_id','area_id','package_id','connection_type','connection_medium','mikrotik_device_id','billing_status','payment_status','client_type','billing_date','due_filter','mac_filter','free_filter','onu_filter','expire_from','expire_to','per_page'];
                    $activeFilterCount = collect($allFilterKeys)->filter(fn($f) => request($f))->count();
                @endphp

                <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <input type="text" name="search" class="form-control" placeholder="Search across all zones: Name, ID, Phone, Username..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="var f=document.getElementById('techDesktopFilterPanel'); f.style.display=f.style.display==='none'?'block':'none';">
                        <i class="fas fa-filter mr-1"></i>Filters
                        @if($activeFilterCount > 0)
                        <span class="badge badge-light ml-1">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                    @if($activeFilterCount > 0)
                    <a href="{{ route('technician.clients', request('filter') ? ['filter' => request('filter')] : []) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times mr-1"></i>Clear All</a>
                    @endif
                    <span class="text-muted ml-auto small">{{ $clients->total() }} results</span>
                </div>
                @if($isSearching)
                <div class="alert alert-info py-1 px-3 mb-2" style="font-size: 11px; border-radius: 6px;">
                    <i class="fas fa-globe mr-1"></i> Showing results from <strong>all zones</strong>.
                    @php $otherZoneCount = $clients->filter(fn($c) => !empty($zoneIds) && !in_array($c->zone_id, $zoneIds))->count(); @endphp
                    @if($otherZoneCount > 0)
                        <span class="badge badge-warning ml-1">{{ $otherZoneCount }} from other zones</span> — <small>view only, no edit access</small>
                    @endif
                </div>
                @else
                <div class="text-muted mb-2" style="font-size: 11px;">
                    <i class="fas fa-info-circle mr-1"></i> Showing your assigned zone clients. <strong>Search</strong> to find clients from any zone.
                </div>
                @endif

                <div id="techDesktopFilterPanel" style="background: #f0f4f8; padding: 12px; border-radius: 8px; border: 1px solid #d2dae2; display: {{ $activeFilterCount > 0 ? 'block' : 'none' }};">
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
                            <select name="zone_id" class="form-control form-control-sm" onchange="loadTechFilterAreas(this.value)">
                                <option value="">Select</option>
                                @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-2">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1" style="font-size:10px;">Sub Zone</label>
                            <select name="area_id" class="form-control form-control-sm" id="techAreaFilter">
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
                        <a href="{{ route('technician.clients', array_filter(['filter' => request('filter'), 'status' => request('status')])) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo mr-1"></i>Reset</a>
                    </div>
                </div>
            </form>
            @if(($activeFilterCount ?? 0) > 0)
            <div class="alert alert-info py-2 px-3 mb-2 mt-2 d-flex align-items-center" style="font-size: 13px;">
                <i class="fas fa-search mr-2"></i>
                <strong>{{ $filteredCount ?? $clients->total() }}</strong>&nbsp;clients found
                <a href="{{ route('technician.clients', array_filter(['filter' => request('filter'), 'status' => request('status')])) }}" class="ml-auto btn btn-outline-info btn-sm py-0 px-2" style="font-size: 11px;"><i class="fas fa-times mr-1"></i>Clear Filters</a>
            </div>
            @endif
        </div>

        <div class="mobile-cards" id="techMobileClientCards">
            @forelse($clients as $client)
            @php $effectiveDue = $client->getEffectiveDue(); @endphp
            <div class="client-card" data-status="{{ $client->status }}" data-due="{{ $effectiveDue > 0 ? 'with_due' : 'no_due' }}" data-search="{{ strtolower($client->name.' '.$client->client_id.' '.($client->username ?? '').' '.$client->phone.' '.($client->zone->name ?? '').' '.($client->package->name ?? '').' '.$client->address) }}">
                <div class="client-header">
                    <div>
                        @php
                            $__aiDay = min($client->payment_deadline ?? 15, 28);
                            $__nowD = \Carbon\Carbon::now('Asia/Dhaka')->startOfDay();
                            $__billingDt = $__nowD->copy()->setDay($__aiDay);
                            if ($__billingDt->lt($__nowD)) { $__billingDt->addMonthNoOverflow(); }
                            $__expDate___daysLeftTM = $client->temp_deadline_date
                                ? \Carbon\Carbon::parse($client->temp_deadline_date)->startOfDay()
                                : $__billingDt;
                            $__daysLeftTM = $__expDate___daysLeftTM ? (int) now()->startOfDay()->diffInDays($__expDate___daysLeftTM, false) : null;
                        @endphp
                        <div class="client-name">{{ $client->name }}</div>
                        @if($__daysLeftTM !== null && $__daysLeftTM <= 3 && $client->status === 'active')
                            @if($__daysLeftTM <= 0)
                                <span class="expiry-badge exp-today">EXPIRED</span>
                            @elseif($__daysLeftTM == 1)
                                <span class="expiry-badge exp-warning">EXP TOMORROW</span>
                            @else
                                <span class="expiry-badge exp-soon">EXP {{ $__daysLeftTM }}d</span>
                            @endif
                        @endif
                        <div class="client-id">ID: {{ $client->username ?? $client->client_id }} | {{ $client->phone }}</div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;">
                    @if($client->status == 'active')
                        <span class="badge badge-success status-badge">Active</span>
                    @elseif($client->status == 'inactive')
                        <span class="badge badge-danger status-badge">Inactive</span>
                    @else
                        <span class="badge badge-secondary status-badge">{{ ucfirst($client->status) }}</span>
                    @endif
                    @if($client->connection_status == 'online')
                        <span class="badge" style="background:#00c853;color:#fff;font-size:9px;padding:2px 5px;"><i class="fas fa-circle mr-1" style="font-size:6px;"></i>Online</span>
                    @elseif($client->status == 'active')
                        <span class="badge" style="background:#bdbdbd;color:#fff;font-size:9px;padding:2px 5px;"><i class="fas fa-circle mr-1" style="font-size:6px;"></i>Offline</span>
                    @endif
                    </div>
                </div>
                @php $isOtherZone = !empty($zoneIds) && !in_array($client->zone_id, $zoneIds); @endphp
                <div class="client-info">
                    <strong>Package:</strong> {{ $client->package->name ?? '-' }} |
                    <strong>Zone:</strong> {{ $client->zone->name ?? '-' }}{{ $client->area ? ' / '.$client->area->name : '' }}{{ $client->box ? ' / TJ: '.$client->box->name : '' }}
                    @if($isOtherZone)
                        <span class="badge badge-warning ml-1" style="font-size: 9px; vertical-align: middle;">Other Zone</span>
                    @endif
                    | <strong>Net Due:</strong> <span class="{{ $effectiveDue > 0 ? 'text-danger' : 'text-success' }}">৳{{ number_format($effectiveDue ?? 0, 0) }}</span>
                </div>
                @if($client->address)
                <div style="font-size:12px;color:#555;margin-top:3px;line-height:1.4;"><i class="fas fa-map-marker-alt mr-1" style="color:#dc3545;font-size:11px;"></i>{{ $client->address }}</div>
                @endif
                @if($client->connection_status == 'online' && $client->session_uptime)
                @php
                    $mUptimeStr = $client->session_uptime; $mSecs = 0;
                    if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $mUptimeStr, $mHms)) {
                        $mSecs = $mHms[1]*3600 + $mHms[2]*60 + $mHms[3];
                    } else {
                        if(preg_match('/(\d+)w/', $mUptimeStr, $mM)) $mSecs += $mM[1]*604800;
                        if(preg_match('/(\d+)d/', $mUptimeStr, $mM)) $mSecs += $mM[1]*86400;
                        if(preg_match('/(\d+)h/', $mUptimeStr, $mM)) $mSecs += $mM[1]*3600;
                        if(preg_match('/(\d+)m/', $mUptimeStr, $mM)) $mSecs += $mM[1]*60;
                        if(preg_match('/(\d+)s/', $mUptimeStr, $mM)) $mSecs += $mM[1];
                    }
                    if ($client->last_online) { $mSecs += abs((int) \Carbon\Carbon::parse($client->last_online)->diffInSeconds(now())); }
                @endphp
                <div style="font-size:11px;color:#00c853;margin-top:3px;"><i class="fas fa-clock mr-1" style="font-size:10px;"></i><strong class="live-uptime" data-uptime-seconds="{{ $mSecs }}"></strong></div>
                @elseif($client->last_online)
                <div style="font-size:11px;color:#999;margin-top:3px;"><i class="fas fa-clock mr-1" style="font-size:10px;"></i>Last seen: {{ \Carbon\Carbon::parse($client->last_online)->setTimezone('Asia/Dhaka')->format('d M Y, h:i A') }}</div>
                @endif
                <div style="display:flex;gap:8px;margin-top:10px;">
                    <a href="{{ route('technician.clients.show', $client) }}"
                       style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px 4px;background:#17a2b8;color:#fff;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;min-width:0;">
                        <i class="fas fa-eye"></i> View
                    </a>
                    @if(!$isOtherZone)
                    <button type="button" data-toggle="modal" data-target="#paymentModal{{ $client->id }}"
                            style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px 4px;background:#28a745;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;min-width:0;">
                        <i class="fas fa-money-bill-wave"></i> Pay
                    </button>
                    @else
                    <span style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px 4px;background:#6c757d;color:#fff;border-radius:6px;font-size:12px;white-space:nowrap;min-width:0;">
                        <i class="fas fa-lock"></i> View Only
                    </span>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">
                @if($isSearching)
                    No clients found matching your search.
                @else
                    No clients found in your assigned zones.
                @endif
            </div>
            @endforelse
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm" id="clientsTable">
                <thead>
                    <tr>
                        <th>ComID</th>
                        <th>ID/Name/Cell</th>
                        <th>Zone/Address</th>
                        <th>Package</th>
                        <th>Due</th>
                        <th style="white-space:nowrap;">Dates</th>
                        <th>Online Info</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    @php
                        $effectiveDue = $client->getEffectiveDue();
                        $isOtherZone = !empty($zoneIds) && !in_array($client->zone_id, $zoneIds);
                        $displayIp = $client->session_ip ?: $client->ip_address;
                        $displayMac = $client->session_mac ?: $client->mac_address;
                    @endphp
                    <tr data-search="{{ strtolower($client->name.' '.($client->client_id ?? '').' '.($client->username ?? '').' '.($client->phone ?? '').' '.($client->address ?? '').' '.($client->package->name ?? '').' '.($client->zone->name ?? '')) }}" @if($isOtherZone) style="background: rgba(255,193,7,0.04);" @endif>
                        <td>{{ $client->client_id ?? $client->id }}</td>
                        <td class="pop-trigger" data-pop-target="pop-usr-{{ $client->id }}" style="cursor:pointer;" title="Click for details">
                        @php
                            $__aiDay = min($client->payment_deadline ?? 15, 28);
                            $__nowD = \Carbon\Carbon::now('Asia/Dhaka')->startOfDay();
                            $__billingDt = $__nowD->copy()->setDay($__aiDay);
                            if ($__billingDt->lt($__nowD)) { $__billingDt->addMonthNoOverflow(); }
                            $__expDate___daysLeftTD = $client->temp_deadline_date
                                ? \Carbon\Carbon::parse($client->temp_deadline_date)->startOfDay()
                                : $__billingDt;
                            $__daysLeftTD = $__expDate___daysLeftTD ? (int) now()->startOfDay()->diffInDays($__expDate___daysLeftTD, false) : null;
                        @endphp
                            {{ $client->name }}
                        @if($__daysLeftTD !== null && $__daysLeftTD <= 3 && $client->status === 'active')
                            @if($__daysLeftTD <= 0)
                                <span class="expiry-badge exp-today">EXPIRED</span>
                            @elseif($__daysLeftTD == 1)
                                <span class="expiry-badge exp-warning">EXP TOMORROW</span>
                            @else
                                <span class="expiry-badge exp-soon">EXP {{ $__daysLeftTD }}d</span>
                            @endif
                        @endif
                            <br><span class="text-primary small">{{ $client->phone ?? '' }}</span>
                            <div id="pop-usr-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-user mr-1 text-info"></i><strong>{{ $client->username ?? $client->name }}</strong></div>
                                <div class="text-muted mt-1"><i class="fas fa-phone mr-1 text-success"></i>{{ $client->phone ?? '-' }}</div>
                                @if($client->email)<div class="text-muted mt-1"><i class="fas fa-envelope mr-1"></i>{{ $client->email }}</div>@endif
                                @if($client->nid)<div class="text-muted mt-1"><i class="fas fa-id-card mr-1"></i>NID: {{ $client->nid }}</div>@endif
                            </div>
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-addr-{{ $client->id }}" style="cursor:pointer;" title="Click for address details">
                            <strong class="text-primary">{{ $client->zone->name ?? '-' }}</strong>
                            @if($isOtherZone)
                                <span class="badge badge-warning ml-1" style="font-size: 9px;">Other Zone</span>
                            @endif
                            <div id="pop-addr-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-map-marker-alt mr-1 text-primary"></i><strong>{{ $client->zone->name ?? '-' }}</strong></div>
                                @if($client->area)<div class="text-muted mt-1"><i class="fas fa-layer-group mr-1 text-secondary" style="font-size:11px;"></i>Sub: {{ $client->area->name }}</div>@endif
                                @if($client->box)<div class="text-muted"><i class="fas fa-project-diagram mr-1 text-secondary" style="font-size:11px;"></i>TJ: {{ $client->box->name }}</div>@endif
                                @if($client->address)<div class="mt-1">{{ $client->address }}</div>@endif
                            </div>
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-pkg-{{ $client->id }}" style="cursor:pointer;" title="Click for package details">
                            @php $tMkProfile = $client->mikrotik_profile_name ?: ($client->package->mikrotik_profile ?? null); @endphp
                            <span class="text-nowrap">{{ $client->package->name ?? '-' }}</span>
                            @if($client->package && $client->package->bandwidth)
                            <br><span class="text-info small"><i class="fas fa-tachometer-alt"></i> {{ $client->package->bandwidth }}</span>
                            @endif
                            <div id="pop-pkg-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                <div><i class="fas fa-box mr-1 text-info"></i><strong>{{ $client->package->name ?? '-' }}</strong></div>
                                @if($client->package && $client->package->bandwidth)<div class="text-info mt-1"><i class="fas fa-tachometer-alt mr-1"></i>{{ $client->package->bandwidth }}</div>@endif
                                @if($tMkProfile)<div class="mt-1"><i class="fas fa-network-wired mr-1 text-muted"></i>Profile: <strong>{{ $tMkProfile }}</strong></div>@endif
                                @if($client->monthly_fee)<div class="text-muted mt-1"><i class="fas fa-tag mr-1"></i>Fee: ৳{{ number_format($client->monthly_fee, 0) }}/mo</div>@endif
                            </div>
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
                            <br><span class="text-info small"><i class="fas fa-clock mr-1"></i>Extended</span>
                            @endif
                            <div id="pop-dates-{{ $client->id }}" class="d-none" style="min-width:260px;">
                                <div><i class="fas fa-calendar mr-1 text-muted"></i>Billing Day: <strong>{{ $client->billing_date ?? 1 }}</strong></div>
                                <div class="mt-1"><i class="fas fa-ban mr-1 text-warning"></i>Auto-Inactive: <strong class="text-warning">{{ $listAiShow->format('d-M-Y') }}</strong></div>
                                @if($client->expire_date)<div class="mt-1"><i class="fas fa-hourglass-end mr-1 text-muted"></i>Expiry: <strong class="{{ $client->expire_date->isPast() ? 'text-danger' : 'text-muted' }}">{{ $client->expire_date->format('d-M-Y') }}</strong></div>@endif
                                @if($client->temp_deadline_date)<div class="mt-1"><i class="fas fa-clock mr-1 text-info"></i>Extended: <strong class="text-info">{{ \Carbon\Carbon::parse($client->temp_deadline_date)->format('d-M-Y') }}</strong></div>@endif
                                @if($client->connection_date)<div class="mt-1"><i class="fas fa-plug mr-1 text-success"></i>Joined: <strong>{{ \Carbon\Carbon::parse($client->connection_date)->format('d-M-Y') }}</strong></div>@endif
                            </div>
                        </td>
                        <td class="pop-trigger" data-pop-target="pop-net-{{ $client->id }}" style="cursor:pointer;" title="Click for network info">
                            @if($client->session_uptime && $client->connection_status == 'online')
                            @php
                                $uptimeStr = $client->session_uptime;
                                $secs = 0;
                                if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $uptimeStr, $hms)) {
                                    $secs = $hms[1]*3600 + $hms[2]*60 + $hms[3];
                                } else {
                                    if(preg_match('/(\d+)w/', $uptimeStr, $m)) $secs += $m[1]*604800;
                                    if(preg_match('/(\d+)d/', $uptimeStr, $m)) $secs += $m[1]*86400;
                                    if(preg_match('/(\d+)h/', $uptimeStr, $m)) $secs += $m[1]*3600;
                                    if(preg_match('/(\d+)m/', $uptimeStr, $m)) $secs += $m[1]*60;
                                    if(preg_match('/(\d+)s/', $uptimeStr, $m)) $secs += $m[1];
                                }
                                if ($client->last_online) {
                                    $secs += abs((int) \Carbon\Carbon::parse($client->last_online)->diffInSeconds(now()));
                                }
                            @endphp
                            <strong class="text-success live-uptime" data-uptime-seconds="{{ $secs }}"></strong>
                            @elseif($displayIp)
                            <span class="text-muted small">{{ $displayIp }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                            @if($displayIp || $displayMac)
                            <div id="pop-net-{{ $client->id }}" class="d-none" style="min-width:240px;">
                                @if($displayIp)<div><i class="fas fa-globe mr-1 text-info"></i>IP: <strong>{{ $displayIp }}</strong></div>@endif
                                @if($displayMac)<div class="mt-1"><i class="fas fa-ethernet mr-1 text-muted"></i>MAC: <strong>{{ $displayMac }}</strong></div>@endif
                                @if($client->session_uptime && $client->connection_status == 'online')<div class="mt-1 text-success"><i class="fas fa-clock mr-1"></i>Uptime: <strong>{{ $client->session_uptime }}</strong></div>@endif
                            </div>
                            @endif
                        </td>
                        <td class="text-center">
                            <div>
                            @if($client->status == 'active')
                                <span class="badge badge-outline-success status-badge" style="border:1.5px solid #28a745;color:#28a745;background:transparent;font-weight:600;padding:4px 12px;">ACTIVE</span>
                            @elseif($client->status == 'inactive')
                                <span class="badge badge-outline-danger status-badge" style="border:1.5px solid #dc3545;color:#dc3545;background:transparent;font-weight:600;padding:4px 12px;">INACTIVE</span>
                            @else
                                <span class="badge badge-outline-secondary status-badge" style="border:1.5px solid #6c757d;color:#6c757d;background:transparent;font-weight:600;padding:4px 12px;">{{ strtoupper($client->status) }}</span>
                            @endif
                            </div>
                            <div style="margin-top:4px;">
                            @if($client->connection_status == 'online')
                                <span class="badge status-badge" style="border:1.5px solid #007bff;color:#007bff;background:transparent;font-weight:600;padding:4px 12px;">ONLINE</span>
                            @else
                                <span class="badge status-badge" style="border:1.5px solid #dc3545;color:#dc3545;background:transparent;font-weight:600;padding:4px 12px;">OFFLINE</span>
                            @endif
                            </div>
                        </td>
                        <td class="action-btns">
                            <div class="btn-group">
                                <a href="{{ route('technician.clients.show', $client) }}" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                <button type="button" class="btn btn-success btn-sm" title="Collect Payment" data-toggle="modal" data-target="#paymentModal{{ $client->id }}">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">
                        @if($isSearching)
                            No clients found matching your search across all zones.
                        @else
                            No clients found in your assigned zones.
                        @endif
                    </td></tr>
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
@php 
    $effectiveDue = $client->getEffectiveDue();
    $unpaidBills = \App\Models\Bill::where('client_id', $client->id)
        ->whereIn('status', ['unpaid', 'partial', 'overdue'])
        ->orderBy('bill_date', 'desc')
        ->get();
@endphp
<div class="modal fade" id="paymentModal{{ $client->id }}" tabindex="-1">
    <div class="modal-dialog modal-sm" style="margin:10px auto;max-width:360px;">
        <div class="modal-content" style="border-radius:10px;overflow:hidden;">
            <form action="{{ route('technician.collect-payment', $client) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white py-2 px-3">
                    <h6 class="modal-title mb-0"><i class="fas fa-money-bill-wave mr-1"></i>Collect Payment</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" style="font-size:18px;">&times;</button>
                </div>
                <div class="modal-body px-3 py-2">
                    {{-- Client Info Row --}}
                    <div style="background:{{ $effectiveDue > 0 ? '#e8f4fd' : '#e8f8e8' }};border-radius:7px;padding:8px 12px;margin-bottom:12px;border-left:3px solid {{ $effectiveDue > 0 ? '#17a2b8' : '#28a745' }};">
                        <div style="font-weight:700;font-size:13px;color:#333;">{{ $client->name }} <span style="font-weight:400;color:#666;font-size:11px;">· {{ $client->client_id }}</span></div>
                        <div style="font-size:12px;margin-top:2px;">
                            @if($effectiveDue > 0)
                                <span style="color:#dc3545;font-weight:700;">Due: ৳{{ number_format($effectiveDue, 0) }}</span>
                            @else
                                <span style="color:#28a745;font-weight:600;">No Due</span>
                                @if($client->getAvailableAdvance() > 0)
                                <span style="color:#28a745;"> · Advance: ৳{{ number_format($client->getAvailableAdvance(), 0) }}</span>
                                @endif
                            @endif
                            @php $oldestBill = $unpaidBills->sortBy('billing_month')->first(); @endphp
                            @if($oldestBill)
                            <span style="color:#888;font-size:11px;"> · {{ \Carbon\Carbon::create($oldestBill->billing_year, $oldestBill->billing_month, 1)->format('M Y') }}</span>
                            @endif
                        </div>
                    </div>
                    {{-- Amount --}}
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;margin-bottom:3px;"><i class="fas fa-money-bill mr-1 text-success"></i>Amount</label>
                        <input type="number" name="amount" class="form-control form-control-sm amount-input-{{ $client->id }}"
                               min="1" step="1" required value="{{ $effectiveDue > 0 ? $effectiveDue : '' }}"
                               placeholder="{{ $effectiveDue > 0 ? 'Amount...' : 'Advance amount...' }}"
                               data-due="{{ $effectiveDue }}" data-client-id="{{ $client->id }}"
                               oninput="checkAmountDiff(this, {{ $client->id }})" onchange="checkAmountDiff(this, {{ $client->id }})">
                        <small class="text-warning amount-warning-{{ $client->id }}" style="display:none;font-size:11px;">Amount differs — note required!</small>
                    </div>
                    {{-- Payment Method --}}
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;margin-bottom:3px;"><i class="fas fa-credit-card mr-1 text-primary"></i>Method</label>
                        <select name="payment_method_id" class="form-control form-control-sm" required>
                            @foreach(auth()->user()->allowedPaymentMethods() as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Receipt No (cash) --}}
                    <div class="form-group mb-2 tech-cash-receipt-field" style="display:none;">
                        <label style="font-size:12px;font-weight:600;margin-bottom:3px;"><i class="fas fa-receipt mr-1"></i>Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control form-control-sm" placeholder="Receipt number">
                    </div>
                    {{-- Online fields --}}
                    <div class="tech-online-fields" style="display:none;">
                        <div class="form-group mb-2">
                            <label style="font-size:12px;font-weight:600;margin-bottom:3px;"><i class="fas fa-hashtag mr-1"></i>Transaction ID</label>
                            <input type="text" name="transaction_id" class="form-control form-control-sm" placeholder="Transaction ID">
                        </div>
                        <div class="form-group mb-2">
                            <label style="font-size:12px;font-weight:600;margin-bottom:3px;"><i class="fas fa-mobile-alt mr-1"></i>Mobile No.</label>
                            <input type="text" name="mobile_payment_number" class="form-control form-control-sm" placeholder="017XXXXXXXX">
                        </div>
                    </div>
                    {{-- Reason (when amount differs) --}}
                    <div class="form-group mb-2 reason-group-{{ $client->id }}" style="display:none;">
                        <label style="font-size:12px;font-weight:600;margin-bottom:3px;"><i class="fas fa-comment-alt mr-1 text-warning"></i>Reason <span class="text-danger">*</span></label>
                        <input type="text" name="notes_reason" class="form-control form-control-sm reason-input-{{ $client->id }}" placeholder="Why different from due?">
                    </div>
                    {{-- Notes --}}
                    <div class="form-group mb-1">
                        <label style="font-size:12px;font-weight:600;margin-bottom:3px;"><i class="fas fa-comment mr-1 text-muted"></i>Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-body pt-0 pb-2 px-3">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="tech_cl_send_sms_{{ $client->id }}" name="send_sms" value="1" checked>
                        <label class="custom-control-label" for="tech_cl_send_sms_{{ $client->id }}" style="font-size:13px;"><i class="fas fa-sms mr-1 text-primary"></i> Send SMS notification</label>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="gap:8px;">
                    <button type="button" class="btn btn-secondary btn-sm flex-fill" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm flex-fill"><i class="fas fa-check mr-1"></i>Collect</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimeout = null;
    const searchInput = $('#mobileClientSearch');
    const resultsDiv = $('#mobileSearchResults');

    searchInput.on('input', function() {
        const query = $(this).val().trim();
        if (searchTimeout) clearTimeout(searchTimeout);
        if (query.length < 2) { resultsDiv.empty().hide(); return; }

        searchTimeout = setTimeout(() => {
            $.ajax({
                url: "{{ route('technician.api.quick-search') }}",
                data: { q: query },
                success: function(data) {
                    resultsDiv.empty();
                    if (data.length > 0) {
                        data.forEach(client => {
                            const initial = client.name.charAt(0).toUpperCase();
                            const statusClass = client.status === 'active' ? 'success' : (client.status === 'inactive' ? 'danger' : 'warning');
                            const connStatusClass = client.connection_status === 'online' ? 'success' : 'danger';
                            resultsDiv.append(`
                                <a href="/technician/clients/${client.id}" class="search-result-item">
                                    <div class="d-flex align-items-center">
                                        <div class="result-avatar">${initial}</div>
                                        <div class="result-info">
                                            <div class="d-flex justify-content-between">
                                                <div class="result-name">${client.name}</div>
                                                <div class="status-badges">
                                                    <span class="badge badge-xs badge-${statusClass}">${client.status}</span>
                                                    <span class="badge badge-xs badge-${connStatusClass}"><i class="fas fa-wifi mr-1"></i>${client.connection_status}</span>
                                                </div>
                                            </div>
                                            <div class="result-meta">
                                                <span><i class="fas fa-id-card"></i> ${client.username || client.client_id}</span>
                                                <span class="text-success"><i class="fas fa-phone"></i> ${client.phone}</span>
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted ml-2"></i>
                                    </div>
                                </a>
                            `);
                        });
                        resultsDiv.show();
                    } else {
                        resultsDiv.append('<div class="p-3 text-center text-muted">No clients found</div>').show();
                    }
                }
            });
        }, 300);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.mobile-quick-search').length) { resultsDiv.empty().hide(); }
    });
});

function checkAmountDiff(input, clientId) {
    var amount = parseFloat(input.value) || 0;
    var due = parseFloat(input.dataset.due) || 0;
    var isDifferent = Math.abs(amount - due) > 0.01;
    $('.amount-warning-' + clientId).toggle(isDifferent);
    var rg = $('.reason-group-' + clientId);
    var ri = $('.reason-input-' + clientId);
    rg.toggle(isDifferent);
    ri.prop('required', isDifferent);
    if (!isDifferent) ri.val('');
}

$(document).on('change', 'select[name="payment_method_id"]', function() {
    var modal = $(this).closest('.modal');
    var methodName = ($(this).find('option:selected').data('name') || '').toLowerCase();
    var isCash = methodName.indexOf('cash') !== -1;
    var isOnline = !isCash && methodName.length > 0;
    modal.find('.tech-cash-receipt-field').toggle(isCash);
    modal.find('.tech-online-fields').toggle(isOnline);
    if (!isCash) modal.find('.tech-cash-receipt-field input').val('');
    if (!isOnline) modal.find('.tech-online-fields input').val('');
});

$(document).ready(function() {
    $('#quickSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#clientsTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    function techMobileFilter() {
        var search = $('#techMobileQuickSearch').val().toLowerCase();
        var status = $('#techMobileStatusFilter').val();
        var due = $('#techMobileDueFilter').val();
        var visibleCount = 0;
        $('#techMobileClientCards .client-card').each(function() {
            var show = true;
            if (search && !$(this).data('search').includes(search)) show = false;
            if (status && $(this).data('status') !== status) show = false;
            if (due && $(this).data('due') !== due) show = false;
            $(this).toggle(show);
            if (show) visibleCount++;
        });
        $('#techMobileFilterCount').text('Showing ' + visibleCount + ' of {{ $clients->count() }} clients');
    }
    $('#techMobileQuickSearch').on('input', techMobileFilter);
    $('#techMobileStatusFilter, #techMobileDueFilter').on('change', techMobileFilter);
    
    $(document).on('input change', 'input[name="amount"]', function() {
        var clientId = $(this).data('client-id') || $(this).attr('class').match(/amount-input-(\d+)/)?.[1];
        if (clientId) {
            checkAmountDiff(this, clientId);
        }
    });
});

function loadTechFilterAreas(zoneId) {
    var select = document.getElementById('techAreaFilter');
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

function loadTechMobileFilterAreas(zoneId) {
    var select = document.getElementById('techMobileAreaSelect');
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

// Live uptime counter
function formatUptime(secs) {
    secs = Math.floor(secs);
    var h = Math.floor(secs/3600), m = Math.floor((secs%3600)/60), s = secs%60;
    return (h>0 ? h+'h' : '') + (h>0||m>0 ? (m<10&&h>0?'0':'')+m+'m' : '') + (s<10&&(h>0||m>0)?'0':'')+s+'s';
}
var uptimeEls = document.querySelectorAll('.live-uptime[data-uptime-seconds]');
uptimeEls.forEach(function(el) {
    var secs = parseInt(el.getAttribute('data-uptime-seconds')) || 0;
    el.textContent = formatUptime(secs);
    setInterval(function() { secs++; el.textContent = formatUptime(secs); }, 1000);
});
</script>
@endpush

@push('scripts')
<script>
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
<script>
// AJAX payment collection for My Clients modals
jQuery(document).on("submit", "[id^=paymentModal] form", function(e) {
    e.preventDefault();
    var formEl = this;
    var btn = jQuery(formEl).find("[type=submit]");
    btn.prop("disabled", true).html("<i class=fas fa-spinner fa-spin mr-1></i>Processing...");
    jQuery.ajax({
        url: formEl.action,
        method: "POST",
        data: jQuery(formEl).serialize(),
        headers: { "Accept": "application/json" },
        success: function(res) {
            if (res.success) {
                jQuery(formEl).closest(".modal").modal("hide");
                if (res.thermal_url) window.open(res.thermal_url, "_blank");
                Swal.fire({ icon: "success", title: "Done!", text: res.message || "Payment collected!", timer: 2000, showConfirmButton: false });
                setTimeout(function() { location.reload(); }, 2200);
            } else {
                Swal.fire({ icon: "error", title: "Failed", text: res.message || "Payment failed." });
                btn.prop("disabled", false).html("<i class=fas fa-check mr-1></i>Collect");
            }
        },
        error: function(xhr) {
            var msg = xhr.responseJSON ? (xhr.responseJSON.message || "Payment failed.") : "Payment failed.";
            Swal.fire({ icon: "error", title: "Error", text: msg });
            btn.prop("disabled", false).html("<i class=fas fa-check mr-1></i>Collect");
        }
    });
});
</script>
@endpush
