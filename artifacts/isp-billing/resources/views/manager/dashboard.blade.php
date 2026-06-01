@extends('layouts.manager')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-3">
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $totalClients ?? 0 }}</div>
            <div class="stat-label">Total Clients</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $activeClients ?? 0 }}</div>
            <div class="stat-label">Active Clients</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($monthlyCollection ?? 0) }}</div>
            <div class="stat-label">Monthly Collection</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-danger text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($totalDue ?? 0) }}</div>
            <div class="stat-label">Total Due</div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $totalResellers ?? 0 }}</div>
            <div class="stat-label">Resellers</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-teal text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $totalPops ?? 0 }}</div>
            <div class="stat-label">POPs</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-olive text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $inactiveClients ?? 0 }}</div>
            <div class="stat-label">Inactive Clients</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-purple text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($todayCollection ?? 0) }}</div>
            <div class="stat-label">Today's Collection</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Payments</h3>
    </div>
    <div class="card-body table-responsive p-0" style="max-height: 400px;">
        <table class="table table-striped table-hover table-head-fixed">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Reseller</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentPayments ?? [] as $payment)
                <tr>
                    <td>{{ $payment->client->name ?? 'N/A' }}</td>
                    <td>{{ $payment->reseller->name ?? 'N/A' }}</td>
                    <td class="text-success">৳{{ number_format($payment->amount) }}</td>
                    <td>{{ ucfirst($payment->payment_method ?? 'cash') }}</td>
                    <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No payments yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection