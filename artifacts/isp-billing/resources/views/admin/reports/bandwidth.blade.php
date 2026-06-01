@extends('layouts.admin')

@section('title', 'Bandwidth Usage Reports')

@section('content')
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reports.bandwidth') }}" class="row align-items-end">
                    <div class="col-md-3">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-3">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Filter</button>
                    </div>
                </form>
            </div>
        </div>

        @php
            $totalRx = $topClients->sum('total_rx');
            $totalTx = $topClients->sum('total_tx');
            $peakRx = $topClients->max('peak_rx');
            $peakTx = $topClients->max('peak_tx');
        @endphp

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="totalDownload">{{ number_format($totalRx / 1073741824, 2) }} GB</h3>
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
                        <h3>{{ number_format(($peakRx ?? 0) / 1000000, 2) }} Mbps</h3>
                        <p>Peak Download</p>
                    </div>
                    <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ number_format(($peakTx ?? 0) / 1000000, 2) }} Mbps</h3>
                        <p>Peak Upload</p>
                    </div>
                    <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
                </div>
            </div>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i>Daily Bandwidth Usage</h3>
            </div>
            <div class="card-body">
                <canvas id="dailyChart" height="100"></canvas>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-users mr-1"></i>Top 50 Clients by Usage</h3>
                        <a href="{{ route('admin.reports.bandwidth-export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-success">
                            <i class="fas fa-file-csv mr-1"></i>Export CSV
                        </a>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Client ID</th>
                                    <th>Name</th>
                                    <th>Download</th>
                                    <th>Upload</th>
                                    <th>Peak DL</th>
                                    <th>Peak UL</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topClients as $index => $client)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="badge badge-primary">{{ $client->client_code }}</span></td>
                                    <td>{{ $client->name }}</td>
                                    <td><span class="text-info">{{ formatBytesHelper($client->total_rx) }}</span></td>
                                    <td><span class="text-success">{{ formatBytesHelper($client->total_tx) }}</span></td>
                                    <td>{{ number_format(($client->peak_rx ?? 0) / 1000000, 2) }} Mbps</td>
                                    <td>{{ number_format(($client->peak_tx ?? 0) / 1000000, 2) }} Mbps</td>
                                    <td>
                                        <a href="{{ route('admin.reports.bandwidth-client', [$client->id, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-xs btn-info">
                                            <i class="fas fa-chart-line"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No bandwidth data found for the selected period</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i>Zone Breakdown</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Zone</th>
                                    <th>Download</th>
                                    <th>Upload</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $zoneTotalRx = $zoneStats->sum('total_rx'); @endphp
                                @forelse($zoneStats as $zone)
                                <tr>
                                    <td>{{ $zone->zone_name }}</td>
                                    <td><span class="text-info">{{ formatBytesHelper($zone->total_rx) }}</span></td>
                                    <td><span class="text-success">{{ formatBytesHelper($zone->total_tx) }}</span></td>
                                    <td>
                                        @if($zoneTotalRx > 0)
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-info" style="width: {{ round(($zone->total_rx / $zoneTotalRx) * 100) }}%"></div>
                                            </div>
                                            <small>{{ round(($zone->total_rx / $zoneTotalRx) * 100, 1) }}%</small>
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">No zone data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i>Zone Distribution</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="zoneChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@php
function formatBytesHelper($bytes) {
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
function formatBytes(bytes) {
    if (bytes >= 1099511627776) return (bytes / 1099511627776).toFixed(2) + ' TB';
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' B';
}

const dailyData = @json($dailyTotals);
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: dailyData.map(d => d.log_date),
        datasets: [{
            label: 'Download',
            data: dailyData.map(d => d.total_rx / 1073741824),
            backgroundColor: 'rgba(23, 162, 184, 0.7)',
            borderColor: 'rgba(23, 162, 184, 1)',
            borderWidth: 1
        }, {
            label: 'Upload',
            data: dailyData.map(d => d.total_tx / 1073741824),
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'GB' }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ctx.dataset.label + ': ' + ctx.raw.toFixed(2) + ' GB';
                    }
                }
            }
        }
    }
});

const zoneData = @json($zoneStats);
if (zoneData.length > 0) {
    const zoneCtx = document.getElementById('zoneChart').getContext('2d');
    new Chart(zoneCtx, {
        type: 'doughnut',
        data: {
            labels: zoneData.map(z => z.zone_name),
            datasets: [{
                data: zoneData.map(z => z.total_rx),
                backgroundColor: [
                    '#17a2b8', '#28a745', '#ffc107', '#dc3545', '#6610f2',
                    '#fd7e14', '#20c997', '#e83e8c', '#6f42c1', '#007bff'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.label + ': ' + formatBytes(ctx.raw);
                        }
                    }
                }
            }
        }
    });
}
</script>
@endpush
