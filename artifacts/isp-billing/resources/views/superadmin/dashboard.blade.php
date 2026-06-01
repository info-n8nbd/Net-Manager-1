@extends('layouts.admin')

@section('title', 'Super Admin Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Super Admin Dashboard</li>
@endsection

@section('content')

{{-- ── Stat Cards Row ─────────────────────────────────────────────────────── --}}
<div class="row">

    {{-- Total Companies --}}
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);color:#fff;">
            <div class="inner">
                <h3>{{ $totalCompanies }}</h3>
                <p>Total Companies</p>
                <small style="opacity:.8;">
                    @if($newCompaniesThisMonth > 0)
                        <i class="fas fa-plus-circle mr-1"></i>{{ $newCompaniesThisMonth }} this month
                    @else
                        <i class="fas fa-building mr-1"></i>{{ $inactiveCompanies }} inactive
                    @endif
                </small>
            </div>
            <div class="icon"><i class="fas fa-building"></i></div>
            <a href="{{ route('superadmin.companies.index') }}" class="small-box-footer" style="background:rgba(0,0,0,.15);">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    {{-- Active Companies --}}
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,#1a7a4a 0%,#27ae60 100%);color:#fff;">
            <div class="inner">
                <h3>{{ $activeCompanies }}</h3>
                <p>Active Companies</p>
                <small style="opacity:.8;">
                    <i class="fas fa-chart-pie mr-1"></i>
                    {{ $totalCompanies > 0 ? round($activeCompanies / $totalCompanies * 100) : 0 }}% of total
                </small>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <a href="{{ route('superadmin.companies.index') }}" class="small-box-footer" style="background:rgba(0,0,0,.15);">
                Manage <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    {{-- Total Clients --}}
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,#0a5275 0%,#117a8b 100%);color:#fff;">
            <div class="inner">
                <h3>{{ $totalClients }}</h3>
                <p>Total Clients</p>
                <small style="opacity:.8;">
                    <i class="fas fa-user-check mr-1"></i>{{ $activeClients }} active &nbsp;
                    <i class="fas fa-user-xmark mr-1"></i>{{ $inactiveClients }} inactive
                </small>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="#" class="small-box-footer" style="background:rgba(0,0,0,.15);">
                All Companies <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    {{-- Online Right Now --}}
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,#4a148c 0%,#7b1fa2 100%);color:#fff;">
            <div class="inner">
                <h3>{{ $onlineClients }}</h3>
                <p>Online Right Now</p>
                <small style="opacity:.8;">
                    <i class="fas fa-wifi mr-1"></i>
                    {{ $activeClients > 0 ? round($onlineClients / $activeClients * 100) : 0 }}% of active clients
                </small>
            </div>
            <div class="icon"><i class="fas fa-wifi"></i></div>
            <a href="#" class="small-box-footer" style="background:rgba(0,0,0,.15);">
                Live Status <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    {{-- Monthly Revenue --}}
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,#b7410e 0%,#e67e22 100%);color:#fff;">
            <div class="inner">
                <h3>{{ number_format($monthlyRevenue, 0) }}</h3>
                <p>{{ now()->format('M Y') }} Revenue</p>
                <small style="opacity:.8;">
                    <i class="fas fa-receipt mr-1"></i>{{ $totalPaymentsThisMonth }} payments
                </small>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="#" class="small-box-footer" style="background:rgba(0,0,0,.15);">
                This Month <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    {{-- All-Time Revenue --}}
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,#1a252f 0%,#2c3e50 100%);color:#fff;">
            <div class="inner">
                <h3>{{ number_format($totalAllTimeRevenue, 0) }}</h3>
                <p>All-Time Revenue</p>
                <small style="opacity:.8;">
                    <i class="fas fa-landmark mr-1"></i>Platform total
                </small>
            </div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
            <a href="#" class="small-box-footer" style="background:rgba(0,0,0,.15);">
                Platform Totals <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

</div>{{-- /row --}}

