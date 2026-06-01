@extends('layouts.admin')

@section('title', 'Daily Collection Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Daily Collection</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daily Collection - {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                <button type="submit" class="btn btn-info btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-6 col-md-4 mb-2">
                <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
                    <div class="stat-number">৳{{ number_format($totalCollection ?? 0, 0) }}</div>
                    <div class="stat-label">Collection</div>
                </div>
            </div>
            <div class="col-6 col-md-4 mb-2">
                <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $payments->count() }}</div>
                    <div class="stat-label">Payments</div>
                </div>
            </div>
        </div>
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th>Payment No</th><th>Client</th><th>Amount (৳)</th><th>Method</th><th>Collected By</th></tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_no }}</td>
                    <td>{{ $payment->client->name ?? 'N/A' }}</td>
                    <td>{{ number_format($payment->amount ?? 0, 2) }}</td>
                    <td>{{ $payment->paymentMethod->name ?? 'Cash' }}</td>
                    <td>{{ $payment->collectedBy->name ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No payments on this date</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
