@extends('layouts.admin')

@section('title', 'Backups')

@section('content')
<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Create Backup</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('admin.backups.database') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-info btn-block">
                                    <i class="fas fa-database mr-1"></i>Database Backup
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('admin.backups.config') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-cog mr-1"></i>Configuration Backup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history mr-2"></i>Backup History</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $backup->created_at->format('d-m-y H:i') }}</td>
                        <td>
                            @if($backup->type === 'database')
                                <span class="badge badge-info"><i class="fas fa-database mr-1"></i>Database</span>
                            @elseif($backup->type === 'config')
                                <span class="badge badge-success"><i class="fas fa-cog mr-1"></i>Configuration</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($backup->type) }}</span>
                            @endif
                        </td>
                        <td>{{ $backup->file_path ? basename($backup->file_path) : '-' }}</td>
                        <td>
                            @if($backup->file_size)
                                @if($backup->file_size >= 1048576)
                                    {{ number_format($backup->file_size / 1048576, 2) }} MB
                                @elseif($backup->file_size >= 1024)
                                    {{ number_format($backup->file_size / 1024, 2) }} KB
                                @else
                                    {{ $backup->file_size }} B
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($backup->status === 'completed')
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Completed</span>
                            @elseif($backup->status === 'processing')
                                <span class="badge badge-warning"><i class="fas fa-spinner fa-spin mr-1"></i>Processing</span>
                            @elseif($backup->status === 'failed')
                                <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Failed</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($backup->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($backup->status === 'completed')
                            <a href="{{ route('admin.backups.download', $backup) }}" class="btn btn-sm btn-success" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            @if($backup->type === 'database')
                            <form action="{{ route('admin.backups.restore', $backup) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to restore this backup? Type RESTORE to confirm. This action cannot be undone.')">
                                @csrf
                                <input type="hidden" name="confirmation" value="RESTORE">
                                <button type="submit" class="btn btn-sm btn-warning" title="Restore"><i class="fas fa-undo"></i></button>
                            </form>
                            @endif
                            @endif
                            <form action="{{ route('admin.backups.destroy', $backup) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this backup?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">No backups found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
