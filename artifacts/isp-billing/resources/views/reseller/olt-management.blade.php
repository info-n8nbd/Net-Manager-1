@extends('layouts.reseller')
@section('title', 'OLT Management')
@section('breadcrumb')<li class="breadcrumb-item active">OLT Management</li>@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif

@if($olts->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-network-wired fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">No OLT Devices Assigned</h5>
        <p class="text-muted">None of your clients are connected to an OLT device yet. Contact your administrator to assign OLT connections.</p>
    </div>
</div>
@else

@foreach($olts as $olt)
@php
    $isActive = $olt->status === 'active' || $olt->is_active;
    $oltClients = $clientsByOlt[$olt->id] ?? collect();
    $onlineCount  = $oltClients->where('onu_live_status', 'online')->count();
    $offlineCount = $oltClients->where('onu_live_status', 'offline')->count();
    $unknownCount = $oltClients->whereNull('onu_live_status')->count();
@endphp
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="border-left: 4px solid {{ $isActive ? '#28a745' : '#dc3545' }};">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-network-wired mr-2 text-primary"></i>
                {{ $olt->name }}
                <span class="badge badge-{{ $isActive ? 'success' : 'secondary' }} ml-2" style="font-size: 11px;">{{ $isActive ? 'Active' : 'Inactive' }}</span>
            </h5>
            <small class="text-muted">
                <i class="fas fa-globe mr-1"></i>{{ $olt->ip_address }}
                @if($olt->vendor) &nbsp;|&nbsp; <i class="fas fa-tag mr-1"></i>{{ ucfirst($olt->vendor) }} @endif
                @if($olt->model) {{ $olt->model }} @endif
                @if($olt->total_ports) &nbsp;|&nbsp; <i class="fas fa-plug mr-1"></i>{{ $olt->total_ports }} Ports @endif
                @if($olt->location) &nbsp;|&nbsp; <i class="fas fa-map-marker-alt mr-1"></i>{{ $olt->location }} @endif
            </small>
        </div>
        <div class="text-right">
            <span class="badge badge-success mr-1"><i class="fas fa-circle mr-1"></i>{{ $onlineCount }} Online</span>
            <span class="badge badge-danger mr-1"><i class="fas fa-circle mr-1"></i>{{ $offlineCount }} Offline</span>
            @if($unknownCount > 0)
            <span class="badge badge-secondary"><i class="fas fa-circle mr-1"></i>{{ $unknownCount }} Unknown</span>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        @if($oltClients->isEmpty())
        <div class="text-center py-3 text-muted"><small>No clients connected to this OLT.</small></div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Client</th>
                        <th>ONU Serial</th>
                        <th>OLT Port</th>
                        <th>ONU Status</th>
                        <th>RX Power</th>
                        <th>Signal Quality</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($oltClients as $client)
                    @php
                        $onuStatus = $client->onu_live_status;
                        $rxPower   = $client->onu_live_rx;
                        $quality   = $client->onu_live_quality;
                        $rxColor   = $rxPower !== null ? ($rxPower >= -20 ? 'success' : ($rxPower >= -27 ? 'warning' : 'danger')) : 'secondary';
                        $statusColor = $onuStatus === 'online' ? 'success' : ($onuStatus === 'offline' ? 'danger' : 'secondary');
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('reseller.clients.show', $client) }}" class="font-weight-bold text-dark">{{ $client->name }}</a>
                            <br><small class="text-muted">{{ $client->client_id }}</small>
                        </td>
                        <td><code style="font-size:11px;">{{ $client->onu_serial ?? '—' }}</code></td>
                        <td><span class="badge badge-light border">{{ $client->olt_port ?? '—' }}</span></td>
                        <td>
                            @if($onuStatus)
                            <span class="badge badge-{{ $statusColor }}">{{ ucfirst($onuStatus) }}</span>
                            @if($onuStatus === 'offline' && $client->onu_down_reason)
                            <br><small class="text-danger" title="{{ $client->onu_down_reason }}">{{ \Illuminate\Support\Str::limit($client->onu_down_reason, 22) }}</small>
                            @endif
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($rxPower !== null)
                            <span class="text-{{ $rxColor }} font-weight-bold">{{ number_format($rxPower, 2) }} dBm</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($quality)
                            <span class="badge badge-{{ $quality === 'excellent' ? 'success' : ($quality === 'good' ? 'primary' : ($quality === 'fair' ? 'warning' : 'danger')) }}">{{ ucfirst($quality) }}</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><small>{{ $client->package->name ?? '—' }}</small></td>
                        <td>
                            <span class="badge badge-{{ $client->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($client->status) }}</span>
                        </td>
                        <td>
                            <a href="{{ route('reseller.clients.show', $client) }}" class="btn btn-xs btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endforeach

@endif

@if(isset($unassignedClients) && $unassignedClients->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 card-title"><i class="fas fa-question-circle mr-2 text-warning"></i>Clients Without OLT Assignment <span class="badge badge-secondary ml-1">{{ $unassignedClients->count() }}</span></h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Client</th>
                        <th>Package</th>
                        <th>Zone</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unassignedClients as $client)
                    <tr>
                        <td>
                            <a href="{{ route('reseller.clients.show', $client) }}" class="font-weight-bold text-dark">{{ $client->name }}</a>
                            <br><small class="text-muted">{{ $client->client_id }}</small>
                        </td>
                        <td><small>{{ $client->package->name ?? '—' }}</small></td>
                        <td><small>{{ $client->zone->name ?? '—' }}</small></td>
                        <td><span class="badge badge-{{ $client->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($client->status) }}</span></td>
                        <td>
                            <a href="{{ route('reseller.clients.show', $client) }}" class="btn btn-xs btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
