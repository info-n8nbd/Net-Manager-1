@extends('layouts.admin')

@section('title', 'Ticket Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.support.tickets') }}">Tickets</a></li>
<li class="breadcrumb-item active">{{ $ticket->ticket_no }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ $ticket->subject }}</h3>
                <div>
                    @if($__canAction('support', 'edit_ticket'))
                    <a href="{{ route('admin.support.tickets.edit', $ticket) }}" class="btn btn-info btn-sm"><i class="fas fa-edit mr-1"></i>Edit</a>
                    @endif
                    @if($__canAction('support', 'delete_ticket'))
                    <form action="{{ route('admin.support.tickets.destroy', $ticket) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this ticket and all messages?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash mr-1"></i>Delete</button>
                    </form>
                    @endif
                    @if($__canAction('support', 'close_ticket'))
                    @if($ticket->status == 'open' || $ticket->status == 'in_progress')
                    <form action="{{ route('admin.support.tickets.resolve', $ticket) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check mr-1"></i>Resolve</button>
                    </form>
                    <form action="{{ route('admin.support.tickets.close', $ticket) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-times mr-1"></i>Close</button>
                    </form>
                    @elseif($ticket->status == 'resolved')
                    <form action="{{ route('admin.support.tickets.close', $ticket) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-times mr-1"></i>Close</button>
                    </form>
                    <form action="{{ route('admin.support.tickets.reopen', $ticket) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-redo mr-1"></i>Reopen</button>
                    </form>
                    @elseif($ticket->status == 'closed')
                    <form action="{{ route('admin.support.tickets.reopen', $ticket) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-redo mr-1"></i>Reopen</button>
                    </form>
                    @endif
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="bg-light p-3 rounded mb-4">
                    <small class="text-muted">Description:</small>
                    <p class="mb-0">{{ $ticket->description }}</p>
                </div>
                
                <h5><i class="fas fa-comments mr-2"></i>Conversation</h5>
                <div class="direct-chat-messages" style="max-height: 400px; overflow-y: auto;">
                    @forelse($ticket->messages as $message)
                    <div class="direct-chat-msg {{ $message->is_staff_reply ? 'right' : '' }} mb-3">
                        <div class="direct-chat-infos clearfix">
                            <span class="direct-chat-name float-{{ $message->is_staff_reply ? 'right' : 'left' }}">
                                <strong>{{ $message->user->name ?? 'Client' }}</strong>
                            </span>
                            <span class="direct-chat-timestamp float-{{ $message->is_staff_reply ? 'left' : 'right' }}">
                                {{ $message->created_at->format('d-m-y H:i') }}
                            </span>
                        </div>
                        <div class="direct-chat-text {{ $message->is_staff_reply ? 'bg-primary' : 'bg-light text-dark' }}">
                            {{ $message->message }}
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3"><i class="fas fa-inbox"></i> No messages yet</p>
                    @endforelse
                </div>
            </div>
            <div class="card-footer">
                <form action="{{ route('admin.support.tickets.reply', $ticket) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <textarea name="message" class="form-control" rows="3" placeholder="Type your reply..." required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <select name="status" class="form-control">
                                <option value="">Keep Current Status</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane mr-1"></i>Send Reply
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Ticket Info</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Ticket No:</strong></td>
                        <td>{{ $ticket->ticket_no }}</td>
                    </tr>
                    <tr>
                        <td><strong>Client:</strong></td>
                        <td>
                            @if($ticket->client)
                            <a href="{{ route('admin.clients.show', $ticket->client) }}">{{ $ticket->client->name }}</a>
                            @else
                            @php $meta = $ticket->metadata ? json_decode($ticket->metadata, true) : []; @endphp
                            {{ $meta['customer_name'] ?? 'New Customer' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td>
                            @if($ticket->client)
                            {{ $ticket->client->phone }}
                            @else
                            @php $meta = $meta ?? ($ticket->metadata ? json_decode($ticket->metadata, true) : []); @endphp
                            {{ $meta['customer_phone'] ?? 'N/A' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Category:</strong></td>
                        <td><span class="badge badge-secondary">{{ $ticket->category->name ?? 'N/A' }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Priority:</strong></td>
                        <td>
                            @php
                                $priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
                            @endphp
                            <span class="badge badge-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">{{ ucfirst($ticket->priority) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            @php
                                $statusColors = ['open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
                            @endphp
                            <span class="badge badge-{{ $statusColors[$ticket->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                            @if($ticket->sla_breached)
                            <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> SLA Breached</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td>{{ $ticket->created_at->format('d-m-y H:i') }}</td>
                    </tr>
                    @if($ticket->sla_deadline)
                    <tr>
                        <td><strong>SLA Deadline:</strong></td>
                        <td class="{{ now()->gt($ticket->sla_deadline) ? 'text-danger' : '' }}">
                            {{ $ticket->sla_deadline->format('d-m-y H:i') }}
                        </td>
                    </tr>
                    @endif
                    @if($ticket->resolved_at)
                    <tr>
                        <td><strong>Resolved:</strong></td>
                        <td>{{ $ticket->resolved_at->format('d-m-y H:i') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title"><i class="fas fa-user-cog mr-2"></i>Technician Assignment</h3>
            </div>
            <div class="card-body">
                @php
                    $assignedTechIds = $ticket->technicianAssignments->where('status', '!=', 'removed')->pluck('technician_id')->toArray();
                @endphp
                @if(count($assignedTechIds) > 0)
                <div class="alert alert-success mb-3">
                    <strong><i class="fas fa-user-check mr-2"></i>Currently Assigned:</strong><br>
                    @foreach($ticket->technicianAssignments->where('status', '!=', 'removed') as $assignment)
                        <span class="badge badge-light mr-1 mb-1">{{ $assignment->technician->name ?? 'Unknown' }}</span>
                    @endforeach
                </div>
                @else
                <p class="text-muted mb-3"><i class="fas fa-user-xmark"></i> No technicians assigned</p>
                @endif
                
                @if($__canAction('support', 'assign_ticket'))
                <form action="{{ route('admin.support.tickets.assign-technician', $ticket) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label><strong>Department</strong></label>
                        <select id="deptFilterShow" class="form-control">
                            <option value="">-- All Departments --</option>
                            @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Assign/Update Employee(s):</strong> <span class="text-danger">*</span></label>
                        <select id="empSelectShow" name="technician_ids[]" class="form-control select2-multi" multiple required>
                            @foreach($departments ?? [] as $dept)
                            <optgroup label="{{ $dept->name }}" data-dept-id="{{ $dept->id }}">
                                @foreach($dept->employees as $emp)
                                <option value="{{ $emp->user_id }}" data-dept="{{ $dept->id }}" {{ in_array($emp->user_id, $assignedTechIds) ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <small class="text-muted">Select a department to filter employees</small>
                    </div>
                    <div class="form-group">
                        <label>Notes (optional):</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus mr-1"></i>Update Assignment
                    </button>
                </form>
                @endif
                <small class="text-muted"><i class="fas fa-bell"></i> Technicians will be notified</small>
            </div>
        </div>

        @if($ticket->technicianAssignments && $ticket->technicianAssignments->count() > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Assignment History</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($ticket->technicianAssignments as $assignment)
                    @php
                        $asBadge = match($assignment->status) {
                            'completed'   => 'success',
                            'in_progress' => 'info',
                            'removed'     => 'danger',
                            'reassigned'  => 'secondary',
                            default       => 'warning',
                        };
                    @endphp
                    <li class="list-group-item">
                        <strong>{{ $assignment->technician->name ?? 'Unknown' }}</strong>
                        <span class="badge badge-{{ $asBadge }} float-right">
                            {{ ucfirst($assignment->status) }}
                        </span>
                        <br>
                        <small class="text-muted">
                            Assigned: {{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('d-m-y H:i') : 'N/A' }}
                            @if($assignment->completed_at)
                            | Completed: {{ \Carbon\Carbon::parse($assignment->completed_at)->format('d-m-y H:i') }}
                            @endif
                        </small>
                        @if($assignment->notes)
                        <div class="mt-1">
                            <small class="text-secondary"><i class="fas fa-sticky-note mr-1"></i><em>{{ $assignment->notes }}</em></small>
                        </div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if($ticket->feedback)
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-star mr-2"></i>Client Feedback</h3>
            </div>
            <div class="card-body text-center">
                <div class="mb-2">
                    @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star {{ $i <= $ticket->feedback->rating ? 'text-warning' : 'text-muted' }}" style="font-size: 24px;"></i>
                    @endfor
                </div>
                <p class="mb-0">{{ $ticket->feedback->comment ?? 'No comment provided' }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var $deptFilter  = $('#deptFilterShow');
    var $empSelect   = $('#empSelectShow');
    var allOptgroups = $empSelect.find('optgroup').clone(true);

    function initEmpSelect2Show() {
        if ($.fn.select2) {
            $empSelect.select2({ placeholder: 'Select employee(s)...', allowClear: true });
        }
    }
    initEmpSelect2Show();

    $deptFilter.on('change', function () {
        var deptId = $(this).val();
        $empSelect.val(null);
        if ($.fn.select2) $empSelect.select2('destroy');
        $empSelect.empty();

        if (deptId === '') {
            allOptgroups.each(function () { $empSelect.append($(this).clone(true)); });
        } else {
            var $matched = allOptgroups.filter('[data-dept-id="' + deptId + '"]');
            if ($matched.length) $empSelect.append($matched.clone(true));
        }
        initEmpSelect2Show();
    });
});
</script>
@endpush
