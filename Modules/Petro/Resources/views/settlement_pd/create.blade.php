
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
</style>
@extends('layouts.app')
@section('title', __('petro::lang.settlement_pd'))

@section('content')
@php
$business_id = session('user.business_id');
$business_details = App\Business::find($business_id);
$currency_precision = $business_details->currency_precision ?? 2;
$meeter_precision = 3;

$asset_vapps = filemtime(public_path('js/app.js'));
$asset_vpayment = filemtime(public_path('js/payment.js'));
@endphp

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petro::lang.petro')</a></li>
                    <li><span>@lang('petro::lang.settlement_pd')</span></li>
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
</style>
@endpush

<section class="content main-content-inner">
    @if (!empty($message))
    {!! $message !!}
    @endif

    {{-- Filters --}}
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="row">
                <div class="col-md-1">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('petro::lang.settlement_no') . ':') !!}
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
                        'placeholder' => __('petro::lang.all'),
                        'style' => 'width:100%',
                        ],
                        ) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('petro::lang.pump_operator') . ':') !!}
                        {!! Form::select('pump_operator_id', $pump_operators, $active_settlement->pump_operator_id ?? ($pump_operator_id ?? null), [
                        'class' => 'form-control select2',
                        'id' => 'pump_operator_id',
                        'placeholder' => __('petro::lang.please_select'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('petro::lang.transaction_date') . ':*') !!}
                        {!! Form::text('transaction_date', $active_settlement->transaction_date ?? null, [
                        'class' => 'form-control transaction_date',
                        'required',
                        'placeholder' => __('petro::lang.transaction_date'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('work_shift', __('petro::lang.work_shift') . ':') !!}
                        {!! Form::select('work_shift[]', $wrok_shifts, $active_settlement->work_shift ?? [], [
                        'class' => 'form-control select2',
                        'id' => 'work_shift',
                        'multiple',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('shift_number', __('petro::lang.shift_number') . ':') !!}
                        <select name="shift_number[]" id="shift_number" class="form-control select2" multiple>
                                @foreach($shift_numbers as $shift_id_key => $shift_data)
                                    @php
                                        $shift_number_display = is_array($shift_data) ? $shift_data['shift_number'] : $shift_data;
                                        $work_shift_id_attr = is_array($shift_data) && isset($shift_data['work_shift_id']) ? $shift_data['work_shift_id'] : '';
                                        $pump_operator_id_attr = is_array($shift_data) && isset($shift_data['pump_operator_id']) ? $shift_data['pump_operator_id'] : '';
                                        $direct_shift_attr = is_array($shift_data) && !empty($shift_data['is_direct_shift']) ? 'data-direct-shift=1' : '';
                                        $selected = isset($shift_id) && (string) $shift_id === (string) $shift_id_key ? 'selected' : '';
                                    @endphp
                                    <option value="{{ $shift_id_key }}" data-work-shift="{{ $work_shift_id_attr }}" data-pump-operator="{{ $pump_operator_id_attr }}" {!! $direct_shift_attr !!} {{ $selected }}>
                                        {{ $shift_number_display }}
                                    </option>
                                @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group">
                        {!! Form::label('note', __('petro::lang.note') . ':') !!}
                        {!! Form::text('note', $active_settlement->note ?? null, [
                        'class' => 'form-control note',
                        'id' => 'note',
                        'placeholder' => __('petro::lang.note'),
                        ]) !!}
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
                            <i class="fa fa-tachometer"></i> <strong>@lang('petro::lang.meter_sale')s</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#other_sale_tab" class="other_sale_tab" data-toggle="tab">
                            <i class="fa fa-balance-scale"></i> <strong>@lang('petro::lang.other_sale')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#other_income_tab" class="other_income_tab" data-toggle="tab">
                            <i class="fa fa-thermometer"></i> <strong>@lang('petro::lang.other_income')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#customer_payment_tab" class="customer_payment_tab" data-toggle="tab">
                            <i class="fa fa-money"></i> <strong>@lang('petro::lang.customer_payment')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#payment_tab" class="payment_tab" data-toggle="tab">
                            <i class="fa fa-book"></i> <strong>@lang('petro::lang.payment')</strong>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="meter_sale_tab">
                        @include('petro::settlement_pd.partials.meter_sale')
                    </div>

                    <div class="tab-pane" id="other_sale_tab">
                        @include('petro::settlement_pd.partials.other_sale')
                        <input type="hidden" value="{{ $check_qty }}" id="allowoverselling">
                    </div>

                    <div class="tab-pane" id="other_income_tab">
                        @include('petro::settlement_pd.partials.other_income')
                    </div>

                    <div class="tab-pane" id="customer_payment_tab">
                        @include('petro::settlement_pd.partials.customer_payment')
                    </div>

                    <div class="tab-pane" id="payment_tab">
                        @include('petro::settlement_pd.partials.payment')
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
<script src="{{ url('js/app.js?v=' . $asset_vapps) }}"></script>
<script src="{{ url('js/payment.js?v=' . $asset_vpayment) }}"></script>
<script src="{{ url('js/petro_payment.js?v=' . $asset_vapps) }}"></script>

<input type="hidden" id="active_settlement_id" value="{{ $active_settlement->id ?? 0 }}">
<input type="hidden" id="shift_closed" value="{{ !empty($shift_closed) ? $shift_closed : 'yes' }}">

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
        let skipHandleFieldChanges = false;
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
        const hasActiveSettlement = {!! json_encode(!empty($active_settlement))!!
    }; // boolean
        const isFinishingExistingSettlement = {!! json_encode(!empty($is_finishing_existing_settlement)) !!};

    // CSRF setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
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

    // Check previous unsettled (returns Promise<boolean>)
    async function checkPreviousUnsettled(shift_id) {
        try {
            const res = await apiGet("{{ url('petro/pdsettlement-pd/check-prev-settlement') }}", {
                shift_id
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
        const $select = $('#pump_no_pd');
        $select.empty().append('<option value="">' + "@lang('petro::lang.please_select')" + '</option>');
        $.each(pump_nos, (id, name) => $select.append(`<option value="${id}">${name}</option>`));
        $select.trigger('change.select2');
    }

    function normalizeShiftIds(value) {
        if (value == null || value === '') {
            return [];
        }

        return Array.isArray(value) ? value : [value];
    }

    const directShiftByOperator = {};

    function getSelectedDirectShiftNumber() {
        const selectedShiftIds = normalizeShiftIds($shiftNumber.val());
        if (!selectedShiftIds.length) return '';

        const $option = $shiftNumber.find(`option[value="${selectedShiftIds[0]}"]`);
        return $option.data('direct-shift') ? $.trim($option.text()) : '';
    }

    function getSelectedDirectShiftOperatorId() {
        const selectedShiftIds = normalizeShiftIds($shiftNumber.val());
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
            number: getHighestKnownDirectShiftNumber(),
            operatorId: getSelectedDirectShiftOperatorId()
        };
    }

    function getSelectedShiftMeta() {
        const selectedShiftIds = normalizeShiftIds($shiftNumber.val());
        const firstShiftId = selectedShiftIds.length ? selectedShiftIds[0] : null;

        if (!firstShiftId) {
            return {
                selectedShiftIds,
                firstShiftId: null,
                selectedOption: $(),
                workShiftId: null,
                pumpOperatorId: null
            };
        }

        const $selectedOption = $shiftNumber.find(`option[value="${firstShiftId}"]`);

        return {
            selectedShiftIds,
            firstShiftId,
            selectedOption: $selectedOption,
            workShiftId: $selectedOption.data('work-shift') || null,
            pumpOperatorId: $selectedOption.data('pump-operator') || null
        };
    }

    async function syncOperatorAndWorkShiftFromShiftSelection() {
        const meta = getSelectedShiftMeta();

        if (!meta.firstShiftId) {
            return false;
        }

        if (meta.workShiftId) {
            skipHandleFieldChanges = true;
            $('#work_shift').val([meta.workShiftId]).trigger('change');
            skipHandleFieldChanges = false;
        }

        const currentPumpOperatorId = $pumpOperator.val();
        if (meta.pumpOperatorId && String(currentPumpOperatorId || '') !== String(meta.pumpOperatorId)) {
            $pumpOperator.val(String(meta.pumpOperatorId)).trigger('change.select2');
            return true;
        }

        return false;
    }

    function calculate_payment_tab_total() {
        var meter_sale_total = parseFloat($('#meter_sale_total').val()) || 0;
        var other_sale_total = parseFloat($('#shift_operator_other_sale_total').val()) || 0;
        var other_income_total = parseFloat($('#other_income_total').val()) || 0;
        var customer_payment_total = parseFloat($('#customer_payment_total').val()) || 0;
        var grand_total = meter_sale_total + other_sale_total + other_income_total;
        
        $('#grand_total').val(grand_total);
        $('.grand_total').text(__number_f(grand_total, false, false, __currency_precision));
        
        // Sync labels in the Payment tab summary
        $('.payment_meter_sale_total').text(__number_f(meter_sale_total, false, false, __currency_precision));
        $('.payment_other_sale_total').text(__number_f(other_sale_total, false, false, __currency_precision));
        $('.payment_other_income_total').text(__number_f(other_income_total, false, false, __currency_precision));
        $('.payment_customer_payment_total').text(__number_f(customer_payment_total, false, false, __currency_precision));
        $('#payment_due').text(__number_f(grand_total, false, false, __currency_precision));
    }

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

        persistTimer = setTimeout(() => {
            var pump_operator_id = $('#pump_operator_id').val();
            var shift_number = $('#shift_number').val(); // could be string or array

            // Normalize shift_number to array
            if (shift_number && !Array.isArray(shift_number)) {
                shift_number = [shift_number];
            }

            if (pump_operator_id && shift_number && shift_number.length > 0) {
                console.log(pump_operator_id, 'with pump', shift_number);
                localStorage.setItem('lastUpdateData', JSON.stringify(data));
            } else {
                console.log('pump_operator_id or shift_number is empty, removing stored data');
                // localStorage.removeItem('lastUpdateData'); // remove if invalid
            }
        }, 5000); // 5-second delay
    }


    // Build data object saved to localStorage
    function buildPersistableData() {
        return {
            note: $note.val(),
            work_shift: $workShift.val(),
            transaction_date: $transactionDate.val(),
            pump_operator_id: $pumpOperator.val(),
            location_id: $location.val(),
            pump_no: $('#pump_no_pd').val(),
            pump_starting_meter: $('#pump_starting_meter').val(),
            sold_qty: $('#sold_qty').val(),
            meter_sale_unit_price: $('#meter_sale_unit_price').val(),
            testing_qty: $('#testing_qty').val(),
            meter_sale_discount_type: $('#meter_sale_discount_type').val(),
            meter_sale_discount: $('#meter_sale_discount').val()
        };
    }

    // Generic DataTable initializer / reload helper
    window.initOrReloadDataTable = function (selector, opts) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().clear().destroy();
            $(selector).find('tbody').remove();
        }
        // merge default settings
        const defaults = {
            processing: true,
            serverSide: true,
            aaSorting: [
                [0, 'desc']
            ],
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
    $doc.on('click', '.settlement_tabs .nav-tabs a.meter_sale_tab, .settlement_tabs .nav-tabs a.other_sale_tab',
        function (e) {
            if ($shiftClosed.val() !== "yes") {
                return toggle_check_operator_shift_status(e);
            }
        });

    // ---------- Update settlement_pd (AJAX) ----------
    async function handleFieldChanges() {
        if (skipHandleFieldChanges) return;
        const pumpOperator = $pumpOperator.val();
        const workShift = $workShift.val();
        var shift_number = $('#shift_number').val();
        const previouslySelectedShiftIds = normalizeShiftIds($shiftNumber.val());

        // Pump operator is required; workShift is optional (will be auto-set after shift loads)
        if (!pumpOperator || pumpOperator === "") {
            return;
        }
        if (pumpOperator && Array.isArray(shift_number) && shift_number.length > 0) {
            // persist current fields locally
            persistLocalUpdate(buildPersistableData());
        } else {
            console.log(
                'pump_operator_id or shift_number is empty, removing stored data i am calling in handfile changes function'
            );
            //c
        }

        const url = hasActiveSettlement ?
            "{{ action('\Modules\Petro\Http\Controllers\SettlementPDController@update', $active_settlement->id ?? 0) }}" :
            "/petro/settlement-pd/" + activeSettlementId;
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
                    $('#shift_number').html(result.optionHtml);

                    const matchingShiftIds = previouslySelectedShiftIds.filter(id =>
                        $('#shift_number').find(`option[value="${id}"]`).length > 0
                    );

                    if (matchingShiftIds.length > 0) {
                        $('#shift_number').val(matchingShiftIds);
                    } else if ($("#shift_number option").length >= 1) {
                        $("#shift_number option:first").prop("selected", true);
                    }
                } else {
                    $('#shift_number').html('');
                }
                rememberSelectedDirectShift(pumpOperator);
                updatePumpDropdown(result.pump_nos || {});
                $shiftClosed.val("yes");
                $belowBox.removeClass('hide');
                skipUnsettledCheck = true;
                $('#shift_number').trigger('change');
                skipUnsettledCheck = false;
            } else {
                toastError(result.msg || "Unable to update settlement_pd.");
                // Don't empty shift dropdown - it causes fetchOtherSales to return early
                // and prevents pump dropdown from being populated
                $belowBox.addClass('show');
                $shiftClosed.val("yes");
                // $('#meter_sale_tab').addClass('active show');
                $('#outside_meter_sale_table').hide();
                $('#meter_sale_table_wrap').show();
                fetchOtherSales();
            }
        } catch (err) {
            console.error("API Error:", err);
            toastError("An error occurred while updating settlement_pd.");
        }




    }

    // wire handlers for field changes
    $doc.on('change', '#note, #work_shift, #transaction_date, #pump_operator_id, #location_id',
        handleFieldChanges);

    // persist small changes when there's no active settlement_pd
    // if (!hasActiveSettlement) {
    //     $doc.on('change', '#pump_no, #pump_starting_meter, #sold_qty, #meter_sale_unit_price, #testing_qty, #meter_sale_discount_type, #meter_sale_discount', () =>
    //         persistLocalUpdate(buildPersistableData())
    //     );
    // }
    // let persistTimer;

    $doc.on('change',
        '#pump_no_pd,#pump_operator_id,#pump_starting_meter,#sold_qty,#meter_sale_unit_price,#testing_qty,#meter_sale_discount_type,#meter_sale_discount',
        () => {
            // clearTimeout(persistTimer); // clear previous timer if user keeps changing

            // persistTimer = setTimeout(() => {
            persistLocalUpdate(buildPersistableData());
            // }, 5000); // 5 seconds delay
        });

    // ---------- Shift number change handler ----------
    $doc.on('change', '#shift_number', async function () {
        const shift_numbers = normalizeShiftIds($(this).val());
        let shift_id = Array.isArray(shift_numbers) ? shift_numbers[0] : shift_numbers;

        if (!shift_id) {
            $belowBox.addClass('hide');
            $('#add_payment').prop('disabled', true).addClass('disabled');
            return;
        }

        const changedOperator = await syncOperatorAndWorkShiftFromShiftSelection();
        if (changedOperator) {
            persistLocalUpdate(buildPersistableData());
        }

        // toggle below_box visibility
        $belowBox.toggleClass('hide', shift_numbers.length === 0);
        $('#add_payment').prop('disabled', shift_numbers.length === 0).toggleClass('disabled',
            shift_numbers.length === 0);

        // build readable label list
        const labels = (shift_numbers || []).map(id => $shiftNumber.find(`option[value="${id}"]`)
            .text()).filter(Boolean);
        $('.shift_number').html(labels.join(', '));

        // Update #add_payment data-href so the modal always receives the currently selected shift_ids.
        // Without this, the URL is baked in at page-render time and misses dynamically-chosen shifts,
        // causing meter sales to return 0 in AddPaymentController::create.
        if (shift_numbers.length > 0) {
            const $addPaymentBtn = $('#add_payment');
            const baseHref = $addPaymentBtn.data('base-href') || $addPaymentBtn.data('href') || '';
            if (baseHref) {
                const shiftParam = shift_numbers.map(id => 'shift_ids[]=' + encodeURIComponent(id)).join('&');
                const separator = baseHref.includes('?') ? '&' : '?';
                $addPaymentBtn.attr('data-href', baseHref + separator + shiftParam);
            }
        }

        // Load meter sale / other sale data as soon as shift is chosen (must not be blocked by unsettled check).
        if (shift_numbers.length > 0) {
            loadOtherSalesData();
            loadMeterSalesData();
        }

        // Warn if a previous shift on this pump is still unsettled (non-blocking for autoload).
        if (!skipUnsettledCheck && String(shift_id) !== '0') {
            await checkPreviousUnsettled(shift_id);
        }
    });

    // ---------- Data loading functions ----------
    function loadOtherSalesData() {
        $('#outside_other_sale_table').show();
        $('#other_sale_table').hide();

        initOrReloadDataTable('#pump_operator_other_sale_table', {
            ajax: {
                url: "{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}",
                data: function (d) {
                    console.log($('#shift_number').val());
                    d.shift_ids = $('#shift_number').val();
                    d.pump_operator_id = $pumpOperator.val();
                    const pumpId = $('#pump_no_pd').val();
                    if (pumpId) d.pump_id = pumpId;
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
        // apiGet("{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}", {
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
        var new_shift_ids = $('#shift_number').val();
        // console.log(new_shift_ids,'mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm');
        if (!new_shift_ids || new_shift_ids.length === 0) {
            // alert('exit');
            return; // nothing selected, exit
        }
        const pumpId = $('#pump_no_pd').val();
        const payload = {
            shift_ids: new_shift_ids,
            pump_operator_id: $pumpOperator.val(),
            get_total: true
        };
        if (pumpId) payload.pump_id = pumpId;

        apiGet("{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@otherSalesList') }}", payload).done(result => {
            if (result && result.success == 1) {
                if (result.pump_nos && !getSelectedDirectShiftNumber()) {
                    const currentPumpId = $('#pump_no_pd').val();
                    updatePumpDropdown(result.pump_nos || {});
                    if (currentPumpId && $('#pump_no_pd option[value="' + currentPumpId + '"]').length) {
                        $('#pump_no_pd').val(currentPumpId).trigger('change.select2');
                    }
                }

                $('#shift_operator_other_sale_total').val(parseFloat(result.total) || 0);
                $('#other_sale_total').val(0);
                calculate_payment_tab_total();
            } else {
                toastError("Error fetching other sale total");
            }
        }).fail(() => toastError("Error fetching other sale data"));
    }


    function loadMeterSalesData() {
        let shiftIds = $shiftNumber.val();
        if (shiftIds == null || shiftIds === '') {
            shiftIds = [];
        } else if (!Array.isArray(shiftIds)) {
            shiftIds = [shiftIds];
        }

        if (!shiftIds.length) {
            const firstShiftId = $shiftNumber.find('option:selected').first().val() || $shiftNumber.find('option:first').val();
            if (firstShiftId) {
                shiftIds = [firstShiftId];
                $shiftNumber.val(shiftIds).trigger('change.select2');
            }
        }

        let pumpOperatorId = $pumpOperator.val();
        if (!pumpOperatorId && shiftIds.length) {
            const optionOperatorId = $shiftNumber.find(`option[value="${shiftIds[0]}"]`).data('pump-operator');
            if (optionOperatorId) {
                pumpOperatorId = optionOperatorId;
                $pumpOperator.val(pumpOperatorId).trigger('change.select2');
            }
        }

        const activeSettlementId = $activeSettlement.val();
        if ((!shiftIds.length && (!activeSettlementId || activeSettlementId === '0')) || !pumpOperatorId) {
            $('#outside_meter_sale_table').hide();
            $('#meter_sale_table_wrap').show();
            return;
        }

        $('#outside_meter_sale_table').show();
        $('#meter_sale_table_wrap').hide();

        initOrReloadDataTable('#pump_operator_meter_sale_table', {
            ajax: {
                url: "{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@meterSalesList') }}",
                data: d => {
                    d.shift_ids = shiftIds;
                    d.pump_operator_id = pumpOperatorId;
                    d.active_settlement_id = $activeSettlement.val();
                    d.only_closed_pump_meter_sales = 1;
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
                    const editButton =
                        `<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro/settlement-pd/get-meter-sale-form/${row.id}"><i class="fa fa-edit"></i></button>`;
                    const deleteButton = (row.later_settlements < 1 || !row.transaction_id ||
                        row.bulk_tank == 1) ?
                        `<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro/settlement-pd/delete-meter-sale/${row.id}"><i class="fa fa-times"></i></button>` :
                        '';
                    return editButton + ' ' + deleteButton;
                }
            }
            ],
            fnDrawCallback() {
                const total = sum_table_col($('#pump_operator_meter_sale_table'), 'discount_amount') || sum_table_col($('#pump_operator_meter_sale_table'), 'sub_total');
                const formatted_total = __number_f(total, false, false, __currency_precision);
                $('#footer_list_meter_sales_amount, .meter_sale_total').val(total).text(formatted_total);

                // Keep Payments tab totals in sync with the shift-based meter sales table.
                // Payments summary reads from `#meter_sale_total` via `calculate_payment_tab_total()`.
                const numericTotal = parseFloat(total) || 0;
                $('#meter_sale_total').val(numericTotal);
                if (typeof calculate_payment_tab_total === 'function') {
                    calculate_payment_tab_total();
                }
                __currency_convert_recursively($('#pump_operator_meter_sale_table'));
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
            $note.val(data.note || '');
            $transactionDate.val(data.transaction_date || '');
            $location.val(data.location_id || '').trigger('change');
            $workShift.val(data.work_shift || '').trigger('change');

            if (data.pump_operator_id) {
                setTimeout(() => $pumpOperator.val(data.pump_operator_id).trigger('change'), 500);
            }
            //  alert('hello');
            console.log(data.pump_operator_id, 'pump operatro id in restore local data');
            if (!hasActiveSettlement) {
                setTimeout(() => {
                    $('#pump_no_pd').val(data.pump_no || '').trigger('change');
                    // $('#pump_starting_meter').val(data.pump_starting_meter || '');
                    // $('#sold_qty').val(data.sold_qty || '');
                    // $('#meter_sale_unit_price').val(data.meter_sale_unit_price || '');
                    // $('#testing_qty').val(data.testing_qty || '');
                    // $('#meter_sale_discount_type').val(data.meter_sale_discount_type || '').trigger('change');
                    // $('#meter_sale_discount').val(data.meter_sale_discount || '');
                }, 1000);
            }
        } catch (e) {
            console.error('Error parsing saved data:', e);
        }
    }

    // ---------- PD Overrides for Add Buttons ----------
    let pd_meter_sale_submitting = false;
    let current_pump_id = null;
    let current_product_id = null;
    let current_price = 0;
    let current_code = '';
    let current_product_name = '';
    let current_pump_name = '';

    $doc.off('change', '#pump_no_pd').on('change', '#pump_no_pd', function () {
        const pump_id = $(this).val();
        const shift_id = $('#shift_number').val();
        if (!pump_id || !shift_id) return;

        $.ajax({
            method: 'get',
            url: '/petro/settlement-pd/get-pump-details/' + pump_id + '/' + (Array.isArray(
                shift_id) ? shift_id[0] : shift_id),
            success: function (result) {
                $('#pump_starting_meter').val(result.colsing_value);
                if (result.po_closing > 0) {
                    $('#pump_closing_meter').val(result.po_closing).trigger('change');
                    $("#is_from_pumper").val(1);
                    $('#assignment_id').val(result.assignment_id);
                    $('#pumper_entry_id').val(result.pumper_entry_id);
                } else {
                    $('#pump_closing_meter').val("");
                    $("#is_from_pumper").val(0);
                }

                if (result.po_testing > 0) {
                    $('#testing_qty').val(result.po_testing).prop('readonly', true).trigger(
                        'change');
                } else {
                    $('#testing_qty').val(0).prop('readonly', false);
                }

                current_pump_id = result.pump_id;
                current_product_id = result.product_id;
                current_price = result.product.default_sell_price;
                current_code = result.product.sku;
                current_product_name = result.product.name;
                current_pump_name = result.pump_name;

                if (result.bulk_sale_meter == '1') {
                    $('#bulk_sale_meter').val(1);
                    $('.pump_starting_meter_div, .pump_closing_meter_div').addClass('hide');
                    $('#sold_qty').prop('disabled', false);
                } else {
                    $('#bulk_sale_meter').val(0);
                    $('.pump_starting_meter_div, .pump_closing_meter_div').removeClass(
                        'hide');
                    $('#sold_qty').prop('disabled', true);
                }
                $('#meter_sale_unit_price').val(current_price);

                // Reload only Other Sales when Pump No changes.
                if (typeof loadOtherSalesData === 'function') loadOtherSalesData();
            }
        });
    });

    $doc.off('click.petro_meter_sale_pd').on('click.petro_meter_sale_pd', '.btn_meter_sale_pd',
        function () {
            if (pd_meter_sale_submitting) return false;

            const $btn = $(this);
            const sold_qty = parseFloat($('#sold_qty').val()) || 0;
            if (sold_qty <= 0 && $('#bulk_sale_meter').val() == 0) {
                toastError("Sold quantity must be greater than zero.");
                return false;
            }

            pd_meter_sale_submitting = true;
            $btn.prop('disabled', true).addClass('disabled');

            const discount = $('#meter_sale_discount').val() || 0;
            const discount_type = $('#meter_sale_discount_type').val() || 'fixed';
            const sub_total = sold_qty * current_price;
            const discount_amount = sub_total - calculate_discount_pd(discount_type, discount,
                sub_total);

            $.ajax({
                method: 'post',
                url: '/petro/settlement-pd/save-meter-sale',
                data: {
                    settlement_no: $('#settlement_no').val(),
                    location_id: $('#location_id').val(),
                    pump_operator_id: $('#pump_operator_id').val(),
                    transaction_date: $('#transaction_date').val(),
                    work_shift: $('#work_shift').val(),
                    direct_shift_number: getSelectedDirectShiftNumber(),
                    note: $('#note').val(),
                    pump_id: current_pump_id,
                    starting_meter: $('#pump_starting_meter').val(),
                    closing_meter: $('#pump_closing_meter').val(),
                    product_id: current_product_id,
                    price: current_price,
                    qty: sold_qty,
                    discount: discount,
                    discount_type: discount_type,
                    discount_amount: discount_amount,
                    testing_qty: $('#testing_qty').val() || 0,
                    sub_total: sub_total,
                    is_edit: $('#is_edit').val() || 0,
                    is_from_pumper: $("#is_from_pumper").val() || 0,
                    assignment_id: $("#assignment_id").val() || 0,
                    pumper_entry_id: $("#pumper_entry_id").val() || 0,
                    shift_id: (Array.isArray($('#shift_number').val()) ? $('#shift_number').val()[
                        0] : $('#shift_number').val())
                },
                success: function (result) {
                    if (result.success) {
                        toastSuccess(result.msg || "Meter sale added.");
                        $('#active_settlement_id').val(result.settlement_id);
                        if (result.shift_id) {
                            $('#shift_number').val([String(result.shift_id)]).trigger('change.select2');
                        }
                        if (result.pump_operator_id) {
                            $('#pump_operator_id').val(String(result.pump_operator_id)).trigger('change.select2');
                        }
                        if (typeof loadMeterSalesData === 'function') loadMeterSalesData();
                        if (typeof refresh_settlement_totals === 'function')
                            refresh_settlement_totals();

                        // Clear fields
                        $('#pump_no_pd').val('').trigger('change');
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

    function calculate_discount_pd(type, value, amount) {
        if (type === 'fixed') return parseFloat(value) || 0;
        if (type === 'percentage') return (amount * (parseFloat(value) || 0) / 100);
        return 0;
    }

    $doc.off('click.petro_other_sale_pd', '.btn_other_sale').on('click.petro_other_sale_pd',
        '.btn_other_sale',
        function (e) {
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
            const discount_amount = calculate_discount_pd(discount_type, discount, sub_total);

            $.ajax({
                method: 'post',
                url: "{{ action('\Modules\Petro\Http\Controllers\SettlementPDController@saveOtherSale') }}",
                data: {
                    settlement_no: $('#settlement_no').val(),
                    location_id: $('#location_id').val(),
                    pump_operator_id: $('#pump_operator_id').val(),
                    transaction_date: $('#transaction_date').val(),
                    work_shift: $('#work_shift').val(),
                    note: $('#note').val(),
                    product_id: $('#item').val(),
                    store_id: $('#store_id').val(),
                    price: price,
                    qty: qty,
                    balance_stock: $('#balance_stock').val(),
                    discount: discount,
                    discount_type: discount_type,
                    discount_amount: discount_amount,
                    sub_total: sub_total,
                    is_edit: $('#is_edit').val() || 0
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
                        if (typeof refresh_settlement_totals === 'function')
                            refresh_settlement_totals();
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


    // ---------- Modal loaders & buttons ----------
    // `#add_payment` is handled by the global `.btn-modal` loader in `public/js/app.js`.

    $doc.on('click', '#payment_review_btn, #product_preview_btn', function () {
        const url = $(this).data('href');
        $('.preview_settlement').load(url, function () {
            $('.preview_settlement').modal({
                backdrop: 'static',
                keyboard: false
            });
        });
    });

    // bulk tank change
    $doc.on('change', '#bulk_tank', function () {
        const tank_id = $(this).val();
        if (!tank_id) return;
        apiGet("{{ action('\Modules\Petro\Http\Controllers\FuelTankController@getTankProduct') }}/" +
            tank_id)
            .done(result => {
                const html =
                    `<option value="">Please Select</option><option value="${result.id}">${result.name}</option>`;
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
        // datepickers & select2 & datetimepickers initialization
        $('.transaction_date').datepicker("setDate",
            @if (!empty($active_settlement))
            "{{ \Carbon::parse($active_settlement->transaction_date)->format('m/d/Y') }}"
        @else
        new Date()
        @endif );
    $('#customer_payment_cheque_date').datepicker("setDate", new Date());
    $('#location_id, #item, #store_id, #bulk_tank, #pump_operator_id, #work_shift, #card_customer_id, #customer_payment_customer_id')
        .select2();
    $('#shift_number').select2();
    $('#pump_no_pd').select2();
    $('#shif_time_in, #shif_time_out').datetimepicker({
        format: 'LT'
    });
    $('#settlement_print').css('visibility', 'hidden');

    function loadPumpsByLocation() {
        const locationId = $('#location_id').val();
        if (!locationId) return;
        if ($('#pump_operator_id').val()) return;

        const $pumpSelect = $('#pump_no_pd');
        const currentPumpId = $pumpSelect.val();

        // Use the exact same endpoint path as other settlement pages.
        apiGet('/petro/settlement/get_pumps_by_location', {
            location_id: locationId,
            include_other_sales_pump: 1
        }).done(res => {
            if (!res || !res.success) return;

            const pumps = res.pumps || {};
            console.log('[PD] pumps_by_location', {
                location_id: locationId,
                include_other_sales_pump: 1,
                pump_count: Object.keys(pumps).length,
                pumps
            });
            const $oldOptions = $pumpSelect.find('option');

            // Build new dropdown options for ALL pumps in the location.
            $pumpSelect.empty().append('<option value="">{{ __('petro::lang.please_select') }}</option>');

            let firstPumpId = null;
            $.each(pumps, function (id, name) {
                if (firstPumpId === null) firstPumpId = id;
                $pumpSelect.append(`<option value="${id}">${name}</option>`);
            });

            // Restore selection if possible, otherwise pick first.
            if (currentPumpId) {
                const hasSelection = pumps[currentPumpId] !== undefined || pumps[String(currentPumpId)] !== undefined;
                if (hasSelection) $pumpSelect.val(currentPumpId);
                else if (firstPumpId !== null) $pumpSelect.val(firstPumpId);
            } else if (firstPumpId !== null) {
                $pumpSelect.val(firstPumpId);
            }

            // Refresh select2 + trigger change flow (pump detail fetch + table reload).
            $pumpSelect.trigger('change.select2');
        }).fail(xhr => {
            console.log('[PD] pumps_by_location FAILED', {
                status: xhr && xhr.status,
                response: xhr && xhr.responseText
            });
            // If this fails, keep existing dropdown options.
        });
    }

    // Reload pump list whenever business location changes.
    $doc.off('change.petro_settlement_pd_location_pumps', '#location_id')
        .on('change.petro_settlement_pd_location_pumps', '#location_id', function () {
            loadPumpsByLocation();
        });

    // Initial pump list should show all pumps in this location.
    loadPumpsByLocation();

    // Ensure correct initial table state before any async restoration.
    // If a shift is already selected, the Ajax table is the source of truth.
    if (normalizeShiftIds($('#shift_number').val()).length) {
        $('#outside_meter_sale_table').show();
        $('#meter_sale_table_wrap').hide();
    } else {
        $('#outside_meter_sale_table').hide();
        $('#meter_sale_table_wrap').show();
    }

    // restore local data if any
    restoreLocalData();

    // Auto-trigger data loading with shift-first logic so the linked operator is derived from the shift.
    setTimeout(function() {
        console.log('=== Auto-selection Logic Starting ===');

        let prefilledShift = normalizeShiftIds($('#shift_number').val());
        let prefilledOperator = $('#pump_operator_id').val();

        console.log('Initial state - Operator:', prefilledOperator, 'Shift:', prefilledShift);
        console.log('Pump operator is disabled:', $('#pump_operator_id').prop('disabled'));
        console.log('Available operators:', $('#pump_operator_id option').length);
        console.log('Available shifts:', $('#shift_number option').length);

        if (!prefilledShift.length) {
            const firstShiftOption = $('#shift_number option:first').val();
            console.log('First shift option found:', firstShiftOption);

            if (firstShiftOption && firstShiftOption !== '') {
                prefilledShift = [firstShiftOption];
                $('#shift_number').val(prefilledShift).trigger('change.select2');
            }
        }

        if (prefilledShift.length) {
            console.log('Auto-loading data for pre-filled shift number:', prefilledShift);
            $('#shift_number').trigger('change');
            return;
        }

        if (!prefilledOperator || prefilledOperator === '') {
            const firstOperatorOption = $('#pump_operator_id option:not([value=""]):first').val();
            console.log('First operator option found:', firstOperatorOption);

            if (firstOperatorOption && firstOperatorOption !== '') {
                console.log('Auto-selecting first pump operator:', firstOperatorOption);
                $('#pump_operator_id').val(firstOperatorOption).trigger('change');
            } else {
                console.log('No valid operator option found to auto-select');
            }
        } else {
            console.log('Operator already pre-filled:', prefilledOperator);
        }
    }, 500);
            });

        }) ();
</script>
@endsection
