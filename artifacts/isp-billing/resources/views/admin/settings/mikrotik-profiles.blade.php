@extends('layouts.admin')

@section('title', 'MikroTik Profiles')
@section('breadcrumb')
<li class="breadcrumb-item active">MikroTik Profiles</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">MikroTik Profile <small class="text-muted">(MikroTik PPP Profiles)</small></h3>
                <div class="card-tools">
                    <form action="{{ route('admin.settings.mikrotik-profiles.sync-from-router') }}" method="POST" class="d-inline" onsubmit="return confirm('Fetch all PPP profiles from connected MikroTik routers?')">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm mr-1" title="Fetch PPP profiles from all connected routers">
                            <i class="fas fa-download"></i> Sync from MikroTik Router
                        </button>
                    </form>
                    <form action="{{ route('admin.settings.mikrotik-profiles.push-to-router') }}" method="POST" class="d-inline" onsubmit="return confirm('Push all profiles to MikroTik routers?')">
                        @csrf
                        <button type="submit" class="btn btn-info btn-sm mr-1" title="Push profiles to all active routers">
                            <i class="fas fa-upload"></i> Push to MikroTik Router
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display: none;" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                            <th>Profile Name</th>
                            <th>Speed (DL/UL)</th>
                            <th>Rate Limit</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Clients</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($profiles as $profile)
                        <tr>
                            <td><input type="checkbox" name="bulk_ids[]" value="{{ $profile->id }}" class="item-checkbox" onclick="updateSelection()" {{ ($profile->clients_count ?? 0) > 0 ? 'disabled' : '' }}></td>
                            <td><strong>{{ $profile->name }}</strong></td>
                            <td>{{ $profile->download_speed }}/{{ $profile->upload_speed }} {{ $profile->speed_unit }}</td>
                            <td><code>{{ $profile->rate_limit ?: '-' }}</code></td>
                            <td>{{ strtoupper($profile->profile_type) }}</td>
                            <td><small>{{ $profile->source_device ?: '-' }}</small></td>
                            <td><span class="badge badge-primary">{{ $profile->clients_count ?? 0 }}</span></td>
                            <td><span class="badge badge-{{ $profile->is_active ? 'success' : 'danger' }}">{{ $profile->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-warning btn-xs edit-profile"
                                        data-id="{{ $profile->id }}"
                                        data-name="{{ $profile->name }}"
                                        data-download="{{ $profile->download_speed }}"
                                        data-upload="{{ $profile->upload_speed }}"
                                        data-unit="{{ $profile->speed_unit }}"
                                        data-local="{{ $profile->local_address }}"
                                        data-remote="{{ $profile->remote_address }}"
                                        data-type="{{ $profile->profile_type }}"
                                        data-active="{{ $profile->is_active }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-xs" onclick="deleteSingle({{ $profile->id }})" {{ ($profile->clients_count ?? 0) > 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-3">
                            No profiles found. Click <strong>"Sync from MikroTik Router"</strong> to import PPP profiles from your MikroTik router.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($profiles->hasPages())
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
        {{ $profiles->appends(request()->query())->links() }}
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
        <div class="card card-primary" id="profileFormCard">
            <div class="card-header"><h3 class="card-title" id="formTitle">Add Profile</h3></div>
            @if($errors->any())
            <div class="alert alert-danger m-3">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <form action="{{ route('admin.settings.mikrotik-profiles.store') }}" method="POST" id="profileForm">
                @csrf
                <div id="methodPlaceholder"></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Profile Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="pf_name" class="form-control" placeholder="e.g. 10M, 20M, GOLD" required>
                        <small class="text-muted">Must match the PPP profile name on MikroTik</small>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Download Speed</label>
                                <input type="number" name="download_speed" id="pf_download" class="form-control" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Upload Speed</label>
                                <input type="number" name="upload_speed" id="pf_upload" class="form-control" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Unit</label>
                                <select name="speed_unit" id="pf_unit" class="form-control">
                                    <option value="Mbps">M</option>
                                    <option value="Kbps">K</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Local Address</label>
                        <input type="text" name="local_address" id="pf_local" class="form-control" placeholder="e.g. 10.10.10.1">
                    </div>
                    <div class="form-group">
                        <label>Remote Address / Pool</label>
                        <input type="text" name="remote_address" id="pf_remote" class="form-control" placeholder="e.g. pool or 10.10.10.0/24">
                    </div>
                    <div class="form-group">
                        <label>Profile Type</label>
                        <select name="profile_type" id="pf_type" class="form-control">
                            <option value="ppp">PPP (PPPoE)</option>
                            <option value="hotspot">Hotspot</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="pf_active" class="form-check-input" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 p-2">
                        <small><i class="fas fa-info-circle mr-1"></i><strong>Profile = Speed control only.</strong> Package = Billing (price). They are independent — any Profile + any Package combination is allowed per client.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Profile</button>
                    <button type="button" class="btn btn-default d-none" id="cancelBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="bulkForm" action="{{ route('admin.settings.mikrotik-profiles.bulk-delete') }}" method="POST" style="display:none;">
    @csrf
    <div id="bulkIdsContainer"></div>
</form>

<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.item-checkbox:not(:disabled)');
    checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
    updateSelection();
}
function updateSelection() {
    var checkboxes = document.querySelectorAll('.item-checkbox:checked');
    var count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';
}
function bulkDelete() {
    var checkboxes = document.querySelectorAll('.item-checkbox:checked');
    var count = checkboxes.length;
    if (count === 0) return;
    if (confirm('Delete ' + count + ' selected profile(s)?')) {
        var container = document.getElementById('bulkIdsContainer');
        container.innerHTML = '';
        checkboxes.forEach(function(cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('bulkForm').submit();
    }
}
function deleteSingle(id) {
    if (confirm('Delete this profile?')) {
        var form = document.getElementById('singleDeleteForm');
        form.action = '{{ url("admin/settings/mikrotik-profiles") }}/' + id;
        form.submit();
    }
}
</script>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.edit-profile').click(function() {
        const data = $(this).data();
        $('#formTitle').text('Edit Profile');
        $('#submitBtn').text('Update Profile');
        $('#cancelBtn').removeClass('d-none');
        $('#profileForm').attr('action', '/admin/settings/mikrotik-profiles/' + data.id);
        $('#methodPlaceholder').html('@method("PUT")');

        $('#pf_name').val(data.name);
        $('#pf_download').val(data.download);
        $('#pf_upload').val(data.upload);
        $('#pf_unit').val(data.unit);
        $('#pf_local').val(data.local);
        $('#pf_remote').val(data.remote);
        $('#pf_type').val(data.type);
        $('#pf_active').prop('checked', data.active == 1);

        $('html, body').animate({ scrollTop: $("#profileFormCard").offset().top }, 500);
    });

    $('#cancelBtn').click(function() {
        $('#formTitle').text('Add Profile');
        $('#submitBtn').text('Add Profile');
        $(this).addClass('d-none');
        $('#profileForm').attr('action', '{{ route("admin.settings.mikrotik-profiles.store") }}');
        $('#methodPlaceholder').html('');
        $('#profileForm')[0].reset();
    });
});
</script>
@endpush
