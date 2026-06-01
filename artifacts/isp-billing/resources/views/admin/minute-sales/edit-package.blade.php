@extends('layouts.admin')

@section('title', 'Edit Minute Package')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Package: {{ $package->name }}</h3>
    </div>
    <form action="{{ route('admin.minute-sales.packages.update', $package) }}" method="POST">
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
                        <label>Package Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $package->name) }}" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Duration (Minutes) <span class="text-danger">*</span></label>
                        <input type="number" name="duration_minutes" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes', $package->duration_minutes) }}" required>
                        @error('duration_minutes')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Price (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $package->price) }}" step="0.01" required>
                        @error('price')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Upload Bandwidth (Mbps)</label>
                        <input type="number" name="bandwidth_up_mbps" class="form-control @error('bandwidth_up_mbps') is-invalid @enderror" value="{{ old('bandwidth_up_mbps', $package->bandwidth_up_mbps) }}" step="0.01">
                        @error('bandwidth_up_mbps')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Download Bandwidth (Mbps)</label>
                        <input type="number" name="bandwidth_down_mbps" class="form-control @error('bandwidth_down_mbps') is-invalid @enderror" value="{{ old('bandwidth_down_mbps', $package->bandwidth_down_mbps) }}" step="0.01">
                        @error('bandwidth_down_mbps')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Connection Type <span class="text-danger">*</span></label>
                        <select name="connection_type" class="form-control @error('connection_type') is-invalid @enderror" required>
                            <option value="pppoe" {{ old('connection_type', $package->connection_type) == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                            <option value="hotspot" {{ old('connection_type', $package->connection_type) == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                        </select>
                        @error('connection_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" name="is_active" class="custom-control-input" id="isActive" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $package->description) }}</textarea>
                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Package</button>
            <a href="{{ route('admin.minute-sales.packages') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection
