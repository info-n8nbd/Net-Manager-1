@extends('layouts.admin')

@section('title', 'Purchase Details - ' . $purchase->purchase_no)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.store.products') }}">Store</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.store.purchases') }}">Purchases</a></li>
<li class="breadcrumb-item active">{{ $purchase->purchase_no }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Purchase Information</h3>
                <div class="card-tools">
                    @if($purchase->status == 'received')
                        <span class="badge badge-success p-2">Received</span>
                    @elseif($purchase->status == 'partial')
                        <span class="badge badge-warning p-2">Partial</span>
                    @else
                        <span class="badge badge-secondary p-2">Pending</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width:40%">PO Number:</th>
                                <td><strong>{{ $purchase->purchase_no }}</strong></td>
                            </tr>
                            <tr>
                                <th>Invoice Number:</th>
                                <td>{{ $purchase->invoice_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Purchase Date:</th>
                                <td>{{ $purchase->purchase_date ? $purchase->purchase_date->format('d M, Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $purchase->createdBy->name ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width:40%">Vendor:</th>
                                <td><strong>{{ $purchase->vendor->name ?? 'N/A' }}</strong></td>
                            </tr>
                            <tr>
                                <th>Company:</th>
                                <td>{{ $purchase->vendor->company ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>{{ $purchase->vendor->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $purchase->vendor->email ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @if($purchase->notes)
                <div class="mt-2">
                    <strong>Notes:</strong>
                    <p class="text-muted">{{ $purchase->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Purchase Items</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-right">Unit Price (৳)</th>
                                <th class="text-right">Total (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchase->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                    @if($item->product && ($item->product->brand || $item->product->model))
                                        <br><small class="text-muted">{{ $item->product->brand }}{{ $item->product->brand && $item->product->model ? ' / ' : '' }}{{ $item->product->model }}</small>
                                    @endif
                                    @if($item->product && $item->product->code)
                                        <br><small class="text-muted">{{ $item->product->code }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right">{{ number_format($item->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No items in this purchase</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-right">Subtotal:</th>
                                <th class="text-right">৳{{ number_format($purchase->subtotal, 2) }}</th>
                            </tr>
                            @if($purchase->discount > 0)
                            <tr>
                                <th colspan="4" class="text-right">Discount:</th>
                                <th class="text-right text-danger">- ৳{{ number_format($purchase->discount, 2) }}</th>
                            </tr>
                            @endif
                            @if($purchase->tax > 0)
                            <tr>
                                <th colspan="4" class="text-right">Tax:</th>
                                <th class="text-right">৳{{ number_format($purchase->tax, 2) }}</th>
                            </tr>
                            @endif
                            <tr class="bg-light">
                                <th colspan="4" class="text-right" style="font-size:16px;">Grand Total:</th>
                                <th class="text-right" style="font-size:16px;">৳{{ number_format($purchase->total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title text-white"><i class="fas fa-money-bill-wave mr-2"></i>Payment Summary</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-1">৳{{ number_format($purchase->total, 2) }}</h4>
                    <small class="text-muted">Total Amount</small>
                </div>
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="text-success mb-0">৳{{ number_format($purchase->paid, 2) }}</h5>
                        <small class="text-muted">Paid</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-danger mb-0">৳{{ number_format($purchase->due, 2) }}</h5>
                        <small class="text-muted">Due</small>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 8px;">
                    @php $paidPercent = $purchase->total > 0 ? ($purchase->paid / $purchase->total) * 100 : 0; @endphp
                    <div class="progress-bar bg-success" style="width: {{ $paidPercent }}%"></div>
                </div>
                <small class="text-muted">{{ number_format($paidPercent, 1) }}% Paid</small>
            </div>
        </div>

        @if($purchase->due > 0)
        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title text-white"><i class="fas fa-plus-circle mr-2"></i>Record Payment</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.store.purchases.payment', $purchase->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Amount (৳)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $purchase->due }}" value="{{ $purchase->due }}" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Payment reference or note"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check mr-1"></i> Record Payment
                    </button>
                </form>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Payment History</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-right">Amount (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $ledgerEntries = $purchase->vendor
                                    ? $purchase->vendor->ledgerEntries()
                                        ->where('reference', 'LIKE', '%' . $purchase->purchase_no . '%')
                                        ->orderBy('transaction_date', 'desc')
                                        ->get()
                                    : collect();
                            @endphp
                            @forelse($ledgerEntries as $entry)
                            <tr>
                                <td>{{ $entry->transaction_date ? $entry->transaction_date->format('d-m-Y') : '-' }}</td>
                                <td>{{ $entry->description }}</td>
                                <td class="text-right">
                                    @if($entry->debit > 0)
                                        <span class="text-success">৳{{ number_format($entry->debit, 2) }}</span>
                                    @else
                                        <span class="text-danger">৳{{ number_format($entry->credit, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No payment records</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.store.purchases') }}" class="btn btn-secondary btn-block">
            <i class="fas fa-arrow-left mr-1"></i> Back to Purchases
        </a>
    </div>
</div>
@endsection
