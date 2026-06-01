@extends('layouts.technician')

@section('title', 'Edit Client')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('technician.clients') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('technician.clients.show', $client) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Edit Client: {{ $client->name }}</h3>
    </div>
    <form action="{{ route('technician.clients.update', $client) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <h5 class="mb-3"><i class="fas fa-user mr-2"></i>Personal Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-user mr-1"></i> Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $client->name) }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-phone mr-1"></i> Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $client->phone) }}" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-phone-alt mr-1"></i> Alt Phone</label>
                        <input type="text" name="alt_phone" class="form-control" value="{{ old('alt_phone', $client->alt_phone) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-envelope mr-1"></i> Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-map-marker-alt mr-1"></i> Address <span class="text-danger">*</span></label>
                <textarea name="address" id="address" class="form-control" rows="2" required>{{ old('address', $client->address) }}</textarea>
            </div>

            <hr>
            <h5 class="mb-3"><i class="fas fa-map-marked-alt mr-2"></i>Map Location</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-globe mr-1"></i> Latitude</label>
                        <input type="text" name="google_map_lat" id="google_map_lat" class="form-control" value="{{ old('google_map_lat', $client->google_map_lat) }}" readonly placeholder="Auto-detected">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-globe mr-1"></i> Longitude</label>
                        <input type="text" name="google_map_lng" id="google_map_lng" class="form-control" value="{{ old('google_map_lng', $client->google_map_lng) }}" readonly placeholder="Auto-detected">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-info btn-block" onclick="getLocationFromAddress()">
                            <i class="fas fa-search-location mr-1"></i> Auto-Find Location
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-block mt-1" onclick="openMapPicker()">
                            <i class="fas fa-map-pin mr-1"></i> Pick Exact on Map
                        </button>
                    </div>
                </div>
            </div>
            <div id="locationStatus" class="mb-3" style="display: none;"></div>
            <a href="#" id="viewMapBtn" target="_blank" class="btn btn-outline-success btn-block mb-3" style="display: none;">
                <i class="fas fa-external-link-alt mr-1"></i> View Location on Map
            </a>

            <!-- Map Picker Modal -->
            <div class="modal fade" id="mapPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h5 class="modal-title"><i class="fas fa-map-marked-alt mr-2 text-primary"></i>Pick Exact Location</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body p-0">
                            <div style="padding:8px 12px;background:#f8f9fa;border-bottom:1px solid #dee2e6;display:flex;gap:8px;align-items:center;">
                                <input type="text" id="mapSearchInput" class="form-control form-control-sm" placeholder="Search address... e.g. Dhakeshwari Rd, Dhaka" style="flex:1;">
                                <button class="btn btn-sm btn-primary" onclick="searchOnMap()"><i class="fas fa-search"></i> Search</button>
                                <span style="font-size:12px;color:#6c757d;white-space:nowrap;"><i class="fas fa-info-circle"></i> Click map for exact pin</span>
                            </div>
                            <div id="leafletMap" style="height:460px;width:100%;"></div>
                        </div>
                        <div class="modal-footer py-2">
                            <div style="flex:1;font-size:13px;" id="mapPickedCoords">No location selected yet. Click on the map to pick.</div>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success btn-sm" id="confirmMapPick" onclick="confirmMapLocation()" disabled><i class="fas fa-check mr-1"></i>Use This Location</button>
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="mb-3"><i class="fas fa-sticky-note mr-2"></i>Notes</h5>
            <div class="form-group">
                <label><i class="fas fa-comment mr-1"></i> Remarks</label>
                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $client->remarks) }}</textarea>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Client</button>
            <a href="{{ route('technician.clients.show', $client) }}" class="btn btn-secondary"><i class="fas fa-times mr-1"></i> Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function updateMapButton() {
    var lat = document.getElementById('google_map_lat').value;
    var lng = document.getElementById('google_map_lng').value;
    var btn = document.getElementById('viewMapBtn');
    if (lat && lng) {
        btn.href = 'https://www.google.com/maps?q=' + lat + ',' + lng;
        btn.style.display = 'block';
    } else {
        btn.style.display = 'none';
    }
}

