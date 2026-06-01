@extends('layouts.sub-reseller')

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
            <div class="stat-number">৳{{ number_format($walletBalance ?? 0, 2) }}</div>
            <div class="stat-label">Wallet Balance</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-purple text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($totalCommission ?? 0, 2) }}</div>
            <div class="stat-label">Commission</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Payments</h3>
            </div>
            <div class="card-body table-responsive p-0" style="max-height: 350px;">
                <table class="table table-striped table-hover table-head-fixed">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments ?? [] as $payment)
                        <tr>
                            <td>{{ $payment->client->name ?? 'N/A' }}</td>
                            <td class="text-success">৳{{ number_format($payment->amount) }}</td>
                            <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No payments yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Recent Clients</h3>
            </div>
            <div class="card-body table-responsive p-0" style="max-height: 350px;">
                <table class="table table-striped table-hover table-head-fixed">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Package</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentClients ?? [] as $client)
                        <tr>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->package->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $client->status == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No clients yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection