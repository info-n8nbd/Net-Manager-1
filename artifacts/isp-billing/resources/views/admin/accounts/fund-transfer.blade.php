@extends('layouts.admin')

@section('title', 'Fund Transfer')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.cash') }}">Accounts</a></li>
<li class="breadcrumb-item active">Fund Transfer</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>New Transfer</h3>
            </div>
            <form action="{{ route('admin.accounts.fund-transfer.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>From Account <span class="text-danger">*</span></label>
                        <select name="from_account_id" class="form-control" required>
                            <option value="">Select Account</option>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} (৳{{ number_format($account->current_balance ?? 0, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>To Account <span class="text-danger">*</span></label>
                        <select name="to_account_id" class="form-control" required>
                            <option value="">Select Account</option>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Transfer Date <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Transfer Now</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Transfer History</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th class="text-right">Amount</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->transfer_date->format('d-m-Y') }}</td>
                            <td>{{ $transfer->fromAccount->name ?? 'N/A' }}</td>
                            <td>{{ $transfer->toAccount->name ?? 'N/A' }}</td>
                            <td class="text-right"><strong>৳{{ number_format($transfer->amount ?? 0, 2) }}</strong></td>
                            <td>{{ $transfer->createdBy->name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No transfers found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transfers->hasPages())
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
            @endif
        </div>
    </div>
</div>
@endsection
