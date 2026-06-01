@extends('layouts.admin')
@section('title', 'Task: ' . $task->title)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.tasks.index') }}">Tasks</a></li>
<li class="breadcrumb-item active">{{ Str::limit($task->title, 30) }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Task</h3>
            </div>
            <form action="{{ route('admin.tasks.update', $task) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $task->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $task->description) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control">
                                    <option value="low" {{ old('priority', $task->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', $task->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority', $task->priority) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority', $task->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control">
                                    <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('status', $task->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Assign To</label>
                                <select name="assigned_to" class="form-control">
                                    <option value="">Unassigned</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to', $task->assigned_to) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes...">{{ old('notes', $task->notes) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update Task</button>
                    <a href="{{ route('admin.tasks.index') }}" class="btn btn-default ml-2">Back to List</a>
                    <form action="{{ route('admin.tasks.delete', $task) }}" method="POST" class="d-inline float-right" onsubmit="return confirm('Delete this task?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash mr-1"></i>Delete</button>
                    </form>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Task Info</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tr>
                        <td class="font-weight-bold">Created By</td>
                        <td>{{ $task->createdBy->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Created At</td>
                        <td>{{ $task->created_at->format('d-M-Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold">Last Updated</td>
                        <td>{{ $task->updated_at->format('d-M-Y h:i A') }}</td>
                    </tr>
                    @if($task->completed_at)
                    <tr>
                        <td class="font-weight-bold">Completed At</td>
                        <td><span class="text-success">{{ $task->completed_at->format('d-M-Y h:i A') }}</span></td>
                    </tr>
                    @endif
                    @if($task->due_date)
                    <tr>
                        <td class="font-weight-bold">Due Date</td>
                        <td>
                            {{ $task->due_date->format('d-M-Y') }}
                            @if($task->due_date->isPast() && $task->status !== 'completed')
                            <span class="badge badge-danger ml-1">Overdue</span>
                            @elseif($task->due_date->isToday())
                            <span class="badge badge-warning ml-1">Today</span>
                            @else
                            <br><small class="text-muted">{{ $task->due_date->diffForHumans() }}</small>
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-body text-center">
                <form action="{{ route('admin.tasks.toggle', $task) }}" method="POST">
                    @csrf
                    @if($task->status === 'completed')
                    <button type="submit" class="btn btn-warning btn-block"><i class="fas fa-undo mr-1"></i>Re-open Task</button>
                    @else
                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check mr-1"></i>Mark as Complete</button>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
