@extends('layouts.admin')

@section('title', 'Inactive Client Inventory History')


@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.stock.summary') }}">Inventory</a></li>
<li class="breadcrumb-item active">Inactive Client History</li>

{{-- Recover Fiber Modal --}}
<div class="modal fade" id="recoverFiberModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" style="font-size:13px;"><i class="fas fa-project-diagram text-success mr-1"></i>Recover Fiber / Cable</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="recoverFiberForm" method="POST">
                @csrf
                <div class="modal-body py-2">
                    <p class="text-muted" style="font-size:12px;">Recovering: <strong id="recoverFiberItemName"></strong></p>
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;"><i class="fas fa-user-tie mr-1 text-info"></i>Collected By (Staff/Employee)</label>
                        <select name="staff_id" class="form-control form-control-sm">
                            <option value="">— Select Staff —</option>
                            @foreach($staffUsers as $su)
                            <option value="{{ $su->id }}">{{ $su->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:12px;font-weight:600;">Remarks <span class="text-muted">(optional)</span></label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Any notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check-circle mr-1"></i>Confirm Recovery</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openRecoverFiberModal(url, label) {
    document.getElementById("recoverFiberItemName").textContent = label;
    document.getElementById("recoverFiberForm").action = url;
    jQuery("#recoverFiberModal").modal("show");
}
</script>

{{-- Recover Instrument Modal --}}
<div class="modal fade" id="recoverModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" style="font-size:13px;"><i class="fas fa-check-circle text-success mr-1"></i>Mark as Recovered</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="recoverForm" method="POST">
                @csrf
                <div class="modal-body py-2">
                    <p class="text-muted" style="font-size:12px;">Recovering: <strong id="recoverItemName"></strong></p>
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;"><i class="fas fa-user-tie mr-1 text-info"></i>Collected By (Staff/Employee)</label>
                        <select name="staff_id" class="form-control form-control-sm">
                            <option value="">— Select Staff —</option>
                            @foreach($staffUsers as $su)
                            <option value="{{ $su->id }}">{{ $su->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;">Condition</label>
                        <select name="condition" class="form-control form-control-sm">
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="damaged">Damaged</option>
                            <option value="faulty">Faulty</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:12px;font-weight:600;">Remarks <span class="text-muted">(optional)</span></label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Any notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check-circle mr-1"></i>Confirm Recovery</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openRecoverModal(id, name) {
    document.getElementById('recoverItemName').textContent = name;
    document.getElementById('recoverForm').action = '{{ url("admin/inventory/inactive-client-history/recover-instrument") }}/' + id;
    $('#recoverModal').modal('show');
}
</script>
@endsection

@section('content')

{{-- Summary Info Boxes --}}
@php
    $totalWithProducts = 0;
    $totalWithFiber    = 0;
    $totalFreeClients  = 0;
    $totalNeedsCollect = 0;
    foreach ($inactiveClients as $c) {
        $ci  = $assignedInstruments[$c->id] ?? collect();
        $cfh = $fiberHistories[$c->id] ?? collect();
        $cfu = $fiberUsages[$c->id] ?? collect();
        if ($ci->count() > 0) $totalWithProducts++;
        if ($cfh->count() + $cfu->count() > 0) $totalWithFiber++;
        if (!empty($c->is_free)) $totalFreeClients++;
        if (!empty($c->is_free) && ($ci->count() > 0 || $cfh->count() + $cfu->count() > 0)) $totalNeedsCollect++;
    }
@endphp

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $inactiveClients->total() }}</h3>
                <p>Total Inactive</p>
            </div>
            <div class="icon"><i class="fas fa-user-xmark"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $totalWithProducts }}</h3>
                <p>With Products</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $totalWithFiber }}</h3>
                <p>With Fiber</p>
            </div>
            <div class="icon"><i class="fas fa-network-wired"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalFreeClients }}</h3>
                <p>Free Install</p>
            </div>
            <div class="icon"><i class="fas fa-gift"></i></div>
        </div>
    </div>
</div>

