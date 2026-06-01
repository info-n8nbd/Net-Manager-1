@extends('layouts.admin')

@section('title', 'Vendor Purchase History')

@section('content')

        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid fa-address-card"></i> Vendor Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 80px; height: 80px; font-size: 32px;">
                                <i class="fa-solid fa-building"></i>
                            </div>
                        </div>
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th style="width: 40%"><i class="fa-solid fa-user"></i> Name</th>
                                <td>{{ $vendor->name }}</td>
                            </tr>
                            <tr>
                                <th><i class="fa-solid fa-building"></i> Company</th>
                                <td>{{ $vendor->company ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th><i class="fa-solid fa-phone"></i> Phone</th>
                                <td>{{ $vendor->phone ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th><i class="fa-solid fa-envelope"></i> Email</th>
                                <td>{{ $vendor->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th><i class="fa-solid fa-location-dot"></i> Address</th>
                                <td>{{ $vendor->address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th><i class="fa-solid fa-wallet"></i> Balance</th>
                                <td>
                                    <span class="badge badge-{{ ($vendor->balance ?? 0) >= 0 ? 'success' : 'danger' }}" style="font-size: 14px;">
                                        {{ number_format($vendor->balance ?? 0, 2) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid fa-cart-shopping"></i> Purchase History</h3>
                    </div>
                    <div class="card-body">
                        <table id="vendorPurchasesTable" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Purchase No</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $purchase)
                                <tr>
                                    <td>{{ $purchase->purchase_no ?? '#' . $purchase->id }}</td>
                                    <td>{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d-m-Y') : ($purchase->created_at ? $purchase->created_at->format('d-m-Y') : 'N/A') }}</td>
                                    <td>
                                        @if($purchase->items && $purchase->items->count())
                                            @foreach($purchase->items as $item)
                                                <span class="badge badge-light border">
                                                    {{ $item->product->name ?? 'N/A' }} x{{ $item->quantity }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="badge badge-secondary">No items</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ number_format($purchase->total ?? $purchase->total_amount ?? 0, 2) }}</strong></td>
                                    <td>
                                        @php
                                            $pStatus = $purchase->status ?? 'pending';
                                            $pBadge = match($pStatus) {
                                                'completed', 'received' => 'badge-success',
                                                'pending' => 'badge-warning',
                                                'cancelled' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $pBadge }}">{{ ucfirst($pStatus) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No purchases found for this vendor.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#vendorPurchasesTable tbody tr td[colspan]').length === 0) { $('#vendorPurchasesTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[1, "desc"]],
        "pageLength": 25
    }); }
});
</script>
@endpush
