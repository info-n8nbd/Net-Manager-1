@extends('layouts.admin')
@section('title', 'ONU Mismatches')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.olt.index') }}">OLT Devices</a></li>
<li class="breadcrumb-item active">ONU Mismatches</li>
@endsection

@section('content')
<div class="container-fluid py-2">

    @if(session("success"))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-check-circle mr-1"></i>{{ session("success") }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif
    @if(session("error"))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-exclamation-circle mr-1"></i>{{ session("error") }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle text-warning mr-2"></i>ONU Mismatches</h5>
            <small class="text-muted">PPPoE users detected on OLT via SNMP FDB but not registered in the billing system.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button id="rescanBtn" onclick="rescanAll()" class="btn btn-dark btn-sm">
                <i class="fas fa-sync-alt mr-1"></i>Re-Scan All OLTs
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="card-body py-2">
                    <div class="h3 fw-bold text-warning mb-0">{{ $totalCount }}</div>
                    <div class="small text-muted">Unmatched Users Found</div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="alert alert-warning border-0 shadow-sm mb-0 py-2">
                <i class="fas fa-info-circle mr-1"></i>
                These PPPoE users are <strong>connected through an ONU</strong> (identified via SNMP FDB)
                but their username has <strong>no matching client</strong> in the billing system.
                Add them as clients or correct their PPPoE username to enable auto-mapping.
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search username / MAC / Serial</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        value="{{ request('search') }}" placeholder="e.g. amir192 or AC:15:A2...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Filter by OLT</label>
                    <select name="olt_id" id="filterOltId" class="form-select form-select-sm" onchange="updatePonPortOptions(this.value)">
                        <option value="">All OLTs</option>
                        @foreach($oltDevices as $olt)
                            <option value="{{ $olt->id }}" @selected(request('olt_id')==$olt->id)>{{ $olt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2" id="ponPortWrap" style="{{ request('olt_id') ? '' : 'display:none' }}">
                    <label class="form-label small mb-1">Filter by PON Port</label>
                    <select name="pon_port" id="filterPonPort" class="form-select form-select-sm" onchange="this.closest('form').submit()">
                        <option value="">All PON Ports</option>
                        @if(request('olt_id') && isset($ponPortsByOlt[request('olt_id')]))
                            @foreach($ponPortsByOlt[request('olt_id')] as $pp)
                                <option value="{{ $pp }}" @selected(request('pon_port')==$pp)>{{ $pp }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-auto" style="min-width:90px;max-width:110px;">
                    <label class="form-label small mb-1">Per page</label>
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="20" @selected((int)request('per_page',20)==20)>20</option>
                        <option value="50" @selected((int)request('per_page',20)==50)>50</option>
                        <option value="100" @selected((int)request('per_page',20)==100)>100</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                    <a href="{{ route('admin.olt.onu-mismatches') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
                <div class="col-auto" style="min-width:180px;max-width:220px;">
                    <label class="form-label small mb-1">Quick Search</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="quickSearch" class="form-control" placeholder="Username / MAC / Serial..." autocomplete="off">
                        <button class="btn btn-primary" id="quickSearchBtn" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">#</th>
                            <th><i class="fas fa-user mr-1"></i>PPPoE Username</th>
                            <th><i class="fas fa-wifi mr-1"></i>Router MAC <small class="text-muted">(caller-id)</small></th>
                            <th>Session IP</th>
                            <th>OLT</th>
                            <th>ONU Port</th>
                            <th>Serial / MAC</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($mismatches as $m)
                        <tr>
                            <td class="ps-3 text-muted small">{{ ($mismatches->currentPage()-1)*$mismatches->perPage() + $loop->iteration }}</td>
                            <td>
                                @if(($m['type'] ?? '') === 'onu_unassigned')
                                    <span class="fw-semibold text-warning">
                                        <i class="fas fa-wifi mr-1"></i>Active ONU &mdash; No Client
                                    </span>
                                    <div class="small mt-1">
                                        @if($m["onu_serial"])
                                        <span class="badge bg-secondary font-monospace" style="font-size:0.72em">{{ $m["onu_serial"] }}</span>
                                        @endif
                                        <span class="text-muted ms-1">{{ $m["pon_port"] }} &bull; ONU #{{ $m["onu_number"] }}</span>
                                    </div>
                                    <div class="small text-muted" style="font-size:0.75em">Online &mdash; no billing client assigned</div>
                                @else
                                    <span class="fw-semibold text-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16" style="margin-right:4px;vertical-align:-1px"><path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm-9 8c0 1 1 1 1 1h7.5a.5.5 0 0 0 0-1H4.5A2.5 2.5 0 0 1 2 10.5V10a.5.5 0 0 0-1 0v.5A3.5 3.5 0 0 0 4.5 14H11c0 .55.45 1 1 1s1-.45 1-1-.45-1-1-1H4.5zM6.5 6a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3z"/></svg>{{ $m['username'] }}
                                    </span>
                                    <div class="small text-muted">Not in billing system</div>
                                @endif
                            </td>
                            <td><code class="small">{{ $m['caller_mac'] ?? '—' }}</code></td>
                            <td class="small font-monospace text-muted">{{ $m['ip'] ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.olt.dashboard', $m['olt_id']) }}" class="text-decoration-none fw-semibold">
                                    {{ $m['olt_name'] }}
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $m['pon_port'] }}:{{ $m['onu_number'] }}</span>
                            </td>
                            <td>
                                @if($m['onu_serial'])
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fw-semibold small text-dark font-monospace">{{ $m['onu_serial'] }}</span>
                                        <button type="button" class="btn-copy" title="Copy Serial" data-copy="{{ $m['onu_serial'] }}" onclick="copyText(this, this.dataset.copy)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/></svg>
                                        </button>
                                    </div>
                                @endif
                                @if($m['onu_mac'])
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <span class="small text-muted font-monospace">{{ $m['onu_mac'] }}</span>
                                        <button type="button" class="btn-copy" title="Copy MAC" data-copy="{{ $m['onu_mac'] }}" onclick="copyText(this, this.dataset.copy)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/></svg>
                                        </button>
                                    </div>
                                @elseif(!$m['onu_serial'])
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="d-flex gap-1">
                                @if(($m['type'] ?? '') !== 'onu_unassigned')
                                <button type="button"
                                    class="btn btn-success btn-xs open-import-modal"
                                    data-username="{{ $m['username'] }}"
                                    data-onu-serial="{{ $m['onu_serial'] ?? '' }}"
                                    data-onu-mac="{{ $m['onu_mac'] ?? '' }}" data-caller-mac="{{ $m['caller_mac'] ?? '' }}"
                                    data-olt-id="{{ $m['olt_id'] }}"
                                    data-olt-name="{{ $m['olt_name'] }}"
                                    data-olt-port="{{ ($m['pon_port'] ?? '') }}"
                                    title="Add as new client">
                                    <i class="fas fa-user-plus"></i> Add
                                </button>
                                @endif
                                @if($m['onu_id'])
                                <a href="{{ route('admin.olt.onu-detail', $m['onu_id']) }}"
                                   class="btn btn-info btn-xs" title="View ONU Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                                <strong>No mismatches found!</strong><br>
                                <small>All detected ONU users are matched to billing clients, or no SNMP FDB data is available yet.</small><br>
                                <button onclick="rescanAll()" class="btn btn-dark btn-sm mt-3">
                                    <i class="fas fa-sync-alt mr-1"></i>Run Scan Now
                                </button>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($mismatches->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <small class="text-muted">Showing {{ $mismatches->firstItem() }}–{{ $mismatches->lastItem() }} of {{ $totalCount }} unmatched users</small>
                {{ $mismatches->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>

</div>

{{-- Re-Scan Progress Modal --}}
<div class="modal fade" id="rescanModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-satellite-dish mr-2"></i>Scanning OLTs...</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="rescanModalBody">
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-3 text-muted">Querying OLT SNMP FDB tables and matching sessions...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="location.reload()">
            <i class="fas fa-redo mr-1"></i>Reload Page
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Import ONU Mismatch User Modal --}}
<div class="modal fade" id="onuImportModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST" action="{{ route('admin.olt.import-mismatch-user') }}" id="onuImportForm">
      @csrf
      <input type="hidden" name="onu_serial"   id="imp_onu_serial">
      <input type="hidden" name="onu_mac"       id="imp_onu_mac">
      <input type="hidden" name="caller_mac"     id="imp_caller_mac">
      <input type="hidden" name="olt_device_id" id="imp_olt_device_id">
      <input type="hidden" name="olt_port"      id="imp_olt_port">
      <div class="modal-content">
        <div class="modal-header bg-success">
          <h5 class="modal-title text-white"><i class="fas fa-user-plus mr-2"></i>Import ONU User to Billing</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info py-2 mb-3">
            <i class="fas fa-info-circle mr-1"></i>
            Importing user <strong id="imp_username_display"></strong>
            via OLT <strong id="imp_olt_display"></strong>
            &mdash; ONU Port: <strong id="imp_port_display"></strong>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>PPPoE Username <span class="text-danger">*</span></label>
                <input type="text" name="username" id="imp_username" class="form-control" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>PPPoE Password</label>
                <input type="text" name="pppoe_password" id="imp_password" class="form-control" placeholder="Leave blank if none">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Client Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="First name" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control" placeholder="Last name">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Phone Number <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" class="form-control" placeholder="Client address">
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Zone <span class="text-danger">*</span></label>
                <select name="zone_id" id="imp_zone" class="form-control" required onchange="onuImportUpdateAreas(this.value)">
                  <option value="">Select Zone</option>
                  @foreach($zones as $zone)
                  <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Area</label>
                <select name="area_id" id="imp_area" class="form-control">
                  <option value="">Select Area</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>TJ/Box</label>
                <select name="box_id" class="form-control">
                  <option value="">Select Box</option>
                  @foreach($boxes as $box)
                  <option value="{{ $box->id }}">{{ $box->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Package <span class="text-danger">*</span></label>
                <select name="package_id" id="imp_package" class="form-control" required onchange="onuImportUpdateFee(this)">
                  <option value="">Select Package</option>
                  @foreach($packages as $pkg)
                  <option value="{{ $pkg->id }}" data-price="{{ $pkg->price }}">{{ $pkg->name }} - {{ number_format($pkg->price) }} BDT</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Monthly Bill</label>
                <input type="number" name="monthly_fee" id="imp_monthly_fee" class="form-control" step="0.01" min="0">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Status <span class="text-danger">*</span></label>
                <select name="status" class="form-control" required>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
          </div>

          <hr class="my-2">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group mb-0">
                <label><i class="fas fa-tools mr-1 text-warning"></i>Installation Charge</label>
                <select name="install_charge_status" id="imp_install_status" class="form-control" onchange="onuImportToggleInstall(this.value)">
                  <option value="free">Free (No Charge)</option>
                  <option value="due">Due (Not Paid Yet)</option>
                  <option value="paid">Paid (Already Collected)</option>
                </select>
              </div>
            </div>
            <div class="col-md-4" id="imp_install_amount_wrap" style="display:none;">
              <div class="form-group mb-0">
                <label>Charge Amount (৳) <span class="text-danger">*</span></label>
                <input type="number" name="install_charge_amount" class="form-control" min="0" step="1" placeholder="e.g. 1000">
              </div>
            </div>
            <div class="col-md-4" id="imp_install_paid_wrap" style="display:none;">
              <div class="form-group mb-0">
                <label>Payment Method</label>
                <select name="install_payment_method" id="imp_payment_method" class="form-control" onchange="onuImportTogglePayment(this.value)">
                  @foreach(auth()->user()->allowedPaymentMethods() as $pm)
                  <option value="{{ strtolower($pm->name) }}">{{ $pm->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="row mt-2" id="imp_payment_extra" style="display:none;">
            <div class="col-md-6">
              <div class="form-group mb-0">
                <label>Account Number</label>
                <input type="text" name="install_account_number" class="form-control" placeholder="e.g. 017XXXXXXXX">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group mb-0">
                <label>Transaction Number</label>
                <input type="text" name="install_transaction_number" class="form-control" placeholder="e.g. TRX123456789">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cancel</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle mr-1"></i>Import to Billing</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
var oltDevices = {!! json_encode($oltDevices->toArray()) !!};
var ponPortsByOlt = {!! json_encode($ponPortsByOlt) !!};

function updatePonPortOptions(oltId) {
    var wrap = document.getElementById('ponPortWrap');
    var sel  = document.getElementById('filterPonPort');
    if (!oltId) {
        wrap.style.display = 'none';
        sel.innerHTML = '<option value="">All PON Ports</option>';
    } else {
        var ports = (ponPortsByOlt[oltId] || []).slice().sort(function(a, b) {
            return a.localeCompare(b, undefined, {numeric: true, sensitivity: 'base'});
        });
        sel.innerHTML = '<option value="">All PON Ports</option>';
        ports.forEach(function(p) {
            var opt = document.createElement('option');
            opt.value = p;
            opt.textContent = p;
            sel.appendChild(opt);
        });
        wrap.style.display = '';
    }
    // Auto-submit the filter form
    var form = document.getElementById('filterOltId').closest('form');
    if (form) form.submit();
}

function rescanAll() {
    var btn = document.getElementById('rescanBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Scanning...';

    $('#rescanModal').modal('show');
    $('#rescanModalBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-3 text-muted">Starting scan...</p></div>');

    var oltIds = oltDevices.map(function(o){ return o.id; });
    var results = [];

    function scanNext(idx) {
        if (idx >= oltIds.length) {
            // Build results table
            var totalMapped = results.reduce(function(s,r){ return s + (r.mapped||0); }, 0);
            var html = '<div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i><strong>Scan complete!</strong> Total new mappings: <strong>' + totalMapped + '</strong></div>';
            html += '<table class="table table-sm table-bordered"><thead class="table-light"><tr><th>OLT</th><th>Newly Mapped</th><th>Skipped</th><th>Status</th></tr></thead><tbody>';
            results.forEach(function(r) {
                html += '<tr><td>' + r.olt + '</td>'
                    + '<td class="text-success fw-bold">' + (r.mapped||0) + '</td>'
                    + '<td class="text-muted">' + (r.skipped||0) + '</td>'
                    + '<td>' + (r.error ? '<span class="badge badge-danger">Error</span>' : '<span class="badge badge-success">OK</span>') + '</td></tr>';
            });
            html += '</tbody></table>';
            if (totalMapped > 0) {
                html += '<div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i>Click "Reload Page" to see updated mismatch list.</div>';
            }
            $('#rescanModalBody').html(html);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt mr-1"></i>Re-Scan All OLTs';
            return;
        }

        var oltId = oltIds[idx];
        var oltName = oltDevices[idx] ? oltDevices[idx].name : 'OLT #' + oltId;
        $('#rescanModalBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-3 text-muted">Scanning <strong>' + oltName + '</strong> (' + (idx+1) + ' of ' + oltIds.length + ')...</p></div>');

        $.ajax({
            url: '/admin/network/olt/' + oltId + '/scan-match',
            type: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            timeout: 120000,
            success: function(res) {
                results.push({ olt: oltName, mapped: res.mapped||0, skipped: res.skipped||0, error: false });
                scanNext(idx + 1);
            },
            error: function() {
                results.push({ olt: oltName, mapped: 0, skipped: 0, error: true });
                scanNext(idx + 1);
            }
        });
    }

    scanNext(0);
}
</script>
<script>
(function () {
    var quickInput = document.getElementById("quickSearch");
    var quickBtn   = document.getElementById("quickSearchBtn");
    var formSearch = document.querySelector("input[name=search]");
    var debounceTimer = null;

    // Set initial value from URL params
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("search")) quickInput.value = urlParams.get("search");

    function doSearch() {
        var val = quickInput.value.trim();
        var url = new URL(window.location.href);
        if (val) { url.searchParams.set("search", val); }
        else { url.searchParams.delete("search"); }
        url.searchParams.set("page", 1);
        var oltSel  = document.querySelector("select[name=olt_id]");
        var ponSel  = document.querySelector("select[name=pon_port]");
        var perPage = document.querySelector("select[name=per_page]");
        if (oltSel  && oltSel.value)  url.searchParams.set("olt_id", oltSel.value);
        else url.searchParams.delete("olt_id");
        if (ponSel  && ponSel.value)  url.searchParams.set("pon_port", ponSel.value);
        else url.searchParams.delete("pon_port");
        if (perPage && perPage.value) url.searchParams.set("per_page", perPage.value);
        window.location.href = url.toString();
    }

    quickInput.addEventListener("keyup", function (e) {
        if (e.key === "Enter") { clearTimeout(debounceTimer); doSearch(); return; }
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doSearch, 600);
    });
    quickBtn.addEventListener("click", function () { clearTimeout(debounceTimer); doSearch(); });

    // Keep quick search box in sync with the filter form search field
    if (formSearch) {
        formSearch.addEventListener("input", function () { quickInput.value = formSearch.value; });
        quickInput.addEventListener("input", function () { formSearch.value = quickInput.value; });
    }
})();

// ONU Import Modal JS
var onuImportZones = {!! json_encode($zones->map(fn($z) => ['id'=>$z->id,'areas'=>$z->areas->map(fn($a)=>['id'=>$a->id,'name'=>$a->name])->values()])->keyBy('id')) !!};

document.querySelectorAll('.open-import-modal').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var username = btn.dataset.username;
        var onuSerial = btn.dataset.onuSerial;
        var onuMac = btn.dataset.onuMac;
        var callerMac = btn.dataset.callerMac;
        var oltId = btn.dataset.oltId;
        var oltName = btn.dataset.oltName;
        var oltPort = btn.dataset.oltPort;

        document.getElementById('imp_username').value = username;
        document.getElementById('imp_username_display').textContent = username;
        document.getElementById('imp_olt_display').textContent = oltName;
        document.getElementById('imp_port_display').textContent = oltPort;
        document.getElementById('imp_onu_serial').value = onuSerial;
        document.getElementById('imp_onu_mac').value = onuMac;
        document.getElementById('imp_caller_mac').value = callerMac;
        document.getElementById('imp_olt_device_id').value = oltId;
        document.getElementById('imp_olt_port').value = oltPort;

        // Auto-fill password from username digits
        var digits = username.replace(/\D/g, '');
        document.getElementById('imp_password').value = digits || '';

        // Reset form fields
        document.getElementById('imp_zone').value = '';
        document.getElementById('imp_area').innerHTML = '<option value="">Select Area</option>';
        document.getElementById('imp_package').value = '';
        document.getElementById('imp_monthly_fee').value = '';
        document.getElementById('imp_install_status').value = 'free';
        onuImportToggleInstall('free');

        $('#onuImportModal').modal('show');
    });
});

function onuImportUpdateAreas(zoneId) {
    var sel = document.getElementById('imp_area');
    sel.innerHTML = '<option value="">Select Area</option>';
    if (zoneId && onuImportZones[zoneId]) {
        onuImportZones[zoneId].areas.forEach(function(a) {
            sel.innerHTML += '<option value="' + a.id + '">' + a.name + '</option>';
        });
    }
}

function onuImportUpdateFee(select) {
    var opt = select.options[select.selectedIndex];
    var price = opt ? opt.getAttribute('data-price') : '';
    document.getElementById('imp_monthly_fee').value = price || '';
}

function onuImportToggleInstall(val) {
    document.getElementById('imp_install_amount_wrap').style.display = (val === 'due' || val === 'paid') ? '' : 'none';
    document.getElementById('imp_install_paid_wrap').style.display = (val === 'paid') ? '' : 'none';
    if (val !== 'paid') { document.getElementById('imp_payment_extra').style.display = 'none'; }
}

function onuImportTogglePayment(val) {
    var extra = document.getElementById('imp_payment_extra');
    extra.style.display = (val && val !== 'cash') ? '' : 'none';
}


function copyText(btn, text) {
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add("copied");
        var orig = btn.innerHTML;
        btn.innerHTML = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"11\" height=\"11\" fill=\"currentColor\" viewBox=\"0 0 16 16\"><path d=\"M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z\"/></svg>";
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.classList.remove("copied");
        }, 1500);
    }).catch(function() {
        var ta = document.createElement("textarea");
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand("copy");
        document.body.removeChild(ta);
        btn.classList.add("copied");
        setTimeout(function() { btn.classList.remove("copied"); }, 1500);
    });
}
</script>
@endpush
