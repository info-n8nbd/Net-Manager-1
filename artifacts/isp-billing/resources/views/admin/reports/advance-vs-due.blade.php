@extends('layouts.admin')

@section('title', 'Advance vs Due Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Reports</a></li>
<li class="breadcrumb-item active">Advance vs Due</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>৳{{ number_format($report['summary']['total_due'] ?? 0, 0) }}</h3>
                <p>Total Due</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-down"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($report['summary']['total_advance'] ?? 0, 0) }}</h3>
                <p>Total Advance</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $report['summary']['total_clients'] ?? 0 }}</h3>
                <p>Total Clients</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $report['summary']['clients_with_due'] ?? 0 }}</h3>
                <p>Clients with Due</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Zone-wise Advance vs Due</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Zone</th>
                    <th>Total Clients</th>
                    <th>Active</th>
                    <th>Suspended</th>
                    <th>With Due</th>
                    <th>Total Due (৳)</th>
                    <th>Total Advance (৳)</th>
                    <th>Net Position (৳)</th>
                    <th>Efficiency</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['zones'] ?? $report as $zone)
                @php $zoneData = is_array($zone) ? $zone : (array)$zone; @endphp
                <tr>
                    <td>{{ $zoneData['zone_name'] }}</td>
                    <td>{{ $zoneData['total_clients'] }}</td>
                    <td>{{ $zoneData['active_clients'] }}</td>
                    <td>{{ $zoneData['inactive_clients'] ?? $zoneData['suspended_clients'] ?? 0 }}</td>
                    <td>{{ $zoneData['clients_with_due'] }}</td>
                    <td class="text-danger">৳{{ number_format($zoneData['total_due'] ?? 0, 2) }}</td>
                    <td class="text-success">৳{{ number_format($zoneData['total_advance'] ?? 0, 2) }}</td>
                    <td class="{{ $zoneData['net_position'] >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                        ৳{{ number_format($zoneData['net_position'] ?? 0, 2) }}
                    </td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar {{ $zoneData['collection_efficiency'] >= 80 ? 'bg-success' : ($zoneData['collection_efficiency'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                 style="width: {{ min($zoneData['collection_efficiency'], 100) }}%">
                                {{ $zoneData['collection_efficiency'] }}%
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center">No zone data available</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
