@extends('layouts.reseller')

@section('title', 'My Packages')
@section('breadcrumb')
<li class="breadcrumb-item active">Packages</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-box mr-2"></i>My Assigned Packages</h3>
    </div>
    <div class="card-body">
        @if($packages->count() > 0)
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Package Name</th>
                        <th>Speed</th>
                        <th class="text-right">Client Price (৳)</th>
                        <th class="text-right">Your Cost (৳)</th>
                        <th class="text-right">Your Profit (৳)</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($packages as $index => $resellerPackage)
                    @php
                        $clientPrice = $resellerPackage->selling_price ?? $resellerPackage->package->price ?? 0;
                        $resellerCost = $resellerPackage->reseller_price ?? 0;
                        $profit = $clientPrice - $resellerCost;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $resellerPackage->package->name ?? 'Package' }}</strong></td>
                        <td>{{ $resellerPackage->package->download_speed ?? 'N/A' }} Mbps</td>
                        <td class="text-right">৳{{ number_format($clientPrice) }}</td>
                        <td class="text-right text-primary font-weight-bold">৳{{ number_format($resellerCost) }}</td>
                        <td class="text-right {{ $profit >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">৳{{ number_format($profit) }}</td>
                        <td class="text-center">
                            <span class="badge badge-{{ $resellerPackage->is_active ? 'success' : 'secondary' }}">
                                {{ $resellerPackage->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No Packages Assigned</h5>
            <p class="text-muted">Contact your administrator to get packages assigned to your account.</p>
        </div>
        @endif
    </div>
</div>
@endsection
