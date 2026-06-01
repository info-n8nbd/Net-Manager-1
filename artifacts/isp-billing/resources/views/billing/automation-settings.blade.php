@extends('layouts.admin')

@section('title', 'Automation Settings')

@push('styles')
<style>
.auto-page { font-size: 0.82rem; }
.auto-page .card-header { padding: 0.45rem 0.85rem; }
.auto-page .card-header h5 { font-size: 0.92rem; font-weight: 700; margin: 0; }
.auto-page .card-body { padding: 0.7rem 0.85rem; }
.auto-page .form-group { margin-bottom: 0.5rem; }
.auto-page label { font-size: 0.8rem; font-weight: 600; margin-bottom: 0.18rem; }
.auto-page .form-control { font-size: 0.8rem; height: calc(1.7rem + 2px); padding: 0.25rem 0.5rem; }
.auto-page .alert { font-size: 0.78rem; padding: 0.35rem 0.65rem; margin-bottom: 0.6rem; }
.auto-page small, .auto-page .small { font-size: 0.73rem; }
.auto-page .how-it-works { background:#f8f9fa; border-radius:6px; padding:0.5rem 0.7rem; }
.auto-page .how-it-works h6 { font-size:0.78rem; font-weight:700; margin-bottom:0.3rem; }
.auto-page .how-it-works ol { padding-left:1.1rem; margin:0; }
.auto-page .how-it-works li { font-size:0.73rem; margin-bottom:0.18rem; }
.auto-page .btn-primary { font-size:0.78rem; padding:0.28rem 0.75rem; }
.auto-page .skip-info-card .card-body { padding: 0.45rem 0.65rem; }
.auto-page .skip-info-card h6 { font-size: 0.78rem; font-weight: 700; margin-bottom: 0.2rem; }
.auto-page .skip-info-card p { font-size: 0.73rem; margin: 0; }
.auto-page table { font-size: 0.78rem; }
.auto-page table th, .auto-page table td { padding: 0.25rem 0.45rem; vertical-align: middle; }
.auto-page .badge { font-size: 0.68rem; }
.auto-page .btn-xs { font-size: 0.72rem; padding: 0.15rem 0.4rem; }
.auto-page .mt-skip-note { font-size: 0.71rem; }
</style>
@endpush

@section('content')
<div class="container-fluid auto-page">

    {{-- ── Automation Settings Card ──────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-robot mr-1"></i>Billing Automation Settings</h5>
        </div>
        <div class="card-body">

            <div class="alert alert-info mb-2">
                <i class="fas fa-info-circle mr-1"></i><strong>Global Automation Control:</strong> These settings apply to ALL clients automatically.
            </div>

            <form method="POST" action="{{ route('billing.automation.settings.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-file-invoice mr-1 text-primary"></i>Bill Generate Time</label>
                            <input type="time" name="bill_generate_time"
                                   value="{{ substr($setting->bill_generate_time ?? '09:00', 0, 5) }}"
                                   class="form-control" required>
                            @error('bill_generate_time')<span class="text-danger small">{{ $message }}</span>@enderror
                            <small class="text-muted">Bills auto-generated at this time for clients whose <strong>Bill Generate Date</strong> matches today.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-power-off mr-1 text-danger"></i>Auto-Inactive Time</label>
                            <input type="time" name="disconnect_time"
                                   value="{{ substr($setting->disconnect_time ?? '23:59', 0, 5) }}"
                                   class="form-control" required>
                            @error('disconnect_time')<span class="text-danger small">{{ $message }}</span>@enderror
                            <small class="text-muted">Clients whose <strong>Auto-Inactive Date</strong> has passed + unpaid bill → set Inactive at this time.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-shield-alt mr-1 text-warning"></i>Min Due for Auto-Inactive (৳)</label>
                            <input type="number" name="min_due_for_inactive"
                                   value="{{ $setting->min_due_for_inactive ?? 0 }}"
                                   class="form-control" min="0" step="0.01"
                                   placeholder="0 = any due triggers inactive">
                            <small class="text-muted">Due ≤ this amount → <strong>not</strong> inactivated. Set <strong>0</strong> to disable.</small>
                        </div>
                    </div>
                </div>

                <div class="row align-items-end">
                    <div class="col-md-8">
                        <div class="how-it-works">
                            <h6><i class="fas fa-question-circle mr-1 text-muted"></i>How it works</h6>
                            <ol>
                                <li>At <strong>Bill Generate Time</strong> → bills created for clients whose Bill Generate Date matches today.</li>
                                <li>At <strong>Auto-Inactive Time</strong> → overdue clients inactivated (unless today is a <strong>Skip Date</strong>).</li>
                                <li>Clients with due ≤ Minimum Due are protected from auto-inactive.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <button class="btn btn-primary btn-sm">
                            <i class="fas fa-save mr-1"></i>Save Settings
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- ── Auto-Inactive Skip Dates Card ────────────────── --}}
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-calendar-times mr-1 text-warning"></i>Auto-Inactive Skip Dates</h5>
            <button class="btn btn-warning btn-sm py-1 px-2" data-toggle="modal" data-target="#addSkipDateModal" style="font-size:0.78rem;">
                <i class="fas fa-plus mr-1"></i>Add Skip Date
            </button>
        </div>
        <div class="card-body">

            <div class="row mb-2">
                <div class="col-md-4">
                    <div class="card border-warning h-100 skip-info-card">
                        <div class="card-body">
                            <h6 class="text-warning"><i class="fas fa-question-circle mr-1"></i>What is a Skip Date?</h6>
                            <p class="text-dark">On a skip date, auto-inactive is completely disabled — no client will be inactivated that day.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info h-100 skip-info-card">
                        <div class="card-body">
                            <h6 class="text-info"><i class="fas fa-calendar-day mr-1"></i>What happens the next day?</h6>
                            <p class="text-dark">All clients whose <strong>Auto-Inactive Date</strong> has passed and have an <strong>unpaid bill</strong> will be inactivated — including those whose date fell on the skip day.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success h-100 skip-info-card">
                        <div class="card-body">
                            <h6 class="text-success"><i class="fas fa-lightbulb mr-1"></i>Example</h6>
                            <p class="text-dark">March 30 = skip date → no one inactivated. On March 31, all clients with Auto-Inactive Date ≤ 31 and a bill due will be inactivated together.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-skip-note text-muted mb-2">
                <i class="fas fa-shield-alt mr-1 text-primary"></i>Clients with a date extension (temp deadline) remain protected even after a skip date.
            </div>

            @if($skipDates->isEmpty())
            <div class="text-center text-muted py-3">
                <i class="fas fa-calendar-check fa-2x mb-1 d-block"></i>
                <small>No skip dates set. System will run auto-inactive every day as scheduled.</small>
            </div>
            @else
            <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Skip Date</th>
                        <th>Day</th>
                        <th>Reason / Note</th>
                        <th>Added By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($skipDates as $i => $skip)
                    @php
                        $isPast   = $skip->skip_date->isPast() && !$skip->skip_date->isToday();
                        $isToday  = $skip->skip_date->isToday();
                        $isFuture = $skip->skip_date->isFuture();
                    @endphp
                    <tr class="{{ $isToday ? 'table-warning' : ($isPast ? 'table-light text-muted' : '') }}">
                        <td>{{ $i + 1 }}</td>
                        <td class="font-weight-bold">{{ $skip->skip_date->format('d M Y') }}</td>
                        <td>{{ $skip->skip_date->format('l') }}</td>
                        <td>{{ $skip->reason ?? '—' }}</td>
                        <td>{{ $skip->createdBy?->name ?? 'System' }}</td>
                        <td>
                            @if($isToday)
                                <span class="badge badge-warning text-dark"><i class="fas fa-clock mr-1"></i>Today</span>
                            @elseif($isFuture)
                                <span class="badge badge-info"><i class="fas fa-calendar-alt mr-1"></i>Upcoming</span>
                            @else
                                <span class="badge badge-secondary">Past</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex" style="gap:3px;">
                                <button type="button" class="btn btn-xs btn-info btn-edit-skip"
                                    title="Edit"
                                    data-id="{{ $skip->id }}"
                                    data-date="{{ $skip->skip_date->format('Y-m-d') }}"
                                    data-reason="{{ $skip->reason }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('billing.skip-dates.destroy', $skip->id) }}"
                                    onsubmit="return confirm('Remove skip date: {{ $skip->skip_date->format('d M Y') }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" title="Remove">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @endif

        </div>
    </div>

</div>

{{-- Edit Skip Date Modal --}}
<div class="modal fade" id="editSkipDateModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="font-size:0.82rem;">
            <div class="modal-header bg-info text-white py-2">
                <h6 class="modal-title mb-0"><i class="fas fa-edit mr-1"></i>Edit Skip Date</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="editSkipDateForm" method="POST" action="">
                @csrf @method('PUT')
                <div class="modal-body py-2">
                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Skip Date <span class="text-danger">*</span></label>
                        <input type="date" name="skip_date" id="editSkipDate" class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Reason / Note</label>
                        <input type="text" name="reason" id="editSkipReason" class="form-control form-control-sm"
                            placeholder="e.g. Eid Holiday">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-save mr-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Skip Date Modal --}}
