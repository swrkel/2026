@extends('layouts.' . $layout)
@section('title', __('pumperdashboard::lang.close_shift'))
@section('content')
    @include('pumperdashboard::partials.pumper_dashboard_ui_standard')


    <!-- Content Header (Page header) -->
    <section class="content-header pumper-ui-standard">
        @if (!empty($shift) && $shift->status !== 2)
            <div>
                <h1>@lang('pumperdashboard::lang.close_shift') <br>
                    <span class="text-red">{{ $pump_operator_name }}</span>
                </h1>
                <h2 style="color: red;">Shift NO: {{ $shift_number }}</h2>
            </div>
        @elseif (empty($shift))
            <div>
                <h1>@lang('pumperdashboard::lang.close_shift') <br>
                    <span class="text-red">{{ $pump_operator_name }}</span>
                </h1>
            </div>
        @endif
        <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}" class="btn btn-flat btn-lg pull-right"
            style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('pumperdashboard::lang.logout')</a>
        <a href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard') }}"
            class="btn btn-flat btn-lg pull-right"
            style="color: #fff; background-color:#810040; margin-left: 5px;">@lang('pumperdashboard::lang.dashboard')
        </a>
        <a data-href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@getPaymentSummaryModal', ['only_pumper' => true]) }}"
            class="btn btn-flat btn-lg pull-right btn-modal" data-container=".view_modal"
            style="color: #fff; background-color:#71b306;">@lang('pumperdashboard::lang.payment_summary')
        </a>

        @php
            /*
             * S281-001: On Close Shift page, Payment must follow the selected shift dropdown.
             * The previous server-side disabled state used the initially loaded/latest shift, so an
             * older closed shift could block the Payment button even when the selected shift is open.
             * Keep the button clickable and let JavaScript validate the currently selected shift.
             */
            $btn_style = 'margin-left: 10px; margin-right: 10px;';
            $closing_payment_url = action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@create');
            $closing_other_sale_url = action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@othersalespage');
        @endphp

        <a id="payment_btn_closing" href="{{ $closing_payment_url }}?only_pumper=1&from_closing_shift=1"
            class="btn btn-flat btn-lg pull-right btn-info" style="{{ $btn_style }}"
            data-payment-url="{{ $closing_payment_url }}"
            onclick="return openClosingShiftPayment(event, this);">@lang('pumperdashboard::lang.payment')
        </a>

        <a id="othersale_btn_closing" href="{{ $closing_other_sale_url }}?only_pumper=1&from_closing_shift=1"
            class="btn btn-flat btn-lg pull-right btn-danger" style="{{ $btn_style }}"
            data-other-sale-url="{{ $closing_other_sale_url }}"
            onclick="return openClosingShiftOtherSale(event, this);">@lang('pumperdashboard::lang.other_sales')
        </a>
    </section>
    <div class="clearfix"></div>
    @include('pumperdashboard::partials.closing_shift')
