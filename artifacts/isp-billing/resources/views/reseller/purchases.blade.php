@extends('layouts.reseller')
@section('title', 'Purchases')
@section('breadcrumb')<li class="breadcrumb-item active">Purchases</li>@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Purchase History</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice No</th>
                    <th>Vendor</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->purchase_date->format('d-m-Y') }}</td>
                    <td>{{ $purchase->invoice_no }}</td>
                    <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                    <td>৳{{ number_format($purchase->total_amount ?? 0, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $purchase->status == 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($purchase->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No purchase records found</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $purchases->links() }}
    </div>
</div>
@endsection
