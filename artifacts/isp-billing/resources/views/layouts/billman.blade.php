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
            padding: 12px 20px !important;
            border-bottom: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .brand-content { text-align: center; }
        .brand-logo-icon {
            width: 180px; height: auto;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 5px;
        }
        .brand-logo-icon img { max-width: 100%; height: auto; max-height: 60px; }
        .brand-text {
            display: block; color: white !important;
            font-weight: 700; font-size: 14px; letter-spacing: 1px;
        }
        .brand-tagline {
            display: block; color: rgba(255,255,255,0.7);
            font-size: 9px; letter-spacing: 0.5px;
        }
        .main-header.navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important; border: none !important;
            box-shadow: 0 2px 10px rgba(30,60,114,0.3);
        }
        .main-header .nav-link { color: rgba(255,255,255,0.9) !important; font-weight: 500; padding: 10px 15px !important; }
        .main-header .nav-link:hover { color: white !important; background: rgba(255,255,255,0.1) !important; border-radius: 6px; }
        .main-header .dropdown-menu { border: none; box-shadow: 0 5px 25px rgba(0,0,0,0.15); border-radius: 8px; }
        .main-sidebar, .main-sidebar::before { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important; }
        .sidebar { padding-bottom: 100px !important; }
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
        .nav-sidebar > .nav-item > .nav-link {
            color: rgba(255,255,255,0.8) !important;
            border-radius: 8px !important;
            margin: 2px 8px !important;
            padding: 10px 12px !important;
        }
        .nav-sidebar > .nav-item > .nav-link:hover {
            background: rgba(255,255,255,0.1) !important;
            color: white !important;
        }
        .nav-sidebar .nav-header {
            color: rgba(255,255,255,0.5) !important;
            text-transform: uppercase;
            font-size: 11px;
            padding: 15px 15px 5px;
        }
        .portal-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 600; color: white;
            margin-left: 10px;
        }
        .stat-box { min-height: 90px; display: flex; flex-direction: column; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 28px; font-weight: bold; line-height: 1.2; }
        .stat-label { font-size: 13px; opacity: 0.9; margin-top: 4px; }
        @media (max-width: 767px) {
            .stat-box { min-height: 55px; padding: 8px 5px !important; box-shadow: none; }
            .stat-number { font-size: 16px; }
            .stat-label { font-size: 10px; margin-top: 2px; }
        }
        .search-hero {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 16px;
            padding: 30px;
            color: white;
            margin-bottom: 20px;
        }
        .search-hero input {
            font-size: 18px;
            padding: 15px 20px;
            border-radius: 12px;
            border: none;
        }
        .search-hero input::placeholder { color: #94a3b8; }

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
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
@include('partials.impersonate-banner')
<div class="wrapper">
    @php
        $__noticeEnabled = \App\Models\SystemSetting::get('notice_enabled', '0');
        $__noticeText    = \App\Models\SystemSetting::get('notice_text', '');
        $__noticeType    = \App\Models\SystemSetting::get('notice_type', 'info');
        $__noticeSpeed   = \App\Models\SystemSetting::get('notice_speed', 'normal');
        $__noticeMessages = array_values(array_filter(array_map('trim', explode("\n", $__noticeText ?? ''))));
    @endphp
    @if($__noticeEnabled == '1' && count($__noticeMessages) > 0)
        @include('partials.announcement-ticker', [
            'messages' => $__noticeMessages,
            'type'     => $__noticeType,
            'speed'    => $__noticeSpeed,
        ])
    @endif
    <nav class="main-header navbar navbar-expand navbar-dark">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('billman.dashboard') }}" class="nav-link">
                    <i class="fas fa-home mr-1"></i> Home
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="portal-badge"><i class="fas fa-file-invoice-dollar mr-1"></i> Billman Portal</span>
            </li>
        </ul>

        <div class="global-search-container mx-auto d-none d-md-block" style="width: 100%; max-width: 400px; position: relative;">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" style="background: rgba(255,255,255,0.15); border: none; color: rgba(255,255,255,0.8);"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="globalQuickSearch" class="form-control" placeholder="Search client (Name, ID, Phone)..." style="background: rgba(255,255,255,0.15); border: none; color: white; border-radius: 0 25px 25px 0;">
            </div>
            <div id="searchResults" class="search-results-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); z-index: 1050; max-height: 400px; overflow-y: auto; margin-top: 8px;"></div>
        </div>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user mr-1"></i> {{ auth()->user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('billman.profile') }}" class="dropdown-item">
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
        <a href="{{ route('billman.dashboard') }}" class="brand-link">
            <div class="brand-content">
                <div class="brand-logo-icon">
                    <img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="Logo">
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
                    <small class="text-muted">Bill Collector</small>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('billman.dashboard') }}" class="nav-link {{ request()->routeIs('billman.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">BILLING</li>
                    <li class="nav-item">
                        <a href="{{ route('billman.collection-report') }}" class="nav-link {{ request()->routeIs('billman.collection-report') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>My Collection</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('billman.submission-history') }}" class="nav-link {{ request()->routeIs('billman.submission-history') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hand-holding-usd"></i>
                            <p>Submit to Admin</p>
                        </a>
                    </li>

                    @include("components.media-servers-sidebar")
                    <li class="nav-header">ACCOUNT</li>
                    <li class="nav-item">
                        <a href="{{ route('billman.profile') }}" class="nav-link {{ request()->routeIs('billman.profile') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-circle"></i>
                            <p>My Profile</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('billman.attendance') }}" class="nav-link {{ request()->routeIs('billman.attendance*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-camera"></i>
                            <p>My Attendance</p>
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
                            <li class="breadcrumb-item"><a href="{{ route('billman.dashboard') }}">Home</a></li>
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
        if (query.length < 2) { searchResults.hide().empty(); return; }
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '{{ route("billman.api.quick-search") }}',
                data: { q: query },
                success: function(data) {
                    if (data.length === 0) {
                        searchResults.html('<div class="p-4 text-center text-muted"><i class="fas fa-search fa-2x mb-2"></i><p class="mb-0">No clients found</p></div>').show();
                        return;
                    }
                    let html = '<div class="p-2">';
                    data.forEach(function(client) {
                        const statusColor = client.status === 'active' ? '#10b981' : '#ef4444';
                        html += `
                            <a href="${client.url}" class="search-result-item d-flex align-items-center p-3" style="text-decoration: none; color: inherit; border-radius: 10px; transition: all 0.2s;">
                                <div class="d-flex align-items-center justify-content-center rounded-circle text-white mr-3" style="width: 45px; height: 45px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); font-size: 16px; font-weight: 600;">
                                    ${client.name.charAt(0).toUpperCase()}
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center">
                                        <strong style="color: #1e3c72;">${client.name}</strong>
                                        <span class="badge ml-2" style="background: ${statusColor}; color: white; font-size: 10px;">${client.status}</span>
                                    </div>
                                    <div class="small text-muted">
                                        <span class="mr-3"><i class="fas fa-id-badge mr-1"></i>${client.client_id}</span>
                                        <span class="mr-3"><i class="fas fa-phone mr-1"></i>${client.phone}</span>
                                        <span><i class="fas fa-map-marker-alt mr-1"></i>${client.zone}</span>
                                    </div>
                                    ${client.due_amount > 0 ? '<div class="small text-danger"><i class="fas fa-exclamation-circle mr-1"></i>Due: ৳' + client.due_amount.toLocaleString() + '</div>' : '<div class="small text-success"><i class="fas fa-check-circle mr-1"></i>No Due</div>'}
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

    searchInput.on('focus', function() { if (searchResults.children().length > 0) searchResults.show(); });
    $(document).on('click', function(e) { if (!$(e.target).closest('.global-search-container').length) searchResults.hide(); });
    $(document).on('mouseenter', '.search-result-item', function() { $(this).css('background', '#f0f4f8'); })
               .on('mouseleave', '.search-result-item', function() { $(this).css('background', 'transparent'); });

    // Also handle the dashboard search
    const dashSearch = $('#dashboardSearch');
    const dashResults = $('#dashboardSearchResults');
    if (dashSearch.length) {
        dashSearch.on('input', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val().trim();
            if (query.length < 2) { dashResults.hide().empty(); return; }
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: '{{ route("billman.api.quick-search") }}',
                    data: { q: query },
                    success: function(data) {
                        if (data.length === 0) {
                            dashResults.html('<div class="p-4 text-center text-muted"><i class="fas fa-search fa-2x mb-2 d-block"></i>No clients found for this search</div>').show();
                            return;
                        }
                        let html = '';
                        data.forEach(function(client) {
                            const statusColor = client.status === 'active' ? '#10b981' : '#ef4444';
                            html += `
                                <a href="${client.url}" class="d-flex align-items-center p-3 border-bottom" style="text-decoration: none; color: inherit; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle text-white mr-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); font-size: 18px; font-weight: 600; flex-shrink: 0;">
                                        ${client.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <strong style="color: #1e3c72; font-size: 15px;">${client.name}</strong>
                                            <span class="badge ml-2" style="background: ${statusColor}; color: white;">${client.status}</span>
                                        </div>
                                        <div class="text-muted" style="font-size: 13px;">
                                            <span class="mr-3"><i class="fas fa-id-badge mr-1"></i>${client.client_id}</span>
                                            <span class="mr-3"><i class="fas fa-phone mr-1"></i>${client.phone}</span>
                                            <span><i class="fas fa-map-marker-alt mr-1"></i>${client.zone}</span>
                                        </div>
                                        <div style="font-size: 13px;">
                                            ${client.due_amount > 0 ? '<span class="text-danger font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i>Due: ৳' + client.due_amount.toLocaleString() + '</span>' : '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>No Due</span>'}
                                            <span class="text-muted ml-2">| ${client.package}</span>
                                        </div>
                                    </div>
                                    <div class="text-primary"><i class="fas fa-arrow-right fa-lg"></i></div>
                                </a>
                            `;
                        });
                        dashResults.html(html).show();
                    }
                });
            }, 200);
        });
    }
});
</script>

<style>
#globalQuickSearch::placeholder { color: rgba(255,255,255,0.6); }
#globalQuickSearch:focus { background: rgba(255,255,255,0.25) !important; box-shadow: 0 0 0 3px rgba(255,255,255,0.1); outline: none; }
.search-results-dropdown::-webkit-scrollbar { width: 6px; }
.search-results-dropdown::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
</style>

<script>
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
@include('components.flatpickr-datepicker')

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="mobile-bottom-bar">
        <a href="{{ route('billman.dashboard') }}" class="{{ request()->routeIs('billman.dashboard') ? 'active' : '' }}">
            <i class="fas fa-home"></i>Home
        </a>
        <a href="{{ route('billman.clients') }}" class="{{ request()->routeIs('billman.clients*') ? 'active' : '' }}">
            <i class="fas fa-users"></i>Clients
        </a>
        <a href="{{ route('billman.attendance') }}" class="{{ request()->routeIs('billman.attendance*') ? 'active' : '' }}">
            <i class="fas fa-user-check"></i>Attendance
        </a>
        <a href="{{ route('billman.profile') }}" class="{{ request()->routeIs('billman.profile*') ? 'active' : '' }}">
            <i class="fas fa-user"></i>Profile
        </a>
        <a href="#" onclick="event.preventDefault(); event.stopPropagation(); (function(){var b=document.body,isOpen=b.classList.contains('sidebar-open');if(isOpen){b.classList.remove('sidebar-open');var ov=document.getElementById('sidebar-overlay');if(ov)ov.remove();}else{b.classList.add('sidebar-open');b.classList.remove('sidebar-collapse');var old=document.getElementById('sidebar-overlay');if(old)old.remove();var ol=document.createElement('div');ol.id='sidebar-overlay';ol.style.display='block';ol.addEventListener('click',function(){b.classList.remove('sidebar-open');ol.remove();});document.body.appendChild(ol);}})();">
            <i class="fas fa-bars"></i>Menu
        </a>
    </nav>

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
