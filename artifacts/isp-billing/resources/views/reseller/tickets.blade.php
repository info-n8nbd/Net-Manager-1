@extends('layouts.reseller')

@section('title', 'Support & Tickets')
@section('breadcrumb')
<li class="breadcrumb-item active">Support & Tickets</li>
@endsection

@section('content')
<style>
.bw-ticket-header{background:linear-gradient(135deg,#0a1628 0%,#1d4ed8 100%);border-radius:14px;padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.bw-ticket-header h2{color:#fff;font-size:18px;font-weight:700;margin:0 0 3px;}
.bw-ticket-header p{color:rgba(255,255,255,.65);font-size:12px;margin:0;}
.tkt-stat-card{background:#fff;border-radius:12px;padding:16px 20px;box-shadow:0 2px 10px rgba(0,0,0,.07);border:1px solid #f1f5f9;text-align:center;}
.tkt-stat-num{font-size:26px;font-weight:700;line-height:1;}
.tkt-stat-lbl{font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.7px;margin-top:4px;}
.tkt-card{background:#fff;border-radius:12px;border:1px solid #f1f5f9;box-shadow:0 2px 10px rgba(0,0,0,.07);margin-bottom:12px;overflow:hidden;transition:box-shadow .2s;}
.tkt-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.12);}
.tkt-card-body{padding:16px 20px;}
.tkt-no{font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.5px;}
.tkt-subject{font-size:15px;font-weight:700;color:#0f172a;margin:4px 0 8px;}
.tkt-desc{font-size:13px;color:#64748b;margin-bottom:10px;}
.tkt-meta{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
.tkt-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;}
.tkt-badge.open{background:#fffbeb;color:#b45309;}
.tkt-badge.in_progress{background:#eff6ff;color:#1d4ed8;}
.tkt-badge.resolved{background:#f0fdf4;color:#15803d;}
.tkt-badge.closed{background:#f8fafc;color:#475569;}
.tkt-badge.pri-low{background:#f0fdf4;color:#15803d;}
.tkt-badge.pri-medium{background:#eff6ff;color:#1d4ed8;}
.tkt-badge.pri-high{background:#fffbeb;color:#b45309;}
.tkt-badge.pri-critical{background:#fef2f2;color:#dc2626;}
.tkt-time{font-size:11px;color:#94a3b8;}
.bw-create-btn{background:linear-gradient(135deg,#1d4ed8,#3b82f6);border:none;color:#fff;border-radius:10px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.bw-create-btn:hover{background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff;text-decoration:none;}
.info-banner{background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;border-radius:10px;padding:14px 18px;margin-bottom:16px;font-size:13px;color:#1e40af;}
</style>

@php $isBandwidth = $isBandwidth ?? false; @endphp

@if($isBandwidth)
{{-- ===== BANDWIDTH RESELLER TICKET VIEW ===== --}}
<div class="bw-ticket-header">
    <div>
        <h2><i class="fas fa-headset mr-2"></i>Support & Tickets</h2>
        <p>Submit and track your bandwidth-related complaints to upstream</p>
    </div>
    <button class="bw-create-btn" data-toggle="modal" data-target="#bwCreateTicketModal">
        <i class="fas fa-plus"></i> New Ticket
    </button>
</div>

<div class="info-banner">
    <i class="fas fa-info-circle mr-2"></i>
    As a <strong>Bandwidth Reseller</strong>, you can submit tickets for bandwidth issues, billing disputes, latency problems, or any service concerns to your upstream provider.
</div>

<div class="row mb-4">
    <div class="col-6 col-md-3 mb-2">
        <div class="tkt-stat-card">
            <div class="tkt-stat-num text-warning">{{ $openTickets }}</div>
            <div class="tkt-stat-lbl">Open</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="tkt-stat-card">
            <div class="tkt-stat-num text-primary">{{ $inProgressTickets }}</div>
            <div class="tkt-stat-lbl">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="tkt-stat-card">
            <div class="tkt-stat-num text-success">{{ $resolvedTickets }}</div>
            <div class="tkt-stat-lbl">Resolved</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="tkt-stat-card">
            <div class="tkt-stat-num" style="color:#6366f1;">{{ $tickets->total() }}</div>
            <div class="tkt-stat-lbl">Total</div>
        </div>
    </div>
</div>

@forelse($tickets as $ticket)
@php
    $statusMap = ['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'];
    $priMap = ['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical','urgent'=>'Urgent'];
@endphp
<div class="tkt-card">
    <div class="tkt-card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="tkt-no">#{{ $ticket->ticket_no ?? $ticket->id }}</div>
                <div class="tkt-subject">{{ $ticket->subject ?? $ticket->title ?? '(No Subject)' }}</div>
                @if($ticket->description)
                <div class="tkt-desc">{{ Str::limit($ticket->description, 120) }}</div>
                @endif
                <div class="tkt-meta">
                    <span class="tkt-badge {{ $ticket->status }}">
                        @if($ticket->status==='open')<i class="fas fa-circle" style="font-size:7px;"></i>
                        @elseif($ticket->status==='in_progress')<i class="fas fa-spinner" style="font-size:9px;"></i>
                        @elseif($ticket->status==='resolved')<i class="fas fa-check" style="font-size:9px;"></i>
                        @else<i class="fas fa-times" style="font-size:9px;"></i>@endif
                        {{ $statusMap[$ticket->status] ?? ucfirst($ticket->status) }}
                    </span>
                    <span class="tkt-badge pri-{{ $ticket->priority }}">{{ $priMap[$ticket->priority] ?? ucfirst($ticket->priority) }}</span>
                    @if($ticket->category)<span style="font-size:11px;color:#64748b;"><i class="fas fa-tag mr-1"></i>{{ $ticket->category->name }}</span>@endif
                    <span class="tkt-time"><i class="fas fa-clock mr-1"></i>{{ $ticket->created_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@empty
<div class="tkt-card">
    <div class="tkt-card-body text-center py-5">
        <i class="fas fa-ticket-alt fa-3x text-muted mb-3 d-block" style="opacity:.3;"></i>
        <p class="text-muted mb-1">No tickets yet</p>
        <small class="text-muted">Click <strong>New Ticket</strong> to submit your first complaint or issue.</small>
    </div>
</div>
@endforelse

{{ $tickets->links() }}

{{-- Bandwidth Create Ticket Modal --}}
<div class="modal fade" id="bwCreateTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <form action="{{ route('reseller.tickets.create') }}" method="POST">
                @csrf
                <div class="modal-header" style="background:linear-gradient(135deg,#0a1628,#1d4ed8);color:#fff;border:none;">
                    <h5 class="modal-title font-weight-700">
                        <i class="fas fa-headset mr-2"></i>Submit Support Ticket
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity:.8;">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="info-banner mb-3">
                        <i class="fas fa-network-wired mr-2"></i>
                        Submit this ticket to your upstream provider regarding bandwidth, latency, billing, or any service issue.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-600"><i class="fas fa-tag mr-1 text-primary"></i>Issue Type <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">-- Select Issue Type --</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-600"><i class="fas fa-exclamation-triangle mr-1 text-warning"></i>Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical / Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-600"><i class="fas fa-heading mr-1 text-primary"></i>Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Bandwidth drop on International link, Billing dispute for May 2026" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-600"><i class="fas fa-align-left mr-1 text-primary"></i>Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe the issue in detail. Include:&#10;- Time when issue started&#10;- Affected bandwidth (e.g. International, GGC)&#10;- Current vs expected Mbps&#10;- Any error messages" required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="bw-create-btn">
                        <i class="fas fa-paper-plane"></i> Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@else
{{-- ===== REGULAR (MAC BINDING) RESELLER TICKET VIEW ===== --}}
<style>
.ticket-card { border-left: 4px solid #007bff; margin-bottom: 15px; }
.ticket-card.priority-low { border-left-color: #17a2b8; }
.ticket-card.priority-medium { border-left-color: #007bff; }
.ticket-card.priority-high { border-left-color: #ffc107; }
.ticket-card.priority-urgent { border-left-color: #dc3545; }
.ticket-card .card-body { padding: 12px 15px; }
.ticket-card .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.ticket-card .ticket-title { font-weight: 600; font-size: 15px; color: #333; margin: 0; }
.ticket-card .ticket-info { font-size: 13px; color: #666; margin-bottom: 5px; }
.ticket-card .ticket-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; font-size: 12px; }
@media (max-width: 767px) { .desktop-table { display: none; } }
@media (min-width: 768px) { .mobile-cards { display: none; } }
</style>

<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
            <div class="stat-number">{{ $openTickets }}</div>
            <div class="stat-label">Open</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $inProgressTickets }}</div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
            <div class="stat-number">{{ $resolvedTickets }}</div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <button class="btn btn-primary btn-block h-100 py-3" data-toggle="modal" data-target="#createTicketModal">
            <i class="fas fa-plus"></i> Create
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-ticket-alt mr-2"></i>My Tickets</h3>
    </div>
    <div class="card-body">
        <div class="mobile-cards">
            @forelse($tickets as $ticket)
            @php
                $priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
                $statusColors = ['open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
                $ticketMeta = $ticket->metadata ? json_decode($ticket->metadata, true) : [];
            @endphp
            <div class="card ticket-card priority-{{ $ticket->priority }}">
                <div class="card-body">
                    <div class="ticket-header">
                        <h6 class="ticket-title">{{ $ticket->client->name ?? ($ticketMeta['customer_name'] ?? 'New Customer') }}</h6>
                        <span class="badge badge-{{ $statusColors[$ticket->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                    </div>
                    <div class="ticket-info"><strong>{{ $ticket->ticket_no }}</strong> | {{ $ticket->client->phone ?? ($ticketMeta['customer_phone'] ?? '') }}</div>
                    <div class="ticket-info">{{ Str::limit($ticket->subject, 50) }}</div>
                    <div class="ticket-meta">
                        <span class="badge badge-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">{{ ucfirst($ticket->priority) }}</span>
                        <span><i class="fas fa-clock mr-1"></i>{{ $ticket->created_at->format('d-m-Y') }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No tickets found</div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>#</th><th>Ticket No</th><th>Client</th><th>Subject</th>
                            <th>Priority</th><th>Status</th><th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        @php $priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger']; @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $ticket->ticket_no }}</td>
                            <td>{{ $ticket->client->name ?? 'New Customer' }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td><span class="badge badge-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">{{ ucfirst($ticket->priority) }}</span></td>
                            <td><span class="badge badge-{{ $ticket->status == 'resolved' ? 'success' : ($ticket->status == 'in_progress' ? 'info' : 'warning') }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></td>
                            <td>{{ $ticket->created_at->format('d-m-y H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No tickets found</td></tr>
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
            <form action="{{ route('reseller.tickets.create') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Create Ticket</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Priority</strong> <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="regularTicketFields">
                        <div class="row">
                            <div class="col-md-6">
                            @include('admin.partials.client-search', [
                                'inputId' => 'resellerTicketClientSearch',
                                'hiddenId' => 'resellerTicketClientSearchId',
                                'fieldName' => 'client_id',
                                'label' => 'Client',
                                'required' => true,
                                'searchUrl' => route('reseller.api.search-clients'),
                            ])
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Subject</strong> <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" id="subjectInput" class="form-control" placeholder="Enter ticket subject">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Description</strong> <span class="text-danger">*</span></label>
                            <textarea name="description" id="descriptionInput" class="form-control" rows="3" placeholder="Describe the issue"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><strong>Assign Technician(s)</strong> <span class="text-danger">*</span></label>
                        <select name="technician_ids[]" class="form-control select2-technicians" multiple required>
                            @foreach($technicians ?? [] as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if($.fn.select2) {
        $('.select2-technicians').select2({
            dropdownParent: $('#createTicketModal'),
            placeholder: 'Select technician(s)...',
            allowClear: true
        });
    }
    $('#categorySelect').change(function() {
        var categoryName = $(this).find('option:selected').data('name') || '';
        var isNewConnection = categoryName.includes('new connection') || categoryName.includes('installation');
        $('#regularTicketFields').toggle(!isNewConnection);
    });
});
</script>
@endpush
