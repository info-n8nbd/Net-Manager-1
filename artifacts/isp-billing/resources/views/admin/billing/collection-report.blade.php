@extends('layouts.admin')
@section('title', 'Collection Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.daily-collections') }}">Billing</a></li>
<li class="breadcrumb-item active">Collection Report</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Collection Report</h3>
            <div class="card-tools">
                <form class="form-inline" method="GET">
                    <input type="date" name="start_date" class="form-control form-control-sm mr-2" value="{{ $startDate }}">
                    <input type="date" name="end_date" class="form-control form-control-sm mr-2" value="{{ $endDate }}">
                    @if($isSuperAdmin && $collectors->count() > 0)
                    <select name="user_id" class="form-control form-control-sm mr-2"><option value="">All Employees</option>@foreach($collectors as $c)<option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Filter</button>
                </form>
            </div>
        </div>
    </div>
    @if($isSuperAdmin && $summary->count() > 0)
    <div class="card">
        <div class="card-header bg-gradient-primary"><h3 class="card-title text-white"><i class="fas fa-users mr-2"></i>Employee-wise Summary</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="bg-light"><tr><th>Employee</th><th class="text-center">Total Bills</th><th class="text-right">Total Amount</th><th class="text-right">Approved</th><th class="text-right">Pending</th></tr></thead>
                <tbody>
                    @foreach($summary as $s)
                    <tr><td><i class="fas fa-user-tie mr-1 text-primary"></i>{{ $s->user->name ?? 'N/A' }}</td><td class="text-center"><span class="badge badge-info">{{ $s->total_bills }}</span></td><td class="text-right font-weight-bold">৳{{ number_format($s->total_amount) }}</td><td class="text-right text-success">৳{{ number_format($s->approved_amount) }}</td><td class="text-right text-warning">৳{{ number_format($s->pending_amount) }}</td></tr>
                    @endforeach
                    <tr class="font-weight-bold bg-light"><td>Total</td><td class="text-center">{{ $summary->sum('total_bills') }}</td><td class="text-right">৳{{ number_format($summary->sum('total_amount')) }}</td><td class="text-right text-success">৳{{ number_format($summary->sum('approved_amount')) }}</td><td class="text-right text-warning">৳{{ number_format($summary->sum('pending_amount')) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-list mr-2"></i>All Collections ({{ $collections->count() }})</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead class="bg-light"><tr><th>#</th><th>Date</th><th>Employee</th><th>Client</th><th>Amount</th><th>Method</th><th>Status</th><th>Receipt</th></tr></thead>
                <tbody>
                    @forelse($collections as $i => $col)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $col->collection_date->format('d-m-Y') }}</td><td>{{ $col->user->name ?? 'N/A' }}</td><td>{{ $col->client->client_id ?? '' }} — {{ $col->client->name ?? 'N/A' }}</td><td class="text-success font-weight-bold">৳{{ number_format($col->amount) }}</td><td><span class="badge badge-secondary">{{ $col->payment_method }}</span></td>
                    <td>@if($col->status == 'approved')<span class="badge badge-success">Approved</span>@elseif($col->status == 'rejected')<span class="badge badge-danger">Rejected</span>@else<span class="badge badge-warning">Pending</span>@endif</td>
                    <td><small class="text-muted">{{ $col->receipt_no }}</small></td></tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No collections found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
