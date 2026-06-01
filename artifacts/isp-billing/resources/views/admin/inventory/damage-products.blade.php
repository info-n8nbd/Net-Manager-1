@extends('layouts.admin')

@section('title', 'Damaged Products')

@section('content')

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $totalDamaged ?? 0 }}</h3>
                        <p>Total Damaged Items</p>
                    </div>
                    <div class="icon">
                        <i class="fa-solid fa-heart-crack"></i>
                    </div>
                </div>
            </div>

            @isset($damagedByProduct)
                @foreach($damagedByProduct as $item)
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $item->count ?? $item['count'] ?? 0 }}</h3>
                            <p>{{ $item->product_name ?? $item['product_name'] ?? 'Unknown' }}</p>
                        </div>
                        <div class="icon">
                            <i class="fa-solid fa-box"></i>
                        </div>
                    </div>
                </div>
                @endforeach
            @endisset
        </div>

        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Damaged Items List</h3>
            </div>
            <div class="card-body">
                <table id="damageTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>Product Name</th>
                            <th>MAC Address</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($damages as $row)
                        <tr>
                            <td><code>{{ $row->serial_number }}</code></td>
                            <td>{{ $row->product_name ?? ($row->product->name ?? 'N/A') }}</td>
                            <td><code>{{ $row->mac_address ?? 'N/A' }}</code></td>
                            <td>
                                <span class="badge badge-danger">{{ ucfirst($row->status ?? 'damaged') }}</span>
                            </td>
                            <td>{{ $row->remarks ?? $row->reason ?? '-' }}</td>
                            <td>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y') : 'N/A' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.inventory.damage.edit', $row->id) }}" class="btn btn-warning btn-xs" title="Edit Remarks">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.inventory.damage.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this device from damaged status?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-success btn-xs" title="Restore">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <i class="fa-solid fa-check-circle text-success"></i> No damaged products found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#damageTable tbody tr td[colspan]').length === 0) { $('#damageTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[5, "desc"]],
        "pageLength": 25
    }); }
});
</script>
@endpush
