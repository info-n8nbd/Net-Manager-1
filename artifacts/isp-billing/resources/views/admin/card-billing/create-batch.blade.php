@extends('layouts.admin')

@section('title', 'Create Card Batch')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Create New Card Batch</h3>
                </div>
                <form action="{{ route('admin.card-billing.store-batch') }}" method="POST">
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

                        <div class="form-group">
                            <label><i class="fas fa-tag mr-1"></i>Batch Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g., January 2026 - 500 Taka Cards" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-cube mr-1"></i>Package</label>
                            <select name="package_id" class="form-control" required>
                                <option value="">Select Package</option>
                                @foreach($packages as $package)
                                <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                    {{ $package->name }} - ৳{{ number_format($package->price, 2) }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-money-bill-wave mr-1"></i>Card Amount (৳)</label>
                                    <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar-day mr-1"></i>Validity (Days)</label>
                                    <input type="number" name="validity_days" class="form-control" value="{{ old('validity_days', 30) }}" min="1" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-credit-card mr-1"></i>Total Cards to Generate</label>
                                    <input type="number" name="total_cards" class="form-control" value="{{ old('total_cards') }}" min="1" max="10000" placeholder="e.g., 100" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-clock mr-1"></i>Batch Expires At</label>
                                    <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                                    <small class="text-muted">Leave empty for no expiry</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.card-billing.batches') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save mr-1"></i>Create Batch & Generate Cards
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
