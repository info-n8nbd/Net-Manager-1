@extends('layouts.admin')

@section('title', 'Batch Details')

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-credit-card"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Cards</span>
                    <span class="info-box-number">{{ $stats['total'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active Cards</span>
                    <span class="info-box-number">{{ $stats['active'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-check-double"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Used Cards</span>
                    <span class="info-box-number">{{ $stats['used'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-ban"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Expired</span>
                    <span class="info-box-number">{{ $stats['expired'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-layer-group mr-2"></i>Batch: {{ $batch->name }}
                @if($batch->is_active)
                    <span class="badge badge-success ml-2">Active</span>
                @else
                    <span class="badge badge-danger ml-2">Inactive</span>
                @endif
            </h3>
            <div class="card-tools">
                <a href="{{ route('admin.card-billing.batches') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Batches
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><strong>Package:</strong> {{ $batch->package->name ?? 'N/A' }}</div>
                <div class="col-md-3"><strong>Amount:</strong> ৳{{ number_format($batch->amount, 2) }}</div>
                <div class="col-md-3"><strong>Validity:</strong> {{ $batch->validity_days }} days</div>
                <div class="col-md-3"><strong>Expires:</strong> {{ $batch->expires_at ? $batch->expires_at->format('d-m-Y') : 'Never' }}</div>
            </div>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Card Number</th>
                        <th>PIN</th>
                        <th>Status</th>
                        <th>Used By</th>
                        <th>Used At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $card)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><code>{{ $card->card_number }}</code></td>
                        <td><code>{{ $card->pin }}</code></td>
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
                        <td>
                            @if($card->status === 'active')
                            <form action="{{ route('admin.card-billing.cards.disable', $card) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-warning" title="Disable"><i class="fas fa-ban"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">No cards in this batch</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if(method_exists($cards, 'links'))
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $cards->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script>
            @endif
        </div>
    </div>
</div>
@endsection
