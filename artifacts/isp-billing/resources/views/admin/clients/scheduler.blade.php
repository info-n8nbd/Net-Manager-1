@extends('layouts.admin')
@section('title', 'Client Scheduler')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item active">Scheduler</li>
@endsection

@section('content')
<style>
.schedule-card { border-left: 4px solid #17a2b8; margin-bottom: 10px; background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.schedule-card.status-pending { border-left-color: #ffc107; }
.schedule-card.status-completed { border-left-color: #28a745; }
.schedule-card.status-failed { border-left-color: #dc3545; }
.schedule-card.status-cancelled { border-left-color: #6c757d; }
.action-badge-enable { background: #d4edda; color: #155724; }
.action-badge-disable { background: #f8d7da; color: #721c24; }
.action-badge-change_package { background: #d1ecf1; color: #0c5460; }
.action-badge-change_profile { background: #e2e3f1; color: #383d41; }
.countdown-text { font-size: 11px; color: #6c757d; }
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>Client Scheduler</h3>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createScheduleModal">
            <i class="fas fa-plus mr-1"></i>New Schedule
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2" style="font-size: 13px;">
            <i class="fas fa-info-circle mr-1"></i> Schedule automatic actions for clients: Enable, Disable, Change Package, or Change MikroTik Profile at a future date/time. Schedules run every minute.
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Scheduled At</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    <tr>
                        <td>
                            @if($schedule->client)
                            <a href="{{ route('admin.clients.show', $schedule->client) }}">{{ $schedule->client->name }}</a>
                            <br><small class="text-muted">{{ $schedule->client->client_id }} | {{ $schedule->client->username ?? '-' }}</small>
                            @else
                            <span class="text-muted">Deleted</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge action-badge-{{ $schedule->action }}" style="font-size: 12px; padding: 4px 10px; border-radius: 4px;">
                                @if($schedule->action == 'enable')<i class="fas fa-play mr-1"></i>Enable
                                @elseif($schedule->action == 'disable')<i class="fas fa-stop mr-1"></i>Disable
                                @elseif($schedule->action == 'change_package')<i class="fas fa-exchange-alt mr-1"></i>Change Package
                                @elseif($schedule->action == 'change_profile')<i class="fas fa-server mr-1"></i>Change Profile
                                @endif
                            </span>
                        </td>
                        <td style="font-size: 13px;">
                            @if($schedule->action == 'change_package')
                                <strong>Package:</strong> {{ $schedule->package->name ?? 'N/A' }}
                                @if($schedule->mikrotik_profile)
                                <br><strong>Profile:</strong> {{ $schedule->mikrotik_profile }}
                                @endif
                            @elseif($schedule->action == 'change_profile')
                                <strong>Profile:</strong> {{ $schedule->mikrotik_profile ?? '-' }}
                            @else
                                -
                            @endif
                            @if($schedule->note)
                            <br><small class="text-muted"><i class="fas fa-sticky-note mr-1"></i>{{ $schedule->note }}</small>
                            @endif
                        </td>
                        <td style="white-space: nowrap; font-size: 13px;">
                            {{ $schedule->scheduled_at->format('d-m-Y h:i A') }}
                            @if($schedule->status == 'pending')
                            <br><small class="countdown-text" data-countdown="{{ $schedule->scheduled_at->toIso8601String() }}"></small>
                            @endif
                        </td>
                        <td>
                            @if($schedule->status == 'pending')
                            <span class="badge badge-warning">Pending</span>
                            @elseif($schedule->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                            <br><small class="text-muted">{{ $schedule->executed_at?->format('d-m h:i A') }}</small>
                            @if($schedule->execution_result)
                            <br><small class="text-success">{{ Str::limit($schedule->execution_result, 50) }}</small>
                            @endif
                            @elseif($schedule->status == 'failed')
                            <span class="badge badge-danger">Failed</span>
                            @if($schedule->execution_result)
                            <br><small class="text-danger">{{ Str::limit($schedule->execution_result, 50) }}</small>
                            @endif
                            @elseif($schedule->status == 'cancelled')
                            <span class="badge badge-secondary">Cancelled</span>
                            @endif
                        </td>
                        <td style="font-size: 13px;">{{ $schedule->creator->name ?? '-' }}</td>
                        <td style="white-space: nowrap;">
                            @if($schedule->status == 'pending')
                            <button class="btn btn-info btn-sm edit-schedule-btn" title="Edit"
                                data-id="{{ $schedule->id }}"
                                data-action="{{ $schedule->action }}"
                                data-scheduled-at="{{ $schedule->scheduled_at->format('Y-m-d\TH:i') }}"
                                data-package-id="{{ $schedule->package_id }}"
                                data-mikrotik-profile="{{ $schedule->mikrotik_profile }}"
                                data-note="{{ $schedule->note }}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-danger btn-sm cancel-schedule-btn" data-id="{{ $schedule->id }}" title="Cancel"><i class="fas fa-times"></i></button>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>No scheduled tasks found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($schedules->hasPages())
    <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $schedules->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script></div>
    @endif
</div>

<div class="modal fade" id="createScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 14px 20px;">
                <h5 class="modal-title" style="font-weight: 700;"><i class="fas fa-calendar-plus mr-2"></i>New Schedule</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="createScheduleForm">
                <div class="modal-body" style="padding: 20px;">
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-user mr-1"></i>Client <span class="text-danger">*</span></label>
                        <input type="text" id="sched_client_search" class="form-control" placeholder="Search by name, ID, username, phone..." style="border-radius: 8px;" autocomplete="off">
                        <input type="hidden" id="sched_client_id">
                        <div id="sched_client_results" style="display:none; position:absolute; z-index:1050; background:#fff; border:1px solid #ddd; border-radius:8px; max-height:200px; overflow-y:auto; width:calc(100% - 40px); box-shadow:0 4px 12px rgba(0,0,0,0.15);"></div>
                        <div id="sched_client_selected" style="display:none; background:#f0f9ff; border-radius:8px; padding:8px 12px; margin-top:6px; font-size:13px;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong id="sched_client_name_display"></strong>
                                    <span class="text-muted ml-2" id="sched_client_id_display"></span>
                                </div>
                                <button type="button" class="close clear-sched-client-btn" style="font-size:16px;">&times;</button>
                            </div>
                            <div id="sched_client_status_info" style="margin-top:6px; padding-top:6px; border-top:1px solid #d6eaf8;">
                                <div class="row" style="font-size:12px;">
                                    <div class="col-6"><i class="fas fa-circle mr-1" id="sched_status_dot"></i><span id="sched_client_status_text"></span></div>
                                    <div class="col-6"><i class="fas fa-cube mr-1 text-info"></i><span id="sched_client_package_text"></span></div>
                                </div>
                                <div class="row mt-1" style="font-size:12px;">
                                    <div class="col-6"><i class="fas fa-server mr-1 text-secondary"></i><span id="sched_client_profile_text"></span></div>
                                    <div class="col-6"><i class="fas fa-money-bill mr-1 text-success"></i><span id="sched_client_fee_text"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-bolt mr-1"></i>Action <span class="text-danger">*</span></label>
                        <select id="sched_action" class="form-control" required style="border-radius: 8px;">
                            <option value="enable">Enable Connection</option>
                            <option value="disable">Disable Connection</option>
                            <option value="change_package">Change Package</option>
                            <option value="change_profile">Change MikroTik Profile</option>
                        </select>
                    </div>
                    <div class="form-group" id="sched_package_group" style="display:none;">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-cube mr-1"></i>New Package <span class="text-danger">*</span></label>
                        <select id="sched_package_id" class="form-control" style="border-radius: 8px;">
                            <option value="">-- Select Package --</option>
                            @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" data-mikrotik-profile="{{ $pkg->mikrotik_profile }}">{{ $pkg->name }} - ৳{{ number_format($pkg->price) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="sched_profile_group" style="display:none;">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-server mr-1"></i>MikroTik Profile <span class="text-danger">*</span></label>
                        <select id="sched_mikrotik_profile" class="form-control" style="border-radius: 8px;">
                            <option value="">-- Select Profile --</option>
                            @foreach($availableProfiles as $profile)
                            <option value="{{ $profile }}">{{ $profile }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-clock mr-1"></i>Scheduled Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="sched_datetime" class="form-control" required style="border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-sticky-note mr-1"></i>Note</label>
                        <textarea id="sched_note" class="form-control" rows="2" placeholder="Optional note..." style="border-radius: 8px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; border-top: 1px solid #f1f3f5;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="button" class="btn btn-primary" id="sched_submit_btn" style="border-radius: 8px;"><i class="fas fa-calendar-check mr-1"></i>Create Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 14px 20px;">
                <h5 class="modal-title" style="font-weight: 700;"><i class="fas fa-edit mr-2"></i>Edit Schedule</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="editScheduleForm">
                <input type="hidden" id="edit_schedule_id">
                <div class="modal-body" style="padding: 20px;">
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-bolt mr-1"></i>Action <span class="text-danger">*</span></label>
                        <select id="edit_action" class="form-control" required style="border-radius: 8px;">
                            <option value="enable">Enable Connection</option>
                            <option value="disable">Disable Connection</option>
                            <option value="change_package">Change Package</option>
                            <option value="change_profile">Change MikroTik Profile</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_package_group" style="display:none;">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-cube mr-1"></i>New Package <span class="text-danger">*</span></label>
                        <select id="edit_package_id" class="form-control" style="border-radius: 8px;">
                            <option value="">-- Select Package --</option>
                            @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" data-mikrotik-profile="{{ $pkg->mikrotik_profile }}">{{ $pkg->name }} - ৳{{ number_format($pkg->price) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="edit_profile_group" style="display:none;">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-server mr-1"></i>MikroTik Profile <span class="text-danger">*</span></label>
                        <select id="edit_mikrotik_profile" class="form-control" style="border-radius: 8px;">
                            <option value="">-- Select Profile --</option>
                            @foreach($availableProfiles as $profile)
                            <option value="{{ $profile }}">{{ $profile }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-clock mr-1"></i>Scheduled Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="edit_datetime" class="form-control" required style="border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-sticky-note mr-1"></i>Note</label>
                        <textarea id="edit_note" class="form-control" rows="2" placeholder="Optional note..." style="border-radius: 8px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; border-top: 1px solid #f1f3f5;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="button" class="btn btn-info" id="edit_submit_btn" style="border-radius: 8px;"><i class="fas fa-save mr-1"></i>Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

function clearScheduleClient() {
    $('#sched_client_id').val('');
    $('#sched_client_selected').hide();
    $('#sched_client_search').val('').show().focus();
}

function resetScheduleForm() {
    $('#createScheduleForm')[0].reset();
    clearScheduleClient();
    $('#sched_package_group, #sched_profile_group').hide();
}

function toggleEditFields(val) {
    if (val === 'change_package') {
        $('#edit_package_group').show();
        $('#edit_profile_group').show();
    } else if (val === 'change_profile') {
        $('#edit_package_group').hide();
        $('#edit_profile_group').show();
    } else {
        $('#edit_package_group').hide();
        $('#edit_profile_group').hide();
    }
}

function addScheduleRow(s) {
    var actionLabels = {
        'enable': '<i class="fas fa-play mr-1"></i>Enable',
        'disable': '<i class="fas fa-stop mr-1"></i>Disable',
        'change_package': '<i class="fas fa-exchange-alt mr-1"></i>Change Package',
        'change_profile': '<i class="fas fa-server mr-1"></i>Change Profile'
    };
    var details = '-';
    if (s.action === 'change_package') {
        details = '<strong>Package:</strong> ' + (s.package_name || 'N/A');
        if (s.mikrotik_profile) details += '<br><strong>Profile:</strong> ' + s.mikrotik_profile;
    } else if (s.action === 'change_profile') {
        details = '<strong>Profile:</strong> ' + (s.mikrotik_profile || '-');
    }
    if (s.note) details += '<br><small class="text-muted"><i class="fas fa-sticky-note mr-1"></i>' + s.note + '</small>';

    var row = '<tr id="schedule-row-' + s.id + '">' +
        '<td><a href="' + s.client_url + '">' + s.client_name + '</a><br><small class="text-muted">' + s.client_id_display + ' | ' + s.client_username + '</small></td>' +
        '<td><span class="badge action-badge-' + s.action + '" style="font-size:12px;padding:4px 10px;border-radius:4px;">' + (actionLabels[s.action] || s.action) + '</span></td>' +
        '<td style="font-size:13px;">' + details + '</td>' +
        '<td style="white-space:nowrap;font-size:13px;">' + s.scheduled_at + '<br><small class="countdown-text" data-countdown="' + s.scheduled_at_iso + '"></small></td>' +
        '<td><span class="badge badge-warning">Pending</span></td>' +
        '<td style="font-size:13px;">' + s.created_by + '</td>' +
        '<td style="white-space:nowrap;"><button class="btn btn-info btn-sm edit-schedule-btn" title="Edit" data-id="' + s.id + '" data-action="' + s.action + '" data-scheduled-at="' + s.scheduled_at_iso.substring(0,16) + '" data-package-id="' + (s.package_id||'') + '" data-mikrotik-profile="' + (s.mikrotik_profile||'') + '" data-note="' + (s.note||'').replace(/"/g,'&quot;') + '"><i class="fas fa-edit"></i></button> <button class="btn btn-danger btn-sm cancel-schedule-btn" data-id="' + s.id + '" title="Cancel"><i class="fas fa-times"></i></button></td>' +
        '</tr>';

    var tbody = $('table tbody');
    var emptyRow = tbody.find('td[colspan]').closest('tr');
    if (emptyRow.length) emptyRow.remove();
    tbody.prepend(row);
    updateCountdowns();
}

function updateCountdowns() {
    $('[data-countdown]').each(function() {
        var target = new Date($(this).data('countdown'));
        var now = new Date();
        var diff = target - now;
        if (diff <= 0) {
            $(this).text('Executing soon...').css('color', '#28a745');
            return;
        }
        var days = Math.floor(diff / 86400000);
        var hours = Math.floor((diff % 86400000) / 3600000);
        var mins = Math.floor((diff % 3600000) / 60000);
        var parts = [];
        if (days > 0) parts.push(days + 'd');
        if (hours > 0) parts.push(hours + 'h');
        parts.push(mins + 'm');
        $(this).text('in ' + parts.join(' '));
    });
}

var searchTimeout;
$(document).on('input', '#sched_client_search', function() {
    var q = $(this).val().trim();
    clearTimeout(searchTimeout);
    if (q.length < 2) { $('#sched_client_results').hide(); return; }
    searchTimeout = setTimeout(function() {
        $.get('{{ route("admin.api.search-clients") }}', { q: q }, function(data) {
            var html = '';
            var clients = data.clients || data;
            if (clients.length === 0) {
                html = '<div style="padding:10px;color:#999;font-size:13px;">No clients found</div>';
            } else {
                clients.forEach(function(c) {
                    var statusColor = c.status === 'active' ? '#28a745' : '#dc3545';
                    var statusIcon = '<i class="fas fa-circle" style="font-size:8px;color:'+statusColor+';margin-right:4px;"></i>';
                    html += '<div class="sched-client-option" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #f1f1f1;font-size:13px;" data-id="'+c.id+'" data-name="'+c.name+'" data-client-id="'+(c.client_id||'')+'" data-status="'+(c.status||'')+'" data-package="'+(c.package_name||'-')+'" data-profile="'+(c.mikrotik_profile_name||'-')+'" data-fee="'+(c.monthly_fee||0)+'">';
                    html += statusIcon+'<strong>'+c.name+'</strong> <span class="text-muted">'+(c.client_id||'')+'</span>';
                    html += '<br><small class="text-primary">'+(c.username||'-')+'</small> | <small>'+(c.phone||'-')+'</small>';
                    html += ' | <small class="text-info">'+(c.package_name||'-')+'</small>';
                    html += '</div>';
                });
            }
            $('#sched_client_results').html(html).show();
        });
    }, 300);
});

$(document).on('click', '.sched-client-option', function() {
    var el = $(this);
    var status = el.data('status') || '-';
    var isActive = status === 'active';
    $('#sched_client_id').val(el.data('id'));
    $('#sched_client_name_display').text(el.data('name'));
    $('#sched_client_id_display').text(el.data('client-id'));
    $('#sched_status_dot').css('font-size', '8px').css('color', isActive ? '#28a745' : '#dc3545');
    $('#sched_client_status_text').text(status.charAt(0).toUpperCase() + status.slice(1));
    $('#sched_client_package_text').text(el.data('package') || '-');
    $('#sched_client_profile_text').text(el.data('profile') || '-');
    $('#sched_client_fee_text').text('৳' + Number(el.data('fee') || 0).toLocaleString());
    $('#sched_client_selected').show();
    $('#sched_client_search').hide();
    $('#sched_client_results').hide();
});

$(document).on('click', '.clear-sched-client-btn', function() { clearScheduleClient(); });

$(document).on('click', function(e) {
    if (!$(e.target).closest('#sched_client_search, #sched_client_results').length) {
        $('#sched_client_results').hide();
    }
});

$(document).on('change', '#sched_action', function() {
    var val = $(this).val();
    if (val === 'change_package') { $('#sched_package_group, #sched_profile_group').show(); }
    else if (val === 'change_profile') { $('#sched_package_group').hide(); $('#sched_profile_group').show(); }
    else { $('#sched_package_group, #sched_profile_group').hide(); }
});

$(document).on('change', '#sched_package_id', function() {
    var profile = $(this).find(':selected').data('mikrotik-profile');
    if (profile && $('#sched_mikrotik_profile option[value="' + profile + '"]').length) {
        $('#sched_mikrotik_profile').val(profile);
    } else {
        $('#sched_mikrotik_profile').val('');
    }
});

$(document).on('click', '#sched_submit_btn', function() {
    var clientId = $('#sched_client_id').val();
    if (!clientId) { toastr.error('Please select a client.'); return; }
    var action = $('#sched_action').val();
    if (action === 'change_package' && !$('#sched_package_id').val()) { toastr.error('Please select a package.'); return; }
    if ((action === 'change_package' || action === 'change_profile') && !$('#sched_mikrotik_profile').val()) { toastr.error('Please select a MikroTik profile.'); return; }
    if (!$('#sched_datetime').val()) { toastr.error('Please select a date and time.'); return; }

    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Creating...');
    $.ajax({
        url: '{{ route("admin.client.scheduler.store") }}',
        type: 'POST',
        headers: { 'Accept': 'application/json' },
        data: {
            client_id: clientId,
            action: action,
            scheduled_at: $('#sched_datetime').val(),
            package_id: $('#sched_package_id').val() || null,
            mikrotik_profile: $('#sched_mikrotik_profile').val() || null,
            note: $('#sched_note').val() || null
        },
        success: function(res) {
            if (res.success) {
                $('#createScheduleModal').modal('hide');
                toastr.success(res.message);
                addScheduleRow(res.schedule);
                resetScheduleForm();
            } else {
                toastr.error(res.message || 'Failed to create schedule.');
            }
        },
        error: function(xhr) {
            var msg = 'Failed to create schedule.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                else if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
            }
            toastr.error(msg);
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i>Create Schedule');
        }
    });
});

$(document).on('click', '.cancel-schedule-btn', function() {
    var id = $(this).data('id');
    var btn = $(this);
    smartConfirm({message:'Cancel this scheduled task?',confirmText:'Cancel Task'}, function() {
        btn.prop('disabled', true);
        $.ajax({
            url: '/admin/client/scheduler/' + id,
            type: 'DELETE',
            headers: { 'Accept': 'application/json' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    toastr.error(res.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                toastr.error('Failed to cancel schedule.');
                btn.prop('disabled', false);
            }
        });
    });
});

$(document).on('click', '.edit-schedule-btn', function() {
    var btn = $(this);
    $('#edit_schedule_id').val(btn.data('id'));
    $('#edit_action').val(btn.data('action'));
    toggleEditFields(btn.data('action'));
    $('#edit_datetime').val(btn.data('scheduled-at'));
    $('#edit_package_id').val(btn.data('package-id') || '');
    $('#edit_mikrotik_profile').val(btn.data('mikrotik-profile') || '');
    $('#edit_note').val(btn.data('note') || '');
    $('#editScheduleModal').modal('show');
});

$(document).on('change', '#edit_action', function() { toggleEditFields($(this).val()); });

$(document).on('change', '#edit_package_id', function() {
    var profile = $(this).find(':selected').data('mikrotik-profile');
    if (profile && $('#edit_mikrotik_profile option[value="' + profile + '"]').length) {
        $('#edit_mikrotik_profile').val(profile);
    } else {
        $('#edit_mikrotik_profile').val('');
    }
});

$(document).on('click', '#edit_submit_btn', function() {
    var id = $('#edit_schedule_id').val();
    var action = $('#edit_action').val();
    if (action === 'change_package' && !$('#edit_package_id').val()) { toastr.error('Please select a package.'); return; }
    if ((action === 'change_package' || action === 'change_profile') && !$('#edit_mikrotik_profile').val()) { toastr.error('Please select a MikroTik profile.'); return; }
    if (!$('#edit_datetime').val()) { toastr.error('Please select a date and time.'); return; }

    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Updating...');
    $.ajax({
        url: '/admin/client/scheduler/' + id,
        type: 'PUT',
        headers: { 'Accept': 'application/json' },
        data: {
            action: action,
            scheduled_at: $('#edit_datetime').val(),
            package_id: $('#edit_package_id').val() || null,
            mikrotik_profile: $('#edit_mikrotik_profile').val() || null,
            note: $('#edit_note').val() || null
        },
        success: function(res) {
            if (res.success) {
                $('#editScheduleModal').modal('hide');
                toastr.success(res.message);
                $('#schedule-row-' + id).remove();
                addScheduleRow(res.schedule);
            } else {
                toastr.error(res.message || 'Failed to update schedule.');
            }
        },
        error: function(xhr) {
            var msg = 'Failed to update schedule.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                else if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
            }
            toastr.error(msg);
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Update Schedule');
        }
    });
});

updateCountdowns();
setInterval(updateCountdowns, 30000);
</script>
@endpush
