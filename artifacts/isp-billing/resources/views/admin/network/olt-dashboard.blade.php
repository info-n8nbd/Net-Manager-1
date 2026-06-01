@extends('layouts.admin')

@section('title', 'OLT Dashboard - ' . $olt->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.olt.index') }}">OLT Devices</a></li>
<li class="breadcrumb-item active">{{ $olt->name }} Dashboard</li>
@endsection

@section('content')
@if(isset($showAlarms) && $showAlarms)
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bell mr-2"></i>Alarms - {{ $olt->name }}</h3>
        <div class="card-tools">
            <a href="{{ route('admin.olt.dashboard', $olt) }}" class="btn btn-sm btn-default"><i class="fas fa-arrow-left mr-1"></i>Back to Dashboard</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Serial</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Port/ONU</th>
                        <th>Rx Power</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alarms as $alarm)
                    <tr>
                        <td><code>{{ $alarm->serial_number }}</code></td>
                        <td><span class="badge badge-warning">{{ $alarm->alarm_type }}</span></td>
                        <td>
                            @if($alarm->severity === 'critical')
                                <span class="badge badge-danger">Critical</span>
                            @elseif($alarm->severity === 'major')
                                <span class="badge badge-warning">Major</span>
                            @else
                                <span class="badge badge-info">{{ ucfirst($alarm->severity) }}</span>
                            @endif
                        </td>
                        <td>{{ $alarm->pon_port ?? 'N/A' }} / {{ $alarm->onu_number ?? 'N/A' }}</td>
                        <td>{{ $alarm->rx_power ? $alarm->rx_power . ' dBm' : 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($alarm->created_at)->diffForHumans() }}</td>
                        <td>
                            @if($alarm->is_resolved)
                                <span class="badge badge-success">Resolved</span>
                            @else
                                <span class="badge badge-danger">Active</span>
                            @endif
                        </td>
                        <td>
                            @if(!$alarm->is_resolved)
                            <button class="btn btn-success btn-sm" onclick="resolveAlarm({{ $alarm->id }})">
                                <i class="fas fa-check"></i> Resolve
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No alarms found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($alarms, 'links'))
    <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $alarms->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script></div>
    @endif
