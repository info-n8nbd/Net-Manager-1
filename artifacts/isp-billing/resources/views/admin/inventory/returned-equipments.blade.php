@extends('layouts.admin')
@section('title', 'Returned Products and Fiber' . ($pendingCount > 0 ? ' (' . $pendingCount . ' Pending)' : ''))
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.returned-equipments') }}">Inventory</a></li>
<li class="breadcrumb-item active">Returned Products &amp; Fiber</li>


{{-- Restock Modal --}}
<div class="modal fade" id="restockModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" style="font-size:13px;"><i class="fas fa-undo-alt text-success mr-1"></i>Restock to Inventory</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="restockForm" method="POST">
                @csrf
                <div class="modal-body py-2">
                    <p style="font-size:12px;" class="text-muted">Adding <strong id="restockItemName"></strong> back to inventory stock.</p>
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;">Restock Category</label>
                        <select name="instrument_category" id="restockCategory" class="form-control form-control-sm">
                            <option value="">-- Select Category --</option>
                            @foreach($instrumentCategories as $cat)
                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                            @if($cat->children->count())
                                @foreach($cat->children as $child)
                                <option value="{{ $child->name }}">&nbsp;&nbsp;↳ {{ $child->name }}</option>
                                @endforeach
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;">Condition after return</label>
                        <select name="condition" id="restockCondition" class="form-control form-control-sm">
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="damaged">Damaged</option>
                            <option value="faulty">Faulty</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:12px;font-weight:600;">Notes <span class="text-muted">(optional)</span></label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Any restock notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i>Confirm Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openRestockModal(id, name, serial, condition) {
    document.getElementById('restockItemName').textContent = name + (serial && serial !== 'N/A' ? ' (S/N: ' + serial + ')' : '');
    var sel = document.getElementById('restockCondition');
    for (var i = 0; i < sel.options.length; i++) { if (sel.options[i].value === condition) { sel.selectedIndex = i; break; } }
    // Auto-suggest category from equipment name
    var catSel = document.getElementById('restockCategory');
    if(catSel){ catSel.selectedIndex = 0; }
    document.getElementById('restockForm').action = '{{ url("admin/inventory/returned-equipments") }}/' + id + '/restock';
    $('#restockModal').modal('show');
}
function openReassignModal(id, name, serial) {
    document.getElementById('reassignItemName').textContent = name + (serial && serial !== 'N/A' ? ' (S/N: ' + serial + ')' : '');
    document.getElementById('reassignForm').action = '{{ url("admin/inventory/returned-equipments") }}/' + id + '/assign';
    $('#reassignModal').modal('show');
}
</script>

