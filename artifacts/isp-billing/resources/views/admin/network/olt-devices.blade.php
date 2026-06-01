@extends('layouts.admin')

@section('title', 'OLT Devices')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item active">OLT Devices</li>
@endsection

@push('styles')
<style>
.olt-table th { font-size: 12px; text-transform: uppercase; white-space: nowrap; }
.olt-table td { vertical-align: middle; }
.olt-info-sub { font-size: 11px; color: #888; }
.olt-status-badge { font-size: 11px; padding: 4px 12px; border-radius: 3px; font-weight: 600; letter-spacing: 0.5px; }
.olt-status-connected { background: #28a745; color: #fff; }
.olt-status-disconnected { background: #dc3545; color: #fff; }
.onu-stat { display: inline-block; margin-right: 2px; font-size: 12px; font-weight: 600; }
.onu-stat-online { color: #28a745; }
.onu-stat-offline { color: #dc3545; }
.onu-stat-total { color: #17a2b8; }
.vendor-badge { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 3px; }
.vendor-vsol { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.vendor-bdcom { background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a; }
.vendor-cdata { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
.vendor-huawei { background: #fff3e0; color: #e65100; border: 1px solid #ffcc80; }
.vendor-zte { background: #f3e5f5; color: #6a1b9a; border: 1px solid #ce93d8; }
.vendor-fiberhome { background: #e0f2f1; color: #00695c; border: 1px solid #80cbc4; }
.vendor-solitine { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
.vendor-gcom { background: #ede7f6; color: #4527a0; border: 1px solid #b39ddb; }
.vendor-dbc_v, .vendor-dbc_b { background: #efebe9; color: #4e342e; border: 1px solid #bcaaa4; }
.vendor-netlink { background: #e8eaf6; color: #283593; border: 1px solid #9fa8da; }
.vendor-other { background: #f5f5f5; color: #616161; border: 1px solid #bdbdbd; }
.snmp-info { font-size: 12px; }
.snmp-info code { font-size: 11px; }
.action-btn { width: 28px; height: 28px; padding: 0; line-height: 28px; text-align: center; font-size: 12px; }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header bg-dark text-white py-2">
        <h3 class="card-title"><i class="fas fa-broadcast-tower mr-2"></i>OLT</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-outline-warning btn-sm mr-1" data-toggle="modal" data-target="#oltGuideModal">
                <i class="fas fa-book mr-1"></i>GUIDE
            </button>
            <a href="{{ route('admin.olt.index') }}" class="btn btn-outline-light btn-sm mr-1">
                <i class="fas fa-globe mr-1"></i>NETWORK
            </a>
            <a href="{{ route('admin.olt.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i>ADD OLT
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0 olt-table">
                <thead style="background: #343a40; color: #fff;">
                    <tr>
                        <th width="50">OLT ID</th>
                        <th>OLT INFO</th>
                        <th width="80" class="text-center">STATUS</th>
                        <th>SNMP INFO</th>
                        <th width="140">ONU</th>
                        <th width="70" class="text-center">Check</th>
                        <th width="130" class="text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($olts as $olt)
                    @php
                        $counts = $onuCounts[$olt->id] ?? null;
                        $total = $counts ? $counts->total : 0;
                        $online = $counts ? $counts->online_count : 0;
                        $offline = $counts ? $counts->offline_count : 0;
                        $isConnected = in_array($olt->status, ['active', 'online', 'connected']);
                        $vendorClass = 'vendor-' . str_replace('-', '_', strtolower($olt->vendor));
                    @endphp
                    <tr>
                        <td class="text-center"><strong>{{ $olt->id }}</strong></td>
                        <td>
                            <strong>{{ $olt->name }}</strong><br>
                            <span class="olt-info-sub">
                                {{ $olt->vendor_name }} ({{ $olt->vendor_name }} - {{ $olt->pon_type_name }}0/{{ $olt->total_ports }})
                            </span><br>
                            <span class="olt-info-sub">{{ $olt->model ?? 'N/A' }}{{ $olt->last_sync ? ' - ' . $olt->last_sync->diffForHumans() : '' }}</span>
                        </td>
                        <td class="text-center">
                            @if($isConnected)
                                <span class="olt-status-badge olt-status-connected">CONNECTED</span>
                            @else
                                <span class="olt-status-badge olt-status-disconnected">DISCONNECTED</span>
                            @endif
                            <br>
                            <span class="vendor-badge {{ $vendorClass }} mt-1 d-inline-block">{{ $olt->vendor_name }}</span>
                        </td>
                        <td>
                            <div class="snmp-info">
                                <code>IP: {{ $olt->ip_address }}</code>
                                @if($olt->snmp_enabled)
                                    <i class="fas fa-check-circle text-success ml-1" title="SNMP Enabled"></i>
                                @endif
                                <br>
                                <span class="text-muted">Discovered: </span><span class="text-success font-weight-bold">{{ $total }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="onu-stat">Online: <span class="onu-stat-online">{{ $online }}</span></span><br>
                            <span class="onu-stat">Offline: <span class="onu-stat-offline">{{ $offline }}</span></span><br>
                            <span class="onu-stat">Total: <span class="onu-stat-total">{{ $total }}</span></span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-success action-btn" onclick="testConnection({{ $olt->id }})" title="Test Connection">
                                <i class="fas fa-plug"></i>
                            </button>
                            <button type="button" class="btn btn-outline-info action-btn" onclick="syncOlt({{ $olt->id }})" title="Sync ONUs">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="{{ route('admin.olt.dashboard', $olt) }}" class="btn btn-primary action-btn" title="Dashboard">
                                    <i class="fas fa-tachometer-alt"></i>
                                </a>
                                <a href="{{ route('admin.olt.onus', $olt) }}" class="btn btn-success action-btn" title="ONU Status">
                                    <i class="fas fa-network-wired"></i>
                                </a>
                                <a href="{{ route('admin.olt.edit', $olt) }}" class="btn btn-warning action-btn" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.olt.destroy', $olt) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this OLT?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger action-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-broadcast-tower fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">No OLT devices found. <a href="{{ route('admin.olt.create') }}">Add one now</a>.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($olts->hasPages())
    <div class="card-footer">
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $olts->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script>
    </div>
    @endif
</div>

<div class="card card-outline card-info mt-3">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Supported OLT Vendors</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            @php
                $vendorColors = [
                    'Huawei' => 'bg-gradient-warning',
                    'ZTE' => 'bg-gradient-purple',
                    'BDCOM' => 'bg-gradient-danger',
                    'VSOL' => 'bg-gradient-success',
                    'FiberHome' => 'bg-gradient-teal',
                    'C-Data' => 'bg-gradient-primary',
                    'Solitine' => 'bg-gradient-orange',
                    'GCOM' => 'bg-gradient-indigo',
                    'DBC-V' => 'bg-gradient-maroon',
                    'DBC-B' => 'bg-gradient-navy',
                    'Netlink' => 'bg-gradient-info',
                    'Other' => 'bg-gradient-secondary',
                ];
            @endphp
            @php
                $vendorExt = [
                    "huawei" => "avif",
                    "zte"    => "png",
                    "bdcom"  => "jpg",
                    "vsol"   => "png",
                ];
            @endphp
            @foreach(\App\Models\OltDevice::VENDORS as $key => $name)
            @php $imgExt = $vendorExt[$key] ?? "svg"; @endphp
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-2">
                <div class="text-center" style="padding:8px 4px;">
                    <img src="/images/vendors/{{ $key }}.{{ $imgExt }}" alt="{{ $name }}" style="width:80px; height:60px; object-fit:contain; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.1);">
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- OLT Guide Modal -->
<div class="modal fade" id="oltGuideModal" tabindex="-1" role="dialog" aria-labelledby="oltGuideModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="oltGuideModalLabel"><i class="fas fa-book mr-2"></i>OLT / EPON / GPON / ONU &mdash; সম্পূর্ণ গাইড</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="font-size:14px;">
        <div class="row mb-3">
          <div class="col-12"><h5 class="text-dark border-bottom pb-2"><i class="fas fa-info-circle text-primary mr-2"></i>মূল ধারণা</h5></div>
          <div class="col-md-4">
            <div class="card card-outline card-primary h-100">
              <div class="card-header py-2 bg-primary text-white"><strong><i class="fas fa-server mr-1"></i>OLT (Optical Line Terminal)</strong></div>
              <div class="card-body py-2">
                <ul class="mb-0 pl-3">
                  <li>ISP-এর <strong>মূল ডিভাইস</strong> &mdash; datacenter বা exchange-এ থাকে</li>
                  <li>Fiber optic নেটওয়ার্কের "মাথা"</li>
                  <li>সব ONU/ONT নিয়ন্ত্রণ করে</li>
                  <li>উদাহরণ: Huawei MA5800, ZTE C300, BDCOM P3310</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card card-outline card-success h-100">
              <div class="card-header py-2 bg-success text-white"><strong><i class="fas fa-network-wired mr-1"></i>ONU/ONT (Optical Network Unit)</strong></div>
              <div class="card-body py-2">
                <ul class="mb-0 pl-3">
                  <li>গ্রাহকের <strong>বাড়িতে থাকে</strong> (Fiber modem)</li>
                  <li>OLT থেকে সিগন্যাল নিয়ে গ্রাহককে ইন্টারনেট দেয়</li>
                  <li>ONT ও ONU একই &mdash; শুধু নাম আলাদা</li>
                  <li>প্রতিটা OLT port-এ অনেকগুলো ONU সংযুক্ত থাকে</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card card-outline card-info h-100">
              <div class="card-header py-2 bg-info text-white"><strong><i class="fas fa-sitemap mr-1"></i>সংযোগ চিত্র</strong></div>
              <div class="card-body py-2">
                <div class="bg-light p-2 rounded" style="font-family:monospace; font-size:13px; line-height:2;">
                  OLT (ISP অফিস)<br>
                  &nbsp;&nbsp;&darr; Fiber<br>
                  Splitter (1:32 বা 1:64)<br>
                  &nbsp;&nbsp;&darr; Fiber<br>
                  ONU/ONT (গ্রাহকের বাড়ি)<br>
                  &nbsp;&nbsp;&darr; Ethernet/WiFi<br>
                  গ্রাহকের ডিভাইস
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-12"><h5 class="text-dark border-bottom pb-2"><i class="fas fa-exchange-alt text-warning mr-2"></i>EPON vs GPON তুলনা</h5></div>
          <div class="col-12">
            <div class="table-responsive">
              <table class="table table-bordered table-sm table-striped">
                <thead class="thead-dark">
                  <tr><th>বিষয়</th><th class="text-center">EPON (Ethernet PON)</th><th class="text-center">GPON (Gigabit PON)</th></tr>
                </thead>
                <tbody>
                  <tr><td><strong>Standard</strong></td><td class="text-center">IEEE 802.3ah</td><td class="text-center">ITU-T G.984</td></tr>
                  <tr><td><strong>Downstream Speed</strong></td><td class="text-center">১.২৫ Gbps (Symmetrical)</td><td class="text-center text-success font-weight-bold">২.৪৮৮ Gbps (Asymmetrical)</td></tr>
                  <tr><td><strong>Upstream Speed</strong></td><td class="text-center">১.২৫ Gbps</td><td class="text-center">১.২৪৪ Gbps</td></tr>
                  <tr><td><strong>Max ONU per PON Port</strong></td><td class="text-center"><span class="badge badge-warning">৬৪টি</span></td><td class="text-center"><span class="badge badge-success">১২৮টি</span></td></tr>
                  <tr><td><strong>Practical ONU (সাধারণত)</strong></td><td class="text-center">৩২টি</td><td class="text-center">৬৪টি</td></tr>
                  <tr><td><strong>Max Distance</strong></td><td class="text-center">২০ km</td><td class="text-center">২০-৩০ km</td></tr>
                  <tr><td><strong>Optical Budget</strong></td><td class="text-center">২৪-২৮ dB</td><td class="text-center text-success font-weight-bold">২৮-৩২ dB</td></tr>
                  <tr><td><strong>Bandwidth Efficiency</strong></td><td class="text-center">৭০-৮০%</td><td class="text-center text-success font-weight-bold">৯২% পর্যন্ত</td></tr>
                  <tr><td><strong>QoS</strong></td><td class="text-center">Basic (802.1p tags)</td><td class="text-center text-success">Advanced (Ethernet, TDM, ATM)</td></tr>
                  <tr><td><strong>Hardware Cost</strong></td><td class="text-center text-success font-weight-bold">কম (সস্তা)</td><td class="text-center">একটু বেশি</td></tr>
                  <tr><td><strong>কার জন্য ভালো</strong></td><td class="text-center"><span class="badge badge-secondary">ছোট ISP</span></td><td class="text-center"><span class="badge badge-primary">বড় ISP</span></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <h5 class="text-dark border-bottom pb-2"><i class="fas fa-signal text-danger mr-2"></i>Splitter Loss রেফারেন্স</h5>
            <div class="table-responsive">
              <table class="table table-bordered table-sm table-striped">
                <thead class="thead-dark">
                  <tr><th>Split Ratio</th><th class="text-center">Theoretical Loss</th><th class="text-center">Typical Insertion Loss</th></tr>
                </thead>
                <tbody>
                  <tr><td>১:২</td><td class="text-center">৩.০ dB</td><td class="text-center">~৩.৫ dB</td></tr>
                  <tr><td>১:৪</td><td class="text-center">৬.০ dB</td><td class="text-center">~৭.০ dB</td></tr>
                  <tr><td>১:৮</td><td class="text-center">৯.০ dB</td><td class="text-center">~১০.৫ dB</td></tr>
                  <tr><td>১:১৬</td><td class="text-center">১২.০ dB</td><td class="text-center">~১৩.৫ dB</td></tr>
                  <tr><td>১:৩২</td><td class="text-center">১৫.০ dB</td><td class="text-center">~১৭.০ dB</td></tr>
                  <tr><td>১:৬৪</td><td class="text-center">১৮.০ dB</td><td class="text-center">~২০.৫ dB</td></tr>
                </tbody>
              </table>
            </div>
            <small class="text-muted"><i class="fas fa-lightbulb text-warning mr-1"></i>প্রতিবার split দ্বিগুণ হলে ~৩.৫ dB loss বাড়ে।</small>
          </div>
          <div class="col-md-6">
            <h5 class="text-dark border-bottom pb-2"><i class="fas fa-calculator text-success mr-2"></i>Total Link Loss হিসাব</h5>
            <div class="bg-dark text-success p-3 rounded" style="font-family:monospace; font-size:12px; line-height:2;">
              L<sub>Total</sub> = (L<sub>Fiber</sub> &times; দূরত্ব) + L<sub>Splitter</sub> + L<sub>Connectors</sub> + L<sub>Splices</sub><br>
              <hr style="border-color:#555; margin:6px 0;">
              <span class="text-warning">Fiber Attenuation:</span><br>
              &nbsp;&bull; 1310nm এ ~০.৩৫ dB/km<br>
              &nbsp;&bull; 1490nm এ ~০.২৫ dB/km<br>
              <span class="text-warning">Connector:</span> ~০.৫ dB প্রতিটা<br>
              <span class="text-warning">Splice:</span> ~০.১ থেকে ০.৩ dB প্রতিটা
            </div>
            <div class="mt-2 p-2 bg-light rounded border">
              <strong><i class="fas fa-calculator mr-1"></i>উদাহরণ (৫ km, ১:৩২):</strong><br>
              Fiber: 0.35 &times; 5 = <strong>1.75 dB</strong><br>
              Splitter (1:32): <strong>17 dB</strong><br>
              Connectors (2টা): 0.5 &times; 2 = <strong>1 dB</strong><br>
              Splices (3টা): 0.2 &times; 3 = <strong>0.6 dB</strong><br>
              <span class="text-success font-weight-bold">Total &asymp; 20.35 dB &#10003; (EPON budget-এর মধ্যে)</span>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12"><h5 class="text-dark border-bottom pb-2"><i class="fas fa-thumbs-up text-success mr-2"></i>কোনটা বেছে নেবেন?</h5></div>
          <div class="col-md-6">
            <div class="card border-warning">
              <div class="card-header bg-warning py-1"><strong>EPON বেছে নিন যদি...</strong></div>
              <div class="card-body py-2">
                <ul class="mb-0 pl-3">
                  <li>ছোট ISP, বাজেট কম</li>
                  <li>দ্রুত deploy করতে চান</li>
                  <li>গ্রাহক সংখ্যা কম (প্রতি port-এ ৩২ এর কম)</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border-success">
              <div class="card-header bg-success text-white py-1"><strong>GPON বেছে নিন যদি...</strong></div>
              <div class="card-body py-2">
                <ul class="mb-0 pl-3">
                  <li>বড় এলাকা, বেশি গ্রাহক</li>
                  <li>ভবিষ্যৎ সম্প্রসারণ চান</li>
                  <li>উচ্চ গতি ও QoS দরকার</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>বন্ধ করুন</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
function testConnection(oltId) {
    Swal.fire({
        title: 'Testing Connection...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: '/admin/network/olt/' + oltId + '/test',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                var ponBadge = response.detected_pon_type
                    ? '<br><small class="mt-1 d-block">PON Type auto-detected: <strong>' + response.detected_pon_type.toUpperCase() + '</strong></small>'
                    : '';
                Swal.fire({ icon: 'success', title: 'Connected!', html: response.message + ponBadge }).then(() => location.reload());
            } else {
                Swal.fire('Failed!', response.message, 'error');
            }
        },
        error: function(xhr) {
            var msg = 'Could not test connection.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            else if (xhr.status === 419) msg = 'Session expired. Please refresh the page.';
            Swal.fire('Error!', msg, 'error');
        }
    });
}

function syncOlt(oltId) {
    Swal.fire({
        title: 'Syncing ONUs...',
        text: 'Fetching ONU data from OLT device',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: '/admin/network/olt/' + oltId + '/sync-onus',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Synced!', response.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Failed!', response.message, 'error');
            }
        },
        error: function(xhr) {
            var msg = 'Could not sync ONUs.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            Swal.fire('Error!', msg, 'error');
        }
    });
}
</script>
@endpush
