@extends('layouts.admin')

@section('title', 'Network Client Diagram')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">Network</a></li>
<li class="breadcrumb-item active">Client Diagram</li>
@endsection

@push('styles')
<link href="https://unpkg.com/vis-network@9.1.6/styles/vis-network.min.css" rel="stylesheet">
<style>
    .network-container {
        height: calc(100vh - 280px);
        min-height: 500px;
        background: linear-gradient(135deg, #0a1628 0%, #1a2a4a 50%, #0d1a2d 100%);
        border-radius: 12px;
        position: relative;
        overflow: hidden;
    }
    #client-network {
        width: 100%;
        height: 100%;
    }
    .grid-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: 
            linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
        background-size: 50px 50px;
        pointer-events: none;
    }
    .stats-panel {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 100;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .stat-card {
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        padding: 12px 18px;
        min-width: 100px;
        text-align: center;
    }
    .stat-card .value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
    }
    .stat-card .label {
        font-size: 11px;
        color: rgba(255,255,255,0.6);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 5px;
    }
    .stat-card.total .value { color: #00aaff; }
    .stat-card.active .value { color: #00ff88; }
    .stat-card.online .value { color: #00ff88; }
    .stat-card.offline .value { color: #ff4757; }
    .control-panel {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 100;
        display: flex;
        gap: 8px;
    }
    .control-btn {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.2);
        background: rgba(0,0,0,0.5);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        backdrop-filter: blur(10px);
    }
    .control-btn:hover {
        background: rgba(0, 170, 255, 0.3);
        border-color: #00aaff;
        transform: scale(1.05);
    }
    .legend-panel {
        position: absolute;
        bottom: 15px;
        left: 15px;
        z-index: 100;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        padding: 15px;
    }
    .legend-title {
        font-size: 12px;
        font-weight: 600;
        color: rgba(255,255,255,0.8);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .legend-items {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: rgba(255,255,255,0.7);
    }
    .legend-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    .search-panel {
        position: absolute;
        top: 65px;
        right: 15px;
        z-index: 100;
    }
    .search-input {
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 8px;
        padding: 10px 15px;
        color: #fff;
        width: 200px;
        font-size: 13px;
    }
    .search-input::placeholder { color: rgba(255,255,255,0.4); }
    .search-input:focus {
        outline: none;
        border-color: #00aaff;
    }
    .view-mode-panel {
        position: absolute;
        bottom: 15px;
        right: 15px;
        z-index: 100;
        display: flex;
        gap: 5px;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        padding: 5px;
    }
    .view-btn {
        padding: 8px 15px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: rgba(255,255,255,0.6);
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .view-btn:hover { color: #fff; }
    .view-btn.active {
        background: rgba(0, 170, 255, 0.3);
        color: #fff;
    }
    .info-panel {
        position: absolute;
        bottom: 70px;
        right: 15px;
        z-index: 100;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        padding: 12px 15px;
        font-size: 11px;
        color: rgba(255,255,255,0.7);
        max-width: 200px;
    }
    @media (max-width: 768px) {
        .stats-panel { flex-wrap: wrap; }
        .stat-card { min-width: 70px; padding: 8px 12px; }
        .stat-card .value { font-size: 20px; }
        .legend-items { flex-direction: column; gap: 8px; }
    }
</style>
@endpush

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-primary mb-0">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Network Client Diagram - All Clients by Zone / Box</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.network.diagram') }}" class="btn btn-sm btn-default mr-1">
                        <i class="fas fa-project-diagram"></i> Device Diagram
                    </a>
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> Client List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="network-container">
    <div class="grid-overlay"></div>
    <div id="diagram-loading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:200;text-align:center;color:rgba(255,255,255,0.8);">
        <div style="font-size:40px;margin-bottom:12px;"><i class="fas fa-spinner fa-spin" style="color:#00aaff;"></i></div>
        <div style="font-size:14px;">Loading diagram...</div>
    </div>
    
    <div class="stats-panel">
        <div class="stat-card total">
            <div class="value" id="total-clients">{{ number_format($totalClients) }}</div>
            <div class="label">Total Clients</div>
        </div>
        <div class="stat-card active">
            <div class="value" id="active-clients">{{ number_format($activeClients) }}</div>
            <div class="label">Active</div>
        </div>
        <div class="stat-card online">
            <div class="value" id="online-clients">{{ number_format($onlineClients) }}</div>
            <div class="label">Online</div>
        </div>
        <div class="stat-card offline">
            <div class="value" id="offline-clients">{{ number_format($offlineClients) }}</div>
            <div class="label">Offline</div>
        </div>
    </div>

    <div class="control-panel">
        <button class="control-btn" id="zoom-in" title="Zoom In"><i class="fas fa-plus"></i></button>
        <button class="control-btn" id="zoom-out" title="Zoom Out"><i class="fas fa-minus"></i></button>
        <button class="control-btn" id="fit-view" title="Fit to View"><i class="fas fa-expand"></i></button>
        <button class="control-btn" id="toggle-physics" title="Toggle Physics"><i class="fas fa-atom"></i></button>
        <button class="control-btn" id="fullscreen" title="Fullscreen"><i class="fas fa-arrows-alt"></i></button>
        <button class="control-btn" title="Refresh" onclick="window.location.reload()" style="font-size:13px;"><i class="fas fa-sync-alt"></i></button>
    </div>
    <div style="position:absolute;top:62px;left:15px;z-index:100;font-size:10px;color:rgba(255,255,255,0.35);">
        Updated: {{ now('Asia/Dhaka')->format('d-M-Y H:i') }}
    </div>

    <div class="search-panel">
        <input type="text" class="search-input" id="client-search" placeholder="Search zones/boxes...">
    </div>

    <div id="client-network"></div>

    <div class="legend-panel">
        <div class="legend-title">Network Hierarchy</div>
        <div class="legend-items">
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #1a237e, #3949ab);"><i class="fas fa-building text-white"></i></div>
                <span>ISP Core</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #0d47a1, #1976d2);"><i class="fas fa-map-marker-alt text-white"></i></div>
                <span>Zone</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #00695c, #00897b);"><i class="fas fa-layer-group text-white"></i></div>
                <span>Sub Zone</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #ef6c00, #ff9800);"><i class="fas fa-cube text-white"></i></div>
                <span>Box (Clients)</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #2e7d32, #4caf50);"><i class="fas fa-circle text-white"></i></div>
                <span>Client Online</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #424242, #757575);"><i class="fas fa-circle text-white"></i></div>
                <span>Client Offline</span>
            </div>
        </div>
    </div>

    <div class="info-panel">
        <i class="fas fa-info-circle mr-1"></i> 
        Nodes show client counts. Click on any zone/box to view clients in that area.
    </div>

    <div class="view-mode-panel">
        <button class="view-btn active" data-view="hierarchical">Hierarchical</button>
        <button class="view-btn" data-view="radial">Radial</button>
        <button class="view-btn" data-view="free">Free</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/vis-network@9.1.6/standalone/umd/vis-network.min.js"></script>
<script>
var network = null;
var physicsEnabled = true;
var nodeId = 1;
var nodes = [];
var edges = [];

nodes.push({ 
    id: nodeId, 
    label: 'SOFTYNEX\nISP\n({{ number_format($totalClients) }} clients)', 
    group: 'isp', 
    title: 'ISP Core Network\nTotal Clients: {{ number_format($totalClients) }}\nActive: {{ number_format($activeClients) }}\nOnline: {{ number_format($onlineClients) }}' 
});
var ispId = nodeId++;

@php
    $knownZoneIds = $zones->pluck('id')->toArray();
    $knownAreaIds = $zones->flatMap(fn($z) => $z->areas->pluck('id'))->toArray();
@endphp

@foreach($zones as $zone)
var zoneId{{ $zone->id }} = nodeId;
nodes.push({
    id: nodeId,
    label: '{{ addslashes($zone->name) }}\n{{ $zone->active_clients }} clients',
    group: 'zone',
    title: 'Zone: {{ addslashes($zone->name) }}\nTotal: {{ $zone->total_clients }}\nActive: {{ $zone->active_clients }}\nOnline: {{ $zone->online_clients }}',
    url: '{{ route("admin.clients.index") }}?zone={{ $zone->id }}'
});
edges.push({ from: ispId, to: nodeId, color: { color: '#00aaff' }, width: 3 });
nodeId++;

@foreach($zone->areas as $area)
var areaId{{ $area->id }} = nodeId;
nodes.push({
    id: nodeId,
    label: '{{ addslashes($area->name) }}\n{{ $area->active_clients ?? 0 }} clients',
    group: 'area',
    title: 'Sub Zone: {{ addslashes($area->name) }}\nActive: {{ $area->active_clients ?? 0 }}\nOnline: {{ $area->online_clients ?? 0 }}',
    url: '{{ route("admin.clients.index") }}?area={{ $area->id }}'
});
edges.push({ from: zoneId{{ $zone->id }}, to: nodeId, color: { color: '#00897b' }, width: 2 });
nodeId++;
@endforeach
@endforeach

@foreach($boxes as $box)
var boxNodeId{{ $box->id }} = nodeId;
nodes.push({
    id: nodeId,
    label: '{{ addslashes($box->name) }}\n{{ $box->active_clients }}/{{ $box->capacity }}',
    group: '{{ $box->active_clients >= $box->capacity ? "box_full" : ($box->active_clients >= $box->capacity * 0.8 ? "box_warning" : "box") }}',
    title: 'Box: {{ addslashes($box->name) }}\nZone: {{ $box->zone->name ?? "N/A" }}\nSub Zone: {{ $box->area->name ?? "N/A" }}\nLocation: {{ $box->location ?? "N/A" }}\nCapacity: {{ $box->active_clients }}/{{ $box->capacity }}',
    url: '{{ route("admin.clients.index") }}?box={{ $box->id }}'
});
@if($box->area_id && in_array($box->area_id, $knownAreaIds))
edges.push({ from: areaId{{ $box->area_id }}, to: nodeId, color: { color: '#ff9800' }, width: 2, dashes: true });
@elseif($box->zone_id && in_array($box->zone_id, $knownZoneIds))
edges.push({ from: zoneId{{ $box->zone_id }}, to: nodeId, color: { color: '#ff9800' }, width: 2, dashes: true });
@else
edges.push({ from: ispId, to: nodeId, color: { color: '#ff9800' }, width: 1, dashes: true });
@endif
nodeId++;

@foreach($box->clients as $client)
nodes.push({
    id: nodeId,
    label: '{{ addslashes($client->name) }}',
    group: '{{ $client->status == "active" ? ($client->connection_status == "online" ? "client_online" : "client_offline") : "client_inactive" }}',
    title: 'Client: {{ addslashes($client->name) }}\nUsername: {{ $client->username }}\nStatus: {{ ucfirst($client->status) }}\nConnection: {{ ucfirst($client->connection_status ?? "unknown") }}',
    url: '{{ route("admin.clients.show", $client->id) }}'
});
edges.push({ from: boxNodeId{{ $box->id }}, to: nodeId, color: { color: '{{ $client->connection_status == "online" ? "#4caf50" : "#666" }}' }, width: 1 });
nodeId++;
@endforeach
@endforeach

{{-- Clients without box_id: show directly under their area or zone --}}
@foreach($zones as $zone)
    @foreach($zone->areas as $area)
        @php
            $unboxedAreaClients = \App\Models\Client::where('area_id', $area->id)
                ->whereNull('box_id')
                ->where('status', '!=', 'deleted')
                ->select('id','name','username','status','connection_status')
                ->orderBy('name')->limit(25)->get();
        @endphp
        @foreach($unboxedAreaClients as $client)
nodes.push({ id: nodeId, label: '{{ addslashes($client->name) }}', group: '{{ $client->status == "active" ? ($client->connection_status == "online" ? "client_online" : "client_offline") : "client_inactive" }}', title: 'Client: {{ addslashes($client->name) }}\nUsername: {{ $client->username }}\nStatus: {{ ucfirst($client->status) }}\nConnection: {{ ucfirst($client->connection_status ?? "unknown") }}\n(No Box)', url: '{{ route("admin.clients.show", $client->id) }}' });
@if(in_array($area->id, $knownAreaIds))
edges.push({ from: areaId{{ $area->id }}, to: nodeId, color: { color: '{{ $client->connection_status == "online" ? "#4caf50" : "#555" }}' }, width: 1 });
@elseif(in_array($zone->id, $knownZoneIds))
edges.push({ from: zoneId{{ $zone->id }}, to: nodeId, color: { color: '{{ $client->connection_status == "online" ? "#4caf50" : "#555" }}' }, width: 1 });
@else
edges.push({ from: ispId, to: nodeId, color: { color: '#555' }, width: 1 });
@endif
nodeId++;
        @endforeach
    @endforeach
    @php
        $unboxedZoneOnly = \App\Models\Client::where('zone_id', $zone->id)
            ->whereNull('area_id')->whereNull('box_id')
            ->where('status', '!=', 'deleted')
            ->select('id','name','username','status','connection_status')
            ->orderBy('name')->limit(15)->get();
    @endphp
    @foreach($unboxedZoneOnly as $client)
nodes.push({ id: nodeId, label: '{{ addslashes($client->name) }}', group: '{{ $client->status == "active" ? ($client->connection_status == "online" ? "client_online" : "client_offline") : "client_inactive" }}', title: 'Client: {{ addslashes($client->name) }}\nUsername: {{ $client->username }}\nStatus: {{ ucfirst($client->status) }}\nConnection: {{ ucfirst($client->connection_status ?? "unknown") }}\n(Zone only, no area/box)', url: '{{ route("admin.clients.show", $client->id) }}' });
@if(in_array($zone->id, $knownZoneIds))
edges.push({ from: zoneId{{ $zone->id }}, to: nodeId, color: { color: '{{ $client->connection_status == "online" ? "#4caf50" : "#555" }}' }, width: 1 });
@else
edges.push({ from: ispId, to: nodeId, color: { color: '#555' }, width: 1 });
@endif
nodeId++;
    @endforeach
@endforeach

var data = {
    nodes: new vis.DataSet(nodes),
    edges: new vis.DataSet(edges)
};

var groups = {
    isp: {
        shape: 'box',
        color: { background: '#1a237e', border: '#3949ab' },
        font: { color: '#fff', size: 16, face: 'Poppins' },
        borderWidth: 3
    },
    zone: {
        shape: 'box',
        color: { background: '#0d47a1', border: '#1976d2' },
        font: { color: '#fff', size: 14, face: 'Poppins' },
        borderWidth: 2
    },
    area: {
        shape: 'box',
        color: { background: '#00695c', border: '#00897b' },
        font: { color: '#fff', size: 12, face: 'Poppins' },
        borderWidth: 2
    },
    box: {
        shape: 'box',
        color: { background: '#ef6c00', border: '#ff9800' },
        font: { color: '#fff', size: 11, face: 'Poppins' },
        borderWidth: 1
    },
    box_warning: {
        shape: 'box',
        color: { background: '#f57c00', border: '#ffb300' },
        font: { color: '#fff', size: 11, face: 'Poppins' },
        borderWidth: 2
    },
    box_full: {
        shape: 'box',
        color: { background: '#c62828', border: '#e53935' },
        font: { color: '#fff', size: 11, face: 'Poppins' },
        borderWidth: 2
    },
    client_online: {
        shape: 'dot',
        size: 12,
        color: { background: '#4caf50', border: '#2e7d32' },
        font: { color: '#fff', size: 10, face: 'Poppins' },
        borderWidth: 2
    },
    client_offline: {
        shape: 'dot',
        size: 10,
        color: { background: '#757575', border: '#424242' },
        font: { color: '#aaa', size: 10, face: 'Poppins' },
        borderWidth: 1
    },
    client_inactive: {
        shape: 'dot',
        size: 8,
        color: { background: '#f44336', border: '#c62828' },
        font: { color: '#999', size: 9, face: 'Poppins' },
        borderWidth: 1
    }
};

var options = {
    groups: groups,
    layout: {
        hierarchical: {
            enabled: true,
            direction: 'UD',
            sortMethod: 'directed',
            levelSeparation: 120,
            nodeSpacing: 80
        }
    },
    physics: {
        enabled: true,
        hierarchicalRepulsion: {
            centralGravity: 0.0,
            springLength: 100,
            springConstant: 0.01,
            nodeDistance: 100,
            damping: 0.09
        }
    },
    interaction: {
        hover: true,
        tooltipDelay: 100,
        zoomView: true,
        dragView: true
    },
    nodes: {
        shadow: true
    },
    edges: {
        smooth: {
            type: 'cubicBezier',
            forceDirection: 'vertical'
        }
    }
};

var container = document.getElementById('client-network');
network = new vis.Network(container, data, options);

network.once('stabilized', function() {
    network.fit({ animation: { duration: 800, easingFunction: 'easeInOutQuad' } });
    var loader = document.getElementById('diagram-loading');
    if (loader) loader.style.display = 'none';
});
setTimeout(function() {
    var loader = document.getElementById('diagram-loading');
    if (loader) loader.style.display = 'none';
    if (network) network.fit({ animation: false });
}, 5000);

document.getElementById('zoom-in').addEventListener('click', function() {
    var scale = network.getScale() * 1.2;
    network.moveTo({ scale: scale });
});

document.getElementById('zoom-out').addEventListener('click', function() {
    var scale = network.getScale() * 0.8;
    network.moveTo({ scale: scale });
});

document.getElementById('fit-view').addEventListener('click', function() {
    network.fit();
});

document.getElementById('toggle-physics').addEventListener('click', function() {
    physicsEnabled = !physicsEnabled;
    network.setOptions({ physics: { enabled: physicsEnabled } });
    this.classList.toggle('active', physicsEnabled);
});

document.getElementById('fullscreen').addEventListener('click', function() {
    var elem = container.parentElement;
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else {
        elem.requestFullscreen();
    }
});

document.querySelectorAll('.view-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        var view = this.dataset.view;
        if (view === 'hierarchical') {
            network.setOptions({
                layout: { hierarchical: { enabled: true, direction: 'UD' } }
            });
        } else if (view === 'radial') {
            network.setOptions({
                layout: { hierarchical: { enabled: true, direction: 'DU' } }
            });
        } else {
            network.setOptions({
                layout: { hierarchical: { enabled: false } }
            });
        }
    });
});

var searchInput = document.getElementById('client-search');
searchInput.addEventListener('input', function() {
    var query = this.value.toLowerCase();
    if (query.length > 0) {
        var matchingNodes = nodes.filter(function(node) {
            return node.label.toLowerCase().includes(query) || 
                   (node.title && node.title.toLowerCase().includes(query));
        });
        if (matchingNodes.length > 0) {
            network.selectNodes(matchingNodes.map(n => n.id));
            network.focus(matchingNodes[0].id, { scale: 1.5, animation: true });
        }
    } else {
        network.unselectAll();
        network.fit();
    }
});

network.on('click', function(params) {
    if (params.nodes.length > 0) {
        var nodeId = params.nodes[0];
        var node = nodes.find(n => n.id === nodeId);
        if (node && node.url) {
            window.location.href = node.url;
        }
    }
});
</script>
@endpush
