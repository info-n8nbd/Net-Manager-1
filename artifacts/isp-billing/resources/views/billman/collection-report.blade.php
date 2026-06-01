@extends('layouts.billman')
@section('title', 'My Collection')
@section('breadcrumb')
<li class="breadcrumb-item active">Collection Report</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4">
                <label>Start Date</label>
                <input type="text" name="start_date" class="form-control flatpickr-date" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label>End Date</label>
                <input type="text" name="end_date" class="form-control flatpickr-date" value="{{ $endDate }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($totalCollection) }}</h3>
                <p>Total Collection</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalCount }}</h3>
                <p>Total Payments</p>
            </div>
            <div class="icon"><i class="fas fa-receipt"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Payment List</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-sm mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $i => $payment)
                    <tr>
                        <td>{{ $payments->firstItem() + $i }}</td>
                        <td>
                            <a href="{{ route('billman.client.show', $payment->client_id) }}">
                                {{ $payment->client->name ?? 'N/A' }}
                            </a>
                            <br><small class="text-muted">{{ $payment->client->client_id ?? '' }} | {{ $payment->client->zone->name ?? '' }}</small>
                        </td>
                        <td class="text-success font-weight-bold">৳{{ number_format($payment->amount) }}</td>
                        <td>{{ $payment->paymentMethod->name ?? 'Cash' }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d-m-y h:i A') }}</td>
                        <td>
                            <a href="{{ route('billman.payments.receipt', $payment) }}" class="btn btn-xs btn-outline-primary">
                                <i class="fas fa-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted p-3">No payments found in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
    <div class="card-footer">
        {{ $payments->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
