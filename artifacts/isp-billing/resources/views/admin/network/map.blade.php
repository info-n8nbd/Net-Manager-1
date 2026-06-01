@extends('layouts.admin')
@section('title', 'Network Map')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">Network</a></li>
<li class="breadcrumb-item active">Network Map</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<script src="https://unpkg.com/leaflet-polylinedecorator@1.6.0/dist/leaflet.polylineDecorator.js"></script>
<style>
/* ── Layout ── */
#network-map { height: calc(100vh - 320px); min-height: 500px; width: 100%; }
.map-card { border-radius:10px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.1); border:none; }

/* ── Stat Cards ── */
.stat-mini { border-radius:8px; border:none; box-shadow:0 2px 6px rgba(0,0,0,.1); transition:transform .15s; }
.stat-mini:hover { transform:translateY(-2px); }
.stat-mini .info-box { margin:0; border-radius:8px; }

/* ── Map Tabs ── */
.map-tabs { background:#fff; border-bottom:1px solid #dee2e6; padding:0 14px; display:flex; gap:4px; align-items:flex-end; }
.map-tab { padding:10px 18px; font-size:13px; font-weight:600; cursor:pointer; border:none; background:none;
           border-bottom:3px solid transparent; color:#6c757d; transition:all .2s; white-space:nowrap; }
.map-tab:hover { color:#495057; background:#f8f9fa; border-radius:6px 6px 0 0; }
.map-tab.active { color:#007bff; border-bottom-color:#007bff; }
.map-tab.tab-device.active { color:#17a2b8; border-bottom-color:#17a2b8; }
.map-tab.tab-client.active { color:#6f42c1; border-bottom-color:#6f42c1; }
.map-tab .tab-count { display:inline-block; background:#e9ecef; color:#495057; border-radius:10px;
                      font-size:10px; padding:1px 6px; margin-left:5px; font-weight:700; }
.map-tab.active .tab-count { background:currentColor; color:#fff; opacity:.85; }

/* ── Toolbar ── */
.map-toolbar { background:#fff; padding:8px 14px; border-bottom:1px solid #eee; display:flex; flex-wrap:wrap; align-items:center; gap:6px; }
.map-toolbar .btn { border-radius:20px; font-size:12px; font-weight:600; padding:3px 12px; }

/* ── Search ── */
.search-box { position:relative; min-width:230px; }
.search-box input { border-radius:20px; padding-left:30px; padding-right:28px; font-size:12px;
    border:2px solid #dee2e6; transition:border-color .2s; }
.search-box input:focus { border-color:#1565C0; box-shadow:0 0 0 3px rgba(21,101,192,.15); outline:none; }
.search-box .fa-search { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#aaa; pointer-events:none; font-size:11px; }
.search-box .s-clear { position:absolute; right:8px; top:50%; transform:translateY(-50%);
    background:none; border:none; color:#aaa; cursor:pointer; font-size:11px; padding:0; display:none; }
.search-box .s-clear:hover { color:#333; }
#searchResults { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border-radius:10px;
    box-shadow:0 6px 24px rgba(0,0,0,.18); z-index:9999; max-height:340px; overflow-y:auto; display:none;
    border:1px solid #e0e0e0; }
#searchResults .sr-hdr { padding:5px 12px; font-size:10px; font-weight:700; color:#888;
    text-transform:uppercase; background:#f8f9fa; border-bottom:1px solid #eee; letter-spacing:.5px; }
#searchResults .sri { padding:7px 12px; cursor:pointer; font-size:12px; display:flex; align-items:center;
    gap:8px; border-bottom:1px solid #f5f5f5; transition:background .1s; }
#searchResults .sri:hover, #searchResults .sri.kbactive { background:#e8f4fd; }
#searchResults .sri .sri-main { flex:1; min-width:0; }
#searchResults .sri .sri-name { font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
#searchResults .sri .sri-path { font-size:10px; color:#888; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:1px; }
#searchResults .sri .sri-st   { font-size:9px; padding:1px 6px; border-radius:8px; font-weight:700; flex-shrink:0; }
#searchResults .sri .sri-nogps{ font-size:9px; color:#dc3545; flex-shrink:0; }
#searchResults .sr-empty { padding:12px; text-align:center; color:#aaa; font-size:12px; }

/* ── Popup ── */
.leaflet-popup-content-wrapper { border-radius:10px; box-shadow:0 4px 18px rgba(0,0,0,.18); }
.leaflet-popup-content { font-size:13px; min-width:220px; padding:2px; }
.popup-header { font-weight:700; font-size:14px; margin-bottom:5px; display:flex; align-items:center; gap:6px; }
.popup-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; margin-bottom:3px; }
.popup-row { display:flex; justify-content:space-between; padding:2px 0; border-bottom:1px solid #f5f5f5; }
.popup-row:last-child { border-bottom:none; }
.popup-label { color:#888; font-size:11px; }
.popup-val { font-weight:600; font-size:12px; }
.popup-actions { margin-top:8px; display:flex; gap:5px; flex-wrap:wrap; }
.popup-actions .btn { border-radius:6px; font-size:11px; padding:3px 9px; }
#placementBanner { display:none; position:fixed; top:0; left:0; right:0; z-index:10000;
    background:linear-gradient(135deg,#e65100,#f57c00); color:#fff;
    padding:12px 20px; font-size:13px; font-weight:700;
    box-shadow:0 3px 12px rgba(0,0,0,.35); text-align:center; }
#placementBanner .cancel-btn { background:rgba(0,0,0,.25); border:none; color:#fff;
    padding:4px 14px; border-radius:20px; cursor:pointer; font-size:12px; margin-left:12px; }
.placement-cursor, .placement-cursor .leaflet-interactive { cursor:crosshair !important; }
.conn-info { background:#f8f9fa; border-radius:6px; padding:6px 8px; margin-top:6px; font-size:11px; }
.conn-info-title { font-weight:700; font-size:11px; color:#555; margin-bottom:3px; }
.conn-device { display:flex; align-items:center; gap:5px; padding:2px 0; }

/* ── Legend ── */
.legend { background:#fff; padding:10px 14px; border-radius:8px; box-shadow:0 1px 5px rgba(0,0,0,.25); font-size:12px; line-height:1.6; min-width:190px; }

/* ── Connection lines highlight ── */
.conn-line-active { animation: dashMove 0.5s linear infinite; }
@keyframes dashMove { to { stroke-dashoffset: -20; } }

/* ── View badge ── */
.view-badge { position:absolute; top:10px; left:50px; z-index:1000; background:rgba(255,255,255,.92);
              padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; box-shadow:0 1px 5px rgba(0,0,0,.2); }

/* Animated flow lines */
@keyframes flowDash { to { stroke-dashoffset: -24; } }
.conn-line-active { stroke-dasharray: 8,6 !important; animation: flowDash 0.8s linear infinite; }
/* Network Tree Panel */
#netTreePanel { position:absolute; right:0; top:0; bottom:0; width:270px;
    background:rgba(255,255,255,.97); border-left:2px solid #dee2e6; z-index:1001;
    overflow-y:auto; transform:translateX(100%); transition:transform .3s ease;
    box-shadow:-4px 0 16px rgba(0,0,0,.12); }
#netTreePanel.open { transform:translateX(0); }
#netTreePanel .tree-hdr { background:#1565C0; color:#fff; padding:8px 12px;
    font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:space-between; }
#netTreePanel .tree-body { padding:8px; font-size:11px; }
.tree-node { padding:4px 6px; border-radius:5px; margin:2px 0; cursor:pointer;
    display:flex; align-items:center; gap:6px; transition:background .15s; }
.tree-node:hover { background:#f0f4ff; }
.tree-node.router-node   { background:#e3f2fd; border-left:3px solid #1565C0; }
.tree-node.olt-node      { background:#fff8e1; border-left:3px solid #f57c00; }
.tree-node.splitter-node { background:#fce4ec; border-left:3px solid #c2185b; }
.tree-node.client-node   { background:#f1f8e9; border-left:3px solid #388e3c; }
.tree-node.client-off    { background:#fbe9e7; border-left:3px solid #c62828; }
.tree-indent { margin-left:14px; border-left:2px dashed #ddd; padding-left:6px; }
.lbadge { font-size:9px; padding:1px 5px; border-radius:8px; font-weight:700; white-space:nowrap; }
.lbadge.bb   { background:#1565C0; color:#fff; }
.lbadge.fd   { background:#0288d1; color:#fff; }
.lbadge.ds   { background:#e65100; color:#fff; }
.lbadge.lm   { background:#2e7d32; color:#fff; }
.lbadge.dt   { background:#6a1b9a; color:#fff; }
/* Legend */
#mapLegend { position:absolute; bottom:40px; left:10px; z-index:1000;
    background:rgba(255,255,255,.95); border-radius:8px; padding:8px 12px;
    font-size:10px; box-shadow:0 2px 8px rgba(0,0,0,.2); border:1px solid #dee2e6; }
#mapLegend .leg-row { display:flex; align-items:center; gap:6px; margin:2px 0; }
#mapLegend .leg-ln  { width:28px; height:3px; border-radius:2px; flex-shrink:0; }
#showAllBtn.active  { background:#1565C0 !important; color:#fff !important; }
</style>

@endpush

@section('content')
<div class="container-fluid">

    {{-- Stats Row --}}
    <div class="row mb-3">
        <div class="col-6 col-md-2 mb-2">
            <div class="stat-mini">
                <div class="info-box bg-info mb-0">
                    <span class="info-box-icon" style="font-size:18px;"><i class="fas fa-server"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text" style="font-size:10px;">Routers</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $devices->count() }}</span>
                        <span class="info-box-text" style="font-size:10px;">{{ $devices->where('status','online')->count() }} online</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="stat-mini">
                <div class="info-box bg-warning mb-0">
                    <span class="info-box-icon" style="font-size:18px;"><i class="fas fa-broadcast-tower"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text" style="font-size:10px;">OLT Devices</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $olts->count() }}</span>
                        <span class="info-box-text" style="font-size:10px;">{{ $olts->where('status','online')->count() }} online</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="stat-mini">
                <div class="info-box mb-0" style="background:#fd7e14;">
                    <span class="info-box-icon" style="font-size:18px;"><i class="fas fa-code-branch"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text" style="font-size:10px;">Splitters</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $splitters->count() }}</span>
                        <span class="info-box-text" style="font-size:10px;">{{ $stats['splitters'] ?? 0 }} on map</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="stat-mini">
                <div class="info-box bg-primary mb-0">
                    <span class="info-box-icon" style="font-size:18px;"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text" style="font-size:10px;">Clients on Map</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $clientCount }}</span>
                        <span class="info-box-text" style="font-size:10px;">of {{ \App\Models\Client::where('status','!=','deleted')->count() }} total</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="stat-mini">
                <div class="info-box bg-success mb-0">
                    <span class="info-box-icon" style="font-size:18px;"><i class="fas fa-wifi"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text" style="font-size:10px;">Online Clients</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $stats['online_clients'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="stat-mini">
                <div class="info-box bg-danger mb-0">
                    <span class="info-box-icon" style="font-size:18px;"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text" style="font-size:10px;">Offline Routers</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $devices->where('status','offline')->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Map Card --}}
    <div class="card map-card">
        {{-- Header --}}
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:8px; padding:10px 14px;">
            <h3 class="card-title mb-0" style="font-size:15px;"><i class="fas fa-map-marked-alt mr-2 text-primary"></i>Network Infrastructure Map</h3>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="mapSearch" class="form-control form-control-sm"
                        placeholder="Name, username, IP, phone..." autocomplete="off" spellcheck="false">
                    <button class="s-clear" id="sClear" onclick="clearMapSearch()" title="Clear"><i class="fas fa-times"></i></button>
                    <div id="searchResults"></div>
                </div>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="cableToggle" checked>
                    <label class="custom-control-label" for="cableToggle" style="font-size:11px;">Cables</label>
                </div>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="clusterToggle" checked>
                    <label class="custom-control-label" for="clusterToggle" style="font-size:11px;">Cluster</label>
                </div>
            </div>
        </div>

        {{-- Map View Tabs --}}
        <div class="map-tabs">
            <button class="map-tab tab-full active" onclick="switchView('full', this)">
                <i class="fas fa-globe mr-1"></i>Full Network
                <span class="tab-count" id="cnt-full">0</span>
            </button>
            <button class="map-tab tab-device" onclick="switchView('device', this)">
                <i class="fas fa-server mr-1"></i>Device Map
                <span class="tab-count" id="cnt-device">0</span>
            </button>
            <button class="map-tab tab-client" onclick="switchView('client', this)">
                <i class="fas fa-users mr-1"></i>Client Map
                <span class="tab-count" id="cnt-client">0</span>
            </button>
            <div class="ml-auto d-flex align-items-center pb-1" style="gap:6px;">
                <select id="tileSelect" class="form-control form-control-sm" style="width:135px;font-size:11px;border-radius:20px;" onchange="changeTile(this.value)">
                    <option value="osm">OpenStreetMap</option>
                    <option value="satellite">Satellite</option>
                    <option value="topo">Topo</option>
                </select>
                <button class="btn btn-outline-secondary btn-sm" onclick="fitAll()" title="Fit all to view" style="border-radius:20px;font-size:11px;">
                    <i class="fas fa-expand-arrows-alt"></i>
                </button>
            </div>
        </div>

        {{-- Filter toolbar (hidden on client-only view) --}}
        <div class="map-toolbar" id="deviceFilterBar">
            <small class="text-muted font-weight-bold mr-1" style="font-size:11px;">STATUS:</small>
            <button class="btn btn-success btn-sm" data-sf="online" onclick="statusFilter(this,'online')">
                <i class="fas fa-circle mr-1" style="font-size:7px;"></i>Online
            </button>
            <button class="btn btn-outline-danger btn-sm" data-sf="offline" onclick="statusFilter(this,'offline')">
                <i class="fas fa-circle mr-1" style="font-size:7px;"></i>Offline
            </button>
            <button class="btn btn-outline-secondary btn-sm" data-sf="all" onclick="statusFilter(this,'all')">
                All Status
            </button>
            <div class="ml-3 border-left pl-3 d-flex" style="gap:4px;">
                <small class="text-muted font-weight-bold mr-1" style="font-size:11px;">TYPE:</small>
                <button class="btn btn-outline-info btn-sm" data-tf="router" onclick="typeFilter(this,'router')"><i class="fas fa-server mr-1"></i>Routers</button>
                <button class="btn btn-outline-warning btn-sm" data-tf="olt" onclick="typeFilter(this,'olt')"><i class="fas fa-broadcast-tower mr-1"></i>OLT</button>
                <button class="btn btn-outline-secondary btn-sm" data-tf="splitter" onclick="typeFilter(this,'splitter')"><i class="fas fa-code-branch mr-1"></i>Splitters</button>
            </div>
            <div class="ml-auto d-flex" style="gap:5px;">
                <button class="btn btn-outline-primary btn-sm" id="showAllBtn" onclick="toggleShowAll(this)" title="Show All Chains" style="border-radius:20px;font-size:12px;">
                    <i class="fas fa-project-diagram mr-1"></i>Show All
                </button>
                <button class="btn btn-outline-info btn-sm" id="treeBtn" onclick="toggleTree()" title="Network Tree" style="border-radius:20px;font-size:12px;">
                    <i class="fas fa-sitemap mr-1"></i>Tree
                </button>
                <button class="btn btn-outline-dark btn-sm" onclick="clearConnections()" id="clearConnBtn" style="display:none;border-radius:20px;">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>
        </div>
        <div class="map-toolbar" id="clientFilterBar" style="display:none;">
            <small class="text-muted font-weight-bold mr-1" style="font-size:11px;">CLIENT STATUS:</small>
            <button class="btn btn-success btn-sm" onclick="clientStatusFilter(this,'online')"><i class="fas fa-circle mr-1" style="font-size:7px;"></i>Online</button>
            <button class="btn btn-outline-secondary btn-sm" onclick="clientStatusFilter(this,'offline')"><i class="fas fa-circle mr-1" style="font-size:7px;"></i>Offline</button>
            <button class="btn btn-outline-warning btn-sm" onclick="clientStatusFilter(this,'inactive')">Inactive</button>
            <button class="btn btn-outline-danger btn-sm" onclick="clientStatusFilter(this,'expired')">Expired</button>
            <button class="btn btn-outline-primary btn-sm" onclick="clientStatusFilter(this,'all')">All</button>
            <button class="btn btn-outline-dark btn-sm ml-auto" onclick="clearConnections()" id="clearConnBtn2" style="display:none;border-radius:20px;">
                <i class="fas fa-times mr-1"></i>Clear Lines
            </button>
        </div>

        <div class="card-body p-0" style="position:relative;">
            <div class="view-badge" id="viewBadge"><i class="fas fa-globe mr-1"></i>Full Network Map</div>
            <div id="network-map"></div>
<div id="placementBanner"><i class="fas fa-map-marker-alt mr-2"></i><span id="placementMsg">Click on the map to place device</span><button class="cancel-btn" onclick="cancelPlacement()"><i class="fas fa-times mr-1"></i>Cancel (Esc)</button></div>
        <!-- Network Tree Panel -->
        <div id="netTreePanel">
            <div class="tree-hdr"><span><i class="fas fa-sitemap mr-2"></i>Network Tree</span><button onclick="toggleTree()" style="background:none;border:none;color:#fff;cursor:pointer;"><i class="fas fa-times"></i></button></div>
            <div class="tree-body" id="netTreeBody"><div class="text-muted text-center mt-3" style="font-size:11px;"><i class="fas fa-mouse-pointer fa-2x mb-2 d-block"></i>Click a device or "Show All"</div></div>
        </div>
        <!-- Legend -->
        <div id="mapLegend">
            <div style="font-weight:700;color:#333;margin-bottom:4px;"><i class="fas fa-info-circle mr-1"></i>Connection Types</div>
            <div class="leg-row"><div class="leg-ln" style="background:#1565C0;"></div><span>Backbone (Router&#8596;OLT)</span></div>
            <div class="leg-row"><div class="leg-ln" style="background:#0288d1;height:2.5px;"></div><span>Feeder (OLT&#8594;Splitter)</span></div>
            <div class="leg-row"><div class="leg-ln" style="background:repeating-linear-gradient(90deg,#e65100 0,#e65100 4px,transparent 4px,transparent 8px);"></div><span>Distribution</span></div>
            <div class="leg-row"><div class="leg-ln" style="background:#2e7d32;height:1.5px;"></div><span>Last Mile (Online)</span></div>
            <div class="leg-row"><div class="leg-ln" style="background:#c62828;height:1.5px;"></div><span>Last Mile (Offline)</span></div>
            <div class="leg-row"><div class="leg-ln" style="background:#6a1b9a;height:2px;"></div><span>Direct OLT&#8594;Client</span></div>
        </div>
        </div>
    </div>

    @php
    $noGpsItems = [];
    foreach($devices as $d)   { if(!$d->latitude||!$d->longitude)   $noGpsItems[]=(['t'=>'router',   'id'=>$d->id,'n'=>$d->name]); }
    foreach($olts as $o)      { if(!$o->latitude||!$o->longitude)   $noGpsItems[]=(['t'=>'olt',      'id'=>$o->id,'n'=>$o->name]); }
    foreach($splitters as $s) { if(!$s->latitude||!$s->longitude)   $noGpsItems[]=(['t'=>'splitter', 'id'=>$s->id,'n'=>$s->name]); }
    @endphp
    @if(count($noGpsItems) > 0)
    <div class="alert alert-warning mt-3 mb-0" id="noGpsAlert">
        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:6px;">
            <div>
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>{{ count($noGpsItems) }} device(s) have no GPS</strong>
                — click their button to place on map and enable full connection chain:
            </div>
            <div class="d-flex flex-wrap" style="gap:4px;">
                @foreach($noGpsItems as $nd)
                <button class="btn btn-sm btn-warning"
                    data-gpsbtn="{{ $nd['t'] }}_{{ $nd['id'] }}"
                    onclick="enterPlacementMode('{{ $nd['t'] }}', {{ $nd['id'] }}, '{{ addslashes($nd['n']) }}', event)"
                    style="font-size:11px;">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    {{ strtoupper($nd['t']) }}: {{ $nd['n'] }}
                </button>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @if(!$hasCoordinates)
    <div class="alert alert-info mt-3">
        <i class="fas fa-info-circle mr-2"></i>
        No devices have GPS yet. Use the buttons above to place them on the map.
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
var mapData = {!! json_encode($mapMarkers) !!};

/* ═══════════════════════════════════════════════════
   TILE LAYERS
═══════════════════════════════════════════════════ */
var tileLayers = {
    osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap contributors',maxZoom:19}),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{attribution:'Esri, DigitalGlobe',maxZoom:18}),
    topo: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenTopoMap',maxZoom:17})
};
var map = L.map('network-map',{center:[23.8103,90.4125],zoom:7,layers:[tileLayers.osm]});
function changeTile(k){Object.values(tileLayers).forEach(function(l){map.removeLayer(l);});tileLayers[k].addTo(map);}

/* ═══════════════════════════════════════════════════
   PIN ICONS
═══════════════════════════════════════════════════ */
function makeIcon(type, st) {
    var online = st==='online'||st==='active';
    var offline = st==='offline'||st==='inactive';
    var fill, border, inner, sz;
    switch(type){
        case 'router':
            fill=online?'#28a745':(offline?'#dc3545':'#6c757d');
            border=online?'#1a6e30':(offline?'#9b1c1c':'#444');
            inner='<text x="16" y="20" text-anchor="middle" fill="#fff" font-size="11" font-weight="900" font-family="Arial">R</text>';
            sz=28; break;
        case 'olt':
            fill=online?'#0d6efd':'#6c757d';
            border=online?'#0043b8':'#444';
            inner='<text x="16" y="19" text-anchor="middle" fill="#fff" font-size="9" font-weight="900" font-family="Arial">OLT</text>';
            sz=30; break;
        case 'splitter':
            fill='#fd7e14'; border='#b35300';
            inner='<text x="16" y="19" text-anchor="middle" fill="#fff" font-size="9" font-weight="900" font-family="Arial">SPL</text>';
            sz=25; break;
        default: // client
            fill=online?'#0dcaf0':(st==='inactive'||st==='expired'?'#ffc107':'#adb5bd');
            border=online?'#0a7a96':(st==='inactive'||st==='expired'?'#c07a00':'#666');
            inner='<circle cx="16" cy="12" r="4" fill="#fff" opacity="0.9"/><path d="M8 22 Q16 16 24 22" stroke="#fff" stroke-width="2" fill="none"/>';
            sz=20;
    }
    var h=Math.round(sz*1.35);
    var svg='<svg xmlns="http://www.w3.org/2000/svg" width="'+sz+'" height="'+h+'" viewBox="0 0 32 43">'
        +'<path d="M16 1 C8.268 1 2 7.268 2 15 C2 24.5 16 42 16 42 C16 42 30 24.5 30 15 C30 7.268 23.732 1 16 1 Z" fill="'+fill+'" stroke="'+border+'" stroke-width="2"/>'
        +inner+'</svg>';
    return L.divIcon({html:'<div style="filter:drop-shadow(0 3px 5px rgba(0,0,0,.45));">'+svg+'</div>',className:'',iconSize:[sz,h],iconAnchor:[sz/2,h],popupAnchor:[0,-h]});
}

/* ═══════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════ */
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmtUptime(s){if(!s||s<1)return null;var d=Math.floor(s/86400),h=Math.floor((s%86400)/3600),m=Math.floor((s%3600)/60);var p=[];if(d)p.push(d+'d');if(h)p.push(h+'h');if(m)p.push(m+'m');return p.length?p.join(' '):'<1m';}
function fmtBytes(b){if(!b||b<1)return null;if(b>=1073741824)return (b/1073741824).toFixed(1)+' GB';if(b>=1048576)return (b/1048576).toFixed(1)+' MB';return (b/1024).toFixed(0)+' KB';}
function fmtSpeed(b){if(!b||b<1)return null;if(b>=1000000)return (b/1000000).toFixed(1)+' Mbps';if(b>=1000)return (b/1000).toFixed(0)+' Kbps';return b+' bps';}
function row(label,val,color){if(val===null||val===undefined||val==='')return '';var s=color?'color:'+color+';font-weight:700;':'';return '<div class="popup-row"><span class="popup-label">'+label+'</span><span class="popup-val" style="'+s+'">'+val+'</span></div>';}
function badge(t,bg,fg){return '<span class="popup-badge" style="background:'+bg+';color:'+fg+';">'+t+'</span>';}

/* ═══════════════════════════════════════════════════
   BUILD LOOKUP MAPS for connections
═══════════════════════════════════════════════════ */
var byId={}, byType={router:[],olt:[],splitter:[],client:[]};
mapData.forEach(function(d){
    byId[d.type+'_'+d.id]=d;
    if(byType[d.type]) byType[d.type].push(d);
});

/* ═══════════════════════════════════════════════════
   POPUP BUILDER
═══════════════════════════════════════════════════ */
function buildPopup(item) {
    var tColors={router:'#17a2b8',olt:'#0d6efd',splitter:'#fd7e14',client:'#6f42c1'};
    var tIcons ={router:'server',olt:'broadcast-tower',splitter:'code-branch',client:'user'};
    var tc=tColors[item.type]||'#888', ti=tIcons[item.type]||'map-marker-alt';
    var h='<div style="min-width:220px;padding:4px 2px;">';
    h+='<div class="popup-header"><i class="fas fa-'+ti+'" style="color:'+tc+'"></i> '+esc(item.name)+'</div>';

    if(item.type==='router'){
        var on=item.status==='online';
        h+=badge(item.is_active?'Active':'Disabled',item.is_active?'#d4edda':'#f8d7da',item.is_active?'#155724':'#721c24')+' ';
        h+=badge(on?'● Online':'● Offline',on?'#d4edda':'#f8d7da',on?'#155724':'#721c24')+'<br>';
        h+=row('IP',esc(item.host));
        h+=row('Zone',esc(item.zone));
        h+=row('Type',(item.router_type||'core').toUpperCase());
        var up=item.uptime||(item.uptime_seconds?fmtUptime(item.uptime_seconds):null);
        h+=row('Uptime',up||'—',on?'#28a745':null);
        if(item.cpu!=null) h+=row('CPU Load',item.cpu+'%',item.cpu>80?'#dc3545':item.cpu>60?'#fd7e14':null);
        if(item.memory!=null) h+=row('Memory',item.memory+'%',item.memory>85?'#dc3545':null);
        h+=row('Active Users',item.active_users>0?'<b style="color:#0d6efd;">'+item.active_users+'</b>':'0');
        h+=row('Total Clients',item.total_clients||0);
        if(on&&item.last_up_at) h+=row('Online since',esc(item.last_up_at));
        if(!on&&item.last_down_at) h+=row('Down since',esc(item.last_down_at),'#dc3545');
        if(item.last_checked_at) h+=row('Last checked',esc(item.last_checked_at));
        // Downlink summary
        var connClients=byType.client.filter(function(c){return c.router_id==item.id&&c.lat&&c.lng;});
        if(connClients.length>0){
            h+='<div class="conn-info"><div class="conn-info-title"><i class="fas fa-arrow-down text-success mr-1"></i>Downlink: '+connClients.length+' clients connected</div></div>';
        }
        h+='<div class="popup-actions">';
        if(item.detail_url) h+='<a href="'+item.detail_url+'" class="btn btn-info btn-sm"><i class="fas fa-chart-line mr-1"></i>Details</a>';
        h+='<button class="btn btn-success btn-sm" onclick="showConnections(\'router\','+item.id+',event)"><i class="fas fa-project-diagram mr-1"></i>Show Connections</button>';
        h+='<button class="btn btn-outline-secondary btn-sm" onclick="enterPlacementMode(\'router\','+item.id+',\''+esc(item.name)+'\',event)"><i class="fas fa-map-marker-alt"></i> Move</button>';
        h+='</div>';

    } else if(item.type==='olt'){
        var on=(item.status==='online'||item.status==='active');
        h+=badge(item.is_active?'Active':'Disabled',item.is_active?'#d4edda':'#f8d7da',item.is_active?'#155724':'#721c24')+' ';
        h+=badge(on?'● Online':'● Offline',on?'#d4edda':'#e2e3e5',on?'#155724':'#383d41')+' ';
        h+=badge((item.pon_type||'EPON').toUpperCase(),'#e8f4fd','#0c5460')+'<br>';
        h+=row('IP',esc(item.ip_address));
        h+=row('Vendor',esc(item.vendor));
        h+=row('Model',esc(item.model));
        h+=row('Total Ports',item.total_ports);
        var oltUp=item.uptime_seconds?fmtUptime(item.uptime_seconds):null;
        h+=row('Uptime',oltUp||'—',on?'#28a745':null);
        if(item.cpu) h+=row('CPU',item.cpu+'%',item.cpu>80?'#dc3545':null);
        if(item.memory) h+=row('Memory',item.memory+'%');
        if(item.temperature) h+=row('Temp',item.temperature+'°C',item.temperature>70?'#dc3545':null);
        if(item.firmware) h+=row('Firmware',esc(item.firmware));
        if(item.last_sync) h+=row('Last Sync',esc(item.last_sync));
        var connSpl=byType.splitter.filter(function(s){return s.olt_id==item.id&&s.lat&&s.lng;});
        var connCli=byType.client.filter(function(c){return c.olt_id==item.id&&c.lat&&c.lng;});
        if(connSpl.length||connCli.length){
            h+='<div class="conn-info">';
            h+='<div class="conn-info-title"><i class="fas fa-arrow-down text-primary mr-1"></i>Downlink Connections</div>';
            if(connSpl.length) h+='<div class="conn-device"><i class="fas fa-code-branch" style="color:#fd7e14;"></i> '+connSpl.length+' Splitters</div>';
            if(connCli.length) h+='<div class="conn-device"><i class="fas fa-users" style="color:#6f42c1;"></i> '+connCli.length+' Clients</div>';
            h+='</div>';
        }
        h+='<div class="popup-actions">';
        if(item.detail_url) h+='<a href="'+item.detail_url+'" class="btn btn-warning btn-sm text-dark"><i class="fas fa-tachometer-alt mr-1"></i>Dashboard</a>';
        h+='<button class="btn btn-primary btn-sm" onclick="showConnections(\'olt\','+item.id+',event)"><i class="fas fa-project-diagram mr-1"></i>Show Connections</button>';
        h+='<button class="btn btn-outline-secondary btn-sm" onclick="enterPlacementMode(\'olt\','+item.id+',\''+esc(item.name)+'\',event)"><i class="fas fa-map-marker-alt"></i> Move</button>';
        h+='</div>';

    } else if(item.type==='splitter'){
        h+=badge('Passive Splitter','#fff3cd','#856404')+' ';
        h+=badge(item.stype||'1:8','#e8f4fd','#0c5460')+'<br>';
        h+=row('Location',esc(item.location));
        // Build splitter uplink chain (walk parent_splitter_id)
        var splChain=[];
        var curSplId=item.parent_splitter_id;
        var walkDepth=0;
        while(curSplId&&walkDepth<10){
            var p=byId['splitter_'+curSplId]; if(!p) break;
            splChain.push(p); curSplId=p.parent_splitter_id||null; walkDepth++;
        }
        var rootSpl=splChain.length?splChain[splChain.length-1]:item;
        var parentOlt=byId['olt_'+item.olt_id]||(rootSpl.olt_id?byId['olt_'+rootSpl.olt_id]:null);
        // Show uplink path as tree
        if(splChain.length||parentOlt){
            h+='<div class="conn-info"><div class="conn-info-title"><i class="fas fa-arrow-up text-warning mr-1"></i>Uplink Path</div>';
            h+='<div style="padding:2px 8px 4px;">';
            if(parentOlt){
                var oltSt=parentOlt.status==='online'||parentOlt.status==='active';
                h+='<div class="conn-device"><i class="fas fa-broadcast-tower" style="color:#0288d1;"></i>'
                 +' <b>'+esc(parentOlt.name)+'</b>'
                 +'<span style="font-size:9px;background:#e8f4fd;color:#0c5460;padding:1px 5px;border-radius:8px;margin-left:4px;">OLT</span>'
                 +(parentOlt.has_coords===false?'<span style="font-size:9px;color:#ccc;"> ⚠ no GPS</span>':'')
                 +'</div>';
                h+='<div style="border-left:2px solid #0288d1;height:8px;margin-left:6px;"></div>';
            }
            splChain.slice().reverse().forEach(function(ps,i){
                var indent=(i*6)+2;
                h+='<div class="conn-device" style="margin-left:'+indent+'px;">'
                 +'<i class="fas fa-code-branch" style="color:#e65100;"></i>'
                 +' '+esc(ps.name)
                 +'<span style="font-size:9px;background:#fff3cd;color:#856404;padding:1px 5px;border-radius:8px;margin-left:4px;">DISTRIB</span>'
                 +(ps.lat&&ps.lng?'':'<span style="font-size:9px;color:#ccc;"> ⚠ no GPS</span>')
                 +'</div>';
                h+='<div style="border-left:2px dashed #e65100;height:8px;margin-left:'+(indent+6)+'px;"></div>';
            });
            h+='<div class="conn-device" style="margin-left:'+((splChain.length)*6+2)+'px;">'
             +'<i class="fas fa-code-branch" style="color:#e65100;font-weight:900;"></i>'
             +' <b>'+esc(item.name)+'</b>'
             +'<span style="font-size:9px;background:#fd7e14;color:#fff;padding:1px 5px;border-radius:8px;margin-left:4px;">THIS</span>'
             +'</div>';
            h+='</div></div>';
        }
        // Child splitters
        var childSpls=byType.splitter.filter(function(s){return s.parent_splitter_id==item.id;});
        if(childSpls.length){
            h+='<div class="conn-info"><div class="conn-info-title"><i class="fas fa-code-branch text-warning mr-1"></i>'+childSpls.length+' Child Splitter(s)</div>';
            h+='<div style="padding:2px 8px 4px;">';
            childSpls.forEach(function(cs){
                h+='<div class="conn-device"><i class="fas fa-code-branch" style="color:#e65100;"></i> '+esc(cs.name)
                 +(cs.lat&&cs.lng?'':'<span style="font-size:9px;color:#ccc;"> ⚠ no GPS</span>')
                 +'</div>';
            });
            h+='</div></div>';
        }
        var connCli=byType.client.filter(function(c){return c.splitter_id==item.id&&c.lat&&c.lng;});
        var onCli=connCli.filter(function(c){return c.connection_status==='online';}).length;
        if(connCli.length){
            h+='<div class="conn-info"><div class="conn-info-title"><i class="fas fa-users text-success mr-1"></i>'+connCli.length+' Clients'+(onCli?' ('+onCli+' online)':'')+'</div></div>';
        }
        h+='<div class="popup-actions"><button class="btn btn-warning btn-sm text-dark" onclick="showConnections(\'splitter\','+item.id+',event)"><i class="fas fa-project-diagram mr-1"></i>Show Full Chain</button> <button class="btn btn-outline-secondary btn-sm" onclick="enterPlacementMode(\'splitter\','+item.id+',\''+esc(item.name)+'\',event)"><i class="fas fa-map-marker-alt"></i> Move</button></div>';

        } else if(item.type==='client'){
        var isOn=item.connection_status==='online';
        var isAct=item.status==='active', isExp=item.status==='expired', isInact=item.status==='inactive';
        h+=badge(isOn?'● Online':'● Offline',isOn?'#d4edda':'#e2e3e5',isOn?'#155724':'#383d41')+' ';
        h+=badge(isAct?'Active':isExp?'Expired':isInact?'Inactive':esc(item.status),
                 isAct?'#cce5ff':isExp?'#f8d7da':isInact?'#fff3cd':'#e2e3e5',
                 isAct?'#004085':isExp?'#721c24':isInact?'#856404':'#383d41')+'<br>';
        h+=row('Username','<b>'+esc(item.username)+'</b>');
        h+=row('Phone',esc(item.phone));
        h+=row('Zone / Area',esc(item.zone)+' / '+esc(item.area));
        var up=item.session_uptime||(item.uptime_seconds?fmtUptime(item.uptime_seconds):null);
        if(up) h+=row('Session Uptime','<b style="color:#28a745;">'+esc(up)+'</b>');
        if(item.rx_speed||item.tx_speed) h+=row('Speed ↓/↑',(fmtSpeed(item.rx_speed)||'—')+' / '+(fmtSpeed(item.tx_speed)||'—'),'#0d6efd');
        var today=fmtBytes(item.today_usage);
        if(today) h+=row('Today Usage',today);
        if(item.expire_date) h+=row('Expires',esc(item.expire_date),isExp?'#dc3545':null);
        if(item.last_seen_at) h+=row('Last Seen',esc(item.last_seen_at));
        // Uplink info
        var upRouter=item.router_id?byId['router_'+item.router_id]:null;
        var upOlt=item.olt_id?byId['olt_'+item.olt_id]:null;
        var upSpl=item.splitter_id?byId['splitter_'+item.splitter_id]:null;
        if(upRouter||upOlt||upSpl){
            h+='<div class="conn-info"><div class="conn-info-title"><i class="fas fa-arrow-up text-info mr-1"></i>Uplink Devices</div>';
            if(upRouter) h+='<div class="conn-device"><i class="fas fa-server" style="color:#17a2b8;"></i> <a href="'+upRouter.detail_url+'" target="_blank">'+esc(upRouter.name)+'</a> <span style="font-size:10px;color:'+(upRouter.status==='online'?'#28a745':'#dc3545')+';">('+upRouter.status+')</span></div>';
            if(upOlt)    h+='<div class="conn-device"><i class="fas fa-broadcast-tower" style="color:#0d6efd;"></i> '+esc(upOlt.name)+'</div>';
            if(upSpl)    h+='<div class="conn-device"><i class="fas fa-code-branch" style="color:#fd7e14;"></i> '+esc(upSpl.name)+'</div>';
            h+='</div>';
        }
        h+='<div class="popup-actions">';
        if(item.detail_url) h+='<a href="'+item.detail_url+'" class="btn btn-primary btn-sm"><i class="fas fa-user mr-1"></i>Profile</a>';
        h+='<button class="btn btn-success btn-sm" onclick="showClientUplink('+item.id+',event)"><i class="fas fa-project-diagram mr-1"></i>Show Full Chain</button>';
        h+='<button class="btn btn-outline-secondary btn-sm" onclick="enterPlacementMode(\'client\','+item.id+',\''+esc(item.name)+'\',event)"><i class="fas fa-map-marker-alt"></i> Move</button>';
        h+='</div>';
    }
    return h+'</div>';
}

/* ═══════════════════════════════════════════════════
   STATE
═══════════════════════════════════════════════════ */
var allObjs=[], cableLines=[], connLines=[], clientCluster=null;
var currentView='full', activeStatusFilter='all', activeTypeFilter='all', activeClientFilter='all';

/* ═══════════════════════════════════════════════════
   LEGEND
═══════════════════════════════════════════════════ */
function pinLgd(fill,border){
    return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 32 43" style="margin-right:6px;flex-shrink:0;">'
        +'<path d="M16 1 C8.268 1 2 7.268 2 15 C2 24.5 16 42 16 42 C16 42 30 24.5 30 15 C30 7.268 23.732 1 16 1 Z" fill="'+fill+'" stroke="'+border+'" stroke-width="2"/>'
        +'<circle cx="16" cy="15" r="5" fill="#fff" opacity="0.5"/></svg>';
}
var legend=L.control({position:'bottomright'});
legend.onAdd=function(){
    var div=L.DomUtil.create('div','legend');
    div.innerHTML='<div style="font-weight:700;font-size:12px;margin-bottom:5px;border-bottom:1px solid #eee;padding-bottom:3px;">Legend</div>'
        +'<div style="font-size:10px;font-weight:700;color:#555;margin:3px 0 1px;">ROUTER</div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#28a745','#1a6e30')+'<span style="color:#28a745;font-size:11px;font-weight:600;">Online</span></div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#dc3545','#9b1c1c')+'<span style="color:#dc3545;font-size:11px;">Offline</span></div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#6c757d','#444')+'<span style="color:#6c757d;font-size:11px;">Unknown</span></div>'
        +'<div style="font-size:10px;font-weight:700;color:#555;margin:4px 0 1px;">OLT</div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#0d6efd','#0043b8')+'<span style="color:#0d6efd;font-size:11px;font-weight:600;">Active</span></div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#6c757d','#444')+'<span style="color:#6c757d;font-size:11px;">Inactive</span></div>'
        +'<div style="font-size:10px;font-weight:700;color:#555;margin:4px 0 1px;">SPLITTER</div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#fd7e14','#b35300')+'<span style="color:#fd7e14;font-size:11px;font-weight:600;">Passive</span></div>'
        +'<div style="font-size:10px;font-weight:700;color:#555;margin:4px 0 1px;">CLIENT</div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#0dcaf0','#0a7a96')+'<span style="color:#0a7a96;font-size:11px;font-weight:600;">Online</span></div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#adb5bd','#666')+'<span style="color:#6c757d;font-size:11px;">Offline</span></div>'
        +'<div style="display:flex;align-items:center;">'+pinLgd('#ffc107','#c07a00')+'<span style="color:#c07a00;font-size:11px;">Inactive/Expired</span></div>'
        +'<div style="margin-top:5px;padding-top:4px;border-top:1px solid #eee;display:flex;align-items:center;">'
        +'<span style="display:inline-block;border-top:2px dashed #8B4513;width:18px;margin-right:6px;"></span>'
        +'<span style="color:#8B4513;font-size:11px;">Fiber Cable</span></div>'
        +'<div style="display:flex;align-items:center;margin-top:2px;">'
        +'<span style="display:inline-block;border-top:2px solid #28a745;width:18px;margin-right:6px;"></span>'
        +'<span style="color:#28a745;font-size:11px;">Connection Line</span></div>';
    return div;
};
legend.addTo(map);

/* ═══════════════════════════════════════════════════
   BUILD LAYERS
═══════════════════════════════════════════════════ */
function buildLayers(useCluster){
    allObjs.forEach(function(o){try{map.removeLayer(o.m);}catch(e){}});
    if(clientCluster){try{map.removeLayer(clientCluster);}catch(e){}}
    allObjs=[];
    clientCluster=L.markerClusterGroup({maxClusterRadius:50,spiderfyOnMaxZoom:true});
    var bounds=[];
    var viewTypes = currentView==='device'?['router','olt','splitter']:currentView==='client'?['client']:null;

    mapData.forEach(function(item){
        if(viewTypes && viewTypes.indexOf(item.type)===-1) return;
        var st=item.status||item.connection_status||'';
        var m=L.marker([item.lat,item.lng],{icon:makeIcon(item.type,st)});
        m.bindPopup(buildPopup(item),{maxWidth:300});
        m._d=item;
        allObjs.push({m:m,type:item.type,status:st,connSt:item.connection_status});
        bounds.push([item.lat,item.lng]);
        if(useCluster&&item.type==='client'){clientCluster.addLayer(m);}
        else{m.addTo(map);}
    });
    if(useCluster) clientCluster.addTo(map);
    if(bounds.length>0){map.fitBounds(bounds,{padding:[40,40]});if(bounds.length===1)map.setZoom(15);}
    if(showAllActive) setTimeout(showAllConnections,300);
    updateTabCounts();
    applyFilters();
}

/* ═══════════════════════════════════════════════════
   TAB COUNTS
═══════════════════════════════════════════════════ */
function updateTabCounts(){
    var full=mapData.length;
    var dev=mapData.filter(function(d){return d.type!=='client';}).length;
    var cli=mapData.filter(function(d){return d.type==='client';}).length;
    document.getElementById('cnt-full').textContent=full;
    document.getElementById('cnt-device').textContent=dev;
    document.getElementById('cnt-client').textContent=cli;
}

/* ═══════════════════════════════════════════════════
   CABLE LINES (OLT → Splitter)
═══════════════════════════════════════════════════ */
function buildCables(show){
    cableLines.forEach(function(l){map.removeLayer(l);});
    cableLines=[];
    if(!show) return;
    if(currentView==='client') return;
    var oltMap={};
    mapData.forEach(function(d){if(d.type==='olt') oltMap[d.id]=d;});
    mapData.forEach(function(d){
        if(d.type==='splitter'&&d.olt_id&&oltMap[d.olt_id]){
            var o=oltMap[d.olt_id];
            var l=L.polyline([[o.lat,o.lng],[d.lat,d.lng]],{color:'#8B4513',weight:2,dashArray:'6,4',opacity:0.65});
            l.bindTooltip(esc(o.name)+' → '+esc(d.name),{sticky:true});
            l.addTo(map); cableLines.push(l);
        }
    });
}

/* ═══════════════════════════════════════════════════
   CONNECTION LINES — Full Hierarchy Chain
   Link type colors:
     backbone      Router ↔ OLT              #1565C0  3px solid
     feeder        OLT → Splitter root       #0288d1  2.5px solid
     distribution  Splitter → Splitter       #e65100  2px dashed
     last_mile     Splitter/OLT → Client     #2e7d32  1.5px (online)
                                             #c62828  1.5px (offline)
     direct_olt    Direct OLT → Client       #6a1b9a  2px solid
     downlink      Router → Client (direct)  #00897b  1.5px dotted
═══════════════════════════════════════════════════ */
var lineStyles = {
    backbone:     {color:'#1565C0', weight:3,   dashArray:null,  opacity:0.9},
    feeder:       {color:'#0288d1', weight:2.5, dashArray:null,  opacity:0.85},
    distribution: {color:'#e65100', weight:2,   dashArray:'7,5', opacity:0.8},
    last_mile:    {color:'#2e7d32', weight:1.5, dashArray:null,  opacity:0.85},
    last_mile_off:{color:'#c62828', weight:1.5, dashArray:null,  opacity:0.75},
    direct_olt:   {color:'#6a1b9a', weight:2,   dashArray:null,  opacity:0.8},
    downlink:     {color:'#00897b', weight:1.5, dashArray:'4,4', opacity:0.75},
};

function clearConnections(){
    connLines.forEach(function(l){map.removeLayer(l);});
    connLines=[];
    document.getElementById('clearConnBtn').style.display='none';
    document.getElementById('clearConnBtn2').style.display='none';
}

function drawLine(lat1,lng1,lat2,lng2,styleKey,tooltip){
    if(!lat1||!lng1||!lat2||!lng2) return null;
    var s=lineStyles[styleKey]||lineStyles.downlink;
    var opt={color:s.color,weight:s.weight+1,opacity:s.opacity};
    if(s.dashArray) opt.dashArray=s.dashArray;
    var l=L.polyline([[lat1,lng1],[lat2,lng2]],opt);
    if(tooltip) l.bindTooltip(tooltip,{sticky:true,direction:'center',className:'chain-tip'});
    l.addTo(map); connLines.push(l);
    // Arrow head at midpoint
    try {
        var dec=L.polylineDecorator(l,{patterns:[{offset:'50%',repeat:0,
            symbol:L.Symbol.arrowHead({pixelSize:s.weight>2?12:9,polygon:false,
                pathOptions:{color:s.color,weight:s.weight,opacity:s.opacity,fillOpacity:1}})}]});
        dec.addTo(map); connLines.push(dec);
    } catch(e){}
    // Flow animation
    setTimeout(function(){
        try{ if(l._path) l._path.classList.add('conn-line-active'); }catch(e){}
    },50);
    return l;
}

/* ── Walk the pre-computed uplink_chain for a client and draw all hops ── */
function drawChain(client){
    if(!client||!client.lat||!client.lng) return;
    var chain=client.uplink_chain||[];

    if(!chain.length){
        // Fallback: single-level direct links
        if(client.splitter_id&&byId['splitter_'+client.splitter_id]){
            var s=byId['splitter_'+client.splitter_id];
            if(s.lat&&s.lng) drawLine(client.lat,client.lng,s.lat,s.lng,'last_mile',esc(s.name)+' → '+esc(client.name));
        } else if(client.olt_id&&byId['olt_'+client.olt_id]){
            var o=byId['olt_'+client.olt_id];
            if(o.lat&&o.lng) drawLine(client.lat,client.lng,o.lat,o.lng,'direct_olt',esc(o.name)+' → '+esc(client.name));
        } else if(client.router_id&&byId['router_'+client.router_id]){
            var r=byId['router_'+client.router_id];
            if(r.lat&&r.lng) drawLine(client.lat,client.lng,r.lat,r.lng,'downlink',esc(r.name)+' → '+esc(client.name));
        }
        return;
    }

    // Walk chain: each hop connects to the previous point
    // chain[0] = nearest upstream device, chain[last] = root (router)
    var prevLat=client.lat, prevLng=client.lng, prevName=client.name;
    chain.forEach(function(hop){
        if(!hop.lat||!hop.lng) return; // skip if no GPS
        var sk=hop.link_type||'downlink';
        if(sk==='last_mile'&&client.connection_status!=='online') sk='last_mile_off';
        var tip=esc(prevName)+' → '+esc(hop.name)
               +' ['+sk.replace(/_/g,' ').toUpperCase()+']';
        drawLine(prevLat,prevLng,hop.lat,hop.lng,sk,tip);
        prevLat=hop.lat; prevLng=hop.lng; prevName=hop.name;
    });
}

/* ── Walk splitter uplink recursively ── */
function drawSplitterUplink(spl){
    if(!spl||!spl.lat||!spl.lng) return;
    if(spl.parent_splitter_id){
        var parent=byId['splitter_'+spl.parent_splitter_id];
        if(parent&&parent.lat&&parent.lng){
            drawLine(spl.lat,spl.lng,parent.lat,parent.lng,'distribution',
                esc(spl.name)+' → '+esc(parent.name)+' [DISTRIBUTION]');
            drawSplitterUplink(parent);
        }
    } else if(spl.olt_id){
        var olt=byId['olt_'+spl.olt_id];
        if(olt&&olt.lat&&olt.lng)
            drawLine(spl.lat,spl.lng,olt.lat,olt.lng,'feeder',
                esc(spl.name)+' → '+esc(olt.name)+' [FEEDER]');
    }
}

/* ── Find root splitter (walks parent_splitter_id chain) ── */
function getSplitterRoot(id,depth){
    depth=depth||0; if(depth>10) return null;
    var s=byId['splitter_'+id]; if(!s) return null;
    if(!s.parent_splitter_id) return s;
    return getSplitterRoot(s.parent_splitter_id,depth+1);
}

/* ── showConnections: click device → show all downlink connections ── */
function showConnections(type,id,evt){
    if(evt){evt.stopPropagation();}
    clearConnections();
    map.closePopup();

    if(type==='router'){
        var router=byId['router_'+id]; if(!router) return;
        // All clients under this router: draw full chain for each
        byType.client.forEach(function(c){
            if(c.router_id==id&&c.lat&&c.lng) drawChain(c);
        });
        // Backbone lines to OLTs that serve clients of this router
        var linkedOltIds={};
        byType.client.forEach(function(c){if(c.router_id==id&&c.olt_id) linkedOltIds[c.olt_id]=true;});
        Object.keys(linkedOltIds).forEach(function(oid){
            var olt=byId['olt_'+oid];
            if(olt&&olt.lat&&olt.lng&&router.lat&&router.lng)
                drawLine(router.lat,router.lng,olt.lat,olt.lng,'backbone',
                    esc(router.name)+' ↔ '+esc(olt.name)+' [BACKBONE]');
        });

    } else if(type==='olt'){
        var olt=byId['olt_'+id]; if(!olt) return;
        // Feeder lines: OLT → root-level splitters
        byType.splitter.forEach(function(s){
            if(s.olt_id==id&&!s.parent_splitter_id&&s.lat&&s.lng&&olt.lat&&olt.lng)
                drawLine(olt.lat,olt.lng,s.lat,s.lng,'feeder',
                    esc(olt.name)+' → '+esc(s.name)+' [FEEDER]');
        });
        // Distribution: splitter → child splitters under this OLT
        byType.splitter.forEach(function(s){
            if(s.parent_splitter_id&&s.lat&&s.lng){
                var parent=byId['splitter_'+s.parent_splitter_id];
                if(parent&&parent.lat&&parent.lng){
                    var root=getSplitterRoot(s.id);
                    if(root&&root.olt_id==id)
                        drawLine(parent.lat,parent.lng,s.lat,s.lng,'distribution',
                            esc(parent.name)+' → '+esc(s.name)+' [DISTRIBUTION]');
                }
            }
        });
        // Last-mile: clients connected to this OLT
        byType.client.forEach(function(c){
            if(c.olt_id==id&&c.lat&&c.lng) drawChain(c);
        });

    } else if(type==='splitter'){
        var spl=byId['splitter_'+id]; if(!spl) return;
        // Uplink from this splitter to root
        drawSplitterUplink(spl);
        // Downlink: child splitters
        byType.splitter.forEach(function(s){
            if(s.parent_splitter_id==id&&s.lat&&s.lng&&spl.lat&&spl.lng)
                drawLine(spl.lat,spl.lng,s.lat,s.lng,'distribution',
                    esc(spl.name)+' → '+esc(s.name)+' [DISTRIBUTION]');
        });
        // Downlink: clients on this splitter
        byType.client.forEach(function(c){
            if(c.splitter_id==id&&c.lat&&c.lng) drawChain(c);
        });
    }

    document.getElementById('clearConnBtn').style.display='';
    document.getElementById('clearConnBtn2').style.display='';
}

/* ── showClientUplink: click client → show its full uplink chain ── */
function showClientUplink(id,evt){
    if(evt){evt.stopPropagation();}
    clearConnections();
    map.closePopup();
    var client=byId['client_'+id];
    if(!client||!client.lat||!client.lng) return;
    drawChain(client);
    // Also draw backbone from router to OLT if both have coords
    if(client.router_id&&client.olt_id){
        var r=byId['router_'+client.router_id];
        var o=byId['olt_'+client.olt_id];
        if(r&&o&&r.lat&&r.lng&&o.lat&&o.lng)
            drawLine(r.lat,r.lng,o.lat,o.lng,'backbone',
                esc(r.name)+' ↔ '+esc(o.name)+' [BACKBONE]');
    }
    document.getElementById('clearConnBtn').style.display='';
    document.getElementById('clearConnBtn2').style.display='';
}

/* ═══════════════════════════════════════════════════
   VIEW SWITCHING (Tabs)
═══════════════════════════════════════════════════ */
var viewBadges={
    full:'<i class="fas fa-globe mr-1"></i>Full Network Map',
    device:'<i class="fas fa-server mr-1"></i>Device Map',
    client:'<i class="fas fa-users mr-1"></i>Client Map'
};
function switchView(view, btn){
    currentView=view;
    clearConnections();
    document.querySelectorAll('.map-tab').forEach(function(b){b.classList.remove('active');});
    if(btn) btn.classList.add('active');
    document.getElementById('viewBadge').innerHTML=viewBadges[view]||'';
    document.getElementById('deviceFilterBar').style.display=view==='client'?'none':'';
    document.getElementById('clientFilterBar').style.display=view==='device'?'none':(view==='client'?'':'none');
    if(view==='client') document.getElementById('clientFilterBar').style.display='';
    buildLayers(document.getElementById('clusterToggle').checked);
    buildCables(document.getElementById('cableToggle').checked);
}

/* ═══════════════════════════════════════════════════
   FILTERS
═══════════════════════════════════════════════════ */
function applyFilters(){
    var useCluster=document.getElementById('clusterToggle').checked;
    allObjs.forEach(function(o){
        var d=o.m._d;
        var show=true;
        if(currentView==='full'||currentView==='device'){
            if(activeStatusFilter==='online')  show=show&&(d.status==='online'||d.status==='active');
            if(activeStatusFilter==='offline') show=show&&(d.status==='offline'||d.status==='inactive');
            if(activeTypeFilter!=='all') show=show&&(d.type===activeTypeFilter);
        }
        if(currentView==='client'||currentView==='full'){
            if(d.type==='client'&&activeClientFilter!=='all'){
                if(activeClientFilter==='online')  show=show&&d.connection_status==='online';
                if(activeClientFilter==='offline') show=show&&d.connection_status!=='online';
                if(activeClientFilter==='inactive')show=show&&d.status==='inactive';
                if(activeClientFilter==='expired') show=show&&d.status==='expired';
            }
        }
        if(d.type==='client'&&useCluster){
            if(show) clientCluster.addLayer(o.m); else clientCluster.removeLayer(o.m);
        } else {
            if(show){if(!map.hasLayer(o.m)) o.m.addTo(map);}
            else{if(map.hasLayer(o.m)) map.removeLayer(o.m);}
        }
    });
}

function statusFilter(btn,val){
    activeStatusFilter=val;
    document.querySelectorAll('[data-sf]').forEach(function(b){
        b.classList.remove('btn-success','btn-danger','btn-secondary');
        b.classList.add('btn-outline-'+(b.dataset.sf==='online'?'success':b.dataset.sf==='offline'?'danger':'secondary'));
    });
    if(btn){btn.classList.remove('btn-outline-success','btn-outline-danger','btn-outline-secondary');btn.classList.add(val==='online'?'btn-success':val==='offline'?'btn-danger':'btn-secondary');}
    applyFilters();
}
function typeFilter(btn,val){
    activeTypeFilter=activeTypeFilter===val?'all':val;
    document.querySelectorAll('[data-tf]').forEach(function(b){
        b.className=b.className.replace(/btn-(?!outline)[a-z-]+/,'');
        if(!b.className.includes('btn-outline')) b.classList.add('btn-outline-'+(b.dataset.tf==='router'?'info':b.dataset.tf==='olt'?'warning':'secondary'));
    });
    if(btn&&activeTypeFilter!=='all'){btn.className=btn.className.replace(/btn-outline-[a-z]+/,'');btn.classList.add(val==='router'?'btn-info':val==='olt'?'btn-warning':'btn-secondary');}
    applyFilters();
}
function clientStatusFilter(btn,val){
    activeClientFilter=val;
    document.querySelectorAll('#clientFilterBar .btn').forEach(function(b){
        b.className=b.className.replace(/^btn-[a-z]/,'btn-outline-');
        if(!b.className.includes('btn-outline')) b.classList.add('btn-outline-secondary');
    });
    if(btn){btn.className=btn.className.replace('btn-outline-','btn-');}
    applyFilters();
}

/* ═══════════════════════════════════════════════════
   TOGGLES
═══════════════════════════════════════════════════ */
document.getElementById('clusterToggle').addEventListener('change',function(){
    buildLayers(this.checked);
    buildCables(document.getElementById('cableToggle').checked);
});
document.getElementById('cableToggle').addEventListener('change',function(){buildCables(this.checked);});

/* ═══════════════════════════════════════════════════
   FIT ALL
═══════════════════════════════════════════════════ */
function fitAll(){
    var b=mapData.map(function(d){return[d.lat,d.lng];});
    if(b.length>0) map.fitBounds(b,{padding:[40,40]});
}

/* ═══════════════════════════════════════════════════
   SEARCH
═══════════════════════════════════════════════════ */
var icMap  = {router:'server',olt:'broadcast-tower',splitter:'code-branch',client:'user'};
var icColor= {router:'#1565C0',olt:'#e65100',splitter:'#c2185b',client:'#2e7d32'};
var srIdx  = -1;

function chainLabel(d){
    if(d.type!=='client') return '';
    var parts=[];
    if(d.splitter_id){var s=byId['splitter_'+d.splitter_id];if(s)parts.push(esc(s.name));}
    if(d.olt_id)     {var o=byId['olt_'+d.olt_id];          if(o)parts.push(esc(o.name));}
    if(d.router_id)  {var r=byId['router_'+d.router_id];    if(r)parts.push(esc(r.name));}
    return parts.length ? parts.join(' \u2192 ') : '';
}

function renderSR(hits,q){
    var rd=document.getElementById('searchResults');
    if(!hits.length){
        rd.innerHTML='<div class="sr-empty"><i class="fas fa-search-minus mr-2"></i>No results for "'+esc(q)+'"</div>';
        rd.style.display='block'; return;
    }
    var grp={router:[],olt:[],splitter:[],client:[]};
    hits.forEach(function(d){if(grp[d.type])grp[d.type].push(d);});
    var lbl={router:'Routers',olt:'OLT Devices',splitter:'Splitters',client:'Clients'};
    var html='';
    ['router','olt','splitter','client'].forEach(function(t){
        if(!grp[t].length) return;
        html+='<div class="sr-hdr"><i class="fas fa-'+icMap[t]+' mr-1"></i>'+lbl[t]+'</div>';
        grp[t].forEach(function(d){
            var on=d.status==='online'||d.connection_status==='online';
            var sc=on?'btn-success':d.status==='inactive'?'btn-warning':d.status==='expired'?'btn-danger':'btn-secondary';
            var st=on?'Online':d.status==='inactive'?'Inactive':d.status==='expired'?'Expired':'Offline';
            var hasGps=!!(d.lat&&d.lng);
            var chain=chainLabel(d);
            var loc=(d.zone||'')+(d.zone&&d.area?', ':'')+(d.area||'');
            html+='<div class="sri" data-id="'+d.id+'" data-type="'+d.type+'" data-lat="'+(d.lat||0)+'" data-lng="'+(d.lng||0)+'" onclick="pickSR(this)">';
            html+='<i class="fas fa-'+icMap[t]+'" style="color:'+icColor[t]+';width:14px;flex-shrink:0;"></i>';
            html+='<div class="sri-main">';
            html+='<div class="sri-name">'+esc(d.name)+(d.username?' <small class="text-muted">('+esc(d.username)+')</small>':'')+'</div>';
            if(chain) html+='<div class="sri-path"><i class="fas fa-arrow-up" style="font-size:8px;margin-right:3px;"></i>'+chain+'</div>';
            if(loc)   html+='<div class="sri-path"><i class="fas fa-map-pin" style="font-size:8px;margin-right:3px;"></i>'+esc(loc)+'</div>';
            html+='</div>';
            html+='<div class="d-flex flex-column align-items-end" style="gap:2px;">';
            html+='<span class="sri-st '+sc+'">'+st+'</span>';
            if(!hasGps) html+='<span class="sri-nogps" title="No GPS"><i class="fas fa-map-marker-alt"></i></span>';
            html+='</div></div>';
        });
    });
    rd.innerHTML=html; rd.style.display='block'; srIdx=-1;
}

document.getElementById('mapSearch').addEventListener('input',function(){
    var q=this.value.trim().toLowerCase();
    var clr=document.getElementById('sClear'); if(clr) clr.style.display=q?'':'none';
    var rd=document.getElementById('searchResults');
    if(q.length<1){rd.style.display='none';return;}
    var hits=mapData.filter(function(d){
        return (d.name       &&d.name.toLowerCase().indexOf(q)>-1)
            || (d.username   &&d.username.toLowerCase().indexOf(q)>-1)
            || (d.ip_address &&d.ip_address.indexOf(q)>-1)
            || (d.host       &&d.host.indexOf(q)>-1)
            || (d.phone      &&d.phone.indexOf(q)>-1)
            || (d.zone       &&d.zone.toLowerCase().indexOf(q)>-1)
            || (d.area       &&d.area.toLowerCase().indexOf(q)>-1);
    }).slice(0,20);
    renderSR(hits,q);
});

document.getElementById('mapSearch').addEventListener('keydown',function(e){
    var items=document.querySelectorAll('#searchResults .sri');
    if(!items.length) return;
    if(e.key==='ArrowDown'){
        e.preventDefault();
        srIdx=Math.min(srIdx+1,items.length-1);
        items.forEach(function(el,i){el.classList.toggle('kbactive',i===srIdx);});
        items[srIdx].scrollIntoView({block:'nearest'});
    } else if(e.key==='ArrowUp'){
        e.preventDefault();
        srIdx=Math.max(srIdx-1,0);
        items.forEach(function(el,i){el.classList.toggle('kbactive',i===srIdx);});
        items[srIdx].scrollIntoView({block:'nearest'});
    } else if(e.key==='Enter'){
        e.preventDefault();
        var t=srIdx>=0?items[srIdx]:items[0]; if(t) pickSR(t);
    } else if(e.key==='Escape'){
        document.getElementById('searchResults').style.display='none'; this.blur();
    }
});

function pickSR(el){
    var id=parseInt(el.dataset.id), type=el.dataset.type;
    var lat=parseFloat(el.dataset.lat), lng=parseFloat(el.dataset.lng);
    var nm=el.querySelector('.sri-name');
    if(nm){
        document.getElementById('mapSearch').value=nm.textContent.trim();
        var clr=document.getElementById('sClear'); if(clr) clr.style.display='';
    }
    document.getElementById('searchResults').style.display='none';
    if(!lat||!lng){
        showToast('No GPS \u2014 use Move Pin to place this device on the map','warning'); return;
    }
    // Smooth fly
    map.flyTo([lat,lng],17,{animate:true,duration:1.0});
    // After fly: popup + chain
    map.once('moveend',function(){
        allObjs.forEach(function(o){
            if(o.m._d&&o.m._d.id===id&&o.m._d.type===type){o.m.openPopup();pulseMarker(o.m,'#1565C0');}
        });
        clearConnections();
        if(type==='client'){
            var d=byId['client_'+id];
            if(d){
                drawChain(d);
                if(d.router_id&&d.olt_id){
                    var r=byId['router_'+d.router_id],o=byId['olt_'+d.olt_id];
                    if(r&&o&&r.lat&&o.lat) drawLine(r.lat,r.lng,o.lat,o.lng,'backbone',esc(r.name)+' \u2194 '+esc(o.name));
                }
            }
        } else { showConnections(type,id,null); }
        document.getElementById('clearConnBtn').style.display='';
        document.getElementById('clearConnBtn2').style.display='';
    });
    buildNetworkTree();
    document.getElementById('netTreePanel').classList.add('open');
    document.getElementById('network-map').style.marginRight='270px';
}

function clearMapSearch(){
    document.getElementById('mapSearch').value='';
    document.getElementById('searchResults').style.display='none';
    var clr=document.getElementById('sClear'); if(clr) clr.style.display='none';
    clearConnections(); document.getElementById('mapSearch').focus();
}

function focusItem(lat,lng,id,type){
    var fake={dataset:{id:id,type:type,lat:lat,lng:lng},querySelector:function(){return{textContent:''};}};
    pickSR(fake);
}

document.addEventListener('click',function(e){if(!e.target.closest('.search-box'))document.getElementById('searchResults').style.display='none';});

/* ═══════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded',function(){
    buildLayers(true);
    buildCables(true);
});

/* ═══════════════════════════════════════════════════
   SHOW ALL + NETWORK TREE
═══════════════════════════════════════════════════ */
var showAllActive = false;

function toggleShowAll(btn){
    showAllActive = !showAllActive;
    if(showAllActive){ btn.classList.add('active'); showAllConnections(); buildNetworkTree(); }
    else { btn.classList.remove('active'); clearConnections(); }
}

function showAllConnections(){
    clearConnections();
    var drawn={};

    // Backbone: Router ↔ OLT
    (byType.router||[]).forEach(function(router){
        if(!router.lat||!router.lng) return;
        var myOlts={};
        (byType.client||[]).forEach(function(c){ if(c.router_id==router.id&&c.olt_id) myOlts[c.olt_id]=1; });
        Object.keys(myOlts).forEach(function(oid){
            var olt=byId['olt_'+oid];
            var k='bb_'+router.id+'_'+oid;
            if(olt&&olt.lat&&olt.lng&&!drawn[k]){ drawn[k]=1;
                drawLine(router.lat,router.lng,olt.lat,olt.lng,'backbone',esc(router.name)+' \u2194 '+esc(olt.name)+' [BACKBONE]'); }
        });
        // Direct router clients
        (byType.client||[]).forEach(function(c){
            if(c.router_id==router.id&&!c.olt_id&&c.lat&&c.lng){
                var k='dc_'+router.id+'_'+c.id;
                if(!drawn[k]){ drawn[k]=1;
                    drawLine(router.lat,router.lng,c.lat,c.lng,'downlink',esc(router.name)+' \u2192 '+esc(c.name)+' [DIRECT]'); }
            }
        });
    });

    // Feeder: OLT → root splitters
    (byType.olt||[]).forEach(function(olt){
        if(!olt.lat||!olt.lng) return;
        (byType.splitter||[]).forEach(function(s){
            if(s.olt_id==olt.id&&!s.parent_splitter_id&&s.lat&&s.lng){
                var k='fd_'+olt.id+'_'+s.id;
                if(!drawn[k]){ drawn[k]=1;
                    drawLine(olt.lat,olt.lng,s.lat,s.lng,'feeder',esc(olt.name)+' \u2192 '+esc(s.name)+' [FEEDER]'); }
            }
        });
    });

    // Distribution: Splitter → child Splitter
    (byType.splitter||[]).forEach(function(s){
        if(!s.lat||!s.lng||!s.parent_splitter_id) return;
        var par=byId['splitter_'+s.parent_splitter_id];
        if(par&&par.lat&&par.lng){
            var k='ds_'+par.id+'_'+s.id;
            if(!drawn[k]){ drawn[k]=1;
                drawLine(par.lat,par.lng,s.lat,s.lng,'distribution',esc(par.name)+' \u2192 '+esc(s.name)+' [DIST]'); }
        }
    });

    // Last mile: all GPS clients via uplink chain
    (byType.client||[]).forEach(function(c){ if(c.lat&&c.lng) drawChain(c); });

    document.getElementById('clearConnBtn').style.display='';
    document.getElementById('clearConnBtn2').style.display='';
}

// ── Tree Panel ───────────────────────────────────────────────
function toggleTree(){
    var p=document.getElementById('netTreePanel');
    p.classList.toggle('open');
    document.getElementById('network-map').style.marginRight=p.classList.contains('open')?'270px':'';
    if(p.classList.contains('open')) buildNetworkTree();
}

function buildNetworkTree(){
    var body=document.getElementById('netTreeBody');
    if(!body) return;
    var h='';
    (byType.router||[]).forEach(function(router){
        h+='<div class="tree-node router-node" onclick="showConnections(\'router\','+router.id+',event)">';
        h+='<i class="fas fa-server text-primary"></i><span style="font-weight:700;">'+esc(router.name)+'</span>';
        h+='<span class="ml-auto lbadge bb">ROOT</span></div>';
        var myOlts={};
        (byType.client||[]).forEach(function(c){ if(c.router_id==router.id&&c.olt_id) myOlts[c.olt_id]=1; });
        Object.keys(myOlts).forEach(function(oid){
            var olt=byId['olt_'+oid]; if(!olt) return;
            h+='<div class="tree-indent">';
            h+='<div class="tree-node olt-node" onclick="showConnections(\'olt\','+olt.id+',event)">';
            h+='<i class="fas fa-broadcast-tower text-warning"></i><span style="font-weight:600;">'+esc(olt.name)+'</span>';
            if(!olt.lat||!olt.lng) h+=' <i class="fas fa-exclamation-circle text-danger" title="No GPS" style="font-size:9px;"></i>';
            h+='<span class="ml-auto lbadge bb">Backbone</span></div>';
            // Root splitters under this OLT
            (byType.splitter||[]).filter(function(s){ return s.olt_id==olt.id&&!s.parent_splitter_id; }).forEach(function(spl){
                h+=renderSplTree(spl,'fd');
            });
            // Direct OLT clients (no splitter)
            (byType.client||[]).filter(function(c){ return c.olt_id==olt.id&&!c.splitter_id&&c.router_id==router.id; }).forEach(function(cli){
                h+='<div class="tree-indent"><div class="tree-node '+(cli.connection_status==='online'?'client-node':'client-off')+'" onclick="showClientUplink('+cli.id+',event)">';
                h+='<i class="fas fa-user '+(cli.connection_status==='online'?'text-success':'text-danger')+'"></i><span>'+esc(cli.name)+'</span>';
                h+='<span class="ml-auto lbadge dt">Direct</span></div></div>';
            });
            h+='</div>';
        });
        // Direct router clients (no OLT)
        (byType.client||[]).filter(function(c){ return c.router_id==router.id&&!c.olt_id; }).forEach(function(cli){
            h+='<div class="tree-indent"><div class="tree-node '+(cli.connection_status==='online'?'client-node':'client-off')+'" onclick="showClientUplink('+cli.id+',event)">';
            h+='<i class="fas fa-user '+(cli.connection_status==='online'?'text-success':'text-danger')+'"></i><span>'+esc(cli.name)+'</span>';
            h+='<span class="ml-auto lbadge lm">Direct</span></div></div>';
        });
    });
    if(!h) h='<div class="text-muted text-center mt-3">No devices with GPS yet</div>';
    body.innerHTML=h;
}

function renderSplTree(spl, badge){
    var h='<div class="tree-indent"><div class="tree-node splitter-node" onclick="showConnections(\'splitter\','+spl.id+',event)">';
    h+='<i class="fas fa-code-branch text-danger"></i><span style="font-weight:600;">'+esc(spl.name)+'</span>';
    h+=' <small style="color:#888;">('+esc(spl.stype||'1:8')+')</small>';
    if(!spl.lat||!spl.lng) h+=' <i class="fas fa-exclamation-circle text-danger" title="No GPS" style="font-size:9px;"></i>';
    h+='<span class="ml-auto lbadge '+(badge||'fd')+'">'+( badge==='fd'?'Feeder':'Dist')+'</span></div>';
    // Child splitters
    (byType.splitter||[]).filter(function(s){ return s.parent_splitter_id==spl.id; }).forEach(function(ch){ h+=renderSplTree(ch,'ds'); });
    // Clients
    (byType.client||[]).filter(function(c){ return c.splitter_id==spl.id; }).forEach(function(cli){
        h+='<div class="tree-indent"><div class="tree-node '+(cli.connection_status==='online'?'client-node':'client-off')+'" onclick="showClientUplink('+cli.id+',event)">';
        h+='<i class="fas fa-user '+(cli.connection_status==='online'?'text-success':'text-danger')+'"></i><span>'+esc(cli.name)+'</span>';
        h+='<span class="ml-auto lbadge lm">LastMile</span></div></div>';
    });
    h+='</div>';
    return h;
}

/* ═══════════════════════════════════════════════════
   MAP PLACEMENT MODE
═══════════════════════════════════════════════════ */
var placementMode = null;
var updateLocUrl  = '{{ route("admin.network.map.update-location") }}';
var csrfToken     = (document.querySelector('meta[name="csrf-token"]')||{}).getAttribute ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

function enterPlacementMode(type, id, name, evt){
    if(evt){evt.stopPropagation();}
    map.closePopup();
    placementMode={type:type,id:id,name:name};
    document.getElementById('placementMsg').textContent =
        'Click anywhere on map to place: '+name+' ('+type.toUpperCase()+')';
    document.getElementById('placementBanner').style.display='';
    document.getElementById('network-map').classList.add('placement-cursor');
    showToast('Placement mode ON for: '+name, 'warning');
}
function cancelPlacement(){
    placementMode=null;
    document.getElementById('placementBanner').style.display='none';
    document.getElementById('network-map').classList.remove('placement-cursor');
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')cancelPlacement();});

map.on('click',function(e){
    if(!placementMode) return;
    var lat=e.latlng.lat, lng=e.latlng.lng, pm=placementMode;
    cancelPlacement();
    var tmpM=L.marker([lat,lng],{
        icon:L.divIcon({html:'<div style="width:14px;height:14px;background:#e65100;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.5);"></div>',className:'',iconSize:[14,14],iconAnchor:[7,7]})
    }).addTo(map).bindPopup('<b>Saving…</b>').openPopup();

    fetch(updateLocUrl,{
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},
        body:JSON.stringify({type:pm.type,id:pm.id,lat:lat,lng:lng})
    })
    .then(function(r){return r.json();})
    .then(function(res){
        map.removeLayer(tmpM);
        if(!res.ok){showToast('Error: '+(res.message||'Unknown'),'danger');return;}
        // Update mapData in-place
        mapData.forEach(function(d,i){
            if(d.type===pm.type&&d.id==pm.id){mapData[i].lat=lat;mapData[i].lng=lng;byId[pm.type+'_'+pm.id]=mapData[i];}
        });
        buildLayers(document.getElementById('clusterToggle').checked);
        buildCables(document.getElementById('cableToggle').checked);
        // Hide the no-GPS alert if no more missing
        var alert=document.getElementById('noGpsAlert');
        if(alert){
            var btn=alert.querySelector('[data-gpsbtn="'+pm.type+'_'+pm.id+'"]');
            if(btn) btn.remove();
            if(!alert.querySelectorAll('button').length) alert.style.display='none';
        }
        showToast('Saved: '+res.name+' at ('+lat.toFixed(5)+', '+lng.toFixed(5)+')','success');
    })
    .catch(function(){map.removeLayer(tmpM);showToast('Network error saving location','danger');});
});

/* ═══════════════════════════════════════════════════
   REAL-TIME AUTO-REFRESH (every 30s)
═══════════════════════════════════════════════════ */
var refreshInterval = 30;  // seconds
var refreshTimer    = null;
var refreshCountdown= refreshInterval;
var isRefreshing    = false;

var liveStatusUrl = '{{ route("admin.network.map.live") }}';

function buildRefreshBar() {
    var bar = document.createElement('div');
    bar.id  = 'liveBar';
    bar.style.cssText = [
        'position:fixed','bottom:12px','left:50%','transform:translateX(-50%)',
        'background:rgba(30,30,30,.85)','color:#fff','padding:7px 20px',
        'border-radius:30px','font-size:12px','z-index:5000',
        'display:flex','align-items:center','gap:10px',
        'box-shadow:0 3px 14px rgba(0,0,0,.35)','cursor:pointer',
        'backdrop-filter:blur(6px)','user-select:none'
    ].join(';');
    bar.title = 'Click to refresh now';
    bar.innerHTML =
        '<span id="liveDot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#28a745;flex-shrink:0;"></span>'
        + '<span id="liveLabel">Live Refresh</span>'
        + '<span style="opacity:.5;font-size:10px;">|</span>'
        + '<span id="liveCountdown">30s</span>'
        + '<button id="liveToggleBtn" onclick="toggleAutoRefresh(event)" style="background:rgba(255,255,255,.15);border:none;border-radius:12px;color:#fff;font-size:10px;padding:2px 8px;cursor:pointer;">Pause</button>'
        + '<button onclick="doRefresh(null,event)" style="background:rgba(255,255,255,.15);border:none;border-radius:12px;color:#fff;font-size:10px;padding:2px 8px;cursor:pointer;"><i class="fas fa-sync-alt"></i></button>';
    document.body.appendChild(bar);
}

function updateCountdownUI(s) {
    var el = document.getElementById('liveCountdown');
    if (el) el.textContent = s > 0 ? s + 's' : '…';
}
function setDot(color) {
    var el = document.getElementById('liveDot');
    if (el) el.style.background = color;
}
function setLabel(txt) {
    var el = document.getElementById('liveLabel');
    if (el) el.textContent = txt;
}

function tickCountdown() {
    if (refreshCountdown <= 0) {
        doRefresh();
    } else {
        refreshCountdown--;
        updateCountdownUI(refreshCountdown);
    }
}

function startAutoRefresh() {
    stopAutoRefresh();
    refreshCountdown = refreshInterval;
    refreshTimer = setInterval(tickCountdown, 1000);
    var btn = document.getElementById('liveToggleBtn');
    if (btn) btn.textContent = 'Pause';
}
function stopAutoRefresh() {
    if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; }
}
function toggleAutoRefresh(evt) {
    if (evt) evt.stopPropagation();
    if (refreshTimer) {
        stopAutoRefresh();
        var btn = document.getElementById('liveToggleBtn');
        if (btn) btn.textContent = 'Resume';
        setDot('#fd7e14');
    } else {
        startAutoRefresh();
        setDot('#28a745');
    }
}

function doRefresh(data, evt) {
    if (evt) evt.stopPropagation();
    if (isRefreshing) return;
    isRefreshing = true;
    setDot('#ffc107');
    setLabel('Refreshing…');

    fetch(liveStatusUrl, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(res){ applyLiveData(res); })
        .catch(function(e){ console.warn('Live refresh failed:', e); setDot('#dc3545'); setLabel('Refresh failed'); })
        .finally(function(){
            isRefreshing = false;
            refreshCountdown = refreshInterval;
            updateCountdownUI(refreshCountdown);
        });
}

/* ── Apply live data to map markers + stat cards ── */
function applyLiveData(res) {
    if (!res || !res.ok) { setDot('#dc3545'); setLabel('Error'); return; }

    var changedRouters = [], changedOlts = [];

    // Build lookup from live data
    var liveRouters = res.routers || {};
    var liveOlts    = res.olts    || {};

    allObjs.forEach(function(o) {
        var d = o.m._d;
        if (d.type === 'router' && liveRouters[d.id]) {
            var live = liveRouters[d.id];
            var oldSt = d.status;
            // Update mapData item
            d.status        = live.status;
            d.cpu           = live.cpu;
            d.memory        = live.memory;
            d.active_users  = live.active_users;
            d.total_clients = live.total_clients;
            d.uptime        = live.uptime;
            d.uptime_seconds= live.uptime_seconds;
            d.last_up_at    = live.last_up_at;
            d.last_down_at  = live.last_down_at;
            // Update icon if status changed
            if (oldSt !== live.status) {
                o.m.setIcon(makeIcon('router', live.status));
                changedRouters.push(d.name + ' → ' + live.status);
                pulseMarker(o.m, live.status === 'online' ? '#28a745' : '#dc3545');
            }
        }
        if (d.type === 'olt' && liveOlts[d.id]) {
            var live = liveOlts[d.id];
            var oldSt = d.status;
            d.status         = live.status;
            d.cpu            = live.cpu;
            d.memory         = live.memory;
            d.temperature    = live.temperature;
            d.uptime_seconds = live.uptime_seconds;
            d.last_sync      = live.last_sync;
            if (oldSt !== live.status) {
                o.m.setIcon(makeIcon('olt', live.status));
                changedOlts.push(d.name + ' → ' + live.status);
                pulseMarker(o.m, live.status === 'online' || live.status === 'active' ? '#0d6efd' : '#6c757d');
            }
        }
    });

    // Update stat cards
    updateStatCard('stat-online-clients',  res.online_clients  || 0);
    updateStatCard('stat-offline-routers', res.routers_offline || 0);
    updateStatCard('stat-online-routers',  res.routers_online  || 0);

    // Rebuild mapData lookup
    byType.router.forEach(function(d){ byId['router_'+d.id]=d; });
    byType.olt.forEach(function(d){    byId['olt_'+d.id]=d;    });

    // Build notification
    var changed = changedRouters.concat(changedOlts);
    var ts = res.timestamp || new Date().toLocaleTimeString();
    if (changed.length > 0) {
        setLabel('Updated ' + ts + ' (' + changed.length + ' changed)');
        setDot('#28a745');
        showToast(changed.length + ' device(s) changed status', 'warning');
    } else {
        setLabel('Updated ' + ts);
        setDot('#28a745');
    }
}

/* ── Update stat card number by id ── */
function updateStatCard(id, val) {
    var el = document.getElementById(id);
    if (!el) return;
    var old = parseInt(el.textContent) || 0;
    if (old !== val) { el.textContent = val; el.style.transition='color .3s'; el.style.color='#ffc107'; setTimeout(function(){el.style.color='';},1200); }
}

/* ── Pulse animation on a marker ── */
function pulseMarker(m, color) {
    var el = m.getElement ? m.getElement() : null;
    if (!el) return;
    el.style.transition = 'filter .2s';
    el.style.filter = 'drop-shadow(0 0 8px ' + color + ')';
    setTimeout(function(){ el.style.filter=''; }, 2000);
}

/* ── Toast notification ── */
function showToast(msg, type) {
    var t = document.createElement('div');
    t.style.cssText = [
        'position:fixed','top:70px','right:16px','z-index:6000',
        'background:'+(type==='warning'?'#fd7e14':type==='success'?'#28a745':'#dc3545'),
        'color:#fff','padding:8px 16px','border-radius:8px',
        'font-size:12px','font-weight:600','box-shadow:0 3px 10px rgba(0,0,0,.25)',
        'animation:fadeIn .3s ease'
    ].join(';');
    t.innerHTML = '<i class="fas fa-bell mr-2"></i>' + msg;
    document.body.appendChild(t);
    setTimeout(function(){ if(t.parentNode) t.parentNode.removeChild(t); }, 4000);
}

/* Add stat card IDs to info-box-number spans on DOMContentLoaded */
document.addEventListener('DOMContentLoaded', function() {
    var nums = document.querySelectorAll('.info-box-number');
    var labels = document.querySelectorAll('.info-box-text');
    nums.forEach(function(el, i) {
        var prev = labels[i*2] ? labels[i*2].textContent.trim().toLowerCase() : '';
        if (prev.includes('online client') || (i===4)) el.id = 'stat-online-clients';
        if (prev.includes('offline router') || (i===5)) el.id = 'stat-offline-routers';
        if (prev.includes('router') && i===0) el.id = 'stat-online-routers';
    });
    buildRefreshBar();
    startAutoRefresh();
    setTimeout(function(){ buildNetworkTree(); },600);
});
</script>
@endpush
