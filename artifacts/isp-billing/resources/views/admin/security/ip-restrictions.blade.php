@extends('layouts.admin')

@section('title', 'IP Restrictions')

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-globe mr-2"></i>IP Restriction Rules</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.security.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Security
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>IP Address / Range</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($restrictions as $restriction)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><code>{{ $restriction->ip_address }}</code></td>
                                <td>
                                    @if($restriction->type === 'whitelist')
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Whitelist</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fas fa-ban mr-1"></i>Blacklist</span>
                                    @endif
                                </td>
                                <td>{{ $restriction->description ?? '-' }}</td>
                                <td>
                                    @if($restriction->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $restriction->created_at->format('d-m-Y') }}</td>
                                <td>
                                    <form action="{{ route('admin.security.ip-restrictions.destroy', $restriction) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this IP restriction?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No IP restrictions configured</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus mr-1"></i>Add IP Rule</h3>
                </div>
                <form action="{{ route('admin.security.ip-restrictions.store') }}" method="POST">
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

                        <div class="form-group">
                            <label>IP Address / CIDR Range</label>
                            <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address') }}" placeholder="e.g., 192.168.1.1 or 10.0.0.0/24" required>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" class="form-control" required>
                                <option value="whitelist" {{ old('type') == 'whitelist' ? 'selected' : '' }}>Whitelist (Allow)</option>
                                <option value="blacklist" {{ old('type') == 'blacklist' ? 'selected' : '' }}>Blacklist (Block)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="e.g., Office network">
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save mr-1"></i>Add Rule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
