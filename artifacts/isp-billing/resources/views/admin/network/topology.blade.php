@extends('layouts.admin')

@section('title', 'Network Topology')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Network Topology</li>
@endsection

@section('content')
<style>
#topology-container { width: 100%; height: calc(100vh - 280px); min-height: 500px; overflow: auto; background: #f8fafc; border-radius: 8px; }
#topology-svg { width: 100%; min-height: 500px; }
.node { cursor: pointer; }
.node circle { stroke-width: 2px; transition: all 0.2s; }
.node:hover circle { stroke-width: 3px; transform: scale(1.1); }
.node text { font-size: 11px; fill: #333; }
.link { fill: none; stroke: #94a3b8; stroke-width: 1.5px; }
.node-olt circle { fill: #ef4444; stroke: #dc2626; }
.node-splitter circle { fill: #8b5cf6; stroke: #7c3aed; }
.node-client circle { fill: #22c55e; stroke: #16a34a; }
.node-root circle { fill: #3b82f6; stroke: #2563eb; }
.node-collapsed circle { fill-opacity: 0.6; }
.tooltip-box { position: absolute; background: #1e293b; color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 12px; pointer-events: none; z-index: 1000; max-width: 260px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.legend { display: flex; gap: 20px; padding: 8px 12px; background: #fff; border-radius: 6px; margin-bottom: 12px; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 5px; font-size: 12px; }
.legend-color { width: 12px; height: 12px; border-radius: 50%; }
.topo-filter { font-size: 12px !important; padding: 3px 6px !important; height: 28px !important; min-width: 110px; max-width: 150px; }
.topo-btn { padding: 3px 8px !important; height: 28px !important; font-size: 12px !important; }
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px; padding: 10px 15px;">
        <h3 class="card-title mb-0" style="font-size:14px;"><i class="fas fa-project-diagram mr-1"></i>Network Topology Diagram</h3>
        <div class="d-flex align-items-center flex-wrap" style="gap:6px;">
            <select id="oltFilter" class="form-control topo-filter">
                <option value="">All OLTs</option>
                @foreach($olts as $olt)
                <option value="{{ $olt->id }}">{{ $olt->name }}</option>
                @endforeach
            </select>
            <select id="zoneFilter" class="form-control topo-filter">
                <option value="">All Zones</option>
                @foreach($zones as $zone)
                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                @endforeach
            </select>
            <select id="areaFilter" class="form-control topo-filter">
                <option value="">All Sub-Zones</option>
                @foreach($areas as $area)
                <option value="{{ $area->id }}" data-zone="{{ $area->zone_id }}">{{ $area->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary topo-btn" onclick="resetFilters()" title="Reset"><i class="fas fa-times"></i></button>
            <button class="btn btn-sm btn-outline-secondary topo-btn" onclick="resetZoom()" title="Reset zoom"><i class="fas fa-compress-arrows-alt"></i></button>
            <button class="btn btn-sm btn-outline-secondary topo-btn" onclick="expandAll()" title="Expand all"><i class="fas fa-expand-alt"></i></button>
            <button class="btn btn-sm btn-outline-secondary topo-btn" onclick="collapseAll()" title="Collapse all"><i class="fas fa-compress-alt"></i></button>
            <a href="{{ route('admin.network.splitters') }}" class="btn btn-sm btn-primary topo-btn"><i class="fas fa-plus mr-1"></i>Splitters</a>
        </div>
    </div>
    <div class="card-body" style="padding: 12px;">
        <div class="legend">
            <div class="legend-item"><div class="legend-color" style="background:#3b82f6;"></div> Network</div>
            <div class="legend-item"><div class="legend-color" style="background:#ef4444;"></div> OLT</div>
            <div class="legend-item"><div class="legend-color" style="background:#8b5cf6;"></div> Splitter</div>
            <div class="legend-item"><div class="legend-color" style="background:#22c55e;"></div> Client</div>
        </div>
        <div id="topology-container">
            <svg id="topology-svg"></svg>
        </div>
    </div>
</div>

<div id="tooltip" class="tooltip-box" style="display:none;"></div>
@endsection

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
let root, svg, g, tree, tooltip;
const width = document.getElementById('topology-container').clientWidth;
const height = Math.max(500, window.innerHeight - 280);
const nodeRadius = 8;
const allAreaOptions = Array.from(document.querySelectorAll('#areaFilter option[data-zone]'));

function initDiagram() {
    d3.select("#topology-svg").selectAll("*").remove();
    svg = d3.select("#topology-svg").attr("width", width).attr("height", height);
    g = svg.append("g").attr("transform", "translate(50,20)");
    tooltip = d3.select("#tooltip");
    const zoom = d3.zoom().scaleExtent([0.3, 3])
        .on("zoom", (event) => g.attr("transform", event.transform));
    svg.call(zoom);
    window.zoomBehavior = zoom;
}

function loadTopology() {
    const f = {
        olt_id:  document.getElementById('oltFilter').value,
        zone_id: document.getElementById('zoneFilter').value,
        area_id: document.getElementById('areaFilter').value,
    };
    const params = new URLSearchParams();
    if (f.olt_id)  params.append('olt_id',  f.olt_id);
    if (f.zone_id) params.append('zone_id', f.zone_id);
    if (f.area_id) params.append('area_id', f.area_id);
    const url = '{{ route("admin.network.topology.data") }}' + (params.toString() ? '?' + params.toString() : '');

    fetch(url)
        .then(res => res.json())
        .then(data => {
            // Clear and re-init to avoid stale elements
            d3.select("#topology-svg").selectAll("*").remove();
            g = svg.append("g").attr("transform", "translate(50,20)");
            const zoom = d3.zoom().scaleExtent([0.3, 3])
                .on("zoom", (event) => g.attr("transform", event.transform));
            svg.call(zoom);
            window.zoomBehavior = zoom;

            root = d3.hierarchy(data);
            root.x0 = height / 2;
            root.y0 = 0;
            root.descendants().forEach((d, i) => { d.id = i; d._children = d.children; });
            updateDiagram(root);
        });
}

function updateDiagram(source) {
    const duration = 300;
    const dx = 25;
    const dy = width / (root.height + 2);
    tree = d3.tree().nodeSize([dx, dy]);
    tree(root);
    let index = -1;
    root.eachBefore(d => { d.x = ++index * dx; });
    const h = Math.max(500, (index + 1) * dx + 50);
    svg.transition().duration(duration).attr("height", h);

    const nodes = root.descendants();
    const links = root.links();

    const link = g.selectAll(".link").data(links, d => d.target.id);
    const linkEnter = link.enter().append("path").attr("class", "link")
        .attr("d", d => { const o = {x: source.x0, y: source.y0}; return diagonal({source: o, target: o}); });
    link.merge(linkEnter).transition().duration(duration).attr("d", d => diagonal(d));
    link.exit().transition().duration(duration)
        .attr("d", d => { const o = {x: source.x, y: source.y}; return diagonal({source: o, target: o}); }).remove();

    const node = g.selectAll(".node").data(nodes, d => d.id);
    const nodeEnter = node.enter().append("g")
        .attr("class", d => "node node-" + d.data.type + (d._children && !d.children ? " node-collapsed" : ""))
        .attr("transform", d => `translate(${source.y0},${source.x0})`)
        .on("click", (event, d) => {
            if (d.children) { d._children = d.children; d.children = null; }
            else { d.children = d._children; }
            updateDiagram(d);
        })
        .on("mouseover", showTooltip)
        .on("mouseout", hideTooltip);

    nodeEnter.append("circle")
        .attr("r", d => d.data.type === 'olt' ? 10 : (d.data.type === 'splitter' ? 9 : nodeRadius));
    nodeEnter.append("text")
        .attr("dy", "0.31em")
        .attr("x", d => d._children ? -12 : 12)
        .attr("text-anchor", d => d._children ? "end" : "start")
        .text(d => d.data.name)
        .clone(true).lower().attr("stroke", "white").attr("stroke-width", 3);

    node.merge(nodeEnter).transition().duration(duration)
        .attr("transform", d => `translate(${d.y},${d.x})`)
        .attr("class", d => "node node-" + d.data.type + (d._children && !d.children ? " node-collapsed" : ""));
    node.exit().transition().duration(duration)
        .attr("transform", d => `translate(${source.y},${source.x})`).remove();
    root.eachBefore(d => { d.x0 = d.x; d.y0 = d.y; });
}

function diagonal(d) {
    return `M${d.source.y},${d.source.x} C${(d.source.y+d.target.y)/2},${d.source.x} ${(d.source.y+d.target.y)/2},${d.target.x} ${d.target.y},${d.target.x}`;
}

function showTooltip(event, d) {
    let html = '<strong>' + d.data.name + '</strong>';
    if (d.data.type === 'olt') {
        html += '<br>IP: ' + (d.data.ip || 'N/A') + '<br>Vendor: ' + (d.data.vendor || 'N/A') + '<br>Ports: ' + (d.data.ports || 'N/A');
    } else if (d.data.type === 'splitter') {
        html += '<br>Type: ' + (d.data.splitter_type || 'N/A') + '<br>Location: ' + (d.data.location || 'N/A');
    } else if (d.data.type === 'client') {
        html += '<br>ID: ' + (d.data.client_id || 'N/A') + '<br>Status: ' + (d.data.status || 'N/A');
        html += '<br>Package: ' + (d.data.package || 'N/A');
        if (d.data.zone)  html += '<br>Zone: ' + d.data.zone;
        if (d.data.area)  html += '<br>Sub-Zone: ' + d.data.area;
        html += '<br>Phone: ' + (d.data.phone || 'N/A');
    }
    tooltip.html(html).style("display","block")
        .style("left",(event.pageX+12)+"px").style("top",(event.pageY-10)+"px");
}
function hideTooltip() { tooltip.style("display","none"); }
function resetZoom() { svg.transition().duration(500).call(window.zoomBehavior.transform, d3.zoomIdentity.translate(50,20)); }
function resetFilters() {
    document.getElementById('oltFilter').value = '';
    document.getElementById('zoneFilter').value = '';
    updateAreaOptions('');
    document.getElementById('areaFilter').value = '';
    loadTopology();
}
function expandAll() {
    root.descendants().forEach(d => { if (d._children) d.children = d._children; });
    updateDiagram(root);
}
function collapseAll() {
    root.descendants().forEach(d => { if (d.depth > 1 && d.children) { d._children = d.children; d.children = null; } });
    updateDiagram(root);
}
function updateAreaOptions(zoneId) {
    const areaSelect = document.getElementById('areaFilter');
    const cur = areaSelect.value;
    while (areaSelect.options.length > 1) areaSelect.remove(1);
    allAreaOptions.forEach(opt => {
        if (!zoneId || opt.dataset.zone === zoneId) areaSelect.appendChild(opt.cloneNode(true));
    });
    if (!Array.from(areaSelect.options).some(o => o.value === cur)) areaSelect.value = '';
}

document.getElementById('oltFilter').addEventListener('change', loadTopology);
document.getElementById('zoneFilter').addEventListener('change', function() { updateAreaOptions(this.value); loadTopology(); });
document.getElementById('areaFilter').addEventListener('change', loadTopology);

initDiagram();
loadTopology();
</script>
@endpush
