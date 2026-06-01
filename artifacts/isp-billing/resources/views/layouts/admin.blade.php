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
    @php
        $__favicon = \App\Models\SystemSetting::get('site_favicon', '');
        $__faviconExt = $__favicon ? strtolower(pathinfo($__favicon, PATHINFO_EXTENSION)) : '';
        $__faviconType = $__faviconExt === 'ico' ? 'image/x-icon' : 'image/png';
        $__faviconVer = ($__favicon && file_exists(public_path($__favicon))) ? filemtime(public_path($__favicon)) : time();
    @endphp
    @if($__favicon)
    <link rel="icon" href="{{ asset($__favicon) }}?v={{ $__faviconVer }}" type="{{ $__faviconType }}">
    <link rel="shortcut icon" href="{{ asset($__favicon) }}?v={{ $__faviconVer }}" type="{{ $__faviconType }}">
    @endif
    @php $__appIcon = \App\Models\SystemSetting::get('site_app_icon', ''); @endphp
    @if($__appIcon)<link rel="apple-touch-icon" href="{{ asset($__appIcon) }}">@endif
    <title>@yield('title', 'Dashboard') - {{ $companyName ?? 'SOFTYNEX' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <style>
        * { font-family: 'Poppins', 'Noto Sans Bengali', sans-serif; }
        body { background: #f0f4f8; }
        
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
            background: #1e3c72 !important;
        }
        .sidebar {
            padding-bottom: 100px !important;
        }
        
        .content-wrapper {
            background: #f0f4f8 !important;
            display: flex !important;
            flex-direction: column !important;
        }
        .content-header {
            padding: 6px 0.5rem 4px !important;
        }
        .content-header h1 {
            color: #1e3c72;
            font-weight: 600;
            font-size: 1.5rem;
        }
        .breadcrumb {
            background: transparent;
            margin: 0;
        }
        .breadcrumb-item a {
            color: #2a5298;
        }
        .breadcrumb-item.active {
            color: #64748b;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #1e3c72;
        }
        .card-primary:not(.card-outline) > .card-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }
        .card-primary.card-outline {
            border-top: 3px solid #1e3c72;
        }
        .card-info.card-outline {
            border-top: 3px solid #0ea5e9;
        }
        .card-success.card-outline {
            border-top: 3px solid #10b981;
        }
        .card-warning.card-outline {
            border-top: 3px solid #f59e0b;
        }
        .card-danger.card-outline {
            border-top: 3px solid #ef4444;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            box-shadow: 0 2px 8px rgba(30,60,114,0.3);
        }
        .btn-primary:hover {
            background: #2a5298;
            box-shadow: 0 4px 12px rgba(30,60,114,0.4);
        }
        .btn-success {
            background: #10b981;
            border: none;
        }
        .btn-info {
            background: #0ea5e9;
            border: none;
        }
        .btn-danger {
            background: #ef4444;
            border: none;
        }
        .btn-warning {
            background: #f59e0b;
            border: none;
            color: white;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .badge-success {
            background: #10b981;
        }
        .badge-info {
            background: #0ea5e9;
        }
        .badge-warning {
            background: #f59e0b;
            color: white;
        }
        .badge-danger {
            background: #ef4444;
        }
        
        .table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-hover tbody tr:hover {
            background: #f0f9ff;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
        }
        
        .small-box {
            border-radius: 12px;
            overflow: hidden;
        }
        .small-box.bg-info {
            background: #17a2b8 !important;
        }
        .small-box.bg-success {
            background: #28a745 !important;
        }
        .small-box.bg-warning {
            background: #ffc107 !important;
        }
        .small-box.bg-danger {
            background: #dc3545 !important;
        }
        .small-box.bg-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
        }
        
        .info-box {
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .info-box-icon {
            border-radius: 12px 0 0 12px;
        }
        .info-box-icon.bg-info {
            background: #17a2b8 !important;
        }
        .info-box-icon.bg-success {
            background: #28a745 !important;
        }
        .info-box-icon.bg-warning {
            background: #ffc107 !important;
        }
        .info-box-icon.bg-danger {
            background: #dc3545 !important;
        }
        .info-box-icon.bg-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
        }
        
        .main-footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .nav-pills .nav-link {
            color: #475569;
            border-radius: 8px;
            margin-right: 5px;
        }
        .nav-pills .nav-link:hover:not(.active) {
            background: #f0f4f8;
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
        
        .page-link {
            border: none;
            color: #1e3c72;
            border-radius: 6px;
            margin: 0 2px;
        }
        .page-item.active .page-link {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
        }
        
        .user-panel .info a {
            color: #475569 !important;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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
        /* ===== MOBILE NAVBAR FIXES ===== */
        @media (max-width: 575px) {
            /* Shrink SMS badge on mobile */
            .sms-balance-badge {
                padding: 4px 8px !important;
                gap: 4px !important;
                font-size: 11px !important;
            }
            /* New ticket button smaller */
            .main-header .nav-item .btn,
            .main-header .nav-item button[data-target="#globalCreateTicketModal"] {
                padding: 4px 8px !important;
            }
            /* Reduce padding on nav-links */
            .main-header .nav-link {
                padding: 8px 6px !important;
            }
            /* SMS nav-item margin */
            .main-header .navbar-nav.ml-auto .nav-item.mr-2 {
                margin-right: 4px !important;
            }
        }
        /* ===== USER DROPDOWN DESKTOP + SEARCH DROPDOWN FIX ===== */
        .main-header.navbar,
        .main-header .navbar {
            overflow: visible !important;
        }
        /* ensure search results show above ticker and content */
        .global-search-container {
            overflow: visible !important;
            position: relative;
        }
        #searchResults {
            z-index: 1060 !important;
        }
        /* ===== MOBILE USER MENU OVERLAY ===== */
        #mobileUserMenu {
            display: none;
            position: fixed;
            top: calc(var(--nav-h, 57px) + var(--at-h, 0px));
            right: 10px;
            width: 220px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            z-index: 99999;
            overflow: hidden;
        }
        #mobileUserMenu.show { display: block; }
        #mobileUserMenuBackdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99998;
            background: rgba(0,0,0,0.01);
        }
        #mobileUserMenuBackdrop.show { display: block; }
        .mobile-user-menu-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            padding: 14px 16px;
            color: white;
        }
        .mobile-user-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }
        .mobile-user-menu-item:hover { background: #f8f9fa; }
        .mobile-user-menu-item.logout-btn { color: #e53e3e; }
        /* Fix sidebar scroll jump on menu click */
        .main-sidebar .sidebar { overflow-anchor: none !important; }
        .nav-sidebar .nav-treeview { overflow: hidden; }
    
        /* Layout positioning (margin-top) handled by applyAdminLayout() JS below */
        /* Sticky footer fix: content-wrapper height natural, .wrapper grows */
        body.layout-fixed .content-wrapper {
            min-height: 0 !important;
        }
        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content-wrapper {
            flex: 1 1 auto !important;
        }
</style>
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css?v=5.2') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/ui-lite.css') . '?v=' . time() }}">

<style>
        /* ===== THEME OVERRIDE: catch remaining hardcoded primary colors ===== */
        [style*="background: linear-gradient(135deg, #1e3c72"],
        [style*="background:linear-gradient(135deg,#1e3c72"],
        [style*="background: #1e3c72"],
        [style*="background:#1e3c72"] {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
        }
        [style*="background: #3050a0"],
        [style*="background:#3050a0"] {
            background: #1e3c72 !important;
        }
        [style*="color: #1e3c72"],
        [style*="color:#1e3c72"],
        [style*="color: #2a5298"],
        [style*="color:#2a5298"],
        [style*="color: #3050a0"],
        [style*="color:#3050a0"] {
            color: #1e3c72 !important;
        }
        [style*="border-bottom: 2px solid #1e3c72"],
        [style*="border: 2px solid #1e3c72"],
        [style*="border-top: 3px solid #1e3c72"] {
            border-color: #1e3c72 !important;
        }
        /* table header theming */
        .table thead th, thead.bg-primary th, thead.bg-dark th,
        .table > thead > tr > th {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            color: #fff !important;
            border-color: rgba(255,255,255,0.2) !important;
        }

    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
