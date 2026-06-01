@extends('layouts.admin')

@section('title', 'Thana / Upazila')
@section('breadcrumb')
<li class="breadcrumb-item active">Thana / Upazila</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Thana / Upazila</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr style="background-color: #3c8dbc; color: #fff;">
                            <th style="width: 40px;">#</th>
                            <th>Division</th>
                            <th>District</th>
                            <th>Thana/Upazila</th>
                            <th>Status</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upazilas as $upazila)
                        <tr>
                            <td>{{ $loop->iteration + ($upazilas->currentPage() - 1) * $upazilas->perPage() }}</td>
                            <td>{{ $upazila->district->division ?? '-' }}</td>
                            <td>{{ $upazila->district->name ?? '-' }}</td>
                            <td>{{ $upazila->name }}</td>
                            <td><span class="badge badge-{{ $upazila->is_active ? 'success' : 'danger' }}">{{ $upazila->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editModal{{ $upazila->id }}"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSingle({{ $upazila->id }})"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No upazilas found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
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
        {{ $upazilas->appends(request()->query())->links() }}
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
            <div class="card-header"><h3 class="card-title">Add Thana/Upazila</h3></div>
            <form action="{{ route('admin.config.upazila.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Division <span class="text-danger">*</span></label>
                        <select id="addDivision" class="form-control form-control-sm" required>
                            <option value="">Select Division</option>
                            @php $divisions = $districts->pluck('division')->unique()->sort()->filter(); @endphp
                            @foreach($divisions as $div)
                            <option value="{{ $div }}">{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District <span class="text-danger">*</span></label>
                        <select name="district_id" id="addDistrict" class="form-control form-control-sm" required disabled>
                            <option value="">Select Division First</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thana/Upazila <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active_new" name="is_active" checked>
                            <label class="custom-control-label" for="is_active_new">Active</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary btn-sm">Add Thana/Upazila</button></div>
            </form>
        </div>
    </div>
</div>

<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

@foreach($upazilas as $upazila)
<div class="modal fade" id="editModal{{ $upazila->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.config.upazila.update', $upazila) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white">Edit Thana/Upazila</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Division <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm edit-division" data-target="editDistrict{{ $upazila->id }}" data-selected-district="{{ $upazila->district_id }}" required>
                            <option value="">Select Division</option>
                            @foreach($divisions as $div)
                            <option value="{{ $div }}" {{ ($upazila->district->division ?? '') == $div ? 'selected' : '' }}>{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District <span class="text-danger">*</span></label>
                        <select name="district_id" id="editDistrict{{ $upazila->id }}" class="form-control form-control-sm" required>
                            <option value="{{ $upazila->district_id }}">{{ $upazila->district->name ?? '-' }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thana/Upazila <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $upazila->name }}" required>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active_{{ $upazila->id }}" name="is_active" {{ $upazila->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active_{{ $upazila->id }}">Active</label>
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
var districtsByDivision = @json($districts->groupBy('division')->map(function($items) { return $items->map(function($d) { return ['id' => $d->id, 'name' => $d->name]; })->values(); }));

function populateDistrictSelect(divisionValue, selectEl, selectedId) {
    selectEl.innerHTML = '<option value="">Select District</option>';
    if (divisionValue && districtsByDivision[divisionValue]) {
        selectEl.disabled = false;
        districtsByDivision[divisionValue].forEach(function(d) {
            var opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.name;
            if (d.id == selectedId) opt.selected = true;
            selectEl.appendChild(opt);
        });
    } else {
        selectEl.disabled = true;
        selectEl.innerHTML = '<option value="">Select Division First</option>';
    }
}

document.getElementById('addDivision').addEventListener('change', function() {
    populateDistrictSelect(this.value, document.getElementById('addDistrict'), null);
});

document.querySelectorAll('.edit-division').forEach(function(sel) {
    var targetId = sel.getAttribute('data-target');
    var selectedDistrict = sel.getAttribute('data-selected-district');
    var districtSelect = document.getElementById(targetId);
    populateDistrictSelect(sel.value, districtSelect, selectedDistrict);
    sel.addEventListener('change', function() {
        populateDistrictSelect(this.value, districtSelect, null);
    });
});

function deleteSingle(id) {
    if (confirm('Delete this upazila?')) {
        var form = document.getElementById('singleDeleteForm');
        form.action = '{{ url("admin/config/upazila") }}/' + id;
        form.submit();
    }
}
</script>
@endsection
