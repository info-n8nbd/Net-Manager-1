@extends('layouts.admin')

@section('title', 'Edit Salary')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.salary') }}">Salary</a></li>
<li class="breadcrumb-item active">Edit Salary</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Salary Record</h3>
            </div>
            <form action="{{ route('admin.hrm.salary.update', $salary) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Employee:</strong> {{ $salary->employee->name ?? 'N/A' }}<br>
                        <strong>Period:</strong> {{ date('F', mktime(0,0,0,$salary->month,1)) }} {{ $salary->year }}<br>
                        <strong>Working Days:</strong> {{ $salary->working_days ?? '-' }}<br>
                        <strong>Overtime Hours:</strong> {{ $salary->overtime ?? 0 }}h
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Basic Salary (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="basic_salary" id="basic_salary" class="form-control salary-field" value="{{ $salary->basic_salary }}" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Net Salary (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="net_salary" id="net_salary" class="form-control" value="{{ $salary->net_salary }}" step="0.01" required style="font-weight: bold; font-size: 16px; background: #f8f9fa;">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-plus-circle text-success mr-1"></i>Allowances (৳)</label>
                                <input type="number" name="allowances" id="allowances" class="form-control salary-field" value="{{ $salary->allowances ?? 0 }}" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-gift text-primary mr-1"></i>Bonus (৳)</label>
                                <input type="number" name="bonus" id="bonus" class="form-control salary-field" value="{{ $salary->bonus ?? 0 }}" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-minus-circle text-danger mr-1"></i>Deductions (৳)</label>
                                <input type="number" name="deductions" id="deductions" class="form-control salary-field" value="{{ $salary->deductions ?? 0 }}" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-hand-holding-usd text-warning mr-1"></i>Advance Deduction (৳)</label>
                                <input type="number" name="advance_deduction" id="advance_deduction" class="form-control salary-field" value="{{ $salary->advance_deduction ?? 0 }}" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" {{ $salary->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="partial" {{ $salary->status == 'partial' ? 'selected' : '' }}>Partial</option>
                                    <option value="paid" {{ $salary->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update Salary</button>
                    <a href="{{ route('admin.hrm.salary') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calculator mr-2"></i>Calculation</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless" style="font-size: 14px;">
                    <tr>
                        <td>Basic Salary</td>
                        <td class="text-right" id="calc_basic">৳{{ number_format($salary->basic_salary, 0) }}</td>
                    </tr>
                    <tr class="text-success">
                        <td>(+) Allowances</td>
                        <td class="text-right" id="calc_allow">৳{{ number_format($salary->allowances ?? 0, 0) }}</td>
                    </tr>
                    <tr class="text-success">
                        <td>(+) Bonus</td>
                        <td class="text-right" id="calc_bonus">৳{{ number_format($salary->bonus ?? 0, 0) }}</td>
                    </tr>
                    <tr class="text-danger">
                        <td>(-) Deductions</td>
                        <td class="text-right" id="calc_deduct">৳{{ number_format($salary->deductions ?? 0, 0) }}</td>
                    </tr>
                    <tr class="text-danger">
                        <td>(-) Advance</td>
                        <td class="text-right" id="calc_advance">৳{{ number_format($salary->advance_deduction ?? 0, 0) }}</td>
                    </tr>
                    <tr class="border-top font-weight-bold" style="font-size: 16px;">
                        <td>Net Salary</td>
                        <td class="text-right" id="calc_net">৳{{ number_format($salary->net_salary, 0) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trash mr-2"></i>Delete Salary</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Permanently delete this salary record.</p>
                <form action="{{ route('admin.hrm.salary.destroy', $salary) }}" method="POST" onsubmit="return confirm('Delete this salary record?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash mr-1"></i>Delete Salary
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    function recalcNet() {
        var basic = parseFloat($('#basic_salary').val()) || 0;
        var allow = parseFloat($('#allowances').val()) || 0;
        var bonus = parseFloat($('#bonus').val()) || 0;
        var deduct = parseFloat($('#deductions').val()) || 0;
        var advance = parseFloat($('#advance_deduction').val()) || 0;
        var net = basic + allow + bonus - deduct - advance;
        $('#net_salary').val(net.toFixed(2));
        $('#calc_basic').text('৳' + Math.round(basic).toLocaleString());
        $('#calc_allow').text('৳' + Math.round(allow).toLocaleString());
        $('#calc_bonus').text('৳' + Math.round(bonus).toLocaleString());
        $('#calc_deduct').text('৳' + Math.round(deduct).toLocaleString());
        $('#calc_advance').text('৳' + Math.round(advance).toLocaleString());
        $('#calc_net').text('৳' + Math.round(net).toLocaleString());
    }
    $('.salary-field').on('input change', recalcNet);
});
</script>
@endpush
