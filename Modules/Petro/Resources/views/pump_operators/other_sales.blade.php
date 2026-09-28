{!! Form::open([
    'url' => action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@saveMeterSale'),
    'method' => 'post',
]) !!}

<div class="row">
    <div class="col-md-8">
        <table class="table table-bordered table-striped" id="other_sale_table">
            <thead>
                <tr>
                    <th>@lang('petro::lang.pump_no')</th>
                    <th>@lang('petro::lang.received_meter')</th>
                    <th>@lang('petro::lang.last_entered_meter')</th>
                    <th>@lang('petro::lang.new_meter')</th>
                    <th>@lang('petro::lang.sold_qty')</th>
                    <th>@lang('petro::lang.unit_price')</th>
                    <th>@lang('petro::lang.amount')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pending_pumps as $pump)
                    @php
                        $qty_baseline = (float) ($pump->last_entered_meter ?? $pump->starting_meter ?? 0);
                    @endphp
                    <tr style="border: 2px solid #b0ade6ff;">
                        <td> {{ $pump->pump_no }}
                            <input type="hidden" name="pump_no[]" class="form-control other_sale_pump_no"
                                value="{{ $pump->pump_no }}">
                            <input type="hidden" name="assignment_id[]" class="form-control other_sale_assigment_id"
                                value="{{ $pump->id }}">
                            <input type="hidden" class="other_sale_qty_baseline"
                                value="{{ $qty_baseline }}">
                        </td>
                        <td> {{ number_format($pump->starting_meter, 3, '.', '') }}
                            <input type="hidden" name="starting_meter[]" class="form-control other_sale_starting_meter"
                                required value="{{ number_format($pump->starting_meter, 3, '.', '') }}">
                        </td>
                        <td>
                            {{ number_format($pump->last_entered_meter ?? $pump->starting_meter, 3, '.', '') }}
                        </td>
                        <td> <input type="number" step="0.001" name="new_meter[]"
                                class="form-control other_sale_input other_sale_new_meter"
                                oninput="validateMeterInput(this, {{ json_encode($qty_baseline) }})"
                                onchange="validateMeterInputOnChange(this, {{ json_encode($qty_baseline) }})"> </td>
                        <td> <span class="other_sale_span_sold_qty">0.00</span> </td>
                        <td> <span
                                class="other_sale_span_unit_price">{{ @num_format($pump->sell_price_inc_tax) }}</span>
                        </td>
                        <td>
                            <span class="other_sale_span_amount">0.00</span>
                            <input type="hidden" name="sold_qty[]" class="form-control other_sale_sold_qty">
                            <input type="hidden" name="unit_price[]" class="form-control other_sale_unit_price"
                                value="{{ $pump->sell_price_inc_tax }}">
                            <input type="hidden" name="sale_amount[]" class="form-control other_sale_amount">

                        </td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td>@lang('petro::lang.total_amount')</td>
                    <td>
                        {{-- <span class="other_sale_grand_total_amount">0.00</span> --}}
                        <span class="other_sale_grand_total_amount">{{ @num_format($total_amount) }}</span>
                        <input type="hidden" name="grand_total" class="other_sale_grand_total_amount_input"
                            value="{{ $total_amount }}">
                    </td>
                    <td colspan="4"></td>
                </tr>

                <tr>
                    <td>@lang('petro::lang.today_deposited')</td>
                    <td>
                        <span class="other_sale_grand_today_deposited">
                            {{ @num_format($today_deposited) }}
                        </span>

                        <input type="hidden" name="today_deposited" class="other_sale_grand_today_deposited_input"
                            value="{{ $today_deposited }}">
                    </td>
                    <td colspan="4"></td>
                </tr>


                <tr>
                    <td>@lang('petro::lang.balance_to_deposit')</td>
                    <td>
                        <span class="other_sale_grand_balance_to_deposit">
                            {{ @num_format($balance_to_deposit ?? 0) }}
                        </span>
                        <input type="hidden" name="balance_to_deposit"
                            class="other_sale_grand_balance_to_deposit_input" value="{{ $balance_to_deposit ?? 0 }}">
                    </td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    .valid-input {
        border-color: green !important;
    }

    .invalid-input {
        border-color: red !important;
    }
</style>
<script>
    function validateMeterInput(input, expected) {
        const input_value = parseFloat(input.value);
        const expected_value = parseFloat(expected);

        if (!isNaN(input_value)) {
            if (input_value >= expected_value) {
                input.classList.add('valid-input');
                input.classList.remove('invalid-input');
            } else {
                input.classList.add('invalid-input');
                input.classList.remove('valid-input');
            }
        } else {
            input.classList.remove('valid-input', 'invalid-input');
        }

        if (typeof calculate_other_sales_totals === 'function') {
            calculate_other_sales_totals();
        }
    }

    function validateMeterInputOnChange(input, expected) {
        const input_value = parseFloat(input.value);
        const expected_value = parseFloat(expected);

        if (!isNaN(input_value) && input_value < expected_value) {
            toastr.error("New meter value should be greater than Received Meter.");
            input.value = '';
            input.focus();
        }
    }
</script>

<div class="row">
    <div class="col-md-2 pull-right">
        <button type="submit" class="btn btn-danger pull-right other_sale_finalize"
            style="margin-top: 23px;">@lang('petro::lang.finalize')</button>
    </div>

</div>

{!! Form::close() !!}
