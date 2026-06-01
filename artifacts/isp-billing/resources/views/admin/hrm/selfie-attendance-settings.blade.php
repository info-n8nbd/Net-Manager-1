@extends('layouts.admin')

@section('title', 'Attendance Settings')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.selfie-attendance') }}">Selfie Attendance</a></li>
<li class="breadcrumb-item active">Settings</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-7">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Attendance Configuration</h3>
                </div>
                <form action="{{ route('admin.hrm.selfie-attendance.settings.save') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <h5 class="text-primary border-bottom pb-2 mb-3"><i class="fas fa-building mr-2"></i>Office Location</h5>
                        <div class="form-group">
                            <label>Office Name</label>
                            <input type="text" name="office_name" class="form-control" value="{{ $settings['office_name'] ?? 'Main Office' }}" placeholder="e.g. Head Office">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Latitude <small class="text-muted">(e.g. 23.8103)</small></label>
                                    <input type="number" name="office_lat" class="form-control" step="any" value="{{ $settings['office_lat'] }}" placeholder="23.8103" id="officeLat">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Longitude <small class="text-muted">(e.g. 90.4125)</small></label>
                                    <input type="number" name="office_lng" class="form-control" step="any" value="{{ $settings['office_lng'] }}" placeholder="90.4125" id="officeLng">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Allowed Radius (meters) — employees within this distance = Valid</label>
                            <input type="number" name="office_radius" class="form-control" value="{{ $settings['office_radius'] ?? 200 }}" min="10" max="10000">
                            <small class="text-muted">Recommended: 100–500 meters for a single office</small>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="getMyLocation()">
                            <i class="fas fa-crosshairs mr-1"></i>Use My Current Location as Office
                        </button>

                        <h5 class="text-primary border-bottom pb-2 mb-3 mt-3"><i class="fas fa-clock mr-2"></i>Time Rules</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Late Check-In After</label>
                                    <input type="time" name="late_time" class="form-control" value="{{ $settings['late_time'] ?? '09:00' }}">
                                    <small class="text-muted">Check-in after this time = Late</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Early Leave Before</label>
                                    <input type="time" name="early_leave_time" class="form-control" value="{{ $settings['early_leave_time'] ?? '17:00' }}">
                                    <small class="text-muted">Check-out before this time = Early Leave</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Settings</button>
                        <a href="{{ route('admin.hrm.selfie-attendance') }}" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-light">
                    <h3 class="card-title"><i class="fas fa-map mr-2"></i>Office Location Preview</h3>
                </div>
                <div class="card-body p-0">
                    <div id="settingsMap" style="height: 300px;"></div>
                </div>
                <div class="card-footer text-muted small">
                    @if($settings['office_lat'])
                    <i class="fas fa-map-marker-alt text-primary mr-1"></i>{{ $settings['office_name'] ?? 'Office' }}: {{ $settings['office_lat'] }}, {{ $settings['office_lng'] }}
                    @else
                    <i class="fas fa-info-circle mr-1"></i>Set latitude & longitude to see office on map
                    @endif
                </div>
            </div>
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>How to get office coordinates?</h3>
                </div>
                <div class="card-body">
                    <ol class="pl-3 small">
                        <li>Open <a href="https://maps.google.com" target="_blank">Google Maps</a></li>
                        <li>Right-click on your office location</li>
                        <li>Click the coordinates at the top of the menu</li>
                        <li>Paste the first number as Latitude, second as Longitude</li>
                    </ol>
                    <p class="small text-muted mb-0">Or click "Use My Current Location" button if you are at the office.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var settingsMap = L.map('settingsMap').setView([23.8103, 90.4125], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(settingsMap);

var officeMarker = null;
var officeCircle = null;

function updateMapMarker(lat, lng, radius) {
    if (officeMarker) { settingsMap.removeLayer(officeMarker); settingsMap.removeLayer(officeCircle); }
    officeMarker = L.marker([lat, lng]).addTo(settingsMap).bindPopup('Office Location').openPopup();
    officeCircle = L.circle([lat, lng], {color: 'blue', fillOpacity: 0.1, radius: radius}).addTo(settingsMap);
    settingsMap.setView([lat, lng], 15);
}

@if($settings['office_lat'])
updateMapMarker({{ $settings['office_lat'] }}, {{ $settings['office_lng'] }}, {{ $settings['office_radius'] }});
@endif

function getMyLocation() {
    if (!navigator.geolocation) { alert('Geolocation not supported'); return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
        document.getElementById('officeLat').value = pos.coords.latitude.toFixed(7);
        document.getElementById('officeLng').value = pos.coords.longitude.toFixed(7);
        updateMapMarker(pos.coords.latitude, pos.coords.longitude, 200);
    }, function(err) { alert('Could not get location: ' + err.message); });
}

document.getElementById('officeLat').addEventListener('change', updateFromInputs);
document.getElementById('officeLng').addEventListener('change', updateFromInputs);
function updateFromInputs() {
    var lat = parseFloat(document.getElementById('officeLat').value);
    var lng = parseFloat(document.getElementById('officeLng').value);
    if (!isNaN(lat) && !isNaN(lng)) updateMapMarker(lat, lng, 200);
}
</script>
@endsection
