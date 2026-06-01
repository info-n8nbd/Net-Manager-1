@extends('layouts.admin')

@section('title', 'Daily Billing')

@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Daily Billing Configurations</h3>
        <form action="{{ route('admin.daily-billing.process') }}" method="POST" class="d-inline" onsubmit="return confirm('Process billing for all configured resellers?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-play mr-1"></i> Process Billing</button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reseller</th>
                        <th>Billing Mode</th>
                        <th>Daily Rate</th>
                        <th>Per-Minute Rate</th>
                        <th>Last Billed</th>
                        <th>Auto-Deduct</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($configs ?? [] as $config)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $config->reseller->name ?? '-' }}</td>
                        <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $config->billing_mode)) }}</span></td>
                        <td>৳{{ number_format($config->daily_rate ?? 0, 2) }}</td>
                        <td>৳{{ number_format($config->per_minute_rate ?? 0, 2) }}</td>
                        <td>{{ $config->last_billed_at ? $config->last_billed_at->format('d-m-y H:i') : 'Never' }}</td>
                        <td>
                            @if($config->auto_deduct)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-danger">No</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.daily-billing.history', $config->reseller) }}" class="btn btn-info btn-xs"><i class="fas fa-history"></i></a>
                            <a href="{{ route('admin.daily-billing.edit', $config) }}" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.daily-billing.destroy', $config) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this configuration?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No billing configurations found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add Billing Configuration</h3>
    </div>
    <form action="{{ route('admin.daily-billing.store') }}" method="POST">
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Reseller <span class="text-danger">*</span></label>
                        <select name="reseller_id" class="form-control @error('reseller_id') is-invalid @enderror" required>
                            <option value="">Select Reseller</option>
                            @foreach($unconfiguredResellers ?? [] as $reseller)
                                <option value="{{ $reseller->id }}" {{ old('reseller_id') == $reseller->id ? 'selected' : '' }}>{{ $reseller->name }}</option>
                            @endforeach
                        </select>
                        @error('reseller_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Billing Mode <span class="text-danger">*</span></label>
                        <select name="billing_mode" class="form-control @error('billing_mode') is-invalid @enderror" required>
                            <option value="">Select Mode</option>
                            <option value="daily_flat" {{ old('billing_mode') == 'daily_flat' ? 'selected' : '' }}>Daily Flat</option>
                            <option value="per_client" {{ old('billing_mode') == 'per_client' ? 'selected' : '' }}>Per Client</option>
                            <option value="per_minute" {{ old('billing_mode') == 'per_minute' ? 'selected' : '' }}>Per Minute</option>
                        </select>
                        @error('billing_mode')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Daily Rate (৳)</label>
                        <input type="number" name="daily_rate" class="form-control @error('daily_rate') is-invalid @enderror" value="{{ old('daily_rate') }}" step="0.01">
                        @error('daily_rate')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Per-Min Rate (৳)</label>
                        <input type="number" name="per_minute_rate" class="form-control @error('per_minute_rate') is-invalid @enderror" value="{{ old('per_minute_rate') }}" step="0.01">
                        @error('per_minute_rate')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Configuration</button>
        </div>
    </form>
</div>
@endsection
