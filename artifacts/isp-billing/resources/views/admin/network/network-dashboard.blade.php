@extends('layouts.admin')

@section('title', 'Network Operations Dashboard')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item active">Operations Dashboard</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between">
        <h4 class="mb-0"><i class="fas fa-tachometer-alt mr-2"></i>Network Operations Dashboard</h4>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('admin.network.devices') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-server mr-1"></i>MikroTik Devices
            </a>
            <a href="{{ route('admin.olt.index') }}" class="btn btn-success btn-sm">
                <i class="fas fa-network-wired mr-1"></i>OLT Devices
            </a>
            <a href="{{ route('admin.vpn.index') }}" class="btn btn-info btn-sm">
                <i class="fas fa-shield-alt mr-1"></i>VPN Accounts
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $summary['routers']['online'] ?? 0 }}<small>/{{ $summary['routers']['total'] ?? 0 }}</small></h3>
                <p>Total Routers</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
            <a href="{{ route('admin.network.devices') }}" class="small-box-footer">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $summary['routers']['core'] ?? 0 }}</h3>
                <p>Core Routers</p>
            </div>
            <div class="icon">
                <i class="fas fa-sitemap"></i>
            </div>
            <a href="{{ route('admin.network.devices') }}" class="small-box-footer">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $summary['routers']['noc'] ?? 0 }}</h3>
                <p>NOC Routers</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
            <a href="{{ route('admin.network.devices') }}" class="small-box-footer">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $summary['routers']['pop'] ?? 0 }}</h3>
                <p>POP Routers</p>
            </div>
            <div class="icon">
                <i class="fas fa-broadcast-tower"></i>
            </div>
            <a href="{{ route('admin.network.devices') }}" class="small-box-footer">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-teal">
            <div class="inner">
                <h3>{{ $summary['routers']['branch'] ?? 0 }}</h3>
                <p>Branch Routers</p>
            </div>
            <div class="icon">
                <i class="fas fa-code-branch"></i>
            </div>
            <a href="{{ route('admin.network.devices') }}" class="small-box-footer">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $summary['olts']['active'] ?? 0 }}<small>/{{ $summary['olts']['total'] ?? 0 }}</small></h3>
                <p>Total OLTs</p>
            </div>
            <div class="icon">
                <i class="fas fa-network-wired"></i>
            </div>
            <a href="{{ route('admin.olt.index') }}" class="small-box-footer">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-purple">
            <div class="inner">
                <h3>{{ $summary['onus']['online'] ?? 0 }}<small>/{{ $summary['onus']['total'] ?? 0 }}</small></h3>
                <p>Total ONUs</p>
            </div>
            <div class="icon">
                <i class="fas fa-broadcast-tower"></i>
            </div>
            <a href="{{ route('admin.olt.index') }}" class="small-box-footer">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box {{ ($summary['active_alarms'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' }}">
            <div class="inner">
                <h3>{{ $summary['active_alarms'] ?? 0 }}</h3>
                <p>Active Alarms</p>
            </div>
            <div class="icon">
                <i class="fas fa-bell"></i>
            </div>
            <span class="small-box-footer">&nbsp;</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-heartbeat mr-2"></i>Router Health Overview</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.network.devices') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list mr-1"></i>View All
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>CPU</th>
                                <th>Memory</th>
                                <th>Uptime</th>
                                <th>Version</th>
                                <th>Last Check</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary['router_health'] ?? [] as $rh)
                            @php
                                $cpuLoad = $rh['cpu_load'] ?? 0;
                                $cpuColor = $cpuLoad >= 80 ? 'danger' : ($cpuLoad >= 50 ? 'warning' : 'success');
                                $memUsage = $rh['memory_usage'] ?? 0;
                                $memColor = $memUsage >= 80 ? 'danger' : ($memUsage >= 50 ? 'warning' : 'success');
                            @endphp
                            <tr style="cursor:pointer" onclick="window.location='{{ route('admin.network.devices.detail', $rh['id']) }}'">
                                <td><strong>{{ $rh['name'] }}</strong></td>
                                <td>
                                    <span class="badge badge-{{ ($rh['router_type'] ?? '') === 'core' ? 'primary' : 'info' }}">
                                        {{ ucfirst($rh['router_type'] ?? 'branch') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ ($rh['status'] ?? '') === 'online' ? 'success' : 'danger' }}">
                                        <i class="fas fa-{{ ($rh['status'] ?? '') === 'online' ? 'check-circle' : 'times-circle' }} mr-1"></i>{{ ucfirst($rh['status'] ?? 'offline') }}
                                    </span>
                                </td>
                                <td style="min-width:120px">
                                    <div class="d-flex align-items-center">
                                        <span class="mr-2" style="min-width:35px">{{ $cpuLoad }}%</span>
                                        <div class="progress flex-grow-1" style="height:8px">
                                            <div class="progress-bar bg-{{ $cpuColor }}" style="width: {{ $cpuLoad }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="min-width:120px">
                                    <div class="d-flex align-items-center">
                                        <span class="mr-2" style="min-width:35px">{{ $memUsage }}%</span>
                                        <div class="progress flex-grow-1" style="height:8px">
                                            <div class="progress-bar bg-{{ $memColor }}" style="width: {{ $memUsage }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $rh['uptime'] ?? 'N/A' }}</td>
                                <td>{{ $rh['version'] ?? 'N/A' }}</td>
                                <td>{{ !empty($rh['last_checked_at']) ? \Carbon\Carbon::parse($rh['last_checked_at'])->diffForHumans() : 'Never' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle mr-1"></i>No router health data available
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>OLT Overview</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.olt.index') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-list mr-1"></i>View All
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Name</th>
                                <th>Vendor</th>
                                <th>IP Address</th>
                                <th>Ports</th>
                                <th>ONUs</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($olts as $olt)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('admin.olt.dashboard', $olt->id) }}'">
                                <td><strong>{{ $olt->name }}</strong></td>
                                <td>{{ $olt->vendor_name ?? ucfirst($olt->vendor ?? 'N/A') }}</td>
                                <td><code>{{ $olt->ip_address }}</code></td>
                                <td>{{ $olt->total_ports ?? 'N/A' }}</td>
                                <td>{{ $olt->onu_count ?? 0 }}</td>
                                <td>
                                    <span class="badge badge-{{ $olt->is_active ? 'success' : 'danger' }}">
                                        <i class="fas fa-{{ $olt->is_active ? 'check-circle' : 'times-circle' }} mr-1"></i>{{ $olt->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle mr-1"></i>No OLT devices configured
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Network Events</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Source</th>
                                <th>Message</th>
                                <th>Severity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(collect($summary['recent_events'] ?? [])->take(10) as $event)
                            @php
                                $severityColors = [
                                    'critical' => 'danger',
                                    'warning' => 'warning',
                                    'info' => 'info',
                                ];
                                $severityColor = $severityColors[$event->severity ?? 'info'] ?? 'secondary';
                            @endphp
                            <tr>
                                <td><small>{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</small></td>
                                <td><span class="badge badge-secondary">{{ $event->event_type ?? 'N/A' }}</span></td>
                                <td>{{ $event->source ?? 'N/A' }}</td>
                                <td><small>{{ Str::limit($event->message ?? '', 60) }}</small></td>
                                <td>
                                    <span class="badge badge-{{ $severityColor }}">
                                        {{ ucfirst($event->severity ?? 'info') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle mr-1"></i>No recent network events
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
