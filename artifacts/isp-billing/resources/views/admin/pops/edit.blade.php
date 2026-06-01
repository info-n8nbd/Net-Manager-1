@extends('layouts.admin')

@section('title', 'Edit POP')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit POP: {{ $pop->name }}</h3>
    </div>
    <form action="{{ route('admin.pops.update', $pop) }}" method="POST">
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
                        <label>POP Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $pop->name) }}" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>POP Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $pop->code) }}" required>
                        @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id', $pop->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control @error('zone_id') is-invalid @enderror">
                            <option value="">Select Zone</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ old('zone_id', $pop->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        @error('zone_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $pop->address) }}</textarea>
                @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person', $pop->contact_person) }}">
                        @error('contact_person')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror" value="{{ old('contact_phone', $pop->contact_phone) }}">
                        @error('contact_phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Bandwidth (Mbps)</label>
                        <input type="number" name="total_bandwidth_mbps" class="form-control @error('total_bandwidth_mbps') is-invalid @enderror" value="{{ old('total_bandwidth_mbps', $pop->total_bandwidth_mbps) }}">
                        @error('total_bandwidth_mbps')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Uplink Provider</label>
                        <input type="text" name="uplink_provider" class="form-control @error('uplink_provider') is-invalid @enderror" value="{{ old('uplink_provider', $pop->uplink_provider) }}">
                        @error('uplink_provider')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Uplink Type</label>
                        <input type="text" name="uplink_type" class="form-control @error('uplink_type') is-invalid @enderror" value="{{ old('uplink_type', $pop->uplink_type) }}">
                        @error('uplink_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>MikroTik Device</label>
                        <select name="mikrotik_device_id" class="form-control @error('mikrotik_device_id') is-invalid @enderror">
                            <option value="">Select Device</option>
                            @foreach($devices ?? [] as $device)
                                <option value="{{ $device->id }}" {{ old('mikrotik_device_id', $pop->mikrotik_device_id) == $device->id ? 'selected' : '' }}>{{ $device->name }} ({{ $device->host }})</option>
                            @endforeach
                        </select>
                        @error('mikrotik_device_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="active" {{ old('status', $pop->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $pop->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="maintenance" {{ old('status', $pop->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                        @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update POP</button>
            <a href="{{ route('admin.pops.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection
