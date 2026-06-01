@extends('layouts.reseller')
@section('title', 'Events & Holidays')
@section('breadcrumb')<li class="breadcrumb-item active">Events & Holidays</li>@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>All Events</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                        <tr>
                            <td>{{ $event->event_date->format('d-m-Y') }}</td>
                            <td>{{ $event->title }}</td>
                            <td>
                                @if($event->type == 'holiday')
                                <span class="badge badge-danger">Holiday</span>
                                @elseif($event->type == 'event')
                                <span class="badge badge-info">Event</span>
                                @elseif($event->type == 'maintenance')
                                <span class="badge badge-warning">Maintenance</span>
                                @else
                                <span class="badge badge-secondary">{{ ucfirst($event->type) }}</span>
                                @endif
                            </td>
                            <td>{{ $event->description ?? '-' }}</td>
                            <td>
                                @if($event->reseller_id !== null)
                                <form action="{{ route('reseller.events.delete', $event) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this event?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                @else
                                <span class="badge badge-info">Global</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No events found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $events->links() }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Upcoming Events</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($upcomingEvents as $event)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $event->title }}</strong>
                                <br><small class="text-muted">{{ $event->event_date->format('d-m-Y') }}</small>
                            </div>
                            <span class="badge badge-{{ $event->type == 'holiday' ? 'danger' : ($event->type == 'maintenance' ? 'warning' : 'info') }}">
                                {{ ucfirst($event->type) }}
                            </span>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted">No upcoming events</li>
                    @endforelse
                </ul>
            </div>
        </div>
        
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Event</h3></div>
            <form action="{{ route('reseller.events.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" required></div>
                    <div class="form-group"><label>Date <span class="text-danger">*</span></label><input type="date" name="event_date" class="form-control" required></div>
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="event">Event</option>
                            <option value="holiday">Holiday</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Add Event</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
