@extends('layouts.admin')

@section('title', 'Income Statement')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" class="form-inline">
                <div class="form-group mr-2">
                    <label class="mr-1"><strong>From:</strong></label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="form-group mr-2">
                    <label class="mr-1"><strong>To:</strong></label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
                <button type="button" class="btn btn-danger btn-sm ml-2" onclick="printIncomeStatement()"><i class="fas fa-print mr-1"></i>Print</button>
            </form>
        </div>
    </div>

    <div id="printArea">
    {{-- ═══════════════ INCOME ═══════════════ --}}
    <div class="card mb-3">
        <div class="card-header py-2" style="background:#1a7a5a; color:#fff;">
            <h5 class="mb-0"><i class="fas fa-arrow-down mr-2"></i>INCOME  <small class="ml-3 font-weight-normal">Period: {{ $startDate->format('d-M, Y') }} — {{ $endDate->format('d-M, Y') }}</small></h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:40%">Income Type</th>
                        <th class="text-center" style="width:12%">Count</th>
                        <th class="text-right" style="width:22%">Amount (৳)</th>
                        <th style="width:18%">Note</th>
                        <th class="text-center" style="width:8%">View</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Opening Balance row --}}
                    <tr class="table-active">
                        <td colspan="2"><strong>Opening Balance (Accounts)</strong></td>
                        <td class="text-right"><strong>{{ number_format($openingBalance, 2) }}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                    @php
                        $groups = [
                            'Client Bill Collection' => ['Direct Client Bill Collection'],
                            'Reseller Bills (ISP Income)' => ['Reseller Monthly Bill (Collected)', 'Bandwidth Reseller Bill (Collected)'],
                            'Other Income Sources' => ['Installation Charge (Paid)', 'Others Income', 'Product Sales', 'Loan Received'],
                        ];
                        $groupedItems = collect($incomeItems)->keyBy('name');
                    @endphp

                    @foreach($groups as $groupLabel => $groupKeys)
                    <tr style="background:#f0f4f8;">
                        <td colspan="5" class="py-1 px-3"><strong><i class="fas fa-layer-group mr-1 text-muted"></i>{{ $groupLabel }}</strong></td>
                    </tr>
                    @foreach($groupKeys as $key)
                        @if($groupedItems->has($key))
                        @php $item = $groupedItems[$key]; @endphp
                        <tr>
                            <td class="pl-4">{{ $item['name'] }}</td>
                            <td class="text-center">{{ number_format($item['object']) }}</td>
                            <td class="text-right text-success"><strong>{{ number_format($item['amount'], 2) }}</strong></td>
                            <td class="text-muted small">{{ $item['note'] ?? '' }}</td>
                            <td class="text-center">
                                @if(!empty($item['view_route']))
                                <a href="{{ $item['view_route'] }}" class="btn btn-info btn-xs"><i class="fas fa-eye"></i></a>
                                @endif
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#d4edda;">
                        <td colspan="2" class="text-right"><strong>Period Total Income</strong></td>
                        <td class="text-right"><strong>৳{{ number_format($periodIncome, 2) }}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                    <tr style="background:#1a7a5a; color:#fff;">
                        <td colspan="2" class="text-right"><strong>TOTAL INCOME (Opening + Period)</strong></td>
                        <td class="text-right"><strong>৳{{ number_format($totalIncome, 2) }}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ═══════════════ EXPENSE ═══════════════ --}}
    <div class="card mb-3">
        <div class="card-header py-2" style="background:#c0392b; color:#fff;">
            <h5 class="mb-0"><i class="fas fa-arrow-up mr-2"></i>EXPENSE</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:40%">Expense Type</th>
                        <th class="text-center" style="width:12%">Category</th>
                        <th class="text-center" style="width:10%">Count</th>
                        <th class="text-right" style="width:22%">Amount (৳)</th>
                        <th class="text-center" style="width:8%">View</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenseItems as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td class="text-center"><span class="badge badge-secondary">{{ $item['category'] }}</span></td>
                        <td class="text-center">{{ number_format($item['object']) }}</td>
                        <td class="text-right text-danger"><strong>{{ number_format($item['amount'], 2) }}</strong></td>
                        <td class="text-center">
                            @if(!empty($item['view_route']))
                            <a href="{{ $item['view_route'] }}" class="btn btn-info btn-xs"><i class="fas fa-eye"></i></a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f8d7da;">
                        <td colspan="3" class="text-right"><strong>TOTAL EXPENSE</strong></td>
                        <td class="text-right text-danger"><strong>৳{{ number_format($totalExpense, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ═══════════════ SUMMARY TABLE ═══════════════ --}}
    <div class="card mb-3">
        <div class="card-header py-2" style="background:#2c3e50; color:#fff;">
            <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Summary</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th style="width:30%">Income Head</th>
                        <th class="text-right" style="width:20%">Amount (৳)</th>
                        <th style="width:30%">Expense Head</th>
                        <th class="text-right" style="width:20%">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Opening Balance</td>
                        <td class="text-right">{{ number_format($summary['opening_balance'], 2) }}</td>
                        <td>Office Expense</td>
                        <td class="text-right">{{ number_format($summary['office_expense'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Direct Client Bill Collection</td>
                        <td class="text-right">{{ number_format($summary['direct_bill_collection'], 2) }}</td>
                        <td>Vendor Bill Payment</td>
                        <td class="text-right">{{ number_format($summary['vendor_bill_pay'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Installation Charge</td>
                        <td class="text-right">{{ number_format($summary['installation_charge'], 2) }}</td>
                        <td>Bandwidth Bill</td>
                        <td class="text-right">{{ number_format($summary['bw_bill_expense'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Reseller Bill (Collected)</td>
                        <td class="text-right">{{ number_format($summary['mac_reseller_bill'], 2) }}</td>
                        <td>Referral Partner Commission</td>
                        <td class="text-right">{{ number_format($summary['agent_commission'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Bandwidth Reseller Bill (Collected)</td>
                        <td class="text-right">{{ number_format($summary['bw_reseller_bill'], 2) }}</td>
                        <td>Loan Payment (Given)</td>
                        <td class="text-right">{{ number_format($summary['loan_payment'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Others Income</td>
                        <td class="text-right">{{ number_format($summary['others_income'], 2) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Product Sales</td>
                        <td class="text-right">{{ number_format($summary['product_sales'], 2) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Loan Received</td>
                        <td class="text-right">{{ number_format($summary['loan_received'], 2) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr class="table-secondary font-weight-bold">
                        <td>TOTAL INCOME</td>
                        <td class="text-right text-success">{{ number_format($totalIncome, 2) }} ৳</td>
                        <td>TOTAL EXPENSE</td>
                        <td class="text-right text-danger">{{ number_format($totalExpense, 2) }} ৳</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════ CLOSING BALANCE ═══════════════ --}}
    <div class="text-center mt-2 mb-3">
        @php $cb = $closingBalance; @endphp
        <div style="display: inline-block; background: {{ $cb >= 0 ? 'linear-gradient(135deg,#1a7a5a 0%,#0d6e4e 100%)' : 'linear-gradient(135deg,#c0392b 0%,#96281b 100%)' }}; color: #fff; padding: 15px 40px; border-radius: 8px; font-size: 18px;">
            <strong>CLOSING BALANCE</strong>
            <span style="background: rgba(255,255,255,0.15); padding: 8px 20px; margin-left: 15px; border-radius: 6px; font-size: 22px; font-weight: 700;">
                {{ $cb >= 0 ? '' : '-' }}৳{{ number_format(abs($cb), 2) }}
            </span>
        </div>
    </div>
    </div>{{-- end printArea --}}
</div>
@endsection

@push('scripts')
<script>
function printIncomeStatement() {
    var content = document.getElementById('printArea').innerHTML;
    var win = window.open('', '_blank');
    win.document.write('<html><head><title>Income Statement</title>');
    win.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">');
    win.document.write('<style>body{padding:20px;font-size:13px;} .badge{padding:2px 6px;font-size:11px;border-radius:3px;background:#6c757d;color:#fff;}</style>');
    win.document.write('</head><body>');
    win.document.write('<h4 class="text-center mb-1">Income Statement</h4>');
    win.document.write('<p class="text-center text-muted mb-3">{{ $startDate->format("d-M, Y") }} To {{ $endDate->format("d-M, Y") }}</p>');
    win.document.write(content);
    win.document.write('</body></html>');
    win.document.close();
    setTimeout(function(){ win.print(); }, 500);
}
</script>
@endpush
