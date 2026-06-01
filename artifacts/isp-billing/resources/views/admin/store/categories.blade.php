@extends('layouts.admin')

@section('title', 'Product Categories')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.store.products') }}">Store</a></li>
<li class="breadcrumb-item active">Product Categories</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="row mb-3">
            <div class="col-6 col-md-3 mb-2">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $categories->total() }}</h3>
                        <p>Total Categories</p>
                    </div>
                    <div class="icon"><i class="fas fa-tags"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $categories->filter(fn($c) => $c->is_active)->count() }}</h3>
                        <p>Active</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $categories->filter(fn($c) => !$c->is_active)->count() }}</h3>
                        <p>Inactive</p>
                    </div>
                    <div class="icon"><i class="fas fa-pause-circle"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $categories->sum(fn($c) => $c->products_count ?? 0) }}</h3>
                        <p>Total Products</p>
                    </div>
                    <div class="icon"><i class="fas fa-box"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Product Categories</h3>
        <div class="card-tools">
            <div class="input-group input-group-sm mr-2 d-inline-flex" style="width: 250px;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search categories...">
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCategoryModal">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </div>
    </div>
    <div class="card-body table-responsive p-0 d-none d-md-block">
        <table class="table table-bordered table-hover" id="categoriesTable">
            <thead>
                <tr>
                    <th style="width: 50px">#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th style="width: 120px">Products</th>
                    <th style="width: 100px">Status</th>
                    <th style="width: 130px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr class="category-row" data-name="{{ strtolower($category->name) }}" data-desc="{{ strtolower($category->description ?? '') }}">
                    <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                    <td><strong>{{ $category->name }}</strong></td>
                    <td>{{ Str::limit($category->description, 60) ?? '-' }}</td>
                    <td>
                        <span class="badge badge-info">{{ $category->products_count ?? 0 }} Products</span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $category->is_active ? 'success' : 'danger' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editCategoryModal{{ $category->id }}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        @if(($category->products_count ?? 0) == 0)
                        <form action="{{ route('admin.store.categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @else
                        <button type="button" class="btn btn-sm btn-secondary" disabled title="Cannot delete - has {{ $category->products_count }} products">
                            <i class="fas fa-trash"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <i class="fas fa-folder-open fa-2x text-muted mb-2 d-block"></i>
                        No categories found. Click "Add Category" to create one.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-body d-md-none">
        @forelse($categories as $category)
        <div class="card mb-2 category-card" data-name="{{ strtolower($category->name) }}" data-desc="{{ strtolower($category->description ?? '') }}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1"><strong>{{ $category->name }}</strong></h6>
                        <p class="text-muted small mb-1">{{ Str::limit($category->description, 80) ?? 'No description' }}</p>
                        <span class="badge badge-info mr-1">{{ $category->products_count ?? 0 }} Products</span>
                        <span class="badge badge-{{ $category->is_active ? 'success' : 'danger' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editCategoryModal{{ $category->id }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        @if(($category->products_count ?? 0) == 0)
                        <form action="{{ route('admin.store.categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                        @else
                        <button type="button" class="btn btn-sm btn-secondary" disabled title="Has products">
                            <i class="fas fa-trash"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-4">
            <i class="fas fa-folder-open fa-2x text-muted mb-2 d-block"></i>
            <p>No categories found</p>
        </div>
        @endforelse
    </div>

    <div class="card-footer clearfix">
        {{ $categories->links() }}
    </div>
</div>

<div class="modal fade" id="addCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.store.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary">
                    <h4 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Add Category</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Enter category name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Enter description (optional)"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="add_is_active" name="is_active" checked>
                            <label class="custom-control-label" for="add_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($categories as $category)
<div class="modal fade" id="editCategoryModal{{ $category->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.store.categories.update', $category->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Category</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $category->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active_{{ $category->id }}" name="is_active" {{ $category->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="edit_is_active_{{ $category->id }}">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var query = this.value.toLowerCase();
            document.querySelectorAll('.category-row').forEach(function(row) {
                var name = row.getAttribute('data-name') || '';
                var desc = row.getAttribute('data-desc') || '';
                row.style.display = (name.indexOf(query) > -1 || desc.indexOf(query) > -1) ? '' : 'none';
            });
            document.querySelectorAll('.category-card').forEach(function(card) {
                var name = card.getAttribute('data-name') || '';
                var desc = card.getAttribute('data-desc') || '';
                card.style.display = (name.indexOf(query) > -1 || desc.indexOf(query) > -1) ? '' : 'none';
            });
        });
    }
});
</script>
@endpush
