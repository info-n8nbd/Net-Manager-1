@extends('layouts.card-distributor')

@section('title', 'Commissions')

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body text-center">
                <h5>Total Commission</h5>
                <h2 class="font-weight-bold">৳{{ number_format($totalCommission ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-success text-white">
            <div class="card-body text-center">
                <h5>This Month</h5>
                <h2 class="font-weight-bold">৳{{ number_format($thisMonthCommission ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-gradient-info text-white">
            <div class="card-body text-center">
                <h5>Commission Rate</h5>
                <h2 class="font-weight-bold">{{ $commissionRate ?? 0 }}%</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-percentage mr-2"></i>Commission History</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Card</th>
                        <th>Sale Amount</th>
                        <th>Commission</th>
                        <th>Rate</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions ?? [] as $commission)
                    <tr>
                        <td>{{ $commission->id }}</td>
                        <td><code>{{ $commission->card_number ?? 'N/A' }}</code></td>
                        <td>৳{{ number_format($commission->sale_amount ?? 0) }}</td>
                        <td class="text-success">৳{{ number_format($commission->amount, 2) }}</td>
                        <td>{{ $commission->rate ?? 0 }}%</td>
                        <td>{{ $commission->created_at->format('d-m-Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No commissions found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($commissions) && $commissions->hasPages())
        <div class="mt-3">
            {{ $commissions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection