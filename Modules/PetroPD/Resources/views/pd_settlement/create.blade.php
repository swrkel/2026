@extends('layouts.app')


<style>
/* S380: settlement button state rules.
   Normal = legacy colour with white text.
   Active/selected/clicked = white background with black text and legacy coloured border. */
.btn,
.btn:link,
.btn:visited,
.btn:hover,
.btn:focus,
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
.btn.active,
.btn:active,
.btn.selected,
.btn.is-active,
.btn[aria-selected="true"],
.payment_tabs .btn.active,
.payment_tabs .btn:active,
.payment_tabs .btn.selected,
.payment_tabs .btn.is-active,
.payment_tabs .btn[aria-selected="true"],
.nav-tabs > li.active > a.btn,
.nav-tabs > li.active > a.btn:focus,
.nav-tabs > li.active > a.btn:hover,
.nav-pills > li.active > a.btn,
.settlement-tab-button.active,
.settlement-tab-button:active,
.settlement-tab-button.selected,
.settlement-tab-button.is-active {
    background: #ffffff !important;
    color: #000000 !important;
    box-shadow: inset 0 0 0 1px currentColor, 0 2px 8px rgba(15,23,42,.10) !important;
}
.btn.active *,
.btn:active *,
.btn.selected *,
.btn.is-active *,
.btn[aria-selected="true"] *,
.payment_tabs .btn.active *,
.payment_tabs .btn:active *,
.payment_tabs .btn.selected *,
.payment_tabs .btn.is-active *,
.payment_tabs .btn[aria-selected="true"] *,
.nav-tabs > li.active > a.btn *,
.nav-pills > li.active > a.btn *,
.settlement-tab-button.active *,
.settlement-tab-button:active *,
.settlement-tab-button.selected *,
.settlement-tab-button.is-active * {
    color: #000000 !important;
}
.btn-default.active,
.btn-default:active,
.btn-default.selected,
.btn-default.is-active {
    background: #ffffff !important;
    color: #000000 !important;
}
.btn-default.active *,
.btn-default:active *,
.btn-default.selected *,
.btn-default.is-active * {
    color: #000000 !important;
}

    /* Settlement header layout: give Settlement No the width saved from Work Shift. */
    .settlement-header-row .settlement-no-col > .form-group > label {
        white-space: nowrap;
    }



</style>
@section('title', __('petropd::lang.settlement_pd'))

