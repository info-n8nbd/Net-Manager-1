@extends('layouts.admin')

@section('title', 'Line Shift History')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item active">Line Shifts</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Line Shift History</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Old Address</th>
                            <th>New Address</th>
                            <th class="text-right">Charge (৳)</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                        <tr>
                            <td>{{ $shift->shift_date->format('d-m-Y') }}</td>
                            <td><small class="text-muted">{{ $shift->old_address }}</small></td>
                            <td>{{ $shift->new_address }}</td>
                            <td class="text-right">৳{{ number_format($$shift->shift_charge ?? 0, 2) }}</td>
                            <td>{{ $shift->processor->name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No line shift records</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
        {{ $shifts->appends(request()->query())->links() }}
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
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Record Line Shift</h3>
            </div>
            <form action="{{ route('admin.clients.line-shifts.store', $client) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Current Address</label>
                        <input type="text" class="form-control" value="{{ $client->address }}" disabled>
                    </div>
                    <div class="form-group">
                        <label>New Address <span class="text-danger">*</span></label>
                        <textarea name="new_address" class="form-control" rows="2" required>{{ old('new_address') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Shift Date <span class="text-danger">*</span></label>
                        <input type="date" name="shift_date" class="form-control" value="{{ old('shift_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Shift Charge (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="shift_charge" class="form-control" value="{{ old('shift_charge', 0) }}" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">Record Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
