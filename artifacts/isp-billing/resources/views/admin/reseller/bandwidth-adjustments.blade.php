@extends('layouts.admin')

@section('title', 'Bandwidth Adjustments — ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type'=>'bandwidth']) }}">Bandwidth Reseller</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.bandwidth', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item active">Mid-Month Adjustments</li>
@endsection

@section('content')
<div class="row">
    {{-- Left: Add Adjustment Form --}}
    <div class="col-lg-4 col-md-5">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sliders-h mr-2"></i>Add Adjustment</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('admin.reseller.bandwidth.adjustments.store', $reseller) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Category <span class="text-danger">*</span></label>
                        <select name="category_key" class="form-control @error('category_key') is-invalid @enderror" id="categorySelect" required>
                            <option value="">-- Select Category --</option>
                            @foreach($categoryMap as $key => $cat)
                            @php
                                if (!empty($cat['is_custom'])) {
                                    $alloc = $customDbCategories->firstWhere('id', $cat['custom_id'] ?? null);
                                    $resellerAlloc = $alloc ? \App\Models\ResellerBwAllocation::where('reseller_id', $reseller->id)->where('category_id', $alloc->id)->first() : null;
                                    $currentMbps = $resellerAlloc ? (float)$resellerAlloc->mbps : 0;
                                    $currentRate = $resellerAlloc ? (float)$resellerAlloc->rate : 0;
                                } else {
                                    $currentMbps = (float)($reseller->{$cat['mbps_field']} ?? 0);
                                    $currentRate = (float)($reseller->{$cat['rate_field']} ?? 0);
                                }
                            @endphp
                            @if(!empty($cat['is_custom']) || $currentRate > 0 || $currentMbps > 0)
                            <option value="{{ $key }}"
                                data-current="{{ $currentMbps }}"
                                data-rate="{{ $currentRate }}"
                                {{ old('category_key') == $key ? 'selected' : '' }}>
                                {{ $cat['label'] }}{{ $currentMbps > 0 ? ' (now: '.number_format($currentMbps,0).' Mbps)' : '' }}
                            </option>
                            @endif
                            @endforeach
                        </select>
                        @error('category_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Current info box --}}
                    <div id="currentInfo" class="alert alert-light border mb-3" style="display:none;font-size:13px;">
                        <div><strong>Current:</strong> <span id="infoMbps">—</span> Mbps &times; <span id="infoRate">—</span> ৳/Mbps = <span id="infoTotal" class="text-primary font-weight-bold">—</span> ৳/month</div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror"
                            value="{{ old('effective_date', now()->toDateString()) }}" required>
                        <small class="text-muted">From which day the new Mbps applies (this month)</small>
                        @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">New Mbps <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="new_mbps" id="newMbpsInput"
                                class="form-control @error('new_mbps') is-invalid @enderror"
                                value="{{ old('new_mbps') }}" min="0" step="0.01" placeholder="e.g. 600" required>
                            <div class="input-group-append"><span class="input-group-text">Mbps</span></div>
                        </div>
                        @error('new_mbps')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Live preview --}}
                    <div id="previewBox" class="alert alert-warning border mb-3 p-2" style="display:none;">
                        <div class="d-flex align-items-center flex-wrap" style="gap:4px 8px;font-size:13px;">
                            <i class="fas fa-calculator text-warning"></i>
                            <strong>After change:</strong>
                            <span id="previewNewMbps" class="badge badge-secondary">—</span>
                            <span>Mbps &times;</span>
                            <span id="previewRate" class="badge badge-secondary">—</span>
                            <span>৳/Mbps =</span>
                            <span id="previewNewTotal" class="badge badge-dark" style="font-size:13px;">—</span>
                            <span>৳/month</span>
                        </div>
                        <div id="previewDiff" class="mt-1" style="font-size:13px;font-weight:600;"></div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Reason</label>
                        <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="e.g. Upgraded by client request">
                    </div>

                    <button type="submit" class="btn btn-warning btn-block"
                        onclick="return confirm('Apply this bandwidth adjustment? The current month bill will be recalculated pro-rated.')">
                        <i class="fas fa-check mr-1"></i>Apply Adjustment
                    </button>
                </form>
            </div>
        </div>

        {{-- Current Allocation Summary --}}
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i>Current Allocation</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach($categoryMap as $key => $cat)
                    @php
                        if (!empty($cat['is_custom'])) {
                            $alloc = isset($customDbCategories) ? $customDbCategories->firstWhere('id', $cat['custom_id'] ?? null) : null;
                            $resellerAlloc = $alloc ? \App\Models\ResellerBwAllocation::where('reseller_id', $reseller->id)->where('category_id', $alloc->id)->first() : null;
                            $mbps = $resellerAlloc ? (float)$resellerAlloc->mbps : 0;
                            $rate = $resellerAlloc ? (float)$resellerAlloc->rate : 0;
                        } else {
                            $mbps = (float)($reseller->{$cat['mbps_field']} ?? 0);
                            $rate = (float)($reseller->{$cat['rate_field']} ?? 0);
                        }
                        $sub  = $mbps * $rate;
                    @endphp
                    @if($mbps > 0 || $rate > 0)
                    <tr>
                        <td><i class="{{ $cat['icon'] }} text-{{ $cat['color'] }} mr-1"></i> <small>{{ $cat['label'] }}</small></td>
                        <td class="text-right"><small>{{ number_format($mbps,0) }} Mbps</small></td>
                        <td class="text-right"><small class="font-weight-bold {{ $sub > 0 ? 'text-dark' : 'text-muted' }}">৳{{ number_format($sub,0) }}</small></td>
                    </tr>
                    @endif
                    @endforeach
                    <tr class="bg-warning">
                        <td colspan="2" class="text-right font-weight-bold">Total Monthly:</td>
                        <td class="text-right font-weight-bold">৳{{ number_format($reseller->bandwidth_monthly_rate ?? 0, 0) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: Adjustment History --}}
    <div class="col-lg-8 col-md-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Adjustment History</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.reseller.bandwidth', $reseller) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Bandwidth
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($adjustments->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="fas fa-sliders-h fa-2x mb-2"></i>
                    <p>No adjustments recorded yet.</p>
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Date Applied</th>
                                <th>Category</th>
                                <th class="text-center">Effective From</th>
                                <th class="text-right">Old Mbps</th>
                                <th class="text-right">New Mbps</th>
                                <th class="text-right">Rate</th>
                                <th>Reason</th>
                                <th>By</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($adjustments as $adj)
                            @php
                                $change = $adj->new_mbps - $adj->old_mbps;
                                $isUp   = $change > 0;
                            @endphp
                            <tr>
                                <td style="font-size:12px;">{{ $adj->created_at->format('d M Y H:i') }}</td>
                                <td style="font-size:12px;">
                                    <i class="{{ $adj->category_icon }} mr-1"></i>
                                    {{ $adj->category_label }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ $adj->effective_date->format('d M Y') }}</span>
                                </td>
                                <td class="text-right">{{ number_format($adj->old_mbps, 0) }} Mbps</td>
                                <td class="text-right">
                                    <strong class="{{ $isUp ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($adj->new_mbps, 0) }} Mbps
                                        <i class="fas fa-arrow-{{ $isUp ? 'up' : 'down' }} ml-1" style="font-size:10px;"></i>
                                    </strong>
                                    <br><small class="{{ $isUp ? 'text-success' : 'text-danger' }}">
                                        {{ $isUp ? '+' : '' }}{{ number_format($change, 0) }} Mbps
                                    </small>
                                </td>
                                <td class="text-right">৳{{ number_format($adj->rate, 0) }}/Mbps</td>
                                <td style="font-size:12px;">{{ $adj->reason ?? '—' }}</td>
                                <td style="font-size:12px;">{{ $adj->createdBy->name ?? 'System' }}</td>
                                <td class="text-center">
                                    <form action="{{ route('admin.reseller.bandwidth.adjustments.destroy', [$reseller, $adj]) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete this adjustment record?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-xs" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Pro-rated Bill Preview for current month --}}
        @php
            $now = now();
            $currentBill = \App\Models\ResellerBill::where('reseller_id', $reseller->id)
                ->where('bill_month', $now->month)->where('bill_year', $now->year)->first();
        @endphp
        @if($currentBill)
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Current Bill — {{ $now->format('F Y') }}
                    @if(collect($currentBill->bandwidth_breakdown ?? [])->contains('prorated', true))
                        <span class="badge badge-warning ml-2" style="font-size:11px;">PRO-RATED</span>
                    @endif
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Category</th>
                            <th class="text-right">Amount</th>
                            <th>Segments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($currentBill->bandwidth_breakdown ?? [] as $key => $cat)
                        <tr>
                            <td><small>{{ $cat['label'] ?? $key }}</small></td>
                            <td class="text-right font-weight-bold">৳{{ number_format($cat['subtotal'] ?? 0, 2) }}</td>
                            <td>
                                @if(!empty($cat['segments']))
                                    @foreach($cat['segments'] as $seg)
                                    <small class="d-block text-muted">
                                        {{ $seg['from'] }}–{{ $seg['to'] }} ({{ $seg['days'] }}d):
                                        {{ number_format($seg['mbps'],0) }}Mbps × ৳{{ number_format($seg['rate'],0) }} × {{ $seg['days'] }}/{{ now()->daysInMonth }}
                                        = <strong>৳{{ number_format($seg['amount'],2) }}</strong>
                                    </small>
                                    @endforeach
                                @else
                                    <small class="text-muted">Full month @ {{ number_format($cat['mbps'] ?? 0,0) }} Mbps</small>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-warning">
                            <th>Total Bill</th>
                            <th class="text-right">৳{{ number_format($currentBill->total_recharge, 2) }}</th>
                            <th>
                                <span class="badge badge-{{ $currentBill->status === 'paid' ? 'success' : ($currentBill->status === 'partial' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($currentBill->status) }}
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    var categoryData = {};
    $('#categorySelect option[value]').each(function(){
        var k = $(this).val();
        categoryData[k] = {
            mbps: parseFloat($(this).data('current')) || 0,
            rate: parseFloat($(this).data('rate'))    || 0
        };
    });

    function updateInfo() {
        var k = $('#categorySelect').val();
        if (!k || !categoryData[k]) {
            $('#currentInfo').hide(); $('#previewBox').hide(); return;
        }
        var d    = categoryData[k];
        var tot  = d.mbps * d.rate;
        $('#infoMbps').text(d.mbps.toLocaleString());
        $('#infoRate').text(d.rate.toLocaleString());
        $('#infoTotal').text(tot.toLocaleString('en-BD',{minimumFractionDigits:2,maximumFractionDigits:2}));
        $('#currentInfo').show();

        var newMbps = parseFloat($('#newMbpsInput').val());
        if (!isNaN(newMbps) && newMbps >= 0) {
            var newTot  = newMbps * d.rate;
            var diff    = newTot - tot;
            var diffStr = (diff >= 0 ? '+' : '') + diff.toLocaleString('en-BD',{minimumFractionDigits:2,maximumFractionDigits:2});
            var color   = diff > 0 ? 'text-danger' : (diff < 0 ? 'text-success' : 'text-muted');
            $('#previewNewMbps').text(newMbps.toLocaleString());
            $('#previewRate').text(d.rate.toLocaleString());
            $('#previewNewTotal').text('৳' + newTot.toLocaleString('en-BD',{minimumFractionDigits:2,maximumFractionDigits:2}));
            $('#previewDiff').html('<span class="' + color + '">' + (diff > 0 ? 'Increase' : 'Decrease') + ': ' + diffStr + ' ৳/month</span>');
            $('#previewBox').show();
        } else {
            $('#previewBox').hide();
        }
    }

    $('#categorySelect, #newMbpsInput').on('change input', updateInfo);
    updateInfo();
});
</script>
@endpush