@section('content')
@php
        $business_id = session('user.business_id');
        $business_details = App\Business::find($business_id);
        $currency_precision = $business_details->currency_precision ?? 2;
        $meeter_precision = 3;

        $asset_vapps = filemtime(module_path('PetroPD', 'Resources/assets/js/app.js'));
        $asset_vpayment = filemtime(module_path('PetroPD', 'Resources/assets/js/payment.js'));
        $asset_vpetro_payment = filemtime(module_path('PetroPD', 'Resources/assets/js/petro_payment.js'));


        // PDRW-008: Blade-safe scalar/option normalizer.
        // Prevents htmlspecialchars(array) when controllers pass rich shift/option arrays.
        $__pdrw_scalar = function ($value, $fallback = '') use (&$__pdrw_scalar) {
            if ($value instanceof \Illuminate\Support\Collection) {
                $value = $value->toArray();
            }

            if (is_array($value)) {
                foreach (['shift_number', 'shift_no', 'number', 'name', 'label', 'value', 'id'] as $key) {
                    if (array_key_exists($key, $value) && !is_array($value[$key]) && !is_object($value[$key])) {
                        return (string) $value[$key];
                    }
                }

                $parts = [];
                foreach ($value as $item) {
                    if (!is_array($item) && !is_object($item) && $item !== null && $item !== '') {
                        $parts[] = (string) $item;
                    }
                }

                return !empty($parts) ? implode(' - ', $parts) : $fallback;
            }

            if (is_object($value)) {
                foreach (['shift_number', 'shift_no', 'number', 'name', 'label', 'value', 'id'] as $key) {
                    if (isset($value->{$key}) && !is_array($value->{$key}) && !is_object($value->{$key})) {
                        return (string) $value->{$key};
                    }
                }

                return method_exists($value, '__toString') ? (string) $value : $fallback;
            }

            return $value === null ? $fallback : (string) $value;
        };

        $__pdrw_options = function ($options) use ($__pdrw_scalar) {
            if ($options instanceof \Illuminate\Support\Collection) {
                $options = $options->toArray();
            }

            if (!is_array($options)) {
                return [];
            }

            $clean = [];
            foreach ($options as $key => $value) {
                $clean[$__pdrw_scalar($key)] = $__pdrw_scalar($value);
            }

            return $clean;
        };

        $settlement_no = $__pdrw_scalar($settlement_no ?? '');
        $check_qty = $__pdrw_scalar($check_qty ?? '');
        $shift_id = $__pdrw_scalar($shift_id ?? '');
        $pump_operator_id = $__pdrw_scalar($pump_operator_id ?? '');
        $default_location = $__pdrw_scalar($default_location ?? '');
        $shift_closed = $__pdrw_scalar($shift_closed ?? 'yes');

        $business_locations = $__pdrw_options($business_locations ?? []);
        $shift_numbers = $__pdrw_options($shift_numbers ?? []);
        $pump_operators = $__pdrw_options($pump_operators ?? []);
        $work_shifts = $__pdrw_options($work_shifts ?? []);
    @endphp

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('petropd::lang.petro')</a></li>
                        <li><span>@lang('petropd::lang.settlement_pd')</span></li>
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

            /* Pump operator reconfirm: render Yes on left, No on right.
               sweetalert defaults to [Cancel] [Confirm] left→right; flipping with row-reverse
               so the confirm button (Yes) appears on the left and cancel (No) on the right. */
            .pump-operator-confirm-swal .swal-footer {
                display: flex !important;
                flex-direction: row-reverse !important;
                justify-content: center !important;
                gap: 8px;
            }

        </style>
    @endpush

    <section class="content main-content-inner">
        @if (!empty($message))
            {!! $message !!}
        @endif

        {{-- Filters --}}
        <div class="row settlement-header-row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="row">
                        <div class="col-md-2 settlement-no-col">
                            <div class="form-group">
                                {!! Form::label('settlement_no', __('petropd::lang.settlement_no') . ':') !!}
                                {!! Form::text('settlement_no', $active_settlement->settlement_no ?? $settlement_no, [
                                    'class' => 'form-control',
                                    'readonly',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                                {!! Form::select(
                                    'location_id',
                                    $business_locations,
                                    $active_settlement->location_id ?? ($default_location ?? null),
                                    [
                                        'class' => 'form-control select2',
                                        'id' => 'location_id',
                                        'placeholder' => __('petropd::lang.all'),
                                        'style' => 'width:100%',
                                    ],
                                ) !!}
                            </div>
                        </div>

                        {{--
                            MA-002: the OPERATOR dropdown now comes before the SHIFT
                            dropdown.

                            Settlement is per operator - each operator's shifts are
                            settled in their own order, and the shift list means
                            nothing until you know whose shifts you are looking at.
                            So the operator must be chosen first.

                            The two blocks are swapped whole. Neither is altered -
                            same ids, same names, same options, same data attributes,
                            so the script that filters shifts by operator is
                            unaffected.
                        --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('pump_operator', __('petropd::lang.pump_operator') . ':') !!}
                                {!! Form::select('pump_operator_id', $pump_operators, $active_settlement->pump_operator_id ?? $pump_operator_id ?? null, [
                                    'class' => 'form-control select2',
                                    'id' => 'pump_operator_id',
                                    'disabled' => !empty($select_pump_operator_in_settlement) ? false : true,
                                    'placeholder' => __('petropd::lang.please_select'),
                                ]) !!}
                                <button type="button" id="pump_operator_reconfirmed_btn"
                                    class="btn btn-success btn-xs"
                                    style="display:none; margin-top:5px;">
                                    <i class="fa fa-check"></i> Reconfirmed
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('shift_number', __('petropd::lang.shift_number') . ':') !!}
                                <select id="shift_number" name="shift_number" class="form-control select2" style="width:100%">
                                    <option value="">{{ __('petropd::lang.please_select') }}</option>
                                    @foreach ($shift_numbers as $shiftOptionId => $shiftOptionNumber)
                                        <option value="{{ $shiftOptionId }}"
                                            data-pump-operator-id="{{ (int) ($shift_operator_map[(string) $shiftOptionId] ?? 0) }}"
                                            {{ isset($shift_id) && (string) $shift_id === (string) $shiftOptionId ? ' selected' : '' }}>
                                            {{ $shiftOptionNumber }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('transaction_date', __('petropd::lang.transaction_date') . ':*') !!}
                                <div class="input-group petropd-transaction-date-picker">
                                    {!! Form::text('transaction_date', $active_settlement->transaction_date ?? null, [
                                        'class' => 'form-control transaction_date date-picker',
                                        'id' => 'transaction_date',
                                        'required',
                                        'autocomplete' => 'off',
                                        'placeholder' => __('petropd::lang.transaction_date'),
                                    ]) !!}
                                    <span class="input-group-addon" id="petropd_transaction_date_calendar"
                                        role="button" tabindex="0" title="{{ __('petropd::lang.transaction_date') }}">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-1 work-shift-col">
                            <div class="form-group">
                                {!! Form::label('work_shift', __('petropd::lang.work_shift') . ':') !!}
                                {!! Form::select('work_shift[]', $work_shifts, $active_settlement->work_shift ?? [], [
                                    'class' => 'form-control select2',
                                    'id' => 'work_shift',
                                    'multiple',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-1">
                            <div class="form-group">
                                {!! Form::label('note', __('petropd::lang.note') . ':') !!}
                                {!! Form::text('note', $active_settlement->note ?? null, [
                                    'class' => 'form-control note',
                                    'id' => 'note',
                                    'placeholder' => __('petropd::lang.note'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        {{-- Widget area with tabs --}}
        <script id="is1821-direct-tab-function">
            window.petroPdOpenSettlementTab = function (selector, link, clickEvent) {
                if (clickEvent) {
                    clickEvent.preventDefault();
                    clickEvent.stopImmediatePropagation();
                }

                var root = link && link.closest
                    ? link.closest('.pd-settlement-main-tabs')
                    : document.querySelector('.pd-settlement-main-tabs');
                if (!root || !selector || selector.charAt(0) !== '#') return false;

                var nav = root.querySelector('.nav-tabs');
                var content = root.querySelector('.tab-content');
                var pane = document.getElementById(selector.substring(1));
                if (!nav || !content || !pane) return false;

                // IS1821 Issue 2: these panes are already protected and
                // conditionally rendered by their explicit petro_pd tab permissions.
                // The global Manage Page scanner can misclassify the top-level
                // pane as a discovered minor action and add this disabled
                // marker even though its explicit tab permission is enabled.
                pane.classList.remove('business-manage-disabled-tab');

                Array.prototype.forEach.call(nav.children, function (item) {
                    item.classList.remove('active');
                    var control = item.querySelector('a, button');
                    if (control) {
                        control.classList.remove('active');
                        control.setAttribute('aria-selected', 'false');
                    }
                });

                Array.prototype.forEach.call(content.children, function (itemPane) {
                    if (!itemPane.classList.contains('pd-main-pane')) return;
                    itemPane.classList.remove('active', 'in', 'show');
                    itemPane.style.setProperty('display', 'none', 'important');
                    itemPane.setAttribute('aria-hidden', 'true');
                });

                var item = link ? link.parentNode : null;
                if (item) item.classList.add('active');
                if (link) {
                    link.classList.add('active');
                    link.setAttribute('aria-selected', 'true');
                }

                pane.classList.add('active', 'in', 'show');
                pane.style.setProperty('display', 'block', 'important');
                pane.setAttribute('aria-hidden', 'false');

                var detail = { target: selector };
                var event;
                try {
                    event = new CustomEvent('petropd:main-tab-shown', { detail: detail });
                } catch (error) {
                    event = document.createEvent('CustomEvent');
                    event.initCustomEvent('petropd:main-tab-shown', true, true, detail);
                }
                document.dispatchEvent(event);

                if ((selector === '#meter_sale_tab' || selector === '#other_sale_tab' || selector === '#payment_tab')
                    && typeof window.petroPdLoadSelectedShiftDetails === 'function') {
                    window.petroPdLoadSelectedShiftDetails();
                }

                if (window.jQuery) {
                    window.jQuery(document).trigger('petropd:main-tab-shown-jquery', [selector]);
                    window.setTimeout(function () {
                        if (window.jQuery.fn && window.jQuery.fn.dataTable) {
                            window.jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                        }
                        if (selector === '#payment_tab' && typeof window.calculate_payment_tab_total === 'function') {
                            window.calculate_payment_tab_total();
                        }
                    }, 20);
                }

                return false;
            };

            /*
             * Add Payment is injected into a modal after this page has loaded.
             * Keep a parent-page tab bridge available even when scripts inside
             * the injected modal are not evaluated by the modal loader.
             */
            (function installPetroPdAddPaymentTabBridge() {
                function activateAddPaymentTab(link, clickEvent) {
                    if (!link || !link.closest) return false;

                    var form = link.closest('#settlement_form');
                    var root = link.closest('.settlement_tabs');
                    var targetId = link.getAttribute('data-pd-payment-tab');
                    var nav = root ? root.querySelector(':scope > .nav-tabs') : null;
                    var content = root ? root.querySelector(':scope > .tab-content') : null;
                    var pane = targetId && content
                        ? content.querySelector('#' + targetId)
                        : null;

                    if (!form || !root || !nav || !content || !pane) return false;

                    // Preserve an intentionally disabled Manage Page permission.
                    if (link.classList.contains('business-manage-disabled-tab')
                        || (link.parentNode
                            && link.parentNode.classList
                            && link.parentNode.classList.contains('business-manage-disabled-tab'))) {
                        return false;
                    }

                    if (clickEvent) {
                        clickEvent.preventDefault();
                        clickEvent.stopPropagation();
                    }

                    Array.prototype.forEach.call(nav.children, function (item) {
                        var control = item.querySelector('[data-pd-payment-tab]');
                        var selected = control === link;
                        item.classList.toggle('active', selected);
                        item.classList.toggle('show', selected);
                        if (control) {
                            control.classList.toggle('active', selected);
                            control.setAttribute('aria-selected', selected ? 'true' : 'false');
                        }
                    });

                    Array.prototype.forEach.call(content.children, function (candidate) {
                        if (!candidate.classList.contains('tab-pane')) return;
                        var visible = candidate === pane;
                        candidate.classList.toggle('active', visible);
                        candidate.classList.toggle('show', visible);
                        candidate.classList.toggle('in', visible);
                        candidate.style.setProperty('display', visible ? 'block' : 'none', 'important');
                        candidate.style.setProperty('visibility', visible ? 'visible' : 'hidden', 'important');
                        candidate.style.setProperty('pointer-events', visible ? 'auto' : 'none', 'important');
                        candidate.setAttribute('aria-hidden', visible ? 'false' : 'true');
                    });

                    if (window.jQuery) {
                        window.jQuery(link).trigger('shown.bs.tab');
                        window.setTimeout(function () {
                            try {
                                if (window.jQuery.fn && window.jQuery.fn.dataTable) {
                                    window.jQuery.fn.dataTable
                                        .tables({ visible: true, api: true })
                                        .columns.adjust();
                                }
                            } catch (ignore) {}
                        }, 30);
                    }

                    return true;
                }

                // Fallback used by the inline handlers in the asynchronously
                // loaded payment-tab markup. The partial can safely replace it
                // with its richer controller when its script is evaluated.
                window.petropdActivatePaymentTab = function (link, event) {
                    activateAddPaymentTab(link, event);
                    return false;
                };

                if (window.__petroPdParentPaymentTabBridgeInstalled) return;
                window.__petroPdParentPaymentTabBridgeInstalled = true;

                var earlyEvent = window.PointerEvent ? 'pointerdown' : 'mousedown';
                document.addEventListener(earlyEvent, function (event) {
                    if (event.__petroPdPaymentTabHandled) return;

                    var link = event.target && event.target.closest
                        ? event.target.closest('#settlement_form [data-pd-payment-tab]')
                        : null;
                    if (link) {
                        event.__petroPdPaymentTabHandled = true;
                        activateAddPaymentTab(link, event);
                    }
                }, true);
            })();
        </script>
        @component('components.widget', ['class' => 'box-primary below_box', 'id' => 'below_box'])
            <div class="row">
                <div class="col-md-12">
                    <div class="settlement_tabs pd-settlement-main-tabs">
                        <ul class="nav nav-tabs">
                            @can('petro_pd.meter_sale_tab')
                            <li class="active">
                                <button type="button" class="pd-main-tab-control meter_sale_tab"
                                    onclick="return window.petroPdOpenSettlementTab('#meter_sale_tab', this, event);">
                                    <i class="fa fa-tachometer"></i> <strong>@lang('petropd::lang.meter_sale')s</strong>
                                </button>
                            </li>
                            @endcan
                            @can('petro_pd.other_sale_tab')
                            <li>
                                <button type="button" class="pd-main-tab-control other_sale_tab"
                                    onclick="return window.petroPdOpenSettlementTab('#other_sale_tab', this, event);">
                                    <i class="fa fa-balance-scale"></i> <strong>@lang('petropd::lang.other_sale')</strong>
                                </button>
                            </li>
                            @endcan
                            @can('petro_pd.other_income_tab')
                            <li>
                                <button type="button" class="pd-main-tab-control other_income_tab"
                                    onclick="return window.petroPdOpenSettlementTab('#other_income_tab', this, event);">
                                    <i class="fa fa-thermometer"></i> <strong>@lang('petropd::lang.other_income')</strong>
                                </button>
                            </li>
                            @endcan
                            @can('petro_pd.customer_payment_tab')
                            <li>
                                <button type="button" class="pd-main-tab-control customer_payment_tab"
                                    onclick="return window.petroPdOpenSettlementTab('#customer_payment_tab', this, event);">
                                    <i class="fa fa-money"></i> <strong>@lang('petropd::lang.customer_payment')</strong>
                                </button>
                            </li>
                            @endcan
                            @can('petro_pd.payment_tab')
                            <li>
                                <button type="button" class="pd-main-tab-control payment_tab"
                                    onclick="return window.petroPdOpenSettlementTab('#payment_tab', this, event);">
                                    <i class="fa fa-book"></i> <strong>@lang('petropd::lang.payment')</strong>
                                </button>
                            </li>
                            @endcan
                        </ul>

                        <div class="tab-content">
                            @can('petro_pd.meter_sale_tab')
                            <div class="pd-main-pane active" id="meter_sale_tab">
                                @include('petropd::pd_settlement.partials.meter_sale')
                            </div>
                            @endcan

                            @can('petro_pd.other_sale_tab')
                            <div class="pd-main-pane" id="other_sale_tab">
                                @include('petropd::pd_settlement.partials.other_sale')
                                <input type="hidden" value="{{ $check_qty }}" id="allowoverselling">
                            </div>
                            @endcan

                            @can('petro_pd.other_income_tab')
                            <div class="pd-main-pane" id="other_income_tab">
                                @include('petropd::pd_settlement.partials.other_income')
                            </div>
                            @endcan

                            @can('petro_pd.customer_payment_tab')
                            <div class="pd-main-pane" id="customer_payment_tab">
                                @include('petropd::pd_settlement.partials.customer_payment')
                            </div>
                            @endcan

                            @can('petro_pd.payment_tab')
                            <div class="pd-main-pane" id="payment_tab">
                                @include('petropd::pd_settlement.partials.payment')
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endcomponent

        <style id="is1821-petropd-tabs">
            .pd-settlement-main-tabs > .nav-tabs > li > .pd-main-tab-control {
                display: block;
                position: relative;
                padding: 10px 15px;
                margin-right: 2px;
                line-height: 1.42857143;
                border: 1px solid transparent;
                border-radius: 4px 4px 0 0;
                color: #fff;
                cursor: pointer;
            }
            .pd-settlement-main-tabs > .nav-tabs > li:nth-child(1) > .pd-main-tab-control { background: #2f6fed; }
            .pd-settlement-main-tabs > .nav-tabs > li:nth-child(2) > .pd-main-tab-control { background: #9b0f8f; }
            .pd-settlement-main-tabs > .nav-tabs > li:nth-child(3) > .pd-main-tab-control { background: #167ea4; }
            .pd-settlement-main-tabs > .nav-tabs > li:nth-child(4) > .pd-main-tab-control { background: #247a2e; }
            .pd-settlement-main-tabs > .nav-tabs > li:nth-child(5) > .pd-main-tab-control { background: #f4a51c; }
            .pd-settlement-main-tabs > .nav-tabs > li.active > .pd-main-tab-control {
                background: #fff !important;
                color: #222 !important;
                border-color: #ddd #ddd #fff;
            }
            .pd-settlement-main-tabs > .tab-content > .pd-main-pane {
                display: none !important;
            }
            .pd-settlement-main-tabs > .tab-content > .pd-main-pane.active {
                display: block !important;
            }
            .pd-settlement-main-tabs > .tab-content > .pd-main-pane.active.business-manage-disabled-tab {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }
        </style>
        <script id="is1821-authorized-tab-marker-guard">
            (function () {
                var root = document.querySelector('.pd-settlement-main-tabs');
                if (!root) return;

                function clearIncorrectTopLevelMarkers() {
                    var content = root.querySelector('.tab-content');
                    if (!content) return;

                    Array.prototype.forEach.call(content.children, function (pane) {
                        if (pane.classList
                            && pane.classList.contains('pd-main-pane')
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

                document.addEventListener('DOMContentLoaded', clearIncorrectTopLevelMarkers);
                window.addEventListener('load', clearIncorrectTopLevelMarkers);
                root.addEventListener('pointerdown', clearIncorrectTopLevelMarkers, true);
                root.addEventListener('mousedown', clearIncorrectTopLevelMarkers, true);
                root.addEventListener('touchstart', clearIncorrectTopLevelMarkers, true);
                window.setTimeout(clearIncorrectTopLevelMarkers, 100);
                window.setTimeout(clearIncorrectTopLevelMarkers, 500);
                window.setTimeout(clearIncorrectTopLevelMarkers, 1500);
            })();
        </script>
        {{-- Modals --}}
        <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade add_payment" role="dialog" aria-labelledby="gridSystemModalLabel" style="overflow-y: auto;">
        </div>
        <div class="modal fade preview_settlement" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div id="settlement_print"></div>
    </section>
    <!-- /.content -->
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
    <script src="{{ url('Modules/PetroPD/Resources/assets/js/app.js?v=' . $asset_vapps) }}"></script>
    <script src="{{ url('Modules/PetroPD/Resources/assets/js/payment.js?v=' . $asset_vpayment) }}"></script>
    <script src="{{ url('Modules/PetroPD/Resources/assets/js/petro_payment.js?v=' . $asset_vpetro_payment) }}"></script>

    <input type="hidden" id="active_settlement_id" value="{{ $active_settlement->id ?? 0 }}">
    <input type="hidden" name="source" id="petropd_source" value="petro_pd">
    <input type="hidden" id="shift_closed" value="{{ !empty($shift_closed) ? $shift_closed : 'yes' }}">

    @include('petropd::pd_settlement.partials.meter_sale_scripts')

    <script>
        // Global variable required by the shared app.js .btn_meter_sale_pd handler.
        // In edit mode the hidden input name="is_edit" is rendered by the form partial.
        var is_edit = $('input[name="is_edit"]').val() || 0;

        // Reset the PetroPD pump dropdown (#pump_id_pd) after a meter sale is saved.
        // The shared app.js handler resets #pump_no (base Petro), so we handle #pump_id_pd here.
        $(document).ajaxSuccess(function (event, xhr, settings) {
            var url = (settings && settings.url) ? settings.url.toString() : '';
            if (url.indexOf('/petropd/settlement-pd/save-') === -1) {
                return;
            }

            try {
                var result = typeof xhr.responseJSON === 'object' && xhr.responseJSON
                    ? xhr.responseJSON
                    : JSON.parse(xhr.responseText || '{}');

                if (!result || !result.success) {
                    return;
                }

                if (result.settlement_id) {
                    $('#active_settlement_id').val(result.settlement_id);
                }
                if (result.settlement_no) {
                    $('#settlement_no').val(result.settlement_no);
                    $('.settlement_no').text(result.settlement_no);
                }

                // The shared Petro JavaScript appends new table rows using Petro
                // delete URLs. Keep those newly inserted actions inside PetroPD.
                $('#other_income_table [data-href^="/petropd/settlement-pd/delete-other-income/"]').each(function () {
                    $(this).attr('data-href', $(this).attr('data-href').replace(
                        '/petropd/settlement-pd/delete-other-income/',
                        '/petropd/settlement-pd/delete-other-income/'
                    ));
                });
                $('#customer_payment_table [data-href^="/petropd/settlement-pd/delete-customer-payment/"]').each(function () {
                    $(this).attr('data-href', $(this).attr('data-href').replace(
                        '/petropd/settlement-pd/delete-customer-payment/',
                        '/petropd/settlement-pd/delete-customer-payment/'
                    ));
                });

                if (url.indexOf('/save-meter-sale') !== -1) {
                    $('#pump_id_pd').val('').trigger('change');
                }

                if (typeof window.petroPdSyncPaymentFinalizeTotal === 'function') {
                    window.petroPdSyncPaymentFinalizeTotal();
                }
            } catch (e) {}
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
            let skipUnsettledCheck = false;
            const $doc = $(document);
            const $window = $(window);
            const $note = $('#note');
            const $workShift = $('#work_shift');
            const $transactionDate = $('.transaction_date');
            const $pumpOperator = $('#pump_operator_id');
            const $location = $('#location_id');
            const $shiftNumber = $('#shift_number');
            const $belowBox = $('#below_box');
            const $shiftClosed = $('#shift_closed');
            const $activeSettlement = $('#active_settlement_id');

            const activeSettlementId = $activeSettlement.val();
            const hasActiveSettlement = {!! json_encode(!empty($active_settlement)) !!}; // boolean
            const isFinishingExistingSettlement = {!! json_encode(!empty($is_finishing_existing_settlement)) !!};
            let pd_meter_sale_submitting = false;
            window.petroPdManualEntryActivated = false;

            // IS1825: The oldest pending closed shift and its own operator are one
            // authoritative selection. Never restore an unrelated operator from
            // stale browser state or leave the shift blank while an operator shows.
            function applyInitialShiftOperatorContext() {
                var serverShiftId = String({!! json_encode($shift_id ?? '') !!} || '');
                var $selectedOption = serverShiftId
                    ? $shiftNumber.find('option[value="' + serverShiftId.replace(/"/g, '\\"') + '"]')
                    : $();

                if (!$selectedOption.length) {
                    $selectedOption = $shiftNumber.find('option[value!=""]').first();
                }

                if (!$selectedOption.length) {
                    $shiftNumber.val('').trigger('change.select2');
                    $pumpOperator.val('').trigger('change.select2');
                    return false;
                }

                var selectedShiftId = String($selectedOption.val() || '');
                var selectedOperatorId = String($selectedOption.attr('data-pump-operator-id') || '');

                $shiftNumber.val(selectedShiftId).trigger('change.select2');

                if (selectedOperatorId && $pumpOperator.find('option[value="' + selectedOperatorId.replace(/"/g, '\\"') + '"]').length) {
                    window.skipOperatorShiftReload = true;
                    $pumpOperator.val(selectedOperatorId).trigger('change.select2');
                    confirmedPumpOperatorValue = selectedOperatorId;
                    window.pumpOperatorAwaitingConfirm = false;
                    window.pumpOperatorUserSelectInProgress = false;
                    lockPumpOperatorAfterConfirm();
                }

                $('.shift_number').text($.trim($selectedOption.text()));
                updateShiftNumberDisabledState();
                return true;
            }

            // CSRF setup
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajaxPrefilter(function (options) {
                if (!options.url) {
                    return;
                }

                var legacyPetroPdSaveRoutes = {
                    '/petropd/settlement-pd/save-other-income': '/petropd/settlement-pd/save-other-income',
                    '/petropd/settlement-pd/save-customer-payment': '/petropd/settlement-pd/save-customer-payment'
                };
                var originalUrl = options.url.toString();
                var originalPath = originalUrl.split('?')[0];
                if (legacyPetroPdSaveRoutes[originalPath]) {
                    options.url = legacyPetroPdSaveRoutes[originalPath] + originalUrl.substring(originalPath.length);
                }

                var requestUrl = options.url.toString();
                var isPetroPdSettlementRequest = requestUrl.indexOf('/petropd/settlement-pd') !== -1;
                var isPetroPdPaymentRequest = requestUrl.indexOf('/petropd/settlement/payment') !== -1;
                if (!isPetroPdSettlementRequest && !isPetroPdPaymentRequest) {
                    return;
                }

                var selectedShiftId = $('#shift_number').val() || $('#shift_id').val() || '';
                var selectedOperatorId = $('#pump_operator_id').val() || '';
                var currentSettlementId = $('#active_settlement_id').val() || '';
                var currentSettlementNo = $('#settlement_no').val() || '';

                if (typeof options.data === 'string') {
                    var appendParam = function (name, value) {
                        if (value !== '' && options.data.indexOf(name + '=') === -1) {
                            options.data += (options.data.length ? '&' : '') + encodeURIComponent(name) + '=' + encodeURIComponent(value);
                        }
                    };
                    appendParam('source', 'petro_pd');
                    appendParam('shift_id', selectedShiftId);
                    appendParam('shift_ids', selectedShiftId);
                    appendParam('pump_operator_id', selectedOperatorId);
                    appendParam('active_settlement_id', currentSettlementId);
                    appendParam('settlement_no', currentSettlementNo);
                    return;
                }

                options.data = $.extend({}, options.data || {}, {
                    source: options.data && options.data.source ? options.data.source : 'petro_pd',
                    shift_id: options.data && options.data.shift_id ? options.data.shift_id : selectedShiftId,
                    shift_ids: options.data && options.data.shift_ids ? options.data.shift_ids : selectedShiftId,
                    pump_operator_id: options.data && options.data.pump_operator_id ? options.data.pump_operator_id : selectedOperatorId,
                    active_settlement_id: options.data && options.data.active_settlement_id ? options.data.active_settlement_id : currentSettlementId,
                    settlement_no: options.data && options.data.settlement_no ? options.data.settlement_no : currentSettlementNo
                });
            });

            // ---------- Helpers ----------
            function toastError(msg = 'Enter closing meter greater than the Starting meter') {
                toastr.error(msg);
            }

            function toastSuccess(msg) {
                toastr.success(msg);
            }

            function apiGet(url, data = {}) {
                return $.ajax({
                    method: 'GET',
                    url,
                    data
                });
            }

            function apiPut(url, data = {}) {
                return $.ajax({
                    method: 'PUT',
                    url,
                    data
                });
            }

            function updateShiftNumberDisabledState() {
                const shift_id = $shiftNumber.val();
                if (shift_id) {
                    $shiftNumber.find('option').each(function() {
                        const val = $(this).val();
                        if (val !== '' && val !== shift_id) {
                            $(this).prop('disabled', true);
                        } else {
                            $(this).prop('disabled', false);
                        }
                    });
                } else {
                    $shiftNumber.find('option').prop('disabled', false);
                }
                $shiftNumber.trigger('change.select2');
            }

            function parseNumber(value) {
                return parseFloat((value || '0').toString().replace(/,/g, '')) || 0;
            }

            function formatNumber(value, precision = 2) {
                if (typeof __number_f === 'function') {
                    return __number_f(parseNumber(value), false, false, precision);
                }

                return parseNumber(value).toFixed(precision);
            }

            function upsertVisibleMeterSaleRow(result, saved) {
                const meterSaleId = result.meter_sale_id || '';
                const pumpId = saved.pump_id || '';
                const startingMeter = parseNumber(saved.starting_meter);
                const closingMeter = parseNumber(saved.closing_meter);
                const soldQty = parseNumber(saved.qty);
                const testingQty = parseNumber(saved.testing_qty);
                const unitPrice = parseNumber(saved.price);
                const subTotal = parseNumber(saved.sub_total);
                const afterDiscount = parseNumber(saved.discount_amount);
                const discount = parseNumber(saved.discount);
                const discountType = saved.discount_type || '-';
                const rowCode = typeof code !== 'undefined' ? code : '';
                const rowProductName = typeof product_name !== 'undefined' ? product_name : '';
                const rowPumpName = typeof pump_name !== 'undefined'
                    ? pump_name
                    : ($('#pump_id_pd option:selected').text() || '');
                const effectiveClosingMeter = closingMeter - testingQty;
                const rowKey = [
                    pumpId,
                    startingMeter.toFixed(2),
                    effectiveClosingMeter.toFixed(2)
                ].join('|');

                const editButton = window.petroPdCanEditMeterSale && meterSaleId
                    ? `<button type="button" class="btn btn-xs btn-primary petropd-meter-sale-edit" data-href="/petropd/settlement-pd/get-meter-sale-form/${meterSaleId}">Edit</button>`
                    : '';
                const cancelButton = window.petroPdCanDeleteMeterSale && meterSaleId
                    ? `<button type="button" class="btn btn-xs btn-danger petropd-meter-sale-cancel" data-href="/petropd/settlement-pd/delete-meter-sale/${meterSaleId}"><i class="fa fa-times"></i></button>`
                    : '';

                const rowHtml = `<tr data-petro-pd-row-key="${rowKey}"
                    data-petro-pd-pump-id="${pumpId}"
                    data-petro-pd-starting-meter="${startingMeter.toFixed(2)}"
                    data-petro-pd-effective-closing-meter="${effectiveClosingMeter.toFixed(2)}">
                    <td>${rowCode}</td>
                    <td>${rowProductName}</td>
                    <td>${rowPumpName}</td>
                    <td>${formatNumber(startingMeter, 2)}</td>
                    <td>${formatNumber(closingMeter, 2)}</td>
                    <td>${formatNumber(unitPrice, 2)}</td>
                    <td>${formatNumber(soldQty, 2)}</td>
                    <td>${discountType === '-' ? '-' : discountType}</td>
                    <td>${formatNumber(discount, 2)}</td>
                    <td>${formatNumber(testingQty, 6)}</td>
                    <td>${formatNumber(soldQty + testingQty, 2)}</td>
                    <td>${formatNumber(subTotal, 2)}</td>
                    <td>${formatNumber(afterDiscount, 2)}</td>
                    <td>${editButton} ${cancelButton}</td>
                </tr>`;

                const $tbody = $('#meter_sale_table tbody');
                const $existing = $tbody.find(`tr[data-petro-pd-row-key="${rowKey}"]`);
                if ($existing.length) {
                    $existing.first().replaceWith(rowHtml);
                    $existing.slice(1).remove();
                } else {
                    $tbody.append(rowHtml);
                }

                let total = 0;
                $tbody.find('tr').each(function () {
                    const amount = parseNumber($(this).find('td').eq(12).text());
                    total += amount;
                });

                $('#meter_sale_total').val(total.toFixed(2));
                $('.meter_sale_total').text(formatNumber(total, 2));

                if (typeof calculate_payment_tab_total === 'function') {
                    calculate_payment_tab_total();
                }
            }

            // Check previous unsettled (returns Promise<boolean>)
            async function checkPreviousUnsettled(shift_id) {
                try {
                    const res = await apiGet("{{ route('petropd.settlement-pd.check-previous-settlement') }}", {
                        shift_id,
                        pump_operator_id: $('#pump_operator_id').val()
                    });
                    if (res && res.status) return true;
                    if (res && res.msg) toastError(res.msg);
                    return false;
                } catch (e) {
                    toastError("Something went wrong.");
                    return false;
                }
            }

            // Update pump dropdown options (Select2 aware)
            function updatePumpDropdown(pump_nos = {}) {
                const $select = $('#pump_id_pd');
                $select.empty().append('<option value="">' + "@lang('petropd::lang.please_select')" + '</option>');
                $.each(pump_nos, (id, name) => $select.append(`<option value="${id}">${name}</option>`));
                $select.trigger('change.select2');
            }

            window.petroPdResetManualEntryMode = function () {
                window.petroPdManualEntryActivated = false;
                $('#pump_closing_meter').prop('readonly', true);
                $('#assignment_id').val(0);
                $('#pumper_entry_id').val(0);
                $('#is_from_pumper').val(0);
            };

            // Save small state to localStorage (if no active settlement_pd)
            // function persistLocalUpdate(data = {}) {
            //     if (!hasActiveSettlement) localStorage.setItem('lastUpdateData', JSON.stringify(data));
            // }

            // function persistLocalUpdate(data = {}) {
            //     var pump_operator_id = $('#pump_operator_id').val();
            //     var shift_number = $('#shift_number').val(); // could be array

            //     if (pump_operator_id && Array.isArray(shift_number) && shift_number.length > 0) {
            //         console.log(pump_operator_id, 'with pump', shift_number);
            //         localStorage.setItem('lastUpdateData', JSON.stringify(data));
            //     } else {
            //         console.log('pump_operator_id or shift_number is empty, Do nothing');
            //         // localStorage.removeItem('lastUpdateData');
            //     }
            // }
            let persistTimer;

            function persistLocalUpdate(data = {}) {
                clearTimeout(persistTimer); // clear previous timer if user keeps changing

                var pump_operator_id = $('#pump_operator_id').val();
                var shift_number = $('#shift_number').val();

                if (pump_operator_id && shift_number) {
                    data.pump_operator_id = pump_operator_id;
                    data.shift_number = shift_number;
                    localStorage.setItem('lastUpdateData', JSON.stringify(data));
                }
            }


            // Build data object saved to localStorage
            function buildPersistableData() {
                return {
                    note: $note.val(),
                    work_shift: $workShift.val(),
                    transaction_date: $transactionDate.val(),
                    pump_operator_id: $pumpOperator.val(),
                    shift_number: $shiftNumber.val(),
                    location_id: $location.val(),
                    pump_no: $('#pump_no').val(),
                    pump_starting_meter: $('#pump_starting_meter').val(),
                    sold_qty: $('#sold_qty').val(),
                    meter_sale_unit_price: $('#meter_sale_unit_price').val(),
                    testing_qty: $('#testing_qty').val(),
                    meter_sale_discount_type: $('#meter_sale_discount_type').val(),
                    meter_sale_discount: $('#meter_sale_discount').val()
                };
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
                    aaSorting: [
                        [0, 'desc']
                    ],
                    fnDrawCallback: function() {}
                };
                $(selector).DataTable($.extend(true, {}, defaults, opts));
            }

            // ---------- Toggle tab behaviour ----------
            function setTabToOtherIncome() {
                // $belowBox.addClass('hide');
                $('.settlement_tabs .nav-tabs li, .settlement_tabs .tab-pane').removeClass('active show');
                $('.settlement_tabs .other_income_tab').closest('li').addClass('active show');
                $('#other_income_tab').addClass('active show');
            }

            function toggle_check_operator_shift_status(e) {
                /* if ($shiftClosed.val() !== "yes") {
                    e.preventDefault();
                    toastError("Operator shift not closed.");
                    setTimeout(setTabToOtherIncome, 1000);
                    return false;
                } */
                $belowBox.removeClass('hide');
                return true;
            }
            window.toggle_check_operator_shift_status = toggle_check_operator_shift_status;

            // Attach tab click handlers (only block when shift_closed isn't 'yes')
            $doc.on('click', '.settlement_tabs .nav-tabs a.meter_sale_tab, .settlement_tabs .nav-tabs a.other_sale_tab',
                function(e) {
                    if ($shiftClosed.val() !== "yes") {
                        return toggle_check_operator_shift_status(e);
                    }
                });

            // Keep the original Bootstrap tab contract and bind directly to
            // these five links. Direct binding makes the PetroPD tabs reliable
            // even when a global document-level tab handler is also present.
            const $petroPdMainTabs = $('.settlement_tabs > .nav-tabs > li > a[data-toggle="tab"]');
            $petroPdMainTabs
                .off('click.petropdMainTabs shown.bs.tab.petropdMainTabs')
                .on('click.petropdMainTabs', function (event) {
                    event.preventDefault();
                    $(this).tab('show');
                })
                .on('shown.bs.tab.petropdMainTabs', function () {
                    const target = $(this).attr('href');

                    if (target === '#meter_sale_tab' || target === '#other_sale_tab' || target === '#payment_tab') {
                        loadSelectedShiftDetails();
                    }

                    if ($.fn.dataTable) {
                        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                    }

                    if (target === '#payment_tab' && typeof calculate_payment_tab_total === 'function') {
                        calculate_payment_tab_total();
                    }
                });

            // ---------- Update settlement_pd (AJAX) ----------
            async function handleFieldChanges() {
                const pumpOperator = $pumpOperator.val();
                const workShift = $workShift.val();
                var shift_number = $('#shift_number').val();
                const currentSettlementId = $activeSettlement.val();

                // PDST-003: Never update/clear settlement data while the page is booting or restoring after browser refresh.
                // Refresh must only re-display the existing shift/operator/payment details; it must not save blank/default values.
                if (window.isBooting || window.petroPdRestoringSettlement) {
                    if (pumpOperator && shift_number) {
                        persistLocalUpdate(buildPersistableData());
                    }
                    return;
                }

                // If pumpOperator missing, do not call api
                if (!pumpOperator || pumpOperator === "") {
                    // $('#meter_sale_tab').addClass('active show');



                    return;
                }
                if (!hasActiveSettlement && (!currentSettlementId || currentSettlementId === '0')) {
                    return;
                }
                if (pumpOperator && shift_number) {
                    // persist current fields locally
                    persistLocalUpdate(buildPersistableData());
                }

                const url = hasActiveSettlement ?
                    "{{ action('\Modules\PetroPD\Http\Controllers\PetroPDSettlementController@update', $active_settlement->id ?? 0) }}" :
                    "/petropd/settlement-pd/" + currentSettlementId;

                try {
                    const result = await apiPut(url, {
                        note: $note.val(),
                        work_shift: workShift,
                        transaction_date: $transactionDate.val(),
                        pump_operator_id: pumpOperator,
                        location_id: $location.val(),
                        shift_number: shift_number,
                        // Tells the server this was a real shift change, not a
                        // side effect of the date or location changing.
                        shift_change_confirmed: window.pdShiftChangedByUser ? 1 : 0,
                        source: 'petro_pd'
                    });

                    if (result && result.success == 1) {
                        // Consumed - a later date change must not inherit it.
                        window.pdShiftChangedByUser = false;
                        $shiftClosed.val("yes");
                        
                        // Dynamically update the add_payment button's data-href with the new shift_number and operator
                        const $addPaymentBtn = $('#add_payment');
                        if ($addPaymentBtn.length) {
                            let currentHref = $addPaymentBtn.attr('data-href');
                            if (currentHref) {
                                try {
                                    // Use a dummy base to handle relative URLs
                                    let base = window.location.origin;
                                    let urlObj = new URL(currentHref, base);
                                    urlObj.searchParams.set('shift_ids', shift_number || '');
                                    urlObj.searchParams.set('pump_operator_id', pumpOperator || '');
                                    urlObj.searchParams.set('transaction_date', $transactionDate.val() || $('#transaction_date').val() || '');
                                    var pdPaymentDueText = ($('#payment_due').text() || '0').toString().replace(/,/g, '').trim();
                                    var pdPaymentDueValue = parseFloat(pdPaymentDueText);
                                    if (!isNaN(pdPaymentDueValue)) {
                                        urlObj.searchParams.set('pd_payment_due_total', pdPaymentDueValue);
                                    }
                                    $addPaymentBtn.attr('data-href', urlObj.pathname + urlObj.search);
                                } catch (e) {
                                    console.error('Failed to parse add_payment data-href:', e);
                                }
                            }
                        }
                        
                        // Trigger payment:updated to recalculate totals
                        $(document).trigger('payment:updated');
                    } else {
                        toastError(result.msg || "Unable to update settlement_pd.");
                        $belowBox.addClass('show');
                        $shiftClosed.val("yes");
                        // $('#meter_sale_tab').addClass('active show');
                        fetchOtherSales();
                    }
                } catch (err) {
                    console.error("API Error:", err);
                    toastError("An error occurred while updating settlement_pd.");
                }




            }

            /*
             | Tracks whether the user actually changed the SHIFT.
             |
             | The server only unlinks a settlement's payments and assignments when
             | this flag is set (see EditsPdSettlements). Without it, reloading the
             | shift list after a date change looked like a shift change and wiped
             | the settlement's data.
             */
            window.pdShiftChangedByUser = false;

            $doc.on('change', '#shift_number, #work_shift', function() {
                if (!window.isBooting && !window.petroPdRestoringSettlement) {
                    window.pdShiftChangedByUser = true;
                }
            });

            // A date change is never a shift change.
            $doc.on('change', '#transaction_date', function() {
                window.pdShiftChangedByUser = false;
            });

            // wire handlers for field changes (pump_operator_id is gated by reconfirm popup below)
            $doc.on('change', '#note, #work_shift, #transaction_date, #location_id',
                handleFieldChanges);

            // ---------- Pump Operator reconfirm popup ----------
            let confirmedPumpOperatorValue = isFinishingExistingSettlement ? ($pumpOperator.val() || '') : '';
            let isRevertingPumpOperator = false;
            window.pumpOperatorAwaitingConfirm = false;

            function lockPumpOperatorAfterConfirm() {
                $pumpOperator.prop('disabled', true).trigger('change.select2');
                $('#pump_operator_reconfirmed_btn').show();
            }

            function confirmPumpOperatorSelection(newVal, newText) {
                if (!newVal || newVal === confirmedPumpOperatorValue) {
                    window.pumpOperatorUserSelectInProgress = false;
                    return;
                }

                // Block downstream change handlers (shift loader, persist) until user confirms.
                window.pumpOperatorAwaitingConfirm = true;

                swal({
                    title: 'Confirm Pump Operator',
                    text: 'Selected Pump Operator is ' + newText +
                        '. If correct, please click "Yes" and continue. If need to change click "No" and select another Pump Operator.',
                    icon: 'warning',
                    className: 'pump-operator-confirm-swal',
                    // Cancel = No (renders right after CSS row-reverse), Confirm = Yes (renders left).
                    buttons: {
                        cancel: { text: 'No', value: 'no', visible: true, closeModal: true, className: 'btn-danger' },
                        confirm: { text: 'Yes', value: 'yes', closeModal: true, className: 'btn-success' }
                    },
                    dangerMode: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false
                }).then((value) => {
                    window.pumpOperatorAwaitingConfirm = false;
                    window.pumpOperatorUserSelectInProgress = false;
                    if (value === 'yes' || value === true) {
                        confirmedPumpOperatorValue = newVal;
                        lockPumpOperatorAfterConfirm();
                    } else {
                        isRevertingPumpOperator = true;
                        $pumpOperator.val(confirmedPumpOperatorValue || '').trigger('change.select2');
                        isRevertingPumpOperator = false;
                        setTimeout(() => $pumpOperator.select2('open'), 50);
                    }
                });
            }

            // Programmatic value sets, such as localStorage restore, must not auto-confirm
            // a pump operator. User selections are handled by select2:select.
            $pumpOperator.on('change', function () {
                if (isRevertingPumpOperator) return;
                if (window.pumpOperatorAwaitingConfirm) return;
                if (window.pumpOperatorUserSelectInProgress) return;
            });

            // select2:select fires when the user picks an option. We mark a synchronous flag here
            // so the underlying `change` event (which fires immediately after) won't race ahead
            // and pre-confirm the new value before the popup resolves.
            $pumpOperator.on('select2:selecting select2:select', function () {
                window.pumpOperatorUserSelectInProgress = true;
            });

            $pumpOperator.on('select2:select', function () {
                const newVal = $(this).val();
                const newText = $(this).find('option:selected').text();
                confirmPumpOperatorSelection(newVal, newText);
                return;

                if (!newVal || newVal === confirmedPumpOperatorValue) {
                    window.pumpOperatorUserSelectInProgress = false;
                    return;
                }

                // Block downstream change handlers (shift loader, persist) until user confirms.
                window.pumpOperatorAwaitingConfirm = true;

                swal({
                    title: 'Confirm Pump Operator',
                    text: 'Selected Pump Operator is ' + newText +
                        '. If correct, please click "Yes" and continue. If need to change click "No" and select another Pump Operator.',
                    icon: 'warning',
                    className: 'pump-operator-confirm-swal',
                    // Cancel = No (renders right after CSS row-reverse), Confirm = Yes (renders left).
                    buttons: {
                        cancel: { text: 'No', value: 'no', visible: true, closeModal: true, className: 'btn-danger' },
                        confirm: { text: 'Yes', value: 'yes', closeModal: true, className: 'btn-success' }
                    },
                    dangerMode: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false
                }).then((value) => {
                    window.pumpOperatorAwaitingConfirm = false;
                    window.pumpOperatorUserSelectInProgress = false;
                    // Yes (confirm) → 'yes'; No (cancel) → 'no' or null.
                    if (value === 'yes' || value === true) {
                        confirmedPumpOperatorValue = newVal;
                        lockPumpOperatorAfterConfirm();
                    } else {
                        isRevertingPumpOperator = true;
                        $pumpOperator.val(confirmedPumpOperatorValue || '').trigger('change.select2');
                        isRevertingPumpOperator = false;
                        setTimeout(() => $pumpOperator.select2('open'), 50);
                    }
                });
            });

            $doc.on('click', '#pump_operator_reconfirmed_btn', function () {
                if (!confirmedPumpOperatorValue) {
                    toastr.error('Please select and confirm a Pump Operator first.');
                    return;
                }

                /*
                 |--------------------------------------------------------------
                 | Reconfirming the SAME operator must not discard loaded data.
                 |--------------------------------------------------------------
                 |
                 | Reported: after changing the date and pressing this button, the
                 | data that was already loaded disappeared.
                 |
                 | The button re-triggers the pump operator change handler, and
                 | that handler EMPTIES the shift dropdown and fires a change on
                 | it. The listener on #shift_number then sets
                 |     window.pdShiftChangedByUser = true
                 | and the server, seeing that flag, unlinks the settlement's
                 | payments and assignments - exactly the behaviour LA-1183 added
                 | for a genuine shift change.
                 |
                 | But nothing has actually changed here. The operator is the one
                 | already confirmed; the button only re-runs the load.
                 |
                 | The flag is suppressed for the duration, using the same guard
                 | the boot sequence uses, then restored. A real change made by the
                 | user afterwards still sets it normally.
                 */
                var pdWasRestoring = window.petroPdRestoringSettlement;
                window.petroPdRestoringSettlement = true;

                $pumpOperator.trigger('change');

                // Released after the handler and its select2 events have run.
                setTimeout(function () {
                    window.petroPdRestoringSettlement = pdWasRestoring;
                    window.pdShiftChangedByUser = false;
                }, 0);
            });

            // persist small changes when there's no active settlement_pd
            // if (!hasActiveSettlement) {
            //     $doc.on('change', '#pump_no, #pump_starting_meter, #sold_qty, #meter_sale_unit_price, #testing_qty, #meter_sale_discount_type, #meter_sale_discount', () =>
            //         persistLocalUpdate(buildPersistableData())
            //     );
            // }
            // let persistTimer;

            $doc.on('change',
                '#pump_no,#pump_operator_id,#pump_starting_meter,#sold_qty,#meter_sale_unit_price,#testing_qty,#meter_sale_discount_type,#meter_sale_discount',
                () => {
                    if (window.pumpOperatorAwaitingConfirm) return;
                    // clearTimeout(persistTimer); // clear previous timer if user keeps changing

                    // persistTimer = setTimeout(() => {
                    persistLocalUpdate(buildPersistableData());
                    // }, 5000); // 5 seconds delay
                });

            // ---------- Load shifts when pump operator is selected ----------
            $doc.on('change', '#pump_operator_id', function() {
                // Block until reconfirm popup resolves with Yes.
                if (window.pumpOperatorAwaitingConfirm) return;
                // When shift selection drives pump operator sync, do not wipe/reload the shift dropdown.
                if (window.skipOperatorShiftReload) {
                    window.skipOperatorShiftReload = false;
                    if (typeof window.petroPdRefreshManualEntryButton === 'function') {
                        window.petroPdRefreshManualEntryButton();
                    }
                    return;
                }

                const operatorId = $(this).val();
                const $shiftSel = $('#shift_number');

                if (!hasActiveSettlement && (!confirmedPumpOperatorValue || String(operatorId) !== String(confirmedPumpOperatorValue))) {
                    return;
                }

                window.petroPdResetManualEntryMode();
                $shiftSel.empty().append('<option value="">{{ __("petropd::lang.please_select") }}</option>');
                $shiftSel.val('').trigger('change.select2');
                if (typeof window.petroPdClearMeterSaleTable === 'function') {
                    window.petroPdClearMeterSaleTable();
                }
                updatePumpDropdown({});
                // $belowBox.addClass('hide');

                if (!operatorId) {
                    if (typeof window.petroPdRefreshManualEntryButton === 'function') {
                        window.petroPdRefreshManualEntryButton();
                    }
                    return;
                }

                apiGet("{{ route('petropd.get-operator-shifts') }}", { pump_operator_id: operatorId })
                    .done(function(res) {
                        if (res.success && res.optionHtml) {
                            $shiftSel.append(res.optionHtml);
                            const $firstEnabled = $shiftSel.find('option:not([disabled]):not([value=""])').first();
                            if ($firstEnabled.length) {
                                $shiftSel.val($firstEnabled.val());
                                skipUnsettledCheck = true;
                                $shiftSel.trigger('change');
                                skipUnsettledCheck = false;
                            } else if (typeof window.petroPdClearMeterSaleTable === 'function') {
                                window.petroPdClearMeterSaleTable();
                            }
                        } else if (typeof window.petroPdClearMeterSaleTable === 'function') {
                            window.petroPdClearMeterSaleTable();
                        }
                        if (typeof window.petroPdRefreshManualEntryButton === 'function') {
                            window.petroPdRefreshManualEntryButton();
                        }
                    })
                    .fail(function() {
                        if (typeof window.petroPdClearMeterSaleTable === 'function') {
                            window.petroPdClearMeterSaleTable();
                        }
                        toastError('Failed to load shifts for this operator.');
                    });
            });

            // ---------- Pump select change → load starting/closing meter ----------
            $doc.on('change', '#pump_id_pd', function() {
                pump_closing_meter = 0.0;
                pump_starting_meter = 0.0;
                var pump_val = $(this).val();
                var shift_id = $('#shift_number').val().toString();

                if (!pump_val) {
                    $('#pump_closing_meter').prop('disabled', false).prop('readonly', true);
                    $('#assignment_id').val(0);
                    $('#pumper_entry_id').val(0);
                    $('#is_from_pumper').val(0);
                    return;
                }

                if (!shift_id) {
                    if (typeof window.isBooting === 'undefined' || !window.isBooting) {
                        toastr.error('Please select a Shift Number first.');
                    }
                    return;
                }

                $.ajax({
                    method: 'get',
                    url: '/petropd/settlement-pd/get-pump-details/' + pump_val + '/' + shift_id,
                    success: function(result) {
                        if (result.is_open > 0) {
                            toastr.error('Please close the pump first before adding a meter sale!');
                            return;
                        }

                        $('#pump_starting_meter').val(result.colsing_value);
                        var hasPendingPumperAssignment = (parseInt(result.assignment_id, 10) || 0) > 0;
                        $('#assignment_id').val(result.assignment_id || 0);
                        $('#pumper_entry_id').val(result.pumper_entry_id || 0);

                        if (result.po_closing > 0) {
                            $('#pump_closing_meter').val(result.po_closing);
                            $('#pump_closing_meter')
                                .prop('disabled', false)
                                .prop('readonly', !window.petroPdManualEntryActivated);
                            if (window.petroPdManualEntryActivated) {
                                $('#pump_closing_meter').removeAttr('disabled readonly');
                            }
                            $('#pump_closing_meter').trigger('change');
                            $('#is_from_pumper').val(1);
                        } else {
                            $('#pump_closing_meter').val('');
                            $('#pump_closing_meter')
                                .prop('disabled', false)
                                .prop('readonly', hasPendingPumperAssignment && !window.petroPdManualEntryActivated);
                            if (window.petroPdManualEntryActivated) {
                                $('#pump_closing_meter').removeAttr('disabled readonly');
                            }
                            $('#is_from_pumper').val(0);
                        }

                        if (result.po_testing > 0) {
                            $('#testing_qty').val(result.po_testing);
                            $('#testing_qty').prop('readonly', true);
                            $('#testing_qty').trigger('change');
                        } else {
                            $('#testing_qty').val(0);
                            $('#testing_qty').prop('readonly', false);
                        }

                        pump_starting_meter = parseFloat(result.colsing_value);
                        tank_qty = result.tank_remaing_qty;
                        code = result.product.sku;
                        price = result.product.default_sell_price;
                        product_name = result.product.name;
                        pump_name = result.pump_name;
                        pump_id = result.pump_id;
                        product_id = result.product_id;

                        if (result.bulk_sale_meter == '1') {
                            $('#bulk_sale_meter').val(1);
                            $('.pump_starting_meter_div').addClass('hide');
                            $('.pump_closing_meter_div').addClass('hide');
                            $('#sold_qty').prop('disabled', false);
                        } else {
                            $('#bulk_sale_meter').val(0);
                            $('.pump_starting_meter_div').removeClass('hide');
                            $('.pump_closing_meter_div').removeClass('hide');
                            $('#sold_qty').prop('disabled', true);
                        }

                        $('#meter_sale_unit_price').val(price);
                    },
                    error: function(xhr) {
                        toastr.error('Failed to load pump details. Please try again.');
                        console.error('Pump details AJAX error:', xhr.status, xhr.responseText);
                    }
                });
            });

            // ---------- Shift number change handler ----------
            $doc.on('change', '#shift_number', async function() {
                const shift_id = $(this).val();

                /*
                 * MA-002: choosing a shift RELOADS the page for that shift.
                 *
                 * The controller now honours ?shift_number=, and it loads
                 * everything for that shift in one pass - meter sales,
                 * payments, totals, pump list, the lot. Reloading is therefore
                 * the reliable way to switch shifts: every figure on the page
                 * comes from the same shift, and none can be left over from
                 * the previous one.
                 *
                 * The ajax work below stitches individual pieces together and
                 * cannot refresh all of them, which is how the page came to
                 * show one shift's number beside another shift's sales.
                 *
                 * The operator is carried along so the shift list stays
                 * filtered to that operator - the page reloads showing the
                 * same operator you had chosen.
                 *
                 * If the shift has not actually changed, nothing happens - so
                 * select2 re-triggering its own change cannot cause a loop.
                 */
                var pdCurrentShift = String(@json((string) ($shift_id ?? '')));

                if (shift_id && String(shift_id) !== pdCurrentShift) {
                    var pdOperatorForReload = $('#pump_operator_id').val() || '';
                    var pdUrl = new URL(window.location.href);

                    pdUrl.searchParams.set('shift_number', shift_id);

                    if (pdOperatorForReload) {
                        pdUrl.searchParams.set('pump_operator', pdOperatorForReload);
                    }

                    window.location.href = pdUrl.toString();

                    return;
                }

                /*
                 * MA-002: choosing a shift now loads its operator.
                 *
                 * The order of work is: pick the shift, and the operator that
                 * shift belongs to fills in automatically - rather than the
                 * person having to choose both and risk pairing a shift with
                 * the wrong operator.
                 *
                 * The operator id is already on each option as
                 * data-pump-operator-id, written from shift_operator_map,
                 * which the controller builds from the SAME query that lists
                 * the shifts. So the operator shown is the one that shift
                 * actually belongs to.
                 *
                 * Nothing is changed when the option carries no operator - the
                 * field is left as it is rather than being blanked.
                 */
                var pdSelectedOperator = $(this).find('option:selected').data('pump-operator-id');

                if (pdSelectedOperator && String(pdSelectedOperator) !== '0') {
                    var $pdOperator = $('#pump_operator_id');

                    if ($pdOperator.length && String($pdOperator.val()) !== String(pdSelectedOperator)) {
                        $pdOperator.val(String(pdSelectedOperator));

                        // select2 needs telling, or the box shows the old name.
                        if ($pdOperator.hasClass('select2') || $pdOperator.data('select2')) {
                            $pdOperator.trigger('change.select2');
                        }
                    }
                }

                updateShiftNumberDisabledState();
                window.petroPdResetManualEntryMode();

                if (!shift_id) {
                    // $belowBox.addClass('hide');
                    $('#add_payment').prop('disabled', true).addClass('disabled');
                    $('#work_shift').val([]).trigger('change');
                    updatePumpDropdown({});
                    if (typeof window.petroPdClearMeterSaleTable === 'function') {
                        window.petroPdClearMeterSaleTable();
                    }
                    if (typeof window.petroPdRefreshManualEntryButton === 'function') {
                        window.petroPdRefreshManualEntryButton();
                    }
                    return;
                }

                // check unsettled previous shifts (skip if triggered programmatically)
                if (!skipUnsettledCheck) {
                    const ok = await checkPreviousUnsettled(shift_id);
                    if (!ok) return;
                }

                $belowBox.removeClass('hide');
                $('#add_payment').prop('disabled', false).removeClass('disabled');

                const label = $shiftNumber.find('option[value="' + shift_id + '"]').text();
                $('.shift_number').html(label);

                const operatorBeforeShiftSync = $pumpOperator.val();
                if (operatorBeforeShiftSync) {
                    loadSelectedShiftDetails();
                }

                // Load work shift linked to petro_shifts row. Pump operator must be chosen
                // explicitly by the user, so do not sync it from the shift response.
                apiGet("{{ route('petropd.get-shift-work-shift') }}", { shift_id: shift_id })
                    .done(function(res) {
                        const $ws = $('#work_shift');
                        const wsIds = (res.success && res.work_shift_id != null && res.work_shift_id !== '')
                            ? [String(res.work_shift_id)]
                            : [];
                        $ws.val(wsIds);
                        $pumpOperator.removeAttr('title');

                        $ws.trigger('change');

                        if (typeof window.petroPdRefreshManualEntryButton === 'function') {
                            window.petroPdRefreshManualEntryButton();
                        }
                        // Reload after all shift-dependent fields have settled. The
                        // earlier request may have been superseded while restoring
                        // the active settlement context.
                        loadSelectedShiftDetails();
                        setTimeout(function () {
                            if (typeof calculate_payment_tab_total === 'function') {
                                calculate_payment_tab_total();
                            }
                            if (typeof petroPdSyncPaymentFinalizeTotal === 'function') {
                                petroPdSyncPaymentFinalizeTotal();
                            }
                        }, 150);
                    })
                    .fail(function() {
                        if (typeof window.petroPdRefreshManualEntryButton === 'function') {
                            window.petroPdRefreshManualEntryButton();
                        }
                    });
            });

            // ---------- Data loading functions ----------
            function loadSelectedShiftDetails() {
                if (!$shiftNumber.val() || !$pumpOperator.val()) {
                    return;
                }

                loadOtherSalesData();
                loadMeterSalesData();
            }
            window.petroPdLoadSelectedShiftDetails = loadSelectedShiftDetails;

            function loadOtherSalesData() {
                $('#outside_other_sale_table').show();
                $('#other_sale_table').hide();

                initOrReloadDataTable('#pump_operator_other_sale_table', {
                    ajax: {
                        url: "{{ action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorPaymentController@otherSalesList') }}",
                        data: function(d) {
                            d.shift_ids = [$('#shift_number').val()];
                            d.active_settlement_id = $('#active_settlement_id').val() || $('#settlement_no').val();
                            d.merge_manual = 1;
                        },
                    },
                    columnDefs: [{
                        targets: 0,
                        orderable: false,
                        searchable: false
                    }],
                    columns: [{
                            data: 'product_sku',
                            name: 'products.sku'
                        },
                        {
                            data: 'product_name',
                            name: 'products.name'
                        },
                        {
                            data: 'qty_available',
                            name: 'qty_available',
                            className: 'text-right'
                        },
                        {
                            data: 'price',
                            name: 'price',
                            className: 'text-right'
                        },
                        {
                            data: 'quantity',
                            name: 'quantity',
                            className: 'text-right'
                        },
                        {
                            data: 'discount_type',
                            name: 'discount_type'
                        },
                        {
                            data: 'discount',
                            name: 'discount',
                            className: 'text-right'
                        },
                        {
                            data: 'sub_total',
                            name: 'sub_total',
                            className: 'text-right'
                        },
                        {
                            data: 'with_discount',
                            name: 'with_discount',
                            className: 'text-right'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    fnDrawCallback() {
                        const total = sum_table_col($('#pump_operator_other_sale_table'), 'with_discount');
                        $('#footer_list_other_sales_amount').val(total).text(total);
                        __currency_convert_recursively($('#pump_operator_other_sale_table'));
                    }
                });

                // fetch totals & pump nos

                fetchOtherSales();
                // apiGet("{{ action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorPaymentController@otherSalesList') }}", {
                //     shift_ids: $shiftNumber.val(),
                //     get_total: true
                // }).done(result => {
                //     if (result && result.success == 1) {
                //         updatePumpDropdown(result.pump_nos || {});
                //         $('#shift_operator_other_sale_total').val(parseFloat(result.total) || 0);
                //         $('#other_sale_total').val(0);
                //         calculate_payment_tab_total();
                //     } else {
                //         toastError("Error fetching other sale total");
                //     }
                // }).fail(() => toastError("Error fetching other sale data"));
            }


            function fetchOtherSales() {
                var shift_id_val = $('#shift_number').val();
                if (!shift_id_val) {
                    return; // nothing selected, exit
                }
                apiGet("{{ action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorPaymentController@otherSalesList') }}", {
                    shift_ids: [shift_id_val],
                    get_total: true
                }).done(result => {
                    if (result && result.success == 1) {
                        updatePumpDropdown(result.pump_nos || {});

                        $('#shift_operator_other_sale_total').val(parseFloat(result.total) || 0);
                        $('#other_sale_total').val(0);
                        calculate_payment_tab_total();
                    } else {
                        toastError("Error fetching other sale total");
                    }
                }).fail(() => toastError("Error fetching other sale data"));
            }


            function loadMeterSalesData() {
                if (typeof window.petroPdRunManualEntryTableLoad === 'function') {
                    window.petroPdRunManualEntryTableLoad({ silent: true });
                }
                return;
                // Keep #meter_sale_table visible: it is filled by Manual Entry / shift autoload (petroPdRunManualEntryTableLoad).

                initOrReloadDataTable('#pump_operator_meter_sale_table', {
                    ajax: {
                        url: "{{ action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorPaymentController@meterSalesList') }}",
                        data: d => {
                            d.shift_ids = [$shiftNumber.val()];
                            d.active_settlement_id = $activeSettlement.val();
                            d.pump_operator_id = $pumpOperator.val();
                        }
                    },
                    columnDefs: [{
                        targets: 0,
                        orderable: false,
                        searchable: false
                    }],
                    columns: [{
                            data: 'product_sku',
                            name: 'products.sku'
                        },
                        {
                            data: 'product_name',
                            name: 'products.name'
                        },
                        {
                            data: 'pump_name',
                            name: 'pump_name'
                        },
                        {
                            data: 'starting_meter',
                            name: 'starting_meter'
                        },
                        {
                            data: 'closing_meter',
                            name: 'closing_meter'
                        },
                        {
                            data: 'price',
                            name: 'price'
                        },
                        {
                            data: 'quantity',
                            name: 'quantity'
                        },
                        {
                            data: 'discount_type',
                            name: 'discount_type'
                        },
                        {
                            data: 'discount',
                            name: 'discount_value'
                        },
                        {
                            data: 'testing_qty',
                            name: 'testing_qty'
                        },
                        {
                            data: 'total_qty',
                            name: 'total_qty'
                        },
                        {
                            data: 'sub_total',
                            name: 'sub_total'
                        },
                        {
                            data: 'discount_amount',
                            name: 'discount_amount'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            render(data, type, row) {
                                const canEdit = window.petroPdCanEditMeterSale === true;
                                const canDelete = window.petroPdCanDeleteMeterSale === true;
                                const showDelete = (row.later_settlements < 1 || !row.transaction_id ||
                                    row.bulk_tank == 1);
                                const editBtn = canEdit
                                    ? `<button type="button" class="btn btn-xs btn-primary petropd-meter-sale-edit" data-href="/petropd/settlement-pd/get-meter-sale-form/${row.id}">Edit</button>`
                                    : '';
                                const deleteBtn = canDelete && showDelete
                                    ? `<button type="button" class="btn btn-xs btn-danger petropd-meter-sale-cancel" data-href="/petropd/settlement-pd/delete-meter-sale/${row.id}"><i class="fa fa-times"></i></button>`
                                    : '';
                                return editBtn + (editBtn && deleteBtn ? ' ' : '') + deleteBtn;
                            }
                        }
                    ],
                    fnDrawCallback() {
                        const total = sum_table_col($('#pump_operator_meter_sale_table'), 'sub_total');
                        $('#footer_list_meter_sales_amount').val(total).text(total);

                        // Keep Payments tab totals in sync with the shift-based meter sales table.
                        // Payments summary reads from `#meter_sale_total` via `calculate_payment_tab_total()`.
                        const numericTotal = parseFloat(total) || 0;
                        $('#meter_sale_total').val(numericTotal);
                        if (typeof calculate_payment_tab_total === 'function') {
                            calculate_payment_tab_total();
                        }
                        __currency_convert_recursively($('#pump_operator_meter_sale_table'));

                        // ✅ Get all pump IDs from the table
                        setTimeout(function() {
                            const tableData = $('#pump_operator_meter_sale_table').DataTable().rows()
                                .data();

                            tableData.each(function(row) {
                                const pump_id = row
                                .pump_id; // assuming your AJAX returns `pump_id`
                                console.log(pump_id, 'pump of table');

                                $('#pump_id_pd')
                                    .find('option[value="' + pump_id +
                                    '"]') // added quotes around value
                                    .remove();
                            });
                        }, 3000); // 10000 ms = 10 seconds

                    }
                });
            }


            // ---------- LocalStorage restore ----------
            function restoreLocalData() {
                if (isFinishingExistingSettlement) return;

                const lastData = localStorage.getItem('lastUpdateData');
                if (!lastData) return;

                try {
                    const data = JSON.parse(lastData);
                    const savedOperator = data.pump_operator_id || '';
                    const savedShift = data.shift_number || '';
                    const serverInitialShift = {!! json_encode($shift_id ?? '') !!};
                    const serverInitialOperator = {!! json_encode($pump_operator_id ?? ($active_settlement->pump_operator_id ?? '')) !!};

                    /*
                     * IS1715: lastUpdateData can belong to a previously finalized shift.
                     * Never let stale browser data replace the closed shift/operator selected
                     * by the server. This also prevents Select2 from falling back to
                     * "Please Select" when the old shift is no longer in the pending list.
                     */
                    const staleServerSelection = serverInitialShift && serverInitialOperator && (
                        (savedShift && String(savedShift) !== String(serverInitialShift)) ||
                        (savedOperator && String(savedOperator) !== String(serverInitialOperator))
                    );

                    if (staleServerSelection) {
                        localStorage.removeItem('lastUpdateData');
                        return;
                    }

                    $note.val(data.note || '');
                    // Keep user's selected Transaction Date. Do not overwrite it with an
                    // older locally saved/blank value during browser refresh restore.
                    if (!$transactionDate.val() && data.transaction_date) {
                        $transactionDate.val(data.transaction_date);
                    }
                    $location.val(data.location_id || '').trigger('change.select2');
                    $workShift.val(data.work_shift || []).trigger('change.select2');

                    /*
                     * PDST-002: Refresh-safe restore.
                     * The previous restore only called change.select2, which updates Select2 UI but does not
                     * run the shift change loader. Therefore, after browser refresh the selected shift/operator
                     * could display while payment details, meter sales and totals were not reloaded.
                     * This restores the selected operator/shift, keeps the operator confirmed, updates Add Payment
                     * link, then triggers the real shift change once the page is ready.
                     */
                    if (savedOperator) {
                        confirmedPumpOperatorValue = savedOperator;
                        window.pumpOperatorAwaitingConfirm = false;
                        window.pumpOperatorUserSelectInProgress = false;
                        window.skipOperatorShiftReload = true;
                        $pumpOperator.val(savedOperator).trigger('change.select2');
                        lockPumpOperatorAfterConfirm();
                    }

                    if (savedShift) {
                        $shiftNumber.val(savedShift).trigger('change.select2');
                        updateShiftNumberDisabledState();
                    }

                    setTimeout(function () {
                        if (savedOperator) {
                            window.skipOperatorShiftReload = true;
                            $pumpOperator.val(savedOperator).trigger('change.select2');
                            lockPumpOperatorAfterConfirm();
                        }

                        if (savedShift) {
                            $shiftNumber.val(savedShift).trigger('change.select2');
                            const label = $shiftNumber.find('option[value="' + savedShift + '"]').text();
                            if (label) {
                                $('.shift_number').html(label);
                            }

                            const $addPaymentBtn = $('#add_payment');
                            if ($addPaymentBtn.length) {
                                let currentHref = $addPaymentBtn.attr('data-href');
                                if (currentHref) {
                                    try {
                                        let urlObj = new URL(currentHref, window.location.origin);
                                        urlObj.searchParams.set('shift_ids', savedShift || '');
                                        urlObj.searchParams.set('pump_operator_id', savedOperator || '');
                                        urlObj.searchParams.set('transaction_date', $transactionDate.val() || $('#transaction_date').val() || '');
                                        var pdPaymentDueText = ($('#payment_due').text() || '0').toString().replace(/,/g, '').trim();
                                        var pdPaymentDueValue = parseFloat(pdPaymentDueText);
                                        if (!isNaN(pdPaymentDueValue)) {
                                            urlObj.searchParams.set('pd_payment_due_total', pdPaymentDueValue);
                                        }
                                        $addPaymentBtn.attr('data-href', urlObj.pathname + urlObj.search);
                                    } catch (ignore) {}
                                }
                            }

                            // Run the real change handler to reload shift details, meter sales,
                            // other sales and payment/balance calculations after refresh.
                            skipUnsettledCheck = true;
                            $shiftNumber.trigger('change');
                            setTimeout(function(){ skipUnsettledCheck = false; }, 1200);
                        }

                        if (data.pump_no) {
                            $('#pump_no').val(data.pump_no).trigger('change');
                        }
                    }, 700);
                } catch (e) {
                    console.error('Error parsing saved data:', e);
                }

            }

            // ---------- PDST-003: Active settlement refresh restore ----------
            function refreshActiveSettlementDisplay() {
                const initialShiftId = {!! json_encode($shift_id ?? '') !!};
                const initialOperatorId = {!! json_encode($pump_operator_id ?? ($active_settlement->pump_operator_id ?? '')) !!};

                if (!hasActiveSettlement && !isFinishingExistingSettlement) {
                    return;
                }

                if (!initialShiftId || !initialOperatorId) {
                    return;
                }

                window.petroPdRestoringSettlement = true;
                window.skipOperatorShiftReload = true;
                skipUnsettledCheck = true;

                $pumpOperator.val(initialOperatorId).trigger('change.select2');
                confirmedPumpOperatorValue = initialOperatorId;
                window.pumpOperatorAwaitingConfirm = false;
                window.pumpOperatorUserSelectInProgress = false;
                lockPumpOperatorAfterConfirm();

                $shiftNumber.val(initialShiftId).trigger('change.select2');
                updateShiftNumberDisabledState();

                const label = $shiftNumber.find('option[value="' + initialShiftId + '"]').text();
                if (label) {
                    $('.shift_number').html(label);
                }

                const $addPaymentBtn = $('#add_payment');
                if ($addPaymentBtn.length) {
                    let currentHref = $addPaymentBtn.attr('data-href');
                    if (currentHref) {
                        try {
                            let urlObj = new URL(currentHref, window.location.origin);
                            urlObj.searchParams.set('shift_ids', initialShiftId || '');
                            urlObj.searchParams.set('pump_operator_id', initialOperatorId || '');
                            urlObj.searchParams.set('transaction_date', $transactionDate.val() || $('#transaction_date').val() || '');
                            var pdPaymentDueText = ($('#payment_due').text() || '0').toString().replace(/,/g, '').trim();
                            var pdPaymentDueValue = parseFloat(pdPaymentDueText);
                            if (!isNaN(pdPaymentDueValue)) {
                                urlObj.searchParams.set('pd_payment_due_total', pdPaymentDueValue);
                            }
                            $addPaymentBtn.attr('data-href', urlObj.pathname + urlObj.search);
                        } catch (ignore) {}
                    }
                    $addPaymentBtn.prop('disabled', false).removeClass('disabled');
                }

                $belowBox.removeClass('hide');

                // Reload visible tables/totals from saved settlement and pumper-dashboard source data.
                // This is display-only; handleFieldChanges is guarded by petroPdRestoringSettlement above.
                loadSelectedShiftDetails();

                setTimeout(function () {
                    // Run one final context-stable load after local/session restore.
                    // The loader aborts any older request and de-duplicates rows.
                    loadSelectedShiftDetails();
                    if (typeof calculate_payment_tab_total === 'function') {
                        calculate_payment_tab_total();
                    }
                    skipUnsettledCheck = false;
                    window.skipOperatorShiftReload = false;
                    window.petroPdRestoringSettlement = false;
                }, 1200);
            }

            // ---------- Modal loaders & buttons ----------
            // IS1508: Before opening Add Payment, always carry the currently selected transaction date.
            $doc.on('click', '#add_payment', function () {
                const $btn = $(this);
                const currentHref = $btn.attr('data-href');
                if (!currentHref) { return; }
                try {
                    let urlObj = new URL(currentHref, window.location.origin);
                    urlObj.searchParams.set('transaction_date', $transactionDate.val() || $('#transaction_date').val() || '');
                    $btn.attr('data-href', urlObj.pathname + urlObj.search);
                } catch (ignore) {}
            });

            // `#add_payment` is handled by the global `.btn-modal` loader in `public/js/app.js`.

            /*
             * S 639: THE PREVIEW IS NO LONGER A MODAL.
             *
             * WHY THIS IS DIFFERENT FROM THE FOUR ATTEMPTS BEFORE IT
             * -----------------------------------------------------
             * Every previous fix tried to make a Bootstrap modal open on top of
             * another Bootstrap modal. Each worked and each exposed the next
             * layer of the same cause - four scripts contend over these modals
             * (the .btn-modal loader in app.js, global-modal-rescue.js,
             * erp-global-modal-system.js and this file), and Add Payment's close
             * never completes:
             *
             *   nested modals    -> both unwound, page left shaded, save abandoned
             *   wait for hidden  -> event never fired, nothing happened at all
             *   remove the fade  -> opened at opacity 0, invisible
             *   force opacity    -> Add Payment never went away, the two overlapped
             *
             * So the preview stops being a modal. It is a plain fixed panel with
             * its own class, its own stacking and its own scrolling. There is no
             * backdrop to contend over, no transition to lose, no open-count to
             * corrupt, and nothing for the other three scripts to act on -
             * they all key off .modal.
             *
             * The preview HTML is unchanged: it is modal-dialog / modal-content,
             * which are ordinary Bootstrap classes and style correctly inside any
             * container. Close and Confirm are handled here, since Bootstrap's
             * data-dismiss and hidden.bs.modal no longer apply.
             */
            if (!document.getElementById('pd-preview-panel-style')) {
                $('head').append(
                    '<style id="pd-preview-panel-style">' +
                    '.pd-preview-panel{position:fixed!important;top:0!important;left:0!important;' +
                    'right:0!important;bottom:0!important;z-index:200000!important;display:none;' +
                    'overflow-y:auto!important;overflow-x:hidden!important;' +
                    'background:rgba(0,0,0,.5)!important;opacity:1!important;padding:24px 0!important;}' +
                    '.pd-preview-panel.pd-preview-open{display:block!important;}' +
                    /*
                     * S 639: the preview partial carries style="width: 65%" from
                     * its modal days, which leaves the wider settlement tables -
                     * credit sales in particular - running off the edge. The
                     * panel is not a modal any more, so it can use the width it
                     * needs.
                     */
                    '.pd-preview-panel .modal-dialog{width:96%!important;max-width:1600px!important;' +
                    'margin:0 auto!important;}' +
                    '.pd-preview-panel .modal-body{padding:15px!important;overflow-x:auto!important;}' +
                    '.pd-preview-panel table{width:100%!important;}' +
                    '.pd-preview-panel .modal-content{box-shadow:0 12px 40px rgba(0,0,0,.35)!important;}' +
                    '.pd-preview-panel .modal-body{max-height:none!important;overflow:visible!important;}' +
                    '.pd-preview-panel .no-print,.pd-preview-panel .petropd-print-toolbar{display:none!important;}' +
                    '</style>'
                );
            }

            window.pdCloseSettlementPreview = function () {
                var $preview = $('.preview_settlement');

                $preview.removeClass('pd-preview-open').empty();
                $('body').css('overflow', '');

                // Add Payment was hidden by hand, so it is reset by hand.
                $('.add_payment').removeClass('in').css('display', '').attr('aria-hidden', 'true');

                // Nothing here creates a backdrop, but earlier attempts could
                // leave one behind; clear any that is not owned by a live modal.
                if (!$('.modal.in').length) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }

                window.pdAddPaymentWasOpen = false;
            };

            /*
             |------------------------------------------------------------------
             | The preview opens ONCE per click.
             |------------------------------------------------------------------
             |
             | Reported: the reconfirmation pop-up showed the cash amount doubled -
             | 217,443.91 appearing twice - while the server log proved it sent that
             | payment once:
             |
             |     cash_payments_count: 1
             |     cash_payments_total: 217443.91
             |
             | The browser console showed why. A single click produced:
             |
             |     save settlement clicked
             |     S639 finalize: opening preview before save
             |     S639 preview: shown as panel
             |     save settlement clicked          <- again
             |     S639 finalize: opening preview before save
             |     S639 preview: shown as panel     <- again
             |
             | The handler runs twice, so the preview is built twice and its figures
             | are rendered twice.
             |
             | The guard below drops a second call while one is already in flight.
             | It is released when the load finishes, so the preview can be opened
             | again normally afterwards - only the immediate repeat is ignored.
             |
             | This is deliberately at the OPENER rather than the button: whatever
             | binds the click twice, the preview can now only build once.
             */
            window.pdSettlementPreviewOpening = false;

            window.pdOpenSettlementPreview = function (url) {
                if (window.pdSettlementPreviewOpening) {
                    window.console && console.log('S639 preview: already opening, ignored');
                    return;
                }

                window.pdSettlementPreviewOpening = true;

                // Never let the guard stick if something goes wrong mid-load.
                setTimeout(function () {
                    window.pdSettlementPreviewOpening = false;
                }, 4000);

                if (!url) {
                    window.console && console.warn('S639 preview: no url');
                    return;
                }

                var $preview = $('.preview_settlement');

                if (!$preview.length) {
                    window.console && console.error('S639 preview: container missing');
                    window.toastr && toastr.error('Unable to open the settlement preview.');
                    return;
                }

                /*
                 * Take the container out of Bootstrap's world entirely. The
                 * other modal scripts all select on .modal, so once these
                 * classes are gone none of them can act on it.
                 */
                $preview
                    .removeClass('modal fade in erp-modal-system erp-modal-wide')
                    .addClass('pd-preview-panel');

                /*
                 * S 639: LOAD FIRST, SWAP ONCE.
                 *
                 * Add Payment used to be hidden immediately and the preview shown
                 * when its request came back a second or two later - so the
                 * settlement page flashed into view in between, which looked like
                 * the click had gone wrong.
                 *
                 * The fetch now happens while Add Payment is still on screen, and
                 * the two swap in the same tick. Nothing is visible in between.
                 */
                var $addPayment = $('.add_payment');
                var $finalizeBtn = $('#settlement_save_btn');
                var finalizeLabel = $finalizeBtn.html();

                $finalizeBtn.prop('disabled', true)
                    .html('<i class="fa fa-spinner fa-spin"></i> Loading preview...');

                window.console && console.log('S639 preview: loading', url);

                $preview.load(url, function (response, status) {
                    $finalizeBtn.prop('disabled', false).html(finalizeLabel);

                    if (status === 'error') {
                        window.console && console.error('S639 preview: load failed');
                        window.pdFinalizeAwaitingPreview = false;
                        $preview.empty();
                        window.toastr && toastr.error('Unable to open the settlement preview.');
                        return;
                    }

                    // Both in the same tick - no frame shows the page behind.
                    if ($addPayment.length && $addPayment.hasClass('in')) {
                        window.console && console.log('S639 preview: hiding Add Payment');
                        $addPayment.removeClass('fade in').css('display', 'none').attr('aria-hidden', 'true');
                        try { $addPayment.modal('hide'); } catch (ignore) {}
                    }

                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');

                    $preview.addClass('pd-preview-open').scrollTop(0);
                    $('body').css({ overflow: 'hidden', paddingRight: '' });

                    window.console && console.log('S639 preview: shown as panel');
                    window.pdSettlementPreviewOpening = false;
                });
            };

            $doc.on('click', '#payment_review_btn, #payment_review_btn_secondary, #product_preview_btn', function (e) {
                e.preventDefault();
                window.pdOpenSettlementPreview($(this).data('href'));
            });

            /*
             * Close. data-dismiss="modal" no longer does anything here, so the
             * header X and the footer Close button are handled directly.
             */
            $doc.on('click', '.pd-preview-panel [data-dismiss="modal"]', function (e) {
                e.preventDefault();

                // Closing without confirming: the next Finalize asks again.
                window.pdFinalizeAwaitingPreview = false;
                $('#settlement_save_btn').data('previewConfirmed', false);

                window.pdCloseSettlementPreview();
            });

            /*
             * Confirm. The button's own handler inside payment_preview.blade.php
             * sets previewConfirmed and re-triggers Finalize; this only clears
             * the panel out of the way first. previewConfirmed is NOT reset here.
             */
            $doc.on('click', '#pd_preview_confirm_btn', function () {
                window.console && console.log('S639 preview: confirmed, closing panel');

                /*
                 |--------------------------------------------------------------
                 | Confirm marks the preview as CONFIRMED, then re-triggers save.
                 |--------------------------------------------------------------
                 |
                 | Reported: the reconfirmation showed the cash amount twice.
                 |
                 | The console log explained it - one click produced the whole
                 | sequence TWICE:
                 |
                 |     save settlement clicked
                 |     S639 finalize: opening preview before save
                 |     S639 preview: shown as panel
                 |     save settlement clicked          <- again
                 |     S639 finalize: opening preview before save
                 |     S639 preview: shown as panel     <- again
                 |
                 | The save handler checks
                 |     if (! $(this).data('previewConfirmed')) { open the preview }
                 | and NOTHING ever set that flag to true. It is cleared in two
                 | places and set in none.
                 |
                 | So confirming re-triggered the handler, the flag was still false,
                 | and the preview opened a second time on top of the first - which
                 | is what showed the figures twice.
                 |
                 | Setting the flag here lets the re-trigger fall through to the
                 | save, which is what the handler's own comment says was intended.
                 */
                $('#settlement_save_btn').data('previewConfirmed', true);

                window.pdCloseSettlementPreview();

                // Now the flag is set, this proceeds to the save rather than
                // reopening the preview.
                $('#settlement_save_btn').trigger('click');
            });

            // bulk tank change
            $doc.on('change', '#bulk_tank', function() {
                const tank_id = $(this).val();
                if (!tank_id) return;
                apiGet("{{ action('\Modules\PetroPD\Http\Controllers\PDFuelTankController@getTankProduct') }}/" +
                        tank_id)
                    .done(result => {
                        const html =
                            `<option value="">Please Select</option><option value="${result.id}">${result.name}</option>`;
                        $('#item').empty().append(html);
                    })
                    .fail(() => toastError("Unable to fetch tank product"));
            });

            // Show/hide bulk_tank fields
            $doc.on('ifChecked', '#show_bulk_tank', function() {
                $('.store_field').addClass('hide');
                $('.bulk_tank_field').removeClass('hide');
            }).on('ifUnchecked', '#show_bulk_tank', function() {
                $('.store_field').removeClass('hide');
                $('.bulk_tank_field').addClass('hide');
            });

            // Save other income edit
            $doc.on('click', '#save_edit_price_other_income_btn', function() {
                const edit_price = $('#other_income_edit_price').val() || 0;
                $('#other_income_price').val(edit_price);
                $('#other_income_edit_price').val('0');
                $('#edit_price_other_income').modal('hide');
            });

            // ---------- Add other sale ----------
            $doc.off('click.global_settlement_other_sale', '.btn_other_sale')
                .off('click.petro_other_sale_pd', '.btn_other_sale')
                .on('click.petro_other_sale_pd', '.btn_other_sale', function (e) {
                    e.preventDefault();
                    if (window.isOtherSaleSubmitting) return false;
                    window.isOtherSaleSubmitting = true;

                    const $btn = $(this);
                    $btn.prop('disabled', true).addClass('disabled');

                    const qty = $('#other_sale_qty').val();
                    const price = $('#other_sale_price').val();
                    const discount = $('#other_sale_discount').val() || 0;
                    const discount_type = $('#other_sale_discount_type').val() || 'fixed';
                    const sub_total = parseFloat(qty) * parseFloat(price);
                    const discount_amount = calculate_discount(discount_type, discount, sub_total);

                    $.ajax({
                        method: 'post',
                        url: '/petropd/settlement-pd/save-other-sale',
                        data: {
                            settlement_no: $('#settlement_no').val(),
                            location_id: $('#location_id').val(),
                            pump_operator_id: $pumpOperator.val(),
                            transaction_date: $('#transaction_date').val(),
                            work_shift: $('#work_shift').val(),
                            note: $('#note').val(),
                            /*
                             | Sends the shift ID.
                             |
                             | #shift_number is a dropdown whose VALUE is the shift
                             | id and whose LABEL is the shift number - so .val()
                             | is correct here. It is spelled out because the id
                             | "shift_number" reads as though it holds the number,
                             | and that misreading is what produced a shift NUMBER
                             | reaching queries that wanted an ID.
                             |
                             | The server no longer relies on this for an existing
                             | settlement: it uses settlements.work_shift, which it
                             | writes itself. This value is used only when building
                             | a brand new settlement.
                             */
                            shift_id: $('#shift_number').val(),
                            shift_ids: $('#shift_number').val(),
                            active_settlement_id: $('#active_settlement_id').val(),
                            product_id: $('#item').val(),
                            store_id: $('#store_id').val(),
                            price: price,
                            qty: qty,
                            balance_stock: $('#balance_stock').val(),
                            discount: discount,
                            discount_type: discount_type,
                            discount_amount: discount_amount,
                            sub_total: sub_total,
                            is_edit: $('input[name="is_edit"]').val() || 0
                        },
                        success: function (result) {
                            if (result.success) {
                                toastSuccess(result.msg || "Other sale added.");
                                if (result.row_html) {
                                    $('#other_sale_table tbody').append(result.row_html);
                                    $('#other_sale_table').show();
                                    $('#outside_other_sale_table').hide();
                                    const currentOtherSaleTotal = parseFloat($('#other_sale_total').val()) || 0;
                                    const newOtherSaleTotal = currentOtherSaleTotal + (parseFloat(result.amount) || 0);
                                    $('#other_sale_total').val(newOtherSaleTotal);
                                    $('.other_sale_total').text(newOtherSaleTotal.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    }));
                                } else if (typeof loadOtherSalesData === 'function') {
                                    loadOtherSalesData();
                                }
                                if (typeof calculate_payment_tab_total === 'function') {
                                    calculate_payment_tab_total();
                                }
                                $('.other_sale_fields').val('').trigger('change');
                            } else {
                                toastError(result.msg);
                            }
                        },
                        complete: function () {
                            window.isOtherSaleSubmitting = false;
                            $btn.prop('disabled', false).removeClass('disabled');
                        }
                    });
                });

            // ---------- Add meter sale ----------
            $doc.off('click.petro_meter_sale_pd').on('click.petro_meter_sale_pd', '.btn_meter_sale_pd', function () {
                if (pd_meter_sale_submitting) return false;

                const $btn = $(this);
                const shiftId = (Array.isArray($shiftNumber.val()) ? $shiftNumber.val()[0] : $shiftNumber.val());
                if (
                    (parseInt($('#assignment_id').val(), 10) || 0) > 0 &&
                    !window.petroPdManualEntryActivated &&
                    String($('#is_from_pumper').val() || '0') !== '1'
                ) {
                    toastError("Please click Manual Entry before entering a manual closing meter.");
                    return false;
                }
                const sold_qty = parseFloat($('#sold_qty').val()) || 0;
                if (sold_qty <= 0 && $('#bulk_sale_meter').val() == 0) {
                    toastError("Sold quantity must be greater than zero.");
                    return false;
                }

                pd_meter_sale_submitting = true;
                $btn.prop('disabled', true).addClass('disabled');

                const discount      = $('#meter_sale_discount').val() || 0;
                const discount_type = $('#meter_sale_discount_type').val() || 'fixed';
                const sub_total     = sold_qty * price;
                const discount_amount = sub_total - calculate_discount(discount_type, discount, sub_total);
                const meterSalePayload = {
                    settlement_no:    $('#settlement_no').val(),
                    source:           'petro_pd',
                    location_id:      $('#location_id').val(),
                    pump_operator_id: $pumpOperator.val(),
                    transaction_date: $('#transaction_date').val(),
                    work_shift:       $('#work_shift').val(),
                    note:             $('#note').val(),
                    pump_id:          pump_id,
                    starting_meter:   $('#pump_starting_meter').val(),
                    closing_meter:    $('#pump_closing_meter').val(),
                    product_id:       product_id,
                    price:            price,
                    qty:              sold_qty,
                    discount:         discount,
                    discount_type:    discount_type,
                    discount_amount:  discount_amount,
                    testing_qty:      $('#testing_qty').val() || 0,
                    sub_total:        sub_total,
                    is_edit:          $('input[name="is_edit"]').val() || 0,
                    is_from_pumper:   $('#is_from_pumper').val() || 0,
                    assignment_id:    $('#assignment_id').val() || 0,
                    pumper_entry_id:  $('#pumper_entry_id').val() || 0,
                    shift_id:         shiftId
                };

                $.ajax({
                    method: 'post',
                    url: '/petropd/settlement-pd/save-meter-sale',
                    data: meterSalePayload,
                    success: function (result) {
                        if (result.success) {
                            toastSuccess(result.msg || "Meter sale added.");
                            $('#active_settlement_id').val(result.settlement_id);
                            if (result.settlement_no) {
                                $('#settlement_no').val(result.settlement_no);
                                $('.settlement_no').text(result.settlement_no);
                            }
                            upsertVisibleMeterSaleRow(result, meterSalePayload);
                            window.petroPdManualEntryActivated = true;
                            if (typeof calculate_payment_tab_total === 'function') {
                                calculate_payment_tab_total();
                            }
                            $('#pump_id_pd').val('').trigger('change');
                            $('.meter_sale_fields').not('select').val('');
                        } else {
                            toastError(result.msg);
                        }
                    },
                    complete: function () {
                        pd_meter_sale_submitting = false;
                        $btn.prop('disabled', false).removeClass('disabled');
                    }
                });
            });

            // ---------- Initialization on ready ----------
            $(function() {
                window.isBooting = true;
                // IS1781: initialise the Transaction Date as an explicit Bootstrap
                // date picker. Calling only `setDate` on an uninitialised input left
                // some tenant pages as a plain text field with no calendar popup.
                // Do not hard-code a display format here: the application-wide
                // datepicker settings must remain authoritative for every tenant.
                const $petroPdTransactionDate = $('#transaction_date');
                if ($petroPdTransactionDate.length && $.fn.datepicker) {
                    if (!$petroPdTransactionDate.data('datepicker')) {
                        $petroPdTransactionDate.datepicker({
                            autoclose: true,
                            todayHighlight: true,
                            orientation: 'bottom auto'
                        });
                    }

                    @if (!empty($active_settlement) && !empty($active_settlement->transaction_date))
                        $petroPdTransactionDate.datepicker('setDate', new Date(
                            {{ \Carbon::parse($active_settlement->transaction_date)->format('Y') }},
                            {{ (int) \Carbon::parse($active_settlement->transaction_date)->format('n') - 1 }},
                            {{ \Carbon::parse($active_settlement->transaction_date)->format('j') }}
                        ));
                    @else
                        $petroPdTransactionDate.datepicker('setDate', new Date());
                    @endif

                    $(document)
                        .off('click.petropdTransactionDate keydown.petropdTransactionDate', '#petropd_transaction_date_calendar')
                        .on('click.petropdTransactionDate keydown.petropdTransactionDate', '#petropd_transaction_date_calendar', function (event) {
                            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                                return;
                            }
                            event.preventDefault();
                            $petroPdTransactionDate.datepicker('show');
                        });
                }
                $('#customer_payment_cheque_date').datepicker("setDate", new Date());
                $('#location_id, #item, #store_id, #bulk_tank, #pump_operator_id, #work_shift, #shift_number, #card_customer_id, #customer_payment_customer_id, #pump_id_pd, #meter_sale_discount_type')
                    .select2();
                if (!isFinishingExistingSettlement) {
                    const initialShiftId = {!! json_encode($shift_id ?? '') !!};
                    const initialOperatorId = {!! json_encode($pump_operator_id ?? '') !!};
                    if (initialShiftId && initialOperatorId) {
                        window.skipOperatorShiftReload = true;
                        $pumpOperator.val(initialOperatorId).trigger('change.select2');
                        $shiftNumber.val(initialShiftId).trigger('change.select2');
                        confirmedPumpOperatorValue = initialOperatorId;
                        lockPumpOperatorAfterConfirm();
                        $shiftNumber.trigger('change');
                    } else {
                        $pumpOperator.val('').trigger('change.select2');
                        $shiftNumber.val('').trigger('change.select2');
                    }
                }
                $('#shif_time_in, #shif_time_out').datetimepicker({
                    format: 'LT'
                });
                $('#settlement_print').css('visibility', 'hidden');

                // IS1471 beforeunload persist: keep settlement selections when browser refreshes.
                window.addEventListener('beforeunload', function(){
                    try { persistLocalUpdate(buildPersistableData()); } catch(e) {}
                });

                // restore local data if any
                restoreLocalData();

                // PDST-003: If a draft/active PD settlement exists, refresh must restore the same
                // shift, operator, payment details and totals without changing saved data.
                refreshActiveSettlementDisplay();

                // IS1825: autoload the oldest pending closed shift and the operator
                // belonging to that same shift. Run the real loader once after the
                // Select2 labels are synchronized.
                if (applyInitialShiftOperatorContext()) {
                    skipUnsettledCheck = true;
                    window.setTimeout(function () {
                        $shiftNumber.trigger('change');
                        window.setTimeout(function () {
                            skipUnsettledCheck = false;
                            window.skipOperatorShiftReload = false;
                        }, 1200);
                    }, 50);
                }
                
                setTimeout(() => { window.isBooting = false; }, 3000);

                if (typeof window.petroPdRefreshManualEntryButton === 'function') {
                    window.petroPdRefreshManualEntryButton();
                }
                updateShiftNumberDisabledState();
            });

        })();
    </script>
@endsection


<script>
// PETROPD-PAYDUE-HREF-ROOTFIX-20260705
// Ensure the Add Payment AJAX URL always carries the currently visible Payment Due before the global btn-modal loader reads data-href.
(function () {
    function syncPetroPdAddPaymentHrefNow() {
        var btn = document.getElementById('add_payment');
        if (!btn) return;
        var href = btn.getAttribute('data-href');
        if (!href) return;
        try {
            var url = new URL(href, window.location.origin);
            var dueText = (($('#payment_due').text() || '0') + '').replace(/,/g, '').trim();
            var due = parseFloat(dueText);
            if (!isNaN(due)) {
                url.searchParams.set('pd_payment_due_total', due.toFixed({{ (int)($currency_precision ?? 2) }}));
            }
            var shift = $('#shift_number').val() || url.searchParams.get('shift_ids') || '';
            var op = $('#pump_operator_id').val() || url.searchParams.get('pump_operator_id') || '';
            var txDate = $('#transaction_date').val() || url.searchParams.get('transaction_date') || '';
            if (shift) url.searchParams.set('shift_ids', shift);
            if (op) url.searchParams.set('pump_operator_id', op);
            if (txDate) url.searchParams.set('transaction_date', txDate);
            btn.setAttribute('data-href', url.pathname + url.search);
        } catch (e) {}
    }
    document.addEventListener('mousedown', function (e) {
        if (e.target && (e.target.id === 'add_payment' || (e.target.closest && e.target.closest('#add_payment')))) {
            syncPetroPdAddPaymentHrefNow();
        }
    }, true);
    document.addEventListener('click', function (e) {
        if (e.target && (e.target.id === 'add_payment' || (e.target.closest && e.target.closest('#add_payment')))) {
            syncPetroPdAddPaymentHrefNow();
        }
    }, true);
    setInterval(syncPetroPdAddPaymentHrefNow, 1000);
})();
</script>
