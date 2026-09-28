
<style id="s429-meter-sale-compact-row">
/* S429: remove empty gaps and keep Meter Sales entry controls in one compact row. */
.meter-sale-entry-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 8px;
    padding-left: 0 !important;
    padding-right: 0 !important;
}
.meter-sale-entry-row > [class*="col-md-"] {
    float: none !important;
    padding-left: 4px !important;
    padding-right: 4px !important;
    width: auto !important;
}
.meter-sale-entry-row > .col-md-2 { flex: 1 1 145px; max-width: 210px; }
.meter-sale-entry-row > .pump_starting_meter_div,
.meter-sale-entry-row > .pump_closing_meter_div { flex-basis: 175px; }
.meter-sale-entry-row > .col-md-1 { flex: 0 0 auto; }
.meter-sale-entry-row .form-group { margin-bottom: 6px; }
.meter-sale-entry-row .btn_meter_sale,
.meter-sale-entry-row .btn_meter_sale_cancel,
.meter-sale-entry-row .btn_update_meter_sale { margin-top: 0 !important; margin-bottom: 6px; }
.meter-sale-entry-row #pump_starting_meter[readonly] {
    background: #e9ecef !important;
    color: #495057 !important;
    cursor: not-allowed !important;
}
@media (max-width: 991px) {
    .meter-sale-entry-row > .col-md-2,
    .meter-sale-entry-row > .pump_starting_meter_div,
    .meter-sale-entry-row > .pump_closing_meter_div { flex: 1 1 190px; max-width: none; }
}
</style>

@php

$pump_no = null;
$pump_starting_meter = null;
$pump_closing_meter = null;
$sold_qty = null;
$meter_sale_unit_price = null;
$testing_qty = "0.00";
$meter_sale_discount_type = null;
$meter_sale_discount = "0.00";
$meter_sale_id = null;
$meter_sale_product_id = null;
$show_mechanical_meter_too = $show_mechanical_meter_too ?? true;
$mechanical_last_meter = null;
$mechanical_digital_last_meter = null;
$mechanical_meter_difference = null;
$mechanical_meter_saved = 0;


