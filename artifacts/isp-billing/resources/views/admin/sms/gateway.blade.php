@extends('layouts.admin')
@section('title', 'SMS Gateway')
@section('breadcrumb')<li class="breadcrumb-item active">SMS Gateway</li>@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">SMS Gateway Configuration</h3>
    </div>
    <form action="{{ route('admin.sms.gateway.update') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label>SMS Provider</label>
                <select name="provider" class="form-control">
                    <option value="bdbulksms">BD Bulk SMS</option>
                    <option value="smsq">SMSQ</option>
                    <option value="reve">REVE Systems</option>
                    <option value="elitbuzz">ElitBuzz</option>
                    <option value="custom">Custom API</option>
                </select>
            </div>
            <div class="form-group">
                <label>API Key</label>
                <input type="text" name="api_key" class="form-control">
            </div>
            <div class="form-group">
                <label>Sender ID</label>
                <input type="text" name="sender_id" class="form-control">
            </div>
            <div class="form-group">
                <label>API URL</label>
                <input type="url" name="api_url" class="form-control">
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Save Configuration</button>
            <button type="button" class="btn btn-info">Test Connection</button>
        </div>
    </form>
</div>
@endsection
