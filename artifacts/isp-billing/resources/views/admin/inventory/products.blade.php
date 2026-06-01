@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Inventory Product List</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Code</th>
                <th>Unit</th>
                <th>Purchase Price</th>
                <th>Selling Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>{{ $product->id ?? '' }}</td>
                <td>{{ $product->name ?? '' }}</td>
                <td>{{ $product->code ?? '' }}</td>
                <td>{{ $product->unit ?? '' }}</td>
                <td>{{ $product->purchase_price ?? '' }}</td>
                <td>{{ $product->selling_price ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
