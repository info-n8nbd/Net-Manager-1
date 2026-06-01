@extends('layouts.reseller')
@section('title', 'Task Management')
@section('breadcrumb')<li class="breadcrumb-item active">Tasks</li>@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $pendingTasks }}</h3><p>Pending Tasks</p></div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $completedTasks }}</h3><p>Completed</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>My Tasks</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                    </div>
                </form>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $task)
                        <tr>
                            <td>
                                <strong>{{ $task->title }}</strong>
                                @if($task->description)
                                <br><small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($task->priority == 'high')
                                <span class="badge badge-danger">High</span>
                                @elseif($task->priority == 'medium')
                                <span class="badge badge-warning">Medium</span>
                                @else
                                <span class="badge badge-info">Low</span>
                                @endif
                            </td>
                            <td>{{ $task->due_date ? $task->due_date->format('d-m-Y') : 'No deadline' }}</td>
                            <td>
                                @if($task->status == 'completed')
                                <span class="badge badge-success">Completed</span>
                                @elseif($task->status == 'in_progress')
                                <span class="badge badge-primary">In Progress</span>
                                @else
                                <span class="badge badge-secondary">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($task->status != 'completed')
                                <form action="{{ route('reseller.tasks.status', $task) }}" method="POST" class="d-inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="btn btn-xs btn-success" title="Mark Complete"><i class="fas fa-check"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No tasks found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $tasks->links() }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Task</h3>
            </div>
            <form action="{{ route('reseller.tasks.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Add Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
