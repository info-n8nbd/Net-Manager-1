@extends('layouts.admin')

@section('title', 'Installation Charges')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.sales.index') }}">Sales & Service</a></li>
<li class="breadcrumb-item active">Installation Charges</li>
@endsection

@section('content')
@php
    $authUser   = auth()->user();
    $canEditIC   = $authUser->isAdmin() || \App\Services\MenuPermissionService::canAccessMenu($authUser, 'sales', 'installation_charges.edit');
    $canDeleteIC = $authUser->isAdmin() || \App\Services\MenuPermissionService::canAccessMenu($authUser, 'sales', 'installation_charges.delete');
@endphp
<div class="row">
    <div class="col-12">

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="small-box bg-info">
                    <div class="inner"><h3>{{ number_format($total, 2) }} ৳</h3><p>Total Installation Charges</p></div>
                    <div class="icon"><i class="fas fa-tools"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-success">
                    <div class="inner"><h3>{{ number_format($totalPaid, 2) }} ৳</h3><p>Collected</p></div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-danger">
                    <div class="inner"><h3>{{ number_format($totalDue, 2) }} ৳</h3><p>Due / Pending</p></div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Installation Charge Records</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row mb-3 align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Search Client</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Name / Client ID" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">All</option>
                            <option value="due"     @selected(request('status') === 'due')>Due</option>
                            <option value="partial" @selected(request('status') === 'partial')>Partial</option>
                            <option value="paid"    @selected(request('status') === 'paid')>Paid</option>
                            <option value="free"    @selected(request('status') === 'free')>Free (0৳)</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">From Date</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">To Date</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3 mb-2 d-flex" style="gap:6px;">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
                        @if(request()->hasAny(['search','status','date_from','date_to']))
                        <a href="{{ route('admin.sales.installation-charges') }}" class="btn btn-secondary btn-sm">Clear</a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Joining Date</th>
                            <th>Client</th>
                            <th>PPPoE / User ID</th>
                            <th>Zone / Area</th>
                            <th class="text-right">Charge (৳)</th>
                            <th class="text-right">Collected (৳)</th>
                            <th class="text-right">Remaining (৳)</th>
                            <th>Status</th>
                            <th>Collected By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($charges as $charge)
                        @php
                            $client      = $charge->client;
                            $amount      = (float) $charge->amount;
                            $isFree      = $amount <= 0;

                            $payments = \App\Models\ClientServiceCharge::where('client_id', $charge->client_id)
                                ->where('charge_type', 'Installation Charge')
                                ->whereNotNull('paid_by')
                                ->where('amount', '>', 0)
                                ->with('paidByUser')
                                ->orderBy('charge_date')
                                ->get();

                            $totalCollected = (float) $payments->sum('amount');
                            $remaining      = max(0, $amount - $totalCollected);

                            $isPaid    = !$isFree && $totalCollected >= $amount && $amount > 0;
                            $isPartial = !$isFree && $totalCollected > 0 && $totalCollected < $amount;
                            $isDue     = !$isFree && $totalCollected <= 0;

                            $lastPayment = $payments->last();
                        @endphp
                        <tr>
                            <td>{{ $charges->firstItem() + $loop->index }}</td>
                            <td>{{ $charge->charge_date ? $charge->charge_date->format('d-m-Y') : '-' }}</td>
                            <td>
                                @if($client)
                                <a href="{{ route('admin.clients.show', $client) }}" target="_blank">
                                    <strong>{{ $client->name }}</strong>
                                    <br><small class="text-muted">{{ $client->client_id }}</small>
                                </a>
                                @else — @endif
                            </td>
                            <td>{{ $client->username ?? '-' }}</td>
                            <td>{{ $client->zone->name ?? '' }}@if($client->area ?? null) / {{ $client->area->name }}@endif</td>
                            <td class="text-right font-weight-bold">
                                @if($isFree)<span class="text-muted">Free</span>
                                @else{{ number_format($amount, 2) }}@endif
                            </td>
                            <td class="text-right">
                                @if($totalCollected > 0)
                                    <span class="text-success font-weight-bold">{{ number_format($totalCollected, 2) }}</span>
                                    @if($payments->count() > 1)<br><small class="text-muted">{{ $payments->count() }} payments</small>@endif
                                @else<span class="text-muted">—</span>@endif
                            </td>
                            <td class="text-right">
                                @if($isFree || $isPaid)<span class="{{ $isPaid ? 'text-success font-weight-bold' : 'text-muted' }}">{{ $isPaid ? '0.00' : '—' }}</span>
                                @elseif($remaining > 0)<span class="text-danger font-weight-bold">{{ number_format($remaining, 2) }}</span>
                                @else<span class="text-muted">—</span>@endif
                            </td>
                            <td>
                                @if($isFree)<span class="badge badge-secondary">Free</span>
                                @elseif($isPaid)
                                    <span class="badge badge-success">Paid</span>
                                    @if($lastPayment?->charge_date)<br><small class="text-muted">{{ $lastPayment->charge_date->format('d-m-Y') }}</small>@endif
                                @elseif($isPartial)
                                    <span class="badge badge-warning text-dark">Partial</span>
                                    <br><small class="text-muted">Due: ৳{{ number_format($remaining, 2) }}</small>
                                @else<span class="badge badge-danger">Due</span>@endif
                            </td>
                            <td>
                                @if($payments->count() > 0)
                                    {{ $lastPayment?->paidByUser?->name ?? '-' }}
                                    @if($lastPayment?->payment_method ?? null)<br><small class="text-muted">{{ $lastPayment->payment_method }}</small>@endif
                                @else<span class="text-muted">—</span>@endif
                            </td>
                            <td>
                                <div class="d-flex" style="gap:4px; flex-wrap:nowrap;">
                                    {{-- Collect Button --}}
                                    @if($client && !$isFree && !$isPaid)
                                    <button type="button"
                                        class="btn btn-xs btn-warning btn-collect-charge"
                                        title="Collect Payment"
                                        data-client-id="{{ $client->id }}"
                                        data-client-name="{{ $client->name }}"
                                        data-client-uid="{{ $client->client_id }}"
                                        data-charge="{{ $amount }}"
                                        data-collected="{{ $totalCollected }}"
                                        data-remaining="{{ $remaining }}"
                                        data-action="{{ route('admin.clients.pay-installation-charge', $client) }}">
                                        <i class="fas fa-money-bill-wave mr-1"></i>Collect
                                    </button>
                                    @elseif($isPaid)
                                    <span class="text-success" title="Fully Paid"><i class="fas fa-check-circle"></i></span>
                                    @endif

                                    {{-- Edit Button --}}
                                    @if($canEditIC)
                                    <button type="button" class="btn btn-xs btn-info btn-edit-charge"
                                        title="Edit Charge"
                                        data-id="{{ $charge->id }}"
                                        data-amount="{{ $amount }}"
                                        data-date="{{ $charge->charge_date ? $charge->charge_date->format('Y-m-d') : '' }}"
                                        data-description="{{ $charge->description }}"
                                        data-client="{{ $client->name ?? '' }}"
                                        data-is-free="{{ $isFree ? '1' : '0' }}"
                                        data-has-payments="{{ $totalCollected > 0 ? '1' : '0' }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif

                                    {{-- Delete Button --}}
                                    @if($canDeleteIC)
                                    <form method="POST" action="{{ route('admin.sales.installation-charges.destroy', $charge) }}"
                                        onsubmit="return confirm('Delete installation charge for {{ addslashes($client->name ?? 'this client') }}?\n\nThis will also delete all payment records and reset the client\'s installation charge to 0.');"
                                        style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="text-center text-muted py-3">No installation charge records found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($charges->count())
                    <tfoot>
                        <tr class="table-secondary font-weight-bold">
                            <td colspan="5" class="text-right">Page Total:</td>
                            <td class="text-right">{{ number_format($charges->filter(fn($c) => $c->amount > 0)->sum('amount'), 2) }}</td>
                            <td colspan="5"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
                </div>

                {{ $charges->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     COLLECT PAYMENT MODAL
     ============================================================ --}}
