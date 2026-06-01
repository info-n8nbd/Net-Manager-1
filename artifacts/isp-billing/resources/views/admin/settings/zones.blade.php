@extends('layouts.admin')

@section('title', 'Zones')
@section('breadcrumb')
<li class="breadcrumb-item active">Zones</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Zones & Areas</h3>
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
                            <th>Zone Name</th>
                            <th>Areas</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($zones as $zone)
                        <tr>
                            <td><input type="checkbox" name="bulk_ids[]" value="{{ $zone->id }}" class="item-checkbox" onclick="updateSelection()"></td>
                            <td>{{ $zone->name }}</td>
                            <td>{{ $zone->areas->pluck('name')->join(', ') }}</td>
                            <td><span class="badge badge-{{ $zone->is_active ? 'success' : 'danger' }}">{{ $zone->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editZoneModal{{ $zone->id }}"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSingle({{ $zone->id }})"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No zones</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $zones->appends(request()->query())->links() }}
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
</script>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add Zone</h3></div>
            <form action="{{ route('admin.settings.zones.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Zone Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="{{ old('name') }}"></div>
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
                        <label>Thana/Upazila <span class="text-danger">*</span></label>
                        <select name="upazila_id" class="form-control" id="add_upazila" required>
                            <option value="">-- Select District First --</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Add Zone</button></div>
            </form>
        </div>
    </div>
</div>

<form id="bulkForm" action="{{ route('admin.settings.zones.bulk-delete') }}" method="POST" style="display:none;">
    @csrf
    <div id="bulkIdsContainer"></div>
</form>

<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

@foreach($zones as $zone)
<div class="modal fade" id="editZoneModal{{ $zone->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.settings.zones.update', $zone) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white">Edit Zone</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Zone Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $zone->name }}" required>
                    </div>
                    @php
                        $zoneUpazila = $zone->upazila;
                        $zoneDistrict = $zoneUpazila ? $zoneUpazila->district : null;
                        $zoneDivision = $zoneDistrict ? $zoneDistrict->division : null;
                    @endphp
                    <div class="form-group">
                        <label>Division</label>
                        <select class="form-control edit-division" data-zone-id="{{ $zone->id }}">
                            <option value="">-- Select Division --</option>
                            @foreach($divisions as $div)
                            <option value="{{ $div }}" {{ $zoneDivision == $div ? 'selected' : '' }}>{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District</label>
                        <select class="form-control edit-district" data-zone-id="{{ $zone->id }}">
                            <option value="">-- Select Division First --</option>
                            @if($zoneDivision)
                                @foreach($districts->where('division', $zoneDivision) as $dist)
                                <option value="{{ $dist->id }}" {{ $zoneDistrict && $zoneDistrict->id == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thana/Upazila <span class="text-danger">*</span></label>
                        <select name="upazila_id" class="form-control edit-upazila" data-zone-id="{{ $zone->id }}" required>
                            <option value="">-- Select District First --</option>
                            @if($zoneDistrict)
                                @foreach($upazilas->where('district_id', $zoneDistrict->id) as $upz)
                                <option value="{{ $upz->id }}" {{ $zone->upazila_id == $upz->id ? 'selected' : '' }}>{{ $upz->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $zone->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active_{{ $zone->id }}" name="is_active" {{ $zone->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active_{{ $zone->id }}">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
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

    addDiv.addEventListener('change', function() {
        addDist.innerHTML = '<option value="">-- Select Division First --</option>';
        addUpz.innerHTML = '<option value="">-- Select District First --</option>';
        if (this.value) {
            loadOptions('/admin/api/districts-by-division/' + encodeURIComponent(this.value), addDist, '-- Select District --');
        }
    });

    addDist.addEventListener('change', function() {
        addUpz.innerHTML = '<option value="">-- Select District First --</option>';
        if (this.value) {
            loadOptions('/admin/api/upazilas-by-district/' + this.value, addUpz, '-- Select Thana/Upazila --');
        }
    });
})();

document.querySelectorAll('.edit-division').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var zoneId = this.dataset.zoneId;
        var distSel = document.querySelector('.edit-district[data-zone-id="' + zoneId + '"]');
        var upzSel = document.querySelector('.edit-upazila[data-zone-id="' + zoneId + '"]');
        distSel.innerHTML = '<option value="">-- Select Division First --</option>';
        upzSel.innerHTML = '<option value="">-- Select District First --</option>';
        if (this.value) {
            loadOptions('/admin/api/districts-by-division/' + encodeURIComponent(this.value), distSel, '-- Select District --');
        }
    });
});

document.querySelectorAll('.edit-district').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var zoneId = this.dataset.zoneId;
        var upzSel = document.querySelector('.edit-upazila[data-zone-id="' + zoneId + '"]');
        upzSel.innerHTML = '<option value="">-- Select District First --</option>';
        if (this.value) {
            loadOptions('/admin/api/upazilas-by-district/' + this.value, upzSel, '-- Select Thana/Upazila --');
        }
    });
});

function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
    updateSelection();
}

function updateSelection() {
    var checkboxes = document.querySelectorAll('.item-checkbox:checked');
    var count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';
    
    var allBoxes = document.querySelectorAll('.item-checkbox');
    document.getElementById('selectAll').checked = allBoxes.length > 0 && allBoxes.length === count;
}

function bulkDelete() {
    var checkboxes = document.querySelectorAll('.item-checkbox:checked');
    var count = checkboxes.length;
    if (count === 0) return;
    
    if (confirm('Delete ' + count + ' selected zone(s)?')) {
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
    if (confirm('Delete this zone?')) {
        var form = document.getElementById('singleDeleteForm');
        form.action = '{{ url("admin/settings/zones") }}/' + id;
        form.submit();
    }
}
</script>
@endsection