@include('partials.impersonate-banner')
<div class="wrapper">
    @php
        $__noticeEnabled = \App\Models\SystemSetting::get('notice_enabled', '0');
        $__noticeText = \App\Models\SystemSetting::get('notice_text', '');
        $__noticeType = \App\Models\SystemSetting::get('notice_type', 'info');
        $__noticeSpeed = \App\Models\SystemSetting::get('notice_speed', 'normal');
        $__noticeMessages = array_values(array_filter(array_map('trim', explode("\n", $__noticeText ?? ''))));
    @endphp
    @if($__noticeEnabled == '1' && count($__noticeMessages) > 0)
        @include('partials.announcement-ticker', [
            'messages' => $__noticeMessages,
            'type' => $__noticeType,
            'speed' => $__noticeSpeed,
        ])
    @endif
    <nav class="main-header navbar navbar-expand navbar-dark">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('admin.dashboard') }}" class="nav-link"><i class="fas fa-home mr-1"></i> Home</a>
            </li>
        </ul>
        
        <div class="global-search-container mx-2" style="flex:1; min-width:80px; max-width:500px; position: relative;">
            <div class="input-group" style="border: none; box-shadow: none;">
                <div class="input-group-prepend">
                    <span class="input-group-text" style="background: rgba(255,255,255,0.15); border: none; color: rgba(255,255,255,0.8); outline: none; box-shadow: none;"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="globalQuickSearch" class="form-control" placeholder="Quick search... (ID, Name, Phone, Zone, Address)" style="background: rgba(255,255,255,0.15); border: none; color: white; border-radius: 0 25px 25px 0; outline: none; box-shadow: none;">
            </div>
            <div id="searchResults" class="search-results-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); z-index: 1050; max-height: 400px; overflow-y: auto; margin-top: 8px;"></div>
        </div>
        
        <ul class="navbar-nav ml-auto align-items-center">
            @if((auth()->user()->hasRole('Super Admin') || auth()->user()->user_type === 'super_admin') || \App\Services\MenuPermissionService::hasAnySubmenuAccess(auth()->user(), 'sms'))
            <li class="nav-item d-flex align-items-center mr-2">
                <a href="{{ route('admin.settings.sms') }}" class="sms-balance-badge" title="SMS Balance" style="display: inline-flex; align-items: center; gap: 6px; background: transparent; padding: 1px 6px; border-radius: 20px; color: #fff; text-decoration: none; font-size: 10px; white-space: nowrap; transition: background 0.2s;">
                    <i class="fas fa-comment-dots" style="font-size: 10px; color: #4fc3f7;"></i>
                    <span class="d-none d-md-inline" style="font-weight: 600;">SMS:</span>
                    <span id="navSmsBalance" style="font-weight: 700; color: #69f0ae;">--</span>
                    <button type="button" onclick="event.preventDefault(); event.stopPropagation(); refreshNavSmsBalance();" class="btn btn-link p-0 m-0 d-none d-md-inline" style="color: rgba(255,255,255,0.7); font-size: 11px; line-height: 1; border: none;" title="Refresh">
                        <i class="fas fa-sync-alt" id="navSmsSpinner" style="font-size: 10px;"></i>
                    </button>
                </a>
            </li>
            @endif
            <li class="nav-item d-flex align-items-center mr-2">
                <button type="button" data-toggle="modal" data-target="#globalCreateTicketModal"
                    title="Create Ticket"
                    style="display:inline-flex;align-items:center;gap:3px;background:transparent;padding:1px 6px;border-radius:20px;color:#fff;font-size:10px;border:none;cursor:pointer;white-space:nowrap;transition:background .2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.22)'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-ticket-alt" style="font-size:10px;color:#ffeaa7;"></i>
                    <span class="d-none d-sm-inline" style="font-weight:600;">New Ticket</span>
                </button>
            </li>
            <li class="nav-item d-flex align-items-center mr-2">
                <a href="{{ route('admin.notes.index') }}"
                    title="My Notes"
                    style="display:inline-flex;align-items:center;gap:3px;background:transparent;padding:1px 6px;border-radius:20px;color:#fff;font-size:10px;border:none;cursor:pointer;white-space:nowrap;transition:background .2s;text-decoration:none;"
                    onmouseover="this.style.background='rgba(255,255,255,0.22)'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-sticky-note" style="font-size:10px;color:#fdfd96;"></i>
                    <span class="d-none d-sm-inline" style="font-weight:600;">Note</span>
                </a>
            </li>
            <li class="nav-item d-none d-md-flex align-items-center">
                <a class="nav-link py-0" href="#" id="contextualHelpBtn" title="Help for this page" style="color: rgba(255,255,255,0.8);">
                    <i class="fas fa-question-circle"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-flex align-items-center">
                <a class="nav-link py-0" href="#" data-widget="fullscreen"><i class="fas fa-expand-arrows-alt"></i></a>
            </li>
            <li class="nav-item d-flex align-items-center">
                {{-- Desktop: normal dropdown --}}
                <div class="d-none d-md-block">
                    <div class="nav-item dropdown">
                        <a class="nav-link py-0" data-toggle="dropdown" href="#" data-display="static">
                            <i class="far fa-user-circle mr-1" style="font-size:18px;"></i>
                            <span>{{ auth()->user()->name }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" style="min-width:180px;border-radius:10px;overflow:hidden;">
                            <div class="dropdown-header text-center py-2" style="background:#f8f9fa;">
                                <div style="font-weight:700;color:#1e3c72;">{{ auth()->user()->name }}</div>
                                <small class="text-muted">{{ auth()->user()->user_type ?? 'Admin' }}</small>
                            </div>
                            <a href="{{ route('admin.profile') }}" class="dropdown-item py-2"><i class="fas fa-user mr-2 text-primary"></i> Profile</a>
                            <div class="dropdown-divider my-1"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item py-2"><i class="fas fa-sign-out-alt mr-2 text-danger"></i> Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- Mobile: icon that opens custom overlay --}}
                <button class="d-flex d-md-none nav-link py-0 btn btn-link border-0" onclick="toggleMobileUserMenu()" style="color:rgba(255,255,255,0.9);">
                    <i class="far fa-user-circle" style="font-size:22px;"></i>
                </button>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar elevation-4">
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <div class="brand-content">
                <div class="brand-logo-icon">
                    <img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="SOFTYNEX Logo">
                </div>
                <span class="brand-text">{{ $companyName ?? 'SOFTYNEX' }}</span>
                <span class="brand-tagline">{{ $companyTagline ?? 'Connecting You to the Future' }}</span>
            </div>
        </a>
        <div class="sidebar">
            @php
                try {
                    $firstDevice = \App\Models\MikrotikDevice::where('is_active', true)->first();
                } catch (\Throwable $e) {
                    $firstDevice = null;
                }
                $firstDeviceId = $firstDevice ? $firstDevice->id : 0;
                $__user = auth()->user();
                $__isSuperAdmin = false;
                if ($__user) {
                    try {
                        $__isSuperAdmin = $__user->hasRole('Super Admin') || $__user->user_type === 'super_admin';
                    } catch (\Throwable $e) {
                        $__isSuperAdmin = ($__user->user_type === 'super_admin');
                    }
                }
                $__hasMenu = function($key) use ($__isSuperAdmin, $__user) {
                    try {
                        if ($__isSuperAdmin) return true;
                        if (!$__user) return false;
                        $parts = explode('.', $key);
                        $menuKey = $parts[0];
                        $submenuKey = $parts[1] ?? null;
                        return \App\Services\MenuPermissionService::canAccessMenu($__user, $menuKey, $submenuKey);
                    } catch (\Throwable $e) {
                        return $__isSuperAdmin;
                    }
                };

                $__hasPageFeature = function($key) use ($__isSuperAdmin, $__user) {
                    try {
                        if ($__isSuperAdmin) return true;
                        if (!$__user) return true;
                        $company = \App\Models\Company::find($__user->company_id);
                        if (!$company) return true;
                        return $company->hasPageFeature($key);
                    } catch (\Throwable $e) {
                        return true;
                    }
                };
                $__hasAnyChild = function($parentKey) use ($__isSuperAdmin, $__user) {
                    try {
                        if ($__isSuperAdmin) return true;
                        if (!$__user) return false;
                        return \App\Services\MenuPermissionService::hasAnySubmenuAccess($__user, $parentKey);
                    } catch (\Throwable $e) {
                        return $__isSuperAdmin;
                    }
                };
            @endphp
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>DashBoard</p>
                        </a>
                    </li>
                    @if($__hasAnyChild('client'))
                    <li class="nav-item {{ request()->is('admin/clients*') || request()->is('admin/applications*') || request()->is('admin/client-recovery*') || request()->is('admin/client/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/clients*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Client<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('client.new_request'))<li class="nav-item"><a href="{{ route('admin.applications.index') }}" class="nav-link {{ request()->routeIs('admin.applications.*') ? 'active' : '' }}"><i class="fas fa-file-signature nav-icon"></i><p>New Request @if(($pendingApplicationsCount ?? 0) > 0)<span class="badge badge-danger">{{ $pendingApplicationsCount ?? 0 }}</span>@endif</p></a></li>@endif
                            @if($__hasMenu('client.add_new'))<li class="nav-item"><a href="{{ route('admin.clients.create') }}" class="nav-link {{ request()->routeIs('admin.clients.create') ? 'active' : '' }}"><i class="fas fa-user-plus nav-icon"></i><p>Add New</p></a></li>@endif
                            @if($__hasMenu('client.client_list'))<li class="nav-item"><a href="{{ route('admin.clients.index') }}" class="nav-link {{ request()->routeIs('admin.clients.index') ? 'active' : '' }}"><i class="fas fa-list nav-icon"></i><p>Client List</p></a></li>@endif
                            @if($__hasMenu('client.change_request'))<li class="nav-item"><a href="{{ route('admin.clients.package-requests') }}" class="nav-link {{ request()->routeIs('admin.clients.package-requests') ? 'active' : '' }}"><i class="fas fa-exchange-alt nav-icon"></i><p>Change Request</p></a></li>@endif
                            @if($__hasMenu('client.scheduler'))<li class="nav-item"><a href="{{ route('admin.client.scheduler') }}" class="nav-link {{ request()->routeIs('admin.client.scheduler') ? 'active' : '' }}"><i class="fas fa-calendar-check nav-icon"></i><p>Scheduler</p></a></li>@endif
                            @if($__hasMenu('client.portal_manage'))<li class="nav-item"><a href="{{ route('admin.client.portal') }}" class="nav-link {{ request()->routeIs('admin.client.portal') ? 'active' : '' }}"><i class="fas fa-door-open nav-icon"></i><p>Portal Manage</p></a></li>@endif
                            @if($__hasMenu('client.left_client'))<li class="nav-item"><a href="{{ route('admin.clients.inactive') }}" class="nav-link {{ request()->routeIs('admin.clients.inactive') ? 'active' : '' }}"><i class="fas fa-user-minus nav-icon"></i><p>Left Client</p></a></li>@endif
                            @if($__hasMenu('client.client_restore'))<li class="nav-item"><a href="{{ route('admin.client-recovery.index') }}" class="nav-link {{ request()->is('admin/client-recovery*') ? 'active' : '' }}"><i class="fas fa-user-shield nav-icon"></i><p>Client Restore</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('billing'))
                    <li class="nav-item {{ request()->is('admin/billing*') || request()->is('admin/staff-dues*') || request()->is('admin/corrections*') || request()->is('billing/automation*') || request()->is('admin/my-collections*') || request()->is('admin/payments*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/billing*') || request()->is('admin/staff-dues*') || request()->is('admin/corrections*') || request()->is('billing/automation*') || request()->is('admin/my-collections*') || request()->is('admin/payments*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-check-alt"></i>
                            <p>Billing<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('billing.generate'))<li class="nav-item"><a href="{{ route('admin.billing.generate') }}" class="nav-link {{ request()->routeIs('admin.billing.generate') ? 'active' : '' }}"><i class="fas fa-file-medical nav-icon"></i><p>Generate Bills</p></a></li>@endif
                            @if($__hasMenu('billing.bill_list'))<li class="nav-item"><a href="{{ route('admin.billing.index') }}" class="nav-link {{ request()->routeIs('admin.billing.index') ? 'active' : '' }}"><i class="fas fa-list-ol nav-icon"></i><p>Bill List</p></a></li>@endif
                            @if($__hasMenu('billing.due'))<li class="nav-item"><a href="{{ route('admin.billing.due') }}" class="nav-link {{ request()->routeIs('admin.billing.due') ? 'active' : '' }}"><i class="fas fa-exclamation-circle nav-icon"></i><p>Due Bills</p></a></li>@endif
                            @if($__hasMenu('billing.payments'))<li class="nav-item"><a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.index') ? 'active' : '' }}"><i class="fas fa-money-bill nav-icon"></i><p>Payments</p></a></li>@endif
                            @if($__hasMenu('billing.advance_collection'))<li class="nav-item"><a href="{{ route('admin.billing.advance-collection') }}" class="nav-link {{ request()->routeIs('admin.billing.advance-collection') ? 'active' : '' }}"><i class="fas fa-forward nav-icon"></i><p>Advance Collection</p></a></li>@endif
                            @if($__hasMenu('billing.staff_dues'))<li class="nav-item"><a href="{{ route('admin.staff-dues.index') }}" class="nav-link {{ request()->is('admin/staff-dues*') ? 'active' : '' }}"><i class="fas fa-hand-holding-usd nav-icon"></i><p>Staff Dues</p></a></li>@endif
                            @if(auth()->check() && \App\Models\StaffDue::where('user_id', auth()->id())->where('total_collected', '>', 0)->exists())<li class="nav-item"><a href="{{ route('admin.my-collections.index') }}" class="nav-link {{ request()->routeIs('admin.my-collections*') ? 'active' : '' }}"><i class="fas fa-wallet nav-icon text-success"></i><p>My Collections</p></a></li>@endif
                            @if($__hasMenu('billing.collection_transfer'))<li class="nav-item"><a href="{{ route('admin.billing.collection-transfers') }}" class="nav-link {{ request()->routeIs('admin.billing.collection-transfers*') ? 'active' : '' }}"><i class="fas fa-exchange-alt nav-icon"></i><p>Collection Transfers</p></a></li>@endif
                            @if($__hasMenu('billing.daily_collections'))<li class="nav-item"><a href="{{ route('admin.billing.daily-collections') }}" class="nav-link {{ request()->is('admin/billing/daily-collections*') ? 'active' : '' }}"><i class="fas fa-money-bill-wave nav-icon"></i><p>Daily Collections</p></a></li>@endif
                            @if($__hasMenu('billing.collection_report'))<li class="nav-item"><a href="{{ route('admin.billing.collection-report') }}" class="nav-link {{ request()->is('admin/billing/collection-report*') ? 'active' : '' }}"><i class="fas fa-chart-line nav-icon"></i><p>Collection Report</p></a></li>@endif
                            @if($__hasMenu('billing.corrections'))<li class="nav-item"><a href="{{ route('admin.corrections.index') }}" class="nav-link {{ request()->is('admin/corrections*') ? 'active' : '' }}"><i class="fas fa-edit nav-icon"></i><p>Bill/Payment Corrections</p></a></li>@endif
                            @if($__hasMenu('billing.automation'))<li class="nav-item"><a href="{{ route('billing.automation.settings') }}" class="nav-link {{ request()->routeIs('billing.automation.settings') ? 'active' : '' }}"><i class="nav-icon fas fa-cogs"></i><p>Automation Settings</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('accounts'))
                    <li class="nav-item {{ request()->is('admin/accounts*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/accounts*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-balance-scale"></i>
                            <p>Accounts<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('accounts.cash'))<li class="nav-item"><a href="{{ route('admin.accounts.cash') }}" class="nav-link {{ request()->routeIs('admin.accounts.cash') ? 'active' : '' }}"><i class="fas fa-money-bill-alt nav-icon"></i><p>Cash In Hand</p></a></li>@endif
                            @if($__hasMenu('accounts.banks'))<li class="nav-item"><a href="{{ route('admin.accounts.banks') }}" class="nav-link {{ request()->routeIs('admin.accounts.banks') ? 'active' : '' }}"><i class="fas fa-university nav-icon"></i><p>Bank Accounts</p></a></li>@endif
                            @if($__hasMenu('accounts.income'))<li class="nav-item"><a href="{{ route('admin.accounts.income') }}" class="nav-link {{ request()->routeIs('admin.accounts.income') ? 'active' : '' }}"><i class="fas fa-arrow-circle-down nav-icon"></i><p>Income</p></a></li>@endif
                            @if($__hasMenu('accounts.expense'))<li class="nav-item"><a href="{{ route('admin.accounts.expense') }}" class="nav-link {{ request()->routeIs('admin.accounts.expense') ? 'active' : '' }}"><i class="fas fa-arrow-circle-up nav-icon"></i><p>Expense</p></a></li>@endif
                            @if($__hasMenu('accounts.loan'))<li class="nav-item"><a href="{{ route('admin.accounts.loan') }}" class="nav-link {{ request()->is('admin/accounts/loan*') ? 'active' : '' }}"><i class="fas fa-hand-holding-usd nav-icon"></i><p>Loan Management</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('reports'))
                    <li class="nav-item {{ (request()->is('admin/reports*') || request()->is('admin/btrc*')) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ (request()->is('admin/reports*') || request()->is('admin/btrc*')) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p>Report<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('reports.income_statement'))<li class="nav-item"><a href="{{ route('admin.reports.income-statement') }}" class="nav-link {{ request()->routeIs('admin.reports.income-statement') ? 'active' : '' }}"><i class="fas fa-file-invoice-dollar nav-icon"></i><p>Income Statement</p></a></li>@endif
                            @if($__hasMenu('reports.profit_loss'))<li class="nav-item"><a href="{{ route('admin.reports.profit-loss') }}" class="nav-link"><i class="fas fa-file-alt nav-icon"></i><p>Profit & Loss</p></a></li>@endif
                            @if($__hasMenu('reports.daily'))<li class="nav-item"><a href="{{ route('admin.reports.daily') }}" class="nav-link {{ request()->routeIs('admin.reports.daily') ? 'active' : '' }}"><i class="fas fa-calendar-day nav-icon"></i><p>Daily Collection</p></a></li>@endif
                            @if($__hasMenu('reports.monthly'))<li class="nav-item"><a href="{{ route('admin.reports.monthly') }}" class="nav-link {{ request()->routeIs('admin.reports.monthly') ? 'active' : '' }}"><i class="fas fa-calendar nav-icon"></i><p>Monthly Revenue</p></a></li>@endif
                            @if($__hasMenu('reports.due'))<li class="nav-item"><a href="{{ route('admin.reports.due') }}" class="nav-link {{ request()->routeIs('admin.reports.due') ? 'active' : '' }}"><i class="fas fa-exclamation-triangle nav-icon"></i><p>Due Report</p></a></li>@endif
                            @if($__hasMenu('reports.client_ledger'))<li class="nav-item"><a href="{{ route('admin.reports.client-ledger') }}" class="nav-link {{ request()->routeIs('admin.reports.client-ledger') ? 'active' : '' }}"><i class="fas fa-user nav-icon"></i><p>Client Ledger</p></a></li>@endif
                            @if($__hasMenu('reports.zone'))<li class="nav-item"><a href="{{ route('admin.reports.zone') }}" class="nav-link {{ request()->routeIs('admin.reports.zone') ? 'active' : '' }}"><i class="fas fa-chart-area nav-icon"></i><p>Zone Report</p></a></li>@endif
                            @if($__hasMenu('reports.employee_collection'))<li class="nav-item"><a href="{{ route('admin.reports.employee-collection') }}" class="nav-link {{ request()->routeIs('admin.reports.employee-collection') ? 'active' : '' }}"><i class="fas fa-file-invoice nav-icon"></i><p>Employee Collection</p></a></li>@endif
                            @if($__hasMenu('reports.bandwidth'))<li class="nav-item"><a href="{{ route('admin.reports.bandwidth') }}" class="nav-link {{ request()->routeIs('admin.reports.bandwidth*') ? 'active' : '' }}"><i class="fas fa-chart-area nav-icon"></i><p>Bandwidth Report</p></a></li>@endif
                            @if($__hasMenu('reports.btrc'))<li class="nav-item"><a href="{{ route('admin.btrc.index') }}" class="nav-link {{ request()->is('admin/btrc*') ? 'active' : '' }}"><i class="fas fa-landmark nav-icon"></i><p>BTRC Report</p></a></li>@endif
                            @if($__hasMenu('reports.stock'))<li class="nav-item"><a href="{{ route('admin.reports.stock') }}" class="nav-link {{ request()->routeIs('admin.reports.stock') ? 'active' : '' }}"><i class="fas fa-cubes nav-icon"></i><p>Stock Report</p></a></li>@endif
                            @if($__hasMenu('reports.inventory_stock'))<li class="nav-item"><a href="{{ route('admin.store.stock-report') }}" class="nav-link {{ request()->routeIs('admin.store.stock-report') ? 'active' : '' }}"><i class="fas fa-warehouse nav-icon"></i><p>Inventory Stock Report</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('olt'))
                    <li class="nav-item {{ (request()->is('admin/network/olt*') || request()->is('admin/olt*')) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ (request()->is('admin/network/olt*') || request()->is('admin/olt*')) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-microchip"></i>
                            <p>OLT Management<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('olt.devices'))<li class="nav-item"><a href="{{ route('admin.olt.index') }}" class="nav-link {{ request()->routeIs('admin.olt.index') ? 'active' : '' }}"><i class="fas fa-hdd nav-icon"></i><p>OLT Devices</p></a></li>@endif
                            @if($__hasMenu('olt.onu_status'))<li class="nav-item"><a href="{{ route('admin.olt.onu-status') }}" class="nav-link {{ (request()->is('admin/network/olt/*/onus*') || request()->routeIs('admin.olt.onu-status')) ? 'active' : '' }}"><i class="fas fa-signal nav-icon"></i><p>ONU Status</p></a></li>@endif
                            @if($__hasMenu('olt.users'))<li class="nav-item"><a href="{{ route('admin.olt.users') }}" class="nav-link {{ request()->routeIs('admin.olt.users') ? 'active' : '' }}"><i class="fas fa-user-cog nav-icon"></i><p>OLT Users</p></a></li>@endif
                            @if($__hasMenu('olt.onu_list'))<li class="nav-item"><a href="{{ route('admin.olt.onu-list') }}" class="nav-link {{ request()->routeIs('admin.olt.onu-list') ? 'active' : '' }}"><i class="fas fa-stream nav-icon"></i><p>ONU List</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.olt.optical-loss-calculator') }}" class="nav-link {{ request()->routeIs('admin.olt.optical-loss-calculator') ? 'active' : '' }}"><i class="fas fa-calculator nav-icon text-warning"></i><p>Loss Calculator</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.olt.onu-mismatches') }}" class="nav-link {{ request()->routeIs('admin.olt.onu-mismatches') ? 'active' : '' }}"><i class="fas fa-exclamation-triangle nav-icon" style="color:#f39c12"></i><p>ONU Mismatches</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('mikrotik'))
                    <li class="nav-item {{ request()->is('admin/network/firewall*') || request()->is('admin/network/nat*') || request()->is('admin/network/bulk*') || request()->is('admin/multi-router*') || request()->is('admin/network/connectivity-test*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-ethernet"></i>
                            <p>MikroTik Router<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('mikrotik.router_list'))<li class="nav-item"><a href="{{ route('admin.multi-router.index') }}" class="nav-link {{ request()->routeIs('admin.multi-router.*') ? 'active' : '' }}"><i class="fas fa-server nav-icon"></i><p>Router List</p></a></li>@endif
                            @if($__hasMenu('mikrotik.connectivity_test'))<li class="nav-item"><a href="{{ route('admin.network.connectivity-test') }}" class="nav-link {{ request()->routeIs('admin.network.connectivity-test') ? 'active' : '' }}"><i class="fas fa-stethoscope nav-icon"></i><p>Connectivity Test</p></a></li>@endif
                            @if($__hasMenu('mikrotik.firewall'))<li class="nav-item"><a href="{{ $firstDeviceId ? route('admin.network.firewall', ['device' => $firstDeviceId]) : route('admin.multi-router.index') }}" class="nav-link {{ request()->routeIs('admin.network.firewall') ? 'active' : '' }}"><i class="fas fa-shield-alt nav-icon"></i><p>Firewall Rules</p></a></li>@endif
                            @if($__hasMenu('mikrotik.nat'))<li class="nav-item"><a href="{{ $firstDeviceId ? route('admin.network.nat', ['device' => $firstDeviceId]) : route('admin.multi-router.index') }}" class="nav-link {{ request()->routeIs('admin.network.nat') ? 'active' : '' }}"><i class="fas fa-random nav-icon"></i><p>NAT Rules</p></a></li>@endif
                            @if($__hasMenu('mikrotik.bulk_operations'))<li class="nav-item"><a href="{{ route('admin.network.bulk-operations') }}" class="nav-link {{ request()->routeIs('admin.network.bulk-operations') ? 'active' : '' }}"><i class="fas fa-tasks nav-icon"></i><p>Bulk Operations</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('network'))
                    <li class="nav-item {{ (request()->is('admin/network*') && !request()->is('admin/network/firewall*') && !request()->is('admin/network/nat*') && !request()->is('admin/network/bulk*') && !request()->is('admin/network/connectivity-test*') && !request()->is('admin/network/diagram*') && !request()->is('admin/network/client-diagram*') && !request()->is('admin/network/map*') && !request()->is('admin/network/topology*') && !request()->is('admin/multi-router*') && !request()->is('admin/network/olt*')) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ (request()->is('admin/network*') && !request()->is('admin/network/firewall*') && !request()->is('admin/network/nat*') && !request()->is('admin/network/bulk*') && !request()->is('admin/network/connectivity-test*') && !request()->is('admin/network/diagram*') && !request()->is('admin/network/client-diagram*') && !request()->is('admin/network/map*') && !request()->is('admin/network/topology*') && !request()->is('admin/multi-router*') && !request()->is('admin/network/olt*')) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-sitemap"></i>
                            <p>Network & Devices<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('network.ip_pools'))<li class="nav-item"><a href="{{ route('admin.network.ip-pools') }}" class="nav-link {{ request()->routeIs('admin.network.ip-pools') ? 'active' : '' }}"><i class="fas fa-globe nav-icon"></i><p>IP Pool Management</p></a></li>@endif
                            @if($__hasMenu('network.pop'))<li class="nav-item"><a href="{{ route('admin.network.devices') }}" class="nav-link {{ request()->routeIs('admin.network.devices*') ? 'active' : '' }}"><i class="fas fa-broadcast-tower nav-icon"></i><p>Network POP</p></a></li>@endif
                            @if($__hasMenu('network.splitters'))<li class="nav-item"><a href="{{ route('admin.network.splitters') }}" class="nav-link {{ request()->routeIs('admin.network.splitters*') ? 'active' : '' }}"><i class="fas fa-sitemap nav-icon"></i><p>Splitter Management</p></a></li>@endif
                            @if($__hasMenu('network.mac_binding'))<li class="nav-item"><a href="{{ route('admin.network.mac-bindings') }}" class="nav-link {{ request()->routeIs('admin.network.mac-bindings') ? 'active' : '' }}"><i class="fas fa-fingerprint nav-icon"></i><p>MAC Binding</p></a></li>@endif
                            @if($__hasMenu('network.radius_settings'))<li class="nav-item"><a href="{{ route('admin.network.radius-settings') }}" class="nav-link {{ request()->routeIs('admin.network.radius-settings') ? 'active' : '' }}"><i class="fas fa-broadcast-tower nav-icon"></i><p>RADIUS Settings</p></a></li>@endif
                            @if($__hasMenu('network.connections'))<li class="nav-item"><a href="{{ route('admin.network.connections') }}" class="nav-link {{ request()->routeIs('admin.network.connections') ? 'active' : '' }}"><i class="fas fa-link nav-icon"></i><p>Network Connections</p></a></li>@endif
                            @if($__hasMenu('network.distributed_items'))<li class="nav-item"><a href="{{ route('admin.network.distributed-items') }}" class="nav-link {{ request()->routeIs('admin.network.distributed-items') ? 'active' : '' }}"><i class="fas fa-truck nav-icon"></i><p>Distributed Inv. Items</p></a></li>@endif
                            @if($__hasMenu('network.two_way_sync'))<li class="nav-item"><a href="{{ route('admin.mikrotik-sync.index') }}" class="nav-link {{ request()->routeIs('admin.mikrotik-sync.*') ? 'active' : '' }}"><i class="fas fa-sync-alt nav-icon"></i><p>Two-Way Sync</p></a></li>@endif
                            @if($__hasMenu('network.device_logs'))<li class="nav-item"><a href="{{ route('admin.network.device-logs') }}" class="nav-link {{ request()->routeIs('admin.network.device-logs') ? 'active' : '' }}"><i class="fas fa-clipboard-list nav-icon"></i><p>Device Logs</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('diagram'))
                    <li class="nav-item {{ request()->is('admin/network/diagram*') || request()->is('admin/network/client-diagram*') || request()->is('admin/network/map*') || request()->is('admin/network/topology*') || request()->is('admin/clients/map*') || request()->is('admin/hrm/selfie-attendance-map*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/network/diagram*') || request()->is('admin/network/client-diagram*') || request()->is('admin/network/map*') || request()->is('admin/network/topology*') || request()->is('admin/clients/map*') || request()->is('admin/hrm/selfie-attendance-map*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-map-marked-alt"></i>
                            <p>Diagram & Maps<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('diagram.network_diagram'))<li class="nav-item"><a href="{{ route('admin.network.diagram') }}" class="nav-link {{ request()->routeIs('admin.network.diagram') ? 'active' : '' }}"><i class="fas fa-project-diagram nav-icon"></i><p>Network Diagram</p></a></li>@endif
                            @if($__hasMenu('diagram.client_diagram'))<li class="nav-item"><a href="{{ route('admin.network.client-diagram') }}" class="nav-link {{ request()->routeIs('admin.network.client-diagram') ? 'active' : '' }}"><i class="fas fa-users nav-icon"></i><p>Client Diagram</p></a></li>@endif
                            @if($__hasMenu('diagram.network_map'))<li class="nav-item"><a href="{{ route('admin.network.map') }}" class="nav-link {{ request()->routeIs('admin.network.map') ? 'active' : '' }}"><i class="fas fa-map nav-icon"></i><p>Network Map</p></a></li>@endif
                            @if($__hasMenu('diagram.network_topology'))<li class="nav-item"><a href="{{ route('admin.network.topology') }}" class="nav-link {{ request()->routeIs('admin.network.topology*') ? 'active' : '' }}"><i class="fas fa-sitemap nav-icon"></i><p>Network Topology</p></a></li>@endif
                            @if($__hasMenu('diagram.client_map'))<li class="nav-item"><a href="{{ route('admin.clients.map') }}" class="nav-link {{ request()->routeIs('admin.clients.map') ? 'active' : '' }}"><i class="fas fa-map-marker-alt nav-icon"></i><p>Client Map</p></a></li>@endif
                            @if($__hasMenu('diagram.selfie_map'))<li class="nav-item"><a href="{{ route('admin.hrm.selfie-attendance.map') }}" class="nav-link {{ request()->routeIs('admin.hrm.selfie-attendance.map') ? 'active' : '' }}"><i class="fas fa-camera-retro nav-icon"></i><p>Selfie Attendance Map</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @php
                        $__routeReseller = request()->route('reseller');
                        $__isBwPage = (request()->get('type') === 'bandwidth')
                            || request()->routeIs('admin.reseller.bandwidth*')
                            || request()->routeIs('admin.reseller.bw-funding')
                            || request()->routeIs('admin.reseller.bw-funding.store')
                            || request()->routeIs('admin.reseller.bw-collection-report')
                            || ($__routeReseller && is_object($__routeReseller) && isset($__routeReseller->type) && $__routeReseller->type === 'bandwidth');
                    @endphp
                    @if($__hasAnyChild('reseller'))
                    <li class="nav-item {{ (request()->is('admin/reseller*') && !$__isBwPage) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ (request()->is('admin/reseller*') && !$__isBwPage) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-handshake"></i>
                            <p>MAC Reseller<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('mac_reseller.list'))<li class="nav-item"><a href="{{ route('admin.reseller.index', ['type'=>'mac']) }}" class="nav-link {{ (request()->is('admin/reseller*') && !$__isBwPage) ? 'active' : '' }}"><i class="fas fa-network-wired nav-icon"></i><p>MAC Reseller</p></a></li>@endif
                            @if($__hasMenu('mac_reseller.clients'))<li class="nav-item"><a href="{{ route('admin.reseller.clients') }}" class="nav-link {{ request()->routeIs('admin.reseller.clients') ? 'active' : '' }}"><i class="fas fa-users nav-icon"></i><p>MAC Reseller Clients</p></a></li>@endif
                            @if($__hasMenu('mac_reseller.funding'))<li class="nav-item"><a href="{{ route('admin.reseller.funding') }}" class="nav-link {{ request()->routeIs('admin.reseller.funding') ? 'active' : '' }}"><i class="fas fa-wallet nav-icon"></i><p>MAC Reseller Billing</p></a></li>@endif
                            @if($__hasMenu('mac_reseller.collection_report'))<li class="nav-item"><a href="{{ route('admin.reseller.collection-report') }}" class="nav-link {{ request()->routeIs('admin.reseller.collection-report') ? 'active' : '' }}"><i class="fas fa-chart-bar nav-icon"></i><p>Collection Report</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasMenu('bw_reseller.list') || $__hasMenu('bw_reseller.bw_categories'))
                    <li class="nav-item {{ $__isBwPage || request()->is('admin/bandwidth-categories*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $__isBwPage || request()->is('admin/bandwidth-categories*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Bandwidth Reseller<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('bw_reseller.list'))<li class="nav-item"><a href="{{ route('admin.reseller.index', ['type'=>'bandwidth']) }}" class="nav-link {{ $__isBwPage ? 'active' : '' }}"><i class="fas fa-tachometer-alt nav-icon"></i><p>Bandwidth Reseller</p></a></li>@endif
                            @if($__hasMenu('bw_reseller.bw_categories'))<li class="nav-item"><a href="{{ route('admin.bandwidth-categories.index') }}" class="nav-link {{ request()->is('admin/bandwidth-categories*') ? 'active' : '' }}"><i class="fas fa-layer-group nav-icon"></i><p>Bandwidth Categories</p></a></li>@endif
                            @if($__hasMenu('bw_reseller.bw_funding'))<li class="nav-item"><a href="{{ route('admin.reseller.bw-funding') }}" class="nav-link {{ request()->routeIs('admin.reseller.bw-funding') ? 'active' : '' }}"><i class="fas fa-file-invoice-dollar nav-icon"></i><p>Bandwidth Billing</p></a></li>@endif
                            @if($__hasMenu('bw_reseller.bw_collection_report'))<li class="nav-item"><a href="{{ route('admin.reseller.bw-collection-report') }}" class="nav-link {{ request()->routeIs('admin.reseller.bw-collection-report') ? 'active' : '' }}"><i class="fas fa-chart-bar nav-icon"></i><p>Collection Report</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('agent'))
                    <li class="nav-item {{ request()->is('admin/agent*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/agent*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-tie"></i>
                            <p>Referral Partner<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('agent.add'))<li class="nav-item"><a href="{{ route('admin.agent.create') }}" class="nav-link {{ request()->routeIs('admin.agent.create') ? 'active' : '' }}"><i class="fas fa-plus-circle nav-icon"></i><p>Add Referral Partner</p></a></li>@endif
                            @if($__hasMenu('agent.list'))<li class="nav-item"><a href="{{ route('admin.agent.index') }}" class="nav-link {{ request()->routeIs('admin.agent.index') ? 'active' : '' }}"><i class="fas fa-list nav-icon"></i><p>Referral Partner List</p></a></li>@endif
                            @if($__hasMenu('agent.ledgers'))<li class="nav-item"><a href="{{ route('admin.agent.ledgers') }}" class="nav-link {{ request()->routeIs('admin.agent.ledgers') ? 'active' : '' }}"><i class="fas fa-book nav-icon"></i><p>Referral Partner Ledger</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('events'))
                    <li class="nav-item {{ request()->is('admin/events*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/events*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-glass-cheers"></i>
                            <p>Events & Holidays<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('events.list'))<li class="nav-item"><a href="{{ route('admin.events.index') }}" class="nav-link {{ request()->routeIs('admin.events.index') ? 'active' : '' }}"><i class="fas fa-calendar nav-icon"></i><p>Events List</p></a></li>@endif
                            @if($__hasMenu('events.holidays'))<li class="nav-item"><a href="{{ route('admin.events.holidays') }}" class="nav-link {{ request()->routeIs('admin.events.holidays') ? 'active' : '' }}"><i class="fas fa-umbrella-beach nav-icon"></i><p>Holidays</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('branches'))
                    <li class="nav-item {{ request()->is('admin/branches*') || request()->is('admin/pops*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-building"></i>
                            <p>Branch & POP<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('branches.list'))<li class="nav-item"><a href="{{ route('admin.branches.index') }}" class="nav-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}"><i class="fas fa-code-branch nav-icon"></i><p>Branches</p></a></li>@endif
                            @if($__hasMenu('branches.pops'))<li class="nav-item"><a href="{{ route('admin.pops.index') }}" class="nav-link {{ request()->routeIs('admin.pops.*') ? 'active' : '' }}"><i class="fas fa-broadcast-tower nav-icon"></i><p>POPs</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('support'))
                    <li class="nav-item {{ request()->is('admin/support*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/support*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-headset"></i>
                            <p>Support & Ticketing<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('support.tickets'))<li class="nav-item"><a href="{{ route('admin.support.tickets') }}" class="nav-link {{ request()->routeIs('admin.support.tickets') ? 'active' : '' }}"><i class="fas fa-ticket-alt nav-icon"></i><p>All Tickets</p></a></li>@endif
                            @if($__hasMenu('support.categories'))<li class="nav-item"><a href="{{ route('admin.support.categories') }}" class="nav-link {{ request()->routeIs('admin.support.categories') ? 'active' : '' }}"><i class="fas fa-tags nav-icon"></i><p>Ticket Categories</p></a></li>@endif
                            @if($__hasMenu('support.sla'))<li class="nav-item"><a href="{{ route('admin.support.sla-config') }}" class="nav-link {{ request()->routeIs('admin.support.sla-config') ? 'active' : '' }}"><i class="fas fa-stopwatch nav-icon"></i><p>SLA Configuration</p></a></li>@endif
                            @if($__hasMenu('support.technician_efficiency'))<li class="nav-item"><a href="{{ route('admin.support.technician-efficiency') }}" class="nav-link {{ request()->routeIs('admin.support.technician-efficiency') ? 'active' : '' }}"><i class="fas fa-chart-line nav-icon"></i><p>Technician Efficiency</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('tasks'))
                    <li class="nav-item {{ request()->is('admin/tasks*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/tasks*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-clipboard-list"></i>
                            <p>Task Management<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('tasks.create'))<li class="nav-item"><a href="{{ route('admin.tasks.create') }}" class="nav-link {{ request()->routeIs('admin.tasks.create') ? 'active' : '' }}"><i class="fas fa-plus-circle nav-icon"></i><p>Create Task</p></a></li>@endif
                            @if($__hasMenu('tasks.all'))<li class="nav-item"><a href="{{ route('admin.tasks.index') }}" class="nav-link {{ request()->routeIs('admin.tasks.index') ? 'active' : '' }}"><i class="fas fa-th-list nav-icon"></i><p>All Tasks</p></a></li>@endif
                            @if($__hasMenu('tasks.calendar'))<li class="nav-item"><a href="{{ route('admin.tasks.calendar') }}" class="nav-link {{ request()->routeIs('admin.tasks.calendar') ? 'active' : '' }}"><i class="fas fa-calendar-alt nav-icon"></i><p>Task Calendar</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('store'))
                    <li class="nav-item {{ request()->is('admin/store*') || request()->is('admin/purchase*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/store*') || request()->is('admin/purchase*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-store"></i>
                            <p>Store<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('store.vendors'))<li class="nav-item"><a href="{{ route('admin.purchase.vendors') }}" class="nav-link {{ request()->routeIs('admin.purchase.vendors') ? 'active' : '' }}"><i class="fas fa-truck nav-icon"></i><p>Vendors</p></a></li>@endif
                            @if($__hasMenu('store.categories'))<li class="nav-item"><a href="{{ route('admin.store.categories') }}" class="nav-link {{ request()->is('admin/store/categories*') ? 'active' : '' }}"><i class="fas fa-tags nav-icon"></i><p>Product Categories</p></a></li>@endif
                            @if($__hasMenu('store.products'))<li class="nav-item"><a href="{{ route('admin.store.products') }}" class="nav-link {{ request()->is('admin/store/products*') ? 'active' : '' }}"><i class="fas fa-boxes nav-icon"></i><p>Products</p></a></li>@endif
                            @if($__hasMenu('inventory.products'))<li class="nav-item"><a href="{{ route('admin.inventory.products.index') }}" class="nav-link"><i class="fas fa-boxes nav-icon"></i><p>Store Stock</p></a></li>@endif
                            @if($__hasMenu('store.add_purchase'))<li class="nav-item"><a href="{{ route('admin.purchase.create') }}" class="nav-link {{ request()->routeIs('admin.purchase.create') ? 'active' : '' }}"><i class="fas fa-cart-plus nav-icon"></i><p>Add Purchase</p></a></li>@endif
                            @if($__hasMenu('store.purchases'))<li class="nav-item"><a href="{{ route('admin.store.purchases') }}" class="nav-link {{ request()->routeIs('admin.store.purchases') ? 'active' : '' }}"><i class="fas fa-shopping-bag nav-icon"></i><p>Purchase List</p></a></li>@endif
                            @if($__hasMenu('reports.inventory_stock'))<li class="nav-item"><a href="{{ route('admin.store.stock-report') }}" class="nav-link {{ request()->routeIs('admin.store.stock-report') ? 'active' : '' }}"><i class="fas fa-chart-bar nav-icon"></i><p>Stock Report</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('sales'))
                    <li class="nav-item {{ request()->is('admin/sales*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/sales*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>Sales & Service<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('sales.create'))<li class="nav-item"><a href="{{ route('admin.sales.create') }}" class="nav-link {{ request()->routeIs('admin.sales.create') ? 'active' : '' }}"><i class="fas fa-cart-plus nav-icon"></i><p>New Sale</p></a></li>@endif
                            @if($__hasMenu('sales.list'))<li class="nav-item"><a href="{{ route('admin.sales.index') }}" class="nav-link {{ request()->routeIs('admin.sales.index') ? 'active' : '' }}"><i class="fas fa-receipt nav-icon"></i><p>Sales List</p></a></li>@endif
                            @if($__hasMenu('sales.installation_charges'))<li class="nav-item"><a href="{{ route('admin.sales.installation-charges') }}" class="nav-link {{ request()->is('admin/sales/installation-charges*') ? 'active' : '' }}"><i class="fas fa-plug nav-icon"></i><p>Installation Charges</p></a></li>@endif
                            @if($__hasMenu('sales.services'))<li class="nav-item"><a href="{{ route('admin.sales.services') }}" class="nav-link {{ request()->routeIs('admin.sales.services') ? 'active' : '' }}"><i class="fas fa-tools nav-icon"></i><p>Service Charges</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('inventory'))
                    <li class="nav-item has-treeview {{ request()->is('admin/inventory*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/inventory*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-warehouse"></i>
                            <p>Inventory<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('inventory.dashboard'))<li class="nav-item"><a href="{{ route('admin.inventory.stock.summary') }}" class="nav-link"><i class="fas fa-tachometer-alt nav-icon"></i><p>Dashboard</p></a></li>@endif
                            @if($__hasMenu('inventory.product_categories'))<li class="nav-item"><a href="{{ route('admin.inventory.instrument-categories.page') }}" class="nav-link"><i class="fas fa-sitemap nav-icon"></i><p>Categories</p></a></li>@endif
                            @if($__hasMenu('inventory.vendors'))<li class="nav-item"><a href="{{ route('admin.inventory.vendors.index') }}" class="nav-link"><i class="fas fa-truck nav-icon"></i><p>Vendors</p></a></li>@endif
                            @if($__hasMenu('inventory.serial'))<li class="nav-item"><a href="{{ route('admin.inventory.serial.search') }}" class="nav-link {{ request()->routeIs('admin.inventory.serial.search') ? 'active' : '' }}"><i class="fas fa-search nav-icon"></i><p>Serial Search</p></a></li>@endif
                            @if($__hasMenu('inventory.instruments'))<li class="nav-item"><a href="{{ route('admin.inventory.instruments.index') }}" class="nav-link {{ request()->routeIs('admin.inventory.instruments.*') ? 'active' : '' }}"><i class="fas fa-tools nav-icon"></i><p>Product Stock</p></a></li>@endif
                            @if($__hasMenu('inventory.product_analyse'))<li class="nav-item"><a href="{{ route('admin.inventory.product.analyse') }}" class="nav-link {{ request()->routeIs('admin.inventory.product.analyse') ? 'active' : '' }}"><i class="fas fa-chart-pie nav-icon"></i><p>Product Analyse</p></a></li>@endif
                            @if($__hasMenu('inventory.fiber'))<li class="nav-item"><a href="{{ route('admin.inventory.fiber.index') }}" class="nav-link {{ request()->is('admin/inventory/fiber') ? 'active' : '' }}"><i class="fas fa-project-diagram nav-icon"></i><p>Fiber Stock</p></a></li>@endif
                            @if($__hasMenu("inventory.fiber_analytics"))<li class="nav-item"><a href="{{ route("admin.inventory.fiber.analytics") }}" class="nav-link {{ request()->routeIs("admin.inventory.fiber.analytics") ? "active" : "" }}"><i class="fas fa-chart-line nav-icon"></i><p>Fiber Analytics</p></a></li>@endif
                            @if($__hasMenu('inventory.damaged'))<li class="nav-item"><a href="{{ route('admin.inventory.damage.index') }}" class="nav-link"><i class="fas fa-exclamation-triangle nav-icon"></i><p>Damaged Items</p></a></li>@endif
                            @if($__hasMenu('inventory.returned_equipments'))<li class="nav-item"><a href="{{ route('admin.inventory.returned-equipments') }}" class="nav-link {{ request()->is('admin/inventory/returned-equipments*') ? 'active' : '' }}"><i class="fas fa-undo-alt nav-icon"></i><p>Returned Products &amp; Fiber</p></a></li>@endif
                            @if($__hasMenu('inventory.inactive_client_history'))<li class="nav-item"><a href="{{ route('admin.inventory.inactive-client-history') }}" class="nav-link {{ request()->routeIs('admin.inventory.inactive-client-history') ? 'active' : '' }}"><i class="fas fa-user-slash nav-icon"></i><p>Inactive Client History</p></a></li>@endif
                            @if($__hasMenu('inventory.stock_availability'))<li class="nav-item"><a href="{{ route('admin.inventory.stock.availability') }}" class="nav-link"><i class="fas fa-warehouse nav-icon"></i><p>Stock Availability</p></a></li>@endif
                            @if($__hasMenu('reports.stock'))<li class="nav-item"><a href="{{ route('admin.reports.stock') }}" class="nav-link {{ request()->routeIs('admin.reports.stock') ? 'active' : '' }}"><i class="fas fa-chart-bar nav-icon"></i><p>Stock Report</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasMenu('daily_billing'))
                    <li class="nav-item {{ request()->is('admin/daily-billing*') ? 'menu-open' : '' }}">
                        <a href="{{ route('admin.daily-billing.index') }}" class="nav-link {{ request()->routeIs('admin.daily-billing.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar-day"></i>
                            <p>Daily Billing</p>
                        </a>
                    </li>
                    @endif
                    @if($__hasAnyChild('minute_sales'))
                    <li class="nav-item {{ request()->is('admin/minute-sales*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Minute Sales<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('minute_sales.packages'))<li class="nav-item"><a href="{{ route('admin.minute-sales.packages') }}" class="nav-link"><i class="fas fa-cube nav-icon"></i><p>Packages</p></a></li>@endif
                            @if($__hasMenu('minute_sales.sell'))<li class="nav-item"><a href="{{ route('admin.minute-sales.sell') }}" class="nav-link"><i class="fas fa-shopping-bag nav-icon"></i><p>Sell Minutes</p></a></li>@endif
                            @if($__hasMenu('minute_sales.report'))<li class="nav-item"><a href="{{ route('admin.minute-sales.sales') }}" class="nav-link"><i class="fas fa-chart-bar nav-icon"></i><p>Sales Report</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasMenu('payment_links'))
                    <li class="nav-item {{ request()->is('admin/payment-links*') ? 'menu-open' : '' }}">
                        <a href="{{ route('admin.payment-links.index') }}" class="nav-link {{ request()->routeIs('admin.payment-links.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>Payment Links</p>
                        </a>
                    </li>
                    @endif
                    @if($__hasAnyChild('hrm'))
                    <li class="nav-item {{ request()->is('admin/hrm*') || request()->is('admin/permissions*') || request()->is('admin/menu-permissions*') || request()->is('admin/role-permissions*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/hrm*') || request()->is('admin/permissions*') || request()->is('admin/menu-permissions*') || request()->is('admin/role-permissions*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-id-card-alt"></i>
                            <p>HRM<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('hrm.employees'))<li class="nav-item"><a href="{{ route('admin.hrm.employees') }}" class="nav-link {{ request()->routeIs('admin.hrm.employees') ? 'active' : '' }}"><i class="fas fa-user-tie nav-icon"></i><p>Employees</p></a></li>@endif
                            @if($__hasMenu('hrm.departments'))<li class="nav-item"><a href="{{ route('admin.hrm.departments') }}" class="nav-link {{ request()->routeIs('admin.hrm.departments') ? 'active' : '' }}"><i class="fas fa-building nav-icon"></i><p>Departments</p></a></li>@endif
                            @if($__hasMenu('hrm.designations'))<li class="nav-item"><a href="{{ route('admin.hrm.designations') }}" class="nav-link {{ request()->routeIs('admin.hrm.designations') ? 'active' : '' }}"><i class="fas fa-id-badge nav-icon"></i><p>Designations</p></a></li>@endif
                            @if($__hasMenu('hrm.attendance'))<li class="nav-item"><a href="{{ route('admin.hrm.attendance') }}" class="nav-link {{ request()->routeIs('admin.hrm.attendance') ? 'active' : '' }}"><i class="fas fa-fingerprint nav-icon"></i><p>Attendance</p></a></li>@endif
                            @if($__hasMenu('hrm.selfie_attendance'))<li class="nav-item"><a href="{{ route('admin.hrm.selfie-attendance') }}" class="nav-link {{ request()->is('admin/hrm/selfie-attendance') ? 'active' : '' }}"><i class="fas fa-camera nav-icon"></i><p>Selfie Attendance</p></a></li>@endif
                            @if($__hasMenu('hrm.selfie_attendance_report'))<li class="nav-item"><a href="{{ route('admin.hrm.selfie-attendance.report') }}" class="nav-link {{ request()->is('admin/hrm/selfie-attendance-report') ? 'active' : '' }}"><i class="fas fa-calendar-check nav-icon"></i><p>Selfie Report</p></a></li>@endif
                            @if($__hasMenu('hrm.leave'))<li class="nav-item"><a href="{{ route('admin.hrm.leave') }}" class="nav-link {{ request()->routeIs('admin.hrm.leave') ? 'active' : '' }}"><i class="fas fa-plane-departure nav-icon"></i><p>Leave Management</p></a></li>@endif
                            @if($__hasMenu('hrm.salary'))<li class="nav-item"><a href="{{ route('admin.hrm.salary') }}" class="nav-link {{ request()->routeIs('admin.hrm.salary') ? 'active' : '' }}"><i class="fas fa-money-check nav-icon"></i><p>Salary Payroll</p></a></li>@endif
                            @if($__hasMenu('hrm.staff_dues'))<li class="nav-item"><a href="{{ route('admin.hrm.staff-dues') }}" class="nav-link {{ request()->is('admin/hrm/staff-dues*') ? 'active' : '' }}"><i class="fas fa-hand-holding-usd nav-icon"></i><p>Staff Dues</p></a></li>@endif
                            @if($__isSuperAdmin || $__hasMenu('hrm.menu_access'))
                            <li class="nav-item">
                                <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ request()->is('admin/permissions*') ? 'active' : '' }}">
                                    <i class="fas fa-shield-alt nav-icon"></i>
                                    <p>Permission Management</p>
                                </a>
                            </li>
                            @endif
                            @if($__hasMenu('hrm.role_permissions'))<li class="nav-item"><a href="{{ route('admin.settings.role-permissions') }}" class="nav-link {{ request()->routeIs('admin.settings.role-permissions') ? 'active' : '' }}"><i class="fas fa-user-shield nav-icon"></i><p>Role Permissions</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('data_transfer'))
                    <li class="nav-item {{ request()->is('admin/data-transfer*') || request()->is('admin/migration*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/data-transfer*') || request()->is('admin/migration*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cloud-upload-alt"></i>
                            <p>Data Transfer<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('data_transfer.overview'))<li class="nav-item"><a href="{{ route('admin.data-transfer.index') }}" class="nav-link {{ request()->routeIs('admin.data-transfer.index') ? 'active' : '' }}"><i class="fas fa-th-large nav-icon"></i><p>Overview</p></a></li>@endif
                            @if($__hasMenu('data_transfer.quick'))<li class="nav-item"><a href="{{ route('admin.data-transfer.quick') }}" class="nav-link {{ request()->is('admin/data-transfer/quick*') ? 'active' : '' }}"><i class="fas fa-bolt nav-icon text-warning"></i><p>Quick Import/Export</p></a></li>@endif
                            @if($__hasMenu('data_transfer.import'))<li class="nav-item"><a href="{{ route('admin.data-transfer.import.index') }}" class="nav-link {{ request()->routeIs('admin.data-transfer.import.*') ? 'active' : '' }}"><i class="fas fa-file-import nav-icon"></i><p>Import Data</p></a></li>@endif
                            @if($__hasMenu('data_transfer.export'))<li class="nav-item"><a href="{{ route('admin.data-transfer.export.index') }}" class="nav-link {{ request()->routeIs('admin.data-transfer.export.*') ? 'active' : '' }}"><i class="fas fa-file-export nav-icon"></i><p>Export Data</p></a></li>@endif
                            @if($__hasMenu('data_transfer.migration'))<li class="nav-item"><a href="{{ route('admin.migration.index') }}" class="nav-link {{ request()->is('admin/migration*') ? 'active' : '' }}"><i class="fas fa-rocket nav-icon text-danger"></i><p>Migration Wizard</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasMenu('hotspot.editor'))
                    <li class="nav-item">
                        <a href="{{ route('admin.hotspot.index') }}" class="nav-link {{ request()->is('admin/hotspot*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-wifi"></i>
                            <p>Hotspot Editor</p>
                        </a>
                    </li>
                    @endif
                    @if($__hasAnyChild('sms'))
                    <li class="nav-item {{ request()->is('admin/sms*') || request()->is('admin/settings/sms*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/sms*') || request()->is('admin/settings/sms*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-paper-plane"></i>
                            <p>SMS Service<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('sms.individual'))<li class="nav-item"><a href="{{ route('admin.sms.individual') }}" class="nav-link {{ request()->routeIs('admin.sms.individual') ? 'active' : '' }}"><i class="fas fa-envelope nav-icon"></i><p>Individual SMS</p></a></li>@endif
                            @if($__hasMenu('sms.bulk'))<li class="nav-item"><a href="{{ route('admin.sms.bulk') }}" class="nav-link {{ request()->routeIs('admin.sms.bulk') ? 'active' : '' }}"><i class="fas fa-mail-bulk nav-icon"></i><p>Send SMS</p></a></li>@endif
                            @if($__hasMenu('sms.templates'))<li class="nav-item"><a href="{{ route('admin.sms.templates') }}" class="nav-link {{ request()->routeIs('admin.sms.templates*') ? 'active' : '' }}"><i class="fas fa-file-alt nav-icon"></i><p>SMS Templates</p></a></li>@endif
                            @if($__hasMenu('sms.gateway'))<li class="nav-item"><a href="{{ route('admin.sms.gateway') }}" class="nav-link {{ request()->routeIs('admin.sms.gateway') ? 'active' : '' }}"><i class="fas fa-server nav-icon"></i><p>SMS Gateway</p></a></li>@endif
                            @if($__hasMenu('sms.settings'))<li class="nav-item"><a href="{{ route('admin.settings.sms') }}" class="nav-link {{ request()->routeIs('admin.settings.sms') ? 'active' : '' }}"><i class="fas fa-cog nav-icon"></i><p>SMS Settings</p></a></li>@endif
                            @if($__hasMenu('sms.logs'))<li class="nav-item"><a href="{{ route('admin.sms.logs') }}" class="nav-link {{ request()->routeIs('admin.sms.logs') ? 'active' : '' }}"><i class="fas fa-history nav-icon"></i><p>SMS Logs</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('whatsapp'))
                    <li class="nav-item {{ request()->is('admin/whatsapp*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/whatsapp*') ? 'active' : '' }}">
                            <i class="nav-icon fab fa-whatsapp" style="color: #25D366;"></i>
                            <p>WhatsApp<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('whatsapp.settings'))<li class="nav-item"><a href="{{ route('admin.whatsapp.settings') }}" class="nav-link {{ request()->routeIs('admin.whatsapp.settings') ? 'active' : '' }}"><i class="fas fa-cog nav-icon"></i><p>Settings</p></a></li>@endif
                            @if($__hasMenu('whatsapp.templates'))<li class="nav-item"><a href="{{ route('admin.whatsapp.templates') }}" class="nav-link {{ request()->routeIs('admin.whatsapp.templates') ? 'active' : '' }}"><i class="fas fa-file-code nav-icon"></i><p>Templates</p></a></li>@endif
                            @if($__hasMenu('whatsapp.logs'))<li class="nav-item"><a href="{{ route('admin.whatsapp.logs') }}" class="nav-link {{ request()->routeIs('admin.whatsapp.logs') ? 'active' : '' }}"><i class="fas fa-clipboard nav-icon"></i><p>Message Logs</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('email'))
                    <li class="nav-item {{ request()->is('admin/email*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/email*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>Email System<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('email.logs'))<li class="nav-item"><a href="{{ route('admin.email.index') }}" class="nav-link {{ request()->routeIs('admin.email.index') ? 'active' : '' }}"><i class="fas fa-chart-bar nav-icon"></i><p>Dashboard & Logs</p></a></li>@endif
                            @if($__hasMenu('email.settings'))<li class="nav-item"><a href="{{ route('admin.email.settings') }}" class="nav-link {{ request()->routeIs('admin.email.settings') ? 'active' : '' }}"><i class="fas fa-cog nav-icon"></i><p>SMTP Settings</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('ipam'))
                    <li class="nav-item {{ request()->is('admin/ipam*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-globe"></i>
                            <p>IPAM<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('ipam.pools'))<li class="nav-item"><a href="{{ route('admin.ipam.index') }}" class="nav-link {{ request()->routeIs('admin.ipam.index') ? 'active' : '' }}"><i class="fas fa-network-wired nav-icon"></i><p>IP Pools</p></a></li>@endif
                            @if($__hasMenu('ipam.create'))<li class="nav-item"><a href="{{ route('admin.ipam.create') }}" class="nav-link {{ request()->routeIs('admin.ipam.create') ? 'active' : '' }}"><i class="fas fa-plus nav-icon"></i><p>Create Pool</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('vpn'))
                    <li class="nav-item {{ request()->is('admin/vpn*') || request()->is('admin/port-forward*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-lock"></i>
                            <p>VPN & Forwarding<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('vpn.accounts'))<li class="nav-item"><a href="{{ route('admin.vpn.index') }}" class="nav-link {{ request()->routeIs('admin.vpn.*') ? 'active' : '' }}"><i class="fas fa-key nav-icon"></i><p>VPN Accounts</p></a></li>@endif
                            @if($__hasMenu('vpn.port_forwarding'))<li class="nav-item"><a href="{{ route('admin.port-forward.index') }}" class="nav-link {{ request()->routeIs('admin.port-forward.*') ? 'active' : '' }}"><i class="fas fa-directions nav-icon"></i><p>Port Forwarding</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('voice_calls'))
                    <li class="nav-item {{ request()->is('admin/voice-calls*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-phone-alt"></i>
                            <p>Voice Calls<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('voice_calls.history'))<li class="nav-item"><a href="{{ route('admin.voice-calls.index') }}" class="nav-link {{ request()->routeIs('admin.voice-calls.index') ? 'active' : '' }}"><i class="fas fa-phone-volume nav-icon"></i><p>Call History</p></a></li>@endif
                            @if($__hasMenu('voice_calls.settings'))<li class="nav-item"><a href="{{ route('admin.voice-calls.settings') }}" class="nav-link"><i class="fas fa-cog nav-icon"></i><p>Settings</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('card_billing'))
                    <li class="nav-item {{ request()->is('admin/card-billing*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>Card Billing<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('card_billing.batches'))<li class="nav-item"><a href="{{ route('admin.card-billing.batches') }}" class="nav-link {{ request()->routeIs('admin.card-billing.batches') ? 'active' : '' }}"><i class="fas fa-layer-group nav-icon"></i><p>Card Batches</p></a></li>@endif
                            @if($__hasMenu('card_billing.cards'))<li class="nav-item"><a href="{{ route('admin.card-billing.cards') }}" class="nav-link {{ request()->routeIs('admin.card-billing.cards') ? 'active' : '' }}"><i class="fas fa-id-badge nav-icon"></i><p>All Cards</p></a></li>@endif
                            @if($__hasMenu('card_billing.redeem'))<li class="nav-item"><a href="{{ route('admin.card-billing.redeem') }}" class="nav-link {{ request()->routeIs('admin.card-billing.redeem') ? 'active' : '' }}"><i class="fas fa-gift nav-icon"></i><p>Redeem Card</p></a></li>@endif
                            @if($__hasMenu('card_billing.distributors'))<li class="nav-item"><a href="{{ route('admin.card-billing.distributors') }}" class="nav-link {{ request()->routeIs('admin.card-billing.distributors') ? 'active' : '' }}"><i class="fas fa-people-carry nav-icon"></i><p>Distributors</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @include("components.media-servers-sidebar")
                    @if($__hasAnyChild('configuration'))
                    <li class="nav-item {{ request()->is('admin/settings/packages*') || request()->is('admin/config*') || request()->routeIs('admin.settings.dropdown-options') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-sliders-h"></i>
                            <p>Configuration<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('configuration.district'))<li class="nav-item"><a href="{{ route('admin.config.district') }}" class="nav-link {{ request()->routeIs('admin.config.district*') ? 'active' : '' }}"><i class="fas fa-map nav-icon"></i><p>District</p></a></li>@endif
                            @if($__hasMenu('configuration.area'))<li class="nav-item"><a href="{{ route('admin.config.area') }}" class="nav-link {{ request()->routeIs('admin.config.area*') ? 'active' : '' }}"><i class="fas fa-map-marked-alt nav-icon"></i><p>Area</p></a></li>@endif
                            @if($__hasMenu('configuration.connection_type'))<li class="nav-item"><a href="{{ route('admin.config.connection-type') }}" class="nav-link {{ request()->routeIs('admin.config.connection-type*') ? 'active' : '' }}"><i class="fas fa-plug nav-icon"></i><p>Connection Type</p></a></li>@endif
                            @if($__hasMenu('configuration.protocol_type'))<li class="nav-item"><a href="{{ route('admin.config.protocol-type') }}" class="nav-link {{ request()->routeIs('admin.config.protocol-type*') ? 'active' : '' }}"><i class="fas fa-code-branch nav-icon"></i><p>Protocol Type</p></a></li>@endif
                            @if($__hasMenu('configuration.client_type'))<li class="nav-item"><a href="{{ route('admin.config.client-type') }}" class="nav-link {{ request()->routeIs('admin.config.client-type*') ? 'active' : '' }}"><i class="fas fa-user-tag nav-icon"></i><p>Client Type</p></a></li>@endif
                            @if($__hasMenu('configuration.package'))<li class="nav-item"><a href="{{ route('admin.settings.packages') }}" class="nav-link"><i class="fas fa-cube nav-icon"></i><p>Package & Profile</p></a></li>@endif
                            @if($__hasMenu('configuration.dropdown_options'))<li class="nav-item"><a href="{{ route('admin.settings.dropdown-options') }}" class="nav-link {{ request()->routeIs('admin.settings.dropdown-options') ? 'active' : '' }}"><i class="fas fa-list-alt nav-icon"></i><p>Dropdown Options</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('settings'))
                    <li class="nav-item {{ (request()->is('admin/settings*') && !request()->is('admin/settings/zones*') && !request()->is('admin/settings/areas*') && !request()->is('admin/settings/packages*') && !request()->routeIs('admin.settings.dropdown-options') && !request()->is('admin/settings/sms*')) || request()->is('admin/system/logs*') || request()->routeIs('admin.security.login-logs') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ (request()->is('admin/settings*') && !request()->is('admin/settings/zones*') && !request()->is('admin/settings/areas*') && !request()->is('admin/settings/packages*') && !request()->routeIs('admin.settings.dropdown-options') && !request()->is('admin/settings/sms*')) || request()->is('admin/system/logs*') || request()->routeIs('admin.security.login-logs') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Settings<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('settings.company'))<li class="nav-item"><a href="{{ route('admin.settings.company') }}" class="nav-link {{ request()->routeIs('admin.settings.company') ? 'active' : '' }}"><i class="fas fa-building nav-icon"></i><p>Company Profile</p></a></li>@endif
                            @if($__hasMenu('settings.logo'))<li class="nav-item"><a href="{{ route('admin.settings.logo') }}" class="nav-link {{ request()->routeIs('admin.settings.logo') ? 'active' : '' }}"><i class="fas fa-image nav-icon"></i><p>Logo Settings</p></a></li>@endif
                            @if($__hasMenu('settings.system'))<li class="nav-item"><a href="{{ route('admin.settings.system') }}" class="nav-link {{ request()->routeIs('admin.settings.system') ? 'active' : '' }}"><i class="fas fa-cog nav-icon"></i><p>System Settings</p></a></li>@endif
                            @if($__hasMenu('settings.payment_methods'))<li class="nav-item"><a href="{{ route('admin.settings.payment-methods') }}" class="nav-link {{ request()->routeIs('admin.settings.payment-methods') ? 'active' : '' }}"><i class="fas fa-credit-card nav-icon"></i><p>Payment Methods</p></a></li>@endif
                            @if($__hasMenu('settings.payment'))<li class="nav-item"><a href="{{ route('admin.settings.payment') }}" class="nav-link {{ request()->routeIs('admin.settings.payment') ? 'active' : '' }}"><i class="fas fa-dollar-sign nav-icon"></i><p>Payment Settings</p></a></li>@endif
                            @if($__hasMenu('settings.logs'))<li class="nav-item"><a href="{{ route('admin.system.logs') }}" class="nav-link {{ request()->routeIs('admin.system.logs') ? 'active' : '' }}"><i class="fas fa-file-medical-alt nav-icon"></i><p>System Logs</p></a></li>@endif
                            @if($__hasMenu('settings.login_logs'))<li class="nav-item"><a href="{{ route('admin.security.login-logs') }}" class="nav-link {{ request()->routeIs('admin.security.login-logs') ? 'active' : '' }}"><i class="fas fa-sign-in-alt nav-icon"></i><p>Login Logs</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__hasAnyChild('security'))
                    <li class="nav-item {{ request()->is('admin/security*') || request()->is('admin/2fa*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-fingerprint"></i>
                            <p>Security<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($__hasMenu('security.dashboard'))<li class="nav-item"><a href="{{ route('admin.security.index') }}" class="nav-link"><i class="fas fa-shield-alt nav-icon"></i><p>Dashboard</p></a></li>@endif
                            @if($__hasMenu('security.2fa'))<li class="nav-item"><a href="{{ route('admin.2fa.setup') }}" class="nav-link"><i class="fas fa-mobile-alt nav-icon"></i><p>Two-Factor Auth</p></a></li>@endif
                            @if($__hasMenu('security.password_policy'))<li class="nav-item"><a href="{{ route('admin.security.password-policy') }}" class="nav-link"><i class="fas fa-lock nav-icon"></i><p>Password Policy</p></a></li>@endif
                            @if($__hasMenu('security.api_tokens'))<li class="nav-item"><a href="{{ route('admin.security.api-tokens') }}" class="nav-link"><i class="fas fa-key nav-icon"></i><p>API Tokens</p></a></li>@endif
                        </ul>
                    </li>
                    @endif
                    @if($__isSuperAdmin || $__hasAnyChild('backups'))
                    <li class="nav-item">
                        <a href="{{ route('admin.backups.index') }}" class="nav-link {{ request()->is('admin/backups*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hdd"></i>
                            <p>Backup & Restore</p>
                        </a>
                    </li>
                    @endif
                    @if($__isSuperAdmin || $__hasMenu('help.center'))
                    <li class="nav-item">
                        <a href="{{ route('admin.help.index') }}" class="nav-link {{ request()->routeIs('admin.help.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book-open"></i>
                            <p>Help Center</p>
                        </a>
                    </li>
                    @endif
                    @if($__isSuperAdmin)
                    <li class="nav-item {{ request()->routeIs('admin.system.vps-monitor') || request()->routeIs('admin.system.app-info') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.system.vps-monitor') || request()->routeIs('admin.system.app-info') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-server"></i>
                            <p>System Tools<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('admin.system.vps-monitor') }}" class="nav-link {{ request()->routeIs('admin.system.vps-monitor') ? 'active' : '' }}"><i class="fas fa-tachometer-alt nav-icon"></i><p>VPS Monitor</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.system.app-info') }}" class="nav-link {{ request()->routeIs('admin.system.app-info') ? 'active' : '' }}"><i class="fas fa-info-circle nav-icon"></i><p>App Info</p></a></li>
                        </ul>
                    </li>
