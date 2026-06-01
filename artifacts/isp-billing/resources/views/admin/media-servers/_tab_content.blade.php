{{-- Reusable tab content partial for Media Servers --}}
{{-- Variables: $tabServers, $tabType, $tabLabel, $tabIcon, $tabColor, $tabBadgeLabel, $addFormId --}}

{{-- AVAILABLE SERVER CARDS --}}
<div class="mb-4">
  <h5 style="font-weight:700;font-size:13px;color:#495057;margin-bottom:12px;">
    <i class="{{ $tabIcon }} mr-2" style="color:{{ $tabColor }};"></i>Available Servers
    <span class="badge ml-1" style="background:{{ $tabColor }};color:white;">{{ $tabServers->where('is_active',true)->count() }}</span>
  </h5>

  @if($tabServers->where('is_active',true)->isNotEmpty())
  <div class="row">
    @foreach($tabServers->where('is_active',true) as $server)
    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 mb-3">
      <a href="{{ $server->url }}" target="_blank" class="text-decoration-none">
        <div class="card h-100 text-center media-server-card">
          <div class="card-body py-3 px-2">
            @if($server->logo_url)
              <img src="{{ $server->logo_url }}" style="height:42px;width:auto;max-width:80px;object-fit:contain;margin-bottom:8px;border-radius:6px;"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
              <div style="display:none;"><i class="{{ $tabIcon }} fa-2x mb-2" style="color:{{ $tabColor }};"></i></div>
            @else
              <i class="{{ $tabIcon }} fa-2x mb-2" style="color:{{ $tabColor }};"></i>
            @endif
            <div style="font-size:12px;font-weight:700;color:#212529;line-height:1.3;margin-bottom:4px;">{{ $server->name }}</div>
            <span class="ms-type-badge" style="background:{{ $tabColor }};">{{ $tabBadgeLabel }}</span>
            @if($server->description)
              <div style="font-size:10px;color:#6c757d;margin-top:5px;line-height:1.3;">{{ $server->description }}</div>
            @endif
          </div>
          <div class="card-footer py-1" style="background:#f8f9fa;border-top:1px solid #f0f0f0;border-radius:0 0 10px 10px;">
            <small style="font-size:10px;color:#2a5298;font-weight:600;"><i class="fas fa-external-link-alt mr-1"></i>Open</small>
          </div>
        </div>
      </a>
    </div>
    @endforeach
  </div>
  @else
  <div class="text-center text-muted py-3" style="background:#f8f9fa;border-radius:10px;border:1px dashed #dee2e6;">
    <i class="{{ $tabIcon }} fa-2x mb-2 d-block" style="color:#ced4da;"></i>
    <small>No active {{ $tabLabel }}s yet.</small>
  </div>
  @endif
</div>

