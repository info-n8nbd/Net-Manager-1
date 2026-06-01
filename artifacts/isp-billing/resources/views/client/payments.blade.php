@extends('layouts.client')
@section('title', 'Payment History')

@push('styles')
<style>
.pay-card { background:#fff; border-radius:10px; padding:11px 14px; margin-bottom:8px; box-shadow:0 1px 5px rgba(0,0,0,0.06); border-left:3px solid #10b981; }
@media (min-width:768px) {
    .payments-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .payments-grid .pay-card { margin-bottom:0; }
}
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <span style="font-size:14px; font-weight:700; color:#1e3c72;"><i class="fas fa-history mr-1"></i>Payment History</span>
    @if(isset($payments) && $payments->total() > 0)<small class="text-muted">{{ $payments->total() }} record(s)</small>@endif
</div>

<div class="payments-grid">
@forelse($payments ?? [] as $payment)
<div class="pay-card">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div style="font-size:18px; font-weight:700; color:#10b981; line-height:1;">৳{{ number_format($payment->amount ?? 0, 0) }}</div>
            <div style="font-size:10px; color:#64748b; margin-top:3px;"><i class="fas fa-calendar mr-1"></i>{{ ($payment->payment_date ?? $payment->created_at)->format('d M Y') }}</div>
        </div>
        <span style="background:#d1fae5; color:#065f46; font-size:10px; font-weight:600; padding:3px 8px; border-radius:20px;"><i class="fas fa-check-circle mr-1"></i>Paid</span>
    </div>
    <div style="border-top:1px solid #f1f5f9; margin-top:8px; padding-top:8px; display:flex; flex-wrap:wrap; gap:8px; font-size:10px; color:#64748b;">
        <span><i class="fas fa-mobile-alt mr-1 text-primary"></i><strong>{{ $payment->paymentMethod->name ?? 'Cash' }}</strong></span>
        @if($payment->money_receipt_no)<span><i class="fas fa-receipt mr-1"></i>{{ $payment->money_receipt_no }}</span>@endif
        @if($payment->transaction_id)<span class="w-100"><i class="fas fa-fingerprint mr-1"></i>{{ $payment->transaction_id }}</span>@endif
    </div>
</div>
@empty
<div class="text-center py-5" style="grid-column:1/-1;"><i class="fas fa-receipt fa-3x text-muted mb-3"></i><p class="text-muted mb-0">No payment history found</p></div>
@endforelse
</div>

@if(isset($payments) && $payments->hasPages())<div class="d-flex justify-content-center mt-3">{{ $payments->links() }}</div>@endif
@endsection
