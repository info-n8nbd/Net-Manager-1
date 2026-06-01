@extends('layouts.admin')

@section('title', 'Client Transactions - ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index') }}">Resellers</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item active">Client Transactions</li>
@endsection

@section('content')

<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <h3 class="card-title">
            <i class="fas fa-exchange-alt mr-2"></i>Client Transactions — {{ $reseller->name }}
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#curtailModal">
                <i class="fas fa-minus-circle mr-1"></i>Curtail Client
            </button>
            <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-secondary btn-xs ml-1">
                <i class="fas fa-file-invoice mr-1"></i>Bill History
            </a>
            <a href="{{ route('admin.reseller.reversed-transactions', $reseller) }}" class="btn btn-dark btn-xs ml-1">
                <i class="fas fa-list-alt mr-1"></i>Reversed List
            </a>
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
                <select name="transaction_type" class="form-control form-control-sm" style="width:150px;">
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
            <a href="{{ route('admin.reseller.client-transactions', $reseller) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo mr-1"></i>Reset</a>
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover table-sm mb-0" id="txn-table">
            <thead>
                <tr style="background:#2c3e50;color:#fff;">
                    <th>Username / Client</th>
                    <th>Package</th>
                    <th class="text-right">Daily Rate</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th class="text-center">Days</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Type</th>
                    <th>Created On</th>
                    <th>By</th>
                    <th>Remarks</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                @php
                    $dailyRate      = $txn->daily_rate ?? ($txn->package_price > 0 ? round($txn->package_price / 30, 4) : 0);
                    $creditedAmt    = $txn->credited_amount ?? (($txn->transaction_type ?? 'recharge') === 'recharge' ? $txn->recharge_amount : -$txn->recharge_amount);
                    $txnType        = $txn->transaction_type ?? 'recharge';
                    $isRecharge     = $txnType === 'recharge';
                    $isRefund       = $txnType === 'recharge_refund';
                    $isCurtail      = $txnType === 'curtailed_with_benefits';
                    $isReversal     = $txnType === 'recharge_reversal';
                    $isClientLeft   = $txnType === 'client_left_reversal';
                    $clientActive   = $txn->client && $txn->client->status === 'active';
                    $alreadyReversed = isset($reversedIds[$txn->id]);
                @endphp
                <tr class="{{ $isRefund ? 'table-secondary' : '' }}">
                    <td>
                        <strong>{{ $txn->client->username ?? 'N/A' }}</strong>
                        @if($txn->client)
                        <br><small class="text-muted">{{ $txn->client->name }}</small>
                        @if(!$clientActive)
                        <br><span class="badge badge-danger" style="font-size:9px;">Inactive</span>
                        @endif
                        @endif
                    </td>
                    <td style="font-size:12px;">{{ $txn->package_name }}</td>
                    <td class="text-right" style="font-size:12px;">{{ number_format($dailyRate, 2) }}</td>
                    <td style="font-size:12px;">{{ $txn->start_date ? $txn->start_date->format('d/m/Y') : '-' }}</td>
                    <td style="font-size:12px;">{{ $txn->end_date ? $txn->end_date->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $txn->days }}</td>
                    <td class="text-right font-weight-bold {{ $creditedAmt >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($creditedAmt, 2) }}
                    </td>
                    <td class="text-center">
                        @if($isRecharge)
                            <span class="badge px-2 py-1" style="background:#28a745;color:#fff;font-size:11px;">RECHARGE</span>
                        @elseif($isClientLeft)
                            <span class="badge px-2 py-1" style="background:#8e44ad;color:#fff;font-size:10px;"><i class="fas fa-user-slash"></i> CLIENT LEFT</span>
                        @elseif($isRefund)
                            <span class="badge px-2 py-1" style="background:#6c757d;color:#fff;font-size:11px;">REFUNDED</span>
                        @elseif($isReversal)
                            <span class="badge px-2 py-1" style="background:#e67e22;color:#fff;font-size:10px;"><i class="fas fa-undo-alt"></i> REVERSED</span>
                        @else
                            <span class="badge px-2 py-1" style="background:#e74c3c;color:#fff;font-size:10px;">CURTAILED</span>
                        @endif
                    </td>
                    <td style="font-size:11px;white-space:nowrap;">{{ $txn->created_at->format('d M Y h:i A') }}</td>
                    <td style="font-size:12px;">{{ $txn->rechargedByUser->name ?? 'System' }}</td>
                    <td><small class="text-muted">{{ $txn->remarks ?? '' }}</small></td>
                    <td class="text-center">
                        @if($isRecharge && $txn->client && !isset($refundedIds[$txn->id]) && !$alreadyReversed)
                        <div class="d-flex flex-column" style="gap:3px;">
                            <button class="btn btn-warning btn-xs correct-btn"
                                data-id="{{ $txn->id }}"
                                data-client="{{ $txn->client->username }} — {{ $txn->client->name }}"
                                data-package="{{ $txn->package_name }}"
                                data-days="{{ $txn->days }}"
                                data-start="{{ $txn->start_date ? $txn->start_date->format('Y-m-d') : '' }}"
                                data-toggle="modal" data-target="#correctModal"
                                title="Correct this recharge (reverses and re-applies)">
                                <i class="fas fa-edit"></i> Correct
                            </button>
                            <button class="btn btn-purple btn-xs admin-reverse-btn" style="background:#8e44ad;color:#fff;"
                                data-recharge-id="{{ $txn->id }}"
                                data-client-id="{{ $txn->client->id }}"
                                data-client="{{ $txn->client->username }} — {{ $txn->client->name }}"
                                data-package="{{ $txn->package_name }}"
                                data-days="{{ $txn->days }}"
                                data-daily="{{ $txn->daily_rate ?? 0 }}"
                                data-start="{{ $txn->start_date ? $txn->start_date->format('Y-m-d') : '' }}"
                                data-end="{{ $txn->end_date ? $txn->end_date->format('Y-m-d') : '' }}"
                                data-toggle="modal" data-target="#adminReverseModal"
                                title="Reverse — Client Left (refund unused days)">
                                <i class="fas fa-user-slash"></i> Reverse
                            </button>
                            <button class="btn btn-info btn-xs refund-btn"
                                data-recharge-id="{{ $txn->id }}"
                                data-client-id="{{ $txn->client->id }}"
                                data-client-name="{{ $txn->client->name }}"
                                data-username="{{ $txn->client->username }}"
                                data-amount="{{ $txn->recharge_amount }}"
                                data-package="{{ $txn->package_name }}"
                                data-from="{{ $txn->start_date ? $txn->start_date->format('d/m/Y') : '-' }}"
                                data-to="{{ $txn->end_date ? $txn->end_date->format('d/m/Y') : '-' }}"
                                title="Refund amount only — client stays active">
                                <i class="fas fa-undo-alt"></i> Refund
                            </button>
                            <button class="btn btn-danger btn-xs deactivate-btn"
                                data-client-id="{{ $txn->client->id }}"
                                data-client-name="{{ $txn->client->name }}"
                                data-username="{{ $txn->client->username }}"
                                data-status="{{ $txn->client->status }}"
                                title="Deactivate client — no refund">
                                <i class="fas fa-ban"></i> Deactivate
                            </button>
                        </div>
                        @elseif($isRecharge && $txn->client && $alreadyReversed)
                        <span class="badge" style="background:#8e44ad;color:#fff;font-size:11px;"><i class="fas fa-user-slash mr-1"></i>Reversed</span>
                        @elseif($isRecharge && $txn->client && isset($refundedIds[$txn->id]))
                        <span class="badge badge-secondary" style="font-size:11px;"><i class="fas fa-undo-alt mr-1"></i>Refunded</span>
                        @elseif($isRefund)
                        <span class="text-muted" style="font-size:11px;"><i class="fas fa-check-circle"></i> Refunded</span>
                        @elseif($isReversal)
                        <span class="text-muted" style="font-size:11px;"><i class="fas fa-ban"></i> Voided</span>
                        @else
                        <span class="text-muted">—</span>
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
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $transactions->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script>
    </div>
    @endif
