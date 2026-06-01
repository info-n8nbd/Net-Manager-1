@extends('layouts.reseller')

@section('title', 'Credited & Recharge Transactions')
@section('breadcrumb')
<li class="breadcrumb-item active">Client Transactions</li>
@endsection

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <h3 class="card-title">
            <i class="fas fa-exchange-alt mr-2"></i>Credited & Recharge Transactions
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-xs mr-1" data-toggle="modal" data-target="#rechargeModal">
                <i class="fas fa-bolt mr-1"></i>Recharge Client
            </button>
            <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#curtailModal">
                <i class="fas fa-minus-circle mr-1"></i>Curtail Client
            </button>
            @if(isset($reseller) && $reseller && $reseller->hasFeature('recharge_reversal'))
            <a href="{{ route('reseller.reversed-transactions') }}" class="btn btn-secondary btn-xs ml-1">
                <i class="fas fa-list-alt mr-1"></i>Reversed List
            </a>
            @endif
        </div>
    </div>

    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-end flex-wrap" style="gap:8px;">
            <div>
                <label class="small font-weight-bold text-muted mb-0" style="font-size:10px;">MONTH</label>
                <select name="month" class="form-control form-control-sm" style="width:100px;">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="small font-weight-bold text-muted mb-0" style="font-size:10px;">YEAR</label>
                <select name="year" class="form-control form-control-sm" style="width:90px;">
                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="small font-weight-bold text-muted mb-0" style="font-size:10px;">TYPE</label>
                <select name="transaction_type" class="form-control form-control-sm" style="width:140px;">
                    <option value="">All Types</option>
                    <option value="recharge" {{ request('transaction_type') == 'recharge' ? 'selected' : '' }}>Recharge</option>
                    <option value="curtailed_with_benefits" {{ request('transaction_type') == 'curtailed_with_benefits' ? 'selected' : '' }}>Curtailed</option>
                    <option value="recharge_refund" {{ request('transaction_type') == 'recharge_refund' ? 'selected' : '' }}>Refunded</option>
                    <option value="recharge_reversal" {{ request('transaction_type') == 'recharge_reversal' ? 'selected' : '' }}>Reversed (Correction)</option>
                    <option value="client_left_reversal" {{ request('transaction_type') == 'client_left_reversal' ? 'selected' : '' }}>Reversed (Client Left)</option>
                </select>
            </div>
            <div>
                <label class="small font-weight-bold text-muted mb-0" style="font-size:10px;">SEARCH</label>
                <input type="text" name="search" class="form-control form-control-sm" style="width:160px;" value="{{ request('search') }}" placeholder="Username/Name...">
            </div>
            <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
            <a href="{{ route('reseller.client-transactions') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo mr-1"></i>Reset</a>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover table-sm mb-0" id="txn-table">
            <thead>
                <tr style="background:#2c3e50;color:#fff;">
                    <th>UserName</th>
                    <th>Package</th>
                    <th class="text-right">DailyRate</th>
                    <th>R.From Date</th>
                    <th>R.To Date</th>
                    <th class="text-center">Days</th>
                    <th class="text-right">CreditedAmount</th>
                    <th class="text-center">TransactionType</th>
                    <th>CreditedOn</th>
                    <th>CreditedBy</th>
                    <th>Remarks</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                @php
                    $dailyRate      = $txn->daily_rate ?? ($txn->package_price > 0 ? round($txn->package_price / 30, 4) : 0);
                    $txnType        = $txn->transaction_type ?? 'recharge';
                    $isRecharge     = $txnType === 'recharge';
                    $isRefund       = $txnType === 'recharge_refund';
                    $isCurtail      = $txnType === 'curtailed_with_benefits';
                    $isClientLeft   = $txnType === 'client_left_reversal';
                    $isReversal     = $txnType === 'recharge_reversal';
                    $creditedAmt    = $txn->credited_amount ?? ($isRecharge ? $txn->recharge_amount : -$txn->recharge_amount);
                    $alreadyReversed = isset($reversedIds[$txn->id]);
                @endphp
                <tr class="{{ $isRefund ? 'table-warning' : '' }}">
                    <td>
                        <strong>{{ $txn->client->username ?? 'N/A' }}</strong>
                        @if($txn->client)
                        <br><small class="text-muted">{{ $txn->client->name }}</small>
                        @endif
                        @if($isRefund)
                        <br><span class="badge badge-danger" style="font-size:9px;">Inactive after refund</span>
                        @endif
                    </td>
                    <td>{{ $txn->package_name }}</td>
                    <td class="text-right">{{ number_format($dailyRate, 2) }}</td>
                    <td>{{ $txn->start_date ? $txn->start_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $txn->end_date ? $txn->end_date->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $txn->days }}</td>
                    <td class="text-right font-weight-bold {{ $creditedAmt >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($creditedAmt, 2) }}
                    </td>
                    <td class="text-center">
                        @if($isRecharge)
                            <span class="badge px-2 py-1" style="background:#28a745;color:#fff;font-size:11px;">RECHARGE</span>
                        @elseif($isClientLeft)
                            <span class="badge px-2 py-1" style="background:#8e44ad;color:#fff;font-size:10px;"><i class="fas fa-user-slash mr-1"></i>CLIENT LEFT</span>
                        @elseif($isReversal)
                            <span class="badge px-2 py-1" style="background:#e67e22;color:#fff;font-size:10px;"><i class="fas fa-undo-alt mr-1"></i>REVERSED</span>
                        @elseif($isRefund)
                            <span class="badge px-2 py-1" style="background:#6c757d;color:#fff;font-size:11px;"><i class="fas fa-undo-alt mr-1"></i>REFUNDED</span>
                        @else
                            <span class="badge px-2 py-1" style="background:#e74c3c;color:#fff;font-size:10px;">CURTAILED</span>
                        @endif
                    </td>
                    <td style="font-size:12px;white-space:nowrap;">{{ $txn->created_at->format('d M Y h:i A') }}</td>
                    <td>{{ $txn->rechargedByUser->name ?? 'System' }}</td>
                    <td><small class="text-muted">{{ $txn->remarks ?? '' }}</small></td>
                    <td class="text-center">
                        @if($isRecharge && !isset($refundedIds[$txn->id]) && !$alreadyReversed)
                        <div class="d-flex flex-column align-items-center" style="gap:3px;">
                            <button type="button" class="btn btn-warning btn-xs correct-btn"
                                data-id="{{ $txn->id }}"
                                data-client="{{ $txn->client->username ?? '' }} — {{ $txn->client->name ?? '' }}"
                                data-package="{{ $txn->package_name }}"
                                data-days="{{ $txn->days }}"
                                data-start="{{ $txn->start_date ? $txn->start_date->format('Y-m-d') : '' }}"
                                data-toggle="modal" data-target="#correctModal">
                                <i class="fas fa-edit"></i> Correct
                            </button>
                            @if(isset($reseller) && $reseller && $reseller->hasFeature('recharge_reversal'))
                            <button type="button" class="btn btn-danger btn-xs reverse-btn"
                                data-id="{{ $txn->id }}"
                                data-client="{{ $txn->client->username ?? '' }} — {{ $txn->client->name ?? '' }}"
                                data-package="{{ $txn->package_name }}"
                                data-days="{{ $txn->days }}"
                                data-daily="{{ $txn->daily_rate ?? 0 }}"
                                data-start="{{ $txn->start_date ? $txn->start_date->format('Y-m-d') : '' }}"
                                data-end="{{ $txn->end_date ? $txn->end_date->format('Y-m-d') : '' }}"
                                data-toggle="modal" data-target="#reverseModal">
                                <i class="fas fa-user-slash"></i> Reverse
                            </button>
                            @endif
                        </div>
                        @elseif($isRecharge && isset($refundedIds[$txn->id]))
                        <span class="badge badge-secondary" style="font-size:11px;"><i class="fas fa-undo-alt mr-1"></i>Refunded</span>
                        @elseif($isRecharge && $alreadyReversed)
                        <span class="badge badge-secondary" style="font-size:11px;background:#8e44ad;color:#fff;"><i class="fas fa-user-slash mr-1"></i>Reversed</span>
                        @else
                        <span class="text-muted" style="font-size:11px;">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="12" class="text-center text-muted py-4">No transactions found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer clearfix">
        {{ $transactions->links() }}
    </div>
    @endif
