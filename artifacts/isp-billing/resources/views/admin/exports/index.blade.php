@extends('layouts.admin')

@section('title', 'Data Exports')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-file-export me-2"></i>Data Exports</h4>
            <p class="text-muted mb-0">Export data to Excel or CSV with background processing</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Export</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Export Type</label>
                            <select id="export-type" class="form-select">
                                <option value="clients">All Clients</option>
                                <option value="bills">Bills</option>
                                <option value="payments">Payments</option>
                                <option value="due_clients">Due Clients</option>
                                <option value="collection_report">Collection Report</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Format</label>
                            <select id="export-format" class="form-select">
                                <option value="xlsx">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" onclick="startExport()">
                                <i class="fas fa-download me-1"></i>Export Now
                            </button>
                        </div>
                    </div>

                    <div id="export-progress" class="mt-4 d-none">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <span>Processing export...</span>
                        </div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div id="progress-bar" class="progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle me-2 text-primary"></i>Export Tips</h6>
                    <ul class="small mb-0 ps-3">
                        <li>Large exports run in background</li>
                        <li>Download link available when ready</li>
                        <li>Files expire after 24 hours</li>
                        <li>Use filters to reduce file size</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Export History</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exports as $export)
                        <tr>
                            <td>{{ $export->name }}</td>
                            <td>{{ strtoupper($export->format ?? 'xlsx') }}</td>
                            <td>
                                <span class="badge {{ $export->getStatusBadgeClass() }}">
                                    {{ ucfirst($export->status) }}
                                </span>
                            </td>
                            <td>{{ $export->getFileSizeFormatted() }}</td>
                            <td>{{ $export->created_at->diffForHumans() }}</td>
                            <td>
                                @if($export->status === 'completed' && $export->file_path)
                                <a href="{{ route('admin.exports.download', $export) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-download"></i>
                                </a>
                                @elseif($export->status === 'failed')
                                <span class="text-danger small" title="{{ $export->error_message }}">
                                    <i class="fas fa-exclamation-triangle"></i> Failed
                                </span>
                                @else
                                <span class="text-muted small">
                                    <i class="fas fa-clock"></i> Processing...
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No exports yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($exports->hasPages())
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
        {{ $exports->appends(request()->query())->links() }}
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
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function startExport() {
    const type = document.getElementById('export-type').value;
    const format = document.getElementById('export-format').value;
    const progress = document.getElementById('export-progress');
    const progressBar = document.getElementById('progress-bar');

    progress.classList.remove('d-none');
    progressBar.style.width = '10%';

    fetch('{{ route("admin.exports.create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type, format, filters: {} })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            progressBar.style.width = '50%';
            checkExportStatus(data.export_id);
        } else {
            alert('Export failed: ' + (data.message || 'Unknown error'));
            progress.classList.add('d-none');
        }
    })
    .catch(e => {
        alert('Export failed');
        progress.classList.add('d-none');
    });
}

function checkExportStatus(exportId) {
    const progress = document.getElementById('export-progress');
    const progressBar = document.getElementById('progress-bar');

    fetch(`/admin/exports/${exportId}/status`)
        .then(r => r.json())
        .then(data => {
            progressBar.style.width = (50 + data.progress / 2) + '%';

            if (data.status === 'completed' && data.download_url) {
                progressBar.style.width = '100%';
                setTimeout(() => {
                    window.location.href = data.download_url;
                    progress.classList.add('d-none');
                    location.reload();
                }, 500);
            } else if (data.status === 'failed') {
                alert('Export failed: ' + (data.error || 'Unknown error'));
                progress.classList.add('d-none');
            } else {
                setTimeout(() => checkExportStatus(exportId), 2000);
            }
        });
}
</script>
@endpush
