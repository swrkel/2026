{!! Form::open([
    'url' => action('\\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorPaymentController@saveMeterSale'),
    'method' => 'post',
    'class' => 'enter-meters-form',
    'autocomplete' => 'off',
]) !!}

<input type="hidden" name="shift_id" value="{{ $shift_id ?? '' }}">

<div class="enter-meters-card">
    <div class="enter-meters-table-wrap">
        <table class="table" id="other_sale_table">
            <thead>
                <tr>
                    <th>@lang('pumperdashboard::lang.pump_no')</th>
                    <th>@lang('pumperdashboard::lang.received_meter')</th>
                    <th>@lang('pumperdashboard::lang.last_entered_meter')</th>
                    <th>@lang('pumperdashboard::lang.new_meter')</th>
                    <th>@lang('pumperdashboard::lang.sold_qty')</th>
                    <th>@lang('pumperdashboard::lang.unit_price')</th>
                    <th>@lang('pumperdashboard::lang.amount')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pending_pumps as $pump)
                    @php
                        $qty_baseline = (float) ($pump->last_entered_meter ?? $pump->starting_meter ?? 0);
                    @endphp
                    <tr>
                        <td>
                            {{ $pump->pump_no }}
                            <input type="hidden" name="pump_no[]" class="form-control other_sale_pump_no"
                                value="{{ $pump->pump_no }}">
                            <input type="hidden" name="assignment_id[]" class="form-control other_sale_assigment_id"
                                value="{{ $pump->id }}">
                            <input type="hidden" class="other_sale_qty_baseline"
                                value="{{ number_format($qty_baseline, 3, '.', '') }}">
                        </td>
                        <td>
                            {{ number_format($pump->starting_meter, 3, '.', '') }}
                            <input type="hidden" name="starting_meter[]" class="form-control other_sale_starting_meter"
                                required value="{{ number_format($pump->starting_meter, 3, '.', '') }}">
                        </td>
                        <td>
                            {{ number_format($pump->last_entered_meter ?? $pump->starting_meter, 3, '.', '') }}
                        </td>
                        <td>
                            <input type="number" step="0.001" inputmode="decimal" name="new_meter[]"
                                class="form-control other_sale_input other_sale_new_meter"
                                aria-label="@lang('pumperdashboard::lang.new_meter') - {{ $pump->pump_no }}"
                                oninput="validateMeterInput(this, {{ json_encode($qty_baseline) }})"
                                onchange="validateMeterInputOnChange(this, {{ json_encode($qty_baseline) }})">
                        </td>
                        <td>
                            <span class="other_sale_span_sold_qty">0.000</span>
                        </td>
                        <td>
                            <span class="other_sale_span_unit_price">{{ @num_format($pump->sell_price_inc_tax) }}</span>
                        </td>
                        <td>
                            <span class="other_sale_span_amount">0.00</span>
                            <input type="hidden" name="sold_qty[]" class="form-control other_sale_sold_qty">
                            <input type="hidden" name="unit_price[]" class="form-control other_sale_unit_price"
                                value="{{ $pump->sell_price_inc_tax }}">
                            <input type="hidden" name="sale_amount[]" class="form-control other_sale_amount">
                        </td>
                    </tr>
                @empty
                    <tr class="enter-meters-empty-row">
                        <td colspan="7">
                            No active open-shift pump assignments found for this operator.
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="7" class="enter-meters-summary-cell">
                        <div class="enter-meters-summary-row">
                            <span class="enter-meters-summary-label">@lang('pumperdashboard::lang.total_amount')</span>
                            <span class="enter-meters-summary-value other_sale_grand_total_amount">{{ @num_format($total_amount) }}</span>
                        </div>
                        <input type="hidden" name="grand_total" class="other_sale_grand_total_amount_input"
                            value="{{ $total_amount }}">
                    </td>
                </tr>

                <tr>
                    <td colspan="7" class="enter-meters-summary-cell">
                        <div class="enter-meters-summary-row">
                            <span class="enter-meters-summary-label">@lang('pumperdashboard::lang.today_deposited')</span>
                            <span class="enter-meters-summary-value other_sale_grand_today_deposited">{{ @num_format($today_deposited) }}</span>
                        </div>
                        <input type="hidden" name="today_deposited" class="other_sale_grand_today_deposited_input"
                            value="{{ $today_deposited }}">
                    </td>
                </tr>

                <tr>
                    <td colspan="7" class="enter-meters-summary-cell">
                        <div class="enter-meters-summary-row">
                            <span class="enter-meters-summary-label">@lang('pumperdashboard::lang.balance_to_deposit')</span>
                            <span class="enter-meters-summary-value other_sale_grand_balance_to_deposit">{{ @num_format($balance_to_deposit ?? 0) }}</span>
                        </div>
                        <input type="hidden" name="balance_to_deposit"
                            class="other_sale_grand_balance_to_deposit_input" value="{{ $balance_to_deposit ?? 0 }}">
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="enter-meters-actions">
        <button type="submit" class="btn btn-danger other_sale_finalize enter-meters-finalize"
            @if($pending_pumps->isEmpty()) disabled @endif>
            @lang('pumperdashboard::lang.finalize')
        </button>
    </div>
</div>

{!! Form::close() !!}

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
            input.classList.remove('valid-input', 'invalid-input');
            input.focus();

            if (typeof calculate_other_sales_totals === 'function') {
                calculate_other_sales_totals();
            }
        }
    }
</script>
