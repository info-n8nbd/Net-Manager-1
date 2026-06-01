@extends('layouts.admin')

@section('title', 'BTRC Disconnections Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.btrc.index') }}">BTRC Reports</a></li>
<li class="breadcrumb-item active">Disconnections</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-minus mr-2"></i>Disconnection Report</h3>
        <div class="card-tools">
            <a href="{{ route('admin.btrc.disconnections', ['from_date' => $fromDate, 'to_date' => $toDate, 'export' => 'excel']) }}" class="btn btn-primary btn-sm mr-1">
                <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
            <a href="{{ route('admin.btrc.disconnections', ['from_date' => $fromDate, 'to_date' => $toDate, 'export' => 'csv']) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-csv mr-1"></i> Export CSV
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> Filter</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="alert alert-danger">
            <i class="fas fa-info-circle mr-2"></i>
            Period: <strong>{{ $fromDate }}</strong> to <strong>{{ $toDate }}</strong> | 
            Total Disconnections: <strong>{{ count($data) }}</strong>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="bg-danger text-white">
                    <tr>
                        <th>SL</th>
                        <th>Client ID</th>
                        <th>Name</th>
                        <th>NID</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Zone</th>
                        <th>Joining Date</th>
                        <th>Disconnection Date</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['client_id'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['nid'] }}</td>
                        <td>{{ $row['phone'] }}</td>
                        <td>{{ Str::limit($row['address'], 30) }}</td>
                        <td>{{ $row['zone'] }}</td>
                        <td>{{ $row['connection_date'] }}</td>
                        <td>{{ $row['disconnection_date'] }}</td>
                        <td>{{ $row['reason'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">No disconnections in this period</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
