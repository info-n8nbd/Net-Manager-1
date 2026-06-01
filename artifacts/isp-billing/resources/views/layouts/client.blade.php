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
    <style>
        * { font-family: 'Poppins', 'Noto Sans Bengali', sans-serif; }
        body { background: #f0f4f8; }
        
        .brand-link {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            padding: 12px 15px !important;
            border-bottom: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .brand-content { text-align: center; }
        .brand-logo-icon {
            width: 160px;
            height: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3px;
        }
        .brand-logo-icon img { max-width: 100%; height: auto; max-height: 55px; }
        .brand-logo-icon i { font-size: 18px; color: #1e3c72; }
        .brand-text {
            display: block;
            color: white !important;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 1px;
        }
        .brand-tagline {
            display: block;
            color: rgba(255,255,255,0.7);
            font-size: 8px;
            letter-spacing: 0.5px;
        }
        
        .main-header.navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            border: none !important;
            box-shadow: 0 2px 10px rgba(30,60,114,0.3);
            min-height: 50px;
        }
        .main-header .nav-link { 
            color: rgba(255,255,255,0.9) !important; 
            font-weight: 500;
            padding: 8px 12px !important;
            font-size: 14px;
        }
        .main-header .nav-link:hover { 
            color: white !important;
            background: rgba(255,255,255,0.1) !important;
            border-radius: 6px;
        }
        .main-header .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            border-radius: 10px;
        }
        
        .main-sidebar {
            background: white !important;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }
        .sidebar {
            padding-bottom: 100px !important;
        }
        .main-sidebar, .main-sidebar::before { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important; }
        
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
            font-size: 12px;
            padding: 8px 12px;
            white-space: nowrap;
        }
        .nav-sidebar .nav-link .nav-icon {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        .nav-sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
        .nav-sidebar .nav-link:hover .nav-icon {
            color: #ffffff !important;
        }
        .nav-sidebar .nav-link.active .nav-icon { color: white !important; }
        
        .nav-header {
            color: rgba(255, 255, 255, 0.5) !important;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 12px 16px 6px !important;
        }
        
        .user-panel { border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important; }
        .user-panel .info a { color: rgba(255, 255, 255, 0.9) !important; font-weight: 600; font-size: 13px; }
        
        .content-wrapper { background: #f0f4f8 !important; }
        .content-header { padding: 5px 0 !important; }
        .content-header h1 { font-size: 15px !important; font-weight: 700; }
        section.content { padding-top: 4px !important; }
        section.content .container-fluid { padding-left: 12px !important; padding-right: 12px !important; }
        

        
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
            padding: 12px 15px;
        }
        
        .main-footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            padding: 10px 15px;
            font-size: 12px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            border-radius: 8px;
        }
        .btn-primary:hover {
            background: #163057;
        }

        .portal-badge {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 600;
            color: white;
            margin-left: 8px;
        }

        .status-badge-online { background: #28a745; }
        .status-badge-offline { background: #dc3545; }

        /* ── Global: prevent text overflow everywhere ── */
        * { box-sizing: border-box; }
        img { max-width: 100%; }
        body { overflow-x: hidden; }
        .db-stat-val, .db-ph-title, .db-stat-lbl {
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        /* Fix: sidebar margin-left compresses content on mobile */
        @media (max-width: 991.98px) {
            .content-wrapper,
            .main-footer,
            .main-header {
                margin-left: 0 !important;
            }
            .main-sidebar {
                transform: translateX(-250px) !important;
                transition: transform 0.3s ease !important;
            }
            body.sidebar-open .main-sidebar {
                transform: translateX(0) !important;
            }
        }

        @media (max-width: 767.98px) {
            /* Layout */
            .content-header { padding: 6px 0 !important; }
            .content-header h1 { font-size: 15px !important; }
            section.content { padding-top: 2px !important; }
            section.content .container-fluid { padding-left: 8px !important; padding-right: 8px !important; }
            .content-wrapper { padding-bottom: 75px !important; }
            .main-footer { display: none; }

            /* Cards */
            .card { border-radius: 10px; margin-bottom: 8px; }
            .card-body { padding: 10px; }
            .card-header { padding: 9px 10px; font-size: 13px; }

            /* Stat cards — prevent icon+text overflow on small screens */
            .db-stat { padding: 8px 8px !important; flex-wrap: wrap; gap: 4px; }
            .db-stat-icon { width: 26px; height: 26px; font-size: 11px; margin-right: 6px; flex-shrink: 0; }
            .db-stat-val { font-size: 13px !important; line-height: 1.2; }
            .db-stat-lbl { font-size: 9px !important; }

            /* Quick actions */
            .db-qa { padding: 8px 2px; min-height: 50px; }
            .db-qa i { font-size: 15px; margin-bottom: 2px; }
            .db-qa span { font-size: 9px; }

            /* Panels */
            .db-ph { padding: 7px 10px; }
            .db-ph-title { font-size: 11px; }
            .db-ph-link { font-size: 10px; }
            .db-pr { padding: 6px 10px; }

            /* Panels: keep border-radius clip but allow text to wrap */
            .db-panel { overflow: visible !important; border-radius: 10px; }
            .u-panel { overflow: visible !important; border-radius: 10px; }

            /* Buttons */
            .btn { padding: 7px 10px; font-size: 12px; }
            .btn-pay-now { font-size: 10px; padding: 4px 8px; }
            .bkash-btn-sm { font-size: 10px; padding: 5px 9px; }
            .gw-btn { padding: 8px 4px; font-size: 10px; }

            /* Status banner */
            .status-card { padding: 8px 10px !important; border-radius: 10px !important; }

            /* Tables */
            .table-responsive { margin: 0 -8px; }
            .table td, .table th { font-size: 11px; padding: 6px 8px; }

            /* Bills */
            .bill-card { padding: 9px 10px; }
            .bills-grid { display: block !important; }

            /* Tickets */
            .ticket-card { padding: 9px 10px; }

            /* Bottom nav — app-like */
            .mobile-bottom-nav {
                position: fixed;
                bottom: 0; left: 0; right: 0;
                background: #fff;
                display: flex;
                justify-content: space-around;
                align-items: stretch;
                padding: 0;
                padding-bottom: env(safe-area-inset-bottom);
                box-shadow: 0 -1px 0 #e2e8f0, 0 -4px 15px rgba(0,0,0,0.08);
                z-index: 1035;
                height: 60px;
            }
            .mobile-bottom-nav a {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                flex: 1;
                color: #94a3b8;
                font-size: 9px;
                font-weight: 600;
                text-decoration: none;
                padding: 6px 2px 4px;
                border-top: 2px solid transparent;
                transition: all 0.2s;
                letter-spacing: 0.2px;
            }
            .mobile-bottom-nav a i { font-size: 19px; margin-bottom: 2px; }
            .mobile-bottom-nav a.active {
                color: #1e3c72;
                border-top-color: #1e3c72;
                background: rgba(30,60,114,0.04);
            }

            /* Modals on mobile */
            .modal-dialog { margin: 8px; }
            .modal-dialog.modal-dialog-centered { margin: 8px auto; }
        }

        @media (max-width: 374px) {
            /* Extra small phones (< 375px) */
            .db-stat-val { font-size: 11px !important; }
            .db-stat-icon { width: 22px; height: 22px; font-size: 10px; }
            .mobile-bottom-nav a { font-size: 8px; }
            .mobile-bottom-nav a i { font-size: 16px; }
        }

        @media (min-width: 768px) {
            .mobile-bottom-nav { display: none; }
        }

        .status-card {
            border-radius: 15px;
            padding: 15px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .status-card.online { background: #28a745; }
        .status-card.offline { background: #dc3545; }
        .status-card.inactive { background: #dc3545; }
        .status-card::after {
            content: '';
            position: absolute;
            right: -20px;
            top: -20px;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .info-card-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-decoration: none;
            color: #334155;
            transition: all 0.3s;
            min-height: 80px;
        }
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            color: #1e3c72;
        }
        .quick-action-btn i { font-size: 22px; margin-bottom: 8px; }
        .quick-action-btn span { font-size: 11px; font-weight: 500; }

        .bkash-btn {
            background: #e2136e;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .bkash-btn:hover { background: #c91b60; color: white; }
        .bkash-btn img { height: 24px; }

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
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
@include('partials.impersonate-banner')
<div class="wrapper">
    @php
        $__clientUser = auth()->user();
        $__clientRecord = $__clientUser ? $__clientUser->client : null;
        $__companyId = $__clientRecord ? $__clientRecord->company_id : null;
        $__tickerText = \App\Models\SystemSetting::get('ticker_text', '');
        $__dbNotices = \DB::table('announcements')
            ->where('is_active', true)
            ->where(function($q){ $q->where('target','all')->orWhere('target','clients'); })
            ->where(function($q){ $q->whereNull('end_date')->orWhere('end_date','>=',now()); })
            ->where(function($q){ $q->whereNull('start_date')->orWhere('start_date','<=',now()); })
            ->when($__companyId, function($q) use($__companyId){ $q->where(function($q2) use($__companyId){ $q2->whereNull('company_id')->orWhere('company_id',$__companyId); }); })
            ->orderByDesc('created_at')->pluck('content')->toArray();
        $__clientMessages = array_values(array_filter(array_merge($__dbNotices, $__tickerText ? [$__tickerText] : [])));
    @endphp
    @if(count($__clientMessages) > 0)
        @include('partials.announcement-ticker', [
            'messages' => $__clientMessages,
            'type'     => 'info',
            'speed'    => 'normal',
        ])
    @endif
    <nav class="main-header navbar navbar-expand navbar-dark">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="portal-badge"><i class="fas fa-user mr-1"></i> Client Portal</span>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('client.notifications') }}">
                    <i class="fas fa-bell"></i>
                    @php $unreadCount = \DB::table('notification_queue')->where('recipient_type', 'client')->where('recipient_id', auth()->user()->client->id ?? 0)->where('status', 'pending')->count(); @endphp
                    @if($unreadCount > 0)
                    <span class="badge badge-warning navbar-badge">{{ $unreadCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('client.profile') }}" class="dropdown-item">
                        <i class="fas fa-user-circle mr-2"></i> Profile
                    </a>
                    <a href="{{ route('client.settings') }}" class="dropdown-item">
                        <i class="fas fa-cog mr-2"></i> Settings
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
        <a href="{{ route('client.dashboard') }}" class="brand-link">
            <div class="brand-content">
                <div class="brand-logo-icon"><img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="SOFTYNEX Logo"></div>
                <span class="brand-text">{{ $companyName ?? 'SOFTYNEX' }}</span>
                <span class="brand-tagline">{{ $companyTagline ?? 'Connecting You to the Future' }}</span>
            </div>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 35px; height: 35px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; font-weight: 600; font-size: 12px;">
                        {{ strtoupper(substr(Auth::user()->name ?? 'C', 0, 1)) }}
                    </div>
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{ Auth::user()->name ?? 'Client' }}</a>
                    @php 
                        $userClient = auth()->user()->client ?? null;
                        $clientStatus = $userClient ? ($userClient->connection_status ?? 'offline') : 'offline';
                    @endphp
                    <small style="color: {{ $clientStatus == 'online' ? '#4ade80' : '#f87171' }}"><i class="fas fa-circle" style="font-size: 7px;"></i> {{ ucfirst($clientStatus) }}</small>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                    <li class="nav-item">
                        <a href="{{ route('client.dashboard') }}" class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-header">CONNECTION</li>

                    @php
                        $showBills = \App\Models\SystemSetting::get('client_portal_view_bills', '1') == '1';
                        $showPayment = \App\Models\SystemSetting::get('client_portal_pay_online', '1') == '1';
                        $showTickets = \App\Models\SystemSetting::get('client_portal_create_tickets', '1') == '1';
                        $showPackageChange = \App\Models\SystemSetting::get('client_portal_change_package', '0') == '1';
                    @endphp
                    @if($showBills || $showPayment)
                    <li class="nav-header">BILLING</li>
                    @endif

                    @if($showPayment)
                    <li class="nav-item">
                        <a href="{{ route('client.pay-now') }}" class="nav-link {{ request()->routeIs('client.pay-now') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>Pay Now (bKash)</p>
                        </a>
                    </li>
                    @endif
                    @if($showTickets)
                    <li class="nav-header">SUPPORT</li>
                    <li class="nav-item">
                        <a href="{{ route('client.tickets') }}" class="nav-link {{ request()->routeIs('client.tickets*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-headset"></i>
                            <p>Support Tickets</p>
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a href="{{ route('client.help') }}" class="nav-link {{ request()->routeIs('client.help') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-question-circle"></i>
                            <p>Self-Help Guide</p>
                        </a>
                    </li>
                    @include("components.media-servers-sidebar")
                    <li class="nav-header">ACCOUNT</li>
                    @if($showPackageChange)
                    <li class="nav-item">
                        <a href="{{ route('client.package-change') }}" class="nav-link {{ request()->routeIs('client.package-change') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-exchange-alt"></i>
                            <p>Change Package</p>
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a href="{{ route('client.profile') }}" class="nav-link {{ request()->routeIs('client.profile') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p>My Profile</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('client.settings') }}" class="nav-link {{ request()->routeIs('client.settings') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Settings</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">

            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-12">
                        <h1 class="m-0" style="color: #1e3c72; font-weight: 600; font-size: 20px;">@yield('title', 'Dashboard')</h1>
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

    <nav class="mobile-bottom-nav d-md-none">
        <a href="{{ route('client.dashboard') }}" class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('client.tickets') }}" class="{{ request()->routeIs('client.tickets*') ? 'active' : '' }}">
            <i class="fas fa-headset"></i>
            <span>Support</span>
        </a>
        <a href="{{ route('client.pay-now') }}" class="{{ request()->routeIs('client.pay-now') ? 'active' : '' }}">
            <i class="fas fa-money-bill-wave"></i>
            <span>Pay</span>
        </a>
        <a href="{{ route('client.self-help') }}" class="{{ request()->routeIs('client.self-help') ? 'active' : '' }}">
            <i class="fas fa-book-open"></i>
            <span>Guide</span>
        </a>
        <a href="{{ route('client.profile') }}" class="{{ request()->routeIs('client.profile') ? 'active' : '' }}">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

// Close sidebar when clicking outside on mobile
$(document).on('click touchstart', function(e) {
    if ($(window).width() <= 991) {
        var sidebar = $('.main-sidebar');
        var toggleBtn = $('[data-widget="pushmenu"]');
        
        if ($('body').hasClass('sidebar-open')) {
            if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 && 
                !toggleBtn.is(e.target) && toggleBtn.has(e.target).length === 0) {
                $('body').removeClass('sidebar-open').addClass('sidebar-collapse');
            }
        }
    }
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
<script src="/js/new-tab-links.js?v={{ time() }}"></script>
@include('components.flatpickr-datepicker')
<script src="{{ asset('js/mobile-app.js') }}?v=1.2"></script>
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
