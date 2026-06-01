@extends('layouts.admin')

@section('title', 'Edit Product - Inventory')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.products.index') }}">Products</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.inventory.products.update', $product->id) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Product Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required placeholder="Enter product name">
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Product Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $product->code) }}" required placeholder="e.g. PRD-001">
                                @error('code')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="ONU" {{ old('type', $product->type) == 'ONU' ? 'selected' : '' }}>ONU</option>
                                    <option value="Router" {{ old('type', $product->type) == 'Router' ? 'selected' : '' }}>Router</option>
                                    <option value="Switch" {{ old('type', $product->type) == 'Switch' ? 'selected' : '' }}>Switch</option>
                                    <option value="Media Converter" {{ old('type', $product->type) == 'Media Converter' ? 'selected' : '' }}>Media Converter</option>
                                    <option value="Instrument" {{ old('type', $product->type) == 'Instrument' ? 'selected' : '' }}>Instrument</option>
                                    <option value="Fiber" {{ old('type', $product->type) == 'Fiber' ? 'selected' : '' }}>Fiber</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="brand">Brand</label>
                                <input type="text" name="brand" id="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand', $product->brand) }}" placeholder="e.g. TP-Link">
                                @error('brand')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model', $product->model) }}" placeholder="e.g. TL-WR840N">
                                @error('model')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Enter product description">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Pricing & Stock</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="unit">Unit <span class="text-danger">*</span></label>
                        <select name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror" required>
                            <option value="pcs" {{ old('unit', $product->unit) == 'pcs' ? 'selected' : '' }}>Pcs</option>
                            <option value="meter" {{ old('unit', $product->unit) == 'meter' ? 'selected' : '' }}>Meter</option>
                        </select>
                        @error('unit')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="purchase_price">Purchase Price</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">৳</span>
                            </div>
                            <input type="number" name="purchase_price" id="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price', $product->purchase_price ?? '0') }}" step="0.01" min="0">
                        </div>
                        @error('purchase_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="selling_price">Selling Price</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">৳</span>
                            </div>
                            <input type="number" name="selling_price" id="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', $product->selling_price ?? '0') }}" step="0.01" min="0">
                        </div>
                        @error('selling_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="alert_quantity">Alert Quantity</label>
                        <input type="number" name="alert_quantity" id="alert_quantity" class="form-control @error('alert_quantity') is-invalid @enderror" value="{{ old('alert_quantity', $product->alert_quantity ?? '5') }}" min="0">
                        @error('alert_quantity')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted">Low stock warning threshold</small>
                    </div>

                    <div class="form-group">
                        <label for="warranty_months">Warranty (Months)</label>
                        <input type="number" name="warranty_months" id="warranty_months" class="form-control @error('warranty_months') is-invalid @enderror" value="{{ old('warranty_months', $product->warranty_months ?? '0') }}" min="0">
                        @error('warranty_months')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="barcode_prefix">Barcode Prefix</label>
                        <input type="text" name="barcode_prefix" id="barcode_prefix" class="form-control @error('barcode_prefix') is-invalid @enderror" value="{{ old('barcode_prefix', $product->barcode_prefix) }}" placeholder="e.g. PRD">
                        @error('barcode_prefix')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="has_serial" id="has_serial" class="custom-control-input" value="1" {{ old('has_serial', $product->has_serial) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="has_serial">
                                <i class="fas fa-barcode mr-1"></i> Enable Serial Tracking
                            </label>
                        </div>
                        <small class="text-muted">Track individual items by serial number</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-2"></i>Update Product
                    </button>
                    <a href="{{ route('admin.inventory.products.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left mr-2"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
