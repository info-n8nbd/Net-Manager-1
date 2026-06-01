@extends('layouts.sub-reseller')
@section('title', 'My Attendance')
@section('content')

{{-- Today Status Bar --}}
<div class="card shadow-sm border-0 mb-3" style="border-radius:12px;overflow:hidden;">
    <div class="card-body p-0">
        <div class="row no-gutters">

            {{-- Date --}}
            <div class="col-12 col-md-2 d-flex align-items-center px-4 py-3 border-right bg-light">
                <div>
                    <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:1px;">Today</div>
                    <div class="font-weight-bold text-dark" style="font-size:14px;">{{ now()->format('d M Y') }}</div>
                    <div class="text-muted" style="font-size:12px;">{{ now()->format('l') }}</div>
                </div>
            </div>

            {{-- Check In --}}
            <div class="col-12 col-md-4 d-flex align-items-center px-4 py-3 border-right">
                @if($todayCheckin)
                    @if($todayCheckin->selfie_url)
                    <a href="{{ $todayCheckin->selfie_url }}" target="_blank">
                        <img src="{{ $todayCheckin->selfie_url }}" class="rounded-circle shadow-sm mr-3" style="width:48px;height:48px;object-fit:cover;border:2px solid #28a745;">
                    </a>
                    @else
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mr-3" style="width:48px;height:48px;min-width:48px;">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    @endif
                    <div>
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:1px;">Check In</div>
                        <div class="font-weight-bold text-success" style="font-size:18px;">
                            <i class="fas fa-sign-in-alt mr-1" style="font-size:14px;"></i>
                            {{ \Carbon\Carbon::parse($todayCheckin->check_in_time)->format('h:i A') }}
                        </div>
                        @if($todayCheckin->location_address)
                        <div class="text-muted" style="font-size:11px;"><i class="fas fa-map-marker-alt mr-1"></i>{{ Str::limit($todayCheckin->location_address, 35) }}</div>
                        @endif
                    </div>
                @else
                    <div class="mr-3">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="fas fa-sign-in-alt text-muted"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:1px;">Check In</div>
                        <button class="btn btn-success btn-sm mt-1 px-3" id="btnCheckin" style="border-radius:20px;">
                            <i class="fas fa-camera mr-1"></i>Take Selfie
                        </button>
                    </div>
                @endif
            </div>

            {{-- Check Out --}}
            <div class="col-12 col-md-4 d-flex align-items-center px-4 py-3 border-right">
                @if($todayCheckout)
                    @if($todayCheckout->selfie_url)
                    <a href="{{ $todayCheckout->selfie_url }}" target="_blank">
                        <img src="{{ $todayCheckout->selfie_url }}" class="rounded-circle shadow-sm mr-3" style="width:48px;height:48px;object-fit:cover;border:2px solid #17a2b8;">
                    </a>
                    @else
                    <div class="rounded-circle bg-info d-flex align-items-center justify-content-center mr-3" style="width:48px;height:48px;min-width:48px;">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    @endif
                    <div>
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:1px;">Check Out</div>
                        <div class="font-weight-bold text-info" style="font-size:18px;">
                            <i class="fas fa-sign-out-alt mr-1" style="font-size:14px;"></i>
                            {{ \Carbon\Carbon::parse($todayCheckout->check_in_time)->format('h:i A') }}
                        </div>
                        @if($todayCheckout->location_address)
                        <div class="text-muted" style="font-size:11px;"><i class="fas fa-map-marker-alt mr-1"></i>{{ Str::limit($todayCheckout->location_address, 35) }}</div>
                        @endif
                    </div>
                @elseif($todayCheckin)
                    <div class="mr-3">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="fas fa-sign-out-alt text-muted"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:1px;">Check Out</div>
                        <button class="btn btn-info btn-sm mt-1 px-3" id="btnCheckout" style="border-radius:20px;">
                            <i class="fas fa-camera mr-1"></i>Take Selfie
                        </button>
                    </div>
                @else
                    <div class="mr-3">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="fas fa-sign-out-alt text-muted"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:1px;">Check Out</div>
                        <div class="text-muted small mt-1">Check in first</div>
                    </div>
                @endif
            </div>

            {{-- Status --}}
            <div class="col-12 col-md-2 d-flex align-items-center justify-content-center px-4 py-3">
                @if($todayCheckin && $todayCheckout)
                    <div class="text-center">
                        <span class="badge badge-success py-2 px-3" style="font-size:13px;border-radius:20px;">
                            <i class="fas fa-check-circle mr-1"></i>Complete
                        </span>
                        @php
                            $mins = abs(strtotime($todayCheckout->check_in_time) - strtotime($todayCheckin->check_in_time)) / 60;
                            $wh   = floor($mins/60).'h '.round($mins%60).'m';
                        @endphp
                        <div class="text-muted mt-1" style="font-size:12px;"><i class="fas fa-hourglass-half mr-1"></i>{{ $wh }}</div>
                    </div>
                @elseif($todayCheckin)
                    <span class="badge badge-primary py-2 px-3" style="font-size:13px;border-radius:20px;">
                        <i class="fas fa-user-check mr-1"></i>Present
                    </span>
                @else
                    <span class="badge badge-warning py-2 px-3" style="font-size:13px;border-radius:20px;">
                        <i class="fas fa-clock mr-1"></i>Not In
                    </span>
                @endif
            </div>

        </div>
    </div>
