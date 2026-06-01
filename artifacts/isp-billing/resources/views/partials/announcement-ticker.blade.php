@php
    $tickerType = $type ?? 'info';
    $tickerSpeed = $speed ?? 'normal';
    $tickerMessages = $messages ?? [];
    $tickerId = 'aticker-' . substr(md5(uniqid()), 0, 8);

    $colorMap = [
        'info'     => ['bg' => 'linear-gradient(135deg, #1565c0, #0d47a1)', 'border' => '#42a5f5', 'dot' => '#90caf9', 'icon' => 'fas fa-bell'],
        'warning'  => ['bg' => 'linear-gradient(135deg, #e65100, #bf360c)', 'border' => '#ffa726', 'dot' => '#ffcc02', 'icon' => 'fas fa-exclamation-triangle'],
        'critical' => ['bg' => 'linear-gradient(135deg, #c62828, #b71c1c)', 'border' => '#ef5350', 'dot' => '#ff8a80', 'icon' => 'fas fa-exclamation-circle'],
    ];
    $c = $colorMap[$tickerType] ?? $colorMap['info'];

    $speedMap = ['slow' => 22, 'normal' => 14, 'fast' => 8];
    $duration = $speedMap[$tickerSpeed] ?? 25;
@endphp

@if(count($tickerMessages) > 0)
<style>
    /*
     * --at-h  : ticker height
     * --nav-h : actual navbar height (set precisely by JS in admin layout).
     *           57px = AdminLTE default, JS overrides with measured value.
     */
    :root { --at-h: 36px; --nav-h: 57px; }

    /*
     * Ticker sits in the header area only (right of the sidebar).
     * The sidebar itself is NOT moved — it keeps top:0 so there is NO gap.
     * The navbar is pushed down by --at-h.
     * The content-wrapper top margin = navbar height + ticker height.
     */
    .at-ticker {
        position: fixed;
        top: 0;
        left: 250px;          /* align with sidebar width */
        right: 0;
        z-index: 1040;
        height: 36px !important;
        color: #fff;
        overflow: hidden;
        background: {{ $c['bg'] }};
        border-bottom: 2px solid {{ $c['border'] }};
        box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        transition: left .3s ease;
        display: flex;
        align-items: center;
    }

    /* Follow sidebar collapse state */
    .sidebar-collapse .at-ticker { left: 4.6rem; }

    /* Navbar + content positioning handled by applyAdminLayout() JS in layout */

    .at-label {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 0 14px;
        height: 100%;
        background: rgba(255,255,255,0.12);
        border-right: 1px solid rgba(255,255,255,0.2);
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .at-pulse {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: {{ $c['dot'] }};
        box-shadow: 0 0 6px {{ $c['dot'] }};
        animation: at-blink 1.5s ease infinite;
        flex-shrink: 0;
    }
    @keyframes at-blink {
        0%, 100% { opacity: 1; }
        50% { opacity: .2; }
    }

    .at-track {
        flex: 1;
        overflow: hidden;
        height: 100%;
        position: relative;
    }
    .at-track::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 40px;
        background: linear-gradient(90deg, rgba(13,71,161,0.8), transparent);
        z-index: 2;
        pointer-events: none;
    }
    .at-track::after {
        content: '';
        position: absolute;
        right: 0; top: 0; bottom: 0;
        width: 40px;
        background: linear-gradient(270deg, rgba(13,71,161,0.8), transparent);
        z-index: 2;
        pointer-events: none;
    }

    .at-rail {
        display: flex;
        align-items: center;
        height: 100%;
        white-space: nowrap;
        animation: at-scroll {{ $duration }}s linear infinite;
        will-change: transform;
    }
    .at-rail:hover { animation-play-state: paused; }

    @keyframes at-scroll {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }

    .at-item {
        display: inline-flex;
        align-items: center;
        padding: 0 28px;
        font-size: 13px;
        font-weight: 500;
        letter-spacing: .15px;
    }
    .at-sep {
        display: inline-block;
        width: 5px; height: 5px;
        border-radius: 50%;
        background: rgba(255,255,255,0.4);
        flex-shrink: 0;
        margin: 0 4px;
    }

    @media (max-width: 768px) {
        :root { --at-h: 36px; }
        .at-ticker { left: 0 !important; }
        .sidebar-collapse .at-ticker { left: 0 !important; }
        .at-label span.at-label-text { display: none; }
        .at-item { font-size: 13px; padding: 0 18px; }
    }
</style>

<div class="at-ticker" id="{{ $tickerId }}">
    <div class="at-label">
        <span class="at-pulse"></span>
        <i class="{{ $c['icon'] }}" style="font-size:12px;"></i>
        <span class="at-label-text">NOTICE</span>
    </div>
    <div class="at-track">
        <div class="at-rail">
            {{-- First copy --}}
            @foreach($tickerMessages as $i => $msg)
                @if($i > 0)<span class="at-sep"></span>@endif
                <span class="at-item">{{ $msg }}</span>
            @endforeach
            <span class="at-sep"></span>
            {{-- Second copy (seamless loop) --}}
            @foreach($tickerMessages as $i => $msg)
                @if($i > 0)<span class="at-sep"></span>@endif
                <span class="at-item">{{ $msg }}</span>
            @endforeach
            <span class="at-sep"></span>
        </div>
    </div>
</div>
@endif
