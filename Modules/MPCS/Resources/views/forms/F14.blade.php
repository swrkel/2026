

@extends('layouts.app')
@section('title', __('mpcs::lang.F14_form'))

@section('content')

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<!-- Main content -->
<section class="content">
    <div class="page-title-area" id="app">
        <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('mpcs::lang.F14_form')])
        <div class="col-md-3" id="location_filter">
            <div class="form-group">
                {{--
                    IS-1931: the label pointed at "f14b_location_id", an id that
                    does not exist on this page, so clicking it did nothing.

                    The id below is deliberately NOT named *_location_id.
                    public/js/global-location-dropdown.js matches selects by
                    name/id (location, business_location, business_location_id,
                    *_location_id) and force-wraps every match in select2. This
                    select is driven by Vue v-model; a select2 wrapper over it
                    would fight the re-render and break the selection. Keeping
                    the id outside that pattern leaves the control Vue-owned.
                --}}
                {!! Form::label('f14_business_location_filter', __('purchase.business_location') . ':') !!}
                <br />
               <select v-model="filter.business_location_id"
                        id="f14_business_location_filter"
                        class="form-select filter-select"
                        @change="setBusinessLocation">

                    <option :value="null">ALL</option>

                    <template v-for="(business_location,id) in business_locations">
                        <option :value="id">@{{ business_location }}</option>
                    </template>
                </select>
                
                
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="type">Date:</label>
                {{--
                    MA-002 (IS-1904 #1): v-model REMOVED from this input, deliberately.

                    It had two owners. The jQuery daterangepicker writes the chosen
                    date straight into the DOM with .val(), and Vue's v-model does not
                    observe that - so on the next re-render Vue put its own model value
                    back and the selection vanished.

                    A re-render is guaranteed on every selection, because getData()
                    writes into the same object it was called with:
                        this.filter.form_no = ...
                    Pick a date or a location, getData runs, filter changes, Vue
                    re-renders, and the picker's value is wiped. That is the
                    "disappears when selected" you reported, and it explains why both
                    controls behaved the same way.

                    The picker is now the only thing that writes this box. Its callback
                    still keeps filter.date_range in step so the API call is unchanged -
                    see the setDateRange() helper below.
                --}}
                <input class="form-control" ref="daterange" name="f14b_date" id="f14b_date" type="text" autocomplete="off">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <div class="form-group">
                    <label for="type">F14B Form No:</label>
                    <input v-model="filter.form_no" class="form-control" readonly="" name="F14b_from_no" type="text" value="1">
                </div>
            </div>
        </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => __('mpcs::lang.F14_form')])
            <div class="f14-toolbar">
                <button type="button" class="btn btn-primary btn-print-f14" @click="printBills">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
            </div>
            <div id="form14B_content" class="f14-a4-wrapper">
                <div class="container f14-a4-paper">
                    {{--
                        IS-1931 follow-up: the on-screen pagination nav is gone.

                        The screen used to slice the result to 9 bills at a time to
                        mimic one A4 sheet, so a date range covering more than 9
                        bills only ever showed the first nine. All bills for the
                        selected range are now rendered continuously in the same
                        3-column grid.

                        This is a SCREEN-only change. Printing is unaffected: the
                        print sheet is built separately in buildPrintDocumentHtml()
                        and the @media print rules below still break every 9th bill
                        onto a new page, so paper output stays 3x3 per A4 sheet.
                    --}}
                    <p class="f14-bill-count" v-if="sales.length > 0">
                        @{{ sales.length }} @{{ sales.length === 1 ? 'bill' : 'bills' }}
                    </p>

                    <div class="row f14-bills-row" id="printarea">
                        <template v-for="(sale,ind) in getDisplayedBills()">
                            <div class="f14-bill-col">
                                <div class="col-md-12 f14-bill-inner">
                                    <div class="row">
                                        <div class="col-md-12 col-sm-12 text-center">
                                        <b>@{{ sale.company }}</b><br>
                                        <b>@lang('mpcs::lang.filling_station')</b><br>
                                        <b>@lang('mpcs::lang.tel') :</b> @{{ sale.tel }}
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6"><b>Date:</b> @{{ sale.date }}</div>
                                        <div class="col-md-6 col-sm-6"><b>Bill No:</b> @{{ billNoForSale(sale) }}</div>
                                        <div class="col-md-6 col-sm-6"><b>Customer:</b> @{{ sale.customer }}</div>
                                        <div class="col-md-6 col-sm-6"><b>Order No:</b> @{{ sale.order_no }}</div>
                                        <div class="col-md-6 col-sm-6"><b>Vehicle No:</b> @{{ sale.customer_reference }}</div>
                                        <div class="col-md-6 col-sm-6"><b>Our Reference:</b> @{{ sale.sattlement_no }}</div>
                                    </div>
                                    <table class="table table-bordered table-striped credit_sale_table" style="width:100%;">
                                        <thead>
                                            <tr>
                                                <th>@lang('mpcs::lang.voucher_no')</th>
                                                <th>@lang('mpcs::lang.balance_qty')</th>
                                                <th>@lang('mpcs::lang.description')</th>
                                                <th>@lang('mpcs::lang.unit_price')</th>
                                                <th>@lang('mpcs::lang.amount')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>@{{ sale.voucher_no }}</td>
                                                <td>@{{ parseFloat(sale.balance_qty).toFixed((sale.is_fuel)?fuel_qty_decimals:business.quantity_precision) }}</td>
                                                <td>@{{ sale.description }}</td>
                                                <td>@{{ parseFloat(sale.unit_price).toFixed(business.currency_precision) }}</td>
                                                <td>@{{ formatLineAmount(sale) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-center">@lang('mpcs::lang.total_amount')</td>
                                                <td>@{{ formatLineAmount(sale) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        @endcomponent
        
        <!-- Transactions Table -->
        @component('components.widget', ['class' => 'box-primary hide', 'title' => 'All Transactions'])
            <div class="table-responsive hide" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-striped" id="transactions_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Invoice No</th>
                            <th>Ref No</th>
                            <th>Transaction Date</th>
                            <th>Final Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->id }}</td>
                            <td>{{ $transaction->invoice_no }}</td>
                            <td>{{ $transaction->ref_no }}</td>
                            <td>{{ $transaction->transaction_date }}</td>
                            <td>{{ number_format($transaction->final_total, 2) }}</td>
                            <td>{{ $transaction->status }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endcomponent
        
    </section>
    </div>
    
</section>

<script>
    new Vue({
        el: '#app',
        data() {
            return {
                title: 'F14B Former',
                business_locations: {!! json_encode($business_locations) !!},
                setting: {!! json_encode($setting) !!},
                business: {!! json_encode($business) !!},
                filter: {business_location_id:"{{ $default_business_location }}",date_range: moment().format('YYYY-MM-DD'),form_no:({{ $setting['F14_form_sn'] ?? 0 }} + 0)},
                fuel_qty_decimals: {{ $fuel_qty_decimals }},
                credit_sales: [],
                sales:[],
                form_no_by_date: {},
                page:0,
                pages:[],
                total_before_startdate:0,
                printMode: false,
                printLabels: {
                    formTitle: {!! json_encode(__('mpcs::lang.F14_form')) !!},
                    fillingStation: {!! json_encode(__('mpcs::lang.filling_station')) !!},
                    tel: {!! json_encode(__('mpcs::lang.tel')) !!},
                    date: {!! json_encode(__('date')) !!},
                    billNo: 'Bill No',
                    customer: 'Customer',
                    orderNo: 'Order No',
                    vehicleNo: 'Vehicle No',
                    ourReference: 'Our Reference',
                    voucherNo: {!! json_encode(__('mpcs::lang.voucher_no')) !!},
                    balanceQty: {!! json_encode(__('mpcs::lang.balance_qty')) !!},
                    description: {!! json_encode(__('mpcs::lang.description')) !!},
                    unitPrice: {!! json_encode(__('mpcs::lang.unit_price')) !!},
                    amount: {!! json_encode(__('mpcs::lang.amount')) !!},
                    totalAmount: {!! json_encode(__('mpcs::lang.total_amount')) !!},
                    noData: {!! json_encode(__('lang_v1.no_data')) !!}
                },
            }
        },
        mounted() {
            // Load invoices immediately on page load (don't wait for daterangepicker)
            this.$nextTick(() => {
                this.getData();
            });

            $(document).ready(() => {
                if (!this.$refs.daterange) return;

                var $daterange = $(this.$refs.daterange);

                /*
                 * IS-1931: bind the handlers that must survive FIRST, before the
                 * picker is created or touched.
                 *
                 * Root cause of "unable to select date range / it disappeared":
                 * the previous build called
                 *     $(...).data('daterangepicker').clickRange('Today');
                 * to preselect Today. clickRange is daterangepicker's internal
                 * CLICK HANDLER, not a public API - it reads the clicked <li> off
                 * its event argument (e.target.getAttribute('data-range-key') in
                 * v3, e.target.innerHTML in the bundled v2). Handing it the string
                 * 'Today' threw on every single page load:
                 *     TypeError: Cannot read properties of undefined
                 *
                 * The throw aborted the rest of this ready callback, so the cancel
                 * handler and - critically - the #custom_date_apply_button handler
                 * were NEVER bound. That is why typing a custom range and pressing
                 * Apply did nothing and the date vanished.
                 *
                 * The call is deleted outright: startDate/endDate below already
                 * default to today, so it was never needed. Bindings now come first
                 * so no future plugin-internal error can silently remove them again.
                 */
                $daterange.on('cancel.daterangepicker', (ev, picker) => {
                    this.setDateRange('');
                    this.getData();
                });

                // Namespaced so a re-run cannot stack duplicates, and so we never
                // detach the handlers other screens bind to this shared button.
                $('#custom_date_apply_button').off('click.f14').on('click.f14', () => {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                    if (startDate.length === 10 && endDate.length === 10) {
                        // Move the picker first, then let setDateRange write the box
                        // and the model together - see the note on setDateRange().
                        let picker = $daterange.data('daterangepicker');
                        if (picker) {
                            picker.setStartDate(moment(startDate));
                            picker.setEndDate(moment(endDate));
                        }
                        this.setDateRange(startDate + ' - ' + endDate);
                        this.getData();
                        $('.custom_date_typing_modal').modal('hide');
                    } else {
                        alert("Please select both start and end dates.");
                    }
                });

                $daterange.daterangepicker({
                    singleDatePicker: false,
                    showDropdowns: true,
                    autoApply: true,
                    startDate: moment(),
                    endDate: moment(),
                    /*
                     * IS-1931: setDateRange() is the ONLY thing allowed to write
                     * this box.
                     *
                     * With autoUpdateInput left on (the default), daterangepicker's
                     * hide() calls our callback and THEN calls updateElement(),
                     * which overwrote whatever the callback had just written. So
                     * picking "Today" left the model holding "2026-08-07" while the
                     * field showed "2026-08-07 - 2026-08-07". Turning it off makes
                     * the field and filter.date_range agree by construction.
                     */
                    autoUpdateInput: false,
                    // Default to "Today" range
                    opens: 'left',
                    locale: {
                        format: 'YYYY-MM-DD',
                    },
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Custom Date Range': [moment().startOf('month'), moment().endOf('month')],
                    }
                }, 
                (start, end, label) => {
                    /*
                     * IS-1931: the custom-range branch now returns immediately.
                     *
                     * It used to fall through to setDateRange(this.filter.date_range),
                     * which pushed the PREVIOUS date back into the box while the
                     * modal was opening, and then fired a getData() for a range the
                     * user had not chosen yet. The real value is applied by the
                     * #custom_date_apply_button handler above.
                     */
                    if (label === 'Custom Date Range') {
                        $('.custom_date_typing_modal').modal('show');
                        return;
                    }

                    if (label === 'Today' || label === 'Yesterday') {
                        this.setDateRange(start.format('YYYY-MM-DD'));
                    } else {
                        this.setDateRange(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
                    }

                    this.getData();
                });

                /*
                 * IS-1931: "Custom Date Range" never opened the typing modal.
                 *
                 * Its preset dates are identical to "This Month"
                 * ([startOf('month'), endOf('month')] in the ranges list above), and
                 * daterangepicker re-derives the label from the dates via
                 * calculateChosenLabel() before firing the callback. With two
                 * identical ranges it always resolved to the FIRST match - so
                 * picking "Custom Date Range" reported "This Month", the callback
                 * took the normal branch, and the modal was never shown. Combined
                 * with the dead Apply handler, entering a custom range was
                 * impossible.
                 *
                 * Rather than perturb the preset dates (any value we pick could
                 * collide again, and a sentinel would strand the calendar on a
                 * nonsense month), the row is intercepted before daterangepicker
                 * sees the click. stopImmediatePropagation keeps the plugin's own
                 * delegated .ranges handler from running, so the picker's dates are
                 * left untouched and hide() will not fire the callback.
                 *
                 * A direct handler on the <li> runs before the plugin's delegated
                 * handler on the .ranges parent, because jQuery dispatches the
                 * target's own handlers before the event bubbles.
                 */
                var picker = $daterange.data('daterangepicker');
                if (picker && picker.container) {
                    picker.container.find('.ranges li').each(function () {
                        var key = this.getAttribute('data-range-key') || $(this).text();
                        if ($.trim(key) === 'Custom Date Range') {
                            $(this).on('click', function (ev) {
                                ev.stopImmediatePropagation();
                                picker.hide();
                                $('.custom_date_typing_modal').modal('show');
                            });
                        }
                    });
                }

                // Seed the field from the model now that the picker exists.
                // (Previously unreachable - the clickRange() throw above killed it.)
                this.setDateRange(this.filter.date_range);
            });
        },
        methods: {
            escapePrintHtml(value){
                var node = document.createElement('div');
                node.textContent = (value === null || value === undefined) ? '' : String(value);
                return node.innerHTML;
            },
            formatPrintQuantity(sale){
                var qty = parseFloat(sale.balance_qty);
                var precision = sale.is_fuel ? this.fuel_qty_decimals : (parseInt(this.business.quantity_precision, 10) || 2);
                return (isNaN(qty) ? 0 : qty).toFixed(precision);
            },
            formatPrintPrice(sale){
                var price = parseFloat(sale.unit_price);
                var precision = parseInt(this.business.currency_precision, 10);
                precision = isNaN(precision) ? 2 : precision;
                return (isNaN(price) ? 0 : price).toFixed(precision);
            },
            buildPrintBillHtml(sale){
                var e = this.escapePrintHtml;
                var labels = this.printLabels;
                var billNo = this.billNoForSale(sale);
                var total = this.formatLineAmount(sale);

                return ''
                    + '<article class="f14-print-bill">'
                    + '  <header class="f14-print-heading">'
                    + '    <strong>' + e(sale.company || '') + '</strong>'
                    + '    <span>' + e(labels.fillingStation) + '</span>'
                    + '    <span><b>' + e(labels.tel) + ':</b> ' + e(sale.tel || '-') + '</span>'
                    + '  </header>'
                    + '  <div class="f14-print-details">'
                    + '    <div><b>' + e(labels.date) + ':</b> ' + e(sale.date || '-') + '</div>'
                    + '    <div><b>' + e(labels.billNo) + ':</b> ' + e(billNo || '-') + '</div>'
                    + '    <div><b>' + e(labels.customer) + ':</b> ' + e(sale.customer || '-') + '</div>'
                    + '    <div><b>' + e(labels.orderNo) + ':</b> ' + e(sale.order_no || '-') + '</div>'
                    + '    <div><b>' + e(labels.vehicleNo) + ':</b> ' + e(sale.customer_reference || '-') + '</div>'
                    + '    <div><b>' + e(labels.ourReference) + ':</b> ' + e(sale.sattlement_no || '-') + '</div>'
                    + '  </div>'
                    + '  <table class="f14-print-table">'
                    + '    <thead><tr>'
                    + '      <th>' + e(labels.voucherNo) + '</th>'
                    + '      <th>' + e(labels.balanceQty) + '</th>'
                    + '      <th>' + e(labels.description) + '</th>'
                    + '      <th>' + e(labels.unitPrice) + '</th>'
                    + '      <th>' + e(labels.amount) + '</th>'
                    + '    </tr></thead>'
                    + '    <tbody><tr>'
                    + '      <td>' + e(sale.voucher_no || '-') + '</td>'
                    + '      <td class="num">' + e(this.formatPrintQuantity(sale)) + '</td>'
                    + '      <td>' + e(sale.description || '-') + '</td>'
                    + '      <td class="num">' + e(this.formatPrintPrice(sale)) + '</td>'
                    + '      <td class="num">' + e(total) + '</td>'
                    + '    </tr></tbody>'
                    + '    <tfoot><tr>'
                    + '      <th colspan="4" class="num">' + e(labels.totalAmount) + '</th>'
                    + '      <th class="num">' + e(total) + '</th>'
                    + '    </tr></tfoot>'
                    + '  </table>'
                    + '</article>';
            },
            buildPrintDocumentHtml(){
                var bills = Array.isArray(this.sales) ? this.sales : [];
                var pages = [];
                for (var i = 0; i < bills.length; i += 9) {
                    pages.push(bills.slice(i, i + 9));
                }

                if (pages.length === 0) {
                    pages.push([]);
                }

                var body = pages.map((pageBills) => {
                    var cards = pageBills.map((sale) => this.buildPrintBillHtml(sale)).join('');
                    if (!cards) {
                        cards = '<div class="f14-print-empty">' + this.escapePrintHtml(this.printLabels.noData) + '</div>';
                    }
                    return '<section class="f14-print-sheet">' + cards + '</section>';
                }).join('');

                return '<!doctype html>'
                    + '<html><head><meta charset="utf-8">'
                    + '<title>' + this.escapePrintHtml(this.printLabels.formTitle) + '</title>'
                    + '<style>'
                    + '@page{size:A4 landscape;margin:5mm;}'
                    + 'html,body{margin:0;padding:0;background:#fff;color:#111;font-family:Arial,sans-serif;}'
                    + '.f14-print-sheet{width:287mm;height:200mm;box-sizing:border-box;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));grid-template-rows:repeat(3,minmax(0,1fr));gap:2mm;page-break-after:always;break-after:page;overflow:hidden;}'
                    + '.f14-print-sheet:last-child{page-break-after:auto;break-after:auto;}'
                    + '.f14-print-bill{border:.25mm solid #333;box-sizing:border-box;padding:1.8mm;min-width:0;min-height:0;overflow:hidden;page-break-inside:avoid;break-inside:avoid;font-size:7.3pt;line-height:1.18;}'
                    + '.f14-print-heading{text-align:center;display:flex;flex-direction:column;gap:.4mm;margin-bottom:1.1mm;white-space:normal;}'
                    + '.f14-print-heading strong{font-size:8pt;}'
                    + '.f14-print-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7mm 1.4mm;margin-bottom:1.2mm;}'
                    + '.f14-print-details div{min-width:0;overflow-wrap:anywhere;}'
                    + '.f14-print-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:6.8pt;}'
                    + '.f14-print-table th,.f14-print-table td{border:.2mm solid #555;padding:.7mm .6mm;vertical-align:middle;overflow-wrap:anywhere;}'
                    + '.f14-print-table th{background:#f2f2f2;font-weight:700;}'
                    + '.f14-print-table th:nth-child(1){width:18%;}'
                    + '.f14-print-table th:nth-child(2){width:15%;}'
                    + '.f14-print-table th:nth-child(3){width:31%;}'
                    + '.f14-print-table th:nth-child(4){width:18%;}'
                    + '.f14-print-table th:nth-child(5){width:18%;}'
                    + '.num{text-align:right;}'
                    + '.f14-print-empty{grid-column:1/-1;display:flex;align-items:center;justify-content:center;font-size:14pt;}'
                    + '@media print{html,body{width:297mm;} .f14-print-sheet{margin:0;}}'
                    + '</style></head><body>' + body + '</body></html>';
            },
            printBills(){
                var printWindow = window.open('', '_blank', 'width=1280,height=900');
                if (!printWindow) {
                    alert('Please allow pop-ups to print the F14 bills.');
                    return;
                }

                printWindow.document.open();
                printWindow.document.write(this.buildPrintDocumentHtml());
                printWindow.document.close();

                printWindow.onload = function () {
                    setTimeout(function () {
                        printWindow.focus();
                        printWindow.print();
                    }, 150);
                };
                printWindow.onafterprint = function () {
                    printWindow.close();
                };
            },
            /*
             * MA-002 (IS-1904 #1): the single place that changes the date.
             *
             * With v-model gone from the input, Vue no longer writes it - so the
             * model and the visible box are kept in step here instead. Every
             * caller goes through this rather than setting one and forgetting
             * the other, which is how they drifted apart before.
             */
            setDateRange(value){
                this.filter.date_range = value || '';

                if (this.$refs.daterange) {
                    $(this.$refs.daterange).val(this.filter.date_range);
                }
            },

            setBusinessLocation(a, b){
                this.getData();
            },
            prevPage(){
              if(this.page > 0){
                  this.setPage(this.page - 1);
              }  
            },
            nextPage(){
              if(this.page < (this.pages.length - 1)){
                  this.setPage(this.page + 1);
              }  
            },
            setPage(page){
                this.page = page;
            },
            /*
             * IS-1931 follow-up: turn an "mm/dd/yyyy" group key into a comparable
             * number (yyyymmdd). Returns null when the key is not in that shape so
             * the caller can leave those entries where they are.
             */
            dateGroupSortKey(key){
                var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(String(key));
                if (!m) {
                    return null;
                }
                return (parseInt(m[3], 10) * 10000)
                     + (parseInt(m[1], 10) * 100)
                     + parseInt(m[2], 10);
            },
            getDisplayedBills(){
                /*
                 * IS-1931 follow-up: return every bill in the selected range.
                 *
                 * This used to slice to this.page * 9 .. +9, so a range with more
                 * than nine bills only ever showed the first nine on screen. The
                 * data was always there - getData() already flattens the full
                 * result into this.sales - it was only the view that capped it.
                 *
                 * The old printMode branch is folded away: it returned exactly this
                 * same full list, and nothing ever set printMode to true (printing
                 * goes through buildPrintDocumentHtml, which reads this.sales
                 * directly).
                 */
                return (this.sales || []).map((s, i) => ({ ...s, _flatIndex: i }));
            },
            // Returns the form number for a given bill's date
            getFormNoForDate(date){
                return this.form_no_by_date[date] || this.filter.form_no;
            },
            billNoForSale(sale){
                var b = sale.bill_no;
                if (b !== undefined && b !== null && b !== '') {
                    return b;
                }
                return this.getFormNoForDate(sale.date);
            },
            formatLineAmount(sale){
                var q = parseFloat(sale.balance_qty);
                var u = parseFloat(sale.unit_price);
                var fallback = (isNaN(q) ? 0 : q) * (isNaN(u) ? 0 : u);
                var prec = this.business.currency_precision;
                var v = parseFloat(sale.final_total);
                if (!isNaN(v)) {
                    return v.toFixed(prec);
                }
                var ps = parseFloat(sale.product_sub_total);
                if (!isNaN(ps)) {
                    return ps.toFixed(prec);
                }
                var pa = parseFloat(sale.product_amount);
                if (!isNaN(pa)) {
                    return pa.toFixed(prec);
                }
                return fallback.toFixed(prec);
            },
            getData(){
                axios.get('/mpcs/get-form-14', { params: this.filter })
                    .then(res => {
                        if (res.status === 200) {
                            this.total_before_startdate = res.data.total_before_startdate || 0;

                            if (res.data.setting) {
                                this.setting = res.data.setting;
                            }
                            var fno = res.data.filter_f14b_form_no;
                            this.filter.form_no = (fno !== null && fno !== undefined) ? fno : '';

                            // Build form_no_by_date: each date with bills gets an auto-incremented form number
                            this.form_no_by_date = res.data.form_no_by_date || {};

                            var raw = res.data.data;
                            if (Array.isArray(raw)) {
                                this.credit_sales = { 0: raw };
                            } else {
                                this.credit_sales = raw;
                            }
                            /*
                             * IS-1931 follow-up: sort the date groups CHRONOLOGICALLY.
                             *
                             * The controller keys each group by date('m/d/Y'), and a
                             * plain .sort() compares those as strings. Across a range
                             * spanning more than one month that is simply wrong:
                             * "08/02/2026" sorts before "12/31/2025" because '0' < '1',
                             * so last December's bills appeared after this August's.
                             *
                             * Now that every bill in the range is on screen at once
                             * this is visible on any multi-month range, so the keys are
                             * compared as real dates. Anything that does not match
                             * mm/dd/yyyy keeps its original relative position rather
                             * than being dropped.
                             */
                            var self = this;
                            var keys = Object.keys(this.credit_sales).sort(function (a, b) {
                                var ka = self.dateGroupSortKey(a);
                                var kb = self.dateGroupSortKey(b);
                                if (ka === null || kb === null) {
                                    return 0;
                                }
                                return ka - kb;
                            });
                            var flat = [];
                            keys.forEach(function(key) {
                                var arr = self.credit_sales[key] || [];
                                flat = flat.concat(Array.isArray(arr) ? arr : []);
                            });
                            this.sales = flat;
                            var totalPages = Math.max(1, Math.ceil(flat.length / 9));
                            this.pages = Array.from({ length: totalPages }, function(_, i) { return i; });
                            this.page = 0;
                        }
                    })
                    .catch(err => {
                        console.log(err);
                    });
                
            }
        }
    });
    
</script>
<style>
    /* F14 screen preview: 3 bills per row, matching the print layout */
    .f14-a4-wrapper {
        max-width: 297mm;
        margin: 0 auto;
        padding: 0;
    }
    .f14-a4-paper {
        width: 100%;
        max-width: 297mm;
        min-height: 210mm;
        padding: 6mm;
        box-sizing: border-box;
        background: #fff;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .f14-toolbar {
        margin-bottom: 12px;
    }
    /* IS-1931 follow-up: count line for the bills now shown in full. */
    .f14-bill-count {
        margin: 0 0 10px;
        font-size: 12px;
        font-weight: bold;
        color: #555;
    }
    .f14-toolbar .btn-print-f14 {
        min-width: 100px;
    }
    .f14-pagination {
        margin-bottom: 12px;
    }
    .f14-bills-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 3mm;
        margin: 0;
        align-items: start;
    }

    /*
     * IS2110 #1: the empty cell at the start of the grid.
     *
     * This element carries Bootstrap's .row class as well as .f14-bills-row.
     * Bootstrap 3's clearfix adds .row:before and .row:after, and once the
     * element becomes display:grid those two pseudo-elements are treated as GRID
     * ITEMS in their own right. The :before therefore occupied the first cell,
     * pushing every bill one place along and leaving the gap in the reported
     * screenshot.
     *
     * They exist only to clear floats, which a grid does not use.
     */
    .f14-bills-row::before,
    .f14-bills-row::after {
        display: none !important;
        content: none !important;
    }
    .f14-bill-col {
        width: auto;
        min-width: 0;
        box-sizing: border-box;
        padding: 0;
        /* MA-002 (IS-1904 #2): stop a card spilling out of its grid cell. */
        overflow: hidden;
    }

    /*
     * MA-002 (IS-1904 #2): the bill layout was correct - three columns, nine
     * bills - but each card was WIDER than its column, so Unit Price and Amount
     * were cut off the right-hand edge and the cards overlapped each other.
     *
     * The grid itself was never the problem. The TABLE inside each card was.
     * A table will not shrink below the natural width of its content unless it
     * is told to, so with long values like "Lanka Auto Diesel" it pushed past
     * the column and the last two columns fell off the visible area.
     *
     * table-layout: fixed makes the table honour its container's width and
     * divide the space between the columns, and the wrapping rules let long
     * text break instead of forcing the table wider.
     *
     * The PRINT sheet already did all of this - .f14-print-table sets
     * table-layout:fixed with the same percentage widths - which is why the
     * printed bills in your document look right while the screen did not.
     * The column widths below are copied from it so the preview matches the
     * paper: 18 / 15 / 31 / 18 / 18.
     */
    .f14-bill-col table {
        table-layout: fixed;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }

    .f14-bill-col table th,
    .f14-bill-col table td {
        word-wrap: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
        /*
         * IS-1932: these three MUST carry !important.
         *
         * resources/views/layouts/app.blade.php ships a global design-system
         * rule set that applies to every .table on every page:
         *
         *     .table td { vertical-align: middle !important; padding: 14px !important; }
         *     .table thead th { border: none !important; text-transform: uppercase;
         *                       font-size: 12px; }
         *     .table { border-radius: 14px; overflow: hidden; }
         *
         * The bill cards use "table table-bordered table-striped", so they were
         * inheriting 14px of padding on both sides of all five columns. That is
         * roughly 140px of padding per row on a card only ~300px wide, so the
         * content could not fit: the cards burst out of their grid cells,
         * overlapped each other, and Unit Price and Amount were pushed off the
         * right edge. table-layout:fixed could not save it because padding is
         * added on top of the computed column widths.
         *
         * The global rule uses !important, so the only way to scope the cards
         * back out of it is to match that weight here. These selectors are
         * confined to .f14-bill-col, so no other table on the site is affected.
         */
        padding: 2px 4px !important;
        font-size: 11px !important;
        vertical-align: middle !important;
    }

    /*
     * IS-1932: undo the rest of the global .table treatment for bill cards only.
     * The F14 bill is a printed receipt facsimile - it needs plain ruled cells and
     * sentence-case headings, not the uppercase/rounded dashboard styling.
     */
    .f14-bill-col table {
        border-radius: 0 !important;
        overflow: visible !important;
        background: #fff !important;
    }

    .f14-bill-col table thead {
        background: #fff !important;
    }

    .f14-bill-col table thead th {
        border: 1px solid #333 !important;
        text-transform: none !important;
        letter-spacing: normal !important;
        color: #000 !important;
        font-weight: bold !important;
        /*
         * IS2110 #2: the headings ran into one another - "Voucher No",
         * "Balance Qty" and "Description" overlapped as one line of text.
         *
         * A heading like "Voucher No" is wider than its 18% column, and without
         * an explicit wrapping rule at !important weight the global
         * .table thead th styling in layouts/app.blade.php won that cascade and
         * kept them on one line, so each spilled over its neighbour.
         *
         * They now wrap inside their own cell, exactly as the printed bill does
         * ("Voucher / No", "Balanc / e Qty").
         */
        white-space: normal !important;
        word-wrap: break-word !important;
        overflow-wrap: anywhere !important;
        text-align: center !important;
        vertical-align: middle !important;
        line-height: 1.15 !important;
        padding: 3px 2px !important;
    }

    .f14-bill-col table td {
        border: 1px solid #333 !important;
    }

    /* Keep the numeric columns readable rather than letting the description
       take everything - description wraps, figures stay on one line. */
    .f14-bill-col table th:nth-child(1),
    .f14-bill-col table td:nth-child(1) { width: 18%; }
    .f14-bill-col table th:nth-child(2),
    .f14-bill-col table td:nth-child(2) { width: 15%; }
    .f14-bill-col table th:nth-child(3),
    .f14-bill-col table td:nth-child(3) { width: 31%; }
    .f14-bill-col table th:nth-child(4),
    .f14-bill-col table td:nth-child(4) { width: 18%; text-align: right; }
    .f14-bill-col table th:nth-child(5),
    .f14-bill-col table td:nth-child(5) { width: 18%; text-align: right; }

    /*
     * IS2110 #1: amounts broke mid-number - "5796.0" on one line and "0" on the
     * next - because overflow-wrap: anywhere applies to figures just as readily
     * as to "Lanka Auto Diesel".
     *
     * Only the DESCRIPTION needs to break mid-word. The numeric columns are kept
     * on one line and given a slightly smaller size so they fit, which is how
     * the printed bill reads.
     */
    .f14-bill-col table td:nth-child(1),
    .f14-bill-col table td:nth-child(2),
    .f14-bill-col table td:nth-child(4),
    .f14-bill-col table td:nth-child(5) {
        white-space: nowrap !important;
        overflow-wrap: normal !important;
        word-wrap: normal !important;
        font-size: 11px !important;
    }

    /*
     * IS2200 #1: make the five F14 bill columns clearly readable on screen.
     * Keep three bills per row and the existing print layout, but recover a
     * little horizontal room from card padding/gaps and give the headings and
     * values enough size/line-height to remain legible.
     */
    .f14-bills-row {
        gap: 2mm;
    }
    .f14-bill-inner {
        padding: 6px !important;
    }
    .f14-bill-col table thead th {
        font-size: 12px !important;
        line-height: 1.18 !important;
        min-height: 30px;
        padding: 4px 2px !important;
    }
    .f14-bill-col table td {
        font-size: 11px !important;
        line-height: 1.18 !important;
        padding: 3px 3px !important;
    }
    .f14-bill-col table th:nth-child(1),
    .f14-bill-col table td:nth-child(1) { width: 20%; }
    .f14-bill-col table th:nth-child(2),
    .f14-bill-col table td:nth-child(2) { width: 16%; }
    .f14-bill-col table th:nth-child(3),
    .f14-bill-col table td:nth-child(3) { width: 26%; }
    .f14-bill-col table th:nth-child(4),
    .f14-bill-col table td:nth-child(4) { width: 19%; }
    .f14-bill-col table th:nth-child(5),
    .f14-bill-col table td:nth-child(5) { width: 19%; }

    /* The Total Amount row carries the largest figure on the card. */
    .f14-bill-col table tfoot td,
    .f14-bill-col table tbody tr:last-child td {
        white-space: nowrap !important;
    }
    .f14-bill-col .col-md-12 {
        border: 1px solid #333;
        padding: 10px !important;
        margin-bottom: 10px !important;
        box-sizing: border-box;
        min-height: 1px;
    }
    .f14-bill-inner {
        border: 1px solid #333;
        padding: 10px !important;
        margin-bottom: 14px !important;
        box-sizing: border-box;
    }
    @media print {
      @page {
        size: A4 landscape;
        margin: 5mm;
      }

      body * {
        visibility: hidden;
      }
      #form14B_content.f14-a4-wrapper,
      #form14B_content .f14-a4-paper,
      #printarea, #printarea * {
        visibility: visible;
      }
      .f14-toolbar {
        display: none !important;
      }
      .f14-a4-wrapper {
        position: absolute;
        top: 0;
        left: 0;
        width: 287mm;
        max-width: 287mm;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
      .f14-a4-paper {
        width: 287mm;
        max-width: 287mm;
        min-height: auto;
        padding: 0;
        box-shadow: none;
      }
      .f14-pagination {
        display: none !important;
      }
      /* IS-1931 follow-up: screen-only count line, never on paper. */
      .f14-bill-count {
        display: none !important;
      }
      .f14-bills-row {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        grid-template-rows: repeat(3, minmax(0, 1fr));
        gap: 2mm;
        margin: 0;
      }
      .f14-bill-col {
        width: auto !important;
        min-width: 0;
        box-sizing: border-box;
        padding: 0;
        page-break-inside: avoid;
        break-inside: avoid;
        margin: 0;
      }
      .f14-bill-inner {
        border: 1px solid #333 !important;
        padding: 8px !important;
        margin-bottom: 0 !important;
      }
      /* F14 uses 3 columns x 3 rows: exactly 9 complete bills per A4 page. */
      .f14-bill-col:nth-child(9n) {
        page-break-after: always;
        break-after: page;
      }
      .filter-select, .daterangepicker, .page-title-area .form-group {
        display: none !important;
      }
    }
    .filter-select{
        background-color: #fff;
        border: 1px solid #aaa;
        border-radius: 4px;
        height: 35px;
        width:100%;
    }
    
    /* Date picker styling */
    .daterangepicker {
        z-index: 9999;
    }
    
    .daterangepicker .ranges li {
        padding: 8px 12px;
        cursor: pointer;
        border-radius: 4px;
        margin: 2px 0;
    }
    
    .daterangepicker .ranges li:hover {
        background-color: #f8f9fa;
    }
    
    .daterangepicker .ranges li.active {
        background-color: #007bff;
        color: white;
    }
    /* Datatable */
    .pagination .disabled a {
      pointer-events: none;
      opacity: 0.5;
      cursor: default;
    }

    
    /* Transactions table scrolling */
    #transactions_table thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
        border-bottom: 2px solid #dee2e6;
    }
    
    #transactions_table tbody tr:hover {
        background-color: #f5f5f5;
    }
</style>
@endsection