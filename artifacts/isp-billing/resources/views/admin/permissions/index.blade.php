@extends('layouts.admin')
@section('title', 'Permission Management')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Permission Management</li>
@endsection

@push('styles')
<style>
/* ── Layout ────────────────────────────────────────────────────────────────── */
#perm-layout { display: flex; gap: 0; min-height: calc(100vh - 160px); }
#role-sidebar { width: 220px; flex-shrink: 0; background: #fff; border-right: 1px solid #dee2e6; border-radius: 4px 0 0 4px; overflow-y: auto; }
#perm-main { flex: 1; background: #fff; border-radius: 0 4px 4px 0; overflow-y: auto; min-width: 0; }

/* ── Role list ─────────────────────────────────────────────────────────────── */
.role-list-header { padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #6c757d; border-bottom: 1px solid #eee; background: #f8f9fa; }
.role-item { display: flex; align-items: center; padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background .15s; gap: 8px; }
.role-item:hover { background: #f0f5ff; }
.role-item.active { background: #e8f0fe; border-left: 3px solid #4a6cf7; }
.role-item .role-icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
.role-item .role-name { font-size: 13px; font-weight: 600; flex: 1; }
.role-item .badge-count { font-size: 10px; background: #e9ecef; color: #495057; padding: 1px 6px; border-radius: 10px; }
.role-item.wildcard .role-icon { background: #fff3cd; color: #856404; }
.role-item.employee .role-icon { background: #d1ecf1; color: #0c5460; }

/* ── Main panel header ─────────────────────────────────────────────────────── */
#perm-header { padding: 16px 20px; border-bottom: 1px solid #dee2e6; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
#perm-header h5 { margin: 0; font-size: 16px; font-weight: 700; }
.perm-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

/* ── Tabs ──────────────────────────────────────────────────────────────────── */
.perm-tabs { display: flex; border-bottom: 1px solid #dee2e6; background: #f8f9fa; }
.perm-tab { padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; border-bottom: 2px solid transparent; color: #6c757d; transition: all .15s; }
.perm-tab:hover { color: #4a6cf7; }
.perm-tab.active { color: #4a6cf7; border-bottom-color: #4a6cf7; background: #fff; }

/* ── Tab content ───────────────────────────────────────────────────────────── */
.tab-pane-perm { display: none; padding: 16px 20px; }
.tab-pane-perm.active { display: block; }

/* ── Menu grid ─────────────────────────────────────────────────────────────── */
.menu-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #6c757d; margin: 16px 0 8px; }
.menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; }
.menu-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px 12px; transition: box-shadow .15s, border-color .15s; background: #fff; }
.menu-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.menu-card.allowed { border-color: #c3e6cb; background: #f8fff9; }
.menu-card.blocked { border-color: #f5c6cb; background: #fff8f8; }
.menu-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.menu-card-label { font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.menu-card-icon { font-size: 12px; color: #6c757d; }
.menu-submenus { margin-top: 8px; padding-top: 6px; border-top: 1px solid #f0f0f0; display: flex; flex-wrap: wrap; gap: 4px; }
.sub-badge { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; padding: 2px 7px; border-radius: 10px; cursor: pointer; border: 1px solid transparent; transition: all .15s; }
.sub-badge.on { background: #d4edda; color: #155724; border-color: #c3e6cb; }
.sub-badge.off { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
.sub-badge:hover { opacity: .8; }

/* ── Toggle switch ─────────────────────────────────────────────────────────── */
.toggle-switch { position: relative; width: 36px; height: 20px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 20px; transition: .25s; }
.toggle-slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: .25s; }
input:checked + .toggle-slider { background: #28a745; }
input:checked + .toggle-slider:before { transform: translateX(16px); }
.toggle-switch.disabled .toggle-slider { background: #adb5bd; cursor: not-allowed; }
input:checked + .toggle-slider.wildcard-on { background: #ffc107; }

/* ── Action table ──────────────────────────────────────────────────────────── */
.action-section { margin-bottom: 16px; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
.action-section-header { padding: 10px 14px; background: #f1f3f9; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
.action-section-header:hover { background: #e8ecf5; }
.action-section-body { padding: 8px 12px; display: none; }
.action-section-body.open { display: flex; flex-wrap: wrap; gap: 6px; padding: 10px 12px; }
.action-chip { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #dee2e6; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all .15s; background: #fff; }
.action-chip.on { border-color: #28a745; background: #d4edda; color: #155724; }
.action-chip.off { border-color: #dc3545; background: #f8d7da; color: #721c24; }
.action-chip:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,.1); }
.action-chip .chip-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

/* ── Empty / loading states ────────────────────────────────────────────────── */
#perm-placeholder { padding: 60px 20px; text-align: center; color: #adb5bd; }
#perm-loading { padding: 40px; text-align: center; color: #6c757d; display: none; }
.wildcard-notice { margin: 16px 0; padding: 10px 14px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; font-size: 13px; }

/* ── Default indicator ─────────────────────────────────────────────────────── */
.default-dot { width: 6px; height: 6px; border-radius: 50%; background: #6c757d; display: inline-block; margin-left: 4px; }
.default-dot.changed { background: #fd7e14; }

/* ── Save bar ──────────────────────────────────────────────────────────────── */
#save-bar { display: none; position: sticky; bottom: 0; background: #fff; border-top: 1px solid #dee2e6; padding: 10px 20px; z-index: 100; align-items: center; justify-content: space-between; gap: 8px; }
#save-bar.show { display: flex; }
.unsaved-msg { font-size: 13px; color: #856404; background: #fff3cd; border-radius: 4px; padding: 4px 10px; }

/* ── Responsive ────────────────────────────────────────────────────────────── */
@media(max-width: 768px) {
    #perm-layout { flex-direction: column; }
    #role-sidebar { width: 100%; border-right: none; border-bottom: 1px solid #dee2e6; max-height: 200px; border-radius: 4px 4px 0 0; }
    #perm-main { border-radius: 0 0 4px 4px; }
    .menu-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
}
</style>
@endpush

@section('content')

<div class="card shadow-sm p-0" style="border:1px solid #dee2e6; border-radius:6px;">
<div id="perm-layout">

    {{-- ── Left: Role Sidebar ─────────────────────────────────────────── --}}
    <div id="role-sidebar">
        <div class="role-list-header"><i class="fas fa-user-tag mr-1"></i> Roles</div>
        @foreach($roles as $role)
        <div class="role-item {{ $role->is_wildcard ? 'wildcard' : 'employee' }}"
             data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}" data-wildcard="{{ $role->is_wildcard ? 1 : 0 }}">
            <div class="role-icon">
                @if($role->is_wildcard)
                    <i class="fas fa-crown"></i>
                @elseif(in_array($role->name, ['Branch Manager','Assistant Manager']))
                    <i class="fas fa-user-tie"></i>
                @elseif($role->name === 'Field Technician')
                    <i class="fas fa-tools"></i>
                @elseif(in_array($role->name, ['Billing Officer','Collection Officer']))
                    <i class="fas fa-cash-register"></i>
                @elseif($role->name === 'Network Engineer')
                    <i class="fas fa-network-wired"></i>
                @elseif($role->name === 'Support Executive')
                    <i class="fas fa-headset"></i>
                @elseif(in_array($role->name, ['Reseller','Sub Reseller']))
                    <i class="fas fa-store"></i>
                @else
                    <i class="fas fa-user-shield"></i>
                @endif
            </div>
            <span class="role-name">{{ $role->name }}</span>
            <span class="badge-count">{{ $role->employee_count }}</span>
        </div>
        @endforeach
    </div>

    {{-- ── Right: Permission Editor ────────────────────────────────────── --}}
    <div id="perm-main">
        {{-- Placeholder --}}
        <div id="perm-placeholder">
            <i class="fas fa-shield-alt fa-3x mb-3 d-block"></i>
            <h6>Select a role from the left to manage its permissions</h6>
            <p class="mb-0" style="font-size:13px;">All changes are saved immediately and apply to all employees with that role.</p>
        </div>

        {{-- Loading --}}
        <div id="perm-loading">
            <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
            <span>Loading permissions…</span>
        </div>

        {{-- Editor (hidden until role selected) --}}
        <div id="perm-editor" style="display:none;">
            <div id="perm-header">
                <div>
                    <h5 id="perm-role-title">-</h5>
                    <small id="perm-role-meta" class="text-muted"></small>
                </div>
                <div class="perm-actions">
                    <button id="btn-reset" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-undo mr-1"></i>Reset to Defaults
                    </button>
                    <button id="btn-save-menus" class="btn btn-sm btn-success" data-tab="menus">
                        <i class="fas fa-save mr-1"></i>Save Menu Access
                    </button>
                    <button id="btn-save-actions" class="btn btn-sm btn-primary" data-tab="actions" style="display:none;">
                        <i class="fas fa-save mr-1"></i>Save Page Actions
                    </button>
                </div>
            </div>

            {{-- Wildcard notice --}}
            <div id="wildcard-notice" class="wildcard-notice" style="display:none;margin:16px 20px;">
                <i class="fas fa-crown text-warning mr-2"></i>
                <strong>This is a full-access role.</strong> Wildcard roles (like admin) always have complete access — their permissions cannot be changed here.
                Changes to individual employees of this role can be made via the <strong>Employee Edit</strong> page.
            </div>

            {{-- Tabs --}}
            <div class="perm-tabs" id="perm-tab-nav">
                <div class="perm-tab active" data-tab="menus"><i class="fas fa-bars mr-1"></i>Menu Access</div>
                <div class="perm-tab" data-tab="actions"><i class="fas fa-bolt mr-1"></i>Page Actions</div>
            </div>

            {{-- Tab: Menu Access --}}
            <div class="tab-pane-perm active" id="tab-menus">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px;color:#6c757d;"><i class="fas fa-info-circle mr-1"></i>Click any menu card to toggle. Green = accessible, Red = blocked. Click sub-items to toggle individual submenus.</span>
                    <div>
                        <button class="btn btn-xs btn-outline-success mr-1" id="btn-allow-all-menus"><i class="fas fa-check-double mr-1"></i>Allow All</button>
                        <button class="btn btn-xs btn-outline-danger" id="btn-block-all-menus"><i class="fas fa-ban mr-1"></i>Block All</button>
                    </div>
                </div>
                <div id="menu-grid-container"></div>
            </div>

            {{-- Tab: Page Actions --}}
            <div class="tab-pane-perm" id="tab-actions">
                <div class="mb-2" style="font-size:12px;color:#6c757d;"><i class="fas fa-info-circle mr-1"></i>Click any action chip to toggle. These control specific buttons and features within each page.</div>
                <div id="action-container"></div>
            </div>
        </div>
    </div>

</div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
let currentRoleId = null;
let permData = null;
let isDirty = false;

// ── Helpers ────────────────────────────────────────────────────────────────
function api(url, method = 'GET', body = null) {
    const opts = { method, headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    return fetch(url, opts).then(r => r.json());
}

function toast(msg, type = 'success') {
    const cls = type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger');
    const t = document.createElement('div');
    t.className = `alert alert-${cls} alert-dismissible shadow`;
    t.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;min-width:280px;font-size:13px;';
    t.innerHTML = `${msg}<button type="button" class="close" onclick="this.parentNode.remove()"><span>&times;</span></button>`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}

function makeToggle(checked, disabled, extraClass = '') {
    return `<label class="toggle-switch ${disabled ? 'disabled' : ''} ${extraClass}">
        <input type="checkbox" ${checked ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
        <span class="toggle-slider ${disabled && checked ? 'wildcard-on' : ''}"></span>
    </label>`;
}

// ── Role selection ─────────────────────────────────────────────────────────
document.querySelectorAll('.role-item').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.role-item').forEach(r => r.classList.remove('active'));
        el.classList.add('active');
        loadRole(el.dataset.roleId);
    });
});

function loadRole(roleId) {
    currentRoleId = roleId;
    document.getElementById('perm-placeholder').style.display = 'none';
    document.getElementById('perm-editor').style.display = 'none';
    document.getElementById('perm-loading').style.display = 'block';
    isDirty = false;

    api(`{{ url('admin/permissions/roles') }}/${roleId}`)
        .then(data => {
            permData = data;
            document.getElementById('perm-loading').style.display = 'none';
            document.getElementById('perm-editor').style.display = 'block';
            renderRole(data);
        })
        .catch(() => {
            document.getElementById('perm-loading').style.display = 'none';
            toast('Failed to load permissions', 'danger');
        });
}

function renderRole(data) {
    const role = data.role;
    const isWildcard = role.is_wildcard;

    document.getElementById('perm-role-title').textContent = role.name;
    document.getElementById('perm-role-meta').textContent = isWildcard
        ? 'Full Access Role — all permissions are always granted'
        : 'Employee Role — customize menu and action access below';

    document.getElementById('wildcard-notice').style.display = isWildcard ? 'block' : 'none';
    document.getElementById('btn-reset').style.display = isWildcard ? 'none' : '';
    document.getElementById('btn-save-menus').style.display = isWildcard ? 'none' : '';

    // Check which tab is active
    const activeTab = document.querySelector('.perm-tab.active')?.dataset.tab || 'menus';
    const saveBtns = { menus: document.getElementById('btn-save-menus'), actions: document.getElementById('btn-save-actions') };
    saveBtns.menus.style.display = (activeTab === 'menus' && !isWildcard) ? '' : 'none';
    saveBtns.actions.style.display = (activeTab === 'actions' && !isWildcard) ? '' : 'none';

    renderMenus(data.menus, isWildcard);
    renderActions(data.actions, isWildcard);
}

// ── Menu rendering ─────────────────────────────────────────────────────────
function renderMenus(menus, isWildcard) {
    const container = document.getElementById('menu-grid-container');
    const grid = document.createElement('div');
    grid.className = 'menu-grid';

    Object.entries(menus).forEach(([key, menu]) => {
        const card = document.createElement('div');
        card.className = `menu-card ${menu.allowed ? 'allowed' : 'blocked'}`;
        card.dataset.key = key;

        const hasSubs = Object.keys(menu.submenus || {}).length > 0;
        const isDefault = menu.allowed === menu.default;

        let subsHtml = '';
        if (hasSubs) {
            subsHtml = '<div class="menu-submenus">';
            Object.entries(menu.submenus).forEach(([sk, sub]) => {
                subsHtml += `<span class="sub-badge ${sub.allowed ? 'on' : 'off'}" data-menu="${key}" data-sub="${sk}" title="${sub.label}">
                    <i class="fas fa-${sub.allowed ? 'check' : 'times'}" style="font-size:9px;"></i>
                    ${sub.label}
                </span>`;
            });
            subsHtml += '</div>';
        }

        card.innerHTML = `
            <div class="menu-card-header">
                <span class="menu-card-label">
                    ${menu.icon ? `<i class="${menu.icon} menu-card-icon"></i>` : ''}
                    ${menu.label}
                    ${!isDefault ? '<span class="default-dot changed" title="Modified from default"></span>' : ''}
                </span>
                ${makeToggle(menu.allowed, isWildcard)}
            </div>
            ${subsHtml}
        `;

        if (!isWildcard) {
            const toggle = card.querySelector('input[type=checkbox]');
            toggle.addEventListener('change', () => {
                const val = toggle.checked;
                permData.menus[key].allowed = val;
                card.className = `menu-card ${val ? 'allowed' : 'blocked'}`;
                // Toggle all submenus to match
                Object.keys(menu.submenus || {}).forEach(sk => {
                    permData.menus[key].submenus[sk].allowed = val;
                });
                renderMenus(permData.menus, isWildcard);
                isDirty = true;
            });

            card.querySelectorAll('.sub-badge').forEach(badge => {
                badge.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const mk = badge.dataset.menu;
                    const sk = badge.dataset.sub;
                    const current = permData.menus[mk].submenus[sk].allowed;
                    permData.menus[mk].submenus[sk].allowed = !current;
                    // If enabling sub, also enable parent
                    if (!current) permData.menus[mk].allowed = true;
                    renderMenus(permData.menus, isWildcard);
                    isDirty = true;
                });
            });
        }

        grid.appendChild(card);
    });

    container.innerHTML = '';
    container.appendChild(grid);
}

// ── Action rendering ───────────────────────────────────────────────────────
function renderActions(actions, isWildcard) {
    const container = document.getElementById('action-container');
    container.innerHTML = '';

    Object.entries(actions).forEach(([pageKey, page]) => {
        const section = document.createElement('div');
        section.className = 'action-section';

        const header = document.createElement('div');
        header.className = 'action-section-header';
        header.innerHTML = `<span><i class="fas fa-caret-right mr-2"></i>${page.label}</span>
            <small class="text-muted">${Object.keys(page.actions).length} actions</small>`;

        const body = document.createElement('div');
        body.className = 'action-section-body';

        Object.entries(page.actions).forEach(([actionKey, action]) => {
            const fullKey = `${pageKey}.${actionKey}`;
            const chip = document.createElement('span');
            chip.className = `action-chip ${action.allowed ? 'on' : 'off'}`;
            chip.dataset.key = fullKey;
            chip.dataset.page = pageKey;
            chip.dataset.action = actionKey;
            chip.innerHTML = `<span class="chip-dot"></span>${action.label}`;

            if (!isWildcard) {
                chip.addEventListener('click', () => {
                    const val = !permData.actions[pageKey].actions[actionKey].allowed;
                    permData.actions[pageKey].actions[actionKey].allowed = val;
                    chip.className = `action-chip ${val ? 'on' : 'off'}`;
                    isDirty = true;
                });
            }

            body.appendChild(chip);
        });

        header.addEventListener('click', () => {
            body.classList.toggle('open');
            header.querySelector('.fa-caret-right, .fa-caret-down').classList.toggle('fa-caret-right');
            header.querySelector('.fa-caret-right, .fa-caret-down').classList.toggle('fa-caret-down');
        });

        section.appendChild(header);
        section.appendChild(body);
        container.appendChild(section);
    });
}

// ── Tabs ───────────────────────────────────────────────────────────────────
document.querySelectorAll('.perm-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.perm-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane-perm').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');

        const isWildcard = permData?.role?.is_wildcard;
        document.getElementById('btn-save-menus').style.display = (!isWildcard && tab.dataset.tab === 'menus') ? '' : 'none';
        document.getElementById('btn-save-actions').style.display = (!isWildcard && tab.dataset.tab === 'actions') ? '' : 'none';
    });
});

// ── Save menus ─────────────────────────────────────────────────────────────
document.getElementById('btn-save-menus').addEventListener('click', () => {
    if (!currentRoleId || !permData) return;

    const menus = {};
    Object.entries(permData.menus).forEach(([mk, m]) => {
        menus[mk] = m.allowed ? 1 : 0;
        Object.entries(m.submenus || {}).forEach(([sk, s]) => {
            menus[`${mk}.${sk}`] = s.allowed ? 1 : 0;
        });
    });

    const btn = document.getElementById('btn-save-menus');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving…';

    api(`{{ url('admin/permissions/roles') }}/${currentRoleId}/menus`, 'POST', { menus })
        .then(res => {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Menu Access';
            if (res.success) { toast(res.message); isDirty = false; loadRole(currentRoleId); }
            else toast(res.message, 'danger');
        })
        .catch(() => {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Menu Access';
            toast('Save failed', 'danger');
        });
});

// ── Save actions ───────────────────────────────────────────────────────────
document.getElementById('btn-save-actions').addEventListener('click', () => {
    if (!currentRoleId || !permData) return;

    const actions = {};
    Object.entries(permData.actions).forEach(([pageKey, page]) => {
        Object.entries(page.actions).forEach(([ak, a]) => {
            actions[`${pageKey}.${ak}`] = a.allowed ? 1 : 0;
        });
    });

    const btn = document.getElementById('btn-save-actions');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving…';

    api(`{{ url('admin/permissions/roles') }}/${currentRoleId}/actions`, 'POST', { actions })
        .then(res => {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Page Actions';
            if (res.success) { toast(res.message); isDirty = false; }
            else toast(res.message, 'danger');
        })
        .catch(() => {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Page Actions';
            toast('Save failed', 'danger');
        });
});

// ── Reset role ─────────────────────────────────────────────────────────────
document.getElementById('btn-reset').addEventListener('click', () => {
    if (!currentRoleId || !permData) return;
    const roleName = permData.role.name;
    if (!confirm(`Reset ALL permissions for "${roleName}" back to system defaults?\n\nThis will delete any custom settings for this role.`)) return;

    api(`{{ url('admin/permissions/roles') }}/${currentRoleId}/reset`, 'DELETE')
        .then(res => {
            if (res.success) { toast(res.message); loadRole(currentRoleId); }
            else toast(res.message, 'danger');
        });
});

// ── Allow/Block All menus ──────────────────────────────────────────────────
document.getElementById('btn-allow-all-menus').addEventListener('click', () => {
    if (!permData) return;
    Object.keys(permData.menus).forEach(mk => {
        permData.menus[mk].allowed = true;
        Object.keys(permData.menus[mk].submenus || {}).forEach(sk => {
            permData.menus[mk].submenus[sk].allowed = true;
        });
    });
    renderMenus(permData.menus, permData.role.is_wildcard);
    isDirty = true;
});

document.getElementById('btn-block-all-menus').addEventListener('click', () => {
    if (!permData) return;
    Object.keys(permData.menus).forEach(mk => {
        permData.menus[mk].allowed = false;
        Object.keys(permData.menus[mk].submenus || {}).forEach(sk => {
            permData.menus[mk].submenus[sk].allowed = false;
        });
    });
    renderMenus(permData.menus, permData.role.is_wildcard);
    isDirty = true;
});

// ── Warn before leaving with unsaved changes ───────────────────────────────
window.addEventListener('beforeunload', e => {
    if (isDirty) { e.preventDefault(); e.returnValue = ''; }
});
</script>
@endsection
