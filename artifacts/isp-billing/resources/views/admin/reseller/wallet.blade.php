@extends('layouts.admin')

@section('title', 'Wallet — ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('admin.reseller.index', ['type' => $reseller->type ?? 'mac']) }}">
        {{ ($reseller->type ?? 'mac') === 'bandwidth' ? 'Bandwidth Resellers' : 'MAC Resellers' }}
    </a>
</li>
<li class="breadcrumb-item">
    <a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a>
</li>
<li class="breadcrumb-item active">Wallet</li>
@endsection

@section('content')
<div class="row">
    {{-- ── Left: Balance Card + Credit/Debit Forms ── --}}
    <div class="col-md-4">

        {{-- Balance Card --}}
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-wallet mr-2"></i>{{ $reseller->name }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.reseller.show', $reseller) }}" class="btn btn-tool" title="Back to Profile">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>
            <div class="card-body text-center py-4">
                <p class="text-muted mb-1" style="font-size:13px;">Current Wallet Balance</p>
                <h2 class="{{ $reseller->wallet_balance >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold mb-1">
                    ৳{{ number_format($reseller->wallet_balance, 2) }}
                </h2>
                <div class="d-flex justify-content-center" style="gap:20px;margin-top:10px;">
                    <div class="text-center">
                        <small class="text-muted d-block">Credit Limit</small>
                        <strong>৳{{ number_format($reseller->credit_limit, 2) }}</strong>
                    </div>
                    <div class="text-center">
                        <small class="text-muted d-block">Available Credit</small>
                        <strong class="{{ ($reseller->credit_limit + $reseller->wallet_balance) > 0 ? 'text-success' : 'text-danger' }}">
                            ৳{{ number_format($reseller->credit_limit + $reseller->wallet_balance, 2) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Credit Form --}}
        <div class="card card-success card-outline">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i>Credit Wallet</h3>
            </div>
            <form action="{{ route('admin.reseller.wallet.credit', $reseller) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="1" required placeholder="Enter amount">
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Description <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control form-control-sm" required placeholder="e.g., Advance payment">
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Reference <small class="text-muted">(optional)</small></label>
                        <input type="text" name="reference" class="form-control form-control-sm" placeholder="Bank TrxID, bKash ref…">
                    </div>
                </div>
                <div class="card-footer py-2">
                    <button type="submit" class="btn btn-success btn-sm btn-block">
                        <i class="fas fa-plus mr-1"></i>Credit Wallet
                    </button>
                </div>
            </form>
        </div>

        {{-- Debit Form --}}
        <div class="card card-danger card-outline">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-minus-circle mr-1"></i>Debit Wallet</h3>
            </div>
            <form action="{{ route('admin.reseller.wallet.debit', $reseller) }}" method="POST"
                  onsubmit="return confirm('Debit ৳ from wallet of {{ addslashes($reseller->name) }}?');">
                @csrf
                <div class="card-body">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="1" required placeholder="Enter amount">
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Description <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control form-control-sm" required placeholder="e.g., Monthly bill">
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Reference <small class="text-muted">(optional)</small></label>
                        <input type="text" name="reference" class="form-control form-control-sm" placeholder="Invoice number…">
                    </div>
                </div>
                <div class="card-footer py-2">
                    <button type="submit" class="btn btn-danger btn-sm btn-block">
                        <i class="fas fa-minus mr-1"></i>Debit Wallet
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- ── Right: Transaction History ── --}}
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>Transaction History
                </h3>
                <div class="card-tools">
                    <form method="GET" class="d-flex align-items-center" style="gap:6px;">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" style="width:130px;">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" style="width:130px;">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
                        @if(request('date_from') || request('date_to'))
                        <a href="{{ route('admin.reseller.wallet', $reseller) }}" class="btn btn-default btn-sm"><i class="fas fa-times"></i></a>
                        @endif
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width:130px;">Date</th>
                                <th style="width:70px;">Type</th>
                                <th>Description</th>
                                <th style="width:100px;">Reference</th>
                                <th class="text-right" style="width:110px;">Amount</th>
                                <th class="text-right" style="width:110px;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                            <tr>
                                <td class="small">{{ $transaction->created_at->format('d-m-y H:i') }}</td>
                                <td>
                                    <span class="badge badge-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}" style="font-size:10px;">
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                </td>
                                <td class="small">{{ $transaction->description }}</td>
                                <td class="small text-muted">{{ $transaction->reference ?? '-' }}</td>
                                <td class="text-right font-weight-bold {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}" style="font-size:13px;">
                                    {{ $transaction->type === 'credit' ? '+' : '-' }}৳{{ number_format($transaction->amount, 2) }}
                                </td>
                                <td class="text-right small">৳{{ number_format($transaction->balance_after, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No transactions found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($transactions->count() > 0)
                        @php
                            $totalCredit = $transactions->where('type','credit')->sum('amount');
                            $totalDebit  = $transactions->where('type','debit')->sum('amount');
                        @endphp
                        <tfoot class="bg-light" style="font-size:12px;">
                            <tr>
                                <td colspan="4" class="text-right font-weight-bold">Page Totals:</td>
                                <td class="text-right">
                                    <span class="text-success font-weight-bold">+৳{{ number_format($totalCredit,2) }}</span><br>
                                    <span class="text-danger font-weight-bold">-৳{{ number_format($totalDebit,2) }}</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
            @if($transactions->hasPages())
            <div class="card-footer clearfix py-2">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <label class="small text-muted mb-0 mr-2">SHOW</label>
                        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
                            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
                            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
                            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
                            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
                        </select>
                        <label class="small text-muted mb-0 ml-2">ENTRIES</label>
                    </div>
                    <div>{{ $transactions->appends(request()->query())->links() }}</div>
                </div>
            </div>
            @endif
        </div>

        <div class="mb-3">
            <a href="{{ route('admin.reseller.show', $reseller) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to Profile
            </a>
            <a href="{{ route('admin.reseller.bill-payment', $reseller) }}" class="btn btn-success btn-sm">
                <i class="fas fa-money-bill-wave mr-1"></i>Collect Payment
            </a>
            <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-info btn-sm">
                <i class="fas fa-history mr-1"></i>Bill History
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
@endpush
