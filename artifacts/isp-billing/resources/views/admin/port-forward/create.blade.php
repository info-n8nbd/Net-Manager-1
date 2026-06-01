@extends('layouts.admin')

@section('title', 'Create Port Forwarding Rule')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Create Port Forwarding Rule</h3>
                </div>
                <form action="{{ route('admin.port-forward.store') }}" method="POST">
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

                        @include('admin.partials.client-search', [
                            'inputId' => 'pfCreateClientSearch',
                            'hiddenId' => 'pfCreateClientSearchId',
                        ])

                        <div class="form-group">
                            <label><i class="fas fa-tag mr-1"></i>Rule Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g., Web Server, CCTV DVR" required>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-exchange-alt mr-1"></i>Protocol</label>
                                    <select name="protocol" class="form-control" required>
                                        <option value="tcp" {{ old('protocol') == 'tcp' ? 'selected' : '' }}>TCP</option>
                                        <option value="udp" {{ old('protocol') == 'udp' ? 'selected' : '' }}>UDP</option>
                                        <option value="tcp+udp" {{ old('protocol') == 'tcp+udp' ? 'selected' : '' }}>TCP + UDP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-sign-out-alt mr-1"></i>External Port</label>
                                    <input type="text" name="external_port" class="form-control" value="{{ old('external_port') }}" placeholder="e.g., 8080" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-sign-in-alt mr-1"></i>Internal Port</label>
                                    <input type="text" name="internal_port" class="form-control" value="{{ old('internal_port') }}" placeholder="e.g., 80" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-globe mr-1"></i>Internal IP Address</label>
                                    <input type="text" name="internal_ip" class="form-control" value="{{ old('internal_ip') }}" placeholder="e.g., 192.168.1.100" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-server mr-1"></i>Router</label>
                                    <select name="mikrotik_device_id" class="form-control" required>
                                        <option value="">Select Router</option>
                                        @foreach($mikrotikDevices as $device)
                                        <option value="{{ $device->id }}" {{ old('mikrotik_device_id') == $device->id ? 'selected' : '' }}>
                                            {{ $device->name }} ({{ $device->ip_address ?? '' }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.port-forward.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save mr-1"></i>Create Rule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
