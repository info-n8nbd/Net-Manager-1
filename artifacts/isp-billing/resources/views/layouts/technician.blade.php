<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e3c72">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <title>@yield('title', 'Dashboard') - {{ $companyName ?? 'SOFTYNEX' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        * { font-family: 'Poppins', 'Noto Sans Bengali', sans-serif; font-size: 12.5px; }
        body { background: #f0f4f8; font-size: 12.5px; }
        
        .brand-link {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            padding: 12px 20px !important;
            border-bottom: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .brand-content {
            text-align: center;
        }
        .brand-logo-icon {
            width: 180px;
            height: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        .brand-logo-icon img {
            max-width: 100%;
            height: auto;
            max-height: 60px;
        }
        .brand-logo-icon i {
            font-size: 22px;
            color: #1e3c72;
        }
        .brand-text {
            display: block;
            color: white !important;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
        }
        .brand-tagline {
            display: block;
            color: rgba(255,255,255,0.7);
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        
        .main-header.navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            border: none !important;
            box-shadow: 0 2px 10px rgba(30,60,114,0.3);
        }
        .main-header .nav-link { 
            color: rgba(255,255,255,0.9) !important; 
            font-weight: 500;
            padding: 10px 15px !important;
        }
        .main-header .nav-link:hover { 
            color: white !important;
            background: rgba(255,255,255,0.1) !important;
            border-radius: 6px;
        }
        .main-header .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            border-radius: 8px;
        }
        
        .main-sidebar {
            background: white !important;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }
        .sidebar {
            padding-bottom: 100px !important;
        }
        .main-sidebar, .main-sidebar::before {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
        }
        
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active,
        .sidebar-light-primary .nav-sidebar > .nav-item > .nav-link.active {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
            box-shadow: none !important;
            border-radius: 8px;
            margin: 0 8px;
        }
        
        .nav-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            border-radius: 8px;
            margin: 2px 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        .nav-sidebar .nav-link .nav-icon {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        .nav-sidebar .nav-link::before {
            display: none !important;
        }
        .nav-sidebar .nav-link:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.1) !important;
            transform: none !important;
            box-shadow: none !important;
        }
        .nav-sidebar .nav-link:hover .nav-icon {
            color: #ffffff !important;
        }
        .nav-sidebar .nav-link.active .nav-icon,
        .nav-sidebar .nav-link.active p {
            color: #ffffff !important;
        }
        .nav-sidebar .nav-treeview {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin: 4px 8px;
            padding: 4px 0;
        }
        .nav-sidebar .nav-treeview .nav-link {
            font-size: 13px;
            padding: 8px 15px;
            margin: 0;
            color: rgba(255, 255, 255, 0.7) !important;
        }
        .nav-sidebar .nav-treeview .nav-link:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
        .nav-sidebar .nav-treeview .nav-link.active {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
        
        .nav-header {
            color: rgba(255, 255, 255, 0.5) !important;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 15px 20px 8px !important;
        }
        
        .user-panel {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        .user-panel .info a {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
        }
        
        .content-wrapper {
            background: #f0f4f8 !important;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
        }
        
        .main-footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
        }
        .btn-primary:hover {
            background: #163057;
        }

        .portal-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            margin-left: 10px;
        }
        
        /* Desktop: Traditional info-box style */
        .stat-box { min-height: 90px; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 28px; font-weight: bold; line-height: 1.2; }
        .stat-label { font-size: 13px; opacity: 0.9; margin-top: 4px; }
        
        /* Mobile: Compact cube style */
        @media (max-width: 767px) {
            .stat-box { min-height: 55px; padding: 8px 5px !important; box-shadow: none; }
            .stat-number { font-size: 16px; }
            .stat-label { font-size: 10px; margin-top: 2px; }
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 4px solid transparent;
            animation: alertSlideIn 0.4s ease-out;
        }
        @keyframes alertSlideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert .close {
            opacity: 0.6;
            text-shadow: none;
            font-size: 18px;
            padding: 0 0 0 10px;
            margin-left: auto;
        }
        .alert .close:hover { opacity: 1; }
        .alert i.fas, .alert i.far { font-size: 18px; margin-top: 1px; flex-shrink: 0; }
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%);
            color: #065f46;
            border-left-color: #10b981;
        }
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fef2f2 100%);
            color: #991b1b;
            border-left-color: #ef4444;
        }
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
            color: #92400e;
            border-left-color: #f59e0b;
        }
        .alert-info {
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            color: #075985;
            border-left-color: #3b82f6;
        }
    
        /* Layout margin-top handled by applyAdminLayout() JS */
        body.layout-fixed .content-wrapper {
            min-height: calc(100vh - var(--nav-h, 57px) - var(--at-h, 0px)) !important;
        }
