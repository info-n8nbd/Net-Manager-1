@if(session('impersonate_original_user_id'))
<div style="position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: linear-gradient(90deg, #e74c3c, #c0392b); color: white; padding: 8px 15px; text-align: center; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
    <i class="fas fa-user-secret mr-2"></i>
    You are logged in as <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->getRoleNames()->first() }})
    <span class="mx-2">|</span>
    Original Admin: <strong>{{ session('impersonate_original_user_name') }}</strong>
    <a href="{{ route('impersonate.switch-back') }}" class="btn btn-sm btn-light ml-3" style="color: #c0392b; font-weight: bold;">
        <i class="fas fa-undo mr-1"></i>Switch Back to Admin
    </a>
</div>
<div style="height: 42px;"></div>
@elseif(session('impersonating_from'))
<div style="position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: linear-gradient(90deg, #e67e22, #d35400); color: white; padding: 8px 15px; text-align: center; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
    <i class="fas fa-user-secret mr-2"></i>
    You are viewing as <strong>{{ Auth::user()->company->name ?? 'Company' }}</strong> admin
    <a href="{{ route('superadmin.switch-back') }}" class="btn btn-sm btn-light ml-3" style="color: #d35400; font-weight: bold;">
        <i class="fas fa-undo mr-1"></i>Switch Back to Super Admin
    </a>
</div>
<div style="height: 42px;"></div>
@endif
