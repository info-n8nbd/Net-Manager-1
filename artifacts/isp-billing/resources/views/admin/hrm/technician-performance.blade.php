@extends('layouts.admin')

@section('title', 'Technician Performance Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.technicians') }}">Technicians</a></li>
<li class="breadcrumb-item active">Performance Report</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Technician Performance Report</h3>
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
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Technician</th>
                    <th>Email</th>
                    <th>Assigned Zones</th>
                    <th class="text-right">Collection (৳)</th>
                    <th class="text-center">Tickets Assigned</th>
                    <th class="text-center">Tickets Completed</th>
                    <th class="text-center">Completion Rate</th>
                    <th class="text-center">Avg. Resolution (min)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($technicians as $tech)
                <tr>
                    <td>
                        <a href="{{ route('admin.hrm.technicians.show', $tech) }}">
                            <strong>{{ $tech->name }}</strong>
                        </a>
                    </td>
                    <td>{{ $tech->email }}</td>
                    <td>
                        @if($tech->employee && $tech->employee->zone_ids)
                            @php
                                $zones = \App\Models\Zone::whereIn('id', $tech->employee->zone_ids)->pluck('name');
                            @endphp
                            @foreach($zones as $zone)
                                <span class="badge badge-info badge-sm">{{ $zone }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <strong class="text-success">৳{{ number_format($tech->collection, 2) }}</strong>
                    </td>
                    <td class="text-center">{{ $tech->tickets_assigned }}</td>
                    <td class="text-center">{{ $tech->tickets_completed }}</td>
                    <td class="text-center">
                        @php
                            $rate = $tech->tickets_assigned > 0 ? round(($tech->tickets_completed / $tech->tickets_assigned) * 100, 1) : 0;
                        @endphp
                        <span class="badge badge-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}">
                            {{ $rate }}%
                        </span>
                    </td>
                    <td class="text-center">
                        {{ $tech->avg_resolution_time }} min
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No technicians found</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-light">
                    <th colspan="3">Total</th>
                    <th class="text-right text-success">৳{{ number_format($technicians->sum('collection'), 2) }}</th>
                    <th class="text-center">{{ $technicians->sum('tickets_assigned') }}</th>
                    <th class="text-center">{{ $technicians->sum('tickets_completed') }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Collection by Technician</h3>
            </div>
            <div class="card-body">
                <canvas id="collectionChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Tickets by Technician</h3>
            </div>
            <div class="card-body">
                <canvas id="ticketsChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const technicians = @json($technicians);
    
    new Chart(document.getElementById('collectionChart'), {
        type: 'bar',
        data: {
            labels: technicians.map(t => t.name),
            datasets: [{
                label: 'Collection (৳)',
                data: technicians.map(t => t.collection),
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    new Chart(document.getElementById('ticketsChart'), {
        type: 'doughnut',
        data: {
            labels: technicians.map(t => t.name),
            datasets: [{
                data: technicians.map(t => t.tickets_completed),
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8',
                    '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d'
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
