@extends('layouts.admin')

@section('title', 'Report Damaged Device')

@section('content')

        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-exclamation-triangle"></i> Report Device as Damaged</h3>
            </div>
            <form action="{{ route('admin.inventory.damage.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Product <span class="text-danger">*</span></label>
                        <select id="product_filter" class="form-control select2">
                            <option value="">-- Filter by Product --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Serial / MAC Address <span class="text-danger">*</span></label>
                        <select name="serial_id" id="serial_id" class="form-control select2" required>
                            <option value="">-- Select Serial --</option>
                            @foreach($serials as $serial)
                                <option value="{{ $serial->id }}" data-product="{{ $serial->product_id }}">
                                    {{ $serial->serial_number }} - {{ $serial->product->name ?? 'N/A' }} [{{ ucfirst($serial->status) }}]
                                </option>
                            @endforeach
                        </select>
                        @error('serial_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Damage Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" required placeholder="Describe the damage or reason...">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-heart-crack"></i> Mark as Damaged</button>
                    <a href="{{ route('admin.inventory.damage.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

    $('#product_filter').on('change', function() {
        var productId = $(this).val();
        var serialSelect = $('#serial_id');
        serialSelect.find('option').each(function() {
            if (!productId || $(this).data('product') == productId || $(this).val() === '') {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        serialSelect.val('').trigger('change');
    });
});
</script>
@endpush
