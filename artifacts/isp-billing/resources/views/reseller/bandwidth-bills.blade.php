@extends('layouts.reseller')
@section('title', 'Billing & Transactions')
@section('breadcrumb')<li class="breadcrumb-item active">Billing & Transactions</li>@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>৳{{ number_format($totalBilled, 0) }}</h3>
                <p>Total Billed</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($totalPaid, 0) }}</h3>
                <p>Total Paid</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>৳{{ number_format($totalDue, 0) }}</h3>
                <p>Total Due</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box" style="background:#6f42c1; color:white;">
            <div class="inner">
                <h3>৳{{ number_format($reseller->wallet_balance ?? 0, 0) }}</h3>
                <p>Wallet Balance</p>
            </div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
    </div>
</div>

<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-2">
            <select name="month" class="form-control form-control-sm">
                <option value="">All Months</option>
                @for($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-2">
            <select name="year" class="form-control form-control-sm">
                <option value="">All Years</option>
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-control form-control-sm">
                <option value="">All Status</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-info btn-sm">Filter</button>
            <a href="{{ route('reseller.bills') }}" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Monthly Bills</h3>
        <div>
            <span class="badge badge-success mr-1">Paid: {{ $paidCount }}</span>
            <span class="badge badge-warning mr-1">Partial: {{ $partialCount }}</span>
            <span class="badge badge-danger">Unpaid: {{ $unpaidCount }}</span>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-striped table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>Bill No</th>
                    <th>Period</th>
                    <th class="text-right">Billed</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Due</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Invoice</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $bill)
                <tr>
                    <td>
                        <strong>{{ $bill->bill_no }}</strong>
                        @if($bill->bandwidth_breakdown && count($bill->bandwidth_breakdown) > 0)
                        <br><a href="#" class="bw-toggle-breakdown" data-target="breakdown-{{ $bill->id }}" style="font-size:11px;color:#3b82f6;">
                            <i class="fas fa-chevron-down mr-1" id="icon-{{ $bill->id }}"></i><span id="lbl-{{ $bill->id }}">Show Breakdown</span>
                        </a>
                        @endif
                    </td>
                    <td>{{ date('M Y', mktime(0,0,0,$bill->bill_month,1,$bill->bill_year)) }}</td>
                    <td class="text-right">৳{{ number_format($bill->total_recharge, 2) }}</td>
                    <td class="text-right text-success font-weight-bold">৳{{ number_format($bill->total_paid, 2) }}</td>
                    <td class="text-right text-info">৳{{ number_format($bill->discount_amount, 2) }}</td>
                    <td class="text-right {{ $bill->due_amount > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                        ৳{{ number_format($bill->due_amount, 2) }}
                    </td>
                    <td class="text-center">
                        @if($bill->status == 'paid')
                        <span class="badge badge-success">Paid</span>
                        @elseif($bill->status == 'partial')
                        <span class="badge badge-warning">Partial</span>
                        @else
                        <span class="badge badge-danger">Unpaid</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('reseller.bandwidth-invoice', $bill->id) }}" target="_blank" class="btn btn-xs btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('reseller.bandwidth-invoice.download', $bill->id) }}" class="btn btn-xs btn-outline-success" title="PDF"><i class="fas fa-download"></i></a>
                    </td>
                </tr>
                @if($bill->bandwidth_breakdown && count($bill->bandwidth_breakdown) > 0)
                <tr id="breakdown-{{ $bill->id }}" class="bg-light" style="display:none;">
                    <td colspan="8" class="p-0">
                        <div class="px-4 py-2">
                            <small class="text-muted font-weight-bold"><i class="fas fa-tachometer-alt mr-1"></i>Bandwidth Breakdown:</small>
                            <table class="table table-sm table-bordered mb-0 mt-1" style="font-size:12px;">
                                <thead><tr><th>Category</th><th>Mbps</th><th>Rate (৳/Mbps)</th><th>Subtotal</th></tr></thead>
                                <tbody>
                                    @foreach($bill->bandwidth_breakdown as $cat)
                                    <tr>
                                        <td>{{ $cat['label'] }}</td>
                                        <td>{{ number_format($cat['mbps'],2) }}</td>
                                        <td>৳{{ number_format($cat['rate'],2) }}</td>
                                        <td>৳{{ number_format($cat['subtotal'],2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No bills found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bills->hasPages())
    <div class="card-footer">{{ $bills->links() }}</div>
    @endif
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-money-check-alt mr-2"></i>Payment Transactions</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-striped table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>Date</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Discount</th>
                    <th>Method</th>
                    <th>Reference / Receipt</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : '-' }}</td>
                    <td class="text-right text-success font-weight-bold">৳{{ number_format($payment->amount, 2) }}</td>
                    <td class="text-right text-info">৳{{ number_format($payment->discount_amount, 2) }}</td>
                    <td>
                        <span class="badge badge-secondary">{{ ucfirst($payment->payment_method ?? '-') }}</span>
                    </td>
                    <td>{{ $payment->payment_reference ?? '-' }}</td>
                    <td>{{ $payment->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3"><i class="fas fa-receipt fa-2x mb-2 d-block"></i>No payment records found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="card-footer">{{ $payments->appends(request()->except('ppage'))->links('pagination::bootstrap-4') }}</div>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.bw-toggle-breakdown').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.getElementById(this.dataset.target);
            var billId = this.dataset.target.replace('breakdown-', '');
            var icon = document.getElementById('icon-' + billId);
            var lbl = document.getElementById('lbl-' + billId);
            if (target.style.display === 'none') {
                target.style.display = '';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
                lbl.textContent = 'Hide Breakdown';
            } else {
                target.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
                lbl.textContent = 'Show Breakdown';
            }
        });
    });
});
</script>
@endsection