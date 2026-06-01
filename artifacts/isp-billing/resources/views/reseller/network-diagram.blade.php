@extends('layouts.reseller')
@section('title', 'Network Diagram')
@section('breadcrumb')<li class="breadcrumb-item active">Network Diagram</li>@endsection

@section('content')
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $devices->count() }}</h3><p>MikroTik Devices</p></div>
            <div class="icon"><i class="fas fa-server"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $olts->count() }}</h3><p>OLT Devices</p></div>
            <div class="icon"><i class="fas fa-network-wired"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner"><h3>{{ $clientCount }}</h3><p>My Clients</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Network Overview</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5><i class="fas fa-server text-primary mr-2"></i>MikroTik Routers</h5>
                <div class="list-group">
                    @forelse($devices as $device)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $device->name }}</strong>
                            <br><small class="text-muted">{{ $device->ip_address }}</small>
                        </div>
                        <span class="badge badge-{{ $device->is_connected ? 'success' : 'danger' }}">
                            {{ $device->is_connected ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    @empty
                    <p class="text-muted">No MikroTik devices configured</p>
                    @endforelse
                </div>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-network-wired text-success mr-2"></i>OLT Devices</h5>
                <div class="list-group">
                    @forelse($olts as $olt)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $olt->name }}</strong>
                            <br><small class="text-muted">{{ $olt->ip_address }}</small>
                        </div>
                        <span class="badge badge-{{ $olt->is_connected ? 'success' : 'danger' }}">
                            {{ $olt->is_connected ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    @empty
                    <p class="text-muted">No OLT devices configured</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
