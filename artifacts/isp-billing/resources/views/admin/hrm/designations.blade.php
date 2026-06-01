@extends('layouts.admin')

@section('title', 'Designations')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.employees') }}">HRM</a></li>
<li class="breadcrumb-item active">Designations</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Designation</h3>
            </div>
            <form action="{{ route('admin.hrm.designations.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Designation Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Manager, Technician">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id" class="form-control">
                            <option value="">-- No Department --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Save Designation</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>All Designations</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Employees</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($designations as $desig)
                        <tr>
                            <td>{{ $loop->iteration + ($designations->currentPage() - 1) * $designations->perPage() }}</td>
                            <td>{{ $desig->name }}</td>
                            <td>{{ $desig->department->name ?? '—' }}</td>
                            <td><span class="badge badge-info">{{ $desig->employees_count }}</span></td>
                            <td>
                                <span class="badge badge-{{ $desig->is_active ? 'success' : 'secondary' }}">
                                    {{ $desig->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editModal{{ $desig->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                                @if($desig->employees_count == 0)
                                <form action="{{ route('admin.hrm.designations.destroy', $desig) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this designation?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No designations found. Add one using the form on the left.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($designations->hasPages())
            <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $designations->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script></div>
            @endif
        </div>
    </div>
</div>

@foreach($designations as $desig)
<div class="modal fade" id="editModal{{ $desig->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.hrm.designations.update', $desig) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white"><i class="fas fa-edit mr-1"></i>Edit Designation</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Designation Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $desig->name }}" required>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id" class="form-control">
                            <option value="">-- No Department --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $desig->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $desig->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active_{{ $desig->id }}" name="is_active" {{ $desig->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="edit_is_active_{{ $desig->id }}">Active</label>
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
