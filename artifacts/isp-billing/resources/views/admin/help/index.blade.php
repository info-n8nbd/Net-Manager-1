@extends('layouts.admin')

@section('title', 'Help Center')

@section('breadcrumb')
    <li class="breadcrumb-item active">Help Center</li>
@endsection

@section('content')
<style>
    .help-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border-radius: 12px;
        padding: 40px 30px;
        margin-bottom: 30px;
        text-align: center;
    }
    .help-hero h2 {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .help-hero p {
        font-size: 16px;
        opacity: 0.9;
        margin-bottom: 20px;
    }
    .help-search-box {
        max-width: 500px;
        margin: 0 auto;
        position: relative;
    }
    .help-search-box input {
        border-radius: 25px;
        padding: 12px 20px 12px 45px;
        font-size: 16px;
        border: none;
        width: 100%;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .help-search-box .search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 16px;
    }

    .help-module-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        margin-bottom: 15px;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .help-module-card:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    .help-module-header {
        background: #f8f9fa;
        padding: 18px 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 15px;
        border-bottom: 1px solid transparent;
        transition: background 0.2s;
    }
    .help-module-header:hover {
        background: #eef1f5;
    }
    .help-module-header.active {
        background: #e8ecf4;
        border-bottom-color: #e0e0e0;
    }
    .help-module-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff;
        flex-shrink: 0;
    }
    .help-module-info {
        flex: 1;
    }
    .help-module-info h5 {
        margin: 0 0 4px;
        font-weight: 600;
        font-size: 16px;
        color: #2d3748;
    }
    .help-module-info p {
        margin: 0;
        font-size: 13px;
        color: #718096;
    }
    .help-module-toggle {
        font-size: 14px;
        color: #a0aec0;
        transition: transform 0.3s;
    }
    .help-module-header.active .help-module-toggle {
        transform: rotate(180deg);
    }

    .help-module-body {
        display: none;
        padding: 0;
    }
    .help-module-body.show {
        display: block;
    }
    .help-module-guide {
        padding: 15px 20px;
        background: #fafbfc;
        border-bottom: 1px solid #eee;
        font-size: 14px;
        color: #4a5568;
        line-height: 1.7;
    }

    .help-submenu-item {
        padding: 15px 20px 15px 35px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.15s;
    }
    .help-submenu-item:last-child {
        border-bottom: none;
    }
    .help-submenu-item:hover {
        background: #f7fafc;
    }
    .help-submenu-title {
        font-weight: 600;
        font-size: 14px;
        color: #2d3748;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .help-submenu-title i {
        color: #a0aec0;
        font-size: 12px;
        transition: transform 0.2s;
    }
    .help-submenu-item.active .help-submenu-title i {
        transform: rotate(90deg);
        color: #667eea;
    }
    .help-submenu-content {
        display: none;
        margin-top: 10px;
        padding: 15px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        color: #4a5568;
        line-height: 1.8;
    }
    .help-submenu-item.active .help-submenu-content {
        display: block;
    }
    .help-submenu-content strong {
        color: #2d3748;
    }

    .help-quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 30px;
    }
    .help-quick-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        text-decoration: none;
        color: #4a5568;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .help-quick-link:hover {
        border-color: #667eea;
        color: #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
        text-decoration: none;
    }
    .help-quick-link i {
        font-size: 18px;
        width: 24px;
        text-align: center;
    }

    .help-no-results {
        text-align: center;
        padding: 60px 20px;
        color: #a0aec0;
    }
    .help-no-results i {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }

    .module-bg-blue { background: #4299e1; }
    .module-bg-green { background: #48bb78; }
    .module-bg-orange { background: #ed8936; }
    .module-bg-purple { background: #9f7aea; }
    .module-bg-red { background: #fc8181; }
    .module-bg-teal { background: #38b2ac; }
    .module-bg-pink { background: #ed64a6; }
    .module-bg-indigo { background: #667eea; }
    .module-bg-cyan { background: #0bc5ea; }
    .module-bg-yellow { background: #ecc94b; }
</style>

<div class="help-hero">
    <h2><i class="fas fa-book-open mr-2"></i> Help Center</h2>
    <p>Complete user guide for your ISP Management System</p>
    <div class="help-search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="helpSearch" placeholder="Search any topic... (e.g., billing, payment, client)" value="{{ $search }}" autocomplete="off">
    </div>
</div>

@if(!$search)
<div class="mb-4">
    <h5 class="mb-3" style="font-weight:600; color:#2d3748;"><i class="fas fa-bolt text-warning mr-1"></i> Quick Navigation</h5>
    <div class="help-quick-links">
        @php
            $quickLinks = [
                ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'module' => 'dashboard'],
                ['icon' => 'fas fa-users', 'label' => 'Client', 'module' => 'client'],
                ['icon' => 'fas fa-money-check-alt', 'label' => 'Billing', 'module' => 'billing'],
                ['icon' => 'fas fa-chart-pie', 'label' => 'Reports', 'module' => 'reports'],
                ['icon' => 'fas fa-ethernet', 'label' => 'MikroTik', 'module' => 'mikrotik'],
                ['icon' => 'fas fa-sitemap', 'label' => 'Network', 'module' => 'network'],
                ['icon' => 'fas fa-id-card-alt', 'label' => 'HRM', 'module' => 'hrm'],
                ['icon' => 'fas fa-headset', 'label' => 'Support', 'module' => 'support'],
                ['icon' => 'fas fa-warehouse', 'label' => 'Inventory', 'module' => 'inventory'],
                ['icon' => 'fas fa-balance-scale', 'label' => 'Accounts', 'module' => 'accounts'],
                ['icon' => 'fas fa-paper-plane', 'label' => 'SMS', 'module' => 'sms'],
                ['icon' => 'fas fa-wrench', 'label' => 'Settings', 'module' => 'settings'],
            ];
        @endphp
        @foreach($quickLinks as $ql)
            <a href="#module-{{ $ql['module'] }}" class="help-quick-link" data-target="{{ $ql['module'] }}">
                <i class="{{ $ql['icon'] }}"></i>
                {{ $ql['label'] }}
            </a>
        @endforeach
    </div>
</div>
@endif

<div id="helpModules">
    @php
        $bgColors = ['module-bg-blue', 'module-bg-green', 'module-bg-orange', 'module-bg-purple', 'module-bg-red', 'module-bg-teal', 'module-bg-pink', 'module-bg-indigo', 'module-bg-cyan', 'module-bg-yellow'];
        $colorIndex = 0;
    @endphp

    @forelse($helpData as $key => $module)
        <div class="help-module-card" id="module-{{ $key }}">
            <div class="help-module-header" data-toggle="module" data-target="#module-body-{{ $key }}">
                <div class="help-module-icon {{ $bgColors[$colorIndex % count($bgColors)] }}">
                    <i class="{{ $module['icon'] }}"></i>
                </div>
                <div class="help-module-info">
                    <h5>{{ $module['title'] }}</h5>
                    <p>{{ $module['description'] }}</p>
                </div>
                <span class="help-module-toggle"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="help-module-body" id="module-body-{{ $key }}">
                @if(!empty($module['guide']))
                    <div class="help-module-guide">
                        {!! nl2br(e($module['guide'])) !!}
                    </div>
                @endif
                @foreach($module['submenus'] ?? [] as $route => $submenu)
                    <div class="help-submenu-item" data-toggle="submenu">
                        <div class="help-submenu-title">
                            <i class="fas fa-chevron-right"></i>
                            {{ $submenu['title'] }}
                        </div>
                        <div class="help-submenu-content">
                            {!! nl2br(e($submenu['content'])) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @php $colorIndex++; @endphp
    @empty
        <div class="help-no-results">
            <i class="fas fa-search"></i>
            <h5>No results found for "{{ $search }}"</h5>
            <p>Try a different search term or <a href="{{ route('admin.help.index') }}">view all</a></p>
        </div>
    @endforelse
</div>

@endsection

@push('scripts')
<script>
$(function() {
    // Module accordion toggle
    $(document).on('click', '.help-module-header', function() {
        var $header = $(this);
        var $body = $($header.data('target'));

        $header.toggleClass('active');
        $body.toggleClass('show');
    });

    // Submenu toggle
    $(document).on('click', '.help-submenu-item', function(e) {
        $(this).toggleClass('active');
    });

    // Quick link scroll & open
    $(document).on('click', '.help-quick-link', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var $module = $('#module-' + target);
        if ($module.length) {
            var $header = $module.find('.help-module-header');
            var $body = $module.find('.help-module-body');
            if (!$header.hasClass('active')) {
                $header.addClass('active');
                $body.addClass('show');
            }
            $('html, body').animate({
                scrollTop: $module.offset().top - 80
            }, 400);
        }
    });

    // Live search
    var searchTimer;
    $('#helpSearch').on('input', function() {
        var query = $(this).val().trim();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (query.length > 0) {
                window.location.href = '{{ route("admin.help.index") }}?search=' + encodeURIComponent(query);
            } else {
                window.location.href = '{{ route("admin.help.index") }}';
            }
        }, 600);
    });

    // Auto-open all modules if search is active
    @if($search)
        $('.help-module-header').addClass('active');
        $('.help-module-body').addClass('show');
        $('.help-submenu-item').addClass('active');
    @endif
});
</script>
@endpush
