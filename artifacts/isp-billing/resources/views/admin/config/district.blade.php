@extends('layouts.admin')

@section('title', 'Districts')
@section('breadcrumb')
<li class="breadcrumb-item active">Districts</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Districts</h3>
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
                        @forelse($districts as $district)
                        <tr>
                            <td>{{ $loop->iteration + ($districts->currentPage() - 1) * $districts->perPage() }}</td>
                            <td>{{ $district->division ?? '-' }}</td>
                            <td>{{ $district->name }}</td>
                            <td>{{ $district->upazilas_count }}</td>
                            <td><span class="badge badge-{{ $district->is_active ? 'success' : 'danger' }}">{{ $district->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editModal{{ $district->id }}"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteSingle({{ $district->id }})"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No districts found</td></tr>
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
        {{ $districts->appends(request()->query())->links() }}
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
            <div class="card-header"><h3 class="card-title">Add District</h3></div>
            <form action="{{ route('admin.config.district.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Division <span class="text-danger">*</span></label>
                        <select name="division" id="addDivision" class="form-control form-control-sm" required>
                            <option value="">Select Division</option>
                            <option value="Barishal">Barishal</option>
                            <option value="Chattogram">Chattogram</option>
                            <option value="Dhaka">Dhaka</option>
                            <option value="Khulna">Khulna</option>
                            <option value="Mymensingh">Mymensingh</option>
                            <option value="Rajshahi">Rajshahi</option>
                            <option value="Rangpur">Rangpur</option>
                            <option value="Sylhet">Sylhet</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District <span class="text-danger">*</span></label>
                        <select name="name" id="addDistrict" class="form-control form-control-sm" required disabled>
                            <option value="">Select Division First</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active_new" name="is_active" checked>
                            <label class="custom-control-label" for="is_active_new">Active</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary btn-sm">Add District</button></div>
            </form>
        </div>
    </div>
</div>

<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

@foreach($districts as $district)
<div class="modal fade" id="editModal{{ $district->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.config.district.update', $district) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white">Edit District</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Division <span class="text-danger">*</span></label>
                        <select name="division" class="form-control form-control-sm edit-division" data-district="{{ $district->name }}" required>
                            <option value="">Select Division</option>
                            @foreach(['Barishal','Chattogram','Dhaka','Khulna','Mymensingh','Rajshahi','Rangpur','Sylhet'] as $div)
                            <option value="{{ $div }}" {{ $district->division == $div ? 'selected' : '' }}>{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District <span class="text-danger">*</span></label>
                        <select name="name" class="form-control form-control-sm edit-district" required>
                            <option value="{{ $district->name }}" selected>{{ $district->name }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active_{{ $district->id }}" name="is_active" {{ $district->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active_{{ $district->id }}">Active</label>
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
var districtsByDivision = {
    'Barishal': ['Barguna','Barishal','Bhola','Jhalokati','Patuakhali','Pirojpur'],
    'Chattogram': ['Bandarban','Brahmanbaria','Chandpur','Chattogram','Comilla','Cox\'s Bazar','Feni','Khagrachhari','Lakshmipur','Noakhali','Rangamati'],
    'Dhaka': ['Dhaka','Faridpur','Gazipur','Gopalganj','Kishoreganj','Madaripur','Manikganj','Munshiganj','Narayanganj','Narsingdi','Rajbari','Shariatpur','Tangail'],
    'Khulna': ['Bagerhat','Chuadanga','Jessore','Jhenaidah','Khulna','Kushtia','Magura','Meherpur','Narail','Satkhira'],
    'Mymensingh': ['Jamalpur','Mymensingh','Netrokona','Sherpur'],
    'Rajshahi': ['Bogura','Chapainawabganj','Joypurhat','Naogaon','Natore','Nawabganj','Pabna','Rajshahi','Sirajganj'],
    'Rangpur': ['Dinajpur','Gaibandha','Kurigram','Lalmonirhat','Nilphamari','Panchagarh','Rangpur','Thakurgaon'],
    'Sylhet': ['Habiganj','Moulvibazar','Sunamganj','Sylhet']
};

function populateDistricts(divisionSelect, districtSelect, selectedDistrict) {
    var division = divisionSelect.value;
    districtSelect.innerHTML = '<option value="">Select District</option>';
    if (division && districtsByDivision[division]) {
        districtSelect.disabled = false;
        districtsByDivision[division].forEach(function(d) {
            var opt = document.createElement('option');
            opt.value = d;
            opt.textContent = d;
            if (d === selectedDistrict) opt.selected = true;
            districtSelect.appendChild(opt);
        });
    } else {
        districtSelect.disabled = true;
        districtSelect.innerHTML = '<option value="">Select Division First</option>';
    }
}

document.getElementById('addDivision').addEventListener('change', function() {
    populateDistricts(this, document.getElementById('addDistrict'), null);
});

document.querySelectorAll('.edit-division').forEach(function(sel) {
    var districtSelect = sel.closest('.modal-body').querySelector('.edit-district');
    var currentDistrict = sel.getAttribute('data-district');
    populateDistricts(sel, districtSelect, currentDistrict);
    sel.addEventListener('change', function() {
        populateDistricts(this, districtSelect, null);
    });
});

function deleteSingle(id) {
    if (confirm('Delete this district?')) {
        var form = document.getElementById('singleDeleteForm');
        form.action = '{{ url("admin/config/district") }}/' + id;
        form.submit();
    }
}
</script>
@endsection
