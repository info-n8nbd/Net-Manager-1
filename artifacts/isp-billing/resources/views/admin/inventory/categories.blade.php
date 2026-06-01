@extends('layouts.admin')
@section('title', 'Item Categories')
@section('breadcrumb')<li class="breadcrumb-item active">Item Categories</li>@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title">Item Category Management</h3>
        <div class="card-tools d-flex align-items-center ml-auto">
            <div class="input-group input-group-sm mr-2" style="width:250px;">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" class="form-control form-control-sm table-quick-search" placeholder="Quick search..." data-table="categoriesTable">
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCategoryModal">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="categoriesTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Items Count</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td>{{ $category->id }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->description ?? '-' }}</td>
                    <td>{{ $category->products_count ?? 0 }}</td>
                    <td><span class="badge badge-{{ $category->is_active ? 'success' : 'danger' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-nowrap">
                        <button class="btn btn-warning btn-xs" title="Edit" onclick="openEditCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}', {{ $category->is_active ? 'true' : 'false' }})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('admin.inventory.categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No categories found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $categories->links() }}
    </div>
</div>

<div class="modal fade" id="addCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.inventory.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title">Add New Category</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h4 class="modal-title">Edit Category</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editCatName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="editCatDesc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="editCatActive" name="is_active">
                            <label class="custom-control-label" for="editCatActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('.table-quick-search').on('input', function() {
    var q = $(this).val().toLowerCase();
    var tableId = $(this).data('table');
    $('#' + tableId + ' tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        $(this).toggle(!q || text.indexOf(q) !== -1);
    });
});

function openEditCategory(id, name, description, isActive) {
    var url = "{{ route('admin.inventory.categories.update', ':id') }}".replace(':id', id);
    $('#editCategoryForm').attr('action', url);
    $('#editCatName').val(name);
    $('#editCatDesc').val(description);
    $('#editCatActive').prop('checked', isActive);
    $('#editCategoryModal').modal('show');
}
</script>
@endpush
