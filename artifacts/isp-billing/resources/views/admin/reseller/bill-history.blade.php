@extends('layouts.admin')

@section('title', ($reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller') . ' Bill History - ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type'=>$reseller->type]) }}">{{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller' }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item active">Bill History</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <ul class="nav nav-tabs" id="billTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="ledger-tab" data-toggle="tab" href="#ledger" role="tab">
                    <i class="fas fa-book mr-1"></i> Ledger
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="history-tab" data-toggle="tab" href="#history" role="tab">
                    <i class="fas fa-history mr-1"></i> History & Payments
                </a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="billTabsContent">
            <div class="tab-pane fade show active" id="ledger" role="tabpanel">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user mr-2"></i>{{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller' }} Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <strong>Name:</strong><br>{{ $reseller->name }}
                            </div>
                            <div class="col-md-2">
                                <strong>Area/Zone:</strong><br>{{ $reseller->thana ?? '-' }}
                            </div>
                            <div class="col-md-2">
                                <strong>Cell No:</strong><br>{{ $reseller->phone ?? '-' }}
                            </div>
                            <div class="col-md-2">
                                <strong>Total Clients:</strong><br>{{ $totalClients }}
                            </div>
                            <div class="col-md-2">
                                <strong>Active Clients:</strong><br>{{ $activeClients }}
                            </div>
                            <div class="col-md-2">
                                <strong>Balance:</strong><br>
                                <span class="{{ $reseller->wallet_balance >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                                    ৳{{ number_format($reseller->wallet_balance, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-table mr-2"></i>Monthly Ledger</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Month</th>
                                    <th class="text-right">Opening Balance</th>
                                    <th class="text-right">{{ $reseller->type === 'bandwidth' ? 'Total Billed' : 'Total Recharged' }}</th>
                                    <th class="text-right">Total Payments</th>
                                    <th class="text-right">Discount</th>
                                    <th class="text-right">Closing Balance</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $sumRecharged = 0;
                                    $sumPayments = 0;
                                    $sumDiscount = 0;
                                @endphp
                                @forelse($ledger as $entry)
                                @php
                                    $sumRecharged += $entry['total_recharge'] ?? 0;
                                    $sumPayments += $entry['total_paid'] ?? 0;
                                    $sumDiscount += $entry['discount_amount'] ?? 0;
                                @endphp
                                <tr>
                                    <td>{{ $entry['month_label'] ?? ($entry['bill_month'] . '-' . $entry['bill_year']) }}</td>
                                    <td class="text-right">৳{{ number_format($entry['opening_balance'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳{{ number_format($entry['total_recharge'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳{{ number_format($entry['total_paid'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳{{ number_format($entry['discount_amount'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳{{ number_format($entry['closing_balance'] ?? 0, 2) }}</td>
                                    <td class="text-center">
                                        @php
                                            $status = $entry['status'] ?? 'unpaid';
                                            $badgeClass = $status === 'paid' ? 'success' : ($status === 'partial' ? 'warning' : 'danger');
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }}">{{ ucfirst($status) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No ledger data found</td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($ledger) > 0)
                            <tfoot class="font-weight-bold bg-light">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-right">৳{{ number_format($ledger[0]['opening_balance'] ?? 0, 2) }}</td>
                                    <td class="text-right">৳{{ number_format($sumRecharged, 2) }}</td>
                                    <td class="text-right">৳{{ number_format($sumPayments, 2) }}</td>
                                    <td class="text-right">৳{{ number_format($sumDiscount, 2) }}</td>
                                    <td class="text-right">৳{{ number_format($ledger[count($ledger) - 1]['closing_balance'] ?? 0, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="history" role="tabpanel">
                @forelse($bills as $bill)
                <div class="card card-outline card-primary collapsed-card">
                    <div class="card-header" data-card-widget="collapse" style="cursor: pointer;">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            {{ \Carbon\Carbon::createFromDate($bill->bill_year, $bill->bill_month, 1)->format('M-Y') }}
                            <span class="ml-3">
                                Total: <strong>৳{{ number_format($bill->total_recharge, 2) }}</strong>
                            </span>
                            <span class="ml-2">
                                Paid: <strong class="text-success">৳{{ number_format($bill->total_paid, 2) }}</strong>
                            </span>
                            <span class="ml-2">
                                Due: <strong class="text-danger">৳{{ number_format($bill->due_amount, 2) }}</strong>
                            </span>
                            @php
                                $bStatus = $bill->status ?? 'unpaid';
                                $bBadge = $bStatus === 'paid' ? 'success' : ($bStatus === 'partial' ? 'warning' : 'danger');
                            @endphp
                            <span class="badge badge-{{ $bBadge }} ml-2">{{ ucfirst($bStatus) }}</span>
                        </h3>
                        <div class="card-tools d-flex align-items-center">
                            <a href="{{ route('admin.reseller.invoice', $bill) }}"
                               class="btn btn-sm btn-outline-primary mr-1" target="_blank" title="View Invoice"
                               onclick="event.stopPropagation();">
                                <i class="fas fa-file-invoice"></i> Invoice
                            </a>
                            <a href="{{ route('admin.reseller.invoice.download', $bill) }}"
                               class="btn btn-sm btn-outline-danger mr-2" title="Download PDF"
                               onclick="event.stopPropagation();">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <button type="button" class="btn btn-tool"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        {{-- Referral Commission (BW resellers with agent) --}}
                        @if($reseller->agent && $bill->commission_amount > 0)
                        <div class="alert alert-info py-2 mb-3">
                            <i class="fas fa-user-tie mr-2"></i>
                            <strong>Referral Commission:</strong>
                            {{ $reseller->agent->name }} &mdash;
                            {{ number_format($bill->commission_rate, 2) }}% on
                            ৳{{ number_format($bill->total_recharge, 2) }}
                            = <strong class="text-primary">৳{{ number_format($bill->commission_amount, 2) }}</strong>
                        </div>
                        @endif
                        {{-- Bandwidth Breakdown (bandwidth resellers only) --}}
                        @if($bill->bandwidth_breakdown && count($bill->bandwidth_breakdown) > 0)
                        <div class="alert alert-warning py-2 mb-3">
                            <strong><i class="fas fa-tachometer-alt mr-1"></i>Bandwidth Breakdown:</strong>
                            <table class="table table-sm table-bordered mt-2 mb-0 bg-white">
                                <thead class="thead-light"><tr><th>Category</th><th class="text-right">Mbps</th><th class="text-right">Rate (৳/Mbps)</th><th class="text-right">Subtotal</th></tr></thead>
                                <tbody>
                                    @foreach($bill->bandwidth_breakdown as $cat)
                                    <tr>
                                        <td>{{ $cat['label'] }}</td>
                                        <td class="text-right">{{ number_format($cat['mbps'],2) }}</td>
                                        <td class="text-right">{{ number_format($cat['rate'],2) }}৳</td>
                                        <td class="text-right font-weight-bold">{{ number_format($cat['subtotal'],2) }}৳</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot><tr><th colspan="3" class="text-right" style="color:#007bff;font-size:1.05rem;">Total</th><th class="text-right" style="color:#28a745;font-size:1.05rem;">৳{{ number_format($bill->total_recharge,2) }}</th></tr></tfoot>
                            </table>
                        </div>
                        @endif
                        <h6 class="font-weight-bold"><i class="fas fa-list-alt mr-1"></i> Bill Entries</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ComID</th>
                                        <th>Client Name</th>
                                        <th>Username</th>
                                        <th>Package</th>
                                        <th class="text-right">Amount</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th class="text-center">Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $rechargeTotal = 0; @endphp
                                    @forelse($bill->rechargeEntries as $recharge)
                                    @php $rechargeTotal += $recharge->recharge_amount; @endphp
                                    <tr>
                                        <td>{{ $recharge->client->client_id ?? '-' }}</td>
                                        <td>{{ $recharge->client->name ?? '-' }}</td>
                                        <td>{{ $recharge->client->username ?? '-' }}</td>
                                        <td>{{ $recharge->package_name ?? '-' }}</td>
                                        <td class="text-right">৳{{ number_format($recharge->recharge_amount, 2) }}</td>
                                        <td>{{ $recharge->start_date ? $recharge->start_date->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $recharge->end_date ? $recharge->end_date->format('d-m-Y') : '-' }}</td>
                                        <td class="text-center">{{ $recharge->days ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No bill entries</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="font-weight-bold bg-light">
                                    <tr>
                                        <td colspan="4" class="text-right">Area Total Bill:</td>
                                        <td class="text-right">৳{{ number_format($rechargeTotal, 2) }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if($bill->paymentEntries && count($bill->paymentEntries) > 0)
                        <h6 class="font-weight-bold mt-3"><i class="fas fa-money-check-alt mr-1"></i> Payment Entries</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                        <th class="text-right">Discount</th>
                                        <th>Method</th>
                                        <th>Reference / Receipt</th>
                                        <th>Received By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bill->paymentEntries as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date ? $payment->payment_date->format('d-m-Y') : '-' }}</td>
                                        <td class="text-right text-success font-weight-bold">৳{{ number_format($payment->amount, 2) }}</td>
                                        <td class="text-right">৳{{ number_format($payment->discount_amount, 2) }}</td>
                                        <td><span class="badge badge-secondary">{{ ucfirst($payment->payment_method ?? '-') }}</span></td>
                                        <td>{{ $payment->payment_reference ?? '-' }}</td>
                                        <td>{{ $payment->receivedByUser->name ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="card">
                    <div class="card-body text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No bill history found</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible mt-3 mb-0">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
        </div>
        @endif

<div class="mb-3">
    <a href="{{ route('admin.reseller.show', $reseller) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller' }}
    </a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (new URLSearchParams(window.location.search).get('tab') === 'history') {
        var ht = document.getElementById('history-tab');
        var lt = document.getElementById('ledger-tab');
        var hp = document.getElementById('history');
        var lp = document.getElementById('ledger');
        if (ht) {
            lt.classList.remove('active');
            lp.classList.remove('show', 'active');
            ht.classList.add('active');
            hp.classList.add('show', 'active');
        }
    }
});
</script>
@endsection