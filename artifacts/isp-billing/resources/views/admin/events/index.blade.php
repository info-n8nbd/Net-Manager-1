@extends('layouts.admin')
@section('title', 'Events')
@section('breadcrumb')<li class="breadcrumb-item active">Events</li>@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>All Events</h3>
                <div class="card-tools">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.events.index', ['filter' => 'all']) }}" class="btn btn-{{ ($filter ?? 'all') === 'all' ? 'primary' : 'outline-primary' }}">All</a>
                        <a href="{{ route('admin.events.index', ['filter' => 'upcoming']) }}" class="btn btn-{{ ($filter ?? '') === 'upcoming' ? 'primary' : 'outline-primary' }}">Upcoming</a>
                        <a href="{{ route('admin.events.index', ['filter' => 'past']) }}" class="btn btn-{{ ($filter ?? '') === 'past' ? 'primary' : 'outline-primary' }}">Past</a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Date</th>
                            <th>Event</th>
                            <th style="width: 110px;">Type</th>
                            <th>Description</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                        <tr class="{{ $event->event_date->isPast() ? 'text-muted' : '' }}">
                            <td>
                                <strong>{{ $event->event_date->format('d-M-Y') }}</strong>
                                @if($event->event_date->isToday())
                                <br><span class="badge badge-success">Today</span>
                                @elseif($event->event_date->isFuture())
                                <br><small class="text-info">{{ $event->event_date->diffForHumans() }}</small>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $event->title }}</strong>
                                @if($event->createdBy)
                                <br><small class="text-muted">by {{ $event->createdBy->name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($event->type == 'holiday')
                                <span class="badge badge-danger"><i class="fas fa-umbrella-beach mr-1"></i>Holiday</span>
                                @elseif($event->type == 'event')
                                <span class="badge badge-info"><i class="fas fa-calendar-check mr-1"></i>Event</span>
                                @elseif($event->type == 'maintenance')
                                <span class="badge badge-warning"><i class="fas fa-tools mr-1"></i>Maintenance</span>
                                @else
                                <span class="badge badge-secondary">{{ ucfirst($event->type) }}</span>
                                @endif
                            </td>
                            <td>{{ $event->description ?? '-' }}</td>
                            <td>
                                <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#editEventModal{{ $event->id }}"><i class="fas fa-edit"></i></button>
                                <form action="{{ route('admin.events.delete', $event) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this event?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4"><i class="fas fa-calendar-times fa-2x text-muted mb-2 d-block"></i>No events found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($events->hasPages())
            <div class="card-footer">
                {{ $events->appends(request()->query())->links() }}
            </div>
            @endif
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
                                <br><small class="text-muted">{{ $event->event_date->format('d-M-Y') }}
                                @if($event->event_date->isToday())
                                — <span class="text-success">Today</span>
                                @else
                                — {{ $event->event_date->diffForHumans() }}
                                @endif
                                </small>
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
            <form action="{{ route('admin.events.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="event_date" class="form-control @error('event_date') is-invalid @enderror" value="{{ old('event_date') }}" required>
                        @error('event_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="event" {{ old('type') == 'event' ? 'selected' : '' }}>Event</option>
                            <option value="holiday" {{ old('type') == 'holiday' ? 'selected' : '' }}>Holiday</option>
                            <option value="maintenance" {{ old('type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Add Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($events as $event)
<div class="modal fade" id="editEventModal{{ $event->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.events.update', $event) }}" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Event</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ $event->title }}" required>
                    </div>
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="event_date" class="form-control" value="{{ $event->event_date->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="event" {{ $event->type == 'event' ? 'selected' : '' }}>Event</option>
                            <option value="holiday" {{ $event->type == 'holiday' ? 'selected' : '' }}>Holiday</option>
                            <option value="maintenance" {{ $event->type == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="other" {{ $event->type == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $event->description }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Event</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection
