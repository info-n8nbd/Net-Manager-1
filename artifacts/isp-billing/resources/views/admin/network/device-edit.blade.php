@extends('layouts.admin')

@section('title', 'Edit MikroTik Device')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">MikroTik Devices</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit MikroTik Device: {{ $device->name }}</h3>
            </div>
            <form action="{{ route('admin.network.devices.update', $device) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Device Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $device->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Router Type</label>
                                <select name="router_type" class="form-control">
                                    <option value="core" {{ old('router_type', $device->router_type) == 'core' ? 'selected' : '' }}>Core Router</option>
                                    <option value="noc" {{ old('router_type', $device->router_type) == 'noc' ? 'selected' : '' }}>NOC Router</option>
                                    <option value="pop" {{ old('router_type', $device->router_type) == 'pop' ? 'selected' : '' }}>POP Router</option>
                                    <option value="branch" {{ old('router_type', $device->router_type) == 'branch' ? 'selected' : '' }}>Branch Router</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model" class="form-control" value="{{ old('model', $device->model) }}" placeholder="e.g., CCR1036">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Host/IP Address <span class="text-danger">*</span></label>
                                <input type="text" name="host" class="form-control @error('host') is-invalid @enderror" value="{{ old('host', $device->host) }}" required>
                                @error('host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>API Port</label>
                                <input type="number" name="port" class="form-control" value="{{ old('port', $device->port) }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mikrotik User Name <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $device->username) }}" required>
                                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mikrotik Password <small class="text-muted">(leave blank to keep current)</small></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>WWW Port</label>
                                <input type="number" name="www_port" class="form-control" value="{{ old('www_port', $device->www_port ?? 80) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>VPN Account</label>
                                <select name="vpn_account_id" class="form-control">
                                    <option value="">-- None --</option>
                                    @foreach($vpnAccounts as $vpn)
                                    <option value="{{ $vpn->id }}" {{ old('vpn_account_id', $device->vpn_account_id) == $vpn->id ? 'selected' : '' }}>
                                        {{ $vpn->username }} ({{ $vpn->vpn_type }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Overwrite PPP Secrets Comment</label>
                                <select name="overwrite_ppp_comment" class="form-control">
                                    <option value="1" {{ old('overwrite_ppp_comment', $device->overwrite_ppp_comment) ? 'selected' : '' }}>yes</option>
                                    <option value="0" {{ !old('overwrite_ppp_comment', $device->overwrite_ppp_comment) ? 'selected' : '' }}>no</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ ucfirst($device->status ?? 'Unknown') }}" readonly>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info" onclick="testConnection()">
                                            <i class="fas fa-plug"></i> Test
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Zone</label>
                                <select name="zone_id" class="form-control">
                                    <option value="">Select Zone</option>
                                    @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}" {{ old('zone_id', $device->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" name="latitude" class="form-control" value="{{ old('latitude', $device->latitude) }}" placeholder="23.8103">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="longitude" class="form-control" value="{{ old('longitude', $device->longitude) }}" placeholder="90.4125">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $device->description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $device->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-2"></i>Update Device</button>
                    <a href="{{ route('admin.network.devices') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function testConnection() {
    Swal.fire({
        title: 'Testing Connection...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: '{{ route("admin.network.devices.test", $device) }}',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success!', response.message, 'success');
            } else {
                Swal.fire('Failed!', response.message, 'error');
            }
        },
        error: function(xhr) {
            var msg = 'Could not test connection.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            } else if (xhr.status === 500) {
                msg = 'Server error (500). Check logs for details.';
            } else if (xhr.status === 419) {
                msg = 'Session expired. Please refresh the page and try again.';
            } else if (xhr.status === 0) {
                msg = 'Network error - request could not reach server.';
            }
            Swal.fire('Error!', msg, 'error');
        }
    });
}
</script>
@endpush
