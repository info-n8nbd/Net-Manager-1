@extends('layouts.admin')

@section('title', 'Area Management')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Area Management</li>
@endsection

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" id="areaTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-upazila" data-toggle="tab" href="#upazila-pane" role="tab">
                    <i class="fas fa-map-pin mr-1"></i> Thana / Upazila
                    <span class="badge badge-info ml-1">{{ $upazilas->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-zone" data-toggle="tab" href="#zone-pane" role="tab">
                    <i class="fas fa-map-marker-alt mr-1"></i> Zone
                    <span class="badge badge-info ml-1">{{ $zones->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-subzone" data-toggle="tab" href="#subzone-pane" role="tab">
                    <i class="fas fa-map mr-1"></i> Sub Zone
                    <span class="badge badge-info ml-1">{{ $allAreas->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-tj" data-toggle="tab" href="#tj-pane" role="tab">
                    <i class="fas fa-cube mr-1"></i> TJ
                    <span class="badge badge-info ml-1">{{ $boxes->count() }}</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="areaTabContent">
            

            {{-- THANA / UPAZILA TAB --}}
            <div class="tab-pane fade show active" id="upazila-pane" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-2 d-flex align-items-center bulk-bar" id="upazilaBulkBar" style="display:none !important;">
                            <span class="mr-2 text-sm text-muted"><strong class="selected-count">0</strong> selected</span>
                            <button type="button" class="btn btn-sm btn-danger bulk-delete-btn" data-type="upazila" data-url="/admin/config/upazila/bulk-delete"><i class="fas fa-trash mr-1"></i> Delete Selected</button>
                        </div>
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr style="background: #1a2942; color: #fff;">
                                    <th style="width:35px;"><input type="checkbox" class="select-all-check" data-type="upazila" title="Select All"></th>
                                    <th>#</th>
                                    <th>Division</th>
                                    <th>District</th>
                                    <th>Thana/Upazila</th>
                                    <th>Status</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($upazilas as $i => $upazila)
                                <tr>
                                    <td><input type="checkbox" class="row-check upazila-check" value="{{ $upazila->id }}"></td>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $upazila->district->division ?? '-' }}</td>
                                    <td>{{ $upazila->district->name ?? '-' }}</td>
                                    <td>{{ $upazila->name }}</td>
                                    <td><span class="badge badge-{{ $upazila->is_active ? 'success' : 'danger' }}">{{ $upazila->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <button class="btn btn-xs btn-info edit-upazila-btn" data-id="{{ $upazila->id }}" data-name="{{ $upazila->name }}" data-district="{{ $upazila->district_id }}" data-active="{{ $upazila->is_active }}"><i class="fas fa-edit"></i></button>
                                        <form action="{{ route('admin.config.upazila.destroy', $upazila) }}" method="POST" style="display:inline" class="delete-form">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this upazila?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">No upazilas found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-success card-outline">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-1"></i> Add Thana/Upazila</h3></div>
                            <form action="{{ route('admin.config.upazila.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Division <span class="text-danger">*</span></label>
                                        <select class="form-control" id="upazila_division_select" required>
                                            <option value="">-- Select Division --</option>
                                            @foreach($configuredDivisions as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>District <span class="text-danger">*</span></label>
                                        <select name="district_id" class="form-control" id="upazila_district_select" required>
                                            <option value="">-- Select Division First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Thana/Upazila Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required placeholder="e.g. Kaliakair">
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" class="form-check-input" id="upazilaActive" checked>
                                        <label class="form-check-label" for="upazilaActive">Active</label>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Save Thana/Upazila</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ZONE TAB --}}
            <div class="tab-pane fade" id="zone-pane" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-2 d-flex align-items-center bulk-bar" id="zoneBulkBar" style="display:none !important;">
                            <span class="mr-2 text-sm text-muted"><strong class="selected-count">0</strong> selected</span>
                            <button type="button" class="btn btn-sm btn-danger bulk-delete-btn" data-type="zone" data-url="/admin/settings/zones/bulk-delete"><i class="fas fa-trash mr-1"></i> Delete Selected</button>
                        </div>
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr style="background: #1a2942; color: #fff;">
                                    <th style="width:35px;"><input type="checkbox" class="select-all-check" data-type="zone" title="Select All"></th>
                                    <th>#</th>
                                    <th>Zone Name</th>
                                    <th>Upazila</th>
                                    <th>Sub Zones</th>
                                    <th>Status</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($zones as $i => $zone)
                                <tr>
                                    <td><input type="checkbox" class="row-check zone-check" value="{{ $zone->id }}"></td>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $zone->name }}</td>
                                    <td>{{ $zone->upazila->name ?? '-' }}</td>
                                    <td><span class="badge badge-secondary">{{ $zone->areas->count() }}</span></td>
                                    <td><span class="badge badge-{{ $zone->is_active ? 'success' : 'danger' }}">{{ $zone->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <button class="btn btn-xs btn-info edit-zone-btn" data-id="{{ $zone->id }}" data-name="{{ $zone->name }}" data-upazila="{{ $zone->upazila_id }}" data-description="{{ $zone->description }}" data-active="{{ $zone->is_active }}"><i class="fas fa-edit"></i></button>
                                        <form action="{{ route('admin.settings.zones.destroy', $zone) }}" method="POST" style="display:inline" class="delete-form">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this zone?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">No zones found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-success card-outline">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-1"></i> Add Zone</h3></div>
                            <form action="{{ route('admin.settings.zones.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Division <span class="text-danger">*</span></label>
                                        <select class="form-control cascade-division" data-target="zone" required>
                                            <option value="">-- Select Division --</option>
                                            @foreach($configuredDivisions as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>District <span class="text-danger">*</span></label>
                                        <select class="form-control cascade-district" id="zone_district_select" data-target="zone" required>
                                            <option value="">-- Select Division First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Upazila <span class="text-danger">*</span></label>
                                        <select name="upazila_id" class="form-control cascade-upazila" id="zone_upazila_select" required>
                                            <option value="">-- Select District First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Zone Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required placeholder="e.g. Zone-A">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <input type="text" name="description" class="form-control" placeholder="Optional description">
                                    </div>
                                    <div class="form-group form-check">
                                        <input type="checkbox" name="is_active" class="form-check-input" id="zoneActive" checked>
                                        <label class="form-check-label" for="zoneActive">Active</label>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Save Zone</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUB ZONE TAB --}}
            <div class="tab-pane fade" id="subzone-pane" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-2 d-flex align-items-center bulk-bar" id="subzoneBulkBar" style="display:none !important;">
                            <span class="mr-2 text-sm text-muted"><strong class="selected-count">0</strong> selected</span>
                            <button type="button" class="btn btn-sm btn-danger bulk-delete-btn" data-type="subzone" data-url="/admin/settings/areas/bulk-delete"><i class="fas fa-trash mr-1"></i> Delete Selected</button>
                        </div>
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr style="background: #1a2942; color: #fff;">
                                    <th style="width:35px;"><input type="checkbox" class="select-all-check" data-type="subzone" title="Select All"></th>
                                    <th>#</th>
                                    <th>Sub Zone Name</th>
                                    <th>Zone</th>
                                    <th>Upazila</th>
                                    <th>Status</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allAreas as $i => $area)
                                <tr>
                                    <td><input type="checkbox" class="row-check subzone-check" value="{{ $area->id }}"></td>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $area->name }}</td>
                                    <td>{{ $area->zone->name ?? '-' }}</td>
                                    <td>{{ $area->zone->upazila->name ?? '-' }}</td>
                                    <td><span class="badge badge-{{ $area->is_active ? 'success' : 'danger' }}">{{ $area->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <button class="btn btn-xs btn-info edit-subzone-btn" data-id="{{ $area->id }}" data-name="{{ $area->name }}" data-zone="{{ $area->zone_id }}" data-description="{{ $area->description }}" data-active="{{ $area->is_active }}"><i class="fas fa-edit"></i></button>
                                        <form action="{{ route('admin.settings.areas.destroy', $area) }}" method="POST" style="display:inline" class="delete-form">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this sub zone?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">No sub zones found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-success card-outline">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-1"></i> Add Sub Zone</h3></div>
                            <form action="{{ route('admin.settings.areas.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Division <span class="text-danger">*</span></label>
                                        <select class="form-control cascade-division" data-target="subzone" required>
                                            <option value="">-- Select Division --</option>
                                            @foreach($configuredDivisions as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>District <span class="text-danger">*</span></label>
                                        <select class="form-control cascade-district" id="subzone_district_select" data-target="subzone" required>
                                            <option value="">-- Select Division First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Upazila <span class="text-danger">*</span></label>
                                        <select class="form-control cascade-upazila" id="subzone_upazila_select" data-target="subzone" required>
                                            <option value="">-- Select District First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Zone <span class="text-danger">*</span></label>
                                        <select name="zone_id" class="form-control cascade-zone" id="subzone_zone_select" required>
                                            <option value="">-- Select Upazila First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Sub Zone Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required placeholder="e.g. Block-A">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <input type="text" name="description" class="form-control" placeholder="Optional description">
                                    </div>
                                    <div class="form-group form-check">
                                        <input type="checkbox" name="is_active" class="form-check-input" id="subzoneActive" checked>
                                        <label class="form-check-label" for="subzoneActive">Active</label>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Save Sub Zone</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TJ TAB --}}
            <div class="tab-pane fade" id="tj-pane" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-2 d-flex align-items-center bulk-bar" id="tjBulkBar" style="display:none !important;">
                            <span class="mr-2 text-sm text-muted"><strong class="selected-count">0</strong> selected</span>
                            <button type="button" class="btn btn-sm btn-danger bulk-delete-btn" data-type="tj" data-url="/admin/config/box/bulk-delete"><i class="fas fa-trash mr-1"></i> Delete Selected</button>
                        </div>
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr style="background: #1a2942; color: #fff;">
                                    <th style="width:35px;"><input type="checkbox" class="select-all-check" data-type="tj" title="Select All"></th>
                                    <th>#</th>
                                    <th>TJ Name</th>
                                    <th>Zone</th>
                                    <th>Sub Zone</th>
                                    <th>Capacity</th>
                                    <th>Clients</th>
                                    <th>Status</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($boxes as $i => $box)
                                <tr>
                                    <td><input type="checkbox" class="row-check tj-check" value="{{ $box->id }}"></td>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $box->name }}</td>
                                    <td>{{ $box->zone->name ?? '-' }}</td>
                                    <td>{{ $box->area->name ?? '-' }}</td>
                                    <td>{{ $box->capacity ?? '-' }}</td>
                                    <td><span class="badge badge-primary">{{ $box->clients_count }}</span></td>
                                    <td><span class="badge badge-{{ ($box->status ?? 'active') === 'active' ? 'success' : 'danger' }}">{{ ucfirst($box->status ?? 'active') }}</span></td>
                                    <td>
                                        <button class="btn btn-xs btn-info edit-tj-btn"
                                            data-id="{{ $box->id }}"
                                            data-name="{{ $box->name }}"
                                            data-zone="{{ $box->zone_id }}"
                                            data-area="{{ $box->area_id }}"
                                            data-location="{{ $box->location }}"
                                            data-capacity="{{ $box->capacity }}"
                                            data-description="{{ $box->description }}"
                                            data-status="{{ $box->status ?? 'active' }}"><i class="fas fa-edit"></i></button>
                                        <form action="{{ route('admin.config.box.destroy', $box) }}" method="POST" style="display:inline" class="delete-form">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this TJ?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center text-muted">No TJs found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-success card-outline">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-1"></i> Add TJ</h3></div>
                            <form action="{{ route('admin.config.box.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Division</label>
                                        <select class="form-control cascade-division" data-target="tj">
                                            <option value="">-- Select Division --</option>
                                            @foreach($configuredDivisions as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>District</label>
                                        <select class="form-control cascade-district" id="tj_district_select" data-target="tj">
                                            <option value="">-- Select Division First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Upazila</label>
                                        <select class="form-control cascade-upazila" id="tj_upazila_select" data-target="tj">
                                            <option value="">-- Select District First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Zone</label>
                                        <select name="zone_id" class="form-control cascade-zone" id="tj_zone_select" data-target="tj">
                                            <option value="">-- Select Upazila First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Sub Zone</label>
                                        <select name="area_id" class="form-control cascade-area" id="tj_area_select">
                                            <option value="">-- Select Zone First --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>TJ Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required placeholder="e.g. TJ-001">
                                    </div>
                                    <div class="form-group">
                                        <label>Location</label>
                                        <input type="text" name="location" class="form-control" placeholder="Optional location">
                                    </div>
                                    <div class="form-group">
                                        <label>Capacity <span class="text-danger">*</span></label>
                                        <input type="number" name="capacity" class="form-control" value="24" required min="1" max="1000">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <input type="text" name="description" class="form-control" placeholder="Optional description">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Save TJ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- EDIT DISTRICT MODAL --}}
