@extends('layouts.admin')

@section('title', 'POP Details')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>POP Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><th>Name</th><td>{{ $pop->name }}</td></tr>
                    <tr><th>Code</th><td>{{ $pop->code }}</td></tr>
                    <tr><th>Branch</th><td>{{ $pop->branch->name ?? '-' }}</td></tr>
                    <tr><th>Zone</th><td>{{ $pop->zone->name ?? '-' }}</td></tr>
                    <tr><th>Address</th><td>{{ $pop->address ?? '-' }}</td></tr>
                    <tr><th>Contact</th><td>{{ $pop->contact_person ?? '-' }} {{ $pop->contact_phone ? '('.$pop->contact_phone.')' : '' }}</td></tr>
                    <tr><th>Bandwidth</th><td>{{ $pop->used_bandwidth_mbps ?? 0 }} / {{ $pop->total_bandwidth_mbps ?? 0 }} Mbps</td></tr>
                    <tr><th>Uplink</th><td>{{ $pop->uplink_provider ?? '-' }} ({{ $pop->uplink_type ?? '-' }})</td></tr>
                    <tr><th>MikroTik</th><td>{{ $pop->mikrotikDevice->name ?? '-' }}</td></tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($pop->status == 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($pop->status == 'maintenance')
                                <span class="badge badge-warning">Maintenance</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                </table>
                <a href="{{ route('admin.pops.edit', $pop) }}" class="btn btn-info btn-sm btn-block"><i class="fas fa-edit mr-1"></i> Edit POP</a>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Associated Clients</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Package</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pop->clients ?? [] as $client)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></td>
                                <td>{{ $client->username ?? $client->client_id }}</td>
                                <td>{{ $client->package->name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $client->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($client->status) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No clients assigned</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>IP Pools</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Network</th>
                                <th>Type</th>
                                <th>Used/Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pop->ipPools ?? [] as $pool)
                            <tr>
                                <td>{{ $pool->name }}</td>
                                <td>{{ $pool->network_address }}/{{ $pool->cidr_prefix }}</td>
                                <td>{{ ucfirst($pool->pool_type) }}</td>
                                <td>{{ $pool->used_ips ?? 0 }} / {{ $pool->total_ips ?? 0 }}</td>
                                <td><a href="{{ route('admin.ipam.show', $pool) }}" class="btn btn-info btn-xs"><i class="fas fa-eye"></i></a></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No IP pools</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
