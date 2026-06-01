@extends('layouts.technician')

@section('title', 'My Collection Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Collection Report</li>
@endsection

@push('styles')
<style>
.payment-card {
    background: #fff;
    border: 1px solid #ddd;
    border-left: 4px solid #28a745;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.payment-card .payment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}
.payment-card .client-name {
    font-weight: 600;
    font-size: 14px;
    color: #333;
}
.payment-card .client-id {
    font-size: 11px;
    color: #666;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
}
.payment-card .payment-info {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}
.payment-card .payment-amount {
    font-size: 18px;
    font-weight: bold;
    color: #28a745;
}
.payment-card .payment-date {
    font-size: 11px;
    color: #888;
}
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
</style>
@endpush

@section('content')
<div class="row mb-3">
    <div class="col-4 mb-2">
        <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($totalCollection ?? 0, 0) }}</div>
            <div class="stat-label">Collection</div>
        </div>
    </div>
    <div class="col-4 mb-2">
        <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $payments->total() }}</div>
            <div class="stat-label">Payments</div>
        </div>
    </div>
    <div class="col-4 mb-2">
        <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ $payments->count() > 0 ? number_format($totalCollection / $payments->total(), 0) : 0 }}</div>
            <div class="stat-label">Average</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>My Collection</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-5 col-md-3">
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-5 col-md-3">
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-2 col-md-2">
                    <button type="submit" class="btn btn-info btn-sm btn-block">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </form>

        <!-- Mobile Cards View -->
        <div class="mobile-cards">
            @forelse($payments as $payment)
            <div class="payment-card">
                <div class="payment-header">
                    <div>
                        <div class="client-name">{{ $payment->client->name ?? 'N/A' }}</div>
                        <span class="client-id">{{ $payment->client->client_id ?? '-' }}</span>
                    </div>
                    <div class="text-right">
                        <div class="payment-amount">৳{{ number_format($payment->amount ?? 0, 0) }}</div>
                        <span class="badge badge-secondary">{{ ucfirst($payment->payment_method) }}</span>
                    </div>
                </div>
                <div class="payment-info">
                    <i class="fas fa-map-marker-alt"></i> {{ $payment->client->zone->name ?? '-' }}
                </div>
                <div class="payment-date">
                    <i class="fas fa-clock"></i> {{ $payment->created_at ? $payment->created_at->format('d-m-y h:i A') : '-' }}
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="fas fa-money-bill-wave fa-3x mb-2"></i>
                <p>No collection found</p>
            </div>
            @endforelse
        </div>

        <!-- Desktop Table View -->
        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-success text-white">
                        <tr>
                            <th>#</th>
                            <th>Date & Time</th>
                            <th>Client ID</th>
                            <th>Client Name</th>
                            <th>Zone</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $payment->created_at ? $payment->created_at->format('d-m-Y') : '-' }}</strong>
                                <br><small class="text-muted">{{ $payment->created_at ? $payment->created_at->format('h:i:s A') : '' }}</small>
                            </td>
                            <td><code>{{ $payment->client->client_id ?? '-' }}</code></td>
                            <td>{{ $payment->client->name ?? '-' }}</td>
                            <td>{{ $payment->client->zone->name ?? '-' }}</td>
                            <td class="text-success font-weight-bold">৳{{ number_format($payment->amount ?? 0, 0) }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No collection found for selected period</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="5" class="text-right">Total:</th>
                            <th colspan="2" class="text-success">৳{{ number_format($totalCollection ?? 0, 0) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{ $payments->withQueryString()->links() }}
    </div>
</div>
@endsection
