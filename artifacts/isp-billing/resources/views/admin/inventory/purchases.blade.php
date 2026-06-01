@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Purchase List</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Purchase No</th>
                <th>Vendor</th>
                <th>Purchase Date</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Due</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchases as $purchase)
            <tr>
                <td>{{ $purchase->id }}</td>
                <td>{{ $purchase->purchase_no }}</td>
                <td>{{ $purchase->vendor_name }}</td>
                <td>{{ $purchase->purchase_date }}</td>
                <td>{{ $purchase->total }}</td>
                <td>{{ $purchase->paid }}</td>
                <td>{{ $purchase->due }}</td>
                <td>{{ $purchase->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