<div class="modal fade" id="addSkipDateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="font-size:0.82rem;">
            <div class="modal-header bg-warning py-2">
                <h6 class="modal-title text-dark mb-0"><i class="fas fa-calendar-times mr-1"></i>Add Skip Date(s)</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="{{ route('billing.skip-dates.store') }}">
                @csrf
                <div class="modal-body py-2">
                    <div class="alert alert-info py-1 mb-2" style="font-size:0.73rem;">
                        On skip date(s), <strong>no client will be auto-inactivated</strong>. You can select a single date or multiple dates at once.
                    </div>
                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Skip Date(s) <span class="text-danger">*</span></label>
                        <input type="text" id="skipDatePicker" name="skip_dates_raw"
                            class="form-control form-control-sm"
                            placeholder="Click to select one or more dates..."
                            readonly required>
                        <input type="hidden" name="skip_dates" id="skipDatesHidden">
                        <small class="text-muted">Click calendar icon or the field to open. Select multiple dates by clicking each one. Click a selected date again to deselect.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Reason / Note</label>
                        <input type="text" name="reason" class="form-control form-control-sm"
                            placeholder="e.g. Eid Holiday, National Holiday"
                            value="{{ old('reason') }}">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm text-dark" id="addSkipSubmitBtn">
                        <i class="fas fa-plus mr-1"></i>Add Skip Date(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Edit skip date modal
document.querySelectorAll('.btn-edit-skip').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('editSkipDate').value   = this.dataset.date;
        document.getElementById('editSkipReason').value = this.dataset.reason || '';
        document.getElementById('editSkipDateForm').action = '/billing/skip-dates/' + this.dataset.id;
        $('#editSkipDateModal').modal('show');
    });
});

