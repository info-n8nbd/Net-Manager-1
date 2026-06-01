@extends('layouts.admin')

@section('title', 'Edit Technician')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.technicians') }}">Technicians</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Edit Technician: {{ $technician->name }}</h3>
            </div>
            <form action="{{ route('admin.hrm.technicians.update', $technician) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $technician->name) }}" required>
                                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $technician->email) }}" required>
                                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $technician->phone) }}" required>
                                @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NID Number</label>
                                <input type="text" name="nid" class="form-control @error('nid') is-invalid @enderror" 
                                       value="{{ old('nid', $technician->employee?->nid) }}" placeholder="National ID Number">
                                @error('nid')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Assigned Zones <span class="text-danger">*</span></label>
                        @php
                            $assignedZoneIds = old('zone_ids', $technician->employee?->zone_ids ?? []);
                            $assignedZoneIds = array_map('intval', $assignedZoneIds);
                        @endphp

                        {{-- Hidden select keeps form data --}}
                        <input type="hidden" name="zone_ids" value="">
                        <select name="zone_ids[]" id="zoneHiddenSelect" multiple style="display:none;" required>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ in_array($zone->id, $assignedZoneIds) ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="card card-outline card-secondary mb-0">
                                    <div class="card-header p-2 text-center"><strong>Available Zones</strong></div>
                                    <div class="card-body p-0">
                                        <ul id="zoneAvailable" class="list-group list-group-flush zone-list" style="min-height:120px;max-height:200px;overflow-y:auto;cursor:pointer;">
                                            @foreach($zones as $zone)
                                                @if(!in_array($zone->id, $assignedZoneIds))
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
                                                @if(in_array($zone->id, $assignedZoneIds))
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department_id" class="form-control">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $technician->employee?->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Basic Salary (৳)</label>
                                <input type="number" name="basic_salary" class="form-control" 
                                       value="{{ old('basic_salary', $technician->employee?->basic_salary ?? 0) }}" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $technician->employee?->address) }}</textarea>
                    </div>
                    
                    <hr>
                    <h5 class="mb-3"><i class="fas fa-file-image mr-2"></i>Documents & Photos</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                @if($technician->employee?->photo)
                                <small class="text-success"><i class="fas fa-check mr-1"></i>Photo uploaded</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>CV / Resume</label>
                                <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx">
                                @if($technician->employee?->cv_file)
                                <small class="text-success"><i class="fas fa-check mr-1"></i>CV uploaded</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NID Photo (Front)</label>
                                <input type="file" name="nid_photo_front" class="form-control" accept="image/*">
                                @if($technician->employee?->nid_photo_front)
                                <small class="text-success"><i class="fas fa-check mr-1"></i>Front uploaded</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NID Photo (Back)</label>
                                <input type="file" name="nid_photo_back" class="form-control" accept="image/*">
                                @if($technician->employee?->nid_photo_back)
                                <small class="text-success"><i class="fas fa-check mr-1"></i>Back uploaded</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" 
                               {{ old('is_active', $technician->is_active) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">Active Account</label>
                    </div>
                </div>
                
                <div class="card-body pt-0">
                    @php $techEmp = $employee ?? $technician->employee; @endphp
                    @include('admin.partials.user-menu-override', ['employee' => $techEmp, 'userEffectivePerms' => $userEffectivePerms ?? []])
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Update Technician
                    </button>
                    <a href="{{ route('admin.hrm.technicians') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Account Info</h3>
            </div>
            <div class="card-body">
                <dl>
                    <dt>Created</dt>
                    <dd>{{ $technician->created_at->format('d-m-y h:i A') }}</dd>
                    
                    <dt>Last Updated</dt>
                    <dd>{{ $technician->updated_at->format('d-m-y h:i A') }}</dd>
                    
                    @if($technician->employee)
                    <dt>Employee ID</dt>
                    <dd>{{ $technician->employee->employee_id }}</dd>
                    
                    <dt>Joining Date</dt>
                    <dd>{{ $technician->employee->joining_date?->format('d-m-Y') ?? 'N/A' }}</dd>
                    @endif
                </dl>
            </div>
        </div>
        
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone</h3>
            </div>
            <div class="card-body">
                <p>Deleting this technician will permanently remove their account and all associated data.</p>
                <form action="{{ route('admin.hrm.technicians.destroy', $technician) }}" method="POST" 
                      onsubmit="return confirm('Are you sure you want to delete this technician? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash mr-2"></i>Delete Technician
                    </button>
                </form>
            </div>
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
            // Insert alphabetically
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

    // Click to select/deselect in list
    $(document).on('click', '.zone-list li', function() {
        $(this).toggleClass('active');
    });

    // Double-click to instantly move
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

    // Before submit, select all assigned in hidden select
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
