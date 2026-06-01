@extends('layouts.admin')

@section('title', 'Client Ledger - ' . $client->name)

@section('content')
        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Client Information</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Client ID:</th>
                                <td>{{ $client->client_id }}</td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td>{{ $client->name }}</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>{{ $client->phone }}</td>
                            </tr>
                            <tr>
                                <th>Package:</th>
                                <td>{{ $client->package?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Zone:</th>
                                <td>{{ $client->zone?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge badge-{{ $client->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($client->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Current Due:</th>
                                <td class="text-danger font-weight-bold">৳{{ number_format($client->due_amount ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Advance Balance:</th>
                                <td class="text-success font-weight-bold">৳{{ number_format($client->advance_amount ?? 0, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Transaction Ledger</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>Description</th>
                                    <th class="text-right">Debit (৳)</th>
                                    <th class="text-right">Credit (৳)</th>
                                    <th class="text-right">Balance (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $runningBalance = 0; @endphp
                                @forelse($ledger as $entry)
                                    @php 
                                        $runningBalance += ($entry['debit'] ?? 0) - ($entry['credit'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d-m-Y') }}</td>
                                        <td>
                                            <span class="badge badge-{{ ($entry['type'] ?? '') === 'bill' || ($entry['type'] ?? '') === 'Bill' ? 'warning' : 'success' }}">
                                                {{ ucfirst($entry['type'] ?? '') }}
                                            </span>
                                        </td>
                                        <td>{{ $entry['reference'] ?? $entry['ref'] ?? '-' }}</td>
                                        <td>
                                            {{ $entry['description'] ?? '-' }}
                                            @if(($entry['type'] ?? '') === 'payment')
                                                @if(!empty($entry['method']))<br><small class="text-muted"><i class="fas fa-credit-card mr-1"></i>{{ $entry['method'] }}</small>@endif
                                                @if(!empty($entry['money_receipt_no']))<br><span class="badge badge-secondary" style="font-size:10px;"><i class="fas fa-receipt mr-1"></i>{{ $entry['money_receipt_no'] }}</span>@endif
                                                @if(!empty($entry['transaction_id']))<br><span class="badge badge-info" style="font-size:10px;"><i class="fas fa-hashtag mr-1"></i>{{ $entry['transaction_id'] }}</span>@endif
                                                @if(!empty($entry['sender_number']))<br><span class="badge badge-warning" style="font-size:10px;"><i class="fas fa-mobile-alt mr-1"></i>{{ $entry['sender_number'] }}</span>@endif
                                            @endif
                                        </td>
                                        <td class="text-right {{ ($entry['debit'] ?? 0) > 0 ? 'text-danger' : '' }}">
                                            {{ ($entry['debit'] ?? 0) > 0 ? number_format($entry['debit'] ?? 0, 2) : '-' }}
                                        </td>
                                        <td class="text-right {{ ($entry['credit'] ?? 0) > 0 ? 'text-success' : '' }}">
                                            {{ ($entry['credit'] ?? 0) > 0 ? number_format($entry['credit'] ?? 0, 2) : '-' }}
                                        </td>
                                        <td class="text-right {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format(abs($runningBalance), 2) }}
                                            {{ $runningBalance > 0 ? 'Dr' : ($runningBalance < 0 ? 'Cr' : '') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No transactions found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($ledger) > 0)
                            <tfoot>
                                <tr class="bg-light font-weight-bold">
                                    <td colspan="4" class="text-right">Total:</td>
                                    <td class="text-right text-danger">৳{{ number_format(collect($ledger)->sum('debit'), 2) }}</td>
                                    <td class="text-right text-success">৳{{ number_format(collect($ledger)->sum('credit'), 2) }}</td>
                                    <td class="text-right {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                                        ৳{{ number_format(abs($runningBalance), 2) }}
                                        {{ $runningBalance > 0 ? 'Dr' : ($runningBalance < 0 ? 'Cr' : '') }}
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
@endsection
