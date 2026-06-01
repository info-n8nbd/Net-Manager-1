@extends('layouts.admin')

@section('title', 'Staff Dues - ' . $user->name)

@section('content')

{{-- RECEIVE ALL MODAL --}}
@if($staffDue && $staffDue->current_due > 0)
<div class="modal fade" id="receiveAllModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
    <div class="modal-content" style="border-radius:10px;border:none;box-shadow:0 8px 30px rgba(0,0,0,0.12);">
      <div class="modal-header border-0 pb-0 pt-3 px-4">
        <div>
          <h5 class="modal-title font-weight-bold" style="font-size:15px;">Receive All Dues</h5>
          <small class="text-muted">{{ $user->name }} &bull; @foreach($user->roles as $r){{ $r->name }}@endforeach</small>
        </div>
        <button type="button" class="close mt-n1" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="{{ route('admin.staff-dues.clear-all', $user) }}" method="POST">
        @csrf
        <div class="modal-body px-4 py-2">
          <div class="text-center py-2 mb-2" style="background:#f8f9fa;border-radius:8px;">
            <div class="text-muted" style="font-size:10px;letter-spacing:.5px;text-transform:uppercase;">Amount</div>
            <div class="font-weight-bold text-success" style="font-size:26px;">৳{{ number_format($staffDue->current_due, 2) }}</div>
          </div>
          <div class="form-group mb-0">
            <label class="text-muted" style="font-size:11px;font-weight:600;">PAYMENT METHOD</label>
            <select name="payment_method" class="form-control form-control-sm" required>
              <option value="" disabled selected>Select method</option>
              <option value="cash">Cash</option>
              <option value="bkash">bKash</option>
              <option value="nagad">Nagad</option>
              <option value="rocket">Rocket</option>
              <option value="bank_transfer">Bank Transfer</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 px-4 pb-3">
          <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm px-4"><i class="fas fa-check mr-1"></i>Approve</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

{{-- APPROVE SUBMISSION MODAL --}}
<div class="modal fade" id="approveSubModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
    <div class="modal-content" style="border-radius:10px;border:none;box-shadow:0 8px 30px rgba(0,0,0,0.12);">
      <div class="modal-header border-0 pb-0 pt-3 px-4">
        <div>
          <h5 class="modal-title font-weight-bold" style="font-size:15px;">Approve Submission</h5>
          <small class="text-muted" id="modalStaffName"></small>
        </div>
        <button type="button" class="close mt-n1" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="approveSubForm" action="" method="POST">
        @csrf
        <div class="modal-body px-4 py-2">
          <div class="text-center py-2 mb-2" style="background:#f8f9fa;border-radius:8px;">
            <div class="text-muted" style="font-size:10px;letter-spacing:.5px;text-transform:uppercase;">Amount</div>
            <div id="modalAmount" class="font-weight-bold text-success" style="font-size:26px;"></div>
            <div id="modalMeta" class="text-muted" style="font-size:11px;"></div>
          </div>
          <div class="form-group mb-0">
            <label class="text-muted" style="font-size:11px;font-weight:600;">RECEIVED VIA</label>
            <select name="payment_method" class="form-control form-control-sm" required>
              <option value="" disabled selected>Select method</option>
              <option value="cash">Cash</option>
              <option value="bkash">bKash</option>
              <option value="nagad">Nagad</option>
              <option value="rocket">Rocket</option>
              <option value="bank_transfer">Bank Transfer</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 px-4 pb-3">
          <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm px-4"><i class="fas fa-check mr-1"></i>Approve</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Staff Info Card --}}
