@extends('layouts.admin')

@section('title', 'Port Forwarding Rules')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-random mr-2"></i>Port Forwarding Rules</h3>
            <div class="card-tools">
                <a href="{{ route('admin.port-forward.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i>Add Rule
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Client</th>
                        <th>Protocol</th>
                        <th>External Port</th>
                        <th>Internal IP</th>
                        <th>Internal Port</th>
                        <th>Router</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                    <tr>
                        <td>{{ $loop->iteration + ($rules->currentPage() - 1) * $rules->perPage() }}</td>
                        <td>{{ $rule->name }}</td>
                        <td>{{ $rule->client->name ?? 'N/A' }}</td>
                        <td>
                            @if($rule->protocol === 'tcp')
                                <span class="badge badge-info">TCP</span>
                            @elseif($rule->protocol === 'udp')
                                <span class="badge badge-warning">UDP</span>
                            @else
                                <span class="badge badge-primary">TCP/UDP</span>
                            @endif
                        </td>
                        <td><code>{{ $rule->external_port }}</code></td>
                        <td><code>{{ $rule->internal_ip }}</code></td>
                        <td><code>{{ $rule->internal_port }}</code></td>
                        <td>{{ $rule->router->name ?? 'N/A' }}</td>
                        <td>
                            @if($rule->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.port-forward.edit', $rule) }}" class="btn btn-sm btn-info" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.port-forward.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this port forwarding rule?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center">No port forwarding rules found</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $rules->appends(request()->query())->links() }}
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
</script>
        </div>
    </div>
</div>
@endsection
