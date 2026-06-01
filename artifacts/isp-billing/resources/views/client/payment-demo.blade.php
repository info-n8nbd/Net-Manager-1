@extends('layouts.client')

@section('title', 'Confirm Payment')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header text-center py-3" style="background: linear-gradient(135deg, #e2136e, #d91763);">
                <h4 class="text-white mb-0">
                    <i class="fas fa-mobile-alt mr-2"></i>bKash Payment
                </h4>
            </div>
            <div class="card-body text-center p-4">
                <div class="mb-4">
                    <div class="alert alert-info" style="font-size: 12px; border-radius: 10px;">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Demo Mode:</strong> This is a simulated payment. No real money will be charged.
                    </div>
                </div>

                <div class="mb-4">
                    <img src="https://www.bkash.com/sites/all/themes/flavor/logo.svg" alt="bKash" style="height: 60px; opacity: 0.9;">
                </div>

                <div class="payment-details mb-4 p-3" style="background: #f8f9fa; border-radius: 10px;">
                    <table class="table table-borderless mb-0" style="font-size: 14px;">
                        <tr>
                            <td class="text-muted text-left">Amount</td>
                            <td class="text-right"><strong style="font-size: 24px; color: #e2136e;">৳{{ number_format($transaction->amount ?? 0, 0) }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted text-left">Transaction ID</td>
                            <td class="text-right"><code>{{ $transaction->transaction_id }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted text-left">Bill Number</td>
                            <td class="text-right">{{ $bill->bill_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted text-left">Customer</td>
                            <td class="text-right">{{ $client->name }}</td>
                        </tr>
                    </table>
                </div>

                <form action="{{ route('payment.demo.process', $transaction->transaction_id) }}" method="POST">
                    @csrf
                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" name="action" value="confirm" class="btn btn-lg px-5" style="background: linear-gradient(135deg, #e2136e, #d91763); color: white; border-radius: 25px;">
                            <i class="fas fa-check mr-2"></i>Confirm Payment
                        </button>
                        <button type="submit" name="action" value="cancel" class="btn btn-lg btn-outline-secondary px-4" style="border-radius: 25px;">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                    </div>
                </form>

                <div class="mt-4">
                    <small class="text-muted">
                        <i class="fas fa-lock mr-1"></i>
                        Secure payment simulation
                    </small>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('client.pay-now') }}" class="btn btn-link text-muted">
                <i class="fas fa-arrow-left mr-1"></i>Back to Pay Now
            </a>
        </div>
    </div>
</div>
@endsection
