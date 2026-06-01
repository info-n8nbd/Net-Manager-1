@extends('layouts.admin')

@section('title', 'Selfie Attendance')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.selfie-attendance') }}">HRM</a></li>
<li class="breadcrumb-item active">Selfie Attendance</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $todaySummary['total_checkins'] }}</h3>
                    <p>Check-ins Today</p>
                </div>
                <div class="icon"><i class="fas fa-sign-in-alt"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $todaySummary['total_checkouts'] }}</h3>
                    <p>Check-outs Today</p>
                </div>
                <div class="icon"><i class="fas fa-sign-out-alt"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $todaySummary['unique_users'] }}</h3>
                    <p>Employees Present</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box {{ $hasCheckedInToday ? 'bg-success' : 'bg-warning' }}">
                <div class="inner">
                    <h3>{{ $hasCheckedInToday ? 'Done' : 'Pending' }}</h3>
                    <p>Your Check-in</p>
                </div>
                <div class="icon"><i class="fas fa-camera"></i></div>
            </div>
        </div>
    </div>

    @if($isAdminView ?? false)
    <div class="row mb-2">
        <div class="col-12">
            <a href="{{ route('admin.hrm.selfie-attendance.map', ['date' => $date]) }}" class="btn btn-info btn-sm mr-2"><i class="fas fa-map-marked-alt mr-1"></i>Map View</a>
            <a href="{{ route('admin.hrm.selfie-attendance.report') }}" class="btn btn-success btn-sm mr-2"><i class="fas fa-calendar-check mr-1"></i>Monthly Report</a>
            @if(auth()->user()->hasAnyRole(['Super Admin', 'superadmin']))
            <a href="{{ route('admin.hrm.selfie-attendance.settings') }}" class="btn btn-warning btn-sm"><i class="fas fa-cog mr-1"></i>Settings</a>
            @endif
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-5">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera mr-2"></i>Take Selfie Attendance</h3>
                </div>
                <div class="card-body text-center">
                    <div id="iframeWarning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Camera/Location access is not available in embedded view.</strong><br>
                        <span class="mt-2 d-block">Please open this page in a new browser tab to use Selfie Attendance:</span>
                        <div class="input-group input-group-sm mt-2" style="max-width: 500px; margin: 0 auto;">
                            <input type="text" id="directUrlInput" class="form-control" readonly value="" onclick="this.select()">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="copyUrl()"><i class="fas fa-copy"></i> Copy</button>
                            </div>
                        </div>
                        <button type="button" onclick="openInNewTab()" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-external-link-alt mr-2"></i>Open in New Tab
                        </button>
                    </div>
                    <div id="cameraSection">
                        <video id="videoPreview" width="100%" autoplay playsinline style="border-radius: 10px; max-height: 300px; background: #000; display: none;"></video>
                        <canvas id="selfieCanvas" style="display:none;"></canvas>
                        <img id="selfiePreview" style="display:none; border-radius: 10px; max-height: 300px; width: 100%; object-fit: cover;" />

                        <div id="locationInfo" class="alert alert-light mt-3 text-left d-none">
                            <small>
                                <i class="fas fa-map-marker-alt text-danger mr-1"></i> <span id="locText">Fetching location...</span><br>
                                <i class="fas fa-globe text-primary mr-1"></i> Lat: <span id="latText">—</span>, Lng: <span id="lngText">—</span><br>
                                <i class="fas fa-clock text-info mr-1"></i> <span id="timeText">—</span>
                            </small>
                        </div>
                        <div id="locationDeniedInfo" class="alert alert-warning mt-2 text-left d-none">
                            <small><i class="fas fa-exclamation-triangle mr-1"></i> <strong>Location permission denied.</strong> You can still check in without location — submit below.<br>
                            To allow location: click the <strong>🔒 icon</strong> in the address bar → Location → Allow.</small>
                        </div>

                        <div class="mt-3">
                            <button id="btnStartCamera" class="btn btn-info btn-lg" onclick="startCamera()">
                                <i class="fas fa-camera mr-2"></i>Open Camera
                            </button>
                            <button id="btnCapture" class="btn btn-warning btn-lg d-none" onclick="capturePhoto()">
                                <i class="fas fa-camera-retro mr-2"></i>Capture Selfie
                            </button>
                            <button id="btnRetake" class="btn btn-secondary d-none" onclick="retakePhoto()">
                                <i class="fas fa-redo mr-2"></i>Retake
                            </button>
                        </div>

                        <div id="submitSection" class="mt-3 d-none">
                            <div class="btn-group btn-group-lg">
                                @if(!$hasCheckedInToday)
                                <button class="btn btn-success" onclick="submitAttendance('check_in')" id="btnCheckIn">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Check In
                                </button>
                                @endif
                                @if($hasCheckedInToday && !$hasCheckedOutToday)
                                <button class="btn btn-danger" onclick="submitAttendance('check_out')" id="btnCheckOut">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Check Out
                                </button>
                                @endif
                            </div>
                            @if($hasCheckedInToday && $hasCheckedOutToday)
                            <div class="alert alert-success mt-2">
                                <i class="fas fa-check-circle mr-2"></i>You have completed both Check-in and Check-out today.
                            </div>
                            @endif
                        </div>

                        <div id="resultMsg" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Attendance — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
                    <div class="card-tools">
                        <form class="form-inline" method="GET">
                            <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                            @if(auth()->user()->hasAnyRole(['Super Admin', 'superadmin']))
                            <select name="user_id" class="form-control form-control-sm mr-2">
                                <option value="">All Employees</option>
                                @foreach($employees as $emp)
                                @if($emp->user)
                                <option value="{{ $emp->user->id }}" {{ request('user_id') == $emp->user->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                @endif
                                @endforeach
                            </select>
                            @endif
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="60">Photo</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Time</th>
                                <th>Location</th>
                                @if(auth()->user()->hasAnyRole(['Super Admin', 'superadmin']))<th width="50"></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @php $grouped = $attendances->groupBy(fn($a) => $a->user_id . '_' . $a->attendance_date->format('Y-m-d')); @endphp
                            @forelse($grouped as $key => $recs)
                            @php
                                $checkIn  = $recs->firstWhere('type', 'check_in');
                                $checkOut = $recs->firstWhere('type', 'check_out');
                                $att = $checkIn ?? $checkOut;
                            @endphp
                            <tr>
                                <td>
                                    @if($att->selfie_path)
                                    <img src="{{ asset('storage/' . $att->selfie_path) }}" class="img-circle" width="45" height="45" style="object-fit: cover; cursor: pointer;" onclick="viewSelfie(this.src, '{{ $att->user->name ?? '' }}', '{{ $att->check_in_time }}', '{{ $att->latitude }}', '{{ $att->longitude }}', '{{ $att->location_address }}')">
                                    @else
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $att->user->name ?? ($att->employee->name ?? 'N/A') }}</strong>
                                    <br>
                                    @if($att->user)
                                    @php
                                        $roleNames = $att->user->getRoleNames()->map(fn($r)=>strtolower($r));
                                        if($roleNames->contains('technician')) $panelBadge = 'badge-info';
                                        elseif($roleNames->contains('reseller')) $panelBadge = 'badge-warning';
                                        elseif($roleNames->filter(fn($r)=>str_contains($r,'admin'))->isNotEmpty()) $panelBadge = 'badge-danger';
                                        else $panelBadge = 'badge-secondary';
                                        $roleLabel = ucfirst($roleNames->first() ?? 'staff');
                                    @endphp
                                    <span class="badge {{ $panelBadge }}" style="font-size:10px;">{{ $roleLabel }}</span>
                                    @endif
                                    @if($att->employee && $att->employee->employee_id)<small class="text-muted ml-1">{{ $att->employee->employee_id }}</small>@endif
                                </td>
                                <td>
                                    @if($checkIn)
                                        <span class="badge badge-success"><i class="fas fa-sign-in-alt mr-1"></i>{{ \Carbon\Carbon::parse($checkIn->check_in_time)->format('h:i A') }}</span>
                                        @if($checkIn->selfie_path)
                                            <img src="{{ asset('storage/' . $checkIn->selfie_path) }}" class="img-circle ml-1" width="28" height="28" style="object-fit:cover;cursor:pointer;" onclick="viewSelfie(this.src,'{{ $att->user->name ?? '' }}','{{ $checkIn->check_in_time }}','{{ $checkIn->latitude }}','{{ $checkIn->longitude }}','{{ $checkIn->location_address }}')">
                                        @endif
                                        &nbsp;
                                        @if($checkOut)
                                            <span class="badge badge-danger"><i class="fas fa-sign-out-alt mr-1"></i>{{ \Carbon\Carbon::parse($checkOut->check_in_time)->format('h:i A') }}</span>
                                            @if($checkOut->selfie_path)
                                                <img src="{{ asset('storage/' . $checkOut->selfie_path) }}" class="img-circle ml-1" width="28" height="28" style="object-fit:cover;cursor:pointer;" onclick="viewSelfie(this.src,'{{ $att->user->name ?? '' }}','{{ $checkOut->check_in_time }}','{{ $checkOut->latitude }}','{{ $checkOut->longitude }}','{{ $checkOut->location_address }}')">
                                            @endif
                                        @else
                                            <span class="text-muted small ml-1">No Check Out</span>
                                        @endif
                                    @else
                                        @if($checkOut)
                                            <span class="text-muted small">No Check In</span> &nbsp;
                                            <span class="badge badge-danger"><i class="fas fa-sign-out-alt mr-1"></i>{{ \Carbon\Carbon::parse($checkOut->check_in_time)->format('h:i A') }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($att->latitude && $att->longitude)
                                    <a href="{{ $att->google_maps_url }}" target="_blank" class="text-primary" title="{{ $att->location_address }}">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        {{ Str::limit($att->location_address ?? $att->latitude . ', ' . $att->longitude, 30) }}
                                    </a>
                                    @else
                                    <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                @if(auth()->user()->hasAnyRole(['Super Admin', 'superadmin']))
                                <td>
                                    <form action="{{ route('admin.hrm.selfie-attendance.destroy', $att->id) }}" method="POST" onsubmit="return confirm('Delete this attendance record?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="{{ auth()->user()->hasAnyRole(['Super Admin', 'superadmin']) ? 6 : 5 }}" class="text-center py-4 text-muted"><i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>No attendance records for this date</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="selfieModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-camera mr-2"></i>Attendance Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img id="modalSelfie" src="" class="img-fluid rounded mb-3" style="max-height: 400px;">
                <div id="modalInfo" class="text-left"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let stream = null;
let capturedImage = null;
let currentLat = null, currentLng = null, currentAddress = null;

function getDirectUrl() {
    return window.location.origin + '/admin/hrm/selfie-attendance';
}

function openInNewTab() {
    var url = getDirectUrl();
    var w = window.open(url, '_blank');
    if (!w || w.closed) {
        prompt('Popup blocked! Copy this URL and paste in a new browser tab:', url);
    }
}

function copyUrl() {
    var input = document.getElementById('directUrlInput');
    input.select();
    document.execCommand('copy');
    alert('URL copied! Paste it in a new browser tab.');
}

function showIframeWarning() {
    var url = getDirectUrl();
    document.getElementById('directUrlInput').value = url;
    document.getElementById('iframeWarning').classList.remove('d-none');
    document.getElementById('cameraSection').style.opacity = '0.5';
}

function isInIframe() {
    try { return window.self !== window.top; } catch(e) { return true; }
}

function showCameraPermissionGuide() {
    document.getElementById('resultMsg').innerHTML =
        '<div class="alert alert-warning text-left">' +
        '<strong><i class="fas fa-camera-slash mr-2"></i>Camera permission denied.</strong><br>' +
        '<ul class="mb-1 mt-2 pl-3">' +
        '<li>Click the <strong>🔒 or ℹ️ icon</strong> in the browser address bar</li>' +
        '<li>Click <strong>Camera</strong> → <strong>Allow</strong></li>' +
        '<li><strong>Refresh</strong> the page (F5)</li>' +
        '</ul>' +
        '<small class="text-muted">Or go to Chrome Settings → Privacy → Camera → Allow softynex.me</small>' +
        '</div>';
}

function startCamera() {
    document.getElementById('resultMsg').innerHTML = '';
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        if (isInIframe()) {
            showIframeWarning();
        } else {
            document.getElementById('resultMsg').innerHTML =
                '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Camera not supported in this browser. Please use Chrome or Firefox.</div>';
        }
        return;
    }
    navigator.mediaDevices.getUserMedia({ video: true })
    .then(function(s) {
        stream = s;
        let video = document.getElementById('videoPreview');
        video.srcObject = s;
        video.style.display = 'block';
        document.getElementById('btnStartCamera').classList.add('d-none');
        document.getElementById('btnCapture').classList.remove('d-none');
        document.getElementById('iframeWarning').classList.add('d-none');
        document.getElementById('cameraSection').style.opacity = '1';
        getLocation();
    })
    .catch(function(err) {
        var name = err.name || '';
        var msg = (err.message || '').toLowerCase();
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || msg.includes('denied') || msg.includes('permission')) {
            if (isInIframe()) {
                showIframeWarning();
            } else {
                showCameraPermissionGuide();
            }
        } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            document.getElementById('resultMsg').innerHTML =
                '<div class="alert alert-danger"><i class="fas fa-camera-slash mr-2"></i>No camera device found. Please check if your camera is connected.</div>';
        } else if (name === 'NotReadableError' || name === 'TrackStartError') {
            document.getElementById('resultMsg').innerHTML =
                '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Camera is being used by another app. Close other apps and try again.</div>';
        } else {
            document.getElementById('resultMsg').innerHTML =
                '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Camera error: ' + (err.message || err.name || 'Unknown error') + '</div>';
        }
    });
}

