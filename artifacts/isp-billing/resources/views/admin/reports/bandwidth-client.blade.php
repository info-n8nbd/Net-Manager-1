@extends('layouts.admin')

@section('title', 'Bandwidth - ' . $client->name)

@section('content')
        <div class="card card-primary card-outline">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4><i class="fas fa-user mr-2"></i>{{ $client->name }}</h4>
                        <p class="mb-1"><strong>Client ID:</strong> <span class="badge badge-primary">{{ $client->client_id }}</span></p>
                        <p class="mb-1"><strong>Package:</strong> {{ $client->package->name ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Status:</strong>
                            <span class="badge badge-{{ $client->status == 'active' ? 'success' : ($client->status == 'inactive' ? 'danger' : 'warning') }}">
                                {{ ucfirst($client->status ?? 'unknown') }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="row align-items-end">
                            <div class="col-md-4">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-4">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @php
            $totalRx = $dailyUsage->sum('rx');
            $totalTx = $dailyUsage->sum('tx');
        @endphp

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($totalRx / 1073741824, 2) }} GB</h3>
                        <p>Total Download</p>
                    </div>
                    <div class="icon"><i class="fas fa-download"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($totalTx / 1073741824, 2) }} GB</h3>
                        <p>Total Upload</p>
                    </div>
                    <div class="icon"><i class="fas fa-upload"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ number_format(($dailyUsage->max('peak_rx') ?? 0) / 1000000, 2) }} Mbps</h3>
                        <p>Peak Download</p>
                    </div>
                    <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ number_format(($dailyUsage->max('peak_tx') ?? 0) / 1000000, 2) }} Mbps</h3>
                        <p>Peak Upload</p>
                    </div>
                    <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
                </div>
            </div>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i>Daily Usage</h3>
            </div>
            <div class="card-body">
                <canvas id="dailyChart" height="80"></canvas>
            </div>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-1"></i>Hourly Usage Pattern (Average)</h3>
            </div>
            <div class="card-body">
                <canvas id="hourlyChart" height="80"></canvas>
            </div>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-table mr-1"></i>Daily Breakdown</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Date</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Total</th>
                            <th>Peak Download</th>
                            <th>Peak Upload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyUsage as $day)
                        <tr>
                            <td>{{ $day->log_date }}</td>
                            <td><span class="text-info">{{ formatBytesView($day->rx) }}</span></td>
                            <td><span class="text-success">{{ formatBytesView($day->tx) }}</span></td>
                            <td><strong>{{ formatBytesView($day->rx + $day->tx) }}</strong></td>
                            <td>{{ number_format(($day->peak_rx ?? 0) / 1000000, 2) }} Mbps</td>
                            <td>{{ number_format(($day->peak_tx ?? 0) / 1000000, 2) }} Mbps</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No bandwidth data found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@php
function formatBytesView($bytes) {
    if ($bytes >= 1099511627776) return number_format($bytes / 1099511627776, 2) . ' TB';
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
@endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dailyData = @json($dailyUsage);
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.log_date),
        datasets: [{
            label: 'Download',
            data: dailyData.map(d => d.rx / 1073741824),
            borderColor: 'rgba(23, 162, 184, 1)',
            backgroundColor: 'rgba(23, 162, 184, 0.1)',
            fill: true,
            tension: 0.3
        }, {
            label: 'Upload',
            data: dailyData.map(d => d.tx / 1073741824),
            borderColor: 'rgba(40, 167, 69, 1)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'GB' } }
        }
    }
});

const hourlyData = @json($hourlyUsage);
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: hourlyData.map(h => h.log_hour + ':00'),
        datasets: [{
            label: 'Avg Download Rate',
            data: hourlyData.map(h => h.avg_rx / 1000000),
            backgroundColor: 'rgba(23, 162, 184, 0.7)',
            borderColor: 'rgba(23, 162, 184, 1)',
            borderWidth: 1
        }, {
            label: 'Avg Upload Rate',
            data: hourlyData.map(h => h.avg_tx / 1000000),
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Mbps' } }
        }
    }
});
</script>
@endpush
