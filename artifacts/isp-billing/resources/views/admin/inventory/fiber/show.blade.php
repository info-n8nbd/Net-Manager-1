@extends('layouts.admin')

@section('title', 'Fiber Stock Detail - ' . $stock->fiber_code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Inventory</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
    <li class="breadcrumb-item active">{{ $stock->fiber_code }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-info-circle"></i> Stock Information</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width:40%"><i class="fa-solid fa-barcode"></i> Fiber Code</th>
                        <td><strong>{{ $stock->fiber_code }}</strong></td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-truck"></i> Vendor</th>
                        <td>{{ $stock->vendor->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-ethernet"></i> Fiber Type</th>
                        <td>{{ $stock->fiber_type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-circle-nodes"></i> Core</th>
                        <td>{{ $stock->core }} Core</td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-ruler-horizontal"></i> Start Meter</th>
                        <td>{{ number_format($stock->start_meter) }}m</td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-ruler-horizontal"></i> End Meter</th>
                        <td>{{ number_format($stock->end_meter) }}m</td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-ruler"></i> Total Meter</th>
                        <td>{{ number_format($stock->total_meter) }}m</td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-ruler-combined"></i> Remaining</th>
                        <td>
                            <strong>{{ number_format($stock->remaining_meter) }}m</strong>
                            @php
                                $percent = $stock->total_meter > 0 ? round(($stock->remaining_meter / $stock->total_meter) * 100) : 0;
                                $usedPercent = 100 - $percent;
                            @endphp
                        </td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-signal"></i> Status</th>
                        <td>
                            @if($stock->status === 'active')
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Active</span>
                            @else
                                <span class="badge badge-danger"><i class="fa-solid fa-times"></i> Finished</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-calendar"></i> Stock Date</th>
                        <td>{{ $stock->stock_date ? \Carbon\Carbon::parse($stock->stock_date)->format('d-m-Y') : ($stock->purchase_date ? \Carbon\Carbon::parse($stock->purchase_date)->format('d-m-Y') : 'N/A') }}</td>
                    </tr>
                </table>

                <div class="mt-3">
                    <label>Usage Progress</label>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar {{ $usedPercent > 80 ? 'bg-danger' : ($usedPercent > 50 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $usedPercent }}%">
                            {{ $usedPercent }}% Used
                        </div>
                    </div>
                    <small class="text-muted">{{ number_format($stock->total_meter - $stock->remaining_meter) }}m used of {{ number_format($stock->total_meter) }}m total</small>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.inventory.fiber.edit', $stock->id) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <form action="{{ route('admin.inventory.fiber.destroy', $stock->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this fiber stock? This cannot be undone!')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm ml-1"><i class="fas fa-trash mr-1"></i> Delete</button>
                </form>
                <a href="{{ route('admin.inventory.fiber.index') }}" class="btn btn-secondary btn-sm ml-1">
                    <i class="fa-solid fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-clipboard-list"></i> Usage Log</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Meter Used</th>
                            <th>Client</th>
                            <th>Technician</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stock->usages as $usage)
                        <tr>
                            <td>{{ $usage->used_date ? \Carbon\Carbon::parse($usage->used_date)->format('d-m-Y') : 'N/A' }}</td>
                            <td><strong>{{ number_format($usage->used_meter) }}m</strong></td>
                            <td>{{ $usage->client->name ?? 'N/A' }}</td>
                            <td>{{ $usage->technician->name ?? 'N/A' }}</td>
                            <td>{{ $usage->note ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No usage records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> History</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Meter</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($histories as $history)
                        <tr>
                            <td>{{ $history->date ? \Carbon\Carbon::parse($history->date)->format('d-m-Y') : 'N/A' }}</td>
                            <td>
                                @if($history->type === 'stock_in')
                                    <span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> Stock In</span>
                                @else
                                    <span class="badge badge-warning"><i class="fa-solid fa-arrow-up"></i> Usage</span>
                                @endif
                            </td>
                            <td>{{ number_format($history->meter) }}m</td>
                            <td>{{ $history->details ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No history records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
