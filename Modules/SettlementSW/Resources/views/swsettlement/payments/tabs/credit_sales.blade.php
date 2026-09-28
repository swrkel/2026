<div class="row">
    <div class="col-md-12">
        <div class="row">

            {{-- Customer --}}
            <div class="col-md-3">
                <div class="form-group" style="width: 100% !important">
                    {!! Form::label('credit_sale_customer_id', __('settlementsw::lang.customer') . ':') !!}
                    <div class="input-group">
                        {!! Form::select(
                            'credit_sale_customer_id',
                            !empty($only_walkin) ? $walkin : $credit_customers,
                            null,
                            ['class' => 'form-control select2', 'style' => 'width: 100%;']
                        ) !!}
                        @if(empty($only_walkin))
                            <span class="input-group-btn">
                                <button type="button"
                                    class="btn btn-default bg-white btn-flat btn-modal"
                                    data-href="{{ action('ContactController@create', ['type' => 'customer', 'is_credit' => true]) }}"
                                    data-container=".contact_modal"
                                    @if (!auth()->user()->can('customer.create')) disabled @endif>
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Order Number --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_number', __('settlementsw::lang.order_number')) !!}
                    @if($business->duplicate_orders_allowed === 1)
                        {!! Form::text('order_number', 0, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('settlementsw::lang.order_number')
                        ]) !!}
                    @else
                        {!! Form::text('order_number', null, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('settlementsw::lang.order_number')
                        ]) !!}
                    @endif
                </div>
            </div>

            {{-- Order Date --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_date', __('settlementsw::lang.order_date')) !!}
                    {!! Form::text('order_date', null, [
                        'class' => 'form-control order_date',
                        'placeholder' => __('settlementsw::lang.order_date')
                    ]) !!}
                </div>
            </div>

            {{-- Customer Vehicle No --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_reference', __('settlementsw::lang.select_customer_vehicle_no')) !!}
                    {!! Form::select('customer_reference', [], null, [
                        'class' => 'form-control credit_sale_fields select2 customer_reference',
                        'required',
                        'id' => 'customer_reference',
                        'style' => 'width: 100%',
                        'placeholder' => __('settlementsw::lang.please_select')
                    ]) !!}
                </div>
            </div>

            <div class="clearfix"></div>

            {{-- Product --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_product_id', __('settlementsw::lang.credit_sale_product') . ':') !!}
                    {!! Form::select('credit_sale_product_id', $products, null, [
                        'class' => 'form-control select2',
                        'style' => 'width: 100%;',
                        'placeholder' => __('settlementsw::lang.please_select')
                    ]) !!}
                </div>
                <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">
            </div>

            {{-- Unit Price --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_price', __('settlementsw::lang.unit_price')) !!}
                    {!! Form::text('unit_price', null, [
                        'class' => 'form-control input_number unit_price',
                        'readonly',
                        'placeholder' => __('settlementsw::lang.unit_price')
                    ]) !!}
                </div>
            </div>

            {{-- Unit Discount --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_discount', __('settlementsw::lang.unit_discount')) !!}
                    {!! Form::text('unit_discount', null, [
                        'class' => 'form-control input_number unit_discount',
                        'disabled' => true,
                        'placeholder' => __('settlementsw::lang.unit_discount')
                    ]) !!}
                </div>
            </div>

            {{-- Quantity --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_qty', __('settlementsw::lang.credit_sale_qty')) !!}
                    {!! Form::text('credit_sale_qty', null, [
                        'class' => 'form-control credit_sale_fields input_number credit_sale_qty',
                        'placeholder' => __('settlementsw::lang.credit_sale_qty'),
                        'disabled' => true
                    ]) !!}
                    <input type="hidden" name="credit_sale_qty_hidden" id="credit_sale_qty_hidden" value="0">
                </div>
            </div>

            <div class="clearfix"></div>

            <input type="hidden" name="total_amount_enable" id="total_amount_enable" value="0">

            {{-- Total Amount (Before Discount) --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_total_amount', __('settlementsw::lang.amount') . __('settlementsw::lang.before_discount_cr')) !!}
                    {!! Form::text('credit_total_amount', null, [
                        'id' => 'credit_total_amount',
                        'class' => 'form-control credit_sale_fields cust_input_number credit_total_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('settlementsw::lang.credit_total_amount')
                    ]) !!}
                </div>
            </div>

            {{-- Hidden Total Amount --}}
            <div class="col-md-3 hidden">
                <div class="form-group">
                    {!! Form::text('credit_sale_amount', null, [
                        'id' => 'credit_sale_amount',
                        'class' => 'form-control credit_sale_fields cust_input_number credit_sale_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('settlementsw::lang.amount')
                    ]) !!}
                    <input type="hidden" name="credit_sale_amount_hidden" id="credit_sale_amount_hidden" value="0">
                </div>
            </div>

            {{-- Discount Amount --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_discount_amount', __('settlementsw::lang.credit_discount_amount')) !!}
                    {!! Form::text('credit_discount_amount', null, [
                        'class' => 'form-control credit_sale_fields cust_input_number credit_discount_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('settlementsw::lang.credit_discount_amount')
                    ]) !!}
                </div>
            </div>

            {{-- Enter Vehicle No --}}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_reference_one_time', __('settlementsw::lang.enter_customer_vehicle_no')) !!}
                    {!! Form::text('customer_reference', null, [
                        'class' => 'form-control customer_reference_one_time',
                        'id' => 'customer_reference_one_time',
                        'placeholder' => __('settlementsw::lang.enter_customer_vehicle_no')
                    ]) !!}
                </div>
            </div>

            {{-- Note --}}
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('credit_note', __('lang_v1.payment_note') . ':') !!}
                    {!! Form::textarea('credit_note', null, ['class' => 'form-control cash_fields', 'rows' => 3]) !!}
                </div>
                {!! Form::hidden('pump_operator_id', $pump_operator->id ?? '', ['id' => 'pump_operator_id']) !!}
            </div>

            {{-- Add Button --}}
            <div class="col-md-2 pull-right">
                <button type="button" class="btn btn-primary pull-right credit_sale_add" style="margin-top: 23px;">
                    @lang('messages.add')
                </button>
            </div>

            <div class="clearfix"></div>

            {{-- Outstanding & Credit Limit --}}
            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold;">
                @lang('settlementsw::lang.current_outstanding'): <span class="current_outstanding"></span>
            </div>

            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold;">
                @lang('settlementsw::lang.credit_limit'): <span class="credit_limit"></span>
            </div>

        </div>
    </div>
</div>

{{-- Credit Sales Table --}}
<div class="row" style="overflow-x: auto;">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="credit_sale_table">
            <thead>
                <tr>
                    <th>@lang('settlementsw::lang.cusotmer_name')</th>
                    <th>@lang('settlementsw::lang.outstanding')</th>
                    <th>@lang('settlementsw::lang.limit')</th>
                    <th>@lang('settlementsw::lang.order_no')</th>
                    <th>@lang('settlementsw::lang.order_date')</th>
                    <th>Vehicle No</th>
                    <th>@lang('settlementsw::lang.product')</th>
                    <th>@lang('settlementsw::lang.unit_price')</th>
                    <th>@lang('settlementsw::lang.qty')</th>
                    <th>@lang('settlementsw::lang.sub_total')</th>
                    <th>@lang('settlementsw::lang.discount_total')</th>
                    <th>@lang('settlementsw::lang.total')</th>
                    <th>@lang('lang_v1.note')</th>
                    <th>@lang('settlementsw::lang.action')</th>
                </tr>
            </thead>

            <tbody id="credit_sale_table_body">
                @foreach ($settlement_credit_sale_payments as $credit_sale_payment)
                    <tr>
                        <td>{{ $credit_sale_payment->customer_name }}</td>
                        <td>{{ @num_format($credit_sale_payment->outstanding) }}</td>
                        <td>{{ @num_format($credit_sale_payment->credit_limit) }}</td>
                        <td>{{ $credit_sale_payment->order_number }}</td>
                        <td>{{ $credit_sale_payment->order_date }}</td>
                        <td>{{ $credit_sale_payment->customer_reference }}</td>
                        <td>{{ $credit_sale_payment->product_name }}</td>
                        <td>{{ @num_format($credit_sale_payment->price) }}</td>
                        <td>{{ @num_format($credit_sale_payment->qty) }}</td>
                        <td class="credit_sale_amount">{{ @num_format($credit_sale_payment->amount) }}</td>
                        <td class="credit_tbl_discount_amount">{{ @num_format($credit_sale_payment->total_discount) }}</td>
                        <td class="credit_tbl_total_amount">
                            {{ @num_format($credit_sale_payment->amount - $credit_sale_payment->total_discount) }}
                        </td>
                        <td>{{ $credit_sale_payment->note }}</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment"
                                data-href="/settlement-sw/sw-add-payment/credit-sale-payment/{{ $credit_sale_payment->id }}">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="9" class="text-right font-weight-bold">@lang('settlementsw::lang.total') :</td>
                    <td class="font-weight-bold credit_sale_total">
                        {{ @num_format($settlement_credit_sale_payments->sum('amount')) }}
                    </td>
                    <td class="font-weight-bold credit_tb_discount_total">
                        {{ @num_format($settlement_credit_sale_payments->sum('total_discount')) }}
                    </td>
                    <td class="font-weight-bold credit_tbl_amount_total">
                        {{ @num_format(
                            $settlement_credit_sale_payments->sum('amount') -
                            $settlement_credit_sale_payments->sum('total_discount')
                        ) }}
                    </td>
                </tr>
                <input type="hidden"
                    id="credit_sale_total"
                    name="credit_sale_total"
                    value="{{ $settlement_credit_sale_payments->sum('amount') - $settlement_credit_sale_payments->sum('total_discount') }}">
            </tfoot>
        </table>
    </div>
</div>







<script>
$(document).ready(function () {
    // Initialize Select2 and datepicker
    $('#credit_sale_customer_id')
        .val($('#credit_sale_customer_id option:eq(0)').val())
        .trigger('change');

    $('#order_date').datepicker('setDate', new Date());

    $('#credit_sale_product_id, #credit_sale_customer_id, #customer_reference').select2();
});

/*-------------------------------------------------
 | One-time Vehicle Reference Input Handling
 -------------------------------------------------*/
$(document).on('change', '#customer_reference_one_time', function () {
    const hasValue = $(this).val()?.trim() !== '';
    $('#customer_reference, .quick_add_customer_reference')
        .prop('disabled', hasValue);
});

/*-------------------------------------------------
 | Add Customer Reference via AJAX
 -------------------------------------------------*/
$(document).on('submit', '#customer_reference_add_form', function (e) {
    e.preventDefault();

    const $form = $(this);
    const url = $form.attr('action');
    const data = $form.serialize();

    $.ajax({
        method: 'POST',
        url,
        data,
        dataType: 'json',
        success: function (result) {
            if (result.success) {
                $('#credit_sale_customer_id').trigger('change');
            }
            $('.view_modal').modal('hide');
        },
    });
});

/*-------------------------------------------------
 | Add Credit Sale Payment
 -------------------------------------------------*/
$(document).off('click', '.credit_sale_add').on('click', '.credit_sale_add', function () {
    console.log('Credit Sale Add Triggered');

    if (!$('#credit_sale_amount').val()) {
        toastr.error('Please enter amount');
        return false;
    }

    // Gather input values
    const credit_sale_customer_id = $('#credit_sale_customer_id').val();
    const customer_name = $('#credit_sale_customer_id :selected').text();
    const credit_sale_product_id = $('#credit_sale_product_id').val();
    const credit_sale_product_name = $('#credit_sale_product_id :selected').text();

    const customer_reference = $('#customer_reference_one_time').val()?.trim()
        ? $('#customer_reference_one_time').val()
        : $('#customer_reference').val();

    const settlement_no = $('#settlement_no').val();
    const order_date = $('#order_date').val();
    const order_number = $('#order_number').val();
    const pump_operator_id = $('#pump_operator_id').val();

    const credit_sale_price = __read_number($('#unit_price'));
    const credit_unit_discount = __read_number($('#unit_discount')) ?? 0;
    const credit_sale_qty = __read_number($('#credit_sale_qty')) ?? 0;
    const credit_total_amount = __read_number($('#credit_total_amount')) ?? 0;
    const credit_total_discount = __read_number($('#credit_discount_amount')) ?? 0;
    const credit_sub_total = __read_number($('#credit_sale_amount')) ?? 0;

    const outstanding = $('.current_outstanding').text();
    const credit_limit = $('.credit_limit').text();
    const credit_note = $('#credit_note').val();
    const is_edit = $('#is_edit').val() ?? 0;

    // AJAX call
    $.ajax({
        method: 'POST',
        url: '/settlement-sw/sw-add-payment/credit-sale-payment',
        data: {
            settlement_no,
            customer_id: credit_sale_customer_id,
            product_id: credit_sale_product_id,
            order_number,
            order_date,
            pump_operator_id,
            price: credit_sale_price,
            unit_discount: credit_unit_discount,
            qty: credit_sale_qty,
            amount: credit_total_amount,
            sub_total: credit_sub_total,
            total_discount: credit_total_discount,
            outstanding,
            credit_limit,
            customer_reference,
            note: credit_note,
            is_edit,
        },
        success: function (result) {
            if (!result.success) {
                toastr.error(result.msg);
                return;
            }

            const paymentId = result.settlement_credit_sale_payment_id;

            // Update totals
            add_payment(credit_total_amount - credit_total_discount);

            // Append new row
            $('#credit_sale_table tbody').prepend(`
                <tr>
                    <td>${customer_name}</td>
                    <td>${outstanding}</td>
                    <td>${credit_limit}</td>
                    <td>${order_number}</td>
                    <td>${order_date}</td>
                    <td>${customer_reference}</td>
                    <td>${credit_sale_product_name}</td>
                    <td>${__number_f(credit_sale_price, false, false, __currency_precision)}</td>
                    <td>${__number_f(credit_sale_qty, false, false, __currency_precision)}</td>
                    <td class="credit_sale_amount">${__number_f(credit_total_amount, false, false, __currency_precision)}</td>
                    <td class="credit_tbl_discount_amount">${__number_f(credit_total_discount, false, false, __currency_precision)}</td>
                    <td class="credit_tbl_total_amount">${__number_f(credit_sub_total, false, false, __currency_precision)}</td>
                    <td>${credit_note}</td>
                    <td>
                        <button type="button"
                            class="btn btn-xs btn-danger delete_credit_sale_payment"
                            data-href="/settlement-sw/sw-add-payment/credit-sale-payment/${paymentId}">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            `);

            // Reset form
            $('#customer_reference_one_time').val('').trigger('change');
            $('.credit_sale_fields, .cash_fields').val('');
            $('#credit_sale_product_id').trigger('change');
            $('#order_number').val(order_number);

            // Recalculate totals
            calculateTotal('#credit_sale_table', '.credit_sale_amount', '.credit_sale_total');
            calculateTotal('#credit_sale_table', '.credit_tbl_discount_amount', '.credit_tb_discount_total');
            calculateTotal('#credit_sale_table', '.credit_tbl_total_amount', '.credit_tbl_amount_total');

            toastr.success('Added!');
        },
    });
});
</script>
