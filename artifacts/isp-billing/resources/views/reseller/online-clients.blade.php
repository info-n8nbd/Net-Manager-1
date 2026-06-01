@extends('layouts.reseller')

@section('title', 'Online Clients')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Online Clients</li>
@endsection

@section('content')
<div class="card card-success card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-wifi mr-2"></i>Online Clients</h3>
        <div class="card-tools">
            <span class="badge badge-success">{{ $onlineClients->count() }} Online</span>
            <span class="badge badge-secondary ml-1">{{ $clients->count() }} Total</span>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Username</th>
                    <th>Package</th>
                    <th>IP Address</th>
                    <th>Uptime</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($onlineClients as $i => $oc)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('reseller.clients.show', $oc['client']->id ?? $oc['client_id'] ?? 0) }}">
                                {{ $oc['client']->name ?? $oc['name'] ?? 'N/A' }}
                            </a>
                        </td>
                        <td><code>{{ $oc['username'] ?? $oc['client']->username ?? 'N/A' }}</code></td>
                        <td>{{ $oc['client']->package->name ?? 'N/A' }}</td>
                        <td><code>{{ $oc['address'] ?? $oc['ip_address'] ?? 'N/A' }}</code></td>
                        <td>{{ $oc['uptime'] ?? 'N/A' }}</td>
                        <td><span class="badge badge-success"><i class="fas fa-circle mr-1" style="font-size:8px;"></i>Online</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-wifi fa-2x mb-2 d-block text-muted"></i>
                            No clients are currently online.
                            <br><small>This feature requires MikroTik router to be accessible from the server.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
