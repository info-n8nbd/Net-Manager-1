@extends('layouts.card-distributor')

@section('title', 'Cards')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-credit-card mr-2"></i>Cards Inventory</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('card-distributor.cards') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Sold</option>
                        <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>Used</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="batch" class="form-control">
                        <option value="">All Batches</option>
                        @foreach($batches ?? [] as $batch)
                        <option value="{{ $batch }}" {{ request('batch') == $batch ? 'selected' : '' }}>{{ $batch }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search card number..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Filter</button>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Card Number</th>
                        <th>Batch</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Sold Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards ?? [] as $card)
                    <tr>
                        <td><code>{{ $card->card_number }}</code></td>
                        <td>{{ $card->batch ?? 'N/A' }}</td>
                        <td>৳{{ number_format($card->value ?? 0) }}</td>
                        <td>
                            <span class="badge badge-{{ $card->status == 'available' ? 'success' : ($card->status == 'sold' ? 'info' : ($card->status == 'used' ? 'secondary' : 'danger')) }}">
                                {{ ucfirst($card->status) }}
                            </span>
                        </td>
                        <td>{{ $card->sold_at ? $card->sold_at->format('d-m-Y') : '-' }}</td>
                        <td>
                            <a href="{{ route('card-distributor.cards.show', $card) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No cards found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($cards) && $cards->hasPages())
        <div class="mt-3">
            {{ $cards->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection