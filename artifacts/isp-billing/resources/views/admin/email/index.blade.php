@extends('layouts.admin')

@section('title', 'Email System')

@section('breadcrumb')
    <li class="breadcrumb-item active">Email System</li>
@endsection

@section('content')
<div class="container-fluid py-2">

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('admin.email.settings') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-cog me-1"></i>SMTP Settings
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#sendModal">
            <i class="fas fa-paper-plane me-1"></i>Send Email
        </button>
    </div>

    @if(!$settings || !$settings->is_active)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Email not configured!</strong> Please configure <a href="{{ route('admin.email.settings') }}" class="alert-link">SMTP Settings</a> first.
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-primary">{{ number_format($stats['total']) }}</div>
                    <div class="small text-muted">Total Emails</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-success">{{ number_format($stats['sent']) }}</div>
                    <div class="small text-muted">Sent</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-danger">{{ number_format($stats['failed']) }}</div>
                    <div class="small text-muted">Failed</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-info">{{ number_format($stats['today']) }}</div>
                    <div class="small text-muted">Today</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-warning">{{ number_format($stats['week']) }}</div>
                    <div class="small text-muted">This Week</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm {{ ($settings && $settings->is_active) ? 'border-success' : 'border-danger' }}">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold {{ ($settings && $settings->is_active) ? 'text-success' : 'text-danger' }}">
                        <i class="fas fa-{{ ($settings && $settings->is_active) ? 'check-circle' : 'times-circle' }}"></i>
                    </div>
                    <div class="small text-muted">Status</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Last 14 Days Email History</h6>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.email.batch') }}">
                    @csrf
                    <input type="hidden" name="type" value="bill_reminder">
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-bell me-1"></i>Send Bill Reminder
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.email.batch') }}">
                    @csrf
                    <input type="hidden" name="type" value="expiry_warning">
                    <button type="submit" class="btn btn-info btn-sm">
                        <i class="fas fa-clock me-1"></i>Send Expiry Warning
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <canvas id="emailChart" height="80"></canvas>
        </div>
    </div>

    {{-- Filters + Log Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Email, name, subject...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="sent" @selected(request('status')=='sent')>Sent</option>
                        <option value="failed" @selected(request('status')=='failed')>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="template" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="bill_reminder"        @selected(request('template')=='bill_reminder')>Bill Reminder</option>
                        <option value="expiry_warning"       @selected(request('template')=='expiry_warning')>Expiry Warning</option>
                        <option value="payment_confirmation" @selected(request('template')=='payment_confirmation')>Payment</option>
                        <option value="suspension_notice"   @selected(request('template')=='suspension_notice')>Suspension</option>
                        <option value="welcome"              @selected(request('template')=='welcome')>Welcome</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                    <a href="{{ route('admin.email.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <form method="POST" action="{{ route('admin.email.clear-logs') }}" class="ms-auto">
                        @csrf
                        <input type="hidden" name="days" value="30">
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete logs older than 30 days?')">
                            <i class="fas fa-trash me-1"></i>Clear Old
                        </button>
                    </form>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td class="ps-3 text-nowrap small">{{ $log->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                @if($log->client)
                                    <a href="{{ route('admin.clients.show', $log->client_id) }}" class="text-decoration-none fw-semibold">
                                        {{ $log->client->name }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">{{ $log->to_email }}</td>
                            <td class="small text-truncate" style="max-width:200px">{{ $log->subject }}</td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'bill_reminder'        => ['warning', 'Bill Reminder'],
                                        'expiry_warning'       => ['info',    'Expiry Warning'],
                                        'payment_confirmation' => ['success', 'Payment'],
                                        'suspension_notice'    => ['danger',  'Suspension'],
                                        'welcome'              => ['primary', 'Welcome'],
                                        'test'                 => ['secondary','Test'],
                                    ];
                                    $tl = $typeLabels[$log->template] ?? ['secondary', $log->template];
                                @endphp
                                <span class="badge bg-{{ $tl[0] }}-subtle text-{{ $tl[0] }} border border-{{ $tl[0] }}-subtle">{{ $tl[1] }}</span>
                            </td>
                            <td>
                                @if($log->status === 'sent')
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sent</span>
                                @elseif($log->status === 'failed')
                                    <span class="badge bg-danger" title="{{ $log->error_message }}"><i class="fas fa-times me-1"></i>Failed</span>
                                @else
                                    <span class="badge bg-secondary">Queued</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.email.delete-log', $log) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0" onclick="return confirm('Delete?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-envelope-open fa-2x mb-2 d-block opacity-30"></i>
                            No email logs found
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $logs->appends(request()->query())->links() }}
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
</script></div>
        @endif
    </div>
</div>

{{-- Send Email Modal --}}
<div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.email.send-to-client') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Send Email to Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Client <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-select" required>
                            <option value="">— Choose a client —</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }} — {{ $client->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" id="emailTypeSelect" required>
                            <option value="bill_reminder">Bill Reminder</option>
                            <option value="expiry_warning">Expiry Warning</option>
                            <option value="payment_confirmation">Payment Confirmation</option>
                            <option value="suspension_notice">Suspension Notice</option>
                            <option value="welcome">Welcome</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="amountRow">
                        <label class="form-label fw-semibold">Amount (BDT)</label>
                        <input type="number" name="amount" class="form-control" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Send</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartData = @json($chartData);
const ctx = document.getElementById('emailChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartData.map(d => d.date),
        datasets: [
            {
                label: 'Sent',
                data: chartData.map(d => d.sent),
                backgroundColor: 'rgba(40,167,69,.7)',
                borderRadius: 4,
            },
            {
                label: 'Failed',
                data: chartData.map(d => d.failed),
                backgroundColor: 'rgba(220,53,69,.7)',
                borderRadius: 4,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

document.getElementById('emailTypeSelect').addEventListener('change', function() {
    const needsAmount = ['payment_confirmation','suspension_notice'].includes(this.value);
    document.getElementById('amountRow').classList.toggle('d-none', !needsAmount);
});
</script>
@endpush
