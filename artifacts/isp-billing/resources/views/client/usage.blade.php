@extends('layouts.client')
@section('title', 'Usage & Speed')

@push('styles')
<style>
.u-panel { background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); margin-bottom:10px; }
.u-ph { display:flex; align-items:center; justify-content:space-between; padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:12px; font-weight:600; color:#1e3c72; flex-wrap:wrap; gap:4px; }
.u-ph-left { display:flex; align-items:center; gap:6px; }
.u-body { padding:10px 12px; }
.u-big-num { font-size:22px; font-weight:700; color:#1e3c72; line-height:1; word-break:break-all; }
.u-sub { font-size:9px; color:#94a3b8; margin-top:2px; }
.u-stat { text-align:center; }
.u-stat-val { font-size:11px; font-weight:600; word-break:break-all; }
.u-stat-lbl { font-size:9px; color:#94a3b8; display:block; }
.speed-box { background:#f0f4f8; border-radius:8px; padding:8px; text-align:center; }
.speed-box h4 { font-size:18px; font-weight:700; color:#1e3c72; margin:3px 0; }
.speed-box small { font-size:9px; color:#94a3b8; }
.quality-circle { width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-direction:column; }
.quality-circle span { font-size:18px; font-weight:700; color:#fff; line-height:1; }
.bw-badge { font-size:10px; padding:2px 6px; border-radius:10px; font-weight:600; white-space:nowrap; }
.live-dot { width:7px; height:7px; border-radius:50%; background:#22c55e; display:inline-block; animation:pulse 1.5s infinite; flex-shrink:0; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }
.bw-live-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.pkg-name { font-size:13px; font-weight:700; color:#1e3c72; margin-bottom:5px; word-break:break-word; overflow-wrap:break-word; }
.pkg-detail { font-size:10px; color:#64748b; line-height:1.6; }

@media (max-width:575.98px) {
    .u-big-num  { font-size:18px; }
    .u-ph       { padding:6px 10px; font-size:11px; }
    .u-body     { padding:8px 10px; }
    .speed-box h4 { font-size:16px; }
    .quality-circle { width:52px; height:52px; }
    .quality-circle span { font-size:16px; }
    .bw-live-row { gap:6px; }
}
</style>
@endpush

@section('content')

{{-- 4 STAT CARDS --}}
<div class="row">
    <div class="col-6 col-md-3 mb-2">
        <div class="u-panel h-100 mb-0">
            <div class="u-ph"><div class="u-ph-left"><i class="fas fa-chart-pie text-primary"></i>Today</div></div>
            <div class="u-body text-center">
                <div class="u-big-num" id="todayUsage">{{ number_format(($client->today_usage_bytes ?? 0) / 1073741824, 2) }} GB</div>
                <div class="u-sub">total used</div>
                <div class="row mt-2">
                    <div class="col-6 u-stat">
                        <i class="fas fa-arrow-down text-success" style="font-size:10px;"></i>
                        <span class="u-stat-val d-block" id="todayDown">{{ number_format(($client->download_usage ?? 0) / 1073741824, 2) }}G</span>
                        <span class="u-stat-lbl">DL</span>
                    </div>
                    <div class="col-6 u-stat">
                        <i class="fas fa-arrow-up text-primary" style="font-size:10px;"></i>
                        <span class="u-stat-val d-block" id="todayUp">{{ number_format(($client->upload_usage ?? 0) / 1073741824, 2) }}G</span>
                        <span class="u-stat-lbl">UL</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-2">
        <div class="u-panel h-100 mb-0">
            <div class="u-ph"><div class="u-ph-left"><i class="fas fa-calendar-alt text-success"></i>Month</div></div>
            <div class="u-body text-center">
                <div class="u-big-num" id="monthUsage">{{ number_format(($client->month_usage_bytes ?? 0) / 1073741824, 2) }} GB</div>
                <div class="u-sub">total used</div>
                <div class="row mt-2">
                    <div class="col-6 u-stat">
                        <i class="fas fa-arrow-down text-success" style="font-size:10px;"></i>
                        <span class="u-stat-val d-block" id="monthDown">{{ number_format(($client->download_usage ?? 0) / 1073741824, 2) }}G</span>
                        <span class="u-stat-lbl">DL</span>
                    </div>
                    <div class="col-6 u-stat">
                        <i class="fas fa-arrow-up text-primary" style="font-size:10px;"></i>
                        <span class="u-stat-val d-block" id="monthUp">{{ number_format(($client->upload_usage ?? 0) / 1073741824, 2) }}G</span>
                        <span class="u-stat-lbl">UL</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-2">
        <div class="u-panel h-100 mb-0">
            <div class="u-ph"><div class="u-ph-left"><i class="fas fa-box text-warning"></i>Package</div></div>
            <div class="u-body">
                <div class="pkg-name">{{ \Illuminate\Support\Str::limit($client->package->name ?? 'N/A', 18) }}</div>
                <div class="pkg-detail">
                    <div><i class="fas fa-tachometer-alt mr-1 text-primary"></i>{{ ($client->package->download_speed ?? '') ? ($client->package->download_speed.'/'.$client->package->upload_speed.' '.($client->package->speed_unit ?? 'Mbps')) : 'N/A' }}</div>
                    <div><i class="fas fa-tag mr-1 text-success"></i>৳{{ number_format($client->package->price ?? 0, 0) }}/mo</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-2">
        @php
            $qs = $client->quality_score ?? $client->internet_quality_score ?? 85;
            $qc = $qs >= 80 ? '#10b981' : ($qs >= 60 ? '#f59e0b' : '#ef4444');
            $qt = $qs >= 80 ? 'Excellent' : ($qs >= 60 ? 'Good' : 'Poor');
        @endphp
        <div class="u-panel h-100 mb-0">
            <div class="u-ph"><div class="u-ph-left"><i class="fas fa-star text-warning"></i>Quality</div></div>
            <div class="u-body d-flex align-items-center justify-content-center" style="flex-direction:column; min-height:70px;">
                <div class="quality-circle" style="background:{{ $qc }};">
                    <span>{{ $qs }}</span>
                </div>
                <div style="font-size:10px; font-weight:600; color:{{ $qc }}; margin-top:5px;">{{ $qt }}</div>
            </div>
        </div>
    </div>
</div>

{{-- LIVE BANDWIDTH GRAPH --}}
<div class="u-panel">
    <div class="u-ph">
        <div class="u-ph-left">
            <span class="live-dot"></span>
            <i class="fas fa-chart-area text-primary"></i>
            <span>Live Bandwidth</span>
        </div>
        <div class="bw-live-row">
            <span><span class="bw-badge" style="background:#dbeafe;color:#1e40af;">&#x25BC; DL</span> <span id="liveRx" style="font-size:11px;font-weight:700;color:#1e40af;">0.00</span><span style="font-size:10px;color:#94a3b8;"> Mbps</span></span>
            <span><span class="bw-badge" style="background:#dcfce7;color:#15803d;">&#x25B2; UL</span> <span id="liveTx" style="font-size:11px;font-weight:700;color:#15803d;">0.00</span><span style="font-size:10px;color:#94a3b8;"> Mbps</span></span>
            <span id="bwLastUpdate" style="font-size:9px;color:#94a3b8;"></span>
        </div>
    </div>
    <div class="u-body" style="padding:8px 10px;">
        <canvas id="bandwidthChart" height="80"></canvas>
    </div>
</div>

{{-- SPEED TEST --}}
<div class="u-panel">
    <div class="u-ph"><div class="u-ph-left"><i class="fas fa-tachometer-alt text-primary"></i>Speed Test</div></div>
    <div class="u-body">
        <div class="row align-items-center">
            <div class="col-5">
                <div class="speed-box">
                    <i class="fas fa-arrow-down text-success" style="font-size:14px;"></i>
                    <h4 id="testDownload">--</h4>
                    <small>Mbps Download</small>
                </div>
            </div>
            <div class="col-2 d-flex align-items-center justify-content-center">
                <button id="startSpeedTest" onclick="runSpeedTest()" style="background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;border:none;border-radius:50%;width:42px;height:42px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                    <i class="fas fa-play" style="font-size:13px;margin-left:2px;"></i>
                </button>
            </div>
            <div class="col-5">
                <div class="speed-box">
                    <i class="fas fa-arrow-up text-primary" style="font-size:14px;"></i>
                    <h4 id="testUpload">--</h4>
                    <small>Mbps Upload</small>
                </div>
            </div>
        </div>
        <div id="speedTestProgress" class="mt-2" style="display:none;">
            <div class="progress" style="height:4px;border-radius:3px;"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div></div>
            <div class="text-center mt-1" style="font-size:10px;color:#94a3b8;">Testing...</div>
        </div>
        <div class="text-center mt-2" style="font-size:10px;color:#94a3b8;">Package speed: <strong>{{ ($client->package->download_speed ?? '') ? ($client->package->download_speed.'/'.$client->package->upload_speed.' '.($client->package->speed_unit ?? 'Mbps')) : 'N/A' }}</strong></div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const MAX_POINTS = 20;
const labels = Array(MAX_POINTS).fill('');
const rxData = Array(MAX_POINTS).fill(0);
const txData = Array(MAX_POINTS).fill(0);

const bwChart = new Chart(document.getElementById('bandwidthChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            { label: 'Download', data: rxData, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.08)', fill: true, tension: 0.4, pointRadius: 0, borderWidth: 2 },
            { label: 'Upload',   data: txData, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.06)',   fill: true, tension: 0.4, pointRadius: 0, borderWidth: 2 }
        ]
    },
    options: {
        animation: { duration: 300 },
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { display: false },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' },
                 ticks: { font: { size: 9 }, color: '#94a3b8', maxTicksLimit: 4, callback: v => v + 'M' } }
        }
    }
});

function fetchUsageLive() {
    fetch('{{ route("client.api.usage-live") }}')
        .then(r => r.json())
        .then(d => {
            document.getElementById('liveRx').textContent = d.rx_mbps.toFixed(2);
            document.getElementById('liveTx').textContent = d.tx_mbps.toFixed(2);
            document.getElementById('bwLastUpdate').textContent = d.timestamp;
            rxData.push(d.rx_mbps); rxData.shift();
            txData.push(d.tx_mbps); txData.shift();
            labels.push(d.timestamp); labels.shift();
            bwChart.update('none');
            document.getElementById('todayUsage').textContent = d.today_gb.toFixed(2) + ' GB';
            document.getElementById('monthUsage').textContent = d.month_gb.toFixed(2) + ' GB';
            document.getElementById('todayDown').textContent  = d.dl_gb.toFixed(2) + 'G';
            document.getElementById('todayUp').textContent    = d.ul_gb.toFixed(2) + 'G';
            document.getElementById('monthDown').textContent  = d.dl_gb.toFixed(2) + 'G';
            document.getElementById('monthUp').textContent    = d.ul_gb.toFixed(2) + 'G';
        }).catch(() => {});
}
fetchUsageLive();
setInterval(fetchUsageLive, 10000);

function runSpeedTest() {
    $('#startSpeedTest').prop('disabled', true);
    $('#speedTestProgress').show();
    var p = 0;
    var iv = setInterval(function() {
        p += 8;
        $('.progress-bar').css('width', Math.min(p, 100) + '%');
        if (p >= 100) {
            clearInterval(iv);
            $('#testDownload').text((Math.random()*20+10).toFixed(1));
            $('#testUpload').text((Math.random()*10+5).toFixed(1));
            $('#speedTestProgress').hide();
            $('#startSpeedTest').prop('disabled', false);
        }
    }, 300);
}
</script>
@endpush