{{-- Info Alert --}}
<div class="alert alert-info py-2 mb-3">
    <i class="fas fa-info-circle mr-1"></i>
    This page shows <strong>inactive clients</strong> with their assigned products/serials and fiber/cable history — so they can be collected/recovered.
</div>

{{-- Filter Card --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter</h3>
        <div class="card-tools">
            <a href="{{ route('admin.inventory.inactive-client-history.export', request()->query()) }}" class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-csv mr-1"></i>Export CSV
            </a>
        </div>
    </div>
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="inactiveSearch" class="form-control" placeholder="Name, ID, phone, product, serial/MAC..." value="{{ request('search') }}" autocomplete="off">
                    @if(request('search'))
                    <div class="input-group-append">
                        <a href="{{ route('admin.inventory.inactive-client-history', array_merge(array_filter(request()->except('search','page')), [])) }}" class="btn btn-outline-secondary" title="Clear search"><i class="fas fa-times"></i></a>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-md-3 mt-2 mt-md-0">
                @php $freeOnly = request('free_only') === '1'; @endphp
                <a href="{{ route('admin.inventory.inactive-client-history', array_merge(request()->except('free_only','page'), $freeOnly ? [] : ['free_only'=>'1'])) }}"
                   class="btn btn-sm {{ $freeOnly ? 'btn-warning' : 'btn-outline-warning' }} btn-block">
                    <i class="fas fa-gift mr-1"></i>
                    Free Install Only
                    @if(!$freeOnly && ($freeCount ?? 0) > 0)
                        <span class="badge badge-dark ml-1">{{ $freeCount }}</span>
                    @elseif($freeOnly)
                        <i class="fas fa-times-circle ml-1"></i>
                    @endif
                </a>
            </div>
            <div class="col-md-3 mt-2 mt-md-0">
                <small class="text-muted">
                    @if($freeOnly) Showing <strong>free-install only</strong> — @endif
                    <strong>{{ $inactiveClients->total() }}</strong> inactive clients
                    @if($freeOnly) (filtered) @endif
                </small>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var inp = document.getElementById('inactiveSearch');
    if (!inp) return;
    var timer;
    inp.addEventListener('input', function() {
        clearTimeout(timer);
        var val = this.value;
        timer = setTimeout(function() {
            var params = new URLSearchParams(window.location.search);
            if (val.trim()) { params.set('search', val.trim()); } else { params.delete('search'); }
            params.delete('page');
            window.location = window.location.pathname + '?' + params.toString();
        }, 400);
    });
    inp.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.location = '{{ route("admin.inventory.inactive-client-history") }}';
    });
})();
</script>

{{-- Client List --}}
@forelse($inactiveClients as $client)
@php
    $clientInstruments  = $assignedInstruments[$client->id] ?? collect();
    $clientFiberHistory = $fiberHistories[$client->id] ?? collect();
    $clientFiberUsages  = $fiberUsages[$client->id] ?? collect();
    $totalFiber = $clientFiberHistory->count() + $clientFiberUsages->count();
    $isManualHold = $client->auto_inactive_note
        && !str_contains($client->auto_inactive_note, 'due payment')
        && !str_contains($client->auto_inactive_note, 'Auto-expired')
        && !str_contains($client->auto_inactive_note, 'Inactive for');
    $isFreeClient    = !empty($client->is_free);
    $hasAssigned     = $clientInstruments->count() > 0 || $totalFiber > 0;
    $needsCollection = $isFreeClient && $hasAssigned;
    $cardColor = $needsCollection ? 'warning' : ($hasAssigned ? 'danger' : 'secondary');
@endphp