function showLocationStatus(message, type) {
    var statusDiv = document.getElementById('locationStatus');
    statusDiv.innerHTML = '<div class="alert alert-' + type + ' mb-0">' + message + '</div>';
    statusDiv.style.display = 'block';
}

function getLocationFromAddress() {
    var address = document.getElementById('address').value.trim();
    if (!address) { showLocationStatus('<i class="fas fa-exclamation-triangle mr-1"></i>Enter address first.', 'warning'); return; }
    showLocationStatus('<i class="fas fa-spinner fa-spin mr-1"></i>Searching...', 'info');
    var key = '37e22662c7614c5996dacf00d6279b95';
    var q = address + ', Dhaka, Bangladesh';
    fetch('https://api.opencagedata.com/geocode/v1/json?q=' + encodeURIComponent(q) + '&key=' + key + '&countrycode=bd&limit=5&language=en&no_annotations=1')
    .then(r=>r.json()).then(function(d){
        if (d.results && d.results.length > 0) {
            var r = d.results[0];
            document.getElementById('lat').value = r.geometry.lat.toFixed(6);
            document.getElementById('lng').value = r.geometry.lng.toFixed(6);
            document.getElementById('google_map_location').value = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(r.formatted);
            updateMapButton();
            showLocationStatus('<i class="fas fa-check-circle mr-1"></i>Found: ' + r.formatted, 'success');
        } else {
            showLocationStatus('<i class="fas fa-times-circle mr-1"></i>Not found. Try with area name.', 'danger');
        }
    }).catch(function(){ showLocationStatus('<i class="fas fa-times-circle mr-1"></i>Error. Check internet.', 'danger'); });
}

document.addEventListener('DOMContentLoaded', function() {
    updateMapButton();
});
</script>

// ── Map Picker (Leaflet + OSM) ──
var _mapInst = null, _mapMarker = null, _pickedLat = null, _pickedLng = null;
function openMapPicker() {
    var existLat = parseFloat(document.getElementById('lat').value) || 23.8103;
    var existLng = parseFloat(document.getElementById('lng').value) || 90.4125;
    var addrVal  = document.getElementById('address') ? document.getElementById('address').value.trim() : '';
    document.getElementById('mapSearchInput').value = addrVal || '';
    $('#mapPickerModal').modal('show');
    setTimeout(function() {
        if (!_mapInst) {
            _mapInst = L.map('leafletMap').setView([existLat, existLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
            }).addTo(_mapInst);
            _mapInst.on('click', function(e) { placeMapPin(e.latlng.lat, e.latlng.lng); });
        } else {
            _mapInst.invalidateSize();
            _mapInst.setView([existLat, existLng], 15);
        }
        if (document.getElementById('lat').value) {
            placeMapPin(existLat, existLng);
        } else if (addrVal) {
            searchOnMap();
        }
    }, 320);
}
function placeMapPin(lat, lng) {
    _pickedLat = lat; _pickedLng = lng;
    if (_mapMarker) { _mapMarker.setLatLng([lat, lng]); }
    else {
        _mapMarker = L.marker([lat, lng], {draggable: true}).addTo(_mapInst);
        _mapMarker.on('dragend', function(e) {
            var pos = e.target.getLatLng();
            placeMapPin(pos.lat, pos.lng);
        });
    }
    _mapInst.panTo([lat, lng]);
    document.getElementById('mapPickedCoords').innerHTML = '<i class="fas fa-map-pin text-danger mr-1"></i><b>Lat:</b> ' + lat.toFixed(6) + '  <b>Lng:</b> ' + lng.toFixed(6) + ' &nbsp;<small class="text-muted">(drag pin to adjust)</small>';
    document.getElementById('confirmMapPick').disabled = false;
}
function searchOnMap() {
    var q = document.getElementById('mapSearchInput').value.trim();
    if (!q) return;
    fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q + ', Bangladesh') + '&limit=1&countrycodes=bd', {headers:{'User-Agent':'SmartOnlineISP/1.0'}})
    .then(r=>r.json()).then(function(d){
        if (d && d.length > 0) {
            placeMapPin(parseFloat(d[0].lat), parseFloat(d[0].lon));
        } else {
            fetch('https://photon.komoot.io/api/?q=' + encodeURIComponent(q + ' Bangladesh') + '&limit=1&bbox=88.0,20.6,92.7,26.6')
            .then(r=>r.json()).then(function(p){
                if (p && p.features && p.features.length > 0) {
                    var c = p.features[0].geometry.coordinates;
                    placeMapPin(c[1], c[0]);
                } else { alert('Address not found on map. Click manually on map.'); }
            }).catch(function(){ alert('Search failed. Click manually on map.'); });
        }
    }).catch(function(){ alert('Search failed. Click manually on map.'); });
}
function confirmMapLocation() {
    if (_pickedLat === null) return;
    document.getElementById('lat').value = _pickedLat.toFixed(6);
    document.getElementById('lng').value = _pickedLng.toFixed(6);
    document.getElementById('google_map_location').value = 'https://www.google.com/maps?q=' + _pickedLat + ',' + _pickedLng;
    updateMapButton();
    showLocationStatus('<i class="fas fa-check-circle mr-1"></i>Location set from map pin!', 'success');
    $('#mapPickerModal').modal('hide');
}
document.getElementById('mapSearchInput') && document.getElementById('mapSearchInput').addEventListener('keydown', function(e){ if(e.key==='Enter'){e.preventDefault();searchOnMap();} });

