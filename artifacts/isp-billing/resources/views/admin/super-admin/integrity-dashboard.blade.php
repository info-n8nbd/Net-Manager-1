@extends('layouts.admin')

@section('title', 'Billing Integrity Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-shield-alt text-danger me-2"></i>
                        Billing Integrity Dashboard
                    </h4>
                    <small class="text-muted">Monitor and maintain billing system accuracy</small>
                </div>
                <div>
                    <a href="{{ route('admin.super-admin.access-panel') }}" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="fas fa-arrow-left"></i> Back to Panel
                    </a>
                    <button type="button" class="btn btn-danger btn-sm" id="runIntegrityCheck">
                        <i class="fas fa-sync-alt"></i> Run Full Check
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm {{ $healthStatus['status'] === 'healthy' ? 'border-start border-success border-4' : 'border-start border-danger border-4' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">System Health</h6>
                            <h4 class="mb-0 {{ $healthStatus['status'] === 'healthy' ? 'text-success' : 'text-danger' }}">
                                {{ $healthStatus['status'] === 'healthy' ? 'Healthy' : 'Issues Detected' }}
                            </h4>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas {{ $healthStatus['status'] === 'healthy' ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-danger' }} fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Balance Mismatches</h6>
                            <h4 class="mb-0">{{ $healthStatus['client_balance_mismatches'] ?? 0 }}</h4>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-balance-scale text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Duplicate Bills</h6>
                            <h4 class="mb-0">{{ $healthStatus['duplicate_bills'] ?? 0 }}</h4>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-copy text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Pending Review</h6>
                            <h4 class="mb-0">{{ $pendingIssues['count'] ?? 0 }}</h4>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-clipboard-list text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Integrity Checks</h6>
                    <a href="{{ route('admin.super-admin.integrity.history') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Clients</th>
                                    <th>Bills</th>
                                    <th>Issues</th>
                                    <th>Auto-Fixed</th>
                                    <th>Review</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentChecks as $check)
                                <tr class="cursor-pointer" onclick="showCheckDetails({{ $check->id }})">
                                    <td>{{ $check->created_at->format('d-m-y H:i') }}</td>
                                    <td>
                                        @if($check->status === 'healthy')
                                            <span class="badge bg-success">Healthy</span>
                                        @else
                                            <span class="badge bg-warning">Issues Found</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($check->clients_checked) }}</td>
                                    <td>{{ number_format($check->bills_checked) }}</td>
                                    <td>
                                        @if($check->issues_found > 0)
                                            <span class="text-danger fw-bold">{{ $check->issues_found }}</span>
                                        @else
                                            <span class="text-success">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($check->auto_corrected > 0)
                                            <span class="text-primary">{{ $check->auto_corrected }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($check->requires_review > 0)
                                            <span class="badge bg-danger">{{ $check->requires_review }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>{{ $check->duration_seconds }}s</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>No integrity checks have been run yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Last Check Summary</h6>
                </div>
                <div class="card-body">
                    @if($lastCheck)
                        <div class="mb-3">
                            <small class="text-muted">Checked At</small>
                            <div class="fw-bold">{{ \Carbon\Carbon::parse($lastCheck['checked_at'])->format('d-m-y H:i:s') }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Status</small>
                            <div>
                                @if($lastCheck['status'] === 'healthy')
                                    <span class="badge bg-success">All Systems OK</span>
                                @else
                                    <span class="badge bg-warning">Requires Attention</span>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-2">
                                <small class="text-muted">Issues Found</small>
                                <div class="h5 mb-0 {{ $lastCheck['issues_found'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $lastCheck['issues_found'] }}
                                </div>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">Auto-Corrected</small>
                                <div class="h5 mb-0 text-primary">{{ $lastCheck['auto_corrected'] }}</div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <p class="mb-0">No checks performed yet</p>
                            <small>Run your first integrity check</small>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Scheduled Check</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <i class="fas fa-calendar-check text-success fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">Daily at 2:00 AM</div>
                            <small class="text-muted">Automatic integrity check with auto-correction</small>
                        </div>
                    </div>
                    <hr>
                    <h6 class="text-muted mb-2">Checks Performed:</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-check text-success me-2"></i>Client balance verification</li>
                        <li><i class="fas fa-check text-success me-2"></i>Bill amount consistency</li>
                        <li><i class="fas fa-check text-success me-2"></i>Payment-bill linking</li>
                        <li><i class="fas fa-check text-success me-2"></i>Duplicate bill detection</li>
                        <li><i class="fas fa-check text-success me-2"></i>Orphaned payment detection</li>
                        <li><i class="fas fa-check text-success me-2"></i>Advance balance validation</li>
                        <li><i class="fas fa-check text-success me-2"></i>Bill status consistency</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if(count($pendingIssues['details'] ?? []) > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Issues Requiring Manual Review</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Severity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingIssues['details'] as $issue)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $issue['type'] ?? 'unknown' }}</span></td>
                                    <td>
                                        @if(isset($issue['client_id']))
                                            Client ID: {{ $issue['client_id'] }} 
                                            @if(isset($issue['client_name']))
                                                ({{ $issue['client_name'] }})
                                            @endif
                                        @endif
                                        @if(isset($issue['billing_period']))
                                            | Period: {{ $issue['billing_period'] }}
                                        @endif
                                        @if(isset($issue['count']))
                                            | Count: {{ $issue['count'] }}
                                        @endif
                                    </td>
                                    <td>
                                        @php $severity = $issue['severity'] ?? 'medium'; @endphp
                                        <span class="badge bg-{{ $severity === 'critical' ? 'danger' : ($severity === 'high' ? 'warning' : 'info') }}">
                                            {{ ucfirst($severity) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="modal fade" id="checkDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Integrity Check Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="checkDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="runningModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-danger mb-3" role="status"></div>
                <h6>Running Integrity Check...</h6>
                <small class="text-muted">This may take a few minutes for large databases</small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('runIntegrityCheck').addEventListener('click', function() {
    if (!confirm('Run a full billing integrity check? This will scan all clients and bills.')) {
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('runningModal'));
    modal.show();
    
    fetch('{{ route("admin.super-admin.integrity.run") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ auto_correct: true })
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        if (data.success) {
            alert('Integrity check completed.\nIssues found: ' + data.result.issues_found + 
                  '\nAuto-corrected: ' + data.result.auto_corrected +
                  '\nRequires review: ' + data.result.requires_review);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        modal.hide();
        alert('Error running integrity check: ' + error.message);
    });
});

function showCheckDetails(logId) {
    const modal = new bootstrap.Modal(document.getElementById('checkDetailsModal'));
    document.getElementById('checkDetailsContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    modal.show();
    
    fetch('{{ url("admin/super-admin/integrity") }}/' + logId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="row">';
                html += '<div class="col-6 mb-3"><strong>Status:</strong> ' + data.log.status + '</div>';
                html += '<div class="col-6 mb-3"><strong>Duration:</strong> ' + data.log.duration_seconds + 's</div>';
                html += '<div class="col-6 mb-3"><strong>Clients Checked:</strong> ' + data.log.clients_checked + '</div>';
                html += '<div class="col-6 mb-3"><strong>Bills Checked:</strong> ' + data.log.bills_checked + '</div>';
                html += '</div>';
                
                if (data.details && data.details.issues && data.details.issues.length > 0) {
                    html += '<h6>Issues Found:</h6>';
                    html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Type</th><th>Details</th></tr></thead><tbody>';
                    data.details.issues.forEach(function(issue) {
                        html += '<tr><td><span class="badge bg-secondary">' + issue.type + '</span></td>';
                        html += '<td>' + JSON.stringify(issue).substring(0, 100) + '...</td></tr>';
                    });
                    html += '</tbody></table></div>';
                } else {
                    html += '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>No issues found in this check</div>';
                }
                
                document.getElementById('checkDetailsContent').innerHTML = html;
            }
        })
        .catch(error => {
            document.getElementById('checkDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading details</div>';
        });
}
</script>
@endpush

@push('styles')
<style>
.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover { background-color: #f8f9fa; }
</style>
@endpush