</style>
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css?v=5.2') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/ui-lite.css') . '?v=' . time() }}">
    <style>
        /* ===== MOBILE USER MENU OVERLAY ===== */
        #mobileUserMenu {
            display: none; position: fixed; top: 57px; right: 10px;
            width: 220px; background: #fff; border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18); z-index: 99999; overflow: hidden;
        }
        #mobileUserMenu.show { display: block; }
        #mobileUserMenuBackdrop {
            display: none; position: fixed; inset: 0; z-index: 99998; background: rgba(0,0,0,0.01);
        }
        #mobileUserMenuBackdrop.show { display: block; }
        .mobile-user-menu-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 14px 16px; color: #fff;
        }
        .mobile-user-menu-item {
            display: flex; align-items: center; gap: 10px; padding: 12px 16px;
            color: #333; text-decoration: none; font-size: 14px; font-weight: 600;
            border: none; background: none; width: 100%; text-align: left; cursor: pointer;
        }
        .mobile-user-menu-item:hover { background: #f8f9fa; }
        .mobile-user-menu-item.logout-btn { color: #e53e3e; }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
@include('partials.impersonate-banner')
<div class="wrapper">
    @php
        $__noticeEnabled = \App\Models\SystemSetting::get("notice_enabled", "0");
        $__noticeText    = \App\Models\SystemSetting::get("notice_text", "");
        $__noticeType    = \App\Models\SystemSetting::get("notice_type", "info");
        $__noticeSpeed   = \App\Models\SystemSetting::get("notice_speed", "normal");
        $__noticeMessages = array_values(array_filter(array_map("trim", explode("\n", $__noticeText ?? ""))));
    @endphp
    @if($__noticeEnabled == "1" && count($__noticeMessages) > 0)
        @include("partials.announcement-ticker", [
            "messages" => $__noticeMessages,
            "type"     => $__noticeType,
            "speed"    => $__noticeSpeed,
        ])
    @endif
    <nav class="main-header navbar navbar-expand navbar-dark">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('technician.dashboard') }}" class="nav-link">
                    <i class="fas fa-home mr-1"></i> Home
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="portal-badge"><i class="fas fa-tools mr-1"></i> Technician Portal</span>
            </li>
        </ul>
        
        <div class="global-search-container mx-auto d-none d-md-block" style="width: 100%; max-width: 400px; position: relative;">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" style="background: rgba(255,255,255,0.15); border: none; color: rgba(255,255,255,0.8);"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="globalQuickSearch" class="form-control" placeholder="Search client..." style="background: rgba(255,255,255,0.15); border: none; color: white; border-radius: 0 25px 25px 0;">
            </div>
            <div id="searchResults" class="search-results-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); z-index: 1050; max-height: 400px; overflow-y: auto; margin-top: 8px;"></div>
        </div>
        
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user mr-1"></i> {{ auth()->user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('technician.profile') }}" class="dropdown-item">
                        <i class="fas fa-user-circle mr-2"></i> My Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-light-primary elevation-1">
        <a href="{{ route('technician.dashboard') }}" class="brand-link">
            <div class="brand-content">
                <div class="brand-logo-icon">
                    <img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="SOFTYNEX Logo">
                </div>
                <span class="brand-text">{{ $companyName ?? 'SOFTYNEX' }}</span>
                <span class="brand-tagline">{{ $companyTagline ?? 'Connecting You to the Future' }}</span>
            </div>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 40px; height: 40px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; font-weight: 600;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{ auth()->user()->name }}</a>
                    <small class="text-muted">Technician</small>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    @if(auth()->user()->can('technician.dashboard'))
                    <li class="nav-item">
                        <a href="{{ route('technician.dashboard') }}" class="nav-link {{ request()->routeIs('technician.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    @endif
                    
                    @if(auth()->user()->can('technician.clients'))
                    <li class="nav-item">
                        <a href="{{ route('technician.clients') }}" class="nav-link {{ request()->routeIs('technician.clients') && !request()->routeIs('technician.clients.create') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>My Clients</p>
                        </a>
                    </li>

                    @endif
                    
                    
                    @if((auth()->user()->can('technician.due_report') || auth()->user()->can('technician.collection')))
                    <li class="nav-header">BILLING</li>
                    @endif
                    
                    @if(auth()->user()->can('technician.due_report'))
                    <li class="nav-item">
                        <a href="{{ route('technician.due-report') }}" class="nav-link {{ request()->routeIs('technician.due-report') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-exclamation-triangle"></i>
                            <p>Due Report</p>
                        </a>
                    </li>
                    @endif
                    
                    @if(auth()->user()->can('technician.collection'))
                    <li class="nav-item">
                        <a href="{{ route('technician.collection-report') }}" class="nav-link {{ request()->routeIs('technician.collection-report') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>My Collection</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('technician.submission-history') }}" class="nav-link {{ request()->routeIs('technician.submission-history') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hand-holding-usd"></i>
                            <p>Submit to Admin</p>
                        </a>
                    </li>
                    @endif
                    
                    @if(auth()->user()->can('technician.tickets'))
                    <li class="nav-header">SUPPORT</li>
                    <li class="nav-item">
                        <a href="{{ route('technician.tickets') }}" class="nav-link {{ request()->routeIs('technician.tickets*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-ticket-alt"></i>
                            <p>Assigned Tickets</p>
                        </a>
                    </li>
                    @endif
                    
                    @if(auth()->user()->can('technician.new_connection'))
                    @if(Route::has('technician.new-connection-applications'))
                    <li class="nav-header">CONNECTIONS</li>
                    <li class="nav-item">
                        <a href="{{ route('technician.new-connection-applications') }}" class="nav-link {{ request()->routeIs('technician.new-connection*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-plus-circle"></i>
                            <p>New Connections</p>
                        </a>
                    </li>
                    @endif
                    @endif
                    
                    @if(auth()->user()->can('technician.package_requests'))
                    @if(Route::has('technician.package-requests'))
                    <li class="nav-item">
                        <a href="{{ route('technician.package-requests') }}" class="nav-link {{ request()->routeIs('technician.package-requests*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-box"></i>
                            <p>Package Requests</p>
                        </a>
                    </li>
                    @endif
                    @endif
                    
                    @include("components.media-servers-sidebar")
                    <li class="nav-header">ACCOUNT</li>
                    <li class="nav-item">
                        <a href="{{ route('technician.attendance') }}" class="nav-link {{ request()->routeIs('technician.attendance*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-camera" style="color:#e67e22;"></i>
                            <p>My Attendance</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('technician.profile') }}" class="nav-link {{ request()->routeIs('technician.profile*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-circle"></i>
                            <p>My Profile</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0" style="color: #1e3c72; font-weight: 600;">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Home</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <div class="flex-grow-1">{{ session('success') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="flex-grow-1">{{ session('warning') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle"></i>
                        <div class="flex-grow-1">{{ session('info') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="flex-grow-1">{{ session('error') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="flex-grow-1">
                            <strong>Please fix the following:</strong>
                            <ul class="mb-0 mt-1 pl-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer text-center">
        &copy; {{ date('Y') }} <strong>{{ $companyName ?? 'SOFTYNEX' }}</strong>. All Rights Reserved.
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };</script>
<script>
const SmartSwal = Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-primary px-4',
        cancelButton: 'btn btn-secondary px-4 ml-2',
    },
    buttonsStyling: false,
});
window.SmartSwal = SmartSwal;
</script>
<script src="/js/sweet-confirm.js?v=4"></script>

<script>
$(document).ready(function() {
    let searchTimeout;
    const searchInput = $('#globalQuickSearch');
    const searchResults = $('#searchResults');
    
    searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        if (query.length < 2) {
            searchResults.hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '{{ route("technician.api.quick-search") }}',
                data: { q: query },
                success: function(data) {
                    if (data.length === 0) {
                        searchResults.html('<div class="p-4 text-center text-muted"><i class="fas fa-search fa-2x mb-2"></i><p class="mb-0">No clients found</p></div>').show();
                        return;
                    }
                    
                    let html = '<div class="p-2">';
                    data.forEach(function(client) {
                        const statusColor = client.status === 'active' ? '#10b981' : (client.status === 'inactive' ? '#ef4444' : '#f59e0b');
                        const connectionColor = client.connection_status === 'online' ? '#10b981' : '#ef4444';
                        html += `
                            <a href="${client.url}" class="search-result-item d-flex align-items-center p-3" style="text-decoration: none; color: inherit; border-radius: 10px; transition: all 0.2s;">
                                <div class="d-flex align-items-center justify-content-center rounded-circle text-white mr-3" style="width: 45px; height: 45px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); font-size: 16px; font-weight: 600;">
                                    ${client.name.charAt(0).toUpperCase()}
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center">
                                        <strong style="color: #1e3c72;">${client.name}</strong>
                                        <span class="badge ml-2" style="background: ${statusColor}; color: white; font-size: 10px;">${client.status}</span>
                                        <span class="badge ml-1" style="background: ${connectionColor}; color: white; font-size: 10px;"><i class="fas fa-wifi mr-1"></i>${client.connection_status}</span>
                                    </div>
                                    <div class="small text-muted">
                                        <span class="mr-3"><i class="fas fa-id-badge mr-1"></i>${client.client_id}</span>
                                        <span class="mr-3"><i class="fas fa-phone mr-1"></i>${client.phone}</span>
                                        <span><i class="fas fa-map-marker-alt mr-1"></i>${client.zone}</span>
                                    </div>
                                    <div class="small" style="color: #64748b;">
                                        <i class="fas fa-wifi mr-1"></i>${client.package} | <i class="fas fa-user mr-1"></i>${client.username || 'N/A'}
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                        `;
                    });
                    html += '</div>';
                    searchResults.html(html).show();
                }
            });
        }, 200);
    });
    
    searchInput.on('focus', function() {
        if (searchResults.children().length > 0) {
            searchResults.show();
        }
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.global-search-container').length) {
            searchResults.hide();
        }
    });
    
    $(document).on('mouseenter', '.search-result-item', function() {
        $(this).css('background', '#f0f4f8');
    }).on('mouseleave', '.search-result-item', function() {
        $(this).css('background', 'transparent');
    });
});
</script>