</div>

{{-- CORRECT MODAL --}}
<div class="modal fade" id="correctModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.reseller.correct-recharge', $reseller) }}" method="POST">
                @csrf
                <input type="hidden" name="recharge_id" id="correct_recharge_id">
                <div class="modal-header py-2" style="background:#e67e22;">
                    <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Correct Recharge</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 mb-3" style="font-size:13px;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Admin Correction:</strong> The wrong recharge will be <strong>reversed</strong> (wallet credited back for prepaid resellers), and a new correct recharge will be applied. Client expire date and status update automatically.
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted">Original Recharge</label>
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
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Correction Reason <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Wrong dates, extra days...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Apply correction? Original recharge will be reversed and new one applied.')">
                        <i class="fas fa-check mr-1"></i>Apply Correction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- REFUND MODAL (financial only — client stays active) --}}
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#17a2b8;">
                <h5 class="modal-title text-white"><i class="fas fa-undo-alt mr-2"></i>Refund Recharge</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="refundForm" method="POST">
                @csrf
                <input type="hidden" name="recharge_id" id="refundRechargeId">
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Refund:</strong> The recharge amount will be reversed and (for prepaid resellers) credited back to the reseller wallet. The refunded days will be deducted from the client's expiry date. <strong>The client will remain active.</strong>
                    </div>
                    <table class="table table-sm table-bordered mb-3">
                        <tr><th class="bg-light" style="width:35%">Client</th><td id="rfClientName"></td></tr>
                        <tr><th class="bg-light">Username</th><td id="rfUsername"></td></tr>
                        <tr><th class="bg-light">Package</th><td id="rfPackage"></td></tr>
                        <tr><th class="bg-light">Period</th><td id="rfPeriod"></td></tr>
                        <tr><th class="bg-light">Refund Amount</th><td class="font-weight-bold text-info" id="rfAmount"></td></tr>
                    </table>
                    <div class="form-group mb-0">
                        <label>Remarks <small class="text-muted">(optional)</small></label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Reason for refund...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info btn-sm">
                        <i class="fas fa-undo-alt mr-1"></i>Confirm Refund (Keep Client Active)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DEACTIVATE MODAL (deactivate only — no financial change) --}}
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h5 class="modal-title"><i class="fas fa-ban mr-2"></i>Deactivate Client</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="deactivateForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger py-2 mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Deactivate Only:</strong> The client will be set to <strong>Inactive</strong> and disabled on MikroTik. <strong>No financial refund will be processed.</strong>
                    </div>
                    <table class="table table-sm table-bordered mb-3">
                        <tr><th class="bg-light" style="width:35%">Client</th><td id="daClientName"></td></tr>
                        <tr><th class="bg-light">Username</th><td id="daUsername"></td></tr>
                        <tr><th class="bg-light">Current Status</th><td id="daStatus"></td></tr>
                    </table>
                    <div class="form-group mb-0">
                        <label>Reason <small class="text-muted">(optional)</small></label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Reason for deactivation...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-ban mr-1"></i>Confirm Deactivate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- CURTAIL MODAL --}}
