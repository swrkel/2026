@extends('layouts.app')
@section('title', 'Manage My Health Permission')

@section('content')
<section class="content-header">
    <h1>Manage My Health Permission - {{ $business->name }}</h1>
</section>

<section class="content">
    <form method="POST" action="{{ route('myhealth.superadmin.permissions.update', $business->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="box box-primary">
            <div class="box-body">
                @foreach([
                    'can_register_member' => 'Can Register Member',
                    'can_view_profile' => 'Can View Profile',
                    'can_edit_profile' => 'Can Edit Profile',
                    'can_view_medical_history' => 'Can View Medical History',
                    'can_create_diagnosis' => 'Can Create Diagnosis',
                    'can_create_prescription' => 'Can Create Prescription',
                    'can_access_pharmacy' => 'Can Access Pharmacy',
                    'can_dispense_medicine' => 'Can Dispense Medicine',
                    'can_manage_pharmacy_stock' => 'Can Manage Pharmacy Stock',
                    'can_access_insurance' => 'Can Access Insurance',
                    'can_manage_claims' => 'Can Manage Claims',
                    'can_access_telemedicine' => 'Can Access Telemedicine',
                    'can_manage_telemedicine' => 'Can Manage Telemedicine',
                    'can_access_billing' => 'Can Access Billing & Claims',
                    'can_manage_billing' => 'Can Manage Billing & Claims',
                    'can_upload_documents' => 'Can Upload Documents',
                    'can_view_documents' => 'Can View Documents',
                    'can_export_print' => 'Can Export / Print',
                ] as $field => $label)
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="{{ $field }}" value="1" {{ !empty($permission->{$field}) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    </div>
                @endforeach

                <div class="form-group">
                    <label>Access Expiry Date</label>
                    <input type="date" name="access_expiry_date" class="form-control" value="{{ $permission->access_expiry_date }}">
                </div>
                <hr>
                <h4><i class="fa fa-heartbeat"></i> My Health Member Portal Branding</h4>
                <p class="text-muted">Current default details are shown below. Super Admin can edit them per business whenever needed.</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Portal Name</label>
                            <input type="text" name="portal_branding[portal_name]" class="form-control" value="{{ old('portal_branding.portal_name', $branding['portal_name'] ?? 'My Health Member Portal') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Browser Title</label>
                            <input type="text" name="portal_branding[browser_title]" class="form-control" value="{{ old('portal_branding.browser_title', $branding['browser_title'] ?? 'My Health Member Portal') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Portal Logo</label>
                            <input type="file" name="portal_logo" class="form-control" accept="image/*">
                            @if(!empty($branding['logo']))<p class="help-block">Current: <a href="{{ asset($branding['logo']) }}" target="_blank">View logo</a></p>@endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Login Page Banner</label>
                            <input type="file" name="login_banner" class="form-control" accept="image/*">
                            @if(!empty($branding['login_banner']))<p class="help-block">Current: <a href="{{ asset($branding['login_banner']) }}" target="_blank">View banner</a></p>@endif
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Welcome Message</label>
                    <textarea name="portal_branding[welcome_message]" class="form-control" rows="3">{{ old('portal_branding.welcome_message', $branding['welcome_message'] ?? 'Your secure health dashboard shows only your own records.') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Footer Text</label>
                    <textarea name="portal_branding[footer_text]" class="form-control" rows="2">{{ old('portal_branding.footer_text', $branding['footer_text'] ?? 'My Health Member Portal • Secure access to your own records only • Powered by standalone MyHealthMembers') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Copyright Text</label>
                    <input type="text" name="portal_branding[copyright_text]" class="form-control" value="{{ old('portal_branding.copyright_text', $branding['copyright_text'] ?? ('© '.date('Y').' My Health Member Portal. All rights reserved.')) }}">
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Primary Theme Color</label>
                            <input type="color" name="portal_branding[primary_theme_color]" class="form-control" value="{{ old('portal_branding.primary_theme_color', $branding['primary_theme_color'] ?? '#0d6efd') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Secondary Theme Color</label>
                            <input type="color" name="portal_branding[secondary_theme_color]" class="form-control" value="{{ old('portal_branding.secondary_theme_color', $branding['secondary_theme_color'] ?? '#00a6a6') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Support Email</label>
                            <input type="email" name="portal_branding[support_email]" class="form-control" value="{{ old('portal_branding.support_email', $branding['support_email'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Support Telephone</label>
                            <input type="text" name="portal_branding[support_telephone]" class="form-control" value="{{ old('portal_branding.support_telephone', $branding['support_telephone'] ?? '') }}">
                        </div>
                    </div>
                </div>

            </div>
            <div class="box-footer">
                <button class="btn btn-primary">Save My Health Settings</button>
                <a href="{{ route('myhealth.superadmin.permissions.index') }}" class="btn btn-default">Back</a>
            </div>
        </div>
    </form>
</section>
@endsection
