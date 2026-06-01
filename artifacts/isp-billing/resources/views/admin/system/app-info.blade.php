@extends('layouts.admin')

@section('title', 'App Info')

@section('content')
<div class="container-fluid">

    {{-- ── Header ── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-info-circle mr-2 text-primary"></i>Application Information</h4>
            <p class="text-muted mb-0">Complete technical overview of SOFTYNEX</p>
        </div>
        <span class="badge badge-success px-3 py-2" style="font-size:12px;">
            <i class="fas fa-check-circle mr-1"></i> {{ strtoupper($info['app']['environment']) }}
        </span>
    </div>

    {{-- ── 1. App Identity ── --}}
    <div class="card shadow-sm mb-4" style="border-left:4px solid #007bff;">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-1 text-center">
                    <div style="width:60px;height:60px;border-radius:14px;background:linear-gradient(135deg,#007bff,#6f42c1);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <i class="fas fa-wifi fa-2x text-white"></i>
                    </div>
                </div>
                <div class="col-md-8">
                    <h4 class="mb-1 font-weight-bold">{{ $info['app']['name'] }}</h4>
                    <p class="text-muted mb-1" style="font-size:13px;">{{ $info['app']['full_name'] }}</p>
                    <p class="mb-0" style="font-size:12px;color:#555;">{{ $info['app']['purpose'] }}</p>
                </div>
                <div class="col-md-3">
                    <div class="small">
                        <div class="mb-1"><i class="fas fa-globe mr-1 text-muted"></i> <a href="{{ $info['app']['url'] }}" target="_blank">{{ $info['app']['url'] }}</a></div>
                        <div class="mb-1"><i class="fas fa-clock mr-1 text-muted"></i> Timezone: {{ $info['app']['timezone'] ?: 'UTC' }}</div>
                        <div><i class="fas fa-flag mr-1 text-muted"></i> Locale: {{ $info['app']['locale'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 2. Backend Tech Stack ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header py-2">
            <i class="fas fa-server mr-1 text-primary"></i>
            <strong>Backend Technology Stack</strong>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($info['stack'] as $tech)
                <div class="col-6 col-md-4 col-lg-2 mb-3">
                    <div class="text-center p-3 rounded h-100" style="border:1px solid #dee2e6;transition:.2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='none'">
                        <div style="width:48px;height:48px;border-radius:50%;background:{{ $tech['color'] }}22;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                            <i class="{{ $tech['icon'] }}" style="font-size:22px;color:{{ $tech['color'] }};"></i>
                        </div>
                        <div class="font-weight-bold" style="font-size:14px;">{{ $tech['label'] }}</div>
                        <div class="badge mt-1" style="background:{{ $tech['color'] }}22;color:{{ $tech['color'] }};font-size:11px;">v{{ $tech['version'] }}</div>
                        <div class="text-muted mt-1" style="font-size:10px;">{{ $tech['desc'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── 3. Frontend & Design ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header py-2">
            <i class="fas fa-paint-brush mr-1 text-warning"></i>
            <strong>Frontend / Design System</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Library / Framework</th>
                            <th>Version</th>
                            <th>Role / Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($info['frontend'] as $fe)
                        <tr>
                            <td>
                                <span class="badge mr-2" style="background:{{ $fe['color'] }};width:10px;height:10px;padding:5px;border-radius:50%;display:inline-block;vertical-align:middle;"></span>
                                <strong>{{ $fe['label'] }}</strong>
                            </td>
                            <td><span class="badge badge-light border">{{ $fe['version'] }}</span></td>
                            <td class="text-muted small">{{ $fe['desc'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 4. Code Statistics + Key Packages ── --}}
    <div class="row mb-4">
        {{-- Code Stats --}}
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2">
                    <i class="fas fa-chart-bar mr-1 text-info"></i>
                    <strong>Codebase Statistics</strong>
                </div>
                <div class="card-body">
                    @foreach($info['stats'] as $stat)
                    <div class="d-flex align-items-center mb-2">
                        <div style="width:34px;height:34px;border-radius:8px;background:{{ $stat['color'] }}22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="{{ $stat['icon'] }}" style="color:{{ $stat['color'] }};font-size:14px;"></i>
                        </div>
                        <div class="flex-grow-1 mx-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small font-weight-bold">{{ $stat['label'] }}</span>
                                <span class="small font-weight-bold" style="color:{{ $stat['color'] }};">{{ $stat['count'] }}</span>
                            </div>
                            <div class="progress" style="height:6px;border-radius:3px;">
                                @php $barW = min(100, round($stat['count'] / 6)) @endphp
                                <div class="progress-bar" style="width:{{ $barW }}%;background:{{ $stat['color'] }};border-radius:3px;"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Key Packages --}}
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2">
                    <i class="fas fa-box-open mr-1 text-danger"></i>
                    <strong>Key PHP Packages (Composer)</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr><th>Package</th><th>Category</th><th>Purpose</th></tr>
                        </thead>
                        <tbody>
                            @foreach($info['packages'] as $pkg)
                            <tr>
                                <td style="font-size:11px;"><code>{{ $pkg['name'] }}</code></td>
                                <td>
                                    @php
                                        $catColors = ['Core'=>'primary','Auth'=>'success','PDF'=>'danger','API Auth'=>'warning','Cache'=>'info','Notification'=>'secondary','OLT'=>'dark','MikroTik'=>'primary'];
                                        $catColor  = $catColors[$pkg['category']] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $catColor }}">{{ $pkg['category'] }}</span>
                                </td>
                                <td class="text-muted" style="font-size:11px;">{{ $pkg['desc'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 5. Runtime Info ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header py-2">
            <i class="fas fa-tachometer-alt mr-1 text-success"></i>
            <strong>Runtime Information</strong>
            <span class="badge badge-light ml-2 small">Live</span>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($info['runtime'] as $item)
                <div class="col-6 col-md-3 mb-3">
                    <div class="d-flex align-items-center">
                        <div style="width:36px;height:36px;border-radius:8px;background:{{ $item['color'] }}22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="{{ $item['icon'] }}" style="color:{{ $item['color'] }};font-size:14px;"></i>
                        </div>
                        <div class="ml-2">
                            <div class="text-muted" style="font-size:10px;">{{ $item['label'] }}</div>
                            <div class="font-weight-bold" style="font-size:13px;">{{ $item['value'] }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── 6. Server Info ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header py-2">
            <i class="fas fa-hdd mr-1 text-secondary"></i>
            <strong>Server Configuration</strong>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($info['server'] as $item)
                <div class="col-6 col-md-3 mb-3">
                    <div class="d-flex align-items-center">
                        <div style="width:36px;height:36px;border-radius:8px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid #dee2e6;">
                            <i class="{{ $item['icon'] }}" style="color:#495057;font-size:14px;"></i>
                        </div>
                        <div class="ml-2">
                            <div class="text-muted" style="font-size:10px;">{{ $item['label'] }}</div>
                            <div class="font-weight-bold" style="font-size:13px;">{{ $item['value'] }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── 7. Architecture Summary ── --}}
    <div class="card shadow-sm mb-4" style="border-left:4px solid #28a745;">
        <div class="card-header py-2">
            <i class="fas fa-sitemap mr-1 text-success"></i>
            <strong>Architecture Overview</strong>
        </div>
        <div class="card-body">
            <div class="row text-center">
                @php
                $arch = [
                    ['icon'=>'fas fa-globe','label'=>'Browser / Client','desc'=>'Chrome, Firefox, Mobile','color'=>'#17a2b8'],
                    ['icon'=>'fas fa-arrow-right','label'=>'','desc'=>'HTTPS','color'=>'#adb5bd'],
                    ['icon'=>'fas fa-server','label'=>'Nginx 1.24','desc'=>'Web server + SSL','color'=>'#269539'],
                    ['icon'=>'fas fa-arrow-right','label'=>'','desc'=>'PHP-FPM','color'=>'#adb5bd'],
                    ['icon'=>'fab fa-php','label'=>'Laravel 12','desc'=>'MVC Framework','color'=>'#ff2d20'],
                    ['icon'=>'fas fa-arrows-alt-h','label'=>'','desc'=>'Query / Cache','color'=>'#adb5bd'],
                    ['icon'=>'fas fa-database','label'=>'PostgreSQL 16','desc'=>'Primary database','color'=>'#336791'],
                ];
                @endphp
                @foreach($arch as $node)
                    @if($node['label'] === '')
                    <div class="col-auto d-flex align-items-center">
                        <div>
                            <i class="{{ $node['icon'] }}" style="color:{{ $node['color'] }};font-size:18px;"></i>
                            <div style="font-size:9px;color:#adb5bd;">{{ $node['desc'] }}</div>
                        </div>
                    </div>
                    @else
                    <div class="col mb-2">
                        <div class="p-2 rounded" style="border:1px solid {{ $node['color'] }}44;background:{{ $node['color'] }}11;">
                            <i class="{{ $node['icon'] }}" style="color:{{ $node['color'] }};font-size:24px;"></i>
                            <div class="font-weight-bold mt-1" style="font-size:12px;">{{ $node['label'] }}</div>
                            <div class="text-muted" style="font-size:10px;">{{ $node['desc'] }}</div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
            <hr class="my-3">
            <div class="row text-center">
                @php
                $services = [
                    ['icon'=>'fab fa-telegram','label'=>'Telegram Bot','color'=>'#0088cc','desc'=>'Alert notifications'],
                    ['icon'=>'fas fa-sms','label'=>'BulkSMSPro','color'=>'#28a745','desc'=>'SMS gateway'],
                    ['icon'=>'fas fa-envelope','label'=>'Gmail SMTP','color'=>'#ea4335','desc'=>'Email alerts'],
                    ['icon'=>'fas fa-memory','label'=>'Redis 7','color'=>'#dc382d','desc'=>'Queue & cache'],
                    ['icon'=>'fas fa-network-wired','label'=>'MikroTik API','color'=>'#293a96','desc'=>'Router management'],
                    ['icon'=>'fas fa-broadcast-tower','label'=>'OLT / SNMP','color'=>'#fd7e14','desc'=>'OLT monitoring'],
                ];
                @endphp
                @foreach($services as $svc)
                <div class="col mb-2">
                    <div class="p-2 rounded" style="border:1px solid {{ $svc['color'] }}44;background:{{ $svc['color'] }}11;">
                        <i class="{{ $svc['icon'] }}" style="color:{{ $svc['color'] }};font-size:20px;"></i>
                        <div class="font-weight-bold mt-1" style="font-size:11px;">{{ $svc['label'] }}</div>
                        <div class="text-muted" style="font-size:9px;">{{ $svc['desc'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection
