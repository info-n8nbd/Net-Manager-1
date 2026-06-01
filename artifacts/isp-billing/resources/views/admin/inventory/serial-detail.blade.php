@extends('layouts.admin')

@section('title', 'Serial Detail')

@section('content')
        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-barcode mr-2"></i>Serial Information</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Serial:</strong></td>
                                <td><code>{{ $serial->serial_number }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>Product:</strong></td>
                                <td>{{ $serial->product->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-{{ $serial->status === 'available' ? 'success' : ($serial->status === 'assigned' ? 'info' : ($serial->status === 'damaged' ? 'danger' : 'secondary')) }}">
                                        {{ ucfirst($serial->status) }}
                                    </span>
                                </td>
                            </tr>
                            @if($serial->mac_address)
                            <tr>
                                <td><strong>MAC:</strong></td>
                                <td><code>{{ $serial->mac_address }}</code></td>
                            </tr>
                            @endif
                            @if($serial->assigned_to)
                            <tr>
                                <td><strong>Assigned To:</strong></td>
                                <td>{{ $serial->assignedClient->name ?? $serial->assigned_to }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td>{{ $serial->created_at ? $serial->created_at->format('d M Y') : 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card card-{{ $warrantyStatus['color'] }} card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Warranty</h3>
                    </div>
                    <div class="card-body text-center">
                        <span class="badge badge-{{ $warrantyStatus['color'] }} px-3 py-2">{{ $warrantyStatus['label'] }}</span>
                        @if($warrantyStatus['expiry_date'])
                            <p class="mt-2 mb-0"><small class="text-muted">Expires: {{ $warrantyStatus['expiry_date'] }}</small></p>
                        @endif
                        @if($warrantyStatus['days_remaining'] !== null)
                            <p class="mb-0"><small>{{ $warrantyStatus['days_remaining'] }} days remaining</small></p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                @if($history->count())
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Stock Movement History</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $h)
                                    <tr>
                                        <td>{{ $h->created_at ? \Carbon\Carbon::parse($h->created_at)->format('d M Y h:i A') : 'N/A' }}</td>
                                        <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $h->type ?? 'N/A')) }}</span></td>
                                        <td>{{ $h->quantity ?? '-' }}</td>
                                        <td>{{ $h->reference ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($assignmentHistory->count())
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-check mr-2"></i>Assignment History</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr><th>Date</th><th>Assigned To</th><th>Details</th></tr>
                            </thead>
                            <tbody>
                                @foreach($assignmentHistory as $a)
                                    <tr>
                                        <td>{{ $a->created_at ? \Carbon\Carbon::parse($a->created_at)->format('d M Y') : 'N/A' }}</td>
                                        <td>{{ $a->assigned_to ?? $a->reference ?? 'N/A' }}</td>
                                        <td>{{ $a->details ?? $a->note ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($damageHistory->count())
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-heart-broken mr-2"></i>Damage History</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr><th>Date</th><th>Remarks</th></tr>
                            </thead>
                            <tbody>
                                @foreach($damageHistory as $d)
                                    <tr>
                                        <td>{{ $d->created_at ? \Carbon\Carbon::parse($d->created_at)->format('d M Y') : 'N/A' }}</td>
                                        <td>{{ $d->remarks ?? $d->details ?? $d->note ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="mb-3">
            <a href="{{ route('admin.inventory.serial.search') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Back to Serial Search</a>
        </div>
@endsection
