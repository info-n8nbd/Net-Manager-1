@extends('layouts.admin')

@section('title', $typeFilter === 'bandwidth' ? 'Bandwidth Resellers' : 'MAC Resellers')
@section('breadcrumb')
<li class="breadcrumb-item active">{{ $typeFilter === 'bandwidth' ? 'Bandwidth Resellers' : 'MAC Resellers' }}</li>
@endsection

@push('styles')
<style>
.reseller-fin-row td {
    background: #f8f9fa;
    border-top: none !important;
    padding: 4px 8px !important;
}
.fin-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    line-height: 1;
}
.fin-pill .fin-label { font-size: 10px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.3px; }
.fin-pill .fin-value { font-size: 12px; font-weight: 700; }
.fin-sep { border-left: 1px solid #dee2e6; height: 14px; margin: 0 4px; }
.btn-xs { padding: 2px 6px; font-size: 11px; line-height: 1.5; border-radius: 3px; }
#reseller-table thead th { border-bottom: 3px solid #dc3545; font-size: 11px; white-space: nowrap; }
#reseller-table > tbody > tr.reseller-main-row td { vertical-align: middle; font-size: 12px; border-bottom: none !important; }
#reseller-table > tbody > tr.reseller-fin-row { border-bottom: 2px solid #dee2e6; }
#bw-reseller-table thead th { border-bottom: 3px solid #17a2b8; font-size: 11px; white-space: nowrap; }
#bw-reseller-table > tbody > tr.bw-main-row td { vertical-align: middle; font-size: 12px; border-bottom: none !important; }
#bw-reseller-table > tbody > tr.bw-fin-row { border-bottom: 2px solid #dee2e6; }
#bw-reseller-table > tbody > tr.bw-fin-row td { background: #f0f8ff; border-top: none !important; padding: 4px 8px !important; }
.bw-badge { display:inline-block; font-size:11px; padding:2px 7px; border-radius:3px; margin-bottom:2px; font-weight:600; }
.bw-grid { display:flex; flex-wrap:wrap; gap:4px; align-items:flex-start; }
.bw-row  { display:flex; flex-direction:column; align-items:center; border:1px solid #dee2e6; border-radius:5px; padding:3px 8px; background:#fff; min-width:72px; text-align:center; }
.bw-lbl  { font-size:10px; font-weight:700; padding:1px 4px; border-radius:3px; color:#fff; flex-shrink:0; letter-spacing:.3px; }
.bw-mbps { font-size:11px; font-weight:700; color:#222; white-space:nowrap; flex-shrink:0; display:none; }
.bw-grid.expanded .bw-mbps,.bw-grid.expanded .bw-rate { display:block !important; }
.bw-expand-btn { display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;border:1px solid #c8ddf0;background:#f0f8ff;color:#1a7ab5;font-size:10px;font-weight:600;cursor:pointer;white-space:nowrap;margin-bottom:4px;line-height:1.5; }
.bw-expand-btn:hover { background:#dbeafe;border-color:#3b82f6; }
.bw-rate { font-size:10px; color:#888; white-space:nowrap; display:none; }
</style>
@endpush

@section('content')

{{-- ===== SUMMARY CARDS (AdminLTE small-box) ===== --}}
@if($typeFilter === 'bandwidth')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner"><h3>{{ $totalResellers }}</h3><p>Total Resellers</p></div>
            <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $activeResellers }}</h3><p>Active</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3>৳{{ number_format($tabTotalDue, 0) }}</h3><p>Total Due</p></div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3>৳{{ number_format($totalWalletBalance, 0) }}</h3><p>Total Wallet</p></div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
    </div>
</div>
<div class="mb-3 d-flex align-items-center" style="gap:8px;">
    <a href="{{ route('admin.reseller.create', ['type' => 'bandwidth']) }}" class="btn btn-info btn-sm"><i class="fas fa-plus mr-1"></i>Add Bandwidth Reseller</a>
    @if($statusFilter === 'suspended')
        <a href="{{ route('admin.reseller.index', ['type' => 'bandwidth']) }}" class="btn btn-success btn-sm"><i class="fas fa-check-circle mr-1"></i>Active Resellers</a>
    @else
        <a href="{{ route('admin.reseller.index', ['type' => 'bandwidth', 'status' => 'terminated']) }}" class="btn btn-danger btn-sm"><i class="fas fa-ban mr-1"></i>Terminated</a>
    @endif
</div>

@else
{{-- MAC Resellers --}}
<div class="row">
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner"><h3>{{ $totalResellers }}</h3><p>Total Resellers</p></div>
            <div class="icon"><i class="fas fa-network-wired"></i></div>
            
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $activeResellers }}</h3><p>Active</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3>{{ $terminatedResellers }}</h3><p>Terminated</p></div>
            <div class="icon"><i class="fas fa-ban"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $totalClients }}</h3><p>Total Clients</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
            
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3>৳{{ number_format($tabTotalDue, 0) }}</h3><p>Total Due (ISP)</p></div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-teal">
            <div class="inner"><h3>৳{{ number_format($totalWalletBalance, 0) }}</h3><p>Total Balance</p></div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
            
        </div>
    </div>
</div>
<div class="mb-3 d-flex align-items-center" style="gap:8px;">
    <a href="{{ route('admin.reseller.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Add MAC Reseller</a>
    @if($statusFilter === 'suspended')
        <a href="{{ route('admin.reseller.index') }}" class="btn btn-success btn-sm"><i class="fas fa-check-circle mr-1"></i>Active Resellers</a>
    @else
        <a href="{{ route('admin.reseller.index', ['status' => 'terminated']) }}" class="btn btn-danger btn-sm"><i class="fas fa-ban mr-1"></i>Terminated Resellers</a>
    @endif
    <form action="{{ route('admin.reseller.sync-all-clients') }}" method="POST" class="d-inline" onsubmit="return confirm('Sync all reseller clients?')">
        @csrf
        <button type="submit" class="btn btn-outline-info btn-sm"><i class="fas fa-sync mr-1"></i>Sync All</button>
    </form>
</div>
@endif


{{-- ============================================================ --}}
{{-- BANDWIDTH RESELLER TABLE --}}
{{-- ============================================================ --}}
@if($typeFilter === 'bandwidth')

<div class="card card-outline card-info mb-0">
    <div class="card-header py-2">
        <h3 class="card-title">
            <i class="fas fa-tachometer-alt mr-2 text-info"></i>
            {{ $statusFilter === 'suspended' ? 'Terminated' : 'Active' }} Bandwidth Resellers
            <small class="text-muted ml-2" style="font-size:12px;">IIG / GGC / FNA / BDIX / CDN / Cache</small>
        </h3>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width:200px;">
                <input type="text" id="bw-search" class="form-control" placeholder="Search reseller...">
                <div class="input-group-append"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-sm mb-0" id="bw-reseller-table">
            <thead class="bg-dark text-white">
                <tr>
                    <th style="width:110px;">Reseller ID</th>
                    <th style="min-width:160px;">Name / Contact</th>
                    <th style="min-width:260px;">Bandwidth Allocation</th>
                    <th class="text-center" style="width:110px;">Monthly Rate</th>
                    <th class="text-center" style="width:100px;">Billing</th>
                    <th class="text-center" style="width:150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resellers as $reseller)
                @php
                    $isSuspended = in_array($reseller->status, ['suspended', 'inactive']);
                    $fin = $resellerFinancials[$reseller->id] ?? ['total_recharge' => 0, 'due_to_admin' => 0];
                    $totalBilled   = (float) $fin['total_recharge'];
                    $dueToAdmin    = (float) $fin['due_to_admin'];
                    $paidToAdmin   = max(0, $totalBilled - $dueToAdmin);
                    $walletBalance = (float) ($reseller->wallet_balance ?? 0);
                    $creditLimit   = (float) ($reseller->credit_limit ?? 0);
                    $duePct = $creditLimit > 0 ? min(100, round($dueToAdmin / $creditLimit * 100)) : 0;
                    $dueBarColor = $duePct >= 90 ? '#dc3545' : ($duePct >= 70 ? '#fd7e14' : '#28a745');

                    // Bandwidth fields
                    $bwFields = array_values(array_map(function($cat) {
                        $shortLabel = strtoupper($cat['key'] === 'intl' ? 'IIG' : $cat['key']);
                        return [
                            'key'        => $cat['key'],
                            'label'      => $shortLabel,
                            'field'      => $cat['mbps_field'],
                            'icon'       => $cat['icon'],
                            'bg'         => \App\Models\BandwidthCategory::colorToHex($cat['color']),
                            'rate_field' => $cat['rate_field'],
                        ];
                    }, \App\Models\BandwidthCategory::getStandardCategories()));
                @endphp

                {{-- Main info row --}}
                <tr class="bw-main-row {{ $isSuspended ? 'table-secondary' : '' }}" data-name="{{ strtolower($reseller->name) }} {{ strtolower($reseller->reseller_id) }}">
                    <td>
                        <a href="{{ route('admin.reseller.show', $reseller) }}" class="font-weight-bold text-info">{{ $reseller->reseller_id }}</a>
                        @if($isSuspended)
                        <br><span class="badge badge-danger" style="font-size:9px;">Terminated</span>
                        @endif
                        <br>
                        <span class="badge badge-{{ $reseller->billing_type == 'prepaid' ? 'purple' : 'success' }}" style="font-size:9px;background:{{ $reseller->billing_type == 'prepaid' ? '#6f42c1' : '#28a745' }};color:#fff;">
                            {{ strtoupper($reseller->billing_type ?? 'PREPAID') }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.reseller.show', $reseller) }}"><strong>{{ $reseller->name }}</strong></a>
                        <br><small class="text-success"><i class="fas fa-phone-alt mr-1"></i>{{ $reseller->phone }}</small>
                        @if($reseller->address)
                        <br><small class="text-muted" style="font-size:10px;"><i class="fas fa-map-marker-alt mr-1"></i>{{ $reseller->address }}</small>
                        @endif
                    </td>
                    <td>
                        <div class="bw-grid" id="bwg-{{ $reseller->id }}">
                        <button type="button" class="bw-expand-btn" onclick="toggleBwGrid('{{ $reseller->id }}', this)" title="Toggle Mbps &amp; Rate"><i class="fas fa-eye" style="font-size:9px;"></i> Details</button>
                        @foreach($bwFields as $bw)
                        @php $mbps = (float)($reseller->{$bw['field']} ?? 0); @endphp
                        @if($mbps > 0)
                        @php $rate = (float)($reseller->{$bw['rate_field']} ?? 0); @endphp
                        <div class="bw-row">
                            <span class="bw-lbl" style="background:{{ $bw['bg'] }};"><i class="{{ $bw['icon'] }}"></i> {{ $bw['label'] }}</span>
                            <span class="bw-mbps">{{ number_format($mbps, 0) }}<span style="font-size:9px;font-weight:400;color:#666;"> Mbps</span></span>
                            @if($rate > 0)<span class="bw-rate">৳{{ number_format($rate, 0) }}/M</span>@endif
                        </div>
                        @endif
                        @endforeach
                        @php $customAllocs = $customAllocsByReseller->get($reseller->id, collect()); @endphp
                        @foreach($customAllocs as $cAlloc)
                        @php $cCat = $customDbCategories->firstWhere('id', $cAlloc->category_id); @endphp
                        @if($cCat)
                        <div class="bw-row">
                            <span class="bw-lbl" style="background:{{ \App\Models\BandwidthCategory::colorToHex($cCat->color) }};"><i class="{{ str_starts_with($cCat->icon, 'img:') ? 'fas fa-network-wired' : $cCat->icon }}"></i> {{ $cCat->label }}</span>
                            <span class="bw-mbps">{{ number_format($cAlloc->mbps, 0) }}<span style="font-size:9px;font-weight:400;color:#666;"> Mbps</span></span>
                            @if($cAlloc->rate > 0)<span class="bw-rate">৳{{ number_format($cAlloc->rate, 0) }}/M</span>@endif
                        </div>
                        @endif
                        @endforeach
                        @if(collect($bwFields)->every(fn($bw) => (float)($reseller->{$bw['field']} ?? 0) === 0.0) && $customAllocs->isEmpty())
                            <div style="grid-column:1/-1;"><span class="text-muted" style="font-size:11px;"><i class="fas fa-exclamation-triangle mr-1 text-warning"></i>No bandwidth allocated</span></div>
                        @endif
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="font-weight-bold text-dark" style="font-size:14px;">৳{{ number_format($reseller->bandwidth_monthly_rate ?? 0, 0) }}</span>
                        <br><small class="text-muted">/month</small>
                        <br>
                        @php
                            $billingDay = ($reseller->recharge_day_type ?? 'multi') === 'single' && $reseller->single_recharge_day
                                ? $reseller->single_recharge_day . ' DAY'
                                : ($reseller->minimum_recharge_days ?? 30) . ' DAYS';
                        @endphp
                        <span class="badge badge-secondary" style="font-size:9px;">{{ $billingDay }}</span>
                        <span class="badge {{ $reseller->auto_bill_generate ? 'badge-success' : 'badge-danger' }}" style="font-size:9px;">{{ $reseller->auto_bill_generate ? 'AUTO' : 'MANUAL' }}</span>
                    </td>
                    <td class="text-center">
                        <div>
                            <span class="text-{{ $dueToAdmin > 0 ? 'danger' : 'success' }} font-weight-bold" style="font-size:13px;">৳{{ number_format($dueToAdmin, 0) }}</span>
                            <br><small class="text-muted">due</small>
                        </div>
                        @if($walletBalance > 0)
                        <div class="mt-1">
                            <span class="text-success" style="font-size:11px;">৳{{ number_format($walletBalance, 0) }}</span>
                            <br><small class="text-muted" style="font-size:9px;">wallet</small>
                        </div>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center flex-wrap" style="gap:2px;">
                            <a href="{{ route('admin.reseller.bandwidth', $reseller) }}" class="btn btn-info btn-xs" title="Bandwidth Allocation"><i class="fas fa-tachometer-alt"></i></a>
                            <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-secondary btn-xs" title="Bill History"><i class="fas fa-file-invoice"></i></a>
                            <a href="{{ route('admin.reseller.current-invoice', $reseller) }}" class="btn btn-primary btn-xs" title="Print Current Invoice" target="_blank"><i class="fas fa-print"></i></a>
                            <a href="{{ route('admin.reseller.generate-bill', $reseller) }}" class="btn btn-dark btn-xs" title="Generate Bill"><i class="fas fa-file-alt"></i></a>
                            <a href="{{ route('admin.reseller.bill-payment', $reseller) }}" class="btn btn-success btn-xs" title="Collect Payment"><i class="fas fa-money-bill-wave"></i></a>
                            <a href="{{ route('admin.reseller.edit', $reseller) }}" class="btn btn-warning btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                            @if($reseller->user_id)
                            <form action="{{ route('admin.reseller.login-as', $reseller) }}" method="POST" class="d-inline" onsubmit="return confirm('Login as {{ addslashes($reseller->name) }}?')">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-xs" title="Login As Reseller"><i class="fas fa-sign-in-alt"></i></button>
                            </form>
                            @endif
                            @if($reseller->status === 'active')
                            <form action="{{ route('admin.reseller.terminate', $reseller) }}" method="POST" class="d-inline" onsubmit="return confirm('Terminate {{ addslashes($reseller->name) }}?')">
                                @csrf
                                <input type="hidden" name="termination_reason" value="Terminated from list">
                                <button type="submit" class="btn btn-outline-danger btn-xs" title="Terminate"><i class="fas fa-ban"></i></button>
                            </form>
                            @else
                            <form action="{{ route('admin.reseller.reactivate', $reseller) }}" method="POST" class="d-inline" onsubmit="return confirm('Reactivate {{ addslashes($reseller->name) }}?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-xs" title="Reactivate"><i class="fas fa-redo"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>

                {{-- Financial sub-row --}}
                <tr class="bw-fin-row">
                    <td colspan="6">
                        <div class="d-flex align-items-center flex-wrap" style="gap:0;padding:3px 2px;">
                            <span class="fin-pill mr-2">
                                <span class="fin-label">Total Billed:</span>
                                <span class="fin-value text-primary">৳{{ number_format($totalBilled, 0) }}</span>
                            </span>
                            <div class="fin-sep"></div>
                            <span class="fin-pill mx-2">
                                <span class="fin-label">Paid:</span>
                                <span class="fin-value text-success">৳{{ number_format($paidToAdmin, 0) }}</span>
                            </span>
                            <div class="fin-sep"></div>
                            <span class="fin-pill mx-2">
                                <span class="fin-label">Due:</span>
                                <span class="fin-value {{ $dueToAdmin > 0 ? 'text-danger' : 'text-success' }}">৳{{ number_format($dueToAdmin, 0) }}</span>
                            </span>
                            @if($creditLimit > 0)
                            <div class="fin-sep"></div>
                            <span class="fin-pill mx-2" style="gap:6px;">
                                <span class="fin-label">Credit Limit:</span>
                                <span class="fin-value text-dark">৳{{ number_format($creditLimit, 0) }}</span>
                                <span style="font-size:9px;color:#aaa;">({{ $duePct }}%)</span>
                                <div style="width:50px;background:#e9ecef;border-radius:2px;height:3px;display:inline-block;vertical-align:middle;">
                                    <div style="width:{{ $duePct }}%;background:{{ $dueBarColor }};height:3px;border-radius:2px;"></div>
                                </div>
                            </span>
                            @endif
                            <div class="fin-sep"></div>
                            <span class="fin-pill mx-2">
                                <span class="fin-label">Wallet:</span>
                                <span class="fin-value {{ $walletBalance > 0 ? 'text-success' : 'text-muted' }}">৳{{ number_format($walletBalance, 2) }}</span>
                            </span>
                            @if($dueToAdmin > 0)
                            <div class="fin-sep"></div>
                            <a href="{{ route('admin.reseller.bill-payment', $reseller) }}" class="btn btn-danger btn-xs ml-2" style="font-size:10px;white-space:nowrap;">
                                <i class="fas fa-money-bill-wave mr-1"></i>Collect ৳{{ number_format($dueToAdmin, 0) }}
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>

                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-tachometer-alt fa-2x text-muted mb-2 d-block"></i>
                    No bandwidth resellers found
                </td></tr>
                @endforelse
            </tbody>

            {{-- Footer totals --}}
            @if($resellers->count() > 0)
            @php
                $footerTotalBilled = collect($resellerFinancials)->sum('total_recharge');
                $footerTotalDue    = collect($resellerFinancials)->sum('due_to_admin');
                $footerTotalPaid   = max(0, $footerTotalBilled - $footerTotalDue);
            @endphp
            <tfoot>
                <tr class="bg-dark text-white" style="font-size:12px;">
                    <td colspan="3" class="text-right py-2"><strong>Page Grand Total →</strong></td>
                    <td colspan="3" class="py-2">
                        <div class="d-flex align-items-center" style="gap:20px;">
                            <span><i class="fas fa-receipt mr-1"></i>Total Billed: <strong class="text-info">৳{{ number_format($footerTotalBilled, 0) }}</strong></span>
                            <span><i class="fas fa-check-circle mr-1"></i>Paid: <strong class="text-success">৳{{ number_format($footerTotalPaid, 0) }}</strong></span>
                            <span><i class="fas fa-exclamation-circle mr-1"></i>Due: <strong class="text-danger">৳{{ number_format($footerTotalDue, 0) }}</strong></span>
                        </div>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @if($resellers->hasPages())
    <div class="card-footer clearfix">{{ $resellers->links() }}</div>
    @endif
</div>

{{-- ============================================================ --}}
{{-- MAC RESELLER TABLE --}}
{{-- ============================================================ --}}
@else

<div class="card card-outline card-primary mb-0">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-store mr-2"></i>{{ $statusFilter === 'suspended' ? 'Terminated Resellers' : 'Active Resellers' }}</h3>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width:200px;">
                <input type="text" id="reseller-search" class="form-control" placeholder="Search...">
                <div class="input-group-append"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-sm mb-0" id="reseller-table">
            <thead class="bg-dark text-white">
                <tr>
                    <th style="width:90px;">MAC Reseller ID</th>
                    <th>Name / Phone</th>
                    <th>Zone / Clients</th>
                    <th class="text-center" style="width:70px;">Now</th>
                    <th class="text-center" style="width:115px;">Type / M.Day</th>
                    <th class="text-center" style="width:160px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resellers as $reseller)
                @php
                    $isSuspended = in_array($reseller->status, ['suspended', 'inactive']);
                    $fin = $resellerFinancials[$reseller->id] ?? ['total_recharge' => 0, 'due_to_admin' => 0];
                    $totalRecharge = (float) $fin['total_recharge'];
                    $dueToAdmin    = (float) $fin['due_to_admin'];
                    $creditLimit   = (float) ($reseller->credit_limit ?? 0);
                    $walletBalance = (float) ($reseller->wallet_balance ?? 0);
                    $paidToAdmin   = max(0, $totalRecharge - $dueToAdmin);
                    $duePct = $creditLimit > 0 ? min(100, round($dueToAdmin / $creditLimit * 100)) : 0;
                    $dueBarColor = $duePct >= 90 ? '#dc3545' : ($duePct >= 70 ? '#fd7e14' : '#28a745');
                @endphp

                {{-- Main info row --}}
                <tr class="reseller-main-row {{ $isSuspended ? 'table-secondary' : '' }}" data-reseller-id="{{ $reseller->id }}">
                    <td>
                        <a href="{{ route('admin.reseller.show', $reseller) }}" class="font-weight-bold text-primary">{{ $reseller->reseller_id }}</a>
                        @if($isSuspended)
                        <br><span class="badge badge-danger" style="font-size:9px;">Terminated</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.reseller.show', $reseller) }}"><strong>{{ $reseller->name }}</strong></a>
                        <br><small class="text-success"><i class="fas fa-phone-alt mr-1"></i>{{ $reseller->phone }}</small>
                        @if($reseller->mikrotikDevice)
                        <br><small class="text-muted" style="font-size:10px;"><i class="fas fa-server mr-1"></i>{{ $reseller->mikrotikDevice->name }} — {{ $reseller->mikrotikDevice->host }}</small>
                        @endif
                        @if($reseller->ppp_short_code)
                        <br><small class="text-info" style="font-size:10px;"><i class="fas fa-tag mr-1"></i>{{ $reseller->ppp_short_code }}</small>
                        @endif
                    </td>
                    <td>
                        <small class="text-muted d-block">{{ $reseller->area_name_en ?? $reseller->address ?? '—' }}</small>
                        <div class="mt-1">
                            <span class="badge badge-secondary" style="font-size:10px;" title="Total Clients">{{ $reseller->clients_count ?? 0 }}</span>
                            <span class="badge badge-success" style="font-size:10px;" title="Active Clients">{{ $reseller->active_clients_count ?? 0 }}</span>
                            <span class="badge badge-danger" style="font-size:10px;" title="Inactive Clients">{{ $reseller->inactive_clients_count ?? 0 }}</span>
                            <span class="badge badge-info" style="font-size:10px;" title="Package Count">PKG: {{ $reseller->packages_count ?? 0 }}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="text-success font-weight-bold" style="font-size:15px;">{{ $reseller->active_clients_count ?? 0 }}</span>
                        <span class="text-muted" style="font-size:12px;">/{{ $reseller->clients_count ?? 0 }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge px-2 py-1 mb-1" style="font-size:11px;background:#e67e22;color:#fff;display:block;" title="MAC Reseller — Package-based pricing">
                            <i class="fas fa-network-wired mr-1"></i>MAC
                        </span>
                        <span class="badge px-2 py-1 mb-1" style="font-size:11px;background:{{ $reseller->billing_type == 'prepaid' ? '#6f42c1' : '#28a745' }};color:#fff;display:block;">{{ strtoupper($reseller->billing_type ?? 'PREPAID') }}</span>
                        <span class="badge badge-secondary" style="font-size:10px;">
                            @if(($reseller->recharge_day_type ?? 'multi') === 'single' && $reseller->single_recharge_day)
                                {{ $reseller->single_recharge_day }} DAY
                            @else
                                {{ $reseller->minimum_recharge_days ?? 30 }} DAY
                            @endif
                        </span>
                        <span class="badge {{ $reseller->auto_recharge ? 'badge-success' : 'badge-danger' }}" style="font-size:10px;">{{ $reseller->auto_recharge ? 'AUTO' : 'MANUAL' }}</span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center flex-wrap" style="gap:2px;">
                            <a href="{{ route('admin.reseller.clients', ['reseller_id' => $reseller->id]) }}" class="btn btn-info btn-xs" title="Clients"><i class="fas fa-users"></i></a>
                            <a href="{{ route('admin.reseller.client-transactions', $reseller) }}" class="btn btn-primary btn-xs" title="Transactions"><i class="fas fa-exchange-alt"></i></a>
                            <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-secondary btn-xs" title="Bills"><i class="fas fa-file-invoice"></i></a>
                            <a href="{{ route('admin.reseller.current-mac-invoice', $reseller) }}" class="btn btn-primary btn-xs" title="Print Current Invoice" target="_blank"><i class="fas fa-print"></i></a>
                            <a href="{{ route('admin.reseller.bill-payment', $reseller) }}" class="btn btn-success btn-xs" title="Collect Payment"><i class="fas fa-money-bill-wave"></i></a>
                            <a href="{{ route('admin.reseller.packages', $reseller) }}" class="btn btn-dark btn-xs" title="Packages"><i class="fas fa-cubes"></i></a>
                            <a href="{{ route('admin.reseller.edit', $reseller) }}" class="btn btn-warning btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.reseller.toggle-recharge-enabled', $reseller) }}" method="POST" class="d-inline" title="{{ $reseller->recharge_enabled ?? true ? 'Instant Recharge ON — Click to disable' : 'Instant Recharge OFF — Click to enable' }}">
                                @csrf
                                <button type="submit" class="btn btn-xs {{ $reseller->recharge_enabled ?? true ? 'btn-success' : 'btn-danger' }}" style="font-weight:600;" onclick="return confirm('{{ $reseller->recharge_enabled ?? true ? 'Disable' : 'Enable' }} instant recharge for {{ addslashes($reseller->name) }}?')">
                                    <i class="fas fa-{{ $reseller->recharge_enabled ?? true ? 'bolt' : 'ban' }}"></i>
                                </button>
                            </form>
                            @if($reseller->user_id)
                            <form action="{{ route('admin.reseller.login-as', $reseller) }}" method="POST" class="d-inline" onsubmit="return confirm('Login as {{ addslashes($reseller->name) }}?')">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-xs" title="Login As"><i class="fas fa-sign-in-alt"></i></button>
                            </form>
                            @endif
                            @if($reseller->status === 'active')
                            <form action="{{ route('admin.reseller.terminate', $reseller) }}" method="POST" class="d-inline" onsubmit="return confirm('Terminate {{ addslashes($reseller->name) }}?')">
                                @csrf
                                <input type="hidden" name="termination_reason" value="Terminated from list">
                                <button type="submit" class="btn btn-outline-danger btn-xs" title="Terminate"><i class="fas fa-ban"></i></button>
                            </form>
                            @else
                            <form action="{{ route('admin.reseller.reactivate', $reseller) }}" method="POST" class="d-inline" onsubmit="return confirm('Reactivate {{ addslashes($reseller->name) }}?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-xs" title="Activate"><i class="fas fa-redo"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>

                {{-- Financial summary sub-row --}}
                <tr class="reseller-fin-row">
                    <td colspan="6">
                        <div class="d-flex align-items-center flex-wrap" style="gap:0;padding:3px 2px;">
                            <span class="fin-pill mr-2">
                                <span class="fin-label">Recharge:</span>
                                <span class="fin-value text-primary">৳{{ number_format($totalRecharge, 0) }}</span>
                            </span>
                            <div class="fin-sep"></div>
                            <span class="fin-pill mx-2">
                                <span class="fin-label">Paid:</span>
                                <span class="fin-value text-success">৳{{ number_format($paidToAdmin, 0) }}</span>
                            </span>
                            <div class="fin-sep"></div>
                            <span class="fin-pill mx-2">
                                <span class="fin-label">Due:</span>
                                <span class="fin-value {{ $dueToAdmin > 0 ? 'text-danger' : 'text-success' }}">৳{{ number_format($dueToAdmin, 0) }}</span>
                            </span>
                            @if($creditLimit > 0)
                            <div class="fin-sep"></div>
                            <span class="fin-pill mx-2" style="gap:6px;">
                                <span class="fin-label">Limit:</span>
                                <span class="fin-value text-dark">৳{{ number_format($creditLimit, 0) }}</span>
                                <span style="font-size:9px;color:#aaa;">({{ $duePct }}%)</span>
                                <div style="width:50px;background:#e9ecef;border-radius:2px;height:3px;display:inline-block;vertical-align:middle;">
                                    <div style="width:{{ $duePct }}%;background:{{ $dueBarColor }};height:3px;border-radius:2px;"></div>
                                </div>
                            </span>
                            @endif
                            <div class="fin-sep"></div>
                            <span class="fin-pill mx-2">
                                <span class="fin-label">Wallet:</span>
                                <span class="fin-value {{ $walletBalance > 0 ? 'text-success' : 'text-muted' }}">৳{{ number_format($walletBalance, 2) }}</span>
                            </span>
                            @if($dueToAdmin > 0)
                            <div class="fin-sep"></div>
                            <a href="{{ route('admin.reseller.bill-payment', $reseller) }}" class="btn btn-danger btn-xs ml-2" style="font-size:10px;white-space:nowrap;">
                                <i class="fas fa-money-bill-wave mr-1"></i>Collect ৳{{ number_format($dueToAdmin, 0) }}
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>

                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No resellers found</td></tr>
                @endforelse
            </tbody>

            {{-- Grand total footer --}}
            @if($resellers->count() > 0)
            @php
                $footerTotalRecharge = collect($resellerFinancials)->sum('total_recharge');
                $footerTotalDue      = collect($resellerFinancials)->sum('due_to_admin');
                $footerTotalPaid     = max(0, $footerTotalRecharge - $footerTotalDue);
            @endphp
            <tfoot>
                <tr class="bg-dark text-white" style="font-size:12px;">
                    <td colspan="2" class="text-right py-2"><strong>Page Grand Total →</strong></td>
                    <td colspan="4" class="py-2">
                        <div class="d-flex align-items-center" style="gap:20px;">
                            <span><i class="fas fa-receipt mr-1"></i>Total Recharge: <strong class="text-info">৳{{ number_format($footerTotalRecharge, 0) }}</strong></span>
                            <span><i class="fas fa-check-circle mr-1"></i>Paid to ISP: <strong class="text-success">৳{{ number_format($footerTotalPaid, 0) }}</strong></span>
                            <span><i class="fas fa-exclamation-circle mr-1"></i>Due (Admin): <strong class="text-danger">৳{{ number_format($footerTotalDue, 0) }}</strong></span>
                        </div>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @if($resellers->hasPages())
    <div class="card-footer clearfix">{{ $resellers->links() }}</div>
    @endif
</div>

@endif {{-- end @if bandwidth/mac --}}

@endsection

@push('scripts')
<script>
$(function(){
    {{-- MAC search --}}
    $('#reseller-search').on('keyup', function(){
        var val = $(this).val().toLowerCase();
        $('#reseller-table tbody tr.reseller-main-row').each(function(){
            var mainRow = $(this);
            var finRow  = mainRow.next('.reseller-fin-row');
            var matches = mainRow.text().toLowerCase().indexOf(val) > -1;
            mainRow.toggle(matches);
            finRow.toggle(matches);
        });
    });

    // BW grid expand/collapse toggle
    window.toggleBwGrid = function(id, btn){
        var g = document.getElementById('bwg-'+id);
        if(!g) return;
        var expanded = g.classList.toggle('expanded');
        btn.innerHTML = expanded
            ? '<i class="fas fa-eye-slash" style="font-size:9px;"></i> Hide'
            : '<i class="fas fa-eye" style="font-size:9px;"></i> Details';
    };

    {{-- BW search --}}
    $('#bw-search').on('keyup', function(){
        var val = $(this).val().toLowerCase();
        $('#bw-reseller-table tbody tr.bw-main-row').each(function(){
            var mainRow = $(this);
            var finRow  = mainRow.next('.bw-fin-row');
            var matches = (mainRow.data('name') + ' ' + mainRow.text()).toLowerCase().indexOf(val) > -1;
            mainRow.toggle(matches);
            finRow.toggle(matches);
        });
    });
});
</script>
@endpush
