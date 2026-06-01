@extends('layouts.admin')

@section('title', 'Minute Sales')

@section('content')
<div class="row">
    <div class="col-md-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($report['total_revenue'] ?? 0) }}</h3>
                <p>Total Revenue</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($report['minutes_sold'] ?? 0) }}</h3>
                <p>Minutes Sold</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-md-4 col-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($report['minutes_used'] ?? 0) }}</h3>
                <p>Minutes Used</p>
            </div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Minute Sales</h3>
        <a href="{{ route('admin.minute-sales.sell') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> New Sale
        </a>
    </div>
    <div class="card-body">
        <form method="GET" class="row mb-3">
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From Date">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To Date">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-control form-control-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>Used</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Package</th>
                        <th>Minutes (Used/Total)</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Purchased At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['sales'] ?? [] as $sale)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $sale->client->name ?? '-' }}</td>
                        <td>{{ $sale->package->name ?? '-' }}</td>
                        <td>{{ $sale->minutes_used ?? 0 }} / {{ $sale->total_minutes ?? 0 }}</td>
                        <td>৳{{ number_format($sale->amount, 2) }}</td>
                        <td>
                            @if($sale->status == 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($sale->status == 'expired')
                                <span class="badge badge-danger">Expired</span>
                            @elseif($sale->status == 'used')
                                <span class="badge badge-info">Used</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($sale->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $sale->created_at->format('d-m-y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No sales found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
