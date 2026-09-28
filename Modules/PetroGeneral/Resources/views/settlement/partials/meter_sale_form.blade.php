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
$show_mechanical_meter_too = $show_mechanical_meter_too ?? true;
$mechanical_last_meter = null;
$mechanical_digital_last_meter = null;
$mechanical_meter_difference = null;
$mechanical_meter_saved = 0;


if(!empty($meter_sale)){


    $pump_no = $meter_sale['pump_id'];
    $pump_starting_meter = number_format($meter_sale['starting_meter'], 3);
    $pump_closing_meter = number_format($meter_sale['closing_meter'], 3);
    $sold_qty = $meter_sale['qty'];
    $meter_sale_unit_price = $meter_sale['price'];
    $testing_qty = $meter_sale['testing_qty'];
    $meter_sale_discount_type = $meter_sale['discount_type'];
    $meter_sale_discount = $meter_sale['discount'];
    $meter_sale_id = $meter_sale['id'];
    $mechanical_last_meter = isset($meter_sale['mechanical_last_meter']) ? number_format((float) $meter_sale['mechanical_last_meter'], 3, '.', '') : null;
    $mechanical_digital_last_meter = isset($meter_sale['mechanical_digital_last_meter']) ? number_format((float) $meter_sale['mechanical_digital_last_meter'], 3, '.', '') : null;
    $mechanical_meter_difference = isset($meter_sale['mechanical_meter_difference']) ? number_format((float) $meter_sale['mechanical_meter_difference'], 3, '.', '') : null;
    $mechanical_meter_saved = !empty($mechanical_last_meter) ? 1 : 0;
}
@endphp
<div class="col-md-12">
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('pump_no', __('petrogeneral::lang.pump_no').':') !!}
				{!! Form::select('pump_no', $pump_nos, $pump_no, ['id' => 'pump_no', 'class' => 'form-control meter_sale_fields check_pumper
				select2',
				'placeholder' => __('petrogeneral::lang.please_select')]); !!}
			</div>
		</div>
		<div class="col-md-2 pump_starting_meter_div">
			<div class="form-group">
				{!! Form::label('pump_starting_meter', __( 'petrogeneral::lang.pump_starting_meter' ) ) !!}
				{!! Form::text('pump_starting_meter', $pump_starting_meter, ['class' => 'form-control meter_sale_fields check_pumper
				input_number
				pump_starting_meter', 'required', 'readonly',
				'placeholder' => __(
				'petrogeneral::lang.pump_starting_meter' ) ]); !!}
			</div>
		</div>
		<div class="col-md-2 pump_closing_meter_div">
			<div class="form-group">
				@if(!empty($show_mechanical_meter_too))
					<div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 5px;">
						{!! Form::label('pump_closing_meter', __( 'petrogeneral::lang.pump_closing_meter' ), ['style' => 'margin-bottom: 0;']) !!}
						<button type="button" class="btn btn-info btn-xs" id="mechanical_meter_btn" style="white-space: nowrap; padding: 2px 6px;">
							Mechanical Meter
						</button>
					</div>
				@else
					{!! Form::label('pump_closing_meter', __( 'petrogeneral::lang.pump_closing_meter' ) ) !!}
				@endif
				{!! Form::text('pump_closing_meter', $pump_closing_meter, ['class' => 'form-control meter_sale_fields check_pumper
				input_number
				pump_closing_meter',
				'required',
				'step' => '0.001',
				'min' => '0',
				'placeholder' => __(
				'petrogeneral::lang.pump_closing_meter' ) ]); !!}
				<input type="hidden" class="meter_sale_fields" id="mechanical_last_meter" value="{{ $mechanical_last_meter }}">
				<input type="hidden" class="meter_sale_fields" id="mechanical_digital_last_meter" value="{{ $mechanical_digital_last_meter }}">
				<input type="hidden" class="meter_sale_fields" id="mechanical_meter_difference" value="{{ $mechanical_meter_difference }}">
				<input type="hidden" class="meter_sale_fields" id="mechanical_meter_saved" value="{{ $mechanical_meter_saved }}">
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('sold_qty', __( 'petrogeneral::lang.sold_qty' ) ) !!}
				{!! Form::text('sold_qty', $sold_qty, ['id' => 'sold_qty', 'class' => 'form-control meter_sale_fields check_pumper sold_qty
				input_number',
				'required', 'disabled',
				'placeholder' => __(
				'petrogeneral::lang.sold_qty' ) ]); !!}
				<input type="hidden" class="meter_sale_fields is_from_pumper" id="is_from_pumper" value="0">
				
				<input type="hidden" class="meter_sale_fields assignment_id" id="assignment_id" value="0">
				<input type="hidden" class="meter_sale_fields pumper_entry_id" id="pumper_entry_id" value="0">
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('unit_price', __( 'petrogeneral::lang.unit_price' ) ) !!}
				{!! Form::text('meter_sale_unit_price', $meter_sale_unit_price, ['id' => 'meter_sale_unit_price', 'class' => 'form-control
				meter_sale_fields check_pumper unit_price input_number',
				'readonly',
				'placeholder' => __(
				'petrogeneral::lang.unit_price' ) ]); !!}
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('testing_qty', __( 'petrogeneral::lang.testing_qty' ) ) !!}
				{!! Form::text('testing_qty', $testing_qty, ['id' => 'testing_qty', 'class' => 'form-control check_pumper input_number
				testing_qty', 'required',
				'placeholder' => __(
				'petrogeneral::lang.testing_qty' ) ]); !!}
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('meter_sale_discount_type', __( 'petrogeneral::lang.discount_type' ) ) !!}
				{!! Form::select('meter_sale_discount_type', $discount_types, $meter_sale_discount_type, ['id' => 'meter_sale_discount_type', 'class' => 'form-control meter_sale_fields check_pumper
				input_number
				meter_sale_discount_type', 'required',
				'placeholder' => __(
				'petrogeneral::lang.please_select' ) ]); !!}
			</div>
		</div>
		<div class="col-md-2">
			<div class="form-group">
				{!! Form::label('meter_sale_discount', __( 'petrogeneral::lang.discount' ) ) !!}
				{!! Form::text('meter_sale_discount', $meter_sale_discount, ['id' => 'meter_sale_discount', 'class' => 'form-control meter_sale_fields check_pumper
				input_number
				meter_sale_discount', 'required',
				'placeholder' => __(
				'petrogeneral::lang.discount' ) ]); !!}
			</div>
		</div>
		{!! Form::hidden('bulk_sale_meter', 0, ['id' => 'bulk_sale_meter']) !!}
        @if(!$meter_sale_id)
		<div class="col-md-1 pull-right">
			<button type="button" class="btn btn-primary btn_meter_sale @if(!empty($show_mechanical_meter_too)) disabled @endif"
				@if(!empty($show_mechanical_meter_too)) disabled @endif
				style="margin-top: 23px;">@lang('messages.add')</button>
		</div>
        @else
		<input type="hidden" name="is_edit" value="1" id="is_edit">
		<div class="col-md-2 pull-right">
			<button type="button" class="btn btn-danger btn_meter_sale_cancel"  data-href="/petro-general/settlement/get-meter-sale-form/{{$meter_sale_id}}"
				style="margin-top: 23px;">@lang('messages.cancel')</button>
			<button type="button" class="btn btn-primary btn_update_meter_sale"   data-href="/petro-general/settlement/update-settlement-meter-sale/{{$meter_sale_id}}"
				style="margin-top: 23px;">@lang('messages.update')</button>
		</div>
        @endif
	</div>