{{-- ADD NEW SERVER FORM --}}
<div class="card mb-3" id="{{ $addFormId }}">
  <div class="card-header d-flex align-items-center justify-content-between"
       style="background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;border-radius:8px 8px 0 0;padding:10px 16px;">
    <span style="font-size:13px;font-weight:700;"><i class="fas fa-plus-circle mr-2"></i>Add New {{ $tabLabel }}</span>
  </div>
  <div class="card-body py-3">
    <form method="POST" action="{{ route('admin.media-servers.store') }}">
      @csrf
      <input type="hidden" name="type" value="{{ $tabType }}">
      <div class="row">
        <div class="col-md-4 form-group mb-2">
          <label style="font-size:12px;font-weight:600;">Server Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control form-control-sm"
                 placeholder="e.g. BD {{ $tabLabel }} 1" required>
        </div>
        <div class="col-md-4 form-group mb-2">
          <label style="font-size:12px;font-weight:600;">Server URL <span class="text-danger">*</span></label>
          <input type="text" name="url" class="form-control form-control-sm"
                 placeholder="http://yourserver.com" required>
        </div>
        <div class="col-md-4 form-group mb-2">
          <label style="font-size:12px;font-weight:600;">Logo URL <span class="text-muted">(optional)</span></label>
          <input type="text" name="logo_url" class="form-control form-control-sm"
                 placeholder="https://... logo image link">
        </div>
      </div>
      <div class="row align-items-end">
        <div class="col-md-6 form-group mb-0">
          <label style="font-size:12px;font-weight:600;">Description <span class="text-muted">(optional)</span></label>
          <input type="text" name="description" class="form-control form-control-sm"
                 placeholder="Short note about this server">
        </div>
        <div class="col-md-2 form-group mb-0">
          <label style="font-size:12px;font-weight:600;">Order</label>
          <input type="number" name="sort_order" class="form-control form-control-sm" value="0" min="0">
        </div>
        <div class="col-md-2 form-group mb-0 d-flex align-items-end" style="padding-bottom:1px;">
          <div>
            <label style="font-size:12px;font-weight:600;display:block;">Active</label>
            <input type="checkbox" name="is_active" value="1" checked id="active_{{ $tabType }}">
            <label for="active_{{ $tabType }}" style="font-size:12px;margin:0;">Yes</label>
          </div>
        </div>
        <div class="col-md-2 form-group mb-0 d-flex align-items-end">
          <button type="submit" class="btn btn-primary btn-sm btn-block" style="background:{{ $tabColor }};border-color:{{ $tabColor }};">
            <i class="fas fa-plus mr-1"></i>Add Server
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- MANAGE TABLE --}}
<div class="card">
  <div class="card-header" style="padding:10px 16px;">
    <i class="{{ $tabIcon }} mr-2" style="color:{{ $tabColor }};"></i>
    <strong>Manage {{ $tabLabel }}s</strong>
    <span class="badge ml-1" style="background:{{ $tabColor }};color:white;">{{ $tabServers->count() }}</span>
  </div>
  <div class="card-body p-0">
    @if($tabServers->isEmpty())
      <div class="text-center text-muted py-5">
        <i class="{{ $tabIcon }} fa-3x mb-3 d-block" style="color:#dee2e6;"></i>
        No {{ $tabLabel }}s added yet. Use the form above to add one.
      </div>
    @else
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
      <thead style="background:#f8f9fa;font-size:11px;">
        <tr>
          <th width="30">#</th>
          <th>Server Name</th>
          <th>URL</th>
          <th>Logo</th>
          <th>Order</th>
          <th>Status</th>
          <th width="90">Actions</th>
        </tr>
      </thead>
      <tbody>
      @foreach($tabServers as $i => $server)
      <tr>
        <td class="text-muted" style="font-size:11px;">{{ $i+1 }}</td>
        <td>
          <strong style="font-size:13px;">{{ $server->name }}</strong>
          @if($server->description)
            <br><small class="text-muted">{{ $server->description }}</small>
          @endif
        </td>
        <td style="font-size:11px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          <a href="{{ $server->url }}" target="_blank" title="{{ $server->url }}">{{ $server->url }}</a>
        </td>
        <td>
          @if($server->logo_url)
            <img src="{{ $server->logo_url }}" style="height:26px;width:auto;border-radius:4px;"
                 onerror="this.style.display='none'">
          @else
            <span class="text-muted" style="font-size:11px;">—</span>
          @endif
        </td>
        <td style="font-size:12px;">{{ $server->sort_order }}</td>
        <td>
          @if($server->is_active)
            <span class="badge badge-success" style="font-size:10px;">Active</span>
          @else
            <span class="badge badge-secondary" style="font-size:10px;">Inactive</span>
          @endif
        </td>
        <td>
          <button class="btn btn-xs btn-outline-primary" onclick="openEdit({{ $server->id }})"
                  style="font-size:11px;padding:2px 7px;" title="Edit">
            <i class="fas fa-edit"></i>
          </button>
          <form method="POST" action="{{ route('admin.media-servers.destroy', $server) }}"
                class="d-inline" data-confirm="Delete this server?">
            @csrf @method("DELETE")
            <button type="submit" class="btn btn-xs btn-outline-danger"
                    style="font-size:11px;padding:2px 7px;" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </form>
        </td>
      </tr>
      {{-- INLINE EDIT ROW --}}
      <tr id="edit-row-{{ $server->id }}" style="display:none;background:#fffbf0;">
        <td colspan="7" style="padding:12px 16px;">
          <form method="POST" action="{{ route('admin.media-servers.update', $server) }}">
            @csrf @method("PUT")
            <input type="hidden" name="type" value="{{ $tabType }}">
            <div class="row">
              <div class="col-md-4 form-group mb-2">
                <label style="font-size:11px;font-weight:600;">Server Name</label>
                <input type="text" name="name" class="form-control form-control-sm"
                       value="{{ $server->name }}" required>
              </div>
              <div class="col-md-4 form-group mb-2">
                <label style="font-size:11px;font-weight:600;">Server URL</label>
                <input type="text" name="url" class="form-control form-control-sm"
                       value="{{ $server->url }}" required>
              </div>
              <div class="col-md-4 form-group mb-2">
                <label style="font-size:11px;font-weight:600;">Logo URL</label>
                <input type="text" name="logo_url" class="form-control form-control-sm"
                       value="{{ $server->logo_url }}">
              </div>
            </div>
            <div class="row align-items-end">
              <div class="col-md-5 form-group mb-0">
                <label style="font-size:11px;font-weight:600;">Description</label>
                <input type="text" name="description" class="form-control form-control-sm"
                       value="{{ $server->description }}">
              </div>
              <div class="col-md-2 form-group mb-0">
                <label style="font-size:11px;font-weight:600;">Order</label>
                <input type="number" name="sort_order" class="form-control form-control-sm"
                       value="{{ $server->sort_order }}" min="0">
              </div>
              <div class="col-md-2 form-group mb-0 d-flex align-items-end" style="padding-bottom:2px;">
                <div>
                  <label style="font-size:11px;font-weight:600;display:block;">Active</label>
                  <input type="checkbox" name="is_active" value="1"
                         {{ $server->is_active ? 'checked' : '' }}
                         id="edit_active_{{ $server->id }}">
                  <label for="edit_active_{{ $server->id }}" style="font-size:11px;margin:0;">Yes</label>
                </div>
              </div>
              <div class="col-md-3 form-group mb-0 d-flex align-items-end" style="gap:6px;">
                <button type="submit" class="btn btn-sm btn-success mr-2">
                  <i class="fas fa-save mr-1"></i>Save
                </button>
                <button type="button" class="btn btn-sm btn-secondary"
                        onclick="closeEdit({{ $server->id }})">Cancel</button>
              </div>
            </div>
          </form>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
    </div>
    @endif
  </div>
</div>
