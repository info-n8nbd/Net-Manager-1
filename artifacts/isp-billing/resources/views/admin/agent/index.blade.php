@extends('layouts.admin')
@section('title', 'Referral Partner')

@push('styles')
<style>
    .toggle-switch { position: relative !important; display: inline-block !important; width: 50px !important; height: 26px !important; overflow: hidden !important; flex-shrink: 0 !important; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #dc3545; transition: .3s; border-radius: 26px; }
    .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    .toggle-switch input:checked + .toggle-slider { background-color: #28a745; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }
    .toggle-label { font-size: 11px; font-weight: 700; margin-left: 6px; vertical-align: middle; }
</style>
@endpush

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title"><i class="fas fa-user-tie mr-2"></i>Referral Partner List</h3>
        <div class="ml-auto">
            <a href="{{ route('admin.agent.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus mr-1"></i> ADD REFERRAL PARTNER
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="agentTable" class="table table-bordered table-striped table-hover mb-0">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>Partner ID</th>
                        <th>Partner Name</th>
                        <th>Joining Date</th>
                        <th>Personal Contact</th>
                        <th>Office Contact</th>
                        <th>Commission Percent</th>
                        <th>Clients</th>
                        <th>Resellers</th>
                        <th>Email</th>
                        <th>Commission ON/OFF</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                    <tr>
                        <td>{{ $agent->agent_id }}</td>
                        <td><strong>{{ $agent->name }}</strong></td>
                        <td>{{ $agent->joining_date ? $agent->joining_date->format('jS F, Y') : '-' }}</td>
                        <td>{{ $agent->personal_contact ?? '-' }}</td>
                        <td>{{ $agent->office_contact ?? '-' }}</td>
                        <td><span class="badge badge-info">{{ number_format($agent->default_commission, 2) }}%</span></td>
                        <td>
                            @php $clientCount = $clientCounts[$agent->id] ?? 0; @endphp
                            @if($clientCount > 0)
                                <a href="{{ route('admin.agent.clients', $agent->id) }}" class="badge badge-success" title="View clients">{{ $clientCount }} Client{{ $clientCount > 1 ? 's' : '' }}</a>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            @php $resellerCount = $resellerCounts[$agent->id] ?? 0; @endphp
                            @if($resellerCount > 0)
                                <a href="{{ route('admin.agent.resellers', $agent->id) }}" class="badge badge-primary" title="View resellers">{{ $resellerCount }} Reseller{{ $resellerCount > 1 ? 's' : '' }}</a>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>{{ $agent->email ?? '-' }}</td>
                        <td class="text-center">
                            <label class="toggle-switch mb-0" title="{{ $agent->status === 'active' ? 'Commission ON - click to turn OFF' : 'Commission OFF - click to turn ON' }}">
                                <input type="checkbox" class="agent-toggle" data-id="{{ $agent->id }}" {{ $agent->status === 'active' ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-label {{ $agent->status === 'active' ? 'text-success' : 'text-danger' }}" id="toggle-label-{{ $agent->id }}">
                                {{ $agent->status === 'active' ? 'ON' : 'OFF' }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.agent.commission', $agent->id) }}" class="btn btn-info btn-xs" title="Commission">
                                <i class="fas fa-coins"></i>
                            </a>
                            <a href="{{ route('admin.agent.edit', $agent->id) }}" class="btn btn-warning btn-xs" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.agent.destroy', $agent->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this referral partner?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-3">No referral partners found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#agentTable tbody tr td[colspan]').length === 0) {
        $('#agentTable').DataTable({
            
            "autoWidth": false,
            "order": [[0, "desc"]],
            "pageLength": 25
        });
    }

    $(document).on('change', '.agent-toggle', function() {
        var checkbox = $(this);
        var agentId = checkbox.data('id');
        var label = $('#toggle-label-' + agentId);
        var switchEl = checkbox.closest('.toggle-switch');

        checkbox.prop('disabled', true);

        $.ajax({
            url: '/admin/agent/' + agentId + '/toggle-status',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    if (res.status === 'active') {
                        label.text('ON').removeClass('text-danger').addClass('text-success');
                        switchEl.attr('title', 'Commission ON - click to turn OFF');
                    } else {
                        label.text('OFF').removeClass('text-success').addClass('text-danger');
                        switchEl.attr('title', 'Commission OFF - click to turn ON');
                    }
                    toastr.success(res.message);
                }
            },
            error: function() {
                checkbox.prop('checked', !checkbox.prop('checked'));
                toastr.error('Failed to update status. Please try again.');
            },
            complete: function() {
                checkbox.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
