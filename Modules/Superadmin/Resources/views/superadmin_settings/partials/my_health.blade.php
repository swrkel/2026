<div class="pos-tab-content">
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-heartbeat"></i> My Health Member Portal Branding</h3>
                        <p class="help-block" style="margin-bottom:0;">These values are used in the public/member-facing My Health Member Portal. Current default details are shown and can be edited by Super Admin.</p>
                    </div>
                    <div class="box-body">
                        @php
                            $mh = function ($key, $default = '') use ($settings) {
                                return old($key, isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default);
                            };
                        @endphp
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('myhealth_portal_name', 'Portal Name:') !!}
                                    {!! Form::text('myhealth_portal_name', $mh('myhealth_portal_name', 'My Health Member Portal'), ['class' => 'form-control', 'placeholder' => 'My Health Member Portal']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('myhealth_browser_title', 'Browser Title:') !!}
                                    {!! Form::text('myhealth_browser_title', $mh('myhealth_browser_title', 'My Health Member Portal'), ['class' => 'form-control', 'placeholder' => 'My Health Member Portal']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('myhealth_login_subtitle', 'Login Page Subtitle:') !!}
                                    {!! Form::text('myhealth_login_subtitle', $mh('myhealth_login_subtitle', 'Passcode-only secure portal access'), ['class' => 'form-control', 'placeholder' => 'Passcode-only secure portal access']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('myhealth_welcome_message', 'Welcome Message:') !!}
                                    {!! Form::textarea('myhealth_welcome_message', $mh('myhealth_welcome_message', 'Login to view only your own profile, medical history, prescriptions, laboratory, radiology, billing, appointments and documents.'), ['class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('myhealth_footer_text', 'Footer Text:') !!}
                                    {!! Form::textarea('myhealth_footer_text', $mh('myhealth_footer_text', 'My Health Member Portal • Secure access to your own records only • Powered by standalone MyHealthMembers'), ['class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('myhealth_copyright_text', 'Copyright Text:') !!}
                                    {!! Form::text('myhealth_copyright_text', $mh('myhealth_copyright_text', '© '.date('Y').' My Health Member Portal. All Rights Reserved.'), ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('myhealth_support_email', 'Support Email:') !!}
                                    {!! Form::email('myhealth_support_email', $mh('myhealth_support_email', ''), ['class' => 'form-control', 'placeholder' => 'support@example.com']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('myhealth_support_phone', 'Support Telephone:') !!}
                                    {!! Form::text('myhealth_support_phone', $mh('myhealth_support_phone', ''), ['class' => 'form-control', 'placeholder' => '+94 ...']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('myhealth_primary_color', 'Primary Theme Color:') !!}
                                    {!! Form::text('myhealth_primary_color', $mh('myhealth_primary_color', '#0d6efd'), ['class' => 'form-control', 'placeholder' => '#0d6efd']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('myhealth_secondary_color', 'Secondary Theme Color:') !!}
                                    {!! Form::text('myhealth_secondary_color', $mh('myhealth_secondary_color', '#00a6a6'), ['class' => 'form-control', 'placeholder' => '#00a6a6']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('myhealth_privacy_url', 'Privacy Policy URL:') !!}
                                    {!! Form::text('myhealth_privacy_url', $mh('myhealth_privacy_url', ''), ['class' => 'form-control', 'placeholder' => 'https://...']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('myhealth_terms_url', 'Terms & Conditions URL:') !!}
                                    {!! Form::text('myhealth_terms_url', $mh('myhealth_terms_url', ''), ['class' => 'form-control', 'placeholder' => 'https://...']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('myhealth_portal_logo', 'Portal Logo:') !!}
                                    {!! Form::file('myhealth_portal_logo', ['class' => 'form-control', 'accept' => 'image/*']) !!}
                                    @if(!empty($settings['myhealth_portal_logo']))
                                        <p class="help-block">Current: {{ $settings['myhealth_portal_logo'] }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('myhealth_login_banner', 'Login Page Banner:') !!}
                                    {!! Form::file('myhealth_login_banner', ['class' => 'form-control', 'accept' => 'image/*']) !!}
                                    @if(!empty($settings['myhealth_login_banner']))
                                        <p class="help-block">Current: {{ $settings['myhealth_login_banner'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info" style="margin-bottom:0;">
                            <i class="fa fa-info-circle"></i> Click the main <strong>Update</strong> button at the bottom of Super Admin Settings to save these My Health settings.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
