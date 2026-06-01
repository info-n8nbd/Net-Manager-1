@extends('layouts.admin')

@section('title', 'My Collections')
@section('breadcrumb')
<li class="breadcrumb-item active">My Collections</li>
@endsection

@push('styles')
<style>
.stat-card { border-radius: 10px; padding: 18px 22px; color: #fff; }
.stat-card .stat-val { font-size: 26px; font-weight: 700; }
.stat-card .stat-lbl { font-size: 12px; opacity: .85; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
    @endif

    {{-- Summary Stats --}}
    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div class="stat-card" style="background:linear-gradient(135deg,#28a745,#1e7e34);">
                <div class="stat-val">৳{{ number_format($staffDue->total_collected ?? 0, 0) }}</div>
                <div class="stat-lbl">Total Collected (All Time)</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="stat-card" style="background:linear-gradient(135deg,#fd7e14,#e06000);">
                <div class="stat-val">৳{{ number_format($staffDue->current_due ?? 0, 0) }}</div>
                <div class="stat-lbl">Pending With Me</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="stat-card" style="background:linear-gradient(135deg,#17a2b8,#117a8b);">
                <div class="stat-val">৳{{ number_format($pendingAmount, 0) }}</div>
                <div class="stat-lbl">In Transit (Pending Approval)</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="stat-card" style="background:linear-gradient(135deg,#6f42c1,#553098);">
                <div class="stat-val">৳{{ number_format($staffDue->total_submitted ?? 0, 0) }}</div>
                <div class="stat-lbl">Total Submitted</div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left: Submit to Admin --}}
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white font-weight-bold">
                    <i class="fas fa-paper-plane mr-1"></i> Submit Collection to Admin
                </div>
                <div class="card-body">
                    @php
                        $available = max(0, ($staffDue->current_due ?? 0) - $pendingAmount);
                    @endphp
                    <div class="mb-3 p-2 rounded" style="background:#f8f9fa;border:1px solid #dee2e6;">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Pending With Me:</span>
                            <strong class="text-danger">৳{{ number_format($staffDue->current_due ?? 0, 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Already Pending Approval:</span>
                            <strong class="text-warning">৳{{ number_format($pendingAmount, 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-top mt-1 pt-1">
                            <span class="text-muted small font-weight-bold">Available to Submit:</span>
                            <strong class="text-success">৳{{ number_format($available, 0) }}</strong>
                        </div>
                    </div>
                    @if($available > 0)
                    <form method="POST" action="{{ route('admin.my-collections.submit') }}">
                        @csrf
                        <div class="form-group">
                            <label class="small font-weight-bold">Amount (BDT)</label>
                            <input type="number" name="amount" class="form-control" min="1" max="{{ $available }}" value="{{ $available }}" required>
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">Remarks (optional)</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Notes..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane mr-1"></i> Submit to Admin
                        </button>
                    </form>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                            No pending amount to submit.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Submissions --}}
            @if($submissions->count())
            <div class="card shadow-sm mt-3">
                <div class="card-header font-weight-bold small">
                    <i class="fas fa-history mr-1"></i> Recent Submissions
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light"><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($submissions as $sub)
                        <tr>
                            <td class="small">{{ \Carbon\Carbon::parse($sub->submission_date)->format('d M') }}</td>
                            <td class="small font-weight-bold">৳{{ number_format($sub->amount, 0) }}</td>
                            <td>
                                @if($sub->status === 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @elseif($sub->status === 'received')
                                    <span class="badge badge-success">Approved</span>
                                @else
                                    <span class="badge badge-secondary">{{ $sub->status }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Collection History --}}
        <div class="col-md-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header font-weight-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list mr-1"></i> My Collection History</span>
                    <form method="GET" action="{{ route('admin.my-collections.index') }}" class="form-inline">
                        <input type="date" name="start_date" class="form-control form-control-sm mr-1" value="{{ $startDate }}">
                        <input type="date" name="end_date" class="form-control form-control-sm mr-1" value="{{ $endDate }}">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    @if($payments->count())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($payments as $p)
                            <tr>
                                <td class="small">{{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}</td>
                                <td class="small">
                                    <strong>{{ $p->client->name ?? '-' }}</strong>
                                    @if($p->client)
                                        <br><span class="text-muted" style="font-size:11px;">{{ $p->client->client_id }}</span>
                                    @endif
                                </td>
                                <td class="small font-weight-bold text-success">৳{{ number_format($p->amount, 0) }}</td>
                                <td class="small">{{ $p->paymentMethod->name ?? 'Cash' }}</td>
                                <td>
                                    @if($p->submitted_to_admin)
                                        <span class="badge badge-success" style="font-size:10px;">Submitted</span>
                                    @else
                                        <span class="badge badge-warning" style="font-size:10px;">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                            <tfoot class="thead-light">
                                <tr>
                                    <th colspan="2" class="text-right">Period Total:</th>
                                    <th class="text-success">৳{{ number_format($payments->sum('amount'), 0) }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No collections found for this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
