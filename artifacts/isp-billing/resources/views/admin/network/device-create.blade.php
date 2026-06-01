@extends('layouts.admin')

@section('title', 'Add Router')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">Router OLT & Package</a></li>
<li class="breadcrumb-item active">Routers</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Add New Router</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.network.devices') }}" class="btn btn-sm btn-default">
                        <i class="fas fa-arrow-left mr-1"></i>Back To Routers List
                    </a>
                </div>
            </div>
            <form action="{{ route('admin.network.devices.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <p class="text-danger mb-3"><small>* required field</small></p>

                    <div class="form-group">
                        <label>VPN Account</label>
                        <select name="vpn_account_id" class="form-control @error('vpn_account_id') is-invalid @enderror">
                            <option value="">-- None --</option>
                            @foreach($vpnAccounts as $vpn)
                            <option value="{{ $vpn->id }}" {{ old('vpn_account_id') == $vpn->id ? 'selected' : '' }}>
                                {{ $vpn->username }} ({{ $vpn->vpn_type }}){{ $vpn->client ? ' - ' . $vpn->client->name : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('vpn_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Device Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g., Main Router">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Router Type <span class="text-danger">*</span></label>
                        <select name="router_type" class="form-control @error('router_type') is-invalid @enderror">
                            <option value="core" {{ old('router_type') == 'core' ? 'selected' : '' }}>Core Router</option>
                            <option value="noc" {{ old('router_type') == 'noc' ? 'selected' : '' }}>NOC Router</option>
                            <option value="pop" {{ old('router_type') == 'pop' ? 'selected' : '' }}>POP Router</option>
                            <option value="branch" {{ old('router_type') == 'branch' ? 'selected' : '' }}>Branch Router</option>
                        </select>
                        @error('router_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Host / IP Address <span class="text-danger">*</span></label>
                        <input type="text" name="host" class="form-control @error('host') is-invalid @enderror" value="{{ old('host') }}" required placeholder="192.168.1.1">
                        @error('host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>API Port <span class="text-danger">*</span></label>
                        <input type="number" name="port" class="form-control @error('port') is-invalid @enderror" value="{{ old('port', 8728) }}">
                        @error('port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>WWW Port <span class="text-danger">*</span></label>
                        <input type="number" name="www_port" class="form-control @error('www_port') is-invalid @enderror" value="{{ old('www_port', 80) }}">
                        @error('www_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Mikrotik User Name <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required>
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Mikrotik Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required placeholder="api password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Overwrite PPP Secrets Comment <span class="text-danger">*</span></label>
                        <select name="overwrite_ppp_comment" class="form-control">
                            <option value="1" {{ old('overwrite_ppp_comment', '1') == '1' ? 'selected' : '' }}>yes</option>
                            <option value="0" {{ old('overwrite_ppp_comment') == '0' ? 'selected' : '' }}>no</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control">
                            <option value="">-- Select Zone --</option>
                            @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" name="model" class="form-control" value="{{ old('model') }}" placeholder="e.g., CCR1036, hAP ac2">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes...">{{ old('description') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" name="latitude" class="form-control" value="{{ old('latitude') }}" placeholder="23.8103">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="longitude" class="form-control" value="{{ old('longitude') }}" placeholder="90.4125">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Submit</button>
                    <a href="{{ route('admin.network.devices') }}" class="btn btn-secondary ml-2"><i class="fas fa-times mr-1"></i>Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