{{-- Reassign/Assign Modal --}}
<div class="modal fade" id="reassignModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" style="font-size:13px;"><i class="fas fa-user-check text-primary mr-1"></i>Assign Equipment to Client</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="reassignForm" method="POST">
                @csrf
                <div class="modal-body py-2">
                    <p style="font-size:12px;" class="text-muted mb-2">Assigning <strong id="reassignItemName"></strong> to a client.</p>
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;">Select Client <span class="text-danger">*</span></label>
                        <select name="client_id" id="reassignClientSelect" class="form-control form-control-sm" required>
                            <option value="">-- Search & Select Client --</option>
                            @foreach($clients as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->name }} ({{ $cl->client_id }}) — {{ $cl->username }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label style="font-size:12px;font-weight:600;">Assigned Through Employee</label>
                        <select name="assigned_through" id="reassignEmployee" class="form-control form-control-sm">
                            <option value="">-- Select Employee (optional) --</option>
                            @foreach($__gtDepts as $dept)
                            <optgroup label="{{ $dept->name }}">
                                @foreach($dept->employees as $emp)
                                <option value="{{ $emp->name }}">{{ $emp->name }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size:11px;">Which employee handled this reassignment</small>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:12px;font-weight:600;">Notes <span class="text-muted">(optional)</span></label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Reassignment notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-user-check mr-1"></i>Confirm Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('content')
<div class="container-fluid">

    {{-- ===== PRODUCTS & EQUIPMENT SUMMARY (shown when Products tab is active) ===== --}}
    <div id="summary-products">
        {{-- Row 1: Status --}}
        <div class="row mb-2">
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="small-box bg-primary">
                    <div class="inner"><h3>{{ $totalCount }}</h3><p>Total Returned</p></div>
                    <div class="icon"><i class="fas fa-undo-alt"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="small-box {{ $pendingCount > 0 ? 'bg-danger' : 'bg-secondary' }}">
                    <div class="inner"><h3>{{ $pendingCount }}</h3><p>Needs Collection</p></div>
                    <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="small-box bg-success">
                    <div class="inner"><h3>{{ $restockedCount }}</h3><p>Restocked</p></div>
                    <div class="icon"><i class="fas fa-boxes"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="small-box bg-info">
                    <div class="inner"><h3>{{ $assignedCount }}</h3><p>Reassigned to Client</p></div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
        </div>
        {{-- Row 2: Equipment type breakdown --}}
        @if($typeCounts->count() > 0)
        <div class="row mb-2">
            @foreach($typeCounts as $eqType => $count)
            <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                <div class="small-box bg-{{ $eqType==='ONU'?'primary':($eqType==='Fiber/Cable'?'success':($eqType==='Patchcord'?'warning':'info')) }}">
                    <div class="inner">
                        <h3>{{ $count }}</h3>
                        <p>{{ $eqType }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-{{ $eqType==='ONU'?'microchip':($eqType==='Fiber/Cable'?'ethernet':($eqType==='Patchcord'?'plug':'box')) }}"></i></div>
                </div>
            </div>
            @endforeach
            @if(isset($productReturnedCount))
            <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                <div class="small-box" style="background:linear-gradient(135deg,#3949ab,#5c6bc0);color:#fff;">
                    <div class="inner"><h3>{{ $productReturnedCount }}</h3><p>Products Returned</p></div>
                    <div class="icon"><i class="fas fa-box"></i></div>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- ===== FIBER SUMMARY (shown when Fiber tab is active) ===== --}}
    <div id="summary-fiber" style="display:none;">
        <div class="row mb-2">
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="small-box" style="background:linear-gradient(135deg,#0d7377,#17c5cb);color:#fff;">
                    <div class="inner">
                        <h3>{{ $recoveredFiberCount ?? 0 }} <small>times</small></h3>
                        <p>Recovered Fiber</p>
                    </div>
                    <div class="icon"><i class="fas fa-project-diagram"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="small-box" style="background:linear-gradient(135deg,#3d9970,#2ecc71);color:#fff;">
                    <div class="inner">
                        <h3>{{ number_format($recoveredFiberMeter ?? 0, 1) }} <small>m</small></h3>
                        <p>Total Fiber Recovered</p>
                    </div>
                    <div class="icon"><i class="fas fa-ruler-horizontal"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="small-box bg-primary">
                    <div class="inner"><h3>{{ $totalCount }}</h3><p>Total Returned Items</p></div>
                    <div class="icon"><i class="fas fa-undo-alt"></i></div>
                </div>
            </div>
        </div>
    </div>
    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-0" id="returnedTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="products-tab" data-toggle="tab" href="#productsTabPane" role="tab">
                <i class="fas fa-box mr-1"></i>Products &amp; Equipment
                @if(isset($productReturnedCount))<span class="badge badge-secondary ml-1">{{ $productReturnedCount }}</span>@endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="fiber-tab" data-toggle="tab" href="#fiberTabPane" role="tab">
                <i class="fas fa-project-diagram mr-1"></i>Recovered Fiber
                @if(isset($recoveredFiberCount))<span class="badge badge-success ml-1">{{ $recoveredFiberCount }}</span>@endif
            </a>
        </li>
    </ul>

    <div class="tab-content" id="returnedTabContent">
    <div class="tab-pane fade show active" id="productsTabPane" role="tabpanel">
    <div class="card mb-0" style="border-top-left-radius:0;">
        <div class="card-header">
            <h3 class="card-title">
                    <i class="fas fa-undo-alt mr-2"></i>Returned Equipment List
                    @if($pendingCount > 0)
                    <span class="badge badge-danger ml-2" title="Items pending restock or reassignment">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $pendingCount }} Pending
                    </span>
                    @endif
                </h3>
            <div class="card-tools">
                <form class="form-inline" method="GET">
                    <select name="type" class="form-control form-control-sm mr-2"><option value="">All Types</option>@foreach($typeCounts->keys() as $t)<option value="{{ $t }}" {{ $type==$t?'selected':'' }}>{{ $t }}</option>@endforeach</select>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead class="bg-light"><tr><th>#</th><th>Return Date</th><th>Client</th><th>Type</th><th>Equipment</th><th>Serial/MAC</th><th>Condition</th><th>Status</th><th>Received By</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($equipments as $i => $eq)
                    <tr>
                        <td>{{ $equipments->firstItem()+$i }}</td>
                        <td>{{ $eq->return_date->format('d-m-Y') }}</td>
                        <td><strong>{{ $eq->client_client_id }}</strong><br><small class="text-muted">{{ $eq->client_name }}</small></td>
                        <td><span class="badge badge-{{ $eq->equipment_type==='ONU'?'primary':($eq->equipment_type==='Fiber/Cable'?'success':'secondary') }}">{{ $eq->equipment_type }}</span></td>
                        <td>{{ $eq->equipment_name }}</td>
                        <td>@if($eq->serial_number&&$eq->serial_number!='N/A')<small>S/N: {{ $eq->serial_number }}</small>@endif @if($eq->mac_address&&$eq->mac_address!='N/A')<br><small>MAC: {{ $eq->mac_address }}</small>@endif</td>
                        <td><span class="badge badge-{{ $eq->condition==='good'?'success':($eq->condition==='damaged'?'danger':($eq->condition==='faulty'?'warning':'secondary')) }}">{{ ucfirst($eq->condition) }}</span></td>
                        <td>
                            @if($eq->assigned_to_client_id)
                                <span class="badge badge-info"><i class="fas fa-user-check mr-1"></i>Re-assigned</span>
                                @if($eq->assignedClient)<br><small class="text-muted">{{ $eq->assignedClient->name }}</small>@endif
                            @elseif($eq->is_restocked)
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Restocked</span>
                                @if($eq->restockedByUser)<br><small class="text-muted">{{ $eq->restockedByUser->name }}</small>@endif
                            @else
                                <span class="badge badge-warning text-dark"><i class="fas fa-clock mr-1"></i>Pending</span>
                            @endif
                        </td>
                        <td>{{ $eq->receivedByUser->name ?? 'N/A' }}</td>
                        <td>
    <div class="d-flex flex-wrap" style="gap:3px;">
        <a href="{{ route('admin.inventory.returned-equipments.show', $eq) }}" class="btn btn-info btn-xs" title="View Details"><i class="fas fa-eye"></i></a>
        @if(!$eq->is_restocked)
        <button type="button" class="btn btn-success btn-xs" onclick="openRestockModal({{ $eq->id }}, '{{ addslashes($eq->equipment_name) }}', '{{ $eq->serial_number ?? "N/A" }}', '{{ $eq->condition }}')" title="Restock to Inventory"><i class="fas fa-undo-alt"></i> Restock</button>
        @else
        <span class="badge badge-success" style="font-size:10px;padding:4px 6px;"><i class="fas fa-check mr-1"></i>Restocked</span>
        @if(!$eq->assigned_to_client_id)
        <button type="button" class="btn btn-primary btn-xs" onclick="openReassignModal({{ $eq->id }}, '{{ addslashes($eq->equipment_name) }}', '{{ $eq->serial_number ?? "N/A" }}')" title="Assign to Client"><i class="fas fa-user-check"></i> Assign</button>
        @else
        <span class="badge badge-info" style="font-size:10px;padding:4px 6px;"><i class="fas fa-user-check mr-1"></i>Assigned</span>
        @endif
        @endif
    </div>
