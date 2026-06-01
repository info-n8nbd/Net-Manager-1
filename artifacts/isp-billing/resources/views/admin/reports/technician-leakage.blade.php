@extends('layouts.admin')

@section('title', 'Technician Leakage Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Reports</a></li>
<li class="breadcrumb-item active">Technician Leakage</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Technician Leakage Report ({{ $startDate->format('d-m-Y') }} - {{ $endDate->format('d-m-Y') }})</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <input type="date" name="start_date" class="form-control form-control-sm mr-2" value="{{ $startDate->format('Y-m-d') }}">
                <input type="date" name="end_date" class="form-control form-control-sm mr-2" value="{{ $endDate->format('Y-m-d') }}">
                <button type="submit" class="btn btn-info btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Technician</th>
                    <th>Role</th>
                    <th>Expected (৳)</th>
                    <th>Collected (৳)</th>
                    <th>Submitted (৳)</th>
                    <th>Outstanding (৳)</th>
                    <th>Leakage (৳)</th>
                    <th>Collection Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $entry)
                <tr>
                    <td>{{ $entry['technician_name'] }}</td>
                    <td><span class="badge badge-info">{{ ucfirst($entry['role']) }}</span></td>
                    <td>৳{{ number_format($entry['expected_collection'] ?? 0, 2) }}</td>
                    <td>৳{{ number_format($entry['total_collected'] ?? 0, 2) }}</td>
                    <td>৳{{ number_format($entry['total_submitted'] ?? 0, 2) }}</td>
                    <td>৳{{ number_format($entry['outstanding'] ?? 0, 2) }}</td>
                    <td class="{{ $entry['leakage'] > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                        ৳{{ number_format($entry['leakage'] ?? 0, 2) }}
                    </td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar {{ $entry['collection_rate'] >= 80 ? 'bg-success' : ($entry['collection_rate'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                 style="width: {{ min($entry['collection_rate'], 100) }}%">
                                {{ $entry['collection_rate'] }}%
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center">No data available for this period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
