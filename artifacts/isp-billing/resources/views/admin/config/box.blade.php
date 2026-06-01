@extends('layouts.admin')
@section('title', 'TJ Management')
@section('breadcrumb')<li class="breadcrumb-item active">TJ</li>@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">TJ Management</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display: none;" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                            <th>Name</th>
                            <th>Zone</th>
                            <th>Sub Zone</th>
                            <th>Capacity</th>
                            <th>Used</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($boxes as $box)
                        <tr>
                            <td><input type="checkbox" name="bulk_ids[]" value="{{ $box->id }}" class="item-checkbox" onclick="updateSelection()" {{ $box->clients_count > 0 ? 'disabled' : '' }}></td>
                            <td><strong>{{ $box->name }}</strong></td>
                            <td>{{ $box->zone->name ?? '-' }}</td>
                            <td>{{ $box->area->name ?? '-' }}</td>
                            <td class="text-center">{{ $box->capacity }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $box->clients_count >= $box->capacity ? 'danger' : ($box->clients_count >= $box->capacity * 0.8 ? 'warning' : 'success') }}">
                                    {{ $box->clients_count }} / {{ $box->capacity }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $box->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($box->status) }}</span>
                            </td>
                            <td>
                                @if($box->clients_count > 0)
                                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#viewClientsModal{{ $box->id }}" title="View Clients">
                                    <i class="fas fa-users"></i>
                                </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editBoxModal{{ $box->id }}"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSingle({{ $box->id }})" {{ $box->clients_count > 0 ? 'disabled' : '' }}><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center">No TJ found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add TJ</h3></div>
            <form action="{{ route('admin.config.box.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>TJ Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., TJ-A1, TJ-1" value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label>Division</label>
                        <select class="form-control" id="add_division">
                            <option value="">-- Select Division --</option>
                            @foreach($divisions as $div)
                            <option value="{{ $div }}">{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District</label>
                        <select class="form-control" id="add_district">
                            <option value="">-- Select Division First --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thana/Upazila</label>
                        <select class="form-control" id="add_upazila">
                            <option value="">-- Select District First --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control" id="add_zone">
                            <option value="">-- Select Thana/Upazila First --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sub Zone</label>
                        <select name="area_id" class="form-control" id="add_area">
                            <option value="">-- Select Zone First --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., Near XYZ Mosque" value="{{ old('location') }}">
                    </div>
                    <div class="form-group">
                        <label>Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 16) }}" required min="1" max="1000">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes...">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Add TJ</button></div>
            </form>
        </div>
    </div>
</div>

<form id="bulkForm" action="{{ route('admin.config.box.bulk-delete') }}" method="POST" style="display:none;">
    @csrf
    <div id="bulkIdsContainer"></div>
</form>

<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

