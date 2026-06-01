@extends('layouts.admin')

@section('title', 'Voice Call Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Voice Call Dashboard</li>
@endsection

@section('content')
<div class="container-fluid py-2">

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('admin.voice-calls.settings') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-cog me-1"></i>Settings
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#makeCallModal">
            <i class="fas fa-phone me-1"></i>New Call
        </button>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-info">{{ number_format($stats['total']) }}</div>
                    <div class="small text-muted">Total Calls</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-success">{{ number_format($stats['answered']) }}</div>
                    <div class="small text-muted">Answered</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-warning">{{ number_format($stats['noAnswer']) }}</div>
                    <div class="small text-muted">No Answer</div>
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
                    <div class="fs-3 fw-bold text-primary">{{ number_format($stats['todayTotal']) }}</div>
                    <div class="small text-muted">Today</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-secondary">৳{{ number_format($stats['totalCost'], 2) }}</div>
                    <div class="small text-muted">Total Cost</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    @if(count($chartData['labels']) > 0)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-info"></i>Last 14 Days Call Summary</h6>
        </div>
        <div class="card-body">
            <canvas id="callChart" height="80"></canvas>
        </div>
    </div>
    @endif

    {{-- Filters + Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="call_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="bill_reminder"        {{ request('call_type')=='bill_reminder'        ?'selected':'' }}>Bill Reminder</option>
                        <option value="expiry_warning"       {{ request('call_type')=='expiry_warning'       ?'selected':'' }}>Expiry Warning</option>
                        <option value="payment_confirmation" {{ request('call_type')=='payment_confirmation' ?'selected':'' }}>Payment Confirm</option>
                        <option value="announcement"         {{ request('call_type')=='announcement'         ?'selected':'' }}>Announcement</option>
                        <option value="custom"               {{ request('call_type')=='custom'               ?'selected':'' }}>Custom</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="completed" {{ request('status')=='completed'?'selected':'' }}>✅ Completed</option>
                        <option value="no_answer" {{ request('status')=='no_answer'?'selected':'' }}>❌ No Answer</option>
                        <option value="busy"      {{ request('status')=='busy'     ?'selected':'' }}>🔔 Busy</option>
                        <option value="failed"    {{ request('status')=='failed'   ?'selected':'' }}>🚫 Failed</option>
                        <option value="initiated" {{ request('status')=='initiated'?'selected':'' }}>🔄 Initiated</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                    <a href="{{ route('admin.voice-calls.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Phone</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Cost</th>
                            <th>Date/Time</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $log->id }}</td>
                            <td><strong>{{ $log->phone }}</strong></td>
                            <td>
                                @if($log->client)
                                    <a href="#" class="text-decoration-none fw-semibold">{{ $log->client->name }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'bill_reminder'        => ['warning', 'Bill Reminder'],
                                        'expiry_warning'       => ['info',    'Expiry Warning'],
                                        'payment_confirmation' => ['success', 'Payment'],
                                        'announcement'         => ['secondary','Announcement'],
                                        'custom'               => ['dark',    'Custom'],
                                    ];
                                    $tl = $typeLabels[$log->call_type] ?? ['secondary', ucfirst($log->call_type)];
                                @endphp
                                <span class="badge bg-{{ $tl[0] }}-subtle text-{{ $tl[0] }} border border-{{ $tl[0] }}-subtle">{{ $tl[1] }}</span>
                            </td>
                            <td>
                                @php
                                    $statusMap = [
                                        'completed' => ['success',   '✅ Completed'],
                                        'answered'  => ['success',   '📱 Answered'],
                                        'failed'    => ['danger',    '🚫 Failed'],
                                        'no_answer' => ['warning',   '❌ No Answer'],
                                        'busy'      => ['warning',   '🔔 Busy'],
                                        'initiated' => ['primary',   '🔄 Initiated'],
                                        'ringing'   => ['info',      '📞 Ringing'],
                                        'queued'    => ['secondary', '⏳ Queued'],
                                        'skipped'   => ['secondary', '⏭ Skipped'],
                                    ];
                                    $sm = $statusMap[$log->status] ?? ['secondary', ucfirst($log->status)];
                                @endphp
                                <span class="badge bg-{{ $sm[0] }}">{{ $sm[1] }}</span>
                            </td>
                            <td class="text-center">
                                @if($log->duration_seconds > 0)
                                    <span class="text-success">{{ gmdate('i:s', $log->duration_seconds) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($log->cost > 0)
                                    <span class="small">৳{{ number_format($log->cost, 4) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-muted text-nowrap">{{ $log->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                @if($log->message_text)
                                    <span class="text-muted small" title="{{ $log->message_text }}">
                                        {{ Str::limit($log->message_text, 35) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-phone-slash fa-2x mb-2 d-block opacity-30"></i>
                            No call history found
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white">{{ $logs->appends(request()->query())->links() }}</div>
        @endif
    </div>

</div>

{{-- Make Call Modal --}}
<div class="modal fade" id="makeCallModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.voice-calls.make-call') }}" method="POST" enctype="multipart/form-data" id="makeCallForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-phone me-2"></i>Make New Call</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX or +880XXXXXXXXX" required>
                            <div class="form-text">Bangladeshi number: 01XXXXXXXXX will auto-convert to +880</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Call Type <span class="text-danger">*</span></label>
                            <select name="call_type" class="form-select" required>
                                <option value="custom">Custom</option>
                                <option value="bill_reminder">Bill Reminder</option>
                                <option value="expiry_warning">Expiry Warning</option>
                                <option value="payment_confirmation">Payment Confirm</option>
                                <option value="announcement">Announcement</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Language</label>
                            <select name="language" class="form-select" id="languageSelect">
                                <option value="en-US">🇺🇸 English</option>
                                <option value="bn-BD">🇧🇩 Bangla</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold">Message Method</label>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-outline-info btn-sm active" id="btnTextMode" onclick="switchMode('text')">
                                <i class="fas fa-keyboard me-1"></i>Write Text
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnAudioMode" onclick="switchMode('audio')">
                                <i class="fas fa-file-audio me-1"></i>Upload Audio File
                            </button>
                        </div>

                        <div id="textModeDiv">
                            <textarea name="message" id="messageTextarea" class="form-control" rows="4"
                                placeholder="Type your message here..."></textarea>
                            <div id="banglaTemplates" class="mt-2" style="display:none;">
                                <small class="text-muted d-block mb-1">Quick Templates:</small>
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="setMsg('Dear customer, your internet bill is due. Please pay your bill. Thank you.')">Bill Reminder</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="setMsg('Dear customer, your package is about to expire. Please renew it. Thank you.')">Expiry Warning</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="setMsg('Dear customer, your connection has been suspended due to unpaid bills. Please pay your bill.')">Line Cut</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="setMsg('Dear customer, your payment has been received and your service is now active. Thank you.')">Payment OK</button>
                            </div>
                        </div>

                        <div id="audioModeDiv" style="display:none;">
                            <input type="file" class="form-control" name="audio_file" id="audioFileInput" accept=".mp3,.wav,.ogg,.aac">
                            <div class="form-text"><i class="fas fa-info-circle me-1"></i>Upload your own recorded audio in any language (MP3/WAV, max 16MB)</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="callSubmitBtn">
                        <i class="fas fa-phone me-1"></i>Make Call
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@if(count($chartData['labels']) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const ctx = document.getElementById('callChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($chartData['labels']) !!},
        datasets: [
            {
                label: 'Total Calls',
                data: {!! json_encode($chartData['totals']) !!},
                backgroundColor: 'rgba(0,123,255,0.2)',
                borderColor: '#007bff',
                borderWidth: 1,
                type: 'line',
                fill: true,
                tension: 0.3,
                order: 0,
            },
            {
                label: '✅ Answered',
                data: {!! json_encode($chartData['answered']) !!},
                backgroundColor: 'rgba(40,167,69,0.7)',
                borderRadius: 4,
                order: 1,
            },
            {
                label: '❌ No Answer',
                data: {!! json_encode($chartData['noAnswer']) !!},
                backgroundColor: 'rgba(253,126,20,0.7)',
                borderRadius: 4,
                order: 1,
            },
            {
                label: '🚫 Failed',
                data: {!! json_encode($chartData['failed']) !!},
                backgroundColor: 'rgba(220,53,69,0.7)',
                borderRadius: 4,
                order: 1,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>
@endif

<script>
function switchMode(mode) {
    if (mode === 'text') {
        document.getElementById('textModeDiv').style.display = '';
        document.getElementById('audioModeDiv').style.display = 'none';
        document.getElementById('btnTextMode').classList.add('active');
        document.getElementById('btnAudioMode').classList.remove('active');
        document.querySelector('[name="audio_file"]').value = '';
        document.querySelector('[name="message"]').required = true;
    } else {
        document.getElementById('textModeDiv').style.display = 'none';
        document.getElementById('audioModeDiv').style.display = '';
        document.getElementById('btnAudioMode').classList.add('active');
        document.getElementById('btnTextMode').classList.remove('active');
        document.querySelector('[name="message"]').required = false;
    }
}

document.getElementById('languageSelect').addEventListener('change', function() {
    const banglaTemplates = document.getElementById('banglaTemplates');
    const textarea = document.getElementById('messageTextarea');
    if (this.value === 'bn-BD') {
        banglaTemplates.style.display = '';
        textarea.placeholder = 'Type your message in Bangla — or choose a template below...';
    } else {
        banglaTemplates.style.display = 'none';
        textarea.placeholder = 'Type your message in English...';
    }
});

function setMsg(txt) {
    document.getElementById('messageTextarea').value = txt;
}

document.getElementById('makeCallForm').addEventListener('submit', function() {
    const btn = document.getElementById('callSubmitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Calling...';
    btn.disabled = true;
});
</script>
@endpush
