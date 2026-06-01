@extends('layouts.admin')

@section('title', 'Purchase Details')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.purchases.index') }}">Purchases</a></li>
<li class="breadcrumb-item active">Details</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Purchase Items</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchase->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item->product->name ?? 'N/A' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td>{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="4" class="text-right"><strong>Total:</strong></td>
                            <td><strong>{{ number_format($purchase->total ?? $purchase->items->sum('total'), 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Purchase Info</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Vendor:</strong></td>
                        <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td>{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge badge-{{ $purchase->status === 'completed' || $purchase->status === 'received' ? 'success' : ($purchase->status === 'cancelled' ? 'danger' : 'warning') }}">
                                {{ ucfirst($purchase->status ?? 'pending') }}
                            </span>
                        </td>
                    </tr>
                    @if($purchase->notes)
                    <tr>
                        <td><strong>Notes:</strong></td>
                        <td>{{ $purchase->notes }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td>{{ $purchase->created_at ? $purchase->created_at->format('d M Y h:i A') : 'N/A' }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.inventory.purchases.edit', $purchase->id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
                <form action="{{ route('admin.inventory.purchases.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this purchase and all its serials? This cannot be undone!')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm ml-1"><i class="fas fa-trash mr-1"></i> Delete</button>
                </form>
                <a href="{{ route('admin.inventory.purchases.index') }}" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-arrow-left mr-1"></i> Back</a>
            </div>
        </div>
    </div>
</div>
@endsection