@foreach($boxes as $box)
@if($box->clients_count > 0)
<div class="modal fade" id="viewClientsModal{{ $box->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-users mr-2"></i>Clients in TJ: {{ $box->name }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">
                    <strong>Zone:</strong> {{ $box->zone->name ?? '-' }} |
                    <strong>Sub Zone:</strong> {{ $box->area->name ?? '-' }} |
                    <strong>Total:</strong> {{ $box->clients_count }} client(s)
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Client Name</th>
                                <th>Username</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($box->clients as $index => $client)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></td>
                                <td>{{ $client->username }}</td>
                                <td>
                                    <span class="badge badge-{{ $client->status == 'active' ? 'success' : ($client->status == 'inactive' ? 'danger' : 'secondary') }}">
                                        {{ ucfirst($client->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif
<div class="modal fade" id="editBoxModal{{ $box->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.config.box.update', $box) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white">Edit TJ</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @php
                        $boxZone = $box->zone;
                        $boxUpazila = $boxZone ? $boxZone->upazila : null;
                        $boxDistrict = $boxUpazila ? $boxUpazila->district : null;
                        $boxDivision = $boxDistrict ? $boxDistrict->division : null;
                    @endphp
                    <div class="form-group">
                        <label>TJ Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ $box->name }}">
                    </div>
                    <div class="form-group">
                        <label>Division</label>
                        <select class="form-control edit-division" data-box-id="{{ $box->id }}">
                            <option value="">-- Select Division --</option>
                            @foreach($divisions as $div)
                            <option value="{{ $div }}" {{ $boxDivision == $div ? 'selected' : '' }}>{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District</label>
                        <select class="form-control edit-district" data-box-id="{{ $box->id }}">
                            <option value="">-- Select Division First --</option>
                            @if($boxDivision)
                                @foreach($districts->where('division', $boxDivision) as $dist)
                                <option value="{{ $dist->id }}" {{ $boxDistrict && $boxDistrict->id == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thana/Upazila</label>
                        <select class="form-control edit-upazila" data-box-id="{{ $box->id }}">
                            <option value="">-- Select District First --</option>
                            @if($boxDistrict)
                                @foreach($upazilas->where('district_id', $boxDistrict->id) as $upz)
                                <option value="{{ $upz->id }}" {{ $boxUpazila && $boxUpazila->id == $upz->id ? 'selected' : '' }}>{{ $upz->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control edit-zone" data-box-id="{{ $box->id }}">
                            <option value="">-- Select Thana/Upazila First --</option>
                            @if($boxUpazila)
                                @foreach($zones->where('upazila_id', $boxUpazila->id) as $zone)
                                <option value="{{ $zone->id }}" {{ $box->zone_id == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sub Zone</label>
                        <select name="area_id" class="form-control edit-area" data-box-id="{{ $box->id }}">
                            <option value="">-- Select Zone First --</option>
                            @if($box->zone_id)
                                @foreach($areas->where('zone_id', $box->zone_id) as $area)
                                <option value="{{ $area->id }}" {{ $box->area_id == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" value="{{ $box->location }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Capacity</label>
                                <input type="number" name="capacity" class="form-control" value="{{ $box->capacity }}" required min="1" max="1000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" {{ $box->status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $box->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $box->description }}</textarea>
                    </div>
                    @if($box->clients_count > 0)
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle mr-2"></i>This TJ has {{ $box->clients_count }} active client(s) assigned.
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update TJ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<script>
function loadOptions(url, select, placeholder, callback) {
    select.innerHTML = '<option value="">Loading...</option>';
    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            select.innerHTML = '<option value="">' + placeholder + '</option>';
            data.forEach(function(item) {
                var opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                select.appendChild(opt);
            });
            if (callback) callback(data);
        })
        .catch(function() { select.innerHTML = '<option value="">' + placeholder + '</option>'; });
}

(function() {
    var addDiv = document.getElementById('add_division');
    var addDist = document.getElementById('add_district');
    var addUpz = document.getElementById('add_upazila');
    var addZone = document.getElementById('add_zone');
    var addArea = document.getElementById('add_area');

    addDiv.addEventListener('change', function() {
        addDist.innerHTML = '<option value="">-- Select Division First --</option>';
        addUpz.innerHTML = '<option value="">-- Select District First --</option>';
        addZone.innerHTML = '<option value="">-- Select Thana/Upazila First --</option>';
        addArea.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (this.value) {
            loadOptions('/admin/api/districts-by-division/' + encodeURIComponent(this.value), addDist, '-- Select District --');
        }
    });

    addDist.addEventListener('change', function() {
        addUpz.innerHTML = '<option value="">-- Select District First --</option>';
        addZone.innerHTML = '<option value="">-- Select Thana/Upazila First --</option>';
        addArea.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (this.value) {
            loadOptions('/admin/api/upazilas-by-district/' + this.value, addUpz, '-- Select Thana/Upazila --');
        }
    });

    addUpz.addEventListener('change', function() {
        addZone.innerHTML = '<option value="">-- Select Thana/Upazila First --</option>';
        addArea.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (this.value) {
            loadOptions('/admin/api/zones-by-upazila/' + this.value, addZone, '-- Select Zone --');
        }
    });

    addZone.addEventListener('change', function() {
        addArea.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (this.value) {
            loadOptions('/admin/api/areas-by-zone/' + this.value, addArea, '-- Select Sub Zone --');
        }
    });
})();

document.querySelectorAll('.edit-division').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var boxId = this.dataset.boxId;
        var distSel = document.querySelector('.edit-district[data-box-id="' + boxId + '"]');
        var upzSel = document.querySelector('.edit-upazila[data-box-id="' + boxId + '"]');
        var zoneSel = document.querySelector('.edit-zone[data-box-id="' + boxId + '"]');
        var areaSel = document.querySelector('.edit-area[data-box-id="' + boxId + '"]');
        distSel.innerHTML = '<option value="">-- Select Division First --</option>';
        upzSel.innerHTML = '<option value="">-- Select District First --</option>';
        zoneSel.innerHTML = '<option value="">-- Select Thana/Upazila First --</option>';
        areaSel.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (this.value) {
            loadOptions('/admin/api/districts-by-division/' + encodeURIComponent(this.value), distSel, '-- Select District --');
        }
    });
});

document.querySelectorAll('.edit-district').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var boxId = this.dataset.boxId;
        var upzSel = document.querySelector('.edit-upazila[data-box-id="' + boxId + '"]');
        var zoneSel = document.querySelector('.edit-zone[data-box-id="' + boxId + '"]');
        var areaSel = document.querySelector('.edit-area[data-box-id="' + boxId + '"]');
        upzSel.innerHTML = '<option value="">-- Select District First --</option>';
        zoneSel.innerHTML = '<option value="">-- Select Thana/Upazila First --</option>';
        areaSel.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (this.value) {
            loadOptions('/admin/api/upazilas-by-district/' + this.value, upzSel, '-- Select Thana/Upazila --');
        }
    });
});

document.querySelectorAll('.edit-upazila').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var boxId = this.dataset.boxId;
        var zoneSel = document.querySelector('.edit-zone[data-box-id="' + boxId + '"]');
        var areaSel = document.querySelector('.edit-area[data-box-id="' + boxId + '"]');
        zoneSel.innerHTML = '<option value="">-- Select Thana/Upazila First --</option>';
        areaSel.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (this.value) {
            loadOptions('/admin/api/zones-by-upazila/' + this.value, zoneSel, '-- Select Zone --');
        }
    });
});

document.querySelectorAll('.edit-zone').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var boxId = this.dataset.boxId;
        var areaSel = document.querySelector('.edit-area[data-box-id="' + boxId + '"]');
        areaSel.innerHTML = '<option value="">-- Select Zone First --</option>';
        if (this.value) {
            loadOptions('/admin/api/areas-by-zone/' + this.value, areaSel, '-- Select Sub Zone --');
        }
    });
});

function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.item-checkbox:not(:disabled)');
    checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
    updateSelection();
}

function updateSelection() {
    var checkboxes = document.querySelectorAll('.item-checkbox:checked');
    var count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';
}

function bulkDelete() {
    var checkboxes = document.querySelectorAll('.item-checkbox:checked');
    var count = checkboxes.length;
    if (count === 0) return;
    if (confirm('Delete ' + count + ' selected TJ(s)?')) {
        var container = document.getElementById('bulkIdsContainer');
        container.innerHTML = '';
        checkboxes.forEach(function(cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('bulkForm').submit();
    }
}

function deleteSingle(id) {
    if (confirm('Delete this TJ?')) {
        var form = document.getElementById('singleDeleteForm');
        form.action = '{{ url("admin/config/box") }}/' + id;
        form.submit();
    }
}
</script>
@endsection
