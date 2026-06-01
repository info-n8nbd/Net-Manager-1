@extends('layouts.admin')

@section('title', 'BTRC Official Format - Clients List')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.btrc.index') }}">BTRC Reports</a></li>
<li class="breadcrumb-item active">Official Format</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-file-excel mr-2 text-success"></i>
            BTRC Official Clients List Format
        </h3>
        <div class="card-tools">
            <a href="{{ route('admin.btrc.official-format', array_merge(request()->all(), ['export' => 'excel'])) }}"
               class="btn btn-primary btn-sm mr-1">
                <i class="fas fa-file-excel mr-1"></i> Export Excel (.xlsx)
            </a>
            <a href="{{ route('admin.btrc.official-format', array_merge(request()->all(), ['export' => 'csv'])) }}"
               class="btn btn-success btn-sm">
                <i class="fas fa-file-csv mr-1"></i> Export CSV
            </a>
        </div>
    </div>
    <div class="card-body">

        {{-- Filter Form --}}
        <form method="GET" class="mb-3" id="btrcFilterForm">
            <div class="row">
                                        {{-- District --}}
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label>District</label>
                        <select name="district_id" class="form-control select2" id="districtSelect"
                                onchange="filterThanaByDistrict(this.value)">
                            <option value="">All Districts</option>
                            @foreach($districts as $dist)
                                <option value="{{ $dist->id }}"
                                    {{ ($filters['district_id'] ?? '') == $dist->id ? 'selected' : '' }}
                                    data-division="{{ $dist->division }}">
                                    {{ $dist->name }}
                                    @if($dist->division) ({{ $dist->division }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>



                {{-- Thana / Upazila --}}
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label>Thana / Upazila</label>
                        <select name="upazila_id" class="form-control select2" id="thanaSelect">
                            <option value="">All Thana</option>
                            @foreach($upazilas as $upa)
                                <option value="{{ $upa->id }}"
                                    data-district="{{ $upa->district_id }}"
                                    {{ ($filters['upazila_id'] ?? '') == $upa->id ? 'selected' : '' }}>
                                    {{ $upa->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>



                {{-- Zone --}}
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control select2">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ ($filters['zone_id'] ?? '') == $zone->id ? 'selected' : '' }}>
                                    {{ $zone->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>




                {{-- Client Source --}}
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="font-weight-bold">Client Source</label>
                        <select name="client_source" class="form-control" id="clientSourceSelect"
                                onchange="toggleResellerSelect(this.value)">
                            <option value="all"       {{ ($filters['client_source'] ?? 'all') === 'all'       ? 'selected' : '' }}>All Clients</option>
                            <option value="own"       {{ ($filters['client_source'] ?? '')    === 'own'       ? 'selected' : '' }}>Company's Own Clients</option>
                            <option value="mac"       {{ ($filters['client_source'] ?? '')    === 'mac'       ? 'selected' : '' }}>MAC Reseller Clients</option>
                            <option value="bandwidth" {{ ($filters['client_source'] ?? '')    === 'bandwidth' ? 'selected' : '' }}>Bandwidth Reseller Clients</option>
                            <option value="specific"  {{ ($filters['client_source'] ?? '')    === 'specific'  ? 'selected' : '' }}>Specific Reseller</option>
                        </select>
                    </div>
                </div>



                {{-- Specific Reseller (shown when source = specific) --}}
                <div class="col-md-3" id="specificResellerWrap"
                     style="{{ ($filters['client_source'] ?? 'all') === 'specific' ? '' : 'display:none' }}">
                    <div class="form-group mb-2">
                        <label class="font-weight-bold">Select Reseller</label>
                        <select name="specific_reseller_id" class="form-control select2" id="specificResellerSelect">
                            <option value="">-- Select Reseller --</option>
                            @foreach($resellers as $rs)
                                <option value="{{ $rs->id }}"
                                    {{ ($filters['specific_reseller_id'] ?? '') == $rs->id ? 'selected' : '' }}>
                                    [{{ strtoupper($rs->type) }}] {{ $rs->name }}
                                    ({{ $rs->reseller_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>



                {{-- Status --}}
                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="all"      {{ ($filters['status'] ?? 'all') === 'all'      ? 'selected' : '' }}>All</option>
                            <option value="active"   {{ ($filters['status'] ?? '') === 'active'   ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>



                {{-- Date Range --}}
                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label>From</label>
                        <input type="date" name="from_date" class="form-control"
                               value="{{ $filters['from_date'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label>To</label>
                        <input type="date" name="to_date" class="form-control"
                               value="{{ $filters['to_date'] ?? '' }}">
                    </div>
                </div>



                {{-- Apply --}}
                <div class="col-md-1">
                    <div class="form-group mb-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        @php
            $sourceLabels = [
                'all'       => 'All Clients',
                'own'       => "Company's Own Clients",
                'mac'       => 'MAC Reseller Clients',
                'bandwidth' => 'Bandwidth Reseller Clients',
                'specific'  => 'Specific Reseller',
            ];
            $currentSource      = $filters['client_source'] ?? 'all';
            $currentSourceLabel = $sourceLabels[$currentSource] ?? 'All Clients';
            $specificResellerId = $filters['specific_reseller_id'] ?? null;
            if ($currentSource === 'specific' && $specificResellerId) {
                $specificRes = $resellers->firstWhere('id', $specificResellerId);
                if ($specificRes) {
                    $currentSourceLabel = 'Reseller: ' . $specificRes->name . ' (' . strtoupper($specificRes->type) . ')';
                }
            }
        @endphp

        {{-- Info bar --}}
        <div class="alert alert-info d-flex align-items-center justify-content-between">
            <div>
                <i class="fas fa-info-circle mr-2 fa-lg"></i>
                Total Records: <strong>{{ count($data) }}</strong>
                &nbsp;&bull;&nbsp;
                <span class="badge badge-secondary">{{ $currentSourceLabel }}</span>
                @if(count($data) > 0)
                    &nbsp;&bull;&nbsp;
                    <a href="{{ route('admin.btrc.official-format', array_merge(request()->all(), ['export' => 'csv'])) }}"
                       class="font-weight-bold text-success">
                        <i class="fas fa-download mr-1"></i>Download CSV for BTRC submission
                    </a>
                @endif
            </div>
            @if(count($data) > 0)
            <div class="d-flex gap-2">
                <a href="{{ route('admin.btrc.official-format', array_merge(request()->only(['zone_id','from_date','to_date','status']), ['client_source'=>'own', 'export'=>'csv'])) }}"
                   class="btn btn-sm btn-success mr-2" title="Download: Own clients only" style="text-decoration:none">
                    <i class="fas fa-download mr-1"></i>Own Only
                </a>
                <a href="{{ route('admin.btrc.official-format', array_merge(request()->only(['zone_id','from_date','to_date','status']), ['client_source'=>'mac', 'export'=>'csv'])) }}"
                   class="btn btn-sm btn-info mr-2" title="Download: MAC reseller clients only" style="text-decoration:none">
                    <i class="fas fa-download mr-1"></i>MAC Reseller
                </a>
                <a href="{{ route('admin.btrc.official-format', array_merge(request()->only(['zone_id','from_date','to_date','status']), ['client_source'=>'bandwidth', 'export'=>'csv'])) }}"
                   class="btn btn-sm btn-warning" title="Download: Bandwidth reseller clients only" style="text-decoration:none">
                    <i class="fas fa-download mr-1"></i>BW Reseller
                </a>
            </div>
            @endif
        </div>

        @if(empty($data) && !request()->anyFilled(['zone_id','from_date','to_date','status','district_id','upazila_id']))
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Use the filters above and click <strong>Apply Filter</strong> to preview data,
            or directly click <strong>Export Excel / Export CSV</strong> to download all clients.
        </div>
        @endif

        {{-- Table Preview --}}
        @if(count($data) > 0)
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm" style="font-size:12px">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>#</th>
                        <th>client_type</th>
                        <th>connection_type</th>
                        <th>client_name</th>
                        <th>bandwidth_distribution_point</th>
                        <th>connectivity_type</th>
                        <th>activation_date</th>
                        <th>bandwidth_allocation</th>
                        <th>allocated_ip</th>
                        <th>division</th>
                        <th>district</th>
                        <th>thana</th>
                        <th>address</th>
                        <th>client_mobile</th>
                        <th>client_email</th>
                        <th>selling_price_bdt_excluding_vat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row['client_type'] }}</td>
                        <td>{{ $row['connection_type'] }}</td>
                        <td>{{ $row['client_name'] }}</td>
                        <td>{{ $row['bandwidth_distribution_point'] }}</td>
                        <td>{{ $row['connectivity_type'] }}</td>
                        <td>{{ $row['activation_date'] }}</td>
                        <td>{{ $row['bandwidth_allocation'] }} Mbps</td>
                        <td><code>{{ $row['allocated_ip'] }}</code></td>
                        <td>{{ $row['division'] }}</td>
                        <td>{{ $row['district'] }}</td>
                        <td>{{ $row['thana'] }}</td>
                        <td>{{ Str::limit($row['address'], 35) }}</td>
                        <td>{{ $row['client_mobile'] }}</td>
                        <td>{{ $row['client_email'] ?: '-' }}</td>
                        <td>{{ $row['selling_price_bdt_excluding_vat'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@push('scripts')
<script>

function filterThanaByDistrict(districtId) {
    var sel = document.getElementById('thanaSelect');
    var opts = sel.querySelectorAll('option');
    opts.forEach(function(o) {
        if (!o.value) { o.style.display = ''; return; }
        o.style.display = (!districtId || o.dataset.district == districtId) ? '' : 'none';
    });
    if (sel.value && sel.selectedOptions[0] && districtId &&
        sel.selectedOptions[0].dataset.district != districtId) {
        sel.value = '';
    }
}
function toggleResellerSelect(val) {
    var wrap = document.getElementById('specificResellerWrap');
    if (val === 'specific') {
        wrap.style.display = '';
        // Try to init select2 if available
        try { $('#specificResellerSelect').select2(); } catch(e) {}
    } else {
        wrap.style.display = 'none';
    }
}
// On page load, ensure correct state
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('clientSourceSelect');
    if (sel) toggleResellerSelect(sel.value);
    var d = document.getElementById('districtSelect');
    if (d && d.value) filterThanaByDistrict(d.value);
});
</script>
@endpush
@endsection
