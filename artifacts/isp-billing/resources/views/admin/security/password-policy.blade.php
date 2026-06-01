@extends('layouts.admin')

@section('title', 'Password Policy')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-key mr-2"></i>Password Policy Settings</h3>
                </div>
                <form action="{{ route('admin.security.password-policy.update') }}" method="POST">
                    @csrf @method('PUT')
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
                            <label><i class="fas fa-ruler mr-1"></i>Minimum Password Length</label>
                            <input type="number" name="min_length" class="form-control" value="{{ $policy->min_length ?? 8 }}" min="6" max="128">
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="require_uppercase" name="require_uppercase" value="1" {{ ($policy->require_uppercase ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="require_uppercase">Require Uppercase Letters (A-Z)</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="require_lowercase" name="require_lowercase" value="1" {{ ($policy->require_lowercase ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="require_lowercase">Require Lowercase Letters (a-z)</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="require_numbers" name="require_numbers" value="1" {{ ($policy->require_numbers ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="require_numbers">Require Numbers (0-9)</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="require_special" name="require_special" value="1" {{ ($policy->require_special ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="require_special">Require Special Characters (!@#$%)</label>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt mr-1"></i>Password Expiry (Days)</label>
                            <input type="number" name="expiry_days" class="form-control" value="{{ $policy->expiry_days ?? 0 }}" min="0">
                            <small class="text-muted">Set to 0 for no expiry</small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-history mr-1"></i>Password History (prevent reuse)</label>
                            <input type="number" name="history_count" class="form-control" value="{{ $policy->history_count ?? 0 }}" min="0" max="24">
                            <small class="text-muted">Number of previous passwords to remember. Set to 0 to disable.</small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock mr-1"></i>Max Login Attempts Before Lockout</label>
                            <input type="number" name="max_attempts" class="form-control" value="{{ $policy->max_attempts ?? 5 }}" min="1" max="20">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-clock mr-1"></i>Lockout Duration (minutes)</label>
                            <input type="number" name="lockout_duration" class="form-control" value="{{ $policy->lockout_duration ?? 30 }}" min="1">
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.security.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save mr-1"></i>Save Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
