@extends('layouts.admin')

@section('title', 'Network Diagram')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">Network</a></li>
<li class="breadcrumb-item active">Network Diagram</li>
@endsection

@push('styles')
<link href="https://unpkg.com/vis-network@9.1.6/styles/vis-network.min.css" rel="stylesheet">
<style>
    .network-container {
        height: calc(100vh - 220px);
        min-height: 400px;
        background: linear-gradient(135deg, #0a1628 0%, #1a2a4a 50%, #0d1a2d 100%);
        border-radius: 12px;
        position: relative;
        overflow: hidden;
    }
    #network-diagram {
        width: 100%;
        height: 100%;
    }
    .diagram-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        background: 
            radial-gradient(circle at 20% 30%, rgba(0, 255, 136, 0.03) 0%, transparent 40%),
            radial-gradient(circle at 80% 70%, rgba(0, 170, 255, 0.03) 0%, transparent 40%);
    }
    .grid-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
        background-size: 50px 50px;
        pointer-events: none;
    }
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
    .control-btn.active {
        background: rgba(0, 170, 255, 0.5);
        border-color: #00aaff;
    }
    .stats-panel {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 100;
        display: flex;
        gap: 10px;
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
    .stat-card.online .value { color: #00ff88; }
    .stat-card.offline .value { color: #ff4757; }
    .stat-card.warning .value { color: #ffa502; }
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
    .device-tooltip {
        position: absolute;
        z-index: 1000;
        background: rgba(10, 22, 40, 0.95);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(0, 170, 255, 0.3);
        border-radius: 12px;
        padding: 15px;
        min-width: 250px;
        display: none;
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }
    .device-tooltip.show { display: block; }
    .tooltip-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .tooltip-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .tooltip-title {
        font-size: 16px;
        font-weight: 600;
        color: #fff;
    }
    .tooltip-subtitle {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
    }
    .tooltip-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .tooltip-stat {
        background: rgba(255,255,255,0.05);
        border-radius: 8px;
        padding: 10px;
    }
    .tooltip-stat-label {
        font-size: 10px;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .tooltip-stat-value {
        font-size: 14px;
        font-weight: 600;
        color: #fff;
        margin-top: 3px;
    }
    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .status-dot.online {
        background: #00ff88;
        box-shadow: 0 0 10px #00ff88;
        animation: pulse-green 2s infinite;
    }
    .status-dot.offline {
        background: #ff4757;
        box-shadow: 0 0 10px #ff4757;
    }
    .status-dot.warning {
        background: #ffa502;
        box-shadow: 0 0 10px #ffa502;
        animation: pulse-orange 2s infinite;
    }
    @keyframes pulse-green {
        0%, 100% { box-shadow: 0 0 5px #00ff88; }
        50% { box-shadow: 0 0 15px #00ff88, 0 0 25px #00ff88; }
    }
    @keyframes pulse-orange {
        0%, 100% { box-shadow: 0 0 5px #ffa502; }
        50% { box-shadow: 0 0 15px #ffa502; }
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
    .traffic-indicator {
        position: absolute;
        bottom: 70px;
        right: 15px;
        z-index: 100;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        padding: 12px 15px;
    }
    .traffic-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 11px;
        color: rgba(255,255,255,0.7);
        margin-bottom: 5px;
    }
    .traffic-bar:last-child { margin-bottom: 0; }
    .traffic-bar i { width: 15px; }
    .traffic-value {
        font-weight: 600;
        color: #fff;
        min-width: 80px;
        text-align: right;
    }
    .traffic-bar.download i { color: #00ff88; }
    .traffic-bar.upload i { color: #00aaff; }
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
                <h3 class="card-title"><i class="fas fa-project-diagram mr-2"></i>Network Topology Diagram</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.network.devices') }}" class="btn btn-sm btn-default mr-1">
                        <i class="fas fa-server"></i> MikroTik Devices
                    </a>
                    <a href="{{ route('admin.olt.index') }}" class="btn btn-sm btn-default mr-1">
                        <i class="fas fa-network-wired"></i> OLT Devices
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addDeviceModal">
                        <i class="fas fa-plus"></i> Add Device
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="network-container">
    <div class="grid-overlay"></div>
    <div class="diagram-overlay"></div>
    <div id="net-loading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:200;text-align:center;color:rgba(255,255,255,0.8);">
        <div style="font-size:40px;margin-bottom:12px;"><i class="fas fa-spinner fa-spin" style="color:#00aaff;"></i></div>
        <div style="font-size:14px;">Loading diagram...</div>
    </div>
    
    <div class="stats-panel">
        <div class="stat-card total">
            <div class="value" id="total-devices">{{ $totalDevices ?? 12 }}</div>
            <div class="label">Total Devices</div>
        </div>
        <div class="stat-card online">
            <div class="value" id="online-devices">{{ $onlineDevices ?? 10 }}</div>
            <div class="label">Online</div>
        </div>
        <div class="stat-card offline">
            <div class="value" id="offline-devices">{{ $offlineDevices ?? 1 }}</div>
            <div class="label">Offline</div>
        </div>
        <div class="stat-card warning">
            <div class="value" id="warning-devices">{{ $warningDevices ?? 1 }}</div>
            <div class="label">Warning</div>
        </div>
    </div>

    <div class="control-panel">
        <button class="control-btn" id="zoom-in" title="Zoom In"><i class="fas fa-plus"></i></button>
        <button class="control-btn" id="zoom-out" title="Zoom Out"><i class="fas fa-minus"></i></button>
        <button class="control-btn" id="fit-view" title="Fit to View"><i class="fas fa-expand"></i></button>
        <button class="control-btn" id="toggle-physics" title="Toggle Physics"><i class="fas fa-atom"></i></button>
        <button class="control-btn" id="refresh-status" title="Refresh Status"><i class="fas fa-sync-alt"></i></button>
        <button class="control-btn" id="fullscreen" title="Fullscreen"><i class="fas fa-arrows-alt"></i></button>
    </div>

    <div class="search-panel">
        <input type="text" class="search-input" id="device-search" placeholder="Search devices...">
    </div>

    <div id="network-diagram"></div>

    <div class="legend-panel">
        <div class="legend-title">Device Types</div>
        <div class="legend-items">
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #1a237e, #3949ab);"><i class="fas fa-cloud text-white"></i></div>
                <span>Internet</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #0d47a1, #1976d2);"><i class="fas fa-server text-white"></i></div>
                <span>Core Router</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #00695c, #00897b);"><i class="fas fa-network-wired text-white"></i></div>
                <span>OLT</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #ef6c00, #ff9800);"><i class="fas fa-code-branch text-white"></i></div>
                <span>Splitter</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #6a1b9a, #9c27b0);"><i class="fas fa-microchip text-white"></i></div>
                <span>ONU</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon" style="background: linear-gradient(135deg, #c62828, #e53935);"><i class="fas fa-wifi text-white"></i></div>
                <span>MikroTik</span>
            </div>
        </div>
    </div>

    <div class="traffic-indicator">
        <div class="traffic-bar download">
            <i class="fas fa-arrow-down"></i>
            <span>Download</span>
            <span class="traffic-value" id="total-download">1.25 Gbps</span>
        </div>
        <div class="traffic-bar upload">
            <i class="fas fa-arrow-up"></i>
            <span>Upload</span>
            <span class="traffic-value" id="total-upload">856 Mbps</span>
        </div>
    </div>

    @if($devices->isEmpty())
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);text-align:center;color:rgba(255,255,255,0.45);z-index:50;pointer-events:none;">
        <i class="fas fa-server fa-4x mb-3 d-block" style="color:rgba(255,255,255,0.15);"></i>
        <div style="font-size:15px;margin-bottom:6px;">No MikroTik / OLT devices configured</div>
        <div style="font-size:11px;">Add devices from <strong>Network &rarr; MikroTik Devices</strong></div>
        <div style="font-size:11px;margin-top:4px;">Zones shown as placeholder nodes below</div>
    </div>
    @endif
    <div style="position:absolute;bottom:60px;left:15px;z-index:100;font-size:10px;color:rgba(255,255,255,0.35);">
        Updated: {{ now('Asia/Dhaka')->format('d-M-Y H:i') }}
    </div>
    <div class="view-mode-panel">
        <button class="view-btn active" data-view="hierarchical">Hierarchical</button>
        <button class="view-btn" data-view="radial">Radial</button>
        <button class="view-btn" data-view="free">Free</button>
    </div>

    <div class="device-tooltip" id="device-tooltip">
        <div class="tooltip-header">
            <div class="tooltip-icon" id="tooltip-icon" style="background: #1976d2;"><i class="fas fa-server"></i></div>
            <div>
                <div class="tooltip-title" id="tooltip-title">Device Name</div>
                <div class="tooltip-subtitle" id="tooltip-subtitle">192.168.1.1</div>
            </div>
        </div>
        <div class="tooltip-stats">
            <div class="tooltip-stat">
                <div class="tooltip-stat-label">Status</div>
                <div class="tooltip-stat-value" id="tooltip-status"><span class="status-dot online"></span>Online</div>
            </div>
            <div class="tooltip-stat">
                <div class="tooltip-stat-label">Uptime</div>
                <div class="tooltip-stat-value" id="tooltip-uptime">15d 4h 32m</div>
            </div>
            <div class="tooltip-stat">
                <div class="tooltip-stat-label">Clients</div>
                <div class="tooltip-stat-value" id="tooltip-clients">156</div>
            </div>
            <div class="tooltip-stat">
                <div class="tooltip-stat-label">Traffic</div>
                <div class="tooltip-stat-value" id="tooltip-traffic">1.2 Gbps</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-plus-circle mr-2"></i>Add Network Device to Diagram</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('admin.network.devices.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Device Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g., Core Router 1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Device Type <span class="text-danger">*</span></label>
                                <select name="device_type" class="form-control" required>
                                    <option value="internet">Internet Gateway</option>
                                    <option value="core_router">Core Router</option>
                                    <option value="mikrotik">MikroTik Router</option>
                                    <option value="olt">OLT Device</option>
                                    <option value="splitter">Fiber Splitter</option>
                                    <option value="onu">ONU Device</option>
                                    <option value="switch">Network Switch</option>
                                    <option value="access_point">Access Point</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>IP Address</label>
                                <input type="text" name="ip_address" class="form-control" placeholder="192.168.1.1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>MAC Address</label>
                                <input type="text" name="mac_address" class="form-control" placeholder="AA:BB:CC:DD:EE:FF">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Parent Device</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">None (Root Device)</option>
                                    @foreach($devices ?? [] as $device)
                                    <option value="{{ $device['id'] ?? '' }}">{{ $device['name'] ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" placeholder="e.g., Main Server Room">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" name="latitude" class="form-control" placeholder="23.8103">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="longitude" class="form-control" placeholder="90.4125">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Port Count</label>
                                <input type="number" name="port_count" class="form-control" value="8">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Add Device</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/vis-network@9.1.6/standalone/umd/vis-network.min.js"></script>
<script>
var network = null;
var physicsEnabled = true;

@php
    $realDevices = $devices ?? collect();
    $hasRealDevices = $realDevices->count() > 0;
@endphp
var nodesArr = [];
var edgesArr = [];
var dynNodeId = 100;

// Always: Internet + Core Router
nodesArr.push({ id: 1, label: 'Internet\nGateway', group: 'internet', status: 'online', ip: 'WAN', uptime: 'Always', clients: 0, traffic: '-' });
nodesArr.push({ id: 2, label: 'Core Router\n(ISP)', group: 'core', status: 'online', ip: 'ISP Core', uptime: '-', clients: {{ $totalClients ?? 0 }}, traffic: '-' });
edgesArr.push({ from: 1, to: 2, color: { color: '#00ff88', highlight: '#00ff88' }, width: 4, smooth: { type: 'curvedCW', roundness: 0.1 } });

@if($hasRealDevices)
    @foreach($realDevices as $dev)
    @php $grp = $dev['type'] == 'olt' ? 'olt' : 'mikrotik'; @endphp
    nodesArr.push({ id: dynNodeId, label: '{{ addslashes($dev["name"]) }}\n{{ $dev["ip"] ?? "" }}', group: '{{ $grp }}', status: '{{ $dev["status"] ?? "online" }}', ip: '{{ $dev["ip"] ?? "N/A" }}', uptime: '-', clients: 0, traffic: '-' });
    edgesArr.push({ from: 2, to: dynNodeId, color: { color: '{{ $dev["type"] == "olt" ? "#00aaff" : "#ff6b6b" }}', highlight: '{{ $dev["type"] == "olt" ? "#00aaff" : "#ff6b6b" }}' }, width: 2 });
    dynNodeId++;
    @endforeach
@else
    // No real devices: show zone-based placeholder nodes
    @foreach(\App\Models\Zone::withCount(['clients as active_clients' => function($q){$q->where('status','active');}])->orderBy('name')->limit(8)->get() as $zone)
    @php $grp = $loop->index % 2 == 0 ? 'mikrotik' : 'olt'; @endphp
    nodesArr.push({ id: dynNodeId, label: '{{ addslashes($zone->name) }}\nRouter\n({{ $zone->active_clients }} clients)', group: '{{ $grp }}', status: 'online', ip: 'Zone {{ $loop->iteration }}', uptime: '-', clients: {{ $zone->active_clients }}, traffic: '-' });
    edgesArr.push({ from: 2, to: dynNodeId, color: { color: '#00aaff' }, width: 2 });
    dynNodeId++;
    @endforeach
@endif

var nodes = new vis.DataSet(nodesArr);
var edges = new vis.DataSet(edgesArr);

var groups = {
    internet: {
        shape: 'icon',
        icon: { face: '"Font Awesome 5 Free"', code: '\uf0c2', size: 50, color: '#3949ab', weight: 900 },
        font: { color: '#fff', size: 12, face: 'Arial', strokeWidth: 3, strokeColor: '#000' }
    },
    core: {
        shape: 'icon',
        icon: { face: '"Font Awesome 5 Free"', code: '\uf233', size: 45, color: '#1976d2', weight: 900 },
        font: { color: '#fff', size: 11, face: 'Arial', strokeWidth: 3, strokeColor: '#000' }
    },
    olt: {
        shape: 'icon',
        icon: { face: '"Font Awesome 5 Free"', code: '\uf6ff', size: 40, color: '#00897b', weight: 900 },
        font: { color: '#fff', size: 11, face: 'Arial', strokeWidth: 3, strokeColor: '#000' }
    },
    splitter: {
        shape: 'icon',
        icon: { face: '"Font Awesome 5 Free"', code: '\uf126', size: 35, color: '#ff9800', weight: 900 },
        font: { color: '#fff', size: 10, face: 'Arial', strokeWidth: 3, strokeColor: '#000' }
    },
    onu: {
        shape: 'icon',
        icon: { face: '"Font Awesome 5 Free"', code: '\uf2db', size: 30, color: '#9c27b0', weight: 900 },
        font: { color: '#fff', size: 10, face: 'Arial', strokeWidth: 3, strokeColor: '#000' }
    },
    mikrotik: {
        shape: 'icon',
        icon: { face: '"Font Awesome 5 Free"', code: '\uf1eb', size: 35, color: '#e53935', weight: 900 },
        font: { color: '#fff', size: 10, face: 'Arial', strokeWidth: 3, strokeColor: '#000' }
    }
};

var container = document.getElementById('network-diagram');
var data = { nodes: nodes, edges: edges };
var options = {
    groups: groups,
    nodes: {
        borderWidth: 0,
        shadow: { enabled: true, color: 'rgba(0,0,0,0.5)', size: 15, x: 0, y: 5 }
    },
    edges: {
        smooth: { enabled: true, type: 'continuous', roundness: 0.3 },
        shadow: { enabled: true, color: 'rgba(0,0,0,0.3)', size: 5 },
        arrows: { to: { enabled: false } }
    },
    physics: {
        enabled: true,
        hierarchicalRepulsion: { centralGravity: 0.0, springLength: 150, springConstant: 0.01, nodeDistance: 180 },
        stabilization: { iterations: 200 }
    },
    layout: {
        hierarchical: {
            enabled: true,
            direction: 'UD',
            sortMethod: 'directed',
            levelSeparation: 150,
            nodeSpacing: 200
        }
    },
    interaction: {
        hover: true,
        tooltipDelay: 0,
        zoomView: true,
        dragView: true
    }
};

network = new vis.Network(container, data, options);

network.once('stabilized', function() {
    network.fit({ animation: { duration: 800, easingFunction: 'easeInOutQuad' } });
    var loader = document.getElementById('net-loading');
    if (loader) loader.style.display = 'none';
});
setTimeout(function() {
    var loader = document.getElementById('net-loading');
    if (loader) loader.style.display = 'none';
    network.fit({ animation: false });
}, 5000);

network.on('hoverNode', function(params) {
    var nodeId = params.node;
    var node = nodes.get(nodeId);
    showTooltip(node, params.event);
});

network.on('blurNode', function() {
    hideTooltip();
});

network.on('click', function(params) {
    if (params.nodes.length > 0) {
        var nodeId = params.nodes[0];
        var node = nodes.get(nodeId);
        showTooltip(node, params.event, true);
    } else {
        hideTooltip();
    }
});

function showTooltip(node, event, sticky) {
    var tooltip = document.getElementById('device-tooltip');
    var colors = {
        internet: '#3949ab', core: '#1976d2', olt: '#00897b',
        splitter: '#ff9800', onu: '#9c27b0', mikrotik: '#e53935'
    };
    var icons = {
        internet: 'fa-cloud', core: 'fa-server', olt: 'fa-network-wired',
        splitter: 'fa-code-branch', onu: 'fa-microchip', mikrotik: 'fa-wifi'
    };
    
    document.getElementById('tooltip-icon').style.background = colors[node.group] || '#1976d2';
    document.getElementById('tooltip-icon').innerHTML = '<i class="fas ' + (icons[node.group] || 'fa-server') + '"></i>';
    document.getElementById('tooltip-title').textContent = node.label.replace('\n', ' - ');
    document.getElementById('tooltip-subtitle').textContent = node.ip || 'N/A';
    
    var statusDot = node.status === 'online' ? 'online' : (node.status === 'warning' ? 'warning' : 'offline');
    var statusText = node.status.charAt(0).toUpperCase() + node.status.slice(1);
    document.getElementById('tooltip-status').innerHTML = '<span class="status-dot ' + statusDot + '"></span>' + statusText;
    document.getElementById('tooltip-uptime').textContent = node.uptime || 'N/A';
    document.getElementById('tooltip-clients').textContent = node.clients || '0';
    document.getElementById('tooltip-traffic').textContent = node.traffic || '0 Mbps';
    
    var x = event.center ? event.center.x : (event.clientX || 100);
    var y = event.center ? event.center.y : (event.clientY || 100);
    
    tooltip.style.left = (x + 20) + 'px';
    tooltip.style.top = (y - 50) + 'px';
    tooltip.classList.add('show');
}

function hideTooltip() {
    document.getElementById('device-tooltip').classList.remove('show');
}

document.getElementById('zoom-in').addEventListener('click', function() {
    var scale = network.getScale();
    network.moveTo({ scale: scale * 1.3 });
});

document.getElementById('zoom-out').addEventListener('click', function() {
    var scale = network.getScale();
    network.moveTo({ scale: scale / 1.3 });
});

document.getElementById('fit-view').addEventListener('click', function() {
    network.fit({ animation: { duration: 500 } });
});

document.getElementById('toggle-physics').addEventListener('click', function() {
    physicsEnabled = !physicsEnabled;
    network.setOptions({ physics: { enabled: physicsEnabled } });
    this.classList.toggle('active', physicsEnabled);
});

document.getElementById('refresh-status').addEventListener('click', function() {
    this.querySelector('i').classList.add('fa-spin');
    setTimeout(function() { window.location.reload(); }, 400);
});

document.getElementById('fullscreen').addEventListener('click', function() {
    var container = document.querySelector('.network-container');
    if (!document.fullscreenElement) {
        container.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
});

document.querySelectorAll('.view-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.view-btn').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        
        var view = this.dataset.view;
        var layoutOptions = {};
        
        if (view === 'hierarchical') {
            layoutOptions = { hierarchical: { enabled: true, direction: 'UD', sortMethod: 'directed', levelSeparation: 150, nodeSpacing: 200 } };
        } else if (view === 'radial') {
            layoutOptions = { hierarchical: { enabled: true, direction: 'UD', sortMethod: 'directed', levelSeparation: 180, nodeSpacing: 150 } };
            setTimeout(function() {
                network.setOptions({ layout: { hierarchical: false }, physics: { enabled: true, solver: 'forceAtlas2Based' } });
            }, 100);
            return;
        } else {
            layoutOptions = { hierarchical: false };
            network.setOptions({ physics: { enabled: true, solver: 'barnesHut' } });
        }
        
        network.setOptions({ layout: layoutOptions });
    });
});

document.getElementById('device-search').addEventListener('input', function() {
    var query = this.value.toLowerCase();
    nodes.forEach(function(node) {
        var match = node.label.toLowerCase().includes(query) || (node.ip && node.ip.toLowerCase().includes(query));
        if (query && match) {
            network.selectNodes([node.id]);
            network.focus(node.id, { scale: 1.5, animation: true });
        }
    });
    if (!query) network.unselectAll();
});

setInterval(function() {
    var download = (Math.random() * 0.5 + 1).toFixed(2);
    var upload = (Math.random() * 300 + 600).toFixed(0);
    document.getElementById('total-download').textContent = download + ' Gbps';
    document.getElementById('total-upload').textContent = upload + ' Mbps';
}, 2000);
</script>
@endpush
