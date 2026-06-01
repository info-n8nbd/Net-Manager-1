@extends('layouts.client')
@section('title', 'Pay Now')
@push('styles')
<style>
.pn-box{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,0.07);margin-bottom:10px;overflow:hidden;}
.pn-hd{padding:8px 14px;border-bottom:1px solid #f1f5f9;font-size:12px;font-weight:600;color:#1e3c72;display:flex;align-items:center;gap:6px;}
.pn-body{padding:12px 14px;}
.gw-chip{display:flex;align-items:center;gap:10px;padding:9px 12px;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .18s;background:#fff;margin-bottom:8px;}
.gw-chip:hover{border-color:#93c5fd;}
.gw-chip.active{border-color:#1e3c72;background:#eff6ff;}
.gw-ico{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.gw-info{flex:1;min-width:0;}
.gw-name{font-size:12px;font-weight:700;color:#1e3c72;}
.gw-sub{font-size:10px;color:#94a3b8;}
.pay-field label{font-size:11px;font-weight:600;color:#64748b;margin-bottom:3px;display:block;}
.pay-field .form-control{font-size:12px;height:34px;border-radius:8px;padding:0 10px;}
.pay-field{margin-bottom:8px;}
.num-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:6px;}
.num-chip{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;}
.submit-btn{background:linear-gradient(135deg,#1e3c72,#2a5298);color:#fff;border:none;border-radius:10px;width:100%;padding:10px;font-size:13px;font-weight:600;margin-top:8px;}
.submit-btn:hover{color:#fff;opacity:.9;}
.gw-submit-btn{width:100%;padding:10px;font-size:13px;font-weight:600;border:none;border-radius:10px;color:#fff;margin-top:6px;}
.bill-row{display:flex;justify-content:space-between;align-items:center;padding:7px 14px;border-bottom:1px solid #f8fafc;font-size:11px;}
.pmt-row{display:flex;justify-content:space-between;align-items:center;padding:7px 14px;border-bottom:1px solid #f8fafc;}
</style>
@endpush
@section('content')

{{-- AMOUNT DUE --}}
<div class="pn-box">
    <div class="pn-hd"><i class="fas fa-money-bill-wave text-success"></i> Payment Summary</div>
    <div class="pn-body text-center" style="padding:10px 14px;">
        @if(($totalDue ?? 0) > 0)
        <div style="font-size:10px;color:#94a3b8;margin-bottom:2px;">Total Amount Due</div>
        <div style="font-size:32px;font-weight:800;color:#ef4444;line-height:1.1;">৳{{ number_format($totalDue, 0) }}</div>
        @else
        <div style="font-size:10px;color:#94a3b8;margin-bottom:2px;">Account Status</div>
        <div style="font-size:22px;font-weight:800;color:#10b981;line-height:1.2;">No Due <i class="fas fa-check-circle ml-1" style="color:#16a34a !important;"></i></div>
        <div style="font-size:11px;color:#64748b;margin-top:4px;">You can still make an advance payment below</div>
        @endif
    </div>
</div>

{{-- ONLINE GATEWAYS --}}
@if(count($enabledGateways ?? []) > 0)
<div class="pn-box">
    <div class="pn-hd"><i class="fas fa-credit-card text-primary"></i> Pay Online</div>
    <div class="pn-body" style="padding:10px 14px;">
        @if(in_array('sslcommerz', $enabledGateways))
        <div class="gw-chip" id="sslCard" onclick="selectGateway('sslcommerz')">
            <div class="gw-ico" style="background:#fff;border:1px solid #e2e8f0;padding:2px;"><img src="{{ asset('images/gateways/sslcommerz.png') }}" alt="SSLCommerz" style="width:30px;height:30px;object-fit:contain;"></div>
            <div class="gw-info"><div class="gw-name">SSLCommerz</div><div class="gw-sub">Visa / Master / Mobile Banking</div></div>
            <i class="fas fa-chevron-right text-muted" style="font-size:10px;"></i>
        </div>
        @endif
        @if(in_array('bkash', $enabledGateways))
        <div class="gw-chip" id="bkashCard" onclick="selectGateway('bkash')">
            <div class="gw-ico" style="background:#fff;border:1px solid #e2e8f0;padding:2px;"><img src="{{ asset('images/gateways/bkash.png') }}" alt="bKash" style="width:30px;height:30px;object-fit:contain;"></div>
            <div class="gw-info"><div class="gw-name">bKash</div><div class="gw-sub">bKash Payment Gateway</div></div>
            <i class="fas fa-chevron-right text-muted" style="font-size:10px;"></i>
        </div>
        @endif
        @if(in_array('nagad', $enabledGateways))
        <div class="gw-chip" id="nagadCard" onclick="selectGateway('nagad')">
            <div class="gw-ico" style="background:#fff;border:1px solid #e2e8f0;padding:2px;"><img src="{{ asset('images/gateways/nagad.png') }}" alt="Nagad" style="width:30px;height:30px;object-fit:contain;"></div>
            <div class="gw-info"><div class="gw-name">Nagad</div><div class="gw-sub">Nagad Payment Gateway</div></div>
            <i class="fas fa-chevron-right text-muted" style="font-size:10px;"></i>
        </div>
        @endif

        <div id="sslcommerzForm" style="display:none;margin-top:8px;">
            <form action="{{ route('client.pay-now.sslcommerz') }}" method="POST">
                @csrf
                <div class="pay-field">
                    <label>Payment Amount (৳)</label>
                    <input type="number" name="amount" class="form-control" value="{{ $totalDue > 0 ? $totalDue : '' }}" min="1" placeholder="Enter amount" required>
                </div>
                <button type="submit" class="gw-submit-btn" style="background:linear-gradient(135deg,#17a2b8,#138496);"><i class="fas fa-shield-alt mr-1"></i>Pay via SSLCommerz</button>
            </form>
        </div>
        <div id="bkashForm" style="display:none;margin-top:8px;">
            <form action="{{ route('client.pay-now.bkash') }}" method="POST">
                @csrf
                <div class="pay-field">
                    <label>Payment Amount (৳)</label>
                    <input type="number" name="amount" class="form-control" value="{{ $totalDue > 0 ? $totalDue : '' }}" min="1" placeholder="Enter amount" required>
                </div>
                <button type="submit" class="gw-submit-btn" style="background:linear-gradient(135deg,#e2136e,#c1105c);"><i class="fas fa-mobile-alt mr-1"></i>Pay via bKash</button>
            </form>
        </div>
        <div id="nagadForm" style="display:none;margin-top:8px;">
            <form action="{{ route('client.pay-now.nagad') }}" method="POST">
                @csrf
                <div class="pay-field">
                    <label>Payment Amount (৳)</label>
                    <input type="number" name="amount" class="form-control" value="{{ $totalDue > 0 ? $totalDue : '' }}" min="1" placeholder="Enter amount" required>
                </div>
                <button type="submit" class="gw-submit-btn" style="background:linear-gradient(135deg,#f6921e,#e07b15);"><i class="fas fa-money-bill-wave mr-1"></i>Pay via Nagad</button>
            </form>
        </div>
    </div>
</div>
@endif

{{-- MANUAL PAYMENT --}}
<div class="pn-box">
    <div class="pn-hd"><i class="fas fa-hand-holding-usd text-warning"></i> Submit Payment Manually</div>
    <div class="pn-body" style="padding:10px 14px;">
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:8px 12px;margin-bottom:10px;">
            <div class="num-row">
                <div class="num-chip"><img src="{{ asset('images/gateways/bkash.png') }}" alt="bKash" style="height:18px;object-fit:contain;vertical-align:middle;"><span>bKash: <strong>{{ $bkashNumber }}</strong></span></div>
                <div class="num-chip"><img src="{{ asset('images/gateways/nagad.png') }}" alt="Nagad" style="height:18px;object-fit:contain;vertical-align:middle;"><span>Nagad: <strong>{{ $nagadNumber }}</strong></span></div>
                <div class="num-chip"><img src="{{ asset('images/gateways/rocket.svg') }}" alt="Rocket" style="height:18px;object-fit:contain;vertical-align:middle;"><span>Rocket: <strong>{{ $rocketNumber }}</strong></span></div>
            </div>
            <div style="font-size:10px;color:#0369a1;"><i class="fas fa-info-circle mr-1"></i>Send money and submit your TrxID below</div>
        </div>
        <form action="{{ route('client.pay-now.submit') }}" method="POST">
            @csrf
            <div class="row" style="margin:0 -5px;">
                <div class="col-6" style="padding:0 5px;">
                    <div class="pay-field">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" style="padding:0 5px;">
                    <div class="pay-field">
                        <label>Amount (৳)</label>
                        <input type="number" name="amount" class="form-control" value="{{ $totalDue > 0 ? $totalDue : '' }}" min="1" placeholder="Enter amount" required>
                    </div>
                </div>
            </div>
            <div class="pay-field">
                <label>Transaction ID (TrxID)</label>
                <input type="text" name="transaction_id" class="form-control" placeholder="Enter your TrxID" required>
            </div>
            <button type="submit" class="submit-btn"><i class="fas fa-paper-plane mr-1"></i>Submit Payment</button>
        </form>
    </div>
</div>

{{-- UNPAID BILLS --}}
@if(isset($unpaidBills) && $unpaidBills->count() > 0)
<div class="pn-box">
    <div class="pn-hd"><i class="fas fa-file-invoice text-danger"></i> Unpaid Bills</div>
    @foreach($unpaidBills as $bill)
    <div class="bill-row">
        <div><strong style="font-size:12px;">{{ $bill->billing_month }}/{{ $bill->billing_year }}</strong><div style="font-size:10px;color:#94a3b8;">{{ $bill->bill_no }}</div></div>
        <div class="text-right"><strong style="color:#ef4444;font-size:13px;">৳{{ number_format($bill->total_amount??0,0) }}</strong><br><span class="badge badge-warning" style="font-size:9px;">{{ ucfirst($bill->status) }}</span></div>
    </div>
    @endforeach
</div>
@endif

{{-- RECENT PAYMENTS --}}
<div class="pn-box">
    <div class="pn-hd"><i class="fas fa-history text-success"></i> Recent Payments</div>
    @forelse($recentPayments as $payment)
    <div class="pmt-row" style="flex-direction:column;align-items:flex-start;gap:2px;padding:9px 14px;">
        <div style="display:flex;justify-content:space-between;width:100%;align-items:center;">
            <strong style="font-size:13px;color:#10b981;">৳{{ number_format($payment->amount??0,0) }}</strong>
            <span class="badge badge-{{ in_array($payment->status,['approved','completed'])?'success':'warning' }}" style="font-size:9px;">{{ ucfirst($payment->status) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;width:100%;align-items:center;margin-top:2px;">
            <span style="font-size:10px;color:#94a3b8;">{{ $payment->payment_date->format('d M Y') }} &bull; {{ $payment->paymentMethod->name??'Cash' }}</span>
            @if($payment->transaction_id)
            <span style="font-size:10px;color:#3b82f6;font-family:monospace;background:#eff6ff;padding:1px 7px;border-radius:4px;letter-spacing:.3px;">{{ $payment->transaction_id }}</span>
            @endif
        </div>
    </div>
    @empty
    <div style="padding:14px;text-align:center;font-size:12px;color:#94a3b8;"><i class="fas fa-receipt mr-1"></i>No payment history</div>
    @endforelse
</div>

@endsection
@push('scripts')
<script>
function selectGateway(gateway){
    document.querySelectorAll('.gw-chip').forEach(function(c){c.classList.remove('active');});
    ['sslcommerzForm','bkashForm','nagadForm'].forEach(function(f){var e=document.getElementById(f);if(e)e.style.display='none';});
    var map={sslcommerz:{card:'sslCard',form:'sslcommerzForm'},bkash:{card:'bkashCard',form:'bkashForm'},nagad:{card:'nagadCard',form:'nagadForm'}};
    var g=map[gateway];
    if(g){var cd=document.getElementById(g.card);var fm=document.getElementById(g.form);if(cd)cd.classList.add('active');if(fm)fm.style.display='block';}
}
</script>
@endpush
