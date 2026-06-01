@extends('layouts.admin')
@section('title', 'Referral Partner Commission (' . $monthName . ')')

@section('content')
<div class="card card-outline card-info">
    <div class="card-header d-flex align-items-center flex-wrap">
        <h3 class="card-title"><i class="fas fa-coins mr-2"></i>Referral Partner Commission ({{ $monthName }})</h3>
        <div class="ml-auto d-flex align-items-center flex-wrap">
            <form method="GET" action="{{ route('admin.agent.commission', $agent->id) }}" class="form-inline mr-2 mb-1">
                <select name="month" class="form-control form-control-sm mr-1">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
                <select name="year" class="form-control form-control-sm mr-1">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
            </form>
            <button class="btn btn-success btn-sm mr-1 mb-1" data-toggle="modal" data-target="#addCommissionModal">
                <i class="fas fa-plus mr-1"></i> ADD COMMISSION
            </button>
            <a href="{{ route('admin.agent.ledger', $agent->id) }}" class="btn btn-info btn-sm mr-1 mb-1">
                <i class="fas fa-book mr-1"></i> LEDGER
            </a>
            <button class="btn btn-danger btn-sm mb-1" onclick="window.print()">
                <i class="fas fa-print mr-1"></i> PRINT
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-2"><strong>Partner Name :</strong> {{ $agent->name }}</div>
            <div class="col-md-2"><strong>Cell :</strong> {{ $agent->personal_contact ?? '-' }}</div>
            <div class="col-md-2"><strong>Address :</strong> {{ $agent->present_address ?? '-' }}</div>
            <div class="col-md-2"><strong>Default :</strong> {{ number_format($agent->default_commission, 2) }}%</div>
            <div class="col-md-2 text-right"><strong>Current Clients :</strong> {{ $clientCount }}</div>
        </div>

        <div class="table-responsive">
            <table id="commissionTable" class="table table-bordered table-striped table-hover mb-0">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>Sl</th>
                        <th>Name/ID/Cell</th>
                        <th>Zone/Address</th>
                        <th>Purpose</th>
                        <th>Note</th>
                        <th>Date</th>
                        <th>Payment</th>
                        <th>Commission(%)</th>
                        <th>Amount (TK)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $i => $c)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $c->client_name }}</strong>
                            @if($c->client_pppoe_id)
                            <br><small class="text-muted">{{ $c->client_pppoe_id }}</small>
                            @endif
                        </td>
                        <td>{{ $c->zone_address ?? '-' }}</td>
                        <td>{{ $c->purpose ?? '-' }}</td>
                        <td>{{ $c->note ?? '-' }}</td>
                        <td>{{ $c->date ? $c->date->format('d-m-Y') : '-' }}</td>
                        <td>{{ number_format($c->payment_amount, 2) }}</td>
                        <td>{{ number_format($c->commission_percent, 2) }}%</td>
                        <td><strong>{{ number_format($c->commission_amount, 2) }}</strong></td>
                        <td class="text-nowrap">
                            <form action="{{ route('admin.agent.commission.destroy', $c->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this commission record?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-3">
                            <em>{{ $monthName }} Commission History</em>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <td colspan="8" class="text-right"><strong>TOTAL</strong></td>
                        <td><strong class="text-success">{{ number_format($totalAmount, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="card-footer text-right">
        <button class="btn btn-danger btn-sm" onclick="window.print()"><i class="fas fa-print mr-1"></i> PRINT</button>
    </div>
</div>

<div class="modal fade" id="addCommissionModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.agent.commission.store', $agent->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-1"></i> Add Commission</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Client Name <span class="text-danger">*</span></label>
                                <input type="text" name="client_name" class="form-control form-control-sm" required>
                            </div>
                            <div class="form-group">
                                <label>PPPoE ID / Client ID</label>
                                <input type="text" name="client_pppoe_id" class="form-control form-control-sm">
                            </div>
                            <div class="form-group">
                                <label>Zone / Address</label>
                                <input type="text" name="zone_address" class="form-control form-control-sm">
                            </div>
                            <div class="form-group">
                                <label>Purpose <span class="text-danger">*</span></label>
                                <select name="purpose" class="form-control form-control-sm" required>
                                    <option value="">Select Purpose</option>
                                    <option value="New Connection">New Connection</option>
                                    <option value="Monthly Bill">Monthly Bill (MAC Client)</option>
                                    <option value="BW Monthly Bill">BW Monthly Bill (Bandwidth Reseller)</option>
                                    <option value="Reseller Bill Payment">Reseller Bill Payment</option>
                                    <option value="Reconnection">Reconnection</option>
                                    <option value="Package Upgrade">Package Upgrade</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="form-group">
                                <label>Payment Amount (TK) <span class="text-danger">*</span></label>
                                <input type="number" name="payment_amount" id="paymentAmount" class="form-control form-control-sm" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>Commission (%) <span class="text-danger">*</span></label>
                                <input type="number" name="commission_percent" id="commissionPercent" class="form-control form-control-sm" step="0.01" min="0" max="100" value="{{ $agent->default_commission }}" required>
                            </div>
                            <div class="form-group">
                                <label>Estimated Commission Amount</label>
                                <input type="text" id="estimatedAmount" class="form-control form-control-sm" readonly>
                            </div>
                            <div class="form-group">
                                <label>Note</label>
                                <textarea name="note" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Save Commission</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function calcCommission() {
        var payment = parseFloat($('#paymentAmount').val()) || 0;
        var percent = parseFloat($('#commissionPercent').val()) || 0;
        var amount = (payment * percent) / 100;
        $('#estimatedAmount').val(amount.toFixed(2) + ' TK');
    }
    $('#paymentAmount, #commissionPercent').on('input', calcCommission);
});
</script>
@endpush
