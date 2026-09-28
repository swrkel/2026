@extends('layouts.' . $layout)
@section('title', __('home.home'))

@section('css')
    @parent
    <link rel="stylesheet" href="{{ asset('css/pumper-dashboard-remaining-modern.css') }}?v=20260726-is1779-1">
@endsection

<style>
    /* Final dashboard design supplied by the user, scoped to this page only. */
    .pumper-dashboard-page {
        --pumper-orange: #f57c00;
        --pumper-gray: #757575;
        --pumper-light-gray: #9e9e9e;
        --pumper-green: #388e3c;
        --pumper-yellow: #fbc02d;
        --pumper-dark-gray: #424242;
        --pumper-purple: #7b1fa2;
        --pumper-blue: #1976d2;
        --pumper-red: #d32f2f;
        --pumper-deep-blue: #1565c0;
        --pumper-deep-orange: #ef6c00;
        color: #333;
        font-family: Inter, Roboto, "Helvetica Neue", Arial, sans-serif;
        height: var(--pumper-viewport-height, 100dvh);
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .pumper-dashboard-page * {
        box-sizing: border-box;
    }

    .pumper-dashboard-header {
        position: sticky;
        top: 0;
        z-index: 1035;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        width: 100%;
        flex: 0 0 auto;
        padding: clamp(11px, 1.8vh, 20px) clamp(22px, 3vw, 40px);
        background: #fff;
        border-bottom: 1px solid #ddd;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .08);
    }

    .pumper-dashboard-heading {
        flex: 1 1 auto;
        min-width: 220px;
    }

    .pumper-dashboard-heading h1 {
        margin: 0;
        color: #333;
        font-size: 22px;
        font-weight: 600;
        line-height: 1.3;
    }

    .pumper-dashboard-heading h2 {
        margin: 4px 0 0;
        color: #d32f2f;
        font-size: clamp(13px, 1.45vw, 16px);
        font-weight: 700;
        line-height: 1.3;
    }

    .pumper-dashboard-welcome {
        margin: 8px 0 0;
        color: #455a64;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.35;
    }

    .pumper-dashboard-welcome i {
        margin-right: 7px;
        color: #f57c00;
    }

    .pumper-dashboard-header-right {
        flex: 0 1 auto;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }

    .pumper-dashboard-clock {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 18px;
        color: #455a64;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }

    .pumper-dashboard-clock-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .pumper-dashboard-clock-item i {
        color: #1976d2;
    }

    .pumper-dashboard-top-buttons {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .pumper-dashboard-top-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        padding: 10px 16px;
        border: 0;
        border-radius: 6px;
        color: #fff !important;
        font-size: 14.5px;
        font-weight: 600;
        line-height: 1.2;
        text-decoration: none !important;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(15, 23, 42, .12);
        transition: filter .2s ease, transform .1s ease, box-shadow .2s ease;
    }

    .pumper-dashboard-top-action:hover,
    .pumper-dashboard-top-action:focus {
        color: #fff !important;
        filter: brightness(1.12);
        box-shadow: 0 4px 9px rgba(15, 23, 42, .18);
        outline: none;
    }

    .pumper-dashboard-top-action:active {
        transform: scale(.96);
        filter: brightness(.92);
    }

    .pumper-action-update { background: #1976d2; }
    .pumper-action-settings { background: #388e3c; }
    .pumper-action-logout { background: #f57c00; }
    .pumper-action-back,
    .pumper-action-main { background: #795548; }
    .pumper-action-fullscreen { background: #7b1fa2; }

    .pumper-dashboard-message {
        flex: 0 0 auto;
        max-width: 1440px;
        margin: clamp(7px, 1.2vh, 14px) auto 0;
        padding: 0 clamp(22px, 3vw, 40px);
        text-align: center;
    }

    .pumper-dashboard-grid {
        flex: 1 1 auto;
        min-height: 0;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        grid-auto-rows: minmax(0, 1fr);
        align-content: stretch;
        gap: clamp(9px, 1.55vh, 18px);
        width: 100%;
        max-width: 1440px;
        margin: 0 auto;
        padding: clamp(10px, 1.8vh, 22px) clamp(22px, 3vw, 40px);
    }

    .pumper-dashboard-card {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 0;
        padding: clamp(10px, 1.65vh, 20px);
        overflow: hidden;
        border: 0;
        border-radius: 10px;
        color: #fff !important;
        font-size: calc(clamp(13px, min(1.35vw, 2.6vh), 16px) + 0.5px);
        font-weight: 600;
        line-height: 1.3;
        text-align: center;
        text-decoration: none !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
        cursor: pointer;
        transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
    }

    .pumper-dashboard-card:hover,
    .pumper-dashboard-card:focus {
        color: #fff !important;
        text-decoration: none !important;
        transform: translateY(-3px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, .18);
        filter: brightness(1.12);
        outline: none;
    }

    .pumper-dashboard-card:active {
        transform: scale(.97);
        filter: brightness(.92);
    }

    .pumper-dashboard-card.is-disabled {
        cursor: not-allowed;
        opacity: .52;
        filter: grayscale(1);
    }

    .pumper-dashboard-card.is-disabled:hover,
    .pumper-dashboard-card.is-disabled:focus {
        transform: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
        filter: grayscale(1);
    }

    .pumper-dashboard-card-icon {
        display: block;
        margin: 0 0 clamp(5px, .9vh, 10px);
        font-size: clamp(18px, min(2vw, 3.4vh), 24px);
        line-height: 1;
    }

    .pumper-dashboard-card-label {
        display: block;
        width: 100%;
    }

    .pumper-dashboard-card-help {
        display: block;
        margin-top: 7px;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.25;
        opacity: .94;
    }

    .pumper-card-orange { background: var(--pumper-orange); }
    .pumper-card-gray { background: var(--pumper-gray); }
    .pumper-card-light-gray { background: var(--pumper-light-gray); }
    .pumper-card-green { background: var(--pumper-green); }
    .pumper-card-yellow { background: var(--pumper-yellow); color: #333 !important; }
    .pumper-card-yellow:hover,
    .pumper-card-yellow:focus { color: #333 !important; }
    .pumper-card-dark-gray { background: var(--pumper-dark-gray); }
    .pumper-card-purple { background: var(--pumper-purple); }
    .pumper-card-blue { background: var(--pumper-blue); }
    .pumper-card-red { background: var(--pumper-red); }
    .pumper-card-deep-blue { background: var(--pumper-deep-blue); }
    .pumper-card-deep-orange { background: var(--pumper-deep-orange); }

    .pumper-dashboard-footer {
        flex: 0 0 auto;
        width: 100%;
        padding: clamp(4px, .65vh, 8px) 20px clamp(5px, .85vh, 10px);
        color: #506f89;
        font-size: 15.12px;
        font-weight: 400;
        line-height: 1.22;
        text-align: center;
        white-space: normal;
    }

    @media (min-width: 992px) and (max-height: 760px) {
        .pumper-dashboard-header {
            gap: 14px;
            padding-top: 9px;
            padding-bottom: 9px;
        }

        .pumper-dashboard-heading h1 {
            font-size: 19px;
        }

        .pumper-dashboard-heading h2 {
            margin-top: 2px;
            font-size: 14px;
        }

        .pumper-dashboard-welcome {
            margin-top: 4px;
            font-size: 13px;
        }

        .pumper-dashboard-header-right {
            gap: 6px;
        }

        .pumper-dashboard-clock {
            gap: 12px;
            font-size: 12px;
        }

        .pumper-dashboard-top-buttons {
            gap: 7px;
        }

        .pumper-dashboard-top-action {
            min-height: 34px;
            padding: 7px 11px;
            font-size: 12.5px;
        }

        .pumper-dashboard-card {
            border-radius: 8px;
        }

        .pumper-dashboard-card-help {
            margin-top: 4px;
            font-size: 10px;
        }

        .pumper-dashboard-footer {
            font-size: 13.65px;
            line-height: 1.12;
            padding-top: 3px;
            padding-bottom: 4px;
        }
    }

    @media (max-width: 991px) {
        .pumper-dashboard-page {
            height: auto;
            min-height: 100vh;
            min-height: 100dvh;
            overflow: visible;
        }

        .pumper-dashboard-header {
            position: relative;
            align-items: flex-start;
            flex-direction: column;
            padding: 18px 24px;
        }

        .pumper-dashboard-header-right {
            width: 100%;
            align-items: flex-start;
        }

        .pumper-dashboard-clock,
        .pumper-dashboard-top-buttons {
            justify-content: flex-start;
        }

        .pumper-dashboard-grid {
            flex: 0 0 auto;
            min-height: auto;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            grid-auto-rows: auto;
            padding: 28px 24px;
        }

        .pumper-dashboard-card {
            min-height: 112px;
        }
    }

    @media (max-width: 600px) {
        .pumper-dashboard-header {
            padding: 16px;
        }

        .pumper-dashboard-clock {
            align-items: flex-start;
            flex-direction: column;
            gap: 5px;
            white-space: normal;
        }

        .pumper-dashboard-top-action {
            flex: 1 1 calc(50% - 10px);
        }

        .pumper-dashboard-grid {
            grid-template-columns: 1fr;
            gap: 14px;
            padding: 22px 16px;
        }

        .pumper-dashboard-card {
            min-height: 112px;
        }

        .pumper-dashboard-message {
            padding: 0 16px;
        }

        .pumper-dashboard-footer {
            font-size: 15.12px;
            line-height: 1.2;
            padding: 8px 16px 12px;
        }
    }
</style>

@section('content')
    @php
        $has_legacy_pumper_dashboard_access = auth()->user()->can('pump_operator.dashboard');
        $canPumperDashboard = function ($permission = null) use ($has_legacy_pumper_dashboard_access, $can_access_dashboard) {
            if ($has_legacy_pumper_dashboard_access) {
                return true;
            }

            if (!empty($permission) && auth()->user()->can($permission)) {
                return true;
            }

            if (!empty($can_access_dashboard)) {
                return true;
            }

            return !empty(auth()->user()->is_pump_operator) && !empty(auth()->user()->pump_operator_id);
        };
    @endphp

    @if ($canPumperDashboard('pumper_dashboard.dashboard'))
        <section class="content no-print pumper-dashboard-page" aria-label="Pump Operator Dashboard">
            <header class="pumper-dashboard-header">
                <div class="pumper-dashboard-heading">
                    <h1>@lang('pumperdashboard::lang.pump_operator_dashboard')</h1>
                    <h2>Shift NO: {{ $shift_number }}</h2>
                    <p class="pumper-dashboard-welcome">
                        <i class="fa fa-smile-o" aria-hidden="true"></i>{{ $time_greeting }}, {{ \Auth::user()->first_name }}!
                    </p>
                </div>

                <div class="pumper-dashboard-header-right">
                    <div class="pumper-dashboard-clock" aria-label="Current dashboard date and time">
                        <span class="pumper-dashboard-clock-item">
                            <i class="fa fa-calendar" aria-hidden="true"></i>
                            <strong>@lang('pumperdashboard::lang.today'):</strong>
                            <span>{{ @format_date($dashboard_now->format('Y-m-d')) }}</span>
                        </span>
                        <span class="pumper-dashboard-clock-item">
                            <i class="fa fa-clock-o" aria-hidden="true"></i>
                            <strong>@lang('pumperdashboard::lang.time'):</strong>
                            <span>{{ $dashboard_now->format('H:i:s') }}</span>
                        </span>
                    </div>

                    <nav class="pumper-dashboard-top-buttons" aria-label="Dashboard actions">
                        <a href="#" class="pumper-dashboard-top-action pumper-action-fullscreen toggle-fullscreen"
                            title="Fullscreen">
                            <i class="fa fa-expand" aria-hidden="true"></i>
                            <span class="sr-only">Fullscreen</span>
                        </a>

                        @if (!empty(session()->get('from_admin')))
                            <a href="{{ action('Auth\PumpOperatorLoginController@logout', ['main_system' => true]) }}"
                                class="pumper-dashboard-top-action pumper-action-back">
                                <i class="fa fa-arrow-left" aria-hidden="true"></i>
                                <span>@lang('pumperdashboard::lang.back')</span>
                            </a>
                        @endif

                        @can('pump_operator.main_system')
                            @if (empty(session()->get('pump_operator_main_system')))
                                <a href="{{ action('Auth\PumpOperatorLoginController@logout', ['main_system' => true]) }}"
                                    class="pumper-dashboard-top-action pumper-action-main">
                                    <i class="fa fa-desktop" aria-hidden="true"></i>
                                    <span>@lang('pumperdashboard::lang.main_system')</span>
                                </a>
                            @endif
                        @endcan

                        @can('pumper_dashboard_settings')
                            @if (!empty($pump_operator_id))
                                <a href="#" data-container=".pump_operator_modal"
                                    data-href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard_settings') }}"
                                    class="pumper-dashboard-top-action pumper-action-settings btn-modal">
                                    <i class="fa fa-cog" aria-hidden="true"></i>
                                    <span>@lang('pumperdashboard::lang.pumper_dashboard_settings')</span>
                                </a>
                            @endif
                        @endcan

                        <a href="#" data-container=".pump_operator_modal"
                            data-href="{{ action('\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorController@update_passcode') }}"
                            class="pumper-dashboard-top-action pumper-action-update btn-modal">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            <span>@lang('pumperdashboard::lang.update_passcode')</span>
                        </a>

                        <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}"
                            class="pumper-dashboard-top-action pumper-action-logout">
                            <i class="fa fa-sign-out" aria-hidden="true"></i>
                            <span>@lang('pumperdashboard::lang.logout')</span>
                        </a>
                    </nav>
                </div>
            </header>

            @if (!empty($general_message))
                <div class="pumper-dashboard-message">{!! $general_message !!}</div>
            @endif

            <div class="pumper-dashboard-grid">
                <a id="receive_pump_btn"
                    class="pumper-dashboard-card pumper-card-orange"
                    href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorActionsController@getReceivePump') }}">
                    <i class="fa fa-tint pumper-dashboard-card-icon" aria-hidden="true"></i>
                    <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.receive_pump')</span>
                </a>

                @php
                    /*
                     * IS2321: Payment is locked until all physical pumps in the
                     * current shift have completed the Receive Pump confirmation.
                     * It then remains available until the shift itself is closed.
                     */
                    $is_payment_enabled = $payment_receive_complete ?? false;

                    /*
                     * MA-002 (S-609 #5): Other Sales keeps its existing received-
                     * pump appearance. Payment gets the open colour only when its
                     * stricter receive-complete condition above is satisfied.
                     */
                    $ma002_pump_received = $has_received_pump ?? false;
                    $payment_url = url('/pumper-dashboard/pump-operator-payments/open-page') . '?only_pumper=1&from_dashboard=1';
                @endphp
                <a id="payments_btn"
                    class="pumper-dashboard-card pumper-card-gray{{ $is_payment_enabled ? ' ma002-tab-open' : '' }}{{ $is_payment_enabled ? '' : ' is-disabled' }}"
                    href="{{ $is_payment_enabled ? $payment_url : '#' }}"
                    data-payment-url="{{ $payment_url }}"
                    data-pump-receive-complete="{{ $is_payment_enabled ? 1 : 0 }}"
                    data-shift-closed="{{ $is_shift_closed ? 1 : 0 }}"
                    onclick="return openPumperPaymentPage(event, this);">
                    <i class="fa fa-money pumper-dashboard-card-icon" aria-hidden="true"></i>
                    <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.payments')</span>
                </a>

                @php
                    $is_othersales_enabled = $unconfirmed_meters == 0 && !$is_shift_closed;
                @endphp
                <a id="othersales_btn"
                    class="pumper-dashboard-card pumper-card-light-gray{{ $ma002_pump_received ? ' ma002-tab-open' : '' }}{{ $is_othersales_enabled ? '' : ' is-disabled' }}"
                    data-other-sale-disabled="{{ $is_othersales_enabled ? '1' : '0' }}"
                    href="{{ $is_othersales_enabled ? action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@othersalespage') : '#' }}"
                    @if ($unconfirmed_meters > 0) onclick="unconfirmed_meters_alert('Other Sales');" @elseif($is_shift_closed) onclick="shift_closed_alert('Other Sales');" @endif>
                    <i class="fa fa-shopping-cart pumper-dashboard-card-icon" aria-hidden="true"></i>
                    <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.other_sales')</span>
                </a>

                <a id="list_othersales_btn"
                    class="pumper-dashboard-card pumper-card-purple{{ $is_shift_closed ? ' is-disabled' : '' }}"
                    href="{{ $is_shift_closed ? '#' : action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}"
                    @if ($is_shift_closed) onclick="shift_closed_alert('List Other Sales');" @endif>
                    <i class="fa fa-list-alt pumper-dashboard-card-icon" aria-hidden="true"></i>
                    <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.list_other_sales')</span>
                </a>

                @if ($canPumperDashboard('pumper_dashboard.close_pump'))
                    <a id="closing_meter"
                        class="pumper-dashboard-card pumper-card-green btn-modal"
                        data-container=".pump_operator_modal"
                        data-href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorActionsController@getClosingMeterModal') }}">
                        <i class="fa fa-lock pumper-dashboard-card-icon" aria-hidden="true"></i>
                        <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.close_pump')</span>
                        <small class="pumper-dashboard-card-help">Tap here to select pump</small>
                    </a>
                @endif

                @if ($canPumperDashboard('pumper_dashboard.close_shift') && $can_close_shift)
                    <a class="pumper-dashboard-card pumper-card-yellow"
                        href="{{ action('\Modules\PumperDashboard\Http\Controllers\ClosingShiftController@index', ['only_pumper' => true]) }}">
                        <i class="fa fa-power-off pumper-dashboard-card-icon" aria-hidden="true"></i>
                        <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.close_shift')</span>
                    </a>
                @else
                    <a class="pumper-dashboard-card pumper-card-yellow is-disabled" href="#" aria-disabled="true">
                        <i class="fa fa-power-off pumper-dashboard-card-icon" aria-hidden="true"></i>
                        <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.close_shift')</span>
                    </a>
                @endif

                @if ($canPumperDashboard('pumper_dashboard.payment_summary'))
                    <a class="pumper-dashboard-card pumper-card-dark-gray"
                        href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@index', ['only_pumper' => true]) }}">
                        <i class="fa fa-file-text-o pumper-dashboard-card-icon" aria-hidden="true"></i>
                        <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.payment_summary')</span>
                    </a>
                @endif

                <a class="pumper-dashboard-card pumper-card-deep-orange"
                    href="{{ action('\Modules\PumperDashboard\Http\Controllers\UnloadStockController@getDetails', ['only_pumper' => true]) }}">
                    <i class="fa fa-list pumper-dashboard-card-icon" aria-hidden="true"></i>
                    <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.unload_stock_details')</span>
                </a>

                <a id="meters_with_payments_btn"
                    class="pumper-dashboard-card pumper-card-purple"
                    href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@metersWithPayments', ['only_pumper' => true]) }}">
                    <i class="fa fa-table pumper-dashboard-card-icon" aria-hidden="true"></i>
                    <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.meters_with_payments')</span>
                </a>

                @if ($canPumperDashboard('pumper_dashboard.day_entries'))
                    <a class="pumper-dashboard-card pumper-card-blue{{ $unconfirmed_meters == 0 ? '' : ' is-disabled' }}"
                        href="{{ $unconfirmed_meters == 0 ? action('\Modules\PumperDashboard\Http\Controllers\PumperDayEntryController@index', ['only_pumper' => true]) : '#' }}"
                        onclick="unconfirmed_meters_alert('Day Entries');">
                        <i class="fa fa-calendar pumper-dashboard-card-icon" aria-hidden="true"></i>
                        <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.day_entries')</span>
                    </a>
                @endif

                <a class="pumper-dashboard-card pumper-card-red btn-modal"
                    data-container=".pump_operator_modal"
                    data-href="{{ action('\Modules\PumperDashboard\Http\Controllers\CurrentMeterController@getModal', ['only_pumper' => true]) }}">
                    <i class="fa fa-tachometer pumper-dashboard-card-icon" aria-hidden="true"></i>
                    <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.enter_current_meter')</span>
                </a>

                <a class="pumper-dashboard-card pumper-card-deep-blue btn-modal"
                    data-container=".pump_operator_modal"
                    data-href="{{ action('\Modules\PumperDashboard\Http\Controllers\UnloadStockController@create', ['only_pumper' => true]) }}">
                    <i class="fa fa-truck pumper-dashboard-card-icon" aria-hidden="true"></i>
                    <span class="pumper-dashboard-card-label">@lang('pumperdashboard::lang.unload_stock')</span>
                </a>
            </div>

            @if (trim((string) ($reports_pages_footer ?? '')) !== '')
                <footer class="pumper-dashboard-footer" data-footer-source="admin_reports_footer">
                    {!! nl2br(e($reports_pages_footer)) !!}
                </footer>
            @endif
        </section>

        <div class="modal fade pump_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

        @if (session()->has('status'))
            @php
                $status = session('status');
                $message = is_array($status) ? $status['msg'] ?? '' : $status;
                $isSuccess = is_array($status) ? $status['success'] ?? 0 : 0;
            @endphp
            <script>
                $(document).ready(function() {
                    @if ($isSuccess)
                        toastr.success("{{ $message }}");
                    @else
                        toastr.error("{{ $message }}");
                    @endif
                });
            </script>
        @endif
    @endif
@endsection

@section('javascript')
    <script>
        /*
         * Close Pump modal stability fix (18 Sep 2026)
         *
         * This page previously had two separate auto-open paths for
         * ?tab=closing_meter: one loaded/shows the modal directly and another
         * triggered the Close Pump button on document-ready.  After a pump is
         * closed the controller redirects back with that tab parameter, so the
         * same modal could be opened twice.  Bootstrap then leaves stacked
         * backdrops/modal-open state, which looks like the dashboard has become
         * blurred while the actual Close Pump panel is hidden behind it.
         *
         * Keep one opening path only (the existing btn-modal click below) and
         * move the shared Pumper Dashboard modal container to <body> before it
         * is shown.  That prevents AdminLTE/content wrappers from creating a
         * stacking context above the modal.  No Close Pump business logic,
         * routes, calculations or save behaviour are changed here.
         */
        (function($) {
            'use strict';

            function pumperDashboardClearStaleModalLayer() {
                if ($('.modal.in:visible, .modal.show:visible').length === 0) {
                    $('.modal-backdrop.pumper-dashboard-stale-backdrop').remove();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }
            }

            $(document)
                .off('show.bs.modal.pumperDashboardLayer', '.pump_operator_modal')
                .on('show.bs.modal.pumperDashboardLayer', '.pump_operator_modal', function() {
                    var $modal = $(this);

                    // Bootstrap modals are safest as direct children of body.
                    // This is presentation-only; the same modal node and loaded
                    // forms/handlers are preserved.
                    if (!$modal.parent().is('body')) {
                        $modal.appendTo(document.body);
                    }

                    $modal.css('z-index', 1060);
                })
                .off('shown.bs.modal.pumperDashboardLayer', '.pump_operator_modal')
                .on('shown.bs.modal.pumperDashboardLayer', '.pump_operator_modal', function() {
                    $(this).css('z-index', 1060);
                    $('.modal-backdrop').last()
                        .addClass('pumper-dashboard-stale-backdrop')
                        .css('z-index', 1050);
                })
                .off('hidden.bs.modal.pumperDashboardLayer', '.pump_operator_modal')
                .on('hidden.bs.modal.pumperDashboardLayer', '.pump_operator_modal', function() {
                    var $modal = $(this);
                    $modal.removeAttr('style');

                    // Remove only orphaned layer state. Never disturb another
                    // modal if one is genuinely still open.
                    window.setTimeout(pumperDashboardClearStaleModalLayer, 0);
                });

            $(document)
                .off('click.pumperDashboardClosePump', '#closing_meter')
                .on('click.pumperDashboardClosePump', '#closing_meter', function() {
                    // A stale backdrop from a previous interrupted modal should
                    // never be allowed to cover the Close Pump selector.
                    pumperDashboardClearStaleModalLayer();
                });
        })(jQuery);

        function unconfirmed_meters_alert(button) {
            if ({{ $unconfirmed_meters }} != "0" && typeof toastr !== 'undefined') {
                toastr.error("Please Receive the Pumps before clicking the " + button);
            }
        }

        function shift_closed_alert(button) {
            var message = "Shift is closed. Cannot perform " + button + " at this time.";
            if (typeof toastr !== 'undefined') {
                toastr.error(message);
            } else {
                alert(message);
            }
        }

        function openPumperPaymentPage(e, el) {
            if (e) {
                e.preventDefault();
                if (typeof e.stopImmediatePropagation === 'function') {
                    e.stopImmediatePropagation();
                }
                e.stopPropagation();
            }

            if (el.getAttribute('data-shift-closed') === '1') {
                shift_closed_alert('Payment');
                return false;
            }

            if (el.getAttribute('data-pump-receive-complete') !== '1') {
                var receiveMessage = 'Please complete the Receive Pump process before opening Payments.';
                if (typeof toastr !== 'undefined') {
                    toastr.error(receiveMessage);
                } else {
                    alert(receiveMessage);
                }
                return false;
            }

            var url = el.getAttribute('data-payment-url') || el.getAttribute('href');
            if (!url || url === '#') {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Payment page URL is missing. Please reload and try again.');
                }
                return false;
            }

            window.location.assign(url);
            return false;
        }

        function fitPumperDashboardToViewport() {
            var page = document.querySelector('.pumper-dashboard-page');
            if (!page || window.innerWidth <= 991) {
                if (page) {
                    page.style.removeProperty('--pumper-viewport-height');
                }
                return;
            }

            var pageTop = Math.max(0, page.getBoundingClientRect().top);
            var availableHeight = Math.max(360, window.innerHeight - pageTop);
            page.style.setProperty('--pumper-viewport-height', availableHeight + 'px');
        }

        $(document).ready(function() {
            fitPumperDashboardToViewport();
            window.addEventListener('resize', fitPumperDashboardToViewport, { passive: true });
            document.addEventListener('fullscreenchange', fitPumperDashboardToViewport);
            document.addEventListener('webkitfullscreenchange', fitPumperDashboardToViewport);

            $("#othersales_btn").click(function(e) {
                var isSellAllowed = $(this).data('other-sale-disabled');
                if (isSellAllowed == '0') {
                    e.preventDefault();
                    toastr.error("Please Request Sale Permission from Owner");
                }
            });

            $('body').addClass('sidebar-collapse');

            var start = $('input[name="date-filter"]:checked').data('start');
            var end = $('input[name="date-filter"]:checked').data('end');
            update_statistics(start, end);

            $(document).on('change', 'input[name="date-filter"]', function() {
                var start = $('input[name="date-filter"]:checked').data('start');
                var end = $('input[name="date-filter"]:checked').data('end');
                update_statistics(start, end);
            });

            @if (request()->tab == 'closing_meter')
                $('#closing_meter').trigger('click');
            @endif
        });

        function update_statistics(start, end) {
            var data = {
                start: start,
                end: end,
                pump_operator_id: {{ auth()->user()->pump_operator_id }}
            };
            var loader = '<i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i>';
            $('.total_liter_sold').html(loader);
            $('.total_income_earned').html(loader);
            $('.total_short').html(loader);
            $('.total_leave').html(loader);

            $.ajax({
                method: 'get',
                url: "{{ action('\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorController@getDashboardData') }}",
                dataType: 'json',
                data: data,
                success: function(data) {
                    $('.total_liter_sold').html(__currency_trans_from_en(data.total_liter_sold, true));
                    $('.total_income_earned').html(__currency_trans_from_en(data.total_income_earned, true));
                    $('.total_short').html(__currency_trans_from_en(data.total_short, true));
                    $('.total_excess').html(__currency_trans_from_en(data.total_excess, true));
                }
            });
        }
    </script>

    {{--
        MA-008: after closing a shift, ask "Take the shift Summary Print".

        Yes     -> opens the summary print
        No need -> logs the operator out automatically

        Button placement is per the requirement: Yes on the RIGHT, No on the LEFT.
        SweetAlert puts confirm on the right and cancel on the left by default, so
        Yes is the confirm button and "No need" is the cancel button - the order
        the requirement asks for, without fighting the library.

        The prompt is driven by a one-time flash from ClosingShiftController, so it
        appears once after an actual close and never on a plain page refresh.
    --}}
    @if(session('pumper_close_shift_print_prompt'))
        @php $ma008Prompt = session('pumper_close_shift_print_prompt'); @endphp
        <script>
            $(function () {
                var printUrl = @json($ma008Prompt['print_url'] ?? '');
                var logoutUrl = @json(action('Auth\PumpOperatorLoginController@logout'));
                var promptTitle = @json(__('pumperdashboard::lang.take_shift_summary_print'));
                var yesLabel = @json(__('pumperdashboard::lang.yes'));
                var noLabel = @json(__('pumperdashboard::lang.no_need'));

                function ma008Logout() {
                    window.location.href = logoutUrl;
                }

                /*
                 * This module ships SweetAlert 1 - see the "Balance settled" prompt
                 * in actions/closing_shift.blade.php, which uses the same swal()
                 * signature. In that API `buttons: [cancel, confirm]` renders the
                 * cancel button on the LEFT and confirm on the RIGHT, which is the
                 * placement the requirement asks for: No need left, Yes right.
                 */
                if (typeof swal !== 'function') {
                    if (printUrl && window.confirm(promptTitle)) {
                        window.open(printUrl, '_blank');
                    } else {
                        ma008Logout();
                    }
                    return;
                }

                swal({
                    title: promptTitle,
                    icon: 'info',
                    buttons: [noLabel, yesLabel],
                    closeOnClickOutside: false,
                    closeOnEsc: false
                }).then(function (wantsPrint) {
                    if (wantsPrint && printUrl) {
                        // New tab, so the dashboard stays available behind it.
                        window.open(printUrl, '_blank');
                    } else {
                        ma008Logout();
                    }
                });
            });
        </script>
    @endif
@endsection

<style>
/*
 * MA-002 (S-609 #5): Payments and Other Sales, once a pump is received.
 *
 * A distinct colour rather than simply un-greying, so the operator can see at
 * a glance which tiles are live. Placed after the card colours so it wins,
 * and NOT applied when .is-disabled is also present - a tile that cannot be
 * used must not look open.
 */
.pumper-dashboard-card.ma002-tab-open:not(.is-disabled) {
    background: linear-gradient(135deg, #0f9d58 0%, #0b8043 100%) !important;
    color: #fff !important;
}
.pumper-dashboard-card.ma002-tab-open:not(.is-disabled) .pumper-dashboard-card-label,
.pumper-dashboard-card.ma002-tab-open:not(.is-disabled) .pumper-dashboard-card-icon {
    color: #fff !important;
}
</style>
