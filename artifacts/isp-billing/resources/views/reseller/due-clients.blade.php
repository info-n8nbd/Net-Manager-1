@extends('layouts.reseller')

@section('title', 'Due Clients')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.clients') }}">Clients</a></li>
<li class="breadcrumb-item active">Due Clients</li>
@endsection

@section('content')
<style>
.client-card { border-left: 4px solid #dc3545; margin-bottom: 12px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
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
    <div class="col-md-6">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control" placeholder="Search clients..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary ml-2"><i class="fas fa-search"></i></button>
        </form>
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
                        <a href="{{ route('reseller.clients.show', $client) }}" class="btn btn-info btn-sm">
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
                            <td>{{ $client->zone->name ?? '-' }}{{ $client->area ? ' - ' . $client->area->name : '' }}</td>
                            <td>{{ $client->package->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $client->status == 'active' ? 'success' : 'warning' }}">{{ ucfirst($client->status) }}</span></td>
                            <td class="text-danger font-weight-bold">{{ number_format($client->due_amount ?? 0, 2) }}</td>
                            <td>
                                <a href="{{ route('reseller.clients.show', $client) }}" class="btn btn-info btn-xs">View</a>
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
                        <input type="number" name="amount" id="qp_amount" class="form-control form-control-lg text-center" required min="1" step="1">
                    </div>
                    <div class="form-group mb-0">
                        <label>Payment Method</label>
                        <select name="payment_method_id" class="form-control" required>
                            @foreach(auth()->user()->allowedPaymentMethods() as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mt-2" id="qp_cash_receipt_field" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
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
function openQuickPay(clientId, clientName, dueAmount) {
    document.getElementById('qp_client_id').value = clientId;
    document.getElementById('qp_client_name').textContent = clientName;
    document.getElementById('qp_due_display').textContent = '৳' + dueAmount.toLocaleString();
    document.getElementById('qp_amount').value = dueAmount;
    document.getElementById('quickPayForm').action = '/reseller/clients/' + clientId + '/collect-payment';
    // Capture current URL at click-time for redirect after payment
    document.getElementById('qp_redirect_to').value = window.location.href;
    $('#quickPayModal').modal('show');
}

$('#quickPayModal').on('change', 'select[name="payment_method_id"]', function() {
    var methodName = ($(this).find('option:selected').data('name') || '').toLowerCase();
    var isCash = methodName.indexOf('cash') !== -1;
    $('#qp_cash_receipt_field').toggle(isCash);
    if (!isCash) $('#qp_cash_receipt_field').find('input').val('');
});
</script>
@endsection