if(!empty($meter_sale)){


    $pump_no = $meter_sale['pump_id'];
    $pump_starting_meter = number_format((float) $meter_sale['starting_meter'], 3, '.', '');
    $pump_closing_meter = number_format((float) $meter_sale['closing_meter'], 3, '.', '');
    $sold_qty = $meter_sale['qty'];
    $meter_sale_unit_price = $meter_sale['price'];
    $testing_qty = $meter_sale['testing_qty'];
    $meter_sale_discount_type = $meter_sale['discount_type'];
    $meter_sale_discount = $meter_sale['discount'];
    $meter_sale_id = $meter_sale['id'];
    $meter_sale_product_id = $meter_sale['product_id'] ?? null;
    $mechanical_last_meter = isset($meter_sale['mechanical_last_meter']) ? number_format((float) $meter_sale['mechanical_last_meter'], 3, '.', '') : null;
    $mechanical_digital_last_meter = isset($meter_sale['mechanical_digital_last_meter']) ? number_format((float) $meter_sale['mechanical_digital_last_meter'], 3, '.', '') : null;
    $mechanical_meter_difference = isset($meter_sale['mechanical_meter_difference']) ? number_format((float) $meter_sale['mechanical_meter_difference'], 3, '.', '') : null;
    $mechanical_meter_saved = !empty($mechanical_last_meter) ? 1 : 0;
}
@endphp
<div class="col-md-12 meter-sale-entry-row">
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('pump_no', __('petrodirect::lang.pump_no').':') !!}
				{!! Form::select('pump_no', $pump_nos, $pump_no, ['id' => 'pump_no', 'class' => 'form-control meter_sale_fields check_pumper
				select2',
				'placeholder' => __('petrodirect::lang.please_select')]); !!}
			</div>
			<input type="hidden" id="meter_sale_selected_pump_id" value="{{ $pump_no }}">
			<input type="hidden" id="meter_sale_selected_pump_text" value="{{ !empty($pump_no) && isset($pump_nos[$pump_no]) ? $pump_nos[$pump_no] : '' }}">
			<input type="hidden" id="meter_sale_selected_pump_context" value="">
			<input type="hidden" id="meter_sale_product_id" value="{{ $meter_sale_product_id }}">
		</div>
		<div class="col-md-2 pump_starting_meter_div">
			<div class="form-group">
				{!! Form::label('pump_starting_meter', __( 'petrodirect::lang.pump_starting_meter' ) ) !!}
				{!! Form::text('pump_starting_meter', $pump_starting_meter, ['class' => 'form-control meter_sale_fields check_pumper
				input_number
				pump_starting_meter', 'required', 'readonly',
				'placeholder' => __(
				'petrodirect::lang.pump_starting_meter' ) ]); !!}
			</div>
		</div>
		<div class="col-md-2 pump_closing_meter_div">
			<div class="form-group">
				@if(!empty($show_mechanical_meter_too))
					<div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 5px;">
						{!! Form::label('pump_closing_meter', __( 'petrodirect::lang.pump_closing_meter' ), ['style' => 'margin-bottom: 0;']) !!}
						<button type="button" class="btn btn-info btn-xs" id="mechanical_meter_btn" style="white-space: nowrap; padding: 2px 6px;">
							Mechanical Meter
						</button>
					</div>
				@else
					{!! Form::label('pump_closing_meter', __( 'petrodirect::lang.pump_closing_meter' ) ) !!}
				@endif
				{!! Form::text('pump_closing_meter', $pump_closing_meter, ['class' => 'form-control meter_sale_fields check_pumper
				input_number
				pump_closing_meter',
				'required',
				'step' => '0.001',
				'min' => '0',
				'placeholder' => __(
				'petrodirect::lang.pump_closing_meter' ) ]); !!}
				<input type="hidden" class="meter_sale_fields" id="mechanical_last_meter" value="{{ $mechanical_last_meter }}">
				<input type="hidden" class="meter_sale_fields" id="mechanical_digital_last_meter" value="{{ $mechanical_digital_last_meter }}">
				<input type="hidden" class="meter_sale_fields" id="mechanical_meter_difference" value="{{ $mechanical_meter_difference }}">
				<input type="hidden" class="meter_sale_fields" id="mechanical_meter_saved" value="{{ $mechanical_meter_saved }}">
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('sold_qty', __( 'petrodirect::lang.sold_qty' ) ) !!}
				{!! Form::text('sold_qty', $sold_qty, ['id' => 'sold_qty', 'class' => 'form-control meter_sale_fields check_pumper sold_qty
				input_number',
				'required', 'disabled',
				'placeholder' => __(
				'petrodirect::lang.sold_qty' ) ]); !!}
				<input type="hidden" class="meter_sale_fields is_from_pumper" id="is_from_pumper" value="0">
				
				<input type="hidden" class="meter_sale_fields assignment_id" id="assignment_id" value="0">
				<input type="hidden" class="meter_sale_fields pumper_entry_id" id="pumper_entry_id" value="0">
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('unit_price', __( 'petrodirect::lang.unit_price' ) ) !!}
				{!! Form::text('meter_sale_unit_price', $meter_sale_unit_price, ['id' => 'meter_sale_unit_price', 'class' => 'form-control
				meter_sale_fields check_pumper unit_price input_number',
				'readonly',
				'placeholder' => __(
				'petrodirect::lang.unit_price' ) ]); !!}
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('testing_qty', __( 'petrodirect::lang.testing_qty' ) ) !!}
				{!! Form::text('testing_qty', $testing_qty, ['id' => 'testing_qty', 'class' => 'form-control check_pumper input_number
				testing_qty', 'required',
				'placeholder' => __(
				'petrodirect::lang.testing_qty' ) ]); !!}
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('meter_sale_discount_type', __( 'petrodirect::lang.discount_type' ) ) !!}
				{!! Form::select('meter_sale_discount_type', $discount_types, $meter_sale_discount_type, ['id' => 'meter_sale_discount_type', 'class' => 'form-control meter_sale_fields check_pumper
				input_number
				meter_sale_discount_type', 'required',
				'placeholder' => __(
				'petrodirect::lang.please_select' ) ]); !!}
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('meter_sale_discount', __( 'petrodirect::lang.discount' ) ) !!}
				{!! Form::text('meter_sale_discount', $meter_sale_discount, ['id' => 'meter_sale_discount', 'class' => 'form-control meter_sale_fields check_pumper
				input_number
				meter_sale_discount', 'required',
				'placeholder' => __(
				'petrodirect::lang.discount' ) ]); !!}
			</div>
		</div>
		{!! Form::hidden('bulk_sale_meter', 0, ['id' => 'bulk_sale_meter']) !!}
        @if(!$meter_sale_id)
		<div class="col-md-1 pull-right">
			<button type="button" class="btn btn-primary btn_meter_sale"
					style="margin-top: 23px;">@lang('messages.add')</button>
		</div>
        @else
		<input type="hidden" name="is_edit" value="1" id="is_edit">
		<div class="col-md-2 pull-right">
			<button type="button" class="btn btn-danger btn_meter_sale_cancel"  data-href="/petrodirect/settlement/get-meter-sale-form/{{$meter_sale_id}}"
				style="margin-top: 23px;">@lang('messages.cancel')</button>
			<button type="button" class="btn btn-primary btn_update_meter_sale"   data-href="/petrodirect/settlement/update-settlement-meter-sale/{{$meter_sale_id}}"
				style="margin-top: 23px;">@lang('messages.update')</button>
		</div>
        @endif
	</div>
{{--
    MA-002 (IS-1944 #2): the Add button waits for the Mechanical Meter.

    When "Show Mechanical Meter" is OFF in Petro General settings, nothing here
    runs and a meter sale can be added exactly as before.

    When it is ON, the mechanical meter becomes mandatory: the Add button is
    disabled until a value has been entered and saved through the Mechanical
    Meter dialog, which sets #mechanical_meter_saved.

    Disabled rather than hidden, with a tooltip explaining why - a button that
    vanishes leaves people wondering what they did wrong.
--}}
@if(!empty($show_mechanical_meter_too))
<script>
    (function () {
        /*
         * IS1981: the gate has to survive being overwritten.
         *
         * The previous version disabled the Add button once, on load. That
         * worked - the button carried the tooltip - but something on the page
         * re-enabled it immediately afterwards, so by the time anyone looked it
         * was clickable again with the tooltip still attached. Diagnosed live:
         *
         *     $('#mechanical_meter_saved').val()      -> '0'   (not entered)
         *     $('.btn_meter_sale').attr('title')      -> 'Enter the Mechanical Meter first.'
         *     $('.btn_meter_sale').prop('disabled')   -> false (re-enabled)
         *
         * Setting a property once is not enough on a page where several other
         * handlers reset button state after every field change and AJAX render.
         * So this now works three ways:
         *
         *   1. re-applies after ANY ajax call and any meter-sale field change
         *   2. watches the button itself, so a direct re-enable is undone
         *   3. blocks the click in the capture phase, which holds even if
         *      something manages to enable the button between checks
         *
         * Point 3 is the guarantee. The others keep the button LOOKING right;
         * that one makes sure a meter sale cannot be added without the
         * mechanical meter, whatever else the page does.
         *
         * When "Show Mechanical Meter" is OFF none of this renders, and a meter
         * sale is added exactly as before.
         */
        function mechanicalMeterReady() {
            var saved = $('#mechanical_meter_saved').val();

            return saved !== undefined && saved !== null
                && String(saved).trim() !== '' && String(saved).trim() !== '0';
        }

        function syncMeterSaleAddButton() {
            var ready = mechanicalMeterReady();
            var $btn = $('.btn_meter_sale');

            if (!$btn.length) {
                return;
            }

            if ($btn.prop('disabled') === !ready) {
                // Already in the right state - do not touch it, so the observer
                // below is not woken for nothing.
                return;
            }

            $btn.prop('disabled', !ready)
                .attr('title', ready ? '' : 'Enter the Mechanical Meter first.');
        }

        // 1. Anything that changes the form, or any AJAX render, re-applies it.
        $(document)
            .off('change.pdMechMeter input.pdMechMeter', '#mechanical_meter_saved')
            .on('change.pdMechMeter input.pdMechMeter', '#mechanical_meter_saved', syncMeterSaleAddButton);

        $(document)
            .off('change.pdMechMeterFields input.pdMechMeterFields', '.meter_sale_fields')
            .on('change.pdMechMeterFields input.pdMechMeterFields', '.meter_sale_fields', syncMeterSaleAddButton);

        $(document)
            .off('hidden.bs.modal.pdMechMeter')
            .on('hidden.bs.modal.pdMechMeter', syncMeterSaleAddButton);

        $(document)
            .off('ajaxComplete.pdMechMeter')
            .on('ajaxComplete.pdMechMeter', function () {
                window.setTimeout(syncMeterSaleAddButton, 50);
            });

        // 2. If something re-enables the button directly, put it back.
        if (window.MutationObserver) {
            var btnNode = $('.btn_meter_sale').get(0);

            if (btnNode) {
                if (window.__pdMechMeterObserver) {
                    window.__pdMechMeterObserver.disconnect();
                }

                window.__pdMechMeterObserver = new MutationObserver(function () {
                    syncMeterSaleAddButton();
                });

                window.__pdMechMeterObserver.observe(btnNode, {
                    attributes: true,
                    attributeFilter: ['disabled', 'class']
                });
            }
        }

        /*
         * 3. The guarantee. Capture phase, so this runs before the page's own
         * click handler regardless of binding order.
         */
        if (!window.__pdMechMeterClickGuard) {
            window.__pdMechMeterClickGuard = true;

            document.addEventListener('click', function (e) {
                var btn = e.target && e.target.closest ? e.target.closest('.btn_meter_sale') : null;

                if (!btn || mechanicalMeterReady()) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                if (window.toastr) {
                    toastr.error('Please enter the Mechanical Meter first.');
                }

                syncMeterSaleAddButton();
            }, true);
        }

        $(syncMeterSaleAddButton);
        window.setTimeout(syncMeterSaleAddButton, 300);
    }());
</script>
@endif

