@extends('layouts.admin')

@section('title', 'IP Pool: ' . $pool->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.ip-pools') }}">IP Pools</a></li>
<li class="breadcrumb-item active">{{ $pool->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Pool Information</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Name:</th>
                        <td>{{ $pool->name }}</td>
                    </tr>
                    <tr>
                        <th>Range:</th>
                        <td><code>{{ $pool->range_start }} - {{ $pool->range_end }}</code></td>
                    </tr>
                    <tr>
                        <th>Router:</th>
                        <td>{{ $pool->router_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Synced:</th>
                        <td>
                            @if($pool->synced_to_router)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </td>
                    </tr>
                    @if($pool->last_synced_at)
                    <tr>
                        <th>Last Sync:</th>
                        <td>{{ \Carbon\Carbon::parse($pool->last_synced_at)->format('d-m-y H:i') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Assign IP to Client</h3>
            </div>
            <div class="card-body">
                <form id="assignForm">
                    <input type="hidden" name="pool_id" value="{{ $pool->id }}">
                    @include('admin.partials.client-search', [
                        'inputId' => 'ipPoolClientSearch',
                        'hiddenId' => 'ipPoolClientSearchId',
                        'fieldName' => 'client_id',
                        'label' => 'Select Client',
                        'required' => true,
                    ])
                    <button type="button" class="btn btn-success btn-block" id="btnAssignIp">
                        <i class="fas fa-check mr-1"></i>Assign Next Available IP
                    </button>
                </form>
                <div id="assignResult" class="mt-2" style="display:none;">
                    <div class="alert" id="assignAlert"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>IP Addresses</h3>
                <div class="card-tools">
                    @php
                        $totalAddrs = $addresses->total();
                        $usedAddrs = DB::table('ip_addresses')->where('ip_pool_id', $pool->id)->where('status', 'assigned')->count();
                        $availAddrs = $totalAddrs - $usedAddrs;
                    @endphp
                    <span class="badge badge-success mr-1">{{ $availAddrs }} Available</span>
                    <span class="badge badge-warning mr-1">{{ $usedAddrs }} Assigned</span>
                    <span class="badge badge-info">{{ $totalAddrs }} Total</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>Client</th>
                                <th>Assigned At</th>
                                <th width="80">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($addresses as $addr)
                            <tr id="addr-row-{{ $addr->id }}">
                                <td><code>{{ $addr->ip_address }}</code></td>
                                <td>
                                    @if($addr->status == 'available')
                                        <span class="badge badge-success">Available</span>
                                    @elseif($addr->status == 'assigned')
                                        <span class="badge badge-warning">Assigned</span>
                                    @elseif($addr->status == 'reserved')
                                        <span class="badge badge-info">Reserved</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $addr->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($addr->client_name)
                                        {{ $addr->client_name }}
                                        <br><small class="text-muted">{{ $addr->client_username }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($addr->assigned_at)
                                        {{ \Carbon\Carbon::parse($addr->assigned_at)->format('d-m-y H:i') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($addr->status == 'assigned' && $addr->client_id)
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="releaseIp({{ $addr->id }})" title="Release IP">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <p class="text-muted">No IP addresses in this pool.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($addresses->hasPages())
            <div class="card-footer">
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
        {{ $addresses->appends(request()->query())->links() }}
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
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var csrfToken = $('meta[name="csrf-token"]').attr('content');

$('#btnAssignIp').click(function() {
    var clientId = $('#ipPoolClientSearchId').val();
    if (!clientId) {
        alert('Please select a client.');
        return;
    }
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Assigning...');
    $.ajax({
        url: '{{ route("admin.network.ip-assign") }}',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: $('#assignForm').serialize(),
        dataType: 'json',
        success: function(res) {
            $('#assignResult').show();
            var alertDiv = $('#assignAlert');
            alertDiv.removeClass('alert-success alert-danger');
            if (res.success) {
                alertDiv.addClass('alert-success').html('<i class="fas fa-check-circle mr-1"></i>' + res.message);
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                alertDiv.addClass('alert-danger').html('<i class="fas fa-exclamation-circle mr-1"></i>' + res.message);
            }
        },
        error: function() {
            $('#assignResult').show();
            $('#assignAlert').removeClass('alert-success').addClass('alert-danger').html('<i class="fas fa-exclamation-circle mr-1"></i>Assignment failed.');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Assign Next Available IP');
        }
    });
});

function releaseIp(addressId) {
    smartConfirm({message:'Release this IP address?',confirmText:'Release'}, function() {
    $.ajax({
        url: '/admin/network/ip-pools/release/' + addressId,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert(res.message);
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        },
        error: function() { alert('Release request failed.'); }
    });
    }); // end smartConfirm
}
</script>
@endpush
