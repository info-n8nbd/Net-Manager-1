@extends('layouts.admin')

@section('title', 'Edit Fiber Stock')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Fiber Stock: {{ $stock->fiber_code }}</h3>
    </div>
    <form method="POST" action="{{ route('admin.inventory.fiber.update', $stock->id) }}">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="alert alert-danger m-3">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" class="form-control" required>
                            <option value="">-- Select Vendor --</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('vendor_id', $stock->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Purchaser</label>
                        <select name="purchaser" class="form-control">
                            <option value="">-- Select Purchaser --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->name }}" {{ old('purchaser', $stock->purchaser) == $emp->name ? 'selected' : '' }}>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fiber Code <span class="text-danger">*</span></label>
                        <input type="text" name="fiber_code" class="form-control" value="{{ old('fiber_code', $stock->fiber_code) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fiber Type <span class="text-danger">*</span></label>
                        <select name="fiber_type" class="form-control" required>
                            <option value="Single Mode" {{ old('fiber_type', $stock->fiber_type) == 'Single Mode' ? 'selected' : '' }}>Single Mode</option>
                            <option value="Multi Mode" {{ old('fiber_type', $stock->fiber_type) == 'Multi Mode' ? 'selected' : '' }}>Multi Mode</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Core <span class="text-danger">*</span></label>
                        <input type="number" name="core" class="form-control" value="{{ old('core', $stock->core) }}" min="1" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Start Meter <span class="text-danger">*</span></label>
                        <input type="number" name="start_meter" class="form-control" value="{{ old('start_meter', $stock->start_meter) }}" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>End Meter <span class="text-danger">*</span></label>
                        <input type="number" name="end_meter" class="form-control" value="{{ old('end_meter', $stock->end_meter) }}" step="0.01" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Stock Date <span class="text-danger">*</span></label>
                        <input type="date" name="stock_date" class="form-control" value="{{ old('stock_date', $stock->stock_date ? \Carbon\Carbon::parse($stock->stock_date)->format('Y-m-d') : '') }}" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Brand Name</label>
                        <input type="text" name="brand_name" class="form-control" value="{{ old('brand_name', $stock->brand_name) }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Invoice No</label>
                        <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $stock->invoice_number) }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Per Meter Price (৳)</label>
                        <input type="number" name="per_meter_price" class="form-control" value="{{ old('per_meter_price', $stock->per_meter_price) }}" step="0.01" min="0">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Cost (৳)</label>
                        <input type="number" name="total_cost" class="form-control" value="{{ old('total_cost', $stock->total_cost) }}" step="0.01" min="0">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $stock->notes) }}</textarea>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Fiber Stock</button>
            <a href="{{ route('admin.inventory.fiber.index') }}" class="btn btn-secondary ml-2">Cancel</a>
        </div>
    </form>
</div>
@endsection
