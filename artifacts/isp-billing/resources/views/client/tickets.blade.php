@extends('layouts.client')
@section('title', 'Support Tickets')

@push('styles')
<style>
@media (min-width:768px) {
    .tickets-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .tickets-grid .ticket-card-wrapper { margin-bottom:0; }
}
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <span style="font-size:14px; font-weight:700; color:#1e3c72;"><i class="fas fa-headset mr-1"></i>Support Tickets</span>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newTicketModal" style="border-radius:6px; padding:5px 12px; font-size:11px;"><i class="fas fa-plus mr-1"></i>New Ticket</button>
</div>

<div class="tickets-grid">
@forelse($tickets ?? [] as $ticket)
<div class="ticket-card-wrapper">
<a href="{{ route('client.tickets.show', $ticket) }}" style="text-decoration:none;">
<div style="background:#fff; border-radius:10px; padding:11px 14px; margin-bottom:8px; box-shadow:0 1px 5px rgba(0,0,0,0.06);">
    <div class="d-flex justify-content-between align-items-start mb-1">
        <div style="flex:1; margin-right:8px;">
            <div style="font-size:13px; font-weight:600; color:#1e293b; line-height:1.2;">{{ Str::limit($ticket->subject, 50) }}</div>
            <div style="font-size:10px; color:#64748b; margin-top:2px;">#{{ $ticket->id }} &middot; {{ $ticket->category->name ?? 'General' }}</div>
        </div>
        <span class="badge badge-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'resolved' ? 'success' : 'secondary') }}" style="font-size:9px; padding:4px 7px; border-radius:20px; flex-shrink:0;">{{ ucfirst($ticket->status) }}</span>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <span class="badge badge-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'info') }}" style="font-size:8px; padding:2px 6px;">{{ ucfirst($ticket->priority ?? 'medium') }}</span>
        <span style="font-size:10px; color:#94a3b8;"><i class="fas fa-clock mr-1"></i>{{ $ticket->created_at->diffForHumans() }}</span>
    </div>
</div>
</a>
</div>
@empty
<div class="text-center py-5" style="grid-column:1/-1;">
    <i class="fas fa-headset fa-3x text-muted mb-3"></i><p class="text-muted mb-3">No tickets yet</p>
    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newTicketModal" style="border-radius:6px;"><i class="fas fa-plus mr-1"></i>Create First Ticket</button>
</div>
@endforelse
</div>

<div class="modal fade" id="newTicketModal">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px; margin:10px auto;">
        <div class="modal-content" style="border-radius:14px; border:none; overflow:hidden;">
            <form action="{{ route('client.tickets.store') }}" method="POST">
                @csrf
                <div class="modal-header py-2" style="background:linear-gradient(135deg,#1e3c72,#2a5298);">
                    <h6 class="modal-title text-white mb-0" style="font-size:13px;"><i class="fas fa-plus mr-1"></i>New Support Ticket</h6>
                    <button type="button" class="close text-white ml-auto" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding:14px;">
                    <div class="form-group mb-2">
                        <label style="font-size:10px; font-weight:600; color:#64748b; text-transform:uppercase;">Subject *</label>
                        <input type="text" name="subject" class="form-control" required placeholder="Brief description" style="border-radius:6px; font-size:12px; border-color:#e2e8f0;">
                    </div>
                    <div class="row">
                        <div class="col-6"><div class="form-group mb-2"><label style="font-size:10px; font-weight:600; color:#64748b; text-transform:uppercase;">Category</label><select name="category_id" class="form-control" style="border-radius:6px; font-size:12px; border-color:#e2e8f0;"><option value="">General</option>@foreach($categories ?? [] as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div></div>
                        <div class="col-6"><div class="form-group mb-2"><label style="font-size:10px; font-weight:600; color:#64748b; text-transform:uppercase;">Priority</label><select name="priority" class="form-control" style="border-radius:6px; font-size:12px; border-color:#e2e8f0;"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div></div>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:10px; font-weight:600; color:#64748b; text-transform:uppercase;">Description *</label>
                        <textarea name="description" class="form-control" rows="3" required placeholder="Describe your issue..." style="border-radius:6px; font-size:12px; border-color:#e2e8f0; resize:none;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border:none; padding:0 14px 14px;">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:6px;">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius:6px; padding:6px 16px;"><i class="fas fa-paper-plane mr-1"></i>Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
