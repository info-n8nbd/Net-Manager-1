@extends('layouts.admin')

@section('title', 'Sell Minutes')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Sell Minutes</h3>
    </div>
    <form action="{{ route('admin.minute-sales.process-sell') }}" method="POST">
        @csrf
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
            <div class="row">
                <div class="col-md-6">
                    @include('admin.partials.client-search', [
                        'inputId' => 'minuteSaleClientSearch',
                        'hiddenId' => 'minuteSaleClientSearchId',
                        'label' => 'Client',
                        'required' => true,
                    ])
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Minute Package <span class="text-danger">*</span></label>
                        <select name="minute_package_id" class="form-control @error('minute_package_id') is-invalid @enderror" id="packageSelect" required>
                            <option value="">Select Package</option>
                            @foreach($packages ?? [] as $package)
                                <option value="{{ $package->id }}" data-duration="{{ $package->duration_minutes }}" data-price="{{ $package->price }}" data-bandwidth="{{ $package->bandwidth_down_mbps }}" data-type="{{ $package->connection_type }}" {{ old('minute_package_id') == $package->id ? 'selected' : '' }}>{{ $package->name }} - {{ $package->duration_minutes }} min - ৳{{ number_format($package->price, 2) }}</option>
                            @endforeach
                        </select>
                        @error('minute_package_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div id="packageDetails" class="card card-outline card-info" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">Package Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>Duration:</strong> <span id="detailDuration"></span> minutes</div>
                        <div class="col-md-3"><strong>Price:</strong> ৳<span id="detailPrice"></span></div>
                        <div class="col-md-3"><strong>Bandwidth:</strong> <span id="detailBandwidth"></span> Mbps</div>
                        <div class="col-md-3"><strong>Type:</strong> <span id="detailType"></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> Process Sale</button>
            <a href="{{ route('admin.minute-sales.sales') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('packageSelect').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var details = document.getElementById('packageDetails');
    if (this.value) {
        document.getElementById('detailDuration').textContent = selected.dataset.duration;
        document.getElementById('detailPrice').textContent = parseFloat(selected.dataset.price).toFixed(2);
        document.getElementById('detailBandwidth').textContent = selected.dataset.bandwidth || '-';
        document.getElementById('detailType').textContent = (selected.dataset.type || '').toUpperCase();
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
});
</script>
@endpush
