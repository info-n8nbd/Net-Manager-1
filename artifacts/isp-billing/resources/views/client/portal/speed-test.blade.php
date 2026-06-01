@extends('layouts.client')

@section('title', 'Speed Test')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Internet Speed Test</h5>
                </div>
                <div class="card-body text-center py-5">
                    <div id="speed-test-container">
                        <div id="speed-gauge" class="mb-4">
                            <svg viewBox="0 0 200 120" width="300" height="180">
                                <defs>
                                    <linearGradient id="gaugeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#dc3545"/>
                                        <stop offset="50%" style="stop-color:#ffc107"/>
                                        <stop offset="100%" style="stop-color:#28a745"/>
                                    </linearGradient>
                                </defs>
                                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#e9ecef" stroke-width="12" stroke-linecap="round"/>
                                <path id="gauge-fill" d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="url(#gaugeGradient)" stroke-width="12" stroke-linecap="round" stroke-dasharray="0 251"/>
                                <circle id="gauge-needle" cx="100" cy="100" r="8" fill="#333"/>
                                <text id="speed-value" x="100" y="85" text-anchor="middle" font-size="28" font-weight="bold" fill="#333">0</text>
                                <text id="speed-unit" x="100" y="105" text-anchor="middle" font-size="14" fill="#666">Mbps</text>
                            </svg>
                        </div>

                        <div class="row mb-4">
                            <div class="col-4">
                                <div class="border rounded p-3">
                                    <i class="fas fa-download fa-2x text-success mb-2"></i>
                                    <h6 class="text-muted">Download</h6>
                                    <h4 id="download-speed" class="mb-0">--</h4>
                                    <small class="text-muted">Mbps</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3">
                                    <i class="fas fa-upload fa-2x text-primary mb-2"></i>
                                    <h6 class="text-muted">Upload</h6>
                                    <h4 id="upload-speed" class="mb-0">--</h4>
                                    <small class="text-muted">Mbps</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3">
                                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                    <h6 class="text-muted">Ping</h6>
                                    <h4 id="ping-value" class="mb-0">--</h4>
                                    <small class="text-muted">ms</small>
                                </div>
                            </div>
                        </div>

                        <div id="test-status" class="mb-4">
                            <p class="text-muted mb-0">Click the button to start speed test</p>
                        </div>

                        <button id="start-test-btn" class="btn btn-primary btn-lg px-5" onclick="startSpeedTest()">
                            <i class="fas fa-play me-2"></i>Start Test
                        </button>
                    </div>

                    <div id="test-progress" class="d-none">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="progress-text" class="text-muted">Initializing...</p>
                        <div class="progress mx-auto" style="max-width: 300px; height: 8px;">
                            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($recentTests) && $recentTests->count() > 0)
            <div class="card mt-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Tests</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Download</th>
                                    <th>Upload</th>
                                    <th>Ping</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTests as $test)
                                <tr>
                                    <td>{{ $test->created_at->format('d-m-y H:i') }}</td>
                                    <td><span class="text-success">{{ number_format($test->download_speed, 1) }}</span> Mbps</td>
                                    <td><span class="text-primary">{{ number_format($test->upload_speed, 1) }}</span> Mbps</td>
                                    <td>{{ $test->ping }} ms</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let testRunning = false;

async function startSpeedTest() {
    if (testRunning) return;
    testRunning = true;
    
    const btn = document.getElementById('start-test-btn');
    const progress = document.getElementById('test-progress');
    const container = document.getElementById('speed-test-container');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const status = document.getElementById('test-status');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
    
    resetResults();
    
    try {
        // Ping test
        progressText.textContent = 'Testing ping...';
        progressBar.style.width = '10%';
        const ping = await testPing();
        document.getElementById('ping-value').textContent = ping;
        updateGauge(0, 'Testing...');
        
        // Download test
        progressText.textContent = 'Testing download speed...';
        progressBar.style.width = '30%';
        const download = await testDownload((speed) => {
            document.getElementById('download-speed').textContent = speed.toFixed(1);
            updateGauge(speed, speed.toFixed(1));
        });
        document.getElementById('download-speed').textContent = download.toFixed(1);
        
        // Upload test
        progressText.textContent = 'Testing upload speed...';
        progressBar.style.width = '70%';
        const upload = await testUpload((speed) => {
            document.getElementById('upload-speed').textContent = speed.toFixed(1);
            updateGauge(speed, speed.toFixed(1));
        });
        document.getElementById('upload-speed').textContent = upload.toFixed(1);
        
        progressBar.style.width = '100%';
        progressText.textContent = 'Test complete!';
        
        // Save results
        await saveResults(download, upload, ping);
        
        status.innerHTML = '<p class="text-success mb-0"><i class="fas fa-check-circle me-1"></i>Test completed successfully!</p>';
        
    } catch (error) {
        console.error('Speed test error:', error);
        status.innerHTML = '<p class="text-danger mb-0"><i class="fas fa-exclamation-circle me-1"></i>Test failed. Please try again.</p>';
    }
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-redo me-2"></i>Test Again';
    testRunning = false;
}

