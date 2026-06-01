@extends('layouts.admin')

@section('title', 'Edit Employee')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.employees') }}">Employees</a></li>
<li class="breadcrumb-item active">Edit {{ $employee->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Employee</h3>
            </div>
            <form action="{{ route('admin.hrm.employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $employee->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone) }}" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NID</label>
                                <input type="text" name="nid" class="form-control" value="{{ old('nid', $employee->nid) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $employee->address) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">Select</option>
                                    <option value="male" {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $employee->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="resigned" {{ old('status', $employee->status) == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department_id" id="edit_department_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Designation</label>
                                <select name="designation_id" id="edit_designation_id" class="form-control"
                                    data-current-id="{{ old('designation_id', $employee->designation_id) }}"
                                    data-current-name="{{ $employee->designation ? $employee->designation->name : '' }}">
                                    <option value="">Select</option>
                                    @foreach($designations as $des)
                                    <option value="{{ $des->id }}" {{ old('designation_id', $employee->designation_id) == $des->id ? 'selected' : '' }}>{{ $des->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Joining Date</label>
                                <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', $employee->joining_date ? $employee->joining_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Basic Salary (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="basic_salary" class="form-control @error('basic_salary') is-invalid @enderror" value="{{ old('basic_salary', $employee->basic_salary) }}" step="0.01" required>
                                @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $employee->bank_name) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bank Account</label>
                                <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $employee->bank_account) }}">
                            </div>
                        </div>
                    </div>

                    @php $empZoneIds = array_map('intval', $employee->zone_ids ?? []); @endphp
                    @if(count($zones ?? []) > 0)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt mr-1 text-info"></i> Assigned Zones</label>

                                <input type="hidden" name="zone_ids" value="">
                                <select name="zone_ids[]" id="empZoneHiddenSelect" multiple style="display:none;">
                                    @foreach($zones ?? [] as $zone)
                                        <option value="{{ $zone->id }}" {{ in_array($zone->id, $empZoneIds) ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                </select>

                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="card card-outline card-secondary mb-0">
                                            <div class="card-header p-2 text-center"><strong>Available Zones</strong></div>
                                            <div class="card-body p-0">
                                                <ul id="empZoneAvailable" class="list-group list-group-flush emp-zone-list" style="min-height:100px;max-height:180px;overflow-y:auto;cursor:pointer;">
                                                    @foreach($zones ?? [] as $zone)
                                                        @if(!in_array($zone->id, $empZoneIds))
                                                            <li class="list-group-item list-group-item-action py-1 px-2" data-id="{{ $zone->id }}" data-name="{{ $zone->name }}">{{ $zone->name }}</li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex flex-column align-items-center justify-content-center" style="gap:8px;">
                                        <button type="button" id="empBtnAdd" class="btn btn-sm btn-primary" title="Add">&#x276F;</button>
                                        <button type="button" id="empBtnRemove" class="btn btn-sm btn-danger" title="Remove">&#x276E;</button>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="card card-outline card-primary mb-0">
                                            <div class="card-header p-2 text-center"><strong>Assigned Zones</strong></div>
                                            <div class="card-body p-0">
                                                <ul id="empZoneAssigned" class="list-group list-group-flush emp-zone-list" style="min-height:100px;max-height:180px;overflow-y:auto;cursor:pointer;">
                                                    @foreach($zones ?? [] as $zone)
                                                        @if(in_array($zone->id, $empZoneIds))
                                                            <li class="list-group-item list-group-item-action list-group-item-primary py-1 px-2" data-id="{{ $zone->id }}" data-name="{{ $zone->name }}">{{ $zone->name }}</li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">Click to select, then use arrows to move. Double-click to move instantly.</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    <hr>
                    <h5 class="mb-3"><i class="fas fa-file-image mr-2"></i>Documents & Photos</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Employee Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                @if($employee->photo)
                                <small class="text-success"><i class="fas fa-check mr-1"></i>Photo uploaded</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>CV / Resume (PDF)</label>
                                <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx">
                                @if($employee->cv_file)
                                <small class="text-success"><i class="fas fa-check mr-1"></i>CV uploaded</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NID Photo (Front) <span class="text-danger">*</span></label>
                                <input type="file" name="nid_photo_front" class="form-control" accept="image/*">
                                @if($employee->nid_photo_front)
                                <small class="text-success"><i class="fas fa-check mr-1"></i>Front uploaded</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>NID Photo (Back) <span class="text-danger">*</span></label>
                                <input type="file" name="nid_photo_back" class="form-control" accept="image/*">
                                @if($employee->nid_photo_back)
                                <small class="text-success"><i class="fas fa-check mr-1"></i>Back uploaded</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update Employee</button>
                    <a href="{{ route('admin.hrm.employees') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Employee Permission Card ────────────────────────────────────── --}}
    @if($employee->user && $employee->user->roles->count() && (auth()->user()->hasRole('Super Admin') || auth()->user()->user_type === 'admin'))
    @php $empUser = $employee->user; $empRole = $empUser->roles->first(); @endphp
    <div class="col-md-12 order-md-4 mt-3">
        <div class="card card-outline card-info shadow-sm" id="empPermCard">
            <div class="card-header py-2" style="cursor:pointer;" onclick="$('#empPermBody').slideToggle(200);$(this).find('i.fa-chevron-down,i.fa-chevron-up').toggleClass('fa-chevron-down fa-chevron-up');">
                <h3 class="card-title">
                    <i class="fas fa-user-shield mr-2 text-info"></i>
                    Personal Permission Override
                    <small class="text-muted ml-2">— {{ $employee->name }}</small>
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info">Role: {{ $empRole->name }}</span>
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-xs btn-outline-secondary ml-2" title="Manage role permissions">
                        <i class="fas fa-cog"></i> Role Settings
                    </a>
                    <i class="fas fa-chevron-up ml-2"></i>
                </div>
            </div>
            <div class="card-body p-0" id="empPermBody">

                {{-- Info bar --}}
                <div class="alert alert-info border-0 border-bottom rounded-0 mb-0 py-2 px-3" style="font-size:12.5px;">
                    <i class="fas fa-info-circle mr-1"></i>
                    These overrides apply <strong>only to {{ $employee->name }}</strong> and take effect on top of the
                    <strong>{{ $empRole->name }}</strong> role defaults.
                    <span class="badge badge-success ml-1">Green</span> = Granted &nbsp;
                    <span class="badge badge-danger ml-1">Red</span> = Revoked &nbsp;
                    <span class="badge badge-secondary ml-1">Gray</span> = Uses role default
                </div>

                {{-- Loading state --}}
                <div id="emp-perm-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-info mb-2 d-block"></i>
                    Loading permissions…
                </div>

                {{-- Permission content --}}
                <div id="emp-perm-content" style="display:none; padding:16px;">
                    {{-- Tabs --}}
                    <ul class="nav nav-tabs mb-3" id="empPermTabs">
                        <li class="nav-item"><a class="nav-link active" href="#emp-tab-menus" data-toggle="tab"><i class="fas fa-bars mr-1"></i>Menu Access</a></li>
                        <li class="nav-item"><a class="nav-link" href="#emp-tab-actions" data-toggle="tab"><i class="fas fa-bolt mr-1"></i>Page Actions</a></li>
                    </ul>

                    <div class="tab-content">
                        {{-- Menu Access Tab --}}
                        <div class="tab-pane fade show active" id="emp-tab-menus">
                            <div id="emp-menu-grid" style="display:grid; grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap:8px;"></div>
                        </div>
                        {{-- Actions Tab --}}
                        <div class="tab-pane fade" id="emp-tab-actions">
                            <div id="emp-action-container"></div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <button id="emp-btn-clear" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-eraser mr-1"></i>Clear All Overrides (Use Role Defaults)
                        </button>
                        <button id="emp-btn-save" class="btn btn-sm btn-success">
                            <i class="fas fa-save mr-1"></i>Save Personal Permissions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const EMP_USER_ID = {{ $empUser->id }};
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
        let empPermData = null;

        function api(url, method, body) {
            const opts = { method: method||'GET', headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'} };
            if(body) opts.body = JSON.stringify(body);
            return fetch(url, opts).then(r=>r.json());
        }

        function toast(msg, type) {
            const cls = type==='danger'?'danger':(type==='warning'?'warning':'success');
            const t = document.createElement('div');
            t.className=`alert alert-${cls} alert-dismissible shadow`;
            t.style.cssText='position:fixed;top:70px;right:20px;z-index:9999;min-width:280px;font-size:13px;';
            t.innerHTML=`${msg}<button type="button" class="close" onclick="this.parentNode.remove()"><span>&times;</span></button>`;
            document.body.appendChild(t);
            setTimeout(()=>t.remove(),4000);
        }

        // ── Render menu grid ─────────────────────────────────────────────
        function renderEmpMenus(menus) {
            const grid = document.getElementById('emp-menu-grid');
            grid.innerHTML = '';
            Object.entries(menus).forEach(([key, menu]) => {
                const ov = menu.override; // null | 'grant' | 'revoke'
                let chipClass, icon, title;
                if (ov === 'grant') { chipClass='border-success bg-light-green'; icon='fas fa-check-circle text-success'; title='Individually Granted'; }
                else if (ov === 'revoke') { chipClass='border-danger bg-light-red'; icon='fas fa-times-circle text-danger'; title='Individually Revoked'; }
                else { chipClass='border-secondary'; icon=menu.role_allowed?'fas fa-check text-muted':'fas fa-times text-muted'; title=menu.role_allowed?'Allowed by Role':'Blocked by Role'; }

                const card = document.createElement('div');
                card.className=`card mb-0 ${chipClass}`;
                card.style.cssText='border-radius:8px;padding:8px 10px;cursor:pointer;transition:all .2s;';
                card.dataset.key = key;

                const subCount = Object.keys(menu.submenus||{}).length;
                card.innerHTML=`
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <strong style="font-size:12px;">${menu.label}</strong>
                        <span title="${title}" style="font-size:13px;"><i class="${icon}"></i></span>
                    </div>
                    ${ov?`<div style="font-size:10px;margin-top:2px;"><span class="badge badge-${ov==='grant'?'success':'danger'} badge-sm">${ov.toUpperCase()}</span></div>`:'<div style="font-size:10px;margin-top:2px;color:#999;">role default</div>'}
                    ${subCount>0?`<div style="font-size:10px;color:#6c757d;margin-top:3px;">${subCount} submenus</div>`:''}
                `;

                card.addEventListener('click', () => cycleOverride(key, null, card, menus));
                grid.appendChild(card);
            });
        }

        function cycleOverride(menuKey, subKey, el, menus) {
            const fullKey = subKey ? `${menuKey}.${subKey}` : menuKey;
            let target = subKey ? empPermData.menus[menuKey].submenus[subKey] : empPermData.menus[menuKey];
            const current = target.override;
            const roleAllowed = target.role_allowed;
            // Cycle: null → grant (if role blocks) | null → revoke (if role allows) → null
            if (current === null || current === undefined) {
                target.override = roleAllowed ? 'revoke' : 'grant';
            } else {
                target.override = null;
            }
            renderEmpMenus(empPermData.menus);
        }

        // ── Render action chips ──────────────────────────────────────────
        function renderEmpActions(actions) {
            const container = document.getElementById('emp-action-container');
            container.innerHTML='';
            Object.entries(actions).forEach(([pageKey,page]) => {
                const section = document.createElement('div');
                section.style.cssText='margin-bottom:12px;border:1px solid #dee2e6;border-radius:8px;overflow:hidden;';
                const header = document.createElement('div');
                header.style.cssText='padding:8px 12px;background:#f8f9fa;font-size:13px;font-weight:700;cursor:pointer;display:flex;justify-content:space-between;';
                header.innerHTML=`<span>${page.label}</span><small class="text-muted">${Object.keys(page.actions).length} actions</small>`;
                const body = document.createElement('div');
                body.style.cssText='padding:8px 10px;display:flex;flex-wrap:wrap;gap:6px;';

                Object.entries(page.actions).forEach(([ak,action]) => {
                    const ov = action.override;
                    let cls='badge badge-lg';
                    if(ov==='grant') cls+=' badge-success';
                    else if(ov==='revoke') cls+=' badge-danger';
                    else cls+=action.role_allowed?' badge-light border':('' + ' badge-light border');
                    const chip = document.createElement('span');
                    chip.style.cssText='font-size:11px;padding:5px 10px;border-radius:20px;cursor:pointer;border:1px solid;display:inline-flex;align-items:center;gap:4px;'+
                        (ov==='grant'?'border-color:#28a745;background:#d4edda;color:#155724;':
                         ov==='revoke'?'border-color:#dc3545;background:#f8d7da;color:#721c24;':
                         action.role_allowed?'border-color:#6c757d;background:#fff;color:#495057;':
                         'border-color:#ccc;background:#f8f8f8;color:#999;');
                    chip.innerHTML=`<i class="fas fa-${ov==='grant'?'check':ov==='revoke'?'times':action.role_allowed?'check':'times'}" style="font-size:9px;"></i> ${action.label}`;
                    chip.title = ov ? ov.toUpperCase() : (action.role_allowed ? 'Allowed by role' : 'Blocked by role');

                    chip.addEventListener('click', () => {
                        const current = empPermData.actions[pageKey].actions[ak].override;
                        const roleA = empPermData.actions[pageKey].actions[ak].role_allowed;
                        empPermData.actions[pageKey].actions[ak].override = (current===null||current===undefined)
                            ? (roleA?'revoke':'grant') : null;
                        renderEmpActions(empPermData.actions);
                    });
                    body.appendChild(chip);
                });

                header.addEventListener('click', ()=> body.style.display = body.style.display==='none'?'flex':'none');
                section.appendChild(header);
                section.appendChild(body);
                container.appendChild(section);
            });
        }

        // ── Load data ────────────────────────────────────────────────────
        function loadEmpPerms() {
            api(`{{ url('admin/permissions/users') }}/${EMP_USER_ID}`)
                .then(data => {
                    empPermData = data;
                    document.getElementById('emp-perm-loading').style.display='none';
                    document.getElementById('emp-perm-content').style.display='block';
                    renderEmpMenus(data.menus);
                    renderEmpActions(data.actions);
                })
                .catch(() => {
                    document.getElementById('emp-perm-loading').innerHTML='<div class="text-danger p-3">Failed to load permissions.</div>';
                });
        }

        // ── Save ─────────────────────────────────────────────────────────
        document.getElementById('emp-btn-save').addEventListener('click', () => {
            if(!empPermData) return;
            const menus={}, actions={};
            Object.entries(empPermData.menus).forEach(([mk,m]) => {
                if(m.override) menus[mk]=m.override;
                Object.entries(m.submenus||{}).forEach(([sk,s]) => { if(s.override) menus[`${mk}.${sk}`]=s.override; });
            });
            Object.entries(empPermData.actions).forEach(([pk,p]) => {
                Object.entries(p.actions).forEach(([ak,a]) => { if(a.override) actions[`${pk}.${ak}`]=a.override; });
            });

            const btn = document.getElementById('emp-btn-save');
            btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin mr-1"></i>Saving…';

            api(`{{ url('admin/permissions/users') }}/${EMP_USER_ID}/overrides`,'POST',{menus,actions})
                .then(res => {
                    btn.disabled=false; btn.innerHTML='<i class="fas fa-save mr-1"></i>Save Personal Permissions';
                    if(res.success) { toast(res.message); loadEmpPerms(); }
                    else toast(res.message,'danger');
                })
                .catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="fas fa-save mr-1"></i>Save Personal Permissions'; toast('Save failed','danger'); });
        });

        // ── Clear ─────────────────────────────────────────────────────────
        document.getElementById('emp-btn-clear').addEventListener('click', () => {
            if(!confirm('Clear all personal overrides for {{ $employee->name }}? They will use role defaults only.')) return;
            api(`{{ url('admin/permissions/users') }}/${EMP_USER_ID}/overrides`,'DELETE')
                .then(res => { if(res.success){toast(res.message); loadEmpPerms();} else toast(res.message,'danger'); });
        });

        // Auto-load
        loadEmpPerms();
    })();
    </script>
    @endif
    {{-- w-100 row-break: keeps col-md-4 on the first row beside col-md-8, perm card drops below --}}
    <div class="w-100 order-md-3"></div>
    <div class="col-md-4 order-md-2">
        @if(auth()->user()->hasRole('Super Admin') || auth()->user()->user_type === 'admin')
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shield-alt mr-2 text-primary"></i>System Access Control</h3>
            </div>
            <div class="card-body">
                @if($employee->user)
                    @if($employee->user->is_active)
                    <div class="alert alert-info mb-2" style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:0;">
                        <span><i class="fas fa-user-check mr-1"></i> Login access <strong>enabled</strong><br><small>{{ $employee->user->email }}</small></span>
                        <span class="badge badge-success" style="font-size:11px;padding:5px 8px;">ACTIVE</span>
                    </div>
                    @else
                    <div class="alert alert-warning mb-2" style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:0;">
                        <span><i class="fas fa-ban mr-1"></i> Login access <strong>disabled</strong><br><small>{{ $employee->user->email }}</small></span>
                        <span class="badge badge-danger" style="font-size:11px;padding:5px 8px;">BLOCKED</span>
                    </div>
                    @endif
                    @if($employee->user->id !== auth()->id())
                    @php $confirmMsg = $employee->user->is_active ? 'Disable login access for this employee?' : 'Enable login access for this employee?'; @endphp
                    <form action="{{ route('admin.hrm.employees.toggle-login-access', $employee) }}" method="POST" class="mb-3" onsubmit="return confirm('{{ $confirmMsg }}')">
                        @csrf
                        <button type="submit" class="btn {{ $employee->user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} btn-block btn-sm mt-2">
                            @if($employee->user->is_active)
                            <i class="fas fa-ban mr-1"></i> Disable Login Access
                            @else
                            <i class="fas fa-check-circle mr-1"></i> Enable Login Access
                            @endif
                        </button>
                    </form>
                    @else
                    <div class="mb-3"></div>
                    @endif
                    <div class="form-group">
                        <label>Current Role</label>
                        <select id="userRole" class="form-control" data-user-id="{{ $employee->user->id }}" data-employee-id="{{ $employee->id }}">
                            <option value="">No Role</option>
                            @foreach($roles ?? [] as $role)
                                @if($role->name !== 'Super Admin')
                                <option value="{{ $role->id }}" {{ $employee->user->hasRole($role->name) ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="updateRoleBtn" class="btn btn-primary btn-block mb-2">
                        <i class="fas fa-save mr-1"></i> Update Role
                    </button>
                @else
                    <div class="alert alert-secondary mb-3">
                        <i class="fas fa-user-slash mr-1"></i> No login access
                    </div>
                    <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#createAccountModal">
                        <i class="fas fa-user-plus mr-1"></i> Create Login Account
                    </button>
                @endif
            </div>
        </div>
        @endif

        @if($employee->user && (auth()->user()->hasRole('Super Admin') || auth()->user()->user_type === 'admin'))
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-key mr-2 text-warning"></i>Login Credentials</h3>
            </div>
            <form action="{{ route('admin.hrm.employees.update-credentials', $employee) }}" method="POST">
                @csrf
                <div class="card-body">
                    @if(session('success') && str_contains(session('success'), 'credentials'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        {{ session('success') }}
                    </div>
                    @endif
                    <div class="form-group">
                        <label>Login Email <span class="text-danger">*</span></label>
                        <input type="email" name="login_email" class="form-control @error('login_email') is-invalid @enderror"
                            value="{{ old('login_email', $employee->user->email) }}" required>
                        @error('login_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="credNewPassword"
                                class="form-control @error('new_password') is-invalid @enderror"
                                placeholder="Min 8 characters" autocomplete="new-password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleCredPass('credNewPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password_confirmation" id="credConfirmPassword"
                                class="form-control" placeholder="Repeat password" autocomplete="new-password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleCredPass('credConfirmPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning btn-block">
                        <i class="fas fa-save mr-1"></i> Update Credentials
                    </button>
                </div>
            </form>
        </div>
        @endif

        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trash mr-2"></i>Delete Employee</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Permanently delete this employee record.</p>
                <form action="{{ route('admin.hrm.employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Delete this employee? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash mr-1"></i>Delete Employee
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($employee->user)
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-credit-card mr-2 text-info"></i>Payment Method Permission (Individual Override)</h3>
                <div class="card-tools"><small class="text-muted">Leave all unchecked = use role-level defaults</small></div>
            </div>
            <form action="{{ route('admin.hrm.employees.payment-method-perms', $employee) }}" method="POST">
                @csrf
                <div class="card-body py-2">
                    @if(session('success') && str_contains(session('success'), 'Payment method'))
                    <div class="alert alert-success py-1 px-3 mb-2">{{ session('success') }}</div>
                    @endif
                    <p class="text-muted small mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        If any method is checked here, <strong>only those methods</strong> will be available to this employee when collecting payments — regardless of role settings.
                        Leave all unchecked to fall back to the role-level permission.
                    </p>
                    <div class="row">
                        @foreach($allPaymentMethods as $method)
                        <div class="col-md-3 col-6 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input pm-user-cb"
                                       id="pm_user_{{ $method->id }}" name="payment_methods[]" value="{{ $method->id }}"
                                       {{ in_array($method->id, $userPaymentPerms) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="pm_user_{{ $method->id }}">
                                    <i class="fas fa-money-bill-wave mr-1 text-success" style="font-size:11px;"></i>{{ $method->name }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="$('.pm-user-cb').prop('checked',false);">
                            <i class="fas fa-times mr-1"></i>Clear (use role default)
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-success ml-1" onclick="$('.pm-user-cb').prop('checked',true);">
                            <i class="fas fa-check-double mr-1"></i>Allow All
                        </button>
                    </div>
                </div>
                <div class="card-footer py-2">
                    <button type="submit" class="btn btn-info btn-sm">
                        <i class="fas fa-save mr-1"></i>Save Payment Method Permission
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function toggleCredPass(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

$(function() {
    function empSyncHidden() {
        $('#empZoneAssigned li').each(function() {
            $('#empZoneHiddenSelect option[value="' + $(this).data('id') + '"]').prop('selected', true);
        });
        $('#empZoneAvailable li').each(function() {
            $('#empZoneHiddenSelect option[value="' + $(this).data('id') + '"]').prop('selected', false);
        });
    }
    function empMoveItems(fromList, toList, isAssigning) {
        fromList.find('li.active').each(function() {
            var $li = $(this).removeClass('active');
            isAssigning ? $li.addClass('list-group-item-primary') : $li.removeClass('list-group-item-primary');
            var name = $li.data('name').toLowerCase();
            var inserted = false;
            toList.find('li').each(function() {
                if ($(this).data('name').toLowerCase() > name) { $li.insertBefore($(this)); inserted = true; return false; }
            });
            if (!inserted) toList.append($li);
        });
        empSyncHidden();
    }
    $(document).on('click', '.emp-zone-list li', function() { $(this).toggleClass('active'); });
    $(document).on('dblclick', '#empZoneAvailable li', function() { $(this).addClass('active'); empMoveItems($('#empZoneAvailable'), $('#empZoneAssigned'), true); });
    $(document).on('dblclick', '#empZoneAssigned li', function() { $(this).addClass('active'); empMoveItems($('#empZoneAssigned'), $('#empZoneAvailable'), false); });
    $('#empBtnAdd').on('click', function() { empMoveItems($('#empZoneAvailable'), $('#empZoneAssigned'), true); });
    $('#empBtnRemove').on('click', function() { empMoveItems($('#empZoneAssigned'), $('#empZoneAvailable'), false); });
    empSyncHidden();
});
</script>
@endpush

@if(auth()->user()->hasRole('Super Admin') || auth()->user()->user_type === 'admin')
<div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Create Login Account</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="createAccountForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required value="{{ $employee->email }}">
                    </div>
                    <div class="form-group">
                        <label>Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Role / Access Level</label>
                        <select name="role_id" class="form-control">
                            <option value="">No Role</option>
                            @foreach($roles ?? [] as $role)
                                @if($role->name !== 'Super Admin')
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i>Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    document.getElementById('updateRoleBtn')?.addEventListener('click', function() {
        const roleSelect  = document.getElementById('userRole');
        const employeeId  = roleSelect.dataset.employeeId;
        const roleId      = roleSelect.value;

        if (!confirm('Are you sure you want to change this employee\'s role?')) return;

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Updating...';

        fetch('/admin/hrm/employees/' + employeeId + '/role', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ role_id: roleId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Role updated successfully!');
                location.reload();
            } else {
                alert(data.error || data.message || 'Failed to update role');
            }
        })
        .catch(() => alert('An error occurred'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save mr-1"></i> Update Role';
        });
    });
    
    document.getElementById('createAccountForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('[type=submit]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Creating...';

        fetch('/admin/hrm/employees/{{ $employee->id }}/create-account', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                email: formData.get('email'),
                password: formData.get('password'),
                role_id: formData.get('role_id')
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Account created successfully!');
                location.reload();
            } else {
                alert(data.error || data.message || 'Failed to create account');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check mr-1"></i>Create Account';
            }
        })
        .catch(() => {
            alert('An error occurred');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check mr-1"></i>Create Account';
        });
    });
});

$('#edit_department_id').on('change', function() {
    var deptId = $(this).val();
    var $desigSelect = $('#edit_designation_id');
    var currentId   = $desigSelect.data('current-id')   ? parseInt($desigSelect.data('current-id'))   : null;
    var currentName = $desigSelect.data('current-name') || '';
    $desigSelect.html('<option value="">Loading...</option>');
    if (!deptId) {
        $desigSelect.html('<option value="">-- Select --</option>');
        return;
    }
    $.get('/admin/hrm/api/designations-by-department/' + deptId, function(data) {
        var html = '<option value="">-- Select --</option>';
        var found = false;
        data.forEach(function(item) {
            var sel = (item.id === currentId) ? ' selected' : '';
            if (item.id === currentId) found = true;
            html += '<option value="' + item.id + '"' + sel + '>' + item.name + '</option>';
        });
        // If current designation is not in the new department's list, keep it as a selectable option
        if (!found && currentId) {
            html += '<option value="' + currentId + '" selected>' + currentName + ' (current)</option>';
        }
        if (data.length === 0 && !currentId) {
            html = '<option value="">No designations found</option>';
        }
        $desigSelect.html(html);
    }).fail(function() {
        $desigSelect.html('<option value="">Failed to load designations</option>');
    });
});
</script>
@endpush
