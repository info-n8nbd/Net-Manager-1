@extends('layouts.admin')
@section('title', 'ONU List')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item active">ONU List</li>
@endsection

@push('styles')
<style>
.onu-table th { font-size: 11px; text-transform: uppercase; white-space: nowrap; padding: 6px 8px !important; }
.onu-table td { font-size: 12px; padding: 5px 8px !important; vertical-align: middle; }
.status-online { color: #28a745; font-weight: 700; }
.status-offline { color: #dc3545; font-weight: 700; }
.status-unknown { color: #6c757d; font-weight: 600; }
.mac-addr { font-family: monospace; font-size: 11px; }
.power-excellent { color: #28a745; font-weight: 600; }
.power-good { color: #17a2b8; font-weight: 600; }
.power-fair { color: #ffc107; font-weight: 600; }
.power-poor { color: #dc3545; font-weight: 600; }
.summary-cards .card { border: none; border-radius: 6px; }
.summary-cards .card-body { padding: 12px 15px; }
.summary-cards .count { font-size: 22px; font-weight: 700; }
</style>
@endpush

@section('content')
@php
    $totalOnus = $stats['total'] ?? $onus->total();
    $onlineCount = $stats['online'] ?? 0;
    $offlineCount = $stats['offline'] ?? 0;
    $assignedCount = $stats['assigned'] ?? 0;
@endphp

<div class="row summary-cards mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card bg-info text-white">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="count">{{ $totalOnus }}</div>
                    <div style="font-size:12px;">Total ONUs</div>
                </div>
                <i class="fas fa-network-wired fa-2x" style="opacity:0.5"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card bg-success text-white">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="count">{{ $onlineCount }}</div>
                    <div style="font-size:12px;">Online</div>
                </div>
                <i class="fas fa-check-circle fa-2x" style="opacity:0.5"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card bg-danger text-white">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="count">{{ $offlineCount }}</div>
                    <div style="font-size:12px;">Offline</div>
                </div>
                <i class="fas fa-times-circle fa-2x" style="opacity:0.5"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card bg-primary text-white">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="count">{{ $assignedCount }}</div>
                    <div style="font-size:12px;">Client Assigned</div>
                </div>
                <i class="fas fa-user-check fa-2x" style="opacity:0.5"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-1"></i>All ONU Devices</h3>
        <div class="card-tools">
            @if(isset($olts) && $olts->count() > 0)
            <div class="d-inline-flex align-items-center">
                @foreach($olts as $olt)
                <a href="{{ route('admin.olt.onus', $olt) }}" class="btn btn-outline-info btn-sm mr-1" title="Manage {{ $olt->name }}">
                    <i class="fas fa-broadcast-tower mr-1"></i>{{ $olt->name }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    <div class="card-body py-2 px-3 border-bottom">
        <div class="d-flex flex-wrap align-items-center" style="gap:6px;">
            <div class="input-group" style="max-width:340px; min-width:180px; flex:2;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0" style="border-radius:4px 0 0 4px;">
                        <i class="fas fa-search text-muted" style="font-size:12px;"></i>
                    </span>
                </div>
                <input type="text" id="onuQuickSearch"
                       class="form-control border-left-0"
                       placeholder="Serial / MAC / PPPoE ID / Client..."
                       style="font-size:13px;">
            </div>
            <select id="onuOltFilter" class="form-control" style="max-width:150px; flex:1; font-size:13px;">
                <option value="">All OLTs</option>
                @foreach($olts as $olt)
                <option value="{{ $olt->id }}">{{ $olt->name }}</option>
                @endforeach
            </select>
            <input type="text" id="onuPonFilter" class="form-control"
                   style="max-width:140px; flex:1; font-size:13px;"
                   placeholder="PON Port (e.g. GPON0/1)">
            <select id="onuStatusFilter" class="form-control" style="max-width:110px; flex:1; font-size:13px;">
                <option value="">All Status</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
            </select>
            <button id="onuSearchBtn" class="btn btn-primary btn-sm px-3" style="font-size:13px;">
                <i class="fas fa-search mr-1"></i>Search
            </button>
            <button id="onuClearBtn" class="btn btn-secondary btn-sm px-3" style="font-size:13px; display:none;" title="Clear filters">
                <i class="fas fa-times mr-1"></i>Clear
            </button>
        </div>
        <div id="onuFilterInfo" class="mt-1" style="display:none;">
            <small class="text-muted"><i class="fas fa-filter mr-1"></i><span id="onuFilterText"></span></small>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover mb-0 onu-table">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>OLT</th>
                        <th>PON Port</th>
                        <th>ONU#</th>
                        <th>Serial / MAC</th>
                        <th>Vendor</th>
                        <th>Model</th>
                        <th>Status</th>
                        <th>RX Power</th>
                        <th>Client</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($onus as $index => $onu)
                    <tr>
                        <td>{{ $onus->firstItem() + $index }}</td>
                        <td>{{ $onu->olt_name ?? '-' }}</td>
                        <td>{{ $onu->pon_port ?? '-' }}</td>
                        <td>{{ $onu->onu_number ?? '-' }}</td>
                        <td>
                            <span class="mac-addr">{{ $onu->serial_number }}</span>
                            @if($onu->mac_address)
                            <br><small class="text-muted">{{ $onu->mac_address }}</small>
                            @endif
                        </td>
                        <td>{{ $onu->vendor ?? $onu->olt_vendor ?? '-' }}</td>
                        <td>{{ $onu->model ?? '-' }}</td>
                        <td>
                            @if($onu->status === 'online')
                                <span class="status-online"><i class="fas fa-circle mr-1" style="font-size:8px;"></i>Online</span>
                            @elseif($onu->status === 'offline')
                                <span class="status-offline"><i class="fas fa-circle mr-1" style="font-size:8px;"></i>Offline</span>
                            @else
                                <span class="status-unknown">{{ ucfirst($onu->status ?? 'Unknown') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($onu->rx_power !== null)
                                @php
                                    $rx = (float) $onu->rx_power;
                                    $cls = $rx > -20 ? 'power-excellent' : ($rx > -25 ? 'power-good' : ($rx > -28 ? 'power-fair' : 'power-poor'));
                                @endphp
                                <span class="{{ $cls }}">{{ number_format($rx, 2) }} dBm</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($onu->client_username)
                                <span class="text-primary font-weight-bold">{{ $onu->client_username }}</span>
                                @if($onu->client_code)
                                <br><small class="text-muted">{{ $onu->client_code }}</small>
                                @endif
                            @elseif($onu->client_name)
                                <span class="text-primary">{{ $onu->client_name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $onu->description ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4">
                            <i class="fas fa-info-circle text-muted mr-1"></i>No ONU devices found. Sync ONUs from OLT devices first.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" id="onuFooter">
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
            <div class="d-flex align-items-center">
                <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
                <select class="form-control form-control-sm" id="onuPerPage" style="width:80px;">
                    <option value="20">20</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
                <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
            </div>
            <div id="onuPagination"></div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    var baseUrl  = '{{ route('admin.olt.onu-list') }}';
    var currentPage = 1;
    var perPage     = 50;
    var debounceTimer;
    var lastTotal   = {{ $onus->total() }};

    var inpSearch  = document.getElementById('onuQuickSearch');
    var selOlt     = document.getElementById('onuOltFilter');
    var inpPon     = document.getElementById('onuPonFilter');
    var selStatus  = document.getElementById('onuStatusFilter');
    var btnSearch  = document.getElementById('onuSearchBtn');
    var btnClear   = document.getElementById('onuClearBtn');
    var filterInfo = document.getElementById('onuFilterInfo');
    var filterText = document.getElementById('onuFilterText');
    var tbody      = document.querySelector('.onu-table tbody');
    var pagDiv     = document.getElementById('onuPagination');
    var selPerPage = document.getElementById('onuPerPage');

    function getParams(page) {
        var p = new URLSearchParams();
        var s = inpSearch.value.trim();
        var o = selOlt.value;
        var po = inpPon.value.trim();
        var st = selStatus.value;
        if (s)  p.set('search',   s);
        if (o)  p.set('olt_id',   o);
        if (po) p.set('pon_port', po);
        if (st) p.set('status',   st);
        p.set('per_page', perPage);
        if (page > 1) p.set('page', page);
        return p;
    }

    function rxClass(rx) {
        if (rx === null || rx === undefined || rx === '') return 'text-muted';
        var v = parseFloat(rx);
        if (v > -20) return 'power-excellent';
        if (v > -25) return 'power-good';
        if (v > -28) return 'power-fair';
        return 'power-poor';
    }

    function renderRows(data, from) {
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4"><i class="fas fa-info-circle text-muted mr-1"></i>No ONU devices found.</td></tr>';
            return;
        }
        var html = '';
        data.forEach(function (o, i) {
            var statusHtml = '';
            if (o.status === 'online') {
                statusHtml = '<span class="status-online"><i class="fas fa-circle mr-1" style="font-size:8px;"></i>Online</span>';
            } else if (o.status === 'offline') {
                statusHtml = '<span class="status-offline"><i class="fas fa-circle mr-1" style="font-size:8px;"></i>Offline</span>';
            } else {
                statusHtml = '<span class="status-unknown">' + (o.status ? o.status.charAt(0).toUpperCase() + o.status.slice(1) : 'Unknown') + '</span>';
            }
            var rxHtml = '';
            if (o.rx_power !== null && o.rx_power !== undefined && o.rx_power !== '') {
                var rx = parseFloat(o.rx_power);
                rxHtml = '<span class="' + rxClass(rx) + '">' + rx.toFixed(2) + ' dBm</span>';
            } else {
                rxHtml = '<span class="text-muted">-</span>';
            }
            var clientHtml = '';
            if (o.client_username) {
                clientHtml = '<span class="text-primary font-weight-bold">' + esc(o.client_username) + '</span>';
                if (o.client_code) clientHtml += '<br><small class="text-muted">' + esc(o.client_code) + '</small>';
            } else if (o.client_name) {
                clientHtml = '<span class="text-primary">' + esc(o.client_name) + '</span>';
            } else {
                clientHtml = '<span class="text-muted">-</span>';
            }
            var macHtml = '<span class="mac-addr">' + esc(o.serial_number || '-') + '</span>';
            if (o.mac_address) macHtml += '<br><small class="text-muted">' + esc(o.mac_address) + '</small>';

            html += '<tr>' +
                '<td>' + (from + i) + '</td>' +
                '<td>' + esc(o.olt_name || '-') + '</td>' +
                '<td>' + esc(o.pon_port || '-') + '</td>' +
                '<td>' + (o.onu_number !== null && o.onu_number !== undefined ? o.onu_number : '-') + '</td>' +
                '<td>' + macHtml + '</td>' +
                '<td>' + esc(o.vendor || o.olt_vendor || '-') + '</td>' +
                '<td>' + esc(o.model || '-') + '</td>' +
                '<td>' + statusHtml + '</td>' +
                '<td>' + rxHtml + '</td>' +
                '<td>' + clientHtml + '</td>' +
                '<td>' + esc(o.description || '-') + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;
    }

    function renderPagination(meta) {
        if (meta.last_page <= 1) { pagDiv.innerHTML = ''; return; }
        var html = '<ul class="pagination pagination-sm mb-0">';
        if (meta.current_page > 1) {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (meta.current_page - 1) + '">&laquo;</a></li>';
        }
        var start = Math.max(1, meta.current_page - 2);
        var end   = Math.min(meta.last_page, meta.current_page + 2);
        if (start > 1) html += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>' + (start > 2 ? '<li class="page-item disabled"><span class="page-link">…</span></li>' : '');
        for (var p = start; p <= end; p++) {
            html += '<li class="page-item' + (p === meta.current_page ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        if (end < meta.last_page) {
            html += (end < meta.last_page - 1 ? '<li class="page-item disabled"><span class="page-link">…</span></li>' : '');
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' + meta.last_page + '">' + meta.last_page + '</a></li>';
        }
        if (meta.current_page < meta.last_page) {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (meta.current_page + 1) + '">&raquo;</a></li>';
        }
        html += '</ul>';
        pagDiv.innerHTML = html;
        pagDiv.querySelectorAll('a[data-page]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                doSearch(parseInt(this.dataset.page));
            });
        });
    }

    function updateFilterInfo() {
        var s = inpSearch.value.trim();
        var o = selOlt.options[selOlt.selectedIndex];
        var po = inpPon.value.trim();
        var st = selStatus.value;
        var hasFilter = s || selOlt.value || po || st;
        btnClear.style.display = hasFilter ? '' : 'none';
        if (hasFilter) {
            var parts = ['Filtered: <strong id="onuResultCount">' + lastTotal + '</strong> result(s)'];
            if (s)  parts.push('Serial/MAC: <strong>' + esc(s) + '</strong>');
            if (selOlt.value) parts.push('OLT: <strong>' + esc(o.text) + '</strong>');
            if (po) parts.push('PON: <strong>' + esc(po) + '</strong>');
            if (st) parts.push('Status: <strong>' + st.charAt(0).toUpperCase() + st.slice(1) + '</strong>');
            filterText.innerHTML = parts.join(' &nbsp;· ');
            filterInfo.style.display = '';
        } else {
            filterInfo.style.display = 'none';
        }
    }

    function esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function doSearch(page) {
        page = page || 1;
        currentPage = page;
        var params = getParams(page);
        var url = baseUrl + '?' + params.toString();

        // Update browser URL without reload
        history.replaceState(null, '', url);

        // Loading indicator
        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Loading...</td></tr>';

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            lastTotal = res.total;
            renderRows(res.data, res.from || 1);
            renderPagination(res);
            updateFilterInfo();
            // Update result count in filter info
            var rc = document.getElementById('onuResultCount');
            if (rc) rc.textContent = res.total;
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4"><i class="fas fa-exclamation-circle mr-1"></i>Error loading data. Please try again.</td></tr>';
        });
    }

    // Search button click
    btnSearch.addEventListener('click', function () { doSearch(1); });

    // Enter key in search box
    inpSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); doSearch(1); }
    });

    // Debounced auto-search while typing (600ms)
    inpSearch.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { doSearch(1); }, 600);
    });

    // Instant search on dropdown changes
    selOlt.addEventListener('change', function () { doSearch(1); });
    selStatus.addEventListener('change', function () { doSearch(1); });

    // PON port: search on blur or Enter
    inpPon.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); doSearch(1); }
    });
    inpPon.addEventListener('blur', function () { doSearch(1); });

    // Clear button
    btnClear.addEventListener('click', function () {
        inpSearch.value = '';
        selOlt.value = '';
        inpPon.value = '';
        selStatus.value = '';
        doSearch(1);
    });

    // Per-page selector
    selPerPage.addEventListener('change', function () {
        perPage = parseInt(this.value);
        doSearch(1);
    });

})();
</script>
();

@endpush
@endsection
