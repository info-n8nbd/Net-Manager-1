@extends('layouts.admin')

@section('title', 'Purchases')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Purchases</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Purchase List</h3>
        <div class="card-tools">
            @if($__canAction('inventory', 'create_purchase'))
            <a href="{{ route('admin.inventory.purchases.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> New Purchase
            </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <table id="purchasesTable" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>Purchase No</th>
                    <th>Vendor</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Subtotal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->purchase_no ?? '#' . $purchase->id }}</td>
                    <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                    <td>{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d-m-Y') : ($purchase->created_at ? $purchase->created_at->format('d-m-Y') : 'N/A') }}</td>
                    <td>
                        @if($purchase->items && $purchase->items->count())
                            <span class="badge badge-info">{{ $purchase->items->count() }} item(s)</span>
                        @else
                            <span class="badge badge-secondary">0</span>
                        @endif
                    </td>
                    <td>{{ number_format($purchase->subtotal ?? 0, 2) }}</td>
                    <td><strong>{{ number_format($purchase->total ?? $purchase->total_amount ?? 0, 2) }}</strong></td>
                    <td>
                        @php
                            $status = $purchase->status ?? 'pending';
                            $badgeClass = match($status) {
                                'completed', 'received' => 'badge-success',
                                'pending' => 'badge-warning',
                                'cancelled' => 'badge-danger',
                                'partial' => 'badge-info',
                                default => 'badge-secondary'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            @if(Route::has('admin.inventory.purchases.show'))
                            <a href="{{ route('admin.inventory.purchases.show', $purchase->id) }}" class="btn btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            @endif
                            @if($__canAction('inventory', 'edit_purchase'))
                            @if(Route::has('admin.inventory.purchases.edit'))
                            <a href="{{ route('admin.inventory.purchases.edit', $purchase->id) }}" class="btn btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            @endif
                            @if($__canAction('inventory', 'delete_purchase'))
                            @if(Route::has('admin.inventory.purchases.destroy'))
                            <form action="{{ route('admin.inventory.purchases.destroy', $purchase->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this purchase?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No purchases found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#purchasesTable tbody tr td[colspan]').length === 0) { $('#purchasesTable').DataTable({
        "autoWidth": false,
        "order": [[2, "desc"]],
        "pageLength": 25
    }); }
});
</script>
@endpush
