@extends('layouts.admin')

@section('title', 'Attendance Map')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.selfie-attendance') }}">Selfie Attendance</a></li>
<li class="breadcrumb-item active">Map View</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-map-marked-alt mr-2"></i>Attendance Location Map</h3>
            <div class="card-tools">
                <form class="form-inline" method="GET">
                    <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Search</button>
                    <a href="{{ route('admin.hrm.selfie-attendance') }}" class="btn btn-sm btn-secondary ml-2"><i class="fas fa-arrow-left mr-1"></i>Back</a>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="map" style="height: 550px; width: 100%;"></div>
        </div>
        <div class="card-footer">
            <span class="badge badge-success mr-2"><i class="fas fa-sign-in-alt mr-1"></i>Check In</span>
            <span class="badge badge-danger mr-2"><i class="fas fa-sign-out-alt mr-1"></i>Check Out</span>
            @if($settings['office_lat'])
            <span class="badge badge-primary mr-2"><i class="fas fa-building mr-1"></i>Office: {{ $settings['office_name'] ?? 'Main Office' }} ({{ $settings['office_radius'] }}m radius)</span>
            @endif
            <span class="float-right text-muted small">{{ $attendances->count() }} location(s) on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
        </div>
    </div>

    @if($attendances->isEmpty())
    <div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>No attendance with location data found for this date.</div>
    @endif
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
var map = L.map('map').setView([23.8103, 90.4125], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

var greenIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34] });
var redIcon   = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',   iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34] });
var blueIcon  = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34] });

var bounds = [];

@php $settings = \App\Models\EmployeeSelfieAttendance::getSettings(); @endphp
@if($settings['office_lat'])
var officeLatLng = [{{ $settings['office_lat'] }}, {{ $settings['office_lng'] }}];
L.marker(officeLatLng, {icon: blueIcon})
  .addTo(map)
  .bindPopup('<b><i class="fas fa-building"></i> {{ $settings["office_name"] ?? "Main Office" }}</b><br>Office Location<br>Radius: {{ $settings["office_radius"] }}m');
L.circle(officeLatLng, {color: 'blue', fillColor: '#3388ff', fillOpacity: 0.1, radius: {{ $settings['office_radius'] }}}).addTo(map);
bounds.push(officeLatLng);
@endif

@foreach($attendances as $att)
@if($att->latitude && $att->longitude)
var latLng{{ $att->id }} = [{{ $att->latitude }}, {{ $att->longitude }}];
var icon{{ $att->id }} = {{ $att->type === 'check_in' ? 'greenIcon' : 'redIcon' }};
var selfieHtml{{ $att->id }} = '';
@if($att->selfie_path)
selfieHtml{{ $att->id }} = '<img src="{{ asset('storage/'.$att->selfie_path) }}" style="width:80px;height:80px;object-fit:cover;border-radius:50%;margin-bottom:6px;"><br>';
@endif
L.marker(latLng{{ $att->id }}, {icon: icon{{ $att->id }}})
  .addTo(map)
  .bindPopup(
    selfieHtml{{ $att->id }} +
    '<b>{{ $att->user->name ?? "N/A" }}</b><br>' +
    '<span class="badge badge-{{ $att->type === "check_in" ? "success" : "danger" }}">{{ $att->type === "check_in" ? "Check In" : "Check Out" }}</span><br>' +
    '<i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($att->check_in_time)->format("h:i A") }}<br>' +
    '<i class="fas fa-map-marker-alt"></i> {{ Str::limit($att->location_address ?? $att->latitude.",".$att->longitude, 40) }}<br>' +
    '<a href="{{ $att->google_maps_url }}" target="_blank" class="btn btn-xs btn-outline-primary mt-1"><i class="fas fa-external-link-alt"></i> Google Maps</a>'
  );
bounds.push(latLng{{ $att->id }});
@endif
@endforeach

if (bounds.length > 0) {
    map.fitBounds(bounds, {padding: [40, 40]});
}
</script>
@endsection
