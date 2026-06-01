@extends('layouts.admin')

@section('title', 'VPN Server Settings')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.vpn.index') }}">VPN Accounts</a></li>
<li class="breadcrumb-item active">VPN Server Settings</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog mr-2"></i>VPN Server Configuration</h3>
                </div>
                <form action="{{ route('admin.vpn.settings.save') }}" method="POST">
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

                        <h6 class="text-bold"><i class="fas fa-server mr-1"></i>VPN Server</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>VPN Server Domain / Public IP <span class="text-danger">*</span></label>
                                    <input type="text" name="vpn_server_domain" class="form-control @error('vpn_server_domain') is-invalid @enderror" value="{{ old('vpn_server_domain', $settings['vpn_server_domain']) }}" placeholder="e.g., vpn.yourisp.com or 203.0.113.1" required>
                                    @error('vpn_server_domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">The public IP or domain name where your SSTP VPN server is running. Routers will connect to this address.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>VPN Server Port <span class="text-danger">*</span></label>
                                    <input type="text" name="vpn_server_port" class="form-control @error('vpn_server_port') is-invalid @enderror" value="{{ old('vpn_server_port', $settings['vpn_server_port']) }}" placeholder="443" required>
                                    @error('vpn_server_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">Default: 443</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-bold"><i class="fas fa-network-wired mr-1"></i>VPN Network</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>VPN Network Range <span class="text-danger">*</span></label>
                                    <input type="text" name="vpn_network_range" class="form-control @error('vpn_network_range') is-invalid @enderror" value="{{ old('vpn_network_range', $settings['vpn_network_range']) }}" placeholder="100.127.240.0/20" required>
                                    @error('vpn_network_range')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">The VPN network CIDR used for NAT and routing rules</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Local Gateway IP <span class="text-danger">*</span></label>
                                    <input type="text" name="vpn_local_gateway" class="form-control @error('vpn_local_gateway') is-invalid @enderror" value="{{ old('vpn_local_gateway', $settings['vpn_local_gateway']) }}" placeholder="100.127.224.1" required>
                                    @error('vpn_local_gateway')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">The VPN server-side gateway IP (local address for PPP profiles)</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-bold"><i class="fas fa-sitemap mr-1"></i>Auto-Assign IP Range</h6>
                        <p class="text-sm text-muted">When creating new VPN accounts, IPs will be auto-assigned starting from <code>100.127.{octet3}.{octet4}</code></p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Starting 3rd Octet <span class="text-danger">*</span></label>
                                    <input type="number" name="vpn_ip_start_octet3" class="form-control @error('vpn_ip_start_octet3') is-invalid @enderror" value="{{ old('vpn_ip_start_octet3', $settings['vpn_ip_start_octet3']) }}" min="0" max="255" required>
                                    @error('vpn_ip_start_octet3')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">100.127.<strong>X</strong>.x (default: 230)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Starting 4th Octet <span class="text-danger">*</span></label>
                                    <input type="number" name="vpn_ip_start_octet4" class="form-control @error('vpn_ip_start_octet4') is-invalid @enderror" value="{{ old('vpn_ip_start_octet4', $settings['vpn_ip_start_octet4']) }}" min="1" max="254" required>
                                    @error('vpn_ip_start_octet4')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="text-muted">100.127.x.<strong>X</strong> (default: 1)</small>
                                </div>
                            </div>
                        </div>

                        <div class="callout callout-info">
                            <p class="mb-0 text-sm">
                                <strong>Next auto-assigned IP:</strong>
                                <code>{{ \App\Models\VpnAccount::getNextAvailableIp() }}</code>
                            </p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.vpn.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save mr-1"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Setup Guide</h3>
                </div>
                <div class="card-body text-sm">
                    <h6 class="text-bold">How VPN Management Works</h6>
                    <p>Your billing system acts as a VPN hub. ISP routers connect to it via SSTP VPN tunnels. This way, you can manage routers remotely without needing a public IP on each router.</p>

                    <h6 class="text-bold">Requirements</h6>
                    <ul>
                        <li>A server/VPS with a public IP address or domain</li>
                        <li>SSTP VPN server running on your main MikroTik router</li>
                        <li>Port 443 (or your chosen port) open on the server firewall</li>
                    </ul>

                    <h6 class="text-bold">IP Addressing</h6>
                    <p>Each VPN account gets a unique IP from the <code>100.127.x.x</code> range. This IP is used by the billing system to reach the router through the VPN tunnel.</p>

                    <h6 class="text-bold">Flow</h6>
                    <ol>
                        <li>Set your VPN server domain/IP here</li>
                        <li>Create VPN accounts (auto-generates credentials)</li>
                        <li>ISP runs the generated commands on their router</li>
                        <li>Router connects back via VPN tunnel</li>
                        <li>Add router using the VPN tunnel IP</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
