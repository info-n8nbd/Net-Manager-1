@extends('layouts.admin')

@section('title', 'Bill List')
@section('breadcrumb')
<li class="breadcrumb-item active">Billing</li>
@endsection

@section('content')
<style>
.bill-card { border-left: 4px solid #17a2b8; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.bill-card.status-paid { border-left-color: #28a745; }
.bill-card.status-partial { border-left-color: #ffc107; }
.bill-card.status-unpaid { border-left-color: #dc3545; }
.bill-card .card-body { padding: 12px 15px; }
.bill-card .bill-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.bill-card .client-name { font-weight: 600; font-size: 15px; color: #333; margin: 0; }
.bill-card .bill-info { font-size: 13px; color: #666; margin-bottom: 5px; }
.bill-card .bill-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; font-size: 12px; }
.bill-card .btn-group-mobile { display: flex; flex-wrap: wrap; gap: 6px; }
.bill-card .btn-group-mobile .btn { flex: 1; min-width: 60px; font-size: 13px; padding: 6px 10px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
.bulk-toolbar { display: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 10px 16px; border-radius: 6px; margin-bottom: 12px; align-items: center; gap: 12px; flex-wrap: wrap; animation: slideDown 0.2s ease; }
.bulk-toolbar.active { display: flex; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
.bulk-toolbar .selected-count { font-weight: 600; font-size: 14px; white-space: nowrap; }
.bulk-toolbar .btn { font-size: 13px; padding: 5px 14px; border: none; }
.bulk-toolbar .btn-light { background: rgba(255,255,255,0.95); color: #333; }
.bulk-toolbar .btn-light:hover { background: #fff; }
.bill-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #667eea; }
</style>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h3 class="card-title mb-2 mb-md-0">All Bills</h3>
        @if($__canAction('billing', 'generate_bills'))
        <a href="{{ route('admin.billing.generate') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Generate Bills</a>
        @endif
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.billing.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-4 col-12 mb-2">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search PPPoE Username, Client ID or Name..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                            @if(request('search'))
                            <a href="{{ route('admin.billing.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-4 mb-2">
                    <select name="month" class="form-control" onchange="this.form.submit()">
                        <option value="">All Months</option>
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2 col-4 mb-2">
                    <select name="year" class="form-control" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2 col-4 mb-2">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="bulk-toolbar" id="bulkToolbar">
            <span class="selected-count"><i class="fas fa-check-circle mr-1"></i> <span id="selectedCount">0</span> selected</span>
            <button type="button" class="btn btn-light btn-sm" onclick="bulkMarkPaid()"><i class="fas fa-check-double text-success mr-1"></i> Mark Paid</button>
            <button type="button" class="btn btn-light btn-sm" onclick="bulkMarkUnpaid()"><i class="fas fa-undo text-warning mr-1"></i> Mark Unpaid</button>
            <button type="button" class="btn btn-light btn-sm" onclick="bulkDelete()"><i class="fas fa-trash text-danger mr-1"></i> Delete</button>
            <button type="button" class="btn btn-light btn-sm" onclick="clearSelection()"><i class="fas fa-times mr-1"></i> Clear</button>
        </div>

        <form id="bulkActionForm" method="POST" action="{{ route('admin.billing.bulk-action') }}" style="display:none;">
            @csrf
            <input type="hidden" name="action" id="bulkActionType">
        </form>

        <div class="mobile-cards">
            @forelse($bills as $bill)
            <div class="card bill-card status-{{ $bill->status }}">
                <div class="card-body">
                    <div class="bill-header">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" class="bill-checkbox mr-2" value="{{ $bill->id }}" onchange="updateBulkToolbar()">
                            <h6 class="client-name">{{ $bill->client->name ?? 'N/A' }}</h6>
                        </div>
                        <span class="badge badge-{{ $bill->status == 'paid' ? 'success' : ($bill->status == 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($bill->status) }}</span>
                    </div>
                    <div class="bill-info">
                        <strong>#{{ $bill->bill_no }}</strong> | {{ $bill->billing_month }}/{{ $bill->billing_year }}
                    </div>
                    <div class="bill-meta">
                        <span>Total: <strong>৳{{ number_format($bill->total_amount ?? 0) }}</strong></span>
                        <span class="text-success">Paid: ৳{{ number_format($bill->paid_amount ?? 0) }}</span>
                        <span class="text-danger">Due: ৳{{ number_format($bill->due_amount ?? 0) }}</span>
                    </div>
                    <div class="btn-group-mobile">
                        <a href="{{ route('admin.billing.show', $bill) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                        @if($__canAction('billing', 'edit_bill'))
                        <a href="{{ route('admin.billing.edit', $bill) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @endif
                        @if($__canAction('billing', 'delete_bill'))
                        <form action="{{ route('admin.billing.destroy', $bill) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this bill?');" style="display:inline; flex: 1;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                        @endif
                        @if($__canAction('billing', 'print_receipt'))
                        <a href="{{ route('admin.billing.invoice', $bill) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-download"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No bills found</div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width:35px"><input type="checkbox" class="bill-checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                            <th>Bill No</th><th>Client</th><th>Month</th><th>Total (৳)</th><th>Paid (৳)</th><th>Due (৳)</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bills as $bill)
                        <tr>
                            <td><input type="checkbox" class="bill-checkbox row-checkbox" value="{{ $bill->id }}" onchange="updateBulkToolbar()"></td>
                            <td>{{ $bill->bill_no }}</td>
                            <td>{{ $bill->client->name ?? 'N/A' }}</td>
                            <td>{{ $bill->billing_month }}/{{ $bill->billing_year }}</td>
                            <td>{{ number_format($bill->total_amount ?? 0, 2) }}</td>
                            <td>{{ number_format($bill->paid_amount ?? 0, 2) }}</td>
                            <td>{{ number_format($bill->due_amount ?? 0, 2) }}</td>
                            <td><span class="badge badge-{{ $bill->status == 'paid' ? 'success' : ($bill->status == 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($bill->status) }}</span></td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.billing.show', $bill) }}" class="btn btn-info btn-xs" title="View"><i class="fas fa-eye"></i></a>
                                    @if($__canAction('billing', 'edit_bill'))
                                    <a href="{{ route('admin.billing.edit', $bill) }}" class="btn btn-warning btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                                    @endif
                                    @if($__canAction('billing', 'delete_bill'))
                                    <form action="{{ route('admin.billing.destroy', $bill) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this bill?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endif
                                    @if($__canAction('billing', 'print_receipt'))
                                    <a href="{{ route('admin.billing.invoice', $bill) }}" class="btn btn-secondary btn-xs" title="Invoice"><i class="fas fa-download"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center">No bills found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
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
        {{ $bills->appends(request()->query())->links() }}
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
@endsection

@push('scripts')
<script>
function getSelectedIds() {
    var ids = [];
    document.querySelectorAll('.bill-checkbox:not(#selectAll)').forEach(function(cb) {
        if (cb.checked) ids.push(cb.value);
    });
    return ids;
}

function updateBulkToolbar() {
    var ids = getSelectedIds();
    var toolbar = document.getElementById('bulkToolbar');
    document.getElementById('selectedCount').textContent = ids.length;
    if (ids.length > 0) {
        toolbar.classList.add('active');
    } else {
        toolbar.classList.remove('active');
    }
    var selectAll = document.getElementById('selectAll');
    var rowCheckboxes = document.querySelectorAll('.row-checkbox');
    if (selectAll && rowCheckboxes.length > 0) {
        var allChecked = true;
        rowCheckboxes.forEach(function(cb) { if (!cb.checked) allChecked = false; });
        selectAll.checked = allChecked;
    }
}

function toggleSelectAll(el) {
    document.querySelectorAll('.bill-checkbox:not(#selectAll)').forEach(function(cb) {
        cb.checked = el.checked;
    });
    updateBulkToolbar();
}

function clearSelection() {
    document.querySelectorAll('.bill-checkbox').forEach(function(cb) { cb.checked = false; });
    updateBulkToolbar();
}

function submitBulkAction(action, confirmMsg) {
    var ids = getSelectedIds();
    if (ids.length === 0) return;
    if (!confirm(confirmMsg)) return;

    var form = document.getElementById('bulkActionForm');
    document.getElementById('bulkActionType').value = action;

    form.querySelectorAll('input[name="bill_ids[]"]').forEach(function(el) { el.remove(); });
    ids.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'bill_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    form.submit();
}

function bulkMarkPaid() {
    var count = getSelectedIds().length;
    submitBulkAction('mark_paid', count + ' bills paid hisebe mark korte chailen? Cash payment create hobe.');
}

function bulkMarkUnpaid() {
    var count = getSelectedIds().length;
    submitBulkAction('mark_unpaid', count + ' bills unpaid korte chailen? Related payments delete hobe.');
}

function bulkDelete() {
    var count = getSelectedIds().length;
    submitBulkAction('delete', count + ' bills permanently delete korte chailen? Related payments o delete hobe. Eta undo kora jabe na!');
}
</script>
@endpush
