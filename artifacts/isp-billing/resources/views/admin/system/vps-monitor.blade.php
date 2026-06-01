@extends('layouts.admin')

@section('title', 'VPS Monitor')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-tachometer-alt me-2 text-primary"></i>VPS Resource Monitor</h4>
            <p class="text-muted mb-0">Real-time server CPU, RAM, Disk, and Services status</p>
        </div>
        <div>
            <span class="badge badge-success px-3 py-2" id="live-badge">
                <i class="fas fa-circle fa-xs mr-1 blink-dot"></i> Live — auto-refresh every 10s
            </span>
        </div>
    </div>

    {{-- Row 1: Uptime / Load / CPU / DB --}}
    <div class="row mb-3" id="top-cards">
        <div class="col-6 col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div style="font-size:22px;color:#6c757d;"><i class="fas fa-clock"></i></div>
                    <div class="small text-muted mt-1">Server Uptime</div>
                    <div class="font-weight-bold mt-1" id="val-uptime" style="font-size:17px;">{{ $data['uptime'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div style="font-size:22px;color:#17a2b8;"><i class="fas fa-chart-line"></i></div>
                    <div class="small text-muted mt-1">Load Avg (1/5/15 min)</div>
                    <div class="font-weight-bold mt-1" id="val-load" style="font-size:15px;">
                        {{ $data['load'][0] }} / {{ $data['load'][1] }} / {{ $data['load'][2] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div style="font-size:22px;color:#28a745;"><i class="fas fa-microchip"></i></div>
                    <div class="small text-muted mt-1">CPU Cores</div>
                    <div class="font-weight-bold mt-1" id="val-cores" style="font-size:17px;">{{ $data['cpu']['cores'] }} vCPU</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div style="font-size:22px;color:#fd7e14;"><i class="fas fa-database"></i></div>
                    <div class="small text-muted mt-1">Database Size</div>
                    <div class="font-weight-bold mt-1" id="val-dbsize" style="font-size:17px;">{{ $data['db_size'] }}</div>
                </div>
            </div>
        </div>
    </div>


    {{-- OS Info --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-2 d-flex align-items-center">
                    <i class="fas fa-desktop mr-3" style="font-size:22px;color:#6c757d;"></i>
                    <div>
                        <div class="small text-muted">Operating System</div>
                        <div class="font-weight-bold" style="font-size:15px;" id="val-os">{{ $data['os'] ?? 'Unknown' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 2: CPU / RAM / Swap --}}
    <div class="row mb-3">
        {{-- CPU --}}
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-microchip mr-1 text-primary"></i><strong>CPU Usage</strong></span>
                    <span class="badge badge-primary px-2" id="cpu-badge">{{ $data['cpu']['usage'] }}%</span>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center" style="height:110px;">
                        <div style="position:relative;width:110px;height:110px;">
                            <canvas id="cpu-donut" width="110" height="110"></canvas>
                            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                                <div id="cpu-label" style="font-size:20px;font-weight:700;">{{ $data['cpu']['usage'] }}%</div>
                                <div style="font-size:10px;color:#888;">CPU</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-primary" id="cpu-bar" style="width:{{ $data['cpu']['usage'] }}%"></div>
                        </div>
                    </div>
                    <div class="text-center text-muted small mt-1">{{ $data['cpu']['cores'] }} vCPU core(s)</div>
                </div>
            </div>
        </div>

        {{-- RAM --}}
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-memory mr-1 text-success"></i><strong>RAM Usage</strong></span>
                    <span class="badge badge-success px-2" id="ram-badge">{{ $data['ram']['percent'] }}%</span>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center" style="height:110px;">
                        <div style="position:relative;width:110px;height:110px;">
                            <canvas id="ram-donut" width="110" height="110"></canvas>
                            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                                <div id="ram-label" style="font-size:20px;font-weight:700;">{{ $data['ram']['percent'] }}%</div>
                                <div style="font-size:10px;color:#888;">RAM</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-success" id="ram-bar" style="width:{{ $data['ram']['percent'] }}%"></div>
                        </div>
                    </div>
                    <div class="text-center text-muted small mt-1">
                        Used: <strong id="ram-used">{{ $data['ram']['used'] }}</strong> MB &nbsp;|&nbsp;
                        Free: <strong id="ram-free">{{ $data['ram']['free'] }}</strong> MB &nbsp;|&nbsp;
                        Total: <strong id="ram-total">{{ $data['ram']['total'] }}</strong> MB
                    </div>
                </div>
            </div>
        </div>

        {{-- Swap --}}
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exchange-alt mr-1 text-warning"></i><strong>Swap Usage</strong></span>
                    <span class="badge badge-warning px-2" id="swap-badge">{{ $data['swap']['percent'] }}%</span>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center" style="height:110px;">
                        <div style="position:relative;width:110px;height:110px;">
                            <canvas id="swap-donut" width="110" height="110"></canvas>
                            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                                <div id="swap-label" style="font-size:20px;font-weight:700;">{{ $data['swap']['percent'] }}%</div>
                                <div style="font-size:10px;color:#888;">Swap</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-warning" id="swap-bar" style="width:{{ $data['swap']['percent'] }}%"></div>
                        </div>
                    </div>
                    <div class="text-center text-muted small mt-1">
                        Used: <strong id="swap-used">{{ $data['swap']['used'] }}</strong> MB &nbsp;|&nbsp;
                        Total: <strong id="swap-total">{{ $data['swap']['total'] }}</strong> MB
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 3: Disk --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <i class="fas fa-hdd mr-1 text-danger"></i><strong>Disk Usage</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Device</th>
                                    <th>Mount</th>
                                    <th>Total</th>
                                    <th>Used</th>
                                    <th>Free</th>
                                    <th style="width:200px;">Usage</th>
                                </tr>
                            </thead>
                            <tbody id="disk-table">
                                @foreach($data['disk'] as $disk)
                                <tr>
                                    <td><code>{{ $disk['device'] }}</code></td>
                                    <td><strong>{{ $disk['mount'] }}</strong></td>
                                    <td>{{ $disk['size'] }}</td>
                                    <td>{{ $disk['used'] }}</td>
                                    <td>{{ $disk['free'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 mr-2" style="height:8px;">
                                                <div class="progress-bar {{ $disk['percent'] > 85 ? 'bg-danger' : ($disk['percent'] > 60 ? 'bg-warning' : 'bg-success') }}"
                                                     style="width:{{ $disk['percent'] }}%"></div>
                                            </div>
                                            <span class="small">{{ $disk['percent'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 4: Services + Network --}}
    <div class="row mb-3">
        {{-- Services --}}
        <div class="col-md-5 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2">
                    <i class="fas fa-server mr-1 text-info"></i><strong>Service Status</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="services-table">
                        @foreach($data['services'] as $svc)
                        <tr>
                            <td class="pl-3"><i class="fas fa-circle mr-1 {{ $svc['status'] === 'active' ? 'text-success' : 'text-danger' }}" style="font-size:8px;"></i> {{ $svc['name'] }}</td>
                            <td>
                                @if($svc['status'] === 'active')
                                    <span class="badge badge-success px-2">Running</span>
                                @elseif($svc['status'] === 'inactive')
                                    <span class="badge badge-secondary px-2">Stopped</span>
                                @else
                                    <span class="badge badge-danger px-2">{{ ucfirst($svc['status']) }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>

        {{-- Network --}}
        <div class="col-md-7 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2">
                    <i class="fas fa-network-wired mr-1 text-primary"></i><strong>Network Interfaces (Total since boot)</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="net-table">
                        <thead class="thead-light">
                            <tr><th>Interface</th><th><i class="fas fa-arrow-down text-success"></i> RX (Received)</th><th><i class="fas fa-arrow-up text-danger"></i> TX (Sent)</th></tr>
                        </thead>
                        <tbody>
                            @foreach($data['network'] as $net)
                            <tr>
                                <td><strong>{{ $net['interface'] }}</strong></td>
                                <td>{{ number_format($net['rx_mb'], 2) }} MB</td>
                                <td>{{ number_format($net['tx_mb'], 2) }} MB</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 5: Top Processes --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <i class="fas fa-list mr-1 text-secondary"></i><strong>Top Processes (by CPU)</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>User</th>
                                    <th>CPU %</th>
                                    <th>MEM %</th>
                                    <th>Command</th>
                                </tr>
                            </thead>
                            <tbody id="procs-table">
                                @foreach($data['top'] as $proc)
                                <tr>
                                    <td>{{ $proc['user'] }}</td>
                                    <td>
                                        <span class="badge {{ (float)$proc['cpu'] > 50 ? 'badge-danger' : ((float)$proc['cpu'] > 20 ? 'badge-warning' : 'badge-secondary') }}">
                                            {{ $proc['cpu'] }}%
                                        </span>
                                    </td>
                                    <td>{{ $proc['mem'] }}%</td>
                                    <td><code style="font-size:11px;">{{ $proc['command'] }}</code></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

    {{-- Row 6: App Size Breakdown --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-folder-open mr-1" style="color:#6f42c1;"></i><strong>Application Size Breakdown</strong></span>
                    <span class="badge px-3 py-2" style="background:#6f42c1;color:#fff;font-size:13px;">
                        Total: <strong id="appsize-total">{{ $data['app_size']['total'] }}</strong>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row" id="appsize-breakdown">
                        @foreach($data['app_size']['breakdown'] as $item)
                        @php
                            $pct = $data['app_size']['total_bytes'] > 0
                                ? round(100 * $item['bytes'] / $data['app_size']['total_bytes'], 1)
                                : 0;
                            $colorMap = ['app' => '#28a745', 'vendor' => '#007bff', 'storage' => '#fd7e14', 'public' => '#17a2b8', 'resources' => '#6f42c1', 'config' => '#6c757d'];
                            $barColor = $colorMap[$item['dir']] ?? '#343a40';
                        @endphp
                        <div class="col-md-4 mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-folder mr-1" style="color:{{ $barColor }};"></i><strong>{{ $item['label'] }}</strong></span>
                                <span class="text-muted small">{{ $item['human'] }} &nbsp;<span class="badge badge-light">{{ $pct }}%</span></span>
                            </div>
                            <div class="progress" style="height:10px;border-radius:5px;">
                                <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $barColor }};border-radius:5px;"></div>
                            </div>
                            <div class="text-muted small mt-1"><code>/{{ $item['dir'] }}</code></div>
                        </div>
                        @endforeach
                    </div>
                    <div class="alert alert-light border mt-2 mb-0 py-2 px-3" style="font-size:12px;">
                        <i class="fas fa-info-circle mr-1 text-muted"></i>
                        <strong>Vendor</strong> = PHP libraries (composer). &nbsp;
                        <strong>Storage</strong> = logs, cache, uploaded files. &nbsp;
                        <strong>App Code</strong> = your application source code. &nbsp;
                        Size updates on every page refresh.
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
.blink-dot { animation: blink 1.5s infinite; }
</style>
@endpush

@push('scripts')
<script>
function drawDonut(canvasId, percent, color) {
    var c = document.getElementById(canvasId);
    if (!c) return;
    var ctx = c.getContext('2d');
    var r = 48, cx = 55, cy = 55;
    ctx.clearRect(0, 0, 110, 110);
    ctx.beginPath(); ctx.arc(cx, cy, r, 0, 2*Math.PI);
    ctx.lineWidth = 12; ctx.strokeStyle = '#e9ecef'; ctx.stroke();
    var angle = (percent / 100) * 2 * Math.PI;
    ctx.beginPath(); ctx.arc(cx, cy, r, -Math.PI/2, -Math.PI/2 + angle);
    ctx.lineWidth = 12; ctx.strokeStyle = color; ctx.lineCap = 'round'; ctx.stroke();
}

function initDonuts(data) {
    var cpuColor = data.cpu.usage > 85 ? '#dc3545' : (data.cpu.usage > 60 ? '#fd7e14' : '#007bff');
    var ramColor = data.ram.percent > 85 ? '#dc3545' : (data.ram.percent > 60 ? '#fd7e14' : '#28a745');
    var swapColor = data.swap.percent > 85 ? '#dc3545' : (data.swap.percent > 60 ? '#fd7e14' : '#ffc107');
    drawDonut('cpu-donut',  data.cpu.usage,    cpuColor);
    drawDonut('ram-donut',  data.ram.percent,  ramColor);
    drawDonut('swap-donut', data.swap.percent, swapColor);
}

function updateUI(data) {
    // CPU
    document.getElementById('cpu-label').textContent = data.cpu.usage + '%';
    document.getElementById('cpu-badge').textContent = data.cpu.usage + '%';
    document.getElementById('cpu-bar').style.width   = data.cpu.usage + '%';
    // RAM
    document.getElementById('ram-label').textContent = data.ram.percent + '%';
    document.getElementById('ram-badge').textContent = data.ram.percent + '%';
    document.getElementById('ram-bar').style.width   = data.ram.percent + '%';
    document.getElementById('ram-used').textContent  = data.ram.used;
    document.getElementById('ram-free').textContent  = data.ram.free;
    document.getElementById('ram-total').textContent = data.ram.total;
    // Swap
    document.getElementById('swap-label').textContent = data.swap.percent + '%';
    document.getElementById('swap-badge').textContent  = data.swap.percent + '%';
    document.getElementById('swap-bar').style.width    = data.swap.percent + '%';
    document.getElementById('swap-used').textContent   = data.swap.used;
    document.getElementById('swap-total').textContent  = data.swap.total;
    // Uptime / Load / DB
    document.getElementById('val-uptime').textContent = data.uptime;
    document.getElementById('val-load').textContent   = data.load[0] + ' / ' + data.load[1] + ' / ' + data.load[2];
    document.getElementById('val-dbsize').textContent = data.db_size;
    if (data.app_size) {
        document.getElementById('val-appsize').textContent = data.app_size.total;
        document.getElementById('appsize-total').textContent = data.app_size.total;
    }

    // Disk rows
    var diskHtml = '';
    (data.disk || []).forEach(function(d) {
        var bc = d.percent > 85 ? 'bg-danger' : (d.percent > 60 ? 'bg-warning' : 'bg-success');
        diskHtml += '<tr><td><code>'+d.device+'</code></td><td><strong>'+d.mount+'</strong></td><td>'+d.size+'</td><td>'+d.used+'</td><td>'+d.free+'</td><td><div class="d-flex align-items-center"><div class="progress flex-grow-1 mr-2" style="height:8px;"><div class="progress-bar '+bc+'" style="width:'+d.percent+'%"></div></div><span class="small">'+d.percent+'%</span></div></td></tr>';
    });
    document.getElementById('disk-table').innerHTML = diskHtml;

    // Services
    var svcHtml = '';
    (data.services || []).forEach(function(s) {
        var dot  = s.status === 'active' ? 'text-success' : 'text-danger';
        var badge = s.status === 'active' ? '<span class="badge badge-success px-2">Running</span>' : (s.status === 'inactive' ? '<span class="badge badge-secondary px-2">Stopped</span>' : '<span class="badge badge-danger px-2">'+s.status+'</span>');
        svcHtml += '<tr><td class="pl-3"><i class="fas fa-circle mr-1 '+dot+'" style="font-size:8px;"></i> '+s.name+'</td><td>'+badge+'</td></tr>';
    });
    document.getElementById('services-table').innerHTML = svcHtml;

    // Network
    var netHtml = '<thead class="thead-light"><tr><th>Interface</th><th><i class="fas fa-arrow-down text-success"></i> RX (Received)</th><th><i class="fas fa-arrow-up text-danger"></i> TX (Sent)</th></tr></thead><tbody>';
    (data.network || []).forEach(function(n) {
        netHtml += '<tr><td><strong>'+n.interface+'</strong></td><td>'+parseFloat(n.rx_mb).toFixed(2)+' MB</td><td>'+parseFloat(n.tx_mb).toFixed(2)+' MB</td></tr>';
    });
    netHtml += '</tbody>';
    document.getElementById('net-table').innerHTML = netHtml;

    // Processes
    var procHtml = '';
    (data.top || []).forEach(function(p) {
        var bc = parseFloat(p.cpu) > 50 ? 'badge-danger' : (parseFloat(p.cpu) > 20 ? 'badge-warning' : 'badge-secondary');
        procHtml += '<tr><td>'+p.user+'</td><td><span class="badge '+bc+'">'+p.cpu+'%</span></td><td>'+p.mem+'%</td><td><code style="font-size:11px;">'+p.command+'</code></td></tr>';
    });
    document.getElementById('procs-table').innerHTML = procHtml;

    initDonuts(data);
}

// Initial draw
var initData = @json($data);
initDonuts(initData);

// Auto refresh every 10 seconds
function fetchStats() {
    fetch('{{ route("admin.system.vps-stats") }}', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(d){ updateUI(d); })
        .catch(function(e){ console.warn('VPS stats fetch failed', e); });
}
setInterval(fetchStats, 10000);
</script>
@endpush
