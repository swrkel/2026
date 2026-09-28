{{--
    PETRO DIRECT SETTLEMENT - cumulative fix marker.

    Verify what is deployed with:
        grep -c "S699-CUMULATIVE" Modules/PetroDirect/Resources/views/settlement/create.blade.php

    This file carries all of the following. Deploying any EARLIER package over
    it removes them, which is what made the fixes appear to regress once before.

      S691  Pump dropdown loads when the operator is chosen (Bulk Sale has no
            shift, so the shift handler that used to load it never fired)
      S693  Transaction Date survives a refresh (sessionStorage; the page used
            to call setDate(new Date()) on every load)
      S693  Pump excluded once selected, and after a refresh - the server
            decides, via active_settlement_id
      S693  Pump returns to the dropdown when its row is removed
      S696  Unit Price from the PUMP's product, not the fuel tank's
      S696  Delete failures now report their reason instead of doing nothing
      IS2151 Finalize Settlement appears on a balance that ROUNDS to zero
            (the check was === 0 on a float, so 0.0000001 kept it hidden)
    S699-CUMULATIVE
--}}

<style id="s383-petro-white-button-text-fix">
/* S383: Settlement/Add Payment buttons - default text must be white in all Petro modules. */
.settlement_tabs .nav-tabs > li > a,
.settlement_tabs .nav-tabs > li > a span,
.payment_tabs .nav-tabs > li > a,
.payment_tabs .nav-tabs > li > a span,
.payment_tabs .btn,
.payment_tabs .btn *,
.settlement_tabs .btn,
.settlement_tabs .btn *,
#settlement_save_btn,
#settlement_save_btn *,
#payment_review_btn,
#payment_review_btn *,
.btn_meter_sale_cancel,
.btn_meter_sale_cancel *,
.btn_update_meter_sale,
.btn_update_meter_sale * {
    color: #ffffff !important;
}

/* Only disabled/default grey buttons may keep their normal contrast. */
.settlement_tabs .btn-default,
.settlement_tabs .btn-default *,
.payment_tabs .btn-default,
.payment_tabs .btn-default * {
    color: #333333 !important;
}

/* Keep selected tab readable only where the tab itself intentionally becomes white. */
.settlement_tabs .nav-tabs > li.active > a,
.settlement_tabs .nav-tabs > li.active > a span,
.payment_tabs .nav-tabs > li.active > a,
.payment_tabs .nav-tabs > li.active > a span,
.settlement_tabs .nav-tabs > li > a.active,
.settlement_tabs .nav-tabs > li > a.active span,
.payment_tabs .nav-tabs > li > a.active,
.payment_tabs .nav-tabs > li > a.active span {
    color: #000000 !important;
}

    /* Settlement header layout: give Settlement No the width saved from Work Shift. */
    .settlement-header-row .settlement-no-col > .form-group > label {
        white-space: nowrap;
    }

</style>
@extends('layouts.app')


<style>
/* V2 REUPLOAD: force all settlement/add-payment action buttons to use white font color. */
.btn,
.btn:link,
.btn:visited,
.btn:hover,
.btn:focus,
.btn:active,
button.btn,
a.btn,
input.btn,
.btn span,
.btn i,
.payment_tabs .btn,
.payment_tabs .btn *,
.settlement-tab-button,
.settlement-tab-button *,
.nav-tabs > li > a.btn,
.nav-pills > li > a.btn {
    color: #ffffff !important;
}
.btn-default,
.btn-default:link,
.btn-default:visited,
.btn-default:hover,
.btn-default:focus,
.btn-default:active {
    color: #ffffff !important;
}
</style>
@section('title', __('petrodirect::lang.settlement'))

@section('content')
@php
// Controller passes the tenant/business context already resolved after tenancy.
// Do not re-resolve Direct Settlement from a pre-tenancy session value here.
$business_id = (int) ($business_id ?? 0);
if ($business_id <= 0) {
    $business_id = (int) (session('business.id') ?? session('user.business_id') ?? optional(auth()->user())->business_id ?? 0);
}
$business_details = $business ?? App\Business::find($business_id);
$currency_precision = $business_details->currency_precision ?? 2;
$meeter_precision = 3;

$asset_vapps = @filemtime(base_path('Modules/PetroDirect/Resources/assets/js/app.js')) ?: 1;
$asset_vpayment = @filemtime(base_path('Modules/PetroDirect/Resources/assets/js/payment.js')) ?: 1;
$asset_vpetro_payment = @filemtime(base_path('Modules/PetroDirect/Resources/assets/js/petro_payment.js')) ?: 1;
$subscription = \Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
$manage_module_enable = !empty($subscription->package_details) ? json_decode(json_encode($subscription->package_details), true) : [];
$disable_shift_no = !array_key_exists('disable_shift_no_direct_settlement', $manage_module_enable) ? true : !empty($manage_module_enable['disable_shift_no_direct_settlement']);
$initial_direct_shift_number = '';
if (!empty($active_settlement->work_shift)) {
    $direct_shift_prefix = 'DST';
    $direct_shift_value = $active_settlement->work_shift;

    for ($i = 0; $i < 3; $i++) {
        if (is_array($direct_shift_value)) {
            $direct_shift_value = collect($direct_shift_value)->first();
        }

        if (is_string($direct_shift_value) && preg_match('/' . preg_quote($direct_shift_prefix, '/') . '\s*(\d+)/i', $direct_shift_value, $matches)) {
            $initial_direct_shift_number = $direct_shift_prefix . $matches[1];
            break;
        }

        $decoded_direct_shift = is_string($direct_shift_value) ? json_decode($direct_shift_value, true) : null;
        if (json_last_error() !== JSON_ERROR_NONE || $decoded_direct_shift === $direct_shift_value) {
            break;
        }

        $direct_shift_value = $decoded_direct_shift;
    }
}
@endphp

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrodirect::lang.petro')</a></li>
                    <li><span>@lang('petrodirect::lang.settlement')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    body.modal-open {
        height: 100vh;
        overflow-y: hidden;
    }

    .col-md-1-7 {
        flex: 0 0 14.2857% !important;
        max-width: 14.2857% !important;
        padding: 6px 12px !important;
    }

    .select2-selection {
        line-height: 21px !important;
    }

    /* Fix swal z-index inside Bootstrap modals */
    .swal-overlay { z-index: 99999 !important; }
    .swal-modal   { z-index: 100000 !important; }
</style>
@endpush

