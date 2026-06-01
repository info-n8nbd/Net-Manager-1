@extends('layouts.admin')

@section('title', 'Warranty Report')

@section('content')

        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-clock"></i> Warranty Status (Sorted by Expiry)</h3>
            </div>
            <div class="card-body">
                <table id="warrantyTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>Product</th>
                            <th>Client</th>
                            <th>Warranty Start</th>
                            <th>Warranty End</th>
                            <th>Days Left</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serials as $serial)
                        @php
                            $warrantyEnd = $serial->warranty_end ? \Carbon\Carbon::parse($serial->warranty_end) : null;
                            $daysLeft = $warrantyEnd ? now()->diffInDays($warrantyEnd, false) : null;

                            if ($daysLeft === null) {
                                $rowClass = '';
                                $statusBadge = 'badge-secondary';
                                $statusText = 'N/A';
                            } elseif ($daysLeft < 0) {
                                $rowClass = 'table-danger';
                                $statusBadge = 'badge-danger';
                                $statusText = 'Expired';
                            } elseif ($daysLeft <= 30) {
                                $rowClass = 'table-warning';
                                $statusBadge = 'badge-warning';
                                $statusText = 'Expiring Soon';
                            } else {
                                $rowClass = '';
                                $statusBadge = 'badge-success';
                                $statusText = 'Active';
                            }
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td><code>{{ $serial->serial_number }}</code></td>
                            <td>{{ $serial->product->name ?? 'N/A' }}</td>
                            <td>{{ $serial->client->name ?? 'N/A' }}</td>
                            <td>{{ $serial->warranty_start ? \Carbon\Carbon::parse($serial->warranty_start)->format('d-m-Y') : 'N/A' }}</td>
                            <td>{{ $warrantyEnd ? $warrantyEnd->format('d-m-Y') : 'N/A' }}</td>
                            <td>
                                @if($daysLeft !== null)
                                    @if($daysLeft < 0)
                                        <span class="badge badge-danger">{{ abs(intval($daysLeft)) }} days ago</span>
                                    @elseif($daysLeft <= 30)
                                        <span class="badge badge-warning">{{ intval($daysLeft) }} days</span>
                                    @else
                                        <span class="badge badge-success">{{ intval($daysLeft) }} days</span>
                                    @endif
                                @else
                                    <span class="badge badge-secondary">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge }}">{{ $statusText }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No warranty records found.</td>
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
    if ($('#warrantyTable tbody tr td[colspan]').length === 0) { $('#warrantyTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[4, "asc"]],
        "pageLength": 25
    }); }
});
</script>
@endpush