<div class="modal fade" id="collectPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:12px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#e67700 0%,#d9480f 100%);color:#fff;border-radius:12px 12px 0 0;padding:14px 20px;">
                <h5 class="modal-title" style="font-weight:700;"><i class="fas fa-tools mr-2"></i>Collect Installation Charge</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="collectPaymentForm" method="POST" action="">
                @csrf
                <div class="modal-body" style="padding:20px;">

                    {{-- Client summary --}}
                    <div style="background:#f8f9fa;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong id="cp_client_name" style="font-size:14px;"></strong>
                                <div id="cp_client_uid" style="font-size:12px;color:#888;"></div>
                            </div>
                            <div class="text-right">
                                <div style="font-size:12px;color:#888;">Remaining Due</div>
                                <strong id="cp_remaining_display" style="font-size:20px;color:#e74c3c;"></strong>
                                <div id="cp_paid_summary" style="font-size:11px;color:#27ae60;display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;"><i class="fas fa-money-bill mr-1"></i>Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="cp_amount" class="form-control form-control-lg text-center"
                            required min="1" step="0.01"
                            style="border-radius:10px;font-weight:700;font-size:22px;">
                        <small class="text-muted">Enter amount collected from client</small>
                        <small id="cp_amount_warning" class="text-warning mt-1" style="display:none;">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Amount differs from remaining due — Note required!
                        </small>
                    </div>

                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;"><i class="fas fa-credit-card mr-1"></i>Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method_id" id="cp_payment_method" class="form-control" required
                            style="border-radius:8px;">
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="cp_online_fields" style="display:none;">
                        <div class="form-group">
                            <label style="font-size:13px;font-weight:600;"><i class="fas fa-hashtag mr-1"></i>Transaction ID <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_id" id="cp_trx_id" class="form-control" placeholder="Enter transaction ID" style="border-radius:8px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size:13px;font-weight:600;"><i class="fas fa-phone mr-1"></i><span id="cp_number_label">Number</span> <span class="text-danger">*</span></label>
                            <input type="text" name="sender_number" id="cp_sender_number" class="form-control" style="border-radius:8px;">
                        </div>
                    </div>

                    <div class="form-group" id="cp_receipt_field" style="display:none;">
                        <label style="font-size:13px;font-weight:600;"><i class="fas fa-receipt mr-1"></i>Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" id="cp_receipt" class="form-control" placeholder="Receipt number (optional)" style="border-radius:8px;">
                    </div>

                    <div class="form-group" id="cp_reason_group" style="display:none;">
                        <label style="font-size:13px;font-weight:600;"><i class="fas fa-comment-alt mr-1"></i>Reason <span class="text-danger">*</span></label>
                        @if(!empty($paymentReasons))
                        <select class="form-control mb-1" style="border-radius:8px;" onchange="if(this.value){document.getElementById('cp_reason').value=this.value;}">
                            <option value="">-- Select reason --</option>
                            @foreach($paymentReasons as $reason)
                            <option value="{{ $reason }}">{{ $reason }}</option>
                            @endforeach
                        </select>
                        @endif
                        <input type="text" name="notes_reason" id="cp_reason" class="form-control" placeholder="Why is amount different?" style="border-radius:8px;">
                    </div>

                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;"><i class="fas fa-sticky-note mr-1"></i>Notes</label>
                        <textarea name="notes" id="cp_notes" class="form-control" rows="2" placeholder="Payment notes..." style="border-radius:8px;"></textarea>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="cp_send_sms" name="send_sms" value="1" checked>
                        <label class="custom-control-label" for="cp_send_sms" style="font-size:13px;"><i class="fas fa-sms mr-1"></i>Send SMS notification</label>
                    </div>
                </div>
                <div class="modal-footer" style="padding:12px 20px;">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white" style="font-weight:600;">
                        <i class="fas fa-check mr-1"></i>Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================
     EDIT CHARGE MODAL
     ============================================================ --}}
