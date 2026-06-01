@extends('layouts.admin')

@section('title', 'Vendor Ledger')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.cash') }}">Accounts</a></li>
<li class="breadcrumb-item active">Vendor Ledger</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book mr-2"></i>Vendor Ledger</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addVendorModal">
                <i class="fas fa-plus mr-1"></i>Add Vendor
            </button>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Vendor Name</th>
                    <th>Contact</th>
                    <th class="text-right">Total Purchase</th>
                    <th class="text-right">Total Payment</th>
                    <th class="text-right">Balance Due</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $vendor)
                <tr>
                    <td>
                        <strong>{{ $vendor->name }}</strong>
                        @if($vendor->company_name)
                        <br><small class="text-muted">{{ $vendor->company_name }}</small>
                        @endif
                    </td>
                    <td>
                        {{ $vendor->phone }}<br>
                        <small class="text-muted">{{ $vendor->email }}</small>
                    </td>
                    <td class="text-right">৳{{ number_format($vendor->purchases_sum_total_amount ?? 0, 2) }}</td>
                    <td class="text-right">৳{{ number_format($vendor->payments_sum_amount ?? 0, 2) }}</td>
                    <td class="text-right">
                        @php
                            $balance = ($vendor->purchases_sum_total_amount ?? 0) - ($vendor->payments_sum_amount ?? 0);
                        @endphp
                        <strong class="{{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                            ৳{{ number_format(abs($balance), 2) }}
                            {{ $balance > 0 ? '(Due)' : ($balance < 0 ? '(Advance)' : '') }}
                        </strong>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info" title="View Ledger"><i class="fas fa-book"></i></a>
                            <button type="button" class="btn btn-success" title="Make Payment"><i class="fas fa-money-bill"></i></button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No vendors found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($vendors->hasPages())
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
        {{ $vendors->appends(request()->query())->links() }}
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
@endsection
