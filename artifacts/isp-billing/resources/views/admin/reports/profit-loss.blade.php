@extends('layouts.admin')

@section('title', 'Profit & Loss Statement')

@section('content')

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline">
                <label class="mr-2"><strong>From:</strong></label>
                <input type="date" name="start_date" class="form-control form-control-sm mr-3" value="{{ $startDate->format('Y-m-d') }}">
                <label class="mr-2"><strong>To:</strong></label>
                <input type="date" name="end_date" class="form-control form-control-sm mr-3" value="{{ $endDate->format('Y-m-d') }}">
                <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter mr-1"></i>Generate</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()"><i class="fas fa-print mr-1"></i>Print</button>
            </form>
        </div>
    </div>

    {{-- Summary KPI cards --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Revenue</span>
                    <span class="info-box-number">৳{{ number_format($report['total_revenue'] ?? 0, 2) }}</span>
                    <span class="info-box-text">{{ $report['period']['start'] }} — {{ $report['period']['end'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Expenses</span>
                    <span class="info-box-number">৳{{ number_format($report['total_expenses'] ?? 0, 2) }}</span>
                    <span class="info-box-text">All expense categories</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @php $np = $report['net_profit'] ?? 0; @endphp
            <div class="info-box {{ $np >= 0 ? 'bg-primary' : 'bg-warning' }}">
                <span class="info-box-icon"><i class="fas fa-{{ $np >= 0 ? 'chart-line' : 'exclamation-triangle' }}"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ $np >= 0 ? 'Net Profit' : 'Net Loss' }}</span>
                    <span class="info-box-number">{{ $np >= 0 ? '' : '-' }}৳{{ number_format(abs($np), 2) }}</span>
                    <span class="info-box-text">Revenue − Expenses</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ── REVENUE ── --}}
        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-arrow-down mr-2"></i>Revenue / Income</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            <tr class="table-active">
                                <td colspan="2"><strong><i class="fas fa-users mr-1"></i>Client Bill Collection</strong></td>
                            </tr>
                            <tr>
                                <td class="pl-4">Direct ISP Client Collection</td>
                                <td class="text-right">৳{{ number_format($report['revenue']['direct_client'] ?? 0, 2) }}</td>
                            </tr>

                            <tr class="table-active">
                                <td colspan="2"><strong><i class="fas fa-store mr-1"></i>Reseller Bills Paid to ISP</strong></td>
                            </tr>
                            <tr>
                                <td class="pl-4">Reseller Monthly Bills</td>
                                <td class="text-right">৳{{ number_format($report['revenue']['mac_reseller'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4">Bandwidth Reseller Bills</td>
                                <td class="text-right">৳{{ number_format($report['revenue']['bw_reseller'] ?? 0, 2) }}</td>
                            </tr>

                            <tr class="table-active">
                                <td colspan="2"><strong><i class="fas fa-plus-circle mr-1"></i>Other Revenue</strong></td>
                            </tr>
                            <tr>
                                <td class="pl-4">Installation Charges</td>
                                <td class="text-right">৳{{ number_format($report['revenue']['installation'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4">Product / Equipment Sales</td>
                                <td class="text-right">৳{{ number_format($report['revenue']['product_sales'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4">Other Income</td>
                                <td class="text-right">৳{{ number_format($report['revenue']['other'] ?? 0, 2) }}</td>
                            </tr>
                            @if(!empty($report['revenue']['other_by_head']))
                                @foreach($report['revenue']['other_by_head'] as $head => $amt)
                                <tr class="text-muted">
                                    <td class="pl-5 small">↳ {{ $head }}</td>
                                    <td class="text-right small">৳{{ number_format($amt, 2) }}</td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="bg-success text-white font-weight-bold">
                                <td>Total Revenue</td>
                                <td class="text-right">৳{{ number_format($report['total_revenue'] ?? 0, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── EXPENSES ── --}}
        <div class="col-md-6">
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-arrow-up mr-2"></i>Expenses</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            <tr class="table-active">
                                <td colspan="2"><strong><i class="fas fa-cogs mr-1"></i>Operating Expenses</strong></td>
                            </tr>
                            <tr>
                                <td class="pl-4">Office / General Expense</td>
                                <td class="text-right">৳{{ number_format($report['expenses']['office'] ?? 0, 2) }}</td>
                            </tr>
                            @if(!empty($report['expenses']['by_head']))
                                @foreach($report['expenses']['by_head'] as $head => $amt)
                                <tr class="text-muted">
                                    <td class="pl-5 small">↳ {{ $head }}</td>
                                    <td class="text-right small">৳{{ number_format($amt, 2) }}</td>
                                </tr>
                                @endforeach
                            @endif
                            <tr>
                                <td class="pl-4">Bandwidth Bill</td>
                                <td class="text-right">৳{{ number_format($report['expenses']['bw_bill'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4">Vendor Bill Payments</td>
                                <td class="text-right">৳{{ number_format($report['expenses']['vendor'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4">Referral Partner Commission</td>
                                <td class="text-right">৳{{ number_format($report['expenses']['agent_commission'] ?? 0, 2) }}</td>
                            </tr>

                            <tr class="table-active">
                                <td colspan="2"><strong><i class="fas fa-university mr-1"></i>Financial Expenses</strong></td>
                            </tr>
                            <tr>
                                <td class="pl-4">Loan Payments (Given)</td>
                                <td class="text-right">৳{{ number_format($report['expenses']['loan_payment'] ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-danger text-white font-weight-bold">
                                <td>Total Expenses</td>
                                <td class="text-right">৳{{ number_format($report['total_expenses'] ?? 0, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card {{ ($report['net_profit'] ?? 0) >= 0 ? 'card-primary' : 'card-warning' }} mt-0">
                <div class="card-body text-center py-3">
                    <h5 class="text-muted mb-1">{{ ($report['net_profit'] ?? 0) >= 0 ? 'NET PROFIT' : 'NET LOSS' }}</h5>
                    <h2 class="{{ ($report['net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }} mb-0">
                        {{ ($report['net_profit'] ?? 0) >= 0 ? '' : '-' }}৳{{ number_format(abs($report['net_profit'] ?? 0), 2) }}
                    </h2>
                    <small class="text-muted">Total Revenue − Total Expenses</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Reseller type comparison --}}
    <div class="card mt-2">
        <div class="card-header py-2">
            <h5 class="card-title mb-0"><i class="fas fa-store mr-2"></i>Reseller Type Revenue Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-info mb-0">
                        <div class="card-body py-3">
                            <h6 class="font-weight-bold text-info mb-3"><i class="fas fa-network-wired mr-1"></i>Resellers (Managed / Subscription)</h6>
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td>Client Bill Collection (via MAC)</td>
                                    <td class="text-right">৳{{ number_format($report['revenue']['mac_client'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Monthly Bill Payments to ISP</td>
                                    <td class="text-right">৳{{ number_format($report['revenue']['mac_reseller'] ?? 0, 2) }}</td>
                                </tr>
                                <tr class="font-weight-bold table-info">
                                    <td>Reseller Total</td>
                                    <td class="text-right">৳{{ number_format(($report['revenue']['mac_client'] ?? 0) + ($report['revenue']['mac_reseller'] ?? 0), 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-warning mb-0">
                        <div class="card-body py-3">
                            <h6 class="font-weight-bold text-warning mb-3"><i class="fas fa-satellite-dish mr-1"></i>Bandwidth Reseller (Wholesale Buyers)</h6>
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td>Bandwidth Bill Payments to ISP</td>
                                    <td class="text-right">৳{{ number_format($report['revenue']['bw_reseller'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Client Management</td>
                                    <td class="text-right"><span class="text-muted">Managed by reseller</span></td>
                                </tr>
                                <tr class="font-weight-bold table-warning">
                                    <td>Bandwidth Reseller Total</td>
                                    <td class="text-right">৳{{ number_format($report['revenue']['bw_reseller'] ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center text-muted mt-3 mb-3">
        <small>Report Period: {{ $report['period']['start'] ?? '' }} to {{ $report['period']['end'] ?? '' }} | Generated: {{ now()->format('d-m-Y H:i') }}</small>
    </div>
@endsection
