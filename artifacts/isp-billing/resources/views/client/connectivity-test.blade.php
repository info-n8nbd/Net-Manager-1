@extends('layouts.client')

@section('title', 'Connectivity Test')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Connection Status</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">IP Address</span>
                                    <span class="info-box-number">{{ $client->ip_address ?? 'Not Assigned' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">Connection Type</span>
                                    <span class="info-box-number">{{ strtoupper($client->connection_type ?? 'PPPoE') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">Status</span>
                                    <span class="info-box-number">
                                        <span class="badge badge-{{ ($client->connection_status ?? 'offline') == 'online' ? 'success' : 'danger' }} px-3 py-2">
                                            {{ strtoupper($client->connection_status ?? 'Offline') }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mb-4">
                        <button class="btn btn-lg btn-primary" onclick="runTest()" id="testBtn">
                            <i class="fas fa-satellite-dish mr-2"></i>Run Connectivity Test
                        </button>
                    </div>

                    <div id="testResults" style="display: none;">
                        <hr>
                        <h5><i class="fas fa-terminal mr-2"></i>Test Results</h5>
                        <div id="resultContent" class="p-3 rounded" style="background: #2d2d2d; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 13px; min-height: 150px; max-height: 300px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function runTest() {
    const btn = document.getElementById('testBtn');
    const results = document.getElementById('testResults');
    const content = document.getElementById('resultContent');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing...';
    results.style.display = 'block';
    content.innerHTML = '<div style="color: #dcdcaa;">Running connectivity test from router...</div>';

    fetch('{{ route("client.connectivity-test.run") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let html = '<div style="color: #4ec9b0;">Test completed successfully!</div>';
            if (data.data) {
                if (Array.isArray(data.data)) {
                    data.data.forEach(item => {
                        html += '<div>' + JSON.stringify(item) + '</div>';
                    });
                } else if (typeof data.data === 'object') {
                    Object.entries(data.data).forEach(([key, val]) => {
                        html += '<div><span style="color: #569cd6;">' + key + '</span>: ' + (typeof val === 'object' ? JSON.stringify(val) : val) + '</div>';
                    });
                }
            }
            if (data.message) html += '<div style="color: #4ec9b0;">' + data.message + '</div>';
            content.innerHTML = html;
        } else {
            content.innerHTML = '<div style="color: #f44747;">Test failed: ' + (data.message || 'Unknown error') + '</div>';
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-satellite-dish mr-2"></i>Run Connectivity Test';
    })
    .catch(err => {
        content.innerHTML = '<div style="color: #f44747;">Error: ' + err.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-satellite-dish mr-2"></i>Run Connectivity Test';
    });
}
</script>
@endpush
