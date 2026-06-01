@extends('layouts.admin')

@section('title', 'BTRC Compliance Reports')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Reports</li>
<li class="breadcrumb-item active">BTRC Compliance</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>BTRC Compliance Reports</strong> - Generate reports required by Bangladesh Telecommunication Regulatory Commission
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($statistics['total_subscribers'] ?? 0) }}</h3>
                <p>Total Subscribers</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($statistics['active_subscribers']) }}</h3>
                <p>Active Subscribers</p>
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($statistics['inactive_subscribers'] ?? $statistics['suspended_subscribers'] ?? 0) }}</h3>
                <p>Inactive</p>
            </div>
            <div class="icon"><i class="fas fa-user-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format($statistics['new_this_month']) }}</h3>
                <p>New This Month</p>
            </div>
            <div class="icon"><i class="fas fa-user-plus"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Available Reports</h3>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.btrc.subscriber-list') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-users text-primary mr-2"></i> Subscriber List Report</span>
                        <span class="badge badge-primary">Required</span>
                    </a>
                    <a href="{{ route('admin.btrc.new-connections') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-plus text-success mr-2"></i> New Connection Report</span>
                        <span class="badge badge-success">Monthly</span>
                    </a>
                    <a href="{{ route('admin.btrc.disconnections') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-minus text-danger mr-2"></i> Disconnection Report</span>
                        <span class="badge badge-danger">Monthly</span>
                    </a>
                    <a href="{{ route('admin.btrc.ip-assignment') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-network-wired text-info mr-2"></i> IP Assignment Report</span>
                        <span class="badge badge-info">Required</span>
                    </a>
                    <a href="{{ route('admin.btrc.monthly-revenue') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-money-bill-wave text-warning mr-2"></i> Monthly Revenue Report</span>
                        <span class="badge badge-warning">Financial</span>
                    </a>
                    <a href="{{ route('admin.btrc.official-format') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="background:#e8f5e9;">
                        <span><i class="fas fa-file-excel text-success mr-2"></i> <strong>Official BTRC Format (Clients List)</strong></span>
                        <span class="badge badge-success">BTRC Submit</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Connection Type Distribution</h3>
            </div>
            <div class="card-body">
                <canvas id="connectionTypeChart" height="200"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Subscribers by Zone</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th class="text-right">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statistics['by_zone'] as $zone => $count)
                        <tr>
                            <td>{{ $zone }}</td>
                            <td class="text-right"><strong>{{ number_format($count) }}</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('connectionTypeChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($statistics['by_connection_type'])) !!},
        datasets: [{
            data: {!! json_encode(array_values($statistics['by_connection_type'])) !!},
            backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8'],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>
@endpush
