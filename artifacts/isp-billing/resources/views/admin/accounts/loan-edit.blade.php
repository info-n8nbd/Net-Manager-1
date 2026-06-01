@extends('layouts.admin')

@section('title', 'Edit Loan — ' . $loan->reference_no)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.cash') }}">Accounts</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.loan') }}">Loan Management</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.loan.show', $loan) }}">{{ $loan->reference_no }}</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Loan — {{ $loan->reference_no }}</h3>
            </div>
            <form action="{{ route('admin.accounts.loan.update', $loan) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @if($loan->paid_amount > 0)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        This loan has payments recorded (৳{{ number_format($loan->paid_amount, 2) }} paid).
                        Amount, Type, Account, and Loan Date cannot be changed.
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Loan Type <span class="text-danger">*</span></label>
                                @if($loan->paid_amount > 0)
                                <input type="text" class="form-control" value="{{ $loan->type === 'received' ? 'Loan Received' : 'Loan Given' }}" disabled>
                                @else
                                <select name="type" class="form-control" required>
                                    <option value="received" {{ $loan->type === 'received' ? 'selected' : '' }}>Loan Received</option>
                                    <option value="given" {{ $loan->type === 'given' ? 'selected' : '' }}>Loan Given</option>
                                </select>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount (৳) <span class="text-danger">*</span></label>
                                @if($loan->paid_amount > 0)
                                <input type="text" class="form-control" value="৳{{ number_format($loan->amount, 2) }}" disabled>
                                @else
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ $loan->amount }}" required>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Party Name <span class="text-danger">*</span></label>
                                <input type="text" name="party_name" class="form-control" value="{{ $loan->party_name }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Party Phone</label>
                                <input type="text" name="party_phone" class="form-control" value="{{ $loan->party_phone }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account <span class="text-danger">*</span></label>
                                @if($loan->paid_amount > 0)
                                <input type="text" class="form-control" value="{{ $loan->account->name ?? '-' }}" disabled>
                                @else
                                <select name="account_id" class="form-control" required>
                                    @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ $loan->account_id == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Loan Date <span class="text-danger">*</span></label>
                                @if($loan->paid_amount > 0)
                                <input type="text" class="form-control" value="{{ $loan->loan_date->format('d-M-Y') }}" disabled>
                                @else
                                <input type="date" name="loan_date" class="form-control" value="{{ $loan->loan_date->format('Y-m-d') }}" required>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date" class="form-control" value="{{ $loan->due_date ? $loan->due_date->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $loan->description }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-6">
                            <a href="{{ route('admin.accounts.loan.show', $loan) }}" class="btn btn-default btn-block"><i class="fas fa-arrow-left mr-1"></i>Cancel</a>
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn btn-warning btn-block"><i class="fas fa-save mr-1"></i>Update Loan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
