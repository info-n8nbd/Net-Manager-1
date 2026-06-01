@extends('layouts.admin')

@section('title', 'Import Data')

@section('content')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Import History</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.data-transfer.import.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> New Import
                    </a>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Data Type</th>
                            <th>Source</th>
                            <th>Rows</th>
                            <th>Imported</th>
                            <th>Status</th>
                            <th>User</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($imports as $import)
                        <tr>
                            <td>{{ $import->id }}</td>
                            <td>{{ $import->name }}</td>
                            <td>{{ ucfirst($import->data_type) }}</td>
                            <td>{{ $import->source_software ?? '-' }}</td>
                            <td>{{ number_format($import->total_rows) }}</td>
                            <td>{{ number_format($import->imported_rows) }}</td>
                            <td><span class="badge {{ $import->getStatusBadgeClass() }}">{{ ucfirst($import->status) }}</span></td>
                            <td>{{ $import->user->name ?? '-' }}</td>
                            <td>{{ $import->created_at->format('d-m-y H:i') }}</td>
                            <td>
                                @if($import->status === 'validated')
                                <a href="{{ route('admin.data-transfer.import.preview', $import->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @elseif($import->status === 'completed')
                                <a href="{{ route('admin.data-transfer.import.result', $import->id) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="{{ route('admin.data-transfer.import.report', $import->id) }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                @elseif($import->status === 'pending')
                                <a href="{{ route('admin.data-transfer.import.mapping', $import->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-cog"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">No imports yet. Start your first import!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $imports->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script>
            </div>
        </div>
@endsection
