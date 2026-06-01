@extends('layouts.admin')
@section('title', 'Fiber History')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.stock.summary') }}">Inventory</a></li>
<li class="breadcrumb-item active">Fiber History</li>
@endsection

@section('content')
<div class="container-fluid">
@include('admin.inventory.partials.nav-buttons')

<div class="card card-outline card-info shadow-sm">
    <div class="card-header p-0 border-bottom-0" style="background:#fff;">
        <ul class="nav nav-tabs px-2 pt-2" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'in' ? 'active font-weight-bold' : '' }}"
                   href="{{ route('admin.inventory.fiber.history', ['tab' => 'in']) }}"
                   style="font-size:13px;">
                    <i class="fas fa-arrow-circle-down mr-1 text-success"></i> In History
                    @if($tab === 'in' && $inHistories)
                        <span class="badge badge-success ml-1" style="font-size:10px;">{{ $inHistories->total() }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'out' ? 'active font-weight-bold' : '' }}"
                   href="{{ route('admin.inventory.fiber.history', ['tab' => 'out']) }}"
                   style="font-size:13px;">
                    <i class="fas fa-arrow-circle-up mr-1 text-danger"></i> Out History
                    @if($tab === 'out' && $outHistories)
                        <span class="badge badge-danger ml-1" style="font-size:10px;">{{ $outHistories->total() }}</span>
                    @endif
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">

        @if($tab === 'in')
        {{-- ===== FIBER IN HISTORY ===== --}}
        <div class="px-3 py-2 border-bottom" style="background:#f8fff8;">
            <div class="row">
                <div class="col-auto">
                    <small class="text-muted">Total Records: <strong>{{ $inHistories->total() }}</strong></small>
                </div>
                <div class="col-auto">
                    <small class="text-muted">Total Meter In: <strong>{{ number_format($totalMeterIn ?? 0, 2) }} m</strong></small>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table id="fiberInHistoryTable" class="table table-bordered table-hover table-sm mb-0">
                <thead class="bg-success text-white">
                    <tr>
                        <th style="width:40px;">#</th>
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
                    @forelse($inHistories as $index => $h)
                    <tr>
                        <td>{{ $inHistories->firstItem() + $index }}</td>
                        <td><strong>{{ \Carbon\Carbon::parse($h->date)->format('d-m-Y') }}</strong><br><small class="text-muted">{{ \Carbon\Carbon::parse($h->created_at)->format('h:i A') }}</small></td>
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
                        <td>@if($h->fiber_type)<span class="badge badge-light border">{{ $h->fiber_type }}</span>@else -@endif</td>
                        <td>{{ $h->core ?? '-' }}</td>
                        <td>{{ $h->vendor_name ?? '-' }}</td>
                        <td class="text-center"><span class="badge badge-success">+ {{ number_format($h->meter) }} m</span></td>
                        <td><strong>{{ ($h->total_cost ?? 0) > 0 ? '৳' . number_format($h->total_cost, 2) : '-' }}</strong></td>
                        <td><small>{{ $h->details }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="12" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No fiber in records found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2">{{ $inHistories->appends(request()->query())->links() }}</div>

        @else
        {{-- ===== FIBER OUT HISTORY ===== --}}
        <div class="px-3 py-2 border-bottom" style="background:#fff8f8;">
            <div class="row">
                <div class="col-auto">
                    <small class="text-muted">Total Records: <strong>{{ $outHistories->total() }}</strong></small>
                </div>
                <div class="col-auto">
                    <small class="text-muted">Total Meter Out: <strong>{{ number_format($totalMeterOut ?? 0, 2) }} m</strong></small>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table id="fiberOutHistoryTable" class="table table-bordered table-hover table-sm mb-0">
                <thead class="bg-danger text-white">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Date</th>
                        <th>Fiber Code</th>
                        <th>Stock Name</th>
                        <th class="text-center">Meter Out</th>
                        <th>Reason</th>
                        <th>PPPoE ID</th>
                        <th>Technician</th>
                        <th>Note</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($outHistories as $index => $h)
                    <tr>
                        <td>{{ $outHistories->firstItem() + $index }}</td>
                        <td><strong>{{ \Carbon\Carbon::parse($h->date)->format('d-m-Y') }}</strong><br><small class="text-muted">{{ \Carbon\Carbon::parse($h->created_at)->format('h:i A') }}</small></td>
                        <td><strong>{{ $h->fiber_code ?? '-' }}</strong></td>
                        <td>{{ $h->stock_name ?? '-' }}</td>
                        <td class="text-center"><span class="badge badge-danger">- {{ number_format($h->meter) }} m</span></td>
                        <td>@if($h->reason && $h->reason != '-')<span class="badge badge-light border">{{ $h->reason }}</span>@else<span class="text-muted">-</span>@endif</td>
                        <td>@if($h->pppoe_id && $h->pppoe_id != '-' && $h->pppoe_id != 'N/A')<code>{{ $h->pppoe_id }}</code>@else<span class="text-muted">-</span>@endif</td>
                        <td>@if($h->technician_name && $h->technician_name != '-' && $h->technician_name != 'N/A'){{ $h->technician_name }}@else<span class="text-muted">-</span>@endif</td>
                        <td>@if($h->note)<small class="text-info"><i class="fas fa-sticky-note mr-1"></i>{{ $h->note }}</small>@else<span class="text-muted">-</span>@endif</td>
                        <td><small>{{ $h->details }}</small></td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.inventory.fiber.out.edit', $h->id) }}" class="btn btn-warning btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.inventory.fiber.out.destroy', $h->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this fiber out record and restore meters?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No fiber out records found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2">{{ $outHistories->appends(request()->query())->links() }}</div>
        @endif

    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var tableId = '{{ $tab === "in" ? "fiberInHistoryTable" : "fiberOutHistoryTable" }}';
    if ($('#' + tableId + ' tbody tr td[colspan]').length === 0) {
        $('#' + tableId).DataTable({
            paging: false, info: false, searching: true, ordering: true,
            order: [[1, 'desc']], autoWidth: false,
            language: { search: "<i class='fas fa-search'></i> Search:" }
        });
    }
});
</script>
@endpush