<section class="content main-content-inner">
    @if(!empty($message)) {!! $message !!} @endif

    {{-- Filters --}}
    <div class="row settlement-header-row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="row">
                <div class="col-md-2 settlement-no-col">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('petrodirect::lang.settlement_no') . ':') !!}
                        {!! Form::text('settlement_no', $active_settlement->settlement_no ?? $settlement_no, ['class' =>
                        'form-control', 'readonly']) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, $active_settlement->location_id ??
                        $default_location ?? null, ['class' => 'form-control select2', 'id' => 'location_id',
                        'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('petrodirect::lang.pump_operator').':') !!}
                        @php
                            $default_pump_operator = $active_settlement->pump_operator_id
                                ?? $default_pump_operator_id ?? null;
                        @endphp
                        {!! Form::select('pump_operator_id', $pump_operators, $default_pump_operator, [
                        'class' => 'form-control select2',
                        'id' => 'pump_operator_id',
                        // Petro Direct settlement requires the operator to be selectable.
                        // Do not inherit the Petro PD read-only restriction here.
                        'placeholder' => __('petrodirect::lang.please_select'),
                        'style' => 'width:100%'
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __( 'petrodirect::lang.transaction_date' ) . ':*') !!}
                        {!! Form::text('transaction_date', $active_settlement->transaction_date ?? null, ['class' =>
                        'form-control transaction_date', 'required', 'placeholder' => __('petrodirect::lang.transaction_date')
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-1 work-shift-col">
                    <div class="form-group">
                        {!! Form::label('work_shift', __('petrodirect::lang.work_shift').':') !!}
                        {!! Form::select('work_shift[]', $wrok_shifts, $active_settlement->work_shift ?? [], ['class' =>
                        'form-control select2', 'id' => 'work_shift', 'multiple']) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('shift_number', __('petrodirect::lang.shift_number').':') !!}
                        {!! Form::select('shift_number', $shift_numbers, null, ['id' => 'shift_number', 'class' =>
                        'form-control select2', 'required']) !!}
                        <input type="text" id="manual_shift_number" class="form-control hide"
                            placeholder="Shift No is auto-generated (DST1, DST2, ...)">
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group">
                        {!! Form::label('note', __('petrodirect::lang.note') . ':') !!}
                        {!! Form::text('note', $active_settlement->note ?? null, ['class' => 'form-control note', 'id'
                        => 'note', 'placeholder' => __('petrodirect::lang.note')]) !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    {{-- Direct Settlement main tabs: deterministic local switcher.
         The global Manage Page scanner can incorrectly mark a rendered
         top-level pane as a disabled minor action. The page itself has already
         passed its route permission; clear only that incorrect pane marker. --}}
    <script id="petrodirect-direct-settlement-tab-fix">
        window.petroDirectOpenDirectSettlementTab = function (selector, link, clickEvent) {
            if (clickEvent) {
                clickEvent.preventDefault();
                clickEvent.stopImmediatePropagation();
            }

            var root = link && link.closest
                ? link.closest('.direct-settlement-main-tabs')
                : document.querySelector('.direct-settlement-main-tabs');
            if (!root || !selector || selector.charAt(0) !== '#') return false;

            var nav = root.querySelector('.nav-tabs');
            var content = root.querySelector('.tab-content');
            var pane = content ? content.querySelector(selector) : null;
            if (!nav || !content || !pane) return false;

            pane.classList.remove('business-manage-disabled-tab');

            Array.prototype.forEach.call(nav.children, function (item) {
                item.classList.remove('active', 'show');
                var control = item.querySelector('a, button');
                if (control) {
                    control.classList.remove('active');
                    control.setAttribute('aria-selected', 'false');
                }
            });

            Array.prototype.forEach.call(content.children, function (candidate) {
                if (!candidate.classList.contains('direct-settlement-main-pane')) return;
                candidate.classList.remove('active', 'in', 'show');
                candidate.style.setProperty('display', 'none', 'important');
                candidate.style.setProperty('visibility', 'hidden', 'important');
                candidate.style.setProperty('pointer-events', 'none', 'important');
                candidate.setAttribute('aria-hidden', 'true');
            });

            if (link && link.parentNode) {
                link.parentNode.classList.add('active', 'show');
            }
            if (link) {
                link.classList.add('active');
                link.setAttribute('aria-selected', 'true');
            }

            pane.classList.add('active', 'in', 'show');
            pane.style.setProperty('display', 'block', 'important');
            pane.style.setProperty('visibility', 'visible', 'important');
            pane.style.setProperty('pointer-events', 'auto', 'important');
            pane.setAttribute('aria-hidden', 'false');

            var belowBox = document.getElementById('below_box');
            if (belowBox) belowBox.classList.remove('hide');

            if (window.jQuery) {
                window.jQuery(link).trigger('shown.bs.tab');
                window.jQuery(document).trigger(
                    'petrodirect:direct-settlement-tab-shown',
                    [selector]
                );
                window.setTimeout(function () {
                    if (window.jQuery.fn && window.jQuery.fn.dataTable) {
                        window.jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                    }
                    if (selector === '#payment_tab'
                        && typeof window.calculate_payment_tab_total === 'function') {
                        window.calculate_payment_tab_total();
                    }
                }, 20);
            }

            return false;
        };
    </script>

    {{-- Widget area with tabs --}}
    @component('components.widget', ['class' => 'box-primary below_box', 'id' => 'below_box'])
    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs direct-settlement-main-tabs">
                <ul class="nav nav-tabs no-erp-global-tabs no-exf-tabs" role="tablist">
                    <li class="active">
                        <button type="button" class="direct-main-tab-control meter_sale_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#meter_sale_tab', this, event);">
                            <i class="fa fa-tachometer"></i> <strong>@lang('petrodirect::lang.meter_sale')s</strong>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="direct-main-tab-control other_sale_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#other_sale_tab', this, event);">
                            <i class="fa fa-balance-scale"></i> <strong>@lang('petrodirect::lang.other_sale')</strong>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="direct-main-tab-control other_income_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#other_income_tab', this, event);">
                            <i class="fa fa-thermometer"></i> <strong>@lang('petrodirect::lang.other_income')</strong>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="direct-main-tab-control customer_payment_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#customer_payment_tab', this, event);">
                            <i class="fa fa-money"></i> <strong>@lang('petrodirect::lang.customer_payment')</strong>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="direct-main-tab-control payment_tab"
                            onclick="return window.petroDirectOpenDirectSettlementTab('#payment_tab', this, event);">
                            <i class="fa fa-book"></i> <strong>@lang('petrodirect::lang.payment')</strong>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane direct-settlement-main-pane active in show" id="meter_sale_tab">
                        @include('petrodirect::settlement.partials.meter_sale')
                    </div>

                    <div class="tab-pane direct-settlement-main-pane" id="other_sale_tab">
                        @include('petrodirect::settlement.partials.other_sale')
                        <input type="hidden" value="{{$check_qty}}" id="allowoverselling">
                    </div>

                    <div class="tab-pane direct-settlement-main-pane" id="other_income_tab">
                        @include('petrodirect::settlement.partials.other_income')
                    </div>

                    <div class="tab-pane direct-settlement-main-pane" id="customer_payment_tab">
                        @include('petrodirect::settlement.partials.customer_payment')
                    </div>

                    <div class="tab-pane direct-settlement-main-pane" id="payment_tab">
                        @include('petrodirect::settlement.partials.payment')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcomponent

    <style id="petrodirect-direct-settlement-tab-visibility">
        .direct-settlement-main-tabs > .nav-tabs > li > .direct-main-tab-control {
            border: 1px solid transparent;
            border-radius: 0;
            color: #fff !important;
            cursor: pointer;
            display: block;
            font: inherit;
            line-height: 1.42857143;
            margin-right: 2px;
            padding: 15px 22px;
            position: relative;
            white-space: nowrap;
        }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(1) > .direct-main-tab-control { background: #2f80ed; }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(2) > .direct-main-tab-control { background: #9b0f8f; }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(3) > .direct-main-tab-control { background: #3486a8; }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(4) > .direct-main-tab-control { background: #247a2e; }
        .direct-settlement-main-tabs > .nav-tabs > li:nth-child(5) > .direct-main-tab-control { background: #f4a51c; }
        .direct-settlement-main-tabs > .nav-tabs > li.active > .direct-main-tab-control {
            background: #fff !important;
            border-color: #ddd #ddd #fff;
            color: #222 !important;
        }
        .direct-settlement-main-tabs > .tab-content > .direct-settlement-main-pane {
            display: none !important;
        }
        .direct-settlement-main-tabs > .tab-content > .direct-settlement-main-pane.active {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
    </style>

    <script id="petrodirect-direct-settlement-tab-marker-guard">
        (function () {
            var root = document.querySelector('.direct-settlement-main-tabs');
            if (!root) return;

            function clearIncorrectTopLevelMarkers() {
                var content = root.querySelector('.tab-content');
                if (!content) return;

                Array.prototype.forEach.call(content.children, function (pane) {
                    if (pane.classList
                        && pane.classList.contains('direct-settlement-main-pane')
                        && pane.classList.contains('business-manage-disabled-tab')) {
                        pane.classList.remove('business-manage-disabled-tab');
                    }
                });
            }

            clearIncorrectTopLevelMarkers();

            if (window.MutationObserver) {
                var observer = new MutationObserver(clearIncorrectTopLevelMarkers);
                observer.observe(root, {
                    attributes: true,
                    attributeFilter: ['class'],
                    childList: true,
                    subtree: true
                });
            }
        })();
    </script>

    {{-- Modals --}}
    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade add_payment" role="dialog" aria-labelledby="gridSystemModalLabel" style="overflow-y: auto;">
    </div>
    <div class="modal fade preview_settlement" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade" id="mechanical_meter_modal" role="dialog" aria-labelledby="mechanicalMeterModalLabel">
        <div class="modal-dialog modal-sm" style="width: 380px; max-width: 95%;">
            <div class="modal-content">
                <div class="modal-body" style="padding: 12px;">
                    <div class="form-group row" style="margin-bottom: 10px;">
                        <label class="col-xs-8 control-label" for="mechanical_last_meter_input" style="padding-top: 7px;">
                            Last Meter - Mechanical
                        </label>
                        <div class="col-xs-4">
                            <input type="text" class="form-control input_number" id="mechanical_last_meter_input" step="0.001">
                        </div>
                    </div>
                    <div class="form-group row" style="margin-bottom: 10px;">
                        <label class="col-xs-8 control-label" for="mechanical_digital_last_meter_input" style="padding-top: 7px;">
                            Entered Last Digital Meter
                        </label>
                        <div class="col-xs-4">
                            <input type="text" class="form-control input_number" id="mechanical_digital_last_meter_input" step="0.001" readonly>
                        </div>
                    </div>
                    <div class="form-group row" style="margin-bottom: 14px;">
                        <label class="col-xs-8 control-label" for="mechanical_meter_difference_input" style="padding-top: 7px;">
                            Difference Mechanical Meter to Digital Meter
                        </label>
                        <div class="col-xs-4">
                            <input type="text" class="form-control input_number" id="mechanical_meter_difference_input" step="0.001" readonly>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-info" id="add_mechanical_meter_btn">
                            @lang('messages.add')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="settlement_print"></div>
</section>
<!-- /.content -->

    @include('petrodirect::partials.global_tab_standard')
@endsection


<style id="s385-force-settlement-button-text-white">
/* S385: focused fix requested by user.
   Settlement/Add Payment button text must be WHITE on Add Settlement and Add Payment pages.
   No functional logic changed. */
.settlement_tabs .btn,
.settlement_tabs .btn:link,
.settlement_tabs .btn:visited,
.settlement_tabs .btn:hover,
.settlement_tabs .btn:focus,
.settlement_tabs .btn:active,
.settlement_tabs .btn.active,
.settlement_tabs .btn.selected,
.settlement_tabs .btn.is-active,
.settlement_tabs .btn *,
.payment_tabs .btn,
.payment_tabs .btn:link,
.payment_tabs .btn:visited,
.payment_tabs .btn:hover,
.payment_tabs .btn:focus,
.payment_tabs .btn:active,
.payment_tabs .btn.active,
.payment_tabs .btn.selected,
.payment_tabs .btn.is-active,
.payment_tabs .btn *,
#settlement_form .btn,
#settlement_form .btn:link,
#settlement_form .btn:visited,
#settlement_form .btn:hover,
#settlement_form .btn:focus,
#settlement_form .btn:active,
#settlement_form .btn.active,
#settlement_form .btn.selected,
#settlement_form .btn.is-active,
#settlement_form .btn *,
#settlement_save_btn,
#settlement_save_btn:link,
#settlement_save_btn:visited,
#settlement_save_btn:hover,
#settlement_save_btn:focus,
#settlement_save_btn:active,
#settlement_save_btn.active,
#settlement_save_btn *,
#payment_review_btn,
#payment_review_btn:link,
#payment_review_btn:visited,
#payment_review_btn:hover,
#payment_review_btn:focus,
#payment_review_btn:active,
#payment_review_btn.active,
#payment_review_btn *,
.btn_meter_sale_cancel,
.btn_meter_sale_cancel *,
.btn_update_meter_sale,
.btn_update_meter_sale *,
.btn-modal.btn,
.btn-modal.btn *,
button.btn,
button.btn *,
a.btn,
a.btn *,
input.btn {
    color: #ffffff !important;
}
</style>

@section('javascript')
{{-- Reload when returning via back/forward cache to avoid showing stale settlement number --}}
<script>
    (function () {
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    })();
</script>

@include('petrodirect::settlement.partials.payment_tab_controller')
<script src="{{ route('petrodirect.assets.js', ['file' => 'app.js']) }}?v={{ $asset_vapps }}"></script>
<script src="{{ route('petrodirect.assets.js', ['file' => 'payment.js']) }}?v={{ $asset_vpayment }}"></script>
<script src="{{ route('petrodirect.assets.js', ['file' => 'petro_payment.js']) }}?v={{ $asset_vpetro_payment }}"></script>
<script>window.__petro_settlement_create_local = true;</script>
<script>
    (function () {
        var $doc = $(document);

        function mechanicalNumber(value) {
            value = (value || '').toString().replace(/,/g, '');
            var parsed = parseFloat(value);
            return isNaN(parsed) ? null : parsed;
        }

        function formatMechanicalNumber(value) {
            return (parseFloat(value) || 0).toFixed(3);
        }

        function getCurrentDigitalMeterValue() {
            var digital = mechanicalNumber($('#pump_closing_meter').val());
            return digital === null ? null : digital;
        }

        function requiresMechanicalMeterSave() {
            return $('#mechanical_meter_btn').length > 0 && $('.btn_meter_sale').length > 0;
        }

        function setMeterSaleAddState() {
            var $button = $('.btn_meter_sale');
            if (!$button.length || window.isMeterSaleSubmitting) {
                return;
            }

            // LA-1091 urgent correction: the optional Mechanical Meter entry
            // must not disable or block the main Meter Sale Add button.
            $button.prop('disabled', false).removeAttr('disabled').removeClass('disabled');
        }

        window.refreshPetroMeterSaleAddState = setMeterSaleAddState;

        function markMechanicalMeterUnsaved() {
            if (!requiresMechanicalMeterSave()) {
                return;
            }

            $('#mechanical_meter_saved').val('0');
            setMeterSaleAddState();
        }

        function updateMechanicalDifference() {
            var mechanical = mechanicalNumber($('#mechanical_last_meter_input').val());
            var digital = getCurrentDigitalMeterValue();

            $('#mechanical_digital_last_meter_input').val(digital === null ? '' : formatMechanicalNumber(digital));

            if (mechanical !== null) {
                $('#mechanical_last_meter_input').val(formatMechanicalNumber(mechanical));
            }

            $('#mechanical_meter_difference_input').val(
                mechanical === null || digital === null ? '' : formatMechanicalNumber(mechanical - digital)
            );
        }

        $doc.off('click.direct_mechanical_meter', '#mechanical_meter_btn')
            .on('click.direct_mechanical_meter', '#mechanical_meter_btn', function () {
                var digital = getCurrentDigitalMeterValue();

                $('#mechanical_last_meter_input').val($('#mechanical_last_meter').val());
                $('#mechanical_digital_last_meter_input').val(digital === null ? '' : formatMechanicalNumber(digital));
                $('#mechanical_meter_difference_input').val('');
                updateMechanicalDifference();
                $('#mechanical_meter_modal').modal('show');
            });

        $doc.off('input.direct_mechanical_meter', '#mechanical_last_meter_input')
            .on('input.direct_mechanical_meter', '#mechanical_last_meter_input', function () {
                var mechanical = mechanicalNumber($(this).val());
                var digital = getCurrentDigitalMeterValue();

                $('#mechanical_digital_last_meter_input').val(digital === null ? '' : formatMechanicalNumber(digital));
                $('#mechanical_meter_difference_input').val(
                    mechanical === null || digital === null ? '' : formatMechanicalNumber(mechanical - digital)
                );
            });

        $doc.off('blur.direct_mechanical_meter', '#mechanical_last_meter_input')
            .on('blur.direct_mechanical_meter', '#mechanical_last_meter_input', updateMechanicalDifference);

        $doc.off('input.direct_mechanical_meter change.direct_mechanical_meter', '#pump_closing_meter')
            .on('input.direct_mechanical_meter change.direct_mechanical_meter', '#pump_closing_meter', function () {
                var digital = getCurrentDigitalMeterValue();
                var mechanical = mechanicalNumber($('#mechanical_last_meter').val());

                markMechanicalMeterUnsaved();
                $('#mechanical_digital_last_meter').val(digital === null ? '' : formatMechanicalNumber(digital));
                $('#mechanical_meter_difference').val(
                    mechanical === null || digital === null ? '' : formatMechanicalNumber(mechanical - digital)
                );

                if ($('#mechanical_meter_modal').hasClass('in')) {
                    $('#mechanical_digital_last_meter_input').val(digital === null ? '' : formatMechanicalNumber(digital));
                    $('#mechanical_meter_difference_input').val($('#mechanical_meter_difference').val());
                }
            });

        $doc.off('click.direct_mechanical_meter', '#add_mechanical_meter_btn')
            .on('click.direct_mechanical_meter', '#add_mechanical_meter_btn', function () {
                var mechanical = mechanicalNumber($('#mechanical_last_meter_input').val());
                var digital = getCurrentDigitalMeterValue();

                if (digital === null) {
                    toastr.error('Please enter the Pump Closing Meter first.');
                    return;
                }

                if (mechanical === null) {
                    toastr.error('Please enter Last Meter - Mechanical.');
                    return;
                }

                var difference = mechanical - digital;
                $('#mechanical_last_meter').val(formatMechanicalNumber(mechanical));
                $('#mechanical_digital_last_meter').val(formatMechanicalNumber(digital));
                $('#mechanical_meter_difference').val(formatMechanicalNumber(difference));
                $('#mechanical_meter_saved').val('1');
                $('#mechanical_last_meter_input').val(formatMechanicalNumber(mechanical));
                $('#mechanical_digital_last_meter_input').val(formatMechanicalNumber(digital));
                $('#mechanical_meter_difference_input').val(formatMechanicalNumber(difference));
                setMeterSaleAddState();
                $('#mechanical_meter_modal').modal('hide');
            });

        $(setMeterSaleAddState);
    })();
</script>

<input type="hidden" id="active_settlement_id" value="{{ $active_settlement->id ?? 0 }}">
<input type="hidden" id="active_settlement_status" value="{{ $active_settlement->status ?? '' }}">
<input type="hidden" id="shift_closed" value="{{ !empty($shift_closed) ? $shift_closed : 'yes' }}">
<script>
    $(document).ready(function () {

        $('#other_income_table').DataTable({
            paging: false,       // no pagination (optional)
            searching: false,    // no search box (optional)
            info: false,         // hide "Showing x of y" (optional)
            ordering: true,      // ENABLE sorting
            order: [],           // no default order
            columnDefs: [
                { orderable: false, targets: [4] } // Disable sorting on Action column
            ]
        });
        $('#customer_payment_table').DataTable({
            paging: false,       // no pagination (optional)
            searching: false,    // no search box (optional)
            info: false,         // hide "Showing x of y" (optional)
            ordering: true,      // ENABLE sorting
            order: [],           // no default order
            columnDefs: [
                { orderable: false, targets: [8] } // Disable sorting on Action column
            ]
        });
    });
</script>
<script>


    /**
     * Optimized Settlement Blade JS
     * - Cached selectors
     * - Helper functions for ajax & datatables
     * - Modern syntax and clearer flow
     */

    (() => {
        // Cached selectors
        const $doc = $(document);
        const $window = $(window);
        const $note = $('#note');
        const $workShift = $('#work_shift');
        const $transactionDate = $('.transaction_date');
        const $pumpOperator = $('#pump_operator_id');
        const $location = $('#location_id');
        const $shiftNumber = $('#shift_number');
        const $manualShiftNumber = $('#manual_shift_number');
        const $belowBox = $('#below_box');
        const $shiftClosed = $('#shift_closed');
        const $activeSettlement = $('#active_settlement_id');
        const $activeSettlementStatus = $('#active_settlement_status');
        const manualShiftUrl = "{{ route('petrodirect.settlement.store-manual-shift-number') }}";

        const hasActiveSettlement = {!! json_encode(!empty($active_settlement)) !!};
        const initialActiveSettlementIsDraft = {!! json_encode(!empty($active_settlement) && (int) $active_settlement->status === 1) !!};
        let manualShiftRequestPending = false;
        let currentPumpOperatorId = $pumpOperator.val() || '';

        /*
         * S715 - Direct Settlement operator selection readiness.
         *
         * app.js intentionally locks every element inside #below_box when this
         * page first opens without an operator. Its old operator-change handler
         * is no longer active, so that blanket lock was never removed when the
         * user selected an operator. A refresh appeared to fix it only because
         * app.js then saw the pre-selected operator during document.ready.
         *
         * Capture the page's real/original disabled state BEFORE document.ready
         * runs. When an operator is selected we restore exactly that state,
         * rather than blindly enabling fields which are intentionally disabled
         * by their own business rules (for example Sold Qty).
         */
        const s715OriginalDisabledKey = 's715DirectSettlementOriginalDisabled';
        $belowBox.find('*').each(function () {
            $(this).data(s715OriginalDisabledKey, this.hasAttribute('disabled'));
        });

        function restoreDirectSettlementControlsForOperator() {
            if (!$belowBox.length || !$pumpOperator.val()) return;

            $belowBox.removeClass('hide');

            $belowBox.find('*').each(function () {
                const $element = $(this);
                const originallyDisabled = $element.data(s715OriginalDisabledKey) === true;

                if (originallyDisabled) {
                    $element.attr('disabled', 'disabled');
                    if ($element.is('input, select, textarea, button, fieldset, optgroup, option')) {
                        $element.prop('disabled', true);
                    }
                } else {
                    $element.removeAttr('disabled');
                    if ($element.is('input, select, textarea, button, fieldset, optgroup, option')) {
                        $element.prop('disabled', false);
                    }
                }
            });

            // The five main tabs must respond immediately after operator selection.
            $belowBox.find('.direct-main-tab-control')
                .prop('disabled', false)
                .removeAttr('disabled')
                .removeClass('disabled');

            // Keep the existing, intentional runtime restrictions intact.
            if (!$('#store_id').val()) {
                $('#item').prop('disabled', true);
            }

            const selectedShiftIds = getSelectedShiftIds();
            const canFinalize = selectedShiftIds.length > 0;
            $('#add_payment')
                .prop('disabled', !canFinalize)
                .toggleClass('disabled', !canFinalize);

            // Select2 was initialised while the controls were locked. Force it to
            // resynchronise with the restored enabled/disabled state.
            $belowBox.find('select.select2').each(function () {
                const $select = $(this);
                if ($select.data('select2')) {
                    $select.trigger('change.select2');
                }
            });
        }

        function lockDirectSettlementControlsWithoutOperator() {
            $belowBox.addClass('hide');
            $belowBox.find('*').attr('disabled', 'disabled');
            $belowBox.find('input, select, textarea, button, fieldset, optgroup, option')
                .prop('disabled', true);
            $('#add_payment').prop('disabled', true).addClass('disabled');
        }

    // CSRF setup
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // ---------- Helpers ----------
    function toastError(msg = 'Enter closing meter greater than the Starting meter') { toastr.error(msg); }
    function toastSuccess(msg) { toastr.success(msg); }

    function apiGet(url, data = {}) {
        return $.ajax({ method: 'GET', url, data });
    }
    function apiPut(url, data = {}) {
        return $.ajax({ method: 'PUT', url, data });
    }

    // Check previous unsettled (returns Promise<boolean>)
    async function checkPreviousUnsettled(shift_id) {
        try {
            const res = await apiGet("{{ url('petrodirect/settlement/check_prev_settlement') }}", {
                shift_id,
                pump_operator_id: $pumpOperator.val() || null
            });
            if (res && res.status) return true;
            // Keep blocking logic, but suppress toast message as requested.
            return false;
        } catch (e) {
            toastError("Something went wrong.");
            return false;
        }
    }

    // LA-1091 v4: keep the selected pump stable while asynchronous pump lists refresh.
    // The page can issue more than one pump-list request during initialisation/shift hydration.
    // A late/empty response must never reset a pump that the user has already selected.
    let pumpOptionsRequestNo = 0;
    let pumpOptionsXhr = null;

    function getPumpSelectionContext() {
        const shifts = getSelectedShiftIds();
        return [
            String($location.val() || ''),
            String($pumpOperator.val() || ''),
            shifts.map(String).join(',')
        ].join('|');
    }

    function rememberSelectedPump($select) {
        $select = $select && $select.length ? $select : $('#pump_no');
        const id = String($select.val() || '');
        if (!id) return;

        const text = $.trim($select.find('option:selected').text() || '');
        const context = getPumpSelectionContext();

        $select.attr('data-selected-pump-id', id)
            .attr('data-selected-pump-text', text)
            .attr('data-selected-pump-context', context);

        $('#meter_sale_selected_pump_id').val(id);
        $('#meter_sale_selected_pump_text').val(text);
        $('#meter_sale_selected_pump_context').val(context);
    }

    function selectedPumpState($select) {
        $select = $select && $select.length ? $select : $('#pump_no');
        const currentId = String($select.val() || '');
        const storedId = String(
            $select.attr('data-selected-pump-id') ||
            $('#meter_sale_selected_pump_id').val() ||
            ''
        );
        const id = currentId || storedId;
        const text = $.trim(
            (currentId ? $select.find('option:selected').text() : '') ||
            $select.attr('data-selected-pump-text') ||
            $('#meter_sale_selected_pump_text').val() ||
            ''
        );
        const context = String(
            $select.attr('data-selected-pump-context') ||
            $('#meter_sale_selected_pump_context').val() ||
            ''
        );

        return { id, text, context };
    }

    function syncPumpSelect2($select, value) {
        window.__directSettlementPumpSyncing = true;
        try {
            $select.val(value);
            // Full change is required by the Select2 version used by this ERP. The guarded
            // handlers in app.js ignore this presentation-only synchronisation event.
            $select.trigger('change');
        } finally {
            window.__directSettlementPumpSyncing = false;
        }
    }

    function updatePumpDropdown(pump_nos = {}, options = {}) {
        const $select = $('#pump_no');
        if (!$select.length) return;

        const state = selectedPumpState($select);
        const currentContext = getPumpSelectionContext();
        const preserveSelection = options.preserveSelection !== false
            && !!state.id
            && (!state.context || state.context === currentContext);

        const selectedId = preserveSelection ? String(state.id) : '';
        let selectedText = preserveSelection ? state.text : '';
        let selectedFound = false;

        const $fragment = $(document.createDocumentFragment());
        $fragment.append($('<option>', { value: '', text: "@lang('petrodirect::lang.please_select')" }));

        $.each(pump_nos || {}, function (id, name) {
            const optionId = String(id);
            const optionText = String(name == null ? '' : name);
            if (selectedId && optionId === selectedId) {
                selectedFound = true;
                selectedText = optionText || selectedText;
            }
            $fragment.append($('<option>', { value: optionId, text: optionText }));
        });

        // The selected pump can legitimately be absent from a later filtered response
        // (for example, because it is now considered assigned/busy). Keep that exact option
        // until the user changes Location/Operator/Shift or successfully adds the row.
        if (selectedId && !selectedFound) {
            $fragment.append($('<option>', {
                value: selectedId,
                text: selectedText || ('Pump ' + selectedId)
            }));
        }

        $select.empty().append($fragment);

        if (selectedId) {
            syncPumpSelect2($select, selectedId);
            rememberSelectedPump($select);
        } else {
            syncPumpSelect2($select, '');
        }
    }

    function requestPumpOptions(url, data = {}, options = {}) {
        const requestNo = ++pumpOptionsRequestNo;
        const requestContext = getPumpSelectionContext();

        if (pumpOptionsXhr && pumpOptionsXhr.readyState !== 4) {
            pumpOptionsXhr.abort();
        }

        pumpOptionsXhr = apiGet(url, data);
        pumpOptionsXhr.done(result => {
            if (requestNo !== pumpOptionsRequestNo || requestContext !== getPumpSelectionContext()) return;
            if (result && result.success) {
                updatePumpDropdown(result.pumps || result.pump_nos || {}, options);
            } else if (!selectedPumpState($('#pump_no')).id) {
                updatePumpDropdown({}, { preserveSelection: false });
            }
        }).fail((xhr, status) => {
            if (status === 'abort' || requestNo !== pumpOptionsRequestNo) return;
            // Keep the current selection on network/server errors. Clearing it creates the
            // exact mismatch where meter details remain but Pump No displays Please Select.
            if (!selectedPumpState($('#pump_no')).id) {
                updatePumpDropdown({}, { preserveSelection: false });
            }
        });

        return pumpOptionsXhr;
    }

    // Keep state before any asynchronous change handlers run.
    $doc.off('select2:select.la1091_pump_memory change.la1091_pump_memory', '#pump_no')
        .on('select2:select.la1091_pump_memory change.la1091_pump_memory', '#pump_no', function () {
            if (window.__directSettlementPumpSyncing) return;
            if ($(this).val()) rememberSelectedPump($(this));
        });

    window.__rememberDirectSettlementPumpSelection = function () {
        rememberSelectedPump($('#pump_no'));
    };

    // Load pump dropdown by selected business location.
    function loadAssignedPumps() {
        const locationId = $location.val();
        if (!locationId) {
            if (!selectedPumpState($('#pump_no')).id) {
                updatePumpDropdown({}, { preserveSelection: false });
            }
            return;
        }

        requestPumpOptions(`/petrodirect/settlement/get_pumps_by_location`, {
            location_id: locationId,
            // MA-002 (IS-1944 #1): same reason as above.
            active_settlement_id: $('#active_settlement_id').val() || 0
        });
    }

    function ensureInitialPumpLoad(retries = 8) {
        const locationId = $location.val();
        if (locationId) {
            loadAssignedPumps();
            return;
        }
        if (retries > 0) {
            setTimeout(() => ensureInitialPumpLoad(retries - 1), 300);
        }
    }

    function getSelectedShiftIds() {
        const v = $shiftNumber.val();
        if (v === null || v === undefined || v === '') return [];
        // IS1761: expose only PetroDirect's synthetic zero ID to client requests.
        return ['0'];
    }

    function isDirectSettlementShiftSelected() {
        const selectedShiftIds = getSelectedShiftIds();
        if (!selectedShiftIds.length) return false;

        const $option = $shiftNumber.find(`option[value="${selectedShiftIds[0]}"]`);
        const text = $.trim($option.text() || '');

        return String(selectedShiftIds[0]) === '0'
            || String($option.data('direct-shift')) === '1'
            || text.toUpperCase().indexOf(directShiftPrefix.toUpperCase()) === 0;
    }

    const directShiftByOperator = {};
    const directSettlementByOperator = {};
    window.__directSettlementByOperator = directSettlementByOperator;
    const initialDirectShiftNumber = {!! json_encode($initial_direct_shift_number) !!};
    const directShiftPrefix = 'DST';

    if (initialActiveSettlementIsDraft && currentPumpOperatorId && $activeSettlement.val() && $activeSettlement.val() !== '0') {
        directSettlementByOperator[currentPumpOperatorId] = $activeSettlement.val();
    }
    if (initialActiveSettlementIsDraft && currentPumpOperatorId && initialDirectShiftNumber) {
        directShiftByOperator[currentPumpOperatorId] = initialDirectShiftNumber;
    }

    function getSelectedDirectShiftNumber() {
        const selectedShiftIds = getSelectedShiftIds();
        if (!selectedShiftIds.length) return '';

        const $option = $shiftNumber.find(`option[value="${selectedShiftIds[0]}"]`);
        const shiftText = $.trim($option.text());
        if ($option.data('direct-shift')) {
            return shiftText;
        }

        return shiftText.toUpperCase().indexOf(directShiftPrefix.toUpperCase()) === 0 ? shiftText : '';
    }
    function getSelectedDirectShiftOperatorId() {
        const selectedShiftIds = getSelectedShiftIds();
        if (!selectedShiftIds.length) return '';

        const $option = $shiftNumber.find(`option[value="${selectedShiftIds[0]}"]`);
        return $option.data('direct-shift') ? ($option.data('pump-operator') || '') : '';
    }
    function rememberSelectedDirectShift(operatorId) {
        const directShiftNumber = getSelectedDirectShiftNumber();
        if (operatorId && directShiftNumber) {
            directShiftByOperator[operatorId] = directShiftNumber;
        }
    }
    function getDirectShiftNumberValue(shiftNumber) {
        const match = (shiftNumber || '').toString().match(/(\d+)$/);
        return match ? parseInt(match[1], 10) : 0;
    }
    function getHighestKnownDirectShiftNumber() {
        const labels = Object.values(directShiftByOperator).filter(Boolean);
        const selectedLabel = getSelectedDirectShiftNumber();
        if (selectedLabel) {
            labels.push(selectedLabel);
        }

        return labels.reduce((highest, label) => {
            return getDirectShiftNumberValue(label) > getDirectShiftNumberValue(highest) ? label : highest;
        }, '');
    }
    function getDirectShiftPayload(operatorId) {
        if (operatorId && directShiftByOperator[operatorId]) {
            return {
                number: directShiftByOperator[operatorId],
                operatorId: operatorId
            };
        }

        return {
            number: '',
            operatorId: operatorId || getSelectedDirectShiftOperatorId()
        };
    }
    window.getSelectedDirectSettlementShiftNumber = getSelectedDirectShiftNumber;

    function normalizeManualShiftNumber(value) {
        let text = (value || '').toString().trim().toUpperCase().replace(/\s+/g, '');
        if (!text) return null;
        if (/^\d+$/.test(text)) return 'DS' + text;
        if (!/^[A-Z0-9_-]+$/.test(text)) return null;
        return text;
    }

    function initShiftNumberSelect2() {
        if ($shiftNumber.data('select2')) {
            $shiftNumber.select2('destroy');
        }

        $shiftNumber.select2({
            width: '100%',
            tags: false,
            placeholder: "{{ __('petrodirect::lang.please_select') }}",
        });
    }

    function toggleManualShiftInput(show) {
        const $container = $shiftNumber.next('.select2-container');

        if (show) {
            $shiftNumber.addClass('hide');
            $container.addClass('hide');
            $manualShiftNumber.removeClass('hide').val('').focus();
        } else {
            $manualShiftNumber.addClass('hide').val('');
            $shiftNumber.removeClass('hide');
            $container.removeClass('hide');
        }
    }

    function resetDirectSettlementPaymentTotals() {
        const zero = typeof __number_f === 'function' ? __number_f(0, false, false, __currency_precision) : '0.00';

        $('#meter_sale_total').val(0);
        $('#other_sale_total').val(0);
        $('#shift_operator_other_sale_total').val(0);
        $('#other_income_total').val(0);
        $('#customer_payment_total').val(0);
        $('#footer_list_meter_sales_amount').val(0).text(0);

        $('.meter_sale_total').text(zero);
        $('.other_sale_total').text(zero);
        $('.other_income_total').text(zero);
        $('.customer_payment_total').text(zero);

        $('.payment_meter_sale_total').text(zero);
        $('.payment_other_sale_total').text(zero);
        $('.payment_other_income_total').text(zero);
        $('.payment_customer_payment_total').text(zero);
        $('#payment_due').text(zero);
    }

    function resetMeterSaleTablesForContext() {
        // Direct Settlement must not carry totals from PetroPD/Pumper Dashboard rows.
        // Clear both visible rows and all total fields whenever the selected context changes
        // or whenever a DST shift is selected.
        $('#meter_sale_table tbody').empty();
        $('#meter_sale_table tfoot .meter_sale_total').text(typeof __number_f === 'function' ? __number_f(0, false, false, __currency_precision) : '0.00');
        $('#outside_meter_sale_table').hide();
        $('#meter_sale_table').show();

        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
            $('#pump_operator_meter_sale_table').DataTable().clear().draw();
        }

        resetDirectSettlementPaymentTotals();
    }

    function createManualShiftNumber(rawValue) {
        if (manualShiftRequestPending) return;

        const manualShiftNumber = normalizeManualShiftNumber(rawValue);
        if (!manualShiftNumber) {
            toastError('Manual shift number can contain only letters, numbers, dash, and underscore.');
            return;
        }

        if (!$pumpOperator.val()) {
            toastError('Please select a pump operator before entering a manual shift number.');
            return;
        }

        manualShiftRequestPending = true;
        $.ajax({
            method: 'POST',
            url: manualShiftUrl,
            dataType: 'json',
            data: {
                shift_number: manualShiftNumber,
                pump_operator_id: $pumpOperator.val(),
                transaction_date: $transactionDate.val(),
                location_id: $location.val(),
                work_shift: $workShift.val()
            },
            success: function (result) {
                if (!result || !result.success) {
                    toastError((result && result.msg) ? result.msg : 'Unable to create manual shift number.');
                    return;
                }

                if (!$shiftNumber.find(`option[value="${result.shift_id}"]`).length) {
                    $shiftNumber.append(`<option value="${result.shift_id}">${result.shift_number}</option>`);
                }

                toggleManualShiftInput(false);
                initShiftNumberSelect2();
                $shiftNumber.val(result.shift_id).trigger('change.select2');
                toastSuccess(result.msg || 'Manual shift number assigned.');
                $belowBox.removeClass('hide');
                $shiftNumber.trigger('change');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Unable to create manual shift number.';
                toastError(msg);
            },
            complete: function () {
                manualShiftRequestPending = false;
            }
        });
    }



    // Generic DataTable initializer / reload helper
    function initOrReloadDataTable(selector, opts) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().ajax.reload(null, false);
            return;
        }
        // merge default settings
        const defaults = {
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            fnDrawCallback: function () { }
        };
        $(selector).DataTable($.extend(true, {}, defaults, opts));
    }

    // ---------- Toggle tab behaviour ----------
    function setTabToOtherIncome() {
        $belowBox.removeClass('hide');
        $('.direct-settlement-main-tabs > .nav-tabs > li').removeClass('active');
        $('.direct-settlement-main-tabs > .tab-content > .tab-pane').removeClass('active in show');
        $('.direct-settlement-main-tabs > .nav-tabs .other_income_tab').closest('li').addClass('active');
        $('#other_income_tab').addClass('active in show').show();
    }

    function toggle_check_operator_shift_status(e) {
        if ($shiftClosed.val() !== "yes") {
            e.preventDefault();
            toastError("Operator shift not closed.");
            setTimeout(setTabToOtherIncome, 1000);
            return false;
        }
        $belowBox.removeClass('hide');
        return true;
    }

    // Attach tab click handlers (only block when shift_closed isn't 'yes')
    $doc.on('click', '.settlement_tabs .nav-tabs a.meter_sale_tab, .settlement_tabs .nav-tabs a.other_sale_tab', function (e) {
        if ($shiftClosed.val() !== "yes") {
            return toggle_check_operator_shift_status(e);
        }

        if ($(this).hasClass('other_sale_tab')) {
            setTimeout(function () {
                loadOtherSalesData();
            }, 0);
        }
    });

    // Button-based top-level tabs bypass the conflicting global anchor
    // permission handler. Preserve the original per-tab calculations/data load.
    $doc.off('petrodirect:direct-settlement-tab-shown.directSettlement')
        .on('petrodirect:direct-settlement-tab-shown.directSettlement', function (e, selector) {
            if (selector === '#other_sale_tab') {
                loadOtherSalesData();
            }

            if (selector === '#payment_tab') {
                calculate_payment_tab_total();
            }
        });

    // ---------- Update settlement (AJAX) ----------
    async function handleFieldChanges() {
        const pumpOperator = $pumpOperator.val();
        const workShift = $workShift.val();
        const shift_numbers = getSelectedShiftIds();

        // Pump operator is required; workShift is optional (will be auto-set after shift loads)
        if (!pumpOperator || pumpOperator === "") {
            return;
        }
        if (pumpOperator && shift_numbers.length > 0) {
            // persist current fields locally
        } else {
            console.log('pump_operator_id or shift_number is empty');
        }

        const url = "/petrodirect/settlement/" + ($activeSettlement.val() || '0');
        const directShiftPayload = getDirectShiftPayload(pumpOperator);

        try {
            const result = await apiPut(url, {
                note: $note.val(),
                work_shift: workShift,
                direct_shift_number: directShiftPayload.number,
                direct_shift_operator_id: directShiftPayload.operatorId,
                transaction_date: $transactionDate.val(),
                pump_operator_id: pumpOperator,
                location_id: $location.val()
            });

            if (result && result.success == 1) {
                if (result.optionHtml) {
                    toastSuccess(result.msg);
                }
                $('#shift_number').html(result.optionHtml || '');
                initShiftNumberSelect2();
                // Only auto-select first if server didn't already mark one as selected
                if ($("#shift_number option").length >= 1 && $("#shift_number option[selected]").length === 0) {
                    $("#shift_number option:first").prop("selected", true);
                }
                // Sync select2 with the selected option(s) from server HTML
                var preSelected = $("#shift_number option[selected]").map(function() { return $(this).val(); }).get();
                if (preSelected.length > 0) {
                    $('#shift_number').val(preSelected[0]).trigger('change.select2');
                }
                if (result.settlement_id) {
                    $activeSettlement.val(result.settlement_id);
                    $activeSettlementStatus.val('1');
                    directSettlementByOperator[pumpOperator] = result.settlement_id;
                    window.__directSettlementByOperator[pumpOperator] = result.settlement_id;
                }
                if (result.settlement_no) {
                    $('#settlement_no').val(result.settlement_no);
                }
                if (result.settlement_id && window.history && window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('view_settlement_id', result.settlement_id);
                    window.history.replaceState({}, '', url.toString());
                }
                rememberSelectedDirectShift(pumpOperator);
                if (result.pump_nos) {
                    updatePumpDropdown(result.pump_nos || {});
                }
                $shiftClosed.val("yes");
                if ($("#shift_number option").length >= 1) {
                    toggleManualShiftInput(false);
                    $belowBox.removeClass('hide');
                    $('#shift_number').trigger('change');
                } else {
                    toggleManualShiftInput(false);
                    $belowBox.addClass('hide');
                    toastError("No shift number could be generated for this operator.");
                }
            } else {
                toastError(result.msg || "Unable to update settlement.");
                $('#shift_number').empty();
                initShiftNumberSelect2();
                toggleManualShiftInput(false);
                $belowBox.addClass('show');
                $shiftClosed.val("yes");
                // $('#meter_sale_tab').addClass('active show');
                $('#outside_meter_sale_table').hide();
                $('#meter_sale_table').show();
                fetchOtherSales();
            }
        } catch (err) {
            console.error("API Error:", err);
            toastError("An error occurred while updating settlement.");
        }




    }

    function refreshDirectSettlementStores(locationId) {
        var $store = $('#store_id');
        if (!$store.length || !locationId) {
            return;
        }

        $.ajax({
            method: 'GET',
            url: '/petrodirect/settlement/get-stores-by-id',
            dataType: 'html',
            data: {
                location_id: locationId,
                current_store_id: $store.val() || $store.data('default-store-id') || ''
            },
            success: function (html) {
                $store.empty().append(html);

                var selectedStoreId = $store.find('option:selected').val()
                    || $store.find('option[value!=""]:first').val()
                    || '';

                $store.val(selectedStoreId).data('default-store-id', selectedStoreId);
                $store.trigger('change.select2');

                $('#item').val(null).trigger('change.select2');
                $('#balance_stock, #other_sale_price').val('');
            },
            error: function () {
                toastError('Unable to load stores for the selected location.');
            }
        });
    }

    // wire handlers for field changes — only after page init is complete
    let pageInitialized = false;
    $doc.on('change', '#note, #work_shift, #transaction_date, #pump_operator_id, #location_id', function (e) {
        if (!pageInitialized) return;

        if (e && e.target && (e.target.id === 'pump_operator_id' || e.target.id === 'location_id')) {
            pumpOptionsRequestNo++;
            if (pumpOptionsXhr && pumpOptionsXhr.readyState !== 4) pumpOptionsXhr.abort();
            $('#pump_no').removeAttr('data-selected-pump-id data-selected-pump-text data-selected-pump-context');
            $('#meter_sale_selected_pump_id, #meter_sale_selected_pump_text, #meter_sale_selected_pump_context').val('');
            syncPumpSelect2($('#pump_no'), '');
        }

        if (e && e.target && e.target.id === 'pump_operator_id') {
            const activeSettlementIsDraft = $activeSettlementStatus.val() === '1';
            const carryCurrentDirectDraft = activeSettlementIsDraft
                && $activeSettlement.val()
                && $activeSettlement.val() !== '0'
                && !!getSelectedDirectShiftNumber();

            if (currentPumpOperatorId && activeSettlementIsDraft && $activeSettlement.val() && $activeSettlement.val() !== '0') {
                directSettlementByOperator[currentPumpOperatorId] = $activeSettlement.val();
                rememberSelectedDirectShift(currentPumpOperatorId);
            }

            currentPumpOperatorId = $pumpOperator.val() || '';

            if (currentPumpOperatorId) {
                restoreDirectSettlementControlsForOperator();
            } else {
                lockDirectSettlementControlsWithoutOperator();
            }

            const mappedSettlementId = currentPumpOperatorId
                ? (carryCurrentDirectDraft ? $activeSettlement.val() : (directSettlementByOperator[currentPumpOperatorId] || '0'))
                : '0';
            $activeSettlement.val(mappedSettlementId);
            $activeSettlementStatus.val(mappedSettlementId !== '0' ? '1' : '');
            $('#settlement_no').val('');
            resetMeterSaleTablesForContext();
        }

        /*
         * S703: the pump load runs AFTER handleFieldChanges().
         *
         * It used to run before it. requestPumpOptions() discards any response
         * whose context no longer matches:
         *
         *     if (requestNo !== pumpOptionsRequestNo
         *         || requestContext !== getPumpSelectionContext()) return;
         *
         * handleFieldChanges() is async and changes that context - so the request
         * went out, the reply came back, and the guard threw it away as stale.
         * The dropdown stayed empty until the page was reloaded, which is exactly
         * the reported behaviour: "shows once refreshed".
         *
         * Running it afterwards means the context has settled before the request
         * is made, so the response is accepted.
         *
         * Promise.resolve() wraps it so this works whether handleFieldChanges
         * returns a promise or not.
         */
        Promise.resolve(handleFieldChanges()).then(function () {
            /*
             * S691: load the Pump dropdown as soon as an operator is chosen.
             *
             * The pump list was only ever requested from the '#shift_number'
             * change handler. That works for a normal shift settlement, where
             * picking a shift is the next step - but BULK SALE has no work
             * shift, so that handler never fired and the Pump dropdown stayed
             * on "Please Select" until the page was reloaded.
             *
             * The request is made here too, from the operator change itself, so
             * it no longer depends on a shift being selected.
             *
             * active_settlement_id is sent for the same reason the shift handler
             * sends it: the server drops pumps already added to this settlement,
             * which is what stops a pump appearing twice - and it keeps working
             * after a refresh, because the exclusion is decided server-side
             * rather than by hiding options in the browser.
             */
            if (currentPumpOperatorId) {
                const s691Shift = getSelectedShiftIds();

                requestPumpOptions('/petrodirect/settlement/get_pumps/' + currentPumpOperatorId, {
                    shift_number: s691Shift.length ? s691Shift[0] : 0,
                    location_id: $location.val(),
                    active_settlement_id: $activeSettlement.val() || 0
                });
            }
        });

        // Pump and Main Store defaults are scoped by business location.
        if (e && e.target && e.target.id === 'location_id') {
            refreshDirectSettlementStores($(e.target).val());
            loadAssignedPumps();
        }
    });

    // persist small changes when there's no active settlement
    // if (!hasActiveSettlement) {
    //     $doc.on('change', '#pump_no, #pump_starting_meter, #sold_qty, #meter_sale_unit_price, #testing_qty, #meter_sale_discount_type, #meter_sale_discount', () =>
    //         persistLocalUpdate(buildPersistableData())
    //     );
    // }
    // let persistTimer;



    // ---------- Shift number change handler ----------
    $doc.on('keydown', '#manual_shift_number', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            createManualShiftNumber($(this).val());
        }
    });

    $doc.on('blur', '#manual_shift_number', function () {
        if ($(this).val()) {
            createManualShiftNumber($(this).val());
        }
    });

    $doc.on('select2:select', '#shift_number', function (e) {
        const selectedData = (e.params && e.params.data) ? e.params.data : {};
        if (!selectedData.newTag) return;

        const manualShiftNumber = normalizeManualShiftNumber(selectedData.id);
        if (!manualShiftNumber) {
            toastError('Manual shift number can contain only letters, numbers, dash, and underscore.');
            $shiftNumber.find(`option[value="${selectedData.id}"]`).remove();
            $shiftNumber.val(null).trigger('change.select2');
            return;
        }

        if (!$pumpOperator.val()) {
            toastError('Please select a pump operator before entering a manual shift number.');
            $shiftNumber.find(`option[value="${selectedData.id}"]`).remove();
            $shiftNumber.val(null).trigger('change.select2');
            return;
        }

        $.ajax({
            method: 'POST',
            url: manualShiftUrl,
            dataType: 'json',
            data: {
                shift_number: manualShiftNumber,
                pump_operator_id: $pumpOperator.val(),
                transaction_date: $transactionDate.val(),
                location_id: $location.val(),
                work_shift: $workShift.val()
            },
            success: function (result) {
                if (!result || !result.success) {
                    toastError((result && result.msg) ? result.msg : 'Unable to create manual shift number.');
                    $shiftNumber.find(`option[value="${selectedData.id}"]`).remove();
                    $shiftNumber.val(null).trigger('change.select2');
                    return;
                }

                const $tempOption = $shiftNumber.find(`option[value="${selectedData.id}"]`);
                $tempOption.val(result.shift_id).text(result.shift_number);
                $shiftNumber.val(result.shift_id).trigger('change.select2');
                toastSuccess(result.msg || 'Manual shift number assigned.');
                $belowBox.removeClass('hide');
                $shiftNumber.trigger('change');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Unable to create manual shift number.';
                toastError(msg);
                $shiftNumber.find(`option[value="${selectedData.id}"]`).remove();
                $shiftNumber.val(null).trigger('change.select2');
            }
        });
    });

    $doc.on('change', '#shift_number', async function () {
        if (window.__directSettlementPumpSyncing) return;
        const previousContext = String($('#meter_sale_selected_pump_context').val() || $('#pump_no').attr('data-selected-pump-context') || '');
        const shift_numbers = getSelectedShiftIds();
        const newContext = getPumpSelectionContext();
        if (previousContext && previousContext !== newContext) {
            pumpOptionsRequestNo++;
            if (pumpOptionsXhr && pumpOptionsXhr.readyState !== 4) pumpOptionsXhr.abort();
            $('#pump_no').removeAttr('data-selected-pump-id data-selected-pump-text data-selected-pump-context');
            $('#meter_sale_selected_pump_id, #meter_sale_selected_pump_text, #meter_sale_selected_pump_context').val('');
            updatePumpDropdown({}, { preserveSelection: false });
        }
        let shift_id = shift_numbers.length ? shift_numbers[0] : null;

        if (!shift_id) {
            $belowBox.addClass('hide');
            return;
        }

        // check unsettled previous shifts (do not block Other Sale refresh)
        const ok = String(shift_id) === '0' ? true : await checkPreviousUnsettled(shift_id);

        // toggle below_box visibility
        $belowBox.toggleClass('hide', shift_numbers.length === 0);

        // build readable label list
        const labels = (shift_numbers || []).map(id => $shiftNumber.find(`option[value="${id}"]`).text()).filter(Boolean);
        $('.shift_number').html(labels.join(', '));

        // Enable/disable Payment to Finalize button based on shift selection.
        // (Button may be disabled by other scripts via `#below_box *` disabling.)
        const enableFinalize = shift_numbers.length > 0 && !!$pumpOperator.val() && ok;
        $('#add_payment').prop('disabled', !enableFinalize).toggleClass('disabled', !enableFinalize);

        if (isDirectSettlementShiftSelected()) {
            // DST shifts are Direct Settlement internal labels, not Pumper Dashboard shifts.
            // IMPORTANT: Do NOT clear #meter_sale_table here. On page refresh the controller
            // already renders saved draft meter-sale rows into the table; clearing here makes
            // them show for a second and then vanish.
            $('#outside_meter_sale_table').hide();
            $('#meter_sale_table').show();
            $('#shift_operator_other_sale_total').val(0);
            $('#other_sale_table tbody tr[data-source="pumper"]').remove();
            updateOtherSaleVisibleTotal();
            calculate_payment_tab_total();
            return;
        }
        requestPumpOptions('/petrodirect/settlement/get_pumps/' + $pumpOperator.val(), {
            shift_number: shift_id,
            location_id: $location.val(),
            // S676: send the numeric settlement id so the server can drop pumps
            // already added to this Direct Settlement.
            active_settlement_id: $activeSettlement.val() || 0
        });

        // Meter sale list should always follow selected shift + operator.
        loadMeterSalesData();

        if ($('#other_sale_tab').hasClass('active')) {
            loadOtherSalesData();
        } else {
            fetchOtherSales();
        }
    });

    // ---------- Pump No change handler ----------
    $doc.on('change', '#pump_no', function () {
        if (window.__directSettlementPumpSyncing) return;
        if (!pageInitialized) return;
        const pumpId = $('#pump_no').val();

        /*
         * S696: fill the Meter Sales Unit Price when a pump is chosen.
         *
         * This is the actual reason the Unit Price was wrong: nothing ever set
         * it. For a NEW meter sale, meter_sale_form.blade.php initialises
         * $meter_sale_unit_price = null, and no script wrote to
         * #meter_sale_unit_price - it is read in three places and never
         * assigned. So the field kept whatever was last in it, or nothing.
         *
         * Earlier attempts corrected the price QUERIES in ProvidesPdLookups and
         * AddPaymentController. Those were real defects and worth fixing, but
         * they feed Other Sale and Credit Sale - not this field. Correcting a
         * value that was never read could not change what the user saw.
         *
         * get-pump-details already returns the product with default_sell_price
         * built from the same coalesce Products New uses
         * (sell_price_inc_tax -> default_sell_price -> 0), so the figure here now
         * matches the product list by construction.
         *
         * Only filled when the field is EMPTY or zero, so a price the user has
         * deliberately typed is never overwritten.
         */
        if (pumpId) {
            $.ajax({
                method: 'get',
                url: '/petrodirect/settlement/get-pump-details/' + pumpId,
                success: function (result) {
                    if (!result || !result.success || !result.product) {
                        return;
                    }

                    var listPrice = parseFloat(
                        String(result.product.default_sell_price || '0').replace(/,/g, '')
                    );

                    if (!isFinite(listPrice) || listPrice <= 0) {
                        return;
                    }

                    var $unitPrice = $('#meter_sale_unit_price');
                    var current = parseFloat(String($unitPrice.val() || '0').replace(/,/g, ''));

                    if (!isFinite(current) || current <= 0) {
                        $unitPrice.val(listPrice.toFixed(__currency_precision || 2)).trigger('change');
                    }
                }
            });
        }

        // Other Sales is scoped by selected shift/operator, with pump as an optional filter.
        loadOtherSalesData();
    });

    // ---------- Data loading functions ----------
    function loadOtherSalesData() {
        $('#outside_other_sale_table').hide();
        $('#other_sale_table').show();

        if (getSelectedShiftIds().length === 0) {
            $('#shift_operator_other_sale_total').val(0);
            updateOtherSaleVisibleTotal();
            calculate_payment_tab_total();
            return;
        }

        fetchOtherSales();
    }


    function fetchOtherSales() {
        // IS1861: Direct Settlement owns only rows entered in this Direct draft.
        // Never hydrate this page from Pumper Dashboard/PetroPD endpoints.
        $('#shift_operator_other_sale_total').val(0);
        $('#other_sale_table tbody tr[data-source="pumper"]').remove();
        updateOtherSaleVisibleTotal();
        calculate_payment_tab_total();
    }

    function updateOtherSaleVisibleTotal() {
        var manualTotal = parseFloat(($('#other_sale_total').val() || '0').toString().replace(/,/g, '')) || 0;
        var pumperTotal = parseFloat(($('#shift_operator_other_sale_total').val() || '0').toString().replace(/,/g, '')) || 0;
        $('.other_sale_total').text(__number_f(manualTotal + pumperTotal, false, false, __currency_precision));
    }

    function renderPumperOtherSalesRows(rows) {
        var total = 0;
        var html = '';

        $('#other_sale_table tbody tr[data-source="pumper"]').remove();

        rows.forEach(function (row) {
            var afterDiscount = getDataOrigValue(row.with_discount);
            total += afterDiscount;

            html += '<tr data-source="pumper">' +
                '<td>' + escapeHtml(row.product_sku || '') + '</td>' +
                '<td>' + escapeHtml(row.product_name || '') + '</td>' +
                '<td>' + escapeHtml(stripHtml(row.qty_available || '')) + '</td>' +
                '<td>' + escapeHtml(stripHtml(row.price || '')) + '</td>' +
                '<td>' + escapeHtml(stripHtml(row.quantity || row.qty || '')) + '</td>' +
                '<td>' + escapeHtml(row.discount_type || '') + '</td>' +
                '<td>' + escapeHtml(stripHtml(row.discount || '')) + '</td>' +
                '<td>' + escapeHtml(stripHtml(row.sub_total || '')) + '</td>' +
                '<td>' + escapeHtml(stripHtml(row.with_discount || '')) + '</td>' +
                '<td></td>' +
                '</tr>';
        });

        if (html) {
            $('#other_sale_table tbody').append(html);
        }

        return total;
    }

    function getDataOrigValue(value) {
        var rawValue = value == null ? '' : value;
        var $html = $('<div>').html(rawValue);
        var orig = $html.find('[data-orig-value]').first().data('orig-value');
        if (orig !== undefined) {
            return parseFloat(orig) || 0;
        }

        return parseFloat(stripHtml(rawValue).replace(/,/g, '')) || 0;
    }

    function stripHtml(value) {
        return $('<div>').html(value == null ? '' : value).text();
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }


    function loadMeterSalesData() {
        // IS1861: this is always the Petro Direct screen.  The controller-rendered
        // draft table is authoritative; Pumper Dashboard DataTables are forbidden.
        $('#outside_meter_sale_table').hide();
        $('#meter_sale_table').show();
    }




    // ---------- Modal loaders & buttons ----------
    $doc.off('click', '#add_payment').on('click', '#add_payment', function (e) {
        e.preventDefault();   // stop default btn-modal load
        e.stopPropagation();  // stop bubbling

        console.log('clicked once custom handler only');

        const baseUrl = $(this).data('href') || '';
        const shiftIds = getSelectedShiftIds();

        if (!shiftIds.length) {
            toastError('Please select or enter the Shift No first.');
            return false;
        }

        const paymentUrl = new URL(baseUrl, window.location.origin);
        const currentSettlementNo = $('#settlement_no').val() || $('.settlement_no').first().text().trim();
        if (currentSettlementNo) {
            paymentUrl.searchParams.set('settlement_no', currentSettlementNo);
        }
        paymentUrl.searchParams.set('operator_id', $pumpOperator.val() || '');
        paymentUrl.searchParams.set('pump_operator_id', $pumpOperator.val() || '');
        paymentUrl.searchParams.set('shift_ids', shiftIds.join(','));
        paymentUrl.searchParams.set('transaction_date', $transactionDate.val() || '');
        paymentUrl.searchParams.set('location_id', $location.val() || '');
        paymentUrl.searchParams.set('active_settlement_id', $activeSettlement.val() || '0');

        const url = paymentUrl.pathname + paymentUrl.search;

        $('.add_payment').load(url, function () {
            $('.add_payment').modal({ backdrop: 'static', keyboard: false });

            setTimeout(() => {
                const firstValue = $('#credit_sale_customer_id option:first').val();
                if (firstValue) loadCustomerDetails(firstValue);
            }, 800);
        });
    });

    // Function to load customer details (defined here to prevent ReferenceError)
    function loadCustomerDetails(customerId) {
        if (!customerId) return;

        $.ajax({
            method: "GET",
            url: "/petrodirect/settlement/payment/get-customer-details/" + customerId,
            data: {},
            success: function (result) {
                // Update outstanding and credit limit
                $(".current_outstanding").text(result.total_outstanding);
                $(".credit_limit").text(result.credit_limit);

                // Enable fields if manual bill settlement
                if (result.manual_bill_settlement == 1) {
                    $('.unit_discount').prop('disabled', false);
                    $('.credit_total_amount').prop('disabled', false);
                    $('.credit_discount_amount').prop('disabled', false);

                    // Set hidden input to 1
                    $('#total_amount_enable').val(1);
                }

                // Clear existing options
                $("#customer_reference").empty();

                if (result.customer_references && result.customer_references.length > 0) {
                    // Add "Please Select"
                    $("#customer_reference").append('<option value="">Please Select</option>');

                    // Add customer references
                    result.customer_references.forEach(function (ref) {
                        $("#customer_reference").append('<option value="' + ref.reference + '">' + ref.reference + '</option>');
                    });
                } else {
                    $("#customer_reference").append('<option value="">No references available</option>');
                }
                $("#customer_reference").trigger('change');
            },
            error: function (xhr, status, error) {
                console.error('Error loading customer details:', error);
                toastr.error('Error loading customer details');
            }
        });
    }



    $doc.on('click', '#payment_review_btn, #product_preview_btn', function () {
        const url = $(this).data('href');
        $('.preview_settlement').load(url, function () {
            $('.preview_settlement').modal({ backdrop: 'static', keyboard: false });
        });
    });

    // bulk tank change
    $doc.on('change', '#bulk_tank', function () {
        const tank_id = $(this).val();
        if (!tank_id) return;
        apiGet("{{ action('\Modules\PetroDirect\Http\Controllers\FuelTankController@getTankProduct') }}/" + tank_id)
            .done(result => {
                const html = `<option value="">Please Select</option><option value="${result.id}">${result.name}</option>`;
                $('#item').empty().append(html);
            })
            .fail(() => toastError("Unable to fetch tank product"));
    });

    // Show/hide bulk_tank fields
    $doc.on('ifChecked', '#show_bulk_tank', function () {
        $('.store_field').addClass('hide');
        $('.bulk_tank_field').removeClass('hide');
    }).on('ifUnchecked', '#show_bulk_tank', function () {
        $('.store_field').removeClass('hide');
        $('.bulk_tank_field').addClass('hide');
    });

    // Save other income edit
    $doc.on('click', '#save_edit_price_other_income_btn', function () {
        const edit_price = $('#other_income_edit_price').val() || 0;
        $('#other_income_price').val(edit_price);
        $('#other_income_edit_price').val('0');
        $('#edit_price_other_income').modal('hide');
    });

    // ---------- Initialization on ready ----------
    $(function () {
        // Clear any stale localStorage data — filters drive data loading, not localStorage
        localStorage.removeItem('lastUpdateData');

        // datepickers & select2 & datetimepickers initialization
        /*
         * S693: keep the Transaction Date the user chose across a refresh.
         *
         * This line ran on every page load and, with no active settlement,
         * called setDate(new Date()) - overwriting whatever date had been
         * selected. So the field silently reset to today on every refresh.
         *
         * The date IS persisted (it is sent with the settlement on change), but
         * this ran regardless and replaced it.
         *
         * Now:
         *   - an active settlement supplies its own saved date, as before
         *   - otherwise the last chosen date is restored from sessionStorage
         *   - only when neither exists does it fall back to today
         *
         * sessionStorage rather than localStorage: the choice belongs to this
         * browser tab and this working session. It should not still be sitting
         * there tomorrow, quietly setting a stale date on a new settlement.
         */
        @if (!empty($active_settlement))
            $('.transaction_date').datepicker("setDate", "{{ \Carbon::parse($active_settlement->transaction_date)->format('m/d/Y') }}");
        @else
            (function () {
                var remembered = null;

                try {
                    remembered = sessionStorage.getItem('petrodirect_transaction_date');
                } catch (e) {
                    // Storage can be unavailable in private modes - fall through.
                    remembered = null;
                }

                $('.transaction_date').datepicker('setDate', remembered ? remembered : new Date());
            }());
        @endif

        // Remember the chosen date so a refresh does not discard it.
        $doc.on('change.s693TxnDate', '.transaction_date', function () {
            try {
                sessionStorage.setItem('petrodirect_transaction_date', $(this).val() || '');
            } catch (e) {
                // Not fatal - the date simply will not survive a refresh.
            }
        });
    $('#customer_payment_cheque_date').datepicker("setDate", new Date());
    $('#location_id, #item, #store_id, #bulk_tank, #pump_operator_id, #work_shift, #card_customer_id, #customer_payment_customer_id').select2();
    initShiftNumberSelect2();
    $('#shif_time_in, #shif_time_out').datetimepicker({ format: 'LT' });
    $('#settlement_print').css('visibility', 'hidden');

    // Allow change handlers to fire only after all init is complete
    pageInitialized = true;

    // Start blank unless a specific settlement/operator was explicitly loaded.
    if (!$pumpOperator.val()) {
        $belowBox.addClass('hide');
        $('#add_payment').prop('disabled', true).addClass('disabled');
        $('#outside_meter_sale_table').hide();
        $('#meter_sale_table').show();
        resetMeterSaleTablesForContext();
    }

    // On page load: if pump operator is already selected by an explicit settlement,
    // populate its Shift No dropdown.
    if ($pumpOperator.val() && $pumpOperator.val() !== '') {
        if ($activeSettlementStatus.val() === '1') {
            handleFieldChanges();
        }
        ensureInitialPumpLoad();
        loadMeterSalesData();
    }
    });

    // Handle delayed hydration/cached navigations where values appear after DOM ready.
    $window.on('load pageshow', function () {
        if ($pumpOperator.val() && $pumpOperator.val() !== '') {
            ensureInitialPumpLoad();
        }
    });

}) ();

// ---------- Direct Settlement Create: meter sale add & update (isolated so table refreshes from server table_html) ----------
// Unbind shared handlers and use page-specific ones so list always refreshes same way (table_html + DataTable.reload)
(function () {
    if (!$('#meter_sale_table').length) return;

    var $doc = $(document);
    var saveMeterSaleUrl = '/petrodirect/settlement/save-meter-sale';

    function getCurrentShiftIdValue() {
        var value = $('#shift_number').val();
        return Array.isArray(value) ? (value || []).join(',') : (value || '').toString();
    }

    function getCurrentDirectShiftNumber() {
        return typeof window.getSelectedDirectSettlementShiftNumber === 'function'
            ? window.getSelectedDirectSettlementShiftNumber()
            : '';
    }

    // Shared: apply server result (table_html + DataTable.reload + totals). Used by both Add and Update.
    function applyMeterSaleSuccessResult(result, pump_id) {
        $('.meter_sale_fields').val('');
        $('.testing_qty').val(0);
        $('#pump_no').removeAttr('data-selected-pump-id data-selected-pump-text data-selected-pump-context');
        $('#meter_sale_selected_pump_id, #meter_sale_selected_pump_text, #meter_sale_selected_pump_context').val('');
        window.__directSettlementPumpSyncing = true;
        try {
            $('#pump_no').val('').trigger('change');
        } finally {
            window.__directSettlementPumpSyncing = false;
        }

        // S676: once a pump is added to this Direct Settlement it must not be
        // selectable again. Prefer the authoritative server-filtered list; the
        // fallback removes the just-added option immediately for older responses.
        if (result && result.pump_nos && typeof updatePumpDropdown === 'function') {
            updatePumpDropdown(result.pump_nos, { preserveSelection: false });
        } else if (pump_id) {
            $('#pump_no option[value="' + pump_id + '"]').remove();
            $('#pump_no').val('').trigger('change.select2');
        }

        var tableHtml = (result && result.table_html) ? result.table_html : '';
        if (typeof tableHtml === 'string' && tableHtml.length > 0) {
            var $wrap = $('<div>').append($.parseHTML(tableHtml));
            var $newTable = $wrap.find('table#meter_sale_table');
            if ($newTable.length) {
                var newTbody = $newTable.children('tbody').html();
                var newTfoot = $newTable.children('tfoot').html();
                if (newTbody != null) $('#meter_sale_table').children('tbody').html(newTbody);
                if (newTfoot != null) $('#meter_sale_table').children('tfoot').html(newTfoot);
                var $totalInput = $newTable.find('input#meter_sale_total');
                if ($totalInput.length && $totalInput.val()) {
                    $('#meter_sale_total').val($totalInput.val());
                    if (typeof calculate_payment_tab_total === 'function') calculate_payment_tab_total();
                }

                // Show the static table immediately (no loading spinner) and hide the DataTable wrapper
                $('#outside_meter_sale_table').hide();
                $('#meter_sale_table').show();
            }
        }

        // Silently sync the DataTable in the background so it stays up-to-date if re-shown
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
            $('#pump_operator_meter_sale_table').DataTable().ajax.reload(null, false);
        }
        if ($('#meter_sale_table').length && typeof sum_table_col === 'function') {
            var total = sum_table_col($('#meter_sale_table'), 'discount_amount');
            if (!isNaN(total)) {
                $('#meter_sale_total').val(total);
                if (typeof calculate_payment_tab_total === 'function') calculate_payment_tab_total();
            }
        }
        if (typeof refresh_settlement_totals === 'function') refresh_settlement_totals();
        if (typeof updateTotalSoldQty === 'function') updateTotalSoldQty();
    }

    $doc.off('click', '.btn_update_meter_sale');
    $doc.on('click', '.btn_update_meter_sale', function () {
        var url = $(this).data('href');
        var tr = (typeof __meter_sale_edit_tr !== 'undefined' && __meter_sale_edit_tr) ? __meter_sale_edit_tr : $(this).closest('tr');
        var is_edit = $('#is_edit').val() || 0;
        var pump_id = $('#pump_no').val();
        var form_start = $('#pump_starting_meter').val();
        var form_close = $('#pump_closing_meter').val();
        var form_price = $('#meter_sale_unit_price').val();
        var testing_qty = $('#testing_qty').val() || 0;
        // Sold Qty field is already chargeable (Closing - Starting - Testing); do not subtract testing again
        var sold_qty = parseFloat($('#sold_qty').val()) || 0;
        var total_qty = sold_qty + (parseFloat(testing_qty) || 0);
        var meter_sale_discount = $('#meter_sale_discount').val() || 0;
        var meter_sale_discount_type = $('#meter_sale_discount_type').val() || 'fixed';
        var price = (typeof price !== 'undefined') ? price : parseFloat($('#meter_sale_unit_price').val() || 0);
        var sub_total = parseFloat(sold_qty) * parseFloat(price);
        var meter_sale_discount_amount = sub_total - (typeof calculate_discount === 'function' ? calculate_discount(meter_sale_discount_type, meter_sale_discount, sub_total) : 0);

        $.ajax({
            method: 'post',
            url: url,
            dataType: 'json',
            data: {
                pump_id: pump_id,
                starting_meter: form_start,
                closing_meter: form_close,
                product_id: $('#meter_sale_product_id').val() || (typeof product_id !== 'undefined' ? product_id : ''),
                price: form_price,
                qty: sold_qty,
                discount: meter_sale_discount,
                discount_type: meter_sale_discount_type,
                discount_amount: meter_sale_discount_amount,
                testing_qty: testing_qty,
                sub_total: sub_total,
                is_edit: is_edit,
                is_from_pumper: $('#is_from_pumper').val() || 0,
                assignment_id: $('#assignment_id').val() || 0,
                pumper_entry_id: $('#pumper_entry_id').val() || 0,
                mechanical_last_meter: $('#mechanical_last_meter').val() || '',
                mechanical_digital_last_meter: $('#mechanical_digital_last_meter').val() || '',
                mechanical_meter_difference: $('#mechanical_meter_difference').val() || '',
                shift_id: getCurrentShiftIdValue()
            },
            success: function (result) {
                if (!result || !result.success) {
                    toastr.error((result && result.msg) ? result.msg : 'Update failed');
                    return;
                }
                toastr.success(result.msg);
                if (typeof __meter_sale_edit_tr !== 'undefined') __meter_sale_edit_tr = null;
                applyMeterSaleSuccessResult(result, pump_id);
            }
        });
    });

    // LA-1091 urgent correction: one authoritative Meter Sale Add handler.
    // It uses a relative URL so the same code works on central and tenant domains.
    $doc.off('click', '.btn_meter_sale');
    $doc.on('click.la1091_meter_sale_add', '.btn_meter_sale', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var $button = $(this);
        if ($button.data('meterSaleSubmitting') || window.isMeterSaleSubmitting) {
            return false;
        }

        function numberValue(value) {
            var parsed = parseFloat((value == null ? '' : value).toString().replace(/,/g, ''));
            return isNaN(parsed) ? 0 : parsed;
        }

        function resetMeterSaleSubmitState() {
            window.isMeterSaleSubmitting = false;
            $button.removeData('meterSaleSubmitting')
                .prop('disabled', false)
                .removeAttr('disabled')
                .removeClass('disabled');

            if (typeof window.refreshPetroMeterSaleAddState === 'function') {
                window.refreshPetroMeterSaleAddState();
            }
        }

        var location_id = $('#location_id').val();
        var pump_operator_id = $('#pump_operator_id').val();
        var pump_id = $('#pump_no').val();
        var starting_meter = numberValue($('#pump_starting_meter').val());
        var closing_meter = numberValue($('#pump_closing_meter').val());
        var testing_qty = Math.abs(numberValue($('#testing_qty').val()));
        var sold_qty = Math.abs(numberValue($('#sold_qty').val()));
        var selected_price = Math.abs(numberValue($('#meter_sale_unit_price').val()));
        var selected_product_id = $('#meter_sale_product_id').val()
            || (typeof product_id !== 'undefined' ? product_id : '');
        var meter_sale_discount = Math.abs(numberValue($('#meter_sale_discount').val()));
        var meter_sale_discount_type = $('#meter_sale_discount_type').val() || 'fixed';
        var sub_total = sold_qty * selected_price;
        var discount_value = 0;
        var shift_id = getCurrentShiftIdValue();
        var isBulkSaleMeter = String($('#bulk_sale_meter').val() || '0') === '1';

        if (meter_sale_discount_type === 'percentage') {
            discount_value = sub_total * (meter_sale_discount / 100);
        } else {
            discount_value = meter_sale_discount;
        }
        var meter_sale_discount_amount = Math.max(0, sub_total - discount_value);

        if (!$('#meter_sale_discount_type').val()) {
            $('#meter_sale_discount_type').val('fixed').trigger('change.select2');
        }

        if (!location_id) {
            toastr.error('Please select the Business Location first.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (!pump_operator_id) {
            toastr.error('Please select the Pump Operator first.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (!shift_id) {
            toastr.error('Please select or enter the Shift No first.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (!pump_id) {
            toastr.error('Please select the Pump No first.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (!isBulkSaleMeter && closing_meter < starting_meter) {
            toastr.error('Pump Closing Meter must be greater than or equal to the Starting Meter.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (selected_price <= 0) {
            toastr.error('The selected pump price is not loaded yet. Please select the pump again.');
            resetMeterSaleSubmitState();
            return false;
        }
        if (sold_qty <= 0) {
            toastr.error('Sold Qty must be greater than zero.');
            resetMeterSaleSubmitState();
            return false;
        }

        $button.data('meterSaleSubmitting', true).prop('disabled', true).addClass('disabled');
        window.isMeterSaleSubmitting = true;

        $.ajax({
            method: 'POST',
            url: saveMeterSaleUrl,
            dataType: 'json',
            timeout: 60000,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
            },
            data: {
                _token: $('meta[name="csrf-token"]').attr('content') || '',
                active_settlement_id: $('#active_settlement_id').val(),
                settlement_no: $('#settlement_no').val(),
                location_id: location_id,
                pump_operator_id: pump_operator_id,
                transaction_date: $('#transaction_date').val(),
                work_shift: $('#work_shift').val(),
                direct_shift_number: getCurrentDirectShiftNumber(),
                note: $('#note').val(),
                pump_id: pump_id,
                starting_meter: starting_meter,
                closing_meter: isBulkSaleMeter ? '' : closing_meter,
                product_id: selected_product_id,
                price: selected_price,
                qty: sold_qty,
                discount: meter_sale_discount,
                discount_type: meter_sale_discount_type,
                discount_amount: meter_sale_discount_amount,
                testing_qty: testing_qty,
                sub_total: sub_total,
                is_edit: $('#is_edit').val() || 0,
                is_from_pumper: $('#is_from_pumper').val() || 0,
                assignment_id: $('#assignment_id').val() || 0,
                pumper_entry_id: $('#pumper_entry_id').val() || 0,
                mechanical_last_meter: $('#mechanical_last_meter').val() || '',
                mechanical_digital_last_meter: $('#mechanical_digital_last_meter').val() || '',
                mechanical_meter_difference: $('#mechanical_meter_difference').val() || '',
                shift_id: shift_id
            },
            success: function (result) {
                if (!result || !result.success) {
                    toastr.error((result && (result.msg || result.message))
                        ? (result.msg || result.message)
                        : 'Unable to add the meter sale.');
                    return;
                }

                toastr.success(result.msg || 'Meter sale added successfully.');
                $('#active_settlement_id').val(result.settlement_id || 0);
                if (result.settlement_no) {
                    $('#settlement_no').val(result.settlement_no);
                }
                if (pump_operator_id && result.settlement_id) {
                    window.__directSettlementByOperator = window.__directSettlementByOperator || {};
                    window.__directSettlementByOperator[pump_operator_id] = result.settlement_id;
                }
                if (result.settlement_id && window.history && window.history.replaceState) {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('view_settlement_id', result.settlement_id);
                    window.history.replaceState({}, '', currentUrl.toString());
                }

                applyMeterSaleSuccessResult(result, pump_id);
            },
            error: function (xhr) {
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                var message = response && (response.msg || response.message)
                    ? (response.msg || response.message)
                    : 'Unable to add the meter sale. Please check the entered details and try again.';

                if (xhr && xhr.status === 419) {
                    message = 'The session has expired. Please refresh the page and try again.';
                } else if (xhr && xhr.status === 404) {
                    message = 'The Meter Sale save route is unavailable. Please clear the Laravel route cache.';
                }

                toastr.error(message);
                if (window.console && console.error) {
                    console.error('LA-1091 Meter Sale Add failed', xhr);
                }
            },
            complete: resetMeterSaleSubmitState
        });

        return false;
    });

    $(function () {
        if (typeof window.refreshPetroMeterSaleAddState === 'function') {
            window.refreshPetroMeterSaleAddState();
            setTimeout(window.refreshPetroMeterSaleAddState, 100);
            setTimeout(window.refreshPetroMeterSaleAddState, 500);
        } else {
            $('.btn_meter_sale').prop('disabled', false).removeAttr('disabled').removeClass('disabled');
        }
    });
}) ();

// ---------- Direct Settlement Create: remaining settlement tab handlers (moved from global app.js) ----------
(function () {
    if (!$('#meter_sale_table').length) return;

    var $doc = $(document);
    function getCurrentDirectShiftNumber() {
        return typeof window.getSelectedDirectSettlementShiftNumber === 'function'
            ? window.getSelectedDirectSettlementShiftNumber()
            : '';
    }

    var otherSaleState = {
        code: null,
        productName: null,
        price: 0
    };
    var otherIncomeState = {
        productName: null,
        price: 0
    };

    function updateOtherIncomeTotalFromRows() {
        var total = 0;

        $('#other_income_table tbody tr').each(function () {
            var $row = $(this);
            if ($row.find('td.dataTables_empty').length) return;

            var subTotal = $row.find('td').eq(3).text();
            total += parseFloat((subTotal || '0').toString().replace(/,/g, '')) || 0;
        });

        $('#other_income_total').val(total);
        $('.other_income_total').text(__number_f(total, false, false, __currency_precision));

        return total;
    }

    function getShiftText() {
        var selected = $('#shift_number').val() || [];
        if (!Array.isArray(selected)) selected = [selected];
        return selected
            .map(function (id) { return $('#shift_number option[value="' + id + '"]').text(); })
            .filter(Boolean)
            .join(', ');
    }

    function formatSettlementNumber(value) {
        if (typeof __number_f === 'function') {
            return __number_f(value, false, false, __currency_precision);
        }

        return (parseFloat(value || 0) || 0).toFixed(2);
    }

    function escapeOtherSaleHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function updateOtherSaleVisibleTotalLocal() {
        var manualTotal = parseFloat(($('#other_sale_total').val() || '0').toString().replace(/,/g, '')) || 0;
        var pumperTotal = parseFloat(($('#shift_operator_other_sale_total').val() || '0').toString().replace(/,/g, '')) || 0;
        $('.other_sale_total').text(formatSettlementNumber(manualTotal + pumperTotal));
    }

    function updateCancelMeterForm(url, data) {
        $.ajax({
            method: 'get',
            url: url,
            data: data,
            success: function (result) {
                if (result.success) {
                    $('#meter-sale-form-block').html(result.html);
                    $('#pump_no').select2();
                    $('#meter_sale_discount_type').select2();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    }

    $doc.off('click.petro_create_meter_edit', '.get_meter_sale_from')
        .on('click.petro_create_meter_edit', '.get_meter_sale_from', function () {
            window.__meter_sale_edit_tr = $(this).closest('tr');
            updateCancelMeterForm($(this).data('href'), { action_type: 'edit' });
        });

    $doc.off('click.petro_create_meter_cancel', '.btn_meter_sale_cancel')
        .on('click.petro_create_meter_cancel', '.btn_meter_sale_cancel', function () {
            updateCancelMeterForm($(this).data('href'), { action_type: 'cancel' });
        });

    $doc.off('click.petro_create_meter_delete', '.delete_meter_sale')
        .on('click.petro_create_meter_delete', '.delete_meter_sale', function () {
            var url = $(this).data('href');
            var tr = $(this).closest('tr');
            var is_edit = $('#is_edit').val() || 0;

            $.ajax({
                method: 'delete',
                url: url,
                /*
                 * _token included as well as the global X-CSRF-TOKEN header set
                 * by the $.ajaxSetup above. Harmless duplication - the header
                 * already covers this - but it removes CSRF as a variable while
                 * the real cause of the silent delete is being identified.
                 */
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    is_edit: is_edit,
                    shift_id: (function () {
                        var value = $('#shift_number').val();
                        return Array.isArray(value) ? (value || []).join(',') : (value || '').toString();
                    })()
                },
                /*
                 * S696: a failed delete must say so.
                 *
                 * There was no error handler at all, so any failure - a 419, a
                 * 500, a dropped connection - was swallowed silently and looked
                 * identical to "nothing is wired up". The real reason is now
                 * shown, so the next report names the cause.
                 */
                error: function (xhr) {
                    var msg = 'The meter sale could not be deleted.';

                    if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                        msg = xhr.responseJSON.msg;
                    } else if (xhr && xhr.status === 419) {
                        msg = 'Session expired. Please reload the page and try again.';
                    } else if (xhr && xhr.status) {
                        msg = 'Delete failed (' + xhr.status + ').';
                    }

                    if (window.toastr) { toastr.error(msg); } else { alert(msg); }
                },
                success: function (result) {
                    if (!result || !result.success) {
                        toastr.error((result && result.msg) ? result.msg : 'Delete failed');
                        return;
                    }

                    toastr.success(result.msg);
                    tr.remove();

                    var currentTotal = parseFloat(($('#meter_sale_total').val() || '0').toString().replace(/,/g, '')) || 0;
                    var removedAmount = parseFloat(result.amount || 0) || 0;
                    var meterSaleTotal = currentTotal - removedAmount;
                    $('#meter_sale_total').val(meterSaleTotal);
                    $('.meter_sale_total').text(__number_f(meterSaleTotal, false, false, __currency_precision));

                    if (result.pump_id && result.pump_name && $('#pump_no option[value="' + result.pump_id + '"]').length === 0) {
                        $('#pump_no').append('<option value="' + result.pump_id + '">' + result.pump_name + '</option>');
                    }

                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
                        $('#pump_operator_meter_sale_table').DataTable().ajax.reload(null, false);
                    }

                    if (typeof calculate_payment_tab_total === 'function') calculate_payment_tab_total();
                    if (typeof updateTotalSoldQty === 'function') updateTotalSoldQty();

                    /*
                     * S693: put the pump back in the dropdown, immediately.
                     *
                     * Removing a meter sale deleted the row and adjusted the
                     * totals, but never re-requested the pump list - so the pump
                     * just freed stayed missing from the dropdown until the page
                     * was reloaded.
                     *
                     * The list is rebuilt from the server rather than by adding
                     * an option in the browser. The server decides which pumps
                     * are still available for this settlement, so asking it is
                     * the only way to be right - and it stays right after a
                     * refresh, which patching the dropdown locally would not.
                     */
                    if (typeof requestPumpOptions === 'function') {
                        var s693Operator = $('#pump_operator_id').val();

                        if (s693Operator) {
                            var s693Shift = (typeof getSelectedShiftIds === 'function')
                                ? getSelectedShiftIds()
                                : [];

                            requestPumpOptions('/petrodirect/settlement/get_pumps/' + s693Operator, {
                                shift_number: s693Shift.length ? s693Shift[0] : 0,
                                location_id: $('#location_id').val(),
                                active_settlement_id: $('#active_settlement_id').val() || 0
                            }, { preserveSelection: false });
                        }
                    }
                }
            });
        });

    $doc.off('change.petro_create_item', '#item')
        .on('change.petro_create_item', '#item', function () {
            var item_id = $(this).val();
            if (!item_id) return;

            $.ajax({
                method: 'get',
                url: '/petrodirect/settlement/get_balance_stock_by_id/' + item_id,
                data: {
                    store_id: $('select#store_id').val(),
                    location_id: $('select#location_id').val()
                },
                success: function (result) {
                    $('#balance_stock').val(result.balance_stock);
                    $('#other_sale_price').val(result.price);
                    otherSaleState.code = result.code;
                    otherSaleState.productName = result.product_name;
                    otherSaleState.price = parseFloat(result.price || 0) || 0;
                }
            });
        });

    $doc.off('click.petro_create_other_sale', '.btn_other_sale')
        .off('click.global_settlement_other_sale', '.btn_other_sale')
        .off('click.petro_other_sale', '.btn_other_sale')
        .on('click.petro_create_other_sale', '.btn_other_sale', function (e) {
            e.preventDefault();
            if (window.isOtherSaleSubmitting) return false;
            window.isOtherSaleSubmitting = true;

            var $button = $(this);
            $button.prop('disabled', true).addClass('disabled');

            var qty = parseFloat($('#other_sale_qty').val() || 0) || 0;
            var balance_stock = parseFloat($('#balance_stock').val() || 0) || 0;
            var allowoverselling = $('#allowoverselling').val();
            if (qty > balance_stock && allowoverselling === 'true') {
                toastr.error('Out of Stock');
                $('#other_sale_qty').focus();
                window.isOtherSaleSubmitting = false;
                $button.prop('disabled', false).removeClass('disabled');
                return false;
            }

            var discount = parseFloat($('#other_sale_discount').val() || 0) || 0;
            var discount_type = $('#other_sale_discount_type').val() || 'fixed';
            var sub_total = qty * (parseFloat(otherSaleState.price || 0) || 0);
            var discount_amount = (typeof calculate_discount === 'function')
                ? calculate_discount(discount_type, discount, sub_total)
                : 0;
            var with_discount = sub_total - discount_amount;
            var is_edit = $('#is_edit').val() || 0;

            var releaseOtherSaleButton = function () {
                window.isOtherSaleSubmitting = false;
                $button.prop('disabled', false).removeClass('disabled');
            };

            $.ajax({
                method: 'post',
                url: '/petrodirect/settlement/save-other-sale',
                dataType: 'json',
                data: {
                    active_settlement_id: $('#active_settlement_id').val(),
                    settlement_no: $('#settlement_no').val(),
                    location_id: $('#location_id').val(),
                    pump_operator_id: $('#pump_operator_id').val(),
                    transaction_date: $('#transaction_date').val(),
                    work_shift: $('#work_shift').val(),
                    direct_shift_number: getCurrentDirectShiftNumber(),
                    note: $('#note').val(),
                    product_id: $('#item').val(),
                    store_id: $('#store_id').val(),
                    price: otherSaleState.price,
                    qty: qty,
                    balance_stock: balance_stock,
                    discount: discount,
                    discount_type: discount_type,
                    discount_amount: discount_amount,
                    sub_total: sub_total,
                    is_edit: is_edit
                },
                success: function (result) {
                    try {
                        if (!result || !result.success) {
                            toastr.error((result && result.msg) ? result.msg : 'Add failed');
                            return;
                        }

                        $('#active_settlement_id').val(result.settlement_id);
                        if (result.settlement_no) {
                            $('#settlement_no').val(result.settlement_no);
                        }
                        if ($('#pump_operator_id').val() && result.settlement_id) {
                            window.__directSettlementByOperator = window.__directSettlementByOperator || {};
                            window.__directSettlementByOperator[$('#pump_operator_id').val()] = result.settlement_id;
                        }
                        if (result.settlement_id && window.history && window.history.replaceState) {
                            var url = new URL(window.location.href);
                            url.searchParams.set('view_settlement_id', result.settlement_id);
                            window.history.replaceState({}, '', url.toString());
                        }

                        var total = (parseFloat(($('#other_sale_total').val() || '0').toString().replace(/,/g, '')) || 0) + with_discount;
                        $('#other_sale_total').val(total);
                        updateOtherSaleVisibleTotalLocal();

                        $('#other_sale_table tbody').prepend(
                            '<tr data-source="manual">' +
                            '<td>' + escapeOtherSaleHtml(otherSaleState.code || '') + '</td>' +
                            '<td>' + escapeOtherSaleHtml(otherSaleState.productName || '') + '</td>' +
                            '<td>' + formatSettlementNumber(balance_stock) + '</td>' +
                            '<td>' + formatSettlementNumber(otherSaleState.price) + '</td>' +
                            '<td>' + formatSettlementNumber(qty) + '</td>' +
                            '<td>' + escapeOtherSaleHtml(discount_type) + '</td>' +
                            '<td>' + formatSettlementNumber(discount) + '</td>' +
                            '<td>' + formatSettlementNumber(sub_total) + '</td>' +
                            '<td>' + formatSettlementNumber(with_discount) + '</td>' +
                            '<td><button class="btn btn-xs btn-danger delete_other_sale" data-href="/petrodirect/settlement/delete-other-sale/' + result.other_sale_id + '"><i class="fa fa-times"></i></button></td>' +
                            '</tr>'
                        );

                        // Keep all other-sale rows in one visible table.
                        $('#outside_other_sale_table').hide();
                        $('#other_sale_table').show();

                        $('.other_sale_fields').val('').trigger('change');
                        calculate_payment_tab_total();
                    } catch (error) {
                        console.error('Other sale UI update failed:', error);
                        toastr.success((result && result.msg) ? result.msg : 'Added successfully. Refreshing row list...');
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.msg
                        ? xhr.responseJSON.msg
                        : 'Add failed';
                    toastr.error(msg);
                }
            }).always(function () {
                setTimeout(releaseOtherSaleButton, 300);
            });
        });

    $doc.off('click.petro_create_other_sale_delete', '.delete_other_sale')
        .off('click.global_settlement_delete_other_sale', '.delete_other_sale')
        .on('click.petro_create_other_sale_delete', '.delete_other_sale', function () {
            var url = $(this).data('href');
            var tr = $(this).closest('tr');
            var is_edit = $('#is_edit').val() || 0;

            $.ajax({
                method: 'delete',
                url: url,
                data: { is_edit: is_edit },
                success: function (result) {
                    if (!result || !result.success) {
                        toastr.error((result && result.msg) ? result.msg : 'Delete failed');
                        return;
                    }
                    toastr.success(result.msg);
                    tr.remove();

                    var total = (parseFloat(($('#other_sale_total').val() || '0').toString().replace(/,/g, '')) || 0) - (parseFloat(result.amount || 0) || 0);
                    $('#other_sale_total').val(total);
                    updateOtherSaleVisibleTotalLocal();
                    calculate_payment_tab_total();
                }
            });
        });

    $doc.off('change.petro_create_other_income_product', '#other_income_product_id')
        .on('change.petro_create_other_income_product', '#other_income_product_id', function () {
            var item_id = $(this).val();
            if (!item_id) return;

            $.ajax({
                method: 'get',
                url: '/petrodirect/settlement/get_balance_stock/' + item_id,
                data: {},
                success: function (result) {
                    otherIncomeState.productName = result.product_name;
                    otherIncomeState.price = parseFloat(result.price || 0) || 0;
                    $('#other_income_price').val(__number_f(otherIncomeState.price, false, false, __currency_precision));
                }
            });
        });

    $doc.off('click.petro_create_other_income', '.btn_other_income')
        .on('click.petro_create_other_income', '.btn_other_income', function () {
            var product_id = $('#other_income_product_id').val();
            var qty = parseFloat($('#other_income_qty').val() || 0) || 0;
            var reason = $('#other_income_reason').val() || '';
            var price = parseFloat(($('#other_income_price').val() || '0').toString().replace(/,/g, '')) || 0;
            var sub_total = qty * price;
            var is_edit = $('#is_edit').val() || 0;

            $.ajax({
                method: 'post',
                url: '/petrodirect/settlement/save-other-income',
                data: {
                    settlement_no: $('#settlement_no').val(),
                    location_id: $('#location_id').val(),
                    pump_operator_id: $('#pump_operator_id').val(),
                    transaction_date: $('#transaction_date').val(),
                    work_shift: $('#work_shift').val(),
                    direct_shift_number: getCurrentDirectShiftNumber(),
                    note: $('#note').val(),
                    product_id: product_id,
                    qty: qty,
                    price: price,
                    other_income_reason: reason,
                    sub_total: sub_total,
                    is_edit: is_edit
                },
                success: function (result) {
                    if (!result || !result.success) {
                        toastr.error((result && result.msg) ? result.msg : 'Add failed');
                        return;
                    }

                    var saved_sub_total = parseFloat(result.sub_total || sub_total) || 0;
                    var rowData = [
                        escapeOtherSaleHtml(otherIncomeState.productName || ''),
                        __number_f(qty, false, false, __currency_precision),
                        escapeOtherSaleHtml(reason),
                        __number_f(saved_sub_total, false, false, __currency_precision),
                        '<button class="btn btn-xs btn-danger delete_other_income" data-href="/petrodirect/settlement/delete-other-income/' + result.other_income_id + '"><i class="fa fa-times"></i></button>'
                    ];
                    var table = $.fn.DataTable.isDataTable('#other_income_table') ? $('#other_income_table').DataTable() : null;
                    if (table) {
                        table.row.add(rowData).draw(false);
                    } else {
                        $('#other_income_table tbody').prepend(
                            '<tr>' +
                            '<td>' + rowData[0] + '</td>' +
                            '<td>' + rowData[1] + '</td>' +
                            '<td>' + rowData[2] + '</td>' +
                            '<td>' + rowData[3] + '</td>' +
                            '<td>' + rowData[4] + '</td>' +
                            '</tr>'
                        );
                    }

                    $('.other_income_fields').val('').trigger('change');
                    updateOtherIncomeTotalFromRows();
                    calculate_payment_tab_total();
                }
            });
        });

    $doc.off('click.petro_create_other_income_delete', '.delete_other_income')
        .on('click.petro_create_other_income_delete', '.delete_other_income', function () {
            var url = $(this).data('href');
            var tr = $(this).closest('tr');
            var is_edit = $('#is_edit').val() || 0;

            $.ajax({
                method: 'delete',
                url: url,
                data: { is_edit: is_edit },
                success: function (result) {
                    if (!result || !result.success) {
                        toastr.error((result && result.msg) ? result.msg : 'Delete failed');
                        return;
                    }
                    toastr.success(result.msg);
                    var table = $.fn.DataTable.isDataTable('#other_income_table') ? $('#other_income_table').DataTable() : null;
                    if (table) {
                        table.row(tr).remove().draw(false);
                    } else {
                        tr.remove();
                    }

                    updateOtherIncomeTotalFromRows();
                    calculate_payment_tab_total();
                }
            });
        });

    $doc.off('change.petro_create_customer_payment_method', '#customer_payment_payment_method')
        .on('change.petro_create_customer_payment_method', '#customer_payment_payment_method', function () {
            $('.cheque_divs').toggleClass('hide', $(this).val() !== 'cheque');
        });

    $doc.off('click.petro_create_customer_payment', '.btn_customer_payment')
        .on('click.petro_create_customer_payment', '.btn_customer_payment', function () {
            if ($('#customer_payment_amount').length === 0 || $('#customer_payment_total').length === 0) return;

            var amount = parseFloat($('#customer_payment_amount').val() || 0) || 0;
            var customer_name = $('#customer_payment_customer_id :selected').text();
            var payment_method = $('#customer_payment_payment_method').val();
            var bank_name = $('#customer_payment_bank_name').val() || '';
            var cheque_date = $('#customer_payment_cheque_date').val() || '';
            var cheque_number = $('#customer_payment_cheque_number').val() || '';
            var post_dated_cheque = $('#customer_payment_post_dated_cheque').is(':checked') ? 1 : 0;
            var is_edit = $('#is_edit').val() || 0;

            $.ajax({
                method: 'post',
                url: '/petrodirect/settlement/save-customer-payment',
                data: {
                    settlement_no: $('#settlement_no').val(),
                    location_id: $('#location_id').val(),
                    pump_operator_id: $('#pump_operator_id').val(),
                    transaction_date: $('#transaction_date').val(),
                    work_shift: $('#work_shift').val(),
                    direct_shift_number: getCurrentDirectShiftNumber(),
                    note: $('#note').val(),
                    customer_id: $('#customer_payment_customer_id').val(),
                    payment_method: payment_method,
                    bank_name: bank_name,
                    cheque_date: cheque_date,
                    cheque_number: cheque_number,
                    amount: amount,
                    sub_total: amount,
                    is_edit: is_edit,
                    post_dated_cheque: post_dated_cheque
                },
                success: function (result) {
                    if (!result || !result.success) {
                        toastr.error((result && result.msg) ? result.msg : 'Add failed');
                        return;
                    }

                    var total = (parseFloat(($('#customer_payment_total').val() || '0').toString().replace(/,/g, '')) || 0) + amount;
                    $('#customer_payment_total').val(total);
                    $('.customer_payment_total').text(__number_f(total, false, false, __currency_precision));

                    $('#customer_payment_table tbody').prepend(
                        '<tr>' +
                        '<td>' + customer_name + '</td>' +
                        '<td>' + (payment_method || '') + '</td>' +
                        '<td>' + bank_name + '</td>' +
                        '<td>' + cheque_date + '</td>' +
                        '<td>' + cheque_number + '</td>' +
                        '<td>' + __number_f(amount, false, false, __currency_precision) + '</td>' +
                        '<td>' + ($('#settlement_no').val() || '') + '</td>' +
                        '<td>' + getShiftText() + '</td>' +
                        '<td><button class="btn btn-xs btn-danger delete_customer_payment" data-href="/petrodirect/settlement/delete-customer-payment/' + result.customer_payment_id + '"><i class="fa fa-times"></i></button></td>' +
                        '</tr>'
                    );

                    $('.customer_payment_fields').val('').trigger('change');
                    calculate_payment_tab_total();
                }
            });
        });

    $doc.off('click.petro_create_customer_payment_delete', '.delete_customer_payment')
        .on('click.petro_create_customer_payment_delete', '.delete_customer_payment', function () {
            var url = $(this).data('href');
            var tr = $(this).closest('tr');
            var is_edit = $('#is_edit').val() || 0;

            $.ajax({
                method: 'delete',
                url: url,
                data: { is_edit: is_edit },
                success: function (result) {
                    if (!result || !result.success) {
                        toastr.error((result && result.msg) ? result.msg : 'Delete failed');
                        return;
                    }
                    toastr.success(result.msg);
                    tr.remove();

                    var total = (parseFloat(($('#customer_payment_total').val() || '0').toString().replace(/,/g, '')) || 0) - (parseFloat(result.amount || 0) || 0);
                    $('#customer_payment_total').val(total);
                    $('.customer_payment_total').text(__number_f(total, false, false, __currency_precision));
                    calculate_payment_tab_total();
                }
            });
        });
})();
</script>


<style id="s429-direct-settlement-layout-fix">
/* S429: compact Direct Settlement entry area and keep the whole page visible. */
.direct-settlement-main-tabs > .tab-content { overflow: visible; }
.direct-settlement-main-tabs > .tab-content > .tab-pane { display: none; }
.direct-settlement-main-tabs > .tab-content > .tab-pane.active { display: block; }
.direct-settlement-main-tabs .table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.direct-settlement-main-tabs table { width: 100% !important; }

/* Locked starting meter must look locked/ash-coloured. */
#pump_starting_meter[readonly],
.pump_starting_meter[readonly] {
    background: #e9ecef !important;
    color: #495057 !important;
    cursor: not-allowed !important;
    border-color: #ced4da !important;
}

/* Keep the tab strip usable even when Bootstrap/global tab handlers conflict. */
.direct-settlement-main-tabs > .nav-tabs {
    position: relative;
    z-index: 20;
    pointer-events: auto !important;
}
.direct-settlement-main-tabs > .nav-tabs > li,
.direct-settlement-main-tabs > .nav-tabs > li > a {
    position: relative;
    pointer-events: auto !important;
    cursor: pointer;
}
.direct-settlement-main-tabs > .tab-content {
    position: relative;
    z-index: 1;
}
</style>

<script id="la1077-direct-settlement-main-tabs-fix">
(function (window, document, $) {
    'use strict';

    var rootSelector = '.direct-settlement-main-tabs';

    function normalizeTarget(rawTarget) {
        if (!rawTarget) { return ''; }
        var hashPosition = rawTarget.indexOf('#');
        return hashPosition >= 0 ? rawTarget.substring(hashPosition) : '';
    }

    function showMainTab(root, target, sourceLink) {
        if (!root || !target || target.charAt(0) !== '#') { return false; }

        var tabList = root.querySelector(':scope > .nav-tabs');
        var tabContent = root.querySelector(':scope > .tab-content');
        if (!tabList || !tabContent) { return false; }

        var links = tabList.querySelectorAll('a[data-toggle="tab"], a[data-bs-toggle="tab"]');
        var panes = tabContent.querySelectorAll(':scope > .tab-pane');
        var pane = null;

        try {
            pane = tabContent.querySelector(':scope > ' + target);
        } catch (error) {
            return false;
        }

        if (!pane) { return false; }

        Array.prototype.forEach.call(links, function (link) {
            var active = normalizeTarget(link.getAttribute('href') || link.getAttribute('data-target')) === target;
            var item = link.closest('li');
            if (item) { item.classList.toggle('active', active); }
            link.classList.toggle('active', active);
            link.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        Array.prototype.forEach.call(panes, function (candidate) {
            var active = candidate === pane;
            candidate.classList.toggle('active', active);
            candidate.classList.toggle('in', active);
            candidate.classList.toggle('show', active);
            candidate.style.display = active ? 'block' : 'none';
            candidate.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        if ($) {
            var $pane = $(pane);
            if ($.fn && $.fn.DataTable) {
                $pane.find('table.dataTable').each(function () {
                    try { $(this).DataTable().columns.adjust(); } catch (error) {}
                });
            }

            var linkToNotify = sourceLink || tabList.querySelector('a[href="' + target + '"]');
            if (linkToNotify) {
                $(linkToNotify).trigger('shown.bs.tab');
            }
        }

        return true;
    }

    function handleTabClick(event) {
        var link = event.target && event.target.closest
            ? event.target.closest(rootSelector + ' > .nav-tabs a[data-toggle="tab"], ' + rootSelector + ' > .nav-tabs a[data-bs-toggle="tab"]')
            : null;

        if (!link) { return; }

        var root = link.closest(rootSelector);
        var target = normalizeTarget(link.getAttribute('href') || link.getAttribute('data-target'));
        if (!root || !target) { return; }

        event.preventDefault();
        showMainTab(root, target, link);

        /* Do not stop propagation here. The page's existing delegated handlers
           still need to run (for example, loading Other Sale data). The local
           no-erp-global-tabs/no-exf-tabs markers keep unrelated global tab
           engines away from this tab strip. */
    }

    /* Capture phase keeps this isolated page functional even when a global tab
       handler or a conflicting Bootstrap version is registered on document. */
    document.addEventListener('click', handleTabClick, true);

    function initialiseTabs() {
        var roots = document.querySelectorAll(rootSelector);
        Array.prototype.forEach.call(roots, function (root) {
            var activeLink = root.querySelector(':scope > .nav-tabs li.active a[data-toggle="tab"], :scope > .nav-tabs li.active a[data-bs-toggle="tab"]');
            var firstLink = root.querySelector(':scope > .nav-tabs a[data-toggle="tab"], :scope > .nav-tabs a[data-bs-toggle="tab"]');
            var link = activeLink || firstLink;
            if (link) {
                showMainTab(root, normalizeTarget(link.getAttribute('href') || link.getAttribute('data-target')), link);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialiseTabs, { once: true });
    } else {
        initialiseTabs();
    }

    window.addEventListener('pageshow', initialiseTabs);
})(window, document, window.jQuery);
</script>

@endsection
