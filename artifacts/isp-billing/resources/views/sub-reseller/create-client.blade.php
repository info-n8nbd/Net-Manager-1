@extends('layouts.sub-reseller')

@section('title', 'Add Client')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Add New Client</h3>
            </div>
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
                <form action="{{ route('sub-reseller.clients.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="package_id">Package</label>
                        <select name="package_id" id="package_id" class="form-control" required>
                            <option value="">Select Package</option>
                            @foreach($packages ?? [] as $package)
                            <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                {{ $package->name }} - ৳{{ number_format($package->price) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="connection_type">Connection Type</label>
                        <select name="connection_type" id="connection_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="pppoe" {{ old('connection_type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                            <option value="static" {{ old('connection_type') == 'static' ? 'selected' : '' }}>Static IP</option>
                            <option value="hotspot" {{ old('connection_type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Create Client</button>
                        <a href="{{ route('sub-reseller.clients') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection