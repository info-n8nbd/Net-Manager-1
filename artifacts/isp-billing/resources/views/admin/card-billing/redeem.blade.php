@extends('layouts.admin')

@section('title', 'Redeem Card')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 offset-md-3">

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-gift mr-2"></i>Redeem Scratch Card</h3>
                </div>
                <form action="{{ route('admin.card-billing.process-redeem') }}" method="POST">
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
                            <label><i class="fas fa-credit-card mr-1"></i>Card Number</label>
                            <input type="text" name="card_number" class="form-control" value="{{ old('card_number') }}" placeholder="Enter card number" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-key mr-1"></i>PIN</label>
                            <input type="text" name="pin" class="form-control" value="{{ old('pin') }}" placeholder="Enter card PIN" required>
                        </div>

                        @include('admin.partials.client-search', [
                            'inputId' => 'redeemClientSearch',
                            'hiddenId' => 'redeemClientSearchId',
                        ])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-check-circle mr-1"></i>Redeem Card
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
