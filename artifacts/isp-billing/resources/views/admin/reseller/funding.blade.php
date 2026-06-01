@extends('layouts.admin')
@section('title', 'MAC Reseller Billing')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index') }}">MAC Resellers</a></li>
<li class="breadcrumb-item active">MAC Reseller Billing</li>
@endsection

@section('content')

{{-- SUMMARY STATS --}}
@php
    $totalWallet  = $resellers->sum('wallet_balance');
    $totalBilled  = $resellers->sum('summary_total_billed');
    $totalPaid    = $resellers->sum('summary_total_paid');
    $totalDisc    = $resellers->sum('summary_total_discount');
    $totalDue     = $resellers->sum('summary_due');
@endphp
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3>৳{{ number_format($totalWallet, 0) }}</h3><p>Total Wallet</p></div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
            
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>৳{{ number_format($totalPaid, 0) }}</h3><p>Total Paid</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3>৳{{ number_format($totalDisc, 0) }}</h3><p>Total Discount</p></div>
            <div class="icon"><i class="fas fa-tag"></i></div>
            
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3>৳{{ number_format($totalDue, 0) }}</h3><p>Total Due</p></div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            
        </div>
    </div>
</div>

{{-- RESELLER SUMMARY TABLE --}}
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-users mr-1"></i> MAC Reseller Summary</h3>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPaymentModal">
            <i class="fas fa-plus"></i> Add Payment
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>MAC Reseller</th>
                        <th>Billing Type</th>
                        <th class="text-right">Total Billed</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">Due</th>
                        <th class="text-right">Wallet</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $i => $reseller)
                    <tr class="{{ $reseller->summary_due > 0 ? 'table-warning' : '' }}">
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a>
                            <br><small class="text-muted">{{ $reseller->clients->count() }} clients</small>
                        </td>
                        <td><span class="badge badge-{{ $reseller->billing_type === 'prepaid' ? 'success' : 'info' }}">{{ ucfirst($reseller->billing_type) }}</span></td>
                        <td class="text-right">৳{{ number_format($reseller->summary_total_billed, 2) }}</td>
                        <td class="text-right text-success font-weight-bold">৳{{ number_format($reseller->summary_total_paid, 2) }}</td>
                        <td class="text-right text-warning">৳{{ number_format($reseller->summary_total_discount, 2) }}</td>
                        <td class="text-right {{ $reseller->summary_due > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                            ৳{{ number_format($reseller->summary_due, 2) }}
                        </td>
                        <td class="text-right">৳{{ number_format($reseller->wallet_balance, 2) }}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-primary pay-btn"
                                data-id="{{ $reseller->id }}"
                                data-name="{{ $reseller->name }}"
                                title="Add Payment">
                                <i class="fas fa-money-bill-wave"></i> Pay
                            </button>
                            <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-xs btn-secondary" title="Bill History">
                                <i class="fas fa-history"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No resellers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- PAYMENT HISTORY --}}
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Payment History</h3>
    </div>
    <div class="card-body">
        {{-- Filter --}}
        <form method="GET" action="{{ route('admin.reseller.funding') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="reseller_id" class="form-control form-control-sm">
                        <option value="">All MAC Resellers</option>
                        @foreach($resellers as $r)
                        <option value="{{ $r->id }}" {{ request('reseller_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To Date">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary mr-1"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('admin.reseller.funding') }}" class="btn btn-sm btn-default"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>MAC Reseller</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Discount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Notes</th>
                        <th>Received By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                        <td>{{ $payment->reseller->name ?? '-' }}</td>
                        <td class="text-right font-weight-bold text-success">৳{{ number_format($payment->amount, 2) }}</td>
                        <td class="text-right text-warning">{{ $payment->discount_amount > 0 ? '৳'.number_format($payment->discount_amount, 2) : '-' }}</td>
                        <td>{{ ucfirst($payment->payment_method) }}</td>
                        <td>{{ $payment->payment_reference ?: '-' }}</td>
                        <td>{{ $payment->notes ?: '-' }}</td>
                        <td>{{ $payment->receivedByUser->name ?? 'System' }}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-warning edit-payment-btn"
                                data-id="{{ $payment->id }}"
                                data-amount="{{ $payment->amount }}"
                                data-discount="{{ $payment->discount_amount }}"
                                data-method="{{ $payment->payment_method }}"
                                data-reference="{{ $payment->payment_reference }}"
                                data-date="{{ $payment->payment_date }}"
                                data-notes="{{ $payment->notes }}"
                                data-reseller="{{ $payment->reseller->name ?? '' }}"
                                title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-xs btn-danger delete-payment-btn"
                                data-id="{{ $payment->id }}"
                                data-reseller="{{ $payment->reseller->name ?? '' }}"
                                data-amount="{{ $payment->amount }}"
                                title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">No payment records found.</td></tr>
                    @endforelse
                </tbody>
                @if($payments->count())
                <tfoot class="thead-light">
                    <tr>
                        <th colspan="2" class="text-right">Page Total:</th>
                        <th class="text-right text-success">৳{{ number_format($payments->sum('amount'), 2) }}</th>
                        <th class="text-right text-warning">৳{{ number_format($payments->sum('discount_amount'), 2) }}</th>
                        <th colspan="5"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        {{ $payments->links() }}
    </div>
</div>

{{-- ADD PAYMENT MODAL --}}
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave mr-1"></i> Add Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('admin.reseller.funding.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Reseller <span class="text-danger">*</span></label>
                        <select name="reseller_id" id="addResellerId" class="form-control" required>
                            <option value="">-- Select Reseller --</option>
                            @foreach($resellers as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} — Due: ৳{{ number_format($r->summary_due, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Amount (BDT) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Discount (BDT)</label>
                                <input type="number" name="discount_amount" class="form-control" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->code ?? $pm->name }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="add_field_receipt" style="display:none;">
                        <label><i class="fas fa-receipt mr-1 text-success"></i> Money Receipt No.</label>
                        <input type="text" id="add_ref_cash" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    <div class="form-group" id="add_field_txn" style="display:none;">
                        <label><i class="fas fa-mobile-alt mr-1 text-info"></i> Account Number</label>
                        <input type="text" id="add_ref_acct" class="form-control" placeholder="Enter account number (e.g. 01XXXXXXXXX)">
                        <label class="mt-2"><i class="fas fa-hashtag mr-1 text-info"></i> Transaction No.</label>
                        <input type="text" id="add_ref_txn" class="form-control" placeholder="Enter transaction number">
                    </div>
                    <div class="form-group" id="add_field_bank" style="display:none;">
                        <label><i class="fas fa-university mr-1 text-primary"></i> Account No. / Bank Reference</label>
                        <input type="text" id="add_ref_bank" class="form-control" placeholder="Enter account or reference number">
                    </div>
                    <input type="hidden" name="payment_reference" id="add_payment_reference">
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addPaymentSubmitBtn"><i class="fas fa-save mr-1"></i> Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT PAYMENT MODAL --}}
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit mr-1"></i> Edit Payment — <span id="editResellerName"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="editPaymentForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Amount (BDT) <span class="text-danger">*</span></label>
                                <input type="number" id="editAmount" name="amount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Discount (BDT)</label>
                                <input type="number" id="editDiscount" name="discount_amount" class="form-control" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select id="editMethod" name="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->code ?? $pm->name }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="date" id="editDate" name="payment_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="edit_field_receipt" style="display:none;">
                        <label><i class="fas fa-receipt mr-1 text-success"></i> Money Receipt No.</label>
                        <input type="text" id="edit_ref_cash" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    <div class="form-group" id="edit_field_txn" style="display:none;">
                        <label><i class="fas fa-mobile-alt mr-1 text-info"></i> Account Number</label>
                        <input type="text" id="edit_ref_acct" class="form-control" placeholder="Enter account number (e.g. 01XXXXXXXXX)">
                        <label class="mt-2"><i class="fas fa-hashtag mr-1 text-info"></i> Transaction No.</label>
                        <input type="text" id="edit_ref_txn" class="form-control" placeholder="Enter transaction number">
                    </div>
                    <div class="form-group" id="edit_field_bank" style="display:none;">
                        <label><i class="fas fa-university mr-1 text-primary"></i> Account No. / Bank Reference</label>
                        <input type="text" id="edit_ref_bank" class="form-control" placeholder="Enter account or reference number">
                    </div>
                    <input type="hidden" id="editReference" name="payment_reference">
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea id="editNotes" name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DELETE PAYMENT MODAL --}}
<div class="modal fade" id="deletePaymentModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash mr-1"></i> Delete Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <p>Delete payment of <strong id="deleteAmount"></strong> for <strong id="deleteReseller"></strong>?</p>
                <p class="text-danger small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                <form id="deletePaymentForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function methodFields(method, prefix) {
    var m = (method || '').toLowerCase();
    var isCash   = m.indexOf('cash') !== -1;
    var isMobile = m.indexOf('bkash') !== -1 || m.indexOf('nagad') !== -1
                || m.indexOf('rocket') !== -1 || m.indexOf('upay') !== -1
                || m.indexOf('mfs') !== -1 || m.indexOf('mobile') !== -1;
    var isBank   = m.indexOf('bank') !== -1 || m.indexOf('transfer') !== -1
                || m.indexOf('cheque') !== -1;

    $('#' + prefix + '_field_receipt').toggle(isCash);
    $('#' + prefix + '_field_txn').toggle(isMobile);
    $('#' + prefix + '_field_bank').toggle(isBank);

    if (!isCash)   $('#' + prefix + '_ref_cash').val('');
    if (!isMobile) { $('#' + prefix + '_ref_acct').val(''); $('#' + prefix + '_ref_txn').val(''); }
    if (!isBank)   $('#' + prefix + '_ref_bank').val('');
}

function syncRef(prefix, hiddenId) {
    var receipt = $('#' + prefix + '_field_receipt').is(':visible') ? $('#' + prefix + '_ref_cash').val() : '';
    var bank    = $('#' + prefix + '_field_bank').is(':visible')    ? $('#' + prefix + '_ref_bank').val() : '';
    var mfsRef  = '';
    if ($('#' + prefix + '_field_txn').is(':visible')) {
        var acct = $('#' + prefix + '_ref_acct').val().trim();
        var txn  = $('#' + prefix + '_ref_txn').val().trim();
        if (acct && txn)      mfsRef = 'Acct: ' + acct + ' | TXN: ' + txn;
        else if (acct)         mfsRef = 'Acct: ' + acct;
        else if (txn)          mfsRef = txn;
    }
    $(hiddenId).val(receipt || mfsRef || bank || '');
}

function parseMfsRef(ref) {
    var acct = '', txn = '';
    if (!ref) return { acct: '', txn: '' };
    var acctMatch = ref.match(/Acct:\s*([^|]+)/);
    var txnMatch  = ref.match(/TXN:\s*(.+)/);
    if (acctMatch) acct = acctMatch[1].trim();
    if (txnMatch)  txn  = txnMatch[1].trim();
    if (!acctMatch && !txnMatch) txn = ref;
    return { acct: acct, txn: txn };
}

$(function () {
    // ---- ADD MODAL: payment method change ----
    var $addMethod = $('#addPaymentModal').find('select[name="payment_method"]');
    $addMethod.on('change', function () {
        methodFields($(this).val(), 'add');
        syncRef('add', '#add_payment_reference');
    });
    $('#add_ref_cash, #add_ref_acct, #add_ref_txn, #add_ref_bank').on('input', function () {
        syncRef('add', '#add_payment_reference');
    });
    $('#addPaymentModal form').on('submit', function () {
        syncRef('add', '#add_payment_reference');
    });
    // Trigger on modal show to set initial state
    $('#addPaymentModal').on('shown.bs.modal', function () {
        methodFields($addMethod.val(), 'add');
    });

    // ---- EDIT MODAL: payment method change ----
    $('#editMethod').on('change', function () {
        methodFields($(this).val(), 'edit');
        syncRef('edit', '#editReference');
    });
    $('#edit_ref_cash, #edit_ref_acct, #edit_ref_txn, #edit_ref_bank').on('input', function () {
        syncRef('edit', '#editReference');
    });
    $('#editPaymentForm').on('submit', function () {
        syncRef('edit', '#editReference');
    });

    // Quick Pay button from summary table
    $('.pay-btn').on('click', function () {
        var id   = $(this).data('id');
        $('#addResellerId').val(id);
        $('#addPaymentModal').modal('show');
    });

    // Edit payment
    $('.edit-payment-btn').on('click', function () {
        var id        = $(this).data('id');
        var amount    = $(this).data('amount');
        var discount  = $(this).data('discount');
        var method    = $(this).data('method');
        var reference = $(this).data('reference') || '';
        var date      = $(this).data('date');
        var notes     = $(this).data('notes');
        var reseller  = $(this).data('reseller');

        $('#editResellerName').text(reseller);
        $('#editAmount').val(amount);
        $('#editDiscount').val(discount);
        $('#editMethod').val(method);
        $('#editDate').val(date);
        $('#editNotes').val(notes);
        $('#editPaymentForm').attr('action', '{{ url("admin/reseller/funding/payment") }}/' + id);

        // Show correct reference field and set value
        methodFields(method, 'edit');
        var m = (method || '').toLowerCase();
        if (m.indexOf('cash') !== -1) {
            $('#edit_ref_cash').val(reference);
        } else if (m.indexOf('bkash') !== -1 || m.indexOf('nagad') !== -1 || m.indexOf('rocket') !== -1 || m.indexOf('mobile') !== -1 || m.indexOf('mfs') !== -1 || m.indexOf('upay') !== -1) {
            var parsed = parseMfsRef(reference);
            $('#edit_ref_acct').val(parsed.acct);
            $('#edit_ref_txn').val(parsed.txn);
        } else if (m.indexOf('bank') !== -1 || m.indexOf('transfer') !== -1 || m.indexOf('cheque') !== -1) {
            $('#edit_ref_bank').val(reference);
        }
        $('#editReference').val(reference);

        $('#editPaymentModal').modal('show');
    });

    // Delete payment
    $('.delete-payment-btn').on('click', function () {
        var id       = $(this).data('id');
        var reseller = $(this).data('reseller');
        var amount   = $(this).data('amount');
        $('#deleteReseller').text(reseller);
        $('#deleteAmount').text('৳' + parseFloat(amount).toFixed(2));
        $('#deletePaymentForm').attr('action', '{{ url("admin/reseller/funding/payment") }}/' + id);
        $('#deletePaymentModal').modal('show');
    });
});
</script>
@endpush
