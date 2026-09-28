{{--
    SW Settlement — create.  8043, laid out as Petro Direct's settlement.

    Header first: settlement number, location, operator, date, shift, note.
    The sections follow beneath.

    The operator is chosen BEFORE the shifts, and the shift list follows from
    it - 8043 is explicit that shifts are "linked to the selected operator
    only". Offering every shift and filtering afterwards would let someone
    settle another operator's shift.
--}}

@extends('layouts.app')

@section('title', __('sw::lang.new_settlement'))

@section('content')

<section class="content-header">
    <h1>@lang('sw::lang.new_settlement')
        <small>@lang('sw::lang.new_settlement_subtitle')</small>
    </h1>
</section>

<section class="content main-content-inner">

    {!! Form::open(['url' => route('sw.settlements.store', [], false), 'method' => 'post', 'id' => 'sw_settlement_form']) !!}

    <div class="row settlement-header-row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="row">

                {{-- Auto-incremented, shown, and not editable: a settlement
                     number the user can change is a settlement number that can
                     collide with another. --}}
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('sw::lang.settlement_no') . ':') !!}
                        {!! Form::text('settlement_no', $settlement_no, [
                            'class' => 'form-control', 'readonly', 'id' => 'sw_settlement_no',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, $default_location, [
                            'class' => 'form-control select2',
                            'id' => 'sw_st_location',
                            'required',
                            'placeholder' => __('messages.please_select'),
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('pump_operator_id', __('sw::lang.pump_operator') . ':*') !!}
                        {!! Form::select('pump_operator_id', $pump_operators, null, [
                            'class' => 'form-control select2',
                            'id' => 'sw_st_operator',
                            'required',
                            'placeholder' => __('sw::lang.please_select_the_operator'),
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('sw::lang.transaction_date') . ':*') !!}
                        {{-- Today, set here rather than by the picker: a
                             default that depends on JavaScript running is a
                             default that is sometimes absent. --}}
                        {!! Form::text('transaction_date', now()->format($settlement_date_php_format ?? 'm/d/Y'), [
                            'class' => 'form-control sw-date',
                            'id' => 'sw_st_date',
                            'required',
                            'readonly',
                        ]) !!}
                    </div>
                </div>

                {{-- Pending shifts for the chosen operator. Multiple may be
                     settled together, so the payment figures sum across them. --}}
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('shift_ids', __('sw::lang.sw_shift_number') . ':*') !!}
                        {{-- Not required in the markup.

                             A site with no pending shifts must still be able to
                             settle. Where pending shifts DO exist, one has to be
                             chosen - enforced below, because "required" cannot
                             express "only when there are any". --}}
                        {!! Form::select('shift_ids[]', [], null, [
                            'class' => 'form-control select2',
                            'id' => 'sw_st_shifts',
                            'multiple',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('note', __('sw::lang.note') . ':') !!}
                        {!! Form::text('note', null, [
                            'class' => 'form-control', 'id' => 'sw_st_note',
                        ]) !!}
                    </div>
                </div>

            </div>
            @endcomponent
        </div>
    </div>

    @include('sw::settlements.partials.section_styles')

    {{-- The sections hold their rows in the browser until the settlement is
         saved, so they live in the page rather than in the AJAX body. --}}
    <div id="sw_st_sections" style="display:none">
        @include('sw::settlements.partials.meter_sales')
        @include('sw::settlements.partials.other_sales')
        @include('sw::settlements.partials.other_income')
        @include('sw::settlements.partials.payments')
    </div>

    <input type="hidden" name="total_meter_sales" id="sw_meter_total_input" value="0">

    <div id="sw_st_body"></div>

    {!! Form::close() !!}

    @include('sw::settlements.partials.settlement_preview')

</section>

@include('sw::settlements.partials.meter_sales_js')
@include('sw::settlements.partials.other_sales_js')
@include('sw::settlements.partials.other_income_js')
@include('sw::settlements.partials.credit_sales_js')
@include('sw::settlements.partials.payments_js')

{{--
    S752: this script is deliberately emitted with the form instead of being
    pushed to a layout stack.  Some host layouts do not render module stacks;
    in that case a type="button" Save action otherwise has no click handler.
--}}
<script>
$(function () {

    /*
     | S732 final: one authoritative Save Settlement state.
     |
     | The visible Balance is the authoritative browser-side state. Save must
     | appear whenever at least one shift is selected and that live balance is
     | zero. An optional AJAX response must never leave a correctly balanced
     | form without its Save action.
     */
    window.swSettlementDataReady = false;
    var swSettlementLoadSeq = 0;

    window.swUpdateSettlementSaveState = function (knownBalance) {
        var selectedShifts = $('#sw_st_shifts').val() || [];
        var balance = knownBalance;

        if (balance === undefined || balance === null || !isFinite(parseFloat(balance))) {
            balance = parseFloat(String($('#sw_balance').text() || '').replace(/,/g, ''));
        }

        var hasSelectedShift = $.isArray(selectedShifts)
            ? selectedShifts.length > 0
            : String(selectedShifts || '').length > 0;
        var zeroBalance = isFinite(balance) && Math.abs(parseFloat(balance)) < 0.005;
        // Visibility follows the user's exact rule: show Save only at Balance
        // 0.00. Submission still requires a selected shift, so visibility and
        // form validity remain separate concerns.
        var showSave = zeroBalance;
        var canSave = showSave && hasSelectedShift;

        var $dock = $('#sw_st_save_dock');
        var $btn = $('#sw_st_save_btn');
        $btn.prop('disabled', !canSave)
            .attr('aria-disabled', canSave ? 'false' : 'true')
            .attr('aria-hidden', showSave ? 'false' : 'true');
        $dock.toggleClass('is-balanced', showSave);
        $dock.toggleClass('is-ready', canSave);
        $('#sw_st_save_hint').text(canSave
            ? @json(__('sw::lang.settlement_ready_to_save'))
            : @json(__('sw::lang.settlement_balance_must_be_zero'))
        );

        return canSave;
    };

    window.swSettlementSaveReady = function () {
        return window.swUpdateSettlementSaveState();
    };

    // Balance changes after payment/credit edits must immediately update Save.
    if (window.MutationObserver && document.getElementById('sw_balance')) {
        new MutationObserver(function () {
            window.swUpdateSettlementSaveState();
        }).observe(document.getElementById('sw_balance'), {
            childList: true, characterData: true, subtree: true
        });
    }

    /*
     | IS2245: browser-side draft protection.
     |
     | A settlement can contain many meter, product, credit-sale and payment rows.
     | A refresh must not erase that work. The draft is keyed by business, user
     | and the displayed settlement number, so after a successful save the next
     | settlement receives another number and cannot accidentally restore the old
     | one. No database table, route or command is needed.
     */
    var swDraftKey = 'sw:settlement:draft:v1:'
        + @json((int) (optional(auth()->user())->business_id ?? session('user.business_id') ?? 0))
        + ':' + @json((int) (auth()->id() ?? 0))
        + ':' + @json((string) ($settlement_no ?? ''));

    var swDraft = null;
    var swDraftRestoring = true;
    var swDraftDynamicRestored = false;
    var swDraftTimer = null;

    var swDraftFieldIds = [
        'sw_ms_pump','sw_ms_start','sw_ms_close','sw_ms_price','sw_ms_testing','sw_ms_disc_type','sw_ms_disc',
        'sw_os_store','sw_os_product','sw_os_qty','sw_os_price','sw_os_disc_type','sw_os_disc',
        'sw_oi_service','sw_oi_details','sw_oi_qty','sw_oi_amount',
        'sw_cs_customer','sw_cs_order_no','sw_cs_order_date','sw_cs_vehicle_select','sw_cs_vehicle',
        'sw_cs_product','sw_cs_qty','sw_cs_price','sw_cs_disc','sw_cs_before','sw_cs_discount_amount','sw_cs_after','sw_cs_note',
        'sw_pay_customer','sw_pay_account','sw_pay_expense_category','sw_pay_amount','sw_pay_note'
    ];

    function swReadDraft() {
        try {
            var raw = window.localStorage ? localStorage.getItem(swDraftKey) : null;
            if (!raw) { return null; }

            var parsed = JSON.parse(raw);
            var age = Date.now() - parseInt(parsed.saved_at || 0, 10);

            // Do not resurrect a very old abandoned settlement.
            if (!parsed || !parsed.saved_at || age > (7 * 24 * 60 * 60 * 1000)) {
                localStorage.removeItem(swDraftKey);
                return null;
            }

            return parsed;
        } catch (ignore) {
            return null;
        }
    }

    function swDraftFields() {
        var fields = {};
        $.each(swDraftFieldIds, function (i, id) {
            var $field = $('#' + id);
            if (!$field.length) { return; }

            var value = $field.val();
            fields[id] = Array.isArray(value) ? value.slice() : value;
        });
        return fields;
    }

    function swDraftSaveNow() {
        if (swDraftRestoring) { return; }

        try {
            var draft = {
                version: 1,
                saved_at: Date.now(),
                settlement_no: $('#sw_settlement_no').val() || '',
                location_id: $('#sw_st_location').val() || '',
                pump_operator_id: $('#sw_st_operator').val() || '',
                shift_ids: $('#sw_st_shifts').val() || [],
                transaction_date: $('#sw_st_date').val() || '',
                note: $('#sw_st_note').val() || '',
                payment_type: String($('.sw-pay-btn.is-active').data('type') || ''),
                fields: swDraftFields(),
                meter_lines: typeof window.swSettlementDraftGetMeterLines === 'function'
                    ? window.swSettlementDraftGetMeterLines() : [],
                other_sales: typeof window.swSettlementDraftGetOtherSales === 'function'
                    ? window.swSettlementDraftGetOtherSales() : [],
                other_income: typeof window.swSettlementDraftGetOtherIncome === 'function'
                    ? window.swSettlementDraftGetOtherIncome() : [],
                credit_sales: typeof window.swSettlementDraftGetCreditSales === 'function'
                    ? window.swSettlementDraftGetCreditSales() : [],
                payments: typeof window.swSettlementDraftGetPayments === 'function'
                    ? window.swSettlementDraftGetPayments() : []
            };

            localStorage.setItem(swDraftKey, JSON.stringify(draft));
            swDraft = draft;
        } catch (ignore) {
            // Storage can be disabled by the browser; settlement entry still works.
        }
    }

    function swDraftSchedule() {
        if (swDraftRestoring) { return; }
        clearTimeout(swDraftTimer);
        swDraftTimer = setTimeout(swDraftSaveNow, 120);
    }

    function swRestoreFieldValues() {
        if (!swDraft || !swDraft.fields) { return; }

        $.each(swDraft.fields, function (id, value) {
            var $field = $('#' + id);
            if (!$field.length) { return; }

            if ($field.is('select')) {
                var values = Array.isArray(value) ? value.map(String) : [String(value == null ? '' : value)];
                var available = values.filter(function (candidate) {
                    return $field.find('option').filter(function () {
                        return String($(this).val()) === candidate;
                    }).length > 0;
                });

                if (available.length) {
                    $field.val($field.prop('multiple') ? available : available[0]).trigger('change');
                }
            } else if (!$field.is('[readonly]') || id === 'sw_cs_before' || id === 'sw_cs_discount_amount' || id === 'sw_cs_after') {
                $field.val(value);
            }
        });

        if (swDraft.payment_type) {
            var $button = $('.sw-pay-btn[data-type="' + String(swDraft.payment_type).replace(/"/g, '\\"') + '"]');
            if ($button.length && !$button.hasClass('is-active')) {
                $button.trigger('click');
            }
        }
    }

    window.swSettlementDraftRestoreFields = swRestoreFieldValues;

    function swRestoreDynamicDraft() {
        if (swDraftDynamicRestored || !swDraft) {
            swDraftRestoring = false;
            return;
        }

        swDraftDynamicRestored = true;

        // Preserve Meter Sale rows only for the exact shift set they were saved
        // with in the browser draft. If those shifts are no longer available and
        // swLoadShifts() selects a different shift, the following shift-change
        // event will clear the old meter rows before Preview/Save.
        if (typeof window.swSettlementMeterShiftContextChanged === 'function') {
            window.swSettlementMeterShiftContextChanged(swDraft.shift_ids || []);
        }

        if (typeof window.swSettlementDraftSetMeterLines === 'function') {
            window.swSettlementDraftSetMeterLines(swDraft.meter_lines || []);
        }
        if (typeof window.swSettlementDraftSetOtherSales === 'function') {
            window.swSettlementDraftSetOtherSales(swDraft.other_sales || []);
        }
        if (typeof window.swSettlementDraftSetOtherIncome === 'function') {
            window.swSettlementDraftSetOtherIncome(swDraft.other_income || []);
        }
        if (typeof window.swSettlementDraftSetCreditSales === 'function') {
            window.swSettlementDraftSetCreditSales(swDraft.credit_sales || []);
        }
        if (typeof window.swSettlementDraftSetPayments === 'function') {
            window.swSettlementDraftSetPayments(swDraft.payments || []);
        }

        swDraftRestoring = false;

        // Several selects load their options asynchronously. Re-apply draft
        // values a few times; text/number fields are idempotent.
        [50, 450, 1200, 2600].forEach(function (delay) {
            setTimeout(swRestoreFieldValues, delay);
        });

        setTimeout(swDraftSaveNow, 2800);
    }

    swDraft = swReadDraft();

    if (swDraft) {
        if (swDraft.transaction_date) { $('#sw_st_date').val(swDraft.transaction_date); }
        if (swDraft.note != null) { $('#sw_st_note').val(swDraft.note); }

        var draftLocation = String(swDraft.location_id || '');
        if (draftLocation && $('#sw_st_location option[value="' + draftLocation.replace(/"/g, '\\"') + '"]').length) {
            $('#sw_st_location').val(draftLocation).trigger('change.select2');
        }
    }

    $(document).on('sw:settlement-draft-changed', swDraftSchedule);
    $('#sw_settlement_form').on('input change', 'input:not([type="hidden"]), select, textarea', swDraftSchedule);

    /*
     | The date cannot be before the system opening date, and cannot be today
     | if today's accounts are closed.
     |
     | Enforced by the picker rather than only on save: a user who fills in a
     | whole settlement and is then refused has lost the work.
    */
    /*
     | The picker must not clear the field.
     |
     | setDate with a format the picker does not recognise empties the input -
     | the date appeared on load and vanished as soon as this ran. The value is
     | set server-side; the picker only offers to change it.
    */
    $('#sw_st_date').datepicker({
        format: '{{ $settlement_date_picker_format ?? "mm/dd/yyyy" }}',
        autoclose: true,
        todayHighlight: true,
        startDate: '{{ !empty($opening_date) ? \Carbon\Carbon::parse($opening_date)->format($settlement_date_php_format ?? 'm/d/Y') : "" }}',
        endDate: '{{ !empty($max_date) ? \Carbon\Carbon::parse($max_date)->format($settlement_date_php_format ?? 'm/d/Y') : "" }}',
    });

    /*
     | IS2231: settlement operators follow the selected LOCATION and the
     | SW shift state.  The list includes operators on a current
     | OPEN shift (IS2230 requirement) and operators whose shift has just been
     | CLOSED and is waiting for settlement, so the close -> settle transition
     | cannot make a valid operator disappear.
     |
     | No extra route is needed, which keeps this plug-and-play even on servers
     | with cached route files.
    */
    var swSettlementOperatorsByLocation = @json($settlement_operators_by_location ?? []);

    function swRefreshSettlementOperators(preferredOperatorId) {
        var $operator = $('#sw_st_operator');
        var locationId = String($('#sw_st_location').val() || '');
        var rows = swSettlementOperatorsByLocation[locationId] || [];
        var oldValue = String(preferredOperatorId || $operator.val() || '');
        var placeholder = @json(__('sw::lang.please_select_the_operator'));

        $operator.empty().append($('<option>', { value: '', text: placeholder }));

        $.each(rows, function (i, row) {
            $operator.append($('<option>', {
                value: String(row.id),
                text: row.name
            }));
        });

        var keepOld = rows.some(function (row) {
            return String(row.id) === oldValue;
        });

        if (keepOld) {
            $operator.val(oldValue);
        } else if (rows.length === 1) {
            // One valid operator at this location: select it automatically.
            $operator.val(String(rows[0].id));
        } else {
            $operator.val('');
        }

        // Refresh Select2's visible text without firing the normal change
        // handler a second time. swLoadShifts() is called explicitly below.
        if ($operator.hasClass('select2-hidden-accessible')) {
            $operator.trigger('change.select2');
        }

        return rows.length;
    }

    /*
     | The shift list follows the OPERATOR, not just the location.
     |
     | 8043: shifts are linked to the selected operator only. Offering every
     | shift at a location would let someone settle a shift another operator
     | worked.
    */
    function swLoadShifts(preferredShiftIds) {
        var $sel = $('#sw_st_shifts');
        var locationId = $('#sw_st_location').val();
        var operatorId = $('#sw_st_operator').val();

        $sel.html('').trigger('change');
        $('#sw_st_sections').hide();
        $('#sw_st_save_btn').prop('disabled', true);
        $('#sw_st_body').html('');

        if (!locationId || !operatorId) {
            // No valid dependency chain can restore a draft yet. Release the
            // restore guard so the next user selection is saved normally.
            if (swDraft) { swDraftRestoring = false; }
            return;
        }

        $.get('{{ route('sw.settlements.available-shifts') }}', {
            location_id: locationId,
            pump_operator_id: operatorId
        }, function (rows) {
            /*
             | SettlementSaveService requires at least one CLOSED shift.
             |
             | An operator can be visible because they are currently working an
             | OPEN shift, but that shift cannot be settled until it is closed.
             | Keep the settlement sections/save button hidden in that case
             | instead of allowing the user to fill the form and receive a 422
             | only at the end.
            */
            $sel.data('has-pending', rows.length > 0);

            if (!rows.length) {
                // A stale draft must never leave autosave disabled indefinitely.
                // Do not restore its payment rows without a valid CLOSED shift,
                // but allow the next user change to create a fresh safe draft.
                if (swDraft) { swDraftRestoring = false; }
                toastr.info('{{ __('sw::lang.no_pending_shifts_for_operator') }}');
                return;
            }
            $.each(rows, function (i, r) {
                $sel.append($('<option>', { value: r.id, text: r.label }));
            });

            /*
             | Auto-load the oldest pending CLOSED shift for this operator.
             |
             | The endpoint is ordered oldest first. Keeping the other options
             | in the multiple-select still lets the user settle additional
             | pending shifts together, but the normal one-shift case now
             | requires no second manual selection.
            */
            var allowed = {};
            $.each(rows, function (i, row) { allowed[String(row.id)] = true; });

            var preferred = Array.isArray(preferredShiftIds)
                ? preferredShiftIds.map(String)
                : (preferredShiftIds ? [String(preferredShiftIds)] : []);

            var selected = preferred.filter(function (id) { return !!allowed[id]; });
            if (!selected.length) {
                selected = [String(rows[0].id)];
            }

            $sel.val(selected);
            swRestoreDynamicDraft();
            $sel.trigger('change');
        });
    }

    /*
     | A pending shift must be settled, not stepped around.
     |
     | Checked on submit rather than by "required": the field is only mandatory
     | when there is something in it to choose.
    */
    function swCanSubmitSettlement(showMessage) {
        var $sel = $('#sw_st_shifts');
        var hasPending = $sel.data('has-pending');
        var chosen = $sel.val() || [];

        if (hasPending && !chosen.length) {
            if (showMessage) {
                toastr.error(@json(__('sw::lang.pending_shift_must_be_settled')));
                $sel.focus();
            }
            return false;
        }

        var balance = parseFloat(String($('#sw_balance').text() || '').replace(/,/g, ''));
        if (!window.swSettlementSaveReady() || !isFinite(balance) || Math.abs(balance) >= 0.005) {
            if (showMessage) {
                toastr.error(@json(__('sw::lang.settlement_balance_must_be_zero')));
            }
            return false;
        }

        var form = document.getElementById('sw_settlement_form');
        if (form && typeof form.checkValidity === 'function' && !form.checkValidity()) {
            if (showMessage && typeof form.reportValidity === 'function') {
                form.reportValidity();
            }
            return false;
        }

        return true;
    }

    /*
     | S739: the visible fixed button submits the form directly once the exact
     | same business rules pass.  This intentionally avoids depending on any
     | global delegated submit/button handler in the host ERP which could stop
     | the SW form after the user clicks Save Settlement.
     */
    var swSettlementSubmitting = false;
    var swSettlementPreviewConfirmed = false;

    function swPreviewEsc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function swPreviewMoney(value) {
        var n = parseFloat(value);
        return (isFinite(n) ? n : 0).toFixed(2);
    }

    function swPreviewQty(value) {
        var n = parseFloat(value);
        return (isFinite(n) ? n : 0).toFixed(3);
    }

    function swPreviewSection(title, headers, bodyHtml, total) {
        var head = '<tr>' + headers.map(function (header) {
            return '<th' + (header.num ? ' class="sw-preview-num"' : '') + '>' + swPreviewEsc(header.label) + '</th>';
        }).join('') + '<th class="sw-preview-actions">Action</th></tr>';

        var footer = total == null ? ''
            : '<tfoot><tr><th colspan="' + Math.max(1, headers.length - 1) + '" class="text-right">Total</th>'
              + '<th class="sw-preview-num">' + swPreviewMoney(total) + '</th><th></th></tr></tfoot>';

        if (!bodyHtml) {
            bodyHtml = '<tr><td colspan="' + (headers.length + 1) + '" class="sw-preview-empty">No records</td></tr>';
        }

        return '<div class="sw-preview-card"><h4>' + swPreviewEsc(title) + '</h4>'
            + '<div class="table-responsive"><table class="table table-bordered table-condensed">'
            + '<thead>' + head + '</thead><tbody>' + bodyHtml + '</tbody>' + footer + '</table></div></div>';
    }

    function swPreviewAction(section, key, paymentType, locked) {
        var edit = '<button type="button" class="btn btn-default btn-xs sw-preview-edit" data-section="' + swPreviewEsc(section) + '"'
            + (paymentType ? ' data-payment-type="' + swPreviewEsc(paymentType) + '"' : '')
            + '><i class="fa fa-pencil"></i> Edit</button>';

        if (locked) {
            return edit + ' <span class="label label-default"><i class="fa fa-lock"></i></span>';
        }

        return edit + ' <button type="button" class="btn btn-danger btn-xs sw-preview-delete" data-section="'
            + swPreviewEsc(section) + '" data-key="' + parseInt(key || 0, 10) + '"><i class="fa fa-trash"></i> Delete</button>';
    }

    function swRenderSettlementPreview() {
        var meter = typeof window.swSettlementDraftGetMeterLines === 'function' ? window.swSettlementDraftGetMeterLines() : [];
        var otherSales = typeof window.swSettlementDraftGetOtherSales === 'function' ? window.swSettlementDraftGetOtherSales() : [];
        var otherIncome = typeof window.swSettlementDraftGetOtherIncome === 'function' ? window.swSettlementDraftGetOtherIncome() : [];
        var creditSales = typeof window.swSettlementDraftGetCreditSales === 'function' ? window.swSettlementDraftGetCreditSales() : [];
        var payments = typeof window.swSettlementPreviewGetPayments === 'function' ? window.swSettlementPreviewGetPayments() : [];

        var shifts = ($('#sw_st_shifts option:selected').map(function () { return $(this).text(); }).get() || []).join(', ');
        var meta = [
            ['Settlement No', $('#sw_settlement_no').val() || '—'],
            ['Date', $('#sw_st_date').val() || '—'],
            ['Location', $('#sw_st_location option:selected').text() || '—'],
            ['Pump Operator', $('#sw_st_operator option:selected').text() || '—'],
            ['SW Shift', shifts || '—']
        ].map(function (item) {
            return '<div><span>' + swPreviewEsc(item[0]) + '</span><strong>' + swPreviewEsc(item[1]) + '</strong></div>';
        }).join('');
        $('#sw_preview_meta').html(meta);

        var html = '';
        var total = 0;
        var rows = '';
        $.each(meter, function (_, r) {
            total += parseFloat(r.amount) || 0;
            rows += '<tr><td>' + swPreviewEsc(r.pump_no || r.pump_id) + '</td><td>' + swPreviewEsc(r.product_name || r.product_code || '') + '</td>'
                + '<td class="sw-preview-num">' + swPreviewQty(r.opening_meter) + '</td><td class="sw-preview-num">' + swPreviewQty(r.closing_meter) + '</td>'
                + '<td class="sw-preview-num">' + swPreviewQty(r.sold_qty) + '</td><td class="sw-preview-num">' + swPreviewQty(r.testing_qty) + '</td>'
                + '<td class="sw-preview-num">' + swPreviewQty(r.quantity) + '</td><td class="sw-preview-num">' + swPreviewMoney(r.rate) + '</td>'
                + '<td class="sw-preview-num">' + swPreviewMoney(r.amount) + '</td><td class="sw-preview-actions">' + swPreviewAction('meter', r.key) + '</td></tr>';
        });
        html += swPreviewSection('Meter Sales', [
            {label:'Pump No'},{label:'Product'},{label:'Starting Meter',num:true},{label:'Closing Meter',num:true},{label:'Sold Qty',num:true},
            {label:'Testing Qty',num:true},{label:'Net Qty',num:true},{label:'Unit Price',num:true},{label:'Amount',num:true}
        ], rows, total);

        total = 0; rows = '';
        $.each(otherSales, function (_, r) {
            total += parseFloat(r.amount) || 0;
            rows += '<tr><td>' + swPreviewEsc(r.name || r.sku || '') + '</td><td>' + swPreviewEsc(r.store_name || '') + '</td>'
                + '<td class="sw-preview-num">' + swPreviewQty(r.quantity) + '</td><td class="sw-preview-num">' + swPreviewMoney(r.rate) + '</td>'
                + '<td class="sw-preview-num">' + swPreviewMoney(r.amount_before_discount) + '</td><td class="sw-preview-num">' + swPreviewMoney(r.amount) + '</td>'
                + '<td class="sw-preview-actions">' + swPreviewAction('other_sales', r.key) + '</td></tr>';
        });
        html += swPreviewSection('Other Sales', [
            {label:'Product'},{label:'Store'},{label:'Qty',num:true},{label:'Unit Price',num:true},{label:'Before Discount',num:true},{label:'Amount',num:true}
        ], rows, total);

        total = 0; rows = '';
        $.each(otherIncome, function (_, r) {
            total += parseFloat(r.amount) || 0;
            rows += '<tr><td>' + swPreviewEsc(r.name || r.sku || '') + '</td><td>' + swPreviewEsc(r.details || '') + '</td>'
                + '<td class="sw-preview-num">' + swPreviewQty(r.quantity) + '</td><td class="sw-preview-num">' + swPreviewMoney(r.rate) + '</td>'
                + '<td class="sw-preview-num">' + swPreviewMoney(r.amount) + '</td><td class="sw-preview-actions">' + swPreviewAction('other_income', r.key) + '</td></tr>';
        });
        html += swPreviewSection('Other Income', [
            {label:'Service'},{label:'Details'},{label:'Qty',num:true},{label:'Rate',num:true},{label:'Amount',num:true}
        ], rows, total);

        total = 0; rows = '';
        $.each(creditSales, function (_, r) {
            total += parseFloat(r.amount) || 0;
            rows += '<tr><td>' + swPreviewEsc(r.customer_name || '') + '</td><td>' + swPreviewEsc(r.order_no || '') + '</td>'
                + '<td>' + swPreviewEsc(r.product_name || '') + '</td><td class="sw-preview-num">' + swPreviewQty(r.quantity) + '</td>'
                + '<td class="sw-preview-num">' + swPreviewMoney(r.unit_price) + '</td><td class="sw-preview-num">' + swPreviewMoney(r.amount) + '</td>'
                + '<td class="sw-preview-actions">' + swPreviewAction('credit_sales', r.key) + '</td></tr>';
        });
        html += swPreviewSection('Credit Sales', [
            {label:'Customer'},{label:'Order No'},{label:'Product'},{label:'Qty',num:true},{label:'Unit Price',num:true},{label:'Amount',num:true}
        ], rows, total);

        total = 0; rows = '';
        $.each(payments, function (_, r) {
            total += parseFloat(r.amount) || 0;
            var locked = String(r.source || '') === 'daily';
            rows += '<tr><td>' + swPreviewEsc(r.label || r.payment_method || '') + '</td><td>' + swPreviewEsc(r.customer_name || '') + '</td>'
                + '<td>' + swPreviewEsc(r.account_name || '') + '</td><td>' + swPreviewEsc(r.expense_category_name || '') + '</td>'
                + '<td class="sw-preview-num">' + swPreviewMoney(r.amount) + '</td><td>' + swPreviewEsc(r.note || '') + '</td>'
                + '<td class="sw-preview-actions">' + swPreviewAction('payments', r.key, r.payment_method, locked) + '</td></tr>';
        });
        html += swPreviewSection('Payment Details', [
            {label:'Payment Type'},{label:'Customer'},{label:'Account / Bank'},{label:'Expense Category'},{label:'Amount',num:true},{label:'Note'}
        ], rows, total);

        $('#sw_preview_sections').html(html);
    }

    function swOpenSettlementPreview() {
        $(document).trigger('sw:settlement-before-submit');
        if (!swCanSubmitSettlement(true)) {
            return false;
        }
        swSettlementPreviewConfirmed = false;
        swRenderSettlementPreview();
        $('#sw_settlement_preview_modal').modal('show');
        return true;
    }

    $(document).on('click', '.sw-preview-edit', function () {
        var section = String($(this).data('section') || '');
        var paymentType = String($(this).data('payment-type') || '');
        var targets = {
            meter: '#sw_sec_meter_sales',
            other_sales: '#sw_sec_other_sales',
            other_income: '#sw_sec_other_income',
            credit_sales: '#sw_sec_credit_sales',
            payments: '#sw_sec_payments'
        };
        var target = targets[section] || '#sw_st_sections';
        $('#sw_settlement_preview_modal').modal('hide');
        if (paymentType) {
            $('.sw-pay-btn[data-type="' + paymentType.replace(/"/g, '\\"') + '"]').trigger('click');
        }
        $(target).find('.collapse').first().collapse('show');
        setTimeout(function () {
            var $target = $(target);
            if ($target.length) { $('html,body').animate({scrollTop: Math.max(0, $target.offset().top - 80)}, 180); }
        }, 220);
    });

    $(document).on('click', '.sw-preview-delete', function () {
        if (!confirm('Are you sure you want to delete this record?')) { return; }
        var section = String($(this).data('section') || '');
        var key = parseInt($(this).data('key'), 10) || 0;
        var configs = {
            meter: ['swSettlementDraftGetMeterLines','swSettlementDraftSetMeterLines'],
            other_sales: ['swSettlementDraftGetOtherSales','swSettlementDraftSetOtherSales'],
            other_income: ['swSettlementDraftGetOtherIncome','swSettlementDraftSetOtherIncome'],
            credit_sales: ['swSettlementDraftGetCreditSales','swSettlementDraftSetCreditSales']
        };

        if (section === 'payments') {
            if (typeof window.swSettlementPreviewRemovePayment === 'function') {
                window.swSettlementPreviewRemovePayment(key);
            }
        } else if (configs[section]) {
            var getter = window[configs[section][0]];
            var setter = window[configs[section][1]];
            if (typeof getter === 'function' && typeof setter === 'function') {
                setter(getter().filter(function (row) { return parseInt(row.key, 10) !== key; }));
            }
        }
        swRenderSettlementPreview();
    });

    $('#sw_confirm_settlement_preview').on('click', function () {
        var form = document.getElementById('sw_settlement_form');
        if (!form || swSettlementSubmitting) { return false; }

        $(document).trigger('sw:settlement-before-submit');
        if (!swCanSubmitSettlement(true)) {
            $('#sw_settlement_preview_modal').modal('hide');
            return false;
        }

        swSettlementPreviewConfirmed = true;
        swSettlementSubmitting = true;
        $(this).prop('disabled', true).text('Saving...');
        $('#sw_st_save_btn').prop('disabled', true).addClass('disabled')
            .find('.sw-save-label').text(@json(__('sw::lang.saving_settlement')));
        $('#sw_st_cancel_btn').addClass('disabled').attr('aria-disabled', 'true');

        try {
            HTMLFormElement.prototype.submit.call(form);
        } catch (err) {
            swSettlementSubmitting = false;
            swSettlementPreviewConfirmed = false;
            $(this).prop('disabled', false).text('Confirm all the entered details are correct');
            $('#sw_st_save_btn').prop('disabled', false).removeClass('disabled')
                .find('.sw-save-label').text(@json(__('sw::lang.save_settlement')));
            toastr.error(@json(__('sw::lang.unable_to_save_settlement')));
        }
        return false;
    });

    $('#sw_st_save_btn').on('click.swSettlementSave', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (swSettlementSubmitting) { return false; }
        return swOpenSettlementPreview();
    });

    // Keyboard/other submit paths retain the same validation.
    $('#sw_settlement_form').on('submit.swSettlementValidation', function (e) {
        if (swSettlementSubmitting && swSettlementPreviewConfirmed) {
            return true;
        }

        e.preventDefault();
        if (!swSettlementSubmitting) {
            swOpenSettlementPreview();
        }
        return false;
    });

    // Operator changes only need to refresh the eligible CLOSED shifts.
    $('#sw_st_operator').on('change', swLoadShifts);

    // Location changes rebuild the operator list first, then the shift list.
    // This prevents a hidden/stale operator from another location remaining in
    // the Select2 control.
    $('#sw_st_location').on('change', function () {
        swRefreshSettlementOperators();
        swLoadShifts();
    });

    // If this operator has a pump assigned in the shared Pump Operator record,
    // refresh Meter Sales immediately so the related pump is offered.
    $('#sw_st_operator').on('change', function () {
        if (typeof window.swLoadPumps === 'function') {
            window.swLoadPumps($('#sw_st_location').val());
        }
    });

    // The pump list follows the location.
    $('#sw_st_location').on('change', function () {
        if (typeof window.swLoadPumps === 'function') {
            window.swLoadPumps($(this).val());
        }
        if (typeof window.swLoadStores === 'function') {
            window.swLoadStores($(this).val());
        }
    });

    $('#sw_st_shifts').on('change', function () {
        var ids = $(this).val() || [];
        var loadSeq = ++swSettlementLoadSeq;

        // Meter rows are valid only for the exact selected SW Shift set. Drop
        // stale rows before any new shift data is assembled so Preview/Save can
        // never combine meter data from two different shift selections.
        if (typeof window.swSettlementMeterShiftContextChanged === 'function') {
            window.swSettlementMeterShiftContextChanged(ids);
        }

        window.swSettlementDataReady = false;
        window.swUpdateSettlementSaveState();

        if (!ids.length) {
            $('#sw_st_sections').hide();
            $('#sw_st_body').html('');
            return;
        }

        $('#sw_st_sections').show();
        $('#sw_st_save_btn').prop('disabled', true);

        // Meter Sales pump choices are shift/operator scoped. Refresh them every
        // time the selected SW Shift set changes so a pump from a previous shift
        // cannot remain available in the settlement.
        if (typeof window.swLoadPumps === 'function') {
            window.swLoadPumps($('#sw_st_location').val());
        }

        var operatorId = $('#sw_st_operator').val();

        var assembleRequest = $.get(
            '{{ route('sw.settlements.assemble') }}',
            { shift_ids: ids }
        ).done(function (html) {
            if (loadSeq !== swSettlementLoadSeq) { return; }
            $('#sw_st_body').html(html);
        });

        var creditRequest = typeof window.swLoadDailyCreditSales === 'function'
            ? window.swLoadDailyCreditSales(ids, operatorId)
            : $.Deferred().resolve().promise();

        var paymentRequest = typeof window.swLoadPaymentSummary === 'function'
            ? window.swLoadPaymentSummary(ids, operatorId)
            : $.Deferred().resolve().promise();

        /*
         | Mark the settlement ready only when the complete selected-shift load
         | succeeds.  An older/aborted request cannot unlock Save because the
         | sequence number must still match the current selection.
         */
        $.when(assembleRequest, creditRequest, paymentRequest).done(function () {
            if (loadSeq !== swSettlementLoadSeq) { return; }

            window.swSettlementDataReady = true;

            if (typeof window.swRecalcPayments === 'function') {
                window.swRecalcPayments();
            } else {
                window.swUpdateSettlementSaveState();
            }

            $(document).trigger('sw:settlement-shift-loaded');
        }).fail(function () {
            if (loadSeq !== swSettlementLoadSeq) { return; }
            window.swSettlementDataReady = false;
            window.swUpdateSettlementSaveState();
        });
    });

    // One location, one operator: choose them rather than making the user.
    if ($('#sw_st_location option').length === 2 && !$('#sw_st_location').val()) {
        $('#sw_st_location').val($('#sw_st_location option').eq(1).val()).trigger('change');
    }

    /*
     | A single location is already selected server-side, so no browser change
     | event fires on page load. Load its dependent lookups explicitly or the
     | Pump No dropdown stays empty until the user changes away and back.
    */
    var initialLocationId = $('#sw_st_location').val();
    if (initialLocationId) {
        // Rebuild the Select2 from the location-scoped pending CLOSED shifts.
        // If there is a draft, preserve its operator and shift selection.
        swRefreshSettlementOperators(swDraft ? swDraft.pump_operator_id : null);
        swLoadShifts(swDraft ? (swDraft.shift_ids || []) : []);

        if (typeof window.swLoadPumps === 'function') {
            window.swLoadPumps(initialLocationId);
        }
        if (typeof window.swLoadStores === 'function') {
            window.swLoadStores(initialLocationId);
        }
    } else {
        // No dependent shift load will fire, so draft mode must not suppress
        // future user changes indefinitely.
        swDraftRestoring = false;
    }

    if (!swDraft) {
        swDraftRestoring = false;
    }

});
</script>

@endsection
