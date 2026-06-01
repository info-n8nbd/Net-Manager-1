@extends('layouts.admin')

@section('title', 'Month Close')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Month Close</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Current Month Status</h3>
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <label class="col-sm-4">Month/Year:</label>
                    <div class="col-sm-8">
                        <strong>{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</strong>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4">Status:</label>
                    <div class="col-sm-8">
                        @if($status && isset($status['is_closed']) && $status['is_closed'])
                            <span class="badge badge-danger">Closed</span>
                        @else
                            <span class="badge badge-success">Open</span>
                        @endif
                    </div>
                </div>
                @if($status && isset($status['total_bills']))
                <div class="form-group row">
                    <label class="col-sm-4">Total Bills:</label>
                    <div class="col-sm-8">{{ $status['total_bills'] ?? 0 }}</div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4">Total Collection:</label>
                    <div class="col-sm-8">৳{{ number_format($status['total_collection'] ?? 0, 2) }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Month History</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr><th>Month</th><th>Year</th><th>Status</th><th>Closed At</th></tr>
                    </thead>
                    <tbody>
                        @forelse($allMonths as $m)
                        <tr>
                            <td>{{ \Carbon\Carbon::create($m->billing_year, $m->billing_month)->format('F') }}</td>
                            <td>{{ $m->billing_year }}</td>
                            <td>
                                @if($m->is_locked)
                                    <span class="badge badge-dark">Locked</span>
                                @elseif($m->is_closed)
                                    <span class="badge badge-danger">Closed</span>
                                @else
                                    <span class="badge badge-success">Open</span>
                                @endif
                            </td>
                            <td>{{ $m->closed_at ? $m->closed_at->format('d-m-Y') : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center">No records found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
