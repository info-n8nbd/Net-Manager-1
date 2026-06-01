@extends('layouts.admin')
@section('title', 'Portal Permission Control — ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type' => $reseller->type]) }}">{{ $reseller->type === 'bandwidth' ? 'Bandwidth Resellers' : 'Resellers' }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item active">Portal Permission Control</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">

        {{-- Reseller Info Card --}}
        <div class="card card-outline card-{{ $reseller->type === 'bandwidth' ? 'info' : 'primary' }} mb-3">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mr-3"
                         style="width:48px;height:48px;background:{{ $reseller->type === 'bandwidth' ? 'linear-gradient(135deg,#17a2b8,#0d6efd)' : 'linear-gradient(135deg,#1e3c72,#2a5298)' }};color:#fff;font-weight:700;font-size:18px;">
                        {{ strtoupper(substr($reseller->name, 0, 1)) }}
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold">{{ $reseller->name }}</h5>
                        <small class="text-muted">
                            {{ $reseller->reseller_id }} &bull; {{ $reseller->email }}
                            &bull; <span class="badge badge-{{ $reseller->type === 'bandwidth' ? 'info' : 'warning' }}">{{ ucfirst($reseller->type) }} Reseller</span>
                            &bull; <span class="badge badge-{{ $reseller->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($reseller->status) }}</span>
                        </small>
                    </div>
                    <div class="ml-auto">
                        <a href="{{ route('admin.reseller.show', $reseller) }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($reseller->allowed_features === null)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>No restrictions applied yet.</strong> This reseller currently has access to ALL features.
            Use the controls below to restrict what they can see in their portal.
        </div>
        @endif

        {{-- Feature Control Form --}}
        <div class="card card-outline" style="border-color:#6f42c1;">
            <div class="card-header" style="border-color:#6f42c1;">
                <h3 class="card-title">
                    <i class="fas fa-sliders-h mr-2" style="color:#6f42c1;"></i>
                    Portal Permission Control
                </h3>
                <div class="card-tools">
                    @php
                        if($reseller->type === 'bandwidth') {
                            $shownKeys = ['view_bw_amount','view_bw_price','view_billing_ledger','view_adjustments','view_payments','view_category_breakdown','view_my_invoices','wallet'];
                        } else {
                            $shownKeys = ['clients','client_create','client_edit','client_delete_restore','online_clients','collection','billing','due_report','reports','wallet','recharge_correction','recharge_reversal','zones','packages','packages_manage','device_control','tickets','network','sub_resellers','sms'];
                        }
                        $badgeTotal   = count($shownKeys);
                        $badgeEnabled = count(array_intersect((array)$enabledFeatures, $shownKeys));
                    @endphp
                    <span class="badge" style="background:#6f42c1;color:#fff;font-size:12px;">
                        {{ $badgeEnabled }} of {{ $badgeTotal }} features enabled
                    </span>
                </div>
            </div>
            <form action="{{ route('admin.reseller.features.save', $reseller) }}" method="POST">
                @csrf
                <div class="card-body">
                    <p class="text-muted mb-4">
                        <i class="fas fa-info-circle mr-1"></i>
                        Select which features this reseller can access in their portal. Unchecked features will be
                        <strong>hidden from their sidebar and blocked at the route level.</strong>
                    </p>

                    @php
                        $featureGroups = [
                            'Bandwidth Portal Visibility' => [
                                'view_bw_amount'      => 'View Bandwidth Amounts (Mbps)',
                                'view_bw_price'       => 'View Bandwidth Pricing & Rates',
                                'view_billing_ledger' => 'View Billing Ledger (Bill History)',
                                'view_adjustments'    => 'View Mid-Month Adjustments',
                                'view_payments'       => 'View Recent Payments',
                                'view_category_breakdown' => 'View Category Breakdown (Invoice Details)',
                                'view_my_invoices'        => 'View My Invoices (Billing & Transactions)',
                            ],
                            'Wallet & Finance' => [
                                'wallet' => 'Wallet Access & Recharge',
                            ],
                        ];
                        if($reseller->type !== 'bandwidth') {
                            $featureGroups = [
                                'Client Management' => [
                                    'clients'       => 'View Clients',
                                    'client_create' => 'Add New Clients',
                                    'client_edit'   => 'Edit Clients',
                                    'client_delete_restore' => 'Delete & Restore Clients',
                                    'online_clients'=> 'View Online Clients',
                                ],
                                'Billing & Finance' => [
                                    'collection'  => 'Collect Payments',
                                    'billing'     => 'Generate Bills',
                                    'due_report'  => 'Due Report',
                                    'reports'     => 'Collection Report',
                                    'wallet'      => 'Wallet Recharge',
                                    'recharge_correction' => 'Recharge Correction',
                                'recharge_reversal'   => 'Recharge Reversal',
                                ],
                                'Configuration' => [
                                    'zones'           => 'Manage Zones & Areas',
                                    'packages'        => 'View Assigned Packages',
                                    'packages_manage' => 'Manage Own Packages',
                                ],
                                'Advanced' => [
                                    'device_control' => 'MikroTik Device Control',
                                    'tickets'        => 'Support Tickets',
                                    'network'        => 'Network / OLT View',
                                    'sub_resellers'  => 'Sub-Resellers',
                                ],
                                'Communications' => [
                                    'sms' => 'SMS Notifications',
                                ],
                            ];
                        }
                    @endphp

                    <div class="row">
                        @foreach($featureGroups as $groupName => $groupFeatures)
                        <div class="col-md-6 mb-3">
                            <div class="card card-outline card-secondary h-100">
                                <div class="card-header py-2" style="background:#f8f9fa;">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-layer-group mr-1" style="color:#6f42c1;"></i>
                                        <strong>{{ $groupName }}</strong>
                                    </h6>
                                </div>
                                <div class="card-body py-2">
                                    @foreach($groupFeatures as $featureKey => $featureLabel)
                                    @if(isset($availableFeatures[$featureKey]))
                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="feat_{{ $featureKey }}"
                                               name="features[]"
                                               value="{{ $featureKey }}"
                                               {{ in_array($featureKey, $enabledFeatures) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="feat_{{ $featureKey }}">
                                            <span style="font-size:13px;">{{ $featureLabel }}</span>
                                            <br><small class="text-muted" style="font-size:11px;">Key: <code>{{ $featureKey }}</code></small>
                                        </label>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-3 p-3" style="background:#f8f9fa;border-radius:8px;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-success mr-1" onclick="toggleAll(true)">
                                    <i class="fas fa-check-double mr-1"></i> Enable All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleAll(false)">
                                    <i class="fas fa-times mr-1"></i> Disable All
                                </button>
                            </div>
                            <button type="submit" class="btn px-4" style="background:#6f42c1;color:#fff;border-color:#6f42c1;">
                                <i class="fas fa-save mr-1"></i> Save Permission Settings
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Preview of what reseller will see --}}
        @if($reseller->type === 'bandwidth')
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-eye mr-2 text-info"></i>What the Reseller Sees (Preview)</h3>
            </div>
            <div class="card-body">
                <p class="text-muted" style="font-size:13px;">Based on current permissions, <strong>{{ $reseller->name }}</strong> will see these sections on their Bandwidth Dashboard:</p>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled" style="font-size:13px;">
                            <li class="mb-1">
                                <i class="fas fa-{{ in_array('view_bw_price', $enabledFeatures) || $reseller->allowed_features === null ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-2"></i>
                                Monthly Bill Amount (KPI card)
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-{{ in_array('view_bw_amount', $enabledFeatures) || $reseller->allowed_features === null ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-2"></i>
                                Bandwidth Mbps Column
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-{{ in_array('view_bw_price', $enabledFeatures) || $reseller->allowed_features === null ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-2"></i>
                                Price Per Mbps & Monthly Total
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled" style="font-size:13px;">
                            <li class="mb-1">
                                <i class="fas fa-{{ in_array('view_billing_ledger', $enabledFeatures) || $reseller->allowed_features === null ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-2"></i>
                                Billing Ledger (bill history table)
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-{{ in_array('view_adjustments', $enabledFeatures) || $reseller->allowed_features === null ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-2"></i>
                                Mid-Month Bandwidth Adjustments
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-{{ in_array('view_payments', $enabledFeatures) || $reseller->allowed_features === null ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-2"></i>
                                Recent Payments Table
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-{{ in_array('view_category_breakdown', $enabledFeatures) || $reseller->allowed_features === null ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-2"></i>
                                View Category Breakdown (Invoice Details)
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-{{ in_array('wallet', $enabledFeatures) || $reseller->allowed_features === null ? 'check-circle text-success' : 'times-circle text-secondary' }} mr-2"></i>
                                Wallet Balance & Recharge
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleAll(state) {
    document.querySelectorAll('input[name="features[]"]').forEach(cb => cb.checked = state);
}
</script>
@endpush
