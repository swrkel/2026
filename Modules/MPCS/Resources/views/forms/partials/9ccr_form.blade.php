<!-- Main content -->
<section class="content" style="padding-top:10px">
    <style>
        #form_f15_table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            color: #333;
            margin-top: 20px;
        }

        #form_f15_table thead {
            color: #333;
            font-weight: bold;
        }

        #form_f15_table th,
        #form_f15_table td {
            padding: 12px 15px;
            text-align: center;
            border: 1px solid #ddd;
        }

        #form_f15_table th {}

        @media (max-width: 768px) {

            #form_f15_table th,
            #form_f15_table td {
                font-size: 12px;
                padding: 8px 10px;
            }
        }

        .total-row {
            background-color: #f1f1f1;
            font-weight: bold;
            color: #fff;
        }

        .card-sale-header {
            font-style: italic;
            background-color: #f9f9f9;
        }

        .card-sale-detail {
            font-style: italic;
            background-color: #f9f9f9;
            padding-left: 50px;
        }

        .total-row td {
            text-align: right;
        }

        #form_f15_table td {
            font-size: 14px;
        }

        #form_f15_table th,
        #form_f15_table td {
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        #form_f15_table tbody tr {
            border-bottom: 2px solid #f0f0f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td {
            height: 10px;
        }

        table td[align="right"] {
            text-align: right;
            padding-right: 10px;
        }

        .no-border-table {
            border-collapse: collapse;
            width: 100%;
        }

        .no-border-table tr {
            border: none !important;
        }

        .no-border-table td {
            border: none !important;
            padding: 8px;
        }

        .no-border-table td,
        .no-border-table th {
            border-bottom: none !important;
        }

        .note-container {
            width: 100%;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
        }

        .dataTables_filter,
        .dataTables_info {
            display: none;
        }

        .dots {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .dots::before {
            content: "";
            flex-grow: 1;
            border-bottom: 1px dotted black;
            margin: 0 5px;
        }


        /* IS2275 follow-up: keep Form 9C Credit columns inside the available screen width.
         * Fixed percentage columns stop DataTables from stretching the report when a
         * reference/product/value is long; descriptive text wraps instead. */
        #form_9ccredit_table_wrapper,
        #form_9ccredit_table_wrapper .dataTables_scroll,
        #form_9ccredit_table_wrapper .dataTables_scrollHead,
        #form_9ccredit_table_wrapper .dataTables_scrollBody,
        #form_9ccredit_table_wrapper .dataTables_scrollHeadInner {
            width: 100% !important;
            max-width: 100% !important;
        }

        #form_9ccredit_table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }

        #form_9ccredit_table th,
        #form_9ccredit_table td {
            box-sizing: border-box;
            padding: 6px 4px !important;
            font-size: 12px;
            line-height: 1.2;
            vertical-align: middle !important;
        }

        #form_9ccredit_table thead th {
            white-space: normal !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
            vertical-align: middle !important;
            text-align: center !important;
            font-weight: 700;
            line-height: 1.15;
        }

        #form_9ccredit_table thead tr:first-child th {
            padding-top: 7px !important;
            padding-bottom: 5px !important;
        }

        #form_9ccredit_table thead tr:nth-child(2) th {
            padding-top: 3px !important;
            padding-bottom: 6px !important;
            font-weight: 600;
        }

        #form_9ccredit_table tbody td:nth-child(2),
        #form_9ccredit_table tbody td:nth-child(3) {
            white-space: normal !important;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        #form_9ccredit_table tbody td:not(:nth-child(2)):not(:nth-child(3)),
        #form_9ccredit_table tfoot td {
            white-space: nowrap;
        }

        @media (max-width: 1366px) {
            #form_9ccredit_table th,
            #form_9ccredit_table td {
                padding: 5px 3px !important;
                font-size: 11px;
            }
        }

        /* Styling for print */
        @media print {
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
            }

            /* Hide non-printable elements */
            #printButton,
            .dataTables_filter,
            .dataTables_info,
            .table-responsive .no-print {
                display: none;
            }

            /* Adjust table layout */
            .table-responsive {
                width: 100%;
                margin: 0;
            }

            /* Style adjustments for tables */
            .no-border-table {
                width: 100%;
                margin-top: 20px;
            }

            /* Ensure all the content fits within the page */
            @page {
                margin: 20mm;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }

            .header-area,
            .header-area * {
                display: none !important;
            }

            .nav,
            .nav-tabs * {
                display: none !important;
            }

            .page-title-area * {
                display: none !important;
            }

            .dots {
                display: flex;
                justify-content: space-between;
                width: 100%;
            }

            .dots::before {
                content: "";
                flex-grow: 1;
                border-bottom: 1px dotted black;
                margin: 0 5px;
            }
        }

        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-width: 250px;
            max-width: 400px;
        }

        .custom-alert.success {
            background-color: #28a745;
        }

        .custom-alert.error {
            background-color: #dc3545;
        }

        .custom-alert button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            margin-left: 10px;
            cursor: pointer;
        }
    </style>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="col-md-12">
                    <div class="row" style="margin-top: 0px;" id="print_content">
                        <table width="100%" style="margin-top: 0px;" class="no-border-table">
                            <tr style="border: none;">
                                <td align="right" width="40%" style="border: none;"> </td>
                                <td align="left" width="35%" style="border: none;">
                                    <h4 id="business_name_print">{{ $business_location_name }}</h4>
                                </td>
                                <td align="left" width="15%" style="border: none;">
                                    <h3 style="color: gray;">Form 9 C</h3>
                                </td>
                                <td align="right" width="10%" style="border: none;">
                                    <div class="box-tools">
                                        <!-- Standard Print button -->
                                        <button class="btn btn-primary print_report pull-right" style="font-size: 9px" id="print_div">
                                            <i class="fa fa-print"></i> @lang('messages.print')</button>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <table width="100%" style="margin-top: 10px;" class="no-border-table">
                            <tr style="border: none;">
                                <td align="center" width="5%" style="border: none;">Date:</td>
                                <td align="right" width="25%" style="border: none;">
                                    <div class="form-group">
                                    {!! Form::text(
                                            'date_range',
                                            @format_date('first day of this month') .
                                                ' ~ ' .
                                                @format_date('last                                                                                                                                                                                    day of this month'),
                                            [
                                                'placeholder' => __('lang_v1.select_a_date_range'),
                                                'class' => 'form-control',
                                                'id' => '9ccr_date_range',
                                                'readonly',
                                            ],
                                        ) !!}
                                    </div>
                                </td>
                                <td align="center" width="10%" style="border: none;"></td>
                                <td align="center" width="35%" style="border: none;">
                                    <h4 id="credit_sales_title">@lang('mpcs::lang.credit_sales_details')</h4>
                                    <h5><span id="custom_message" style="color:red"></span></h5>
                                </td>
                                <td align="center" width="20%" style="border: none;">
                                    <h4>Form No: <span id="form_9ccr_no">-</span>
                                     </h4>
                                </td>
                                <td align="right" width="5%" style="border: none;">
                                </td>
                            </tr>
                        </table>
                        <div class="col-md-12" style="margin-top: 0px;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="form_9ccredit_table">
                                            <colgroup>
                                                <col style="width:6%">
                                                <col style="width:7%">
                                                <col style="width:15%">
                                                <col style="width:6%">
                                                <col style="width:8%">
                                                <col style="width:5%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                                <col style="width:8%">
                                            </colgroup>
                                            <thead class="align-middle">
                                                <tr class="align-middle text-center">
                                                    <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.bill_no')
                                                    </th>
                                                    <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.our_ref')
                                                    </th>
                                                    <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.product_name')
                                                    </th>
                                                    <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.qty')
                                                    </th>
                                                    <th class="align-middle text-center" rowspan="2">Unit Price
                                                    </th>
                                                    <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.page')
                                                    </th>
                                                    <th class="align-middle text-center">Total Amount</th>
                                                    <th class="align-middle text-center">Goods</th>
                                                    <th class="align-middle text-center">Loading</th>
                                                    <th class="align-middle text-center">Empty</th>
                                                    <th class="align-middle text-center">Transport</th>
                                                    <th class="align-middle text-center">Others</th>
                                                </tr>
                                                <tr class="align-middle text-center">
                                                    <th scope="col">Amount</th>
                                                    <th scope="col">Amount</th>
                                                    <th scope="col">Amount</th>
                                                    <th scope="col">Amount</th>
                                                    <th scope="col">Amount</th>
                                                    <th scope="col">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                               
                                            </tbody>
                                            <tfoot class="bg-gray">
                                                <tr>
                                                    <td class="text-red text-bold" colspan="6">@lang('mpcs::lang.total_this_page')</td>
                                                    <td class="text-red text-bold text-right" id="footer_9c_total"></td>
                                                    <td  colspan="5"></td>
                                                    
                                                </tr>
                                                <tr class="f9c-total-previous-day-row">
    <td class="text-red text-bold" colspan="6">Total Previous Day</td>
    <td class="text-red text-bold text-right" id="previous_day_9c_total">0.00</td>
    <td colspan="5"></td>
</tr>
<tr class="f9c-total-previous-page-row" style="display: none;">
                                                    <td class="text-red text-bold" colspan="6">Previous Page Total
                                                    </td>
                                                    <td class="text-red text-bold text-right" id="pre_9c_total">
                                                        </td>
                                                    <td  colspan="5"></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-red text-bold" colspan="6">@lang('mpcs::lang.grand_total')</td>
                                                    <td class="text-red text-bold text-right" id="grand_9c_total"></td>
                                                    <td  colspan="5"></td>
                                                </tr>
                                                
                                                
                                            </tfoot>
                                        </table>
                                    </div>
                                    <table width="100%" style="margin-top: 20px;" class="no-border-table">
                                        <tr style="border: none;">
                                            <td align="center" width="25%" style="border: none;">Entered in the Book
                                            </td>
                                            <td align="center" width="25%" style="border: none;">
                                                .............................. <br> Checked By</td>
                                            <td align="center" width="25%" style="border: none;">
                                                .............................. <br> Manager</td>
                                            <td align="center" width="25%" style="border: none;">

                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->


<script>
     
</script>
