@extends('layouts.admin')

@section('title', 'BTRC IP Assignment Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.btrc.index') }}">BTRC Reports</a></li>
<li class="breadcrumb-item active">IP Assignment</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>IP Assignment Report</h3>
        <div class="card-tools">
            <a href="{{ route('admin.btrc.ip-assignment', array_merge(request()->all(), ['export' => 'excel'])) }}" class="btn btn-primary btn-sm mr-1">
                <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
            <a href="{{ route('admin.btrc.ip-assignment', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-csv mr-1"></i> Export CSV
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ ($filters['zone_id'] ?? '') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> Filter</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            Total IP Assignments: <strong>{{ count($data) }}</strong>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="bg-info text-white">
                    <tr>
                        <th>SL</th>
                        <th>Client ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>IP Address</th>
                        <th>MAC Address</th>
                        <th>Connection Type</th>
                        <th>Zone</th>
                        <th>Assignment Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['client_id'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td><code>{{ $row['username'] }}</code></td>
                        <td><code>{{ $row['ip_address'] }}</code></td>
                        <td><code>{{ $row['mac_address'] }}</code></td>
                        <td><span class="badge badge-info">{{ $row['connection_type'] }}</span></td>
                        <td>{{ $row['zone'] }}</td>
                        <td>{{ $row['assignment_date'] }}</td>
                        <td>
                            <span class="badge badge-{{ $row['status'] == 'Active' ? 'success' : 'warning' }}">
                                {{ $row['status'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">No IP assignments found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
