@extends('layouts.admin')

@section('title', 'Reconnection History')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item active">Reconnections</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plug mr-2"></i>Reconnection History</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Disconnect Date</th>
                            <th>Reconnect Date</th>
                            <th>Reason</th>
                            <th class="text-right">Fee (৳)</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconnections as $rec)
                        <tr>
                            <td>{{ $rec->disconnect_date->format('d-m-Y') }}</td>
                            <td>{{ $rec->reconnect_date->format('d-m-Y') }}</td>
                            <td>{{ $rec->disconnect_reason ?? 'N/A' }}</td>
                            <td class="text-right">৳{{ number_format($$rec->reconnection_fee ?? 0, 2) }}</td>
                            <td>{{ $rec->processor->name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No reconnection records</td>
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
        {{ $reconnections->appends(request()->query())->links() }}
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
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Record Reconnection</h3>
            </div>
            <form action="{{ route('admin.clients.reconnections.store', $client) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Disconnect Date <span class="text-danger">*</span></label>
                        <input type="date" name="disconnect_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Reconnect Date <span class="text-danger">*</span></label>
                        <input type="date" name="reconnect_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Disconnect Reason</label>
                        <select name="disconnect_reason" class="form-control">
                            <option value="">Select Reason</option>
                            <option value="Non-payment">Non-payment</option>
                            <option value="Client Request">Client Request</option>
                            <option value="Technical Issue">Technical Issue</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reconnection Fee (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="reconnection_fee" class="form-control" value="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">Record Reconnection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
