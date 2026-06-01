@extends('layouts.admin')

@section('title', 'Ticket Categories')
@section('breadcrumb')
<li class="breadcrumb-item active">Categories</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Ticket Categories</h3></div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead><tr><th>Name</th><th>Subjects</th><th>Description</th><th>Status</th><th style="width:80px;">Action</th></tr></thead>
                    <tbody>
                        @forelse($categories as $cat)
                        <tr>
                            <td class="font-weight-bold">{{ $cat->name }}</td>
                            <td>
                                @if($cat->subjects && count($cat->subjects) > 0)
                                    @foreach($cat->subjects as $sub)
                                        <span class="badge badge-info mr-1 mb-1">{{ $sub }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $cat->description ?? '-' }}</td>
                            <td><span class="badge badge-{{ $cat->is_active ? 'success' : 'danger' }}">{{ $cat->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-cat-btn"
                                    data-id="{{ $cat->id }}"
                                    data-name="{{ $cat->name }}"
                                    data-description="{{ $cat->description }}"
                                    data-subjects="{{ json_encode($cat->subjects ?? []) }}"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.support.categories.destroy', $cat) }}" method="POST" style="display:inline;"
                                      onsubmit="return confirm('Delete category \"{{ addslashes($cat->name) }}\"? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No categories</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add Category</h3></div>
            <form action="{{ route('admin.support.categories.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="form-group">
                        <label>Subjects <small class="text-muted">(dropdown options for ticket creation)</small></label>
                        <div id="addSubjectsList">
                            <div class="input-group mb-2 subject-row">
                                <input type="text" name="subjects[]" class="form-control" placeholder="e.g. Internet Not Working">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-danger remove-subject-row" style="display:none;"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info" id="addSubjectBtn"><i class="fas fa-plus mr-1"></i>Add Another Subject</button>
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Add Category</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editCategoryForm">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Category</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Name <span class="text-danger">*</span></label><input type="text" name="name" id="editCatName" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" id="editCatDesc" class="form-control" rows="2"></textarea></div>
                    <div class="form-group">
                        <label>Subjects</label>
                        <div id="editSubjectsList"></div>
                        <button type="button" class="btn btn-sm btn-outline-info" id="editAddSubjectBtn"><i class="fas fa-plus mr-1"></i>Add Subject</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#addSubjectBtn').click(function() {
        var row = '<div class="input-group mb-2 subject-row">' +
            '<input type="text" name="subjects[]" class="form-control" placeholder="e.g. Speed Issue">' +
            '<div class="input-group-append"><button type="button" class="btn btn-danger remove-subject-row"><i class="fas fa-times"></i></button></div>' +
            '</div>';
        $('#addSubjectsList').append(row);
        $('#addSubjectsList .subject-row:first .remove-subject-row').show();
    });

    $(document).on('click', '.remove-subject-row', function() {
        var container = $(this).closest('#addSubjectsList, #editSubjectsList');
        $(this).closest('.subject-row').remove();
        if (container.find('.subject-row').length <= 1) {
            container.find('.remove-subject-row').hide();
        }
    });

    $('#editAddSubjectBtn').click(function() {
        var row = '<div class="input-group mb-2 subject-row">' +
            '<input type="text" name="subjects[]" class="form-control" placeholder="e.g. Speed Issue">' +
            '<div class="input-group-append"><button type="button" class="btn btn-danger remove-subject-row"><i class="fas fa-times"></i></button></div>' +
            '</div>';
        $('#editSubjectsList').append(row);
        $('#editSubjectsList .remove-subject-row').show();
    });

    $(document).on('click', '.edit-cat-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var desc = $(this).data('description') || '';
        var subjects = $(this).data('subjects') || [];

        $('#editCategoryForm').attr('action', '/admin/support/categories/' + id);
        $('#editCatName').val(name);
        $('#editCatDesc').val(desc);

        var list = $('#editSubjectsList');
        list.empty();
        if (subjects.length > 0) {
            subjects.forEach(function(sub, i) {
                var removeStyle = subjects.length <= 1 ? 'display:none;' : '';
                list.append('<div class="input-group mb-2 subject-row">' +
                    '<input type="text" name="subjects[]" class="form-control" value="' + $('<div>').text(sub).html() + '">' +
                    '<div class="input-group-append"><button type="button" class="btn btn-danger remove-subject-row" style="' + removeStyle + '"><i class="fas fa-times"></i></button></div>' +
                    '</div>');
            });
        } else {
            list.append('<div class="input-group mb-2 subject-row">' +
                '<input type="text" name="subjects[]" class="form-control" placeholder="e.g. Internet Not Working">' +
                '<div class="input-group-append"><button type="button" class="btn btn-danger remove-subject-row" style="display:none;"><i class="fas fa-times"></i></button></div>' +
                '</div>');
        }

        $('#editCategoryModal').modal('show');
    });
});
</script>
@endpush
