@extends('layouts.technician')

@section('title', 'Due Clients')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('technician.clients') }}">Clients</a></li>
<li class="breadcrumb-item active">Due Clients</li>
@endsection

@push('styles')
<style>
.client-card { border-left: 4px solid #dc3545; margin-bottom: 10px; background: #fff; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.client-card .card-body { padding: 10px 12px; }
.client-card .client-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
.client-card .client-name { font-weight: 600; font-size: 14px; color: #333; margin: 0; }
.client-card .client-info { font-size: 12px; color: #666; margin-bottom: 4px; }
.client-card .client-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; font-size: 11px; }
.client-card .btn-group-mobile { display: flex; flex-wrap: wrap; gap: 5px; }
.client-card .btn-group-mobile .btn { flex: 1; min-width: 60px; font-size: 12px; padding: 4px 8px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
.due-header-wrap { display: flex; flex-direction: column; gap: 8px; width: 100%; }
.due-search-bar { display: flex; gap: 6px; align-items: center; }
.due-search-bar .form-control-sm { height: 30px; }
.mobile-quick-search { position: relative; }
.mobile-search-results {
    position: absolute; width: 100%; max-height: 350px; overflow-y: auto;
    background: white; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    border-radius: 6px; margin-top: 4px; border: 1px solid #ddd; display: none;
}
.search-result-item { display: block; padding: 10px 12px; border-bottom: 1px solid #eee; color: #333; text-decoration: none !important; }
.search-result-item:hover { background-color: #f0f7ff; color: #007bff; }
.result-avatar { width: 36px; height: 36px; background: #1e3c72; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; margin-right: 10px; }
.result-info { flex: 1; }
.result-name { font-weight: 600; font-size: 13px; margin-bottom: 2px; }
.result-meta { font-size: 10px; color: #666; display: flex; flex-wrap: wrap; gap: 6px; }
</style>
@endpush

@section('content')
@if(isset($isSearching) && $isSearching)
<div class="alert alert-info py-2 mb-2" style="font-size: 13px;">
    <i class="fas fa-info-circle mr-1"></i> Showing results from all zones.
</div>
@endif

<div class="card">
    <div class="card-header py-2">
        <h3 class="card-title" style="font-size:14px;">
            <i class="fas fa-exclamation-circle text-danger mr-1"></i>Due Clients
            <span class="text-danger ml-1">৳{{ number_format($totalDue ?? 0, 0) }}</span>
            <small class="text-muted ml-1">({{ $dueCount }})</small>
        </h3>
        @if(request('search') || request('zone_id'))
        <div class="card-tools">
            <a href="{{ route('technician.due-clients') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times mr-1"></i>Clear</a>
        </div>
        @endif
    </div>
    <div class="card-body pb-0 pt-2">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;">
            <div class="mobile-quick-search" style="flex:1;position:relative;">
                <input type="text" id="mobileClientSearch" class="form-control form-control-sm" placeholder="Search name, ID, phone..." style="font-size:12px;">
                <div class="mobile-search-results" id="mobileSearchResults"></div>
            </div>
            <form method="GET" style="margin:0;">
                <select name="zone_id" class="form-control form-control-sm" style="font-size:12px;width:auto;" onchange="this.form.submit()">
                    <option value="">All Zones</option>
                    @foreach($zones as $zone)
                    <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
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
                        <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $client->zone->name ?? '-' }}</span>
                        <span><i class="fas fa-box mr-1"></i>{{ $client->package->name ?? '-' }}</span>
                    </div>
                    <div class="client-meta">
                        <span class="text-danger"><strong>Due: ৳{{ number_format($client->due_amount ?? 0) }}</strong></span>
                    </div>
                    <div class="btn-group-mobile">
                        <a href="{{ route('technician.clients.show', $client) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <button type="button" class="btn btn-success btn-sm" onclick="openQuickPay({{ $client->id }}, '{{ $client->name }}', {{ $client->due_amount }})">
                            <i class="fas fa-money-bill mr-1"></i> Pay
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
                            <td>{{ $client->zone->name ?? '-' }}</td>
                            <td>{{ $client->package->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $client->status == 'active' ? 'success' : 'warning' }}">{{ ucfirst($client->status) }}</span></td>
                            <td class="text-danger font-weight-bold">৳{{ number_format($client->due_amount ?? 0, 0) }}</td>
                            <td>
                                <a href="{{ route('technician.clients.show', $client) }}" class="btn btn-info btn-xs">View</a>
                                <button type="button" class="btn btn-success btn-xs" onclick="openQuickPay({{ $client->id }}, '{{ $client->name }}', {{ $client->due_amount }})">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                                <a href="tel:{{ $client->phone }}" class="btn btn-primary btn-xs"><i class="fas fa-phone"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center">No clients with due amount</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $clients->links() }}</div>
        </div>
    </div>
</div>

<div class="modal fade" id="quickPayModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Quick Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="quickPayForm" method="POST" action="">
                @csrf
                <input type="hidden" name="client_id" id="qp_client_id">
                <input type="hidden" name="redirect_to" id="qp_redirect_to" value="">
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <h6 id="qp_client_name" class="mb-1"></h6>
                        <p class="text-danger mb-0">Due: <strong id="qp_due_display"></strong></p>
                    </div>
                    <div class="form-group">
                        <label>Amount (৳)</label>
                        <input type="number" name="amount" id="qp_amount" class="form-control form-control-lg text-center" required min="1" step="1" data-due="0" oninput="checkQuickPayDiff()" onchange="checkQuickPayDiff()">
                        <small class="text-warning" id="qp_amount_warning" style="display: none;">Amount differs from due - Reason required!</small>
                    <div class="form-group mt-2" id="qp_reason_field" style="display: none;">
                        <label><i class="fas fa-info-circle mr-1"></i> Reason for Partial/Extra Payment</label>
                        <input type="text" name="partial_reason" id="qp_partial_reason" class="form-control" placeholder="e.g. Client paid partially, will pay rest later...">
                    </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Payment Method</label>
                        <select name="payment_method_id" id="tech_qp_method" class="form-control" required>
                            @foreach(auth()->user()->allowedPaymentMethods() as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mt-2" id="tech_cash_receipt_field" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    <div id="tech_online_fields" style="display: none;">
                        <div class="form-group mt-2">
                            <label><i class="fas fa-hashtag mr-1"></i> Transaction ID</label>
                            <input type="text" name="transaction_id" class="form-control" placeholder="Enter transaction ID">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-mobile-alt mr-1"></i> Account / Mobile No.</label>
                            <input type="text" name="mobile_payment_number" class="form-control" placeholder="e.g. 017XXXXXXXX">
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <label><i class="fas fa-comment mr-1"></i> Notes</label>
                        <textarea name="notes" class="form-control mt-2" rows="2" placeholder="Payment notes..."></textarea>
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimeout = null;
    const searchInput = $('#mobileClientSearch');
    const resultsDiv = $('#mobileSearchResults');

    searchInput.on('input', function() {
        const query = $(this).val().trim();
        if (searchTimeout) clearTimeout(searchTimeout);
        if (query.length < 2) { resultsDiv.empty().hide(); return; }

        searchTimeout = setTimeout(() => {
            $.ajax({
                url: "{{ route('admin.api.quick-search') }}",
                data: { q: query },
                success: function(data) {
                    resultsDiv.empty();
                    if (data.length > 0) {
                        data.forEach(client => {
                            const initial = client.name.charAt(0).toUpperCase();
                            const statusClass = client.status === 'active' ? 'success' : (client.status === 'inactive' ? 'danger' : 'warning');
                            const connStatusClass = client.connection_status === 'online' ? 'success' : 'danger';
                            resultsDiv.append(`
                                <a href="/technician/clients/${client.id}" class="search-result-item">
                                    <div class="d-flex align-items-center">
                                        <div class="result-avatar">${initial}</div>
                                        <div class="result-info">
                                            <div class="d-flex justify-content-between">
                                                <div class="result-name">${client.name}</div>
                                                <div class="status-badges">
                                                    <span class="badge badge-xs badge-${statusClass}">${client.status}</span>
                                                    <span class="badge badge-xs badge-${connStatusClass}"><i class="fas fa-wifi mr-1"></i>${client.connection_status}</span>
                                                </div>
                                            </div>
                                            <div class="result-meta">
                                                <span><i class="fas fa-id-card"></i> ${client.username || client.client_id}</span>
                                                <span class="text-success"><i class="fas fa-phone"></i> ${client.phone}</span>
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted ml-2"></i>
                                    </div>
                                </a>
                            `);
                        });
                        resultsDiv.show();
                    } else {
                        resultsDiv.append('<div class="p-3 text-center text-muted">No clients found</div>').show();
                    }
                }
            });
        }, 300);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.mobile-quick-search').length) { resultsDiv.empty().hide(); }
    });
});

function openQuickPay(clientId, clientName, dueAmount) {
    document.getElementById('qp_client_id').value = clientId;
    document.getElementById('qp_client_name').textContent = clientName;
    document.getElementById('qp_due_display').textContent = '৳' + dueAmount.toLocaleString();
    var amountInput = document.getElementById('qp_amount');
    amountInput.value = dueAmount;
    amountInput.dataset.due = dueAmount;
    document.getElementById('quickPayForm').action = '/technician/clients/' + clientId + '/collect-payment';
    document.getElementById('qp_redirect_to').value = window.location.href;
    document.getElementById('qp_amount_warning').style.display = 'none';
    var rf = document.getElementById('qp_reason_field');
    var ri = document.getElementById('qp_partial_reason');
    if (rf) rf.style.display = 'none';
    if (ri) { ri.value = ''; ri.required = false; }
    $('#tech_cash_receipt_field').hide();
    $('#tech_online_fields').hide();
    $('#tech_online_fields input').val('');
    $('#tech_cash_receipt_field input').val('');
    toggleTechQpFields();
    $('#quickPayModal').modal('show');
}

function toggleTechQpFields() {
    var sel = $('#tech_qp_method');
    var methodName = (sel.find('option:selected').data('name') || '').toLowerCase();
    var isCash = methodName.indexOf('cash') !== -1;
    var isOnline = !isCash && methodName.length > 0;
    $('#tech_cash_receipt_field').toggle(isCash);
    $('#tech_online_fields').toggle(isOnline);
    if (!isCash) $('#tech_cash_receipt_field input').val('');
    if (!isOnline) $('#tech_online_fields input').val('');
}

function checkQuickPayDiff() {
    var input = document.getElementById('qp_amount');
    var amount = parseFloat(input.value) || 0;
    var due = parseFloat(input.dataset.due) || 0;
    var isDifferent = Math.abs(amount - due) > 0.01;
    document.getElementById('qp_amount_warning').style.display = isDifferent ? 'block' : 'none';
    var reasonField = document.getElementById('qp_reason_field');
    var reasonInput = document.getElementById('qp_partial_reason');
    if (reasonField) {
        reasonField.style.display = isDifferent ? 'block' : 'none';
        if (reasonInput) reasonInput.required = isDifferent;
        if (!isDifferent && reasonInput) reasonInput.value = '';
    }
}

$('#quickPayModal').on('shown.bs.modal', function() {
    toggleTechQpFields();
});

$('#quickPayModal').on('change', '#tech_qp_method', function() {
    toggleTechQpFields();
});
</script>
@endpush
