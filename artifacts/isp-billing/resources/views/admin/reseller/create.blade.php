@extends('layouts.admin')

@section('title', $initialType === 'bandwidth' ? 'Add Bandwidth Reseller' : 'Add MAC Reseller')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type' => $initialType]) }}">{{ $initialType === 'bandwidth' ? 'Bandwidth Resellers' : 'MAC Resellers' }}</a></li>
<li class="breadcrumb-item active">{{ $initialType === 'bandwidth' ? 'Add Bandwidth Reseller' : 'Add MAC Reseller' }}</li>
@endsection

@section('content')
<form action="{{ route('admin.reseller.store') }}" method="POST">
    @csrf

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>New <span id="reseller_type_label">MAC</span> Reseller</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Reseller Name <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Reseller Full Name" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right text-danger">Login Username <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="e.g. sajib123 or sajib@email.com" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right text-danger">Login Password <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="6">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Confirm Password <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Contact No <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="Personal Contact No" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Area/Zone <span class="text-danger">*</span></label>
                        <div class="col-sm-4">
                            <input type="text" name="area_name_en" class="form-control" value="{{ old('area_name_en') }}" placeholder="Area Name in English">
                        </div>
                        <div class="col-sm-4">
                            <input type="text" name="area_name_bn" class="form-control" value="{{ old('area_name_bn') }}" placeholder="Area Name in Bangla">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Thana</label>
                        <div class="col-sm-8">
                            <input type="text" name="thana" class="form-control" value="{{ old('thana') }}" placeholder="Multiple Thana">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Company Name</label>
                        <div class="col-sm-8">
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="Company/Business Name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">NID Number</label>
                        <div class="col-sm-8">
                            <input type="text" name="nid" class="form-control" value="{{ old('nid') }}" placeholder="National ID Number">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Address</label>
                        <div class="col-sm-8">
                            <textarea name="address" class="form-control" rows="2" placeholder="Full Address">{{ old('address') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Type <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select name="type" class="form-control" required>
                                <option value="mac" {{ old('type', $initialType) == 'mac' ? 'selected' : '' }}>MAC Binding</option>
                                <option value="bandwidth" {{ old('type', $initialType) == 'bandwidth' ? 'selected' : '' }}>Bandwidth</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Credit Limit</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="number" name="credit_limit" class="form-control" value="{{ old('credit_limit', 0) }}" step="0.01" min="0">
                                <div class="input-group-append"><span class="input-group-text">&#2547;</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Status</label>
                        <div class="col-sm-8">
                            <select name="status" class="form-control">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="mac_billing_section" class="card card-info card-outline" style="{{ $initialType === 'bandwidth' ? 'display:none;' : '' }}">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box mr-2"></i>Packages & Billing Information <small class="text-muted">(MAC Binding Only)</small></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Network <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select name="mikrotik_device_id" class="form-control">
                                <option value="">Choose a Network...</option>
                                @foreach($mikrotikDevices as $device)
                                <option value="{{ $device->id }}" {{ old('mikrotik_device_id') == $device->id ? 'selected' : '' }}>{{ $device->name }} ({{ $device->host }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Billing Type <span class="text-danger">*</span></label>
                        <div class="col-sm-8 pt-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="billing_type" id="billing_postpaid" value="postpaid" {{ old('billing_type', 'prepaid') == 'postpaid' ? 'checked' : '' }}>
                                <label class="form-check-label" for="billing_postpaid">Postpaid</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="billing_type" id="billing_prepaid" value="prepaid" {{ old('billing_type', 'prepaid') == 'prepaid' ? 'checked' : '' }}>
                                <label class="form-check-label" for="billing_prepaid">Prepaid</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Over Due Amount <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="number" name="over_due_limit" class="form-control" value="{{ old('over_due_limit', 0) }}" step="0.01" min="0">
                                <div class="input-group-append"><span class="input-group-text">&#2547;</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card card-outline card-secondary mb-3">
                        <div class="card-header py-2">
                            <h3 class="card-title" style="font-size: 13px;">Client Billing Cycle <small class="text-muted">(select one)</small></h3>
                        </div>
                        <div class="card-body py-2">
                            <div class="form-group row mb-2">
                                <div class="col-sm-1 pt-1">
                                    <input class="form-check-input ml-0" type="radio" name="recharge_day_type" id="rdt_single" value="single" {{ old('recharge_day_type', 'multi') == 'single' ? 'checked' : '' }}>
                                </div>
                                <label class="col-sm-4 col-form-label" for="rdt_single">Fixed Date Each Month</label>
                                <div class="col-sm-7">
                                    <select name="single_recharge_day" class="form-control form-control-sm">
                                        <option value="">-- Select Day of Month --</option>
                                        @for($i = 1; $i <= 31; $i++)
                                        <option value="{{ $i }}" {{ old('single_recharge_day') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <small class="text-muted">All clients renew on this same day every month, regardless of join date.</small>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="form-group row mb-2">
                                <div class="col-sm-1 pt-1">
                                    <input class="form-check-input ml-0" type="radio" name="recharge_day_type" id="rdt_multi" value="multi" {{ old('recharge_day_type', 'multi') == 'multi' ? 'checked' : '' }}>
                                </div>
                                <label class="col-sm-4 col-form-label" for="rdt_multi">Fixed Period (Days)</label>
                                <div class="col-sm-7">
                                    <input type="number" name="minimum_recharge_days" class="form-control form-control-sm" value="{{ old('minimum_recharge_days', 30) }}" min="1" placeholder="e.g. 30">
                                    <small class="text-muted">Each recharge covers exactly this many days (e.g. 30 = 1 month). Cannot recharge before the period ends.</small>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="form-group row mb-1">
                                <div class="col-sm-1 pt-1">
                                    <input class="form-check-input ml-0" type="radio" name="recharge_day_type" id="rdt_flexible" value="flexible" {{ old('recharge_day_type') == 'flexible' ? 'checked' : '' }}>
                                </div>
                                <label class="col-sm-4 col-form-label" for="rdt_flexible">Flexible Period</label>
                                <div class="col-sm-7">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text">Minimum Days</span></div>
                                        <input type="number" name="flexible_min_days" class="form-control form-control-sm" value="{{ old('flexible_min_days', 1) }}" min="1" max="365" placeholder="1">
                                    </div>
                                    <small class="text-muted">Reseller can recharge for any number of days (at least the minimum above). Prepaid: deducted from wallet. Postpaid: within credit limit.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Auto Recharge? <span class="text-danger">*</span></label>
                        <div class="col-sm-8 pt-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="auto_recharge" id="ar_yes" value="yes" {{ old('auto_recharge') == 'yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ar_yes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="auto_recharge" id="ar_no" value="no" {{ old('auto_recharge', 'no') == 'no' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ar_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">
                            Terminate Time
                            <br><small class="text-danger">Expired Clients Will Terminate at</small>
                        </label>
                        <div class="col-sm-8">
                            <div class="d-flex align-items-center">
                                <span class="text-info mr-2">Hours</span>
                                <select name="terminate_hour" class="form-control" style="width:80px">
                                    @for($h = 0; $h <= 23; $h++)
                                    <option value="{{ $h }}" {{ old('terminate_hour', 0) == $h ? 'selected' : '' }}>{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</option>
                                    @endfor
                                </select>
                                <span class="mx-2 font-weight-bold">:</span>
                                <select name="terminate_minute" class="form-control" style="width:80px">
                                    @for($m = 0; $m <= 59; $m += 5)
                                    <option value="{{ $m }}" {{ old('terminate_minute', 5) == $m ? 'selected' : '' }}>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                    @endfor
                                </select>
                                <span class="text-info ml-2">Minute</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Serial Start From <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="number" name="serial_start_from" class="form-control" value="{{ old('serial_start_from', 0) }}" min="0" style="width:120px">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Client Code Prefix</label>
                        <div class="col-sm-8">
                            <input type="text" name="client_prefix" class="form-control" value="{{ old('client_prefix') }}" placeholder="like: ABCD" maxlength="20">
                            <small class="text-muted">Auto client code prefix (e.g. ABCD0001)</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">PPP Short Code</label>
                        <div class="col-sm-8">
                            <input type="text" name="ppp_short_code" class="form-control" value="{{ old('ppp_short_code') }}" placeholder="like: sami_" maxlength="30">
                            <small class="text-muted">Auto-added before PPPoE username (e.g. sami_client1)</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Referral Partner</label>
                        <div class="col-sm-8">
                            <select name="agent_id" id="agent_id" class="form-control">
                                <option value="" data-commission="0">--None--</option>
                                @foreach($agents ?? [] as $agent)
                                <option value="{{ $agent->id }}" data-commission="{{ $agent->default_commission ?? 0 }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }} ({{ $agent->default_commission }}%)</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Referral partner will earn commission on this reseller's total bill</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-right">Referral Commission %</label>
                        <div class="col-sm-8">
                            <input type="number" name="referral_commission_percent" id="referral_commission_percent" class="form-control" value="{{ old('referral_commission_percent') }}" step="0.01" min="0" max="100" placeholder="Auto from partner default">
                            <small class="text-muted">Leave blank to use partner's default %. Set custom % for this reseller only.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="bandwidth_section" class="card card-warning card-outline" style="{{ $initialType === 'bandwidth' ? '' : 'display:none;' }}">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i>Bandwidth Allocation &amp; Pricing (Bandwidth Reseller)</h3>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-1"></i>Enter Mbps and rate per Mbps for each category. Monthly bill = Σ (Mbps × Rate). Leave blank if not applicable.</p>

            @php
                $bwCategories = array_values(App\Models\BandwidthCategory::getStandardCategories());
            @endphp

            <table class="table table-bordered table-sm bw-pricing-table">
                <thead class="thead-dark">
                    <tr>
                        <th>Category</th>
                        <th style="width:160px;">Bandwidth (Mbps)</th>
                        <th style="width:160px;">Rate (৳/Mbps)</th>
                        <th style="width:160px;">Monthly Cost (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bwCategories as $cat)
                    @php $catOldMbps = (float)old($cat['mbps_field'], 0); $catOldRate = (float)old($cat['rate_field'], 0); @endphp
                    <tr class="sys-cat-row" id="sys_cat_row_{{ $cat['key'] }}" data-key="{{ $cat['key'] }}" style="{{ ($catOldMbps > 0 || $catOldRate > 0) ? '' : 'display:none;' }}">
                        <td>
                            <i class="{{ $cat['icon'] }} mr-1 text-{{ $cat['color'] }}"></i> {{ $cat['label'] }}
                            <button type="button" class="btn btn-xs btn-outline-danger ml-2 remove-sys-cat float-right"
                                data-key="{{ $cat['key'] }}" data-label="{{ $cat['label'] }}" title="Remove row">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                        <td>
                            <input type="number" name="{{ $cat['mbps_field'] }}" class="form-control form-control-sm bw-mbps"
                                data-key="{{ $cat['key'] }}" value="{{ old($cat['mbps_field']) }}" min="0" step="0.01" placeholder="0">
                        </td>
                        <td>
                            <input type="number" name="{{ $cat['rate_field'] }}" class="form-control form-control-sm bw-rate"
                                data-key="{{ $cat['key'] }}" value="{{ old($cat['rate_field']) }}" min="0" step="0.01" placeholder="0">
                        </td>
                        <td>
                            <span id="bw_subtotal_{{ $cat['key'] }}" class="font-weight-bold">0</span> ৳
                        </td>
                    </tr>
                    @endforeach
                    {{-- Custom Categories --}}
                    @isset($customDbCategories)
                    @foreach($customDbCategories as $ccat)
                    <tr class="custom-cat-row" id="custom_cat_row_{{ $ccat->id }}" data-cat-id="{{ $ccat->id }}" style="display:none;">
                        <td>
                            @if(str_starts_with($ccat->icon, "img:"))
                                <img src="{{ substr($ccat->icon, 4) }}" alt="{{ $ccat->label }}" height="18" style="vertical-align:middle;" class="mr-1">
                            @else
                                <i class="{{ $ccat->icon }} mr-1 text-{{ $ccat->color }}"></i>
                            @endif
                            {{ $ccat->label }}
                            <span class="badge badge-info ml-1" style="font-size:10px;">Custom</span>
                            <button type="button" class="btn btn-xs btn-outline-danger ml-2 remove-custom-cat float-right"
                                data-cat-id="{{ $ccat->id }}" data-label="{{ $ccat->label }}" title="Remove row">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                        <td><input type="number" name="bw_custom[{{ $ccat->id }}][mbps]" class="form-control form-control-sm bw-mbps-custom"
                            data-cat-id="{{ $ccat->id }}" value="{{ old("bw_custom.".$ccat->id.".mbps") }}" min="0" step="0.01" placeholder="0"></td>
                        <td><input type="number" name="bw_custom[{{ $ccat->id }}][rate]" class="form-control form-control-sm bw-rate-custom"
                            data-cat-id="{{ $ccat->id }}" value="{{ old("bw_custom.".$ccat->id.".rate") }}" min="0" step="0.01" placeholder="0"></td>
                        <td><span id="bw_custom_subtotal_{{ $ccat->id }}" class="font-weight-bold">0.00</span></td>
                    </tr>
                    @endforeach
                    @endisset
                </tbody>
                <tfoot>
                    <tr class="bg-warning">
                        <th colspan="3" class="text-right">Total Monthly Bill:</th>
                        <th><span id="bw_total" class="text-dark font-weight-bold">0</span> ৳</th>
                    </tr>
                </tfoot>
            </table>
            <div class="mt-2 mb-1" id="add_custom_cat_wrapper">
                <div class="input-group input-group-sm" style="max-width:380px;">
                    <div class="input-group-prepend"><span class="input-group-text bg-info text-white"><i class="fas fa-plus-circle mr-1"></i> Add Category</span></div>
                    <select id="add_custom_cat_select" class="form-control form-control-sm">
                        <option value="">-- Select category to add --</option>
                        {{-- All system categories --}}
                        @foreach($bwCategories as $cat)
                        @php $catOldMbps2 = (float)old($cat['mbps_field'], 0); $catOldRate2 = (float)old($cat['rate_field'], 0); @endphp
                        @if($catOldMbps2 == 0 && $catOldRate2 == 0)
                        <option value="{{ $cat['key'] }}" data-type="system">{{ $cat['label'] }}</option>
                        @endif
                        @endforeach
                        {{-- Custom categories --}}
                        @isset($customDbCategories)
                        @foreach($customDbCategories as $ccat)
                        @php $cOldMbps = (float)old("bw_custom.{$ccat->id}.mbps", 0); $cOldRate = (float)old("bw_custom.{$ccat->id}.rate", 0); @endphp
                        @if($cOldMbps == 0 && $cOldRate == 0)
                        <option value="{{ $ccat->id }}" data-type="custom">{{ $ccat->label }}</option>
                        @endif
                        @endforeach
                        @endisset
                    </select>
                </div>
            </div>
            <input type="hidden" name="bandwidth_monthly_rate" id="bw_monthly_rate_hidden" value="{{ old('bandwidth_monthly_rate', 0) }}">
        </div>
    </div>

    <div class="card card-warning card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Access Control Permissions</h3>
        </div>
        <div class="card-body">
            @php
                $resellerPermissions = [
                    'reseller.dashboard'     => ['label' => 'Dashboard',          'icon' => 'fa-tachometer-alt',      'desc' => 'View dashboard statistics',                      'mac_only' => false],
                    'reseller.clients'       => ['label' => 'My Clients',         'icon' => 'fa-users',               'desc' => 'View and manage assigned clients',                'mac_only' => true],
                    'reseller.client_edit'   => ['label' => 'Client Edit',        'icon' => 'fa-user-edit',           'desc' => 'Edit client details and information',             'mac_only' => true],
                    'reseller.client_restore'=> ['label' => 'Client Restore',     'icon' => 'fa-undo',                'desc' => 'Restore deleted clients from trash',              'mac_only' => true],
                    'reseller.device_control'=> ['label' => 'Device Control',     'icon' => 'fa-server',              'desc' => 'Enable/disable client connections on MikroTik',  'mac_only' => true],
                    'reseller.wallet'        => ['label' => 'Wallet',             'icon' => 'fa-wallet',              'desc' => 'View wallet balance and transactions',             'mac_only' => false],
                    'reseller.packages'      => ['label' => 'My Packages',        'icon' => 'fa-box',                 'desc' => 'View available packages and pricing',             'mac_only' => true],
                    'reseller.collection'    => ['label' => 'Collection Report',  'icon' => 'fa-chart-line',          'desc' => 'View collection reports',                         'mac_only' => false],
                    'reseller.due_report'    => ['label' => 'Due Report',         'icon' => 'fa-exclamation-triangle','desc' => 'View client dues',                                'mac_only' => true],
                    'reseller.tickets'       => ['label' => 'Tickets',            'icon' => 'fa-ticket-alt',          'desc' => 'View and manage support tickets',                 'mac_only' => false],
                ];
                $roleDefaults = \Spatie\Permission\Models\Role::where('name','reseller')->first()?->permissions->pluck('name')->toArray() ?? array_keys($resellerPermissions);
            @endphp
            <p class="text-muted small mb-2"><i class="fas fa-info-circle mr-1"></i> Pre-filled from default role settings. Customize as needed for this reseller.</p>
            <div id="bandwidth_perm_note" class="alert alert-warning py-2 mb-2" style="{{ $initialType === 'bandwidth' ? '' : 'display:none;' }}">
                <i class="fas fa-info-circle mr-1"></i>
                Bandwidth resellers do not manage clients — only <strong>Dashboard</strong>, <strong>Wallet</strong>, and <strong>Tickets</strong> are available.
            </div>
            <div class="row">
                @foreach($resellerPermissions as $permName => $perm)
                <div class="col-md-6 mb-3 perm-item {{ $perm['mac_only'] ? 'mac-only-perm' : '' }}">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="perm_{{ str_replace('.', '_', $permName) }}"
                               name="permissions[]" value="{{ $permName }}"
                               {{ in_array($permName, old('permissions', $roleDefaults)) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="perm_{{ str_replace('.', '_', $permName) }}">
                            <i class="fas {{ $perm['icon'] }} mr-1 text-warning"></i> {{ $perm['label'] }}
                            @if($perm['mac_only'])<span class="badge badge-secondary ml-1" style="font-size:10px;">MAC Only</span>@endif
                        </label>
                        <br><small class="text-muted ml-4">{{ $perm['desc'] }}</small>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-outline-success" id="btn_select_all">
                    <i class="fas fa-check-double mr-1"></i>Select All
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="$('.perm-item:visible input[type=checkbox]').prop('checked', false)">
                    <i class="fas fa-times mr-1"></i>Deselect All
                </button>
            </div>
        </div>
    </div>


    <div id="bandwidth_portal_permissions" class="card card-outline mb-3" data-bw-perm style="border-color:#6f42c1;">
        <div class="card-header" style="border-color:#6f42c1;">
            <h3 class="card-title"><i class="fas fa-sliders-h mr-2" style="color:#6f42c1;"></i>Bandwidth Portal Permission Control</h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3" style="font-size:13px;"><i class="fas fa-info-circle mr-1"></i>Select which features this reseller can see in their portal. Unchecked items will be <strong>hidden and blocked.</strong></p>
            @php
                $bwFeatureGroupsCreate = [
                    "Bandwidth Portal Visibility" => [
                        "view_bw_amount"      => "View Bandwidth Amounts (Mbps)",
                        "view_bw_price"       => "View Bandwidth Pricing and Rates",
                        "view_billing_ledger" => "View Billing Ledger (Bill History)",
                        "view_adjustments"    => "View Mid-Month Adjustments",
                        "view_payments"       => "View Recent Payments",
                    ],
                    "Wallet and Finance" => [
                        "wallet" => "Wallet Access and Recharge",
                    ],
                ];
            @endphp
            <div class="row">
                @foreach($bwFeatureGroupsCreate as $groupName => $groupFeatures)
                <div class="col-md-6 mb-3">
                    <div class="card card-outline card-secondary h-100">
                        <div class="card-header py-2" style="background:#f8f9fa;">
                            <h6 class="card-title mb-0"><i class="fas fa-layer-group mr-1" style="color:#6f42c1;"></i><strong>{{ $groupName }}</strong></h6>
                        </div>
                        <div class="card-body py-2">
                            @foreach($groupFeatures as $featureKey => $featureLabel)
                            @if(isset($availableFeatures[$featureKey]))
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="feat_create_{{ $featureKey }}" name="features[]" value="{{ $featureKey }}" checked>
                                <label class="custom-control-label" for="feat_create_{{ $featureKey }}">
                                    <span style="font-size:13px;">{{ $featureLabel }}</span>
                                    <br><small class="text-muted" style="font-size:11px;">Key: <code>{{ $featureKey }}</code></small>
                                </label>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-2 p-2" style="background:#f8f9fa;border-radius:8px;">
                <button type="button" class="btn btn-sm btn-outline-success mr-1" id="bwPermEnableAll"><i class="fas fa-check-double mr-1"></i> Enable All</button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="bwPermDisableAll"><i class="fas fa-times mr-1"></i> Disable All</button>
            </div>
        </div>
    </div>

    <div class="text-center mb-4">
        <button type="submit" class="btn btn-primary btn-lg" id="submit_btn"><i class="fas fa-save mr-2"></i>Save <span id="submit_type_label">MAC</span> Reseller</button>
        <a href="{{ route('admin.reseller.index') }}" class="btn btn-secondary btn-lg ml-2">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function(){
    function toggleResellerType() {
        var isBandwidth = $('select[name=type]').val() === 'bandwidth';
        var typeLabel = isBandwidth ? 'Bandwidth' : 'MAC';
        $('#bandwidth_section').toggle(isBandwidth);
        $('#mac_billing_section').toggle(!isBandwidth);
        // Update dynamic labels
        $('#reseller_type_label').text(typeLabel);
        $('#submit_type_label').text(typeLabel);
        if (isBandwidth) {
            $('.mac-only-perm').hide().find('input[type=checkbox]').prop('checked', false);
            $('#bandwidth_perm_note').show();
            $('#bandwidth_portal_permissions').show();
        } else {
            $('.mac-only-perm').show();
            $('#bandwidth_perm_note').hide();
            $('#bandwidth_portal_permissions').hide().find('input[name="features[]"]').prop('checked', false);
        }
    }
    $('select[name=type]').on('change', toggleResellerType);
    toggleResellerType();

    $('#btn_select_all').on('click', function() {
        $('input[name="permissions[]"]').filter(':visible').prop('checked', true);
    });

    $('#bwPermEnableAll').on('click', function() {
        $('#bandwidth_portal_permissions input[name="features[]"]').prop('checked', true);
    });
    $('#bwPermDisableAll').on('click', function() {
        $('#bandwidth_portal_permissions input[name="features[]"]').prop('checked', false);
    });

    // Bandwidth pricing auto-calculator
    function recalcBandwidth() {
        var total = 0;
        $('.sys-cat-row:visible .bw-mbps').each(function() {
            var key  = $(this).data('key');
            var mbps = parseFloat($(this).val()) || 0;
            var rate = parseFloat($('.bw-rate[data-key="' + key + '"]').val()) || 0;
            var sub  = mbps * rate;
            total += sub;
            $('#bw_subtotal_' + key).text(sub.toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2}));
        });
        $('.custom-cat-row:visible .bw-mbps-custom').each(function() {
            var catId = $(this).data('cat-id');
            var mbps  = parseFloat($(this).val()) || 0;
            var rate  = parseFloat($('.bw-rate-custom[data-cat-id="' + catId + '"]').val()) || 0;
            var sub   = mbps * rate;
            total += sub;
            $('#bw_custom_subtotal_' + catId).text(sub.toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2}));
        });
        $('#bw_total').text(total.toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2}));
        $('#bw_monthly_rate_hidden').val(total.toFixed(2));
    }
    $(document).on('input', '.bw-mbps, .bw-rate, .bw-mbps-custom, .bw-rate-custom', recalcBandwidth);
    recalcBandwidth();
    $('#add_custom_cat_select').on('change', function() {
        var val = $(this).val(); if (!val) return;
        var type = $(this).find('option[value="' + val + '"]').data('type');
        if (type === 'system') { $('#sys_cat_row_' + val).show(); }
        else { $('#custom_cat_row_' + val).show(); }
        $(this).find('option[value="' + val + '"]').remove();
        $(this).val(''); recalcBandwidth();
    });
    // Remove system category row
    $(document).on('click', '.remove-sys-cat', function() {
        var key = $(this).data('key'), label = $(this).data('label');
        $('#sys_cat_row_' + key).hide();
        $('.bw-mbps[data-key="' + key + '"]').val('');
        $('.bw-rate[data-key="' + key + '"]').val('');
        $('#bw_subtotal_' + key).text('0.00');
        var $sel = $('#add_custom_cat_select');
        if ($sel.length) $sel.append('<option value="' + key + '" data-type="system">' + label + '</option>');
        recalcBandwidth();
    });
    $(document).on('click', '.remove-custom-cat', function() {
        var catId = $(this).data('cat-id'), label = $(this).data('label');
        $('#custom_cat_row_' + catId).hide();
        $('input[name="bw_custom[' + catId + '][mbps]"]').val('');
        $('input[name="bw_custom[' + catId + '][rate]"]').val('');
        $('#bw_custom_subtotal_' + catId).text('0.00');
        var $sel = $('#add_custom_cat_select');
        if ($sel.length) $sel.append('<option value="' + catId + '" data-type="custom">' + label + '</option>');
        recalcBandwidth();
    });
});
</script>
@endpush