{{-- ── Subscription Alert Banners ─────────────────────────────────────────── --}}
@if($expiredCompanies > 0)
<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-left:5px solid #c0392b;border-radius:6px;">
    <i class="fas fa-exclamation-circle mr-2"></i>
    <strong>{{ $expiredCompanies }} {{ Str::plural('company', $expiredCompanies) }}</strong>
    {{ $expiredCompanies === 1 ? 'has' : 'have' }} an expired subscription. Immediate renewal required.
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif
@if($expiringCompanies > 0)
<div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert" style="border-left:5px solid #f39c12;border-radius:6px;">
    <i class="fas fa-clock mr-2"></i>
    <strong>{{ $expiringCompanies }} {{ Str::plural('company', $expiringCompanies) }}</strong>
    will expire within the next 7 days. Please renew soon.
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

{{-- ── Subscription Expiry Tables ─────────────────────────────────────────── --}}
@if($expiredList->count() > 0 || $expiring7List->count() > 0 || $expiring30List->count() > 0)
<div class="row">
    @if($expiredList->count() > 0)
    <div class="col-md-{{ ($expiring7List->count() > 0 || $expiring30List->count() > 0) ? 6 : 12 }}">
        <div class="card card-danger card-outline shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-exclamation-circle mr-2 text-danger"></i>Expired Subscriptions <span class="badge badge-danger ml-1">{{ $expiredList->count() }}</span></h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light"><tr><th>Company</th><th>Expired On</th><th>Days Ago</th><th></th></tr></thead>
                    <tbody>
                        @foreach($expiredList as $c)
                        <tr>
                            <td><strong>{{ $c->name }}</strong><br><small class="text-muted">{{ $c->email }}</small></td>
                            <td><span class="text-danger">{{ $c->subscription_end->format('d M Y') }}</span></td>
                            <td><span class="badge badge-danger">{{ $c->subscription_end->diffInDays(now()) }}d ago</span></td>
                            <td><a href="{{ route('superadmin.companies.edit', $c) }}" class="btn btn-xs btn-danger"><i class="fas fa-sync-alt"></i> Renew</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @if($expiring7List->count() > 0 || $expiring30List->count() > 0)
    <div class="col-md-{{ $expiredList->count() > 0 ? 6 : 12 }}">
        <div class="card card-warning card-outline shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-clock mr-2 text-warning"></i>Expiring Soon <span class="badge badge-warning ml-1">{{ $expiring7List->count() + $expiring30List->count() }}</span></h3>
                <div class="card-tools">
                    <span class="badge badge-danger mr-1"><i class="fas fa-fire mr-1"></i>{{ $expiring7List->count() }} within 7d</span>
                    <span class="badge badge-warning"><i class="fas fa-calendar mr-1"></i>{{ $expiring30List->count() }} within 30d</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light"><tr><th>Company</th><th>Expires On</th><th>Days Left</th><th></th></tr></thead>
                    <tbody>
                        @foreach($expiring7List as $c)
                        <tr class="table-danger">
                            <td><strong>{{ $c->name }}</strong><br><small class="text-muted">{{ $c->email }}</small></td>
                            <td><span class="text-danger font-weight-bold">{{ $c->subscription_end->format('d M Y') }}</span></td>
                            <td>@php $d = now()->diffInDays($c->subscription_end, false); @endphp<span class="badge badge-danger">{{ $d === 0 ? 'Today!' : $d.'d left' }}</span></td>
                            <td><a href="{{ route('superadmin.companies.edit', $c) }}" class="btn btn-xs btn-warning"><i class="fas fa-sync-alt"></i> Renew</a></td>
                        </tr>
                        @endforeach
                        @foreach($expiring30List as $c)
                        <tr>
                            <td><strong>{{ $c->name }}</strong><br><small class="text-muted">{{ $c->email }}</small></td>
                            <td>{{ $c->subscription_end->format('d M Y') }}</td>
                            <td><span class="badge badge-warning">{{ now()->diffInDays($c->subscription_end, false) }}d left</span></td>
                            <td><a href="{{ route('superadmin.companies.edit', $c) }}" class="btn btn-xs btn-outline-warning"><i class="fas fa-sync-alt"></i> Renew</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ── Main Content Row ─────────────────────────────────────────────────────── --}}
