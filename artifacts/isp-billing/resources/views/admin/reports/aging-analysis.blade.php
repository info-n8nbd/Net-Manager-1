@extends('layouts.admin')

@section('title', 'Aging Analysis Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Reports</a></li>
<li class="breadcrumb-item active">Aging Analysis</li>
@endsection

@section('content')
<div class="row mb-3">
    @foreach($report as $bracket => $data)
    <div class="col-md mb-2">
        <div class="small-box {{ $loop->last ? 'bg-danger' : ($loop->index >= 3 ? 'bg-warning' : 'bg-info') }}">
            <div class="inner">
                <h4>৳{{ number_format($data['total_due'] ?? 0, 0) }}</h4>
                <p>{{ $bracket }}</p>
                <small>{{ $data['bill_count'] }} bills / {{ $data['client_count'] }} clients</small>
            </div>
        </div>
    </div>
    @endforeach
</div>

@foreach($report as $bracket => $data)
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $bracket }} - ৳{{ number_format($data['total_due'] ?? 0, 2) }} ({{ $data['bill_count'] }} bills)</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Bill No</th>
                    <th>Client ID</th>
                    <th>Client Name</th>
                    <th>Amount (৳)</th>
                    <th>Paid (৳)</th>
                    <th>Remaining (৳)</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['bills'] as $bill)
                <tr>
                    <td>{{ $bill['bill_no'] }}</td>
                    <td>{{ $bill['client_id'] ?? 'N/A' }}</td>
                    <td>{{ $bill['client_name'] ?? 'N/A' }}</td>
                    <td>৳{{ number_format($bill['amount'] ?? 0, 2) }}</td>
                    <td>৳{{ number_format($bill['paid'] ?? 0, 2) }}</td>
                    <td class="text-danger font-weight-bold">৳{{ number_format($bill['remaining'] ?? 0, 2) }}</td>
                    <td>{{ $bill['due_date'] ? \Carbon\Carbon::parse($bill['due_date'])->format('d-m-Y') : 'N/A' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center">No bills in this bracket</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endsection
