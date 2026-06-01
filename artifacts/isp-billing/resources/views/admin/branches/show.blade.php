@extends('layouts.admin')

@section('title', 'Branch Details')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-code-branch mr-2"></i>Branch Information</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Name</th>
                        <td>{{ $branch->name }} @if($branch->is_main)<span class="badge badge-info">Main</span>@endif</td>
                    </tr>
                    <tr>
                        <th>Code</th>
                        <td>{{ $branch->code }}</td>
                    </tr>
                    <tr>
                        <th>Manager</th>
                        <td>{{ $branch->manager->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>{{ $branch->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $branch->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $branch->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Contact Person</th>
                        <td>{{ $branch->contact_person ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($branch->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                </table>
                <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-info btn-sm btn-block"><i class="fas fa-edit mr-1"></i> Edit Branch</a>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total_clients'] ?? 0 }}</h3>
                        <p>Total Clients</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-md-6 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['active_clients'] ?? 0 }}</h3>
                        <p>Active Clients</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['total_pops'] ?? 0 }}</h3>
                        <p>POPs</p>
                    </div>
                    <div class="icon"><i class="fas fa-network-wired"></i></div>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['total_zones'] ?? 0 }}</h3>
                        <p>Zones</p>
                    </div>
                    <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>৳{{ number_format($stats['monthly_revenue'] ?? 0) }}</h3>
                        <p>Monthly Revenue</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

    {{-- POPs under this branch --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>POPs Under This Branch</h3>
                    <a href="{{ route('admin.pops.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> Add POP
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Zone</th>
                                    <th>Bandwidth (Used/Total)</th>
                                    <th>Uplink Provider</th>
                                    <th>MikroTik</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branch->pops as $pop)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><a href="{{ route('admin.pops.show', $pop) }}">{{ $pop->name }}</a></td>
                                    <td><code>{{ $pop->code }}</code></td>
                                    <td>{{ $pop->zone->name ?? '-' }}</td>
                                    <td>
                                        {{ $pop->used_bandwidth_mbps ?? 0 }} / {{ $pop->total_bandwidth_mbps ?? 0 }} Mbps
                                        @if(($pop->total_bandwidth_mbps ?? 0) > 0)
                                            @php $bwPct = min((($pop->used_bandwidth_mbps ?? 0) / $pop->total_bandwidth_mbps * 100), 100); @endphp
                                            <div class="progress progress-xs mt-1">
                                                <div class="progress-bar bg-{{ $bwPct > 80 ? 'danger' : ($bwPct > 60 ? 'warning' : 'success') }}" style="width:{{ $bwPct }}%"></div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $pop->uplink_provider ?? '-' }}</td>
                                    <td>{{ $pop->mikrotikDevice->name ?? '-' }}</td>
                                    <td>
                                        @if($pop->status == 'active')
                                            <span class="badge badge-success">Active</span>
                                        @elseif($pop->status == 'maintenance')
                                            <span class="badge badge-warning">Maintenance</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.pops.show', $pop) }}" class="btn btn-primary btn-xs" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="{{ route('admin.pops.edit', $pop) }}" class="btn btn-info btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center text-muted py-3">No POPs assigned to this branch</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Zones under this branch --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Zones Under This Branch</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Zone Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branch->zones as $zone)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $zone->name }}</td>
                                    <td>{{ $zone->description ?? '-' }}</td>
                                    <td>
                                        @if($zone->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No zones assigned to this branch</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
