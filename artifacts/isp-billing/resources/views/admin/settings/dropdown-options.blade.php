@extends('layouts.admin')

@section('title', 'Dropdown Options')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Settings</a></li>
<li class="breadcrumb-item active">Dropdown Options</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($groups as $key => $group)
                    <li class="nav-item">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#tab-{{ $key }}" role="tab">
                            <i class="fas fa-list mr-1"></i> {{ $group['label'] }}
                            <span class="badge badge-primary badge-pill ml-1">{{ $group['options']->where('is_active', true)->count() }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    @foreach($groups as $key => $group)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $key }}" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card card-outline card-info mb-0">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-cogs mr-1"></i> {{ $group['label'] }}</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 5%;">#</th>
                                                    <th>Option Name</th>
                                                    <th style="width: 10%;" class="text-center">Status</th>
                                                    <th style="width: 20%;" class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="sortable-list" data-group="{{ $key }}">
                                                @forelse($group['options'] as $index => $option)
                                                <tr data-id="{{ $option->id }}" class="{{ !$option->is_active ? 'table-secondary' : '' }}">
                                                    <td class="text-muted drag-handle" style="cursor: grab;"><i class="fas fa-grip-vertical"></i></td>
                                                    <td>
                                                        <span class="option-label-display" data-id="{{ $option->id }}">{{ $option->label }}</span>
                                                        <div class="option-edit-form d-none" data-id="{{ $option->id }}">
                                                            <div class="input-group input-group-sm">
                                                                <input type="text" class="form-control edit-label-input" value="{{ $option->label }}" data-id="{{ $option->id }}">
                                                                <div class="input-group-append">
                                                                    <button type="button" class="btn btn-success save-edit-btn" data-id="{{ $option->id }}" title="Save"><i class="fas fa-check"></i></button>
                                                                    <button type="button" class="btn btn-secondary cancel-edit-btn" data-id="{{ $option->id }}" title="Cancel"><i class="fas fa-times"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <form action="{{ route('admin.settings.dropdown-options.toggle', $option) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-xs {{ $option->is_active ? 'btn-success' : 'btn-secondary' }}" title="{{ $option->is_active ? 'Active — Click to disable' : 'Disabled — Click to enable' }}">
                                                                {{ $option->is_active ? 'Active' : 'Disabled' }}
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-primary start-edit-btn" data-id="{{ $option->id }}" title="Rename">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form action="{{ route('admin.settings.dropdown-options.destroy', $option) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this option permanently?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No options defined.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-outline card-success">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-plus mr-1"></i> Add New Option</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('admin.settings.dropdown-options.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="group_name" value="{{ $key }}">
                                            <div class="form-group">
                                                <label style="font-weight: 600; font-size: 13px;">Option Name <span class="text-danger">*</span></label>
                                                <input type="text" name="label" class="form-control" placeholder="Enter option name..." required>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fas fa-plus mr-1"></i> Add Option
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="callout callout-info" style="font-size: 13px;">
                                    <h6><i class="fas fa-info-circle mr-1"></i> Tips</h6>
                                    <ul class="mb-0 pl-3">
                                        <li>Click <i class="fas fa-edit"></i> to rename an option</li>
                                        <li>Use <strong>Active/Disabled</strong> button to show/hide options</li>
                                        <li>Drag <i class="fas fa-grip-vertical"></i> to reorder</li>
                                        <li>Disabled options won't appear in dropdowns</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.start-edit-btn', function() {
    var id = $(this).data('id');
    $('.option-label-display[data-id="' + id + '"]').addClass('d-none');
    $('.option-edit-form[data-id="' + id + '"]').removeClass('d-none');
    $('.edit-label-input[data-id="' + id + '"]').focus().select();
});

$(document).on('click', '.cancel-edit-btn', function() {
    var id = $(this).data('id');
    $('.option-label-display[data-id="' + id + '"]').removeClass('d-none');
    $('.option-edit-form[data-id="' + id + '"]').addClass('d-none');
});

$(document).on('click', '.save-edit-btn', function() {
    var id = $(this).data('id');
    var newLabel = $('.edit-label-input[data-id="' + id + '"]').val().trim();
    if (!newLabel) return;

    var url = "{{ url('admin/settings/dropdown-options') }}/" + id;
    $.ajax({
        url: url,
        method: 'PUT',
        data: { _token: '{{ csrf_token() }}', label: newLabel },
        success: function(resp) {
            if (resp.success) {
                $('.option-label-display[data-id="' + id + '"]').text(resp.label).removeClass('d-none');
                $('.option-edit-form[data-id="' + id + '"]').addClass('d-none');
                toastr.success('Option renamed successfully.');
            }
        },
        error: function() {
            toastr.error('Failed to rename option.');
        }
    });
});

$(document).on('keypress', '.edit-label-input', function(e) {
    if (e.which === 13) {
        e.preventDefault();
        var id = $(this).data('id');
        $('.save-edit-btn[data-id="' + id + '"]').click();
    }
});

$(document).on('keydown', '.edit-label-input', function(e) {
    if (e.key === 'Escape') {
        var id = $(this).data('id');
        $('.cancel-edit-btn[data-id="' + id + '"]').click();
    }
});

var dragRow = null;
$(document).on('mousedown', '.drag-handle', function(e) {
    dragRow = $(this).closest('tr');
    dragRow.attr('draggable', 'true');
});
$(document).on('dragstart', 'tr[draggable]', function(e) {
    dragRow = $(this);
    $(this).css('opacity', '0.4');
    e.originalEvent.dataTransfer.effectAllowed = 'move';
});
$(document).on('dragover', '.sortable-list tr', function(e) {
    e.preventDefault();
    e.originalEvent.dataTransfer.dropEffect = 'move';
    $(this).css('border-top', '2px solid #007bff');
});
$(document).on('dragleave', '.sortable-list tr', function() {
    $(this).css('border-top', '');
});
$(document).on('drop', '.sortable-list tr', function(e) {
    e.preventDefault();
    $(this).css('border-top', '');
    if (dragRow && dragRow[0] !== this) {
        dragRow.insertBefore($(this));
        saveOrder(dragRow.closest('tbody'));
    }
});
$(document).on('dragend', 'tr[draggable]', function() {
    $(this).css('opacity', '').removeAttr('draggable');
    dragRow = null;
});

function saveOrder($tbody) {
    var ids = [];
    $tbody.find('tr[data-id]').each(function() {
        ids.push($(this).data('id'));
    });
    $.ajax({
        url: "{{ route('admin.settings.dropdown-options.reorder') }}",
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', ids: ids },
        success: function() {
            toastr.success('Order updated.');
        },
        error: function() {
            toastr.error('Failed to save order.');
        }
    });
}
</script>
@endpush