<style>
#globalQuickSearch::placeholder {
    color: rgba(255,255,255,0.6);
}
#globalQuickSearch:focus {
    background: rgba(255,255,255,0.25) !important;
    box-shadow: 0 0 0 3px rgba(255,255,255,0.1);
    outline: none;
}
.search-results-dropdown::-webkit-scrollbar {
    width: 6px;
}
.search-results-dropdown::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
</style>

<script>
// Close sidebar when clicking outside on mobile
$(document).ready(function() {
    $(document).on('click touchstart', function(e) {
        if ($(window).width() <= 991) {
            var sidebar = $('.main-sidebar');
            var toggleBtn = $('[data-widget="pushmenu"]');
            
            if ($('body').hasClass('sidebar-open')) {
                if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 && 
                    !toggleBtn.is(e.target) && toggleBtn.has(e.target).length === 0 &&
                    !$(e.target).closest('.mobile-bottom-bar').length) {
                    $('body').removeClass('sidebar-open').addClass('sidebar-collapse');
                }
            }
        }
    });
});
</script>

<script>
setTimeout(function() {
    $('.content-wrapper > .content > .container-fluid > .alert-success, .content-wrapper > .content > .container-fluid > .alert-info, .alert-dismissible.alert-success, .alert-dismissible.alert-info').each(function() {
        var el = $(this);
        setTimeout(function() { el.fadeOut(500, function() { el.remove(); }); }, 5000);
    });
}, 100);
</script>
<style>
.smart-swal-popup {
    border-radius: 16px !important;
    padding: 24px !important;
    font-family: 'Poppins', 'Noto Sans Bengali', sans-serif !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15) !important;
}
.smart-swal-title { font-size: 20px !important; font-weight: 600 !important; color: #1e3c72 !important; }
.smart-swal-html { font-size: 14px !important; color: #4a5568 !important; line-height: 1.6 !important; }
.swal2-icon { transform: scale(0.85) !important; }
</style>


<script>
(function () {
    if (!navigator.geolocation) return;
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) return;
    var endpoint = '/update-my-location';
    function sendLocation(lat, lng, address) {
        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ lat: lat, lng: lng, address: address })
        }).catch(function(){});
    }
    navigator.geolocation.getCurrentPosition(function (pos) {
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        // Reverse geocode with Nominatim
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng, {
            headers: { 'Accept-Language': 'en' }
        }).then(function(r){ return r.json(); }).then(function(data){
            sendLocation(lat, lng, data.display_name || '');
        }).catch(function(){
            sendLocation(lat, lng, '');
        });
    }, function(){}, { timeout: 10000, maximumAge: 300000 });
})();
</script>

