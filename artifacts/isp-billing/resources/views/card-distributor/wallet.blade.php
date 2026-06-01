@extends('layouts.card-distributor')

@section('title', 'Wallet')

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-gradient-success text-white">
            <div class="card-body text-center">
                <h5>Current Balance</h5>
                <h2 class="font-weight-bold">৳{{ number_format($walletBalance ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-info text-white">
            <div class="card-body text-center">
                <h5>Total Earnings</h5>
                <h2 class="font-weight-bold">৳{{ number_format($totalEarnings ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-warning text-dark">
            <div class="card-body text-center">
                <h5>Total Withdrawals</h5>
                <h2 class="font-weight-bold">৳{{ number_format($totalWithdrawals ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Transaction History</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions ?? [] as $transaction)
                    <tr>
                        <td>{{ $transaction->id }}</td>
                        <td>
                            <span class="badge badge-{{ $transaction->type == 'credit' ? 'success' : 'danger' }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </td>
                        <td class="{{ $transaction->type == 'credit' ? 'text-success' : 'text-danger' }}">
                            {{ $transaction->type == 'credit' ? '+' : '-' }}৳{{ number_format($transaction->amount, 2) }}
                        </td>
                        <td>{{ $transaction->description ?? 'N/A' }}</td>
                        <td>{{ $transaction->created_at->format('d-m-y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No transactions found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($transactions) && $transactions->hasPages())
        <div class="mt-3">
            {{ $transactions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection