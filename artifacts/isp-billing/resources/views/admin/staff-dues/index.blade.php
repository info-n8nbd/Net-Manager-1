@extends('layouts.admin')

@section('title', 'Staff Collection Dues')

@section('content')
{{-- Top Stats --}}
<div class="row mb-2">
    <div class="col-4 mb-2">
        <div style="background:#dc3545;color:#fff;border-radius:8px;padding:10px 6px;text-align:center;">
            <div style="font-size:15px;font-weight:800;white-space:nowrap;">৳{{ number_format($totalPendingDues, 0) }}</div>
            <div style="font-size:10px;opacity:.85;">Pending</div>
        </div>
    </div>
    <div class="col-4 mb-2">
        <div style="background:#17a2b8;color:#fff;border-radius:8px;padding:10px 6px;text-align:center;">
            <div style="font-size:15px;font-weight:800;white-space:nowrap;">৳{{ number_format($totalCollected, 0) }}</div>
            <div style="font-size:10px;opacity:.85;">Collected</div>
        </div>
    </div>
    <div class="col-4 mb-2">
        <div style="background:#28a745;color:#fff;border-radius:8px;padding:10px 6px;text-align:center;">
            <div style="font-size:15px;font-weight:800;white-space:nowrap;">৳{{ number_format($totalSubmitted, 0) }}</div>
            <div style="font-size:10px;opacity:.85;">Submitted</div>
        </div>
    </div>
</div>

{{-- Pending Submissions --}}
@if(isset($pendingSubmissions) && $pendingSubmissions->count() > 0)
<div class="card card-warning card-outline mb-3">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-bottom:1px solid #ffc107;">
        <span style="font-size:13px;font-weight:700;color:#856404;">
            <i class="fas fa-clock mr-1 text-warning"></i>Pending Submissions
            <span class="badge badge-warning ml-1">{{ $pendingSubmissions->count() }}</span>
        </span>
        <span class="badge badge-warning">Total: ৳{{ number_format($pendingSubmissions->sum('amount'), 0) }}</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" style="table-layout:fixed;width:100%;font-size:12px;">
            <colgroup>
                <col style="width:22%">
                <col style="width:28%">
                <col style="width:15%">
                <col style="width:20%" class="d-none d-sm-table-column">
                <col style="width:15%">
            </colgroup>
            <thead class="thead-light">
                <tr style="font-size:11px;">
                    <th class="px-2 py-2" style="white-space:nowrap;">#Sub</th>
                    <th class="py-2" style="white-space:nowrap;">Employee</th>
                    <th class="py-2" style="white-space:nowrap;">Amount</th>
                    <th class="py-2 d-none d-sm-table-cell" style="white-space:nowrap;">Submitted</th>
                    <th class="py-2" style="white-space:nowrap;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingSubmissions as $sub)
                <tr>
                    <td class="px-2 py-2" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:120px;">
                        <code style="font-size:10px;">{{ substr($sub->submission_no, -10) }}</code>
                    </td>
                    <td class="py-2" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:0;">
                        <strong style="font-size:12px;">{{ $sub->staff->name ?? 'N/A' }}</strong>
                        <small class="text-muted d-block" style="font-size:10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sub->staff->email ?? '' }}</small>
                    </td>
                    <td class="py-2">
                        <span class="badge badge-warning text-dark" style="font-size:11px;white-space:nowrap;">৳{{ number_format($sub->amount, 0) }}</span>
                    </td>
                    <td class="py-2 d-none d-sm-table-cell" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:0;font-size:11px;color:#666;">
                        {{ $sub->submission_date ? \Carbon\Carbon::parse($sub->submission_date)->format('d-m-y h:i A') : '-' }}
                    </td>
                    <td class="py-2">
                        <div style="display:flex;flex-wrap:wrap;gap:3px;">
                            @foreach(['cash'=>['Cash','success','fa-money-bill-wave'],'bkash'=>['bK','danger','fa-mobile-alt'],'nagad'=>['Na','warning','fa-mobile-alt'],'rocket'=>['Ro','secondary','fa-rocket'],'bank_transfer'=>['Bk','info','fa-university']] as $method=>$info)
                            <form action="{{ route('admin.staff-dues.approve-submission', $sub) }}" method="POST" style="display:inline;" onsubmit="return confirm('Received ৳{{ number_format($sub->amount,0) }} via {{ $info[0] }}?')">
                                @csrf
                                <input type="hidden" name="payment_method" value="{{ $method }}">
                                <button type="submit" class="btn btn-{{ $info[1] }}" style="font-size:10px;padding:2px 5px;white-space:nowrap;line-height:1.4;">
                                    <i class="fas {{ $info[2] }}"></i>
                                </button>
                            </form>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Staff with Pending Dues --}}
<div class="card card-danger card-outline mb-3">
    <div style="display:flex;align-items:center;padding:8px 12px;border-bottom:1px solid #dc3545;">
        <span style="font-size:13px;font-weight:700;color:#dc3545;"><i class="fas fa-users mr-1"></i>Staff with Pending Dues</span>
    </div>
    <div class="card-body p-0">
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="table table-sm table-hover table-striped mb-0" style="width:100%;font-size:12px;">
            <thead class="thead-light">
                <tr style="font-size:11px;">
                    <th class="px-2 py-2" style="white-space:nowrap;min-width:120px;">Staff</th>
                    <th class="py-2" style="white-space:nowrap;min-width:80px;">Role</th>
                    <th class="py-2 d-none d-sm-table-cell" style="white-space:nowrap;min-width:80px;">Collected</th>
                    <th class="py-2 d-none d-sm-table-cell" style="white-space:nowrap;min-width:80px;">Submitted</th>
                    <th class="py-2" style="white-space:nowrap;min-width:70px;">Due</th>
                    <th class="py-2 d-none d-md-table-cell" style="white-space:nowrap;min-width:100px;">Last Submission</th>
                    <th class="py-2" style="white-space:nowrap;min-width:80px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffDues as $due)
                <tr>
                    <td class="px-2 py-2" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:120px;">
                        <strong style="font-size:12px;">{{ $due->user->name ?? 'N/A' }}</strong>
                        <small class="text-muted d-block" style="font-size:10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $due->user->email ?? '' }}</small>
                    </td>
                    <td class="py-2">
                        @if($due->user && $due->user->roles->count() > 0)
                            @foreach($due->user->roles as $role)
                                <span class="badge badge-info" style="font-size:10px;">{{ $role->name }}</span>
                            @endforeach
                        @else
                            <span class="badge badge-secondary" style="font-size:10px;">Staff</span>
                        @endif
                    </td>
                    <td class="py-2 d-none d-sm-table-cell" style="white-space:nowrap;font-size:11px;">৳{{ number_format($due->total_collected, 0) }}</td>
                    <td class="py-2 text-success d-none d-sm-table-cell" style="white-space:nowrap;font-size:11px;">৳{{ number_format($due->total_submitted, 0) }}</td>
                    <td class="py-2 text-danger" style="white-space:nowrap;font-size:12px;font-weight:700;">৳{{ number_format($due->current_due, 0) }}</td>
                    <td class="py-2 d-none d-md-table-cell" style="font-size:11px;white-space:nowrap;">
                        {{ $due->last_submission_date ? $due->last_submission_date->format('d-m-y') : '—' }}
                    </td>
                    <td class="py-2">
                        <div style="display:flex;gap:3px;">
                            <a href="{{ route('admin.staff-dues.show', $due->user) }}" class="btn btn-primary" style="font-size:11px;padding:3px 7px;white-space:nowrap;"><i class="fas fa-eye"></i></a>
                            <button type="button" class="btn btn-success" style="font-size:11px;padding:3px 7px;white-space:nowrap;" data-toggle="modal" data-target="#receiveModal{{ $due->user_id }}"><i class="fas fa-hand-holding-usd"></i></button>
                        </div>
                    </td>
                </tr>
                {{-- Modal --}}
                <div class="modal fade" id="receiveModal{{ $due->user_id }}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
                        <div class="modal-content" style="border-radius:10px;border:none;box-shadow:0 8px 30px rgba(0,0,0,0.12);">
                            <form action="{{ route('admin.staff-dues.receive', $due->user) }}" method="POST">
                                @csrf
                                <div class="modal-header bg-success text-white py-2 px-3">
                                    <span style="font-size:14px;font-weight:700;"><i class="fas fa-hand-holding-usd mr-1"></i>Receive — {{ $due->user->name }}</span>
                                    <button type="button" class="close text-white ml-auto" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body px-3 py-2">
                                    <div class="text-center py-2 mb-2" style="background:#f8f9fa;border-radius:8px;">
                                        <small class="text-muted">Current Due</small>
                                        <div class="font-weight-bold text-danger" style="font-size:22px;">৳{{ number_format($due->current_due, 2) }}</div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label style="font-size:11px;font-weight:600;color:#666;">AMOUNT</label>
                                        <input type="number" name="amount" class="form-control form-control-sm" value="{{ $due->current_due }}" max="{{ $due->current_due }}" step="0.01" required>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label style="font-size:11px;font-weight:600;color:#666;">METHOD</label>
                                        <select name="payment_method" class="form-control form-control-sm" required>
                                            <option value="cash">Cash</option>
                                            <option value="bkash">bKash</option>
                                            <option value="nagad">Nagad</option>
                                            <option value="rocket">Rocket</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-0 staff-cash-receipt-field" style="display:none;">
                                        <label style="font-size:11px;font-weight:600;color:#666;">RECEIPT NO.</label>
                                        <input type="text" name="money_receipt_no" class="form-control form-control-sm" placeholder="Money receipt number">
                                    </div>
                                    <div class="form-group mb-0 mt-2">
                                        <label style="font-size:11px;font-weight:600;color:#666;">REMARKS (optional)</label>
                                        <textarea name="remarks" class="form-control form-control-sm" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer py-2 px-3">
                                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success btn-sm px-3"><i class="fas fa-check mr-1"></i>Receive</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4" style="font-size:13px;">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success d-block"></i>No staff have pending dues
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- Collection Summary --}}
<div class="card card-info card-outline">
    <div style="display:flex;align-items:center;padding:8px 12px;border-bottom:1px solid #17a2b8;">
        <span style="font-size:13px;font-weight:700;color:#17a2b8;"><i class="fas fa-chart-bar mr-1"></i>Collection Summary</span>
        <small class="text-muted ml-auto">{{ now()->setTimezone('Asia/Dhaka')->format('d-m-y, h:i A') }}</small>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            @foreach($allStaffDues->take(10) as $due)
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3" style="font-size:12px;">
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:70%;">{{ $due->user->name ?? 'Unknown' }}</span>
                <span class="badge badge-{{ $due->current_due > 0 ? 'danger' : 'success' }} badge-pill">৳{{ number_format($due->current_due, 0) }}</span>
            </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function toggleStaffCashReceipt(selectEl) {
        var val = $(selectEl).val();
        var isCash = val === 'cash';
        var receiptField = $(selectEl).closest('.modal-body').find('.staff-cash-receipt-field');
        if (isCash) { receiptField.show(); } else { receiptField.hide(); receiptField.find('input').val(''); }
    }
    $(document).on('change', 'select[name="payment_method"]', function() { toggleStaffCashReceipt(this); });
    $('select[name="payment_method"]').each(function() { toggleStaffCashReceipt(this); });
});
</script>
@endpush