<div class="row">

    {{-- Left: Chart + Company Health Table --}}
    <div class="col-md-8">

        {{-- Revenue Bar Chart --}}
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>6-Month Revenue Overview</h3>
                <div class="card-tools">
                    <span class="badge badge-primary">৳ {{ number_format($totalAllTimeRevenue, 0) }} all-time</span>
                </div>
            </div>
            <div class="card-body" style="padding:12px 16px;">
                <canvas id="revenueChart" height="90"></canvas>
            </div>
        </div>

        {{-- Company Health Table --}}
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-building mr-2"></i>Company Health Overview</h3>
                <div class="card-tools">
                    <a href="{{ route('superadmin.companies.create') }}" class="btn btn-xs btn-primary">
                        <i class="fas fa-plus mr-1"></i>Add Company
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" style="font-size:0.88rem;">
                    <thead style="background:#f4f6f9;">
                        <tr>
                            <th class="pl-3">Company</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Admins</th>
                            <th class="text-center">Clients</th>
                            <th class="text-center">Online</th>
                            <th class="text-right">This Month</th>
                            <th class="text-right pr-3">All-Time</th>
                            <th class="text-center">Subscription</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companiesDetailed as $company)
                        <tr>
                            <td class="pl-3">
                                <a href="{{ route('superadmin.companies.show', $company) }}" class="font-weight-bold text-dark">
                                    {{ $company->name }}
                                </a>
                                <br><small class="text-muted">{{ $company->email }}</small>
                            </td>
                            <td class="text-center">
                                @if($company->is_active)
                                    <span class="badge badge-success"><i class="fas fa-circle mr-1" style="font-size:.5rem;vertical-align:middle;"></i>Active</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-circle mr-1" style="font-size:.5rem;vertical-align:middle;"></i>Inactive</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge badge-light border">{{ $company->users_count }}</span></td>
                            <td class="text-center">
                                <span class="font-weight-bold">{{ $company->active_clients_count }}</span>
                                @if($company->total_clients_count > $company->active_clients_count)
                                <span class="text-muted" style="font-size:.8rem;">/ {{ $company->total_clients_count }}</span>
                                @endif
                                @if($company->max_clients > 0)
                                <br>
                                @php $pct = $company->max_clients > 0 ? min(100, round($company->active_clients_count / $company->max_clients * 100)) : 0; @endphp
                                <div class="progress mt-1" style="height:4px;width:60px;margin:0 auto;">
                                    <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') }}" style="width:{{ $pct }}%"></div>
                                </div>
                                <small class="text-muted" style="font-size:.7rem;">{{ $pct }}% of {{ $company->max_clients }} max</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($company->online_clients_count > 0)
                                    <span class="badge badge-success">
                                        <i class="fas fa-circle mr-1" style="font-size:.45rem;vertical-align:middle;"></i>{{ $company->online_clients_count }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:.8rem;">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($company->monthly_revenue > 0)
                                    <span class="font-weight-bold text-success">৳ {{ number_format($company->monthly_revenue, 0) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-right pr-3">
                                @if($company->total_revenue > 0)
                                    <span class="text-dark">৳ {{ number_format($company->total_revenue, 0) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$company->subscription_end)
                                    <span class="badge badge-secondary"><i class="fas fa-infinity mr-1"></i>Unlimited</span>
                                @elseif($company->subscription_end->isPast())
                                    <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Expired</span>
                                @elseif($company->subscription_end->lte(now()->addDays(7)))
                                    <span class="badge badge-danger"><i class="fas fa-fire mr-1"></i>{{ now()->diffInDays($company->subscription_end) }}d</span>
                                @elseif($company->subscription_end->lte(now()->addDays(30)))
                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>{{ now()->diffInDays($company->subscription_end) }}d</span>
                                @else
                                    <span class="badge badge-success" style="font-size:.72rem;">{{ $company->subscription_end->format('d M Y') }}</span>
                                @endif
                            </td>
                            <td class="text-center pr-2">
                                <a href="{{ route('superadmin.companies.show', $company) }}" class="btn btn-xs btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('superadmin.companies.edit', $company) }}" class="btn btn-xs btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No companies found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Payments --}}
        @if($recentPayments->count() > 0)
        <div class="card card-success card-outline shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-receipt mr-2"></i>Recent Payments (Platform-wide)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:.87rem;">
                    <thead style="background:#f4f6f9;">
                        <tr>
                            <th class="pl-3">Payment #</th>
                            <th>Client</th>
                            <th>Company</th>
                            <th class="text-right">Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPayments as $pay)
                        <tr>
                            <td class="pl-3"><code style="font-size:.8rem;">{{ $pay->payment_no }}</code></td>
                            <td>{{ $pay->client->name ?? '—' }}</td>
                            <td><small class="text-muted">{{ $pay->client->company->name ?? '—' }}</small></td>
                            <td class="text-right font-weight-bold text-success">
                                {{ $pay->client->company->currency_symbol ?? '৳' }} {{ number_format($pay->amount, 0) }}
                            </td>
                            <td><small>{{ $pay->payment_date instanceof \Carbon\Carbon ? $pay->payment_date->format('d M Y') : \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>{{-- /col-md-8 --}}

    {{-- Right: Actions + Platform Stats --}}
    <div class="col-md-4">

        {{-- Quick Actions --}}
        <div class="card card-outline shadow-sm" style="border-top:3px solid #1e3c72;">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-bolt mr-2" style="color:#1e3c72;"></i>Quick Actions</h3>
            </div>
            <div class="card-body pb-2">
                <a href="{{ route('superadmin.companies.create') }}" class="btn btn-primary btn-block mb-2">
                    <i class="fas fa-plus mr-2"></i>Add New Company
                </a>
                <a href="{{ route('superadmin.companies.index') }}" class="btn btn-outline-secondary btn-block mb-2">
                    <i class="fas fa-list mr-2"></i>Manage Companies
                </a>
            </div>
        </div>

        {{-- Platform Stats --}}
        <div class="card card-outline shadow-sm" style="border-top:3px solid #27ae60;">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2" style="color:#27ae60;"></i>Platform Overview</h3>
            </div>
            <div class="card-body pt-2 pb-1">

                {{-- Companies --}}
                <div class="d-flex justify-content-between align-items-center mb-1 mt-1">
                    <span class="text-muted" style="font-size:.85rem;">Active Companies</span>
                    <strong>{{ $activeCompanies }} / {{ $totalCompanies }}</strong>
                </div>
                <div class="progress mb-3" style="height:6px;border-radius:3px;">
                    <div class="progress-bar bg-success" style="width:{{ $totalCompanies > 0 ? ($activeCompanies / $totalCompanies * 100) : 0 }}%;border-radius:3px;"></div>
                </div>

                {{-- Clients --}}
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted" style="font-size:.85rem;">Active Clients</span>
                    <strong>{{ $activeClients }} / {{ $totalClients }}</strong>
                </div>
                <div class="progress mb-3" style="height:6px;border-radius:3px;">
                    <div class="progress-bar bg-info" style="width:{{ $totalClients > 0 ? round($activeClients / $totalClients * 100) : 0 }}%;border-radius:3px;"></div>
                </div>

                {{-- Online --}}
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted" style="font-size:.85rem;">Online Now</span>
                    <strong>{{ $onlineClients }} / {{ $activeClients }}</strong>
                </div>
                <div class="progress mb-3" style="height:6px;border-radius:3px;">
                    <div class="progress-bar bg-purple" style="width:{{ $activeClients > 0 ? round($onlineClients / $activeClients * 100) : 0 }}%;border-radius:3px;background:#7b1fa2;"></div>
                </div>

                <hr class="my-2">

                <div class="row text-center mb-2">
                    <div class="col-4">
                        <div style="font-size:1.3rem;font-weight:700;color:#1e3c72;">{{ $totalCompanies }}</div>
                        <div style="font-size:.72rem;color:#6c757d;line-height:1.2;">Companies</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:1.3rem;font-weight:700;color:#27ae60;">{{ $activeClients }}</div>
                        <div style="font-size:.72rem;color:#6c757d;line-height:1.2;">Active Clients</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:1.3rem;font-weight:700;color:#e67e22;">৳{{ number_format($monthlyRevenue/1000, 1) }}K</div>
                        <div style="font-size:.72rem;color:#6c757d;line-height:1.2;">This Month</div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Subscription Health --}}
        <div class="card card-outline shadow-sm" style="border-top:3px solid #e67e22;">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-calendar-check mr-2" style="color:#e67e22;"></i>Subscription Health</h3>
            </div>
            <div class="card-body pt-2 pb-2">
                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f0f0f0;">
                    <div>
                        <i class="fas fa-times-circle text-danger mr-2"></i>
                        <span style="font-size:.88rem;">Expired</span>
                    </div>
                    <span class="badge badge-{{ $expiredCompanies > 0 ? 'danger' : 'light border' }} px-3" style="font-size:.85rem;">
                        {{ $expiredCompanies }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f0f0f0;">
                    <div>
                        <i class="fas fa-fire text-danger mr-2"></i>
                        <span style="font-size:.88rem;">Expiring (7 days)</span>
                    </div>
                    <span class="badge badge-{{ $expiringCompanies > 0 ? 'danger' : 'light border' }} px-3" style="font-size:.85rem;">
                        {{ $expiringCompanies }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f0f0f0;">
                    <div>
                        <i class="fas fa-clock text-warning mr-2"></i>
                        <span style="font-size:.88rem;">Expiring (30 days)</span>
                    </div>
                    <span class="badge badge-{{ $expiring30List->count() > 0 ? 'warning' : 'light border' }} px-3" style="font-size:.85rem;">
                        {{ $expiring30List->count() }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <div>
                        <i class="fas fa-infinity text-secondary mr-2"></i>
                        <span style="font-size:.88rem;">Unlimited</span>
                    </div>
                    <span class="badge badge-secondary px-3" style="font-size:.85rem;">
                        {{ $companiesDetailed->whereNull('subscription_end')->count() }}
                    </span>
                </div>

                @if($expiredCompanies > 0 || $expiringCompanies > 0)
                <div class="mt-2">
                    <a href="{{ route('superadmin.companies.index') }}" class="btn btn-sm btn-outline-danger btn-block">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Review Expiring Companies
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- ISP Client Distribution --}}
        <div class="card card-outline shadow-sm" style="border-top:3px solid #117a8b;">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-network-wired mr-2" style="color:#117a8b;"></i>Client Distribution</h3>
            </div>
            <div class="card-body pt-2 pb-2">
                @forelse($companiesDetailed as $co)
                <div class="mb-2">
                    <div class="d-flex justify-content-between" style="font-size:.83rem;">
                        <span class="font-weight-bold text-truncate" style="max-width:120px;" title="{{ $co->name }}">{{ $co->name }}</span>
                        <span class="text-muted">{{ $co->active_clients_count }} / {{ $co->total_clients_count }}</span>
                    </div>
                    <div class="progress mt-1" style="height:5px;border-radius:3px;">
                        @php
                            $pct = $totalClients > 0 ? round($co->total_clients_count / $totalClients * 100) : 0;
                        @endphp
                        <div class="progress-bar" style="width:{{ $pct }}%;background:#117a8b;border-radius:3px;"></div>
                    </div>
                    <small class="text-muted" style="font-size:.7rem;">{{ $pct }}% of platform</small>
                </div>
                @empty
                <p class="text-muted text-center py-2" style="font-size:.85rem;">No companies yet</p>
                @endforelse
            </div>
        </div>

    </div>{{-- /col-md-4 --}}

</div>{{-- /main row --}}

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('revenueChart').getContext('2d');
    var labels  = @json(array_column($revenueByMonth, 'label'));
    var amounts = @json(array_column($revenueByMonth, 'amount'));

    var maxVal = Math.max.apply(null, amounts) || 1;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (৳)',
                data: amounts,
                backgroundColor: amounts.map(function(v) {
                    return v === Math.max.apply(null, amounts) ? 'rgba(231,76,60,0.85)' : 'rgba(30,60,114,0.75)';
                }),
                borderColor: amounts.map(function(v) {
                    return v === Math.max.apply(null, amounts) ? '#c0392b' : '#1e3c72';
                }),
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ৳ ' + ctx.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: function(val) {
                            return val >= 1000 ? (val/1000).toFixed(0) + 'K' : val;
                        },
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
});
</script>
@endpush