<div class="card card-primary card-outline mb-3">
    <div style="display:flex;align-items:center;padding:8px 12px;border-bottom:1px solid #007bff;">
        <i class="fas fa-user-circle text-primary mr-2"></i>
        <span style="font-size:13px;font-weight:700;color:#007bff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $user->name }}</span>
        <div class="ml-2">
            @foreach($user->roles as $role)<span class="badge badge-info" style="font-size:10px;">{{ $role->name }}</span>@endforeach
        </div>
    </div>
    <div class="card-body p-2">
        <div style="font-size:11px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:8px;padding:0 4px;">
            <i class="fas fa-envelope mr-1 text-muted"></i>{{ $user->email }}
        </div>
        @if($staffDue)
        <div style="display:flex;gap:6px;margin-bottom:8px;">
            <div style="flex:1;min-width:0;text-align:center;background:#fff5f5;border-radius:7px;padding:7px 4px;border-top:2px solid #dc3545;">
                <div style="font-size:10px;color:#999;white-space:nowrap;">Due</div>
                <div style="font-size:13px;font-weight:800;color:#dc3545;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">৳{{ number_format($staffDue->current_due, 0) }}</div>
            </div>
            <div style="flex:1;min-width:0;text-align:center;background:#f0f8ff;border-radius:7px;padding:7px 4px;border-top:2px solid #007bff;">
                <div style="font-size:10px;color:#999;white-space:nowrap;">Collected</div>
                <div style="font-size:13px;font-weight:800;color:#007bff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">৳{{ number_format($staffDue->total_collected, 0) }}</div>
            </div>
            <div style="flex:1;min-width:0;text-align:center;background:#f0fff4;border-radius:7px;padding:7px 4px;border-top:2px solid #28a745;">
                <div style="font-size:10px;color:#999;white-space:nowrap;">Submitted</div>
                <div style="font-size:13px;font-weight:800;color:#28a745;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">৳{{ number_format($staffDue->total_submitted, 0) }}</div>
            </div>
        </div>
        @if($staffDue->current_due > 0)
        <button type="button" class="btn btn-success btn-block btn-sm" data-toggle="modal" data-target="#receiveAllModal">
            <i class="fas fa-check-double mr-1"></i>Receive All Dues (৳{{ number_format($staffDue->current_due, 2) }})
        </button>
        @else
        <div class="alert alert-success mb-0 py-2 text-center" style="font-size:12px;">
            <i class="fas fa-check-circle mr-1"></i>All dues cleared
        </div>
        @endif
        @else
        <div class="alert alert-info mb-0 py-2" style="font-size:12px;">No collection records found.</div>
        @endif
    </div>
</div>

{{-- Unsubmitted Payments --}}
<div class="card card-warning card-outline mb-3">
    <div style="display:flex;align-items:center;padding:8px 12px;border-bottom:1px solid #ffc107;">
        <span style="font-size:13px;font-weight:700;color:#856404;"><i class="fas fa-clock mr-1 text-warning"></i>Unsubmitted Payments ({{ $unsubmittedPayments->count() }})</span>
    </div>
    <div class="card-body p-0" style="max-height:320px;overflow-y:auto;">
        <table class="table table-sm table-hover mb-0" style="table-layout:fixed;width:100%;font-size:12px;">
            <colgroup>
                <col style="width:35%">
                <col style="width:20%">
                <col style="width:28%" class="d-none d-sm-table-column">
                <col style="width:17%">
            </colgroup>
            <thead class="thead-light">
                <tr style="font-size:11px;">
                    <th class="px-2 py-2" style="white-space:nowrap;">Client</th>
                    <th class="py-2" style="white-space:nowrap;">Amount</th>
                    <th class="py-2 d-none d-sm-table-cell" style="white-space:nowrap;">Date &amp; Time</th>
                    <th class="py-2" style="white-space:nowrap;">Method</th>
                </tr>
            </thead>
            <tbody>
                @forelse($unsubmittedPayments as $payment)
                <tr>
                    <td class="px-2 py-2" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:0;">
                        <strong>{{ $payment->client->name ?? 'N/A' }}</strong>
                        <small class="text-muted d-block" style="font-size:10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $payment->client->client_id ?? '' }}</small>
                    </td>
                    <td class="py-2 text-success font-weight-bold" style="white-space:nowrap;">৳{{ number_format($payment->amount, 2) }}</td>
                    <td class="py-2 d-none d-sm-table-cell" style="font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:0;">
                        {{ $payment->created_at->setTimezone('Asia/Dhaka')->format('d M y, h:i A') }}
                    </td>
                    <td class="py-2"><span class="badge badge-info" style="font-size:10px;white-space:nowrap;">{{ $payment->paymentMethod->name ?? 'Cash' }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3" style="font-size:12px;">
                        <i class="fas fa-check-circle text-success d-block mb-1" style="font-size:20px;"></i>All payments submitted
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($unsubmittedPayments->count() > 0)
            <tfoot class="bg-light">
                <tr style="font-size:12px;">
                    <th class="px-2 py-2">Total Unsubmitted</th>
                    <th class="py-2 text-danger">৳{{ number_format($unsubmittedPayments->sum('amount'), 2) }}</th>
                    <th class="py-2 d-none d-sm-table-cell" colspan="2"></th>
                    <th class="py-2 d-table-cell d-sm-none"></th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Submission History --}}
