@extends('layouts.auth-login')
@inject('request', 'Illuminate\Http\Request')

@php
    $bg_showing_type = $settings->background_showing_type;
    $dashboard_title = $login_display === 'my_auto_agent'
        ? 'My Auto Agent Display'
        : __('lang_v1.pump_operator_dashboard');
    // Exact value configured in Super Admin → Footer for the Reports & Pages.
    $footer_text = trim((string) ($reports_pages_footer ?? ''));
@endphp

@section('css')
<link rel="stylesheet" href="{{ asset('css/pump-operator-login-modern.css?v=' . $asset_v . '-reports-pages-footer-20260726') }}">

{{--
    MA-002: the login keypad now matches the Other Sales keypad.

    APPEARANCE ONLY. The markup is untouched - every button keeps its
    data-key and data-action attributes, so the login script continues to
    work exactly as before. Only how the keys look changes.

    Ported from the Other Sales keypad in
    Modules/PumperDashboard/Resources/views/partials/other_sales.blade.php:
    the same panel, the same 12px radius, the same gradients for number,
    backspace and submit, and the same inset-plus-drop shadow.

    ONE DELIBERATE DIFFERENCE. Other Sales sizes its keys from viewport
    WIDTH alone. The login screen has no other content competing for
    height, and its existing sizing also allowed for screen HEIGHT, which
    is what keeps the keypad usable on a short tablet in landscape. That
    calculation is kept - only the appearance is replaced. Copying the
    width-only sizing would have made the keys overflow on those screens.

    Loaded after the stylesheet above so these rules win without needing
    to edit the shared CSS file, which other screens also use.
--}}
<style>
    /* The panel behind the keys, as on Other Sales. */
    .pump-keypad {
        gap: 6px !important;
        padding: 6px !important;
        background: #f8fafc !important;
        border-radius: 15px !important;
    }

    .pump-key {
        border: 1px solid transparent !important;
        border-radius: 12px !important;
        font-weight: 900 !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .28),
            inset 0 -3px 0 rgba(15, 23, 42, .10),
            0 7px 16px rgba(15, 23, 42, .17) !important;
        transition: transform .15s ease, box-shadow .15s ease, filter .15s ease !important;
    }

    /* Digits. */
    .pump-key-number {
        background: linear-gradient(135deg, #246fe5 0%, #31a7e9 100%) !important;
        border-color: #2d85e8 !important;
    }

    /* Backspace. */
    .pump-key-clear {
        background: linear-gradient(135deg, #ef4444 0%, #fb5b61 100%) !important;
        border-color: #ef4444 !important;
    }

    /* Submit. */
    .pump-key-submit {
        background: linear-gradient(135deg, #16a34a 0%, #22b455 100%) !important;
        border-color: #16a34a !important;
    }

    /* The same press feedback Other Sales gives. */
    .pump-key:hover,
    .pump-key:focus-visible {
        filter: brightness(1.04) !important;
        transform: translateY(-1px) !important;
    }

    .pump-key:active {
        transform: translateY(1px) !important;
        box-shadow:
            inset 0 2px 6px rgba(15, 23, 42, .22),
            0 3px 8px rgba(15, 23, 42, .14) !important;
    }
</style>
@endsection

@section('content')
<main class="pump-login-page" aria-labelledby="pump-login-title">
    <div class="pump-login-background" aria-hidden="true"></div>

    <div class="pump-login-content">
        <section class="pump-login-left">
            <header class="pump-login-welcome">
                <h1>@lang('lang_v1.welcome')</h1>
                <p class="pump-login-business">{{ !empty($business) ? $business->name : 'SYZYGY' }}</p>
            </header>

            <div class="pump-login-access-panel">
                <h2 id="pump-login-title">{{ $dashboard_title }}</h2>
                <p class="pump-login-status">@lang('lang_v1.locked')</p>

                {!! Form::open([
                    'url' => url('/pump-operator/login'),
                    'method' => 'post',
                    'id' => 'login_form',
                    'class' => 'pump-login-form',
                    'autocomplete' => 'off'
                ]) !!}
                    <input type="hidden" name="company_number" value="{{ $cc }}">
                    <input type="hidden" name="business_id" value="{{ !empty($business) ? $business->id : '' }}">
                    <input type="hidden" name="login_display" value="{{ $login_display }}">

                    <div class="pump-passcode-group">
                        <span class="pump-passcode-icon" aria-hidden="true">
                            <i class="fa fa-lock"></i>
                        </span>
                        {!! Form::password('passcode', [
                            'id' => 'passcode',
                            'class' => 'pump-passcode-input',
                            'placeholder' => 'passcode',
                            'inputmode' => 'numeric',
                            'pattern' => '[0-9]*',
                            'maxlength' => 20,
                            'aria-label' => 'Passcode',
                            'autofocus' => true
                        ]) !!}
                    </div>

                    <button type="submit" class="pump-enter-button" id="check_password_btn">
                        <span>@lang('pumperdashboard::lang.click_to_enter')</span>
                    </button>

                    <img src="{{ asset('img/loading.gif') }}" alt="" class="loading_gif pump-login-loader" hidden>
                {!! Form::close() !!}
            </div>
        </section>

        <section class="pump-login-right" aria-label="Numeric keypad">
            <div class="pump-login-datetime" data-timezone="{{ config('app.timezone', 'Asia/Colombo') }}">
                <div><strong>@lang('lang_v1.today'):</strong> <span id="pump-current-date">{{ now()->format('m-d-Y') }}</span></div>
                <div><strong>@lang('lang_v1.time'):</strong> <span id="pump-current-time">{{ now()->format('H:i') }}</span></div>
            </div>

            <div class="pump-keypad" id="key_pad">
                @foreach ([7, 8, 9, 4, 5, 6, 1, 2, 3] as $digit)
                    <button type="button" class="pump-key pump-key-number" data-key="{{ $digit }}" aria-label="{{ $digit }}">
                        {{ $digit }}
                    </button>
                @endforeach

                <button type="button" class="pump-key pump-key-clear" data-action="backspace" aria-label="Delete last digit">
                    <span aria-hidden="true">⌫</span>
                </button>
                <button type="button" class="pump-key pump-key-number" data-key="0" aria-label="0">0</button>
                <button type="button" class="pump-key pump-key-submit" data-action="submit" aria-label="Submit passcode">
                    <span aria-hidden="true">↵</span>
                </button>
            </div>
        </section>
    </div>

    @if ($footer_text !== '')
        <footer class="pump-login-footer" data-footer-source="admin_reports_footer">
            {!! nl2br(e($footer_text)) !!}
        </footer>
    @endif
</main>
@stop

@section('javascript')
<script>
    window.PumpOperatorLoginConfig = {
        timezone: @json(config('app.timezone', 'Asia/Colombo')),
        loadingText: @json(__('lang_v1.please_wait'))
    };
</script>
<script src="{{ asset('js/pump-operator-login-modern.js?v=' . $asset_v . '-duplicate-fix-20260726') }}"></script>
@endsection
