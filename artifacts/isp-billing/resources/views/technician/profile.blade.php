@extends('layouts.technician')

@section('title', 'My Profile')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">My Profile</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <div class="position-relative d-inline-block">
                        @if($user->avatar)
                        <img class="profile-user-img img-fluid img-circle" src="{{ asset('uploads/avatars/' . $user->avatar) }}" alt="Profile Picture" style="width: 128px; height: 128px; object-fit: cover;">
                        @else
                        <div class="profile-user-img img-fluid img-circle bg-primary d-flex align-items-center justify-content-center" style="width: 128px; height: 128px; margin: 0 auto;">
                            <span class="text-white" style="font-size: 48px;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                        @endif
                        <button type="button" class="btn btn-primary btn-sm position-absolute" style="bottom: 0; right: 0;" data-toggle="modal" data-target="#avatarModal">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                </div>
                <h3 class="profile-username text-center mt-3">{{ $user->name }}</h3>
                <p class="text-muted text-center">
                    <span class="badge badge-info">{{ ucfirst($user->user_type ?? 'Technician') }}</span>
                </p>
                
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b><i class="fas fa-envelope mr-2"></i>Email</b>
                        <span class="float-right">{{ $user->email }}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-phone mr-2"></i>Phone</b>
                        <span class="float-right">{{ $user->phone ?? 'Not set' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-calendar mr-2"></i>Joined</b>
                        <span class="float-right">{{ $user->created_at->format('d-m-Y') }}</span>
                    </li>
                    @if($employee)
                    <li class="list-group-item">
                        <b><i class="fas fa-building mr-2"></i>Department</b>
                        <span class="float-right">{{ $employee->department->name ?? 'N/A' }}</span>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
        
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>My Statistics</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total Collections</span>
                        <strong>{{ number_format($stats['total_collections'] ?? 0) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total Amount Collected</span>
                        <strong class="text-success">৳{{ number_format($stats['total_amount'] ?? 0, 0) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>This Month Collection</span>
                        <strong class="text-primary">৳{{ number_format($stats['this_month'], 0) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Tickets Resolved</span>
                        <strong class="text-info">{{ number_format($stats['tickets_resolved']) }}</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#profile" data-toggle="tab"><i class="fas fa-user mr-1"></i> Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="#documents" data-toggle="tab"><i class="fas fa-file-alt mr-1"></i> Documents</a></li>
                    <li class="nav-item"><a class="nav-link" href="#password" data-toggle="tab"><i class="fas fa-lock mr-1"></i> Password</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active" id="profile">
                        <form action="{{ route('technician.profile.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" class="form-control" value="{{ $user->email }}" readonly disabled>
                                <small class="text-muted">Email cannot be changed. Contact admin if needed.</small>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="01XXXXXXXXX">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Your address...">{{ old('address', $employee->address ?? '') }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Update Profile</button>
                        </form>
                    </div>
                    
                    <div class="tab-pane" id="documents">
                        @if($employee)
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-primary text-white py-2">
                                        <i class="fas fa-user mr-2"></i>My Photo
                                    </div>
                                    <div class="card-body text-center">
                                        @if($employee->photo)
                                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="Photo" class="img-fluid rounded" style="max-height: 150px;">
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $employee->photo) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-expand mr-1"></i>View Full
                                            </a>
                                        </div>
                                        @else
                                        <div class="text-muted py-3"><i class="fas fa-image fa-2x mb-2"></i><p class="mb-0 small">No photo uploaded</p></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-info text-white py-2">
                                        <i class="fas fa-file-pdf mr-2"></i>My CV
                                    </div>
                                    <div class="card-body text-center">
                                        @if($employee->cv_file)
                                        <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                        <p class="mb-2 small">CV Available</p>
                                        <a href="{{ asset('storage/' . $employee->cv_file) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </a>
                                        @else
                                        <div class="text-muted py-3"><i class="fas fa-file-alt fa-2x mb-2"></i><p class="mb-0 small">No CV uploaded</p></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-success text-white py-2">
                                        <i class="fas fa-id-card mr-2"></i>NID (Front)
                                    </div>
                                    <div class="card-body text-center">
                                        @if($employee->nid_photo_front)
                                        <img src="{{ asset('storage/' . $employee->nid_photo_front) }}" alt="NID Front" class="img-fluid rounded" style="max-height: 120px;">
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $employee->nid_photo_front) }}" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-expand mr-1"></i>View
                                            </a>
                                        </div>
                                        @else
                                        <div class="text-muted py-3"><i class="fas fa-id-card fa-2x mb-2"></i><p class="mb-0 small">Not uploaded</p></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-warning text-dark py-2">
                                        <i class="fas fa-id-card mr-2"></i>NID (Back)
                                    </div>
                                    <div class="card-body text-center">
                                        @if($employee->nid_photo_back)
                                        <img src="{{ asset('storage/' . $employee->nid_photo_back) }}" alt="NID Back" class="img-fluid rounded" style="max-height: 120px;">
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $employee->nid_photo_back) }}" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-expand mr-1"></i>View
                                            </a>
                                        </div>
                                        @else
                                        <div class="text-muted py-3"><i class="fas fa-id-card fa-2x mb-2"></i><p class="mb-0 small">Not uploaded</p></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($employee->nid)
                        <div class="alert alert-light border mt-2">
                            <i class="fas fa-info-circle mr-2 text-primary"></i><strong>NID Number:</strong> {{ $employee->nid }}
                        </div>
                        @endif
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle mr-2"></i>Contact your administrator to update your documents.
                        </div>
                        @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>No employee record linked to your account.</p>
                        </div>
                        @endif
                    </div>
                    
                    <div class="tab-pane" id="password">
                        <form action="{{ route('technician.profile.password') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>Current Password <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>New Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-key mr-2"></i>Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-camera mr-2"></i>Change Profile Picture</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('technician.profile.avatar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="text-center mb-3">
                        @if($user->avatar)
                        <img id="avatarPreview" src="{{ asset('uploads/avatars/' . $user->avatar) }}" class="img-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                        <div id="avatarPreview" class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px;">
                            <span class="text-white" style="font-size: 60px;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Select Image</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input @error('avatar') is-invalid @enderror" id="avatarInput" name="avatar" accept="image/*" required>
                            <label class="custom-file-label" for="avatarInput">Choose file</label>
                            @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <small class="text-muted">Max 2MB. Formats: JPEG, PNG, GIF</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-2"></i>Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
        
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').attr('src', e.target.result).removeClass('d-flex align-items-center justify-content-center bg-primary').html('');
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>
@endpush
