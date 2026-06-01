@extends('layouts.admin')

@section('title', 'Fiber Usage Log')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Inventory</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
    <li class="breadcrumb-item active">Usage Log</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-plus-circle"></i> Log Fiber Usage</h3>
            </div>
            <form method="POST" action="{{ route('admin.inventory.fiber.usage.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="fiber_stock_id"><i class="fa-solid fa-ethernet"></i> Fiber Stock</label>
                        <select name="fiber_stock_id" id="fiber_stock_id" class="form-control" required>
                            <option value="">-- Select Fiber --</option>
                            @foreach($stocks as $s)
                                <option value="{{ $s->id }}" {{ old('fiber_stock_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->fiber_code }} ({{ number_format($s->remaining_meter) }}m remaining)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="used_meter"><i class="fa-solid fa-ruler"></i> Meter Used</label>
                        <input type="number" name="used_meter" id="used_meter" class="form-control" step="0.01" min="0.01" value="{{ old('used_meter') }}" placeholder="Enter meters used" required>
                    </div>
                    @include('admin.partials.client-search', [
                        'inputId' => 'fiberUsageClientSearch',
                        'hiddenId' => 'fiberUsageClientSearchId',
                        'fieldName' => 'client_id',
                        'label' => 'Client',
                        'required' => false,
                    ])
                    <div class="form-group">
                        <label for="technician_id"><i class="fa-solid fa-user-gear"></i> Technician</label>
                        <select name="technician_id" id="technician_id" class="form-control">
                            <option value="">-- Select Technician (Optional) --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ old('technician_id') == $tech->id ? 'selected' : '' }}>
                                    {{ $tech->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="used_date"><i class="fa-solid fa-calendar"></i> Date</label>
                        <input type="date" name="used_date" id="used_date" class="form-control" value="{{ old('used_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="note"><i class="fa-solid fa-sticky-note"></i> Note</label>
                        <textarea name="note" id="note" class="form-control" rows="2" placeholder="Optional note...">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fa-solid fa-save"></i> Log Usage
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Recent Usage Log</h3>
            </div>
            <div class="card-body">
                <table id="usageTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Fiber Code</th>
                            <th>Meter Used</th>
                            <th>Client</th>
                            <th>Technician</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usages as $usage)
                        <tr>
                            <td>{{ $usage->used_date ? \Carbon\Carbon::parse($usage->used_date)->format('d-m-Y') : 'N/A' }}</td>
                            <td><strong>{{ $usage->fiberStock->fiber_code ?? 'N/A' }}</strong></td>
                            <td>{{ number_format($usage->used_meter) }}m</td>
                            <td>{{ $usage->client->name ?? 'N/A' }}</td>
                            <td>{{ $usage->technician->name ?? 'N/A' }}</td>
                            <td>{{ $usage->note ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No usage records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
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
        {{ $usages->appends(request()->query())->links() }}
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
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var colCount = $('#usageTable thead tr th').length;
    var bodyColCount = $('#usageTable tbody tr:first td').length;
    if ((colCount === bodyColCount || $('#usageTable tbody tr').length === 0) && $('#usageTable tbody tr td[colspan]').length === 0) {
        $('#usageTable').DataTable({
            responsive: true,
            paging: false,
            info: false,
            searching: true,
            order: [[0, 'desc']]
        });
    }
});
</script>
@endpush
