@extends('layouts.admin')

@section('title', 'Client Ledger')
@section('breadcrumb')
<li class="breadcrumb-item active">Client Ledger</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Client Ledger</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="form-inline mb-4">
            @include('admin.partials.client-search', [
                'inputId' => 'ledgerClientSearch',
                'hiddenId' => 'ledgerClientSearchId',
                'selectedValue' => request('client_id'),
                'selectedName' => $client ? ($client->client_id . ' - ' . $client->name) : '',
            ])
            <button type="submit" class="btn btn-primary">View Ledger</button>
        </form>

        @if($client)
        <h5>Ledger for: {{ $client->name }} ({{ $client->client_id }})</h5>
        <table class="table table-bordered">
            <thead><tr><th>Date</th><th>Type</th><th>Reference</th><th>Debit (৳)</th><th>Credit (৳)</th></tr></thead>
            <tbody>
                @php $balance = 0; @endphp
                @forelse($ledger as $entry)
                @php $balance += $entry['debit'] - $entry['credit']; @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d-m-Y') }}</td>
                    <td>{{ $entry['type'] }}</td>
                    <td>{{ $entry['reference'] ?? '-' }}</td>
                    <td>{{ $entry['debit'] > 0 ? number_format($entry['debit'] ?? 0, 2) : '-' }}</td>
                    <td>{{ $entry['credit'] > 0 ? number_format($entry['credit'] ?? 0, 2) : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No transactions</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="table-primary">
                    <td colspan="3"><strong>Balance</strong></td>
                    <td colspan="2"><strong>৳{{ number_format($balance ?? 0, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>
</div>
@endsection
