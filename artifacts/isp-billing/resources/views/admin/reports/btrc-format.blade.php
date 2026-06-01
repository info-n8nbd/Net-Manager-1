@extends('layouts.admin')

@section('title', 'BTRC Format Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reports.daily') }}">Reports</a></li>
<li class="breadcrumb-item active">BTRC Format</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>BTRC Format Report</h3>
        <div class="card-tools">
            <a href="{{ route('admin.reports.export.pdf', ['type' => 'btrc']) }}" class="btn btn-sm btn-danger">
                <i class="fas fa-file-pdf mr-1"></i>Export PDF
            </a>
            <a href="{{ route('admin.reports.export.excel', ['type' => 'btrc']) }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel mr-1"></i>Export CSV
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>ISP Information</h5>
                        <table class="table table-sm table-borderless">
                            <tr><td width="40%"><strong>Company Name:</strong></td><td>{{ $report['company_name'] ?? 'ISP Company' }}</td></tr>
                            <tr><td><strong>License No:</strong></td><td>{{ $report['license_no'] ?? 'ISP-XXXX' }}</td></tr>
                            <tr><td><strong>Report Period:</strong></td><td>{{ $report['period'] ?? now()->format('F Y') }}</td></tr>
                            <tr><td><strong>Generated:</strong></td><td>{{ now()->format('d-m-y H:i') }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>Summary Statistics</h5>
                        <table class="table table-sm table-borderless">
                            <tr><td width="50%"><strong>Total Subscribers:</strong></td><td>{{ $report['total_subscribers'] ?? 0 }}</td></tr>
                            <tr><td><strong>Active Connections:</strong></td><td>{{ $report['active_connections'] ?? 0 }}</td></tr>
                            <tr><td><strong>Total Bandwidth:</strong></td><td>{{ $report['total_bandwidth'] ?? '0 Mbps' }}</td></tr>
                            <tr><td><strong>Avg. Speed per User:</strong></td><td>{{ $report['avg_speed'] ?? '0 Mbps' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <h5 class="mb-3">Subscriber Breakdown by Package</h5>
        <table class="table table-bordered table-striped">
            <thead class="bg-primary text-white">
                <tr>
                    <th>Package Name</th>
                    <th>Bandwidth (Mbps)</th>
                    <th class="text-right">Active Subscribers</th>
                    <th class="text-right">New This Month</th>
                    <th class="text-right">Churned</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['packages'] ?? [] as $pkg)
                <tr>
                    <td>{{ $pkg['name'] }}</td>
                    <td>{{ $pkg['bandwidth'] }}</td>
                    <td class="text-right">{{ $pkg['active'] }}</td>
                    <td class="text-right text-success">+{{ $pkg['new'] }}</td>
                    <td class="text-right text-danger">-{{ $pkg['churned'] }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No package data available</td></tr>
                @endforelse
            </tbody>
        </table>
        
        <h5 class="mb-3 mt-4">Zone-wise Distribution</h5>
        <table class="table table-bordered table-striped">
            <thead class="bg-info text-white">
                <tr>
                    <th>Zone/Area</th>
                    <th class="text-right">Subscribers</th>
                    <th class="text-right">Active</th>
                    <th class="text-right">Inactive</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['zones'] ?? [] as $zone)
                <tr>
                    <td>{{ $zone['name'] }}</td>
                    <td class="text-right">{{ $zone['total'] }}</td>
                    <td class="text-right">{{ $zone['active'] }}</td>
                    <td class="text-right">{{ $zone['inactive'] }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">No zone data available</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