</div>

{{-- Camera Modal --}}
<div class="modal fade" id="cameraModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 shadow" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header py-3 bg-dark text-white border-0">
                <h6 class="modal-title mb-0" id="cameraModalTitle"><i class="fas fa-camera mr-2"></i>Take Selfie</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-3 text-center">
                <video id="cameraStream" autoplay playsinline style="width:100%;max-height:260px;border-radius:10px;background:#111;transform:scaleX(-1);"></video>
                <canvas id="selfieCanvas" style="display:none;"></canvas>
                <img id="selfiePreview" style="display:none;width:100%;max-height:260px;border-radius:10px;transform:scaleX(-1);" class="mb-2">
                <div id="locationStatus" class="alert alert-light border py-2 px-3 mt-2 mb-2 text-left" style="font-size:12px;border-radius:8px;">
                    <i class="fas fa-map-marker-alt text-danger mr-1"></i><span id="locationText">Getting location...</span>
                </div>
                <div id="captureButtons" class="mt-2">
                    <button class="btn btn-dark px-4" id="btnCapture" style="border-radius:20px;">
                        <i class="fas fa-camera mr-1"></i>Capture
                    </button>
                </div>
                <div id="confirmButtons" style="display:none;" class="mt-2">
                    <button class="btn btn-outline-secondary mr-2" id="btnRetake" style="border-radius:20px;">
                        <i class="fas fa-redo mr-1"></i>Retake
                    </button>
                    <button class="btn btn-success px-4" id="btnSubmit" style="border-radius:20px;">
                        <i class="fas fa-check mr-1"></i>Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- History Table --}}
