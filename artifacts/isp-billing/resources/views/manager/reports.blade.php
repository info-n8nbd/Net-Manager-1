@extends('layouts.manager')

@section('title', 'Reports')

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-gradient-success text-white">
            <div class="card-body text-center">
                <h5>Total Revenue</h5>
                <h2 class="font-weight-bold">৳{{ number_format($totalRevenue ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-info text-white">
            <div class="card-body text-center">
                <h5>This Month Revenue</h5>
                <h2 class="font-weight-bold">৳{{ number_format($monthlyRevenue ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-danger text-white">
            <div class="card-body text-center">
                <h5>Outstanding Due</h5>
                <h2 class="font-weight-bold">৳{{ number_format($outstandingDue ?? 0) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Client Status Breakdown</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td><span class="badge badge-success">Active</span></td>
                            <td class="text-right font-weight-bold">{{ $activeClients ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-secondary">Inactive</span></td>
                            <td class="text-right font-weight-bold">{{ $inactiveClients ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td class="text-right font-weight-bold">{{ ($activeClients ?? 0) + ($inactiveClients ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Collection Trend</h3>
            </div>
            <div class="card-body">
                <canvas id="collectionTrendChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-store mr-2"></i>Reseller Performance</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Reseller</th>
                        <th>Total Clients</th>
                        <th>Active</th>
                        <th>Collection</th>
                        <th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellerPerformance ?? [] as $reseller)
                    <tr>
                        <td>{{ $reseller->name }}</td>
                        <td>{{ $reseller->total_clients ?? 0 }}</td>
                        <td>{{ $reseller->active_clients ?? 0 }}</td>
                        <td class="text-success">৳{{ number_format($reseller->collection ?? 0) }}</td>
                        <td class="text-danger">৳{{ number_format($reseller->due ?? 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var ctx = document.getElementById('collectionTrendChart');
if(ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($trendLabels ?? []) !!},
            datasets: [{
                label: 'Collection (৳)',
                data: {!! json_encode($trendData ?? []) !!},
                borderColor: '#7d2d2d',
                backgroundColor: 'rgba(125, 45, 45, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#7d2d2d'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}
</script>
@endpush