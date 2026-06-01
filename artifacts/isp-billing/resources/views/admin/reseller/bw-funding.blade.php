@extends('layouts.admin')
@section('title', 'Bandwidth Reseller Billing')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type'=>'bandwidth']) }}">Bandwidth Resellers</a></li>
<li class="breadcrumb-item active">Bandwidth Reseller Billing</li>
@endsection

@section('content')

@php
    $totalWallet = $resellers->sum('wallet_balance');
    $totalBilled = $resellers->sum('summary_total_billed');
    $totalPaid   = $resellers->sum('summary_total_paid');
    $totalDisc   = $resellers->sum('summary_total_discount');
    $totalDue    = $resellers->sum('summary_due');
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

<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-network-wired mr-1"></i> Bandwidth Reseller Summary</h3>
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
                        <th>Bandwidth Reseller</th>
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
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a>
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
                    <tr><td colspan="9" class="text-center text-muted">No bandwidth resellers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Payment History</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reseller.bw-funding') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="reseller_id" class="form-control form-control-sm">
                        <option value="">All Bandwidth Resellers</option>
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
                    <a href="{{ route('admin.reseller.bw-funding') }}" class="btn btn-sm btn-default"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>Bandwidth Reseller</th>
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
                                title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-xs btn-danger delete-payment-btn"
                                data-id="{{ $payment->id }}"
                                data-reseller="{{ $payment->reseller->name ?? '' }}"
                                data-amount="{{ $payment->amount }}"
                                title="Delete"><i class="fas fa-trash"></i></button>
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
            <form action="{{ route('admin.reseller.bw-funding.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Bandwidth Reseller <span class="text-danger">*</span></label>
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
                    <input type="hidden" name="payment_reference" id="add_payment_reference">
                    <div class="form-group">
                        <label>Reference / Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional reference or notes">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Payment</button>
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
                    <div class="form-group">
                        <label>Reference / Notes</label>
                        <input type="text" id="editNotes" name="notes" class="form-control">
                    </div>
                    <input type="hidden" id="editReference" name="payment_reference">
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
$(function () {
    $('.pay-btn').on('click', function () {
        $('#addResellerId').val($(this).data('id'));
        $('#addPaymentModal').modal('show');
    });

    $('.edit-payment-btn').on('click', function () {
        $('#editResellerName').text($(this).data('reseller'));
        $('#editAmount').val($(this).data('amount'));
        $('#editDiscount').val($(this).data('discount'));
        $('#editMethod').val($(this).data('method'));
        $('#editDate').val($(this).data('date'));
        $('#editNotes').val($(this).data('notes'));
        $('#editReference').val($(this).data('reference'));
        $('#editPaymentForm').attr('action', '{{ url("admin/reseller/funding/payment") }}/' + $(this).data('id'));
        $('#editPaymentModal').modal('show');
    });

    $('.delete-payment-btn').on('click', function () {
        $('#deleteReseller').text($(this).data('reseller'));
        $('#deleteAmount').text('৳' + parseFloat($(this).data('amount')).toFixed(2));
        $('#deletePaymentForm').attr('action', '{{ url("admin/reseller/funding/payment") }}/' + $(this).data('id'));
        $('#deletePaymentModal').modal('show');
    });
});
</script>
@endpush
