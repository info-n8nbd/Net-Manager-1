@extends('layouts.admin')

@section('title', 'Edit Ticket')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.support.tickets') }}">Tickets</a></li>
<li class="breadcrumb-item active">Edit {{ $ticket->ticket_no }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Ticket #{{ $ticket->ticket_no }}</h3>
            </div>
            <form action="{{ route('admin.support.tickets.update', $ticket) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" value="{{ old('subject', $ticket->subject) }}" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-control" required>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $ticket->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    <option value="low" {{ $ticket->priority == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ $ticket->priority == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ $ticket->priority == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ $ticket->priority == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="5">{{ old('description', $ticket->description) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update Ticket</button>
                    <a href="{{ route('admin.support.tickets.show', $ticket) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title text-white">Ticket Information</h3>
            </div>
            <div class="card-body">
                <dl>
                    <dt>Ticket No</dt>
                    <dd>{{ $ticket->ticket_no }}</dd>
                    
                    <dt>Client</dt>
                    <dd>{{ $ticket->client->name ?? 'N/A' }}</dd>
                    
                    <dt>Created</dt>
                    <dd>{{ $ticket->created_at->format('d-m-y h:i A') }}</dd>
                    
                    @if($ticket->resolved_at)
                    <dt>Resolved</dt>
                    <dd>{{ $ticket->resolved_at->format('d-m-y h:i A') }}</dd>
                    @endif
                    
                    @if($ticket->closed_at)
                    <dt>Closed</dt>
                    <dd>{{ $ticket->closed_at->format('d-m-y h:i A') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
        
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title">Danger Zone</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Permanently delete this ticket and all its messages.</p>
                <form action="{{ route('admin.support.tickets.destroy', $ticket) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ticket? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash mr-1"></i>Delete Ticket</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
