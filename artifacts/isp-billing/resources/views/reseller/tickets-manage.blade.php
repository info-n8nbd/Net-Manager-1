@extends('layouts.reseller')
@section('title', 'Ticket Management')
@section('breadcrumb')<li class="breadcrumb-item active">Tickets</li>@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3>{{ $stats['open'] }}</h3><p>Open</p></div>
            <div class="icon"><i class="fas fa-folder-open"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $stats['in_progress'] }}</h3><p>In Progress</p></div>
            <div class="icon"><i class="fas fa-spinner"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $stats['resolved'] }}</h3><p>Resolved</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner"><h3>{{ $stats['closed'] }}</h3><p>Closed</p></div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ticket-alt mr-2"></i>All Tickets</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Client</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr>
                            <td>#{{ $ticket->id }}</td>
                            <td>{{ Str::limit($ticket->title, 30) }}</td>
                            <td>{{ $ticket->client->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $ticket->priority == 'urgent' ? 'danger' : ($ticket->priority == 'high' ? 'warning' : ($ticket->priority == 'medium' ? 'info' : 'secondary')) }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $ticket->status == 'open' ? 'danger' : ($ticket->status == 'in_progress' ? 'warning' : ($ticket->status == 'resolved' ? 'success' : 'secondary')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td>{{ $ticket->assignedTo->name ?? 'Unassigned' }}</td>
                            <td>
                                <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#editTicketModal{{ $ticket->id }}"><i class="fas fa-edit"></i></button>
                                <form action="{{ route('reseller.tickets-manage.delete', $ticket) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this ticket?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        
                        <div class="modal fade" id="editTicketModal{{ $ticket->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form action="{{ route('reseller.tickets-manage.update', $ticket) }}" method="POST">
                                    @csrf @method('PUT')
                                    <div class="modal-content">
                                        <div class="modal-header"><h5 class="modal-title">Update Ticket</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                                                    <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                    <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                                    <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Priority</label>
                                                <select name="priority" class="form-control">
                                                    <option value="low" {{ $ticket->priority == 'low' ? 'selected' : '' }}>Low</option>
                                                    <option value="medium" {{ $ticket->priority == 'medium' ? 'selected' : '' }}>Medium</option>
                                                    <option value="high" {{ $ticket->priority == 'high' ? 'selected' : '' }}>High</option>
                                                    <option value="urgent" {{ $ticket->priority == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Assign To</label>
                                                <select name="assigned_to" class="form-control">
                                                    <option value="">Unassigned</option>
                                                    @foreach($technicians as $tech)
                                                    <option value="{{ $tech->id }}" {{ $ticket->assigned_to == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @empty
                        <tr><td colspan="7" class="text-center">No tickets found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-2"></i>Create Ticket</h3></div>
            <form action="{{ route('reseller.tickets-manage.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" required></div>
                    @include('admin.partials.client-search', [
                        'inputId' => 'resTktMgmtClientSearch',
                        'hiddenId' => 'resTktMgmtClientSearchId',
                        'fieldName' => 'client_id',
                        'label' => 'Client',
                        'required' => false,
                        'searchUrl' => route('reseller.api.search-clients'),
                    ])
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-control" required>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-control" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assign To</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">Unassigned</option>
                            @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Create Ticket</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
