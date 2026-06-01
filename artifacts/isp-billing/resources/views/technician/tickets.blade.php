@extends('layouts.technician')

@section('title', 'Tickets')
@section('breadcrumb')
<li class="breadcrumb-item active">Tickets</li>
@endsection

@section('content')
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
.ticket-card .btn-group-mobile { display: flex; flex-wrap: wrap; gap: 6px; }
.ticket-card .btn-group-mobile .btn { flex: 1; min-width: 70px; font-size: 13px; padding: 6px 10px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } .info-box { margin-bottom: 10px; } }
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
        <h3 class="card-title"><i class="fas fa-ticket-alt mr-2"></i>My Assigned Tickets</h3>
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
                    <div class="ticket-info">
                        <strong>{{ $ticket->ticket_no }}</strong> | {{ $ticket->client->phone ?? ($ticketMeta['customer_phone'] ?? '') }}
                    </div>
                    <div class="ticket-info">{{ Str::limit($ticket->subject, 50) }}</div>
                    <div class="ticket-meta">
                        <span><i class="fas fa-tag mr-1"></i>{{ $ticket->category->name ?? 'N/A' }}</span>
                        <span class="badge badge-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">{{ ucfirst($ticket->priority) }}</span>
                        <span><i class="fas fa-clock mr-1"></i>{{ $ticket->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="btn-group-mobile">
                        <a href="{{ route('technician.tickets.view', $ticket) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        @if($ticket->client && $ticket->client->phone)
                        <a href="tel:{{ $ticket->client->phone }}" class="btn btn-success btn-sm">
                            <i class="fas fa-phone mr-1"></i> Call
                        </a>
                        @elseif(isset($ticketMeta['customer_phone']))
                        <a href="tel:{{ $ticketMeta['customer_phone'] }}" class="btn btn-success btn-sm">
                            <i class="fas fa-phone mr-1"></i> Call
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No tickets assigned to you</div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>#</th>
                            <th>Ticket No</th>
                            <th>Client</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><a href="{{ route('technician.tickets.view', $ticket) }}" class="font-weight-bold">{{ $ticket->ticket_no }}</a></td>
                            <td>
                                {{ $ticket->client->name ?? 'New Customer' }}<br>
                                <small class="text-muted">{{ $ticket->client->zone->name ?? '' }}</small>
                            </td>
                            <td>{{ Str::limit($ticket->subject, 30) }}</td>
                            <td>
                                @php
                                    $priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
                                @endphp
                                <span class="badge badge-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $ticket->status == 'resolved' ? 'success' : ($ticket->status == 'in_progress' ? 'info' : 'warning') }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td>{{ $ticket->created_at->format('d-m-y H:i') }}</td>
                            <td>
                                <a href="{{ route('technician.tickets.view', $ticket) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center">No tickets assigned to you</td></tr>
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
            <form action="{{ route('technician.tickets.create') }}" method="POST">
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
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="regularTicketFields">
                        <div class="row">
                            <div class="col-md-6">
                            @include('admin.partials.client-search', [
                                'inputId' => 'techTicketClientSearch',
                                'hiddenId' => 'techTicketClientSearchId',
                                'fieldName' => 'client_id',
                                'label' => 'Client',
                                'required' => true,
                                'searchUrl' => route('technician.api.search-clients'),
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

                    <div id="newConnectionFields" style="display:none;">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle mr-2"></i>New Connection - Enter customer details
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Customer Name</strong></label>
                                    <input type="text" name="new_customer_name" class="form-control" placeholder="Enter name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Phone Number</strong></label>
                                    <input type="text" name="new_customer_phone" class="form-control" placeholder="Enter phone">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Address</strong></label>
                            <textarea name="new_customer_address" class="form-control" rows="2" placeholder="Enter address"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Package</strong></label>
                                    <select name="package_id" class="form-control">
                                        <option value="">-- Select Package --</option>
                                        @foreach($packages ?? [] as $package)
                                        <option value="{{ $package->id }}">{{ $package->name }} - ৳{{ number_format($package->price ?? 0) }}</option>
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
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Assign Technician(s)</strong> <span class="text-danger">*</span></label>
                        <select name="technician_ids[]" class="form-control select2-technicians" multiple required>
                            @foreach($technicians ?? [] as $tech)
                            <option value="{{ $tech->id }}" {{ $tech->id == auth()->id() ? 'selected' : '' }}>{{ $tech->name }}</option>
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
        
        if (isNewConnection) {
            $('#regularTicketFields').hide();
            $('#newConnectionFields').show();
            $('#techTicketClientSearchId').removeAttr('required');
            $('#subjectInput').removeAttr('required');
            $('#descriptionInput').removeAttr('required');
        } else {
            $('#regularTicketFields').show();
            $('#newConnectionFields').hide();
            $('#techTicketClientSearchId').attr('required', 'required');
            $('#subjectInput').attr('required', 'required');
            $('#descriptionInput').attr('required', 'required');
        }
    });
});
</script>
@endpush
