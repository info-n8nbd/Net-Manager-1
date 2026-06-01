@extends('layouts.admin')

@section('title', 'Fiber Analytics')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Inventory</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
    <li class="breadcrumb-item active">Analytics</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalStocks }}</h3>
                <p>Total Stocks</p>
            </div>
            <div class="icon"><i class="fa-solid fa-box"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $activeStocks }}</h3>
                <p>Active</p>
            </div>
            <div class="icon"><i class="fa-solid fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $finishedStocks }}</h3>
                <p>Finished</p>
            </div>
            <div class="icon"><i class="fa-solid fa-times-circle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format($totalMeter) }}m</h3>
                <p>Total Meter</p>
            </div>
            <div class="icon"><i class="fa-solid fa-ruler"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($usedMeter) }}m</h3>
                <p>Used Meter</p>
            </div>
            <div class="icon"><i class="fa-solid fa-ruler-combined"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ number_format($remainingMeter) }}m</h3>
                <p>Remaining Meter</p>
            </div>
            <div class="icon"><i class="fa-solid fa-ruler-horizontal"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-chart-pie"></i> Utilization</h3>
            </div>
            <div class="card-body text-center">
                @php
                    $utilizationPercent = $totalMeter > 0 ? round(($usedMeter / $totalMeter) * 100, 1) : 0;
                @endphp
                <div style="position:relative; display:inline-block;">
                    <canvas id="utilizationChart" width="200" height="200"></canvas>
                    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center;">
                        <h2 class="mb-0">{{ $utilizationPercent }}%</h2>
                        <small class="text-muted">Utilized</small>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="mb-1"><strong>{{ number_format($usedMeter) }}m</strong> used of <strong>{{ number_format($totalMeter) }}m</strong> total</p>
                    <p class="text-muted mb-0"><strong>{{ number_format($remainingMeter) }}m</strong> remaining</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-chart-bar"></i> Usage by Month</h3>
            </div>
            <div class="card-body">
                <canvas id="usageByMonthChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-user-gear"></i> Usage by Technician</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Technician</th>
                            <th>Total Used (m)</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usageByTechnician as $index => $tech)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><i class="fa-solid fa-user"></i> {{ $tech->technician_name ?? 'Unknown' }}</td>
                            <td><strong>{{ number_format($tech->total_used, 2) }}m</strong></td>
                            <td>
                                @php
                                    $techPercent = $usedMeter > 0 ? round(($tech->total_used / $usedMeter) * 100, 1) : 0;
                                @endphp
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-info" style="width: {{ $techPercent }}%">{{ $techPercent }}%</div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No technician usage data available.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    var utilizationPercent = {{ $utilizationPercent }};
    new Chart(document.getElementById('utilizationChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Used', 'Remaining'],
            datasets: [{
                data: [{{ $usedMeter }}, {{ $remainingMeter }}],
                backgroundColor: ['#17a2b8', '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            cutout: '75%',
            plugins: {
                legend: { display: false }
            }
        }
    });

    var monthLabels = {!! json_encode($usageByMonth->pluck('month')->reverse()->values()) !!};
    var monthData = {!! json_encode($usageByMonth->pluck('total_used')->reverse()->values()) !!};

    new Chart(document.getElementById('usageByMonthChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Meter Used',
                data: monthData,
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Meters' }
                },
                x: {
                    title: { display: true, text: 'Month' }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
@endpush
