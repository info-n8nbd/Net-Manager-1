@extends('layouts.admin')

@section('title', 'All Cards')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-credit-card mr-2"></i>All Cards</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>Used</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="disabled" {{ request('status') == 'disabled' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Batch</label>
                            <select name="batch_id" class="form-control">
                                <option value="">All Batches</option>
                                @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Card number..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Filter</button>
                                <a href="{{ route('admin.card-billing.cards') }}" class="btn btn-secondary"><i class="fas fa-undo mr-1"></i>Reset</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Card Number</th>
                        <th>Batch</th>
                        <th>Amount (৳)</th>
                        <th>Status</th>
                        <th>Used By</th>
                        <th>Used At</th>
                        <th>Expires At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $card)
                    <tr>
                        <td>{{ $loop->iteration + ($cards->currentPage() - 1) * $cards->perPage() }}</td>
                        <td><code>{{ $card->card_number }}</code></td>
                        <td>{{ $card->batch->name ?? 'N/A' }}</td>
                        <td>{{ number_format($card->batch->amount ?? 0, 2) }}</td>
                        <td>
                            @if($card->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($card->status === 'used')
                                <span class="badge badge-warning">Used</span>
                            @elseif($card->status === 'expired')
                                <span class="badge badge-danger">Expired</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($card->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $card->client->name ?? '-' }}</td>
                        <td>{{ $card->used_at ? $card->used_at->format('d-m-y H:i') : '-' }}</td>
                        <td>{{ $card->expires_at ? $card->expires_at->format('d-m-Y') : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No cards found</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $cards->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
