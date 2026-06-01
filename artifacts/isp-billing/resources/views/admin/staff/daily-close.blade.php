@extends('layouts.admin')

@section('title', 'Daily Cash Close')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Staff</a></li>
<li class="breadcrumb-item active">Daily Cash Close</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daily Cash Close - {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                <button type="submit" class="btn btn-info btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        @if($pendingCloses->count() > 0)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> {{ $pendingCloses->count() }} pending close(s) awaiting verification.
        </div>
        @endif

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Role</th>
                    <th>Total Collected (৳)</th>
                    <th>Total Submitted (৳)</th>
                    <th>Outstanding (৳)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffWithCollections as $staff)
                <tr>
                    <td>{{ $staff['name'] }}</td>
                    <td><span class="badge badge-info">{{ ucfirst($staff['role']) }}</span></td>
                    <td>{{ number_format($staff['summary']['total_collected'] ?? 0, 2) }}</td>
                    <td>{{ number_format($staff['summary']['total_submitted'] ?? 0, 2) }}</td>
                    <td>{{ number_format($staff['summary']['outstanding'] ?? 0, 2) }}</td>
                    <td>
                        @if(($staff['summary']['outstanding'] ?? 0) > 0)
                            <span class="badge badge-danger">Pending</span>
                        @else
                            <span class="badge badge-success">Cleared</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="viewSummary({{ $staff['id'] }})">
                            <i class="fas fa-eye"></i> Summary
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center">No collections found for this date</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
