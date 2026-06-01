@extends('layouts.admin')

@section('title', 'Due Clients')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item active">Due Clients</li>
@endsection

@section('content')
<style>
.client-card { border-left: 4px solid #dc3545; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.client-card .card-body { padding: 12px 15px; }
.client-card .client-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.client-card .client-name { font-weight: 600; font-size: 15px; color: #333; margin: 0; }
.client-card .client-info { font-size: 13px; color: #666; margin-bottom: 5px; }
.client-card .client-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; font-size: 12px; }
.client-card .btn-group-mobile { display: flex; flex-wrap: wrap; gap: 6px; }
.client-card .btn-group-mobile .btn { flex: 1; min-width: 70px; font-size: 13px; padding: 6px 10px; }
.summary-box { background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
.summary-box h4 { margin: 0; font-size: 24px; font-weight: 700; }
.summary-box p { margin: 5px 0 0; opacity: 0.9; }
.quick-pay-btn { background: #28a745; border: none; padding: 4px 10px; font-size: 12px; }
.quick-pay-btn:hover { background: #218838; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
</style>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="summary-box">
            <h4>৳{{ number_format($totalDue ?? 0, 2) }}</h4>
            <p>Total Due Amount from {{ $dueCount }} Clients</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-circle text-danger mr-2"></i>Due Clients ({{ $dueCount }})</h3>
    </div>
    <div class="card-body">
        <div class="mobile-cards">
            @forelse($clients as $client)
            <div class="card client-card">
                <div class="card-body">
                    <div class="client-header">
                        <h6 class="client-name">{{ $client->name }}</h6>
                        <span class="badge badge-{{ $client->status == 'active' ? 'success' : 'warning' }}">{{ ucfirst($client->status) }}</span>
                    </div>
                    <div class="client-info">
                        <strong>ID:</strong> {{ $client->client_id }} | <strong>Phone:</strong> {{ $client->phone }}
                    </div>
                    <div class="client-meta">
                        <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $client->zone->name ?? '-' }}{{ $client->area ? ' - ' . $client->area->name : '' }}</span>
                        <span><i class="fas fa-box mr-1"></i>{{ $client->package->name ?? '-' }}</span>
                    </div>
                    <div class="client-meta">
                        <span class="text-danger"><strong>Due: ৳{{ number_format($client->due_amount ?? 0) }}</strong></span>
                    </div>
                    <div class="btn-group-mobile">
                        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <button type="button" class="btn btn-success btn-sm quick-pay-btn" onclick="openQuickPay({{ $client->id }}, '{{ addslashes($client->name) }}', {{ $client->due_amount }})">
                            <i class="fas fa-bolt mr-1"></i> Quick Pay
                        </button>
                        <a href="tel:{{ $client->phone }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-phone mr-1"></i> Call
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No clients with due amount</div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Zone</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Due (৳)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr>
                            <td>{{ $client->client_id }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->phone }}</td>
                            <td>{{ $client->zone->name ?? '-' }}{{ $client->area ? ' - ' . $client->area->name : '' }}</td>
                            <td>{{ $client->package->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $client->status == 'active' ? 'success' : 'warning' }}">{{ ucfirst($client->status) }}</span></td>
                            <td class="text-danger font-weight-bold">{{ number_format($client->due_amount ?? 0, 2) }}</td>
                            <td>
                                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-info btn-xs">View</a>
                                <button type="button" class="btn btn-success btn-xs quick-pay-btn" onclick="openQuickPay({{ $client->id }}, '{{ addslashes($client->name) }}', {{ $client->due_amount }})">
                                    <i class="fas fa-bolt"></i> Quick Pay
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center">No clients with due amount</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $clients->appends(request()->query())->links() }}
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
        </div>
    </div>
</div>

<div class="modal fade" id="quickPayModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title"><i class="fas fa-bolt mr-2"></i>Quick Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="quickPayForm" method="POST" action="">
                @csrf
                <input type="hidden" name="redirect_to" id="qp_redirect_to" value="">
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <h6 id="qp_client_name" class="mb-1"></h6>
                        <p class="text-danger mb-0">Due: <strong id="qp_due_display"></strong></p>
                    </div>
                    <div class="form-group">
                        <label>Amount (৳)</label>
                        <input type="number" name="amount" id="qp_amount" class="form-control form-control-lg text-center" required min="1" step="0.01">
                        <small id="qp_amount_warning" class="text-warning" style="display: none;">Amount differs from due - Note required!</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Payment Method</label>
                        <select name="payment_method_id" class="form-control" required id="qp_payment_method" onchange="toggleQpOnlineFields()">
                            @foreach(auth()->user()->allowedPaymentMethods() as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="qp_online_payment_fields" style="display: none;" class="mt-2">
                        <div class="form-group mb-2">
                            <label><i class="fas fa-hashtag mr-1"></i> Transaction ID (TrxID) <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_id" id="qp_trx_id" class="form-control" placeholder="Enter transaction ID">
                        </div>
                        <div class="form-group mb-0">
                            <label><i class="fas fa-phone mr-1"></i> <span id="qp_number_label">bKash Number</span> <span class="text-danger">*</span></label>
                            <input type="text" name="sender_number" id="qp_sender_number" class="form-control" placeholder="">
                        </div>
                    </div>
                    <div class="form-group mt-2" id="qp_cash_receipt_field" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    <div class="form-group mt-2">
                        <label>Notes</label>
                        <textarea name="notes" id="qp_notes" class="form-control mt-2" rows="2" placeholder="Payment notes..."></textarea>
                    </div>
                    <div class="form-group mt-2 mb-0">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="qp_send_sms" name="send_sms" value="1" checked>
                            <label class="custom-control-label" for="qp_send_sms">
                                <i class="fas fa-sms mr-1"></i> Send SMS notification
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check mr-1"></i>Collect Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var qpDueAmount = 0;

function toggleQpOnlineFields() {
    var sel = document.getElementById('qp_payment_method');
    var opt = sel.options[sel.selectedIndex];
    var name = (opt.getAttribute('data-name') || '').toLowerCase();
    var displayName = opt.textContent.trim();
    var isCash = name.indexOf('cash') !== -1;
    var isOnline = !isCash;
    var fields = document.getElementById('qp_online_payment_fields');
    var trxId = document.getElementById('qp_trx_id');
    var senderNum = document.getElementById('qp_sender_number');
    fields.style.display = isOnline ? 'block' : 'none';
    trxId.required = isOnline;
    senderNum.required = isOnline;
    if (isOnline) {
        document.getElementById('qp_number_label').textContent = displayName + ' Number';
        senderNum.placeholder = 'Enter ' + displayName + ' number';
    }
    if (!isOnline) { trxId.value = ''; senderNum.value = ''; }
    var cashField = document.getElementById('qp_cash_receipt_field');
    if (cashField) {
        cashField.style.display = isCash ? 'block' : 'none';
    }
}

function checkQpDiff() {
    var amount = parseFloat(document.getElementById('qp_amount').value) || 0;
    var isDifferent = qpDueAmount > 0 && Math.abs(amount - qpDueAmount) > 0.01;
    document.getElementById('qp_amount_warning').style.display = isDifferent ? 'block' : 'none';
    return isDifferent;
}

function openQuickPay(clientId, clientName, dueAmount) {
    qpDueAmount = dueAmount;
    document.getElementById('qp_client_name').textContent = clientName;
    document.getElementById('qp_due_display').textContent = '৳' + dueAmount.toLocaleString();
    document.getElementById('qp_amount').value = dueAmount;
    document.getElementById('quickPayForm').action = '/admin/clients/' + clientId + '/quick-payment';
    // Capture current URL at click-time for redirect after payment
    document.getElementById('qp_redirect_to').value = window.location.href;
    setTimeout(checkQpDiff, 50);
    toggleQpOnlineFields();
    $('#quickPayModal').modal('show');
}

// Strict validation on multiple events
var qpAmountEl = document.getElementById('qp_amount');
qpAmountEl.addEventListener('input', checkQpDiff);
qpAmountEl.addEventListener('change', checkQpDiff);
qpAmountEl.addEventListener('keyup', checkQpDiff);

document.getElementById('quickPayForm').addEventListener('submit', function(e) {
    checkQpDiff();
});
</script>
@endsection
