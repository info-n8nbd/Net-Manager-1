@extends('layouts.admin')

@section('title', 'Optical Loss Calculator')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item active">Optical Loss Calculator</li>
@endsection

@push('styles')
<style>
.calc-card { border-radius: 10px; }
.result-box { background: #ffffff; color: #212529; font-family: monospace; border-radius: 8px; padding: 20px; font-size: 15px; }
.result-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #dee2e6; }
.result-row:last-child { border-bottom: none; }
.result-label { color: #555; }
.result-value { color: #198754; font-weight: bold; }
.result-total { color: #0d6efd; font-size: 18px; font-weight: bold; }
.budget-bar { height: 22px; border-radius: 4px; background: #e9ecef; overflow: hidden; margin-top: 8px; }
.budget-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
.status-ok { color: #28a745; }
.status-warn { color: #ffc107; }
.status-danger { color: #dc3545; }
.ref-table td, .ref-table th { font-size: 12px; padding: 5px 10px; }
.input-group-text { font-size: 13px; min-width: 50px; }
.formula-box { background: #f8f9fa; border-left: 4px solid #007bff; padding: 12px 16px; border-radius: 0 6px 6px 0; font-family: monospace; font-size: 13px; }
.splitter-item { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; position: relative; }
.splitter-item .splitter-label { font-weight: bold; font-size: 13px; margin-bottom: 6px; }
.splitter-item .remove-btn { position: absolute; top: 8px; right: 8px; }
.splitter-manual-row { display: flex; gap: 6px; align-items: center; margin-top: 6px; }
.splitter-chain { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; font-size: 12px; color: #666; margin-bottom: 8px; }
.splitter-chain-item { background: #e9ecef; border-radius: 4px; padding: 2px 8px; font-weight: 600; }
.splitter-chain-arrow { color: #aaa; }
/* Basic calculator styles */
.basic-card { border-radius: 12px; border: 2px solid #dee2e6; }
.basic-sfp-input { font-size: 28px; font-weight: bold; text-align: center; height: 60px; border: 2px solid #007bff; border-radius: 8px; }
.basic-real-input { font-size: 24px; font-weight: bold; text-align: center; height: 52px; }
.compare-box { border-radius: 10px; padding: 16px; }
.compare-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,0.08); }
.compare-row:last-child { border-bottom: none; }
.compare-label { font-size: 13px; color: #666; }
.compare-value { font-size: 18px; font-weight: bold; }
.arrow-icon { font-size: 20px; margin: 0 8px; }
.basic-splitter-pill { display: inline-flex; align-items: center; background: #e9ecef; border-radius: 20px; padding: 4px 12px; margin: 3px; font-size: 13px; font-weight: 600; }
.basic-splitter-pill .remove-pill { margin-left: 8px; cursor: pointer; color: #dc3545; font-size: 14px; }
.verdict-big { font-size: 20px; font-weight: bold; padding: 16px; border-radius: 10px; text-align: center; margin-top: 12px; }
.nav-tabs .nav-link { font-weight: 600; }
</style>
@endpush

@section('content')

{{-- ============ HOW TO USE GUIDE ============ --}}
<div class="card card-outline card-info mb-3">
  <div class="card-header py-2" style="cursor:pointer;" data-toggle="collapse" data-target="#guidePanel" aria-expanded="false">
    <h3 class="card-title text-info"><i class="fas fa-book-open mr-2"></i>How to Use — Optical Loss Calculator</h3>
    <div class="card-tools">
      <button type="button" class="btn btn-tool text-info"><i class="fas fa-plus" id="guideToggleIcon"></i></button>
    </div>
  </div>
  <div class="collapse" id="guidePanel">
    <div class="card-body pb-2">

      <div class="row">

        {{-- Basic Calculator Guide --}}
        <div class="col-lg-6 mb-3">
          <div class="p-3 rounded border-left border-warning" style="background:#fffdf0; border-left-width:4px !important;">
            <h5 class="text-warning mb-3"><i class="fas fa-bolt mr-2"></i>Basic Calculator — Quick Loss Check</h5>
            <p class="text-muted mb-2" style="font-size:13px;">Use this when you already have real RX readings from an ONU and want to quickly compare against expected values.</p>
            <ol class="mb-0 pl-3" style="font-size:13px; line-height:1.9;">
              <li><strong>SFP TX Power (OLT Port)</strong> — Enter the transmit power of your OLT's SFP module.<br>
                <span class="text-muted">Example: Class B SFP = +7 dBm, Class C+ = +9 dBm</span></li>
              <li><strong>Select Splitter Ratio</strong> — Choose the splitter between OLT and ONU.<br>
                <span class="text-muted">Example: 1:32 adds ~17 dB loss, 1:8 adds ~10.5 dB loss</span></li>
              <li><strong>ONU RX Sensitivity (Min)</strong> — The minimum RX power the ONU can accept.<br>
                <span class="text-muted">Example: EPON ONU = -27 dBm, GPON ONU C+ = -30 dBm</span></li>
              <li><strong>Real RX (Actual Reading)</strong> — The actual RX power measured at the ONU (from SNMP or power meter).<br>
                <span class="text-muted">Example: -22.5 dBm</span></li>
              <li>Press <strong>Compare Now</strong> — See if the link is within budget and how much margin remains.</li>
            </ol>
            <div class="mt-2 p-2 rounded bg-white border" style="font-size:12px;">
              <i class="fas fa-lightbulb text-warning mr-1"></i>
              <strong>Tip:</strong> If Real RX is much lower than Expected RX, there may be dirty connectors, a bad splice, or too many splitters.
            </div>
          </div>
        </div>

        {{-- Advanced Calculator Guide --}}
        <div class="col-lg-6 mb-3">
          <div class="p-3 rounded border-left border-primary" style="background:#f0f4ff; border-left-width:4px !important;">
            <h5 class="text-primary mb-3"><i class="fas fa-sliders-h mr-2"></i>Advanced Calculator — Full Link Budget</h5>
            <p class="text-muted mb-2" style="font-size:13px;">Use this when planning a new fiber route or troubleshooting a link. Calculates total loss from all components.</p>
            <ol class="mb-0 pl-3" style="font-size:13px; line-height:1.9;">
              <li><strong>PON Type</strong> — Select EPON (28 dB budget) or GPON (32 dB budget).</li>
              <li><strong>SFP TX Power</strong> (optional) — OLT SFP transmit power. Leave blank to use standard budget.</li>
              <li><strong>ONU RX Sensitivity</strong> (optional) — Minimum RX of your ONU. Leave blank to use standard.</li>
              <li><strong>Fiber Distance (km)</strong> — Total fiber length from OLT to ONU.<br>
                <span class="text-muted">Loss = Distance × Attenuation (0.35 dB/km for 1310nm)</span></li>
              <li><strong>Wavelength</strong> — Select upstream (1310nm) or downstream (1490nm).</li>
              <li><strong>Cascaded Splitters</strong> — Add each splitter level. Click <em>"Add Splitter"</em> and select ratio.<br>
                <span class="text-muted">Example: 1:4 (feeder) + 1:8 (distribution) = 6.0 + 10.5 = 16.5 dB</span></li>
              <li><strong>Connectors</strong> — Number of connector pairs (each = 0.5 dB loss).</li>
              <li><strong>Splice Points</strong> — Number of splices (each = 0.2 dB loss).</li>
              <li><strong>Safety Margin</strong> — Recommended 3 dB buffer for future degradation.</li>
              <li>Press <strong>Calculate</strong> to see the full breakdown and link verdict.</li>
            </ol>
            <div class="mt-2 p-2 rounded bg-white border" style="font-size:12px;">
              <i class="fas fa-lightbulb text-primary mr-1"></i>
              <strong>Tip:</strong> Keep Remaining Margin above 3 dB for a healthy link. Orange bar = marginal, Red = link will fail.
            </div>
          </div>
        </div>

      </div>

      {{-- Loss Reference Quick Table --}}
      <div class="row">
        <div class="col-12">
          <div class="p-2 rounded bg-light border" style="font-size:12px;">
            <strong><i class="fas fa-table mr-1 text-secondary"></i>Quick Reference — Loss per Component</strong>
            <div class="row mt-2">
              <div class="col-sm-3">
                <span class="badge badge-secondary mr-1">Fiber</span>0.35 dB/km (1310nm) · 0.25 dB/km (1490nm)
              </div>
              <div class="col-sm-2">
                <span class="badge badge-secondary mr-1">Connector</span>~0.5 dB each
              </div>
              <div class="col-sm-2">
                <span class="badge badge-secondary mr-1">Splice</span>~0.2 dB each
              </div>
              <div class="col-sm-2">
                <span class="badge badge-warning text-dark mr-1">1:8 Splitter</span>~10.5 dB
              </div>
              <div class="col-sm-2">
                <span class="badge badge-danger mr-1">1:32 Splitter</span>~17.0 dB
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<script>
document.getElementById('guidePanel').addEventListener('show.bs.collapse', function() {
  document.getElementById('guideToggleIcon').className = 'fas fa-minus';
});
document.getElementById('guidePanel').addEventListener('hide.bs.collapse', function() {
  document.getElementById('guideToggleIcon').className = 'fas fa-plus';
});
// AdminLTE uses jQuery collapse
$(document).on('show.bs.collapse', '#guidePanel', function() {
  $('#guideToggleIcon').removeClass('fa-plus').addClass('fa-minus');
});
$(document).on('hide.bs.collapse', '#guidePanel', function() {
  $('#guideToggleIcon').removeClass('fa-minus').addClass('fa-plus');
});
</script>

{{-- Tab Navigation --}}
<ul class="nav nav-tabs mb-3" id="calcTabs" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basicTab" role="tab">
      <i class="fas fa-bolt text-warning mr-1"></i>Basic Calculator
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="advanced-tab" data-toggle="tab" href="#advancedTab" role="tab">
      <i class="fas fa-sliders-h text-primary mr-1"></i>Advanced Calculator
    </a>
  </li>
</ul>

<div class="tab-content" id="calcTabContent">

{{-- ============================= BASIC TAB ============================= --}}
<div class="tab-pane fade show active" id="basicTab" role="tabpanel">
  <div class="row">

    {{-- LEFT: Basic Inputs --}}
    <div class="col-lg-5">
      <div class="card basic-card">
        <div class="card-header bg-warning text-dark py-2">
          <h3 class="card-title mb-0"><i class="fas fa-bolt mr-2"></i>Quick Loss Check</h3>
          <small>SFP dBm + Splitter → Expected vs Actual</small>
        </div>
        <div class="card-body">

          {{-- SFP TX Power --}}
          <div class="form-group text-center">
            <label class="font-weight-bold d-block mb-2"><i class="fas fa-broadcast-tower text-primary mr-1"></i>OLT SFP TX Power</label>
            <div class="input-group justify-content-center" style="max-width:220px; margin:0 auto;">
              <div class="input-group-prepend"><span class="input-group-text bg-primary text-white font-weight-bold">+</span></div>
              <input type="number" id="b_sfpTx" class="basic-sfp-input form-control" value="7" min="-10" max="15" step="0.5" oninput="basicCalc()">
              <div class="input-group-append"><span class="input-group-text bg-primary text-white font-weight-bold">dBm</span></div>
            </div>
            <div class="mt-2">
              <button class="btn btn-outline-secondary btn-sm mr-1" onclick="document.getElementById('b_sfpTx').value=7; basicCalc();">+7 (B)</button>
              <button class="btn btn-outline-secondary btn-sm mr-1" onclick="document.getElementById('b_sfpTx').value=9; basicCalc();">+9 (C+)</button>
              <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('b_sfpTx').value=5; basicCalc();">+5 (B+)</button>
            </div>
          </div>

          <hr>

          {{-- ONU RX Sensitivity --}}
          <div class="form-group text-center">
            <label class="font-weight-bold d-block mb-2"><i class="fas fa-satellite-dish text-success mr-1"></i>ONU RX Sensitivity (Minimum)</label>
            <div class="input-group justify-content-center" style="max-width:220px; margin:0 auto;">
              <div class="input-group-prepend"><span class="input-group-text bg-success text-white font-weight-bold">-</span></div>
              <input type="number" id="b_onuRx" class="form-control" value="27" min="1" max="40" step="0.5" style="font-size:20px; font-weight:bold; text-align:center; height:52px;" oninput="basicCalc()">
              <div class="input-group-append"><span class="input-group-text bg-success text-white font-weight-bold">dBm</span></div>
            </div>
            <div class="mt-2">
              <button class="btn btn-outline-secondary btn-sm mr-1" onclick="document.getElementById('b_onuRx').value=27; basicCalc();">-27 (EPON)</button>
              <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('b_onuRx').value=30; basicCalc();">-30 (GPON C+)</button>
            </div>
          </div>

          <div class="alert alert-primary text-center py-2 mb-3">
            <strong style="font-size:17px;">Max Optical Budget = <span id="b_budget_show">34</span> dB</strong>
          </div>
          <div class="bg-light border rounded px-3 py-2 mb-3 text-center" style="font-size:13px; color:#333;">
            <i class="fas fa-calculator text-primary mr-1"></i>
            <strong>হিসাব:</strong>
            OLT TX <span class="badge badge-primary">+<span id="b_sfp_show">7</span> dBm</span>
            &minus;
            ONU Sensitivity <span class="badge badge-success">&minus;<span id="b_rx_show">27</span> dBm</span>
            <span class="text-muted mx-1">=</span>
            <span class="badge badge-warning text-dark"><span id="b_budget_show2">34</span> dB Budget</span>
          </div>

          <hr>

          {{-- Splitters --}}
          <div class="form-group">
            <label class="font-weight-bold"><i class="fas fa-sitemap text-warning mr-1"></i>Splitters (Cascaded)</label>
            <div id="b_splitterPills" class="mb-2"></div>
            <div class="input-group">
              <select class="form-control" id="b_splitterSelect">
                <option value="3.5">1:2  (~3.5 dB)</option>
                <option value="7.0">1:4  (~7.0 dB)</option>
                <option value="10.5">1:8  (~10.5 dB)</option>
                <option value="13.5">1:16 (~13.5 dB)</option>
                <option value="17.0" selected>1:32 (~17.0 dB)</option>
                <option value="20.5">1:64 (~20.5 dB)</option>
              </select>
              <div class="input-group-append">
                <button class="btn btn-warning" type="button" onclick="bAddSplitter()"><i class="fas fa-plus"></i></button>
              </div>
            </div>
            <small class="text-muted">Select ratio and click + to add. Multiple splitters = cascaded.</small>
          </div>

          <div class="bg-light border rounded p-2 mb-3" id="b_splitterChain" style="font-size:13px; color:#555;">
            OLT &rarr; ONU
          </div>

          <hr>

          {{-- Real Measured Value --}}
          <div class="form-group text-center">
            <label class="font-weight-bold d-block mb-2"><i class="fas fa-tachometer-alt text-danger mr-1"></i>Real Network Measured Value</label>
            <div class="input-group justify-content-center" style="max-width:220px; margin:0 auto;">
              <div class="input-group-prepend"><span class="input-group-text bg-danger text-white font-weight-bold">-</span></div>
              <input type="number" id="b_realRx" class="form-control" value="" min="-60" max="0" step="0.1" placeholder="e.g. 22" style="font-size:20px; font-weight:bold; text-align:center; height:52px;" oninput="basicCalc()">
              <div class="input-group-append"><span class="input-group-text bg-danger text-white font-weight-bold">dBm</span></div>
            </div>
            <small class="text-muted">RX dBm shown on ONU power meter or OLT SNMP</small>
          </div>

          <button class="btn btn-warning btn-block btn-lg mt-2 font-weight-bold" onclick="basicCalc()">
            <i class="fas fa-calculator mr-1"></i>Compare Now
          </button>

        </div>
      </div>
    </div>

    {{-- RIGHT: Basic Result --}}
    <div class="col-lg-7">
      <div class="card basic-card">
        <div class="card-header bg-dark text-white py-2">
          <h3 class="card-title mb-0"><i class="fas fa-chart-bar mr-2"></i>Result</h3>
        </div>
        <div class="card-body">

          {{-- Budget vs Splitter loss compare --}}
          <div class="compare-box bg-light border mb-3">
            <div class="compare-row">
              <span class="compare-label"><i class="fas fa-broadcast-tower text-primary mr-1"></i>Max Optical Budget</span>
              <span class="compare-value text-primary" id="br_budget">34.0 dB</span>
            </div>
            <div class="compare-row">
              <span class="compare-label"><i class="fas fa-sitemap text-warning mr-1"></i>Total Splitter Loss</span>
              <span class="compare-value text-warning" id="br_splitter">17.0 dB</span>
            </div>
            <div class="compare-row">
              <span class="compare-label"><i class="fas fa-ruler text-success mr-1"></i>Remaining for Fiber + Misc</span>
              <span class="compare-value" id="br_remaining">17.0 dB</span>
            </div>
            <div class="compare-row">
              <span class="compare-label"><i class="fas fa-shield-alt text-secondary mr-1"></i>After 3 dB Safety Margin</span>
              <span class="compare-value" id="br_safe">14.0 dB</span>
            </div>
          </div>

          {{-- Budget Bar --}}
          <div class="mb-1 d-flex justify-content-between"><small>0 dB</small><small id="br_budget_label">Budget: 34 dB</small></div>
          <div class="budget-bar mb-1">
            <div class="budget-fill" id="b_budgetFill" style="width:50%; background:#ffc107;"></div>
          </div>
          <div class="text-center mb-3"><small id="br_bar_label" class="text-muted">Splitter uses 50% of budget</small></div>

          {{-- Loss Breakdown Chart --}}
          <div class="card border mb-3">
            <div class="card-header bg-light py-2">
              <h6 class="card-title mb-0"><i class="fas fa-chart-pie text-primary mr-1"></i>Loss Breakdown Chart</h6>
            </div>
            <div class="card-body py-2 px-3">
              <canvas id="b_lossChart" style="max-height:160px;"></canvas>
            </div>
          </div>

          {{-- Real vs Expected compare --}}
          <div id="b_compareSection">
            <h6 class="font-weight-bold border-bottom pb-2"><i class="fas fa-exchange-alt mr-1"></i>Real Network Comparison</h6>
            <div class="compare-box bg-light border mb-2">
              <div class="compare-row">
                <span class="compare-label">Expected ONU RX Range</span>
                <span class="compare-value" id="br_expected_range" style="font-size:15px;">—</span>
              </div>
              <div class="compare-row">
                <span class="compare-label"><i class="fas fa-tachometer-alt text-danger mr-1"></i>Real Measured RX</span>
                <span class="compare-value text-danger" id="br_real_rx">—</span>
              </div>
              <div class="compare-row">
                <span class="compare-label">Difference</span>
                <span class="compare-value" id="br_diff">—</span>
              </div>
            </div>
            <div id="b_verdictBox" class="verdict-big bg-light border text-muted">
              <i class="fas fa-info-circle mr-1"></i>Enter real value to compare
            </div>
          </div>

          {{-- Recommendation --}}
          <div class="mt-3" id="b_recommendation" style="display:none;">
            <h6 class="font-weight-bold border-bottom pb-2"><i class="fas fa-lightbulb text-warning mr-1"></i>Recommendation</h6>
            <div id="b_rec_text" class="alert alert-info" style="font-size:13px;"></div>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>

{{-- ============================= ADVANCED TAB ============================= --}}
<div class="tab-pane fade" id="advancedTab" role="tabpanel">
<div class="row">
  <div class="col-lg-5">
    <div class="card calc-card">
      <div class="card-header bg-dark text-white py-2">
        <h3 class="card-title"><i class="fas fa-calculator mr-2"></i>Optical Link Loss Calculator</h3>
      </div>
      <div class="card-body">
        <div class="formula-box mb-3">
          L<sub>Total</sub> = (L<sub>fiber</sub> &times; km) + &Sigma;L<sub>splitters</sub> + (connectors &times; 0.5) + (splices &times; 0.2)
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><i class="fas fa-broadcast-tower text-primary mr-1"></i>PON Type</label>
          <div class="row mb-2">
            <div class="col-6">
              <div class="custom-control custom-radio">
                <input type="radio" id="epon" name="ponType" class="custom-control-input" value="epon" checked onchange="updateCalc()">
                <label class="custom-control-label" for="epon">EPON <small class="text-muted">(28 dB)</small></label>
              </div>
            </div>
            <div class="col-6">
              <div class="custom-control custom-radio">
                <input type="radio" id="gpon" name="ponType" class="custom-control-input" value="gpon" onchange="updateCalc()">
                <label class="custom-control-label" for="gpon">GPON <small class="text-muted">(32 dB)</small></label>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><i class="fas fa-bolt text-warning mr-1"></i>SFP TX Power (OLT Port)</label>
          <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text">+</span></div>
            <input type="number" id="sfpTx" class="form-control" value="" min="-10" max="15" step="0.5" placeholder="e.g. 7 or 9  (optional)" oninput="updateCalc()">
            <div class="input-group-append"><span class="input-group-text">dBm</span></div>
          </div>
          <small class="text-muted">+7 dBm (Class B) &nbsp;|&nbsp; +9 dBm (Class C+)</small>
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><i class="fas fa-satellite-dish text-success mr-1"></i>ONU RX Sensitivity (Min)</label>
          <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text">-</span></div>
            <input type="number" id="onuRx" class="form-control" value="" min="1" max="40" step="0.5" placeholder="e.g. 27 or 30  (optional)" oninput="updateCalc()">
            <div class="input-group-append"><span class="input-group-text">dBm</span></div>
          </div>
          <small class="text-muted">-27 dBm (EPON) &nbsp;|&nbsp; -30 dBm (GPON C+)</small>
        </div>
        <div class="alert alert-info py-2 px-3 mb-3" style="font-size:13px;">
          <i class="fas fa-info-circle mr-1"></i> <strong>Real Budget = SFP TX &minus; ONU RX Sensitivity</strong><br>
          Leave blank to use Standard budget.
          <span id="realBudgetInfo" class="d-block mt-1 font-weight-bold text-primary"></span>
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><i class="fas fa-ruler text-success mr-1"></i>Fiber Distance</label>
          <div class="input-group">
            <input type="number" id="fiberDist" class="form-control" value="5" min="0" step="0.1" oninput="updateCalc()">
            <div class="input-group-append"><span class="input-group-text">km</span></div>
          </div>
          <small class="text-muted">Attenuation: <span id="fiberRate">0.35</span> dB/km</small>
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><i class="fas fa-wave-square text-info mr-1"></i>Wavelength</label>
          <select class="form-control" id="wavelength" onchange="updateCalc()">
            <option value="0.35">1310 nm — 0.35 dB/km (Upstream)</option>
            <option value="0.25">1490 nm — 0.25 dB/km (Downstream)</option>
            <option value="0.22">1550 nm — 0.22 dB/km (Analog Video)</option>
          </select>
        </div>
        <div class="form-group mb-1">
          <label class="font-weight-bold"><i class="fas fa-sitemap text-warning mr-1"></i>Cascaded Splitters</label>
        </div>
        <div id="splitterChain" class="splitter-chain mb-2"><span class="splitter-chain-item">OLT</span></div>
        <div id="splitterList"></div>
        <div class="row mb-3">
          <div class="col-6">
            <button type="button" class="btn btn-outline-warning btn-sm btn-block" onclick="addSplitter()">
              <i class="fas fa-plus mr-1"></i>Add Splitter Level
            </button>
          </div>
          <div class="col-6">
            <button type="button" class="btn btn-outline-secondary btn-sm btn-block" onclick="resetSplitters()">
              <i class="fas fa-undo mr-1"></i>Reset Splitters
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><i class="fas fa-plug text-danger mr-1"></i>Connectors</label>
          <div class="input-group">
            <input type="number" id="connectors" class="form-control" value="2" min="0" max="20" oninput="updateCalc()">
            <div class="input-group-append"><span class="input-group-text">&times; 0.5 dB</span></div>
          </div>
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><i class="fas fa-cut mr-1" style="color:#8e44ad;"></i>Fusion Splices</label>
          <div class="input-group">
            <input type="number" id="splices" class="form-control" value="3" min="0" max="50" oninput="updateCalc()">
            <div class="input-group-append"><span class="input-group-text">&times; 0.2 dB</span></div>
          </div>
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><i class="fas fa-shield-alt text-secondary mr-1"></i>Safety Margin</label>
          <div class="input-group">
            <input type="number" id="safetyMargin" class="form-control" value="3" min="0" max="10" step="0.5" oninput="updateCalc()">
            <div class="input-group-append"><span class="input-group-text">dB</span></div>
          </div>
          <small class="text-muted">Recommended: 3 dB</small>
        </div>
        <button class="btn btn-primary btn-block" onclick="updateCalc()"><i class="fas fa-sync-alt mr-1"></i>Calculate</button>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card calc-card">
      <div class="card-header bg-white text-dark border-bottom py-2">
        <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Calculation Result</h3>
      </div>
      <div class="card-body">
        <div class="result-box">
          <div class="result-row"><span class="result-label">Fiber Loss:</span><span class="result-value" id="r_fiber">1.75 dB</span></div>
          <div id="r_splitters_block"></div>
          <div class="result-row" id="r_splitter_total_row" style="display:none;">
            <span class="result-label"><strong>Total Splitter Loss:</strong></span>
            <span class="result-value" id="r_splitter_total" style="color:#fd7e14; font-weight:bold;">0.00 dB</span>
          </div>
          <div class="result-row"><span class="result-label">Connector Loss:</span><span class="result-value" id="r_connector">1.00 dB</span></div>
          <div class="result-row"><span class="result-label">Splice Loss:</span><span class="result-value" id="r_splice">0.60 dB</span></div>
          <div class="result-row"><span class="result-label">Safety Margin:</span><span class="result-value" id="r_margin">3.00 dB</span></div>
          <hr style="border-color:#dee2e6;">
          <div class="result-row"><span class="result-label" style="font-size:16px;">Total Link Loss:</span><span class="result-total" id="r_total">23.35 dB</span></div>
          <div class="result-row"><span class="result-label">PON Budget:</span><span class="result-value" id="r_budget">28.00 dB</span></div>
          <div class="result-row"><span class="result-label">Remaining Margin:</span><span id="r_remaining" style="font-weight:bold;">4.65 dB</span></div>
        </div>
        <div class="mt-3">
          <div class="d-flex justify-content-between mb-1"><small>0 dB</small><small id="r_budget_label">Budget: 28 dB</small></div>
          <div class="budget-bar"><div class="budget-fill" id="budgetFill" style="width:84%; background:#28a745;"></div></div>
          <div class="text-center mt-1"><span id="r_status" class="font-weight-bold status-ok"><i class="fas fa-check-circle mr-1"></i>Link is within budget</span></div>
        </div>
        <div id="verdictBox" class="mt-3 alert alert-success">
          <strong><i class="fas fa-check-circle mr-1"></i>Link OK!</strong> Total loss is within the optical budget with <span id="verdictMargin">4.65 dB</span> margin remaining.
        </div>
      </div>
    </div>

    <div class="card calc-card mt-3">
      <div class="card-header py-2 bg-secondary text-white">
        <h3 class="card-title"><i class="fas fa-table mr-2"></i>Splitter Loss Quick Reference</h3>
        <div class="card-tools"><button type="button" class="btn btn-tool text-white" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-sm table-striped ref-table mb-0">
            <thead class="thead-dark">
              <tr><th>Split Ratio</th><th class="text-center">Max ONU</th><th class="text-center">Theoretical</th><th class="text-center">Typical</th><th class="text-center">EPON</th><th class="text-center">GPON</th></tr>
            </thead>
            <tbody>
              <tr><td>1:2</td><td class="text-center">2</td><td class="text-center">3.0 dB</td><td class="text-center">~3.5 dB</td><td class="text-center text-success">✔</td><td class="text-center text-success">✔</td></tr>
              <tr><td>1:4</td><td class="text-center">4</td><td class="text-center">6.0 dB</td><td class="text-center">~7.0 dB</td><td class="text-center text-success">✔</td><td class="text-center text-success">✔</td></tr>
              <tr><td>1:8</td><td class="text-center">8</td><td class="text-center">9.0 dB</td><td class="text-center">~10.5 dB</td><td class="text-center text-success">✔</td><td class="text-center text-success">✔</td></tr>
              <tr><td>1:16</td><td class="text-center">16</td><td class="text-center">12.0 dB</td><td class="text-center">~13.5 dB</td><td class="text-center text-success">✔</td><td class="text-center text-success">✔</td></tr>
              <tr><td><strong>1:32</strong></td><td class="text-center"><strong>32</strong></td><td class="text-center">15.0 dB</td><td class="text-center">~17.0 dB</td><td class="text-center text-success">✔</td><td class="text-center text-success">✔</td></tr>
              <tr><td>1:64</td><td class="text-center">64</td><td class="text-center">18.0 dB</td><td class="text-center">~20.5 dB</td><td class="text-center text-warning">⚠ Limited</td><td class="text-center text-success">✔</td></tr>
              <tr><td>1:128</td><td class="text-center">128</td><td class="text-center">21.0 dB</td><td class="text-center">~24.0 dB</td><td class="text-center text-danger">✘</td><td class="text-center text-warning">⚠</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>{{-- end advanced tab --}}

</div>{{-- end tab-content --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ─── BASIC CALCULATOR ──────────────────────────────────────────────────────────
var bSplitters = [];

function bAddSplitter() {
  var sel = document.getElementById('b_splitterSelect');
  var val = parseFloat(sel.value);
  var label = sel.options[sel.selectedIndex].text.split(' ')[0]; // "1:32"
  bSplitters.push({ val: val, label: label });
  renderBSplitters();
  basicCalc();
}

function bRemoveSplitter(idx) {
  bSplitters.splice(idx, 1);
  renderBSplitters();
  basicCalc();
}

function renderBSplitters() {
  var colors = ['#f39c12','#e74c3c','#9b59b6','#16a085','#2980b9','#d35400','#27ae60'];
  var html = '';
  bSplitters.forEach(function(s, i) {
    html += '<span class="basic-splitter-pill" style="background:' + colors[i % colors.length] + '20; border:1px solid ' + colors[i % colors.length] + ';color:' + colors[i % colors.length] + ';">' +
      '<i class="fas fa-sitemap mr-1"></i>S' + (i+1) + ': ' + s.label + ' (' + s.val.toFixed(1) + ' dB)' +
      '<span class="remove-pill" onclick="bRemoveSplitter(' + i + ')">&times;</span>' +
    '</span>';
  });
  document.getElementById('b_splitterPills').innerHTML = html || '<small class="text-muted">No splitter added yet</small>';

  // Update chain
  var chain = '<strong>OLT</strong>';
  bSplitters.forEach(function(s, i) {
    chain += ' &rarr; <strong style="color:#f39c12;">S' + (i+1) + ':' + s.label + '</strong>';
  });
  chain += ' &rarr; <strong>ONU</strong>';
  document.getElementById('b_splitterChain').innerHTML = chain;
}

var bLossChart = null;

function updateBasicChart(splitterLoss, safety, availFiber, overBudget) {
  var el = document.getElementById("b_lossChart");
  if (!el) return;
  var labels = ["Splitter Loss", "Safety Margin", "Available for Fiber"];
  var data = [splitterLoss, safety, availFiber];
  var colors = ["#ffc107", "#6c757d", "#28a745"];
  if (overBudget > 0) { labels.push("Over Budget"); data.push(overBudget); colors.push("#dc3545"); }
  if (bLossChart) {
    bLossChart.data.labels = labels;
    bLossChart.data.datasets[0].data = data;
    bLossChart.data.datasets[0].backgroundColor = colors;
    bLossChart.update();
    return;
  }
  bLossChart = new Chart(el.getContext("2d"), {
    type: "doughnut",
    data: {
      labels: labels,
      datasets: [{ data: data, backgroundColor: colors, borderWidth: 2, borderColor: "#fff", hoverOffset: 6 }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2.2,
      cutout: "65%",
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            font: { size: 12 }, padding: 12,
            generateLabels: function(chart) {
              return chart.data.labels.map(function(label, i) {
                return {
                  text: label + " (" + (chart.data.datasets[0].data[i] || 0).toFixed(1) + " dB)",
                  fillStyle: chart.data.datasets[0].backgroundColor[i],
                  strokeStyle: "#fff", lineWidth: 2, index: i
                };
              });
            }
          }
        },
        tooltip: {
          callbacks: {
            label: function(ctx) { return " " + ctx.label + ": " + ctx.parsed.toFixed(1) + " dB"; }
          }
        }
      }
    }
  });
}

function basicCalc() {
  var sfpTx = parseFloat(document.getElementById('b_sfpTx').value) || 0;
  var onuRx = parseFloat(document.getElementById('b_onuRx').value) || 27;
  var budget = sfpTx + onuRx;

  document.getElementById('b_budget_show').textContent = budget.toFixed(1);
  document.getElementById('b_sfp_show').textContent = sfpTx.toFixed(1);
  document.getElementById('b_rx_show').textContent = onuRx.toFixed(1);

  var totalSplitter = bSplitters.reduce(function(sum, s) { return sum + s.val; }, 0);
  var remaining = budget - totalSplitter;
  var afterSafety = remaining - 3;
  var pct = Math.min((totalSplitter / budget) * 100, 100);

  document.getElementById('br_budget').textContent = budget.toFixed(1) + ' dB';
  document.getElementById('br_splitter').textContent = totalSplitter.toFixed(1) + ' dB';
  document.getElementById('br_remaining').textContent = remaining.toFixed(1) + ' dB';
  document.getElementById('br_safe').textContent = afterSafety.toFixed(1) + ' dB';
  document.getElementById('br_budget_label').textContent = 'Budget: ' + budget.toFixed(1) + ' dB';
  document.getElementById('br_bar_label').textContent = 'Splitter uses ' + pct.toFixed(0) + '% of budget';

  var fill = document.getElementById('b_budgetFill');
  fill.style.width = pct.toFixed(1) + '%';
  fill.style.background = pct < 60 ? '#28a745' : pct < 80 ? '#ffc107' : '#dc3545';

  // Expected ONU RX range
  // Expected: ONU should receive between (sfpTx - budget) and (sfpTx - totalSplitter - 3)
  // ONU RX = SFP TX - Link Loss
  // Min expected RX = sfpTx - budget  (worst case, just at sensitivity limit)
  // Max expected RX = sfpTx - totalSplitter  (only splitter loss, ideal)
  var minExpRx = sfpTx - budget;        // e.g. 7 - 34 = -27 (at limit)
  var maxExpRx = sfpTx - totalSplitter; // e.g. 7 - 17 = -10 (best case no fiber)
  var typExpRx = sfpTx - totalSplitter - 2; // typical with some fiber/connectors

  var expRangeText = minExpRx.toFixed(1) + ' dBm to ' + maxExpRx.toFixed(1) + ' dBm';
  document.getElementById('br_expected_range').textContent = expRangeText;

  // Real comparison
  var realRxStr = document.getElementById('b_realRx').value.trim();
  var verdictBox = document.getElementById('b_verdictBox');
  var recDiv = document.getElementById('b_recommendation');
  var recText = document.getElementById('b_rec_text');

  if (realRxStr !== '') {
    var realRx = parseFloat(realRxStr);
    var realLoss = sfpTx - (-Math.abs(realRx)); // sfpTx - |realRx|
    // Actually: real total loss = sfpTx - realRx (since realRx is negative)
    // ONU rx = sfpTx - totalLoss  =>  totalLoss = sfpTx - onuRx_actual
    var realTotalLoss = sfpTx + Math.abs(realRx); // sfpTx - (-|realRx|)
    var diff = realTotalLoss - totalSplitter;

    document.getElementById('br_real_rx').textContent = '-' + Math.abs(realRx).toFixed(1) + ' dBm (Loss: ' + realTotalLoss.toFixed(1) + ' dB)';

    var diffText = '';
    var diffColor = '';
    if (diff > 0) {
      diffText = '+' + diff.toFixed(1) + ' dB extra loss (fiber + connectors + splices)';
      diffColor = diff > 5 ? 'text-danger' : 'text-warning';
    } else {
      diffText = diff.toFixed(1) + ' dB (splitter loss only — very short fiber)';
      diffColor = 'text-success';
    }
    document.getElementById('br_diff').className = 'compare-value ' + diffColor;
    document.getElementById('br_diff').textContent = diffText;

    // Verdict
    var margin = budget - realTotalLoss;
    if (margin >= 3) {
      verdictBox.className = 'verdict-big alert alert-success';
      verdictBox.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Link OK! <br><small>' + margin.toFixed(1) + ' dB margin remaining</small>';
    } else if (margin >= 0) {
      verdictBox.className = 'verdict-big alert alert-warning';
      verdictBox.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Marginal! <br><small>Only ' + margin.toFixed(1) + ' dB remaining — may cause issues</small>';
    } else {
      verdictBox.className = 'verdict-big alert alert-danger';
      verdictBox.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Link FAIL! <br><small>Budget ' + Math.abs(margin).toFixed(1) + ' dB over budget</small>';
    }

    // Recommendation
    recDiv.style.display = 'block';
    var rec = '';
    if (margin < 0) {
      rec = '<strong>Possible Issues:</strong><ul class="mb-0 mt-1"><li>Fiber distance too long or too many splitters</li><li>Dirty or poorly connected connector</li><li>Excessive loss at splice points</li><li>Solution: Use GPON or higher class SFP</li></ul>';
      recText.className = 'alert alert-danger mb-0';
    } else if (diff > 8) {
      rec = '<strong>Extra loss too high (' + diff.toFixed(1) + ' dB):</strong> Clean connectors, check splice points, locate fault with OTDR.';
      recText.className = 'alert alert-warning mb-0';
    } else {
      rec = 'Link is normal. Extra loss ' + diff.toFixed(1) + ' dB — fiber distance and connectors look good.';
      recText.className = 'alert alert-success mb-0';
    }
    recText.innerHTML = rec;

  } else {
    document.getElementById('br_real_rx').textContent = '— (enter value)';
    document.getElementById('br_diff').textContent = '—';
    document.getElementById('br_diff').className = 'compare-value';
    verdictBox.className = 'verdict-big bg-light border text-muted';
    verdictBox.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Enter real value to compare';
    recDiv.style.display = 'none';
  }

  // Update loss chart
  var safety = (remaining >= 0) ? 3 : 0;
  var availFiber = Math.max(remaining - 3, 0);
  var overBudget = (remaining < 0) ? Math.abs(remaining) : 0;
  updateBasicChart(totalSplitter, safety, availFiber, overBudget);
}

document.addEventListener('DOMContentLoaded', function() {
  bSplitters = [];
  bAddSplitter(); // default 1:32
  basicCalc();
  renderBSplitters();
});

// ─── ADVANCED CALCULATOR ────────────────────────────────────────────────────────
var splitterOptions = [
  { label: 'No Splitter', value: 0 },
  { label: '1:2  (~3.5 dB)',  value: 3.5 },
  { label: '1:4  (~7.0 dB)',  value: 7.0 },
  { label: '1:8  (~10.5 dB)', value: 10.5 },
  { label: '1:16 (~13.5 dB)', value: 13.5 },
  { label: '1:32 (~17.0 dB)', value: 17.0 },
  { label: '1:64 (~20.5 dB)', value: 20.5 },
  { label: 'Manual (enter dB below)', value: 'manual' },
];
var splitterCount = 0;
var splitterIds = [];

function buildSelectHtml(id, defaultVal) {
  var html = '<select class="form-control form-control-sm" id="' + id + '" onchange="onSplitterSelectChange(\'' + id + '\')">';
  splitterOptions.forEach(function(opt) {
    var sel = (opt.value == defaultVal) ? ' selected' : '';
    html += '<option value="' + opt.value + '"' + sel + '>' + opt.label + '</option>';
  });
  html += '</select>';
  return html;
}

function addSplitter(defaultVal) {
  splitterCount++;
  var sid = 'splitter_' + splitterCount;
  splitterIds.push(sid);
  var colors = ['#f39c12','#e74c3c','#9b59b6','#16a085','#2980b9','#d35400','#27ae60','#8e44ad'];
  var color = colors[(splitterCount - 1) % colors.length];
  var dv = (defaultVal !== undefined) ? defaultVal : 0;
  var html = '<div class="splitter-item" id="item_' + sid + '">' +
    '<button type="button" class="btn btn-xs btn-outline-danger remove-btn" onclick="removeSplitter(\'' + sid + '\')" title="Remove"><i class="fas fa-times"></i></button>' +
    '<div class="splitter-label"><i class="fas fa-sitemap mr-1" style="color:' + color + ';"></i>Splitter-' + splitterCount + ' <small class="text-muted font-weight-normal">(Level ' + splitterCount + ')</small></div>' +
    buildSelectHtml('sel_' + sid, dv) +
    '<div class="splitter-manual-row" id="manual_' + sid + '" style="display:none;">' +
      '<input type="number" class="form-control form-control-sm" id="manual_val_' + sid + '" placeholder="Loss in dB" min="0" max="30" step="0.1" oninput="updateCalc()" style="max-width:110px;">' +
      '<span class="text-muted" style="font-size:12px;">dB (custom)</span>' +
    '</div>' +
  '</div>';
  document.getElementById('splitterList').insertAdjacentHTML('beforeend', html);
  document.getElementById('sel_' + sid).addEventListener('change', updateCalc);
  updateChain();
  updateCalc();
}

function onSplitterSelectChange(selId) {
  var val = document.getElementById(selId).value;
  var sid = selId.replace('sel_', '');
  var manualRow = document.getElementById('manual_' + sid);
  if (manualRow) manualRow.style.display = (val === 'manual') ? 'flex' : 'none';
  updateCalc();
}

function removeSplitter(sid) {
  var el = document.getElementById('item_' + sid);
  if (el) el.remove();
  splitterIds = splitterIds.filter(function(id) { return id !== sid; });
  var items = document.querySelectorAll('#splitterList .splitter-item');
  var colors = ['#f39c12','#e74c3c','#9b59b6','#16a085','#2980b9','#d35400','#27ae60','#8e44ad'];
  items.forEach(function(item, idx) {
    var lbl = item.querySelector('.splitter-label');
    if (lbl) {
      var color = colors[idx % colors.length];
      lbl.innerHTML = '<i class="fas fa-sitemap mr-1" style="color:' + color + ';"></i>Splitter-' + (idx+1) + ' <small class="text-muted font-weight-normal">(Level ' + (idx+1) + ')</small>';
    }
  });
  updateChain();
  updateCalc();
}

function resetSplitters() {
  document.getElementById('splitterList').innerHTML = '';
  splitterIds = [];
  splitterCount = 0;
  addSplitter(17.0);
  updateChain();
  updateCalc();
}

function updateChain() {
  var chain = document.getElementById('splitterChain');
  if (!chain) return;
  var html = '<span class="splitter-chain-item">OLT</span>';
  var items = document.querySelectorAll('#splitterList .splitter-item');
  items.forEach(function(item, idx) {
    var sel = item.querySelector('select');
    var val = sel ? sel.value : 0;
    var mi = item.querySelector('input[type=number]');
    var label = 'S' + (idx+1);
    if (val === 'manual') {
      label += ':' + (mi && mi.value ? mi.value + 'dB' : '?dB');
    } else if (val > 0) {
      var opt = splitterOptions.find(function(o) { return o.value == val; });
      label += opt ? ':' + opt.label.split(' ')[0] : '';
    } else { label += ':None'; }
    html += '<span class="splitter-chain-arrow">&rarr;</span><span class="splitter-chain-item">' + label + '</span>';
  });
  html += '<span class="splitter-chain-arrow">&rarr;</span><span class="splitter-chain-item">ONU</span>';
  chain.innerHTML = html;
}

function getSplitterTotalLoss() {
  var total = 0;
  document.querySelectorAll('#splitterList .splitter-item').forEach(function(item) {
    var sel = item.querySelector('select');
    if (!sel) return;
    if (sel.value === 'manual') {
      var mi = item.querySelector('input[type=number]');
      total += mi ? (parseFloat(mi.value) || 0) : 0;
    } else { total += parseFloat(sel.value) || 0; }
  });
  return total;
}

function updateCalc() {
  updateChain();
  var ponType = document.querySelector('input[name="ponType"]:checked').value;
  var standardBudget = ponType === 'gpon' ? 32 : 28;
  var sfpTxVal = document.getElementById('sfpTx').value.trim();
  var onuRxVal = document.getElementById('onuRx').value.trim();
  var budget = standardBudget;
  var budgetSource = 'Standard (' + (ponType === 'gpon' ? 'GPON' : 'EPON') + ')';
  if (sfpTxVal !== '' && onuRxVal !== '') {
    var sfpTxNum = parseFloat(sfpTxVal);
    var onuRxNum = parseFloat(onuRxVal);
    budget = sfpTxNum + onuRxNum;
    budgetSource = 'SFP: +' + sfpTxNum.toFixed(1) + ' − (−' + onuRxNum.toFixed(1) + ') = ' + budget.toFixed(1) + ' dB';
  }
  var infoEl = document.getElementById('realBudgetInfo');
  if (infoEl) infoEl.textContent = (sfpTxVal !== '' && onuRxVal !== '') ? 'Real Budget: ' + budgetSource : '';
  var dist = parseFloat(document.getElementById('fiberDist').value) || 0;
  var atten = parseFloat(document.getElementById('wavelength').value) || 0.35;
  var fiberLoss = dist * atten;
  var totalSplitterLoss = getSplitterTotalLoss();
  var connectors = parseInt(document.getElementById('connectors').value) || 0;
  var splices = parseInt(document.getElementById('splices').value) || 0;
  var margin = parseFloat(document.getElementById('safetyMargin').value) || 0;
  var connLoss = connectors * 0.5;
  var spliceLoss = splices * 0.2;
  var total = fiberLoss + totalSplitterLoss + connLoss + spliceLoss + margin;
  var remaining = budget - total;
  var pct = Math.min((total / budget) * 100, 100);
  document.getElementById('fiberRate').textContent = atten.toFixed(2);
  document.getElementById('r_fiber').textContent = fiberLoss.toFixed(2) + ' dB';
  var splitterBlock = '';
  document.querySelectorAll('#splitterList .splitter-item').forEach(function(item, idx) {
    var sel = item.querySelector('select');
    var val = sel ? sel.value : 0;
    var loss = 0;
    if (val === 'manual') { var mi = item.querySelector('input[type=number]'); loss = mi ? (parseFloat(mi.value) || 0) : 0; }
    else { loss = parseFloat(val) || 0; }
    splitterBlock += '<div class="result-row"><span class="result-label">Splitter-' + (idx+1) + ' Loss:</span><span class="result-value">' + loss.toFixed(2) + ' dB</span></div>';
  });
  document.getElementById('r_splitters_block').innerHTML = splitterBlock;
  var items = document.querySelectorAll('#splitterList .splitter-item');
  document.getElementById('r_splitter_total_row').style.display = items.length > 1 ? 'flex' : 'none';
  document.getElementById('r_splitter_total').textContent = totalSplitterLoss.toFixed(2) + ' dB';
  document.getElementById('r_connector').textContent = connLoss.toFixed(2) + ' dB';
  document.getElementById('r_splice').textContent = spliceLoss.toFixed(2) + ' dB';
  document.getElementById('r_margin').textContent = margin.toFixed(2) + ' dB';
  document.getElementById('r_total').textContent = total.toFixed(2) + ' dB';
  document.getElementById('r_budget').textContent = budget.toFixed(2) + ' dB';
  document.getElementById('r_budget_label').textContent = 'Budget: ' + budget.toFixed(1) + ' dB (' + budgetSource + ')';
  document.getElementById('r_remaining').textContent = remaining.toFixed(2) + ' dB';
  var fill = document.getElementById('budgetFill');
  fill.style.width = pct.toFixed(1) + '%';
  var statusEl = document.getElementById('r_status');
  var verdictEl = document.getElementById('verdictBox');
  var remEl = document.getElementById('r_remaining');
  if (remaining >= 3) {
    fill.style.background = '#28a745'; statusEl.className = 'font-weight-bold status-ok';
    statusEl.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Link is within budget';
    verdictEl.className = 'mt-3 alert alert-success';
    verdictEl.innerHTML = '<strong><i class="fas fa-check-circle mr-1"></i>Link OK!</strong> Total loss is within budget with <strong>' + remaining.toFixed(2) + ' dB</strong> margin.';
    remEl.className = 'status-ok font-weight-bold';
  } else if (remaining >= 0) {
    fill.style.background = '#ffc107'; statusEl.className = 'font-weight-bold status-warn';
    statusEl.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Marginal';
    verdictEl.className = 'mt-3 alert alert-warning';
    verdictEl.innerHTML = '<strong><i class="fas fa-exclamation-triangle mr-1"></i>Warning!</strong> Low margin (' + remaining.toFixed(2) + ' dB).';
    remEl.className = 'status-warn font-weight-bold';
  } else {
    fill.style.background = '#dc3545'; statusEl.className = 'font-weight-bold status-danger';
    statusEl.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Over Budget! Link will FAIL';
    verdictEl.className = 'mt-3 alert alert-danger';
    verdictEl.innerHTML = '<strong><i class="fas fa-times-circle mr-1"></i>Link FAILED!</strong> Exceeds budget by <strong>' + Math.abs(remaining).toFixed(2) + ' dB</strong>.';
    remEl.className = 'status-danger font-weight-bold';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  addSplitter(17.0);
  updateCalc();
});
</script>
@endpush
