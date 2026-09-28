<div class="row">
    <div class="col-md-12">

        <div class="row">
            <div class="col-md-3">
                <div class="form-group" style="width: 100% !important">
                    {!! Form::label('credit_sale_customer_id', __('petrodirect::lang.customer').':') !!}
                    <div class="input-group">
                        {!! Form::select('credit_sale_customer_id', !empty($only_walkin) ? $walkin : $credit_customers, null, ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
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
                    {!! Form::label('order_number', __('petrodirect::lang.order_number')) !!}
                    {!! Form::text('order_number', $business->duplicate_orders_allowed ? 0 : null, [
                        'class' => 'form-control credit_sale_fields order_number',
                        'placeholder' => __('petrodirect::lang.order_number'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {{--
                        S693: defaults to TODAY, and posts an unambiguous value.

                        The field was Form::text('order_date', null, ...) - no
                        default at all. An empty date input makes the browser's
                        native picker open at its own minimum, which is why the
                        calendar showed AUGUST 1914.

                        type="date" with today's date fixes both halves of it:
                        the picker opens on the current month, and the value
                        posts as Y-m-d rather than a formatted string that could
                        be read as either d/m/Y or m/d/Y - the ambiguity that
                        swapped day and month on the F22 form.

                        The browser still DISPLAYS the user's own locale format,
                        so nothing changes on screen.
                    --}}
                    {!! Form::label('order_date', __('petrodirect::lang.order_date')) !!}
                    {!! Form::input('date', 'order_date', date('Y-m-d'), [
                        'class' => 'form-control order_date',
                    ]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_reference', __('petrodirect::lang.select_customer_vehicle_no')) !!}
                    {!! Form::select('customer_reference', [], null, [
                        'class' => 'form-control credit_sale_fields select2 customer_reference',
                        'required',
                        'id' => 'customer_reference',
                        'style' => 'width: 100%',
                        'placeholder' => __('petrodirect::lang.please_select'),
                    ]) !!}
                </div>
            </div>

            <div class="clearfix"></div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_product_id', __('petrodirect::lang.credit_sale_product').':') !!}
                    {!! Form::select('credit_sale_product_id', $products, null, [
                        'class' => 'form-control select2',
                        'style' => 'width: 100%;',
                        'placeholder' => __('petrodirect::lang.please_select'),
                    ]) !!}
                </div>
                <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_price', __('petrodirect::lang.unit_price')) !!}
                    {!! Form::text('unit_price', null, [
                        'class' => 'form-control input_number unit_price',
                        'readonly',
                        'placeholder' => __('petrodirect::lang.unit_price'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_discount', __('petrodirect::lang.unit_discount')) !!}
                    {!! Form::text('unit_discount', null, [
                        'class' => 'form-control input_number unit_discount',
                        'disabled' => true,
                        'placeholder' => __('petrodirect::lang.unit_discount'),
                    ]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('credit_sale_qty', __('petrodirect::lang.credit_sale_qty')) !!}
                    {!! Form::text('credit_sale_qty', null, [
                        'class' => 'form-control credit_sale_fields input_number credit_sale_qty',
                        'placeholder' => __('petrodirect::lang.credit_sale_qty'),
                        'disabled' => true,
                    ]) !!}
                    <input type="hidden" name="credit_sale_qty_hidden" value="0" id="credit_sale_qty_hidden">
                </div>
            </div>

            <div class="clearfix"></div>
            <input type="hidden" name="total_amount_enable" id="total_amount_enable" value="0">

            <div class="col-md-3">
                {!! Form::label('credit_total_amount', __('petrodirect::lang.amount') . __('petrodirect::lang.before_discount_cr')) !!}
                {!! Form::text('credit_total_amount', null, [
                    'id' => 'credit_total_amount',
                    'class' => 'form-control credit_sale_fields cust_input_number credit_total_amount',
                    'required',
                    'disabled' => true,
                    'placeholder' => __('petrodirect::lang.credit_total_amount'),
                ]) !!}
            </div>

            <div class="col-md-3 hidden">
                {!! Form::text('credit_sale_amount', null, [
                    'id' => 'credit_sale_amount',
                    'class' => 'form-control credit_sale_fields cust_input_number credit_sale_amount',
                    'required',
                    'disabled' => true,
                    'placeholder' => __('petrodirect::lang.amount'),
                ]) !!}
                <input type="hidden" name="credit_sale_amount_hidden" value="0" id="credit_sale_amount_hidden">
            </div>

            <div class="col-md-3">
                {!! Form::label('credit_discount_amount', __('petrodirect::lang.credit_discount_amount')) !!}
                {!! Form::text('credit_discount_amount', null, [
                    'class' => 'form-control credit_sale_fields cust_input_number credit_discount_amount',
                    'required',
                    'disabled' => true,
                    'placeholder' => __('petrodirect::lang.credit_discount_amount'),
                ]) !!}
            </div>

            <div class="col-md-3">
                {!! Form::label('customer_reference_one_time', __('petrodirect::lang.enter_customer_vehicle_no')) !!}
                {!! Form::text('customer_reference', null, [
                    'class' => 'form-control customer_reference_one_time',
                    'id' => 'customer_reference_one_time',
                    'placeholder' => __('petrodirect::lang.enter_customer_vehicle_no'),
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
                @lang('petrodirect::lang.current_outstanding'):
                <span class="current_outstanding"></span>
            </div>

            <div class="col-md-4 text-red" style="font-size: 18px; font-weight: bold;">
                @lang('petrodirect::lang.credit_limit'):
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

                    <th>@lang('petrodirect::lang.cusotmer_name')</th>

                    <th>@lang('petrodirect::lang.outstanding')</th>

                    <th>@lang('petrodirect::lang.limit')</th>

                    <th>@lang('petrodirect::lang.order_no')</th>

                    <th>@lang('petrodirect::lang.order_date')</th>

                    <th>Vehicle No</th>

                    <th>@lang('petrodirect::lang.product')</th>

                    <th>@lang('petrodirect::lang.unit_price')</th>

                    <th>@lang('petrodirect::lang.qty')</th>

                    <th>@lang('petrodirect::lang.sub_total')</th>

                    <th>@lang('petrodirect::lang.discount_total')</th>

                    <th>@lang('petrodirect::lang.total')</th>

                    <th>@lang('lang_v1.note') </th>

                    <th>@lang('petrodirect::lang.action')</th>

                </tr>

            </thead>

            <tbody id="credit_sale_table_body">

                @foreach ($settlement_credit_sale_payments as $credit_sale_payment)
                    <tr data-credit-sale-id="{{ $credit_sale_payment->id }}" data-order-date="{{ $credit_sale_payment->order_date }}">

                        <td>{{ $credit_sale_payment->customer_name }}</td>

                        <td>{{ @num_format($credit_sale_payment->outstanding) }}</td>

                        <td>{{ @num_format($credit_sale_payment->credit_limit) }}</td>

                        <td>{{ $credit_sale_payment->order_number }}</td>

                        <td>{{ $credit_sale_payment->order_date }}</td>

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
                                data-href="/petrodirect/settlement/payment/delete-credit-sale-payment/{{ $credit_sale_payment->id }}"><i
                                    class="fa fa-times"></i></button></td>

                    </tr>
                @endforeach

            </tbody>



            <tfoot>

                <tr>

                    <td colspan="9" style="text-align: right; font-weight: bold;">@lang('petrodirect::lang.total') :</td>

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
                    url: "/petrodirect/settlement/payment/check-order-number",
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

        $("#credit_sale_customer_id").val($("#credit_sale_customer_id option:eq(0)").val()).trigger('change');

        // IS1638: keep Credit Sales order date exactly as selected by the user.
        // Use yyyy-mm-dd to match the settlement tables and avoid converting the selected
        // date back to the current date after Add.
        $('#order_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });

        // Set initial date to today only when the field is empty. Do not overwrite a
        // manually selected date when the tab is re-rendered.
        if (!$('#order_date').val()) {
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = ('0' + (today.getMonth() + 1)).slice(-2);
            var dd = ('0' + today.getDate()).slice(-2);
            $('#order_date').val(yyyy + '-' + mm + '-' + dd);
        }

        // S300/IS1552 follow-up: Select2 inside Add Payment modal must open under the field.
        // Using document.body or an unresolved modal parent makes the customer dropdown render at the top
        // of the Add Payment modal. Keep the dropdown inside the active Add Payment modal/form group.
        function initPetroCreditSaleSelect2(selector) {
            var $field = $(selector);
            if (!$field.length) {
                return;
            }

            if ($field.data('select2')) {
                $field.select2('destroy');
            }

            var $dropdownParent = $field.closest('.add_payment');
            if (!$dropdownParent.length) {
                $dropdownParent = $field.closest('.modal');
            }
            if (!$dropdownParent.length) {
                $dropdownParent = $field.closest('.form-group');
            }
            if (!$dropdownParent.length) {
                $dropdownParent = $(document.body);
            }

            $field.select2({
                width: '100%',
                dropdownParent: $dropdownParent
            });
        }

        initPetroCreditSaleSelect2('#credit_sale_product_id');
        initPetroCreditSaleSelect2('#credit_sale_customer_id');
        initPetroCreditSaleSelect2('#customer_reference');

        $(document).off('select2:open.petroCreditSaleDropdownFix', '#credit_sale_customer_id')
            .on('select2:open.petroCreditSaleDropdownFix', '#credit_sale_customer_id', function () {
                // Prevent Select2 from visually keeping the customer list in the "above" style.
                setTimeout(function () {
                    $('.select2-dropdown').removeClass('select2-dropdown--above').addClass('select2-dropdown--below');
                }, 0);
            });

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
    // Credit Sale payments: update the currently visible table from the
    // authoritative saved row returned by the server. This avoids writing into
    // an inactive/hidden tab and removes the former refresh requirement.
    (function ($) {
        'use strict';

        function creditNumber(value) {
            var number = parseFloat(value);
            if (!isFinite(number)) {
                number = 0;
            }

            return __number_f(number, false, false, __currency_precision);
        }

        function visibleCreditSaleBody() {
            var $modal = $('.add_payment:visible').last();
            var $body = $modal.find('#credit_sale_table_body').first();

            if (!$body.length) {
                $body = $('#credit_sale_table_body:visible').first();
            }
            if (!$body.length) {
                $body = $('#credit_sale_table_body').first();
            }

            return $body;
        }

        function creditLimitDisplay(value) {
            var text = value == null ? '' : String(value).trim();
            if (text === '') {
                return '';
            }

            var numeric = parseFloat(text.replace(/,/g, ''));
            return isFinite(numeric) && /^[-+]?\d[\d,]*(\.\d+)?$/.test(text)
                ? creditNumber(numeric)
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

        window.petrodirectApplyCreditSaleResponse = function (result) {
            if (!result || !result.success || !result.payment) {
                return;
            }

            var payment = result.payment;
            var paymentId = parseInt(payment.id || result.settlement_credit_sale_payment_id, 10);
            var $body = visibleCreditSaleBody();

            if (!paymentId || !$body.length) {
                return;
            }

            $body.find('tr[data-credit-sale-id="' + paymentId + '"]').remove();

            var $row = $('<tr>')
                .attr('data-credit-sale-id', paymentId)
                .attr('data-order-date', payment.order_date || result.order_date || '');

            appendTextCell($row, payment.customer_name || '');
            appendTextCell($row, creditNumber(payment.outstanding));
            appendTextCell($row, creditLimitDisplay(payment.credit_limit));
            appendTextCell($row, payment.order_number || '');
            appendTextCell($row, payment.order_date || result.order_date || '');
            appendTextCell($row, payment.customer_reference || '');
            appendTextCell($row, payment.product_name || '');
            appendTextCell($row, creditNumber(payment.price));
            appendTextCell($row, creditNumber(payment.qty));
            appendTextCell($row, creditNumber(payment.amount), 'credit_sale_amount');
            appendTextCell($row, creditNumber(payment.total_discount), 'credit_tbl_discount_amount');
            appendTextCell($row, creditNumber(payment.sub_total), 'credit_tbl_total_amount');
            appendTextCell($row, payment.note || '');

            var $deleteCell = $('<td>');
            $('<button>', {
                type: 'button',
                class: 'btn btn-xs btn-danger delete_credit_sale_payment',
                'data-href': '/petrodirect/settlement/payment/delete-credit-sale-payment/' + paymentId,
                'aria-label': 'Delete credit sale'
            }).append($('<i>', { class: 'fa fa-times' })).appendTo($deleteCell);
            $row.append($deleteCell);

            $body.prepend($row);

            if (result.totals) {
                $('.credit_sale_total').text(creditNumber(result.totals.gross));
                $('.credit_tb_discount_total').text(creditNumber(result.totals.discount));
                $('.credit_tbl_amount_total').text(creditNumber(result.totals.net));
                $('#credit_sale_total').val(parseFloat(result.totals.net || 0));
            } else {
                calculateTotal('#credit_sale_table', '.credit_sale_amount', '.credit_sale_total');
                calculateTotal('#credit_sale_table', '.credit_tbl_discount_amount', '.credit_tb_discount_total');
                calculateTotal('#credit_sale_table', '.credit_tbl_total_amount', '.credit_tbl_amount_total');
            }

            window.__petroDirectLastCreditSaleResponse = result;

            if (typeof window.syncActivePaymentRows === 'function') {
                window.syncActivePaymentRows($body.closest('.s271-direct-payment-tabs'));
            }
        };
    })(jQuery);

    // credit_sale payments

    // $(document).on("click", ".credit_sale_add", function () {
    $(document).off("click", ".credit_sale_add").on("click", ".credit_sale_add", function() {

        console.log('789');

        var $btn = $(this);
        if ($btn.data('submitting')) {
            return false;
        }
        $btn.data('submitting', true).prop('disabled', true);

        credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;
        if (credit_total_amount <= 0) {
            toastr.error("Please enter amount");
            $btn.data('submitting', false).prop('disabled', false);
            return false;
        }

        var credit_sale_customer_id = $("#credit_sale_customer_id").val();

        var customer_name = $("#credit_sale_customer_id :selected").text();

        var credit_sale_product_id = $("#credit_sale_product_id").val();

        var credit_sale_product_name = $("#credit_sale_product_id :selected").text();

        if ($("#customer_reference_one_time").val() !== "" && $("#customer_reference_one_time").val() !==
            null && $("#customer_reference_one_time").val() !== undefined) {

            var customer_reference = $("#customer_reference_one_time").val();

        } else {

            var customer_reference = $("#customer_reference").val();

        }

        var settlement_no = $("#settlement_no").val();

        var order_date = $("#order_date").val();

        var order_number = $("#order_number").val();

        var pump_operator_id = $('#pump_operator_id').val();



        console.log(pump_operator_id);



        var credit_sale_price = __read_number($("#unit_price"));

        var credit_unit_discount = __read_number($("#unit_discount")) ?? 0;

        var credit_sale_qty = __read_number($("#credit_sale_qty")) ?? 0;

        var credit_total_amount = __read_number($("#credit_total_amount")) ?? 0;

        var credit_total_discount = __read_number($("#credit_discount_amount")) ?? 0;

        var credit_sub_total = credit_total_amount - credit_total_discount;
        $("#credit_sale_amount").val(credit_sub_total);



        var outstanding = $(".current_outstanding").text();

        var credit_limit = $(".credit_limit").text();

        var credit_note = $("#credit_note").val();

        var is_edit = $("#is_edit").val() ?? 0;



        swal({
            title: "Add Credit sale?",
            text: "Are you sure you want to add this Credit sale?",
            icon: "warning",
            buttons: { cancel: "No", confirm: { text: "Yes", value: true } },
            dangerMode: false,
        }).then(function(confirmed) {
            if (!confirmed) {
                $btn.data('submitting', false).prop('disabled', false);
                return;
            }

            $.ajax({

                method: "post",

                url: "/petrodirect/settlement/payment/save-credit-sale-payment",

                data: {

                    settlement_no: settlement_no,
                    scsp_id: $("#scsp_id").val(),

                    customer_id: credit_sale_customer_id,

                    product_id: credit_sale_product_id,

                    order_number: order_number,

                    order_date: order_date,

                    pump_operator_id: pump_operator_id,

                    price: credit_sale_price,

                    unit_discount: credit_unit_discount,

                    qty: credit_sale_qty,

                    amount: credit_total_amount,

                    sub_total: credit_sub_total,

                    total_discount: credit_total_discount,

                    outstanding: outstanding,

                    credit_limit: credit_limit,

                    customer_reference: customer_reference,

                    note: credit_note,

                    is_edit: is_edit,

                    transaction_date: $('#transaction_date').val() || $('.transaction_date').val(),

                    active_settlement_id: $('#active_settlement_id').val() || 0,

                    type: 'settlement'

                },

                success: function(result) {

                    if (!result.success) {

                        toastr.error(result.msg);
                        $btn.data('submitting', false).prop('disabled', false);

                    } else {

                        settlement_credit_sale_payment_id = result.settlement_credit_sale_payment_id;

                        // Insert the exact saved row into the visible Credit Sales table.
                        // This also displays the selected Order Date immediately.
                        if (typeof window.petrodirectApplyCreditSaleResponse === 'function') {
                            window.petrodirectApplyCreditSaleResponse(result);
                        }

                        var display_order_date = (result.payment && result.payment.order_date)
                            ? result.payment.order_date
                            : (result.order_date || order_date);

                        const creditNetAmount = (result.payment && result.payment.sub_total !== undefined)
                            ? parseFloat(result.payment.sub_total || 0)
                            : credit_sub_total;
                        const isDirectSettlement = ($('#is_direct_settlement').val() === '1');
                        if (isDirectSettlement && typeof add_sale_amount === 'function') {
                            add_sale_amount(creditNetAmount);
                        } else {
                            add_payment(creditNetAmount);
                        }

                        /*
                         * IS2134: refresh the Finalize Settlement button now.
                         *
                         * With the balance down to zero, Finalize Settlement did
                         * not appear until the page was reloaded.
                         *
                         * add_sale_amount() ends by calling
                         * show_hide_excess_shortage_tab(), which is what shows the
                         * button - but add_sale_amount is declared inside the
                         * add_payment view's own closure and is NOT exposed on
                         * window. This partial is a separate file, so the guard
                         * above ("typeof add_sale_amount === 'function'") is false
                         * here and the call is silently skipped. The balance was
                         * updated on screen by other means, while the button that
                         * depends on it never re-evaluated.
                         *
                         * show_hide_excess_shortage_tab IS exposed on window
                         * (add_payment.blade.php sets window.show_hide_excess_
                         * shortage_tab), so it can be called directly from here.
                         * That single call re-runs the balance check and shows or
                         * hides Finalize Settlement accordingly.
                         *
                         * Guarded on existence: this partial is also used by
                         * screens that have no such function, and a missing one
                         * must not break saving a credit sale.
                         */
                        if (typeof window.show_hide_excess_shortage_tab === 'function') {
                            window.show_hide_excess_shortage_tab();
                        }

                        $("#customer_reference_one_time").val("").trigger("change");

                        // Save last customer and order number for next entry
                        var last_customer_id = credit_sale_customer_id;
                        var last_order_number = order_number;

                        $(".credit_sale_fields").val("");
                        $("#credit_sale_amount").val(0);

                        $(".cash_fields").val("");

                        $("#credit_sale_product_id").val(null).trigger('change');
                        // DON'T reset customer_id - keep the last entered customer name
                        // $("#credit_sale_customer_id").val($("#credit_sale_customer_id option:eq(0)").val()).trigger('change');
                        
                        // DON'T reset order_number - keep the last entered order number
                        // $("#order_number").val("");
                        
                        // Keep the selected order_date. Operators may add many credit sales
                        // for the same selected order date and the visible table must continue
                        // showing that selected date, not today's date.
                        if (!$("#order_date").val()) {
                            $("#order_date").val(display_order_date || order_date);
                        }
                        $("#customer_reference").val(null).trigger('change');
                        $("#unit_price").val("");
                        $("#unit_discount").val("");
                        $("#credit_sale_qty").val("");
                        $("#credit_total_amount").val("");
                        $("#credit_discount_amount").val("");
                        $("#credit_note").val("");
                        $("#credit_sale_qty_hidden").val(0);

                        calculateTotal("#credit_sale_table", ".credit_sale_amount",
                            ".credit_sale_total");

                        calculateTotal("#credit_sale_table", ".credit_tbl_discount_amount",
                            ".credit_tb_discount_total");

                        calculateTotal("#credit_sale_table", ".credit_tbl_total_amount",
                            ".credit_tbl_amount_total");

                        // handlePaymentSuccess();
                        
                        // Save form data to session after successful credit sale
                        if (typeof saveFormDataToSession === 'function') {
                            saveFormDataToSession();
                        }

                        toastr.success("Added!");
                        // Keep the currently rendered payment tab/template in sync so the
                        // newly added row remains visible immediately when moving between tabs,
                        // without requiring a full page refresh.
                        if (typeof window.syncActivePaymentRows === 'function') {
                            window.syncActivePaymentRows($('#credit_sale_table').closest('.s271-direct-payment-tabs'));
                        }
                        if (typeof handlePaymentSuccessConfirmation === 'function') {
                            handlePaymentSuccessConfirmation();
                        }

                        // A few older installations run modal/tab housekeeping after
                        // the AJAX callback. Re-apply the same authoritative row after
                        // that cycle so it cannot disappear until a full refresh.
                        setTimeout(function () {
                            if (typeof window.petrodirectApplyCreditSaleResponse === 'function') {
                                window.petrodirectApplyCreditSaleResponse(result);
                            }
                        }, 150);

                        $btn.data('submitting', false).prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    var message = 'Unable to save Credit Sale. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        message = xhr.responseJSON.msg;
                    }
                    toastr.error(message);
                    $btn.data('submitting', false).prop('disabled', false);
                },

            });
        });

    });
</script>
