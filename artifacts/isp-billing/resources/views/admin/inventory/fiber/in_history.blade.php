@extends('layouts.admin')

@section('title', 'Fiber In History')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
<li class="breadcrumb-item active">Fiber In History</li>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Fiber History Navigation --}}
    <div class="d-flex align-items-center mb-3" style="gap:0;">
        <a href="{{ route('admin.inventory.fiber.in_history') }}"
           class="btn btn-sm {{ request()->routeIs('admin.inventory.fiber.in_history') ? 'btn-success' : 'btn-outline-success' }}"
           style="border-radius:6px 0 0 6px; font-size:12px; padding:6px 16px; white-space:nowrap;">
            <i class="fas fa-arrow-circle-down mr-1"></i> Fiber In History
        </a>
        <a href="{{ route('admin.inventory.fiber.out_history') }}"
           class="btn btn-sm {{ request()->routeIs('admin.inventory.fiber.out_history') ? 'btn-danger' : 'btn-outline-danger' }}"
           style="border-radius:0 6px 6px 0; border-left:0; font-size:12px; padding:6px 16px; white-space:nowrap;">
            <i class="fas fa-arrow-circle-up mr-1"></i> Fiber Out History
        </a>
    </div>

    <div class="row mb-3">
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $histories->total() }}</h3>
                <p>Total Fiber In Records</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-circle-down"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($totalMeterIn ?? 0, 2) }} <small>m</small></h3>
                <p>Total Meter In</p>
            </div>
            <div class="icon"><i class="fas fa-ruler"></i></div>
        </div>
    </div>
</div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ number_format($totalMeterIn ?? 0, 2) }} <small>m</small></h3><p>Total Meter In</p></div>
                <div class="icon"><i class="fas fa-ruler"></i></div>
            </div>
        </div>
    </div>
    @include('admin.inventory.partials.nav-buttons')

    <div class="card card-success card-outline shadow-sm">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-arrow-circle-down mr-2"></i>Fiber In History</h3>
            <div class="card-tools">
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table id="fiberInHistoryTable" class="table table-hover table-sm mb-0">
                <thead class="bg-success text-white">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Date</th>
                        <th>Invoice / Ref</th>
                        <th>Fiber Code</th>
                        <th>Stock Name</th>
                        <th>Brand</th>
                        <th>Fiber Type</th>
                        <th>Core</th>
                        <th>Vendor</th>
                        <th class="text-center">Meter In</th>
                        <th>Total Price</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($histories as $index => $h)
                    <tr>
                        <td>{{ $histories->firstItem() + $index }}</td>
                        <td>
                            <strong>{{ \Carbon\Carbon::parse($h->date)->format('d-m-Y') }}</strong>
                            <br><small class="text-muted">{{ \Carbon\Carbon::parse($h->created_at)->format('h:i A') }}</small>
                        </td>
                        <td>
                            @if($h->invoice_number)
                            <span class="badge badge-light border"><i class="fas fa-file-invoice mr-1"></i>{{ $h->invoice_number }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><strong>{{ $h->fiber_code ?? '-' }}</strong></td>
                        <td>{{ $h->stock_name ?? '-' }}</td>
                        <td>{{ $h->brand_name ?? '-' }}</td>
                        <td>
                            @if($h->fiber_type)
                            <span class="badge badge-light border">{{ $h->fiber_type }}</span>
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ $h->core ?? '-' }}</td>
                        <td>{{ $h->vendor_name ?? '-' }}</td>
                        <td class="text-center"><span class="badge badge-success">+ {{ number_format($h->meter) }} m</span></td>
                        <td><strong>{{ ($h->total_cost ?? 0) > 0 ? '৳' . number_format($h->total_cost, 2) : '-' }}</strong></td>
                        <td><small>{{ $h->details }}</small></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No fiber in records found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($histories->hasPages())
        <div class="card-footer clearfix">
            {{ $histories->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#fiberInHistoryTable tbody tr td[colspan]').length === 0) {
        $('#fiberInHistoryTable').DataTable({
            paging: false,
            info: false,
            searching: true,
            ordering: true,
            order: [[1, 'desc']],
            autoWidth: false,
            language: {
                search: "<i class='fas fa-search'></i> Search:"
            }
        });
    }
});
</script>
@endpush