</div>
@elseif(isset($showLogs) && $showLogs)
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Provision Logs - {{ $olt->name }}</h3>
        <div class="card-tools">
            <a href="{{ route('admin.olt.dashboard', $olt) }}" class="btn btn-sm btn-default"><i class="fas fa-arrow-left mr-1"></i>Back to Dashboard</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Time</th>
                        <th>Serial</th>
                        <th>Action</th>
                        <th>Port</th>
                        <th>Profile</th>
                        <th>VLAN</th>
                        <th>Result</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs ?? [] as $log)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d-m-y H:i') }}</td>
                        <td><code>{{ $log->serial_number }}</code></td>
                        <td>
                            @if($log->action === 'provision')
                                <span class="badge badge-success">Provision</span>
                            @else
                                <span class="badge badge-danger">Decommission</span>
                            @endif
                        </td>
                        <td>{{ $log->pon_port }}</td>
                        <td>{{ $log->service_profile ?? 'N/A' }}</td>
                        <td>{{ $log->vlan ?? 'N/A' }}</td>
                        <td>
                            @if($log->result === 'success')
                                <span class="badge badge-success">Success</span>
                            @else
                                <span class="badge badge-danger">Failed</span>
                            @endif
                        </td>
                        <td>{{ $log->performed_by_name ?? 'System' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No provision logs found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="row mb-3">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-tachometer-alt mr-2"></i>{{ $olt->name }} <small class="text-muted">({{ $olt->vendor_name }} - {{ $olt->ip_address }})</small></h4>
            <div>
                <button id="pollDashboardBtn" onclick="pollDashboard({{ $olt->id }})" class="btn btn-success btn-sm"><i class="fas fa-sync-alt mr-1"></i>Refresh Data</button>
                <button id="scanMatchBtnTop" onclick="scanAndMatch({{ $olt->id }})" class="btn btn-dark btn-sm"><i class="fas fa-project-diagram mr-1"></i>Scan &amp; Match</button>
                <a href="{{ route('admin.olt.onus', $olt) }}" class="btn btn-primary btn-sm"><i class="fas fa-list mr-1"></i>Manage ONUs</a>
                <a href="{{ route('admin.olt.alarms', $olt) }}" class="btn btn-warning btn-sm"><i class="fas fa-bell mr-1"></i>Alarms</a>
                <a href="{{ route('admin.olt.provision-logs', $olt) }}" class="btn btn-info btn-sm"><i class="fas fa-history mr-1"></i>Logs</a>
                <a href="{{ route('admin.olt.index') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left mr-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalOnus }}</h3>
                <p>Total ONUs</p>
            </div>
            <div class="icon"><i class="fas fa-network-wired"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $onlineOnus }}</h3>
                <p>Online ONUs</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $offlineOnus }}</h3>
                <p>Offline ONUs</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $alarmCount }}</h3>
                <p>Active Alarms</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>OLT System Information</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="font-weight-bold" style="width:40%">Vendor / Model</td>
                        <td>{{ $olt->vendor_name }} {{ $olt->model ? '/ ' . $olt->model : '' }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">PON Type</td>
                        <td>{{ $olt->pon_type_name }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Management IP</td>
                        <td><code>{{ $olt->ip_address }}</code></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Connection</td>
                        <td>
                            @php
                                $connTypes = ['telnet'=>'Telnet','ssh'=>'SSH','http'=>'HTTP (Web)','snmp'=>'SNMP'];
                                $connLabel = $connTypes[$olt->connection_type ?? ''] ?? ucfirst($olt->connection_type ?? 'N/A');
                                if ($olt->connection_type !== 'snmp' && $olt->snmp_enabled) {
                                    $connLabel .= ' + SNMP';
                                }
                            @endphp
                            {{ $connLabel }}
                        </td>
                    </tr>
                    @if($olt->snmp_enabled)
                    <tr>
                        <td class="font-weight-bold">SNMP Port</td>
                        <td><code>{{ $olt->snmp_port ?? 161 }}</code> &nbsp;<small class="text-muted">(Community: {{ $olt->snmp_community ?? 'public' }})</small></td>
                    </tr>
                    @endif
                    @if($olt->system_description)
                    <tr>
                        <td class="font-weight-bold">System Description</td>
                        <td><small>{!! nl2br(e($olt->system_description)) !!}</small></td>
                    </tr>
                    @endif
                    @if($olt->firmware_version)
                    <tr>
                        <td class="font-weight-bold">Firmware</td>
                        <td>{{ $olt->firmware_version }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="font-weight-bold">Uptime</td>
                        <td>
                            @php
                                $uptimeSec = $olt->uptime_seconds ?? ($latestSnmpStat->uptime_seconds ?? null);
                            @endphp
                            @if($uptimeSec)
                                @php
                                    $days  = floor($uptimeSec / 86400);
                                    $hours = floor(($uptimeSec % 86400) / 3600);
                                    $mins  = floor(($uptimeSec % 3600) / 60);
                                @endphp
                                {{ $days }}d {{ $hours }}h {{ $mins }}m
                            @else
                                <span class="text-muted">N/A <small>(click Refresh Data)</small></span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Last Polled</td>
                        <td>{{ $olt->last_sync ? \Carbon\Carbon::parse($olt->last_sync)->diffForHumans() : 'Never' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h3 class="card-title"><i class="fas fa-microchip mr-2"></i>OLT Health Metrics</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-microchip mr-1"></i>CPU Usage</span>
                        <strong>{{ $olt->cpu_usage !== null ? round($olt->cpu_usage) . '%' : 'N/A' }}</strong>
                    </div>
                    @if($olt->cpu_usage !== null)
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-{{ $olt->cpu_usage < 60 ? 'success' : ($olt->cpu_usage < 80 ? 'warning' : 'danger') }}" style="width: {{ min($olt->cpu_usage, 100) }}%"></div>
                    </div>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-memory mr-1"></i>Memory Usage</span>
                        <strong>{{ $olt->memory_usage !== null ? round($olt->memory_usage) . '%' : 'N/A' }}</strong>
                    </div>
                    @if($olt->memory_usage !== null)
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-{{ $olt->memory_usage < 60 ? 'info' : ($olt->memory_usage < 80 ? 'warning' : 'danger') }}" style="width: {{ min($olt->memory_usage, 100) }}%"></div>
                    </div>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-thermometer-half mr-1"></i>Temperature</span>
                        <strong>{{ $olt->temperature !== null ? round($olt->temperature, 1) . '°C' : 'N/A' }}</strong>
                    </div>
                    @if($olt->temperature !== null)
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-{{ $olt->temperature < 50 ? 'success' : ($olt->temperature < 65 ? 'warning' : 'danger') }}" style="width: {{ min($olt->temperature, 100) }}%"></div>
                    </div>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-signal mr-1"></i>Avg Rx Power</span>
                        @php
                            $displayRxPower = $avgRxPower ?? ($latestSnmpStat->avg_rx_power ?? null);
                        @endphp
                        <strong>{{ $displayRxPower !== null ? $displayRxPower . ' dBm' : 'N/A' }}</strong>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <span><i class="fas fa-location-arrow mr-1"></i>Location</span>
                    <strong>{{ $olt->location ?: 'N/A' }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Port Utilization</h3>
                <div class="card-tools">
                    @if($olt->snmp_enabled)
                    <button class="btn btn-sm btn-outline-primary mr-1" onclick="openPortDescEditor()" title="Edit port descriptions via SNMP">
                        <i class="fas fa-edit mr-1"></i>Edit Descriptions
                    </button>
                    @endif
                    <button class="btn btn-sm btn-outline-success mr-1" id="syncPonTxBtn" onclick="syncPonTx()" title="Sync OLT PON TX Power"
                        data-url="{{ route('admin.olt.sync-pon-tx', $olt) }}">
                        <i class="fas fa-bolt mr-1"></i>Sync Pon Tx
                    </button>
                    <button class="btn btn-tool" onclick="refreshPortData()"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div id="portUtilizationChart">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Port</th>
                                    <th>Description</th>
                                    <th>Total ONUs</th>
                                    <th>Online</th>
                                    <th>Offline</th>
                                    <th style="white-space:nowrap;">Olt Pon Tx<br><small class="text-muted font-weight-normal" style="font-size:10px;">PON Module</small></th>
                                    <th>Port Capacity<br><small class="text-muted font-weight-normal" style="font-size:10px;">Total / Max ONUs</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $ponType = strtolower($olt->pon_type ?? 'epon');
                                    $maxOnu  = $ponType === 'gpon' ? 128 : 64;
                                @endphp
                                @php $portMetrics = $portMetrics ?? collect(); @endphp
                                    @forelse($portSummary as $port)
                                <tr>
                                    <td><strong>{{ $port->pon_port }}</strong></td>
                                    <td class="small" style="max-width:220px">
                                        <span class="port-desc-display text-muted" id="desc-{{ $port->pon_port }}">{{ $portDescriptions[$port->pon_port] ?? $portDescriptions[strtoupper($port->pon_port)] ?? '—' }}</span>
                                    </td>
                                    <td>{{ $port->total }}</td>
                                    <td><span class="text-success">{{ $port->online }}</span></td>
                                    <td><span class="text-danger">{{ $port->offline }}</span></td>
                                    <td class="text-center" id="ponTxCell-{{ str_replace('/', '-', strtoupper($port->pon_port)) }}">
                                        @php
                                            $portKey  = strtoupper($port->pon_port);
                                            $portMet  = $portMetrics[$portKey] ?? null;
                                            $txPow    = $portMet->tx_power ?? null;
                                        @endphp
                                        @if($txPow !== null)
                                            @php
                                                $txColor = $txPow >= 6 ? 'success' : ($txPow >= 3 ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge badge-{{ $txColor }}" title="Temp: {{ $portMet->temperature ?? '—' }}°C | Voltage: {{ $portMet->voltage ?? '—' }}V | Bias: {{ $portMet->tx_bias ?? '—' }}mA" style="font-size:12px; cursor:default;">
                                                {{ number_format($txPow, 1) }} dBm
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $capPct = round(($port->total / $maxOnu) * 100); @endphp
                                        <div class="progress progress-sm mb-1">
                                            <div class="progress-bar bg-{{ $capPct >= 80 ? 'danger' : ($capPct >= 50 ? 'warning' : 'info') }}" style="width: {{ min($capPct, 100) }}%"></div>
                                        </div>
                                        <small><strong>{{ $capPct }}%</strong> <span class="text-muted">({{ $port->total }}/{{ $maxOnu }})</span></small>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">No port data available</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-signal mr-2"></i>Signal Quality</h3>
            </div>
            <div class="card-body">
                @if($signalDistribution)
                @php
                    $sqBase = route('admin.olt.onus', $olt);
                @endphp
                <div class="mb-3">
                    <a href="{{ $sqBase }}?signal_quality=excellent" class="text-decoration-none d-block" title="View Excellent ONUs">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-circle text-success mr-1"></i>Excellent (&ge; -15 dBm)</span>
                        <strong class="text-success">{{ $signalDistribution->excellent ?? 0 }}</strong>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: {{ $totalOnus > 0 ? round((($signalDistribution->excellent ?? 0) / max($totalOnus, 1)) * 100) : 0 }}%"></div>
                    </div>
                    </a>
                </div>
                <div class="mb-3">
                    <a href="{{ $sqBase }}?signal_quality=good" class="text-decoration-none d-block" title="View Good ONUs">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-circle text-info mr-1"></i>Good (-20 to -15 dBm)</span>
                        <strong class="text-info">{{ $signalDistribution->good ?? 0 }}</strong>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-info" style="width: {{ $totalOnus > 0 ? round((($signalDistribution->good ?? 0) / max($totalOnus, 1)) * 100) : 0 }}%"></div>
                    </div>
                    </a>
                </div>
                <div class="mb-3">
                    <a href="{{ $sqBase }}?signal_quality=fair" class="text-decoration-none d-block" title="View Fair ONUs">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-circle text-warning mr-1"></i>Fair (-25 to -20 dBm)</span>
                        <strong class="text-warning">{{ $signalDistribution->fair ?? 0 }}</strong>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" style="width: {{ $totalOnus > 0 ? round((($signalDistribution->fair ?? 0) / max($totalOnus, 1)) * 100) : 0 }}%"></div>
                    </div>
                    </a>
                </div>
                <div class="mb-3">
                    <a href="{{ $sqBase }}?signal_quality=poor" class="text-decoration-none d-block" title="View Poor ONUs">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-circle text-danger mr-1"></i>Poor (&lt; -25 dBm)</span>
                        <strong class="text-danger">{{ $signalDistribution->poor ?? 0 }}</strong>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" style="width: {{ $totalOnus > 0 ? round((($signalDistribution->poor ?? 0) / max($totalOnus, 1)) * 100) : 0 }}%"></div>
                    </div>
                    </a>
                </div>
                @else
                <p class="text-center text-muted">No signal data available</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Quick Actions</h3>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.olt.onus', $olt) }}" class="btn btn-primary btn-block mb-2">
                    <i class="fas fa-list mr-1"></i>Manage ONUs
                </a>
                <a href="{{ route('admin.olt.onus', ['olt' => $olt, 'status' => 'offline']) }}" class="btn btn-danger btn-block mb-2">
                    <i class="fas fa-times-circle mr-1"></i>View Offline ONUs
                </a>
                <a href="{{ route('admin.olt.alarms', $olt) }}" class="btn btn-warning btn-block mb-2">
                    <i class="fas fa-bell mr-1"></i>View Alarms ({{ $alarmCount }})
                </a>
                <a href="{{ route('admin.olt.provision-logs', $olt) }}" class="btn btn-info btn-block mb-2">
                    <i class="fas fa-history mr-1"></i>Provision History
                </a>
                <a href="{{ route('admin.olt.edit', $olt) }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-cog mr-1"></i>OLT Settings
                </a>
                <hr class="my-2">
                <button id="scanMatchBtn" onclick="scanAndMatch({{ $olt->id }})" class="btn btn-dark btn-block">
                    <i class="fas fa-project-diagram mr-1"></i>Scan &amp; Match ONUs
                </button>
                <small class="text-muted d-block text-center mt-1">Match ONUs to clients via SNMP</small>
            </div>
        </div>
    </div>
</div>


@if(isset($trafficStats) && $trafficStats->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Port Traffic Statistics</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Port</th>
                                <th>Description</th>
                                <th>Download (In)</th>
                                <th>Upload (Out)</th>
                                <th>In Rate</th>
                                <th>Out Rate</th>
                                <th>ONUs</th>
                                <th>Online</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trafficStats as $ts)
                            @php
                                $tsPortName = $idxToPort[$ts->port_no] ?? null;
                                $tsDesc = $tsPortName ? ($portDescriptions[$tsPortName] ?? null) : null;
                            @endphp
                            <tr>
                                <td><strong>{{ $tsPortName ?? ('Port '.$ts->port_no) }}</strong></td>
                                <td class="small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $tsDesc ?? '' }}">{{ $tsDesc ?? '—' }}</td>
                                <td>{{ number_format($ts->in_octets / 1073741824, 2) }} GB</td>
                                <td>{{ number_format($ts->out_octets / 1073741824, 2) }} GB</td>
                                <td>
                                    @if($ts->in_rate_bps > 1000000)
                                        {{ number_format($ts->in_rate_bps / 1000000, 1) }} Mbps
                                    @elseif($ts->in_rate_bps > 1000)
                                        {{ number_format($ts->in_rate_bps / 1000, 1) }} Kbps
                                    @else
                                        {{ $ts->in_rate_bps }} bps
                                    @endif
                                </td>
                                <td>
                                    @if($ts->out_rate_bps > 1000000)
                                        {{ number_format($ts->out_rate_bps / 1000000, 1) }} Mbps
                                    @elseif($ts->out_rate_bps > 1000)
                                        {{ number_format($ts->out_rate_bps / 1000, 1) }} Kbps
                                    @else
                                        {{ $ts->out_rate_bps }} bps
                                    @endif
                                </td>
                                <td>{{ $ts->total_onu }}</td>
                                <td><span class="text-success">{{ $ts->online_onu }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h3 class="card-title"><i class="fas fa-chart-area mr-2"></i>Signal Quality Trend</h3>
                <div class="card-tools">
                    <small class="text-muted">Last {{ count($signalHistory ?? []) }} polls &mdash; click Refresh Data to add new point</small>
                </div>
            </div>
            <div class="card-body">
                @if(isset($signalHistory) && count($signalHistory) > 0)
                <canvas id="signalTrendChart" height="80"></canvas>
                @else
                <p class="text-center text-muted py-4"><i class="fas fa-info-circle mr-1"></i>No trend data yet. Click <strong>Refresh Data</strong> a few times to start building history.</p>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-2"></i>Recent Alarms</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.olt.alarms', $olt) }}" class="btn btn-sm btn-default">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Serial</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Rx Power</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAlarms as $alarm)
                            <tr>
                                <td><code>{{ $alarm->serial_number }}</code></td>
                                <td><span class="badge badge-warning">{{ $alarm->alarm_type }}</span></td>
                                <td>
                                    @if($alarm->severity === 'critical')
                                        <span class="badge badge-danger">Critical</span>
                                    @elseif($alarm->severity === 'major')
                                        <span class="badge badge-warning">Major</span>
                                    @else
                                        <span class="badge badge-info">{{ ucfirst($alarm->severity) }}</span>
                                    @endif
                                </td>
                                <td>{{ $alarm->rx_power ? $alarm->rx_power . ' dBm' : 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($alarm->created_at)->diffForHumans() }}</td>
                                <td>
                                    @if($alarm->is_resolved)
                                        <span class="badge badge-success">Resolved</span>
                                    @else
                                        <span class="badge badge-danger">Active</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$alarm->is_resolved)
                                    <button class="btn btn-success btn-xs" onclick="resolveAlarm({{ $alarm->id }})">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-3 text-muted">No recent alarms</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- OLT Port Description Editor Modal --}}
@if($olt->snmp_enabled)
<div class="modal fade" id="portDescModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit OLT Port Descriptions (via SNMP)</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="portDescModalBody">
                <p class="text-muted small"><i class="fas fa-info-circle mr-1"></i>Changes are written directly to the OLT via SNMP SET (ifAlias). Make sure SNMP write community is configured.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="120">Port</th>
                                <th>Description</th>
                                <th width="100" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="portDescTableBody">
                            @forelse($portSummary as $port)
                            <tr id="portrow-{{ $port->pon_port }}">
                                <td><strong>{{ $port->pon_port }}</strong></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm port-desc-input"
                                        id="input-{{ $port->pon_port }}"
                                        data-port="{{ strtoupper($port->pon_port) }}"
                                        value="{{ $portDescriptions[$port->pon_port] ?? $portDescriptions[strtoupper($port->pon_port)] ?? '' }}"
                                        placeholder="Enter description (e.g. Area A - Main Road)">
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-success btn-sm" onclick="savePortDesc('{{ strtoupper($port->pon_port) }}')" id="savebtn-{{ $port->pon_port }}">
                                        <i class="fas fa-save mr-1"></i>Save
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted">No ports found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="portDescFeedback" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveAllPortDescs()" id="saveAllBtn">
                    <i class="fas fa-save mr-1"></i>Save All
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var portDescUrl = '{{ route("admin.olt.set-port-description", $olt) }}';
var csrfToken  = $('meta[name="csrf-token"]').attr('content');

function openPortDescEditor() {
    $('#portDescFeedback').html('');
    $('#portDescModal').modal('show');
}

function savePortDesc(portName) {
    var inputId  = 'input-' + portName.replace('/', '-');
    var input    = $('[data-port="' + portName + '"]');
    var desc     = input.val().trim();
    var btn      = $('#savebtn-' + portName.replace('/', '-'));
    var origHtml = btn.html();

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
        url:  portDescUrl,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: { port: portName, description: desc },
        success: function(res) {
            if (res.success) {
                $('#desc-' + portName.replace('/', '-')).text(desc || '—');
                showFeedback('success', '<i class="fas fa-check mr-1"></i>' + res.message);
                input.addClass('border-success');
                setTimeout(function(){ input.removeClass('border-success'); }, 2000);
            } else {
                showFeedback('danger', '<i class="fas fa-times mr-1"></i>' + res.message);
            }
        },
        error: function(xhr) {
            showFeedback('danger', 'Request failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
        },
        complete: function() {
            btn.prop('disabled', false).html(origHtml);
        }
    });
}

function saveAllPortDescs() {
    var inputs = $('.port-desc-input');
    var saveAllBtn = $('#saveAllBtn');
    saveAllBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving...');

    var promises = [];
    inputs.each(function() {
        var portName = $(this).data('port');
        var desc = $(this).val().trim();
        promises.push($.ajax({
            url:  portDescUrl,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { port: portName, description: desc }
        }).then(function(res) {
            if (res.success) {
                $('#desc-' + portName.replace('/', '-')).text(desc || '—');
            }
            return res;
        }));
    });

    Promise.all(promises.map(function(p){ return p.catch(function(e){ return {success:false, message:'Failed'}; }); }))
        .then(function(results) {
            var failed = results.filter(function(r){ return !r.success; });
            if (failed.length === 0) {
                showFeedback('success', '<i class="fas fa-check-circle mr-1"></i>All port descriptions saved successfully!');
            } else {
                showFeedback('warning', failed.length + ' port(s) failed to save. Check SNMP write access.');
            }
            saveAllBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Save All');
        });
}

function showFeedback(type, msg) {
    $('#portDescFeedback').html('<div class="alert alert-' + type + ' py-2 mb-0">' + msg + '</div>');
}
</script>
@endif

@endsection

@push('scripts')
@if(isset($signalHistory) && count($signalHistory) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var historyData = {!! json_encode($signalHistory->map(function($r) {
        return [
            'label' => \Carbon\Carbon::parse($r->polled_at)->format('d M H:i'),
            'excellent' => (int)$r->excellent_onu,
            'good'      => (int)$r->good_onu,
            'fair'      => (int)$r->fair_onu,
            'poor'      => (int)$r->poor_onu,
        ];
    })->values()) !!};

    var labels    = historyData.map(function(d){ return d.label; });
    var excellent = historyData.map(function(d){ return d.excellent; });
    var good      = historyData.map(function(d){ return d.good; });
    var fair      = historyData.map(function(d){ return d.fair; });
    var poor      = historyData.map(function(d){ return d.poor; });

    var ctx = document.getElementById('signalTrendChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Excellent (≥-15)', data: excellent, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.15)', fill: true, tension: 0.3, pointRadius: 4 },
                { label: 'Good (-20 to -15)', data: good, borderColor: '#17a2b8', backgroundColor: 'rgba(23,162,184,0.15)', fill: true, tension: 0.3, pointRadius: 4 },
                { label: 'Fair (-25 to -20)', data: fair, borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,0.15)', fill: true, tension: 0.3, pointRadius: 4 },
                { label: 'Poor (<-25)', data: poor, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.15)', fill: true, tension: 0.3, pointRadius: 4 },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        footer: function(items) {
                            var total = items.reduce(function(s, i){ return s + i.parsed.y; }, 0);
                            return 'Total with signal: ' + total;
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 }, title: { display: true, text: 'ONU Count' } }
            }
        }
    });
})();
</script>
@endif
<script>
function resolveAlarm(alarmId) {
    smartConfirm({message:'Resolve this alarm?',confirmText:'Resolve'}, function() {
    $.ajax({
        url: '/admin/network/olt/alarms/' + alarmId + '/resolve',
        type: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Failed to resolve alarm');
            }
        },
        error: function() { alert('Error resolving alarm'); }
    });
    }); // end smartConfirm
}

