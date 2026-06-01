@extends('layouts.reseller')

@section('title', 'My Profile')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">My Profile</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <div class="profile-user-img img-fluid img-circle bg-primary d-flex align-items-center justify-content-center" style="width: 128px; height: 128px; margin: 0 auto;">
                        <span class="text-white" style="font-size: 48px;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                </div>
                <h3 class="profile-username text-center mt-3">{{ $user->name }}</h3>
                <p class="text-muted text-center">
                    <span class="badge badge-success">Reseller</span>
                </p>
                
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b><i class="fas fa-envelope mr-2"></i>Email</b>
                        <span class="float-right">{{ $user->email }}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-phone mr-2"></i>Phone</b>
                        <span class="float-right">{{ $reseller->phone ?? 'Not set' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-map-marker-alt mr-2"></i>Address</b>
                        <span class="float-right">{{ $reseller->address ?? 'Not set' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-calendar mr-2"></i>Joined</b>
                        <span class="float-right">{{ $user->created_at->format('d-m-Y') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Reseller Statistics</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-users mr-2 text-primary"></i>Total Clients</span>
                        <strong>{{ $reseller->clients()->count() }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-user-check mr-2 text-success"></i>Active Clients</span>
                        <strong class="text-success">{{ $reseller->clients()->where('status', 'active')->count() }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-wallet mr-2 text-warning"></i>Wallet Balance</span>
                        <strong class="text-warning">৳{{ number_format($reseller->wallet_balance ?? 0, 0) }}</strong>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title"><i class="fas fa-id-card mr-2"></i>Account Information</h3>
                @if($reseller->type === 'bandwidth')
                <span class="badge badge-primary" style="font-size:11px;padding:5px 12px;border-radius:100px;">
                    <i class="fas fa-network-wired mr-1"></i>Bandwidth Reseller
                </span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <tr>
                        <th width="35%" class="bg-light">Reseller ID</th>
                        <td><strong>{{ $reseller->reseller_id ?? ('RSL-' . str_pad($reseller->id, 4, '0', STR_PAD_LEFT)) }}</strong></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Business Name</th>
                        <td>{{ $reseller->business_name ?? $reseller->name }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Type</th>
                        <td>
                            @if($reseller->type === 'bandwidth')
                            <span class="badge badge-primary" style="border-radius:100px;padding:4px 12px;">
                                <i class="fas fa-network-wired mr-1"></i>Bandwidth
                            </span>
                            @else
                            <span class="badge badge-secondary" style="border-radius:100px;padding:4px 12px;">
                                <i class="fas fa-desktop mr-1"></i>MAC Binding
                            </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Status</th>
                        <td>
                            @if($reseller->status == 'active')
                            <span class="badge badge-success" style="border-radius:100px;padding:4px 12px;">
                                <i class="fas fa-circle mr-1" style="font-size:7px;"></i>Active
                            </span>
                            @else
                            <span class="badge badge-danger" style="border-radius:100px;padding:4px 12px;">{{ ucfirst($reseller->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">Phone</th>
                        <td>{{ $reseller->phone ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Area</th>
                        <td>{{ $reseller->area_name_en ?? $reseller->thana ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Joined Date</th>
                        <td>{{ $reseller->created_at->format('d M Y') }}</td>
                    </tr>
                    @if($reseller->type === 'bandwidth')
                    <tr>
                        <th class="bg-light">Monthly Rate</th>
                        <td><strong class="text-primary">৳{{ number_format($reseller->bandwidth_monthly_rate ?? 0, 0) }}</strong></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
