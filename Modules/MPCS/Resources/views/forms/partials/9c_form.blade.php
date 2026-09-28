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


        /* IS2275 follow-up: keep Form 9C Cash columns inside the available screen width.
         * DataTables must not size the report from the longest cell value. The report
         * keeps its accounting columns visible while product names wrap naturally. */
        #form_9ccash_table_wrapper,
        #form_9ccash_table_wrapper .dataTables_scroll,
        #form_9ccash_table_wrapper .dataTables_scrollHead,
        #form_9ccash_table_wrapper .dataTables_scrollBody,
        #form_9ccash_table_wrapper .dataTables_scrollHeadInner {
            width: 100% !important;
            max-width: 100% !important;
        }

        #form_9ccash_table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }

        #form_9ccash_table th,
        #form_9ccash_table td {
            box-sizing: border-box;
            padding: 6px 4px !important;
            font-size: 12px;
            line-height: 1.2;
            vertical-align: middle !important;
        }

        #form_9ccash_table th {
            white-space: normal !important;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        #form_9ccash_table tbody td:nth-child(2) {
            white-space: normal !important;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        #form_9ccash_table tbody td:not(:nth-child(2)),
        #form_9ccash_table tfoot td {
            white-space: nowrap;
        }

        @media (max-width: 1366px) {
            #form_9ccash_table th,
            #form_9ccash_table td {
                padding: 5px 3px !important;
                font-size: 11px;
            }
        }

        /* Styling for print */
        @media print {
            /* ========== HIDE UNNEEDED UI; KEEP FORM AT TOP ========== */
            /* Hide everything by default, then reveal the form area */
            body * {
                visibility: hidden !important;
            }
            #print_content,
            #print_content * {
                visibility: visible !important;
            }

            /* Anchor form to top-left with no extra spacing */
            #print_content {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Hide main layout elements */
            .main-header,
            .main-sidebar,
            .main-footer,
            .navbar,
            .sidebar,
            .control-sidebar,
            .wrapper > .content-wrapper > .content-header,
            .breadcrumb {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                overflow: hidden !important;
            }
            
            /* Hide tabs navigation */
            .settlement_tabs > .nav-tabs,
            .settlement_tabs .nav.nav-tabs,
            .nav-tabs {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                overflow: hidden !important;
            }

            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 0;
                padding: 0;
            }

            /* Hide non-printable elements */
            #printButton,
            .dataTables_filter,
            .dataTables_info,
            .table-responsive .no-print {
                display: none !important;
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
                size: landscape;
                margin: 5mm;
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
                                    <h3 id="business_name_print">{{ $business_location_name }}</h3>
                                </td>
                                <td align="left" width="15%" style="border: none;">
                                    <h2 style="color: gray;">Form 9 C</h2>
                                </td>
                                <td align="right" width="10%" style="border: none;">
                                    <div class="box-tools">
                                        <!-- Standard Print button -->
                                        <button class="btn btn-primary print_report pull-right" id="print_div">
                                            <i class="fa fa-print"></i> @lang('messages.print')</button>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <table width="100%" style="margin-top: 10px;" class="no-border-table">
                            <tr style="border: none;">
                                {{-- Task 7788: Product Category Filter --}}
                                <td align="center" width="7%" style="border: none;">Product Category:</td>
                                <td align="right" width="18%" style="border: none;">
                                    <div class="form-group">
                                        <select class="form-control" id="9c_product_category" style="width: 100%;">
                                            <option value="">All Categories</option>
                                            @if(isset($categories))
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </td>
                                {{-- Task 7788: Product Sub Category Filter --}}
                                <td align="center" width="7%" style="border: none;">Sub Category:</td>
                                <td align="right" width="18%" style="border: none;">
                                    <div class="form-group">
                                        <select class="form-control" id="9c_product_sub_category" style="width: 100%;">
                                            <option value="">All Sub Categories</option>
                                        </select>
                                    </div>
                                </td>
                                {{-- Task 7788: Product Filter --}}
                                <td align="center" width="7%" style="border: none;">Product:</td>
                                <td align="right" width="18%" style="border: none;">
                                    <div class="form-group">
                                        <select class="form-control select2" id="9c_product" style="width: 100%;">
                                            <option value="">All Products</option>
                                        </select>
                                    </div>
                                </td>
                                <td align="center" width="25%" style="border: none;"></td>
                            </tr>
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
                                                'id' => '9c_date_range',
                                            ],
                                        ) !!}
                                    </div>
                                </td>
                                <td align="center" width="10%" style="border: none;"></td>
                                <td align="left" width="35%" style="border: none;">
                                    <h3 id="cash_sales_title">@lang('mpcs::lang.cash_sales_details')</h3>
                                    <h5><span id="custom_message" style="color:red"></span></h5>
                                </td>
                                <td align="center" width="20%" style="border: none;">
                                    <h3>Form No: <span id="form_no1">{{ $form_9a_no }}</span>
                                        <h3>
                                </td>
                                <td align="right" width="5%" style="border: none;">
                                </td>
                            </tr>
                        </table>
                        <div class="col-md-12" style="margin-top: 0px;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="form_9ccash_table">
                                            <colgroup>
                                                <col style="width:7%">
                                                <col style="width:18%">
                                                <col style="width:7%">
                                                <col style="width:9%">
                                                <col style="width:5%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                                <col style="width:9%">
                                            </colgroup>
                                            <thead class="align-middle">
                                                <tr class="align-middle text-center">
                                                    <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.bill_no')
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
                                                <tr class="align-middle" style="text-align: center;">
                                                    <td>Amount</td>
                                                    <td>Amount</td>
                                                    <td>Amount</td>
                                                    <td>Amount</td>
                                                    <td>Amount</td>
                                                    <td>Amount</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                               
                                            </tbody>
                                            <tfoot class="bg-gray">
                                    <tr>
                                        <td class="text-red text-bold" colspan="5">@lang('mpcs::lang.total_this_page')</td>
                                        <td class="text-red text-bold text-right" id="footer_9c_total"></td>
                                        <td  colspan="5"></td>
                                        
                                    </tr>
                                    <tr class="f9c-total-previous-day-row">
                                        <td class="text-red text-bold" colspan="5">Total Previous Day</td>
                                        <td class="text-red text-bold text-right" id="previous_day_9c_total">0.00</td>
                                        <td colspan="5"></td>
                                    </tr>
                                    <tr class="f9c-total-previous-page-row" style="display: none;">
                                        <td class="text-red text-bold" colspan="5">Previous Page Total
                                        </td>
                                        <td class="text-red text-bold text-right" id="pre_9c_total">
                                            </td>
                                        <td  colspan="5"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-red text-bold" colspan="5">@lang('mpcs::lang.grand_total')</td>
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

    <div id="9c_details_section">
        
    </div>
</section>
<!-- /.content -->


<script>
     
</script>
