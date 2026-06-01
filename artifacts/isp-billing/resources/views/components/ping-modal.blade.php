<div class="modal fade" id="pingModal" tabindex="-1" role="dialog" aria-labelledby="pingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2" style="background: #1e3c72; color: white;">
                <h6 class="modal-title" id="pingModalLabel"><i class="fas fa-wifi mr-2"></i>Ping Check - <span id="pingModalIP"></span></h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-2">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <small class="text-muted" id="pingModalClientName"></small>
                    <span class="badge badge-secondary" id="pingModalProgress">0 / 20</span>
                </div>
                <div class="progress mb-2" style="height: 4px;">
                    <div class="progress-bar bg-info" id="pingProgressBar" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="pingResultsContainer" style="max-height: 340px; overflow-y: auto;">
                    <table class="table table-sm table-bordered mb-0" style="font-size: 12px;">
                        <thead style="background: #f0f4f8;">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Status</th>
                                <th style="width: 100px;">Latency</th>
                            </tr>
                        </thead>
                        <tbody id="pingResultsBody">
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 p-2 rounded" style="background: #f0f4f8; font-size: 12px;" id="pingSummary" style="display:none;">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="text-muted">Sent</div>
                            <strong id="pingSumSent">0</strong>
                        </div>
                        <div class="col-3">
                            <div class="text-muted">Success</div>
                            <strong class="text-success" id="pingSumSuccess">0</strong>
                        </div>
                        <div class="col-3">
                            <div class="text-muted">Failed</div>
                            <strong class="text-danger" id="pingSumFailed">0</strong>
                        </div>
                        <div class="col-3">
                            <div class="text-muted">Avg</div>
                            <strong class="text-primary" id="pingSumAvg">--</strong><small>ms</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-outline-info" id="pingRestartBtn" style="display:none;" onclick="startPingSequence()"><i class="fas fa-redo mr-1"></i>Ping Again</button>
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
var pingClientId = null;
var pingIP = null;
var pingClientName = '';
var pingRunning = false;
var pingAborted = false;

function openPingModal(clientId, ip, clientName) {
    pingClientId = clientId;
    pingIP = ip;
    pingClientName = clientName || '';
    pingAborted = false;
    
    document.getElementById('pingModalIP').textContent = ip || 'No IP Set';
    document.getElementById('pingModalClientName').textContent = clientName;
    document.getElementById('pingResultsBody').innerHTML = '';
    document.getElementById('pingProgressBar').style.width = '0%';
    document.getElementById('pingModalProgress').textContent = '0 / 20';
    document.getElementById('pingSumSent').textContent = '0';
    document.getElementById('pingSumSuccess').textContent = '0';
    document.getElementById('pingSumFailed').textContent = '0';
    document.getElementById('pingSumAvg').textContent = '--';
    document.getElementById('pingRestartBtn').style.display = 'none';
    
    $('#pingModal').modal('show');
    
    setTimeout(function() { startPingSequence(); }, 300);
}

function startPingSequence() {
    if (pingRunning) return;
    pingRunning = true;
    pingAborted = false;
    
    document.getElementById('pingResultsBody').innerHTML = '';
    document.getElementById('pingProgressBar').style.width = '0%';
    document.getElementById('pingModalProgress').textContent = '0 / 20';
    document.getElementById('pingRestartBtn').style.display = 'none';
    
    var results = [];
    var totalPings = 20;
    var currentPing = 0;
    
    function doNextPing() {
        if (pingAborted || currentPing >= totalPings) {
            pingRunning = false;
            document.getElementById('pingRestartBtn').style.display = 'inline-block';
            updatePingSummary(results);
            return;
        }
        
        currentPing++;
        var seq = currentPing;
        
        var row = document.createElement('tr');
        row.innerHTML = '<td>' + seq + '</td><td><i class="fas fa-spinner fa-spin text-info"></i> Pinging...</td><td>--</td>';
        document.getElementById('pingResultsBody').appendChild(row);
        
        var container = document.getElementById('pingResultsContainer');
        container.scrollTop = container.scrollHeight;
        
        document.getElementById('pingModalProgress').textContent = seq + ' / ' + totalPings;
        document.getElementById('pingProgressBar').style.width = ((seq / totalPings) * 100) + '%';
        
        $.ajax({
            url: '/admin/clients/' + pingClientId + '/live-ping',
            type: 'GET',
            timeout: 10000,
            success: function(data) {
                var latency = data.latency || data.avg || null;
                var online = data.online || false;
                
                if (online && latency !== null) {
                    row.innerHTML = '<td>' + seq + '</td><td><span class="badge badge-success"><i class="fas fa-check mr-1"></i>Reply</span></td><td><strong>' + latency + '</strong> ms</td>';
                    results.push({ seq: seq, status: 'success', latency: parseFloat(latency) });
                } else {
                    var msg = data.message || 'Timeout';
                    row.innerHTML = '<td>' + seq + '</td><td><span class="badge badge-danger"><i class="fas fa-times mr-1"></i>' + msg.substring(0, 30) + '</span></td><td>--</td>';
                    results.push({ seq: seq, status: 'fail', latency: null });
                }
            },
            error: function() {
                row.innerHTML = '<td>' + seq + '</td><td><span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Error</span></td><td>--</td>';
                results.push({ seq: seq, status: 'fail', latency: null });
            },
            complete: function() {
                updatePingSummary(results);
                setTimeout(doNextPing, 500);
            }
        });
    }
    
    doNextPing();
}

function updatePingSummary(results) {
    var sent = results.length;
    var success = results.filter(function(r) { return r.status === 'success'; }).length;
    var failed = sent - success;
    var latencies = results.filter(function(r) { return r.latency !== null; }).map(function(r) { return r.latency; });
    var avg = latencies.length > 0 ? (latencies.reduce(function(a, b) { return a + b; }, 0) / latencies.length).toFixed(1) : '--';
    
    document.getElementById('pingSumSent').textContent = sent;
    document.getElementById('pingSumSuccess').textContent = success;
    document.getElementById('pingSumFailed').textContent = failed;
    document.getElementById('pingSumAvg').textContent = avg;
}

$('#pingModal').on('hidden.bs.modal', function() {
    pingAborted = true;
    pingRunning = false;
});
</script>
