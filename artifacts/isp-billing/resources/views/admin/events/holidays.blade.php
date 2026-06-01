@extends('layouts.admin')
@section('title', 'Holidays')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Events</a></li>
<li class="breadcrumb-item active">Holidays</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-umbrella-beach mr-2"></i>All Holidays</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Date</th>
                            <th>Holiday Name</th>
                            <th>Description</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holidays as $holiday)
                        <tr class="{{ $holiday->event_date->isPast() ? 'text-muted' : '' }}">
                            <td>
                                <strong>{{ $holiday->event_date->format('d-M-Y') }}</strong>
                                @if($holiday->event_date->isToday())
                                <br><span class="badge badge-success">Today</span>
                                @elseif($holiday->event_date->isFuture())
                                <br><small class="text-info">{{ $holiday->event_date->diffForHumans() }}</small>
                                @endif
                            </td>
                            <td>{{ $holiday->title }}</td>
                            <td>{{ $holiday->description ?? '-' }}</td>
                            <td>
                                <form action="{{ route('admin.events.delete', $holiday) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this holiday?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4">No holidays found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($holidays->hasPages())
            <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $holidays->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script></div>
            @endif
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Holiday</h3></div>
            <form action="{{ route('admin.events.holidays.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="event_date" class="form-control @error('event_date') is-invalid @enderror" value="{{ old('event_date') }}" required>
                        @error('event_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Add Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
