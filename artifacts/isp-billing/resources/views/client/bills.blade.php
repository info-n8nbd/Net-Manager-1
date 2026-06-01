@extends('layouts.client')
@section('title', 'My Bills')

@push('styles')
<style>
.bill-card {
    background:#fff; border-radius:10px; padding:11px 14px;
    margin-bottom:8px; box-shadow:0 1px 5px rgba(0,0,0,0.06);
    position:relative;
}
.bill-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; border-radius:10px 0 0 10px; overflow:hidden; }
.bill-card.paid::before   { background:#10b981; }
.bill-card.unpaid::before { background:#ef4444; }
.bill-card.partial::before{ background:#f59e0b; }
.btn-pay-now { background:linear-gradient(135deg,#e2136e,#c1105c); color:white; border:none; border-radius:6px; padding:5px 10px; font-size:11px; font-weight:600; cursor:pointer; }
.btn-dl-inv  { background:#f0f4f8; color:#1e3c72; border:none; border-radius:6px; padding:5px 9px; font-size:11px; text-decoration:none; }
.btn-dl-inv:hover { color:#1e3c72; text-decoration:none; }
.gw-btn { display:block; width:100%; padding:10px 6px; border:none; border-radius:8px; color:#fff; font-size:11px; font-weight:600; cursor:pointer; }
@media (min-width:768px) {
    .bills-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .bills-grid .bill-wrapper { margin-bottom:0; }
}
@media (max-width:767.98px) {
    .bill-card { padding:10px 12px; }
    .btn-pay-now { font-size:10px; padding:5px 9px; }
    .btn-dl-inv  { font-size:10px; padding:5px 8px; }
    .gw-btn { font-size:11px; padding:9px 4px; }
}
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <span style="font-size:14px; font-weight:700; color:#1e3c72;"><i class="fas fa-file-invoice-dollar mr-1"></i>My Bills</span>
    @if($bills->count() > 0)<small class="text-muted">{{ $bills->total() }} bill(s)</small>@endif
</div>

<div class="bills-grid">
@forelse($bills as $bill)
@php
  $status = $bill->status ?? 'unpaid';
  $mn = $bill->billing_month;
  $monthName = $mn ? \Carbon\Carbon::create(null, (int)$mn)->format('M') : $bill->created_at->format('M');
  $year = $bill->billing_year ?? $bill->created_at->format('Y');
@endphp
<div class="bill-wrapper">
<div class="bill-card {{ $status }}">
    <div class="d-flex justify-content-between align-items-start mb-1">
        <div>
            <div style="font-size:13px; font-weight:700; color:#1e293b;">{{ $monthName }} {{ $year }}</div>
            <div style="font-size:10px; color:#94a3b8;">Bill #{{ $bill->bill_number ?? $bill->id }}</div>
        </div>
        <div class="text-right">
            <div style="font-size:19px; font-weight:700; color:#1e3c72; line-height:1;">৳{{ number_format($bill->total_amount ?? 0, 0) }}</div>
            <span class="badge badge-{{ $status === 'paid' ? 'success' : ($status === 'partial' ? 'warning' : 'danger') }}" style="font-size:9px; margin-top:2px; display:inline-block;">{{ ucfirst($status) }}</span>
        </div>
    </div>
    @if($bill->due_date)
    <div style="font-size:10px; color:#64748b; margin-bottom:8px;">
        <i class="fas fa-calendar-alt mr-1"></i>Due: {{ $bill->due_date->format('d M Y') }}
        @if($status !== 'paid' && $bill->due_date->isPast())<span class="badge badge-danger ml-1" style="font-size:8px;">Overdue</span>@endif
    </div>
    @endif
    <div class="d-flex" style="gap:6px;">
        <a href="{{ route('client.invoice', $bill) }}" class="btn-dl-inv"><i class="fas fa-download mr-1"></i>Invoice</a>
        @php
            $payLabel = $status === 'paid'
                ? 'Pay Again ৳' . number_format($bill->total_amount ?? 0, 0)
                : 'Pay ৳' . number_format(max(0, ($bill->total_amount ?? 0) - ($bill->paid_amount ?? 0)), 0);
        @endphp
        <button class="btn-pay-now" data-toggle="modal" data-target="#payModal{{ $bill->id }}"><i class="fas fa-credit-card mr-1"></i>{{ $payLabel }}</button>
    </div>
</div>
</div>

<div class="modal fade" id="payModal{{ $bill->id }}">
    <div class="modal-dialog modal-dialog-centered" style="max-width:340px; margin:10px auto;">
        <div class="modal-content" style="border-radius:14px; border:none; overflow:hidden;">
            <div class="modal-header py-2" style="background:linear-gradient(135deg,#1e3c72,#2a5298);">
                <div><h6 class="modal-title text-white mb-0" style="font-size:13px;"><i class="fas fa-credit-card mr-1"></i>Pay Bill</h6><div class="text-white" style="font-size:18px; font-weight:700;">
                ৳{{ $status === 'paid' ? number_format($bill->total_amount ?? 0, 0) : number_format(max(0,($bill->total_amount ?? 0) - ($bill->paid_amount ?? 0)), 0) }}
                @if($status === 'paid')<span style="font-size:10px; opacity:0.8;"> (extra)</span>@endif
                </div></div>
                <button type="button" class="close text-white ml-auto" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" style="padding:14px;">
                <p style="font-size:11px; color:#64748b; font-weight:600; margin-bottom:10px;">Select payment method:</p>
                <div class="row" style="margin:0 -4px;">
                    @foreach([['bkash','bKash','#e2136e','fa-mobile-alt'],['nagad','Nagad','#f6921e','fa-wallet'],['rocket','Rocket','#8C3494','fa-rocket'],['ssl','SSLCommerz','#17a2b8','fa-shield-alt']] as $gw)
                    <div class="col-6 px-1 mb-2">
                        <form action="{{ route('payment.initiate') }}" method="POST">
                            @csrf
                            <input type="hidden" name="bill_id" value="{{ $bill->id }}">
                            <input type="hidden" name="gateway" value="{{ $gw[0] }}">
                            <button type="submit" class="gw-btn" style="background:{{ $gw[2] }};"><i class="fas {{ $gw[3] }} d-block mb-1" style="font-size:16px;"></i>{{ $gw[1] }}</button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@empty
<div class="text-center py-5" style="grid-column:1/-1;"><i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i><p class="text-muted mb-0">No bills found</p></div>
@endforelse
</div>

@if($bills->hasPages())<div class="d-flex justify-content-center mt-3">{{ $bills->links() }}</div>@endif
@endsection
