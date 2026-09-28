<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3" id="location_filter">
                    <div class="form-group">
                        {!! Form::label('16a_location_id', __('purchase.business_location') . ':') !!}

                        {!! Form::select('16a_location_id', $business_locations, $default_location_id ?? null, [
                            'id' => '16a_location_id',
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}


                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('16a_product_filter', __('mpcs::lang.product') . ':') !!}
                        {!! Form::select('16a_product_filter', [], null, [
                            'id' => '16a_product_filter',
                            'class' => 'form-control select2-product',
                            'style' => 'width:100%',
                            'placeholder' => 'All Products',
                            'data-url' => url('/mpcs/get-f16a-products'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('form_16a_date', __('report.date') . ':') !!}
                       
                            {{-- Single text input that triggers the dropdown --}}
                            {!! Form::text('form_16a_date', @format_date(date('Y-m-d')), [
                                'class' => 'form-control dropdown-toggle input_number customer_transaction_date',
                                'id' => 'form_16a_date',
                              
                                'readonly',
                                'required',
                            ]) !!}

                       
                    </div>

                </div>


                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('type', __('mpcs::lang.F16a_from_no') . ':') !!}
                        {!! Form::text('F16a_from_no', $F16a_from_no, ['class' => 'form-control', 'readonly', 'id' => 'F16a_from_no']) !!}
                    </div>
                </div>
                {!! Form::hidden('formId', $form_number, ['class' => 'form-control', 'readonly', 'id' => 'form_id']) !!}
                {{-- <button type="button" class="btn btn-success" style="margin-top: 20px; margin-right: 5px;" id="f16a_save_all">
                    <i class="fa fa-save"></i> @lang('messages.save')
                </button> --}}
                <button type="button" class="btn btn-primary" style="margin-top: 20px;" id="print_form_16a_btn">
                    <i class="fa fa-print"></i> Print
                </button>
            @endcomponent
        </div>
    </div>

    <div class="row" id="printarea">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                @php
                    $totPurchasePreValue = optional($settings)->total_purchase_price_with_vat ?? '0.00';
                    $totSalePreValue = optional($settings)->total_sale_price_with_vat ?? '0.00';

                    /*
                     * IS2016: the business name was missing from the top of the
                     * print.
                     *
                     * It is read from session('business.name'), which is only
                     * populated when the session was built that way. Where it is
                     * absent the heading rendered EMPTY, which is what the ticket
                     * shows - the location line printed but the business name
                     * above it did not.
                     *
                     * Falling back to the Business record makes the heading
                     * reliable regardless of how the session was seeded. Session
                     * first, so an install that deliberately overrides the
                     * displayed name keeps that behaviour.
                     */
                    $f16aBusinessName = request()->session()->get('business.name');

                    if (empty($f16aBusinessName)) {
                        $f16aBusinessId = request()->session()->get('business.id')
                            ?? request()->session()->get('user.business_id');

                        $f16aBusinessName = $f16aBusinessId
                            ? optional(\App\Business::find($f16aBusinessId))->name
                            : '';
                    }
                @endphp
                <div class="col-md-12 f16a-report-container">
                    <div class="f16a-report-header" aria-label="F16A report heading">
                        <div class="f16a-report-meta f16a-report-meta-left">
                            <span class="f16a-report-label">@lang('petro::lang.date')</span>
                            <span class="f16a-report-value from_date">{{ \Carbon\Carbon::now()->format('Y-m-d') }}</span>
                        </div>
                        <div class="f16a-report-business text-center">
                            <span class="f16a-report-label">Business</span>
                            <strong class="f16a-report-business-name">{{ $f16aBusinessName }}</strong>
                            <span class="f16a-report-location f16a_location_name">{{ $default_location_name ?: __('petro::lang.all') }}</span>
                        </div>
                        <div class="f16a-report-meta f16a-report-meta-right">
                            <span class="f16a-report-label">@lang('mpcs::lang.F16a_from_no')</span>
                            <span class="f16a-report-value" id="form_no1">{{ $F16a_from_no ?? '-' }}</span>
                        </div>
                    </div>
                    <style>
                        /* F16A presentation standard: clean on-screen report and print-safe A4 landscape output. */
                        #printarea,
                        #printarea .f16a-report-container,
                        #form_16a_live_table_wrapper,
                        #form_16a_summary_table,
                        .f16a-summary-slot {
                            width: 100% !important;
                            max-width: 100% !important;
                            box-sizing: border-box !important;
                        }

                        #printarea {
                            overflow: visible !important;
                        }

                        #printarea .f16a-report-container {
                            padding-left: 0 !important;
                            padding-right: 0 !important;
                            font-family: Arial, sans-serif !important;
                        }

                        /* Bootstrap .row uses negative side margins. Do not allow those margins to
                           push the report/summary outside the printable area. */
                        #printarea .f16a-report-container > .row,
                        #form_16a_live_table_wrapper > .row,
                        #form_16a_live_table_wrapper .f16a-dt-toolbar,
                        #form_16a_live_table_wrapper .f16a-dt-footer {
                            margin-left: 0 !important;
                            margin-right: 0 !important;
                        }

                        .f16a-report-header {
                            display: grid;
                            grid-template-columns: 23% 54% 23%;
                            align-items: center;
                            width: 100%;
                            margin: 8px 0 14px;
                            padding: 11px 14px;
                            border: 1px solid #d9e1e8;
                            border-radius: 6px;
                            background: #f8fafc;
                            box-sizing: border-box;
                            font-family: Arial, sans-serif;
                        }

                        .f16a-report-meta,
                        .f16a-report-business {
                            min-width: 0;
                        }

                        .f16a-report-meta-right {
                            text-align: right;
                        }

                        .f16a-report-label {
                            display: block;
                            margin-bottom: 2px;
                            color: #66727d;
                            font-size: 12px;
                            font-weight: 700;
                            line-height: 1.1;
                            text-transform: uppercase;
                            letter-spacing: .2px;
                        }

                        .f16a-report-value,
                        .f16a-report-business-name {
                            display: block;
                            color: #202b33;
                            font-size: 15px;
                            font-weight: 700;
                            line-height: 1.2;
                            overflow-wrap: anywhere;
                        }

                        .f16a-report-location {
                            display: block;
                            margin-top: 2px;
                            color: #4f5d67;
                            font-size: 13px;
                            font-weight: 600;
                            line-height: 1.15;
                            overflow-wrap: anywhere;
                        }

                        /* Keep the DataTable inside the report width. */
                        #form_16a_live_table_wrapper,
                        #form_16a_live_table_wrapper .dataTables_scroll,
                        #form_16a_live_table_wrapper .dataTables_scrollHead,
                        #form_16a_live_table_wrapper .dataTables_scrollHeadInner,
                        #form_16a_live_table_wrapper .dataTables_scrollBody,
                        #printarea .table-responsive {
                            width: 100% !important;
                            min-width: 0 !important;
                            max-width: 100% !important;
                            overflow: visible !important;
                            box-sizing: border-box !important;
                        }

                        #printarea .table-responsive {
                            margin: 0 !important;
                            padding: 0 !important;
                            border: 0 !important;
                        }

                        #form_16a_live_table,
                        #form_16a_summary_table {
                            width: 100% !important;
                            min-width: 0 !important;
                            max-width: 100% !important;
                            table-layout: fixed !important;
                            border-collapse: collapse !important;
                            border-spacing: 0 !important;
                            font-family: Arial, sans-serif !important;
                            box-sizing: border-box !important;
                        }

                        #form_16a_live_table {
                            margin: 0 !important;
                            border: 1px solid #d8e0e7 !important;
                            background: #fff !important;
                        }

                        #form_16a_live_table thead th {
                            color: #263238 !important;
                            background: #eef2f5 !important;
                            border: 1px solid #d8e0e7 !important;
                            border-bottom: 2px solid #c7d0d8 !important;
                            font-size: 12px !important;
                            font-weight: 700 !important;
                            line-height: 1.08 !important;
                            white-space: normal !important;
                            overflow-wrap: normal !important;
                            word-break: normal !important;
                            vertical-align: middle !important;
                            text-align: center !important;
                            padding: 7px 3px !important;
                        }

                        #form_16a_live_table tbody td {
                            color: #263238 !important;
                            background: #fff !important;
                            border: 1px solid #e0e6eb !important;
                            font-size: 12px !important;
                            font-weight: 400 !important;
                            line-height: 1.15 !important;
                            vertical-align: middle !important;
                            padding: 7px 4px !important;
                            white-space: normal !important;
                            overflow-wrap: anywhere !important;
                            word-break: normal !important;
                        }

                        #form_16a_live_table tbody tr:nth-child(even) td {
                            background: #fbfcfd !important;
                        }

                        /* Fixed percentage allocation totals 100%, so all 11 columns remain visible. */
                        #form_16a_live_table th:nth-child(1),
                        #form_16a_live_table td:nth-child(1)  { width: 4% !important; }
                        #form_16a_live_table th:nth-child(2),
                        #form_16a_live_table td:nth-child(2)  { width: 7% !important; }
                        #form_16a_live_table th:nth-child(3),
                        #form_16a_live_table td:nth-child(3)  { width: 8% !important; }
                        #form_16a_live_table th:nth-child(4),
                        #form_16a_live_table td:nth-child(4)  { width: 15% !important; }
                        #form_16a_live_table th:nth-child(5),
                        #form_16a_live_table td:nth-child(5)  { width: 10% !important; }
                        #form_16a_live_table th:nth-child(6),
                        #form_16a_live_table td:nth-child(6)  { width: 7% !important; }
                        #form_16a_live_table th:nth-child(7),
                        #form_16a_live_table td:nth-child(7)  { width: 10% !important; }
                        #form_16a_live_table th:nth-child(8),
                        #form_16a_live_table td:nth-child(8)  { width: 10% !important; }
                        #form_16a_live_table th:nth-child(9),
                        #form_16a_live_table td:nth-child(9)  { width: 10% !important; }
                        #form_16a_live_table th:nth-child(10),
                        #form_16a_live_table td:nth-child(10) { width: 11% !important; }
                        #form_16a_live_table th:nth-child(11),
                        #form_16a_live_table td:nth-child(11) { width: 8% !important; }

                        #form_16a_live_table td:nth-child(1),
                        #form_16a_live_table td:nth-child(2),
                        #form_16a_live_table td:nth-child(3),
                        #form_16a_live_table td:nth-child(4),
                        #form_16a_live_table td:nth-child(5) {
                            text-align: left;
                        }

                        #form_16a_live_table td:nth-child(6),
                        #form_16a_live_table td:nth-child(7),
                        #form_16a_live_table td:nth-child(8),
                        #form_16a_live_table td:nth-child(9),
                        #form_16a_live_table td:nth-child(10),
                        #form_16a_live_table td:nth-child(11) {
                            text-align: right;
                            font-variant-numeric: tabular-nums;
                            font-feature-settings: "tnum" 1;
                        }

                        /* Professional totals block. It lives in its own DataTables slot so it
                           never inherits Bootstrap negative row margins or clips at either edge. */
                        .f16a-summary-slot {
                            clear: both;
                            display: block;
                            margin: 8px 0 4px !important;
                            padding: 0 !important;
                            overflow: visible !important;
                        }

                        #form_16a_summary_table {
                            margin: 0 !important;
                            border: 1px solid #cfd8df !important;
                            background: #fff !important;
                        }

                        #form_16a_summary_table td {
                            color: #263238 !important;
                            border: 1px solid #dde4e9 !important;
                            background: #fff !important;
                            font-size: 12px !important;
                            line-height: 1.15 !important;
                            vertical-align: middle !important;
                            padding: 7px 9px !important;
                            white-space: normal !important;
                            overflow-wrap: anywhere !important;
                        }

                        #form_16a_summary_table tr:nth-child(2) td {
                            background: #f8fafb !important;
                        }

                        #form_16a_summary_table tr:last-child td {
                            background: #eef2f5 !important;
                            border-top: 2px solid #c7d0d8 !important;
                        }

                        #form_16a_summary_table .f16a-summary-label {
                            width: 27% !important;
                            font-weight: 600 !important;
                            text-align: left !important;
                        }

                        #form_16a_summary_table .f16a-summary-value {
                            width: 23% !important;
                            text-align: right !important;
                            font-weight: 700 !important;
                            white-space: nowrap !important;
                            overflow-wrap: normal !important;
                            font-variant-numeric: tabular-nums;
                            font-feature-settings: "tnum" 1;
                        }

                        #form_16a_summary_table tr:last-child .f16a-summary-label,
                        #form_16a_summary_table tr:last-child .f16a-summary-value {
                            font-weight: 700 !important;
                        }

                        #form_16a_live_table_wrapper .dataTables_info,
                        #form_16a_live_table_wrapper .dataTables_paginate {
                            margin-top: 4px !important;
                        }

                        @media (max-width: 1366px) {
                            #form_16a_live_table thead th,
                            #form_16a_live_table tbody td,
                            #form_16a_summary_table td {
                                font-size: 12px !important;
                            }

                            #form_16a_live_table thead th { padding: 6px 2px !important; }
                            #form_16a_live_table tbody td { padding: 6px 3px !important; }
                            #form_16a_summary_table td { padding: 6px 7px !important; }
                        }

                        @media print {
                            @page { size: A4 landscape; margin: 7mm; }

                            html,
                            body {
                                background: #fff !important;
                            }

                            #printarea,
                            #printarea > .col-md-12,
                            #printarea .f16a-report-container,
                            #form_16a_live_table_wrapper,
                            #form_16a_live_table,
                            #form_16a_summary_table,
                            .f16a-summary-slot {
                                width: 100% !important;
                                min-width: 0 !important;
                                max-width: 100% !important;
                                margin-left: 0 !important;
                                margin-right: 0 !important;
                                padding-left: 0 !important;
                                padding-right: 0 !important;
                                float: none !important;
                                box-sizing: border-box !important;
                            }

                            #printarea .f16a-report-container > .row,
                            #form_16a_live_table_wrapper > .row {
                                margin-left: 0 !important;
                                margin-right: 0 !important;
                            }

                            .f16a-report-header {
                                margin: 0 0 7px !important;
                                padding: 7px 9px !important;
                                border-radius: 0 !important;
                                background: #fff !important;
                            }

                            .f16a-report-label {
                                font-size: 11px !important;
                                line-height: 1.05 !important;
                            }

                            .f16a-report-value,
                            .f16a-report-business-name {
                                font-size: 15px !important;
                                line-height: 1.08 !important;
                            }

                            .f16a-report-location {
                                font-size: 12px !important;
                                line-height: 1.08 !important;
                            }

                            #form_16a_live_table thead th,
                            #form_16a_live_table tbody td,
                            #form_16a_summary_table td {
                                font-family: Arial, sans-serif !important;
                                font-size: 12px !important;
                            }

                            #form_16a_live_table thead th {
                                padding: 5px 2px !important;
                                background: #eef2f5 !important;
                                -webkit-print-color-adjust: exact;
                                print-color-adjust: exact;
                            }

                            #form_16a_live_table tbody td {
                                padding: 5px 3px !important;
                            }

                            .f16a-summary-slot {
                                margin-top: 7px !important;
                            }

                            #form_16a_summary_table td {
                                padding: 6px 8px !important;
                            }

                            #form_16a_summary_table tr:nth-child(2) td,
                            #form_16a_summary_table tr:last-child td {
                                -webkit-print-color-adjust: exact;
                                print-color-adjust: exact;
                            }

                            #form_16a_live_table tr,
                            #form_16a_summary_table tr {
                                page-break-inside: avoid !important;
                                break-inside: avoid !important;
                            }
                        }
                    </style>

                    <div class="row f16a-table-row" style="margin-top: 14px;">
                        <div class="table-responsive f16a-table-responsive">
                            {{-- S755: fixed-layout percentages keep every F16A column visible
                                 inside the available report width on screen and in print. --}}
                            <table class="table table-bordered table-striped" id="form_16a_live_table">
                                <thead>
                                    <tr>
                                        {{-- IS2039: headings split onto two lines as
                                             specified, so each column needs less width.
                                             The Action column is removed - its matching
                                             entry in the DataTables columns array in
                                             F16A.blade.php was removed too, or the table
                                             would error on a header/column mismatch. --}}
                                        <th>No</th>
                                        <th>PO<br>No</th>
                                        <th>P. Invoice<br>No</th>
                                        <th>@lang('mpcs::lang.product')</th>
                                        <th>@lang('mpcs::lang.location')</th>
                                        <th>Received<br>Qty</th>
                                        <th>Unit Purchase<br>Price<br>With VAT</th>
                                        <th>Purchase<br>Total<br>With VAT</th>
                                        <th>Unit Sale<br>Price<br>With VAT</th>
                                        <th>Sale<br>Total<br>With VAT</th>
                                        <th>Stock<br>Book No</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>

                            <table class="table table-bordered bg-gray" id="form_16a_summary_table">
                                <tbody>
                                    <tr>
                                        <td class="f16a-summary-label">@lang('mpcs::lang.total_this_page')</td>
                                        <td class="f16a-summary-value" id="footer_F16A_total_purchase_price" data-role="footer-total-purchase">0.00</td>
                                        <td class="f16a-summary-label">Sale Total</td>
                                        <td class="f16a-summary-value" id="footer_F16A_total_sale_price" data-role="footer-total-sale">0.00</td>
                                    </tr>
                                    <tr id="f16a_previous_total_row">
                                        <td class="f16a-summary-label" id="f16a_previous_purchase_label">Total Previous Day</td>
                                        <td class="f16a-summary-value" id="pre_F16A_total_purchase_price" data-role="pre-total-purchase">0.00</td>
                                        <td class="f16a-summary-label" id="f16a_previous_sale_label">Previous Day Sale Total</td>
                                        <td class="f16a-summary-value" id="pre_F16A_total_sale_price" data-role="pre-total-sale">0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="f16a-summary-label">@lang('mpcs::lang.grand_total')</td>
                                        <td class="f16a-summary-value" id="grand_F16A_total_purchase_price" data-role="grand-total-purchase">0.00</td>
                                        <td class="f16a-summary-label">Grand Sale Total</td>
                                        <td class="f16a-summary-value" id="grand_F16A_total_sale_price" data-role="grand-total-sale">0.00</td>
                                    </tr>
                                </tbody>
                            </table>

                            <input type="hidden" name="total_this_p_prev" id="total_this_p_prev" value="{{ $totPurchasePreValue }}">
                            <input type="hidden" name="total_this_s_prev" id="total_this_s_prev" value="{{ $totSalePreValue }}">
                            <input type="hidden" name="total_this_p" id="total_this_p" value="0">
                            <input type="hidden" name="total_this_s" id="total_this_s" value="0">
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->
