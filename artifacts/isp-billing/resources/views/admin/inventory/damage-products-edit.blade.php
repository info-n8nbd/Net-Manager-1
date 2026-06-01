@extends('layouts.admin')

@section('title', 'Edit Damage Remarks')

@section('content')

        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-pen"></i> Edit Damage Remarks</h3>
            </div>
            <form action="{{ route('admin.inventory.damage.update', $serial->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>Product</label>
                        <input type="text" class="form-control" value="{{ $serial->product->name ?? 'N/A' }} ({{ $serial->serial_number }})" readonly>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" class="form-control" value="{{ ucfirst($serial->status) }}" readonly>
                    </div>

                    <div class="form-group">
                        <label>Damage Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" required>{{ old('remarks', $serial->remarks) }}</textarea>
                        @error('remarks')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-save"></i> Update Remarks</button>
                    <a href="{{ route('admin.inventory.damage.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
@endsection
