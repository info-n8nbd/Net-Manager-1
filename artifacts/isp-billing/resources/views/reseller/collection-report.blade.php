@extends('layouts.reseller')

@section('title', 'Collection Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Collection Report</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Collection Report</h3>
        <div class="card-tools">
            <form class="form-inline" method="GET">
                <div class="input-group input-group-sm mr-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text">From</span>
                    </div>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="input-group input-group-sm mr-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text">To</span>
                    </div>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-6 mb-2">
                <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
                    <div class="stat-number">৳{{ number_format($totalCollection ?? 0, 0) }}</div>
                    <div class="stat-label">Collection</div>
                </div>
            </div>
            <div class="col-6 mb-2">
                <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $paymentCount }}</div>
                    <div class="stat-label">Payments</div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="text-right">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                        <td>
                            <a href="{{ route('reseller.clients.show', $payment->client) }}">
                                {{ $payment->client->name }}
                            </a>
                            <br><small class="text-muted">{{ $payment->client->client_id }}</small>
                        </td>
                        <td>{{ ucfirst($payment->payment_method) }}</td>
                        <td><code>{{ $payment->reference ?? 'N/A' }}</code></td>
                        <td class="text-right text-success">৳{{ number_format($payment->amount ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No payments in selected period</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <th colspan="4">Total</th>
                        <th class="text-right text-success">৳{{ number_format($totalCollection ?? 0, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
