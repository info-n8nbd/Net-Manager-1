@extends('layouts.admin')

@section('title', 'Advance Collection')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.index') }}">Billing</a></li>
<li class="breadcrumb-item active">Advance Collection</li>
@endsection

@section('content')
<style>
.advance-card { border-left: 4px solid #17a2b8; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.advance-card.status-full { border-left-color: #dc3545; }
.advance-card.status-partial { border-left-color: #ffc107; }
.advance-card.status-unused { border-left-color: #28a745; }
.advance-card .card-body { padding: 12px 15px; }
.advance-card .advance-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.advance-card .advance-amount { font-weight: bold; font-size: 18px; color: #28a745; }
.advance-card .advance-info { font-size: 13px; color: #666; margin-bottom: 4px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
</style>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>৳{{ number_format($totalCollected, 2) }}</h3>
                <p>Total Collected</p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>৳{{ number_format($totalUsed, 2) }}</h3>
                <p>Total Used</p>
            </div>
            <div class="icon"><i class="fas fa-receipt"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($totalRemaining, 2) }}</h3>
                <p>Total Remaining</p>
            </div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-forward mr-2"></i>Advance Collection</h3>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addAdvanceModal">
            <i class="fas fa-plus mr-1"></i> Collect Advance
        </button>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.billing.advance-collection') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Search client..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="payment_method_id" class="form-control">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}" {{ request('payment_method_id') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_from" class="form-control" placeholder="From" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_to" class="form-control" placeholder="To" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
                    <a href="{{ route('admin.billing.advance-collection') }}" class="btn btn-secondary"><i class="fas fa-redo mr-1"></i> Reset</a>
                </div>
            </div>
        </form>

        <div class="mobile-cards">
            @forelse($advances as $advance)
            @php
                $status = $advance->remaining_amount <= 0 ? 'full' : ($advance->used_amount > 0 ? 'partial' : 'unused');
            @endphp
            <div class="card advance-card status-{{ $status }}">
                <div class="card-body">
                    <div class="advance-header">
                        <div>
                            <strong>{{ $advance->client->name ?? 'N/A' }}</strong>
                            <br><small class="text-muted">{{ $advance->client->client_id ?? '' }}</small>
                        </div>
                        <span class="advance-amount">৳{{ number_format($advance->amount, 2) }}</span>
                    </div>
                    <div class="advance-info">
                        <i class="fas fa-calendar mr-1"></i> {{ $advance->payment_date?->format('d-m-Y') }}
                        <span class="ml-2"><i class="fas fa-credit-card mr-1"></i> {{ $advance->paymentMethod->name ?? 'N/A' }}</span>
                    </div>
                    <div class="advance-info">
                        <span class="text-warning">Used: ৳{{ number_format($advance->used_amount, 2) }}</span>
                        <span class="ml-2 text-success">Remaining: ৳{{ number_format($advance->remaining_amount, 2) }}</span>
                    </div>
                    <div class="advance-info">
                        <i class="fas fa-user-check mr-1"></i> {{ $advance->collectedBy->name ?? 'N/A' }}
                        @if($advance->remarks)
                        <br><small class="text-muted"><i class="fas fa-comment mr-1"></i>{{ $advance->remarks }}</small>
                        @endif
                    </div>
                    <span class="badge badge-{{ $status == 'full' ? 'danger' : ($status == 'partial' ? 'warning' : 'success') }}">
                        {{ $status == 'full' ? 'Fully Used' : ($status == 'partial' ? 'Partially Used' : 'Unused') }}
                    </span>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No advance payments found</div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Used</th>
                            <th>Remaining</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Collected By</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($advances as $advance)
                        @php
                            $status = $advance->remaining_amount <= 0 ? 'full' : ($advance->used_amount > 0 ? 'partial' : 'unused');
                        @endphp
                        <tr>
                            <td>{{ $advance->id }}</td>
                            <td>
                                <strong>{{ $advance->client->name ?? 'N/A' }}</strong>
                                <br><small class="text-muted">{{ $advance->client->client_id ?? '' }} | {{ $advance->client->phone ?? '' }}</small>
                            </td>
                            <td class="text-success font-weight-bold">৳{{ number_format($advance->amount, 2) }}</td>
                            <td class="text-warning">৳{{ number_format($advance->used_amount, 2) }}</td>
                            <td class="text-info font-weight-bold">৳{{ number_format($advance->remaining_amount, 2) }}</td>
                            <td>{{ $advance->paymentMethod->name ?? 'N/A' }}</td>
                            <td>{{ $advance->payment_date?->format('d-m-Y') }}</td>
                            <td>{{ $advance->collectedBy->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $status == 'full' ? 'danger' : ($status == 'partial' ? 'warning' : 'success') }}">
                                    {{ $status == 'full' ? 'Fully Used' : ($status == 'partial' ? 'Partially Used' : 'Unused') }}
                                </span>
                            </td>
                            <td>{{ $advance->remarks ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No advance payments found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $advances->appends(request()->query())->links() }}
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
</div>

<div class="modal fade" id="addAdvanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.billing.advance-collection.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Collect Advance Payment</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    @include('admin.partials.client-search', [
                        'inputId' => 'advClientSearch',
                        'hiddenId' => 'advClientSearchId',
                        'fieldName' => 'client_id',
                        'label' => 'Client',
                        'required' => true,
                    ])
                    <div class="form-group">
                        <label>Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method_id" class="form-control" required>
                            <option value="">-- Select Method --</option>
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="advCashReceiptField" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    <div class="form-group">
                        <label>Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Collect Advance</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function toggleAdvCashReceipt() {
        var sel = $('#addAdvanceModal select[name="payment_method_id"]');
        var name = (sel.find('option:selected').data('name') || '').toLowerCase();
        var isCash = name.indexOf('cash') !== -1;
        if (isCash) {
            $('#advCashReceiptField').show();
        } else {
            $('#advCashReceiptField').hide();
            $('#advCashReceiptField input').val('');
        }
    }
    $('#addAdvanceModal select[name="payment_method_id"]').on('change', toggleAdvCashReceipt);
    toggleAdvCashReceipt();
});
</script>
@endpush
