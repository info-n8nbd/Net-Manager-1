@extends('layouts.technician')

@section('title', 'Due Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Due Report</li>
@endsection

@push('styles')
<style>
.client-card {
    background: #fff;
    border: 1px solid #ddd;
    border-left: 4px solid #dc3545;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.client-card .client-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}
.client-card .client-name {
    font-weight: 600;
    font-size: 14px;
    color: #333;
}
.client-card .client-id {
    font-size: 11px;
    color: #666;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
}
.client-card .client-info {
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}
.client-card .client-info span {
    display: inline-block;
    margin-right: 12px;
}
.client-card .client-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.client-card .client-actions .btn {
    flex: 1;
    min-width: 80px;
    font-size: 12px;
    padding: 6px 10px;
}
.client-card .due-amount {
    font-size: 18px;
    font-weight: bold;
    color: #dc3545;
}
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
</style>
@endpush

@section('content')
<div class="row mb-3">
    <div class="col-4 mb-2">
        <div class="stat-box bg-danger text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($totalDue ?? 0, 0) }}</div>
            <div class="stat-label">Total Due</div>
        </div>
    </div>
    <div class="col-4 mb-2">
        <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
            <div class="stat-number">{{ $clients->total() }}</div>
            <div class="stat-label">Clients</div>
        </div>
    </div>
    <div class="col-4 mb-2">
        <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ $clients->count() > 0 ? number_format($totalDue / $clients->total(), 0) : 0 }}</div>
            <div class="stat-label">Average</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Due Report</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-8 col-md-3">
                    <select name="zone_id" class="form-control form-control-sm">
                        <option value="">All Zones</option>
                        @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-2">
                    <button type="submit" class="btn btn-info btn-sm btn-block">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Mobile Cards View -->
        <div class="mobile-cards">
            @forelse($clients as $client)
            <div class="client-card">
                <div class="client-header">
                    <div>
                        <div class="client-name">{{ $client->name }}</div>
                        <span class="client-id">{{ $client->client_id }}</span>
                    </div>
                    <div class="due-amount">৳{{ number_format($client->due_amount ?? 0, 0) }}</div>
                </div>
                <div class="client-info">
                    <span><i class="fas fa-phone"></i> {{ $client->phone }}</span>
                    <span><i class="fas fa-map-marker-alt"></i> {{ $client->zone->name ?? '-' }}</span>
                </div>
                <div class="client-info">
                    <span><i class="fas fa-wifi"></i> {{ $client->package->name ?? '-' }}</span>
                    <span class="badge badge-{{ $client->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($client->status) }}</span>
                </div>
                <div class="client-actions">
                    <a href="{{ route('technician.clients.show', $client) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="tel:{{ $client->phone }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-phone"></i> Call
                    </a>
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#paymentModal{{ $client->id }}">
                        <i class="fas fa-money-bill"></i> Collect
                    </button>
                </div>
            </div>

            <div class="modal fade" id="paymentModal{{ $client->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('technician.collect-payment', $client) }}" method="POST">
                            @csrf
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><i class="fas fa-money-bill mr-2"></i>Collect Payment</h5>
                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span><strong>{{ $client->name }}</strong></span>
                                        <span class="badge badge-{{ $client->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($client->status) }}</span>
                                    </div>
                                    <small>ID: {{ $client->client_id }} | {{ $client->phone }}</small>
                                    <div class="mt-1"><small><i class="fas fa-wifi"></i> {{ $client->package->name ?? 'No Package' }}</small></div>
                                </div>
                                <div class="bg-light p-2 rounded mb-3 text-center">
                                    <small class="text-muted">Current Due</small>
                                    <div class="h4 text-danger mb-0">৳{{ number_format($client->due_amount ?? 0, 0) }}</div>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-coins mr-1"></i>Amount (৳)</label>
                                    <input type="number" name="amount" class="form-control form-control-lg payment-amount" 
                                           value="{{ $client->due_amount }}" min="1" required
                                           data-due="{{ $client->due_amount }}" data-client="{{ $client->id }}">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-credit-card mr-1"></i>Payment Method</label>
                                    <select name="payment_method_id" class="form-control" required>
                                        @foreach(auth()->user()->allowedPaymentMethods() as $method)
                                        <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group due-report-cash-receipt" style="display: none;">
                                    <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                                    <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                                </div>
                                <div class="form-group payment-reason-group" style="display: none;">
                                    <label><i class="fas fa-comment-alt mr-1"></i>Reason for Different Amount <span class="text-danger">*</span></label>
                                    <input type="text" name="notes_reason" class="form-control"
                                              placeholder="Why is the amount different from due?">
                                    <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Required when amount differs from due</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i>Collect Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-3x mb-2"></i>
                <p>No clients with due amount</p>
            </div>
            @endforelse
        </div>

        <!-- Desktop Table View -->
        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-danger text-white">
                        <tr>
                            <th>#</th>
                            <th>Client ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Zone</th>
                            <th>Package</th>
                            <th>Due Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $client->client_id }}</td>
                            <td>{{ $client->name }}</td>
                            <td><a href="tel:{{ $client->phone }}">{{ $client->phone }}</a></td>
                            <td>{{ $client->zone->name ?? '-' }}</td>
                            <td>{{ $client->package->name ?? '-' }}</td>
                            <td class="text-danger font-weight-bold">৳{{ number_format($client->due_amount ?? 0, 0) }}</td>
                            <td>
                                <span class="badge badge-{{ $client->status == 'active' ? 'success' : 'danger' }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('technician.clients.show', $client) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#paymentModalDesktop{{ $client->id }}">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                            </td>
                        </tr>

                        <div class="modal fade" id="paymentModalDesktop{{ $client->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('technician.collect-payment', $client) }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title"><i class="fas fa-money-bill mr-2"></i>Collect Payment</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <span><strong>{{ $client->name }}</strong></span>
                                                    <span class="badge badge-{{ $client->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($client->status) }}</span>
                                                </div>
                                                <small>ID: {{ $client->client_id }} | {{ $client->phone }}</small>
                                                <div class="mt-1"><small><i class="fas fa-wifi"></i> {{ $client->package->name ?? 'No Package' }}</small></div>
                                            </div>
                                            <div class="bg-light p-2 rounded mb-3 text-center">
                                                <small class="text-muted">Current Due</small>
                                                <div class="h4 text-danger mb-0">৳{{ number_format($client->due_amount ?? 0, 0) }}</div>
                                            </div>
                                            <div class="form-group">
                                                <label><i class="fas fa-coins mr-1"></i>Amount (৳)</label>
                                                <input type="number" name="amount" class="form-control form-control-lg payment-amount" 
                                                       value="{{ $client->due_amount }}" min="1" required
                                                       data-due="{{ $client->due_amount }}" data-client="{{ $client->id }}">
                                            </div>
                                            <div class="form-group">
                                                <label><i class="fas fa-credit-card mr-1"></i>Payment Method</label>
                                                <select name="payment_method_id" class="form-control" required>
                                                    @foreach(auth()->user()->allowedPaymentMethods() as $method)
                                                    <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group due-report-cash-receipt" style="display: none;">
                                                <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                                                <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                                            </div>
                                            <div class="form-group payment-reason-group" style="display: none;">
                                                <label><i class="fas fa-comment-alt mr-1"></i>Reason for Different Amount <span class="text-danger">*</span></label>
                                                <input type="text" name="notes_reason" class="form-control"
                                                          placeholder="Why is the amount different from due?">
                                                <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Required when amount differs from due</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i>Collect Payment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr><td colspan="9" class="text-center">No clients with due amount found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $clients->withQueryString()->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('change', 'select[name="payment_method_id"]', function() {
        var modal = $(this).closest('.modal');
        var methodName = ($(this).find('option:selected').data('name') || '').toLowerCase();
        var isCash = methodName.indexOf('cash') !== -1;
        modal.find('.due-report-cash-receipt').toggle(isCash);
        if (!isCash) modal.find('.due-report-cash-receipt').find('input').val('');
    });

    document.querySelectorAll('.payment-amount').forEach(function(input) {
        input.addEventListener('input', function() {
            var due = parseFloat(this.dataset.due) || 0;
            var amount = parseFloat(this.value) || 0;
            var reasonGroup = this.closest('.modal-body').querySelector('.payment-reason-group');
            
            if (reasonGroup) {
                var isDiff = Math.abs(amount - due) > 0.01;
                reasonGroup.style.display = isDiff ? 'block' : 'none';
                var ri = reasonGroup.querySelector('[name="notes_reason"]');
                if (ri) { ri.required = isDiff; if (!isDiff) ri.value = ''; }
            }
        });
    });
});
</script>
@endpush
