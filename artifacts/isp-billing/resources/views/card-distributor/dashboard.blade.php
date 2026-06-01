@extends('layouts.card-distributor')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-3">
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $totalCards ?? 0 }}</div>
            <div class="stat-label">Total Cards</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $soldCards ?? 0 }}</div>
            <div class="stat-label">Sold Cards</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
            <div class="stat-number">{{ $availableCards ?? 0 }}</div>
            <div class="stat-label">Available Cards</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-purple text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($totalCommission ?? 0, 2) }}</div>
            <div class="stat-label">Commission</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Transactions</h3>
    </div>
    <div class="card-body table-responsive p-0" style="max-height: 400px;">
        <table class="table table-striped table-hover table-head-fixed">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Batch</th>
                    <th>Amount</th>
                    <th>Buyer</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions ?? [] as $transaction)
                <tr>
                    <td>{{ $transaction->card_number ?? 'N/A' }}</td>
                    <td>{{ $transaction->batch ?? 'N/A' }}</td>
                    <td class="text-success">৳{{ number_format($transaction->amount ?? 0) }}</td>
                    <td>{{ $transaction->buyer_name ?? 'N/A' }}</td>
                    <td>{{ $transaction->created_at->format('d-m-y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No transactions yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection