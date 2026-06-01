@extends('layouts.reseller')

@section('title', 'Bandwidth Dashboard')

@push('styles')
<style>
.kpi-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;}
@media(max-width:991px){.kpi-cards{grid-template-columns:repeat(2,1fr);}}
@media(max-width:480px){.kpi-cards{grid-template-columns:repeat(2,1fr);gap:10px;}}
.kpi-card{background:#fff;border-radius:14px;padding:20px 20px 16px;box-shadow:0 2px 12px rgba(0,0,0,.08);border:1px solid #f1f5f9;position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s;}
.kpi-card:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(0,0,0,.12);}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.kpi-card.blue::before{background:linear-gradient(90deg,#1d4ed8,#3b82f6);}
.kpi-card.red::before{background:linear-gradient(90deg,#dc2626,#f87171);}
.kpi-card.green::before{background:linear-gradient(90deg,#15803d,#22c55e);}
.kpi-card.gray::before{background:linear-gradient(90deg,#475569,#94a3b8);}
.kpi-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:12px;}
.kpi-card.blue  .kpi-icon{background:#eff6ff;color:#1d4ed8;}
.kpi-card.red   .kpi-icon{background:#fef2f2;color:#dc2626;}
.kpi-card.green .kpi-icon{background:#f0fdf4;color:#15803d;}
.kpi-card.gray  .kpi-icon{background:#f8fafc;color:#475569;}
.kpi-value{font-size:26px;font-weight:700;letter-spacing:-.5px;color:#0f172a;line-height:1;margin-bottom:4px;}
.kpi-label{font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;}

.bw-card{background:#fff;border-radius:14px;border:1px solid #f1f5f9;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:20px;overflow:hidden;}
.bw-card-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #f1f5f9;}
.bw-card-title{font-size:14px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;}
.bw-card-title i{color:#3b82f6;font-size:13px;}

.pricing-table{width:100%;border-collapse:collapse;font-size:13px;}
.pricing-table thead tr{background:#f8fafc;}
.pricing-table th{padding:10px 16px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f1f5f9;white-space:nowrap;}
.pricing-table td{padding:10px 16px;border-bottom:1px solid #f8fafc;color:#374151;}
.pricing-table tbody tr:last-child td{border-bottom:none;}
.pricing-table tbody tr:hover td{background:#fafbfc;}
.pricing-table tfoot td{padding:12px 16px;font-weight:700;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;}

.info-table{width:100%;border-collapse:collapse;font-size:13px;}
.info-table td{padding:9px 16px;border-bottom:1px solid #f8fafc;vertical-align:middle;}
.info-table tr:last-child td{border-bottom:none;}
.info-label{color:#94a3b8;font-weight:500;width:38%;}
.info-val{color:#0f172a;font-weight:600;}

.ledger-table{width:100%;border-collapse:collapse;font-size:13px;}
.ledger-table thead tr{background:#f8fafc;}
.ledger-table th{padding:10px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #f1f5f9;white-space:nowrap;}
.ledger-table td{padding:11px 14px;border-bottom:1px solid #f8fafc;color:#374151;vertical-align:middle;}
.ledger-table tbody .bill-row:hover td{background:#fafbfc;}
.ledger-table tfoot td{padding:11px 14px;font-weight:700;color:#0f172a;background:#f8fafc;border-top:2px solid #e9ecef;}

.breakdown-row td{padding:0 !important;background:#f0f4ff !important;}
.breakdown-inner{padding:14px 16px 14px 36px;border-left:3px solid #3b82f6;}
.breakdown-title{font-size:11px;font-weight:700;color:#3b82f6;text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.breakdown-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:6px;}
.breakdown-item{display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;}
.bk-cat{color:#374151;font-weight:600;font-size:12px;}
.bk-calc{color:#94a3b8;font-size:11px;margin-top:2px;}
.bk-total{font-weight:700;color:#1d4ed8;font-size:13px;}

.btn-invoice{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:12px;text-decoration:none;transition:all .2s;margin-left:3px;}
.btn-invoice:hover{background:#eff6ff;border-color:#3b82f6;color:#3b82f6;text-decoration:none;}

.sbadge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;}
.sbadge-paid{background:#f0fdf4;color:#15803d;}
.sbadge-partial{background:#fffbeb;color:#b45309;}
.sbadge-unpaid{background:#fef2f2;color:#dc2626;}
.sbadge-blue{background:#eff6ff;color:#1d4ed8;}

.portal-header{background:linear-gradient(135deg,#0a1628 0%,#1d4ed8 100%);border-radius:14px;padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;position:relative;overflow:hidden;}
.portal-header::after{content:'';position:absolute;top:-60px;right:-60px;width:240px;height:240px;background:radial-gradient(circle,rgba(255,255,255,.07) 0%,transparent 70%);pointer-events:none;}
.portal-header h2{color:#fff;font-size:20px;font-weight:700;margin:0 0 3px;letter-spacing:-.3px;}
.portal-header p{color:rgba(255,255,255,.6);font-size:13px;margin:0;}
.reseller-tag{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:12px;font-weight:600;padding:5px 14px;border-radius:100px;display:inline-flex;align-items:center;gap:6px;}

.wallet-card{background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;margin:12px 20px;display:flex;align-items:center;justify-content:space-between;}
.wallet-balance{font-size:24px;font-weight:700;color:#15803d;}
.wallet-label{font-size:12px;color:#64748b;font-weight:500;}

.adj-table,.pay-table{width:100%;border-collapse:collapse;font-size:13px;}
.adj-table th,.pay-table th{padding:9px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;background:#f8fafc;border-bottom:1px solid #f1f5f9;white-space:nowrap;}
.adj-table td,.pay-table td{padding:10px 14px;border-bottom:1px solid #f8fafc;color:#374151;vertical-align:middle;}
.adj-table tr:last-child td,.pay-table tr:last-child td{border-bottom:none;}
.change-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:100px;font-size:11px;font-weight:600;}
.cp-up{background:#f0fdf4;color:#15803d;}
.cp-down{background:#fef2f2;color:#dc2626;}
.cp-same{background:#f8fafc;color:#64748b;}
</style>
@endpush

@section('content')

{{-- Portal Header --}}
<div class="portal-header">
    <div>
        <h2><i class="fas fa-network-wired mr-2"></i>Bandwidth Dashboard</h2>
        <p>Bandwidth reseller billing &amp; allocation overview</p>
    </div>
    <span class="reseller-tag">
        <i class="fas fa-store"></i>
        Bandwidth Reseller Portal &nbsp;&middot;&nbsp; {{ $reseller->name }}
    </span>
</div>

{{-- KPI Cards --}}
<div class="kpi-cards">
    @if($reseller->hasFeature('view_bw_price'))
    <div class="kpi-card blue">
        <div class="kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="kpi-value">{{ number_format($reseller->bandwidth_monthly_rate ?? 0) }}৳</div>
        <div class="kpi-label">Monthly Bill</div>
    </div>
    @endif
    <div class="kpi-card {{ ($totalDue ?? 0) > 0 ? 'red' : 'green' }}">
        <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="kpi-value">{{ number_format($totalDue ?? 0, 0) }}৳</div>
        <div class="kpi-label">Total Due</div>
    </div>
    @php $totalPaidAll = $bills->sum('total_paid'); @endphp
    <div class="kpi-card green">
        <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
        <div class="kpi-value">{{ number_format($totalPaidAll, 0) }}৳</div>
        <div class="kpi-label">Total Paid</div>
    </div>
    <div class="kpi-card gray">
        <div class="kpi-icon"><i class="fas fa-wallet"></i></div>
        <div class="kpi-value">{{ number_format($reseller->wallet_balance ?? 0, 0) }}৳</div>
        <div class="kpi-label">Wallet Balance</div>
    </div>
</div>

<div class="row">
    {{-- LEFT COLUMN --}}
    <div class="col-md-6">

        {{-- Bandwidth Allocation --}}
        <div class="bw-card">
            <div class="bw-card-header">
                <div class="bw-card-title"><i class="fas fa-chart-bar"></i> Bandwidth Allocation &amp; Pricing</div>
            </div>
            @php $categories = $reseller->getBandwidthCategories(); @endphp
            @if($categories)
            <table class="pricing-table">
                <thead><tr>
                    <th>Category</th>
                    @if($reseller->hasFeature('view_bw_amount'))<th class="text-right">Mbps</th>@endif
                    @if($reseller->hasFeature('view_bw_price'))<th class="text-right">Rate/Mbps</th>@endif
                    @if($reseller->hasFeature('view_bw_price'))<th class="text-right">Monthly</th>@endif
                </tr></thead>
                <tbody>
                @foreach($categories as $cat)
                <tr>
                    <td>{{ $cat['label'] }}</td>
                    @if($reseller->hasFeature('view_bw_amount'))<td class="text-right font-weight-bold">{{ number_format($cat['mbps'], 2) }}</td>@endif
                    @if($reseller->hasFeature('view_bw_price'))<td class="text-right" style="color:#64748b;">{{ number_format($cat['rate'], 2) }}৳</td>@endif
                    @if($reseller->hasFeature('view_bw_price'))<td class="text-right font-weight-bold" style="color:#1d4ed8;">{{ number_format($cat['subtotal'], 0) }}৳</td>@endif
                </tr>
                @endforeach
                </tbody>
                <tfoot><tr>
                    <td @if(!$reseller->hasFeature('view_bw_amount') && !$reseller->hasFeature('view_bw_price')) colspan="1" @elseif(!$reseller->hasFeature('view_bw_amount') || !$reseller->hasFeature('view_bw_price')) colspan="2" @else colspan="3" @endif class="text-right">Total Monthly Bill</td>
                    @if($reseller->hasFeature('view_bw_price'))<td class="text-right">{{ number_format($reseller->bandwidth_monthly_rate ?? 0, 0) }}৳</td>@else<td class="text-right text-muted">—</td>@endif
                </tr></tfoot>
            </table>
            @else
            <div class="text-center text-muted py-4" style="font-size:13px;padding:20px;">No bandwidth categories configured yet.</div>
            @endif
        </div>

        {{-- Wallet --}}
        @if($reseller->hasFeature('wallet'))
        <div class="bw-card">
            <div class="bw-card-header">
                <div class="bw-card-title"><i class="fas fa-wallet"></i> Wallet</div>
                <a href="{{ route('reseller.wallet') }}" style="font-size:12px;color:#3b82f6;font-weight:600;"><i class="fas fa-history mr-1"></i>History</a>
            </div>
            <div class="wallet-card">
                <div>
                    <div class="wallet-label">Current Balance</div>
                    <div class="wallet-balance">৳{{ number_format($reseller->wallet_balance, 2) }}</div>
                </div>
                <i class="fas fa-wallet" style="font-size:32px;color:#22c55e;opacity:.35;"></i>
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="col-md-6">

        {{-- Billing Ledger --}}
        @if($reseller->hasFeature('view_billing_ledger'))
        <div class="bw-card">
            <div class="bw-card-header">
                <div class="bw-card-title"><i class="fas fa-book"></i> Billing Ledger</div>
            </div>
            @if($bills->isEmpty())
            <div class="text-center text-muted py-4" style="font-size:13px;padding:20px;">No bills generated yet.</div>
            @else
            <table class="ledger-table">
                <thead><tr>
                    <th>Month</th>
                    <th class="text-right">Bill</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Due</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Invoice</th>
                </tr></thead>
                <tbody>
                @foreach($bills as $idx => $bill)
                <tr class="bill-row" style="cursor:{{ ($bill->bandwidth_breakdown && count($bill->bandwidth_breakdown) > 0) ? 'pointer' : 'default' }};"
                    @if($bill->bandwidth_breakdown && count($bill->bandwidth_breakdown) > 0)
                    onclick="toggleBD({{ $idx }})"
                    @endif>
                    <td>
                        <div style="font-weight:600;color:#0f172a;">{{ \Carbon\Carbon::create($bill->bill_year, $bill->bill_month, 1)->format('M Y') }}</div>
                        @if($bill->bandwidth_breakdown && count($bill->bandwidth_breakdown) > 0)
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                            <i class="fas fa-chevron-down" id="chv-{{ $idx }}" style="transition:transform .2s;"></i>
                            {{ count($bill->bandwidth_breakdown) }} categories
                        </div>
                        @endif
                    </td>
                    <td class="text-right" style="font-weight:700;color:#0f172a;">{{ number_format($bill->total_recharge, 0) }}৳</td>
                    <td class="text-right" style="font-weight:700;color:#15803d;">{{ number_format($bill->total_paid, 0) }}৳</td>
                    <td class="text-right" style="font-weight:700;color:{{ $bill->due_amount > 0 ? '#dc2626' : '#15803d' }};">{{ number_format($bill->due_amount, 0) }}৳</td>
                    <td class="text-center">
                        @if($bill->status === 'paid')
                            <span class="sbadge sbadge-paid"><i class="fas fa-check" style="font-size:9px;"></i> Paid</span>
                        @elseif($bill->status === 'partial')
                            <span class="sbadge sbadge-partial">Partial</span>
                        @else
                            <span class="sbadge sbadge-unpaid">Unpaid</span>
                        @endif
                    </td>
                    <td class="text-center" onclick="event.stopPropagation();">
                        <a href="{{ route('reseller.bandwidth-invoice', $bill->id) }}" class="btn-invoice" title="View" target="_blank"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('reseller.bandwidth-invoice.download', $bill->id) }}" class="btn-invoice" title="Download"><i class="fas fa-download"></i></a>
                    </td>
                </tr>
                @if(is_array($reseller->allowed_features) && in_array('view_category_breakdown', $reseller->allowed_features) && $bill->bandwidth_breakdown && count($bill->bandwidth_breakdown) > 0)
                <tr class="breakdown-row" id="bd-{{ $idx }}" style="display:none;">
                    <td colspan="6">
                        <div class="breakdown-inner">
                            <div class="breakdown-title"><i class="fas fa-sitemap"></i> Category Breakdown</div>
                            <div class="breakdown-grid">
                                @foreach($bill->bandwidth_breakdown as $cat)
                                <div class="breakdown-item">
                                    <div>
                                        <div class="bk-cat">{{ $cat['label'] }}</div>
                                        <div class="bk-calc">{{ number_format($cat['mbps'],0) }} Mbps &times; {{ number_format($cat['rate'],0) }}৳</div>
                                    </div>
                                    <div class="bk-total">{{ number_format($cat['subtotal'],0) }}৳</div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
                </tbody>
                <tfoot><tr>
                    <td>Total</td>
                    <td class="text-right">{{ number_format($bills->sum('total_recharge'), 0) }}৳</td>
                    <td class="text-right" style="color:#15803d;">{{ number_format($bills->sum('total_paid'), 0) }}৳</td>
                    <td class="text-right" style="color:#dc2626;">{{ number_format($bills->sum('due_amount'), 0) }}৳</td>
                    <td colspan="2"></td>
                </tr></tfoot>
            </table>
            @endif
        </div>
        @endif {{-- view_billing_ledger --}}

    </div>
</div>

{{-- Mid-Month Adjustments --}}
@if(isset($midMonthAdjustments) && $midMonthAdjustments && $midMonthAdjustments->count() > 0 && $reseller->hasFeature('view_adjustments'))
<div class="bw-card">
    <div class="bw-card-header">
        <div class="bw-card-title"><i class="fas fa-sliders-h"></i> Mid-Month Adjustments <span style="font-size:11px;font-weight:400;color:#94a3b8;margin-left:4px;">(Current Month)</span></div>
    </div>
    <table class="adj-table">
        <thead><tr>
            <th>Category</th><th>Effective Date</th><th class="text-right">Old Mbps</th>
            <th class="text-right">New Mbps</th><th class="text-right">Rate</th><th>Reason</th>
        </tr></thead>
        <tbody>
        @foreach($midMonthAdjustments as $adj)
        @php $diff = ($adj->new_mbps ?? 0) - ($adj->old_mbps ?? 0); @endphp
        <tr>
            <td style="font-weight:600;">{{ $adj->category_label ?? $adj->category ?? '—' }}</td>
            <td style="color:#64748b;">{{ \Carbon\Carbon::parse($adj->effective_date)->format('d M Y') }}</td>
            <td class="text-right">{{ number_format($adj->old_mbps ?? 0, 0) }}</td>
            <td class="text-right">
                <span class="change-pill {{ $diff > 0 ? 'cp-up' : ($diff < 0 ? 'cp-down' : 'cp-same') }}">
                    @if($diff > 0)<i class="fas fa-arrow-up" style="font-size:9px;"></i>@elseif($diff < 0)<i class="fas fa-arrow-down" style="font-size:9px;"></i>@endif
                    {{ number_format($adj->new_mbps ?? 0, 0) }}
                </span>
            </td>
            <td class="text-right" style="font-weight:600;">{{ $adj->rate ? number_format($adj->rate,0).'৳' : '—' }}</td>
            <td style="color:#64748b;font-size:12px;">{{ $adj->reason ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Recent Payments --}}
@if(isset($recentPayments) && $recentPayments && $recentPayments->count() > 0 && $reseller->hasFeature('view_payments'))
<div class="bw-card">
    <div class="bw-card-header">
        <div class="bw-card-title"><i class="fas fa-money-bill-wave"></i> Recent Payments <span style="font-size:11px;font-weight:400;color:#94a3b8;margin-left:4px;">({{ \Carbon\Carbon::now()->format('M Y') }})</span></div>
    </div>
    <table class="pay-table">
        <thead><tr>
            <th>Date</th><th class="text-right">Amount</th><th class="text-right">Discount</th>
            <th>Method</th><th>Reference</th><th>Received By</th>
        </tr></thead>
        <tbody>
        @foreach($recentPayments as $pmt)
        <tr>
            <td style="color:#64748b;white-space:nowrap;">{{ \Carbon\Carbon::parse($pmt->payment_date ?? $pmt->created_at)->format('d M Y') }}</td>
            <td class="text-right" style="font-weight:700;color:#15803d;">৳{{ number_format($pmt->amount ?? 0, 2) }}</td>
            <td class="text-right" style="color:#64748b;">{{ $pmt->discount ? '৳'.number_format($pmt->discount,2) : '৳0.00' }}</td>
            <td>
                @if(isset($pmt->method))
                <span class="sbadge sbadge-blue" style="font-size:10px;">{{ ucfirst($pmt->method) }}</span>
                @else —
                @endif
            </td>
            <td style="font-size:12px;color:#64748b;">{{ $pmt->reference ?? '—' }}</td>
            <td style="font-size:12px;color:#64748b;">{{ $pmt->received_by ?? $pmt->collector_name ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection

@push('scripts')
<script>
function toggleBD(idx){
    var r=document.getElementById('bd-'+idx);
    var c=document.getElementById('chv-'+idx);
    if(!r)return;
    if(r.style.display==='none'||r.style.display===''){
        r.style.display='table-row';
        if(c)c.style.transform='rotate(180deg)';
    }else{
        r.style.display='none';
        if(c)c.style.transform='rotate(0)';
    }
}
</script>
@endpush