<div class="card shadow-sm border-0" style="border-radius:12px;overflow:hidden;">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="mb-0 font-weight-bold text-dark">
            <i class="fas fa-history mr-2 text-primary"></i>Attendance History
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th class="pl-4 py-3 border-0 text-uppercase" style="font-size:11px;letter-spacing:.5px;color:#6c757d;">Date</th>
                        <th class="py-3 border-0 text-uppercase" style="font-size:11px;letter-spacing:.5px;color:#6c757d;">Check In</th>
                        <th class="py-3 border-0 text-uppercase" style="font-size:11px;letter-spacing:.5px;color:#6c757d;">Check Out</th>
                        <th class="py-3 border-0 text-uppercase" style="font-size:11px;letter-spacing:.5px;color:#6c757d;">Hours</th>
                        <th class="pr-4 py-3 border-0 text-uppercase" style="font-size:11px;letter-spacing:.5px;color:#6c757d;">Location</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($history as $date => $recs)
                    @php
                        $ci   = $recs->firstWhere('type', 'check_in');
                        $co   = $recs->firstWhere('type', 'check_out');
                        $loc  = $ci ?? $co;
                        $wh   = null;
                        if ($ci && $co) {
                            $mins = abs(strtotime($co->check_in_time) - strtotime($ci->check_in_time)) / 60;
                            $wh   = floor($mins/60).'h '.round($mins%60).'m';
                        }
                    @endphp
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td class="pl-4 py-3 align-middle">
                            <div class="font-weight-bold text-dark">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ \Carbon\Carbon::parse($date)->format('l') }}</div>
                        </td>
                        <td class="py-3 align-middle">
                            @if($ci)
                                <div class="d-flex align-items-center">
                                    @if($ci->selfie_url)
                                    <a href="{{ $ci->selfie_url }}" target="_blank">
                                        <img src="{{ $ci->selfie_url }}" class="rounded-circle mr-2 shadow-sm" style="width:32px;height:32px;object-fit:cover;border:2px solid #28a745;">
                                    </a>
                                    @endif
                                    <span class="font-weight-bold text-success">{{ \Carbon\Carbon::parse($ci->check_in_time)->format('h:i A') }}</span>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="py-3 align-middle">
                            @if($co)
                                <div class="d-flex align-items-center">
                                    @if($co->selfie_url)
                                    <a href="{{ $co->selfie_url }}" target="_blank">
                                        <img src="{{ $co->selfie_url }}" class="rounded-circle mr-2 shadow-sm" style="width:32px;height:32px;object-fit:cover;border:2px solid #17a2b8;">
                                    </a>
                                    @endif
                                    <span class="font-weight-bold text-info">{{ \Carbon\Carbon::parse($co->check_in_time)->format('h:i A') }}</span>
                                </div>
                            @elseif($ci)
                                <span class="text-warning small"><i class="fas fa-clock mr-1"></i>Pending</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="py-3 align-middle">
                            @if($wh)
                                <span class="badge badge-light border px-2 py-1" style="font-size:12px;"><i class="fas fa-hourglass-half mr-1 text-muted"></i>{{ $wh }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="pr-4 py-3 align-middle">
                            @if($loc && $loc->latitude)
                                <a href="{{ $loc->google_maps_url }}" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                    <span class="text-muted small">{{ Str::limit($loc->location_address ?? $loc->latitude.','.$loc->longitude, 32) }}</span>
                                </a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-calendar-times fa-2x mb-2 d-block text-muted"></i>
                            No attendance records yet
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
let stream = null, capturedData = null, currentType = null, userLat = null, userLng = null, userAddr = '';
document.addEventListener('DOMContentLoaded', function () {
    var btnCI = document.getElementById('btnCheckin');
    var btnCO = document.getElementById('btnCheckout');
    if (btnCI) btnCI.addEventListener('click', function () { openCamera('check_in'); });
    if (btnCO) btnCO.addEventListener('click', function () { openCamera('check_out'); });
    document.getElementById('btnCapture').addEventListener('click', capturePhoto);
    document.getElementById('btnRetake').addEventListener('click', retakePhoto);
    document.getElementById('btnSubmit').addEventListener('click', submitAttendance);
    $('#cameraModal').on('hidden.bs.modal', stopCamera);
});
function openCamera(type) {
    currentType = type;
    document.getElementById('cameraModalTitle').innerHTML = '<i class="fas fa-camera mr-2"></i>' + (type === 'check_in' ? 'Check In' : 'Check Out') + ' — Take Selfie';
    document.getElementById('cameraStream').style.display = '';
    document.getElementById('selfiePreview').style.display = 'none';
    document.getElementById('captureButtons').style.display = '';
    document.getElementById('confirmButtons').style.display = 'none';
    capturedData = null;
    $('#cameraModal').modal('show');
    getLocation();
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
        .then(function (s) { stream = s; document.getElementById('cameraStream').srcObject = s; })
        .catch(function (e) { document.getElementById('locationText').textContent = 'Camera error: ' + e.message; });
}
var locationWatchId = null;
var bestAccuracy = Infinity;
function getLocation() {
    bestAccuracy = Infinity;
    userLat = null; userLng = null; userAddr = '';
    var locEl = document.getElementById('locationText');
    var submitBtn = document.getElementById('btnSubmit');
    locEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Getting GPS location...';
    if (submitBtn) submitBtn.disabled = true;
    if (!navigator.geolocation) { locEl.textContent = 'Geolocation not supported'; return; }
    if (locationWatchId !== null) { navigator.geolocation.clearWatch(locationWatchId); locationWatchId = null; }
    var opts = { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 };
    function onSuccess(pos) {
        var acc = pos.coords.accuracy;
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        if (acc < bestAccuracy) {
            bestAccuracy = acc;
            userLat = lat;
            userLng = lng;
            var accLabel = acc <= 30 ? '<span style="color:#28a745;font-weight:600;">' + Math.round(acc) + 'm</span>' :
                           acc <= 100 ? '<span style="color:#ffc107;font-weight:600;">' + Math.round(acc) + 'm</span>' :
                                        '<span style="color:#dc3545;font-weight:600;">' + Math.round(acc) + 'm</span>';
            locEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Improving accuracy... ' + accLabel;
            fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json')
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    userAddr = d.display_name || '';
                    var txt = userAddr.substring(0, 80) || lat.toFixed(5) + ', ' + lng.toFixed(5);
                    locEl.innerHTML = '<i class="fas fa-map-marker-alt mr-1 text-danger"></i> ' + txt + ' [' + accLabel + ']';
                    if (submitBtn && acc <= 300) submitBtn.disabled = false;
                })
                .catch(function() {
                    locEl.innerHTML = '<i class="fas fa-map-marker-alt mr-1 text-danger"></i> ' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ' [' + accLabel + ']';
                    if (submitBtn && acc <= 300) submitBtn.disabled = false;
                });
        }
        if (acc <= 30 && locationWatchId !== null) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
        }
    }
    function onError(err) {
        locEl.innerHTML = '<i class="fas fa-exclamation-triangle mr-1 text-warning"></i> Location error: ' + err.message;
        if (submitBtn) submitBtn.disabled = false;
    }
    locationWatchId = navigator.geolocation.watchPosition(onSuccess, onError, opts);
    setTimeout(function() {
        if (locationWatchId !== null) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
            if (userLat && submitBtn) submitBtn.disabled = false;
            if (!userLat) locEl.innerHTML = '<i class="fas fa-exclamation-triangle mr-1 text-warning"></i> Could not get location. Try outdoor.';
        }
    }, 30000);
}
function capturePhoto() {
    var video = document.getElementById('cameraStream');
    var canvas = document.getElementById('selfieCanvas');
    canvas.width = video.videoWidth || 640; canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0);
    capturedData = canvas.toDataURL('image/jpeg', 0.85);
    document.getElementById('selfiePreview').src = capturedData;
    document.getElementById('selfiePreview').style.display = '';
    video.style.display = 'none';
    document.getElementById('captureButtons').style.display = 'none';
    document.getElementById('confirmButtons').style.display = '';
}
function retakePhoto() {
    capturedData = null;
    document.getElementById('selfiePreview').style.display = 'none';
    document.getElementById('cameraStream').style.display = '';
    document.getElementById('captureButtons').style.display = '';
    document.getElementById('confirmButtons').style.display = 'none';
}
function submitAttendance() {
    if (!capturedData) { alert('Please take a selfie first.'); return; }
    var btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Submitting...';
    fetch('{{ route("sub-reseller.attendance.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ selfie: capturedData, type: currentType, latitude: userLat, longitude: userLng, location_address: userAddr })
    }).then(function (r) { return r.json(); }).then(function (data) {
        stopCamera();
        $('#cameraModal').modal('hide');
        if (data.success) { alert(data.message); location.reload(); }
        else { alert(data.error || 'Error occurred.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-check mr-1"></i>Submit'; }
    }).catch(function () { alert('Network error. Please try again.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-check mr-1"></i>Submit'; });
}
function stopCamera() {
    if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
}
</script>
@endpush
