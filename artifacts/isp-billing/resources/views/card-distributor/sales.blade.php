@extends('layouts.card-distributor')

@section('title', 'Sales')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Sales Transactions</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('card-distributor.sales') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> Filter</button>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Card Number</th>
                        <th>Buyer</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales ?? [] as $sale)
                    <tr>
                        <td>{{ $sale->id }}</td>
                        <td><code>{{ $sale->card_number ?? 'N/A' }}</code></td>
                        <td>{{ $sale->buyer_name ?? 'N/A' }}</td>
                        <td class="text-success">৳{{ number_format($sale->amount ?? 0) }}</td>
                        <td class="text-info">৳{{ number_format($sale->commission ?? 0, 2) }}</td>
                        <td>{{ $sale->created_at->format('d-m-y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No sales found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($sales) && $sales->hasPages())
        <div class="mt-3">
            {{ $sales->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection