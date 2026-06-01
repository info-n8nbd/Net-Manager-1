@extends('layouts.admin')

@section('title', 'Technicians')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.employees') }}">HRM</a></li>
<li class="breadcrumb-item active">Technicians</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $technicians->total() }}</div>
            <div class="stat-label">Technicians</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $technicians->where('is_active', true)->count() }}</div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
            <div class="stat-number">{{ $zones->count() }}</div>
            <div class="stat-label">Zones</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <a href="{{ route('admin.hrm.technicians.performance') }}" class="text-decoration-none">
            <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
                <div class="stat-number"><i class="fas fa-chart-line"></i></div>
                <div class="stat-label">Performance</div>
            </div>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users-cog mr-2"></i>Technician Accounts</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Assigned Zones</th>
                            <th>Status</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($technicians as $tech)
                        <tr>
                            <td>
                                <a href="{{ route('admin.hrm.technicians.show', $tech) }}">
                                    <strong>{{ $tech->name }}</strong>
                                </a>
                            </td>
                            <td>{{ $tech->email }}</td>
                            <td>{{ $tech->phone }}</td>
                            <td>
                                @if($tech->employee && $tech->employee->zone_ids)
                                    @php
                                        $zoneNames = $zones->whereIn('id', $tech->employee->zone_ids)->pluck('name');
                                    @endphp
                                    @foreach($zoneNames as $zoneName)
                                        <span class="badge badge-info">{{ $zoneName }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">No zones assigned</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $tech->is_active ? 'success' : 'danger' }}">
                                    {{ $tech->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.hrm.technicians.show', $tech) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.hrm.technicians.edit', $tech) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.hrm.technicians.destroy', $tech) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this technician?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-users-cog fa-3x mb-3 d-block"></i>
                                No technicians found. Add your first technician using the form.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
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
        {{ $technicians->appends(request()->query())->links() }}
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
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Add New Technician</h3>
            </div>
            <form action="{{ route('admin.hrm.technicians.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                        @error('phone')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>NID Number</label>
                        <input type="text" name="nid" class="form-control @error('nid') is-invalid @enderror" value="{{ old('nid') }}" placeholder="National ID Number">
                        @error('nid')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Assigned Zones <span class="text-danger">*</span></label>
                        @php $oldZoneIds = array_map('intval', old('zone_ids', [])); @endphp

                        {{-- Hidden select keeps form data --}}
                        <input type="hidden" name="zone_ids" value="">
                        <select name="zone_ids[]" id="zoneHiddenSelect" multiple style="display:none;" required>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ in_array($zone->id, $oldZoneIds) ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="card card-outline card-secondary mb-0">
                                    <div class="card-header p-2 text-center"><strong>Available Zones</strong></div>
                                    <div class="card-body p-0">
                                        <ul id="zoneAvailable" class="list-group list-group-flush zone-list" style="min-height:120px;max-height:200px;overflow-y:auto;cursor:pointer;">
                                            @foreach($zones as $zone)
                                                @if(!in_array($zone->id, $oldZoneIds))
                                                    <li class="list-group-item list-group-item-action py-1 px-2" data-id="{{ $zone->id }}" data-name="{{ $zone->name }}">
                                                        {{ $zone->name }}
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center" style="gap:8px;">
                                <button type="button" id="btnAddZone" class="btn btn-sm btn-primary" title="Add selected">&#x276F;</button>
                                <button type="button" id="btnRemoveZone" class="btn btn-sm btn-danger" title="Remove selected">&#x276E;</button>
                            </div>
                            <div class="col-md-5">
                                <div class="card card-outline card-primary mb-0">
                                    <div class="card-header p-2 text-center"><strong>Assigned Zones</strong></div>
                                    <div class="card-body p-0">
                                        <ul id="zoneAssigned" class="list-group list-group-flush zone-list" style="min-height:120px;max-height:200px;overflow-y:auto;cursor:pointer;">
                                            @foreach($zones as $zone)
                                                @if(in_array($zone->id, $oldZoneIds))
                                                    <li class="list-group-item list-group-item-action list-group-item-primary py-1 px-2" data-id="{{ $zone->id }}" data-name="{{ $zone->name }}">
                                                        {{ $zone->name }}
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Click a zone to select it (highlighted), then use arrows to move. Double-click to move instantly.</small>
                        @error('zone_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id" class="form-control">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Basic Salary (৳)</label>
                        <input type="number" name="basic_salary" class="form-control" value="{{ old('basic_salary', 0) }}" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                    </div>
                    
                    <hr>
                    <h6 class="text-info"><i class="fas fa-file-image mr-1"></i> Documents & Photos</h6>
                    
                    <div class="form-group">
                        <label>Photo</label>
                        <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                        @error('photo')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>NID Photo (Front)</label>
                        <input type="file" name="nid_photo_front" class="form-control @error('nid_photo_front') is-invalid @enderror" accept="image/*">
                        @error('nid_photo_front')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>NID Photo (Back)</label>
                        <input type="file" name="nid_photo_back" class="form-control @error('nid_photo_back') is-invalid @enderror" accept="image/*">
                        @error('nid_photo_back')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label>CV / Resume</label>
                        <input type="file" name="cv_file" class="form-control @error('cv_file') is-invalid @enderror" accept=".pdf,.doc,.docx">
                        @error('cv_file')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <hr>
                    
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                        <label class="custom-control-label" for="is_active">Active Account</label>
                    </div>

                    @include('admin.partials.menu-access-control', ['showMenuAccess' => true, 'menuPermMap' => $menuPermMap ?? []])
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus mr-2"></i>Create Technician Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function syncHiddenSelect() {
        $('#zoneAssigned li').each(function() {
            var id = $(this).data('id');
            $('#zoneHiddenSelect option[value="' + id + '"]').prop('selected', true);
        });
        $('#zoneAvailable li').each(function() {
            var id = $(this).data('id');
            $('#zoneHiddenSelect option[value="' + id + '"]').prop('selected', false);
        });
    }

    function moveItems(fromList, toList, isAssigning) {
        fromList.find('li.active').each(function() {
            var $li = $(this).removeClass('active');
            if (isAssigning) {
                $li.addClass('list-group-item-primary');
            } else {
                $li.removeClass('list-group-item-primary');
            }
            var name = $li.data('name').toLowerCase();
            var inserted = false;
            toList.find('li').each(function() {
                if ($(this).data('name').toLowerCase() > name) {
                    $li.insertBefore($(this));
                    inserted = true;
                    return false;
                }
            });
            if (!inserted) toList.append($li);
        });
        syncHiddenSelect();
    }

    $(document).on('click', '.zone-list li', function() {
        $(this).toggleClass('active');
    });

    $(document).on('dblclick', '#zoneAvailable li', function() {
        $(this).addClass('active');
        moveItems($('#zoneAvailable'), $('#zoneAssigned'), true);
    });
    $(document).on('dblclick', '#zoneAssigned li', function() {
        $(this).addClass('active');
        moveItems($('#zoneAssigned'), $('#zoneAvailable'), false);
    });

    $('#btnAddZone').on('click', function() {
        moveItems($('#zoneAvailable'), $('#zoneAssigned'), true);
    });
    $('#btnRemoveZone').on('click', function() {
        moveItems($('#zoneAssigned'), $('#zoneAvailable'), false);
    });

    $('form').on('submit', function() {
        syncHiddenSelect();
        if ($('#zoneAssigned li').length === 0) {
            alert('Please assign at least one zone.');
            return false;
        }
    });

    syncHiddenSelect();
});
</script>
@endpush
