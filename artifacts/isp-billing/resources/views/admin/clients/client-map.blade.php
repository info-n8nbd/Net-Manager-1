@extends('layouts.admin')
@section('title', 'Client Location Map')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item active">Client Map</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
#client-map { height: 550px; width: 100%; border-radius: 0 0 4px 4px; }
.legend { background: #fff; padding: 10px 14px; border-radius: 6px; box-shadow: 0 1px 5px rgba(0,0,0,.3); font-size: 12px; line-height: 2; }
.legend-dot { display: inline-block; width: 13px; height: 13px; border-radius: 50%; margin-right: 6px; vertical-align: middle; border: 2px solid rgba(0,0,0,.15); }
.client-list-item { border-left: 3px solid #ccc; padding: 6px 10px; margin-bottom: 6px; background: #fafafa; border-radius: 0 4px 4px 0; font-size: 13px; }
.client-list-item.active { border-color: #28a745; }
.client-list-item.inactive { border-color: #ffc107; }
.client-list-item.expired { border-color: #dc3545; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Summary Cards --}}
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-primary">
                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Clients</span>
                    <span class="info-box-number">{{ $allClients->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active</span>
                    <span class="info-box-number">{{ $statusCounts['active'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-map-marker-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">On Map</span>
                    <span class="info-box-number">{{ $mapMarkers->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-map-marker"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">No Location</span>
                    <span class="info-box-number">{{ $withoutLocation->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap" id="filterForm">
                <div class="form-group mr-2 mb-2">
                    <label class="mr-1 text-sm">Zone:</label>
                    <select name="zone_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Zones</option>
                        @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label class="mr-1 text-sm">Area:</label>
                    <select name="area_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Areas</option>
                        @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label class="mr-1 text-sm">Status:</label>
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="form-group mb-2 mr-2">
                    <div class="input-group input-group-sm">
                        <input type="text" id="markerSearch" class="form-control" placeholder="Search on map..." onkeyup="searchMarkers(this.value)">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
                @if(request()->hasAny(['zone_id','area_id','status']))
                <a href="{{ route('admin.clients.map') }}" class="btn btn-sm btn-outline-secondary mb-2">
                    <i class="fas fa-times mr-1"></i>Clear
                </a>
                @endif
            </form>
        </div>
    </div>

    <div class="row">
        {{-- Map --}}
        <div class="{{ $withoutLocation->count() > 0 ? 'col-lg-8' : 'col-12' }}">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-map-marked-alt mr-2"></i>Client Locations
                        <span class="badge badge-info ml-2">{{ $mapMarkers->count() }} on map</span>
                    </h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-outline-success active" onclick="filterStatus('all', this)">All</button>
                        <button class="btn btn-sm btn-outline-success" onclick="filterStatus('active', this)">Active</button>
                        <button class="btn btn-sm btn-outline-warning" onclick="filterStatus('inactive', this)">Inactive</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="filterStatus('expired', this)">Expired</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($mapMarkers->count() == 0)
                    <div class="p-4">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>No clients have location set.</strong>
                            To show clients on the map, edit each client and set their GPS coordinates (Latitude & Longitude) in the client edit form.
                            <br><br>
                            <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-users mr-1"></i>Go to Client List
                            </a>
                        </div>
                    </div>
                    @endif
                    <div id="client-map"></div>
                </div>
            </div>
        </div>

        {{-- Clients without location --}}
        @if($withoutLocation->count() > 0)
        <div class="col-lg-4">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-2 text-warning"></i>
                        No Location Set
                        <span class="badge badge-warning ml-1">{{ $withoutLocation->count() }}</span>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-2" style="max-height:550px;overflow-y:auto;">
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Search..." onkeyup="filterList(this.value)">
                    </div>
                    <div id="noLocationList">
                        @foreach($withoutLocation->sortBy('name') as $client)
                        <div class="client-list-item {{ $client->status }}" data-name="{{ strtolower($client->name) }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong style="font-size:13px;">{{ $client->name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-map-pin mr-1"></i>{{ $client->zone->name ?? 'N/A' }} / {{ $client->area->name ?? 'N/A' }}
                                    </small>
                                    <br>
                                    <small>
                                        <span class="badge badge-{{ $client->status === 'active' ? 'success' : ($client->status === 'expired' ? 'danger' : 'warning') }} badge-sm">
                                            {{ ucfirst($client->status) }}
                                        </span>
                                    </small>
                                </div>
                                <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn btn-xs btn-outline-primary" title="Set Location">
                                    <i class="fas fa-map-marker-alt"></i>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var map, allMarkers = [];
var clientData = @json($mapMarkers);

function getColor(status) {
    if (status === 'active')   return '#28a745';
    if (status === 'expired')  return '#dc3545';
    if (status === 'inactive') return '#ffc107';
    return '#6c757d';
}

function makeCircleIcon(status) {
    var color = getColor(status);
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18">' +
              '<circle cx="9" cy="9" r="7" fill="' + color + '" stroke="#fff" stroke-width="2"/>' +
              '</svg>';
    return L.divIcon({ html: svg, className: '', iconSize: [18,18], iconAnchor: [9,9], popupAnchor: [0,-10] });
}

function buildPopup(item) {
    var statusColor = getColor(item.status);
    return '<div style="min-width:210px;font-family:Arial,sans-serif;">' +
        '<h6 style="margin:0 0 8px 0;font-size:14px;border-bottom:2px solid ' + statusColor + ';padding-bottom:5px;">' +
        '<i class="fas fa-user" style="margin-right:5px;"></i><b>' + item.name + '</b></h6>' +
        '<table style="font-size:12px;width:100%;">' +
        '<tr><td style="color:#666;padding:2px 8px 2px 0;width:75px;">Status:</td><td><span style="background:' + statusColor + ';color:#fff;padding:1px 7px;border-radius:10px;font-size:11px;">' + item.status + '</span></td></tr>' +
        '<tr><td style="color:#666;padding:2px 8px 2px 0;">Zone:</td><td>' + item.zone + '</td></tr>' +
        '<tr><td style="color:#666;padding:2px 8px 2px 0;">Area:</td><td>' + item.area + '</td></tr>' +
        '<tr><td style="color:#666;padding:2px 8px 2px 0;">Package:</td><td>' + item.package + '</td></tr>' +
        (item.phone ? '<tr><td style="color:#666;padding:2px 8px 2px 0;">Phone:</td><td>' + item.phone + '</td></tr>' : '') +
        (item.username ? '<tr><td style="color:#666;padding:2px 8px 2px 0;">Username:</td><td><code style="font-size:11px;">' + item.username + '</code></td></tr>' : '') +
        (item.address ? '<tr><td style="color:#666;padding:2px 8px 2px 0;">Address:</td><td style="font-size:11px;">' + item.address + '</td></tr>' : '') +
        '</table>' +
        '<div style="margin-top:9px;display:flex;gap:6px;">' +
        '<a href="' + item.show_url + '" style="background:#007bff;color:#fff;padding:3px 10px;border-radius:3px;text-decoration:none;font-size:11px;flex:1;text-align:center;">View</a>' +
        '<a href="' + item.edit_url + '" style="background:#ffc107;color:#212529;padding:3px 10px;border-radius:3px;text-decoration:none;font-size:11px;flex:1;text-align:center;">Edit</a>' +
        '</div></div>';
}

document.addEventListener('DOMContentLoaded', function() {
    map = L.map('client-map').setView([23.8103, 90.4125], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    var legend = L.control({ position: 'bottomright' });
    legend.onAdd = function() {
        var div = L.DomUtil.create('div', 'legend');
        div.innerHTML =
            '<b style="font-size:12px;">Client Status</b><br>' +
            '<span class="legend-dot" style="background:#28a745;"></span> Active<br>' +
            '<span class="legend-dot" style="background:#dc3545;"></span> Expired<br>' +
            '<span class="legend-dot" style="background:#ffc107;"></span> Inactive';
        return div;
    };
    legend.addTo(map);

    var bounds = [];
    clientData.forEach(function(item) {
        var marker = L.marker([item.lat, item.lng], {
            icon: makeCircleIcon(item.status),
            title: item.name
        });
        marker.bindPopup(buildPopup(item), { maxWidth: 260 });
        marker.itemData = item;
        marker.addTo(map);
        allMarkers.push(marker);
        bounds.push([item.lat, item.lng]);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [50, 50] });
        if (bounds.length === 1) map.setZoom(15);
    }
});

function filterStatus(status, btn) {
    document.querySelectorAll('.card-tools .btn').forEach(function(b) { b.classList.remove('active'); });
    if (btn) btn.classList.add('active');

    allMarkers.forEach(function(marker) {
        var show = status === 'all' || marker.itemData.status === status;
        if (show) map.addLayer(marker);
        else map.removeLayer(marker);
    });
}

function searchMarkers(val) {
    val = val.toLowerCase();
    allMarkers.forEach(function(marker) {
        var d = marker.itemData;
        var match = !val ||
            d.name.toLowerCase().includes(val) ||
            (d.username && d.username.toLowerCase().includes(val)) ||
            (d.phone && d.phone.includes(val)) ||
            (d.area && d.area.toLowerCase().includes(val));
        if (match) map.addLayer(marker);
        else map.removeLayer(marker);
    });
}

function filterList(val) {
    val = val.toLowerCase();
    document.querySelectorAll('#noLocationList .client-list-item').forEach(function(el) {
        var name = el.dataset.name || '';
        el.style.display = name.includes(val) ? '' : 'none';
    });
}
</script>
@endpush
