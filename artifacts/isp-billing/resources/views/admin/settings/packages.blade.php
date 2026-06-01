@extends('layouts.admin')

@section('title', 'Packages & MikroTik Profile')
@section('breadcrumb')
<li class="breadcrumb-item active">Packages & MikroTik Profile</li>
@endsection

@section('content')
<ul class="nav nav-tabs mb-3" id="pkgProfileTabs">
    <li class="nav-item">
        <a class="nav-link {{ !request('tab') || request('tab') == 'packages' ? 'active' : '' }}" data-toggle="tab" href="#tabPackages">
            <i class="fas fa-cube mr-1"></i> Packages
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request('tab') == 'profiles' ? 'active' : '' }}" data-toggle="tab" href="#tabProfiles">
            <i class="fas fa-tachometer-alt mr-1"></i> MikroTik Profile
            <span class="badge badge-info ml-1">{{ $profiles->count() }}</span>
        </a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade {{ !request('tab') || request('tab') == 'packages' ? 'show active' : '' }}" id="tabPackages">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Internet Packages</h3>
                        <div class="card-tools">
                            @if($__canAction('packages', 'add'))
                            <form action="{{ route('admin.settings.packages.import-from-router') }}" method="POST" class="d-inline" onsubmit="return confirm('Import all PPP profiles from connected MikroTik routers as packages?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm mr-1" title="Import PPP profiles from all connected routers">
                                    <i class="fas fa-download"></i> Import from Router
                                </button>
                            </form>
                            <form action="{{ route('admin.settings.packages.sync-to-router') }}" method="POST" class="d-inline" onsubmit="return confirm('Sync all packages to MikroTik routers? This will create/update PPP profiles on all active routers.')">
                                @csrf
                                <button type="submit" class="btn btn-info btn-sm mr-1" title="Push all packages to routers as PPP profiles">
                                    <i class="fas fa-upload"></i> Sync to Router
                                </button>
                            </form>
                            @endif
                            @if($__canAction('packages', 'delete'))
                            <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display: none;" onclick="bulkDelete()">
                                <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr><th style="width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th><th>Name</th><th>Price</th><th>Bandwidth</th><th>Type</th><th>MikroTik</th><th>MikroTik Profile</th><th>Status</th><th>Public</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                @forelse($packages as $package)
                                <tr>
                                    <td><input type="checkbox" name="bulk_ids[]" value="{{ $package->id }}" class="item-checkbox" onclick="updateSelection()" {{ ($package->clients_count ?? 0) > 0 ? 'disabled' : '' }}></td>
                                    <td>{{ $package->name }}</td>
                                    <td>{{ number_format($package->price, 2) }}</td>
                                    <td>{{ $package->bandwidth }}</td>
                                    <td>{{ $package->connection_type }}</td>
                                    <td><small>{{ $package->boundProfile->source_device ?? '-' }}</small></td>
                                    <td>{{ $package->mikrotik_profile ?: '-' }}</td>
                                    <td><span class="badge badge-{{ $package->is_active ? 'success' : 'danger' }}">{{ $package->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td><span class="badge badge-{{ $package->show_in_public ? 'info' : 'secondary' }}">{{ $package->show_in_public ? 'Yes' : 'No' }}</span></td>
                                    <td>
                                        <div class="btn-group">
                                            @if($__canAction('packages', 'edit'))
                                            <button class="btn btn-warning btn-xs edit-package" data-id="{{ $package->id }}" data-name="{{ $package->name }}" data-code="{{ $package->code }}" data-price="{{ $package->price }}" data-download="{{ $package->download_speed }}" data-upload="{{ $package->upload_speed }}" data-type="{{ $package->connection_type }}" data-active="{{ $package->is_active }}" data-public="{{ $package->show_in_public }}" data-mikrotik-profile="{{ $package->mikrotik_profile }}" data-btrc-amount="{{ $package->btrc_amount }}"><i class="fas fa-edit"></i></button>
                                            @endif
                                            @if($__canAction('packages', 'delete'))
                                            <button type="button" class="btn btn-danger btn-xs" onclick="deleteSingle({{ $package->id }})"><i class="fas fa-trash"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center">No packages</td></tr>
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
        {{ $packages->appends(request()->query())->links() }}
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
            @if($__canAction('packages', 'add'))
            <div class="col-md-4">
                <div class="card card-primary" id="packageFormCard">
                    <div class="card-header"><h3 class="card-title" id="formTitle">Add Package</h3></div>
                    @if($errors->any())
                    <div class="alert alert-danger m-3">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <form action="{{ route('admin.settings.packages.store') }}" method="POST" id="packageForm">
                        @csrf
                        <div id="methodPlaceholder"></div>
                        <div class="card-body">
                            <div class="form-group"><label>Package Name</label><input type="text" name="name" id="p_name" class="form-control" placeholder="e.g. Bronze 50Mbps, Gold 100Mbps" required></div>
                            <div class="form-group"><label>Price (৳)</label><input type="number" name="price" id="p_price" class="form-control" step="0.01" required></div>
                            <div class="form-group"><label>BTRC Amount (৳) <small class="text-muted">(Used as monthly bill in BTRC report)</small></label><input type="number" name="btrc_amount" id="p_btrc_amount" class="form-control" step="0.01" min="0" placeholder="Leave blank to use client monthly fee"></div>
                            <div class="form-group"><label>Download Speed <small class="text-muted">(display only)</small></label><input type="number" name="download_speed" id="p_download" class="form-control" required></div>
                            <div class="form-group"><label>Upload Speed <small class="text-muted">(display only)</small></label><input type="number" name="upload_speed" id="p_upload" class="form-control" required></div>
                            <div class="form-group"><label>Type</label>
                                <select name="connection_type" id="p_type" class="form-control">
                                    <option value="PPPoE">PPPoE</option>
                                    <option value="Static IP">Static IP</option>
                                    <option value="Hotspot">Hotspot</option>
                                    <option value="DHCP">DHCP</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>MikroTik Router <small class="text-muted">(filter profiles by router)</small></label>
                                <select id="p_router_filter" class="form-control">
                                    <option value="">-- All Routers --</option>
                                    @foreach($routers as $router)
                                    <option value="{{ $router->name }}">{{ $router->name }} ({{ $router->host }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>MikroTik Profile <small class="text-muted">(bind to this package)</small></label>
                                <select name="mikrotik_profile" id="p_mikrotik_profile" class="form-control">
                                    <option value="">-- No Profile Bound --</option>
                                    @foreach($profiles as $prof)
                                    <option value="{{ $prof->name }}" data-source-device="{{ $prof->source_device }}">{{ $prof->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">When this package is selected, the profile will auto-select in client forms.</small>
                            </div>
                            <div class="form-check mb-2"><input type="checkbox" name="is_active" id="p_active" class="form-check-input" checked><label class="form-check-label">Active</label></div>
                            <div class="form-check"><input type="checkbox" name="show_in_public" id="p_public" class="form-check-input" checked><label class="form-check-label">Show in Public Application Form</label></div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" id="submitBtn">Add Package</button>
                            <button type="button" class="btn btn-default d-none" id="cancelBtn">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="tab-pane fade {{ request('tab') == 'profiles' ? 'show active' : '' }}" id="tabProfiles">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">MikroTik Profile <small class="text-muted">(MikroTik PPP Profiles)</small></h3>
                        <div class="card-tools">
                            @if($__canAction('packages', 'add'))
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
                            @endif
                            @if($__canAction('packages', 'delete'))
                            <button type="button" class="btn btn-danger btn-sm" id="profileBulkDeleteBtn" style="display: none;" onclick="profileBulkDelete()">
                                <i class="fas fa-trash"></i> Delete Selected (<span id="profileSelectedCount">0</span>)
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" id="profileSelectAll" onclick="profileToggleSelectAll()"></th>
                                    <th>Profile Name</th>
                                    <th>Rate Limit</th>
                                    <th>Source</th>
                                    <th>Clients</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($profiles as $profile)
                                <tr>
                                    <td><input type="checkbox" name="profile_bulk_ids[]" value="{{ $profile->id }}" class="profile-checkbox" onclick="profileUpdateSelection()" {{ ($profile->clients_count ?? 0) > 0 ? 'disabled' : '' }}></td>
                                    <td><strong>{{ $profile->name }}</strong></td>
                                    <td><code>{{ $profile->rate_limit ?: '-' }}</code></td>
                                    <td><small>{{ $profile->source_device ?: '-' }}</small></td>
                                    <td><span class="badge badge-primary">{{ $profile->clients_count ?? 0 }}</span></td>
                                    <td><span class="badge badge-{{ $profile->is_active ? 'success' : 'danger' }}">{{ $profile->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        @if($__canAction('packages', 'delete'))
                                        <button type="button" class="btn btn-danger btn-xs" onclick="profileDeleteSingle({{ $profile->id }})" {{ ($profile->clients_count ?? 0) > 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endif
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
                </div>
            </div>
        </div>
    </div>
</div>

<form id="bulkForm" action="{{ route('admin.settings.packages.bulk-delete') }}" method="POST" style="display:none;">
    @csrf
    <div id="bulkIdsContainer"></div>
</form>

<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<form id="profileBulkForm" action="{{ route('admin.settings.mikrotik-profiles.bulk-delete') }}" method="POST" style="display:none;">
    @csrf
    <div id="profileBulkIdsContainer"></div>
</form>

<form id="profileSingleDeleteForm" method="POST" style="display:none;">
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
    if (confirm('Delete ' + count + ' selected package(s)?')) {
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
    if (confirm('Delete this package?')) {
        var form = document.getElementById('singleDeleteForm');
        form.action = '{{ url("admin/settings/packages") }}/' + id;
        form.submit();
    }
}

function profileToggleSelectAll() {
    var selectAll = document.getElementById('profileSelectAll');
    var checkboxes = document.querySelectorAll('.profile-checkbox:not(:disabled)');
    checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
    profileUpdateSelection();
}
function profileUpdateSelection() {
    var checkboxes = document.querySelectorAll('.profile-checkbox:checked');
    var count = checkboxes.length;
    document.getElementById('profileSelectedCount').textContent = count;
    document.getElementById('profileBulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';
}
function profileBulkDelete() {
    var checkboxes = document.querySelectorAll('.profile-checkbox:checked');
    var count = checkboxes.length;
    if (count === 0) return;
    if (confirm('Delete ' + count + ' selected profile(s)?')) {
        var container = document.getElementById('profileBulkIdsContainer');
        container.innerHTML = '';
        checkboxes.forEach(function(cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('profileBulkForm').submit();
    }
}
function profileDeleteSingle(id) {
    if (confirm('Delete this profile?')) {
        var form = document.getElementById('profileSingleDeleteForm');
        form.action = '{{ url("admin/settings/mikrotik-profiles") }}/' + id;
        form.submit();
    }
}
</script>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#p_router_filter').on('change', function() {
        var selectedRouter = $(this).val();
        var profileSelect = $('#p_mikrotik_profile');
        var currentVal = profileSelect.val();
        profileSelect.find('option').each(function() {
            if (!this.value) return;
            var source = $(this).data('source-device') || '';
            if (!selectedRouter || source === selectedRouter) {
                $(this).show().prop('disabled', false);
            } else {
                $(this).hide().prop('disabled', true);
                if (currentVal === this.value) {
                    profileSelect.val('');
                }
            }
        });
    });

    $('.edit-package').click(function() {
        const data = $(this).data();
        $('#formTitle').text('Edit Package');
        $('#submitBtn').text('Update Package');
        $('#cancelBtn').removeClass('d-none');
        $('#packageForm').attr('action', '/admin/settings/packages/' + data.id);
        $('#methodPlaceholder').html('@method("PUT")');
        $('#p_name').val(data.name);
        $('#p_price').val(data.price);
        $('#p_download').val(data.download);
        $('#p_upload').val(data.upload);
        $('#p_type').val(data.type);
        $('#p_active').prop('checked', data.active == 1);
        $('#p_public').prop('checked', data.public == 1);
        $('#p_router_filter').val('').trigger('change');
        $('#p_mikrotik_profile').val(data.mikrotikProfile || '');
        $('#p_btrc_amount').val(data.btrcAmount || '');
        $('html, body').animate({ scrollTop: $("#packageFormCard").offset().top }, 500);
    });

    $('#cancelBtn').click(function() {
        $('#formTitle').text('Add Package');
        $('#submitBtn').text('Add Package');
        $(this).addClass('d-none');
        $('#packageForm').attr('action', '{{ route("admin.settings.packages.store") }}');
        $('#methodPlaceholder').html('');
        $('#packageForm')[0].reset();
        $('#p_router_filter').val('').trigger('change');
    });

    var urlParams = new URLSearchParams(window.location.search);
    var tabFromUrl = urlParams.get('tab');
    if (tabFromUrl === 'profiles') {
        $('#pkgProfileTabs .nav-link').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $('a[href="#tabProfiles"]').addClass('active').tab('show');
        $('#tabProfiles').addClass('show active');
    }

    $(document).on('click', '#pkgProfileTabs .nav-link', function(e) {
        var target = $(this).attr('href');
        var tabName = target === '#tabProfiles' ? 'profiles' : 'packages';
        history.replaceState(null, '', window.location.pathname + '?tab=' + tabName);
    });
});
</script>
@endpush
