@extends('layouts.admin')

@section('title', 'IP Pool Details')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Pool Information</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><th>Name</th><td>{{ $pool->name }}</td></tr>
                    <tr><th>IP Version</th><td><span class="badge badge-primary">{{ strtoupper($pool->ip_version) }}</span></td></tr>
                    <tr><th>Network</th><td><code>{{ $pool->network_address }}/{{ $pool->cidr_prefix }}</code></td></tr>
                    <tr><th>Subnet Mask</th><td>{{ $pool->subnet_mask ?? '-' }}</td></tr>
                    <tr><th>Gateway</th><td>{{ $pool->gateway }}</td></tr>
                    <tr><th>DNS Primary</th><td>{{ $pool->dns_primary ?? '-' }}</td></tr>
                    <tr><th>DNS Secondary</th><td>{{ $pool->dns_secondary ?? '-' }}</td></tr>
                    <tr><th>Type</th><td><span class="badge badge-info">{{ ucfirst($pool->pool_type) }}</span></td></tr>
                    <tr><th>Branch</th><td>{{ $pool->branch->name ?? '-' }}</td></tr>
                    <tr><th>Zone</th><td>{{ $pool->zone->name ?? '-' }}</td></tr>
                    <tr><th>POP</th><td>{{ $pool->pop->name ?? '-' }}</td></tr>
                    <tr><th>Description</th><td>{{ $pool->description ?? '-' }}</td></tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($pool->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                </table>
                <a href="{{ route('admin.ipam.edit', $pool) }}" class="btn btn-info btn-sm btn-block"><i class="fas fa-edit mr-1"></i> Edit Pool</a>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-4 col-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $pool->assigned_ips ?? 0 }}</h3>
                        <p>Assigned</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-4 col-4">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $pool->reserved_ips ?? 0 }}</h3>
                        <p>Reserved</p>
                    </div>
                    <div class="icon"><i class="fas fa-lock"></i></div>
                </div>
            </div>
            <div class="col-md-4 col-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ ($pool->total_ips ?? 0) - ($pool->used_ips ?? 0) }}</h3>
                        <p>Available</p>
                    </div>
                    <div class="icon"><i class="fas fa-plus-circle"></i></div>
                </div>
            </div>
        </div>

        @php
            $totalIps = $pool->total_ips ?? 1;
            $assigned = $pool->assigned_ips ?? 0;
            $reserved = $pool->reserved_ips ?? 0;
            $available = $totalIps - ($assigned + $reserved);
        @endphp
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Utilization ({{ $totalIps }} Total IPs)</h3>
            </div>
            <div class="card-body">
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar bg-success" style="width: {{ ($assigned / $totalIps) * 100 }}%">Assigned {{ $assigned }}</div>
                    <div class="progress-bar bg-warning" style="width: {{ ($reserved / $totalIps) * 100 }}%">Reserved {{ $reserved }}</div>
                    <div class="progress-bar bg-info" style="width: {{ ($available / $totalIps) * 100 }}%">Available {{ $available }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>IP Allocations</h3>
                <form action="{{ route('admin.ipam.allocate', $pool) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Allocate IP</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>Client</th>
                                <th>Assigned By</th>
                                <th>Assigned At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allocations ?? [] as $allocation)
                            <tr>
                                <td><code>{{ $allocation->ip_address }}</code></td>
                                <td>
                                    @if($allocation->status == 'assigned')
                                        <span class="badge badge-success">Assigned</span>
                                    @elseif($allocation->status == 'reserved')
                                        <span class="badge badge-warning">Reserved</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($allocation->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $allocation->client->name ?? '-' }}</td>
                                <td>{{ $allocation->assignedBy->name ?? '-' }}</td>
                                <td>{{ $allocation->assigned_at ? $allocation->assigned_at->format('d-m-y H:i') : '-' }}</td>
                                <td>
                                    <form action="{{ route('admin.ipam.release', [$pool, $allocation]) }}" method="POST" class="d-inline" onsubmit="return confirm('Release this IP?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-times"></i> Release</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted">No allocations</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
