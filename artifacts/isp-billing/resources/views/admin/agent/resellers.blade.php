@extends('layouts.admin')
@section('title', 'Resellers under ' . $agent->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.agent.index') }}">Referral Partner</a></li>
<li class="breadcrumb-item active">{{ $agent->name }} — Resellers</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-body text-center">
                <h5 class="mb-1">{{ $agent->name }}</h5>
                <p class="text-muted mb-2">Partner ID: {{ $agent->agent_id }}</p>
                <span class="badge badge-{{ $agent->status === 'active' ? 'success' : 'danger' }} mb-2" style="font-size: 13px; padding: 5px 12px;">
                    Commission {{ $agent->status === 'active' ? 'ON' : 'OFF' }}
                </span>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-0">{{ $resellers->count() }}</h4>
                        <small class="text-muted">Total Resellers</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-info mb-0">{{ number_format($agent->default_commission, 2) }}%</h4>
                        <small class="text-muted">Default Rate</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="text-success mb-0">৳{{ number_format($totalBills, 2) }}</h5>
                        <small class="text-muted">Total Reseller Bills</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-warning mb-0">৳{{ number_format($totalCommission, 2) }}</h5>
                        <small class="text-muted">Total Commission Earned</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Resellers under {{ $agent->name }}</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead class="bg-info text-white">
                            <tr>
                                <th>Reseller ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Phone</th>
                                <th>Area</th>
                                <th>Clients</th>
                                <th>Commission %</th>
                                <th>Total Bill</th>
                                <th>Total Paid</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($resellers as $reseller)
                            <tr>
                                <td>{{ $reseller->reseller_id }}</td>
                                <td><strong>{{ $reseller->name }}</strong></td>
                                <td>
                                    @if($reseller->type === 'bandwidth')
                                    <span class="badge badge-warning">BW</span>
                                    @else
                                    <span class="badge badge-info">MAC</span>
                                    @endif
                                </td>
                                <td>{{ $reseller->phone }}</td>
                                <td>{{ $reseller->area_name_en ?? '-' }}</td>
                                <td><span class="badge badge-primary">{{ $reseller->clients_count }}</span></td>
                                <td>
                                    @php
                                        $effectiveRate = $reseller->referral_commission_percent ?? $agent->default_commission;
                                        $isCustom = $reseller->referral_commission_percent !== null;
                                    @endphp
                                    <span class="badge badge-{{ $isCustom ? 'success' : 'secondary' }}" title="{{ $isCustom ? 'Custom rate' : 'Partner default rate' }}">
                                        {{ number_format($effectiveRate, 2) }}%
                                    </span>
                                </td>
                                <td>৳{{ number_format($reseller->total_bill, 2) }}</td>
                                <td>৳{{ number_format($reseller->total_paid, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $reseller->status === 'active' ? 'success' : 'danger' }}">
                                        {{ ucfirst($reseller->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.reseller.show', $reseller) }}" class="btn btn-info btn-xs" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.reseller.edit', $reseller) }}" class="btn btn-warning btn-xs" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3">No resellers assigned to this referral partner</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
