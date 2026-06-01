@extends('layouts.admin')

@section('title', 'Fiber Out History')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
<li class="breadcrumb-item active">Fiber Out History</li>
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
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $histories->total() }}</h3>
                <p>Total Fiber Out Records</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-circle-up"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($totalMeterOut ?? 0, 2) }} <small>m</small></h3>
                <p>Total Meter Out</p>
            </div>
            <div class="icon"><i class="fas fa-ruler"></i></div>
        </div>
    </div>
</div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ number_format($totalMeterOut ?? 0, 2) }} <small>m</small></h3><p>Total Meter Out</p></div>
                <div class="icon"><i class="fas fa-ruler"></i></div>
            </div>
        </div>
    </div>
    @include('admin.inventory.partials.nav-buttons')

    <div class="card card-danger card-outline shadow-sm">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-arrow-circle-up mr-2"></i>Fiber Out History</h3>
            <div class="card-tools">
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table id="fiberOutHistoryTable" class="table table-hover table-sm mb-0">
                <thead class="bg-danger text-white">
                    <tr>
                        <th style="width:40px">#</th>
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
                    @forelse($histories as $index => $h)
                    <tr>
                        <td>{{ $histories->firstItem() + $index }}</td>
                        <td>
                            <strong>{{ \Carbon\Carbon::parse($h->date)->format('d-m-Y') }}</strong>
                            <br><small class="text-muted">{{ \Carbon\Carbon::parse($h->created_at)->format('h:i A') }}</small>
                        </td>
                        <td><strong>{{ $h->fiber_code ?? '-' }}</strong></td>
                        <td>{{ $h->stock_name ?? '-' }}</td>
                        <td class="text-center"><span class="badge badge-danger">- {{ number_format($h->meter) }} m</span></td>
                        <td>
                            @if($h->reason && $h->reason != '-')
                            <span class="badge badge-light border">{{ $h->reason }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($h->pppoe_id && $h->pppoe_id != '-' && $h->pppoe_id != 'N/A')
                            <code>{{ $h->pppoe_id }}</code>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($h->technician_name && $h->technician_name != '-' && $h->technician_name != 'N/A')
                            {{ $h->technician_name }}
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($h->note)
                            <small class="text-info"><i class="fas fa-sticky-note mr-1"></i>{{ $h->note }}</small>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><small>{{ $h->details }}</small></td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.inventory.fiber.out.edit', $h->id) }}" class="btn btn-warning btn-xs" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.inventory.fiber.out.destroy', $h->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this fiber out record and restore meters?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No fiber out records found
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
    if ($('#fiberOutHistoryTable tbody tr td[colspan]').length === 0) {
        $('#fiberOutHistoryTable').DataTable({
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
