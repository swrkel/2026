@extends('layouts.app')

@section('content')
<style>
    .settings-wrap {
        max-width: 700px;
        width: 100%;
        margin: auto;
        background: #fff;
        padding: 16px;
    }

    .auto-number-lg {
        font-size: 200%;
    }

    .required-star {
        color: red;
        margin-left: 2px;
    }

    @media (max-width: 480px) {
        .settings-wrap {
            padding: 10px;
        }

        .auto-number-lg {
            font-size: 140%;
        }

        .nav-tabs {
            display: flex;
            overflow-x: auto;
            flex-wrap: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .nav-tabs > li {
            flex-shrink: 0;
        }
    }
</style>

    @php
        $user = auth()->user();

        $canEdit = $user->hasRole('Super Admin')
            || $user->hasRole('Admin#' . $business_id);

        $locked = isset($setting) && $setting->is_locked && ! $canEdit;
    @endphp

    <div class="settings-wrap mt-3">
        <h4>My Auto Settings</h4>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status')['msg'] ?? 'Saved.' }}</div>
        @endif

        <form method="POST" action="{{ route('myauto.settings.store') }}" id="settings-form">
            @csrf

            <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px;">
                <li role="presentation" class="active">
                    <a href="#general-settings" aria-controls="general-settings" role="tab" data-toggle="tab">General Settings</a>
                </li>
                <li role="presentation">
                    <a href="#auto-sms" aria-controls="auto-sms" role="tab" data-toggle="tab">Auto SMS</a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="general-settings">
                    <div class="form-group">
                        <label>User Name</label>
                        <input name="user_name" class="form-control" value="{{ $user->user_full_name }}" readonly>
                    </div>

                    <div class="form-group">
                        <label>First Date <span class="required-star">*</span></label>
                        <input type="date" id="input-first-date" name="first_date" class="form-control"
                            value="{{ $setting->first_date ?? now()->toDateString() }}" {{ $locked ? 'readonly' : '' }}
                            {{ !$locked ? 'required' : '' }}>
                        @if (!$locked)
                            <small class="text-muted">Required to save settings.</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Starting Meter <span class="required-star">*</span></label>
                        <input type="number" id="input-starting-meter" name="starting_meter" class="form-control"
                            value="{{ $setting->starting_meter ?? '' }}" {{ $locked ? 'readonly' : '' }}
                            {{ !$locked ? 'required' : '' }}>
                        @if (!$locked)
                            <small class="text-muted">Required to save settings.</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>My Auto Number <span class="required-star">*</span></label>
                        <input id="input-auto-number" name="auto_number" class="form-control auto-number-lg"
                            value="{{ $setting->auto_number ?? '' }}" {{ $locked ? 'readonly' : '' }}
                            {{ !$locked ? 'required' : '' }}>
                        @if (!$locked)
                            <small class="text-muted">Required to save settings.</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Passcode (to unlock dashboard) <span class="required-star">*</span></label>
                        <input type="password" id="input-passcode" name="passcode" class="form-control" maxlength="8"
                            placeholder="Enter passcode (4-8 chars)" value="{{ $setting->passcode ?? '' }}" {{ $locked ? 'readonly' : '' }}
                            {{ !$locked ? 'required' : '' }}>
                        @if (!$locked)
                            <small class="text-muted">Required (4-8 characters). Changing this will send an SMS if Auto SMS is enabled.</small>
                        @endif
                    </div>

                    <div class="form-group" {{ $locked ? 'style=display:none' : '' }}>
                        <label>Confirm Passcode <span class="required-star">*</span></label>
                        <input type="password" id="input-passcode-confirmation" name="passcode_confirmation" class="form-control" maxlength="8"
                            placeholder="Re-enter passcode" value="{{ $setting->passcode ?? '' }}" {{ $locked ? 'readonly' : '' }}
                            {{ !$locked ? 'required' : '' }}>
                        <div id="passcode-mismatch-error" class="text-danger" style="display:none;">Passcodes do not match.</div>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="auto-sms">
                    <div class="alert alert-info" style="font-size:13px;">
                        An SMS will be sent to these numbers when the passcode is changed — either via "Change Passcode" on the home page or via General Settings.
                    </div>

                    <div class="form-group">
                        <label>Mobile Numbers</label>
                        <textarea name="sms_mobile_numbers" class="form-control" rows="3"
                            placeholder="Enter multiple mobile numbers, separated by comma">{{ $setting->sms_mobile_numbers ?? '' }}</textarea>
                        <small class="text-muted">Enter multiple mobile numbers, separated by comma.</small>
                    </div>

                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="is_sms_enabled" value="1"
                                    {{ (isset($setting) && $setting->is_sms_enabled) ? 'checked' : '' }}>
                                <strong>Send SMS</strong>
                            </label>
                        </div>
                        <small class="text-muted">SMS notifications will only be sent when this is enabled.</small>
                    </div>
                </div>
            </div>

            @if (!$locked)
                <div style="margin-top: 20px;">
                    <button id="save-settings-btn" class="btn btn-primary" type="submit">Save Settings</button>
                </div>
            @endif

        </form>
    </div>
@endsection

@section('javascript')
    <script>
        @if (!$locked)
        (function() {
            const firstDateInput = document.getElementById('input-first-date');
            const meterInput     = document.getElementById('input-starting-meter');
            const autoNumInput   = document.getElementById('input-auto-number');
            const passcodeInput  = document.getElementById('input-passcode');
            const saveBtn        = document.getElementById('save-settings-btn');

            function validateForm() {
                const allFilled =
                    (firstDateInput && firstDateInput.value.trim() !== '') &&
                    (meterInput     && meterInput.value.trim()     !== '') &&
                    (autoNumInput   && autoNumInput.value.trim()   !== '') &&
                    (passcodeInput  && passcodeInput.value.trim()  !== '');

                if (saveBtn) {
                    saveBtn.disabled = !allFilled;
                }
            }

            if (firstDateInput) { firstDateInput.addEventListener('input', validateForm); }
            if (meterInput)     { meterInput.addEventListener('input', validateForm); }
            if (autoNumInput)   { autoNumInput.addEventListener('input', validateForm); }
            if (passcodeInput) {
                passcodeInput.addEventListener('input', validateForm);
            }

            const confirmInput = document.getElementById('input-passcode-confirmation');
            const mismatchError = document.getElementById('passcode-mismatch-error');

            if (passcodeInput && confirmInput) {
                const checkMismatch = function() {
                    if (confirmInput.value && passcodeInput.value !== confirmInput.value) {
                        mismatchError.style.display = 'block';
                    } else {
                        mismatchError.style.display = 'none';
                    }
                };
                passcodeInput.addEventListener('input', checkMismatch);
                confirmInput.addEventListener('input', checkMismatch);
            }

            validateForm();
        })();
        @endif
    </script>
@endsection
