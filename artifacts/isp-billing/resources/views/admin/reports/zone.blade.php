@extends('layouts.admin')

@section('title', 'Zone-wise Revenue Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Reports</a></li>
<li class="breadcrumb-item active">Zone Revenue</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-map-marked-alt mr-2"></i>Zone-wise Revenue Report</h3>
        <div class="card-tools">
            <form class="form-inline" method="GET">
                <div class="input-group input-group-sm mr-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text">From</span>
                    </div>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="input-group input-group-sm mr-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text">To</span>
                    </div>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-6 col-md-4 mb-2">
                <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
                    <i class="fas fa-coins fa-2x mb-2 opacity-75"></i>
                    <div class="stat-number">৳{{ number_format(collect($report)->sum('total_revenue'), 0) }}</div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
            <div class="col-6 col-md-4 mb-2">
                <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                    <i class="fas fa-map-marker-alt fa-2x mb-2 opacity-75"></i>
                    <div class="stat-number">{{ count($report) }}</div>
                    <div class="stat-label">Zones</div>
                </div>
            </div>
            <div class="col-6 col-md-4 mb-2">
                <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
                    <i class="fas fa-users fa-2x mb-2 opacity-75"></i>
                    <div class="stat-number">{{ collect($report)->sum('client_count') }}</div>
                    <div class="stat-label">Clients</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Zone</th>
                                <th class="text-center">Clients</th>
                                <th class="text-right">Revenue (৳)</th>
                                <th class="text-center">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalRevenue = collect($report)->sum('total_revenue');
                            @endphp
                            @forelse($report as $row)
                            <tr>
                                <td><strong>{{ $row['zone_name'] }}</strong></td>
                                <td class="text-center">{{ $row['client_count'] }}</td>
                                <td class="text-right">৳{{ number_format($row['total_revenue'] ?? 0, 2) }}</td>
                                <td class="text-center">
                                    @php
                                        $percentage = $totalRevenue > 0 ? ($row['total_revenue'] / $totalRevenue) * 100 : 0;
                                    @endphp
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: {{ $percentage }}%;">
                                            {{ number_format($percentage, 1) }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No data available for selected period</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th>Total</th>
                                <th class="text-center">{{ collect($report)->sum('client_count') }}</th>
                                <th class="text-right">৳{{ number_format($totalRevenue ?? 0, 2) }}</th>
                                <th class="text-center">100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <canvas id="zoneChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.reports.export.pdf', ['type' => 'zone', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-danger">
            <i class="fas fa-file-pdf mr-1"></i> Export PDF
        </a>
        <a href="{{ route('admin.reports.export.excel', ['type' => 'zone', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-success">
            <i class="fas fa-file-excel mr-1"></i> Export Excel
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const report = @json($report);
    
    new Chart(document.getElementById('zoneChart'), {
        type: 'pie',
        data: {
            labels: report.map(r => r.zone_name),
            datasets: [{
                data: report.map(r => r.total_revenue),
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8',
                    '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d',
                    '#0056b3', '#1e7e34', '#d39e00', '#bd2130', '#138496'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
@endpush
