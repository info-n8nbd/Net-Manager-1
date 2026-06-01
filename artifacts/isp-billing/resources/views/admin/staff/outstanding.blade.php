@extends('layouts.admin')

@section('title', 'Staff Outstanding Dues')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Staff</a></li>
<li class="breadcrumb-item active">Outstanding Dues</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>৳{{ number_format($totalOutstanding, 0) }}</h3>
                <p>Total Outstanding</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $staffOutstanding->count() }}</h3>
                <p>Staff with Dues</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Staff Outstanding Report</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Role</th>
                    <th>Total Collected (৳)</th>
                    <th>Total Submitted (৳)</th>
                    <th>Current Due (৳)</th>
                    <th>Last Collection</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffOutstanding as $staff)
                <tr>
                    <td>{{ $staff['staff_name'] }}</td>
                    <td><span class="badge badge-info">{{ ucfirst($staff['role']) }}</span></td>
                    <td>{{ number_format($staff['total_collected'], 2) }}</td>
                    <td>{{ number_format($staff['total_submitted'], 2) }}</td>
                    <td class="text-danger font-weight-bold">৳{{ number_format($staff['current_due'], 2) }}</td>
                    <td>{{ $staff['last_collection'] ? \Carbon\Carbon::parse($staff['last_collection'])->format('d-m-Y') : 'N/A' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center">No outstanding dues found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
