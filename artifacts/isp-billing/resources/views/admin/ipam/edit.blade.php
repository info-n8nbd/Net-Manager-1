@extends('layouts.admin')

@section('title', 'Edit IP Pool')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit IP Pool: {{ $pool->name }}</h3>
    </div>
    <form action="{{ route('admin.ipam.update', $pool) }}" method="POST">
        @csrf
        @method('PUT')
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
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $pool->name) }}" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Network</label>
                        <input type="text" class="form-control" value="{{ $pool->network_address }}/{{ $pool->cidr_prefix }} ({{ strtoupper($pool->ip_version) }})" disabled>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Gateway <span class="text-danger">*</span></label>
                        <input type="text" name="gateway" class="form-control @error('gateway') is-invalid @enderror" value="{{ old('gateway', $pool->gateway) }}" required>
                        @error('gateway')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Primary DNS</label>
                        <input type="text" name="dns_primary" class="form-control @error('dns_primary') is-invalid @enderror" value="{{ old('dns_primary', $pool->dns_primary) }}">
                        @error('dns_primary')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Secondary DNS</label>
                        <input type="text" name="dns_secondary" class="form-control @error('dns_secondary') is-invalid @enderror" value="{{ old('dns_secondary', $pool->dns_secondary) }}">
                        @error('dns_secondary')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Pool Type</label>
                        <select name="pool_type" class="form-control @error('pool_type') is-invalid @enderror">
                            <option value="client" {{ old('pool_type', $pool->pool_type) == 'client' ? 'selected' : '' }}>Client</option>
                            <option value="management" {{ old('pool_type', $pool->pool_type) == 'management' ? 'selected' : '' }}>Management</option>
                            <option value="infrastructure" {{ old('pool_type', $pool->pool_type) == 'infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                            <option value="public" {{ old('pool_type', $pool->pool_type) == 'public' ? 'selected' : '' }}>Public</option>
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
                                <option value="{{ $branch->id }}" {{ old('branch_id', $pool->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
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
                                <option value="{{ $zone->id }}" {{ old('zone_id', $pool->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
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
                                <option value="{{ $pop->id }}" {{ old('pop_id', $pool->pop_id) == $pop->id ? 'selected' : '' }}>{{ $pop->name }}</option>
                            @endforeach
                        </select>
                        @error('pop_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $pool->description) }}</textarea>
                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" name="is_active" class="custom-control-input" id="isActive" value="1" {{ old('is_active', $pool->is_active) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="isActive">Active</label>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Pool</button>
            <a href="{{ route('admin.ipam.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection
