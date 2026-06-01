@extends('layouts.admin')

@section('title', 'Manage Splitters')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.network.topology') }}">Network Topology</a></li>
<li class="breadcrumb-item active">Splitters</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Splitter</h3></div>
            <div class="card-body">
                <form action="{{ route('admin.network.splitters.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Office-1:8 Splitter">
                    </div>
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="1:2">1:2 Splitter</option>
                            <option value="1:4">1:4 Splitter</option>
                            <option value="1:8" selected>1:8 Splitter</option>
                            <option value="1:16">1:16 Splitter</option>
                            <option value="1:32">1:32 Splitter</option>
                            <option value="1:64">1:64 Splitter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>OLT Device <span class="text-danger">*</span></label>
                        <select name="olt_device_id" class="form-control" required>
                            <option value="">Select OLT</option>
                            @foreach($olts as $olt)
                            <option value="{{ $olt->id }}">{{ $olt->name }} ({{ $olt->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Parent Splitter (Optional)</label>
                        <select name="parent_splitter_id" class="form-control">
                            <option value="">None (Direct to OLT)</option>
                            @foreach($allSplitters as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Port Number</label>
                        <input type="number" name="port_number" class="form-control" min="1" placeholder="Port on parent device">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Physical location">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Add Splitter</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>Splitters List</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.network.topology') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-project-diagram mr-1"></i>View Diagram
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>OLT</th>
                                <th>Parent</th>
                                <th>Connections</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($splitters as $splitter)
                            <tr>
                                <td>
                                    <strong>{{ $splitter->name }}</strong>
                                    @if($splitter->location)
                                    <br><small class="text-muted">{{ $splitter->location }}</small>
                                    @endif
                                </td>
                                <td><span class="badge badge-purple">{{ $splitter->type }}</span></td>
                                <td>{{ $splitter->oltDevice->name ?? 'N/A' }}</td>
                                <td>{{ $splitter->parentSplitter->name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $splitter->used_ports }}/{{ $splitter->port_count }}</span>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-info" data-toggle="modal" data-target="#editModal{{ $splitter->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.network.splitters.destroy', $splitter) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this splitter?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No splitters added yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($splitters->hasPages())
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
        {{ $splitters->appends(request()->query())->links() }}
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

@foreach($splitters as $splitter)
<div class="modal fade" id="editModal{{ $splitter->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.network.splitters.update', $splitter) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit: {{ $splitter->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $splitter->name }}" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" class="form-control" required>
                            @foreach(['1:2','1:4','1:8','1:16','1:32','1:64'] as $t)
                            <option value="{{ $t }}" {{ $splitter->type == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>OLT Device</label>
                        <select name="olt_device_id" class="form-control" required>
                            @foreach($olts as $olt)
                            <option value="{{ $olt->id }}" {{ $splitter->olt_device_id == $olt->id ? 'selected' : '' }}>{{ $olt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Parent Splitter</label>
                        <select name="parent_splitter_id" class="form-control">
                            <option value="">None</option>
                            @foreach($allSplitters as $s)
                            @if($s->id != $splitter->id)
                            <option value="{{ $s->id }}" {{ $splitter->parent_splitter_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Port Number</label>
                        <input type="number" name="port_number" class="form-control" value="{{ $splitter->port_number }}">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" value="{{ $splitter->location }}">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2">{{ $splitter->notes }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('styles')
<style>.badge-purple { background: #8b5cf6; color: #fff; }</style>
@endpush
