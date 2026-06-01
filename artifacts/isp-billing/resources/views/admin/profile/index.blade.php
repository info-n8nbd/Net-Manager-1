@extends('layouts.admin')

@section('title', 'My Profile')

@push('styles')
<style>
/* ========== Profile Page Mobile Compact Styles ========== */
.profile-card-top {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border-radius: 16px;
    padding: 24px 20px;
    color: white;
    text-align: center;
    margin-bottom: 16px;
}
.profile-avatar-circle {
    width: 90px; height: 90px;
    background: rgba(255,255,255,0.2);
    border: 3px solid rgba(255,255,255,0.5);
    border-radius: 50%;
    font-size: 38px;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 12px;
    color: white;
    letter-spacing: 1px;
}
.profile-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 12px;
}
.profile-info-item {
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    padding: 10px 12px;
    text-align: left;
}
.profile-info-item .info-label {
    font-size: 10px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.profile-info-item .info-value {
    font-size: 13px;
    font-weight: 600;
    word-break: break-all;
}
.profile-info-item.full-width {
    grid-column: 1 / -1;
}
.profile-tabs-card {
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    border: none;
}
.profile-nav-pills .nav-link {
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 16px;
    color: #1e3c72;
}
.profile-nav-pills .nav-link.active {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: white;
}
@media (max-width: 767px) {
    .profile-avatar-circle { width: 72px; height: 72px; font-size: 30px; }
    .profile-card-top { padding: 18px 14px; }
    .profile-card-top h4 { font-size: 16px; margin-bottom: 2px; }
    .profile-card-top p { font-size: 12px; margin-bottom: 8px; }
    .profile-info-item { padding: 8px 10px; }
    .profile-info-item .info-value { font-size: 12px; }
    /* Force full width on mobile regardless of Bootstrap grid */
    .col-md-3.col-12,
    .col-md-9.col-12 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }
}
</style>
@endpush

@section('content')
        <div class="row">
            {{-- Profile Info Card (Left on desktop, Top on mobile) --}}
            <div class="col-md-3 col-12 mb-3">
                <div class="profile-card-top">
                    <div class="profile-avatar-circle">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <h4 class="mb-0 font-weight-bold">{{ $user->name }}</h4>
                    <p class="mb-0" style="opacity:0.8;font-size:13px;">{{ ucfirst(str_replace('_', ' ', $user->user_type ?? 'Admin')) }}</p>

                    <div class="profile-info-grid">
                        <div class="profile-info-item full-width">
                            <div class="info-label"><i class="fas fa-envelope mr-1"></i>Email</div>
                            <div class="info-value">{{ $user->email }}</div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-label"><i class="fas fa-phone mr-1"></i>Phone</div>
                            <div class="info-value">{{ $user->phone ?? 'Not set' }}</div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-label"><i class="fas fa-calendar mr-1"></i>Since</div>
                            <div class="info-value">{{ $user->created_at->format('d-m-Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs Card (Right on desktop, Bottom on mobile) --}}
            <div class="col-md-9 col-12">
                <div class="card profile-tabs-card">
                    <div class="card-header p-2" style="background:#f8f9fa;border-bottom:1px solid #eee;">
                        <ul class="nav nav-pills profile-nav-pills">
                            <li class="nav-item">
                                <a class="nav-link active" href="#profile" data-toggle="tab">
                                    <i class="fas fa-user mr-1"></i> Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#password" data-toggle="tab">
                                    <i class="fas fa-lock mr-1"></i> Change Password
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="active tab-pane" id="profile">
                                <form action="{{ route('admin.profile.update') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="name" class="font-weight-600 text-dark">Full Name</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                               id="name" name="name" value="{{ old('name', $user->name) }}">
                                        @error('name')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="email" class="font-weight-600 text-dark">Email Address</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                               id="email" name="email" value="{{ old('email', $user->email) }}">
                                        @error('email')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="phone" class="font-weight-600 text-dark">Phone Number</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                               id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                        @error('phone')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Update Profile
                                    </button>
                                </form>
                            </div>

                            <div class="tab-pane" id="password">
                                <form action="{{ route('admin.profile.password') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="current_password" class="font-weight-600 text-dark">Current Password</label>
                                        <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                               id="current_password" name="current_password">
                                        @error('current_password')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="password" class="font-weight-600 text-dark">New Password</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                                               id="password" name="password">
                                        @error('password')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="password_confirmation" class="font-weight-600 text-dark">Confirm New Password</label>
                                        <input type="password" class="form-control"
                                               id="password_confirmation" name="password_confirmation">
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key mr-1"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
