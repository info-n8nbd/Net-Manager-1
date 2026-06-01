@extends('layouts.admin')
@section('title', 'Edit Referral Partner')

@section('content')
<div class="card card-outline card-warning">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Edit Referral Partner</h3>
        <div class="ml-auto">
            <a href="{{ route('admin.agent.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> BACK</a>
        </div>
    </div>
    <form action="{{ route('admin.agent.update', $agent->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Partner ID</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control form-control-sm" value="{{ $agent->agent_id }}" readonly>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Referral Partner Name<span class="text-danger">*</span></label>
                        <div class="col-sm-7">
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $agent->name) }}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Username</label>
                        <div class="col-sm-7">
                            <input type="text" name="username" class="form-control form-control-sm" value="{{ old('username', $agent->username) }}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Password</label>
                        <div class="col-sm-7">
                            <div class="input-group input-group-sm">
                                <input type="password" name="password" id="editPassword" class="form-control form-control-sm" placeholder="Leave blank to keep current">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#editPassword" tabindex="-1"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Default Commission<span class="text-danger">*</span></label>
                        <div class="col-sm-7">
                            <div class="input-group input-group-sm">
                                <input type="number" name="default_commission" class="form-control form-control-sm" value="{{ old('default_commission', $agent->default_commission) }}" step="0.01" min="0" max="100" required>
                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Father's Name</label>
                        <div class="col-sm-7">
                            <input type="text" name="father_name" class="form-control form-control-sm" value="{{ old('father_name', $agent->father_name) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Mother's Name</label>
                        <div class="col-sm-7">
                            <input type="text" name="mother_name" class="form-control form-control-sm" value="{{ old('mother_name', $agent->mother_name) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Date of Birth</label>
                        <div class="col-sm-7">
                            <input type="date" name="date_of_birth" class="form-control form-control-sm" value="{{ old('date_of_birth', $agent->date_of_birth ? $agent->date_of_birth->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Joining Date</label>
                        <div class="col-sm-7">
                            <input type="date" name="joining_date" class="form-control form-control-sm" value="{{ old('joining_date', $agent->joining_date ? $agent->joining_date->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Gender</label>
                        <div class="col-sm-7 pt-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" value="male" {{ old('gender', $agent->gender) === 'male' ? 'checked' : '' }}>
                                <label class="form-check-label">Male</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" value="female" {{ old('gender', $agent->gender) === 'female' ? 'checked' : '' }}>
                                <label class="form-check-label">Female</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Marital Status</label>
                        <div class="col-sm-7 pt-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="marital_status" value="unmarried" {{ old('marital_status', $agent->marital_status) === 'unmarried' ? 'checked' : '' }}>
                                <label class="form-check-label">Unmarried</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="marital_status" value="married" {{ old('marital_status', $agent->marital_status) === 'married' ? 'checked' : '' }}>
                                <label class="form-check-label">Married</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Blood Group</label>
                        <div class="col-sm-7">
                            <select name="blood_group" class="form-control form-control-sm" style="width:120px;">
                                <option value="">Blood Gr...</option>
                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                <option value="{{ $bg }}" {{ old('blood_group', $agent->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">National ID</label>
                        <div class="col-sm-7">
                            <input type="text" name="national_id" class="form-control form-control-sm" value="{{ old('national_id', $agent->national_id) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Present Address</label>
                        <div class="col-sm-7">
                            <textarea name="present_address" class="form-control form-control-sm" rows="2">{{ old('present_address', $agent->present_address) }}</textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Permanent Address</label>
                        <div class="col-sm-7">
                            <textarea name="permanent_address" class="form-control form-control-sm" rows="2">{{ old('permanent_address', $agent->permanent_address) }}</textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Personal Contact No</label>
                        <div class="col-sm-7">
                            <input type="text" name="personal_contact" class="form-control form-control-sm" value="{{ old('personal_contact', $agent->personal_contact) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">WhatsApp No</label>
                        <div class="col-sm-7">
                            <input type="text" name="whatsapp_no" class="form-control form-control-sm" value="{{ old('whatsapp_no', $agent->whatsapp_no) }}" placeholder="WhatsApp No">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Office Contact No</label>
                        <div class="col-sm-7">
                            <input type="text" name="office_contact" class="form-control form-control-sm" value="{{ old('office_contact', $agent->office_contact) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Family Contact No</label>
                        <div class="col-sm-7">
                            <input type="text" name="family_contact" class="form-control form-control-sm" value="{{ old('family_contact', $agent->family_contact) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">First Reference No</label>
                        <div class="col-sm-7">
                            <input type="text" name="first_reference_no" class="form-control form-control-sm" value="{{ old('first_reference_no', $agent->first_reference_no) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Second Reference No</label>
                        <div class="col-sm-7">
                            <input type="text" name="second_reference_no" class="form-control form-control-sm" value="{{ old('second_reference_no', $agent->second_reference_no) }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-5 col-form-label text-right">Email</label>
                        <div class="col-sm-7">
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $agent->email) }}" placeholder="abc@example.com">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="reset" class="btn btn-secondary btn-sm mr-2">RESET</button>
            <button type="submit" class="btn btn-warning btn-sm">SUBMIT</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.toggle-password', function() {
    var input = $($(this).data('target'));
    var icon = $(this).find('i');
    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        input.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});
</script>
@endpush