@stack('scripts')
<script src="{{ asset('js/bill-realtime-sync.js') }}"></script>
<script src="/js/new-tab-links.js?v={{ time() }}"></script>
@include('components.flatpickr-datepicker')

    <!-- Mobile Bottom Navigation Bar -->
    {{-- Mobile User Menu Overlay --}}
    <div id="mobileUserMenuBackdrop" onclick="closeMobileUserMenu()"></div>
    <div id="mobileUserMenu">
        <div class="mobile-user-menu-header">
            <div style="font-weight:700;font-size:15px;">{{ auth()->user()->name }}</div>
            <div style="font-size:12px;opacity:0.8;">Technician</div>
        </div>
        <a href="{{ route('technician.profile') }}" class="mobile-user-menu-item" onclick="closeMobileUserMenu()">
            <i class="fas fa-user text-primary" style="width:18px;"></i> Profile
        </a>
        <div style="height:1px;background:#eee;margin:0;"></div>
        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="mobile-user-menu-item logout-btn">
                <i class="fas fa-sign-out-alt" style="width:18px;color:#e53e3e;"></i> Logout
            </button>
        </form>
    </div>

    <nav class="mobile-bottom-bar">
        <a href="#" onclick="event.preventDefault(); event.stopPropagation(); (function(){var b=document.body,isOpen=b.classList.contains('sidebar-open');if(isOpen){b.classList.remove('sidebar-open');var ov=document.getElementById('sidebar-overlay');if(ov)ov.remove();}else{b.classList.add('sidebar-open');b.classList.remove('sidebar-collapse');var old=document.getElementById('sidebar-overlay');if(old)old.remove();var ol=document.createElement('div');ol.id='sidebar-overlay';ol.style.display='block';ol.addEventListener('click',function(){b.classList.remove('sidebar-open');ol.remove();});document.body.appendChild(ol);}})();">
            <i class="fas fa-bars"></i>Menu
        </a>
        <a href="{{ route('technician.clients') }}" class="{{ request()->routeIs('technician.clients*') ? 'active' : '' }}">
            <i class="fas fa-users"></i>Clients
        </a>
        <a href="{{ route('technician.dashboard') }}" class="{{ request()->routeIs('technician.dashboard') ? 'active' : '' }}">
            <i class="fas fa-home"></i>Home
        </a>
        <a href="{{ route('technician.attendance') }}" class="{{ request()->routeIs('technician.attendance*') ? 'active' : '' }}">
            <i class="fas fa-user-check"></i>Attendance
        </a>
        <a href="#" onclick="event.preventDefault();event.stopPropagation();toggleMobileUserMenu();" class="{{ request()->routeIs('technician.profile*') ? 'active' : '' }}">
            <i class="fas fa-user"></i>Profile
        </a>
    </nav>

