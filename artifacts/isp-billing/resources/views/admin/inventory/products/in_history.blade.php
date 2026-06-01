@extends('layouts.admin')

@section('title', 'Product In History')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.products.index') }}">Products</a></li>
<li class="breadcrumb-item active">Product In History</li>
@endsection

@section('content')
<div class="d-flex align-items-center mb-3" style="gap:0;">
    <a href="{{ route('admin.inventory.products.in_history') }}"
       class="btn btn-sm {{ request()->routeIs('admin.inventory.products.in_history') ? 'btn-success' : 'btn-outline-success' }}"
       style="border-radius:6px 0 0 6px; font-size:12px; padding:6px 16px; white-space:nowrap;">
        <i class="fas fa-arrow-circle-down mr-1"></i> Product In History
    </a>
    <a href="{{ route('admin.inventory.products.out_history') }}"
       class="btn btn-sm {{ request()->routeIs('admin.inventory.products.out_history') ? 'btn-danger' : 'btn-outline-danger' }}"
       style="border-radius:0 6px 6px 0; border-left:0; font-size:12px; padding:6px 16px; white-space:nowrap;">
        <i class="fas fa-arrow-circle-up mr-1"></i> Product Out History
    </a>
</div>

<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $histories->total() }}</h3>
                <p>Total Stock-In Records</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-circle-down"></i></div>
        </div>
    </div>
</div>
@include('admin.inventory.partials.nav-buttons')

<div class="card card-success card-outline">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title"><i class="fas fa-arrow-circle-down mr-2"></i>Product In History</h3>
        <div class="card-tools d-flex align-items-center ml-auto">
            <div class="input-group input-group-sm mr-2" style="width:250px;">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" class="form-control form-control-sm table-quick-search" placeholder="Quick search..." data-table="productInHistoryTable">
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="productInHistoryTable" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Invoice</th>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Quantity In</th>
                    <th>Unit</th>
                    <th>Reference</th>
                    <th>Details</th>
                    <th>Added By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($histories as $index => $h)
                <tr>
                    <td>{{ $histories->firstItem() + $index }}</td>
                    <td>{{ \Carbon\Carbon::parse($h->created_at)->format('d-m-y H:i') }}</td>
                    <td>
                        @if($h->reference_type === 'instrument')
                            <span class="badge badge-dark">{{ $h->product_code }}</span>
                        @elseif($h->reference_type === 'purchase')
                            <span class="badge badge-info">Purchase #{{ $h->reference_id }}</span>
                        @else
                            <code>{{ $h->product_code ?? '-' }}</code>
                        @endif
                    </td>
                    <td><strong>{{ $h->product_name ?? '-' }}</strong></td>
                    <td>{{ $h->product_brand ?? '-' }}</td>
                    <td><span class="badge badge-success">+ {{ $h->quantity }}</span></td>
                    <td>{{ $h->product_unit ?? 'pcs' }}</td>
                    <td>
                        @if($h->reference_type === 'purchase')
                            <span class="badge badge-info"><i class="fas fa-shopping-cart mr-1"></i>Purchase</span>
                        @elseif($h->reference_type === 'product_create')
                            <span class="badge badge-primary"><i class="fas fa-plus-circle mr-1"></i>Product Create</span>
                        @elseif($h->reference_type === 'instrument')
                            <span class="badge badge-success"><i class="fas fa-tools mr-1"></i>Instrument Add</span>
                        @elseif($h->reference_type)
                            <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $h->reference_type)) }}</span>
                        @else
                            <span class="badge badge-light">Manual Entry</span>
                        @endif
                    </td>
                    <td style="font-size: 12px; max-width: 350px;">
                        @if($h->reference_type === 'instrument' && $h->products_detail)
                            @php
                                $parts = explode(' | Serials: ', $h->products_detail);
                                $info = $parts[0] ?? '';
                                $serials = $parts[1] ?? '';
                            @endphp
                            <div>{{ $info }}</div>
                            @if($serials)
                                <div class="mt-1">
                                    <small class="text-muted"><i class="fas fa-barcode mr-1"></i>{{ $serials }}</small>
                                </div>
                            @endif
                            @if(isset($h->total_price) && $h->total_price > 0)
                                <div class="mt-1">
                                    <small class="text-success font-weight-bold"><i class="fas fa-money-bill mr-1"></i>৳{{ number_format($h->total_price, 2) }}</small>
                                </div>
                            @endif
                        @else
                            {{ $h->description ?? '-' }}
                        @endif
                    </td>
                    <td>{{ $h->created_by_name ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">No product in records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
        {{ $histories->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#productInHistoryTable thead th').length > 0 && $('#productInHistoryTable tbody tr').length > 1 && $('#productInHistoryTable tbody tr td[colspan]').length === 0) {
        var dt = $('#productInHistoryTable').DataTable({
            paging: false,
            info: false,
            order: [[1, 'desc']],
            autoWidth: false,
            dom: 'lrtip'
        });
        $('.table-quick-search').on('input', function() {
            dt.search($(this).val()).draw();
        });
    } else {
        $('.table-quick-search').on('input', function() {
            var q = $(this).val().toLowerCase();
            $('#productInHistoryTable tbody tr').each(function() {
                $(this).toggle(!q || $(this).text().toLowerCase().indexOf(q) !== -1);
            });
        });
    }
});
</script>
@endpush