<div class="card card-outline card-{{ $cardColor }} mb-2">
    {{-- Card Header --}}
    <div class="card-header py-2">
        <div class="d-flex align-items-center flex-wrap" style="gap:5px;">
            <span class="badge badge-danger">Inactive</span>
            @if($isFreeClient)
            <span class="badge badge-warning"><i class="fas fa-gift"></i> FREE</span>
            @endif
            @if($isManualHold)
            <span class="badge badge-secondary" title="{{ $client->auto_inactive_note }}"><i class="fas fa-hand-paper mr-1"></i>Manual Hold</span>
            @endif
            <strong>{{ $client->name }}</strong>
            <span class="text-muted" style="font-size:12px;">{{ $client->client_id }}</span>
            @if($client->username)
            <span class="badge badge-info"><i class="fas fa-network-wired"></i> {{ $client->username }}</span>
            @endif
            @if($isFreeClient && $client->free_reason)
            <span class="badge badge-light border" title="{{ $client->free_reason }}"><i class="fas fa-comment-alt mr-1"></i>{{ Str::limit($client->free_reason, 40) }}</span>
            @endif
        </div>
        <div class="card-tools d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
            @if($client->phone)<small class="text-muted"><i class="fas fa-phone mr-1"></i>{{ $client->phone }}</small>@endif
            @if($client->zone)<small class="text-muted"><i class="fas fa-map-marker-alt mr-1"></i>{{ $client->zone->name }}</small>@endif
            @if($client->package)<small class="text-muted"><i class="fas fa-cube mr-1"></i>{{ $client->package->name }}</small>@endif
            <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-xs btn-default"><i class="fas fa-external-link-alt mr-1"></i>Profile</a>
        </div>
    </div>

    {{-- Needs-collection / free-collected alert strip --}}
    @if($needsCollection)
    <div class="alert alert-warning py-1 mb-0 px-3" style="border-radius:0;border-left:none;border-right:none;border-top:none;">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong>Needs Collection:</strong> Free client — has
        {{ $clientInstruments->count() > 0 ? $clientInstruments->count().' product(s)' : '' }}{{ $clientInstruments->count() > 0 && $totalFiber > 0 ? ' & ' : '' }}{{ $totalFiber > 0 ? $totalFiber.' fiber record(s)' : '' }} to collect!
    </div>
    @elseif($isFreeClient && !$hasAssigned)
    <div class="alert alert-success py-1 mb-0 px-3" style="border-radius:0;border-left:none;border-right:none;border-top:none;">
        <i class="fas fa-check-circle mr-1"></i>
        Free client — no products or fiber assigned (already collected / never assigned)
    </div>
    @endif

    {{-- Card Body: Products + Fiber side by side --}}
    <div class="card-body p-0">
        <div class="row no-gutters">

            {{-- PRODUCTS --}}
            <div class="col-md-6" style="border-right:1px solid #dee2e6;">
                <div class="p-2">
                    <h6 class="mb-2" style="font-size:12px;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">
                        <i class="fas fa-boxes mr-1 text-primary"></i> Products
                        <span class="badge badge-primary ml-1">{{ $clientInstruments->count() }}</span>
                    </h6>
                    @if($clientInstruments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Serial / MAC</th>
                                    <th>Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientInstruments as $inst)
                                <tr>
                                    <td>
                                        <strong style="font-size:12px;">{{ $inst->category }}@if($inst->sub_category) &rsaquo; {{ $inst->sub_category }}@endif</strong>
                                        @if($inst->brand || $inst->model)
                                        <br><small class="text-muted">{{ $inst->brand }}{{ $inst->model ? ' · '.$inst->model : '' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inst->serial_number)
                                        <code style="font-size:11px;">{{ $inst->serial_number }}</code>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="font-size:11px;white-space:nowrap;">{{ $inst->updated_at ? $inst->updated_at->format('d M Y') : '—' }}</td>
                                    <td class="text-center">
                                        <button type="button"
                                            onclick="openRecoverModal({{ $inst->id }}, '{{ addslashes($inst->name) }}{{ $inst->serial_number ? ' — '.$inst->serial_number : '' }}')"
                                            class="btn btn-xs btn-success">
                                            <i class="fas fa-check-circle mr-1"></i>Recover
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0" style="font-size:12px;font-style:italic;"><i class="fas fa-box-open mr-1"></i>No products assigned</p>
                    @endif
                </div>
            </div>

            {{-- FIBER / CABLE --}}
            <div class="col-md-6">
                <div class="p-2">
                    <h6 class="mb-2" style="font-size:12px;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">
                        <i class="fas fa-network-wired mr-1 text-success"></i> Fiber / Cable
                        <span class="badge badge-success ml-1">{{ $totalFiber }}</span>
                    </h6>
                    @if($clientFiberHistory->count() > 0 || $clientFiberUsages->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fiber Code</th>
                                    <th>Type</th>
                                    <th>Used</th>
                                    <th>Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientFiberHistory as $fh)
                                @php
                                    preg_match('/Out:\s*([\d.]+)\s*m/i', $fh->details ?? '', $m);
                                    $usedM = $m[1] ?? '—';
                                    preg_match('/Out:\s*([\d.]+)\s*m/i', $fh->details ?? '', $rM);
                                    $rMeter = $rM[1] ?? ($fh->meter ?? 0);
                                @endphp
                                <tr>
                                    <td><code style="font-size:11px;">{{ $fh->fiber_code ?? '—' }}</code></td>
                                    <td style="font-size:12px;">
                                        {{ $fh->fiber_type ?? '—' }}
                                        @if($fh->core)<small class="text-muted"> · {{ $fh->core }}</small>@endif
                                    </td>
                                    <td style="font-size:12px;white-space:nowrap;">{{ $usedM }} m</td>
                                    <td style="font-size:11px;white-space:nowrap;">{{ $fh->date ? \Carbon\Carbon::parse($fh->date)->format('d M Y') : '—' }}</td>
                                    <td class="text-center">
                                        <button type="button"
                                            onclick="openRecoverFiberModal('{{ url('admin/inventory/inactive-client-history/recover-fiber-history') }}/' + {{ $fh->id }}, '{{ addslashes($fh->fiber_code ?? 'Fiber') }} — {{ $rMeter }} m')"
                                            class="btn btn-xs btn-success">
                                            <i class="fas fa-check-circle mr-1"></i>Recover
                                        </button>
                                    </td>
                                </tr>
                                @endforeach

                                @foreach($clientFiberUsages as $fu)
                                <tr>
                                    <td><code style="font-size:11px;">{{ $fu->fiberStock->fiber_code ?? '—' }}</code></td>
                                    <td style="font-size:12px;">{{ $fu->fiberStock->fiber_type ?? '—' }}</td>
                                    <td style="font-size:12px;white-space:nowrap;">{{ number_format($fu->used_meter ?? 0, 1) }} m</td>
                                    <td style="font-size:11px;white-space:nowrap;">{{ $fu->used_date ? \Carbon\Carbon::parse($fu->used_date)->format('d M Y') : '—' }}</td>
                                    <td class="text-center">
                                        <button type="button"
                                            onclick="openRecoverFiberModal('{{ url('admin/inventory/inactive-client-history/recover-fiber-usage') }}/' + {{ $fu->id }}, '{{ addslashes($fu->fiberStock->fiber_code ?? 'Fiber') }} — {{ number_format($fu->used_meter ?? 0, 1) }} m')"
                                            class="btn btn-xs btn-success">
                                            <i class="fas fa-check-circle mr-1"></i>Recover
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0" style="font-size:12px;font-style:italic;"><i class="fas fa-plug mr-1"></i>No fiber records</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@empty
<div class="text-center text-muted py-5">
    <i class="fas fa-user-xmark fa-3x mb-3 d-block" style="color:#ced4da;"></i>
    <p>No inactive clients found{{ request('search') ? ' matching "'.request('search').'"' : '' }}.</p>
</div>
@endforelse

{{-- Pagination --}}
<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        <label class="small text-muted mr-2">SHOW</label>
        <select class="form-control form-control-sm d-inline-block" style="width:80px;" onchange="var p=new URLSearchParams(window.location.search);p.set('per_page',this.value);p.delete('page');window.location=window.location.pathname+'?'+p.toString();">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
        </select>
        <label class="small text-muted ml-2">per page — {{ $inactiveClients->total() }} total</label>
    </div>
    {{ $inactiveClients->appends(request()->query())->links() }}
</div>

@endsection
