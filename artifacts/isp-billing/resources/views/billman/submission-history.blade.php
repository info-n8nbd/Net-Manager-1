@extends('layouts.billman')
@section('title', 'Submit to Admin')
@section('breadcrumb')
<li class="breadcrumb-item active">Submission History</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>BDT {{ number_format($staffDue->current_due ?? 0) }}</h3>
                <p>Current Due to Admin</p>
            </div>
            <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>BDT {{ number_format($staffDue->total_submitted ?? 0) }}</h3>
                <p>Total Submitted</p>
            </div>
            <div class="icon"><i class="fas fa-check-double"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>BDT {{ number_format($staffDue->total_collected ?? 0) }}</h3>
                <p>Total Collected</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
</div>

@php $availableDue = ($staffDue->current_due ?? 0) - ($pendingAmount ?? 0); @endphp

@if(($pendingAmount ?? 0) > 0)
<div class="alert alert-warning d-flex justify-content-between align-items-center py-2 px-3 mb-3">
    <span><i class="fas fa-hourglass-half mr-2"></i><strong>BDT {{ number_format($pendingAmount, 0) }}</strong> submitted &mdash; Waiting for Admin Approval</span>
    <span class="badge badge-warning text-dark px-2 py-1">Pending</span>
</div>
@endif

@if($availableDue > 0)
<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-paper-plane mr-2"></i>Submit Collection to Admin</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('billman.submit-to-admin') }}" method="POST" class="row align-items-end">
            @csrf
            <div class="col-md-5">
                <label>Amount</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text">BDT</span></div>
                    <input type="number" name="amount" class="form-control" max="{{ $availableDue }}" min="1" step="1" required>
                </div>
                <small class="text-muted">Available: BDT {{ number_format($availableDue) }}</small>
            </div>
            <div class="col-md-4">
                <label>Notes</label>
                <input type="text" name="notes" class="form-control" placeholder="Optional notes...">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-warning btn-block" onclick="return confirm('Submit ' + this.form.amount.value + ' to admin?')">
                    <i class="fas fa-paper-plane mr-1"></i>Submit
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Submission History</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-sm mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Amount</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $i => $sub)
                    <tr>
                        <td>{{ $submissions->firstItem() + $i }}</td>
                        <td class="font-weight-bold {{ $sub->status === 'pending' ? 'text-warning' : 'text-success' }}">BDT {{ number_format($sub->amount) }}</td>
                        <td>{{ $sub->remarks ?? '-' }}</td>
                        <td>
                            @if($sub->status === 'pending')
                                <span class="badge badge-warning text-dark"><i class="fas fa-hourglass-half mr-1"></i>Pending Approval</span>
                            @elseif($sub->status === 'received')
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Received</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($sub->status) }}</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($sub->submission_date)->format('d-m-Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted p-3">No submissions yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($submissions->hasPages())
    <div class="card-footer">
        {{ $submissions->links() }}
    </div>
    @endif
</div>
@endsection