// Flatpickr multiple date picker for Add Skip Date
var skipFp = null;

function initSkipDatePicker() {
    if (!skipFp) {
        skipFp = flatpickr('#skipDatePicker', {
            mode: 'multiple',
            dateFormat: 'd M Y',
            minDate: 'today',
            conjunction: ', ',
            disableMobile: true,
            onChange: function(selectedDates, dateStr) {
                var isoStr = selectedDates.map(function(d) {
                    return d.toISOString().split('T')[0];
                }).join(',');
                document.getElementById('skipDatesHidden').value = isoStr;
                var btn = document.getElementById('addSkipSubmitBtn');
                btn.innerHTML = selectedDates.length > 1
                    ? '<i class="fas fa-plus mr-1"></i>Add ' + selectedDates.length + ' Skip Dates'
                    : '<i class="fas fa-plus mr-1"></i>Add Skip Date(s)';
            }
        });
    } else {
        skipFp.clear();
    }
    document.getElementById('skipDatesHidden').value = '';
    var btn = document.getElementById('addSkipSubmitBtn');
    btn.innerHTML = '<i class="fas fa-plus mr-1"></i>Add Skip Date(s)';
}

// Use jQuery .on() for Bootstrap modal events (native addEventListener doesn't work)
$(document).ready(function() {
    $('#addSkipDateModal').on('shown.bs.modal', function() {
        initSkipDatePicker();
    });
}); 

// Validate before submit
document.getElementById('addSkipDateModal').querySelector('form').addEventListener('submit', function(e) {
    var dates = document.getElementById('skipDatesHidden').value;
    if (!dates) {
        e.preventDefault();
        alert('Please select at least one skip date.');
    }
});
</script>
@endpush
@endsection
