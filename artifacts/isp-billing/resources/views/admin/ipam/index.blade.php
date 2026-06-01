@extends('layouts.admin')

@section('title', 'IP Address Management')

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.ipam.create') }}" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> Create IP Pool
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-globe mr-2"></i>IPv4 Pools</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Network/CIDR</th>
                        <th>Type</th>
                        <th>Branch</th>
                        <th>Used/Total</th>
                        <th>Utilization</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $ipv4Pools = ($pools ?? collect())->where('ip_version', 'ipv4'); @endphp
                    @forelse($ipv4Pools as $pool)
                    @php $utilization = $pool->total_ips > 0 ? round(($pool->used_ips / $pool->total_ips) * 100) : 0; @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><a href="{{ route('admin.ipam.show', $pool) }}">{{ $pool->name }}</a></td>
                        <td><code>{{ $pool->network_address }}/{{ $pool->cidr_prefix }}</code></td>
                        <td><span class="badge badge-info">{{ ucfirst($pool->pool_type) }}</span></td>
                        <td>{{ $pool->branch->name ?? '-' }}</td>
                        <td>{{ $pool->used_ips ?? 0 }} / {{ $pool->total_ips ?? 0 }}</td>
                        <td style="min-width: 120px;">
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-{{ $utilization > 90 ? 'danger' : ($utilization > 70 ? 'warning' : 'success') }}" style="width: {{ $utilization }}%"></div>
                            </div>
                            <small>{{ $utilization }}%</small>
                        </td>
                        <td>
                            <a href="{{ route('admin.ipam.show', $pool) }}" class="btn btn-info btn-xs"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.ipam.edit', $pool) }}" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.ipam.destroy', $pool) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No IPv4 pools found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-globe-americas mr-2"></i>IPv6 Pools</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Network/CIDR</th>
                        <th>Type</th>
                        <th>Branch</th>
                        <th>Used/Total</th>
                        <th>Utilization</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $ipv6Pools = ($pools ?? collect())->where('ip_version', 'ipv6'); @endphp
                    @forelse($ipv6Pools as $pool)
                    @php $utilization = $pool->total_ips > 0 ? round(($pool->used_ips / $pool->total_ips) * 100) : 0; @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><a href="{{ route('admin.ipam.show', $pool) }}">{{ $pool->name }}</a></td>
                        <td><code>{{ $pool->network_address }}/{{ $pool->cidr_prefix }}</code></td>
                        <td><span class="badge badge-info">{{ ucfirst($pool->pool_type) }}</span></td>
                        <td>{{ $pool->branch->name ?? '-' }}</td>
                        <td>{{ $pool->used_ips ?? 0 }} / {{ $pool->total_ips ?? 0 }}</td>
                        <td style="min-width: 120px;">
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-{{ $utilization > 90 ? 'danger' : ($utilization > 70 ? 'warning' : 'success') }}" style="width: {{ $utilization }}%"></div>
                            </div>
                            <small>{{ $utilization }}%</small>
                        </td>
                        <td>
                            <a href="{{ route('admin.ipam.show', $pool) }}" class="btn btn-info btn-xs"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.ipam.edit', $pool) }}" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.ipam.destroy', $pool) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No IPv6 pools found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
