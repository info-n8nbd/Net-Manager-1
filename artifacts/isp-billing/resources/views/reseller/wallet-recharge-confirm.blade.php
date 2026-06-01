@extends('layouts.reseller')

@section('title', 'Confirm Wallet Recharge')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('reseller.wallet') }}">Wallet</a></li>
<li class="breadcrumb-item active">Confirm Recharge</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-wallet mr-2"></i>Confirm Wallet Recharge</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                    <h5>Payment Initiated Successfully</h5>
                </div>

                <table class="table table-bordered">
                    <tr>
                        <td><strong>Transaction ID:</strong></td>
                        <td><code>{{ $transaction->transaction_id }}</code></td>
                    </tr>
                    <tr>
                        <td><strong>Amount:</strong></td>
                        <td><strong class="text-success">৳{{ number_format($transaction->amount, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Gateway:</strong></td>
                        <td>{{ ucfirst($transaction->gateway ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge badge-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'initiated' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td>{{ $transaction->created_at ? $transaction->created_at->format('d M Y h:i A') : 'N/A' }}</td>
                    </tr>
                </table>

                @if($transaction->status === 'initiated')
                <form method="POST" action="{{ route('reseller.wallet.recharge.demo-confirm', $transaction->transaction_id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-block mt-3">
                        <i class="fas fa-check mr-1"></i> Confirm Recharge
                    </button>
                </form>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('reseller.wallet') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Back to Wallet</a>
            </div>
        </div>
    </div>
</div>
@endsection
