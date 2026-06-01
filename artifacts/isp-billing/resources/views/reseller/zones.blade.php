@extends('layouts.reseller')
@section('title', 'Zone Management')
@section('breadcrumb')<li class="breadcrumb-item active">Zones</li>@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Zones, Subzones & TJs</h3>
            </div>
            <div class="card-body">
                @forelse($zones as $zone)
                <div class="card mb-3" style="border-left: 4px solid #1e3c72;">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <div>
                            <strong><i class="fas fa-map mr-1"></i>{{ $zone->name }}</strong>
                            <span class="badge badge-{{ $zone->is_active ? 'success' : 'secondary' }} ml-2">{{ $zone->is_active ? 'Active' : 'Inactive' }}</span>
                            <span class="badge badge-info">{{ $zone->clients_count }} Clients</span>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-toggle="modal" data-target="#editZoneModal{{ $zone->id }}"><i class="fas fa-edit"></i></button>
                            <form action="{{ route('reseller.zones.delete', $zone) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this zone?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        @if($zone->areas && $zone->areas->count() > 0)
                        <div class="ml-3">
                            @foreach($zone->areas as $area)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <i class="fas fa-layer-group text-info mr-1"></i>{{ $area->name }}
                                    @if($area->boxes && $area->boxes->count() > 0)
                                    <small class="text-muted">({{ $area->boxes->count() }} TJs)</small>
                                    @endif
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#editAreaModal{{ $area->id }}"><i class="fas fa-edit"></i></button>
                                    <form action="{{ route('reseller.subzones.delete', $area) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this subzone?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            @if($area->boxes && $area->boxes->count() > 0)
                            <div class="ml-4 mb-2">
                                @foreach($area->boxes as $box)
                                <span class="badge badge-secondary mr-1 mb-1">
                                    <i class="fas fa-box mr-1"></i>{{ $box->name }}
                                    <form action="{{ route('reseller.tjs.delete', $box) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this TJ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs text-white p-0 ml-1" style="background:none;border:none;"><i class="fas fa-times"></i></button>
                                    </form>
                                </span>
                                @endforeach
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted mb-0 ml-3"><small>No subzones yet</small></p>
                        @endif
                    </div>
                </div>
                
                <div class="modal fade" id="editZoneModal{{ $zone->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('reseller.zones.update', $zone) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title">Edit Zone</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                                <div class="modal-body">
                                    <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $zone->name }}" required></div>
                                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2">{{ $zone->description }}</textarea></div>
                                    <div class="form-check"><input type="checkbox" name="is_active" class="form-check-input" {{ $zone->is_active ? 'checked' : '' }}><label class="form-check-label">Active</label></div>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
                            </div>
                        </form>
                    </div>
                </div>
                
                @foreach($zone->areas as $area)
                <div class="modal fade" id="editAreaModal{{ $area->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('reseller.subzones.update', $area) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title">Edit Subzone</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                                <div class="modal-body">
                                    <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="{{ $area->name }}" required></div>
                                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2">{{ $area->description }}</textarea></div>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
                @empty
                <div class="text-center py-4">
                    <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No zones yet. Create one to get started.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Zone</h3></div>
            <form action="{{ route('reseller.zones.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Zone Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Add Zone</button></div>
            </form>
        </div>
        
        <div class="card card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Add Subzone</h3></div>
            <form action="{{ route('reseller.subzones.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Zone <span class="text-danger">*</span></label>
                        <select name="zone_id" class="form-control" required>
                            <option value="">Select Zone</option>
                            @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Subzone Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-info">Add Subzone</button></div>
            </form>
        </div>
        
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-box mr-2"></i>Add TJ/Box</h3></div>
            <form action="{{ route('reseller.tjs.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Zone <span class="text-danger">*</span></label>
                        <select name="zone_id" class="form-control" required>
                            <option value="">Select Zone</option>
                            @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subzone</label>
                        <select name="area_id" class="form-control">
                            <option value="">Select Subzone (Optional)</option>
                            @foreach($zones as $zone)
                                @foreach($zone->areas as $area)
                                <option value="{{ $area->id }}">{{ $zone->name }} > {{ $area->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>TJ Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Capacity</label><input type="number" name="capacity" class="form-control" value="0"></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-success">Add TJ</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
