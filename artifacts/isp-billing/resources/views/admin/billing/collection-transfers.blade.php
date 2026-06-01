@extends('layouts.admin')

@section('title', 'Collection Transfers')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.index') }}">Billing</a></li>
<li class="breadcrumb-item active">Collection Transfers</li>
@endsection

@section('content')
<style>
.transfer-card { border-left: 4px solid #17a2b8; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.transfer-card.status-pending { border-left-color: #ffc107; }
.transfer-card.status-completed { border-left-color: #28a745; }
.transfer-card .card-body { padding: 12px 15px; }
.transfer-card .transfer-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.transfer-card .transfer-amount { font-weight: bold; font-size: 18px; color: #28a745; }
.transfer-card .transfer-info { font-size: 13px; color: #666; margin-bottom: 8px; }
.transfer-card .btn-group-mobile { display: flex; flex-wrap: wrap; gap: 6px; }
.transfer-card .btn-group-mobile .btn { flex: 1; min-width: 80px; font-size: 13px; padding: 6px 10px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
</style>

<div class="row mb-3">
    <div class="col-md-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $pendingCount }}</h3>
                <p>Pending Transfers</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($totalTransferred ?? 0) }}</h3>
                <p>Total Transferred</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
</div>

<div class="row mb-3">
    @if($__canAction('billing', 'collection_transfer'))
    <div class="col-md-4">
        <div class="card card-info card-outline">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> New Collection Transfer</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.billing.collection-transfers.store') }}" method="POST">
                    @csrf

                    {{-- FROM: Who is transferring --}}
                    <div class="form-group">
                        <label><i class="fas fa-user-circle mr-1 text-warning"></i> From Staff <span class="text-danger">*</span></label>
                        <select name="from_user_id" id="fromUserId" class="form-control" required>
                            <option value="">-- Select Staff Member --</option>
                            @foreach($allStaffUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ ucfirst($user->user_type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- FROM: Balance Info --}}
                    <div id="fromBalanceBox" class="mb-3" style="display:none;">
                        <div class="card card-body py-2 px-3 bg-light border" style="font-size:13px;">
                            <div id="fromBalanceLoading" class="text-center text-muted" style="display:none;">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Loading...
                            </div>
                            <div id="fromBalanceContent">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mr-3">
                                        <span class="text-muted d-block" style="font-size:11px;">COLLECTED</span>
                                        <strong id="fbPending" class="text-success" style="font-size:14px;">—</strong>
                                    </div>
                                    <div class="mr-3">
                                        <span class="text-muted d-block" style="font-size:11px;">IN TRANSIT</span>
                                        <strong id="fbTransit" class="text-warning" style="font-size:14px;">—</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted d-block" style="font-size:11px;">AVAILABLE</span>
                                        <strong id="fbAvailable" style="font-size:15px;color:#e74c3c;">—</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-2">

                    {{-- TO: Transfer destination type --}}
                    <div class="form-group">
                        <label><i class="fas fa-arrow-right mr-1 text-info"></i> Transfer To</label>
                        <select name="transfer_type" id="transferType" class="form-control" required>
                            <option value="to_account">Account (Cash/Bank/Mobile)</option>
                            <option value="to_user">Staff Member</option>
                        </select>
                    </div>
                    <div class="form-group" id="accountField">
                        <label>To Account</label>
                        <select name="to_account_id" id="toAccountId" class="form-control">
                            <option value="">-- Select Account --</option>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} ({{ ucfirst($account->type) }} - BDT {{ number_format($account->current_balance ?? 0) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="userField" style="display: none;">
                        <label>To Staff</label>
                        <select name="to_user_id" id="toUserId" class="form-control">
                            <option value="">-- Select Staff --</option>
                            @foreach($staffUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ ucfirst($user->user_type) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (BDT)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Transfer Date</label>
                        <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-info btn-block">
                        <i class="fas fa-paper-plane mr-1"></i> Submit Transfer
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="{{ $__canAction('billing', 'collection_transfer') ? 'col-md-8' : 'col-md-12' }}">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Collection Transfers</h3>
                <div class="card-tools">
                    <form class="form-inline" method="GET">
                        <select name="status" class="form-control form-control-sm mr-1">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        <input type="date" name="date_from" class="form-control form-control-sm mr-1" value="{{ request('date_from') }}" placeholder="From">
                        <input type="date" name="date_to" class="form-control form-control-sm mr-1" value="{{ request('date_to') }}" placeholder="To">
                        <button class="btn btn-sm btn-default"><i class="fas fa-filter"></i></button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="mobile-cards">
                    @forelse($transfers as $transfer)
                    <div class="card transfer-card status-{{ $transfer->status }}">
                        <div class="card-body">
                            <div class="transfer-header">
                                <div>
                                    <strong>{{ $transfer->transfer_no }}</strong>
                                    <span class="badge badge-{{ $transfer->status == 'completed' ? 'success' : 'warning' }} ml-2">
                                        {{ ucfirst($transfer->status) }}
                                    </span>
                                </div>
                                <span class="transfer-amount">৳{{ number_format($transfer->amount ?? 0) }}</span>
                            </div>
                            <div class="transfer-info">
                                <i class="fas fa-user mr-1"></i> {{ $transfer->fromUser->name ?? 'N/A' }}
                                <i class="fas fa-arrow-right mx-2"></i>
                                @if($transfer->to_account_id)
                                <i class="fas fa-university mr-1"></i> {{ $transfer->toAccount->name ?? 'N/A' }}
                                @elseif($transfer->to_user_id)
                                <i class="fas fa-user-tie mr-1"></i> {{ $transfer->toUser->name ?? 'N/A' }}
                                @else
                                <span class="text-muted">N/A</span>
                                @endif
                            </div>
                            <div class="transfer-info">
                                <i class="fas fa-calendar mr-1"></i> {{ $transfer->transfer_date?->format('d-m-Y') ?? $transfer->created_at->format('d-m-Y') }}
                                @if($transfer->remarks)
                                <br><small class="text-muted"><i class="fas fa-comment mr-1"></i>{{ $transfer->remarks }}</small>
                                @endif
                            </div>
                            @if($transfer->status == 'pending')
                            @if($__canAction('billing', 'collection_transfer'))
                            <div class="btn-group-mobile">
                                <form action="{{ route('admin.billing.collection-transfers.verify', $transfer) }}" method="POST" class="flex-fill">
                                    @csrf
                                    <input type="hidden" name="action" value="verify">
                                    <button type="submit" class="btn btn-success btn-sm btn-block" onclick="return confirm('Verify this transfer?')">
                                        <i class="fas fa-check mr-1"></i> Verify
                                    </button>
                                </form>
                                <form action="{{ route('admin.billing.collection-transfers.verify', $transfer) }}" method="POST" class="flex-fill">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger btn-sm btn-block" onclick="return confirm('Reject this transfer?')">
                                        <i class="fas fa-times mr-1"></i> Reject
                                    </button>
                                </form>
                            </div>
                            @endif
                            @else
                            <small class="text-muted">
                                <i class="fas fa-check-circle text-success mr-1"></i>Verified by {{ $transfer->verifier->name ?? 'Admin' }}
                            </small>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">No collection transfers found</div>
                    @endforelse
                </div>

                <div class="desktop-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Transfer No</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                <tr>
                                    <td><code>{{ $transfer->transfer_no }}</code></td>
                                    <td>{{ $transfer->fromUser->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($transfer->to_account_id)
                                        <i class="fas fa-university text-info mr-1"></i>{{ $transfer->toAccount->name ?? 'N/A' }}
                                        @elseif($transfer->to_user_id)
                                        <i class="fas fa-user-tie text-primary mr-1"></i>{{ $transfer->toUser->name ?? 'N/A' }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-success font-weight-bold">৳{{ number_format($transfer->amount ?? 0, 2) }}</td>
                                    <td>{{ $transfer->transfer_date?->format('d-m-Y') ?? $transfer->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $transfer->status == 'completed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($transfer->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($transfer->status == 'pending')
                                        @if($__canAction('billing', 'collection_transfer'))
                                        <form action="{{ route('admin.billing.collection-transfers.verify', $transfer) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="action" value="verify">
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Verify this transfer?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.billing.collection-transfers.verify', $transfer) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this transfer?')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        @endif
                                        @else
                                        <small class="text-muted">
                                            <i class="fas fa-check-circle text-success"></i> {{ $transfer->verifier->name ?? 'Admin' }}
                                        </small>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No collection transfers found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $transfers->appends(request()->query())->links() }}
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
</script></div>
        </div>
    </div>
</div>

<script>
function fmtBDT(n) {
    return 'BDT ' + Number(n).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// FROM Staff change → fetch balance
document.getElementById('fromUserId')?.addEventListener('change', function() {
    var userId = this.value;
    var box = document.getElementById('fromBalanceBox');
    if (!userId) { box.style.display = 'none'; return; }

    box.style.display = 'block';
    document.getElementById('fromBalanceLoading').style.display = '';
    document.getElementById('fromBalanceContent').style.opacity = '0.4';

    fetch('/admin/billing/api/staff-balance/' + userId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('fromBalanceLoading').style.display = 'none';
        document.getElementById('fromBalanceContent').style.opacity = '1';
        document.getElementById('fbPending').textContent  = fmtBDT(data.pending);
        document.getElementById('fbTransit').textContent  = fmtBDT(data.in_transit);
        document.getElementById('fbAvailable').textContent = fmtBDT(data.available);
    })
    .catch(() => {
        document.getElementById('fromBalanceLoading').style.display = 'none';
        document.getElementById('fromBalanceContent').style.opacity = '1';
        document.getElementById('fbPending').textContent  = 'Error';
        document.getElementById('fbTransit').textContent  = '—';
        document.getElementById('fbAvailable').textContent = '—';
    });
});

// Transfer To type toggle
document.getElementById('transferType')?.addEventListener('change', function() {
    var isAccount = this.value === 'to_account';
    document.getElementById('accountField').style.display = isAccount ? '' : 'none';
    document.getElementById('userField').style.display    = isAccount ? 'none' : '';
    document.getElementById('toAccountId').required = isAccount;
    document.getElementById('toUserId').required    = !isAccount;
    if (isAccount) document.getElementById('toUserId').value = '';
    else           document.getElementById('toAccountId').value = '';
});
</script>
@endsection