</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center py-4 text-muted"><i class="fas fa-box-open fa-2x mb-2 d-block"></i>No returned equipment found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($equipments->hasPages())<div class="card-footer">{{ $equipments->withQueryString()->links() }}</div>@endif
    </div>

    </div>{{-- end productsTabPane --}}
    <div class="tab-pane fade" id="fiberTabPane" role="tabpanel">
    <div class="card mb-0" style="border-top-left-radius:0;">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-project-diagram mr-2 text-success"></i>Recovered Fiber History</h3></div>
        <div class="card-body p-0">
            @if(isset($recoveredFibers) && $recoveredFibers->count() > 0)
            <table class="table table-sm table-striped mb-0">
                <thead class="bg-light"><tr><th>Date</th><th>Fiber Code</th><th>Meters</th><th>Details</th><th>Note</th></tr></thead>
                <tbody>
                @foreach($recoveredFibers as $rf)
                <tr>
                    <td><small>{{ \Carbon\Carbon::parse($rf->created_at)->format('d-m-Y') }}</small></td>
                    <td><span class="badge badge-success">{{ $rf->fiber_code ?? '—' }}</span></td>
                    <td><strong>{{ number_format($rf->meter, 1) }} m</strong></td>
                    <td><small>{{ $rf->details ?? '—' }}</small></td>
                    <td><small class="text-muted">{{ $rf->note ?? '—' }}</small></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-5 text-muted"><i class="fas fa-project-diagram fa-2x mb-2 d-block"></i>No fiber recovered yet</div>
            @endif
        </div>
    </div>
    </div>{{-- end fiberTabPane --}}
    </div>{{-- end tab-content --}}

</div>
<script>
(function () {
    function switchSummary(tabId) {
        if (tabId === 'fiber-tab') {
            document.getElementById('summary-products').style.display = 'none';
            document.getElementById('summary-fiber').style.display = '';
        } else {
            document.getElementById('summary-fiber').style.display = 'none';
            document.getElementById('summary-products').style.display = '';
        }
    }

    document.querySelectorAll('#returnedTabs .nav-link').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            switchSummary(e.target.id);
        });
        // jQuery fallback for AdminLTE (Bootstrap 4)
        $(tab).on('shown.bs.tab', function (e) {
            switchSummary(e.target.id);
        });
    });
})();
</script>
@endsection