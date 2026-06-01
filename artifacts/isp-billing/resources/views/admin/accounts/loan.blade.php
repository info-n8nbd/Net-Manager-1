@extends('layouts.admin')

@section('title', 'Loan Management')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.cash') }}">Accounts</a></li>
<li class="breadcrumb-item active">Loan Management</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h4>৳{{ number_format($summary['totalReceived'], 2) }}</h4>
                <p>Total Loan Received</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-down"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h4>৳{{ number_format($summary['totalGiven'], 2) }}</h4>
                <p>Total Loan Given</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h4>৳{{ number_format($summary['outstandingGiven'] + $summary['outstandingReceived'], 2) }}</h4>
                <p>Total Outstanding</p>
            </div>
            <div class="icon"><i class="fas fa-balance-scale"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h4>{{ $summary['overdueCount'] }}</h4>
                <p>Overdue Loans</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-2"></i>New Loan Entry</h3>
            </div>
            <form action="{{ route('admin.accounts.loan.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Loan Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="received">Loan Received (We Borrowed)</option>
                            <option value="given">Loan Given (We Lent)</option>
                        </select>
                    </div>
                    @if($employees->count())
                    <div class="form-group" id="employee-select-group">
                        <label>Select Employee <small class="text-muted">(Optional — auto-fills name & phone)</small></label>
                        <select id="employee_select" class="form-control">
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" data-name="{{ $emp->name }}" data-phone="{{ $emp->phone }}">
                                {{ $emp->name }}{{ $emp->phone ? ' — '.$emp->phone : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="form-group">
                        <label>Party Name <span class="text-danger">*</span></label>
                        <input type="text" id="party_name_input" name="party_name" class="form-control" placeholder="Lender / Borrower name" required>
                    </div>
                    <div class="form-group">
                        <label>Party Phone</label>
                        <input type="text" id="party_phone_input" name="party_phone" class="form-control" placeholder="01XXXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Account <span class="text-danger">*</span></label>
                        <select name="account_id" class="form-control" required>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Loan Date <span class="text-danger">*</span></label>
                        <input type="date" name="loan_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Save Loan</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Loan Records</h3>
                <div class="card-tools">
                    <form action="{{ route('admin.accounts.loan') }}" method="GET" class="form-inline">
                        <select name="type" class="form-control form-control-sm mr-1">
                            <option value="">All Types</option>
                            <option value="received" {{ request('type') === 'received' ? 'selected' : '' }}>Received</option>
                            <option value="given" {{ request('type') === 'given' ? 'selected' : '' }}>Given</option>
                        </select>
                        <select name="status" class="form-control form-control-sm mr-1">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        </select>
                        <div class="input-group input-group-sm mr-1">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                        </div>
                        <button type="submit" class="btn btn-sm btn-default mr-1"><i class="fas fa-filter"></i></button>
                        @if(request()->hasAny(['type', 'status', 'search']))
                        <a href="{{ route('admin.accounts.loan') }}" class="btn btn-sm btn-outline-secondary" title="Clear"><i class="fas fa-times"></i></a>
                        @endif
                    </form>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Ref#</th>
                            <th>Type</th>
                            <th>Party</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Remaining</th>
                            <th>Status</th>
                            <th>Loan Date</th>
                            <th>Due Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loans as $loan)
                        @php
                            $isOverdue = $loan->due_date && $loan->due_date->isPast() && $loan->status !== 'completed';
                        @endphp
                        <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                            <td><a href="{{ route('admin.accounts.loan.show', $loan) }}"><code>{{ $loan->reference_no }}</code></a></td>
                            <td>
                                <span class="badge badge-{{ $loan->type === 'received' ? 'success' : 'danger' }}">
                                    {{ $loan->type === 'received' ? '↓ Received' : '↑ Given' }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $loan->party_name }}</strong>
                                @if($loan->party_phone)
                                <br><small class="text-muted"><i class="fas fa-phone fa-xs"></i> {{ $loan->party_phone }}</small>
                                @endif
                            </td>
                            <td class="text-right">৳{{ number_format($loan->amount, 2) }}</td>
                            <td class="text-right text-success">৳{{ number_format($loan->paid_amount, 2) }}</td>
                            <td class="text-right text-danger font-weight-bold">৳{{ number_format($loan->remaining_amount, 2) }}</td>
                            <td>
                                @if($isOverdue)
                                <span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Overdue</span>
                                @elseif($loan->status === 'completed')
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Completed</span>
                                @elseif($loan->status === 'partial')
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Partial</span>
                                @else
                                <span class="badge badge-info"><i class="fas fa-hourglass-start"></i> Pending</span>
                                @endif
                            </td>
                            <td>{{ $loan->loan_date->format('d-M-Y') }}</td>
                            <td>
                                @if($loan->due_date)
                                <span class="{{ $isOverdue ? 'text-danger font-weight-bold' : '' }}">{{ $loan->due_date->format('d-M-Y') }}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.accounts.loan.show', $loan) }}" class="btn btn-info" title="View Details"><i class="fas fa-eye"></i></a>
                                    @if($loan->status !== 'completed')
                                    <button type="button" class="btn btn-success" title="Add Payment" data-toggle="modal" data-target="#payModal{{ $loan->id }}"><i class="fas fa-money-bill-wave"></i></button>
                                    @endif
                                    <a href="{{ route('admin.accounts.loan.edit', $loan) }}" class="btn btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="{{ route('admin.accounts.loan.statement', $loan) }}" class="btn btn-secondary" title="Statement"><i class="fas fa-file-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fas fa-hand-holding-usd fa-3x mb-3 d-block text-gray"></i>
                                No loans found. Create your first loan entry.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($loans->hasPages())
            <div class="card-footer clearfix"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $loans->appends(request()->query())->links() }}
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var empSelect = document.getElementById('employee_select');
    if (!empSelect) return;

    empSelect.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        var nameField  = document.getElementById('party_name_input');
        var phoneField = document.getElementById('party_phone_input');

        if (opt.value) {
            nameField.value  = opt.getAttribute('data-name')  || '';
            phoneField.value = opt.getAttribute('data-phone') || '';
        } else {
            nameField.value  = '';
            phoneField.value = '';
        }
    });
});
</script>
@endpush

@foreach($loans as $loan)
@if($loan->status !== 'completed')
<div class="modal fade" id="payModal{{ $loan->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.accounts.loan.payment', $loan) }}" method="POST">
                @csrf
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white"><i class="fas fa-money-bill-wave mr-2"></i>Add Payment — {{ $loan->reference_no }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="callout callout-info py-2">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted">Total</small><br>
                                <strong>৳{{ number_format($loan->amount, 2) }}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Paid</small><br>
                                <strong class="text-success">৳{{ number_format($loan->paid_amount, 2) }}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Remaining</small><br>
                                <strong class="text-danger">৳{{ number_format($loan->remaining_amount, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Payment Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $loan->remaining_amount }}" required>
                        <small class="text-muted">Maximum: ৳{{ number_format($loan->remaining_amount, 2) }}</small>
                    </div>
                    <div class="form-group">
                        <label>Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Account <span class="text-danger">*</span></label>
                        <select name="account_id" class="form-control" required>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional note...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i>Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
