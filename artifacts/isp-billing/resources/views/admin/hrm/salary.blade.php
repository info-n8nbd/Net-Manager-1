@extends('layouts.admin')

@section('title', 'Salary Management')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.employees') }}">HRM</a></li>
<li class="breadcrumb-item active">Salary</li>
@endsection

@section('content')
<style>
.salary-stat {
    border-radius: 10px;
    padding: 15px 20px;
    color: #fff;
    margin-bottom: 15px;
}
.salary-stat .stat-num { font-size: 22px; font-weight: 700; }
.salary-stat .stat-label { font-size: 12px; opacity: 0.9; }
.salary-status-paid { background: #d4edda; color: #155724; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.salary-status-pending { background: #fff3cd; color: #856404; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.salary-status-partial { background: #cce5ff; color: #004085; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
</style>

@php
    $totalBasic = $salaries->sum('basic_salary');
    $totalAllowances = $salaries->sum('allowances');
    $totalDeductions = $salaries->sum('deductions');
    $totalBonus = $salaries->sum('bonus');
    $totalNet = $salaries->sum('net_salary');
    $paidCount = $salaries->where('status', 'paid')->count();
    $pendingCount = $salaries->where('status', 'pending')->count();
@endphp

<div class="row mb-3">
    <div class="col-md-3 col-6">
        <div class="salary-stat" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <div class="stat-num">{{ $salaries->total() }}</div>
            <div class="stat-label">Total Records</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="salary-stat" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
            <div class="stat-num">{{ $paidCount }}</div>
            <div class="stat-label">Paid</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="salary-stat" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
            <div class="stat-num">{{ $pendingCount }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="salary-stat" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
            <div class="stat-num">৳{{ number_format($totalNet, 0) }}</div>
            <div class="stat-label">Total Net Salary</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-money-check-alt mr-2"></i>Salary - {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</h3>
        <div class="card-tools d-flex align-items-center" style="gap: 10px;">
            <form method="GET" class="form-inline">
                <select name="month" class="form-control form-control-sm mr-1">
                    @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $i == $month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                    @endfor
                </select>
                <select name="year" class="form-control form-control-sm mr-1">
                    @for($y = date('Y')-2; $y <= date('Y')+1; $y++)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
            </form>
            <form action="{{ route('admin.hrm.salary.process') }}" method="POST" class="d-inline" onsubmit="return confirm('Process salaries for all active employees for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}? This will create/update salary records based on attendance data.')">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-cogs mr-1"></i>Process Salaries</button>
            </form>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover table-striped mb-0">
            <thead class="bg-dark text-white">
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th class="text-center">Working Days</th>
                    <th class="text-right">Basic (৳)</th>
                    <th class="text-right">Allowances (৳)</th>
                    <th class="text-right">Overtime (h)</th>
                    <th class="text-right">Bonus (৳)</th>
                    <th class="text-right">Deductions (৳)</th>
                    <th class="text-right">Net Salary (৳)</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaries as $salary)
                <tr>
                    <td>{{ $loop->iteration + ($salaries->currentPage() - 1) * $salaries->perPage() }}</td>
                    <td>
                        <strong>{{ $salary->employee->name ?? 'N/A' }}</strong>
                        @if($salary->employee?->designation)
                        <br><small class="text-muted">{{ $salary->employee->designation->name }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $salary->working_days ?? '-' }}</td>
                    <td class="text-right">{{ number_format($salary->basic_salary, 0) }}</td>
                    <td class="text-right">{{ number_format($salary->allowances ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format($salary->overtime ?? 0, 1) }}h</td>
                    <td class="text-right">{{ number_format($salary->bonus ?? 0, 0) }}</td>
                    <td class="text-right text-danger">{{ number_format($salary->deductions ?? 0, 0) }}</td>
                    <td class="text-right"><strong>{{ number_format($salary->net_salary, 0) }}</strong></td>
                    <td class="text-center">
                        <span class="salary-status-{{ $salary->status }}">{{ ucfirst($salary->status) }}</span>
                        @if($salary->payment_date)
                        <br><small class="text-muted">{{ $salary->payment_date->format('d-m-Y') }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.hrm.salary.edit', $salary) }}" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('admin.hrm.salary.destroy', $salary) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this salary record?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">
                        <i class="fas fa-money-check-alt fa-2x mb-2 d-block"></i>
                        No salary records for this month. Click <strong>Process Salaries</strong> to generate from attendance data.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($salaries->count() > 0)
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="3" class="text-right">Totals:</td>
                    <td class="text-right">{{ number_format($totalBasic, 0) }}</td>
                    <td class="text-right">{{ number_format($totalAllowances, 0) }}</td>
                    <td class="text-right">{{ number_format($salaries->sum('overtime'), 1) }}h</td>
                    <td class="text-right">{{ number_format($totalBonus, 0) }}</td>
                    <td class="text-right text-danger">{{ number_format($totalDeductions, 0) }}</td>
                    <td class="text-right"><strong>{{ number_format($totalNet, 0) }}</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @if($salaries->hasPages())
    <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $salaries->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script></div>
    @endif
</div>
@endsection
