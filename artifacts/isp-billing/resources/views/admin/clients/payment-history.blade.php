@extends('layouts.admin')

@section('title', 'Billing History')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item active">Billing History</li>
@endsection

@push('styles')
<style>
.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}
.summary-item {
    text-align: center;
    padding: 10px;
}
.summary-value {
    font-size: 24px;
    font-weight: bold;
}
.summary-label {
    font-size: 12px;
    opacity: 0.9;
}
.bill-card {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    margin-bottom: 15px;
    overflow: hidden;
}
.bill-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.bill-header.paid {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}
.bill-header.partial {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}
.bill-header.unpaid {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}
.bill-month {
    font-size: 18px;
    font-weight: bold;
}
.bill-amount {
    font-size: 20px;
    font-weight: bold;
}
.bill-details {
    padding: 15px;
    background: #f8f9fa;
}
.bill-info-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px dashed #dee2e6;
}
.bill-info-row:last-child {
    border-bottom: none;
}
.payment-list {
    background: white;
    padding: 10px 15px;
}
.payment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #e8f5e9;
    border-radius: 8px;
    margin-bottom: 8px;
}
.payment-item:last-child {
    margin-bottom: 0;
}
.payment-amount {
    font-weight: bold;
    color: #28a745;
    font-size: 16px;
}
.no-payment {
    color: #dc3545;
    font-style: italic;
    padding: 10px;
    text-align: center;
}
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>{{ $client->name }}</h3>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Client ID:</strong> {{ $client->client_id }}</p>
                <p class="mb-1"><strong>Phone:</strong> {{ $client->phone }}</p>
                <p class="mb-1"><strong>Package:</strong> {{ $client->package->name ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Monthly Bill:</strong> ৳{{ number_format($client->monthly_fee ?? 0) }}</p>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Profile
                </a>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="row">
                <div class="col-6 summary-item">
                    <div class="summary-value">৳{{ number_format($totalBills ?? 0) }}</div>
                    <div class="summary-label">Total Bills</div>
                </div>
                <div class="col-6 summary-item">
                    <div class="summary-value">৳{{ number_format($totalPaid ?? 0) }}</div>
                    <div class="summary-label">Total Paid</div>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.3);">
            <div class="text-center">
                @php $balance = $totalBills - $totalPaid; @endphp
                @if($balance > 0)
                    <div class="summary-value text-warning">৳{{ number_format($balance ?? 0) }}</div>
                    <div class="summary-label">Due Amount</div>
                @elseif($balance < 0)
                    <div class="summary-value" style="color: #000000;">৳{{ number_format(abs($balance)) }}</div>
                    <div class="summary-label">Advance Amount</div>
                @else
                    <div class="summary-value">৳0</div>
                    <div class="summary-label">Balance Clear</div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Full Billing History ({{ $bills->count() }} Bills)</h3>
            </div>
            <div class="card-body">
                @if($bills->count() > 0)
                    @foreach($bills as $bill)
                    @php
                        $statusClass = $bill->status == 'paid' ? 'paid' : ($bill->status == 'partial' ? 'partial' : 'unpaid');
                        $statusText = $bill->status == 'paid' ? 'PAID' : ($bill->status == 'partial' ? 'PARTIAL' : 'UNPAID');
                    @endphp
                    <div class="bill-card">
                        <div class="bill-header {{ $statusClass }}">
                            <div>
                                <div class="bill-month">{{ $bill->bill_month }}</div>
                                <small>Generated: {{ $bill->created_at->format('d-m-y h:i A') }}</small>
                            </div>
                            <div class="text-right">
                                <div class="bill-amount">৳{{ number_format($bill->total_amount ?? 0) }}</div>
                                <span class="badge badge-light">{{ $statusText }}</span>
                            </div>
                        </div>
                        <div class="bill-details">
                            <div class="bill-info-row">
                                <span>Package Fee:</span>
                                <strong>৳{{ number_format($bill->package_fee ?? $bill->total_amount) }}</strong>
                            </div>
                            @if($bill->discount > 0)
                            <div class="bill-info-row">
                                <span>Discount:</span>
                                <strong class="text-danger">-৳{{ number_format($bill->discount ?? 0) }}</strong>
                            </div>
                            @endif
                            <div class="bill-info-row">
                                <span>Paid Amount:</span>
                                <strong class="text-success">৳{{ number_format($bill->paid_amount ?? 0) }}</strong>
                            </div>
                            <div class="bill-info-row">
                                <span>Due Amount:</span>
                                <strong class="{{ $bill->due_amount > 0 ? 'text-danger' : 'text-success' }}">৳{{ number_format($bill->due_amount ?? 0) }}</strong>
                            </div>
                        </div>
                        <div class="payment-list">
                            <h6 class="mb-2"><i class="fas fa-money-bill-wave mr-1"></i>Payments for this bill:</h6>
                            @if($bill->payments->count() > 0)
                                @foreach($bill->payments as $payment)
                                <div class="payment-item flex-column align-items-start">
                                    <div class="d-flex justify-content-between w-100">
                                        <div>
                                            <div><strong>{{ $payment->payment_date->format('d-m-Y') }}</strong> <small class="text-muted">{{ $payment->created_at->format('h:i A') }}</small></div>
                                            <small>
                                                <span class="badge badge-success">{{ $payment->paymentMethod->name ?? 'Cash' }}</span>
                                                @if($payment->collectedBy)
                                                <span class="text-muted ml-1">by {{ $payment->collectedBy->name }}</span>
                                                @endif
                                            </small>
                                        </div>
                                        <div class="payment-amount">৳{{ number_format($payment->amount ?? 0) }}</div>
                                    </div>
                                    @if($payment->transaction_id || $payment->money_receipt_no || $payment->remarks)
                                    <div class="mt-1 w-100" style="font-size: 12px; display: flex; flex-wrap: wrap; gap: 4px; align-items: center;">
                                        @if($payment->money_receipt_no)
                                        <span class="badge badge-secondary"><i class="fas fa-receipt mr-1"></i>Receipt: {{ $payment->money_receipt_no }}</span>
                                        @endif
                                        @if($payment->transaction_id)
                                        <span class="badge badge-info"><i class="fas fa-hashtag mr-1"></i>TrxID: {{ $payment->transaction_id }}</span>
                                        @endif
                                        @php
                                            $senderNum = null;
                                            if ($payment->remarks && preg_match('/Sender:\s*([\d\-\+]+)/', $payment->remarks, $m)) {
                                                $senderNum = $m[1];
                                            }
                                        @endphp
                                        @if($senderNum)
                                        <span class="badge badge-warning"><i class="fas fa-mobile-alt mr-1"></i>{{ $senderNum }}</span>
                                        @endif
                                        @if($payment->remarks)
                                        <small class="text-muted d-block w-100" style="font-size: 11px; margin-top: 1px;"><i class="fas fa-info-circle mr-1"></i>{{ $payment->remarks }}</small>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <div class="no-payment"><i class="fas fa-exclamation-circle mr-1"></i>No payment received yet</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @else
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No bills generated for this client</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