<div class="modal fade" id="editChargeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Installation Charge</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="editChargeForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <small><i class="fas fa-info-circle mr-1"></i>Editing charge for: <strong id="editClientName"></strong></small>
                    </div>
                    <div class="alert alert-warning py-2" id="editPartialWarning" style="display:none;">
                        <small><i class="fas fa-exclamation-triangle mr-1"></i>
                            This client has already made partial payments. Changing the charge amount will automatically update their payment status.
                        </small>
                    </div>
                    <div class="alert alert-success py-2" id="editFreeWarning" style="display:none;">
                        <small><i class="fas fa-gift mr-1"></i>This is a <strong>free installation</strong> client. Setting an amount &gt; 0 will mark them as a paid client and create a due charge.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Installation Charge Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="editAmount" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Charge Date <span class="text-danger">*</span></label>
                        <input type="date" name="charge_date" id="editDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description / Notes</label>
                        <input type="text" name="description" id="editDescription" class="form-control" placeholder="Optional notes">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i>Update Charge</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {

    // ── Collect Payment Modal ──────────────────────────────
    var cpForm      = document.getElementById('collectPaymentForm');
    var cpAmount    = document.getElementById('cp_amount');
    var cpRemaining = document.getElementById('cp_remaining_display');
    var cpPaidSum   = document.getElementById('cp_paid_summary');
    var cpWarning   = document.getElementById('cp_amount_warning');
    var cpReason    = document.getElementById('cp_reason_group');
    var cpMethod    = document.getElementById('cp_payment_method');
    var cpOnline    = document.getElementById('cp_online_fields');
    var cpReceipt   = document.getElementById('cp_receipt_field');
    var cpLabel     = document.getElementById('cp_number_label');

    var remainingDue = 0;

    document.querySelectorAll('.btn-collect-charge').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action      = this.dataset.action;
            var name        = this.dataset.clientName;
            var uid         = this.dataset.clientUid;
            var charge      = parseFloat(this.dataset.charge);
            var collected   = parseFloat(this.dataset.collected);
            remainingDue    = parseFloat(this.dataset.remaining);

            document.getElementById('cp_client_name').textContent = name;
            document.getElementById('cp_client_uid').textContent  = uid;
            cpRemaining.textContent = '৳' + remainingDue.toFixed(2);
            cpAmount.value = remainingDue.toFixed(2);

            if (collected > 0) {
                cpPaidSum.style.display = 'block';
                cpPaidSum.textContent   = 'Paid: ৳' + collected.toFixed(2) + ' / ৳' + charge.toFixed(2);
            } else {
                cpPaidSum.style.display = 'none';
            }

            cpWarning.style.display = 'none';
            cpReason.style.display  = 'none';
            cpForm.action = action;

            toggleCpPaymentFields();
            $('#collectPaymentModal').modal('show');
        });
    });

    // Amount warning
    if (cpAmount) {
        cpAmount.addEventListener('input', function () {
            var entered = parseFloat(this.value) || 0;
            var diff    = Math.abs(entered - remainingDue);
            var show    = diff > 0.01 && entered > 0;
            cpWarning.style.display = show ? 'block' : 'none';
            cpReason.style.display  = show ? 'block' : 'none';
        });
    }

    // Payment method toggle
    function toggleCpPaymentFields() {
        if (!cpMethod) return;
        var selected = cpMethod.options[cpMethod.selectedIndex];
        var name     = (selected ? selected.dataset.name : '') || '';
        var isCash   = name.includes('cash');

        cpOnline.style.display  = isCash ? 'none' : 'block';
        cpReceipt.style.display = isCash ? 'block' : 'none';

        // Label for sender number
        if (name.includes('bkash'))       cpLabel.textContent = 'bKash Number';
        else if (name.includes('nagad'))  cpLabel.textContent = 'Nagad Number';
        else if (name.includes('rocket')) cpLabel.textContent = 'Rocket Number';
        else                              cpLabel.textContent = 'Sender Number';
    }

    if (cpMethod) cpMethod.addEventListener('change', toggleCpPaymentFields);

    // Form validation
    if (cpForm) {
        cpForm.addEventListener('submit', function (e) {
            var selected = cpMethod ? cpMethod.options[cpMethod.selectedIndex] : null;
            var name     = (selected ? selected.dataset.name : '') || '';
            var isCash   = name.includes('cash');

            if (!isCash) {
                var trx = document.getElementById('cp_trx_id');
                var snr = document.getElementById('cp_sender_number');
                if (trx && !trx.value.trim()) {
                    e.preventDefault();
                    trx.focus();
                    alert('Transaction ID is required for online payments.');
                    return;
                }
                if (snr && !snr.value.trim()) {
                    e.preventDefault();
                    snr.focus();
                    alert('Sender number is required for online payments.');
                    return;
                }
            }

            var entered = parseFloat(cpAmount.value) || 0;
            var diff    = Math.abs(entered - remainingDue);
            if (diff > 0.01) {
                var reason = document.getElementById('cp_reason');
                if (reason && !reason.value.trim()) {
                    e.preventDefault();
                    reason.focus();
                    alert('Please enter a reason for the amount difference.');
                    return;
                }
            }
        });
    }

    // ── Edit Charge Modal ──────────────────────────────────
    document.querySelectorAll('.btn-edit-charge').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editClientName').textContent    = this.dataset.client;
            document.getElementById('editAmount').value              = this.dataset.amount;
            document.getElementById('editDate').value                = this.dataset.date;
            document.getElementById('editDescription').value         = this.dataset.description || '';
            document.getElementById('editChargeForm').action         = '/admin/sales/installation-charges/' + this.dataset.id;
            var isFreeClient = this.dataset.isFree === '1';
            document.getElementById('editPartialWarning').style.display = (!isFreeClient && this.dataset.hasPayments === '1') ? 'block' : 'none';
            document.getElementById('editFreeWarning').style.display = isFreeClient ? 'block' : 'none';
            $('#editChargeModal').modal('show');
        });
    });

})();
</script>
@endpush
@endsection
