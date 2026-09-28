{!! Form::open([
    'url' => action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@saveMeterSale'),
    'method' => 'post',
    'id' => 'other_sale_form',
]) !!}

<div class="row">
    <div class="col-md-8">
        <table class="table table-bordered table-striped" id="other_sale_table">
            <thead>
                <tr>
                    <th>@lang('petro::lang.pump_no')</th>
                    <th>@lang('petro::lang.received_meter')</th>
                    <th>@lang('petro::lang.new_meter')</th>
                    <th>@lang('petro::lang.sold_qty')</th>
                    <th>@lang('petro::lang.unit_price')</th>
                    <th>@lang('petro::lang.amount')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pending_pumps as $pump)
                    <tr style="border: 2px solid #b0ade6ff;">
                        <td> {{ $pump->pump_no }}
                            <input type="hidden" name="pump_no[]" class="form-control other_sale_pump_no"
                                value="{{ $pump->pump_no }}">
                            <input type="hidden" name="assignment_id[]" class="form-control other_sale_assigment_id"
                                value="{{ $pump->id }}">
                        </td>
                        <td> {{ number_format($pump->starting_meter, 3, '.', '') }}
                            <input type="hidden" name="starting_meter[]" class="form-control other_sale_starting_meter"
                                required value="{{ number_format($pump->starting_meter, 3, '.', '') }}">
                        </td>
                        <td> <input type="number" step="0.001" name="new_meter[]"
                                class="form-control other_sale_input other_sale_new_meter"
                                oninput="validateMeterInput(this, {{ $pump->starting_meter }})"
                                onchange="validateMeterInputOnChange(this, {{ $pump->starting_meter }})"> </td>
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
                        <span class="other_sale_grand_total_amount">0.00</span>
                        <input type="hidden" name="grand_total" class="other_sale_grand_total_amount_input">
                    </td>
                    <td colspan="4"></td>
                </tr>

                <tr>
                    <td>@lang('petro::lang.today_deposited')</td>
                    <td>
                        <span class="other_sale_grand_today_deposited">{{ @num_format($balance_to_deposit) }}</span>
                        <input type="hidden" name="today_deposited" class="other_sale_grand_today_deposited_input"
                            value="{{ $balance_to_deposit }}">
                    </td>
                    <td colspan="4"></td>
                </tr>

                <tr>
                    <td>@lang('petro::lang.balance_to_deposit')</td>
                    <td>
                        <span class="other_sale_grand_balance_to_deposit"></span>
                        <input type="hidden" name="balance_to_deposit"
                            class="other_sale_grand_balance_to_deposit_input" value="">
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
    /* ========= GLOBAL FUNCTIONS (IMPORTANT) ========= */

    function calculateTotalAmount() {
        let totalAmount = 0;

        $('#other_sale_table tbody tr').each(function() {
            const amount = parseFloat($(this).find('.other_sale_amount').val()) || 0;
            totalAmount += amount;
        });

        $('.other_sale_grand_total_amount').text(__number_f(totalAmount));
        $('.other_sale_grand_total_amount_input').val(totalAmount.toFixed(2));
    }

    function validateMeterInput(input, expected) {
        const row = $(input).closest('tr');

        const newMeter = parseFloat(input.value);
        const startMeter = parseFloat(expected);
        const unitPrice = parseFloat(row.find('.other_sale_unit_price').val());

        if (isNaN(newMeter)) {
            row.find('.other_sale_span_sold_qty').text('0.00');
            row.find('.other_sale_span_amount').text(__number_f(0));
            row.find('.other_sale_sold_qty').val('');
            row.find('.other_sale_amount').val('');
            calculateTotalAmount();
            return;
        }

        if (newMeter < startMeter) {
            input.classList.add('invalid-input');
            input.classList.remove('valid-input');
            return;
        }

        input.classList.add('valid-input');
        input.classList.remove('invalid-input');

        const soldQty = newMeter - startMeter;
        const amount = soldQty * unitPrice;

        row.find('.other_sale_span_sold_qty').text(soldQty.toFixed(3));
        row.find('.other_sale_span_amount').text(__number_f(amount));

        row.find('.other_sale_sold_qty').val(soldQty.toFixed(3));
        row.find('.other_sale_amount').val(amount.toFixed(2));

        calculateTotalAmount();
    }

    function validateMeterInputOnChange(input, expected) {
        const value = parseFloat(input.value);
        if (!isNaN(value) && value < expected) {
            toastr.error('New meter value should be greater than Received Meter');
        }
    }

    /* ========= DOCUMENT READY ========= */

    $(document).ready(function() {

        $('#other_sale_form').on('submit', function(e) {
            e.preventDefault();

            if ($('.invalid-input').length > 0) {
                toastr.error('Please fix invalid meter values');
                return;
            }

            const $btn = $('.other_sale_finalize');
            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.msg);
                        $('#other_sale_modal').modal('hide');
                    } else {
                        toastr.error(res.msg);
                    }
                },
                error: function() {
                    toastr.error('Something went wrong');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('@lang('petro::lang.finalize')');
                }
            });
        });

    });
</script>


<div class="row">
    <div class="col-md-2 pull-right">
        <button type="submit" class="btn btn-danger pull-right other_sale_finalize"
            style="margin-top: 23px;">@lang('petro::lang.finalize')</button>
    </div>

</div>

{!! Form::close() !!}
