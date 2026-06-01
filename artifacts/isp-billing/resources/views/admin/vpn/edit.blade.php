@extends('layouts.admin')

@section('title', 'Edit VPN Account')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.vpn.index') }}">VPN Accounts</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit VPN Account: {{ $vpnAccount->name ?? $vpnAccount->username }}</h3>
                </div>
                <form action="{{ route('admin.vpn.update', $vpnAccount) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card-body">
                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Account Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $vpnAccount->name) }}" placeholder="e.g., Main Office VPN" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Account Type <span class="text-danger">*</span></label>
                                    <select name="account_type" class="form-control @error('account_type') is-invalid @enderror" required>
                                        @foreach($accountTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('account_type', $vpnAccount->account_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>VPN Type <span class="text-danger">*</span></label>
                                    <select name="vpn_type" class="form-control @error('vpn_type') is-invalid @enderror" required>
                                        @foreach($vpnTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('vpn_type', $vpnAccount->vpn_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('vpn_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SSTP/VPN Server Address</label>
                                    <input type="text" name="server_address" class="form-control @error('server_address') is-invalid @enderror" value="{{ old('server_address', $vpnAccount->server_address) }}" placeholder="e.g., vpn.yourisp.com">
                                    @error('server_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-bold"><i class="fas fa-key mr-1"></i>Credentials</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $vpnAccount->username) }}" required>
                                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Password <small class="text-muted">(leave blank to keep current)</small></label>
                                    <input type="text" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Leave blank to keep current">
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-bold"><i class="fas fa-network-wired mr-1"></i>Network Configuration</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Local Address</label>
                                    <input type="text" name="local_address" class="form-control" value="{{ old('local_address', $vpnAccount->local_address) }}" placeholder="e.g., 100.127.0.1">
                                    <small class="text-muted">VPN server-side tunnel IP</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Remote Address (VPN IP)</label>
                                    <input type="text" name="remote_address" class="form-control" value="{{ old('remote_address', $vpnAccount->remote_address) }}" placeholder="e.g., 100.127.0.2">
                                    <small class="text-muted">Router IP in VPN tunnel</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>DNS Server</label>
                                    <input type="text" name="dns_server" class="form-control" value="{{ old('dns_server', $vpnAccount->dns_server) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Profile</label>
                                    <input type="text" name="profile" class="form-control" value="{{ old('profile', $vpnAccount->profile) }}">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-bold"><i class="fas fa-cog mr-1"></i>Additional Settings</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Managed on Router</label>
                                    <select name="mikrotik_device_id" class="form-control">
                                        <option value="">Select Router (optional)</option>
                                        @foreach($mikrotikDevices as $device)
                                        <option value="{{ $device->id }}" {{ old('mikrotik_device_id', $vpnAccount->mikrotik_device_id) == $device->id ? 'selected' : '' }}>
                                            {{ $device->name }} ({{ $device->host }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Monthly Bill (&#2547;)</label>
                                    <input type="number" name="monthly_fee" class="form-control" value="{{ old('monthly_fee', $vpnAccount->monthly_fee) }}" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $vpnAccount->notes) }}</textarea>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $vpnAccount->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.vpn.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-warning float-right">
                            <i class="fas fa-save mr-1"></i>Update VPN Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            @if($vpnAccount->linkedRouters->count() > 0)
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-server mr-2"></i>Linked Routers ({{ $vpnAccount->linkedRouters->count() }})</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($vpnAccount->linkedRouters as $router)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $router->name }}</strong><br>
                                <small class="text-muted"><code>{{ $router->host }}:{{ $router->port }}</code></small>
                                @if($router->system_identity)
                                <br><small class="text-info">Identity: {{ $router->system_identity }}</small>
                                @endif
                            </div>
                            <span class="badge badge-{{ $router->status == 'online' ? 'success' : 'secondary' }}">
                                {{ ucfirst($router->status ?? 'unknown') }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Account Info</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Created</dt>
                        <dd class="col-sm-7">{{ $vpnAccount->created_at->format('d-m-Y') }}</dd>
                        <dt class="col-sm-5">Updated</dt>
                        <dd class="col-sm-7">{{ $vpnAccount->updated_at->diffForHumans() }}</dd>
                        @if($vpnAccount->mikrotikDevice)
                        <dt class="col-sm-5">Server Router</dt>
                        <dd class="col-sm-7">{{ $vpnAccount->mikrotikDevice->name }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
