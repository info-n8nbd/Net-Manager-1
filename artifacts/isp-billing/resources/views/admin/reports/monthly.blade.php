@extends('layouts.admin')

@section('title', 'Monthly Revenue Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Monthly Revenue</li>
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom">
        <h3 class="card-title mb-0" style="font-weight: 600; color: #2d3748;">Monthly Revenue - {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <select name="month" class="form-control form-control-sm mr-2" style="border-radius: 6px;">
                    @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $i == $month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                    @endfor
                </select>
                <select name="year" class="form-control form-control-sm mr-2" style="border-radius: 6px;">
                    @for($y = date('Y')-2; $y <= date('Y'); $y++)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 6px; padding: 5px 15px;">Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        {{-- Summary stats --}}
        <div class="row mb-4">
            <div class="col-4">
                <div class="bg-primary text-white rounded p-3">
                    <h4 class="mb-0">৳{{ number_format($revenue ?? 0, 0) }}</h4>
                    <small>Total Revenue</small>
                </div>
            </div>
            <div class="col-4">
                <div class="bg-success text-white rounded p-3">
                    <h4 class="mb-0">{{ $dailyData->count() }}</h4>
                    <small>Active Days</small>
                </div>
            </div>
            <div class="col-4">
                <div class="bg-danger text-white rounded p-3">
                    <h4 class="mb-0">৳{{ $dailyData->count() > 0 ? number_format($revenue / $dailyData->count(), 0) : 0 }}</h4>
                    <small>Daily Average</small>
                </div>
            </div>
        </div>

        {{-- Polar Area Chart — Full Width --}}
        <div class="bg-light rounded p-4">
            <h6 class="text-muted mb-3">
                <i class="fas fa-circle-notch text-purple mr-1"></i>
                {{ $year }} Monthly Revenue &mdash; Polar Area
            </h6>
            <canvas id="polarRevenueChart" style="width:100%; display:block;"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var canvas = document.getElementById('polarRevenueChart');
    if (!canvas) return;
    var _dpr = window.devicePixelRatio || 1;
    var ctx   = canvas.getContext('2d');
    var W     = canvas.parentElement ? canvas.parentElement.offsetWidth - 48 : 700;
    var H     = Math.round(W * 0.72);
    canvas.width        = W * _dpr;
    canvas.height       = H * _dpr;
    canvas.style.width  = W + 'px';
    canvas.style.height = H + 'px';
    ctx.scale(_dpr, _dpr);

    var cx = W / 2, cy = H / 2;
    var maxR   = Math.min(W, H) * 0.38;
    var mLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var revenues = @json($monthlyRevenue);
    var maxVal   = Math.max.apply(null, revenues) || 1;
    var colors   = [
        '#3b82f6','#6366f1','#8b5cf6','#a855f7','#ec4899',
        '#ef4444','#f97316','#f59e0b','#eab308','#22c55e','#10b981','#0ea5e9'
    ];
    var n = 12, sliceAngle = (Math.PI * 2) / n, rotation = -Math.PI / 2, prog = 0;

    function draw(t) {
        ctx.clearRect(0, 0, W, H);
        // grid rings
        for (var r = 1; r <= 5; r++) {
            ctx.beginPath();
            ctx.arc(cx, cy, (maxR * r) / 5, 0, Math.PI * 2);
            ctx.strokeStyle = '#cbd5e1';
            ctx.lineWidth   = 0.8;
            ctx.stroke();
        }
        revenues.forEach(function(rev, i) {
            var startA = rotation + i * sliceAngle;
            var endA   = startA + sliceAngle - 0.025;
            var r      = (rev / maxVal) * maxR * Math.min(1, t * 1.2);

            // slice fill
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, startA, endA);
            ctx.closePath();
            ctx.fillStyle   = colors[i] + 'cc';
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth   = 1.5;
            ctx.stroke();

            // month label outside ring
            var midA  = startA + sliceAngle / 2;
            var lblR  = maxR + 26;
            ctx.fillStyle      = '#374151';
            ctx.font           = 'bold 13px sans-serif';
            ctx.textAlign      = 'center';
            ctx.textBaseline   = 'middle';
            ctx.fillText(mLabels[i], cx + Math.cos(midA) * lblR, cy + Math.sin(midA) * lblR);

            // value inside slice
            if (t > 0.7 && r > 22) {
                var vr = r * 0.58;
                ctx.fillStyle = '#fff';
                ctx.font      = 'bold 11px sans-serif';
                var valTxt    = rev >= 1000 ? (rev / 1000).toFixed(0) + 'k' : rev + '';
                ctx.fillText(valTxt, cx + Math.cos(midA) * vr, cy + Math.sin(midA) * vr);
            }
        });
        ctx.textAlign      = 'left';
        ctx.textBaseline   = 'alphabetic';
    }

    function animate() {
        prog = Math.min(1, prog + 0.02);
        draw(prog);
        if (prog < 1) requestAnimationFrame(animate);
    }
    requestAnimationFrame(animate);
})();
</script>
@endpush
