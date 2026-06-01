@extends('layouts.admin')

@section('title', 'Payments')
@section('breadcrumb')
<li class="breadcrumb-item active">Payments</li>
@endsection

@section('content')
<style>
.payment-card { border-left: 4px solid #28a745; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.payment-card .card-body { padding: 12px 15px; }
.payment-card .payment-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.payment-card .client-name { font-weight: 600; font-size: 15px; color: #333; margin: 0; }
.payment-card .payment-amount { font-weight: bold; font-size: 16px; color: #28a745; }
.payment-card .payment-info { font-size: 13px; color: #666; margin-bottom: 5px; }
.payment-card .payment-meta { display: flex; flex-wrap: wrap; gap: 10px; font-size: 12px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
#bulkPayBar { display: none; background: linear-gradient(135deg,#fff3cd,#ffe8a1); border: 1px solid #ffc107; border-radius: 6px; padding: 8px 12px; margin-bottom: 10px; }
#bulkPayBar.show { display: flex; }
.pay-row-cb:checked ~ td { background-color: rgba(255,193,7,0.08); }
tr.pay-selected { background-color: rgba(255,193,7,0.10) !important; }
</style>

@include('admin.billing.partials.payment-tabs')
<div class="card card-outline card-primary mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Quick Filter</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.payments.index') }}" method="GET">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="form-group mb-2">
                        <label class="small mb-1"><i class="fas fa-search mr-1"></i>Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Client, TrxID, Payment No..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group mb-2">
                        <label class="small mb-1"><i class="fas fa-mobile-alt mr-1"></i>bKash/Nagad Number</label>
                        <input type="text" name="bkash_number" class="form-control form-control-sm" placeholder="01XXXXXXXXX" value="{{ request('bkash_number') }}">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group mb-2">
                        <label class="small mb-1"><i class="fas fa-credit-card mr-1"></i>Method</label>
                        <select name="payment_method_id" class="form-control form-control-sm">
                            <option value="">All Methods</option>
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ request('payment_method_id') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group mb-2">
                        <label class="small mb-1"><i class="fas fa-calendar mr-1"></i>Date</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                    </div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="form-group mb-2">
                        <label class="small mb-1">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-search mr-1"></i>Filter</button>
                            <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-times mr-1"></i>Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h3 class="card-title mb-2 mb-md-0">All Payments ({{ $payments->total() }})</h3>
        <div class="d-flex gap-2 align-items-center">
            @if($__canAction('billing', 'create_payment'))
            <a href="{{ route('admin.payments.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Receive Payment</a>
            @endif
        </div>
    </div>
    <div class="card-body">


        {{-- ── Bulk Action Toolbar ── --}}
        @if($__canAction('billing', 'delete_payment'))
        <div id="bulkPayBar" class="align-items-center flex-wrap gap-2">
            <span class="font-weight-bold mr-2"><i class="fas fa-check-square mr-1 text-warning"></i><span id="bulkPayCount">0</span> selected</span>
            <button type="button" class="btn btn-danger btn-sm" onclick="submitBulkPayDelete()">
                <i class="fas fa-trash mr-1"></i>Delete Selected
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearPaySelection()">
                <i class="fas fa-times mr-1"></i>Clear
            </button>
        </div>
        <form id="bulkPayForm" method="POST" action="{{ route('admin.payments.bulk-delete') }}" style="display:none;">
            @csrf
            <div id="bulkPayIds"></div>
        </form>
        @endif

        <div class="mobile-cards">
            @forelse($payments as $payment)
            <div class="card payment-card">
                <div class="card-body">
                    @if($__canAction('billing', 'delete_payment'))
                    <div class="d-flex align-items-center mb-2">
                        <input type="checkbox" class="pay-cb-mobile mr-2" data-id="{{ $payment->id }}" style="width:16px;height:16px;cursor:pointer;">
                        <small class="text-muted">Select for bulk delete</small>
                    </div>
                    @endif
                    <div class="payment-header">
                        <h6 class="client-name">{{ $payment->client->name ?? 'N/A' }}</h6>
                        <span class="payment-amount">৳{{ number_format($payment->amount ?? 0) }}</span>
                    </div>
                    <div class="payment-info">
                        <strong>#{{ $payment->payment_no }}</strong>
                    </div>
                    <div class="payment-meta mb-2">
                        <span><i class="fas fa-credit-card mr-1"></i>{{ $payment->paymentMethod->name ?? 'Cash' }}</span>
                        <span><i class="fas fa-calendar mr-1"></i>{{ $payment->payment_date->format('d-m-Y') }}</span>
                        <span><i class="fas fa-user mr-1"></i>{{ $payment->collectedBy->name ?? 'N/A' }}</span>
                    </div>
                    @if($payment->money_receipt_no)
                    <div class="mb-1"><span class="badge badge-secondary"><i class="fas fa-receipt mr-1"></i>Receipt: {{ $payment->money_receipt_no }}</span></div>
                    @endif
                    @if($payment->transaction_id)
                    <div class="mb-1"><span class="badge badge-info"><i class="fas fa-hashtag mr-1"></i>TrxID: {{ $payment->transaction_id }}</span></div>
                    @endif
                    @php
                        $senderNumMob = null;
                        if ($payment->remarks && preg_match('/Sender:\s*([\d\-\+]+)/', $payment->remarks, $mm)) {
                            $senderNumMob = $mm[1];
                        }
                    @endphp
                    @if($senderNumMob)
                    <div class="mb-1"><span class="badge badge-warning"><i class="fas fa-mobile-alt mr-1"></i>{{ $senderNumMob }}</span></div>
                    @endif
                    @if($payment->remarks)
                    <div class="mb-1" style="font-size: 11px;"><i class="fas fa-info-circle mr-1 text-muted"></i><span class="text-muted">{{ $payment->remarks }}</span></div>
                    @endif
                    <div class="btn-group-mobile d-flex flex-wrap gap-2">
                        @if($__canAction('billing', 'print_receipt'))
                        <a href="{{ route('admin.payments.receipt.print', $payment) }}" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-print mr-1"></i>Print</a>
                        <a href="{{ route('admin.payments.receipt.download', $payment) }}" class="btn btn-success btn-sm flex-fill"><i class="fas fa-download mr-1"></i>PDF</a>
                        @endif
                        @if($__canAction('billing', 'edit_payment'))
                        <a href="{{ route('admin.payments.edit', $payment) }}" class="btn btn-warning btn-sm flex-fill"><i class="fas fa-edit mr-1"></i>Edit</a>
                        @endif
                        @if($__canAction('billing', 'delete_payment'))
                        <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST" class="flex-fill" onsubmit="return confirm('Delete this payment?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm btn-block"><i class="fas fa-trash mr-1"></i>Delete</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No payments found</div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            @if($__canAction('billing', 'delete_payment'))
                            <th style="width:32px; text-align:center;">
                                <input type="checkbox" id="paySelectAll" title="Select All" style="cursor:pointer;">
                            </th>
                            @endif
                            <th>Payment No</th><th>Client</th><th>Amount (৳)</th><th>Method</th><th>Sender No.</th><th>TrxID</th><th>Receipt</th><th>Date</th><th>Collected By</th><th>Notes</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="payTableBody">
                        @forelse($payments as $payment)
                        @php
                            $senderNum = null;
                            if ($payment->remarks && preg_match('/Sender:\s*([\d\-\+]+)/', $payment->remarks, $m)) {
                                $senderNum = $m[1];
                            }
                        @endphp
                        <tr class="pay-tr" data-id="{{ $payment->id }}">
                            @if($__canAction('billing', 'delete_payment'))
                            <td style="text-align:center; vertical-align:middle;">
                                <input type="checkbox" class="pay-cb" data-id="{{ $payment->id }}" style="cursor:pointer; width:15px; height:15px;">
                            </td>
                            @endif
                            <td>{{ $payment->payment_no }}</td>
                            <td>{{ $payment->client->name ?? 'N/A' }}<br><small class="text-muted">{{ $payment->client->client_id ?? '' }}</small></td>
                            <td><strong>{{ number_format($payment->amount ?? 0, 2) }}</strong></td>
                            <td><span class="badge badge-success">{{ $payment->paymentMethod->name ?? 'Cash' }}</span></td>
                            <td>
                                @if($senderNum)
                                <span class="badge badge-warning"><i class="fas fa-mobile-alt mr-1"></i>{{ $senderNum }}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->transaction_id)
                                <span class="badge badge-info">{{ $payment->transaction_id }}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->money_receipt_no)
                                <span class="badge badge-secondary"><i class="fas fa-receipt mr-1"></i>{{ $payment->money_receipt_no }}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                            <td>{{ $payment->collectedBy->name ?? 'N/A' }}</td>
                            <td>
                                @if($payment->remarks)
                                <small class="text-muted">{{ Str::limit($payment->remarks, 30) }}</small>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($__canAction('billing', 'print_receipt'))
                                <a href="{{ route('admin.payments.receipt.print', $payment) }}" class="btn btn-primary btn-xs" title="Print Receipt"><i class="fas fa-print"></i></a>
                                <a href="{{ route('admin.payments.receipt.download', $payment) }}" class="btn btn-success btn-xs" title="Download PDF"><i class="fas fa-download"></i></a>
                                @endif
                                @if($__canAction('billing', 'edit_payment'))
                                <a href="{{ route('admin.payments.edit', $payment) }}" class="btn btn-warning btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                                @endif
                                @if($__canAction('billing', 'delete_payment'))
                                <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this payment?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="12" class="text-center">No payments found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
            <div class="mb-2">Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} results</div>
            {{ $payments->links('vendor.pagination.bootstrap-4') }}
        </div>
    </div>
</div>

<script>
(function() {
    var selectAll = document.getElementById('paySelectAll');
    var bulkBar  = document.getElementById('bulkPayBar');
    var bulkCount = document.getElementById('bulkPayCount');

    function getChecked() {
        return Array.from(document.querySelectorAll('.pay-cb:checked'));
    }

    function updateBar() {
        var checked = getChecked();
        var n = checked.length;
        if (bulkCount) bulkCount.textContent = n;
        if (bulkBar) {
            bulkBar.classList.toggle('show', n > 0);
        }
        // update select-all state
        var all = document.querySelectorAll('.pay-cb');
        if (selectAll) {
            selectAll.checked      = all.length > 0 && n === all.length;
            selectAll.indeterminate = n > 0 && n < all.length;
        }
        // highlight rows
        document.querySelectorAll('.pay-tr').forEach(function(tr) {
            var cb = tr.querySelector('.pay-cb');
            tr.classList.toggle('pay-selected', cb && cb.checked);
        });
    }

    // Row checkbox change
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('pay-cb') || e.target.classList.contains('pay-cb-mobile')) {
            updateBar();
        }
    });

    // Select all
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.pay-cb').forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
            updateBar();
        });
    }

    // Click row to toggle checkbox (but not on buttons/links)
    document.querySelectorAll('.pay-tr').forEach(function(tr) {
        tr.style.cursor = 'pointer';
        tr.addEventListener('click', function(e) {
            if (e.target.closest('a,button,form,input')) return;
            var cb = tr.querySelector('.pay-cb');
            if (cb) { cb.checked = !cb.checked; updateBar(); }
        });
    });

    window.clearPaySelection = function() {
        document.querySelectorAll('.pay-cb, .pay-cb-mobile').forEach(function(cb) { cb.checked = false; });
        if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
        updateBar();
    };

    window.submitBulkPayDelete = function() {
        var allChecked = getChecked();
        document.querySelectorAll('.pay-cb-mobile:checked').forEach(function(cb) { allChecked.push(cb); });
        if (allChecked.length === 0) {
            SmartSwal.fire({ title: 'Nothing Selected', text: 'Please select at least one payment.', icon: 'info', confirmButtonText: 'OK' });
            return;
        }
        var n = allChecked.length;
        smartConfirm({
            title: 'Delete Payments',
            message: n + ' payment(s) will be permanently deleted. This cannot be undone.',
            confirmText: '<i class=fas fa-trash mr-1></i>Yes, Delete',
            isDelete: true
        }, function() {
            var form = document.getElementById('bulkPayForm');
            var container = document.getElementById('bulkPayIds');
            container.innerHTML = '';
            var seen = {};
            allChecked.forEach(function(cb) {
                var id = cb.getAttribute('data-id');
                if (id && !seen[id]) {
                    seen[id] = true;
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'ids[]';
                    inp.value = id;
                    container.appendChild(inp);
                }
            });
            // show visual feedback on button
            document.querySelectorAll('#bulkPayBar button').forEach(function(b){ b.disabled = true; });
            form.submit();
        });
    };
})();
</script>
@endsection
