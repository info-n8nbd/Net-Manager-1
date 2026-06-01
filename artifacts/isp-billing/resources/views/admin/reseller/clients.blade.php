@extends('layouts.admin')

@section('title', 'MAC Reseller Clients')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index') }}">MAC Reseller</a></li>
@if($reseller)
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
@endif
<li class="breadcrumb-item active">MAC Reseller Clients</li>
@endsection

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users mr-1"></i>
            @if($reseller) {{ $reseller->name }} — Clients @else All Reseller Clients @endif
        </h3>
        <div class="card-tools">
            <span class="badge badge-primary">{{ $clients->total() }} clients</span>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reseller.clients') }}" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, Phone, PPPoE, Client ID..." value="{{ request('search') }}">
                </div>
                @if(!$reseller)
                <div class="col-md-3">
                    <select name="reseller_id" class="form-control form-control-sm">
                        <option value="">All MAC Resellers</option>
                        @foreach($resellers as $r)
                        <option value="{{ $r->id }}" {{ request('reseller_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                    <a href="{{ route('admin.reseller.clients') }}" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>PPPoE Username</th>
                        <th>Phone</th>
                        @if(!$reseller)<th>MAC Reseller</th>@endif
                        <th>Zone</th>
                        <th>Package</th>
                        <th>MAC Reseller Rate</th>
                        <th>Selling Price</th>
                        <th>Due (৳)</th>
                        <th>Status</th>
                        <th>Recharge</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $i => $client)
                    <tr>
                        <td>{{ $clients->firstItem() + $i }}</td>
                        <td>
                            <strong>{{ $client->name }}</strong><br>
                            <small class="text-muted">{{ $client->client_id }}</small>
                        </td>
                        <td><code>{{ $client->username ?? '-' }}</code></td>
                        <td>{{ $client->phone ?? '-' }}</td>
                        @if(!$reseller)
                        <td>
                            @if($client->reseller)
                            <a href="{{ route('admin.reseller.clients', $client->reseller) }}" class="text-primary">{{ $client->reseller->name }}</a>
                            @else <span class="text-muted">N/A</span> @endif
                        </td>
                        @endif
                        <td>{{ $client->zone->name ?? '-' }}</td>
                        <td>{{ $client->package->name ?? '-' }}</td>
                        @php
                            $rpKey = $client->reseller_id . '-' . $client->package_id;
                            $rp = $resellerPackageMap[$rpKey] ?? null;
                        @endphp
                        <td>
                            @if($rp)
                                <span class="text-info font-weight-bold">৳{{ number_format($rp->reseller_price ?? 0, 0) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($rp)
                                <span class="text-primary font-weight-bold">৳{{ number_format($rp->selling_price ?? 0, 0) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if(($client->due_amount ?? 0) > 0)
                                <span class="text-danger font-weight-bold">৳{{ number_format($client->due_amount, 0) }}</span>
                            @else
                                <span class="text-success">৳0</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $client->status == 'active' ? 'success' : ($client->status == 'inactive' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($client->status) }}
                            </span>
                        </td>
                        <td class="text-center" style="white-space:nowrap;">
                            <form action="{{ route('admin.reseller.client.toggle-recharge-block', [$client->reseller_id, $client]) }}" method="POST" class="d-inline">
                                @csrf
                                @if($client->recharge_blocked)
                                    <button type="submit" class="btn btn-warning btn-xs" style="font-size:11px;padding:2px 7px;" title="Unblock Recharge" onclick="return confirm('Unblock recharge for {{ addslashes($client->name) }}?')">
                                        <i class="fas fa-ban"></i> Blocked
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-outline-danger btn-xs" style="font-size:11px;padding:2px 7px;" title="Block Recharge" onclick="return confirm('Block recharge for {{ addslashes($client->name) }}?')">
                                        <i class="fas fa-lock"></i> Block
                                    </button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $reseller ? 11 : 12 }}" class="text-center text-muted py-4">No reseller clients found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $clients->links() }}
    </div>
</div>
@endsection
