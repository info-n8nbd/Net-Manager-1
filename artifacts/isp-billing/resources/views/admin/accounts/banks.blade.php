@extends('layouts.admin')

@section('title', 'Bank Accounts')
@section('breadcrumb')
<li class="breadcrumb-item active">Bank Accounts</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Bank Accounts</h3></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Account Name</th><th>Account Number</th><th>Current Balance (৳)</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($accounts as $account)
                <tr>
                    <td>{{ $account->name }}</td>
                    <td>{{ $account->account_number ?? '-' }}</td>
                    <td>{{ number_format($account->current_balance ?? 0, 2) }}</td>
                    <td><span class="badge badge-{{ $account->is_active ? 'success' : 'danger' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center">No bank accounts</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