<li class="nav-item">
                        <a href="{{ route('superadmin.dashboard') }}" class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-crown"></i>
                            <p>Super Admin Panel</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('superadmin.companies.index') }}" class="nav-link {{ request()->routeIs('superadmin.companies.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-building"></i>
                            <p>Company Mgmt.</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('superadmin.footer-settings') }}" class="nav-link {{ request()->routeIs('superadmin.footer-settings') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shoe-prints"></i>
                            <p>Footer Settings</p>
                        </a>
                    </li>
                    @endif
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        @hasSection('content_header')
                        @yield('content_header')
                        @else
                        <h1 class="m-0">@yield('title', 'Dashboard')</h1>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content" style="flex: 1 1 auto;">
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

        {{-- Footer moved outside container-fluid so flexbox works correctly --}}
        <footer style="text-align:center; padding:14px 0 10px; border-top:1px solid #e2e8f0; background:#fff; color:#64748b; font-size:13px; flex-shrink:0;">
            @php
                $globalFooter = \App\Models\SystemSetting::getGlobal('global_footer_text', '');
            @endphp
            @if($globalFooter)
                {!! str_replace('{year}', date('Y'), $globalFooter) !!}
            @else
                &copy; {{ date('Y') }} <strong>{{ $companyName ?? 'SOFTYNEX' }}</strong>. All Rights Reserved.
            @endif
        </footer>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
(function() {
    var _redirectingToLogin = false;
    function redirectToLogin() {
        if (_redirectingToLogin) return;
        _redirectingToLogin = true;
        window.location.href = '/login';
    }
    $(document).ajaxError(function(event, xhr) {
        if (xhr.status === 419 || xhr.status === 401) {
            redirectToLogin();
        }
    });
    var _originalFetch = window.fetch;
    window.fetch = function() {
        return _originalFetch.apply(this, arguments).then(function(response) {
            if (response.status === 419 || response.status === 401) {
                redirectToLogin();
            }
            return response;
        });
    };
})();
</script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global Chart.js font size increase
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.size = 14;
        Chart.defaults.font.family = "'Poppins', 'Noto Sans Bengali', sans-serif";
        Chart.defaults.plugins.legend.labels.font = { size: 13 };
        Chart.defaults.plugins.title.font = { size: 15, weight: 'bold' };
        Chart.defaults.plugins.tooltip.bodyFont = { size: 13 };
        Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' };
        Chart.defaults.scale.ticks.font = { size: 12 };
    }
});
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const SmartSwal = Swal.mixin({
    customClass: {
        popup: 'smart-swal-popup',
        title: 'smart-swal-title',
        htmlContainer: 'smart-swal-html',
        confirmButton: 'btn btn-primary px-4',
        cancelButton: 'btn btn-secondary px-4 ml-2',
        denyButton: 'btn btn-danger px-4 ml-2',
    },
    buttonsStyling: false,
    showClass: { popup: 'animate__animated animate__fadeInDown animate__faster' },
    hideClass: { popup: 'animate__animated animate__fadeOutUp animate__faster' }
});
window.SmartSwal = SmartSwal;
})();
</script>
<script src="/js/sweet-confirm.js?v=4"></script>
<script>
setTimeout(function() {
    $('.content-wrapper > .content > .container-fluid > .alert-success:not([data-permanent]), .content-wrapper > .content > .container-fluid > .alert-info:not([data-permanent]), .alert-dismissible.alert-success:not([data-permanent]), .alert-dismissible.alert-info:not([data-permanent])').each(function() {
        var el = $(this);
        setTimeout(function() { el.fadeOut(500, function() { el.remove(); }); }, 5000);
    });
}, 100);
})();
</script>
<style>
.smart-swal-popup {
    border-radius: 10px !important;
    padding: 16px 20px !important;
    font-family: 'Poppins', sans-serif !important;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important;
    max-width: 380px !important;
}
.smart-swal-title { font-size: 16px !important; font-weight: 600 !important; color: #1e3c72 !important; }
.smart-swal-html { font-size: 13px !important; color: #4a5568 !important; line-height: 1.5 !important; }
.swal2-icon { transform: scale(0.7) !important; margin: 0.4em auto 0 !important; }
.swal2-actions { gap: 8px !important; margin-top: 0.6em !important; }
.swal2-actions .btn { min-width: 80px; border-radius: 6px !important; font-weight: 500 !important; font-size: 13px !important; padding: 6px 16px !important; }
.swal2-actions .btn-primary { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important; border-color: #1e3c72 !important; color: #fff !important; }
.swal2-actions .btn-danger { background: #dc3545 !important; border-color: #dc3545 !important; color: #fff !important; }
</style>

<script>
// SIDEBAR SCROLL PERSISTENCE FIX
(function() {
    var KEY = 'sidebarScrollPos';
    var SIDEBAR_SEL = '.main-sidebar .sidebar';

    // Restore scroll position as early as possible (before render)
    document.addEventListener('DOMContentLoaded', function() {
        var sb = document.querySelector(SIDEBAR_SEL);
        if (!sb) return;
        var pos = parseInt(localStorage.getItem(KEY) || '0', 10);
        if (pos > 0) {
            sb.scrollTop = pos;
            // Double-ensure after AdminLTE initializes
            setTimeout(function() { sb.scrollTop = pos; }, 50);
            setTimeout(function() { sb.scrollTop = pos; }, 200);
            setTimeout(function() { sb.scrollTop = pos; }, 500);
        }
    });

    // Save scroll position on every scroll
    document.addEventListener('scroll', function() {
        var sb = document.querySelector(SIDEBAR_SEL);
        if (sb) localStorage.setItem(KEY, sb.scrollTop);
    }, true);

    // Also save when sidebar itself is scrolled
    document.addEventListener('DOMContentLoaded', function() {
        var sb = document.querySelector(SIDEBAR_SEL);
        if (!sb) return;
        sb.addEventListener('scroll', function() {
            localStorage.setItem(KEY, sb.scrollTop);
        });
    });

    // Save before navigation (page leave)
    window.addEventListener('beforeunload', function() {
        var sb = document.querySelector(SIDEBAR_SEL);
        if (sb) localStorage.setItem(KEY, sb.scrollTop);
    });
})();
</script>
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
                url: '{{ route("admin.api.quick-search") }}',
                data: { q: query },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(data) {
                    if (!data || data.length === 0) {
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
                                        <i class="fas fa-wifi mr-1"></i>${client.package} | <i class="fas fa-user mr-1"></i>${client.username}
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                        `;
                    });
                    html += '</div>';
                    searchResults.html(html).show();
                },
                error: function(xhr) {
                    if (xhr.status === 401 || xhr.status === 419) {
                        window.location.reload();
                    }
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
})();
</script>

<style>
#globalQuickSearch::placeholder {
    color: rgba(255,255,255,0.6);
}
#globalQuickSearch:focus {
    background: rgba(255,255,255,0.25) !important;
    box-shadow: none !important;
    border: none !important;
    outline: none !important;
}
.global-search-container .input-group-text {
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
}
.global-search-container .form-control {
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
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
                    !$('#mobileMenuBtn').is(e.target) && $('#mobileMenuBtn').has(e.target).length === 0) {
                    $('body').removeClass('sidebar-open').addClass('sidebar-collapse');
                }
            }
        }
    });
});
})();
</script>

@include('components.payment-receipt-modal')
@include('components.ping-modal')


<div class="modal fade" id="contextualHelpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content" style="border-radius:14px; overflow:hidden; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; padding:20px 25px;">
                <div>
                    <h5 class="modal-title" style="font-weight:700; font-size:18px;">
                        <i id="helpModalIcon" class="fas fa-question-circle mr-2"></i>
                        <span id="helpModalTitle">Help</span>
                    </h5>
                    <small id="helpModalModule" style="opacity:0.85;"></small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity:0.9; text-shadow:none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:25px; font-size:14px; line-height:1.9; color:#4a5568;" id="helpModalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-3 text-muted">Loading...</p>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #eee; padding:12px 25px;">
                <a href="{{ route('admin.help.index') }}" class="btn btn-outline-primary btn-sm" style="border-radius:8px;">
                    <i class="fas fa-book-open mr-1"></i> Full Help Center
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:8px;">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
#helpModalBody strong {
    color: #2d3748;
}
#helpModalBody ul, #helpModalBody ol {
    padding-left: 20px;
}
.help-content-formatted p {
    margin-bottom: 12px;
}
</style>

<script>
$(document).ready(function() {
    var currentRoute = @json(optional(\Illuminate\Support\Facades\Route::current())->getName() ?? '');

    $('#contextualHelpBtn').on('click', function() {
        $('#helpModalBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-3 text-muted">Loading...</p></div>');
        $('#contextualHelpModal').modal('show');

        $.ajax({
            url: '{{ route("admin.help.contextual") }}',
            data: { route: currentRoute },
            success: function(data) {
                if (data.found) {
                    $('#helpModalIcon').attr('class', data.icon + ' mr-2');
                    $('#helpModalTitle').text(data.title);
                    $('#helpModalModule').text(data.module + ' Module');

                    var tmp = document.createElement('div');
                    tmp.textContent = data.content;
                    var content = tmp.innerHTML;
                    content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    content = content.replace(/^• (.+)$/gm, '<li style="list-style:none;padding-left:0;"><span style="color:#667eea;margin-right:6px;">●</span> $1</li>');
                    content = content.replace(/^(\d+)\. (.+)$/gm, '<li style="list-style:none;padding-left:0;"><span style="background:#667eea;color:#fff;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;margin-right:8px;">$1</span> $2</li>');
                    content = content.replace(/⚠️/g, '<span style="color:#e53e3e;">⚠️</span>');

                    var lines = content.split('\n');
                    var html = '';
                    for (var i = 0; i < lines.length; i++) {
                        var line = lines[i].trim();
                        if (line === '') {
                            html += '<div style="height:8px;"></div>';
                        } else {
                            html += '<div style="margin-bottom:4px;">' + line + '</div>';
                        }
                    }

                    $('#helpModalBody').html('<div class="help-content-formatted">' + html + '</div>');
                } else {
                    $('#helpModalTitle').text('Help');
                    $('#helpModalModule').text('');
                    $('#helpModalBody').html(
                        '<div class="text-center py-4">' +
                        '<i class="fas fa-info-circle fa-3x text-muted mb-3" style="display:block;"></i>' +
                        '<h6 class="text-muted">No help information found for this page</h6>' +
                        '<p class="text-muted small">Click the button below to see the full Help Center</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#helpModalBody').html(
                    '<div class="text-center py-4">' +
                    '<i class="fas fa-exclamation-triangle fa-3x text-warning mb-3" style="display:block;"></i>' +
                    '<h6 class="text-muted">Failed to load help information</h6>' +
                    '</div>'
                );
            }
        });
    });
});
})();
</script>

<script>
function refreshNavSmsBalance() {
    var spinner = document.getElementById('navSmsSpinner');
    if (spinner) spinner.classList.add('fa-spin');
    fetch('{{ route("admin.settings.sms.balance") }}')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var el = document.getElementById('navSmsBalance');
            if (el) {
                var bal = parseFloat(data.balance || 0);
                el.textContent = isNaN(bal) ? (data.balance || 'N/A') : bal.toFixed(2);
                el.style.color = bal < 50 ? '#ff5252' : '#69f0ae';
            }
            if (spinner) spinner.classList.remove('fa-spin');
        })
        .catch(function() {
            if (spinner) spinner.classList.remove('fa-spin');
        });
}
document.addEventListener('DOMContentLoaded', function() { refreshNavSmsBalance(); });
setInterval(refreshNavSmsBalance, 300000);
</script>


{{-- ═══ Global Create Ticket Modal ═══ --}}
@php
    $__gtCats  = \App\Models\TicketCategory::orderBy('name')->get();
    $__gtPkgs  = \App\Models\Package::orderBy('name')->get();
    $__gtDepts = \App\Models\Department::with(['employees'])->get();
    $__gtCatSubs = $__gtCats->mapWithKeys(fn($c) => [(string)$c->id => $c->subjects ?? []]);
@endphp
<div class="modal fade" id="globalCreateTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.support.tickets.create') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title mb-0"><i class="fas fa-ticket-alt mr-2"></i>Create Support Ticket</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="gtCategorySelect" class="form-control" required>
                                    <option value="">Select Category</option>
                                    @foreach($__gtCats as $__cat)
                                    <option value="{{ $__cat->id }}" data-name="{{ strtolower($__cat->name) }}">{{ $__cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="gtRegularFields">
                        <div class="row">
                            <div class="col-md-6">
                                @include('admin.partials.client-search', ['inputId' => 'gtClientSearch', 'hiddenId' => 'gtClientId', 'fieldName' => 'client_id', 'label' => 'Client', 'required' => true])
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Subject <span class="text-danger">*</span></label>
                                    <select id="gtSubjectSelect" class="form-control" style="display:none;"><option value="">Select Subject</option></select>
                                    <input type="text" name="subject" id="gtSubjectInput" class="form-control" placeholder="Enter ticket subject" required>
                                    <small id="gtSubjectHint" style="display:none;"><a href="#" id="gtSubjectCustom">Or type custom subject</a></small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe the issue in detail" required></textarea>
                        </div>
                    </div>
                    <div id="gtNewConnectionFields" style="display:none;">
                        <div class="alert alert-info mb-3 py-2"><i class="fas fa-info-circle mr-2"></i>New Connection – Enter customer details</div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label class="font-weight-bold">Customer Name</label><input type="text" name="new_customer_name" class="form-control" placeholder="Name"></div></div>
                            <div class="col-md-6"><div class="form-group"><label class="font-weight-bold">Phone</label><input type="text" name="new_customer_phone" class="form-control" placeholder="Phone"></div></div>
                        </div>
                        <div class="form-group"><label class="font-weight-bold">Address</label><textarea name="new_customer_address" class="form-control" rows="2" placeholder="Full address"></textarea></div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group"><label class="font-weight-bold">Package</label>
                                <select name="package_id" class="form-control"><option value="">-- Select Package --</option>
                                @foreach($__gtPkgs as $__pkg)<option value="{{ $__pkg->id }}">{{ $__pkg->name }} – ৳{{ number_format($__pkg->price) }}</option>@endforeach
                                </select></div>
                            </div>
                            <div class="col-md-6"><div class="form-group"><label class="font-weight-bold">Installation Charge</label><input type="text" name="installation_charge" class="form-control" placeholder="e.g. 2500, Free, TBD"></div></div>
                        </div>
                        <div class="form-group"><label class="font-weight-bold">Connection Time</label><input type="datetime-local" name="connection_time" class="form-control"></div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Department</label>
                        <select id="gtDeptFilter" class="form-control"><option value="" selected disabled>-- Select Department --</option>
                        @foreach($__gtDepts as $__dept)<option value="{{ $__dept->id }}">{{ $__dept->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Assign Employee(s) <span class="text-danger">*</span></label>
                        <select id="gtEmployeeSelect" name="technician_ids[]" class="form-control" multiple required>
                            @foreach($__gtDepts as $__dept)
                            <optgroup label="{{ $__dept->name }}" data-dept-id="{{ $__dept->id }}">
                                @foreach($__dept->employees as $__emp)
                                <option value="{{ $__emp->user_id }}" data-dept="{{ $__dept->id }}">{{ $__emp->name }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <small class="text-muted">Filter by department, then select employee(s)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.support.tickets') }}" class="btn btn-link btn-sm text-muted mr-auto"><i class="fas fa-external-link-alt mr-1"></i>All Tickets</a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i>Create Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function(){
var _gtSubs = {!! json_encode($__gtCatSubs) !!};
var _gtAllOpts;
function _gtInitS2(){
    if($.fn.select2){
        $("#gtEmployeeSelect").select2({dropdownParent:$("#globalCreateTicketModal"),placeholder:"Select employee(s)...",allowClear:true});
    }
}
$("#globalCreateTicketModal").on("shown.bs.modal",function(){
    if(!_gtAllOpts) _gtAllOpts = $("#gtEmployeeSelect optgroup").clone(true);
    // Start with empty employee list - user must select department first
    if($.fn.select2) $("#gtEmployeeSelect").select2("destroy");
    $("#gtEmployeeSelect").val(null).empty();
    _gtInitS2();
});
$("#gtDeptFilter").on("change",function(){
    var d=$(this).val();
    if($.fn.select2) $("#gtEmployeeSelect").select2("destroy");
    $("#gtEmployeeSelect").val(null).empty();
    if(!_gtAllOpts) _gtAllOpts = $("#gtEmployeeSelect optgroup").clone(true);
    if(d!==""){var m=_gtAllOpts.filter("[data-dept-id=\""+d+"\"]");if(m.length)$("#gtEmployeeSelect").append(m.clone(true));}
    _gtInitS2();
});
$("#gtCategorySelect").on("change",function(){
    var n=$(this).find("option:selected").data("name")||"";
    var isNew=n.indexOf("new connection")!==-1||n.indexOf("installation")!==-1;
    $("#gtRegularFields").toggle(!isNew);
    $("#gtNewConnectionFields").toggle(isNew);
    var id=String($(this).val());
    var catName=$(this).find("option:selected").text()||"";
    var subs=_gtSubs[id]||[];
    var sel=$("#gtSubjectSelect"),inp=$("#gtSubjectInput"),hint=$("#gtSubjectHint");
    sel.empty().append("<option value=>Select Subject</option>");
    if(subs.length){subs.forEach(function(s){sel.append("<option value=\""+s+"\">"+s+"</option>");});sel.attr("name","subject").show();inp.removeAttr("name").removeAttr("required").hide();hint.show();}
    else{sel.removeAttr("name").hide();inp.attr("name","subject").show().attr("required","required").val(catName).focus();hint.hide();}
});
$("#gtSubjectSelect").on("change",function(){if($(this).val())$("#gtSubjectInput").val($(this).val());});
$("#gtSubjectCustom").on("click",function(e){e.preventDefault();$("#gtSubjectSelect").removeAttr("name").hide();$("#gtSubjectInput").attr("name","subject").show().attr("required","required").val("").focus();$("#gtSubjectHint").hide();});
$("#globalCreateTicketModal").on("hidden.bs.modal",function(){
    $(this).find("form")[0].reset();
    $("#gtSubjectSelect").hide().empty();
    $("#gtSubjectInput").show().attr("required","required");
    $("#gtRegularFields").show();
    $("#gtNewConnectionFields").hide();
    $("#gtDeptFilter").prop("selectedIndex",0);if($.fn.select2) $("#gtEmployeeSelect").select2("destroy");$("#gtEmployeeSelect").val(null).empty();_gtInitS2();
    $(".client-search-results").hide();
    $(".client-search-info").html("");
});
})();
</script>




<script>
(function(){
  function applyAdminLayout(){
    var ticker = document.querySelector('.at-ticker');
    var navbar = document.querySelector('.main-header.navbar');
    var cw    = document.querySelector('.content-wrapper');
    if(!navbar) return;

    // 1) Measure ticker height (0 when ticker is OFF)
    var tickH = 0;
    if(ticker){
      tickH = ticker.getBoundingClientRect().height || ticker.offsetHeight || 36;
    }
    document.documentElement.style.setProperty('--at-h', tickH + 'px');

    // 2) Push navbar below ticker
    navbar.style.top = tickH + 'px';

    // 3) Measure actual navbar height after it's positioned
    var navH = navbar.getBoundingClientRect().height || navbar.offsetHeight || 57;
    document.documentElement.style.setProperty('--nav-h', Math.ceil(navH) + 'px');

    // 4) Push content-wrapper below navbar + ticker
    if(cw){
      cw.style.marginTop = Math.ceil(tickH) + 'px';
    }
  }

  if(document.readyState==="loading"){
    document.addEventListener("DOMContentLoaded", applyAdminLayout);
  } else { applyAdminLayout(); }
  window.addEventListener("load", applyAdminLayout);
  window.addEventListener("resize", function(){ setTimeout(applyAdminLayout, 50); });
})();
</script>

@php $locationEnabled = \App\Models\SystemSetting::getGlobal('location_capture_enabled', '1'); @endphp
@if($locationEnabled !== '0')
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
@endif

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
            <div style="font-size:12px;opacity:0.8;">{{ auth()->user()->user_type ?? 'Admin' }}</div>
        </div>
        <a href="{{ route('admin.profile') }}" class="mobile-user-menu-item" onclick="closeMobileUserMenu()">
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
        <a href="#" onclick="event.preventDefault(); event.stopPropagation(); openMobileSidebar(this);" id="mobileMenuBtn" class="{{ request()->routeIs('') ? '' : '' }}">
            <i class="fas fa-bars"></i>Menu
        </a>
        <a href="{{ route('admin.clients.index') }}" class="{{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
            <i class="fas fa-users"></i>Clients
        </a>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
            <i class="fas fa-home"></i>Home
        </a>
        <a href="{{ route('admin.billing.index') }}" class="{{ request()->routeIs('admin.billing.*') ? 'active' : '' }}">
            <i class="fas fa-file-invoice-dollar"></i>Billing
        </a>
        <a href="{{ route('admin.support.tickets') }}" class="{{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
            <i class="fas fa-headset"></i>Support
        </a>
    </nav>

<script src="{{ asset('js/mobile-app.js') }}?v=1.2"></script>
<script>
function toggleMobileUserMenu() {
    var menu = document.getElementById('mobileUserMenu');
    var backdrop = document.getElementById('mobileUserMenuBackdrop');
    var isOpen = menu.classList.contains('show');
    if (isOpen) {
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
// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMobileUserMenu();
});

// Mobile sidebar toggle — reliable cross-AdminLTE approach
function openMobileSidebar(btn) {
    var body = document.body;
    var isOpen = body.classList.contains('sidebar-open');

    if (isOpen) {
        // Close sidebar — remove class + AdminLTE overlay
        body.classList.remove('sidebar-open');
        var ov = document.getElementById('sidebar-overlay');
        if (ov) ov.remove();
    } else {
        // Open sidebar — exactly what AdminLTE PushMenu does internally
        body.classList.add('sidebar-open');
        body.classList.remove('sidebar-collapse');

        // Remove old overlay if exists
        var old = document.getElementById('sidebar-overlay');
        if (old) old.remove();

        // Create AdminLTE-compatible overlay
        var overlay = document.createElement('div');
        overlay.id = 'sidebar-overlay';
        overlay.style.display = 'block';
        overlay.addEventListener('click', function() {
            body.classList.remove('sidebar-open');
            overlay.remove();
        });
        document.body.appendChild(overlay);
    }
}
})();
</script>

{{-- Auto Logout System --}}
<div id="autoLogoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:32px 28px; max-width:400px; width:90%; text-align:center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="width:60px; height:60px; background:#fff3cd; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
            <i class="fas fa-clock" style="color:#e67700; font-size:26px;"></i>
        </div>
        <h5 style="font-weight:700; color:#333; margin-bottom:8px;">Session Timeout Warning</h5>
        <p style="color:#666; font-size:13px; margin-bottom:16px;">Your session will expire soon due to inactivity.</p>
        <div style="font-size:36px; font-weight:800; color:#e67700; margin-bottom:20px;" id="autoLogoutCountdown">02:00</div>
        <div class="d-flex" style="gap:10px; justify-content:center;">
            <button onclick="autoLogoutExtend()" style="background:#28a745; color:#fff; border:none; border-radius:8px; padding:10px 24px; font-weight:600; font-size:14px; cursor:pointer;">
                <i class="fas fa-sync-alt mr-1"></i>Extend Session
            </button>
            <button onclick="autoLogoutNow()" style="background:#dc3545; color:#fff; border:none; border-radius:8px; padding:10px 24px; font-weight:600; font-size:14px; cursor:pointer;">
                <i class="fas fa-sign-out-alt mr-1"></i>Logout
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var TIMEOUT_MS   = 30 * 60 * 1000;  // 30 minutes
    var WARNING_MS   = 2  * 60 * 1000;  // warn at 2 min remaining
    var warningShown = false;
    var lastActivity = Date.now();
    var countdownInterval = null;
    var logoutForm = null;

    // Track user activity
    ['mousemove','keydown','click','scroll','touchstart'].forEach(function(evt) {
        document.addEventListener(evt, function() {
            if (!warningShown) {
                lastActivity = Date.now();
                keepAlive();
            }
        }, { passive: true });
    });

    // Throttled keep-alive ping (max once per 60s)
    var lastPing = 0;
    function keepAlive() {
        var now = Date.now();
        if (now - lastPing < 60000) return;
        lastPing = now;
        fetch('{{ route("session.keepalive") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
            }
        }).catch(function(){});
    }

    // Main timer check
    setInterval(function() {
        var idle = Date.now() - lastActivity;
        var remaining = TIMEOUT_MS - idle;

        if (remaining <= 0) {
            autoLogoutNow();
        } else if (remaining <= WARNING_MS && !warningShown) {
            showWarning(remaining);
        }
    }, 5000);

    function showWarning(remaining) {
        warningShown = true;
        var modal = document.getElementById('autoLogoutModal');
        modal.style.display = 'flex';
        var seconds = Math.floor(remaining / 1000);
        updateCountdown(seconds);
        countdownInterval = setInterval(function() {
            seconds--;
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                autoLogoutNow();
            } else {
                updateCountdown(seconds);
            }
        }, 1000);
    }

    function updateCountdown(sec) {
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        var el = document.getElementById('autoLogoutCountdown');
        if (el) el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    window.autoLogoutExtend = function() {
        clearInterval(countdownInterval);
        warningShown = false;
        lastActivity = Date.now();
        document.getElementById('autoLogoutModal').style.display = 'none';
        keepAlive();
    };

    window.autoLogoutNow = function() {
        clearInterval(countdownInterval);
        // Redirect directly to login to avoid 419 CSRF errors on expired sessions
        window.location.href = '/login';
    };
})();
</script>
</body>
</html>
