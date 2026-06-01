@extends('layouts.admin')

@section('title', 'Support Tickets')
@section('breadcrumb')
<li class="breadcrumb-item active">Tickets</li>
@endsection

@section('content')
    <style>
    .ticket-card { border-left: 4px solid #007bff; margin-bottom: 15px; border-radius: 8px; overflow: hidden; }
    .ticket-card.priority-low { border-left-color: #17a2b8; }
    .ticket-card.priority-medium { border-left-color: #007bff; }
    .ticket-card.priority-high { border-left-color: #ffc107; }
    .ticket-card.priority-urgent { border-left-color: #dc3545; }
    .ticket-card .card-body { padding: 15px; }
    .ticket-card .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .ticket-card .ticket-title { font-weight: 700; font-size: 16px; color: #1a202c; margin: 0; }
    .ticket-card .ticket-info { font-size: 14px; color: #4a5568; margin-bottom: 5px; }
    .ticket-card .ticket-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; font-size: 12px; }
    .bg-info-light { background-color: #e1f5fe; color: #01579b; border: 1px solid #b3e5fc; }
    .badge-outline-secondary { border: 1px solid #cbd5e0; color: #4a5568; background: transparent; }
    .btn-group-mobile { display: flex; gap: 10px; }
    .btn-group-mobile .btn { font-weight: 600; border-radius: 6px; }
    @media (min-width: 768px) { .mobile-cards { display: none; } }
    @media (max-width: 767px) { .desktop-table { display: none; } }
    </style>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h3 class="card-title mb-2 mb-md-0">Support Tickets</h3>
        @if($__canAction('support', 'create_ticket'))
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createTicketModal">
            <i class="fas fa-plus mr-1"></i> Create Ticket
        </button>
        @endif
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-12 col-md-4 mb-2">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <input type="text" id="ticketSearchInput" class="form-control" placeholder="Search ticket#, client, subject..." value="{{ request('search') }}">
                    <div class="input-group-append"><button class="btn btn-info" id="ticketSearchBtn" type="button">Go</button></div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <select class="form-control form-control-sm" onchange="filterTickets('status', this.value)">
                    <option value="">All Status</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <select class="form-control form-control-sm" onchange="filterTickets('priority', this.value)">
                    <option value="">All Priority</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
        </div>

    <div class="mobile-cards">
        @forelse($tickets as $ticket)
        @php
            $priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
            $statusColors = ['open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
            $ticketMeta = $ticket->metadata ? json_decode($ticket->metadata, true) : [];
            $isNewConnection = str_contains(strtolower($ticket->category->name ?? ''), 'new connection') || str_contains(strtolower($ticket->category->name ?? ''), 'installation');
        @endphp
        <div class="card ticket-card priority-{{ $ticket->priority }} shadow-sm">
            <div class="card-body">
                <div class="ticket-header border-bottom pb-2 mb-2">
                    <h6 class="ticket-title font-weight-bold text-primary">
                        <i class="fas {{ $isNewConnection ? 'fa-plug' : 'fa-ticket-alt' }} mr-1"></i>
                        {{ $ticket->client->name ?? ($ticketMeta['customer_name'] ?? 'New Customer') }}
                    </h6>
                    <span class="badge badge-{{ $statusColors[$ticket->status] ?? 'secondary' }} px-2 py-1">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                </div>

                @if($isNewConnection)
                <div class="bg-light p-2 rounded mb-2 border">
                    <div class="ticket-info mb-1"><i class="fas fa-map-marker-alt mr-2 text-danger"></i><strong>Address:</strong> {{ $ticketMeta['customer_address'] ?? 'N/A' }}</div>
                    <div class="ticket-info mb-1"><i class="fas fa-phone mr-2 text-success"></i><strong>Phone:</strong> {{ $ticketMeta['customer_phone'] ?? 'N/A' }}</div>
                    <div class="row no-gutters mt-2 border-top pt-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Package</small>
                            <span class="font-weight-bold text-info">{{ $ticketMeta['package'] ?? 'N/A' }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Installation</small>
                            <span class="font-weight-bold text-success">৳{{ $ticketMeta['installation_charge'] ?? '0' }}</span>
                        </div>
                    </div>
                    @if(isset($ticketMeta['connection_time']))
                    <div class="mt-2 pt-2 border-top">
                        <small class="text-muted d-block">Connection Time</small>
                        <span class="font-weight-bold text-warning"><i class="far fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($ticketMeta['connection_time'])->format('d-m-y h:i A') }}</span>
                    </div>
                    @endif
                </div>
                @else
                <div class="ticket-info font-weight-bold text-dark mb-1">{{ Str::limit($ticket->subject, 60) }}</div>
                <div class="ticket-info text-muted small mb-2">{{ Str::limit($ticket->description, 100) }}</div>
                @endif

                <div class="ticket-meta d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge badge-outline-secondary">{{ $ticket->category->name ?? 'N/A' }}</span>
                        <span class="badge badge-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">{{ ucfirst($ticket->priority) }}</span>
                    </div>
                    <small class="text-muted"><i class="fas fa-calendar-alt mr-1"></i>{{ $ticket->created_at->format('d M') }}</small>
                </div>

                @if($ticket->technician)
                <div class="ticket-info mb-3 p-1 px-2 bg-info-light rounded d-inline-block small">
                    <i class="fas fa-user-cog mr-1 text-info"></i>Assigned: <strong>{{ $ticket->technician->name }}</strong>
                </div>
                @endif

                <div class="btn-group-mobile d-flex gap-2">
                    <a href="{{ route('admin.support.tickets.show', $ticket) }}" class="btn btn-primary flex-fill">
                        <i class="fas fa-eye mr-1"></i> Details
                    </a>
                    @php 
                        $callPhone = $ticket->client->phone ?? ($ticketMeta['customer_phone'] ?? null);
                    @endphp
                    @if($callPhone)
                    <a href="tel:{{ $callPhone }}" class="btn btn-success flex-fill">
                        <i class="fas fa-phone-alt mr-1"></i> Call
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5 bg-white rounded border">
            <i class="fas fa-ticket-alt fa-3x mb-3 text-light"></i>
            <p>No active tickets found</p>
        </div>
        @endforelse
    </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Ticket No</th>
                            <th>Client</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Technician</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr>
                            <td><strong>{{ $ticket->ticket_no }}</strong></td>
                            <td>
                                {{ $ticket->client->name ?? 'N/A' }}
                                <br><small class="text-muted">{{ $ticket->client->phone ?? '' }}</small>
                            </td>
                            <td>{{ Str::limit($ticket->subject, 35) }}</td>
                            <td><span class="badge badge-secondary">{{ $ticket->category->name ?? 'N/A' }}</span></td>
                            <td>
                                @php
                                    $priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
                                @endphp
                                <span class="badge badge-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">{{ ucfirst($ticket->priority) }}</span>
                            </td>
                            <td>
                                @php
                                    $statusColors = ['open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
                                @endphp
                                <span class="badge badge-{{ $statusColors[$ticket->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                                @if($ticket->sla_breached)
                                <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> SLA</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->technician)
                                    <small>{{ $ticket->technician->name }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $ticket->created_at->format('d-m-Y') }}</td>
                            <td class="d-flex" style="gap:4px">
                                <a href="{{ route('admin.support.tickets.show', $ticket) }}" class="btn btn-info btn-xs">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="{{ route('admin.support.tickets.destroy', $ticket) }}" method="POST"
                                      onsubmit="return confirm('Delete ticket {{ $ticket->ticket_no }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No tickets found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $tickets->links() }}
    </div>
</div>

<div class="modal fade" id="createTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.support.tickets.create') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Create Support Ticket</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Row 1: Category | Subject -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Category</strong> <span class="text-danger">*</span></label>
                                <select name="category_id" id="categorySelect" class="form-control" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" data-name="{{ strtolower($category->name) }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6" id="subjectCol">
                            <div class="form-group">
                                <label><strong>Subject</strong> <span class="text-danger">*</span></label>
                                <select id="subjectSelect" name="subject" class="form-control" style="display:none;">
                                    <option value="">Select Subject</option>
                                </select>
                                <input type="text" name="subject" id="subjectInput" class="form-control" placeholder="Enter ticket subject">
                                <small class="text-muted mt-1" id="subjectCustomHint" style="display:none;"><a href="#" id="subjectTypeCustom">Or type custom subject</a></small>
                            </div>
                        </div>
                    </div>

                    <!-- Regular Ticket Fields (for existing clients) -->
                    <div id="regularTicketFields">
                        <!-- Row 2: Priority | Client -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Priority</strong> <span class="text-danger">*</span></label>
                                    <select name="priority" class="form-control" required>
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @include('admin.partials.client-search', [
                                'inputId' => 'ticketClientSearch',
                                'hiddenId' => 'ticketClientSearchId',
                                'fieldName' => 'client_id',
                                'label' => 'Client',
                                'required' => true,
                            ])
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Description</strong> <span class="text-danger">*</span></label>
                            <textarea name="description" id="descriptionInput" class="form-control" rows="3" placeholder="Describe the issue in detail"></textarea>
                        </div>
                    </div>

                    <!-- New Connection Fields (for new customers) -->
                    <div id="newConnectionFields" style="display:none;">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle mr-2"></i>New Connection - Enter customer details below
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Customer Name</strong></label>
                                    <input type="text" name="new_customer_name" class="form-control" placeholder="Enter customer name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Phone Number</strong></label>
                                    <input type="text" name="new_customer_phone" class="form-control" placeholder="Enter phone number">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Address</strong></label>
                            <textarea name="new_customer_address" class="form-control" rows="2" placeholder="Enter full address"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Package</strong></label>
                                    <select name="package_id" class="form-control">
                                        <option value="">-- Select Package --</option>
                                        @foreach($packages ?? [] as $package)
                                        <option value="{{ $package->id }}">{{ $package->name }} - ৳{{ number_format($package->price) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Installation Charge</strong></label>
                                    <input type="text" name="installation_charge" class="form-control" placeholder="e.g. 2500, Free, TBD">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Connection Time</strong></label>
                            <input type="datetime-local" name="connection_time" class="form-control">
                            <small class="text-muted">When to do the connection</small>
                        </div>
                    </div>

                    <!-- Technician Assignment (always visible) -->
                    <div class="form-group">
                        <label><strong>Department</strong></label>
                        <select id="deptFilter" class="form-control">
                            <option value="">-- Select Department --</option>
                            @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Assign Employee(s)</strong> <span class="text-danger">*</span></label>
                        <select id="employeeSelect" name="technician_ids[]" class="form-control select2-technicians" multiple required>
                            @foreach($departments ?? [] as $dept)
                            <optgroup label="{{ $dept->name }}" data-dept-id="{{ $dept->id }}">
                                @foreach($dept->employees as $emp)
                                @if($emp->user_id)
                                <option value="{{ $emp->user_id }}" data-dept="{{ $dept->id }}">{{ $emp->name }}</option>
                                @else
                                <option value="" disabled data-dept="{{ $dept->id }}" style="color:#aaa;">{{ $emp->name }} (No Account)</option>
                                @endif
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <small class="text-muted">Select a department above to filter, then pick employee(s)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Create Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // ── Search field: preserve existing query params ──────────────────────
    (function(){
        var inp = document.getElementById('ticketSearchInput');
        var btn = document.getElementById('ticketSearchBtn');
        function doSearch(val){
            var p = new URLSearchParams(window.location.search);
            if(val){ p.set('search',val); } else { p.delete('search'); }
            p.delete('page');
            window.location = window.location.pathname + '?' + p.toString();
        }
        if(inp){
            var t;
            inp.addEventListener('input', function(){ clearTimeout(t); var v=this.value; t=setTimeout(function(){ doSearch(v); },400); });
            inp.addEventListener('keydown', function(e){ if(e.key==='Enter'){ clearTimeout(t); doSearch(this.value); } });
        }
        if(btn){ btn.addEventListener('click', function(){ doSearch((inp||{}).value||''); }); }
    })();

    // ── Department filter → Employee cascading select ─────────────────────
    var $deptFilter  = $('#deptFilter');
    var $empSelect   = $('#employeeSelect');
    var allOptgroups = $empSelect.find('optgroup').clone(true); // cache all groups

    function initEmpSelect2() {
        if ($.fn.select2) {
            $empSelect.select2({
                dropdownParent: $('#createTicketModal'),
                placeholder: 'Select employee(s)...',
                allowClear: true
            });
        }
    }

    // FIX 3: Start with EMPTY employee list — require dept selection first
    $empSelect.val(null);
    if ($.fn.select2) { try { $empSelect.select2('destroy'); } catch(e){} }
    $empSelect.empty();
    initEmpSelect2();

    $deptFilter.on('change', function () {
        var deptId = $(this).val();
        $empSelect.val(null);
        if ($.fn.select2) { try { $empSelect.select2('destroy'); } catch(e){} }
        $empSelect.empty();

        if (deptId !== '') {
            // Show only selected department employees
            var $matched = allOptgroups.filter('[data-dept-id="' + deptId + '"]');
            if ($matched.length) {
                $empSelect.append($matched.clone(true));
            }
        }
        // If deptId === '', employee select stays empty
        initEmpSelect2();
    });

    // Reset on modal close
    $('#createTicketModal').on('hidden.bs.modal', function () {
        $deptFilter.val('').trigger('change');
        $('#categorySelect').val('').trigger('change');
    });

    // ── Category → Subject (FIX 1+2) ─────────────────────────────────────
    var categorySubjects = @json(($categories ?? collect())->mapWithKeys(function($c){ return [$c->id => $c->subjects ?? []]; }));

    function showSubjectDropdown(subjects) {
        var $select = $('#subjectSelect');
        var $input  = $('#subjectInput');

        // Destroy select2 if applied, before manipulating
        if ($.fn.select2) { try { $select.select2('destroy'); } catch(e){} }

        if (subjects && subjects.length > 0) {
            $select.empty().append('<option value="">-- Select Subject --</option>');
            subjects.forEach(function(s) {
                var escaped = $('<div>').text(s).html();
                $select.append('<option value="' + escaped + '">' + escaped + '</option>');
            });
            $select.append('<option value="__custom__">✏️ Custom subject...</option>');

            $select.show().attr('name', 'subject');
            $input.hide().removeAttr('name').val('');

            // Reinit select2 on subject select
            if ($.fn.select2) {
                $select.select2({
                    dropdownParent: $('#createTicketModal'),
                    placeholder: '-- Select Subject --',
                    allowClear: false
                });
            }
        } else {
            $select.hide().removeAttr('name').empty();
            $input.show().attr('name', 'subject');
        }
    }

    // Handle custom subject option
    $(document).on('change select2:select', '#subjectSelect', function() {
        if ($(this).val() === '__custom__') {
            if ($.fn.select2) { try { $(this).select2('destroy'); } catch(e){} }
            $(this).hide().removeAttr('name');
            $('#subjectInput').show().attr('name', 'subject').focus();
        }
    });

    $('#subjectTypeCustom').click(function(e) {
        e.preventDefault();
        var $sel = $('#subjectSelect');
        if ($.fn.select2) { try { $sel.select2('destroy'); } catch(e){} }
        $sel.hide().removeAttr('name');
        $('#subjectInput').show().attr('name', 'subject').focus();
    });

    // Main category change handler
    function onCategoryChange() {
        var $opt = $('#categorySelect').find('option:selected');
        var categoryName = ($opt.data('name') || '').toLowerCase();
        var categoryId   = String($('#categorySelect').val() || '');
        var isNewConn    = categoryName.includes('new connection') || categoryName.includes('installation');

        var subjects = (categoryId && categorySubjects[categoryId]) ? categorySubjects[categoryId] : [];
        showSubjectDropdown(subjects);

        if (isNewConn) {
            $('#regularTicketFields').hide();
            $('#newConnectionFields').show();
            $('#subjectCol').hide();
            $('#ticketClientSearchId').removeAttr('required');
            $('#subjectInput').removeAttr('required');
            $('#subjectSelect').removeAttr('required');
            $('#descriptionInput').removeAttr('required');
        } else {
            $('#regularTicketFields').show();
            $('#newConnectionFields').hide();
            $('#subjectCol').show();
            $('#ticketClientSearchId').attr('required', 'required');
            if (subjects && subjects.length > 0) {
                $('#subjectSelect').attr('required', 'required');
                $('#subjectInput').removeAttr('required');
            } else {
                $('#subjectInput').attr('required', 'required');
                $('#subjectSelect').removeAttr('required');
            }
            $('#descriptionInput').attr('required', 'required');
        }
    }

    $('#categorySelect').on('change', onCategoryChange);
    // Also handle select2:select in case select2 is applied to category
    $('#categorySelect').on('select2:select select2:unselecting', function(){ setTimeout(onCategoryChange, 50); });
});

// FIX 4: filterTickets — preserve all other query params
function filterTickets(param, value) {
    var p = new URLSearchParams(window.location.search);
    if (value) { p.set(param, value); } else { p.delete(param); }
    p.delete('page');
    window.location = window.location.pathname + '?' + p.toString();
}
</script>
@endpush