// ── OpenCage Address Autocomplete ──
(function(){
    var _oc_timer = null, _oc_key = '37e22662c7614c5996dacf00d6279b95';
    var addrEl = document.getElementById('address');
    if (!addrEl) return;

    // Create suggestions dropdown
    var sug = document.createElement('div');
    sug.id = 'ocSuggestions';
    sug.style.cssText = 'position:absolute;z-index:9999;background:#fff;border:1px solid #ced4da;border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,0.12);max-height:220px;overflow-y:auto;display:none;width:100%;left:0;top:100%;margin-top:2px;';
    var wrapper = addrEl.parentNode;
    if (wrapper.style.position !== 'relative') wrapper.style.position = 'relative';
    wrapper.appendChild(sug);

    addrEl.addEventListener('input', function(){
        clearTimeout(_oc_timer);
        var q = addrEl.value.trim();
        if (q.length < 4) { sug.style.display='none'; return; }
        _oc_timer = setTimeout(function(){
            fetch('https://api.opencagedata.com/geocode/v1/json?q=' + encodeURIComponent(q + ' Bangladesh') +
                  '&key=' + _oc_key + '&countrycode=bd&limit=6&language=en&no_annotations=1&abbrv=1')
            .then(r=>r.json()).then(function(d){
                sug.innerHTML = '';
                if (!d.results || !d.results.length) { sug.style.display='none'; return; }
                d.results.forEach(function(r){
                    var item = document.createElement('div');
                    item.style.cssText = 'padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f1f5f9;color:#1e293b;';
                    item.innerHTML = '<i class="fas fa-map-marker-alt text-danger mr-2" style="font-size:11px;"></i>' + r.formatted;
                    item.addEventListener('mouseenter', function(){ item.style.background='#f0f9ff'; });
                    item.addEventListener('mouseleave', function(){ item.style.background='#fff'; });
                    item.addEventListener('mousedown', function(e){
                        e.preventDefault();
                        addrEl.value = r.formatted;
                        document.getElementById('lat').value = r.geometry.lat.toFixed(6);
                        document.getElementById('lng').value = r.geometry.lng.toFixed(6);
                        document.getElementById('google_map_location').value = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(r.formatted);
                        updateMapButton();
                        showLocationStatus('<i class="fas fa-check-circle mr-1"></i>' + r.formatted, 'success');
                        sug.style.display = 'none';
                    });
                    sug.appendChild(item);
                });
                sug.style.display = 'block';
            }).catch(function(){});
        }, 550);
    });

    addrEl.addEventListener('blur', function(){ setTimeout(function(){ sug.style.display='none'; }, 200); });
    addrEl.addEventListener('focus', function(){ if(sug.children.length) sug.style.display='block'; });
})();
</script>
@endpush
@endsection
