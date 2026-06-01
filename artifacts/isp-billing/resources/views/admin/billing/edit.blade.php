@extends('layouts.admin')

@section('title', 'Edit Bill')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.index') }}">Bills</a></li>
<li class="breadcrumb-item active">Edit Bill #{{ $bill->bill_no }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Bill</h3>
            </div>
            <form action="{{ route('admin.billing.update', $bill) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Client:</strong> {{ $bill->client->name }} ({{ $bill->client->client_id }})<br>
                        <strong>Bill No:</strong> {{ $bill->bill_no }}<br>
                        <strong>Period:</strong> {{ $bill->billing_month }}/{{ $bill->billing_year }}
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Package Amount (৳)</label>
                                <input type="number" name="package_amount" class="form-control" value="{{ $bill->package_amount }}" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Previous Due (৳)</label>
                                <input type="number" name="previous_due" class="form-control" value="{{ $bill->previous_due }}" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Total Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="total_amount" class="form-control" value="{{ $bill->total_amount }}" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Discount (৳) <span class="text-success"><i class="fas fa-tag"></i></span></label>
                                <input type="number" name="discount" class="form-control" value="{{ $bill->discount ?? 0 }}" step="0.01" min="0">
                                <small class="text-muted">Enter discount amount to reduce due</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Due Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="due_amount" class="form-control" value="{{ $bill->due_amount }}" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" required>
                                    <option value="unpaid" {{ $bill->status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="partial" {{ $bill->status == 'partial' ? 'selected' : '' }}>Partial</option>
                                    <option value="paid" {{ $bill->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Discount Reason</label>
                                <input type="text" name="discount_reason" class="form-control" value="{{ $bill->discount_reason ?? '' }}" placeholder="e.g., Service issue, Promotional discount">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update Bill</button>
                    <a href="{{ route('admin.billing.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trash mr-2"></i>Delete Bill</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Permanently delete this bill. This action cannot be undone.</p>
                <form action="{{ route('admin.billing.destroy', $bill) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this bill? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash mr-1"></i>Delete Bill
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