function resetResults() {
    document.getElementById('download-speed').textContent = '--';
    document.getElementById('upload-speed').textContent = '--';
    document.getElementById('ping-value').textContent = '--';
    updateGauge(0, '0');
}

function updateGauge(speed, display) {
    const maxSpeed = 100;
    const percentage = Math.min(speed / maxSpeed, 1);
    const dashLength = percentage * 251;
    
    document.getElementById('gauge-fill').setAttribute('stroke-dasharray', `${dashLength} 251`);
    document.getElementById('speed-value').textContent = display;
}

async function testPing() {
    const iterations = 5;
    let totalPing = 0;
    
    for (let i = 0; i < iterations; i++) {
        const start = performance.now();
        try {
            await fetch('/api/ping?' + Date.now(), { method: 'HEAD', cache: 'no-store' });
        } catch (e) {
            await fetch('/', { method: 'HEAD', cache: 'no-store' });
        }
        totalPing += performance.now() - start;
    }
    
    return Math.round(totalPing / iterations);
}

async function testDownload(onProgress) {
    const testSizes = [1024 * 100, 1024 * 500, 1024 * 1000];
    let maxSpeed = 0;
    
    for (const size of testSizes) {
        const url = `/api/speed-test/download?size=${size}&t=${Date.now()}`;
        const start = performance.now();
        
        try {
            const response = await fetch(url, { cache: 'no-store' });
            const blob = await response.blob();
            const duration = (performance.now() - start) / 1000;
            const speedMbps = (blob.size * 8) / (duration * 1000000);
            
            if (speedMbps > maxSpeed) {
                maxSpeed = speedMbps;
                onProgress(maxSpeed);
            }
        } catch (e) {
            console.log('Download test chunk failed:', e);
        }
    }
    
    return maxSpeed > 0 ? maxSpeed : await simulateDownload(onProgress);
}

async function simulateDownload(onProgress) {
    const chunks = 10;
    let totalBytes = 0;
    const start = performance.now();
    
    for (let i = 0; i < chunks; i++) {
        try {
            const response = await fetch(`/?chunk=${i}&t=${Date.now()}`, { cache: 'no-store' });
            const blob = await response.blob();
            totalBytes += blob.size;
            
            const duration = (performance.now() - start) / 1000;
            const speed = (totalBytes * 8) / (duration * 1000000);
            onProgress(speed);
        } catch (e) {}
    }
    
    const totalDuration = (performance.now() - start) / 1000;
    return (totalBytes * 8) / (totalDuration * 1000000);
}

async function testUpload(onProgress) {
    const testSizes = [1024 * 50, 1024 * 200];
    let maxSpeed = 0;
    
    for (const size of testSizes) {
        const data = new Blob([new ArrayBuffer(size)]);
        const formData = new FormData();
        formData.append('data', data);
        
        const start = performance.now();
        
        try {
            await fetch('/api/speed-test/upload', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            
            const duration = (performance.now() - start) / 1000;
            const speedMbps = (size * 8) / (duration * 1000000);
            
            if (speedMbps > maxSpeed) {
                maxSpeed = speedMbps;
                onProgress(maxSpeed);
            }
        } catch (e) {
            console.log('Upload test chunk failed:', e);
        }
    }
    
    return maxSpeed > 0 ? maxSpeed : 0;
}

async function saveResults(download, upload, ping) {
    try {
        await fetch('/api/speed-test/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                download_speed: download,
                upload_speed: upload,
                ping: ping
            })
        });
    } catch (e) {
        console.log('Failed to save results:', e);
    }
}
</script>
@endpush
