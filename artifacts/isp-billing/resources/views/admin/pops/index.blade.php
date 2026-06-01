@extends("layouts.admin")

@section("title", "POP Management")

@section("content")
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-broadcast-tower mr-2"></i>POP Management</h3>
        <a href="{{ route("admin.pops.create") }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Add POP
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Source / Linked Reseller</th>
                        <th>Branch</th>
                        <th>Zone</th>
                        <th>Bandwidth</th>
                        <th>Uplink Provider</th>
                        <th>Status</th>
                        <th>Clients</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pops as $pop)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><a href="{{ route("admin.pops.show", $pop) }}"><strong>{{ $pop->name }}</strong></a></td>
                        <td><code>{{ $pop->code }}</code></td>
                        <td>
                            <span class="badge {{ $pop->source_badge ?? "badge-success" }}">{{ $pop->source_label ?? "Standalone" }}</span>
                            @if($pop->reseller)
                                <br><small class="text-muted mt-1 d-block">
                                    <i class="fas fa-user mr-1"></i>
                                    {{ $pop->reseller->company_name ?? $pop->reseller->name }}
                                    <em class="text-secondary">({{ $pop->reseller->reseller_id }})</em>
                                </small>
                            @endif
                        </td>
                        <td>{{ $pop->branch->name ?? "-" }}</td>
                        <td>{{ $pop->zone->name ?? "-" }}</td>
                        <td>
                            {{ $pop->used_bandwidth_mbps ?? 0 }} / {{ $pop->total_bandwidth_mbps ?? 0 }} Mbps
                            @if(($pop->total_bandwidth_mbps ?? 0) > 0)
                                @php $bwPct = min((($pop->used_bandwidth_mbps ?? 0) / $pop->total_bandwidth_mbps * 100), 100); @endphp
                                <div class="progress progress-xs mt-1">
                                    <div class="progress-bar bg-{{ $bwPct > 80 ? "danger" : ($bwPct > 60 ? "warning" : "success") }}" style="width: {{ $bwPct }}%"></div>
                                </div>
                            @endif
                        </td>
                        <td>{{ $pop->uplink_provider ?? "-" }}</td>
                        <td>
                            @if($pop->status == "active") <span class="badge badge-success">Active</span>
                            @elseif($pop->status == "maintenance") <span class="badge badge-warning">Maintenance</span>
                            @else <span class="badge badge-danger">Inactive</span> @endif
                        </td>
                        <td><span class="badge badge-secondary">{{ $pop->clients_count ?? 0 }}</span></td>
                        <td class="text-nowrap">
                            <a href="{{ route("admin.pops.show", $pop) }}" class="btn btn-primary btn-xs" title="View"><i class="fas fa-eye"></i></a>
                            <a href="{{ route("admin.pops.edit", $pop) }}" class="btn btn-info btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                            <form action="{{ route("admin.pops.destroy", $pop) }}" method="POST" class="d-inline" onsubmit="return confirm("Delete this POP?")">
                                @csrf @method("DELETE")
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="fas fa-broadcast-tower fa-2x mb-2 d-block text-secondary"></i>
                            No POPs configured yet. <a href="{{ route("admin.pops.create") }}">Create one now</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