function getLocation() {
    document.getElementById('locationInfo').classList.remove('d-none');
    document.getElementById('locText').textContent = 'Fetching location...';
    document.getElementById('locationDeniedInfo').classList.add('d-none');

    if (!navigator.geolocation) {
        document.getElementById('locText').textContent = 'Location not supported';
        document.getElementById('locationDeniedInfo').classList.remove('d-none');
        return;
    }

    navigator.geolocation.getCurrentPosition(function(pos) {
        currentLat = pos.coords.latitude;
        currentLng = pos.coords.longitude;
        document.getElementById('latText').textContent = currentLat.toFixed(6);
        document.getElementById('lngText').textContent = currentLng.toFixed(6);
        document.getElementById('timeText').textContent = new Date().toLocaleTimeString('en-US');
        document.getElementById('locationDeniedInfo').classList.add('d-none');

        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + currentLat + '&lon=' + currentLng)
        .then(r => r.json())
        .then(data => {
            currentAddress = data.display_name || (currentLat + ', ' + currentLng);
            document.getElementById('locText').textContent = currentAddress.substring(0, 80);
        })
        .catch(() => {
            currentAddress = currentLat + ', ' + currentLng;
            document.getElementById('locText').textContent = currentAddress;
        });
    }, function(err) {
        currentLat = null; currentLng = null; currentAddress = null;
        document.getElementById('locText').textContent = 'Location not available';
        document.getElementById('locationDeniedInfo').classList.remove('d-none');
    }, { enableHighAccuracy: true, timeout: 10000 });
}

