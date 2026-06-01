@extends('layouts.admin')
@section('title', 'Bandwidth Categories')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type'=>'bandwidth']) }}">Bandwidth Reseller</a></li>
<li class="breadcrumb-item active">BW Categories</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-12">

        {{-- Category List --}}
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Bandwidth Categories</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCategoryModal">
                        <i class="fas fa-plus mr-1"></i>Add Category
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible m-3 mb-0">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible m-3 mb-0">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        {{ session('error') }}
                    </div>
                @endif
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Category Name</th>
                            <th style="width:120px;">Key</th>
                            <th style="width:80px;">Icon</th>
                            <th style="width:80px;">Color</th>
                            <th style="width:70px;">Order</th>
                            <th style="width:80px;">Type</th>
                            <th style="width:110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if(str_starts_with($cat->icon, 'img:'))
                                    <img src="{{ substr($cat->icon, 4) }}" alt="{{ $cat->label }}" height="18" style="vertical-align:middle;" class="mr-1">
                                @else
                                    <i class="{{ $cat->icon }} mr-1 text-{{ $cat->color }}"></i>
                                @endif
                                <strong>{{ $cat->label }}</strong>
                            </td>
                            <td><code>{{ $cat->key }}</code></td>
                            <td>
                                @if(str_starts_with($cat->icon, 'img:'))
                                    <img src="{{ substr($cat->icon, 4) }}" alt="logo" height="20" style="vertical-align:middle;">
                                @else
                                    <small><code>{{ $cat->icon }}</code></small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $cat->color }} px-2">{{ $cat->color }}</span>
                            </td>
                            <td class="text-center">{{ $cat->sort_order }}</td>
                            <td class="text-center">
                                @if($cat->is_system)
                                    <span class="badge badge-secondary">System</span>
                                @else
                                    <span class="badge badge-info">Custom</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-warning btn-xs mr-1"
                                    onclick="openEditModal({{ $cat->id }}, '{{ addslashes($cat->label) }}', '{{ $cat->key }}', '{{ $cat->icon }}', '{{ $cat->color }}', {{ $cat->sort_order }}, {{ $cat->is_system ? 'true' : 'false' }})"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if(!$cat->is_system)
                                <form action="{{ route('admin.bandwidth-categories.destroy', $cat) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Delete category &quot;{{ addslashes($cat->label) }}&quot;? Any custom allocations for this category will also be removed.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">No categories found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Info card --}}
        <div class="card card-outline card-info">
            <div class="card-body py-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1 text-info"></i>
                    <strong>System</strong> categories are built-in (International, GGC, FNA, CDN, BDIX, Cache) — you can edit their label/icon/color but cannot delete them.
                    <strong>Custom</strong> categories can be fully managed.
                    Category values (Mbps + Rate) are set per reseller via the <i class="fas fa-edit"></i> Edit Reseller form.
                </small>
            </div>
        </div>

    </div>
</div>

{{-- Add Category Modal --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form action="{{ route('admin.bandwidth-categories.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Add Bandwidth Category</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="form-group">
                        <label>Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="label" class="form-control" value="{{ old('label') }}" placeholder="e.g. P2P / Peering" required>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 99) }}" min="1" max="999">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Category</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Category Modal --}}
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form id="editCategoryForm" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Bandwidth Category</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="label" id="edit_label" class="form-control" required>
                    </div>
                    <div class="form-group" id="edit_key_group">
                        <label>Key</label>
                        <input type="text" name="key" id="edit_key" class="form-control">
                        <small class="text-muted">Lowercase letters, numbers, underscores only.</small>
                    </div>
                    <div class="form-group">
                        <label>Icon <span class="text-danger">*</span></label>
                        <input type="text" name="icon" id="edit_icon" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Color <span class="text-danger">*</span></label>
                        <select name="color" id="edit_color" class="form-control" required>
                            @foreach(['secondary','primary','info','success','warning','danger','dark'] as $c)
                            <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="1" max="999">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditModal(id, label, key, icon, color, sortOrder, isSystem) {
    document.getElementById('editCategoryForm').action = '/admin/bandwidth-categories/' + id;
    document.getElementById('edit_label').value     = label;
    document.getElementById('edit_key').value       = key;
    document.getElementById('edit_icon').value      = icon;
    document.getElementById('edit_sort_order').value = sortOrder;
    var colorSel = document.getElementById('edit_color');
    for (var i = 0; i < colorSel.options.length; i++) {
        colorSel.options[i].selected = (colorSel.options[i].value === color);
    }
    var keyGroup = document.getElementById('edit_key_group');
    if (isSystem) {
        keyGroup.style.display = 'none';
    } else {
        keyGroup.style.display = 'block';
    }
    $('#editCategoryModal').modal('show');
}
@if($errors->any())
$('#addCategoryModal').modal('show');
@endif
</script>
@endpush
@endsection
