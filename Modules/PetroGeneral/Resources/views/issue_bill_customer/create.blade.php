<style type="text/css">
    .add_row, .minus_row {
        padding: 0 5px;
    }
</style>
@php
    $business_id = request()->session()->get('business.id');
    $business_details = \App\Business::find($business_id);
    $currency_precision = $business_details->currency_precision ?? 2;
@endphp
<div class="modal-dialog" role="document" style="width: 80%;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\IssueCustomerBillController@store'), 'method' =>
        'post', 'id' => 'issue_bill_customer_form' ])
        !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.issue_customer_bill' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date', __( 'petrogeneral::lang.time_and_date' )) !!}
                        {!! Form::text('date', null, ['class' => 'form-control', 'required', 'readonly', 'placeholder' =>
                        __( 'petrogeneral::lang.date')]);
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('customer_bill_no', __( 'petrogeneral::lang.customer_bill_no' )) !!}
                        {!! Form::text('customer_bill_no', $customer_bill_no, ['class' => 'form-control', 'required', 'placeholder' =>
                        __( 'petrogeneral::lang.customer_bill_no')]);
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        @php
                            $businessId = Auth::user()->business_id;
                            $business_location = \App\BusinessLocation::where('business_id', $businessId)->first()->id;
                        @endphp
                        {!! Form::label('location_id', __( 'petrogeneral::lang.location' )) !!}
                        {!! Form::select('location_id', $business_locations, $business_location, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required', 'placeholder' =>
                        __( 'petrogeneral::lang.please_select')]);
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('pump_id', __( 'petrogeneral::lang.pump' )) !!}
                        {!! Form::select('pump_id', $pumps, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required', 'placeholder' =>
                        __( 'petrogeneral::lang.please_select')]);
                        !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('operator_id', __( 'petrogeneral::lang.pump_operator' )) !!}
                        {!! Form::select('operator_id', $pump_operators, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required', 'placeholder' =>
                        __( 'petrogeneral::lang.please_select')]);
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('customer_id', __( 'petrogeneral::lang.customer' )) !!}
                        {!! Form::select('customer_id', $customers, $default_customer_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required', 'placeholder' =>
                        __( 'petrogeneral::lang.please_select')]);
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('reference_id', __( 'petrogeneral::lang.reference' )) !!}
                        {!! Form::select('reference_id', ['' => 'No vehicle No'], null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'required']);
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('show_in_daily_voucher', __( 'petrogeneral::lang.show_in_daily_voucher' )) !!}
                        {!! Form::select('show_in_daily_voucher', ['1' => 'Yes', '0' => 'No'], null, ['class' => 'form-control', 'required', 'style' => 'width:100%;', 'placeholder' =>
                        __( 'petrogeneral::lang.please_select')]);
                        !!}
                    </div>
                </div>
            </div>
            <div class="row">
                
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('order_voucher_no', __( 'petrogeneral::lang.order_voucher_no' )) !!}
                        {!! Form::text('order_voucher_no', null, ['class' => 'form-control', 'placeholder' =>
                        __( 'petrogeneral::lang.order_voucher_no')]);
                        !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('order_voucher_date', __( 'petrogeneral::lang.order_voucher_date' )) !!}
                        {!! Form::text('order_voucher_date', null, ['class' => 'form-control', 'required', 'placeholder' =>
                        __( 'petrogeneral::lang.order_voucher_date')]);
                        !!}
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>

            <table class="table table-responsive" id="issue_customer_bill_add_table">
                <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.product')</th>
                    <th>@lang('petrogeneral::lang.unit_price')</th>
                    <th>@lang('petrogeneral::lang.qty')</th>
                    <th>@lang('petrogeneral::lang.discount')</th>
                    {{--                    <th>@lang('petrogeneral::lang.tax_percentage')</th>--}}
                    <th>@lang('petrogeneral::lang.sub_total')</th>
                    <th>@lang('petrogeneral::lang.action')</th>
                </tr>
                </thead>
                <tbody>
                <tr class="product_row">
                    <td>
                        {!! Form::select('issue_customer_bill[0][product_id]', $products, null, ['class' => 'form-control select2 product_id', 'style' => 'width:100%;', 'required', 'placeholder' =>
                        __( 'petrogeneral::lang.please_select')]) !!}
                    </td>
                    <td>
                        {!! Form::text('issue_customer_bill[0][unit_price]', number_format(0, $currency_precision, '.', ','), ['class' => 'form-control unit_price text-right', 'style' => 'width: 120px;', 'placeholder' => __('petrogeneral::lang.unit_price'), 'readonly']) !!}
                    </td>
                    <td>
                        {!! Form::text('issue_customer_bill[0][qty]', number_format(0, $currency_precision, '.', ','), ['class' => 'form-control qty text-right', 'style' => 'width: 120px;', 'placeholder' => __('petrogeneral::lang.qty')]) !!}
                    </td>
                    <td>
                        {!! Form::text('issue_customer_bill[0][discount]', number_format(0, $currency_precision, '.', ','), ['class' => 'form-control discount text-right', 'style' => 'width: 120px;', 'placeholder' => __('petrogeneral::lang.discount')]) !!}
                    </td>
                    {{--                    <td>--}}
                    {{--                        {!! Form::text('issue_customer_bill[0][tax]', 0, ['class' => 'form-control tax', 'style' => 'width: 120px;', 'placeholder' => __('petrogeneral::lang.tax')]) !!}--}}
                    {{--                    </td>--}}
                    <td>
                        {!! Form::text('issue_customer_bill[0][sub_total]', number_format(0, $currency_precision, '.', ','), ['class' => 'form-control sub_total text-right', 'style' => 'width: 120px;', 'placeholder' => __('petrogeneral::lang.sub_total')]) !!}
                    </td>
                    <td>
                        <button type="button" class="btn btn-xs btn-primary add_row" style="margin-top: 6px;">+</button>
                    </td>
                </tr>
                <input type="hidden" name="index" id="index" value="1">
                </tbody>
            </table>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"
                        id="save_issue_bill_customer_btn">@lang( 'messages.save_and_print' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div>
</div>

<script>
    (function () {
        const defaultCustomerId = @json($default_customer_id ?? null);
        const defaultReferenceId = @json($default_reference_id ?? null);
        $('#date').datetimepicker({
            ignoreReadonly: false,
            format: 'YYYY-MM-DD HH:mm:ss',
            defaultDate: new Date()
        });
        $('#voucher_order_date').datepicker("setDate", new Date());
        $('#order_voucher_date').datepicker("setDate", new Date());
        $('.select2').select2();

        $('#customer_id').change(function () {
            let customer_id = $('#customer_id :selected').val();

            $.ajax({
                method: 'get',
                url: '/petro-general/issue-customer-bill/get-customer-reference/' + customer_id,
                data: {},
                contentType: 'html',
                success: function (result) {
                    const referenceSelect = $('#reference_id');
                    referenceSelect.empty().append(result);

                    if (defaultCustomerId && defaultReferenceId && customer_id == defaultCustomerId) {
                        referenceSelect.val(defaultReferenceId).trigger('change');
                    } else {
                        referenceSelect.val(null).trigger('change');
                    }

                    referenceSelect.select2('close');
                },
            });
        })
        if (defaultCustomerId) {
            $('#customer_id').val(defaultCustomerId).trigger('change');
        }
    })();

    $('#operator_id').select2({
    placeholder: 'Please select Pump Operator',
    dropdownParent: $('#issue_bill_customer_form').closest('.modal'),
    minimumResultsForSearch: 0,
    width: '100%'
});


    $(document).on('change', '.product_id', function () {
        let product_id = $(this).val();
        let this_row = $(this).parent().parent();
        let this_unit_input = $(this).parent().parent().find('input.unit_price');

        $.ajax({
            method: 'get',
            url: '/petro-general/issue-customer-bill/get-product-price/' + product_id,
            data: {},
            success: function (result) {
                this_unit_input.val(result.unit_price);
                calculate_total(this_row);
            },
        });
    });

    $(document).on('change', '#pump_id', function () {
        let pump_id = $(this).val();

        $.ajax({
            method: 'get',
            url: '/petro-general/issue-customer-bill/setting/' + pump_id,
            success: function (response) {
                $('#operator_id').val(response.data.operator_id).trigger('change');

                if (response.data.product_id) {
                    $('.product_id').first().val(response.data.product_id).trigger('change');
                }
            },
        });
    });

    $(document).on('change', '.unit_price, .qty, .discount', function () {
        let this_row = $(this).parent().parent();
        calculate_total(this_row);
    });


    function calculate_total(this_row) {
        let unit_price = parseFloat(this_row.find('.unit_price').val());
        let qty = this_row.find('.qty');
        let qtyValue = parseFloat(qty.val());
        let discount = parseFloat(this_row.find('.discount').val());
        let sub_total = this_row.find('.sub_total');
        let subtotal = ((unit_price * qtyValue) - discount);
        qty.val(formatNumber(qtyValue));
        sub_total.val(formatNumber(subtotal));
    }

    $(document).on('click', '.add_row', function () {
        let index = parseInt($('#index').val());
        $.ajax({
            method: 'get',
            url: '/petro-general/issue-customer-bill/get-product-row',
            data: {index: index},
            success: function (result) {
                $('#index').val(index + 1);
                $('#issue_customer_bill_add_table').append(result);
            },
        });

    });

    $(document).on('click', '.minus_row', function () {
        $(this).closest('tr').remove();
    });

    function formatNumber(value) {
        const currency_precision = {{ $currency_precision }};
        return parseFloat(value)
            .toFixed(currency_precision)
            .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
</script>
