@extends('layouts.admin')

@section('title', 'Minute Packages')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Minute Packages</h3>
        <a href="{{ route('admin.minute-sales.create-package') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Create Package
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Duration (min)</th>
                        <th>Price</th>
                        <th>Bandwidth</th>
                        <th>Connection Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $package->name }}</td>
                        <td>{{ $package->duration_minutes }}</td>
                        <td>৳{{ number_format($package->price, 2) }}</td>
                        <td>{{ $package->bandwidth_up_mbps ?? '-' }} / {{ $package->bandwidth_down_mbps ?? '-' }} Mbps</td>
                        <td><span class="badge badge-info">{{ strtoupper($package->connection_type) }}</span></td>
                        <td>
                            @if($package->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.minute-sales.packages.edit', $package) }}" class="btn btn-info btn-xs"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.minute-sales.destroy-package', $package) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No packages found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
