@extends('layouts.admin')

@section('title', 'Create VPN Account')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.vpn.index') }}">VPN Accounts</a></li>
<li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Create VPN Account</h3>
                </div>
                <form action="{{ route('admin.vpn.store') }}" method="POST">
                    @csrf
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
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g., Main Office VPN" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Account Type <span class="text-danger">*</span></label>
                                    <select name="account_type" class="form-control @error('account_type') is-invalid @enderror" required>
                                        @foreach($accountTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('account_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">Main Router = primary site, Branch Router = remote site</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>VPN Type <span class="text-danger">*</span></label>
                                    <select name="vpn_type" class="form-control @error('vpn_type') is-invalid @enderror" required>
                                        @foreach($vpnTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('vpn_type', 'sstp') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('vpn_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">SSTP recommended for management tunnels</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SSTP/VPN Server Address</label>
                                    <input type="text" name="server_address" class="form-control @error('server_address') is-invalid @enderror" value="{{ old('server_address') }}" placeholder="e.g., vpn.yourisp.com or 203.0.113.1">
                                    @error('server_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">Public IP or hostname of the VPN server</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-bold"><i class="fas fa-key mr-1"></i>Credentials</h6>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="auto_generate" name="auto_generate" value="1" checked onchange="toggleCredentialFields()">
                                <label class="custom-control-label" for="auto_generate">
                                    <strong>Auto-generate username & password</strong>
                                    <small class="text-muted d-block">System will create secure random credentials</small>
                                </label>
                            </div>
                        </div>
                        <div class="row" id="credential-fields" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" placeholder="VPN username">
                                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="text" name="password" class="form-control @error('password') is-invalid @enderror" value="{{ old('password') }}" placeholder="VPN password">
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
                                    <input type="text" name="local_address" class="form-control" value="{{ old('local_address') }}" placeholder="e.g., 100.127.0.1">
                                    <small class="text-muted">VPN server-side tunnel IP</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Remote Address (VPN IP)</label>
                                    <input type="text" name="remote_address" class="form-control" value="{{ old('remote_address') }}" placeholder="e.g., 100.127.0.2">
                                    <small class="text-muted">Router IP in VPN tunnel (used for API access)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>DNS Server</label>
                                    <input type="text" name="dns_server" class="form-control" value="{{ old('dns_server') }}" placeholder="e.g., 8.8.8.8">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Profile</label>
                                    <input type="text" name="profile" class="form-control" value="{{ old('profile', 'default') }}" placeholder="MikroTik PPP profile">
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
                                        <option value="{{ $device->id }}" {{ old('mikrotik_device_id') == $device->id ? 'selected' : '' }}>
                                            {{ $device->name }} ({{ $device->host }})
                                        </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Router where this VPN secret is configured</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Monthly Bill (&#2547;)</label>
                                    <input type="number" name="monthly_fee" class="form-control" value="{{ old('monthly_fee', 0) }}" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about this VPN account...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.vpn.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save mr-1"></i>Create VPN Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>VPN Management Guide</h3>
                </div>
                <div class="card-body">
                    <h6 class="text-bold">Account Types</h6>
                    <p class="text-sm"><strong><i class="fas fa-server text-success mr-1"></i>Main Router:</strong> Primary ISP router at the main office/data center. Usually the VPN server endpoint.</p>
                    <p class="text-sm"><strong><i class="fas fa-code-branch text-warning mr-1"></i>Branch Router:</strong> Remote site routers that connect back to the main router via VPN tunnel.</p>

                    <hr>
                    <h6 class="text-bold">SSTP Management Tunnel</h6>
                    <p class="text-sm">SSTP is the recommended VPN protocol for ISP management tunnels because:</p>
                    <ul class="text-sm">
                        <li>Works over port 443 (bypasses firewalls)</li>
                        <li>SSL/TLS encrypted</li>
                        <li>Native MikroTik support</li>
                        <li>Stable for long-running tunnels</li>
                    </ul>

                    <hr>
                    <h6 class="text-bold">VPN IP Addressing</h6>
                    <p class="text-sm">Use the <code>100.127.x.x</code> range for VPN tunnel IPs. Each router gets a unique VPN IP that is used for API access instead of the router's public IP.</p>
                    <p class="text-sm mb-0"><strong>Example:</strong><br>
                    Main: <code>100.127.0.1</code><br>
                    Branch 1: <code>100.127.0.2</code><br>
                    Branch 2: <code>100.127.0.3</code></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleCredentialFields() {
    var autoGen = document.getElementById('auto_generate');
    var fields = document.getElementById('credential-fields');
    fields.style.display = autoGen.checked ? 'none' : 'flex';
}
</script>
@endpush
