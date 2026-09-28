<div class="row">
    <div class="col-md-12">

        <div class="row">
            <div class="col-md-3">
                <div class="form-group" style="width: 100% !important">
                    {!! Form::label('credit_sale_customer_id', __('petro::lang.customer').':') !!}
                    <div class="input-group">
                        {!! Form::select('credit_sale_customer_id', !empty($only_walkin) ? $walkin : $credit_customers, null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('petro::lang.please_select')]) !!}
                        @if (empty($only_walkin))
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat btn-modal"
                                    data-href="{{ action('ContactController@create', ['type' => 'customer', 'is_credit' => true]) }}"
                                    data-container=".contact_modal" @if (! auth()->user()->can('customer.create')) disabled @endif>
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_number', __('petro::lang.order_number')) !!}
                    {!! Form::text('order_number', $business->duplicate_orders_allowed ? 0 : null, [
                        'class' => 'form-control credit_sale_fields order_number',
                        'placeholder' => __('petro::lang.order_number'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('order_date', __('petro::lang.order_date')) !!}
                    {!! Form::text('order_date', null, [
                        'class' => 'form-control order_date',
                        'placeholder' => __('petro::lang.order_date'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_reference', __('petro::lang.select_customer_vehicle_no')) !!}
                    {!! Form::select('customer_reference', [], null, [
                        'class' => 'form-control credit_sale_fields select2 customer_reference',
                        'required',
                        'id' => 'customer_reference',
                        'style' => 'width: 100%',
                        'placeholder' => __('petro::lang.please_select'),
                    ]) !!}
                </div>
            </div>

            <div class="clearfix"></div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_product_id', __('petro::lang.credit_sale_product').':') !!}
                    {!! Form::select('credit_sale_product_id', $products, null, [
                        'id' => 'credit_sale_product_id',
                        'class' => 'form-control select2',
                        'style' => 'width: 100%;',
                        'data-force-dropdown-below' => '1',
                        'placeholder' => __('petro::lang.please_select'),
                    ]) !!}
                </div>
                <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_price', __('petro::lang.unit_price')) !!}
                    {!! Form::text('unit_price', null, [
                        'class' => 'form-control input_number unit_price',
                        'readonly',
                        'placeholder' => __('petro::lang.unit_price'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_discount', __('petro::lang.unit_discount')) !!}
                    {!! Form::text('unit_discount', null, [
                        'class' => 'form-control input_number unit_discount',
                        'disabled' => true,
                        'placeholder' => __('petro::lang.unit_discount'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_qty', __('petro::lang.credit_sale_qty')) !!}
                    {!! Form::text('credit_sale_qty', null, [
                        'class' => 'form-control credit_sale_fields input_number credit_sale_qty',
                        'placeholder' => __('petro::lang.credit_sale_qty'),
                        'disabled' => true,
                    ]) !!}
                    <input type="hidden" name="credit_sale_qty_hidden" value="0" id="credit_sale_qty_hidden">
                </div>
            </div>

            <div class="clearfix"></div>
            <input type="hidden" name="total_amount_enable" id="total_amount_enable" value="0">

            <div class="col-md-3">
                {!! Form::label('credit_total_amount', __('petro::lang.amount') . __('petro::lang.before_discount_cr')) !!}
                {!! Form::text('credit_total_amount', null, [
                    'id' => 'credit_total_amount',
                    'class' => 'form-control credit_sale_fields cust_input_number credit_total_amount',
                    'required',
                    'disabled' => true,
                    'placeholder' => __('petro::lang.credit_total_amount'),
                ]) !!}
            </div>

            <div class="col-md-3 hidden">
                {!! Form::text('credit_sale_amount', null, [
                    'id' => 'credit_sale_amount',
                    'class' => 'form-control credit_sale_fields cust_input_number credit_sale_amount',
                    'required',
                    'disabled' => true,
                    'placeholder' => __('petro::lang.amount'),
                ]) !!}
                <input type="hidden" name="credit_sale_amount_hidden" value="0" id="credit_sale_amount_hidden">
            </div>

            <div class="col-md-3">
                {!! Form::label('credit_discount_amount', __('petro::lang.credit_discount_amount')) !!}
                {!! Form::text('credit_discount_amount', null, [
                    'class' => 'form-control credit_sale_fields cust_input_number credit_discount_amount',
                    'required',
                    'disabled' => true,
                    'placeholder' => __('petro::lang.credit_discount_amount'),
                ]) !!}
            </div>

            <div class="col-md-3">
                {!! Form::label('customer_reference_one_time', __('petro::lang.enter_customer_vehicle_no')) !!}
                {!! Form::text('customer_reference', null, [
                    'class' => 'form-control customer_reference_one_time',
                    'id' => 'customer_reference_one_time',
                    'placeholder' => __('petro::lang.enter_customer_vehicle_no'),
                ]) !!}
            </div>

            <div class="col-md-4">
                {!! Form::label('credit_note', __('lang_v1.payment_note').':') !!}
                {!! Form::textarea('credit_note', null, ['class' => 'form-control cash_fields', 'rows' => 3]) !!}
                {!! Form::hidden('pump_operator_id', $pump_operator->id ?? '', ['id' => 'pump_operator_id']) !!}
                {!! Form::hidden('scsp_id', null, ['id' => 'scsp_id']) !!}
            </div>

            <div class="col-md-2 pull-right">
                <button type="button" class="btn btn-primary pull-right credit_sale_add" style="margin-top: 23px;">
                    @lang('messages.add')
                </button>
            </div>
        </div>

        <div class="row" style="margin-top:8px;">
            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold;">
                @lang('petro::lang.current_outstanding'):
                <span class="current_outstanding"></span>
            </div>

            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold;">
                @lang('petro::lang.credit_limit'):
                <span class="credit_limit"></span>
            </div>
        </div>

    </div>
</div>


<div class="row" style="overflow-x: auto;">

    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="credit_sale_table">

            <thead>

                <tr>

                    <th>@lang('petro::lang.cusotmer_name')</th>

                    <th>@lang('petro::lang.outstanding')</th>

                    <th>@lang('petro::lang.limit')</th>

                    <th>@lang('petro::lang.order_no')</th>

                    <th>@lang('petro::lang.order_date')</th>

                    <th>Vehicle No</th>

                    <th>@lang('petro::lang.product')</th>

                    <th>@lang('petro::lang.unit_price')</th>

                    <th>@lang('petro::lang.qty')</th>

                    <th>@lang('petro::lang.sub_total')</th>

                    <th>@lang('petro::lang.discount_total')</th>

                    <th>@lang('petro::lang.total')</th>

                    <th>@lang('lang_v1.note') </th>

                    <th>@lang('petro::lang.action')</th>

                </tr>

            </thead>

            <tbody id="credit_sale_table_body">

                @foreach ($settlement_credit_sale_payments as $credit_sale_payment)
                    <tr data-credit-sale-id="{{ $credit_sale_payment->id }}"
                        data-credit-sale-payment-id="{{ $credit_sale_payment->id }}"
                        data-order-date="{{ $credit_sale_payment->order_date }}">

                        <td>{{ $credit_sale_payment->customer_name }}</td>

                        <td>{{ @num_format($credit_sale_payment->outstanding) }}</td>

                        <td>{{ @num_format($credit_sale_payment->credit_limit) }}</td>

                        <td>{{ $credit_sale_payment->order_number }}</td>

                        <td>{{ !empty($credit_sale_payment->order_date) ? @format_date($credit_sale_payment->order_date) : '' }}</td>

                        <td>{{ $credit_sale_payment->customer_reference }}</td>

                        <td>{{ $credit_sale_payment->product_name }}</td>

                        <td>{{ @num_format($credit_sale_payment->price) }}</td>

                        <td>{{ @num_format($credit_sale_payment->qty) }}</td>

                        <td class="credit_sale_amount">{{ @num_format($credit_sale_payment->amount) }}

                        </td>

                        <td class="credit_tbl_discount_amount">{{ @num_format($credit_sale_payment->total_discount) }}

                        </td>

                        <td class="credit_tbl_total_amount">
                            {{ @num_format($credit_sale_payment->amount - $credit_sale_payment->total_discount) }}

                        </td>

                        <td>{{ $credit_sale_payment->note }}</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment"
                                data-href="/petro/settlement/payment/delete-credit-sale-payment/{{ $credit_sale_payment->id }}"><i
                                    class="fa fa-times"></i></button></td>

                    </tr>
                @endforeach

            </tbody>



            <tfoot>

                <tr>

                    <td colspan="9" style="text-align: right; font-weight: bold;">@lang('petro::lang.total') :</td>

                    <td style="text-align: left; font-weight: bold;" class="credit_sale_total">

                        {{ @num_format($settlement_credit_sale_payments->sum('amount')) }}</td>

                    <td style="text-align: left; font-weight: bold;" class="credit_tb_discount_total">

                        {{ @num_format($settlement_credit_sale_payments->sum('total_discount')) }}</td>

                    <td style="text-align: left; font-weight: bold;" class="credit_tbl_amount_total">

                        {{ @num_format($settlement_credit_sale_payments->sum('amount') - $settlement_credit_sale_payments->sum('total_discount')) }}
                    </td>

                </tr>

                <input type="hidden"
                    value="{{ $settlement_credit_sale_payments->sum('amount') - $settlement_credit_sale_payments->sum('total_discount') }}"
                    name="credit_sale_total" id="credit_sale_total">

            </tfoot>

        </table>

    </div>

</div>

<!-- <script>
    $(document).ready(function() {

        $(document).on("change", "#order_number_input", function() {

            var orderNumber = $(this).val();
            if (orderNumber) {
                $.ajax({
                    url: "/petro/settlement/payment/check-order-number",
                    type: 'GET',
                    data: {
                        order_number: orderNumber
                    },
                    success: function(response) {
                        if (response.exists) {
                            alert(
                                'Order number already exists! Please choose a different number.');
                            $('#order_number_input').val('');
                            $('#order_number_input').focus();
                        }
                    },
                    error: function(xhr) {
                        console.error('API call failed', xhr);
                    }
                });
            }
        });

    });
</script> -->



<script>
    $(document).ready(function() {

        // IS1552: Do not auto-select the first customer. Users must be able to open the list and select the correct customer.

        // Initialize datepicker on order_date field
        $('#order_date').datepicker({
            autoclose: true,
            format: 'mm/dd/yyyy'
        });
        
        // Set initial date to today if field is empty
        if (!$('#order_date').val()) {
            $('#order_date').datepicker("setDate", new Date());
        }

        // IS1762: use the single idempotent Add Payment initializer. Repeatedly
        // destroying Select2 after unrelated AJAX responses was closing the Product
        // list before the user could select an item.
        function initPetroCreditSaleSelect2(selector) {
            var $field = $(selector);
            if (!$field.length) {
                return;
            }

            if (typeof window.__is1762InitPetroPaymentSelect2 === 'function') {
                window.__is1762InitPetroPaymentSelect2($field.closest('.tab-pane'));
                return;
            }

            if ($field.data('select2')) {
                return;
            }

            var $dropdownParent = $field.closest('.form-group');
            if (!$dropdownParent.length) {
                $dropdownParent = $field.closest('.add_payment');
            }
            if (!$dropdownParent.length) {
                $dropdownParent = $(document.body);
            }

            $dropdownParent.css({
                position: 'relative',
                overflow: 'visible'
            });

            $field.select2({
                width: '100%',
                dropdownParent: $dropdownParent
            });
        }

        initPetroCreditSaleSelect2('#credit_sale_product_id');
        initPetroCreditSaleSelect2('#credit_sale_customer_id');
        initPetroCreditSaleSelect2('#customer_reference');

    });



    $(document).on('change', '#customer_reference_one_time', function() {

        if ($(this).val() !== '' && $(this).val() !== null && $(this).val() !== undefined) {

            $('#customer_reference').attr('disabled', 'disabled');

            $('.quick_add_customer_reference').attr('disabled', 'disabled');

        } else {

            $('#customer_reference').removeAttr('disabled');

            $('.quick_add_customer_reference').removeAttr('disabled');

        }

    })



    $(document).on('submit', '#customer_reference_add_form', function(e) {

        e.preventDefault();

        let url = $('#customer_reference_add_form').attr('action');

        let data = $('#customer_reference_add_form').serialize();

        $.ajax({

            method: 'POST',

            url: url,

            dataType: 'json',

            data: data,

            success: function(result) {

                if (result.success) {

                    let customer_reference = result.customer_reference;

                    $('#credit_sale_customer_id').trigger('change');

                }



                $('.view_modal').modal('hide');

            },

        });

    })
</script>



<script>
    // IS1782: keep Credit Sales operations bound to the exact active Direct
    // Settlement payment pane. This prevents a successful response from being
    // inserted into another/hidden table and makes the saved Order Date visible
    // immediately without refreshing the page.
    (function ($, window, document) {
        'use strict';

        function resolveCreditSaleContext(source) {
            var $source = source && source.jquery ? source : $(source || []);
            var paneSelector = '[data-petro-payment-pane="credit_sales_tab"], #credit_sales_tab';
            var $pane = $source.closest(paneSelector);

            if (!$pane.length) {
                $pane = $(paneSelector).filter(':visible').last();
            }
            if (!$pane.length) {
                $pane = $(paneSelector).last();
            }

            var $tabs = $pane.closest('.s271-direct-payment-tabs');
            var $paymentArea = $pane.closest('.add_payment');
            var $scope = $paymentArea.length ? $paymentArea : ($tabs.length ? $tabs : $pane);

            return {
                $source: $source,
                $pane: $pane,
                $tabs: $tabs,
                $scope: $scope
            };
        }

        function scopedField(context, selector) {
            var $field = context.$pane.find(selector).first();

            if (!$field.length && context.$scope && context.$scope.length) {
                $field = context.$scope.find(selector).first();
            }
            if (!$field.length) {
                $field = $(selector).first();
            }

            return $field;
        }

        function readCreditNumber($field, defaultValue) {
            if (!$field || !$field.length) {
                return defaultValue || 0;
            }

            var value = typeof __read_number === 'function'
                ? __read_number($field)
                : parseFloat(String($field.val() || '').replace(/,/g, ''));

            return isFinite(value) ? value : (defaultValue || 0);
        }

        function formatCreditNumber(value) {
            var number = parseFloat(value);
            if (!isFinite(number)) {
                number = 0;
            }

            return __number_f(number, false, false, __currency_precision);
        }

        function formatCreditLimit(value) {
            var text = value == null ? '' : String(value).trim();
            if (text === '') {
                return '';
            }

            var numeric = parseFloat(text.replace(/,/g, ''));
            return isFinite(numeric) && /^[-+]?\d[\d,]*(\.\d+)?$/.test(text)
                ? formatCreditNumber(numeric)
                : text;
        }

        function appendTextCell($row, value, className) {
            var $cell = $('<td>');
            if (className) {
                $cell.addClass(className);
            }
            $cell.text(value == null ? '' : value);
            $row.append($cell);
        }

        function activateCreditSalesPane(context) {
            if (!context.$pane.length) {
                return;
            }

            var tabsRoot = context.$tabs.length ? context.$tabs.get(0) : context.$pane.get(0);
            if (tabsRoot && typeof window.s271LoadOnlySelectedPaymentTab === 'function') {
                window.s271LoadOnlySelectedPaymentTab(tabsRoot, 'credit_sales_tab');
            } else {
                context.$pane
                    .addClass('active in show')
                    .attr('aria-hidden', 'false')
                    .css({ display: 'block', visibility: 'visible', height: 'auto', overflow: 'visible' });
            }
        }

        function updateCreditSaleTotals(context, totals) {
            if (totals) {
                context.$pane.find('.credit_sale_total').text(formatCreditNumber(totals.gross));
                context.$pane.find('.credit_tb_discount_total').text(formatCreditNumber(totals.discount));
                context.$pane.find('.credit_tbl_amount_total').text(formatCreditNumber(totals.net));
                scopedField(context, '#credit_sale_total').val(parseFloat(totals.net || 0));
                return;
            }

            var $table = context.$pane.find('#credit_sale_table').first();
            if (!$table.length) {
                return;
            }

            function sumColumn(selector) {
                var total = 0;
                $table.find('tbody ' + selector).each(function () {
                    var value = typeof __number_uf === 'function'
                        ? parseFloat(__number_uf($(this).text()))
                        : parseFloat(String($(this).text() || '').replace(/,/g, ''));
                    total += isFinite(value) ? value : 0;
                });
                return total;
            }

            var gross = sumColumn('.credit_sale_amount');
            var discount = sumColumn('.credit_tbl_discount_amount');
            var net = sumColumn('.credit_tbl_total_amount');
            context.$pane.find('.credit_sale_total').text(formatCreditNumber(gross));
            context.$pane.find('.credit_tb_discount_total').text(formatCreditNumber(discount));
            context.$pane.find('.credit_tbl_amount_total').text(formatCreditNumber(net));
            scopedField(context, '#credit_sale_total').val(net);
        }

        function applyCreditSaleResponse(result, source) {
            if (!result || !result.success || !result.payment) {
                return false;
            }

            var context = resolveCreditSaleContext(source);
            var payment = result.payment;
            var paymentId = parseInt(payment.id || result.settlement_credit_sale_payment_id, 10);
            var $body = context.$pane.find('#credit_sale_table_body').first();

            if (!paymentId || !$body.length) {
                return false;
            }

            $body.find(
                'tr[data-credit-sale-id="' + paymentId + '"], ' +
                'tr[data-credit-sale-payment-id="' + paymentId + '"]'
            ).remove();

            var displayOrderDate = payment.order_date_display
                || result.order_date_display
                || payment.order_date
                || result.order_date
                || '';

            var rawOrderDate = payment.order_date_raw
                || result.order_date_raw
                || payment.order_date
                || result.order_date
                || '';

            var $row = $('<tr>')
                .attr('data-credit-sale-id', paymentId)
                .attr('data-credit-sale-payment-id', paymentId)
                .attr('data-order-date', rawOrderDate);

            appendTextCell($row, payment.customer_name || '');
            appendTextCell($row, formatCreditNumber(payment.outstanding));
            appendTextCell($row, formatCreditLimit(payment.credit_limit));
            appendTextCell($row, payment.order_number || '');
            appendTextCell($row, displayOrderDate);
            appendTextCell($row, payment.customer_reference || '');
            appendTextCell($row, payment.product_name || '');
            appendTextCell($row, formatCreditNumber(payment.price));
            appendTextCell($row, formatCreditNumber(payment.qty));
            appendTextCell($row, formatCreditNumber(payment.amount), 'credit_sale_amount');
            appendTextCell($row, formatCreditNumber(payment.total_discount), 'credit_tbl_discount_amount');
            appendTextCell($row, formatCreditNumber(payment.sub_total), 'credit_tbl_total_amount');
            appendTextCell($row, payment.note || '');

            var $deleteCell = $('<td>');
            $('<button>', {
                type: 'button',
                class: 'btn btn-xs btn-danger delete_credit_sale_payment',
                'data-href': '/petro/settlement/payment/delete-credit-sale-payment/' + paymentId,
                'aria-label': 'Delete credit sale'
            }).append($('<i>', { class: 'fa fa-times' })).appendTo($deleteCell);
            $row.append($deleteCell);

            $body.prepend($row);
            $row.show();
            updateCreditSaleTotals(context, result.totals || null);
            activateCreditSalesPane(context);

            window.__petroLastCreditSaleResponse = result;
            return true;
        }

        window.petroApplyCreditSaleResponse = applyCreditSaleResponse;

        $(document)
            .off('click.is1782CreditSaleAdd', '.credit_sale_add')
            .on('click.is1782CreditSaleAdd', '.credit_sale_add', function (event) {
                event.preventDefault();

                var $button = $(this);
                var context = resolveCreditSaleContext($button);
                if ($button.data('submitting')) {
                    return false;
                }

                var $amountField = scopedField(context, '#credit_total_amount');
                var creditTotalAmount = readCreditNumber($amountField, 0);
                if (creditTotalAmount <= 0) {
                    toastr.error('Please enter amount');
                    return false;
                }

                var $customer = scopedField(context, '#credit_sale_customer_id');
                var $product = scopedField(context, '#credit_sale_product_id');
                var $orderDate = scopedField(context, '#order_date');

                if (!$customer.val()) {
                    toastr.error('Please select customer');
                    $customer.focus();
                    return false;
                }
                if (!$product.val()) {
                    toastr.error('Please select product');
                    $product.focus();
                    return false;
                }
                if (!String($orderDate.val() || '').trim()) {
                    toastr.error('Please select order date');
                    $orderDate.focus();
                    return false;
                }

                var $oneTimeReference = scopedField(context, '#customer_reference_one_time');
                var oneTimeReference = String($oneTimeReference.val() || '').trim();
                var customerReference = oneTimeReference !== ''
                    ? oneTimeReference
                    : scopedField(context, '#customer_reference').val();

                var creditSalePrice = readCreditNumber(scopedField(context, '#unit_price'), 0);
                var creditUnitDiscount = readCreditNumber(scopedField(context, '#unit_discount'), 0);
                var creditSaleQty = readCreditNumber(scopedField(context, '#credit_sale_qty'), 0);
                var creditTotalDiscount = readCreditNumber(scopedField(context, '#credit_discount_amount'), 0);
                var creditSubTotal = creditTotalAmount - creditTotalDiscount;
                scopedField(context, '#credit_sale_amount').val(creditSubTotal);

                var requestData = {
                    settlement_no: scopedField(context, '#settlement_no').val(),
                    scsp_id: scopedField(context, '#scsp_id').val(),
                    customer_id: $customer.val(),
                    product_id: $product.val(),
                    order_number: scopedField(context, '#order_number').val(),
                    order_date: $orderDate.val(),
                    pump_operator_id: scopedField(context, '#pump_operator_id').val(),
                    price: creditSalePrice,
                    unit_discount: creditUnitDiscount,
                    qty: creditSaleQty,
                    amount: creditTotalAmount,
                    sub_total: creditSubTotal,
                    total_discount: creditTotalDiscount,
                    outstanding: context.$pane.find('.current_outstanding').first().text(),
                    credit_limit: context.$pane.find('.credit_limit').first().text(),
                    customer_reference: customerReference,
                    note: scopedField(context, '#credit_note').val(),
                    is_edit: scopedField(context, '#is_edit').val() || 0,
                    transaction_date: scopedField(context, '#transaction_date').val()
                        || context.$scope.find('.transaction_date').first().val(),
                    active_settlement_id: scopedField(context, '#active_settlement_id').val() || 0
                };

                $button.data('submitting', true).prop('disabled', true);

                swal({
                    title: 'Add Credit sale?',
                    text: 'Are you sure you want to add this Credit sale?',
                    icon: 'warning',
                    buttons: { cancel: 'No', confirm: { text: 'Yes', value: true } },
                    dangerMode: false
                }).then(function (confirmed) {
                    if (!confirmed) {
                        $button.data('submitting', false).prop('disabled', false);
                        return;
                    }

                    $.ajax({
                        method: 'post',
                        url: '/petro/settlement/payment/save-credit-sale-payment',
                        data: requestData,
                        success: function (result) {
                            if (!result || !result.success) {
                                toastr.error(result && result.msg ? result.msg : 'Unable to add credit sale');
                                return;
                            }

                            var netAmount = result.payment && result.payment.sub_total !== undefined
                                ? parseFloat(result.payment.sub_total || 0)
                                : creditSubTotal;
                            var isDirectSettlement = scopedField(context, '#is_direct_settlement').val() === '1';

                            if (isDirectSettlement && typeof add_sale_amount === 'function') {
                                add_sale_amount(netAmount);
                            } else if (typeof add_payment === 'function') {
                                add_payment(netAmount);
                            }

                            applyCreditSaleResponse(result, $button);

                            // Clear only the completed line-item fields. Keep Customer,
                            // Order No and Order Date ready for the next item, as before.
                            scopedField(context, '#scsp_id').val('');
                            $oneTimeReference.val('').trigger('change');
                            scopedField(context, '#customer_reference').val(null).trigger('change');
                            $product.val(null).trigger('change');
                            scopedField(context, '#unit_price').val('');
                            scopedField(context, '#unit_discount').val('');
                            scopedField(context, '#credit_sale_qty').val('');
                            scopedField(context, '#credit_sale_qty_hidden').val(0);
                            scopedField(context, '#credit_total_amount').val('');
                            scopedField(context, '#credit_sale_amount').val(0);
                            scopedField(context, '#credit_discount_amount').val('');
                            scopedField(context, '#credit_note').val('');

                            if (typeof saveFormDataToSession === 'function') {
                                saveFormDataToSession();
                            }

                            toastr.success('Added!');
                            if (typeof handlePaymentSuccessConfirmation === 'function') {
                                handlePaymentSuccessConfirmation();
                            }

                            // Some older modal housekeeping runs after the AJAX success
                            // callback. Re-assert the exact saved row in the same pane.
                            setTimeout(function () {
                                applyCreditSaleResponse(result, $button);
                            }, 150);
                        },
                        error: function (xhr) {
                            var message = xhr && xhr.responseJSON && xhr.responseJSON.msg
                                ? xhr.responseJSON.msg
                                : 'Unable to add credit sale';
                            toastr.error(message);
                        },
                        complete: function () {
                            $button.data('submitting', false).prop('disabled', false);
                        }
                    });
                });

                return false;
            });
    })(jQuery, window, document);
</script>