</div>

<div class="modal fade" id="rechargeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reseller.recharge-client') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title"><i class="fas fa-bolt mr-2"></i>Recharge Client</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @if($reseller->billing_type === 'prepaid')
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-wallet mr-1"></i>Wallet Balance:
                        <strong class="{{ $reseller->wallet_balance > 0 ? 'text-success' : 'text-danger' }}">৳{{ number_format($reseller->wallet_balance, 2) }}</strong>
                    </div>
                    @endif
                    <div class="form-group">
                        <label>Select Client <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-control" required>
                            <option value="">-- Select Client --</option>
                            @foreach($reseller->clients()->with('package')->orderBy('username')->get() as $client)
                            <option value="{{ $client->id }}">{{ $client->username }} — {{ $client->name }} ({{ $client->package->name ?? 'N/A' }} - ৳{{ $client->package->price ?? $client->monthly_fee ?? 0 }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Days</label>
                                <input type="number" name="days" class="form-control" placeholder="Auto ({{ $reseller->minimum_recharge_days ?? 30 }})" min="1" max="365">
                                <small class="text-muted">Leave empty for default</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Remarks <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional note...">
                    </div>
                    @if($reseller->billing_type === 'postpaid')
                    <div class="alert alert-warning py-2 mb-0">
                        <small><i class="fas fa-info-circle mr-1"></i>Postpaid: This amount will be added to your monthly dues.</small>
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-bolt mr-1"></i>Recharge Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="curtailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reseller.curtail-client') }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white py-2">
                    <h5 class="modal-title"><i class="fas fa-minus-circle mr-2"></i>Curtail Client (Credit Adjustment)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Client <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-control" required>
                            <option value="">-- Select Client --</option>
                            @foreach($reseller->clients()->with('package')->orderBy('username')->get() as $client)
                            <option value="{{ $client->id }}">{{ $client->username }} — {{ $client->name }} ({{ $client->package->name ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>From Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>To Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Reason for curtailment...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-minus-circle mr-1"></i>Create Curtailed Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="correctModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reseller.correct-recharge') }}" method="POST" id="correctForm">
                @csrf
                <input type="hidden" name="recharge_id" id="correct_recharge_id">
                <div class="modal-header py-2" style="background:#e67e22;">
                    <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Correct Recharge</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 mb-3" style="font-size:13px;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>How correction works:</strong> The wrong recharge will be <strong>reversed</strong> (wallet credited back for prepaid), and a new correct recharge will be applied automatically. Client status and expire date will update accordingly.
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted">Original Recharge (to be corrected)</label>
                        <div class="border rounded p-2 bg-light" id="correct_original_info" style="font-size:13px;"></div>
                    </div>
                    <hr class="my-2">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>New Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="new_start_date" id="correct_new_start" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>New Days <span class="text-danger">*</span></label>
                                <input type="number" name="new_days" id="correct_new_days" class="form-control" min="1" max="365" required>
                                <small class="text-muted">Days of service to apply</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Correction Reason <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Wrong dates entered, extra days added...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure? This will reverse the original recharge and apply a new one.')">
                        <i class="fas fa-check mr-1"></i>Apply Correction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#txn-table thead th {
    font-size: 12px;
    white-space: nowrap;
    border-bottom: 2px solid #e74c3c;
    padding: 8px 10px;
}
#txn-table td {
    vertical-align: middle;
    font-size: 13px;
    padding: 6px 10px;
}
</style>

@if(isset($reseller) && $reseller && $reseller->hasFeature('recharge_reversal'))
<div class="modal fade" id="reverseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reseller.reverse-recharge') }}" method="POST">
                @csrf
                <input type="hidden" name="recharge_id" id="rev_recharge_id">
                <div class="modal-header py-2" style="background:#8e44ad;">
                    <h5 class="modal-title text-white"><i class="fas fa-user-slash mr-2"></i>Reverse Recharge (Client Left)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert py-2 mb-3" style="background:#f3e5f5;border-left:4px solid #8e44ad;font-size:13px;">
                        <i class="fas fa-info-circle mr-1" style="color:#8e44ad;"></i>
                        Unused days থেকে টাকা ফেরত আসবে।
                        @if(auth()->user() && auth()->user()->reseller && auth()->user()->reseller->billing_type === 'prepaid')
                        <strong>Prepaid:</strong> Wallet-এ credit হবে।
                        @else
                        <strong>Postpaid:</strong> Admin-কে দেওয়ার Due কমবে।
                        @endif
                    </div>
                    <div class="border rounded p-2 bg-light mb-3" id="rev_original_info" style="font-size:13px;"></div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Reverse From Date <span class="text-danger">*</span></label>
                            <input type="date" name="reverse_from" id="rev_from_date" class="form-control form-control-sm" required>
                            <small class="text-muted">এই তারিখ থেকে end date পর্যন্ত unused</small>
                        </div>
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Unused Days (Auto)</label>
                            <input type="text" id="rev_unused_days" class="form-control form-control-sm bg-light" readonly placeholder="Date select করুন">
                        </div>
                    </div>
                    <div class="mb-2 p-2 rounded" id="rev_refund_box" style="background:#e8f5e9;display:none;">
                        <strong>Estimated Refund:</strong> <span id="rev_refund_amt" class="text-success font-weight-bold" style="font-size:15px;"></span>
                        <small class="text-muted d-block">= Daily Rate × Unused Days</small>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Remarks <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="কারণ লিখুন...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white" style="background:#8e44ad;" onclick="return confirm('Recharge reverse করবেন? এই action টি undo করা যাবে না।')">
                        <i class="fas fa-user-slash mr-1"></i>Confirm Reverse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Reverse button handler
document.querySelectorAll('.reverse-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id      = this.getAttribute('data-id');
        var client  = this.getAttribute('data-client');
        var pkg     = this.getAttribute('data-package');
        var days    = this.getAttribute('data-days');
        var daily   = parseFloat(this.getAttribute('data-daily')) || 0;
        var start   = this.getAttribute('data-start');
        var end     = this.getAttribute('data-end');

        document.getElementById('rev_recharge_id').value = id;
        document.getElementById('rev_from_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('rev_original_info').innerHTML =
            '<strong>Client:</strong> ' + client + '<br>' +
            '<strong>Package:</strong> ' + pkg + '<br>' +
            '<strong>Period:</strong> ' + start + ' → ' + end + ' (' + days + ' days)' + '<br>' +
            '<strong>Daily Rate:</strong> ৳' + daily.toFixed(2);

        // Store for calculation
        document.getElementById('rev_from_date').setAttribute('data-end', end);
        document.getElementById('rev_from_date').setAttribute('data-daily', daily);
        calculateUnused();
    });
});

function calculateUnused() {
    var fromInput = document.getElementById('rev_from_date');
    var end = fromInput.getAttribute('data-end');
    var daily = parseFloat(fromInput.getAttribute('data-daily')) || 0;
    var from = fromInput.value;
    if (!from || !end) return;
    var fromDate = new Date(from);
    var endDate  = new Date(end);
    var unused = fromDate <= endDate ? Math.floor((endDate - fromDate) / 86400000) + 1 : 0;
    var refund = (daily * unused).toFixed(2);
    document.getElementById('rev_unused_days').value = unused + ' days';
    var box = document.getElementById('rev_refund_box');
    box.style.display = 'block';
    document.getElementById('rev_refund_amt').textContent = '৳' + parseFloat(refund).toLocaleString('en-BD', {minimumFractionDigits:2});
}

var revFromDate = document.getElementById('rev_from_date');
if (revFromDate) {
    revFromDate.addEventListener('change', calculateUnused);
}

document.querySelectorAll('.correct-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id      = this.getAttribute('data-id');
        var client  = this.getAttribute('data-client');
        var pkg     = this.getAttribute('data-package');
        var days    = this.getAttribute('data-days');
        var start   = this.getAttribute('data-start');

        document.getElementById('correct_recharge_id').value = id;
        document.getElementById('correct_new_start').value   = start;
        document.getElementById('correct_new_days').value    = days;
        document.getElementById('correct_original_info').innerHTML =
            '<strong>Client:</strong> ' + client + '<br>' +
            '<strong>Package:</strong> ' + pkg + '<br>' +
            '<strong>Original Start:</strong> ' + start + ' &nbsp; <strong>Days:</strong> ' + days;
    });
});
</script>
@endpush
@endsection
