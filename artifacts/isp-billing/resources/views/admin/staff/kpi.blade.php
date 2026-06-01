@extends('layouts.admin')

@section('title', 'Staff KPI Dashboard')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Staff</a></li>
<li class="breadcrumb-item active">KPI Dashboard</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Staff KPI Dashboard - {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <select name="month" class="form-control form-control-sm mr-2">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
                <select name="year" class="form-control form-control-sm mr-2">
                    @for($y = now()->year - 2; $y <= now()->year; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-info btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Role</th>
                    <th>Total Collected (৳)</th>
                    <th>Collections</th>
                    <th>Avg Collection (৳)</th>
                    <th>Tickets Resolved</th>
                    <th>Avg Resolution (hrs)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffKpi as $staff)
                <tr>
                    <td>{{ $staff['name'] }}</td>
                    <td><span class="badge badge-info">{{ ucfirst($staff['role']) }}</span></td>
                    <td>৳{{ number_format($staff['kpi']['total_collected'], 2) }}</td>
                    <td>{{ $staff['kpi']['collection_count'] }}</td>
                    <td>৳{{ number_format($staff['kpi']['avg_collection'], 2) }}</td>
                    <td>{{ $staff['kpi']['tickets_resolved'] }}</td>
                    <td>{{ $staff['kpi']['avg_resolution_hours'] }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center">No staff KPI data available</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
