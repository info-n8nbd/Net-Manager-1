@extends('layouts.admin')

@section('title', 'Bandwidth Allocation — ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type'=>'bandwidth']) }}">Bandwidth Reseller</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item active">Bandwidth Allocation</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9 col-md-12">

        {{-- Reseller Info Card --}}
        <div class="card card-warning card-outline mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tachometer-alt mr-2"></i>Bandwidth Allocation &amp; Pricing
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning px-3 py-1" style="font-size:13px;">{{ $reseller->name }}</span>
                    <span class="badge badge-secondary px-2 py-1 ml-1">{{ $reseller->reseller_id }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="px-3 pt-2 pb-1">
                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Monthly bill = &Sigma; (Mbps &times; Rate). Blank rows are excluded.</small>
                </div>
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Category</th>
                            <th class="text-right" style="width:160px;">Bandwidth (Mbps)</th>
                            <th class="text-right" style="width:160px;">Rate (৳/Mbps)</th>
                            <th class="text-right" style="width:170px;">Monthly Cost (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotal = 0; @endphp
                        @foreach($bwCategories as $cat)
                        @php
                            $mbps    = (float)($reseller->{$cat['mbps_field']} ?? 0);
                            $rate    = (float)($reseller->{$cat['rate_field']} ?? 0);
                            $subtotal = $mbps * $rate;
                            $grandTotal += $subtotal;
                        @endphp
                        <tr class="{{ $mbps == 0 && $rate == 0 ? 'text-muted' : '' }}">
                            <td>
                                <i class="{{ $cat['icon'] }} mr-2 text-{{ $cat['color'] }}"></i>
                                {{ $cat['label'] }}
                                @if($mbps == 0 && $rate == 0)
                                    <span class="badge badge-light ml-1" style="font-size:10px;">Not set</span>
                                @endif
                            </td>
                            <td class="text-right font-weight-{{ $mbps > 0 ? 'bold' : 'normal' }}">
                                {{ $mbps > 0 ? number_format($mbps, 2) : '—' }}
                            </td>
                            <td class="text-right font-weight-{{ $rate > 0 ? 'bold' : 'normal' }}">
                                {{ $rate > 0 ? '৳ ' . number_format($rate, 2) : '—' }}
                            </td>
                            <td class="text-right font-weight-bold {{ $subtotal > 0 ? 'text-dark' : 'text-muted' }}">
                                {{ $subtotal > 0 ? '৳ ' . number_format($subtotal, 2) : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#e67e22;">
                            <th colspan="3" class="text-right text-white" style="font-size:15px;">Total Monthly Bill:</th>
                            <th class="text-right text-white" style="font-size:16px;">৳ {{ number_format($grandTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Custom BW Categories --}}
        @if(isset($customDbCategories) && $customDbCategories->isNotEmpty())
        <div class="card card-outline card-info mb-3">
            <div class="card-header py-2">
                <h3 class="card-title" style="color:#17a2b8;"><i class="fas fa-layer-group mr-2"></i>Custom Bandwidth Categories</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Category</th>
                            <th class="text-right" style="width:160px;">Bandwidth (Mbps)</th>
                            <th class="text-right" style="width:160px;">Rate (৳/Mbps)</th>
                            <th class="text-right" style="width:170px;">Monthly Cost (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customDbCategories as $ccat)
                        @php
                            $alloc    = $customAllocations->get($ccat->id);
                            $cMbps    = $alloc ? (float)$alloc->mbps : 0;
                            $cRate    = $alloc ? (float)$alloc->rate : 0;
                            $cSubtotal = $cMbps * $cRate;
                        @endphp
                        <tr class="{{ $cMbps == 0 && $cRate == 0 ? 'text-muted' : '' }}">
                            <td>
                                @if(str_starts_with($ccat->icon, 'img:'))
                                    <img src="{{ substr($ccat->icon, 4) }}" alt="{{ $ccat->label }}" height="18" style="vertical-align:middle;" class="mr-1">
                                @else
                                    <i class="{{ $ccat->icon }} mr-2 text-{{ $ccat->color }}"></i>
                                @endif
                                {{ $ccat->label }}
                                @if($cMbps == 0 && $cRate == 0)<span class="badge badge-light ml-1" style="font-size:10px;">Not set</span>@endif
                            </td>
                            <td class="text-right font-weight-{{ $cMbps > 0 ? 'bold' : 'normal' }}">{{ $cMbps > 0 ? number_format($cMbps, 2) : '—' }}</td>
                            <td class="text-right font-weight-{{ $cRate > 0 ? 'bold' : 'normal' }}">{{ $cRate > 0 ? '৳ ' . number_format($cRate, 2) : '—' }}</td>
                            <td class="text-right font-weight-bold {{ $cSubtotal > 0 ? 'text-dark' : 'text-muted' }}">{{ $cSubtotal > 0 ? '৳ ' . number_format($cSubtotal, 2) : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-file-invoice-dollar"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Monthly Rate</span>
                        <span class="info-box-number">৳ {{ number_format($reseller->bandwidth_monthly_rate ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Status</span>
                        <span class="info-box-number text-capitalize">{{ $reseller->status }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-calendar-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Billing Type</span>
                        <span class="info-box-number text-capitalize">{{ $reseller->billing_type ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.reseller.index', ['type' => 'bandwidth']) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Bandwidth Reseller
            </a>
            <div>
                <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-outline-secondary mr-2">
                    <i class="fas fa-file-invoice mr-1"></i> Bill History
                </a>
                <a href="{{ route('admin.reseller.bandwidth.adjustments', $reseller) }}" class="btn btn-info mr-2">
                    <i class="fas fa-sliders-h mr-1"></i> Mid-Month Adjustment
                </a>
                <a href="{{ route('admin.reseller.edit', $reseller) }}" class="btn btn-warning">
                    <i class="fas fa-edit mr-1"></i> Edit Bandwidth
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
