@extends('layouts.admin')

@section('title', 'Bill Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.index') }}">Billing</a></li>
<li class="breadcrumb-item active">{{ $bill->bill_no }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Bill: {{ $bill->bill_no }}</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Client:</strong> {{ $bill->client->name ?? 'N/A' }}</p>
                        <p><strong>Client ID:</strong> {{ $bill->client->client_id ?? 'N/A' }}</p>
                        <p><strong>Phone:</strong> {{ $bill->client->phone ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Bill Date:</strong> {{ $bill->bill_date->format('d-m-Y') }}</p>
                        <p><strong>Due Date:</strong> {{ $bill->due_date->format('d-m-Y') }}</p>
                        <p><strong>Billing Period:</strong> {{ $bill->billing_month }}/{{ $bill->billing_year }}</p>
                    </div>
                </div>
                <hr>
                <table class="table">
                    <tr><td>Package Amount</td><td class="text-right">৳{{ number_format($bill->package_amount ?? 0, 2) }}</td></tr>
                    <tr><td>Previous Due</td><td class="text-right">৳{{ number_format($bill->previous_due ?? 0, 2) }}</td></tr>
                    <tr><td>Discount</td><td class="text-right">-৳{{ number_format($bill->discount ?? 0, 2) }}</td></tr>
                    <tr class="table-primary"><td><strong>Total Amount</strong></td><td class="text-right"><strong>৳{{ number_format($bill->total_amount ?? 0, 2) }}</strong></td></tr>
                    <tr><td>Paid Amount</td><td class="text-right text-success">৳{{ number_format($bill->paid_amount ?? 0, 2) }}</td></tr>
                    <tr class="table-warning"><td><strong>Due Amount</strong></td><td class="text-right text-danger"><strong>৳{{ number_format($bill->due_amount ?? 0, 2) }}</strong></td></tr>
                </table>
            </div>
            <div class="card-footer">
                @if($__canAction('billing', 'print_receipt'))
                <a href="{{ route('admin.billing.invoice', $bill) }}" class="btn btn-primary"><i class="fas fa-download"></i> Download Invoice</a>
                @endif
                @if($bill->due_amount > 0)
                @if($__canAction('billing', 'create_payment'))
                <a href="{{ route('admin.payments.create') }}?client_id={{ $bill->client_id }}&bill_id={{ $bill->id }}" class="btn btn-success">Collect Payment</a>
                @endif
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment History</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Amount</th></tr></thead>
                    <tbody>
                        @forelse($bill->payments as $payment)
                        <tr><td>{{ $payment->payment_date->format('d-m-Y') }}</td><td>৳{{ number_format($payment->amount ?? 0, 2) }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center">No payments</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
