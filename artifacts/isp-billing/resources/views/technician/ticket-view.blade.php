@extends('layouts.technician')

@section('title', 'Ticket Details - ' . $ticket->ticket_no)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('technician.tickets') }}">Tickets</a></li>
<li class="breadcrumb-item active">{{ $ticket->ticket_no }}</li>
@endsection

@section('content')
<style>
@media (max-width: 767px) {
    .card-header .card-tools { margin-top: 5px; }
    .card-header { flex-wrap: wrap; }
    .btn-group-action { display: flex; flex-wrap: wrap; gap: 6px; }
    .btn-group-action .btn { flex: 1; min-width: 80px; }
    .timeline-item { padding-left: 30px !important; }
}
</style>
<div class="row">
    <div class="col-lg-8 col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title"><i class="fas fa-ticket-alt mr-2"></i>{{ $ticket->ticket_no }}</h3>
                <div class="card-tools">
                    @php
                        $statusColors = ['open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
                    @endphp
                    <span class="badge badge-{{ $statusColors[$ticket->status] ?? 'secondary' }} badge-lg">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Subject:</strong>
                        <p class="mb-2">{{ $ticket->subject }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Category:</strong>
                        <p class="mb-2">{{ $ticket->category->name ?? 'N/A' }}</p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Priority:</strong>
                        @php
                            $priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
                        @endphp
                        <p><span class="badge badge-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">{{ ucfirst($ticket->priority) }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Created:</strong>
                        <p class="mb-2">{{ $ticket->created_at->format('d-m-y h:i A') }}</p>
                    </div>
                </div>

                <div class="mb-3">
                    <strong>Description:</strong>
                    <div class="bg-light p-3 rounded mt-2">
                        {!! nl2br(e($ticket->description)) !!}
                    </div>
                </div>
                
                @if($ticket->client)
                <div class="alert alert-info">
                    <h5><i class="fas fa-user mr-2"></i>Client Information</h5>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Client ID:</strong> {{ $ticket->client->client_id ?? 'N/A' }}<br>
                            <strong>Name:</strong> {{ $ticket->client->name }}<br>
                            <strong>Phone:</strong> <a href="tel:{{ $ticket->client->phone }}">{{ $ticket->client->phone }}</a>
                        </div>
                        <div class="col-md-6">
                            <strong>Zone:</strong> {{ $ticket->client->zone->name ?? 'N/A' }}<br>
                            <strong>Package:</strong> {{ $ticket->client->package->name ?? 'N/A' }}<br>
                            <strong>Address:</strong> {{ $ticket->client->address }}
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-info">
                    <h5><i class="fas fa-user-plus mr-2"></i>New Connection Request</h5>
                    @if(!empty($ticketMetadata) && (isset($ticketMetadata['customer_name']) || isset($ticketMetadata['customer_phone'])))
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Customer Name:</strong> {{ $ticketMetadata['customer_name'] ?? 'N/A' }}<br>
                            <strong>Phone:</strong> 
                            @if(isset($ticketMetadata['customer_phone']))
                            <a href="tel:{{ $ticketMetadata['customer_phone'] }}" class="btn btn-sm btn-success ml-2">
                                <i class="fas fa-phone"></i> {{ $ticketMetadata['customer_phone'] }}
                            </a>
                            @else
                            N/A
                            @endif
                            <br><br>
                            <strong>Address:</strong><br>
                            {{ $ticketMetadata['customer_address'] ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Package:</strong> {{ $ticketMetadata['package'] ?? 'Not Selected' }}<br>
                            <strong>Installation Charge:</strong> 
                            @if(isset($ticketMetadata['installation_charge']) && $ticketMetadata['installation_charge'])
                                @if(is_numeric($ticketMetadata['installation_charge']))
                                    ৳{{ number_format($ticketMetadata['installation_charge'] ?? 0) }}
                                @else
                                    {{ $ticketMetadata['installation_charge'] }}
                                @endif
                            @else
                                N/A
                            @endif
                            <br>
                            <strong>Connection Time:</strong> 
                            @if(isset($ticketMetadata['connection_time']) && $ticketMetadata['connection_time'])
                                {{ \Carbon\Carbon::parse($ticketMetadata['connection_time'])->format('d-m-y h:i A') }}
                            @else
                                Not Scheduled
                            @endif
                        </div>
                    </div>
                    @else
                    <p class="mb-0">Customer details are in the description above.</p>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-comments mr-2"></i>Notes & Updates</h3>
            </div>
            <div class="card-body">
                @forelse($ticket->messages as $message)
                <div class="direct-chat-msg {{ $message->is_staff_reply ? 'right' : '' }}">
                    <div class="direct-chat-infos clearfix">
                        <span class="direct-chat-name {{ $message->is_staff_reply ? 'float-right' : 'float-left' }}">
                            {{ $message->user->name ?? 'System' }}
                        </span>
                        <span class="direct-chat-timestamp {{ $message->is_staff_reply ? 'float-left' : 'float-right' }}">
                            {{ $message->created_at->format('d-m-y h:i A') }}
                        </span>
                    </div>
                    <div class="direct-chat-text {{ $message->is_staff_reply ? 'bg-primary' : 'bg-light' }}">
                        {{ $message->message }}
                    </div>
                </div>
                @empty
                <p class="text-muted text-center">No notes yet</p>
                @endforelse

                <hr>
                <form action="{{ route('technician.tickets.add-note', $ticket) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label><strong>Add Note</strong></label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Write your note or update here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i>Add Note</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-12 mt-3 mt-lg-0">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Update Status</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('technician.tickets.update-status', $ticket) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label><strong>Change Status</strong></label>
                        <select name="status" class="form-control">
                            <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i>Update Status</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Assigned Technicians</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($assignments as $assignment)
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
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <i class="fas fa-user-cog mr-2 text-primary"></i>
                                <strong>{{ $assignment->technician->name ?? 'Unknown' }}</strong>
                                @if($assignment->technician_id == Auth::id())
                                <span class="badge badge-info ml-1">You</span>
                                @endif
                                <br>
                                <small class="text-muted ml-4">
                                    Assigned: {{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('d-m-y H:i') : 'N/A' }}
                                </small>
                            </div>
                            <span class="badge badge-{{ $asBadge }}">
                                {{ ucfirst($assignment->status) }}
                            </span>
                        </div>
                        @if($assignment->notes)
                        <div class="mt-1 ml-4">
                            <small class="text-secondary"><i class="fas fa-sticky-note mr-1"></i><em>{{ $assignment->notes }}</em></small>
                        </div>
                        @endif
                    </li>
                    @empty
                    <li class="list-group-item text-muted">No technicians assigned</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Timeline</h3>
            </div>
            <div class="card-body">
                <div class="timeline timeline-inverse">
                    <div class="time-label">
                        <span class="bg-primary">{{ $ticket->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div>
                        <i class="fas fa-plus bg-success"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> {{ $ticket->created_at->format('h:i A') }}</span>
                            <h3 class="timeline-header">Ticket Created</h3>
                        </div>
                    </div>
                    @if($ticket->resolved_at)
                    <div>
                        <i class="fas fa-check bg-success"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> {{ $ticket->resolved_at->format('h:i A') }}</span>
                            <h3 class="timeline-header">Resolved</h3>
                        </div>
                    </div>
                    @endif
                    @if($ticket->closed_at)
                    <div>
                        <i class="fas fa-times bg-secondary"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> {{ $ticket->closed_at->format('h:i A') }}</span>
                            <h3 class="timeline-header">Closed</h3>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <a href="{{ route('technician.tickets') }}" class="btn btn-secondary btn-block mb-3">
            <i class="fas fa-arrow-left mr-1"></i>Back to Tickets
        </a>
    </div>
</div>
@endsection
