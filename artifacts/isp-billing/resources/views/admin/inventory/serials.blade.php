@extends('layouts.admin')

@section('title', 'Serial Number Management')

@section('content')
        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Add Serial Number</h3>
                    </div>
                    <form action="{{ route('admin.store.serials.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Product</label>
                                <select name="product_id" class="form-control select2" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $prod)
                                        <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Serial Number</label>
                                <input type="text" name="serial_number" class="form-control" required placeholder="Enter serial number">
                            </div>
                            <div class="form-group">
                                <label>MAC Address (Optional)</label>
                                <input type="text" name="mac_address" class="form-control" placeholder="e.g., AA:BB:CC:DD:EE:FF">
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Add Serial
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Summary</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>Total Serials</td>
                                <td class="text-right"><strong>{{ $serials->total() }}</strong></td>
                            </tr>
                            <tr>
                                <td>In Stock</td>
                                <td class="text-right text-success"><strong>{{ $serials->where('status', 'in_stock')->count() }}</strong></td>
                            </tr>
                            <tr>
                                <td>Assigned</td>
                                <td class="text-right text-primary"><strong>{{ $serials->where('status', 'assigned')->count() }}</strong></td>
                            </tr>
                            <tr>
                                <td>Damaged</td>
                                <td class="text-right text-danger"><strong>{{ $serials->where('status', 'damaged')->count() }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h3 class="card-title">Serial Numbers</h3>
                        <div class="card-tools d-flex align-items-center">
                            <div class="input-group input-group-sm mr-2" style="width:250px;">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" class="form-control form-control-sm table-quick-search" placeholder="Quick search..." data-table="serialsTable">
                            </div>
                            <form method="GET" class="form-inline">
                                <select name="product_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                    <option value="">All Products</option>
                                    @foreach($products as $prod)
                                        <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                                            {{ $prod->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table id="serialsTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Serial Number</th>
                                    <th>Product</th>
                                    <th>MAC Address</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Warranty</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serials as $serial)
                                    <tr>
                                        <td><code>{{ $serial->serial_number }}</code></td>
                                        <td>{{ $serial->product->name }}</td>
                                        <td>{{ $serial->mac_address ?? '-' }}</td>
                                        <td>
                                            @if($serial->status === 'in_stock')
                                                <span class="badge badge-success">In Stock</span>
                                            @elseif($serial->status === 'assigned')
                                                <span class="badge badge-primary">Assigned</span>
                                            @elseif($serial->status === 'damaged')
                                                <span class="badge badge-danger">Damaged</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $serial->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $serial->client?->name ?? '-' }}</td>
                                        <td>
                                            @if($serial->warranty_end)
                                                @if($serial->warranty_end < now())
                                                    <span class="text-danger">Expired</span>
                                                @else
                                                    {{ $serial->warranty_end->format('d-m-Y') }}
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($serial->status === 'in_stock')
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-toggle="modal" data-target="#assignModal{{ $serial->id }}">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No serial numbers found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($serials->hasPages())
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
        {{ $serials->appends(request()->query())->links() }}
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
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$('.table-quick-search').on('input', function() {
    var q = $(this).val().toLowerCase();
    var tableId = $(this).data('table');
    $('#' + tableId + ' tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        $(this).toggle(!q || text.indexOf(q) !== -1);
    });
});
</script>
@endpush
