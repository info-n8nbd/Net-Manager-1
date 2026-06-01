@extends('layouts.admin')

@section('title', 'Create IP Pool')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Create IP Pool</h3>
    </div>
    <form action="{{ route('admin.ipam.store') }}" method="POST">
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
                        <label>Pool Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>IP Version <span class="text-danger">*</span></label>
                        <div class="mt-2">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" name="ip_version" class="custom-control-input" id="ipv4" value="ipv4" {{ old('ip_version', 'ipv4') == 'ipv4' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ipv4">IPv4</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" name="ip_version" class="custom-control-input" id="ipv6" value="ipv6" {{ old('ip_version') == 'ipv6' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ipv6">IPv6</label>
                            </div>
                        </div>
                        @error('ip_version')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Network Address <span class="text-danger">*</span></label>
                        <input type="text" name="network_address" class="form-control @error('network_address') is-invalid @enderror" value="{{ old('network_address') }}" placeholder="e.g. 192.168.1.0" required>
                        @error('network_address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>CIDR Prefix <span class="text-danger">*</span></label>
                        <input type="number" name="cidr_prefix" class="form-control @error('cidr_prefix') is-invalid @enderror" value="{{ old('cidr_prefix') }}" placeholder="e.g. 24" required>
                        @error('cidr_prefix')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Subnet Mask</label>
                        <input type="text" name="subnet_mask" class="form-control @error('subnet_mask') is-invalid @enderror" value="{{ old('subnet_mask') }}" placeholder="e.g. 255.255.255.0">
                        @error('subnet_mask')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Gateway <span class="text-danger">*</span></label>
                        <input type="text" name="gateway" class="form-control @error('gateway') is-invalid @enderror" value="{{ old('gateway') }}" required>
                        @error('gateway')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Primary DNS</label>
                        <input type="text" name="dns_primary" class="form-control @error('dns_primary') is-invalid @enderror" value="{{ old('dns_primary') }}">
                        @error('dns_primary')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Secondary DNS</label>
                        <input type="text" name="dns_secondary" class="form-control @error('dns_secondary') is-invalid @enderror" value="{{ old('dns_secondary') }}">
                        @error('dns_secondary')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Pool Type <span class="text-danger">*</span></label>
                        <select name="pool_type" class="form-control @error('pool_type') is-invalid @enderror" required>
                            <option value="">Select Type</option>
                            <option value="client" {{ old('pool_type') == 'client' ? 'selected' : '' }}>Client</option>
                            <option value="management" {{ old('pool_type') == 'management' ? 'selected' : '' }}>Management</option>
                            <option value="infrastructure" {{ old('pool_type') == 'infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                            <option value="public" {{ old('pool_type') == 'public' ? 'selected' : '' }}>Public</option>
                        </select>
                        @error('pool_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Branch</label>
                        <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                            <option value="">Select Branch</option>
                            @foreach($branches ?? [] as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control @error('zone_id') is-invalid @enderror">
                            <option value="">Select Zone</option>
                            @foreach($zones ?? [] as $zone)
                                <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        @error('zone_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>POP</label>
                        <select name="pop_id" class="form-control @error('pop_id') is-invalid @enderror">
                            <option value="">Select POP</option>
                            @foreach($pops ?? [] as $pop)
                                <option value="{{ $pop->id }}" {{ old('pop_id') == $pop->id ? 'selected' : '' }}>{{ $pop->name }}</option>
                            @endforeach
                        </select>
                        @error('pop_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description') }}</textarea>
                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Create Pool</button>
            <a href="{{ route('admin.ipam.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection
