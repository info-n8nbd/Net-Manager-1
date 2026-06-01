@extends('layouts.admin')
@section('title', 'Task Calendar')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.tasks.index') }}">Tasks</a></li>
<li class="breadcrumb-item active">Calendar</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css">
<style>
.task-completed { opacity: 0.5; text-decoration: line-through; }
.fc-event { cursor: pointer; padding: 2px 4px; }
.priority-legend { display: flex; gap: 15px; flex-wrap: wrap; }
.priority-legend span { display: inline-flex; align-items: center; gap: 5px; font-size: 13px; }
.priority-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Task Calendar</h3>
        <div class="card-tools">
            <a href="{{ route('admin.tasks.index') }}" class="btn btn-outline-primary btn-sm mr-1"><i class="fas fa-list"></i> List View</a>
            <a href="{{ route('admin.tasks.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Create Task</a>
        </div>
    </div>
    <div class="card-body">
        <div class="priority-legend mb-3">
            <span><span class="priority-dot" style="background: #dc3545;"></span> Urgent</span>
            <span><span class="priority-dot" style="background: #fd7e14;"></span> High</span>
            <span><span class="priority-dot" style="background: #007bff;"></span> Medium</span>
            <span><span class="priority-dot" style="background: #28a745;"></span> Low</span>
        </div>
        <div id="calendar"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek,listWeek'
        },
        events: @json($calendarEvents),
        eventClick: function(info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        },
        height: 'auto',
        firstDay: 6,
    });
    calendar.render();
});
</script>
@endpush
