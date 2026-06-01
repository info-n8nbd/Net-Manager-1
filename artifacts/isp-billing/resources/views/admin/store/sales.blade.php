@extends('layouts.admin')
@section('title', 'Sales List')
@section('breadcrumb')<li class="breadcrumb-item active">Sales</li>@endsection

@section('content')
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $totalSales }}</h3><p>Total Sales</p></div>
            <div class="icon"><i class="fas fa-shopping-cart"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>৳{{ number_format($totalAmount, 2) }}</h3><p>Total Amount</p></div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner"><h3>৳{{ number_format($totalPaid, 2) }}</h3><p>Total Collected</p></div>
            <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3>৳{{ number_format($totalDue, 2) }}</h3><p>Total Due</p></div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Sales List</h3>
        <div class="card-tools">
            <a href="{{ route('admin.sales.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>New Sale</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search invoice, customer..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="customer_type" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        <option value="client" {{ request('customer_type') === 'client' ? 'selected' : '' }}>Client</option>
                        <option value="walk_in" {{ request('customer_type') === 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-info btn-sm btn-block"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead class="bg-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Items</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Due</th>
                        <th>Date</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Sold By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td><a href="{{ route('admin.sales.show', $sale) }}" class="font-weight-bold">{{ $sale->invoice_number }}</a></td>
                        <td>
                            @if($sale->customer_type === 'client' && $sale->client)
                                <i class="fas fa-user text-primary mr-1"></i>{{ $sale->client->name }}
                                @if($sale->client->client_id)<small class="text-muted">[{{ $sale->client->client_id }}]</small>@endif
                            @else
                                <i class="fas fa-walking text-secondary mr-1"></i>{{ $sale->customer_name ?: 'Walk-in Customer' }}
                                @if($sale->customer_phone)<br><small class="text-muted"><i class="fas fa-phone"></i> {{ $sale->customer_phone }}</small>@endif
                            @endif
                        </td>
                        <td>
                            @if($sale->customer_type === 'client')
                                <span class="badge badge-primary">Client</span>
                            @else
                                <span class="badge badge-secondary">Walk-in</span>
                            @endif
                        </td>
                        <td>
                            @foreach($sale->items as $si)
                                <span class="badge badge-light">{{ $si->product_name }}@if($si->brand || $si->model) <small>({{ implode(' ', array_filter([$si->brand, $si->model])) }})</small>@endif x{{ $si->quantity }}</span>
                            @endforeach
                        </td>
                        <td class="text-right">৳{{ number_format($sale->total, 2) }}</td>
                        <td class="text-right text-success">৳{{ number_format($sale->paid, 2) }}</td>
                        <td class="text-right {{ $sale->due > 0 ? 'text-danger font-weight-bold' : '' }}">৳{{ number_format($sale->due, 2) }}</td>
                        <td>{{ $sale->sale_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge badge-light">{{ ucfirst($sale->payment_method ?? 'cash') }}</span>
                            @if($sale->payment_reference)
                                <br><small class="text-muted"><i class="fas fa-hashtag"></i> {{ $sale->payment_reference }}</small>
                            @endif
                        </td>
                        <td>
                            @if($sale->status === 'paid')
                                <span class="badge badge-success">Paid</span>
                            @elseif($sale->status === 'partial')
                                <span class="badge badge-warning">Partial</span>
                            @else
                                <span class="badge badge-danger">Unpaid</span>
                            @endif
                        </td>
                        <td><small>{{ $sale->creator->name ?? '—' }}</small></td>
                        <td>
                            <a href="{{ route('admin.sales.show', $sale) }}" class="btn btn-info btn-xs" title="View"><i class="fas fa-eye"></i></a>
                            <form action="{{ route('admin.sales.destroy', $sale) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this sale? Stock will be restored.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            <i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i>
                            No sales found. <a href="{{ route('admin.sales.create') }}">Create your first sale</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
        <div class="mt-3 d-flex justify-content-center">{{ $sales->links() }}</div>
        @endif
    </div>
</div>
@endsection