<script src="{{ asset('js/mobile-app.js') }}?v=1.2"></script>
<script>
function toggleMobileUserMenu() {
    var menu = document.getElementById('mobileUserMenu');
    var backdrop = document.getElementById('mobileUserMenuBackdrop');
    if (menu.classList.contains('show')) {
        closeMobileUserMenu();
    } else {
        menu.classList.add('show');
        backdrop.classList.add('show');
    }
}
function closeMobileUserMenu() {
    document.getElementById('mobileUserMenu').classList.remove('show');
    document.getElementById('mobileUserMenuBackdrop').classList.remove('show');
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeMobileUserMenu(); });
</script>
<script>
(function(){
  function applyAdminLayout(){
    var ticker = document.querySelector(".at-ticker");
    var navbar = document.querySelector(".main-header.navbar");
    var cw    = document.querySelector(".content-wrapper");
    if(!navbar) return;
    var tickH = 0;
    if(ticker){ tickH = ticker.getBoundingClientRect().height || ticker.offsetHeight || 36; }
    document.documentElement.style.setProperty("--at-h", tickH + "px");
    navbar.style.top = tickH + "px";
    var navH = navbar.getBoundingClientRect().height || navbar.offsetHeight || 57;
    document.documentElement.style.setProperty("--nav-h", Math.ceil(navH) + "px");
    if(cw){ cw.style.marginTop = (Math.ceil(navH) + Math.ceil(tickH)) + "px"; }
  }
  if(document.readyState==="loading"){
    document.addEventListener("DOMContentLoaded", applyAdminLayout);
  } else { applyAdminLayout(); }
  window.addEventListener("load", applyAdminLayout);
  window.addEventListener("resize", function(){ setTimeout(applyAdminLayout, 50); });
})();
</script>
</body>
</html>
