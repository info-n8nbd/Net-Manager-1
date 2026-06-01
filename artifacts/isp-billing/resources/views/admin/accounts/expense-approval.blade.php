@extends('layouts.admin')

@section('title', 'Expense Approval')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.expense') }}">Accounts</a></li>
<li class="breadcrumb-item active">Expense Approval</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
            <div class="stat-number">{{ $pendingCount ?? 0 }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $approvedToday ?? 0 }}</div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-danger text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $rejectedToday ?? 0 }}</div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($pendingAmount ?? 0, 0) }}</div>
            <div class="stat-label">Amount</div>
        </div>
    </div>
</div>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Pending Expenses</h3>
        <div class="card-tools">
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary active" data-filter="pending">Pending</button>
                <button type="button" class="btn btn-sm btn-outline-success" data-filter="approved">Approved</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-filter="rejected">Rejected</button>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses ?? [] as $expense)
                <tr>
                    <td>{{ $expense->expense_date->format('d-m-Y') }}</td>
                    <td>{{ $expense->employee->name ?? 'N/A' }}</td>
                    <td><span class="badge badge-info">{{ $expense->category ?? 'General' }}</span></td>
                    <td>{{ Str::limit($expense->description, 40) }}</td>
                    <td class="text-right"><strong>৳{{ number_format($expense->amount ?? 0, 2) }}</strong></td>
                    <td>
                        @if($expense->status === 'pending')
                        <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                        @elseif($expense->status === 'approved')
                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Approved</span>
                        @else
                        <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Rejected</span>
                        @endif
                    </td>
                    <td>
                        @if($expense->status === 'pending')
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-success" title="Approve" onclick="approveExpense({{ $expense->id }})">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-danger" title="Reject" onclick="rejectExpense({{ $expense->id }})">
                                <i class="fas fa-times"></i>
                            </button>
                            <button type="button" class="btn btn-info" title="View Details" data-toggle="modal" data-target="#viewModal{{ $expense->id }}">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @else
                        <button type="button" class="btn btn-sm btn-info" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">No expenses found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($expenses) && $expenses->hasPages())
    <div class="card-footer">
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
    @endif
</div>
@endsection

@push('scripts')
<script>
function approveExpense(id) {
    if (confirm('Are you sure you want to approve this expense?')) {
        fetch('/admin/accounts/expense/' + id + '/approve', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function rejectExpense(id) {
    var reason = prompt('Please enter rejection reason:');
    if (reason) {
        fetch('/admin/accounts/expense/' + id + '/reject', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({reason: reason})
        }).then(r => r.json()).then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>
@endpush