<div class="card card-success card-outline mb-3">
    <div style="display:flex;align-items:center;padding:8px 12px;border-bottom:1px solid #28a745;">
        <span style="font-size:13px;font-weight:700;color:#28a745;"><i class="fas fa-history mr-1"></i>Submission History</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover table-striped mb-0" style="table-layout:fixed;width:100%;font-size:12px;">
            <colgroup>
                <col style="width:28%">
                <col style="width:20%">
                <col style="width:15%" class="d-none d-sm-table-column">
                <col style="width:27%">
                <col style="width:20%" class="d-none d-sm-table-column">
                <col style="width:10%">
            </colgroup>
            <thead class="thead-light">
                <tr style="font-size:11px;">
                    <th class="px-2 py-2" style="white-space:nowrap;">Sub #</th>
                    <th class="py-2" style="white-space:nowrap;">Amount</th>
                    <th class="py-2 d-none d-sm-table-cell" style="white-space:nowrap;">Method</th>
                    <th class="py-2" style="white-space:nowrap;">Date &amp; Time</th>
                    <th class="py-2 d-none d-sm-table-cell" style="white-space:nowrap;">Received By</th>
                    <th class="py-2" style="white-space:nowrap;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                <tr class="{{ $submission->status == 'pending' ? 'table-warning' : '' }}">
                    <td class="px-2 py-2" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:0;">
                        <code style="font-size:10px;">{{ substr($submission->submission_no, -10) }}</code>
                    </td>
                    <td class="py-2 text-success font-weight-bold" style="white-space:nowrap;">৳{{ number_format($submission->amount, 2) }}</td>
                    <td class="py-2 d-none d-sm-table-cell">
                        @php
                            $mColors = ['cash'=>'success','bkash'=>'danger','nagad'=>'warning','rocket'=>'secondary','bank_transfer'=>'info'];
                            $mLabels = ['cash'=>'Cash','bkash'=>'bKash','nagad'=>'Nagad','rocket'=>'Rocket','bank_transfer'=>'Bank'];
                            $mk = strtolower($submission->payment_method ?? '');
                        @endphp
                        @if($mk)<span class="badge badge-{{ $mColors[$mk] ?? 'info' }}" style="font-size:10px;">{{ $mLabels[$mk] ?? ucfirst($mk) }}</span>@else<span class="text-muted">—</span>@endif
                    </td>
                    <td class="py-2" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:0;font-size:11px;color:#555;">
                        {{ ($submission->received_at ?? $submission->created_at)->setTimezone('Asia/Dhaka')->format('d M y, h:i A') }}
                    </td>
                    <td class="py-2 d-none d-sm-table-cell" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:0;font-size:11px;">{{ $submission->receiver->name ?? '—' }}</td>
                    <td class="py-2">
                        @if($submission->status == 'pending')
                            <button type="button" class="btn btn-warning approve-btn" style="font-size:10px;padding:2px 6px;white-space:nowrap;"
                                data-url="{{ route('admin.staff-dues.approve-submission', $submission) }}"
                                data-amount="৳{{ number_format($submission->amount, 2) }}"
                                data-subno="{{ $submission->submission_no }}"
                                data-staff="{{ $user->name }}"
                                data-date="{{ $submission->created_at->format('d M Y, h:i A') }}"
                                data-toggle="modal" data-target="#approveSubModal">
                                <i class="fas fa-check"></i>
                            </button>
                        @else
                            <span class="badge badge-success" style="font-size:10px;">✓</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-3" style="font-size:12px;">No submission history</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($submissions->hasPages())
    <div class="card-footer py-2"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $submissions->appends(request()->query())->links() }}
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

<a href="{{ route('admin.staff-dues.index') }}" class="btn btn-secondary btn-sm mb-3">
    <i class="fas fa-arrow-left mr-1"></i>Back to Staff Dues
</a>

@push('scripts')
<script>
$(document).on('click', '.approve-btn', function() {
    var btn = $(this);
    $('#approveSubForm').attr('action', btn.data('url'));
    $('#modalStaffName').text(btn.data('staff') + ' \u2022 ' + btn.data('subno'));
    $('#modalAmount').text(btn.data('amount'));
    $('#modalMeta').text(btn.data('date'));
    $('#approveSubModal select[name="payment_method"]').val('');
});
</script>
@endpush

@endsection
