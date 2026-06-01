@extends('layouts.admin')
@section('title', 'All Tasks')
@section('breadcrumb')<li class="breadcrumb-item active">Tasks</li>@endsection

@section('content')
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $stats['total'] }}</h3><p>Total Tasks</p></div>
            <div class="icon"><i class="fas fa-tasks"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $stats['pending'] + $stats['in_progress'] }}</h3><p>Active (Pending + In Progress)</p></div>
            <div class="icon"><i class="fas fa-spinner"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $stats['completed'] }}</h3><p>Completed</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3>{{ $stats['overdue'] }}</h3><p>Overdue</p></div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Task Management</h3>
        <div class="card-tools">
            <a href="{{ route('admin.tasks.calendar') }}" class="btn btn-outline-info btn-sm mr-1"><i class="fas fa-calendar"></i> Calendar</a>
            <a href="{{ route('admin.tasks.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Create Task</a>
        </div>
    </div>
    <div class="card-header py-2 border-top">
        <form method="GET" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <select name="status" class="form-control form-control-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <select name="priority" class="form-control form-control-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Priority</option>
                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
            </select>
            <select name="assigned_to" class="form-control form-control-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Users</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
            @if(request()->hasAny(['status', 'priority', 'assigned_to']))
            <a href="{{ route('admin.tasks.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i> Clear</a>
            @endif
        </form>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Title</th>
                    <th style="width: 130px;">Assigned To</th>
                    <th style="width: 90px;">Priority</th>
                    <th style="width: 120px;">Due Date</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr class="{{ $task->status === 'completed' ? 'text-muted' : '' }} {{ $task->due_date && $task->due_date->isPast() && $task->status !== 'completed' ? 'table-danger' : '' }}">
                    <td class="text-center align-middle">
                        <form action="{{ route('admin.tasks.toggle', $task) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="{{ $task->status === 'completed' ? 'Mark Pending' : 'Mark Complete' }}">
                                <i class="fas {{ $task->status === 'completed' ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}" style="font-size: 18px;"></i>
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="{{ route('admin.tasks.show', $task) }}" class="text-dark {{ $task->status === 'completed' ? 'text-decoration-line-through' : '' }}">
                            <strong>{{ $task->title }}</strong>
                        </a>
                        @if($task->description)
                        <br><small class="text-muted">{{ Str::limit($task->description, 60) }}</small>
                        @endif
                    </td>
                    <td>
                        @if($task->assignedTo)
                        <small>{{ $task->assignedTo->name }}</small>
                        @else
                        <small class="text-muted">Unassigned</small>
                        @endif
                    </td>
                    <td>
                        @php
                        $priorityColors = ['urgent' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'success'];
                        $priorityIcons = ['urgent' => 'fa-fire', 'high' => 'fa-arrow-up', 'medium' => 'fa-minus', 'low' => 'fa-arrow-down'];
                        @endphp
                        <span class="badge badge-{{ $priorityColors[$task->priority] ?? 'secondary' }}">
                            <i class="fas {{ $priorityIcons[$task->priority] ?? '' }} mr-1"></i>{{ ucfirst($task->priority) }}
                        </span>
                    </td>
                    <td>
                        @if($task->due_date)
                        <small>{{ $task->due_date->format('d-M-Y') }}</small>
                        @if($task->due_date->isPast() && $task->status !== 'completed')
                        <br><span class="badge badge-danger">Overdue</span>
                        @elseif($task->due_date->isToday())
                        <br><span class="badge badge-warning">Today</span>
                        @endif
                        @else
                        <small class="text-muted">No deadline</small>
                        @endif
                    </td>
                    <td>
                        @php
                        $statusColors = ['pending' => 'secondary', 'in_progress' => 'primary', 'completed' => 'success', 'cancelled' => 'dark'];
                        @endphp
                        <span class="badge badge-{{ $statusColors[$task->status] ?? 'secondary' }}">
                            {{ str_replace('_', ' ', ucfirst($task->status)) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.tasks.show', $task) }}" class="btn btn-xs btn-info" title="View/Edit"><i class="fas fa-eye"></i></a>
                        <form action="{{ route('admin.tasks.delete', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this task?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-2x text-muted mb-2 d-block"></i>
                    No tasks found. <a href="{{ route('admin.tasks.create') }}">Create your first task</a>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tasks->hasPages())
    <div class="card-footer">{{ $tasks->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection
