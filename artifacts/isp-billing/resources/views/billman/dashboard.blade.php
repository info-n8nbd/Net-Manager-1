@extends('layouts.billman')
@section('title', 'Dashboard')

@section('content')
<div class="search-hero">
    <h4 class="mb-1"><i class="fas fa-search mr-2"></i>Search Client to Collect Bill</h4>
    <p class="mb-3" style="opacity: 0.8; font-size: 14px;">Search by Name, Client ID, Phone, or Username</p>
    <div style="position: relative;">
        <input type="text" id="dashboardSearch" class="form-control form-control-lg" placeholder="Type client name, ID, or phone number..."
               style="font-size: 18px; padding: 15px 20px; border-radius: 12px; border: none;">
        <div id="dashboardSearchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 0 0 12px 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); z-index: 1050; max-height: 400px; overflow-y: auto;"></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($todayCollection) }}</h3>
                <p>Today's Collection ({{ $todayCount }})</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>৳{{ number_format($monthlyCollection) }}</h3>
                <p>This Month ({{ $monthlyCount }})</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>৳{{ number_format($myDueToAdmin) }}</h3>
                <p>Due to Admin</p>
            </div>
            <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>৳{{ number_format($totalSubmitted) }}</h3>
                <p>Total Submitted</p>
            </div>
            <div class="icon"><i class="fas fa-check-double"></i></div>
        </div>
    </div>
</div>

{{-- Pending Submissions alert --}}
@if(isset($pendingSubmissions) && $pendingSubmissions->count() > 0)
<div class="alert alert-warning py-2 px-3 mb-2 d-flex justify-content-between align-items-center" style="border-radius:6px;">
    <span><i class="fas fa-hourglass-half mr-1"></i> <strong>{{ number_format($pendingAmount ?? 0, 0) }}</strong> BDT submitted &mdash; Waiting for Admin Approval</span>
    <span class="badge badge-warning text-dark">{{ $pendingSubmissions->count() }} pending</span>
</div>
@endif

@php $availableDue = ($myDueToAdmin ?? 0) - ($pendingAmount ?? 0); @endphp
@if($availableDue > 0)
<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-2"></i>Submit Collection to Admin</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('billman.submit-to-admin') }}" method="POST" class="row align-items-end">
            @csrf
            <div class="col-md-4">
                <label>Amount to Submit</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text">BDT</span></div>
                    <input type="number" name="amount" class="form-control" max="{{ $availableDue }}" step="1" min="1" required>
                </div>
                <small class="text-muted">Available: BDT {{ number_format($availableDue) }}</small>
            </div>
            <div class="col-md-4">
                <label>Notes (optional)</label>
                <input type="text" name="notes" class="form-control" placeholder="Any notes...">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-warning btn-block" onclick="return confirm('Submit ' + this.form.amount.value + ' to admin?')">
                    <i class="fas fa-paper-plane mr-1"></i>Submit to Admin
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-2 text-success"></i>Today's Payments ({{ $todayPayments->count() }})</h3>
            </div>
            <div class="card-body p-0">
                @if($todayPayments->isEmpty())
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No payments collected today
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayPayments->take(10) as $payment)
                                <tr>
                                    <td>
                                        <a href="{{ route('billman.client.show', $payment->client_id) }}">
                                            {{ $payment->client->name ?? 'N/A' }}
                                        </a>
                                        <br><small class="text-muted">{{ $payment->client->client_id ?? '' }}</small>
                                    </td>
                                    <td class="text-success font-weight-bold">৳{{ number_format($payment->amount) }}</td>
                                    <td>{{ $payment->created_at->format('h:i A') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2 text-warning"></i>Unsubmitted Payments ({{ $unsubmittedPayments->count() }})</h3>
                @if($unsubmittedPayments->isNotEmpty())
                    <span class="badge badge-warning float-right">৳{{ number_format($unsubmittedPayments->sum('amount')) }}</span>
                @endif
            </div>
            <div class="card-body p-0">
                @if($unsubmittedPayments->isEmpty())
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                        All payments submitted
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unsubmittedPayments->take(10) as $payment)
                                <tr>
                                    <td>
                                        {{ $payment->client->name ?? 'N/A' }}
                                        <br><small class="text-muted">{{ $payment->client->client_id ?? '' }}</small>
                                    </td>
                                    <td class="text-warning font-weight-bold">৳{{ number_format($payment->amount) }}</td>
                                    <td>{{ $payment->created_at->format('d-m-Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