function syncPonTx() {
    var btn = document.getElementById('syncPonTxBtn');
    if (!btn) return;
    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Syncing...';

    $.ajax({
        url:  btn.dataset.url,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        timeout: 240000,
        success: function(res) {
            if (res.success) {
                if (res.metrics) {
                    $.each(res.metrics, function(port, m) {
                        var cellId = 'ponTxCell-' + port.replace('/', '-');
                        var cell = document.getElementById(cellId);
                        if (cell && m.tx_power !== null) {
                            var color = m.tx_power >= 6 ? 'success' : (m.tx_power >= 3 ? 'warning' : 'danger');
                            var temp  = m.temperature ? m.temperature + '°C' : '—';
                            var volt  = m.voltage ? m.voltage + 'V' : '—';
                            var bias  = m.tx_bias ? m.tx_bias + 'mA' : '—';
                            cell.innerHTML = '<span class="badge badge-' + color + '" title="Temp: ' + temp + ' | Voltage: ' + volt + ' | Bias: ' + bias + '" style="font-size:12px;cursor:default;">' + parseFloat(m.tx_power).toFixed(1) + ' dBm</span>';
                        }
                    });
                }
                if (typeof toastr !== 'undefined') toastr.success(res.message || 'Olt Pon Tx synced');
                else alert(res.message || 'Synced OK');
            } else {
                if (typeof toastr !== 'undefined') toastr.warning(res.message || 'Sync failed');
                else alert('Sync failed: ' + (res.message || ''));
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : xhr.statusText;
            if (typeof toastr !== 'undefined') toastr.error('Sync error: ' + msg);
            else alert('Sync error: ' + msg);
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    });
}

function refreshPortData() {
    $.get('{{ route("admin.olt.port-utilization", $olt) }}', function(res) {
        if (res.success) {
            location.reload();
        }
    });
}

function scanAndMatch(oltId) {
    var btn = document.getElementById('scanMatchBtn');
    var btnTop = document.getElementById('scanMatchBtnTop');
    var originalHtml = '<i class="fas fa-project-diagram mr-1"></i>Scan &amp; Match ONUs';
    var originalHtmlTop = '<i class="fas fa-project-diagram mr-1"></i>Scan &amp; Match';

    if (btn) btn.disabled = true;
    if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Scanning...';
    if (btnTop) btnTop.disabled = true;
    if (btnTop) btnTop.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Scanning...';

    $.ajax({
        url: '/admin/network/olt/' + oltId + '/scan-match',
        type: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        timeout: 120000,
        success: function(res) {
            if (res.success) {
                var msg = res.message + '\n\nMapped: ' + res.mapped + ' | Skipped: ' + res.skipped;
                if (res.mapped > 0) {
                    toastr.success(res.message, 'ONU Match Complete', {timeOut: 8000});
                } else {
                    toastr.warning(res.message, 'ONU Scan Complete', {timeOut: 8000});
                }
                // Show log details in a small toast
                var successLogs = (res.log || []).filter(function(l){ return l.level === 'success'; });
                successLogs.forEach(function(l) {
                    toastr.info(l.msg, 'Matched', {timeOut: 10000});
                });
            } else {
                toastr.error(res.message || 'Scan failed', 'Error');
            }
        },
        error: function(xhr) {
            toastr.error('Scan & Match request failed. Check console.', 'Error');
        },
        complete: function() {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
            if (btnTop) { btnTop.disabled = false; btnTop.innerHTML = originalHtmlTop; }
        }
    });
}

function pollDashboard(oltId) {
    var btn = $('#pollDashboardBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Refreshing...');
    $.ajax({
        url: '/admin/network/olt/' + oltId + '/poll-dashboard',
        type: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(res) {
            if (res.success) {
                toastr.success(res.message || 'Dashboard data refreshed!');
                setTimeout(function() { location.reload(); }, 1200);
            } else {
                toastr.error(res.message || 'Refresh failed');
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>Refresh Data');
            }
        },
        error: function() {
            toastr.error('Failed to refresh dashboard data');
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>Refresh Data');
        }
    });
}
</script>
@endpush
