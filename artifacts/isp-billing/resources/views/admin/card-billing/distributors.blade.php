@extends('layouts.admin')

@section('title', 'Card Distributors')

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users mr-2"></i>Card Distributors</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Area</th>
                                <th>Commission (%)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($distributors as $distributor)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $distributor->name }}</td>
                                <td>{{ $distributor->phone ?? '-' }}</td>
                                <td>{{ $distributor->email ?? '-' }}</td>
                                <td>{{ $distributor->area ?? '-' }}</td>
                                <td>{{ $distributor->commission_rate ?? 0 }}%</td>
                                <td>
                                    @if($distributor->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editDistributorModal{{ $distributor->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.card-billing.distributors.destroy', $distributor) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this distributor?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center">No distributors found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-plus mr-1"></i>Add Distributor</h3>
                </div>
                <form action="{{ route('admin.card-billing.distributors.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="form-group">
                            <label>Area</label>
                            <input type="text" name="area" class="form-control" value="{{ old('area') }}">
                        </div>
                        <div class="form-group">
                            <label>Commission Rate (%)</label>
                            <input type="number" name="commission_rate" class="form-control" value="{{ old('commission_rate', 0) }}" step="0.01" min="0" max="100">
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
                            <i class="fas fa-save mr-1"></i>Add Distributor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($distributors as $distributor)
<div class="modal fade" id="editDistributorModal{{ $distributor->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.card-billing.distributors.update', $distributor) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white">Edit Distributor</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $distributor->name }}" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ $distributor->phone }}">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ $distributor->email }}">
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area" class="form-control" value="{{ $distributor->area }}">
                    </div>
                    <div class="form-group">
                        <label>Commission Rate (%)</label>
                        <input type="number" name="commission_rate" class="form-control" value="{{ $distributor->commission_rate }}" step="0.01" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active_{{ $distributor->id }}" name="is_active" value="1" {{ $distributor->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="edit_is_active_{{ $distributor->id }}">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
