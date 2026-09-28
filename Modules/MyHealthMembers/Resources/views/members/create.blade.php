@extends('layouts.app')
@section('title', $member->exists ? __('Edit My Health Member') : __('Add My Health Member'))

@section('content')
<section class="content-header">
    <h1>{{ $member->exists ? __('Edit My Health Member') : __('Add My Health Member') }}</h1>
</section>

<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>{{ __('Please correct the following errors') }}</strong>
            <ul style="margin-bottom:0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $member->exists ? route('myhealth.members.update', $member->id) : route('myhealth.members.store') }}">
        @csrf
        @if($member->exists)
            @method('PUT')
        @endif

        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">{{ __('Personal Information') }}</h3></div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>{{ __('Full Name') }} *</label><input type="text" name="name" class="form-control" value="{{ old('name', $member->name) }}" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>{{ __('Date of Birth') }}</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', optional($member->date_of_birth)->format('Y-m-d')) }}"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>{{ __('Gender') }}</label><select name="gender" class="form-control"><option value="">{{ __('Please Select') }}</option>@foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $value=>$label)<option value="{{ $value }}" @selected(old('gender', $member->gender) === $value)>{{ __($label) }}</option>@endforeach</select></div></div>
                </div>
                <div class="row">
                    <div class="col-md-3"><div class="form-group"><label>{{ __('NIC No') }}</label><input type="text" name="nic_no" class="form-control" value="{{ old('nic_no', $member->nic_no) }}"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>{{ __('Passport No') }}</label><input type="text" name="passport_no" class="form-control" value="{{ old('passport_no', $member->passport_no) }}"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>{{ __('Blood Group') }}</label><select name="blood_group" class="form-control"><option value="">{{ __('Please Select') }}</option>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $group)<option value="{{ $group }}" @selected(old('blood_group', $member->blood_group) === $group)>{{ $group }}</option>@endforeach</select></div></div>
                    <div class="col-md-3"><div class="form-group"><label>{{ __('Status') }}</label><select name="status" class="form-control">@foreach(['active'=>'Active','inactive'=>'Inactive','deceased'=>'Deceased'] as $value=>$label)<option value="{{ $value }}" @selected(old('status', $member->status ?? 'active') === $value)>{{ __($label) }}</option>@endforeach</select></div></div>
                </div>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">{{ __('Contact Details') }}</h3></div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label>{{ __('Mobile') }}</label><input type="text" name="mobile" class="form-control" value="{{ old('mobile', $member->mobile) }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>{{ __('Email') }}</label><input type="email" name="email" class="form-control" value="{{ old('email', $member->email) }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>{{ __('Emergency Contact Mobile') }}</label><input type="text" name="emergency_contact_mobile" class="form-control" value="{{ old('emergency_contact_mobile', $member->emergency_contact_mobile) }}"></div></div>
                </div>
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label>{{ __('Emergency Contact Name') }}</label><input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $member->emergency_contact_name) }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>{{ __('Guardian Name') }}</label><input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $member->guardian_name) }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>{{ __('Guardian Mobile') }}</label><input type="text" name="guardian_mobile" class="form-control" value="{{ old('guardian_mobile', $member->guardian_mobile) }}"></div></div>
                </div>
                <div class="form-group"><label>{{ __('Address') }}</label><textarea name="address" class="form-control" rows="3">{{ old('address', $member->address) }}</textarea></div>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">{{ __('Medical Information') }}</h3></div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3"><div class="form-group"><label>{{ __('Height - Feet') }}</label><input type="number" name="height_feet" min="0" max="9" step="1" class="form-control" value="{{ old('height_feet', $member->height_feet) }}"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>{{ __('Height - Inches') }}</label><input type="number" name="height_inches" min="0" max="11" step="1" class="form-control" value="{{ old('height_inches', $member->height_inches) }}"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>{{ __('Weight (Kg)') }}</label><input type="number" name="weight_kg" min="0" step="0.01" class="form-control" value="{{ old('weight_kg', $member->weight_kg) }}"></div></div>
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('Save') }}</button>
                <a href="{{ route('myhealth.members.index') }}" class="btn btn-default">{{ __('Cancel') }}</a>
            </div>
        </div>
    </form>
</section>
@endsection