<div class="modal fade" id="curtailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.reseller.curtail-client', $reseller) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning text-dark py-2">
                    <h5 class="modal-title"><i class="fas fa-minus-circle mr-2"></i>Curtail Client (Credit Adjustment)</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Client <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-control" required>
                            <option value="">-- Select Client --</option>
                            @foreach($reseller->clients()->with('package')->orderBy('username')->get() as $rClient)
                            <option value="{{ $rClient->id }}">{{ $rClient->username }} — {{ $rClient->name }} ({{ $rClient->package->name ?? 'N/A' }})</option>
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

<div class="modal fade" id="adminReverseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#8e44ad;">
                <h5 class="modal-title text-white"><i class="fas fa-user-slash mr-2"></i>Reverse Recharge (Client Left)</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="adminReverseForm" method="POST">
                @csrf
                <input type="hidden" name="recharge_id" id="adm_rev_recharge_id">
                <div class="modal-body">
                    <div class="alert py-2 mb-3" style="background:#f3e5f5;border-left:4px solid #8e44ad;font-size:13px;">
                        <i class="fas fa-info-circle mr-1" style="color:#8e44ad;"></i>
                        Unused days-এর amount reseller-এর account-এ ফেরত যাবে।
                        <strong>Prepaid:</strong> Wallet credit | <strong>Postpaid:</strong> Due কমবে।
                    </div>
                    <div class="border rounded p-2 bg-light mb-3" id="adm_rev_info" style="font-size:13px;"></div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Reverse From Date <span class="text-danger">*</span></label>
                            <input type="date" name="reverse_from" id="adm_rev_from" class="form-control form-control-sm" required>
                            <small class="text-muted">এই তারিখ থেকে end পর্যন্ত refund হবে</small>
                        </div>
                        <div class="col-md-6">
                            <label class="small font-weight-bold">Unused Days (Auto)</label>
                            <input type="text" id="adm_rev_unused" class="form-control form-control-sm bg-light" readonly>
                        </div>
                    </div>
                    <div class="mb-2 p-2 rounded" id="adm_rev_refund_box" style="background:#e8f5e9;display:none;">
                        <strong>Estimated Refund:</strong> <span id="adm_rev_amt" class="text-success font-weight-bold" style="font-size:15px;"></span>
                        <small class="text-muted d-block">= Daily Rate × Unused Days</small>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Remarks <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="কারণ লিখুন...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white" style="background:#8e44ad;" onclick="return confirm('Recharge reverse করবেন?')">
                        <i class="fas fa-user-slash mr-1"></i>Confirm Reverse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    // Admin Reverse button handler
    $('.admin-reverse-btn').on('click', function () {
        var rechargeId = $(this).data('recharge-id');
        var clientId   = $(this).data('client-id');
        var client     = $(this).data('client');
        var pkg        = $(this).data('package');
        var days       = $(this).data('days');
        var daily      = parseFloat($(this).data('daily')) || 0;
        var start      = $(this).data('start');
        var end        = $(this).data('end');

        $('#adm_rev_recharge_id').val(rechargeId);
        $('#adm_rev_from').val(new Date().toISOString().split('T')[0]);
        $('#adm_rev_from').attr('data-end', end).attr('data-daily', daily);
        $('#adm_rev_info').html(
            '<strong>Client:</strong> ' + client + '<br>' +
            '<strong>Package:</strong> ' + pkg + '<br>' +
            '<strong>Period:</strong> ' + start + ' → ' + end + ' (' + days + ' days)<br>' +
            '<strong>Daily Rate:</strong> ৳' + daily.toFixed(2)
        );
        var actionUrl = '{{ url("admin/reseller/" . $reseller->id . "/clients") }}/' + clientId + '/reverse-recharge';
        $('#adminReverseForm').attr('action', actionUrl);
        admCalcUnused();
    });

    function admCalcUnused() {
        var from = $('#adm_rev_from').val();
        var end  = $('#adm_rev_from').attr('data-end');
        var daily = parseFloat($('#adm_rev_from').attr('data-daily')) || 0;
        if (!from || !end) return;
        var fd = new Date(from), ed = new Date(end);
        var unused = fd <= ed ? Math.floor((ed - fd) / 86400000) + 1 : 0;
        var refund = (daily * unused).toFixed(2);
        $('#adm_rev_unused').val(unused + ' days');
        $('#adm_rev_refund_box').show();
        $('#adm_rev_amt').text('৳' + parseFloat(refund).toLocaleString('en-BD', {minimumFractionDigits:2}));
    }
    $('#adm_rev_from').on('change', admCalcUnused);

    // Correct button handler
    $('.correct-btn').on('click', function () {
        var id      = $(this).data('id');
        var client  = $(this).data('client');
        var pkg     = $(this).data('package');
        var days    = $(this).data('days');
        var start   = $(this).data('start');

        $('#correct_recharge_id').val(id);
        $('#correct_new_start').val(start);
        $('#correct_new_days').val(days);
        $('#correct_original_info').html(
            '<strong>Client:</strong> ' + client + '<br>' +
            '<strong>Package:</strong> ' + pkg + '<br>' +
            '<strong>Start:</strong> ' + start + ' &nbsp; <strong>Days:</strong> ' + days
        );
    });

    // Refund button handler (financial only)
    $('.refund-btn').on('click', function () {
        var clientId   = $(this).data('client-id');
        var rechargeId = $(this).data('recharge-id');
        var clientName = $(this).data('client-name');
        var username   = $(this).data('username');
        var amount     = $(this).data('amount');
        var pkg        = $(this).data('package');
        var from       = $(this).data('from');
        var to         = $(this).data('to');

        $('#rfClientName').text(clientName);
        $('#rfUsername').text(username);
        $('#rfPackage').text(pkg);
        $('#rfPeriod').text(from + ' → ' + to);
        $('#rfAmount').text('৳' + parseFloat(amount).toFixed(2));
        $('#refundRechargeId').val(rechargeId);

        var actionUrl = '{{ url("admin/reseller/" . $reseller->id . "/clients") }}/' + clientId + '/refund-recharge';
        $('#refundForm').attr('action', actionUrl);

        $('#refundModal').modal('show');
    });

    // Deactivate button handler
    $('.deactivate-btn').on('click', function () {
        var clientId   = $(this).data('client-id');
        var clientName = $(this).data('client-name');
        var username   = $(this).data('username');
        var status     = $(this).data('status');

        $('#daClientName').text(clientName);
        $('#daUsername').text(username);
        $('#daStatus').html('<span class="badge badge-' + (status === 'active' ? 'success' : 'secondary') + '">' + status.toUpperCase() + '</span>');

        var actionUrl = '{{ url("admin/reseller/" . $reseller->id . "/clients") }}/' + clientId + '/deactivate';
        $('#deactivateForm').attr('action', actionUrl);

        $('#deactivateModal').modal('show');
    });
});
</script>
@endpush
@endsection
