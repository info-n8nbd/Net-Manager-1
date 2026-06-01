@extends('layouts.admin')

@section('title', 'Expense')
@section('breadcrumb')
<li class="breadcrumb-item active">Expense</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Expense Records</h3></div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Date</th><th>Head</th><th>Account</th><th>Amount (৳)</th><th>Description</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date->format('d-m-Y') }}</td>
                            <td>{{ $expense->expenseHead->name ?? 'N/A' }}</td>
                            <td>{{ $expense->account->name ?? 'N/A' }}</td>
                            <td>{{ number_format($expense->amount ?? 0, 2) }}</td>
                            <td>{{ $expense->description ?? '-' }}</td>
                            <td>
                                @if($__canAction('accounts', 'edit_transaction'))
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editExpenseModal{{ $expense->id }}"><i class="fas fa-edit"></i></button>
                                @endif
                                @if($__canAction('accounts', 'delete_transaction'))
                                <form action="{{ route('admin.accounts.expense.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense record?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No expense records</td></tr>
                        @endforelse
                    </tbody>
                </table>
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
        {{ $expenses->appends(request()->query())->links() }}
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
        </div>
    </div>
    @if($__canAction('accounts', 'add_expense'))
    <div class="col-md-4">
        <div class="card card-danger">
            <div class="card-header"><h3 class="card-title">Add Expense</h3></div>
            <form action="{{ route('admin.accounts.expense.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Expense Head</label>
                        <select name="expense_head_id" class="form-control" required>
                            @foreach($expenseHeads as $head)
                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Account</label>
                        <select name="account_id" class="form-control" required>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Amount (৳)</label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                    <div class="form-group"><label>Date</label><input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-danger">Add Expense</button></div>
            </form>
        </div>
    </div>
    @endif
</div>

@foreach($expenses as $expense)
<div class="modal fade" id="editExpenseModal{{ $expense->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.accounts.expense.update', $expense) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white">Edit Expense</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Expense Head</label>
                        <select name="expense_head_id" class="form-control" required>
                            @foreach($expenseHeads as $head)
                            <option value="{{ $head->id }}" {{ $expense->expense_head_id == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account</label>
                        <select name="account_id" class="form-control" required>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ $expense->account_id == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Amount (৳)</label><input type="number" name="amount" class="form-control" step="0.01" value="{{ $expense->amount }}" required></div>
                    <div class="form-group"><label>Date</label><input type="date" name="expense_date" class="form-control" value="{{ $expense->expense_date->format('Y-m-d') }}" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2">{{ $expense->description }}</textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
