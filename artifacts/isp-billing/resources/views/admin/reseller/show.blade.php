@extends('layouts.admin')

@section('title', $reseller->type === 'bandwidth' ? 'Bandwidth Reseller — '.$reseller->name : 'View MAC Reseller')
@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('admin.reseller.index', ['type' => $reseller->type]) }}">
        {{ $reseller->type === 'bandwidth' ? 'Bandwidth Resellers' : 'MAC Resellers' }}
    </a>
</li>
<li class="breadcrumb-item active">{{ $reseller->name }}</li>
@endsection

@push('styles')
<style>
.bw-cat-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:14px; font-size:12px; font-weight:600; margin:2px; }
.adj-timeline { border-left: 3px solid #dee2e6; padding-left: 16px; margin-left: 8px; }
.adj-timeline-item { position:relative; margin-bottom:14px; }
.adj-timeline-item::before { content:''; position:absolute; left:-22px; top:4px; width:10px; height:10px; border-radius:50%; background:#17a2b8; border:2px solid #fff; box-shadow:0 0 0 2px #17a2b8; }
</style>
@endpush

@section('content')
<div class="row">
    {{-- ========== LEFT: Profile Card ========== --}}
    <div class="col-md-4">
        <div class="card card-{{ $reseller->type === 'bandwidth' ? 'info' : 'primary' }} card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                         style="width:80px;height:80px;background:linear-gradient(135deg,{{ $reseller->type === 'bandwidth' ? '#17a2b8,#0d6efd' : '#1e3c72,#2a5298' }});color:#fff;font-weight:700;font-size:28px;">
                        {{ strtoupper(substr($reseller->name, 0, 1)) }}
                    </div>
                </div>
                <h3 class="profile-username text-center mt-3">{{ $reseller->name }}</h3>
                <p class="text-muted text-center">
                    @if($reseller->type === 'bandwidth')
                        <span class="badge badge-info px-2"><i class="fas fa-tachometer-alt mr-1"></i>Bandwidth Reseller</span>
                    @else
                        <span class="badge badge-warning px-2"><i class="fas fa-network-wired mr-1"></i>MAC Reseller</span>
                    @endif
                </p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Reseller ID</b>
                        <span class="float-right font-weight-bold">{{ $reseller->reseller_id }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Status</b>
                        <span class="float-right badge badge-{{ $reseller->status === 'active' ? 'success' : 'danger' }}">
                            {{ ucfirst($reseller->status) }}
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Phone</b>
                        <span class="float-right">{{ $reseller->phone }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Wallet Balance</b>
                        <span class="float-right text-success font-weight-bold">৳{{ number_format($reseller->wallet_balance ?? 0, 2) }}</span>
                    </li>
                    @if($reseller->type === 'bandwidth')
                    <li class="list-group-item">
                        <b>Monthly Rate</b>
                        <span class="float-right text-dark font-weight-bold">৳{{ number_format($reseller->bandwidth_monthly_rate ?? 0, 0) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Due to ISP</b>
                        <span class="float-right text-danger font-weight-bold">৳{{ number_format(App\Services\ResellerBillingService::getResellerDueToAdmin($reseller), 0) }}</span>
                    </li>
                    @else
                    <li class="list-group-item">
                        <b>Total Clients</b>
                        <span class="float-right">{{ $reseller->clients()->count() }}</span>
                    </li>
                    @endif
                </ul>

                @if($reseller->type === 'bandwidth')
                <div class="btn-group w-100 mb-2">
                    <a href="{{ route('admin.reseller.edit', $reseller) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    <a href="{{ route('admin.reseller.bandwidth', $reseller) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-tachometer-alt mr-1"></i>Bandwidth
                    </a>
                    <a href="{{ route('admin.reseller.bill-payment', $reseller) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-money-bill-wave mr-1"></i>Payment
                    </a>
                </div>
                <div class="btn-group w-100 mb-2">
                    <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-file-invoice mr-1"></i>Bill History
                    </a>
                    <a href="{{ route('admin.reseller.generate-bill', $reseller) }}" class="btn btn-dark btn-sm">
                        <i class="fas fa-file-alt mr-1"></i>Generate Bill
                    </a>
                </div>
                @else
                <div class="btn-group w-100 mb-2">
                    <a href="{{ route('admin.reseller.edit', $reseller) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('admin.reseller.wallet', $reseller) }}" class="btn btn-success">
                        <i class="fas fa-wallet"></i> Wallet
                    </a>
                    <a href="{{ route('admin.reseller.clients', $reseller) }}" class="btn btn-info">
                        <i class="fas fa-users"></i> Clients
                    </a>
                </div>
                <a href="{{ route('admin.reseller.packages', $reseller) }}" class="btn btn-dark btn-block">
                    <i class="fas fa-cubes"></i> Manage Packages & Pricing
                </a>
                @endif
            </div>
        </div>

        {{-- Bandwidth Category Allocation (left sidebar for BW) --}}
        @if($reseller->type === 'bandwidth')
        <div class="card card-outline card-warning">
            <div class="card-header py-2">
                <h3 class="card-title" style="font-size:13px;"><i class="fas fa-layer-group mr-1"></i>Current Allocation</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.reseller.edit', $reseller) }}" class="btn btn-xs btn-warning">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @php
                $bwCats = $reseller->getBandwidthCategories();
                $catColors = ['intl'=>'#6c757d','ggc'=>'#17a2b8','fna'=>'#007bff','cdn'=>'#6f42c1','bdix'=>'#28a745','cache'=>'#fd7e14'];
                @endphp
                @if($bwCats)
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th style="font-size:10px;">Category</th>
                            <th class="text-right" style="font-size:10px;">Mbps</th>
                            <th class="text-right" style="font-size:10px;">৳/Mbps</th>
                            <th class="text-right" style="font-size:10px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bwCats as $key => $cat)
                        @if($cat['mbps'] > 0)
                        <tr>
                            <td style="font-size:11px;">
                                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $catColors[$key] ?? '#888' }};margin-right:4px;"></span>
                                {{ $cat['label'] }}
                            </td>
                            <td class="text-right font-weight-bold" style="font-size:11px;">{{ number_format($cat['mbps'], 0) }}</td>
                            <td class="text-right text-muted" style="font-size:11px;">{{ number_format($cat['rate'], 0) }}</td>
                            <td class="text-right font-weight-bold text-warning" style="font-size:11px;">৳{{ number_format($cat['subtotal'], 0) }}</td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-warning">
                            <th colspan="3" class="text-right" style="font-size:11px;">Monthly Total</th>
                            <th class="text-right text-dark" style="font-size:12px;">৳{{ number_format($reseller->bandwidth_monthly_rate ?? 0, 0) }}</th>
                        </tr>
                    </tfoot>
                </table>
                @else
                <p class="text-muted text-center py-3 small">No allocation set. <a href="{{ route('admin.reseller.edit', $reseller) }}">Edit to add.</a></p>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- ========== RIGHT: Details + Charts ========== --}}
    <div class="col-md-8">
        {{-- Basic Details Card --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    {{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller Details' : 'MAC Reseller Details' }}
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th style="width:40%;">Name:</th><td>{{ $reseller->name }}</td></tr>
                            <tr><th>Company:</th><td>{{ $reseller->company_name ?? '-' }}</td></tr>
                            <tr><th>Email:</th><td>{{ $reseller->email }}</td></tr>
                            <tr><th>Phone:</th><td>{{ $reseller->phone }}</td></tr>
                            <tr><th>NID:</th><td>{{ $reseller->nid ?? '-' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th style="width:40%;">Type:</th><td><span class="badge badge-{{ $reseller->type === 'bandwidth' ? 'info' : 'warning' }}">{{ ucfirst($reseller->type) }}</span></td></tr>
                            <tr><th>Billing:</th><td><span class="badge badge-secondary">{{ ucfirst($reseller->billing_type ?? 'Prepaid') }}</span></td></tr>
                            <tr><th>Credit Limit:</th><td>৳{{ number_format($reseller->credit_limit ?? 0, 2) }}</td></tr>
                            <tr><th>Auto Recharge:</th><td><span class="badge badge-{{ $reseller->auto_recharge ? 'success' : 'danger' }}">{{ $reseller->auto_recharge ? 'Yes' : 'No' }}</span></td></tr>
                            <tr><th>Joined:</th><td>{{ $reseller->created_at ? $reseller->created_at->format('d-m-Y') : '-' }}</td></tr>
                        </table>
                    </div>
                </div>
                @if($reseller->address)
                <hr class="mt-0">
                <strong><i class="fas fa-map-marker-alt mr-1"></i> Address:</strong>
                <p class="text-muted mb-0">{{ $reseller->address }}</p>
                @endif
            </div>
        </div>

        {{-- ===== BANDWIDTH RESELLER SECTION ===== --}}
        @if($reseller->type === 'bandwidth')

        {{-- Summary boxes --}}
        <div class="row">
            <div class="col-md-4">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>৳{{ number_format($reseller->bandwidth_monthly_rate ?? 0, 0) }}</h3>
                        <p>Monthly Rate</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>৳{{ number_format($reseller->wallet_balance ?? 0, 0) }}</h3>
                        <p>Wallet Balance</p>
                    </div>
                    <div class="icon"><i class="fas fa-wallet"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>৳{{ number_format(App\Services\ResellerBillingService::getResellerDueToAdmin($reseller), 0) }}</h3>
                        <p>Due to ISP</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                </div>
            </div>
        </div>

        {{-- ===== BANDWIDTH USAGE GRAPH ===== --}}
        @if($bwBillHistory->count() > 0)
        @php
            $chartLabels = [];
            $chartCategories = [
                'intl'  => ['label' => 'IIG (International)', 'color' => 'rgba(108,117,125,0.85)', 'mbps' => [], 'amount' => []],
                'ggc'   => ['label' => 'GGC',                 'color' => 'rgba(23,162,184,0.85)',  'mbps' => [], 'amount' => []],
                'fna'   => ['label' => 'FNA',                 'color' => 'rgba(0,123,255,0.85)',   'mbps' => [], 'amount' => []],
                'cdn'   => ['label' => 'CDN',                 'color' => 'rgba(111,66,193,0.85)',  'mbps' => [], 'amount' => []],
                'bdix'  => ['label' => 'BDIX',                'color' => 'rgba(40,167,69,0.85)',   'mbps' => [], 'amount' => []],
                'cache' => ['label' => 'Cache',               'color' => 'rgba(253,126,20,0.85)',  'mbps' => [], 'amount' => []],
            ];
            $chartBilling = []; // total monthly billing
            $chartPaid    = []; // total monthly paid

            foreach ($bwBillHistory as $bill) {
                $monthLabel = \Carbon\Carbon::createFromDate($bill->bill_year, $bill->bill_month, 1)->format('M Y');
                $chartLabels[] = $monthLabel;
                $chartBilling[] = (float) $bill->total_recharge;
                $chartPaid[]    = (float) $bill->total_paid;

                $breakdown = is_string($bill->bandwidth_breakdown)
                    ? json_decode($bill->bandwidth_breakdown, true)
                    : $bill->bandwidth_breakdown;

                foreach (array_keys($chartCategories) as $catKey) {
                    $chartCategories[$catKey]['mbps'][]   = isset($breakdown[$catKey]) ? (float)($breakdown[$catKey]['mbps'] ?? 0) : 0;
                    $chartCategories[$catKey]['amount'][] = isset($breakdown[$catKey]) ? (float)($breakdown[$catKey]['subtotal'] ?? 0) : 0;
                }
            }
        @endphp

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2 text-info"></i>Monthly Bandwidth Bill — Per Category</h3>
                <div class="card-tools">
                    <div class="btn-group btn-group-sm" id="chartTypeToggle">
                        <button class="btn btn-info active" data-chart="amount">৳ Amount</button>
                        <button class="btn btn-outline-info" data-chart="mbps">Mbps</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="bwCategoryChart" height="90"></canvas>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2 text-primary"></i>Monthly Billing Overview</h3>
            </div>
            <div class="card-body">
                <canvas id="bwBillingChart" height="70"></canvas>
            </div>
        </div>
        @endif

        {{-- ===== BANDWIDTH ADJUSTMENT HISTORY ===== --}}
        @if($bwAdjustments->count() > 0)
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2 text-warning"></i>Bandwidth Change History</h3>
                <div class="card-tools">
                    <span class="badge badge-warning">{{ $bwAdjustments->count() }} changes</span>
                </div>
            </div>
            <div class="card-body pb-2">
                <div class="adj-timeline">
                    @php
                    $catLabels = ['intl'=>'IIG','ggc'=>'GGC','fna'=>'FNA','cdn'=>'CDN','bdix'=>'BDIX','cache'=>'Cache'];
                    $catColors = ['intl'=>'secondary','ggc'=>'info','fna'=>'primary','cdn'=>'purple','bdix'=>'success','cache'=>'warning'];
                    @endphp
                    @foreach($bwAdjustments as $adj)
                    @php
                        $catKey = $adj->category_key;
                        $isCustom = str_starts_with($catKey, 'custom_');
                        $catLabel = $isCustom ? ucfirst(str_replace('custom_', '', $catKey)) : ($catLabels[$catKey] ?? strtoupper($catKey));
                        $badgeColor = $isCustom ? 'dark' : ($catColors[$catKey] ?? 'secondary');
                        $diff = (float)$adj->new_mbps - (float)$adj->old_mbps;
                        $arrow = $diff > 0 ? '▲' : ($diff < 0 ? '▼' : '→');
                        $arrowColor = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                    @endphp
                    <div class="adj-timeline-item">
                        <div class="d-flex align-items-center flex-wrap" style="gap:6px;">
                            <span class="badge badge-{{ $badgeColor }} px-2" style="font-size:11px;">{{ $catLabel }}</span>
                            <strong style="font-size:13px;">{{ number_format($adj->old_mbps, 0) }} → {{ number_format($adj->new_mbps, 0) }} Mbps</strong>
                            <span class="{{ $arrowColor }} font-weight-bold">{{ $arrow }} {{ number_format(abs($diff), 0) }} Mbps</span>
                            @if($adj->rate)
                            <span class="text-muted" style="font-size:11px;">@ ৳{{ number_format($adj->rate, 0) }}/Mbps</span>
                            @endif
                            <small class="text-muted ml-auto"><i class="fas fa-calendar-alt mr-1"></i>{{ \Carbon\Carbon::parse($adj->effective_date)->format('d M Y') }}</small>
                        </div>
                        @if($adj->reason)
                        <small class="text-muted d-block mt-1"><i class="fas fa-comment-alt mr-1"></i>{{ $adj->reason }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ===== MAC RESELLER SECTION ===== --}}
        @else
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Quick Stats</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $reseller->clients()->where('status', 'active')->count() }}</h3>
                                <p>Active Clients</p>
                            </div>
                            <div class="icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>৳{{ number_format($reseller->wallet_balance ?? 0, 0) }}</h3>
                                <p>Wallet Balance</p>
                            </div>
                            <div class="icon"><i class="fas fa-wallet"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $reseller->clients()->where('status', 'inactive')->count() }}</h3>
                                <p>Inactive Clients</p>
                            </div>
                            <div class="icon"><i class="fas fa-user-slash"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="small-box bg-maroon">
                            <div class="inner">
                                <h3>৳{{ number_format(App\Services\ResellerBillingService::getResellerDueToAdmin($reseller), 0) }}</h3>
                                <p>Due to ISP</p>
                            </div>
                            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="mb-3">
    <a href="{{ route('admin.reseller.index', ['type' => $reseller->type]) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $reseller->type === 'bandwidth' ? 'Bandwidth' : 'MAC' }} Resellers
    </a>
</div>

@endsection

@if($reseller->type === 'bandwidth' && isset($bwBillHistory) && $bwBillHistory->count() > 0)
@push('scripts')
<script>
(function() {
    var labels   = @json($chartLabels);
    var billing  = @json($chartBilling);
    var paid     = @json($chartPaid);

    var categories = {
        @foreach($chartCategories as $key => $cat)
        '{{ $key }}': {
            label:  '{{ $cat['label'] }}',
            color:  '{{ $cat['color'] }}',
            mbps:   @json($cat['mbps']),
            amount: @json($cat['amount']),
        },
        @endforeach
    };

    // ---- Stacked Bar Chart (per-category amount or mbps) ----
    var ctxCat  = document.getElementById('bwCategoryChart').getContext('2d');
    var catDatasets = Object.values(categories).map(function(cat) {
        return {
            label: cat.label,
            backgroundColor: cat.color,
            data: cat.amount,
            mbpsData: cat.mbps,
            borderWidth: 0,
            borderRadius: 2,
        };
    });

    var bwCatChart = new Chart(ctxCat, {
        type: 'bar',
        data: { labels: labels, datasets: catDatasets },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': ৳' + ctx.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    ticks: {
                        callback: function(v) { return '৳' + v.toLocaleString(); }
                    }
                }
            }
        }
    });

    // Toggle between amount and Mbps
    document.getElementById('chartTypeToggle').addEventListener('click', function(e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        var mode = btn.dataset.chart;
        document.querySelectorAll('#chartTypeToggle button').forEach(function(b) {
            b.classList.remove('active', 'btn-info');
            b.classList.add('btn-outline-info');
        });
        btn.classList.remove('btn-outline-info');
        btn.classList.add('active', 'btn-info');

        var datasets = bwCatChart.data.datasets;
        if (mode === 'amount') {
            datasets.forEach(function(ds, i) {
                ds.data = Object.values(categories)[i].amount;
            });
            bwCatChart.options.scales.y.ticks.callback = function(v) { return '৳' + v.toLocaleString(); };
            bwCatChart.options.plugins.tooltip.callbacks.label = function(ctx) {
                return ctx.dataset.label + ': ৳' + ctx.parsed.y.toLocaleString();
            };
        } else {
            datasets.forEach(function(ds, i) {
                ds.data = Object.values(categories)[i].mbps;
            });
            bwCatChart.options.scales.y.ticks.callback = function(v) { return v + ' Mbps'; };
            bwCatChart.options.plugins.tooltip.callbacks.label = function(ctx) {
                return ctx.dataset.label + ': ' + ctx.parsed.y + ' Mbps';
            };
        }
        bwCatChart.update();
    });

    // ---- Line Chart (billing overview) ----
    var ctxBill = document.getElementById('bwBillingChart').getContext('2d');
    new Chart(ctxBill, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Billed',
                    data: billing,
                    borderColor: '#fd7e14',
                    backgroundColor: 'rgba(253,126,20,0.08)',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 4,
                    fill: true,
                },
                {
                    label: 'Paid',
                    data: paid,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,0.08)',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 4,
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': ৳' + ctx.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(v) { return '৳' + v.toLocaleString(); }
                    }
                }
            }
        }
    });
})();
</script>
@endpush
@endif
