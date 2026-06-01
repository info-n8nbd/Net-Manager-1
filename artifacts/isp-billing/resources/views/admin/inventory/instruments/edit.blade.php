@extends('layouts.admin')

@section('title', 'Edit Product - Inventory')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.instruments.index') }}">Products</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.inventory.instruments.update', $instrument->id) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Product Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="invoice_number"><i class="fas fa-file-invoice mr-1"></i> Invoice Number</label>
                            <input type="text" name="invoice_number" id="invoice_number" class="form-control" value="{{ old('invoice_number', $instrument->invoice_number) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="brand"><i class="fas fa-tag mr-1"></i> Brand</label>
                            <input type="text" name="brand" id="brand" class="form-control" value="{{ old('brand', $instrument->brand) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="model"><i class="fas fa-cube mr-1"></i> Model</label>
                            <input type="text" name="model" id="model" class="form-control" value="{{ old('model', $instrument->model) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="serial_number"><i class="fas fa-barcode mr-1"></i> Serial Number</label>
                            <input type="text" name="serial_number" id="serial_number" class="form-control" value="{{ old('serial_number', $instrument->serial_number) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="category"><i class="fas fa-folder mr-1"></i> Category</label>
                            <select name="category" id="editCategory" class="form-control">
                                <option value="">-- Select Category --</option>
                                @foreach($parentCategories as $pcat)
                                    <option value="{{ $pcat->name }}" {{ old('category', $instrument->category) == $pcat->name ? 'selected' : '' }}>{{ $pcat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group" id="editSubCatGroup" style="{{ $instrument->sub_category ? '' : 'display:none;' }}">
                            <label for="sub_category"><i class="fas fa-folder-open mr-1"></i> Type</label>
                            <select name="sub_category" id="editSubCategory" class="form-control">
                                <option value="">-- Select Type --</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="vendor_id"><i class="fas fa-truck mr-1"></i> Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-control">
                                <option value="">-- Select Vendor --</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id', $instrument->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 form-group">
                            <label for="notes"><i class="fas fa-sticky-note mr-1"></i> Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes', $instrument->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Status & Pricing</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="quantity"><i class="fas fa-boxes mr-1"></i> Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="{{ old('quantity', $instrument->quantity ?? 1) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="purchase_price"><i class="fas fa-money-bill mr-1"></i> Purchase Price (৳)</label>
                        <input type="number" name="purchase_price" id="purchase_price" class="form-control" step="0.01" min="0" value="{{ old('purchase_price', $instrument->purchase_price) }}">
                    </div>
                    <div class="form-group">
                        <label for="purchase_date"><i class="fas fa-calendar mr-1"></i> Purchase Date</label>
                        <input type="date" name="purchase_date" id="purchase_date" class="form-control" value="{{ old('purchase_date', optional($instrument->purchase_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label for="warranty_expiry"><i class="fas fa-shield-alt mr-1"></i> Warranty Expiry</label>
                        <input type="date" name="warranty_expiry" id="warranty_expiry" class="form-control" value="{{ old('warranty_expiry', optional($instrument->warranty_expiry)->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label for="condition"><i class="fas fa-heartbeat mr-1"></i> Condition <span class="text-danger">*</span></label>
                        <select name="condition" id="condition" class="form-control" required>
                            <option value="new" {{ old('condition', $instrument->condition) == 'new' ? 'selected' : '' }}>Brand New</option>
                            <option value="used" {{ old('condition', $instrument->condition) == 'used' ? 'selected' : '' }}>Used</option>
                            <option value="replaced" {{ in_array(old('condition', $instrument->condition), ['replaced', 'replace']) ? 'selected' : '' }}>Replaced</option>
                            <option value="damaged" {{ in_array(old('condition', $instrument->condition), ['damaged', 'dead']) ? 'selected' : '' }}>Damage/Dead</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status"><i class="fas fa-toggle-on mr-1"></i> Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="available" {{ old('status', $instrument->status) == 'available' ? 'selected' : '' }}>Available</option>
                            <option value="in_use" {{ old('status', $instrument->status) == 'in_use' ? 'selected' : '' }}>In Use</option>
                            <option value="under_repair" {{ old('status', $instrument->status) == 'under_repair' ? 'selected' : '' }}>Under Repair</option>
                            <option value="retired" {{ old('status', $instrument->status) == 'retired' ? 'selected' : '' }}>Retired</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="assigned_to"><i class="fas fa-user mr-1"></i> Assigned To</label>
                        <input type="text" name="assigned_to" id="assigned_to" class="form-control" value="{{ old('assigned_to', $instrument->assigned_to) }}" placeholder="Person / Team name">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i> Update Product</button>
                    <a href="{{ route('admin.inventory.instruments.index') }}" class="btn btn-secondary btn-block"><i class="fas fa-arrow-left mr-1"></i> Back to List</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
var subCatData = @json($parentCategories->load('children')->mapWithKeys(function($p) { return [$p->name => $p->children->map(function($c) { return ['id' => $c->id, 'name' => $c->name]; })]; }));
var currentSubCategory = @json(old('sub_category', $instrument->sub_category));

function loadEditSubCategories(catName) {
    var $sub = $('#editSubCategory');
    var $group = $('#editSubCatGroup');
    $sub.html('<option value="">-- Select Type --</option>');

    if (!catName || !subCatData[catName] || subCatData[catName].length === 0) {
        $group.hide();
        return;
    }

    subCatData[catName].forEach(function(sc) {
        var sel = (currentSubCategory && sc.name === currentSubCategory) ? ' selected' : '';
        $sub.append('<option value="' + sc.name + '"' + sel + '>' + sc.name + '</option>');
    });
    $group.show();
}

$(document).ready(function() {
    var initialCat = $('#editCategory').val();
    if (initialCat) loadEditSubCategories(initialCat);

    $('#editCategory').on('change', function() {
        currentSubCategory = null;
        loadEditSubCategories($(this).val());
    });
});
</script>
@endpush
