@extends('layouts.admin')

@section('title', 'Knowledge Base')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-book mr-2"></i>Knowledge Base Categories</h4>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createCategoryModal">
            <i class="fas fa-plus mr-1"></i>New Category
        </button>
    </div>

    <div class="row">
        @forelse($categories as $category)
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-header text-center">
                    <i class="fas {{ $category->icon ?? 'fa-folder' }} fa-2x text-primary mb-2 d-block"></i>
                    <h5 class="card-title mb-0">{{ $category->name }}</h5>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">{{ $category->description ?? 'No description' }}</p>
                    <span class="badge badge-info badge-lg" style="font-size: 14px;">
                        {{ $category->articles_count ?? $category->articles->count() ?? 0 }} Articles
                    </span>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('admin.knowledge-base.articles', $category) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye mr-1"></i>View Articles
                    </a>
                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editCategoryModal{{ $category->id }}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form action="{{ route('admin.knowledge-base.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category and all its articles?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>No categories found. Create your first category to get started.
            </div>
        </div>
        @endforelse
    </div>
</div>

<div class="modal fade" id="createCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.knowledge-base.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary">
                    <h4 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Create Category</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Getting Started" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this category"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Icon (Font Awesome class)</label>
                        <input type="text" name="icon" class="form-control" placeholder="e.g., fa-book, fa-wifi, fa-tools" value="fa-folder">
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($categories as $category)
<div class="modal fade" id="editCategoryModal{{ $category->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.knowledge-base.categories.update', $category) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Category</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $category->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Icon (Font Awesome class)</label>
                        <input type="text" name="icon" class="form-control" value="{{ $category->icon ?? 'fa-folder' }}">
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ $category->sort_order ?? 0 }}" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
