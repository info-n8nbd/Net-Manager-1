@extends('layouts.admin')
@section('title', 'Sale #' . $sale->invoice_number)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.sales.index') }}">Sales</a></li>
<li class="breadcrumb-item active">{{ $sale->invoice_number }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Sale Invoice — {{ $sale->invoice_number }}</h3>
                <div class="card-tools">
                    <button onclick="window.print()" class="btn btn-default btn-sm"><i class="fas fa-print mr-1"></i>Print</button>
                    <form action="{{ route('admin.sales.destroy', $sale) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this sale? Stock will be restored.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash mr-1"></i>Delete</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted mb-2">Customer Info</h5>
                        @if($sale->customer_type === 'client' && $sale->client)
                            <p class="mb-1"><strong><i class="fas fa-user text-primary mr-1"></i>{{ $sale->client->name }}</strong></p>
                            @if($sale->client->client_id)<p class="mb-1 text-muted">Client ID: {{ $sale->client->client_id }}</p>@endif
                            @if($sale->client->phone)<p class="mb-1 text-muted"><i class="fas fa-phone mr-1"></i>{{ $sale->client->phone }}</p>@endif
                            @if($sale->client->address)<p class="mb-0 text-muted"><i class="fas fa-map-marker-alt mr-1"></i>{{ $sale->client->address }}</p>@endif
                        @else
                            <p class="mb-1"><strong><i class="fas fa-walking text-secondary mr-1"></i>{{ $sale->customer_name ?: 'Walk-in Customer' }}</strong></p>
                            @if($sale->customer_phone)<p class="mb-0 text-muted"><i class="fas fa-phone mr-1"></i>{{ $sale->customer_phone }}</p>@endif
                        @endif
                    </div>
                    <div class="col-md-6 text-right">
                        <h5 class="text-muted mb-2">Sale Details</h5>
                        <p class="mb-1">Date: <strong>{{ $sale->sale_date->format('d M Y') }}</strong></p>
                        <p class="mb-1">Payment: <strong>{{ ucfirst($sale->payment_method ?? 'N/A') }}</strong></p>
                        @if($sale->payment_reference)<p class="mb-1">{{ in_array($sale->payment_method, ['bkash','nagad','rocket','card']) ? 'Transaction ID' : ($sale->payment_method === 'bank' ? 'Account/Ref' : 'Receipt No') }}: <strong>{{ $sale->payment_reference }}</strong></p>@endif
                        @if($sale->creator)<p class="mb-1">Sold By: <strong>{{ $sale->creator->name }}</strong></p>@endif
                        <p class="mb-0">Status:
                            @if($sale->status === 'paid')
                                <span class="badge badge-success badge-lg">Paid</span>
                            @elseif($sale->status === 'partial')
                                <span class="badge badge-warning badge-lg">Partial</span>
                            @else
                                <span class="badge badge-danger badge-lg">Unpaid</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Brand / Model</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                                <th>Serial #</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $idx => $item)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <strong>{{ $item->product_name }}</strong>
                                    @if($item->product && $item->product->code)<br><small class="text-muted">{{ $item->product->code }}</small>@endif
                                </td>
                                <td>
                                    @if($item->brand || $item->model)
                                        {{ $item->brand }}@if($item->brand && $item->model) / @endif{{ $item->model }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">৳{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right">৳{{ number_format($item->total, 2) }}</td>
                                <td>{{ $item->serial_number ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right"><strong>Subtotal</strong></td>
                                <td class="text-right"><strong>৳{{ number_format($sale->subtotal, 2) }}</strong></td>
                                <td></td>
                            </tr>
                            @if($sale->discount > 0)
                            <tr>
                                <td colspan="5" class="text-right text-danger">Discount</td>
                                <td class="text-right text-danger">-৳{{ number_format($sale->discount, 2) }}</td>
                                <td></td>
                            </tr>
                            @endif
                            @if($sale->tax_amount > 0)
                            <tr>
                                <td colspan="5" class="text-right">Tax ({{ $sale->tax_percent }}%)</td>
                                <td class="text-right">+৳{{ number_format($sale->tax_amount, 2) }}</td>
                                <td></td>
                            </tr>
                            @endif
                            <tr class="bg-light">
                                <td colspan="5" class="text-right"><strong class="text-lg">Grand Total</strong></td>
                                <td class="text-right"><strong class="text-lg text-primary">৳{{ number_format($sale->total, 2) }}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($sale->notes)
                <div class="callout callout-info mt-3">
                    <h6><i class="fas fa-sticky-note mr-1"></i>Notes</h6>
                    <p class="mb-0">{{ $sale->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Payment Summary</h3></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Grand Total</td>
                        <td class="text-right"><strong>৳{{ number_format($sale->total, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Paid</td>
                        <td class="text-right text-success"><strong>৳{{ number_format($sale->paid, 2) }}</strong></td>
                    </tr>
                    <tr class="border-top">
                        <td><strong>Due Amount</strong></td>
                        <td class="text-right">
                            <strong class="{{ $sale->due > 0 ? 'text-danger' : 'text-success' }}">
                                ৳{{ number_format($sale->due, 2) }}
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if($sale->due > 0)
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-hand-holding-usd mr-2"></i>Record Payment</h3></div>
            <div class="card-body">
                <form action="{{ route('admin.sales.payment', $sale) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $sale->due }}" required placeholder="Payment amount">
                        <small class="text-muted">Max: ৳{{ number_format($sale->due, 2) }}</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check mr-1"></i>Record Payment</button>
                </form>
            </div>
        </div>
        @endif

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Info</h3></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Customer Type</td>
                        <td class="text-right">
                            @if($sale->customer_type === 'client')
                                <span class="badge badge-primary">Client</span>
                            @else
                                <span class="badge badge-secondary">Walk-in</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Items</td>
                        <td class="text-right">{{ $sale->items->sum('quantity') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created</td>
                        <td class="text-right">{{ $sale->created_at->format('d M Y h:i A') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
