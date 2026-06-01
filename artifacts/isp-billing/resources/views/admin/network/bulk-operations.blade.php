@extends('layouts.admin')

@section('title', 'Bulk MikroTik Operations')

@section('content')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter & Select Clients</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Zone</label>
                        <select id="filter-zone" class="form-control form-control-sm">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Status</label>
                        <select id="filter-status" class="form-control form-control-sm">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Connection Type</label>
                        <select id="filter-connection" class="form-control form-control-sm">
                            <option value="">All Types</option>
                            <option value="pppoe">PPPoE</option>
                            <option value="hotspot">Hotspot</option>
                            <option value="static">Static IP</option>
                            <option value="dhcp">DHCP</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Search</label>
                        <input type="text" id="filter-search" class="form-control form-control-sm" placeholder="Name, ID, Username...">
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-select-all">
                        <i class="fas fa-check-double"></i> Select All Visible
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-deselect-all">
                        <i class="fas fa-times"></i> Deselect All
                    </button>
                    <span class="ml-3 text-muted" id="selected-count">0 clients selected</span>
                </div>

                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover table-striped" id="clients-table">
                        <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th width="40"><input type="checkbox" id="check-all"></th>
                                <th>Client ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Zone</th>
                                <th>Package</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clients as $client)
                            <tr class="client-row" 
                                data-zone="{{ $client->zone_id }}" 
                                data-status="{{ $client->status }}" 
                                data-connection="{{ $client->connection_type }}"
                                data-search="{{ strtolower($client->name . ' ' . $client->client_id . ' ' . $client->username) }}">
                                <td><input type="checkbox" class="client-checkbox" value="{{ $client->id }}"></td>
                                <td><small>{{ $client->client_id }}</small></td>
                                <td>{{ $client->name }}</td>
                                <td><small class="text-muted">{{ $client->username }}</small></td>
                                <td><small>{{ $client->zone->name ?? '-' }}</small></td>
                                <td><small>{{ $client->package->name ?? '-' }}</small></td>
                                <td>
                                    <span class="badge badge-{{ $client->connection_type === 'pppoe' ? 'primary' : ($client->connection_type === 'hotspot' ? 'warning' : ($client->connection_type === 'static' ? 'info' : 'secondary')) }}">
                                        {{ strtoupper($client->connection_type ?? 'pppoe') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $client->status === 'active' ? 'success' : ($client->status === 'inactive' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($client->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-1"></i>Bulk Actions</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-success btn-block bulk-action-btn" data-action="enable">
                            <i class="fas fa-play mr-1"></i> Enable All
                        </button>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-danger btn-block bulk-action-btn" data-action="disable">
                            <i class="fas fa-stop mr-1"></i> Disable All
                        </button>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-info btn-block bulk-action-btn" data-action="sync">
                            <i class="fas fa-sync mr-1"></i> Sync All
                        </button>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-warning btn-block bulk-action-btn" data-action="disconnect">
                            <i class="fas fa-unlink mr-1"></i> Disconnect All
                        </button>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="button" class="btn btn-primary btn-block bulk-action-btn" data-action="provision">
                            <i class="fas fa-plus-circle mr-1"></i> Provision All
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" id="results-card" style="display: none;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i>Operation Results</h3>
                <div class="card-tools">
                    <span id="results-summary" class="mr-3"></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Client ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody id="results-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
            <div class="text-center text-white">
                <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                <h4>Processing bulk operation...</h4>
                <p id="loading-text">Please wait while we process your request.</p>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const actionUrls = {
        enable: "{{ route('admin.network.bulk-enable') }}",
        disable: "{{ route('admin.network.bulk-disable') }}",
        sync: "{{ route('admin.network.bulk-sync') }}",
        disconnect: "{{ route('admin.network.bulk-disconnect') }}",
        provision: "{{ route('admin.network.bulk-provision') }}"
    };

    const actionLabels = {
        enable: 'Enable',
        disable: 'Disable',
        sync: 'Sync',
        disconnect: 'Disconnect',
        provision: 'Provision'
    };

    function applyFilters() {
        const zone = $('#filter-zone').val();
        const status = $('#filter-status').val();
        const connection = $('#filter-connection').val();
        const search = $('#filter-search').val().toLowerCase();

        $('.client-row').each(function() {
            let show = true;
            if (zone && $(this).data('zone') != zone) show = false;
            if (status && $(this).data('status') != status) show = false;
            if (connection && $(this).data('connection') != connection) show = false;
            if (search && $(this).data('search').indexOf(search) === -1) show = false;
            $(this).toggle(show);
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const count = $('.client-checkbox:checked').length;
        $('#selected-count').text(count + ' clients selected');
    }

    function getSelectedIds() {
        return $('.client-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
    }

    $('#filter-zone, #filter-status, #filter-connection').on('change', applyFilters);
    $('#filter-search').on('input', applyFilters);

    $('#check-all').on('change', function() {
        $('.client-row:visible .client-checkbox').prop('checked', $(this).is(':checked'));
        updateSelectedCount();
    });

    $('#btn-select-all').on('click', function() {
        $('.client-row:visible .client-checkbox').prop('checked', true);
        updateSelectedCount();
    });

    $('#btn-deselect-all').on('click', function() {
        $('.client-checkbox').prop('checked', false);
        $('#check-all').prop('checked', false);
        updateSelectedCount();
    });

    $(document).on('change', '.client-checkbox', function() {
        updateSelectedCount();
    });

    $('.bulk-action-btn').on('click', function() {
        const action = $(this).data('action');
        const selectedIds = getSelectedIds();

        if (selectedIds.length === 0) {
            smartAlert('Please select at least one client.', 'warning');
            return;
        }

        const label = actionLabels[action];
        if (!confirm(`Are you sure you want to ${label} ${selectedIds.length} selected clients?`)) {
            return;
        }

        $('#loading-overlay').css('display', 'flex');
        $('#loading-text').text(`Processing ${label} for ${selectedIds.length} clients...`);

        $.ajax({
            url: actionUrls[action],
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                client_ids: selectedIds
            },
            success: function(response) {
                $('#loading-overlay').hide();
                displayResults(response, label);
            },
            error: function(xhr) {
                $('#loading-overlay').hide();
                smartAlert('Operation failed: ' + (xhr.responseJSON?.message || 'Unknown error'), 'error');
            }
        });
    });

    function displayResults(response, actionLabel) {
        const results = response.results || [];
        $('#results-card').show();
        $('#results-summary').html(
            `<span class="badge badge-success">${response.successful || 0} Success</span> ` +
            `<span class="badge badge-danger">${response.failed || 0} Failed</span> ` +
            `<span class="badge badge-secondary">${response.total || 0} Total</span>`
        );

        let html = '';
        results.forEach(function(r) {
            html += `<tr class="${r.success ? 'table-success' : 'table-danger'}">
                <td>${r.client_id}</td>
                <td>${r.name}</td>
                <td>
                    <span class="badge badge-${r.success ? 'success' : 'danger'}">
                        <i class="fas fa-${r.success ? 'check' : 'times'} mr-1"></i>${r.success ? 'Success' : 'Failed'}
                    </span>
                </td>
                <td><small>${r.message}</small></td>
            </tr>`;
        });

        $('#results-body').html(html);
        $('html, body').animate({ scrollTop: $('#results-card').offset().top - 100 }, 500);
    }
});
</script>
@endpush
