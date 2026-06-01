@php $u = auth()->user(); @endphp
@if($u)
  @php
    $__isSA = $u->isSuperAdmin();
    $__isAdminOrSA = $__isSA || (method_exists($u,'hasRole') && ($u->hasRole('admin') || $u->hasRole('Administrator')));
    // Check company menu permission for media_servers
    $__msAllowed = $__isAdminOrSA;
    if (!$__msAllowed && $u->company_id) {
        $__co = $u->company ?? \App\Models\Company::find($u->company_id);
        if ($__co) {
            $__menus = $__co->allowed_menus ?? [];
            $__msAllowed = empty($__menus)
                || in_array('media_servers.ftp', $__menus)
                || in_array('media_servers.live_tv', $__menus)
                || in_array('media_servers.other', $__menus);
        }
    }
  @endphp

  @if($__isAdminOrSA)
    {{-- Admin / SuperAdmin: link to management page --}}
    <li class="nav-item">
      <a href="{{ route('admin.media-servers.index') }}"
         class="nav-link {{ request()->routeIs('admin.media-servers*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-play-circle" style="color:#e74c3c;"></i>
        <p>Media Servers</p>
      </a>
    </li>
  @elseif($__msAllowed && isset($mediaServers) && $mediaServers->count() > 0)
    {{-- Regular staff with permission: link to client-facing page --}}
    <li class="nav-item">
      <a href="{{ route('client.media-servers') }}"
         class="nav-link {{ request()->routeIs('client.media-servers') ? 'active' : '' }}">
        <i class="nav-icon fas fa-play-circle" style="color:#e74c3c;"></i>
        <p>Media Servers</p>
      </a>
    </li>
  @endif
@endif
