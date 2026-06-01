@extends('layouts.admin')

@section('title', 'Employee Collection Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Reports</a></li>
<li class="breadcrumb-item active">Employee Collection</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-check mr-2"></i>Employee Collection Report</h3>
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
            <div class="col-4 mb-2">
                <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
                    <div class="stat-number">৳{{ number_format(collect($report)->sum('total_amount'), 0) }}</div>
                    <div class="stat-label">Collection</div>
                </div>
            </div>
            <div class="col-4 mb-2">
                <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ collect($report)->sum('payment_count') }}</div>
                    <div class="stat-label">Payments</div>
                </div>
            </div>
            <div class="col-4 mb-2">
                <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ count($report) }}</div>
                    <div class="stat-label">Collectors</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee Name</th>
                                <th class="text-center">Payments</th>
                                <th class="text-right">Total Amount (৳)</th>
                                <th class="text-center">Contribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalAmount = collect($report)->sum('total_amount');
                            @endphp
                            @forelse($report as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $row['employee_name'] }}</strong></td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ $row['payment_count'] }}</span>
                                </td>
                                <td class="text-right text-success">
                                    <strong>৳{{ number_format($row['total_amount'] ?? 0, 2) }}</strong>
                                </td>
                                <td class="text-center">
                                    @php
                                        $percentage = $totalAmount > 0 ? ($row['total_amount'] / $totalAmount) * 100 : 0;
                                    @endphp
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-primary" style="width: {{ $percentage }}%;">
                                            {{ number_format($percentage, 1) }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No collection data available for selected period
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th colspan="2">Total</th>
                                <th class="text-center">{{ collect($report)->sum('payment_count') }}</th>
                                <th class="text-right text-success">৳{{ number_format($totalAmount ?? 0, 2) }}</th>
                                <th class="text-center">100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <canvas id="employeeChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.reports.export.pdf', ['type' => 'employee-collection', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-danger">
            <i class="fas fa-file-pdf mr-1"></i> Export PDF
        </a>
        <a href="{{ route('admin.reports.export.excel', ['type' => 'employee-collection', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-success">
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
    
    new Chart(document.getElementById('employeeChart'), {
        type: 'bar',
        data: {
            labels: report.map(r => r.employee_name),
            datasets: [{
                label: 'Collection (৳)',
                data: report.map(r => r.total_amount),
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
@endpush
