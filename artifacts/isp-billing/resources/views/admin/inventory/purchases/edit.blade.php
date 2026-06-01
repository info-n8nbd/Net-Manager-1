@extends('layouts.admin')

@section('title', 'Edit Purchase')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.purchases.index') }}">Purchases</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.inventory.purchases.update', $purchase->id) }}">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                                <th>Product</th>
                                <th style="width: 15%">Quantity</th>
                                <th style="width: 20%">Unit Price</th>
                                <th style="width: 20%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseItems as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->unit_price, 2) }}</td>
                                    <td>{{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Purchase Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label><i class="fas fa-truck mr-1"></i> Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" class="form-control @error('vendor_id') is-invalid @enderror" required>
                            <option value="">-- Select Vendor --</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('vendor_id', $purchase->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }} {{ $vendor->company ? '(' . $vendor->company . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt mr-1"></i> Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror"
                               value="{{ old('purchase_date', $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : '') }}" required>
                        @error('purchase_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-info-circle mr-1"></i> Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            @foreach(['pending', 'completed', 'received', 'cancelled', 'partial'] as $s)
                                <option value="{{ $s }}" {{ old('status', $purchase->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-1"></i> Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $purchase->notes) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Update Purchase
                    </button>
                    <a href="{{ route('admin.inventory.purchases.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
