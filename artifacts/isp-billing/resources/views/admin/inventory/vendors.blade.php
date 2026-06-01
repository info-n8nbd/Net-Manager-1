@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Vendor List</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Address</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendors as $vendor)
            <tr>
                <td>{{ $vendor->id }}</td>
                <td>{{ $vendor->name }}</td>
                <td>{{ $vendor->phone }}</td>
                <td>{{ $vendor->address }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
