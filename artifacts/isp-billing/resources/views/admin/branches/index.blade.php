@extends('layouts.admin')

@section('title', 'Branch Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-code-branch mr-2"></i>Branch Management</h3>
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Create Branch
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Manager</th>
                        <th>Clients</th>
                        <th>POPs</th>
                        <th>Zones</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('admin.branches.show', $branch) }}">{{ $branch->name }}</a>
                            @if($branch->is_main)
                                <span class="badge badge-info ml-1">Main</span>
                            @endif
                        </td>
                        <td>{{ $branch->code }}</td>
                        <td>{{ $branch->manager->name ?? '-' }}</td>
                        <td>{{ $branch->clients_count ?? 0 }}</td>
                        <td>{{ $branch->pops_count ?? 0 }}</td>
                        <td>{{ $branch->zones_count ?? 0 }}</td>
                        <td>
                            @if($branch->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.branches.show', $branch) }}" class="btn btn-primary btn-xs" title="View"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-info btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No branches found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
