@extends('layouts.admin')

@section('title', 'Zone Analytics & Problem Graphs')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Reports</li>
<li class="breadcrumb-item active">Zone Analytics</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-chart-bar mr-2"></i>
            <strong>Zone-wise Analytics</strong> - Monitor performance, problems, and collection by zone
        </div>
    </div>
</div>

<div class="row">
    @foreach($zones as $zone)
    <div class="col-lg-4 col-md-6">
        <div class="card card-outline card-{{ $zone->due_clients_count > 0 ? 'warning' : 'success' }}">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>{{ $zone->name }}</h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $zone->active_clients_count > 0 ? 'success' : 'secondary' }}">
                        {{ $zone->active_clients_count }} Active
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td><i class="fas fa-users text-primary mr-2"></i>Total Clients</td>
                        <td class="text-right"><strong>{{ $zone->clients_count }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-user-check text-success mr-2"></i>Active</td>
                        <td class="text-right text-success">{{ $zone->active_clients_count }}</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-user-clock text-danger mr-2"></i>Inactive</td>
                        <td class="text-right text-danger">{{ $zone->inactive_clients_count ?? $zone->suspended_clients_count ?? 0 }}</td>
                    </tr>
                    <tr class="{{ $zone->due_clients_count > 0 ? 'bg-light' : '' }}">
                        <td><i class="fas fa-exclamation-triangle text-danger mr-2"></i>With Due</td>
                        <td class="text-right text-danger"><strong>{{ $zone->due_clients_count }}</strong></td>
                    </tr>
                </table>
            </div>
            <div class="card-footer text-center">
                <button class="btn btn-sm btn-info" onclick="showZoneDetails({{ $zone->id }}, '{{ $zone->name }}')">
                    <i class="fas fa-chart-line mr-1"></i> View Details
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Zone Collection Rate</h3>
            </div>
            <div class="card-body">
                <canvas id="collectionChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h3 class="card-title"><i class="fas fa-exclamation-circle mr-2"></i>Problem Score by Zone</h3>
            </div>
            <div class="card-body">
                <canvas id="problemChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>6-Month Trend</h3>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="zoneDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-map-marker-alt mr-2"></i><span id="zoneModalTitle">Zone Details</span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="zoneModalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let collectionChart, problemChart, trendChart;

document.addEventListener('DOMContentLoaded', function() {
    loadCollectionData();
    loadProblemData();
    loadTrendData();
});

function loadCollectionData() {
    fetch('{{ route("admin.zone-analytics.collection") }}?month={{ now()->month }}&year={{ now()->year }}')
        .then(r => r.json())
        .then(data => {
            const ctx = document.getElementById('collectionChart').getContext('2d');
            collectionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.data.map(d => d.zone_name),
                    datasets: [
                        {
                            label: 'Billed',
                            data: data.data.map(d => d.total_billed),
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Collected',
                            data: data.data.map(d => d.total_collected),
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
}

function loadProblemData() {
    fetch('{{ route("admin.zone-analytics.problems") }}')
        .then(r => r.json())
        .then(data => {
            const ctx = document.getElementById('problemChart').getContext('2d');
            problemChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.data.map(d => d.zone_name),
                    datasets: [{
                        label: 'Problem Score',
                        data: data.data.map(d => d.problem_score),
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    scales: { x: { beginAtZero: true } }
                }
            });
        });
}

function loadTrendData() {
    fetch('{{ route("admin.zone-analytics.trend") }}?months=6')
        .then(r => r.json())
        .then(data => {
            const ctx = document.getElementById('trendChart').getContext('2d');
            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.data.map(d => d.month),
                    datasets: [
                        {
                            label: 'New Clients',
                            data: data.data.map(d => d.new_clients),
                            borderColor: 'rgba(75, 192, 192, 1)',
                            tension: 0.1
                        },
                        {
                            label: 'Left Clients',
                            data: data.data.map(d => d.left_clients),
                            borderColor: 'rgba(255, 99, 132, 1)',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
}

function showZoneDetails(zoneId, zoneName) {
    $('#zoneModalTitle').text(zoneName + ' - Details');
    $('#zoneModalBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    $('#zoneDetailModal').modal('show');
    
    fetch('{{ route("admin.zone-analytics.distribution") }}?zone_id=' + zoneId)
        .then(r => r.json())
        .then(data => {
            let html = '<div class="row">';
            html += '<div class="col-md-6"><h6>By Status</h6><ul class="list-group">';
            for (let [status, count] of Object.entries(data.by_status)) {
                html += `<li class="list-group-item d-flex justify-content-between">${status}<span class="badge badge-primary">${count}</span></li>`;
            }
            html += '</ul></div>';
            html += '<div class="col-md-6"><h6>By Connection Type</h6><ul class="list-group">';
            for (let [type, count] of Object.entries(data.by_connection_type)) {
                html += `<li class="list-group-item d-flex justify-content-between">${type || 'Unknown'}<span class="badge badge-info">${count}</span></li>`;
            }
            html += '</ul></div></div>';
            $('#zoneModalBody').html(html);
        });
}
</script>
@endpush