function capturePhoto() {
    let video = document.getElementById('videoPreview');
    let canvas = document.getElementById('selfieCanvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    capturedImage = canvas.toDataURL('image/jpeg', 0.8);

    document.getElementById('selfiePreview').src = capturedImage;
    document.getElementById('selfiePreview').style.display = 'block';
    video.style.display = 'none';

    document.getElementById('btnCapture').classList.add('d-none');
    document.getElementById('btnRetake').classList.remove('d-none');
    document.getElementById('submitSection').classList.remove('d-none');

    document.getElementById('timeText').textContent = new Date().toLocaleTimeString('en-US');

    if (stream) { stream.getTracks().forEach(t => t.stop()); }
}

function retakePhoto() {
    capturedImage = null;
    document.getElementById('selfiePreview').style.display = 'none';
    document.getElementById('btnRetake').classList.add('d-none');
    document.getElementById('submitSection').classList.add('d-none');
    startCamera();
}

function submitAttendance(type) {
    if (!capturedImage) { alert('Please capture a selfie first.'); return; }

    let btn = type === 'check_in' ? document.getElementById('btnCheckIn') : document.getElementById('btnCheckOut');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...'; }

    fetch('{{ route("admin.hrm.selfie-attendance.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            selfie: capturedImage,
            latitude: currentLat,
            longitude: currentLng,
            location_address: currentAddress,
            type: type
        })
    })
    .then(function(r) {
        if (r.status === 419) {
            document.getElementById('resultMsg').innerHTML = '<div class="alert alert-warning"><i class="fas fa-sync mr-2"></i>Session expired. Refreshing page...</div>';
            setTimeout(() => window.location.reload(), 1500);
            return null;
        }
        return r.json();
    })
    .then(function(data) {
        if (!data) return;
        if (data.success) {
            document.getElementById('resultMsg').innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>' + data.message + '</div>';
            setTimeout(() => window.location.reload(), 1500);
        } else {
            var errMsg = data.message || data.error || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed');
            document.getElementById('resultMsg').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>' + errMsg + '</div>';
            if (btn) { btn.disabled = false; btn.innerHTML = type === 'check_in' ? '<i class="fas fa-sign-in-alt mr-2"></i>Check In' : '<i class="fas fa-sign-out-alt mr-2"></i>Check Out'; }
        }
    })
    .catch(function(err) {
        document.getElementById('resultMsg').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Network error. Please check your internet connection.</div>';
        if (btn) { btn.disabled = false; }
    });
}

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function viewSelfie(src, name, time, lat, lng, address) {
    document.getElementById('modalSelfie').src = src;
    var safeName = escHtml(name), safeTime = escHtml(time), safeLat = escHtml(lat), safeLng = escHtml(lng), safeAddr = escHtml(address || (lat + ', ' + lng));
    document.getElementById('modalInfo').innerHTML =
        '<table class="table table-sm table-bordered">' +
        '<tr><th width="120">Employee</th><td>' + safeName + '</td></tr>' +
        '<tr><th>Time</th><td>' + safeTime + '</td></tr>' +
        '<tr><th>Location</th><td>' + safeAddr + '</td></tr>' +
        '<tr><th>Coordinates</th><td><a href="https://www.google.com/maps?q=' + safeLat + ',' + safeLng + '" target="_blank">' + safeLat + ', ' + safeLng + ' <i class="fas fa-external-link-alt ml-1"></i></a></td></tr>' +
        '</table>';
    $('#selfieModal').modal('show');
}
</script>
@endpush