@endsection
@section('javascript')
    <script>
        $('#submit').click(function() {
            let amount = $('#amount').val();
            let payment_type = $('#payment_type').val();
            if (amount === '' || amount === undefined) {
                toastr.error('Please enter amount');
                return false
            }
            if (payment_type === '' || payment_type === undefined) {
                toastr.error('Please select payment type');
                return false
            }
            amount = parseFloat(amount);
            console.log(amount);
            $.ajax({
                method: 'POST',
                url: "{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorPaymentController@store') }}",
                data: {
                    amount,
                    payment_type
                },
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        reset();
                    } else {
                        toastr.error(result.msg)
                    }
                },
            });
        })
    </script>
    <script type="text/javascript">
        var body = document.getElementsByTagName("body")[0];
        body.className += " sidebar-collapse";
        if ($('#date_range').length == 1) {
            $('#date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
            });
            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#date_range').val('');
            });
            $('#date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
        }
        $(document).ready(function() {
            // IS1460_DATATABLE_SAFE_DEFAULTS: avoid user-facing DataTables alerts when users click headings.
            if ($.fn.dataTable) { $.fn.dataTable.ext.errMode = 'none'; }
            reloadClosingShift();
            toggleClosingButtons();

            $(document).on('click', '#settleBalance', function(e) {
                e.preventDefault();
                var pump_operator_id = @json(auth()->user()->pump_operator_id);
                var selected_shift_id = $('#closing_shift_id').val();
                var url = '/pumper-dashboard/pump-operator-payments/balance-to-operator/' + pump_operator_id + '?shift_id=' + selected_shift_id;
                var actionText = $.trim($(this).text()) || 'settle this balance';

                /*
                 * MA-002: the dialog now names the AMOUNT.
                 *
                 * It said only "continue with Shortage?" - the person
                 * confirming a settlement could not see how much they were
                 * agreeing to without going back to the table.
                 *
                 * The figure comes from the button's data-balance-amount,
                 * which the view writes from $balance_to_settle - the SAME
                 * value that decides which button appears at all. So the
                 * dialog cannot show a different number from the button.
                 */
                /*
                 * MA-002: the amount shown large and in red.
                 *
                 * SweetAlert's "text" option is PLAIN TEXT - it cannot carry
                 * colour or size. So the message is built as a DOM element and
                 * passed as "content", which the library renders as-is.
                 *
                 * WHY A DOM ELEMENT AND NOT AN HTML STRING: the figures come
                 * from a data attribute, and building HTML by concatenation is
                 * how an unexpected value ends up executing. textContent is
                 * set on each part, so nothing in the data can be interpreted
                 * as markup.
                 *
                 * If content is not supported for any reason, it falls back to
                 * the plain text message rather than showing nothing.
                 */
                var balanceAmount = $(this).data('balance-amount');
                var balanceKind = $(this).data('balance-kind') || actionText;
                var shiftNumber = $(this).data('shift-number');
                var confirmText = 'Are you sure you want to continue with ' + actionText + '?';
                var confirmContent = null;

                if (balanceAmount !== undefined && balanceAmount !== '') {
                    var pretty = (parseFloat(balanceAmount) || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    var kindWord = String(balanceKind).charAt(0).toUpperCase()
                        + String(balanceKind).slice(1);

                    var shiftLine = (shiftNumber !== undefined && shiftNumber !== '')
                        ? 'Shift ' + shiftNumber + ' - '
                        : '';

                    confirmText = shiftLine + kindWord + ': ' + pretty
                        + '. Are you sure you want to settle it?';

                    try {
                        var wrap = document.createElement('div');
                        wrap.style.textAlign = 'center';

                        /*
                         * MA-002: the shift number, so the figure can be
                         * checked against the right shift before settling.
                         *
                         * It comes from the button's data-shift-number, which
                         * the summary writes from $shift_number - read for the
                         * SAME shift_id the settlement applies to. So the
                         * number shown is the shift being settled, not merely
                         * the operator's latest.
                         */
                        if (shiftNumber !== undefined && shiftNumber !== '') {
                            var shiftEl = document.createElement('div');
                            shiftEl.textContent = 'Shift ' + shiftNumber;
                            shiftEl.style.fontSize = '15px';
                            shiftEl.style.fontWeight = '600';
                            shiftEl.style.color = '#333';
                            shiftEl.style.marginBottom = '10px';
                            wrap.appendChild(shiftEl);
                        }

                        var kindEl = document.createElement('div');
                        kindEl.textContent = kindWord;
                        kindEl.style.color = '#d32f2f';
                        kindEl.style.fontSize = '20px';
                        kindEl.style.fontWeight = '700';
                        kindEl.style.letterSpacing = '.5px';
                        kindEl.style.marginBottom = '2px';

                        var amountEl = document.createElement('div');
                        amountEl.textContent = pretty;
                        amountEl.style.color = '#d32f2f';
                        amountEl.style.fontSize = '34px';
                        amountEl.style.fontWeight = '700';
                        amountEl.style.lineHeight = '1.2';

                        var askEl = document.createElement('div');
                        askEl.textContent = 'Are you sure you want to settle it?';
                        askEl.style.marginTop = '12px';
                        askEl.style.fontSize = '15px';
                        askEl.style.color = '#333';

                        wrap.appendChild(kindEl);
                        wrap.appendChild(amountEl);
                        wrap.appendChild(askEl);
                        confirmContent = wrap;
                    } catch (e) {
                        confirmContent = null;
                    }
                }

                var swalOptions = {
                    title: 'Please confirm',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: false
                };

                if (confirmContent) {
                    swalOptions.content = confirmContent;
                } else {
                    swalOptions.text = confirmText;
                }

                /*
                 | IS2166: no reconfirmation.
                 |
                 | The operator has already chosen by clicking. The shift close
                 | page that follows shows every figure, and the print prompt
                 | comes after it - so a "are you sure" here only added a click
                 | to a decision already made.
                 |
                 | The note below about two separate acts no longer applies:
                 | settling now closes the shift, which is what was asked for.
                */
                (function() {

                    /*
                     * MA-002: settle, then PROMPT to close the shift.
                     *
                     * Settling a balance does not close the shift - it records
                     * a payment. And the Close Shift button is deliberately
                     * hidden while a balance is outstanding, so after settling
                     * the person was left on a page with no obvious next step
                     * and the shift silently stayed open. That is how shifts 5
                     * and 6 came to sit open after being settled.
                     *
                     * The settle still happens by navigation, exactly as
                     * before - nothing about recording the money changes. The
                     * page remembers that it must offer to close the shift,
                     * and does so once it has reloaded and confirmed the
                     * balance really is zero.
                     *
                     * A SEPARATE CONFIRMATION, deliberately. Closing a shift
                     * is final, and it should not happen as a side effect of
                     * settling. The person confirms the amount, then confirms
                     * closing - two acts, neither by accident.
                     */
                    try {
                        sessionStorage.setItem('pd_offer_close_shift', String(selected_shift_id || ''));
                    } catch (e) {
                        // Storage unavailable - the settle still proceeds.
                    }

                    window.location.href = url;
                })();
            });

            pump_operators_closing_shift_table = $('#pump_operators_closing_shift_table').DataTable({
                autoWidth: false,
                responsive: false,
                deferRender: true,
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ action('\Modules\PumperDashboard\Http\Controllers\ClosingShiftController@index', ['only_pumper' => true]) }}",
                    data: function(d) {
                        @if (empty(auth()->user()->pump_operator_id))
                            // d.start_date = $('input#date_range')
                            //     .data('daterangepicker')
                            //     .startDate.format('YYYY-MM-DD');
                            // d.end_date = $('input#date_range')
                            //     .data('daterangepicker')
                            //     .endDate.format('YYYY-MM-DD');
                            // d.location_id = $('#day_entries_location_id').val();
                            // d.pump_operator_id = $('#day_entries_pump_operators').val();
                            // d.pump_id = $('#day_entries_pumps').val();
                            // d.payment_method = $('#day_entries_payment_method').val();
                            // d.difference = $('#day_entries_difference').val();
                        @endif
                        d.shift_id = $("#closing_shift_id").val();
                        console.log($("#closing_shift_id").val());
                    },
                },
                columnDefs: [
                    {
                        targets: '_all',
                        defaultContent: ''
                    },
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        width: '125px',
                        className: 'pd-cs-action'
                    },
                    {
                        targets: 1,
                        width: '125px',
                        className: 'pd-cs-date'
                    },
                    {
                        targets: 2,
                        visible: false,
                        width: '175px',
                        className: 'pd-cs-location'
                    },
                    {
                        targets: 3,
                        width: '110px',
                        className: 'pd-cs-time'
                    },
                    {
                        targets: 4,
                        width: '170px',
                        className: 'pd-cs-operator'
                    },
                    {
                        targets: 5,
                        width: '100px',
                        className: 'pd-cs-shift'
                    },
                    {
                        targets: 6,
                        width: '130px',
                        className: 'pd-cs-pump'
                    },
                    {
                        targets: [7, 8],
                        width: '125px',
                        className: 'pd-cs-meter'
                    },
                    {
                        targets: [9, 10],
                        width: '110px',
                        className: 'pd-cs-qty'
                    },
                    {
                        targets: [11, 12],
                        width: '145px',
                        className: 'pd-cs-amount'
                    }
                ],
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'time',
                        name: 'time'
                    },
                    {
                        data: 'name',
                        name: 'pump_operators.name'
                    },
                    {
                        data: 'shift_number',
                        name: 'shift_number'
                    },
                    {
                        data: 'pump_no',
                        name: 'pumps.pump_no'
                    },
                    {
                        data: 'starting_meter',
                        name: 'starting_meter'
                    },
                    {
                        data: 'closing_meter',
                        name: 'closing_meter'
                    },
                    {
                        data: 'testing_ltr',
                        name: 'testing_ltr'
                    },
                    {
                        data: 'sold_ltr',
                        name: 'sold_ltr'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        searchable: false
                    },
                    {
                        data: 'short_amount',
                        name: 'short_amount',
                        searchable: false
                    },
                ],
                initComplete: function() {
                    var api = this.api();
                    window.setTimeout(function() {
                        api.columns.adjust();
                    }, 0);
                },
                fnDrawCallback: function(oSettings) {
                    var testing_ltr = sum_table_col($('#pump_operators_closing_shift_table'),
                        'testing_ltr');
                    $('#footer_cs_testing_ltr').text(testing_ltr);
                    var sold_ltr = sum_table_col($('#pump_operators_closing_shift_table'), 'sold_ltr');
                    $('#footer_cs_sold_ltr').text(sold_ltr);
                    var sold_amount = sum_table_col($('#pump_operators_closing_shift_table'),
                        'sold_amount');
                    $('#footer_cs_sold_amount').text(sold_amount);
                    var short_amount = sum_table_col($('#pump_operators_closing_shift_table'),
                        'short_amount');
                    $('#footer_cs_short_amount').text(short_amount);

                    /*
                     * MA-002: mirror that same figure into the banner above the
                     * table, so it cannot be missed in the last column.
                     *
                     * It uses short_amount as already summed above - ONE
                     * calculation, so the banner and the footer can never
                     * disagree.
                     *
                     * A shortage is shown in red, an excess in green. Which is
                     * which follows the system's own convention: the column
                     * holds the shortage as a positive figure and an excess as
                     * a negative one, matching how PumpOperatorPayment stores
                     * them.
                     */
                    (function () {
                        var raw = parseFloat(String(short_amount).replace(/,/g, '')) || 0;
                        var $banner = $('#cs_balance_banner');

                        if (!$banner.length) {
                            return;
                        }

                        $banner.removeClass('pd-balance-neutral pd-balance-short pd-balance-excess');

                        if (raw > 0.001) {
                            $banner.addClass('pd-balance-short');
                            $('#cs_balance_note').text('Shortage');
                        } else if (raw < -0.001) {
                            $banner.addClass('pd-balance-excess');
                            $('#cs_balance_note').text('Excess');
                        } else {
                            $banner.addClass('pd-balance-neutral');
                            $('#cs_balance_note').text('Balanced');
                        }

                        // The figure itself is shown without its sign - the
                        // word and the colour say which way it goes.
                        $('#cs_balance_value').text(
                            Math.abs(raw).toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            })
                        );
                    })();
                    __currency_convert_recursively($('#pump_operators_closing_shift_table'));
                    var closingShiftApi = this.api();
                    window.setTimeout(function() {
                        closingShiftApi.columns.adjust();
                    }, 0);
                },
                'footerCallback': function(row, data, start, end, display) {
                    var testQtyTotal = data.reduce(function(total, row) {
                        var value = $('<div>').html(row.testing_ltr || '').find('.testing_ltr').data('orig-value');

                        if (value === undefined) {
                            value = row.testing_ltr;
                        }

                        return total + (__number_uf(value) || 0);
                    }, 0);

                    $('#footer_cs_testing_ltr').text(__number_f(testQtyTotal, false, false, __currency_precision));
                },
            });
            $(document).off('column-visibility.dt.pdCloseShift', '#pump_operators_closing_shift_table')
                .on('column-visibility.dt.pdCloseShift', '#pump_operators_closing_shift_table', function() {
                    if ($.fn.DataTable.isDataTable('#pump_operators_closing_shift_table')) {
                        $('#pump_operators_closing_shift_table').DataTable().columns.adjust();
                    }
                });

            $('#day_entries_location_id, #day_entries_pump_operator, #day_entries_pump_operator, #day_entries_payment_method, #day_entries_date_range, #day_entries_difference, #closing_shift_id')
                .change(function() {
                    pump_operators_closing_shift_table.ajax.reload();
                    reloadClosingShift();
                    toggleClosingButtons();
                });
        });

        function selectedClosingShiftStatus() {
            return String($('#closing_shift_id option:selected').attr('data-status') || '');
        }

        function selectedClosingShiftId() {
            return $('#closing_shift_id').val() || '';
        }

        function selectedClosingShiftClosed() {
            return selectedClosingShiftStatus() === '2';
        }

        function toggleClosingButtons() {
            if (selectedClosingShiftClosed()) {
                $('#payment_btn_closing, #othersale_btn_closing').css({
                    'opacity': '0.5'
                }).attr('data-shift-closed', '1');
            } else {
                $('#payment_btn_closing, #othersale_btn_closing').css({
                    'opacity': '1'
                }).attr('data-shift-closed', '0');
            }
        }

        function openClosingShiftPayment(e, el) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            if (selectedClosingShiftClosed()) {
                toastr.error('Shift is closed. Cannot perform Payment at this time.');
                return false;
            }

            var url = $(el).data('payment-url') || $(el).attr('href');
            var shiftId = selectedClosingShiftId();
            var glue = url.indexOf('?') === -1 ? '?' : '&';
            window.location.href = url + glue + 'only_pumper=1&from_closing_shift=1&shift_id=' + encodeURIComponent(shiftId);
            return false;
        }

        function openClosingShiftOtherSale(e, el) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            if (selectedClosingShiftClosed()) {
                toastr.error('Shift is closed. Cannot perform Other Sales at this time.');
                return false;
            }

            var url = $(el).data('other-sale-url') || $(el).attr('href');
            var shiftId = selectedClosingShiftId();
            var glue = url.indexOf('?') === -1 ? '?' : '&';
            window.location.href = url + glue + 'only_pumper=1&from_closing_shift=1&shift_id=' + encodeURIComponent(shiftId);
            return false;
        }


        function reloadClosingShift() {
            $("#closing_shift_summary").empty();

            $.ajax({
                method: 'GET',
                url: '{{ action('\Modules\PumperDashboard\Http\Controllers\PumperDayEntryController@getClosingShiftSummary') }}',
                dataType: 'html',
                data: {
                    'shift_id': $("#closing_shift_id").val(),
                    'only_pumper': 1
                },
                success: function(result) {
                    $("#closing_shift_summary").html(result);
                    pdOfferCloseShiftIfSettled();
                    pdOfferShiftClosePrint();
                },
            });
        }

        /*
         * MA-002: after a settlement, offer to close the shift.
         *
         * Settling records a payment; it does not close the shift. And the
         * Close Shift button is hidden while a balance is outstanding, so the
         * person was left with nothing to click and the shift stayed open.
         *
         * This runs after the summary has RELOADED, so it is reading the
         * balance as it now stands - not as it was before the settlement.
         *
         * IT ONLY OFFERS WHEN THE BUTTON IS GENUINELY AVAILABLE. If the
         * balance is still outstanding, or pumps are unreceived, the button is
         * absent or hidden and no prompt appears - the page's own rules about
         * when a shift may be closed are untouched.
         */
        function pdOfferCloseShiftIfSettled() {
            var flagged;

            try {
                flagged = sessionStorage.getItem('pd_offer_close_shift');
            } catch (e) {
                return;
            }

            if (flagged === null || flagged === undefined) {
                return;
            }

            // Once only - clear it before doing anything else.
            try {
                sessionStorage.removeItem('pd_offer_close_shift');
            } catch (e) {
                // Nothing to do.
            }

            var $btn = $('#close_shift_btn');

            if (!$btn.length || $btn.hasClass('hide') || !$btn.is(':visible')) {
                return;
            }

            var closeUrl = $btn.attr('href');

            if (!closeUrl || closeUrl === '#') {
                return;
            }

            /*
             | IS2166: close it, do not ask.
             |
             | Settling a balance ends the operator's shift - there is nothing
             | further for them to do, and a shift left open after settling is
             | how shifts 5 and 6 came to sit open. The close page that follows
             | shows every figure, and offers the print.
            */
            window.location.href = closeUrl;
        }

        /*
         | IS2166: offer the print once the shift has closed.
         |
         | The id comes from the controller's success output, so this fires only
         | after a shift really closed - not on every page load.
        */
        function pdOfferShiftClosePrint() {
            var shiftId = '{{ session('status.closed_shift_id') }}';
            if (!shiftId) { return; }

            swal({
                title: 'Shift closed',
                text: 'Print the shift close statement?',
                icon: 'success',
                buttons: ['No', 'Print'],
                dangerMode: false
            }).then(function(ok) {
                if (ok) {
                    window.open('/pumper-dashboard/closing-shift/summary-print/' + shiftId, '_blank');
                }
            });
        }


        $(document).on('shown.bs.modal', '.view_modal', function() {
            $('#amount').focus();
        });

        other_sale_code = null;
        other_sale_product_name = null;
        other_sale_price = 0.0;
        other_sale_qty = 0.0;
        other_sale_discount = 0.0;
        other_sale_total = parseFloat($('#other_sale_total').val());
        $(document).on('change', '#item', function() {
            let item_id = $(this).val();

            if (item_id) {
                $.ajax({
                    method: 'get',
                    url: '/pumper-dashboard/settlement/get_balance_stock_by_id/' + item_id,
                    data: {
                        store_id: $("select#store_id option").filter(":selected").val(),
                        location_id: $("#location_id").val()
                    },
                    success: function(result) {
                        console.log(result);

                        $('#balance_stock').val(result.balance_stock);
                        $('#other_sale_price').val(result.price);
                        other_sale_code = result.code;
                        other_sale_product_name = result.product_name;
                        other_sale_price = result.price;
                    },
                });
            }

        });

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        $(document).on('click', '.btn_other_sale', function(e) {
            e.preventDefault();

            var allowoverselling = $("#allowoverselling").val();
            if (parseFloat(other_sale_qty) > parseFloat(balance_stock) && allowoverselling != true) {
                toastr.error('Out of Stock');
                $(this).val('').focus();
                return false;
            }

            var other_sale_discount = $('#other_sale_discount').val();
            var other_sale_discount_type = $('#other_sale_discount_type').val();
            var other_sale_qty = $('#other_sale_qty').val();
            var balance_stock = $('#balance_stock').val();
            var sub_total = parseFloat(other_sale_qty) * parseFloat(other_sale_price);
            if (!other_sale_discount_type) {
                other_sale_discount_type = 'fixed';
            }
            var other_sale_discount_amount = calculate_discount(other_sale_discount_type, other_sale_discount,
                sub_total);

            var other_sale_id = null;
            let sub = parseFloat(sub_total);
            let other_sale_total = parseFloat($('#other_sale_total').val().replace(',', ''));

            let with_discount = sub_total - other_sale_discount_amount;

            other_sale_total = other_sale_total + with_discount;

            $.ajax({
                method: 'post',
                url: '/pumper-dashboard/pump-operator-pmts/save-other-sale',
                data: {
                    shift_id: $('#shift_id').val(),
                    location_id: $('#location_id').val(),
                    pump_operator_id: $('#pump_operator_id').val(),
                    product_id: $('#item').val(), //item is product in whole page
                    store_id: $('#store_id').val(),
                    price: other_sale_price,
                    qty: other_sale_qty,
                    balance_stock: balance_stock,
                    discount: other_sale_discount,
                    discount_type: other_sale_discount_type,
                    discount_amount: other_sale_discount_amount,
                    sub_total: sub,
                },
                success: function(result) {
                    if (!result.success) {
                        toastr.error(result.msg);
                        return false;
                    }
                    $('#other_sale_total').val(other_sale_total);

                    other_sale_id = result.other_sale_id;
                    sub_total = __number_f(sub_total);
                    $('#other_sale_table tbody').prepend(
                        `
                <tr> 
                    <td>` + other_sale_code + `</td>
                    <td>` + other_sale_product_name + `</td>
                    <td>` + balance_stock + `</td>
                    <td>` + __number_f(other_sale_price) + `</td>
                    <td>` + other_sale_qty + `</td>
                    <td>` + capitalizeFirstLetter(other_sale_discount_type) + `</td>
                    <td>` + __number_f(other_sale_discount) + `</td>
                    <td>` + sub_total + `</td>
                    <td>` + __number_f(with_discount) +
                        `</td>
                    <td><button class="btn btn-xs btn-danger delete_other_sale" data-href="/pumper-dashboard/settlement/delete-other-sale/` +
                        other_sale_id +
                        `"><i class="fa fa-times"></i></button>
                    </td>
                </tr>
            `
                    );
                    $('.other_sale_fields').val('').trigger('change');

                    calculate_payment_tab_total();
                },
            });
        });

        $(document).on('click', '.delete_other_sale', function() {
            url = $(this).data('href');
            tr = $(this).closest('tr');
            var is_edit = $("#is_edit").val() ?? 0;

            $.ajax({
                method: 'delete',
                url: url,
                data: {
                    is_edit
                },
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        tr.remove();
                        let other_sale_total =
                            parseFloat($('#other_sale_total').val().replace(',', '')) -
                            parseFloat(result.amount);
                        other_sale_total_text = __number_f(
                            other_sale_total,
                            false,
                            false,
                            __currency_precision
                        );
                        $('.other_sale_total').text(other_sale_total_text);
                        $('#other_sale_total').val(other_sale_total);
                        calculate_payment_tab_total();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        function calculate_discount(discount_type, discount_value, amount) {
            if (discount_type == 'fixed') {
                return parseFloat(discount_value) || 0;
            }
            if (discount_type == 'percentage') {
                return ((amount * parseFloat(discount_value)) / 100) || 0;
            }
            return 0;
        }


        $(document).on('change', '#store_id', function() {
            let location_id = $('#location_id').val();
            let store_id = $(this).val();
            let tab = 'other_sat';


            $.ajax({
                method: 'get',
                url: "/pumper-dashboard/get-products-by-store-id",
                data: {
                    'location_id': location_id,
                    'store_id': store_id,
                    'tab': tab
                },
                contentType: 'html',
                success: function(result) {
                    $('#item').empty().append(result);
                },
            });
        });

        function calculate_payment_tab_total() {
            let other_sale_totals = parseFloat($('#other_sale_total').val());

            $('.payment_other_sale_total').text(
                __number_f(other_sale_totals, false, false, __currency_precision)
            );

            $('.other_sale_total').text(__number_f(other_sale_totals, false, false, __currency_precision));
        }
    </script>
@endsection




