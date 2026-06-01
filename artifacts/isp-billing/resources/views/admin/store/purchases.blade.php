@extends('layouts.admin')

@section('title', 'Purchases')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.store.products') }}">Store</a></li>
<li class="breadcrumb-item active">Purchases</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $purchases->total() }}</h3>
                <p>Total Purchases</p>
            </div>
            <div class="icon"><i class="fas fa-shopping-cart"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($totalAmount ?? 0, 2) }}</h3>
                <p>Total Amount</p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>৳{{ number_format($totalPaid ?? 0, 2) }}</h3>
                <p>Total Paid</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>৳{{ number_format($totalDue ?? 0, 2) }}</h3>
                <p>Total Due</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Purchase Orders</h3>
        <div class="card-tools">
            <a href="{{ route('admin.purchase.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Purchase
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.store.purchases') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <select name="vendor_id" class="form-control">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_from" class="form-control" placeholder="From Date" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_to" class="form-control" placeholder="To Date" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('admin.store.purchases') }}" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead>
                    <tr>
                        <th>PO No</th>
                        <th>Invoice</th>
                        <th>Vendor</th>
                        <th>Date</th>
                        <th class="text-center">Items</th>
                        <th class="text-right">Total (৳)</th>
                        <th class="text-right">Paid (৳)</th>
                        <th class="text-right">Due (৳)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                    <tr>
                        <td><strong>{{ $purchase->purchase_no }}</strong></td>
                        <td>{{ $purchase->invoice_number ?? '-' }}</td>
                        <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                        <td>{{ $purchase->purchase_date ? $purchase->purchase_date->format('d-m-Y') : '-' }}</td>
                        <td class="text-center">
                            <span class="badge badge-info">{{ $purchase->items_count ?? $purchase->items->count() }}</span>
                        </td>
                        <td class="text-right">{{ number_format($purchase->total, 2) }}</td>
                        <td class="text-right text-success">{{ number_format($purchase->paid, 2) }}</td>
                        <td class="text-right {{ $purchase->due > 0 ? 'text-danger font-weight-bold' : '' }}">{{ number_format($purchase->due, 2) }}</td>
                        <td class="text-center">
                            @if($purchase->status == 'received')
                                <span class="badge badge-success">Received</span>
                            @elseif($purchase->status == 'partial')
                                <span class="badge badge-warning">Partial</span>
                            @else
                                <span class="badge badge-secondary">Pending</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.store.purchases.show', $purchase->id) }}" class="btn btn-sm btn-info" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form action="{{ route('admin.store.purchases.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this purchase?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No purchases found. <a href="{{ route('admin.purchase.create') }}">Create your first purchase</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $purchases->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
