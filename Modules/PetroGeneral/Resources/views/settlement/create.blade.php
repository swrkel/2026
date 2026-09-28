@extends('layouts.app')
@section('title', __('petrogeneral::lang.settlement'))

@section('content')
@php
$business_id = session('user.business_id');
$business_details = App\Business::find($business_id);
$currency_precision = $business_details->currency_precision ?? 2;
$meeter_precision = 3;

$asset_vapps = filemtime(public_path('js/app.js'));
$asset_vpayment = filemtime(public_path('js/payment.js'));
$subscription = \Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
$manage_module_enable = !empty($subscription->package_details) ? json_decode(json_encode($subscription->package_details), true) : [];
$disable_shift_no = !array_key_exists('disable_shift_no_direct_settlement', $manage_module_enable) ? true : !empty($manage_module_enable['disable_shift_no_direct_settlement']);
$initial_direct_shift_number = '';
if (!empty($active_settlement->work_shift)) {
    $direct_shift_prefix = $business_details->ref_no_prefixes['direct_settlement_shift'] ?? 'DST';
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
                    <li><a href="#">@lang('petrogeneral::lang.petro')</a></li>
                    <li><span>@lang('petrogeneral::lang.settlement')</span></li>
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
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="row">
                <div class="col-md-1">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('petrogeneral::lang.settlement_no') . ':') !!}
                        {!! Form::text('settlement_no', $active_settlement->settlement_no ?? '', ['class' =>
                        'form-control', 'readonly']) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, $active_settlement->location_id ??
                        $default_location ?? null, ['class' => 'form-control select2', 'id' => 'location_id',
                        'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('petrogeneral::lang.pump_operator').':') !!}
                        @php
                            $default_pump_operator = $active_settlement->pump_operator_id
                                ?? $default_pump_operator_id ?? null;
                        @endphp
                        {!! Form::select('pump_operator_id', $pump_operators, $default_pump_operator, [
                        'class' => 'form-control select2',
                        'id' => 'pump_operator_id',
                        'disabled' => !empty($select_pump_operator_in_settlement) ? false : true,
                        'placeholder' => __('petrogeneral::lang.please_select')
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __( 'petrogeneral::lang.transaction_date' ) . ':*') !!}
                        {!! Form::text('transaction_date', $active_settlement->transaction_date ?? null, ['class' =>
                        'form-control transaction_date', 'required', 'placeholder' => __('petrogeneral::lang.transaction_date')
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('work_shift', __('petrogeneral::lang.work_shift').':') !!}
                        {!! Form::select('work_shift[]', $wrok_shifts, $active_settlement->work_shift ?? [], ['class' =>
                        'form-control select2', 'id' => 'work_shift', 'multiple']) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('shift_number', __('petrogeneral::lang.shift_number').':') !!}
                        {!! Form::select('shift_number', $shift_numbers, null, ['id' => 'shift_number', 'class' =>
                        'form-control select2', 'required']) !!}
                        <input type="text" id="manual_shift_number" class="form-control hide"
                            placeholder="Type Shift No, e.g. ST8">
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group">
                        {!! Form::label('note', __('petrogeneral::lang.note') . ':') !!}
                        {!! Form::text('note', $active_settlement->note ?? null, ['class' => 'form-control note', 'id'
                        => 'note', 'placeholder' => __('petrogeneral::lang.note')]) !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    {{-- Widget area with tabs --}}
    @component('components.widget', ['class' => 'box-primary below_box', 'id' => 'below_box'])
    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#meter_sale_tab" class="meter_sale_tab" data-toggle="tab">
                            <i class="fa fa-tachometer"></i> <strong>@lang('petrogeneral::lang.meter_sale')s</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#other_sale_tab" class="other_sale_tab" data-toggle="tab">
                            <i class="fa fa-balance-scale"></i> <strong>@lang('petrogeneral::lang.other_sale')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#other_income_tab" class="other_income_tab" data-toggle="tab">
                            <i class="fa fa-thermometer"></i> <strong>@lang('petrogeneral::lang.other_income')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#customer_payment_tab" class="customer_payment_tab" data-toggle="tab">
                            <i class="fa fa-money"></i> <strong>@lang('petrogeneral::lang.customer_payment')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#payment_tab" class="payment_tab" data-toggle="tab">
                            <i class="fa fa-book"></i> <strong>@lang('petrogeneral::lang.payment')</strong>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="meter_sale_tab">
                        @include('petrogeneral::settlement.partials.meter_sale')
                    </div>

                    <div class="tab-pane" id="other_sale_tab">
                        @include('petrogeneral::settlement.partials.other_sale')
                        <input type="hidden" value="{{$check_qty}}" id="allowoverselling">
                    </div>

                    <div class="tab-pane" id="other_income_tab">
                        @include('petrogeneral::settlement.partials.other_income')
                    </div>

                    <div class="tab-pane" id="customer_payment_tab">
                        @include('petrogeneral::settlement.partials.customer_payment')
                    </div>

                    <div class="tab-pane" id="payment_tab">
                        @include('petrogeneral::settlement.partials.payment')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcomponent

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
@endsection

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

<script src="{{ url('js/payment.js?v=' . $asset_vpayment) }}"></script>
<script src="{{ url('js/petro_payment.js?v=' . $asset_vapps) }}"></script>
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
            if (!requiresMechanicalMeterSave()) {
                $('.btn_meter_sale').prop('disabled', false).removeClass('disabled');
                return;
            }

            var saved = $('#mechanical_meter_saved').val() === '1';
            $('.btn_meter_sale').prop('disabled', !saved).toggleClass('disabled', !saved);
        }

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
        const manualShiftUrl = "{{ action('\Modules\PetroGeneral\Http\Controllers\SettlementController@storeManualShiftNumber') }}";

        const hasActiveSettlement = {!! json_encode(!empty($active_settlement)) !!};
        const initialActiveSettlementIsDraft = {!! json_encode(!empty($active_settlement) && (int) $active_settlement->status === 1) !!};
        let manualShiftRequestPending = false;
        let currentPumpOperatorId = $pumpOperator.val() || '';

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
            const res = await apiGet("{{ url('petro-general/settlement/check_prev_settlement') }}", {
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

    // Update pump dropdown options (Select2 aware)
    function updatePumpDropdown(pump_nos = {}) {
        const $select = $('#pump_no');
        $select.empty().append('<option value="">' + "@lang('petrogeneral::lang.please_select')" + '</option>');
        $.each(pump_nos, (id, name) => $select.append(`<option value="${id}">${name}</option>`));
        $select.trigger('change.select2');
    }

    // Load pump dropdown by selected business location (NOT by operator/shift).
    function loadAssignedPumps() {
        const locationId = $location.val();
        if (!locationId) {
            updatePumpDropdown({});
            return;
        }
        // ZIP 058: Direct Settlement pump dropdown is location-based.
        // Do not skip loading just because a pump operator is selected.
        apiGet(`/petro-general/settlement/get_pumps_by_location`, {
            location_id: locationId
        }).done(result => {
            if (result && result.success) {
                updatePumpDropdown(result.pumps || {});
            } else {
                updatePumpDropdown({});
            }
        }).fail(() => updatePumpDropdown({}));
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
        if (!v) return [];
        return Array.isArray(v) ? v : [v];
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
    const directShiftPrefix = {!! json_encode($business_details->ref_no_prefixes['direct_settlement_shift'] ?? 'DST') !!};

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
            placeholder: "{{ __('petrogeneral::lang.please_select') }}",
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

    function resetMeterSaleTablesForContext(options = {}) {
        /*
         * ZIP 058:
         * Do not clear Direct Settlement manual meter sale rows when an active
         * draft settlement is being reopened after refresh. Those rows are rendered
         * server-side from active_settlement->meter_sales and must remain visible
         * until the settlement is finalized.
         */
        const keepExistingRows = options.keepExistingRows === true ||
            (isDirectSettlementShiftSelected() && parseInt($activeSettlement.val() || '0', 10) > 0);

        $('#outside_meter_sale_table').hide();
        $('#meter_sale_table').show();

        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pump_operator_meter_sale_table')) {
            $('#pump_operator_meter_sale_table').DataTable().clear().draw();
        }

        if (keepExistingRows) {
            var visibleTotal = sum_table_col($('#meter_sale_table'), 'discount_amount');
            var meterSaleTotal = parseFloat((visibleTotal || '0').toString().replace(/,/g, '')) || 0;

            $('#footer_list_meter_sales_amount').val(meterSaleTotal).text(
                typeof __number_f === 'function' ? __number_f(meterSaleTotal, false, false, __currency_precision) : meterSaleTotal
            );
            $('#meter_sale_total').val(meterSaleTotal);
            $('.meter_sale_total').text(
                typeof __number_f === 'function' ? __number_f(meterSaleTotal, false, false, __currency_precision) : meterSaleTotal
            );

            calculate_payment_tab_total();
            return;
        }

        $('#meter_sale_table tbody').empty();
        $('#meter_sale_table tfoot .meter_sale_total').text(typeof __number_f === 'function' ? __number_f(0, false, false, __currency_precision) : '0.00');

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
        $belowBox.addClass('hide');
        $('.settlement_tabs .nav-tabs li, .settlement_tabs .tab-pane').removeClass('active show');
        $('.settlement_tabs .other_income_tab').closest('li').addClass('active show');
        $('#other_income_tab').addClass('active show');
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

        const url = "/petro-general/settlement/" + ($activeSettlement.val() || '0');
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

    // wire handlers for field changes — only after page init is complete
    let pageInitialized = false;
    $doc.on('change', '#note, #work_shift, #transaction_date, #pump_operator_id, #location_id', function (e) {
        if (!pageInitialized) return;

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
            const mappedSettlementId = currentPumpOperatorId
                ? (carryCurrentDirectDraft ? $activeSettlement.val() : (directSettlementByOperator[currentPumpOperatorId] || '0'))
                : '0';
            $activeSettlement.val(mappedSettlementId);
            $activeSettlementStatus.val(mappedSettlementId !== '0' ? '1' : '');
            $('#settlement_no').val('');
            resetMeterSaleTablesForContext();
        }

        handleFieldChanges();

        // Pump dropdown is scoped only by business location.
        if (e && e.target && e.target.id === 'location_id') {
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
        const shift_numbers = getSelectedShiftIds();
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
            // DST shifts are Direct Settlement internal labels.
            // Load pumps by location and preserve draft manual meter-sale rows.
            loadAssignedPumps();
            resetMeterSaleTablesForContext({ keepExistingRows: true });
            $('#shift_operator_other_sale_total').val(0);
            $('#other_sale_table tbody tr[data-source="pumper"]').remove();
            updateOtherSaleVisibleTotal();
            calculate_payment_tab_total();
            return;
        }

        apiGet('/petro-general/settlement/get_pumps/' + $pumpOperator.val(), {
            shift_number: shift_id,
            location_id: $location.val(),
            active_settlement_id: $activeSettlement.val()
        }).done(result => {
            updatePumpDropdown(result && result.success ? (result.pumps || {}) : {});
        }).fail(() => updatePumpDropdown({}));

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
        if (!pageInitialized) return;
        const pumpId = $('#pump_no').val();

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
        if (getSelectedShiftIds().length === 0) {
            $('#shift_operator_other_sale_total').val(0);
            $('#other_sale_table tbody tr[data-source="pumper"]').remove();
            updateOtherSaleVisibleTotal();
            calculate_payment_tab_total();
            return;
        }

        apiGet("{{ action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}", {
            shift_ids: getSelectedShiftIds(),
            pump_operator_id: $pumpOperator.val(),
            pump_id: $('#pump_no').val() || '',
            settlement_view: 1,
            draw: 1,
            start: 0,
            length: -1
        }).done(result => {
            if (!result || !Array.isArray(result.data)) {
                toastError("Error fetching other sale total");
                return;
            }

            var pumperTotal = renderPumperOtherSalesRows(result.data);
            $('#shift_operator_other_sale_total').val(pumperTotal);
            updateOtherSaleVisibleTotal();
            calculate_payment_tab_total();
        }).fail(() => toastError("Error fetching other sale data"));
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
        const shiftIds = getSelectedShiftIds();
        const pumpOperatorId = $pumpOperator.val();
        if (!shiftIds.length || !pumpOperatorId || isDirectSettlementShiftSelected()) {
            // Direct Settlement uses manual ST/DST flow only.
            // Do not load Pumper Dashboard / PetroPD rows, and do not clear existing draft rows.
            loadAssignedPumps();
            resetMeterSaleTablesForContext({ keepExistingRows: true });
            return;
        }

        $('#outside_meter_sale_table').show();
        $('#meter_sale_table').hide();

        initOrReloadDataTable('#pump_operator_meter_sale_table', {
            ajax: {
                url: "{{ action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorPaymentController@meterSalesList') }}",
                data: d => {
                    d.shift_ids = getSelectedShiftIds();
                    d.pump_operator_id = $pumpOperator.val();
                    d.active_settlement_id = $activeSettlement.val();
                    d.settlement_view = 1;
                }
            },
            columnDefs: [{ targets: 0, orderable: false, searchable: false }],
            columns: [
                { data: 'product_sku', name: 'products.sku' },
                { data: 'product_name', name: 'products.name' },
                { data: 'pump_name', name: 'pump_name' },
                { data: 'starting_meter', name: 'starting_meter', className: 'text-right' },
                { data: 'closing_meter', name: 'closing_meter', className: 'text-right' },
                { data: 'price', name: 'price', className: 'text-right' },
                { data: 'quantity', name: 'quantity', className: 'text-right' },
                { data: 'discount_type', name: 'discount_type' },
                { data: 'discount', name: 'discount_value', className: 'text-right' },
                { data: 'testing_qty', name: 'testing_qty', className: 'text-right' },
                { data: 'total_qty', name: 'total_qty', className: 'text-right' },
                { data: 'sub_total', name: 'sub_total', className: 'text-right' },
                { data: 'discount_amount', name: 'discount_amount', className: 'text-right' },
                {
                    data: 'action',
                    name: 'action',
                    render(data, type, row) {
                        const editButton = `<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro-general/settlement/get-meter-sale-form/${row.id}"><i class="fa fa-edit"></i></button>`;
                        const deleteButton = (row.later_settlements < 1 || !row.transaction_id || row.bulk_tank == 1)
                            ? `<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro-general/settlement/delete-meter-sale/${row.id}"><i class="fa fa-times"></i></button>`
                            : '';
                        return editButton + ' ' + deleteButton;
                    }
                }
            ],
            fnDrawCallback() {
                // Sum "After Discount" column only; recalc from scratch so total never accumulates
                const total = sum_table_col($('#pump_operator_meter_sale_table'), 'discount_amount');
                $('#footer_list_meter_sales_amount').val(total).text(total);
                __currency_convert_recursively($('#pump_operator_meter_sale_table'));

                // Sync Payment tab meter sales total with Meter Sales tab total
                // (Payment tab uses #meter_sale_total via calculate_payment_tab_total())
                const meterSaleTotal = parseFloat((total || '0').toString().replace(/,/g, '')) || 0;
                $('#meter_sale_total').val(meterSaleTotal);
                calculate_payment_tab_total();

                // Ensure finalize button is enabled once meter sales data is loaded (when shifts are selected).
                const currentShifts = $shiftNumber.val() || [];
                const hasShifts = Array.isArray(currentShifts) ? currentShifts.length > 0 : !!currentShifts;
                const enableFinalize = hasShifts && !!$pumpOperator.val();
                $('#add_payment').prop('disabled', !enableFinalize).toggleClass('disabled', !enableFinalize);
            }
        });
    }





    /*
     * ZIP 058:
     * On refreshed Direct Settlement draft, reload pump dropdown and preserve
     * already entered meter sale rows.
     */
    setTimeout(function () {
        if (isDirectSettlementShiftSelected() || parseInt($activeSettlement.val() || '0', 10) > 0) {
            loadAssignedPumps();
            resetMeterSaleTablesForContext({ keepExistingRows: true });
            $('#add_payment').prop('disabled', false).removeClass('disabled');
        }
    }, 600);


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
        paymentUrl.searchParams.set('shift_ids', shiftIds.join(','));

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
            url: "/petro-general/settlement/payment/get-customer-details/" + customerId,
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
        apiGet("{{ action('\Modules\PetroGeneral\Http\Controllers\FuelTankController@getTankProduct') }}/" + tank_id)
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
        $('.transaction_date').datepicker("setDate", @if (!empty($active_settlement)) "{{ \Carbon::parse($active_settlement->transaction_date)->format('m/d/Y') }}" @else new Date() @endif);
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
    var saveMeterSaleUrl = "{{ action('\Modules\PetroGeneral\Http\Controllers\SettlementController@saveMeterSale') }}";

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
        var meter_sale_discount_type = $('#meter_sale_discount_type').val();
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
                product_id: typeof product_id !== 'undefined' ? product_id : '',
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

    // Page-specific Add: same refresh strategy as Update (table_html + DataTable.reload), no loadMeterSalesData()
    $doc.off('click', '.btn_meter_sale').off('click.petro_meter_sale', '.btn_meter_sale');
    $doc.on('click', '.btn_meter_sale', function () {
        if (window.isMeterSaleSubmitting) return false;
        window.isMeterSaleSubmitting = true;
        var $button = $(this);
        $button.prop('disabled', true).addClass('disabled');

        var pump_id = $('#pump_no').val();
        var starting_meter = $('#pump_starting_meter').val();
        var closing_meter = $('#pump_closing_meter').val();
        var testing_qty = $('#testing_qty').val() || 0;
        // Sold Qty field is already chargeable (Closing - Starting - Testing); do not subtract testing again
        var sold_qty = parseFloat($('#sold_qty').val()) || 0;
        var total_qty = sold_qty + (parseFloat(testing_qty) || 0);
        var price = (typeof price !== 'undefined') ? price : parseFloat($('#meter_sale_unit_price').val() || 0);
        var sub_total = parseFloat(sold_qty) * parseFloat(price);
        var meter_sale_discount = $('#meter_sale_discount').val() || 0;
        var meter_sale_discount_type = $('#meter_sale_discount_type').val();
        var meter_sale_discount_amount = sub_total - (typeof calculate_discount === 'function' ? calculate_discount(meter_sale_discount_type, meter_sale_discount, sub_total) : 0);
        var is_edit = $('#is_edit').val() || 0;
        var shift_id = getCurrentShiftIdValue();

        if (!shift_id) {
            toastr.error('Please select or enter the Shift No first.');
            window.isMeterSaleSubmitting = false;
            $button.prop('disabled', false).removeClass('disabled');
            return false;
        }

        $.ajax({
            method: 'post',
            url: saveMeterSaleUrl,
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
                pump_id: pump_id,
                starting_meter: starting_meter,
                closing_meter: closing_meter,
                product_id: typeof product_id !== 'undefined' ? product_id : '',
                price: price,
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
                shift_id: shift_id
            },
            success: function (result) {
                if (!result || !result.success) {
                    toastr.error((result && result.msg) ? result.msg : 'Add failed');
                    return;
                }
                toastr.success(result.msg || 'Success');
                $('#active_settlement_id').val(result.settlement_id);
                if (result.settlement_no) {
                    $('#settlement_no').val(result.settlement_no);
                }
                if ($('#pump_operator_id').val() && result.settlement_id) {
                    window.__directSettlementByOperator = window.__directSettlementByOperator || {};
                    window.__directSettlementByOperator[$('#pump_operator_id').val()] = result.settlement_id;
                }
                if (result.settlement_id && window.history && window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('view_settlement_id', result.settlement_id);
                    window.history.replaceState({}, '', url.toString());
                }
                $('.btn_meter_sale_cancel').trigger('click');
                applyMeterSaleSuccessResult(result, pump_id);
            },
            error: function () {
                setTimeout(function () {
                    window.isMeterSaleSubmitting = false;
                    $button.prop('disabled', false).removeClass('disabled');
                }, 500);
            },
            complete: function () {
                setTimeout(function () {
                    window.isMeterSaleSubmitting = false;
                    $button.prop('disabled', false).removeClass('disabled');
                }, 500);
            }
        });
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
                data: {
                    is_edit: is_edit,
                    shift_id: (function () {
                        var value = $('#shift_number').val();
                        return Array.isArray(value) ? (value || []).join(',') : (value || '').toString();
                    })()
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
                }
            });
        });

    $doc.off('change.petro_create_item', '#item')
        .on('change.petro_create_item', '#item', function () {
            var item_id = $(this).val();
            if (!item_id) return;

            $.ajax({
                method: 'get',
                url: '/petro-general/settlement/get_balance_stock_by_id/' + item_id,
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
                url: '/petro-general/settlement/save-other-sale',
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
                            '<td><button class="btn btn-xs btn-danger delete_other_sale" data-href="/petro-general/settlement/delete-other-sale/' + result.other_sale_id + '"><i class="fa fa-times"></i></button></td>' +
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
                url: '/petro-general/settlement/get_balance_stock/' + item_id,
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
                url: '/petro-general/settlement/save-other-income',
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
                        '<button class="btn btn-xs btn-danger delete_other_income" data-href="/petro-general/settlement/delete-other-income/' + result.other_income_id + '"><i class="fa fa-times"></i></button>'
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
                url: '/petro-general/settlement/save-customer-payment',
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
                        '<td><button class="btn btn-xs btn-danger delete_customer_payment" data-href="/petro-general/settlement/delete-customer-payment/' + result.customer_payment_id + '"><i class="fa fa-times"></i></button></td>' +
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
@endsection