<div class="modal fade" id="editDistrictModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editDistrictForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-info text-white"><h5 class="modal-title">Edit District</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Division</label>
                        <select name="division" id="ed_division" class="form-control" required>
                            <option value="">-- Select Division --</option>
                            @foreach($divisions as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="ed_name" class="form-control" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ed_active">
                        <label class="form-check-label" for="ed_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- EDIT UPAZILA MODAL --}}
<div class="modal fade" id="editUpazilaModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editUpazilaForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-info text-white"><h5 class="modal-title">Edit Thana/Upazila</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>District <span class="text-danger">*</span></label>
                        <select name="district_id" id="eu_district" class="form-control" required>
                            @foreach($districts as $d)<option value="{{ $d->id }}">{{ $d->division ? $d->division.' - ' : '' }}{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thana/Upazila Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="eu_name" class="form-control" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="eu_active">
                        <label class="form-check-label" for="eu_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- EDIT ZONE MODAL --}}
<div class="modal fade" id="editZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editZoneForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-info text-white"><h5 class="modal-title">Edit Zone</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Upazila <span class="text-danger">*</span></label>
                        <select name="upazila_id" id="ez_upazila" class="form-control" required>
                            @foreach($upazilas as $u)<option value="{{ $u->id }}">{{ $u->district->name ?? '' }} - {{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Zone Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="ez_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" id="ez_description" class="form-control">
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ez_active">
                        <label class="form-check-label" for="ez_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- EDIT SUB ZONE MODAL --}}
<div class="modal fade" id="editSubZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editSubZoneForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-info text-white"><h5 class="modal-title">Edit Sub Zone</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Zone <span class="text-danger">*</span></label>
                        <select name="zone_id" id="esz_zone" class="form-control" required>
                            @foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sub Zone Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="esz_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" id="esz_description" class="form-control">
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="esz_active">
                        <label class="form-check-label" for="esz_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- EDIT TJ MODAL --}}
<div class="modal fade" id="editTjModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editTjForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-info text-white"><h5 class="modal-title">Edit TJ</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control" id="et_zone">
                            <option value="">-- None --</option>
                            @foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sub Zone</label>
                        <select name="area_id" class="form-control" id="et_area">
                            <option value="">-- None --</option>
                            @foreach($allAreas as $a)<option value="{{ $a->id }}" data-zone="{{ $a->zone_id }}">{{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>TJ Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="et_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" id="et_location" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" id="et_capacity" class="form-control" required min="1" max="1000">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" id="et_description" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" id="et_status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var activeTab = localStorage.getItem('areaActiveTab');
    if (activeTab) {
        $('#areaTabs a[href="' + activeTab + '"]').tab('show');
    }
    $('#areaTabs a').on('shown.bs.tab', function(e) {
        localStorage.setItem('areaActiveTab', $(e.target).attr('href'));
    });

    function loadOptions(url, selectEl, placeholder, callback) {
        selectEl.innerHTML = '<option value="">Loading...</option>';
        fetch(url).then(r => r.json()).then(function(data) {
            var html = '<option value="">' + placeholder + '</option>';
            data.forEach(function(item) {
                html += '<option value="' + item.id + '">' + item.name + '</option>';
            });
            selectEl.innerHTML = html;
            if (callback) callback();
        }).catch(function() {
            selectEl.innerHTML = '<option value="">' + placeholder + '</option>';
        });
    }

    $('#upazila_division_select').on('change', function() {
        var div = this.value;
        var distSel = document.getElementById('upazila_district_select');
        if (!div) { distSel.innerHTML = '<option value="">-- Select Division First --</option>'; return; }
        loadOptions('/admin/api/districts-by-division/' + encodeURIComponent(div), distSel, '-- Select District --');
    });

    $('.cascade-division').on('change', function() {
        var div = this.value;
        var target = $(this).data('target');
        var distSel = document.getElementById(target + '_district_select');
        var upzSel = document.getElementById(target + '_upazila_select');
        var zoneSel = document.getElementById(target + '_zone_select');
        var areaSel = document.getElementById(target + '_area_select');
        if (distSel) distSel.innerHTML = '<option value="">-- Select Division First --</option>';
        if (upzSel) upzSel.innerHTML = '<option value="">-- Select District First --</option>';
        if (zoneSel) zoneSel.innerHTML = '<option value="">-- Select Upazila First --</option>';
        if (areaSel) areaSel.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (div && distSel) {
            loadOptions('/admin/api/districts-by-division/' + encodeURIComponent(div), distSel, '-- Select District --');
        }
    });

    $('.cascade-district').on('change', function() {
        var distId = this.value;
        var target = $(this).data('target');
        var upzSel = document.getElementById(target + '_upazila_select');
        var zoneSel = document.getElementById(target + '_zone_select');
        var areaSel = document.getElementById(target + '_area_select');
        if (upzSel) upzSel.innerHTML = '<option value="">-- Select District First --</option>';
        if (zoneSel) zoneSel.innerHTML = '<option value="">-- Select Upazila First --</option>';
        if (areaSel) areaSel.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (distId && upzSel) {
            loadOptions('/admin/api/upazilas-by-district/' + distId, upzSel, '-- Select Upazila --');
        }
    });

    $('.cascade-upazila').on('change', function() {
        var upzId = this.value;
        var target = $(this).data('target');
        var zoneSel = document.getElementById(target + '_zone_select');
        var areaSel = document.getElementById(target + '_area_select');
        if (zoneSel) zoneSel.innerHTML = '<option value="">-- Select Upazila First --</option>';
        if (areaSel) areaSel.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (upzId && zoneSel) {
            loadOptions('/admin/api/zones-by-upazila/' + upzId, zoneSel, '-- Select Zone --');
        }
    });

    $('.cascade-zone').on('change', function() {
        var zoneId = this.value;
        var target = $(this).data('target');
        var areaSel = document.getElementById(target + '_area_select');
        if (areaSel) areaSel.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (zoneId && areaSel) {
            loadOptions('/admin/api/areas-by-zone/' + zoneId, areaSel, '-- Select Sub Zone --');
        }
    });

    var bulkTypes = {
        district: { bar: '#districtBulkBar', check: '.district-check', url: '/admin/config/district/bulk-delete' },
        upazila: { bar: '#upazilaBulkBar', check: '.upazila-check', url: '/admin/config/upazila/bulk-delete' },
        zone: { bar: '#zoneBulkBar', check: '.zone-check', url: '/admin/settings/zones/bulk-delete' },
        subzone: { bar: '#subzoneBulkBar', check: '.subzone-check', url: '/admin/settings/areas/bulk-delete' },
        tj: { bar: '#tjBulkBar', check: '.tj-check', url: '/admin/config/box/bulk-delete' }
    };
    function updateBulkBar(type) {
        var cfg = bulkTypes[type];
        var count = $(cfg.check + ':checked').length;
        $(cfg.bar).find('.selected-count').text(count);
        if (count > 0) { $(cfg.bar).removeAttr('style').show(); } else { $(cfg.bar).hide(); }
    }
    $('.select-all-check').on('change', function() {
        var type = $(this).data('type');
        $(bulkTypes[type].check + ':not(:disabled)').prop('checked', this.checked);
        updateBulkBar(type);
    });
    $(document).on('change', '.row-check, .district-check', function() {
        var type = null;
        for (var k in bulkTypes) {
            if ($(this).hasClass(k + '-check')) { type = k; break; }
        }
        if (!type) return;
        var cfg = bulkTypes[type];
        var total = $(cfg.check + ':not(:disabled)').length;
        var checked = $(cfg.check + ':checked').length;
        $(this).closest('table').find('.select-all-check').prop('checked', total > 0 && total === checked);
        updateBulkBar(type);
    });
    $(document).on('click', '.bulk-delete-btn', function() {
        var type = $(this).data('type');
        var cfg = bulkTypes[type];
        var ids = [];
        $(cfg.check + ':checked').each(function() { ids.push($(this).val()); });
        if (ids.length === 0) return;
        if (!confirm('Delete ' + ids.length + ' selected item(s)? This cannot be undone.')) return;
        $.ajax({
            url: cfg.url,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', ids: ids },
            success: function() { location.reload(); },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Failed to delete some items.');
            }
        });
    });

    $('#add_district_division').on('change', function() {
        var div = $(this).val();
        var sel = $('#add_district_select');
        sel.html('<option value="">-- Loading... --</option>');
        if (!div) {
            sel.html('<option value="">-- Select Division First --</option>');
            return;
        }
        $.get('/admin/api/districts-available/' + encodeURIComponent(div), function(data) {
            sel.empty().append('<option value="">-- Select District --</option>');
            $.each(data, function(i, d) {
                sel.append('<option value="' + d.id + '">' + d.name + '</option>');
            });
        });
    });

    $(document).on('click', '.edit-district-btn', function() {
        var id = $(this).data('id');
        $('#editDistrictForm').attr('action', '/admin/config/district/' + id);
        $('#ed_name').val($(this).data('name'));
        $('#ed_division').val($(this).data('division'));
        $('#ed_active').prop('checked', $(this).data('active') == 1);
        $('#editDistrictModal').modal('show');
    });

    $(document).on('click', '.edit-upazila-btn', function() {
        var id = $(this).data('id');
        $('#editUpazilaForm').attr('action', '/admin/config/upazila/' + id);
        $('#eu_name').val($(this).data('name'));
        $('#eu_district').val($(this).data('district'));
        $('#eu_active').prop('checked', $(this).data('active') == 1);
        $('#editUpazilaModal').modal('show');
    });

    $(document).on('click', '.edit-zone-btn', function() {
        var id = $(this).data('id');
        $('#editZoneForm').attr('action', '/admin/settings/zones/' + id);
        $('#ez_name').val($(this).data('name'));
        $('#ez_upazila').val($(this).data('upazila'));
        $('#ez_description').val($(this).data('description'));
        $('#ez_active').prop('checked', $(this).data('active') == 1);
        $('#editZoneModal').modal('show');
    });

    $(document).on('click', '.edit-subzone-btn', function() {
        var id = $(this).data('id');
        $('#editSubZoneForm').attr('action', '/admin/settings/areas/' + id);
        $('#esz_name').val($(this).data('name'));
        $('#esz_zone').val($(this).data('zone'));
        $('#esz_description').val($(this).data('description'));
        $('#esz_active').prop('checked', $(this).data('active') == 1);
        $('#editSubZoneModal').modal('show');
    });

    $(document).on('click', '.edit-tj-btn', function() {
        var id = $(this).data('id');
        $('#editTjForm').attr('action', '/admin/config/box/' + id);
        $('#et_name').val($(this).data('name'));
        $('#et_zone').val($(this).data('zone'));
        $('#et_area').val($(this).data('area'));
        $('#et_location').val($(this).data('location'));
        $('#et_capacity').val($(this).data('capacity'));
        $('#et_description').val($(this).data('description'));
        $('#et_status').val($(this).data('status'));
        $('#editTjModal').modal('show');
    });
});
</script>
@endpush
