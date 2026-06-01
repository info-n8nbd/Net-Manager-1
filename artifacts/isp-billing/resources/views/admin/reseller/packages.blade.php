@extends('layouts.admin')

@section('title', 'MAC Reseller Packages')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index') }}">MAC Resellers</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item active">Packages</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cubes mr-2"></i>Package Rates for {{ $reseller->name }}</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Original Price (৳)</th>
                            <th>MAC Reseller Cost (৳)</th>
                            <th>Client Selling Price (৳)</th>
                            <th>Profit (৳)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resellerPackages as $rp)
                        <tr id="row-{{ $rp->id }}">
                            <td>{{ $rp->package->name ?? 'N/A' }}</td>
                            <td>৳{{ number_format($rp->package->price ?? 0, 2) }}</td>
                            <td class="display-cell-{{ $rp->id }}">৳{{ number_format($rp->reseller_price, 2) }}</td>
                            <td class="display-cell-{{ $rp->id }}">৳{{ number_format($rp->selling_price, 2) }}</td>
                            <td>
                                <span class="badge badge-{{ ($rp->selling_price - $rp->reseller_price) > 0 ? 'success' : 'warning' }}">
                                    ৳{{ number_format($rp->selling_price - $rp->reseller_price, 2) }}
                                </span>
                            </td>
                            <td>
                                <div class="display-cell-{{ $rp->id }}">
                                    <button class="btn btn-sm btn-info" onclick="showEditForm({{ $rp->id }}, {{ $rp->reseller_price }}, {{ $rp->selling_price }})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.reseller.packages.delete', [$reseller, $rp]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this package from reseller?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Remove">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <form action="{{ route('admin.reseller.packages.update', [$reseller, $rp]) }}" method="POST" class="edit-form-{{ $rp->id }}" style="display:none;">
                                    @csrf
                                    @method('PUT')
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="reseller_price" class="form-control" step="0.01" min="0" style="width:80px;">
                                        <input type="number" name="selling_price" class="form-control" step="0.01" min="0" style="width:80px;">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i></button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="hideEditForm({{ $rp->id }})"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                            No packages assigned yet. Use the form on the right to add packages.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Add Package</h3>
            </div>
            <form action="{{ route('admin.reseller.packages.store', $reseller) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Package</label>
                        <select name="package_id" class="form-control" id="packageSelect" required>
                            <option value="">-- Select Package --</option>
                            @foreach($allPackages as $package)
                            <option value="{{ $package->id }}" data-price="{{ $package->price }}">{{ $package->name }} (৳{{ number_format($package->price, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>MAC Reseller Cost (৳)</label>
                        <input type="number" name="reseller_price" id="newResellerPrice" class="form-control" step="0.01" min="0" required placeholder="What ISP charges reseller">
                        <small class="text-muted">Amount ISP will charge the reseller per client</small>
                    </div>
                    <div class="form-group">
                        <label>Client Selling Price (৳)</label>
                        <input type="number" name="selling_price" class="form-control" step="0.01" min="0" required placeholder="What client pays" id="sellingPrice">
                        <small class="text-muted">Amount client pays to the reseller</small>
                    </div>
                    <div class="callout callout-info py-2 px-3 mb-0" id="profitPreview" style="display:none;">
                        <small><strong>Reseller Profit:</strong> ৳<span id="profitAmount">0.00</span> per client/month</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Save Package Rate
                    </button>
                </div>
            </form>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-body">
                <h6><i class="fas fa-info-circle mr-1"></i> How it works</h6>
                <ul class="pl-3 mb-0" style="font-size: 0.85rem;">
                    <li><strong>MAC Reseller Cost:</strong> What ISP charges the reseller for each client on this package</li>
                    <li><strong>Client Selling Price:</strong> What the end client pays the reseller</li>
                    <li><strong>Profit:</strong> Selling Price - Reseller Cost = Reseller's profit per client</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showEditForm(id, resellerPrice, sellingPrice) {
    document.querySelectorAll('.display-cell-' + id).forEach(el => el.style.display = 'none');
    var form = document.querySelector('.edit-form-' + id);
    form.style.display = '';
    form.querySelector('[name="reseller_price"]').value = resellerPrice;
    form.querySelector('[name="selling_price"]').value = sellingPrice;
}
function hideEditForm(id) {
    document.querySelectorAll('.display-cell-' + id).forEach(el => el.style.display = '');
    document.querySelector('.edit-form-' + id).style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    var resellerPriceInput = document.getElementById('newResellerPrice');
    var sellingPriceInput = document.getElementById('sellingPrice');
    var profitPreview = document.getElementById('profitPreview');
    var profitAmount = document.getElementById('profitAmount');
    var packageSelect = document.getElementById('packageSelect');

    function updateProfit() {
        var rp = parseFloat(resellerPriceInput.value) || 0;
        var sp = parseFloat(sellingPriceInput.value) || 0;
        if (rp > 0 || sp > 0) {
            profitPreview.style.display = '';
            profitAmount.textContent = (sp - rp).toFixed(2);
        } else {
            profitPreview.style.display = 'none';
        }
    }

    if (packageSelect) {
        packageSelect.addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            var price = selected.getAttribute('data-price');
            if (price) {
                sellingPriceInput.value = parseFloat(price).toFixed(2);
                updateProfit();
            }
        });
    }

    if (resellerPriceInput) resellerPriceInput.addEventListener('input', updateProfit);
    if (sellingPriceInput) sellingPriceInput.addEventListener('input', updateProfit);
});
</script>
@endpush
@endsection
