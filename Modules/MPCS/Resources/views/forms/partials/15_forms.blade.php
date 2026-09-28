<section class="content">
    <style>
        .editable {
            display: inline-block;
            min-width: 80px;
            padding: 3px 6px;
            border-bottom: 1px dotted #999;
            cursor: text;
            color: #000;
        }

        .editable:empty::before {
            content: '.............';
            color: #ccc;
        }

        .editable:focus {
            outline: none;
            background-color: #fdfdfd;
        }

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
            padding: 6px 0;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        @media (max-width: 768px) {

            #form_f15_table th,
            #form_f15_table td {
                padding: 5px;
            }
        }

        .total-row {
            background-color: #f1f1f1;
            font-weight: bold;
            color: #000;
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

        .total-row td:first-child {
            font-weight: bold;
            font-size: 13px !important;
            text-align: right !important;
            padding-right: 10px !important;
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

        #form_f15_table input[type="text"],
        #form_f15_table input[type="number"] {
            width: 150px;
            padding: 2px 6px;
            font-size: 13px;
            box-sizing: border-box;
        }

        /* Stable F15 report columns.
           Date/Ajax changes update only cell values and must never resize the table. */
        #form_f15_table {
            table-layout: fixed;
            width: 100%;
            min-width: 820px;
        }

        #form_f15_table col.f15-description-col {
            width: 24%;
        }

        #form_f15_table col.f15-equal-data-col {
            width: 19%;
        }

        #form_f15_table th,
        #form_f15_table td {
            box-sizing: border-box;
        }

        #form_f15_table th:nth-child(n+2),
        #form_f15_table td:nth-child(n+2) {
            padding-left: 8px;
            padding-right: 8px;
        }

        /* Ref numbers may wrap, while statutory monetary values remain on one line. */
        #form_f15_table th:nth-child(2),
        #form_f15_table td:nth-child(2) {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        #form_f15_table th:nth-child(n+3),
        #form_f15_table td:nth-child(n+3) {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
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

        .form-input {
            height: 28px;
            padding: 2px 6px;
            font-size: 13px;
        }

        .tb-previous-data td {
            padding: 5px 0;
        }

        .f15-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 10px 14px;
            margin: 8px 0 12px;
            width: 100%;
        }

        .f15-summary-field {
            display: grid;
            grid-template-columns: minmax(115px, auto) minmax(90px, 1fr);
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .f15-summary-field label {
            margin: 0;
            font-weight: 600;
            white-space: nowrap;
        }

        .f15-summary-field .form-input {
            width: 100%;
            min-width: 0;
        }

        @media (max-width: 1200px) {
            .f15-summary-grid {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }
        }

        @media (max-width: 640px) {
            .f15-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        #form_15_date_range {
            max-width: 150px;
        }
    </style>@if(session('success') || session('error'))
        <div id="custom-alert" class="custom-alert {{ session('success') ? 'success' : 'error' }}">
            <span>{{ session('success') ?? session('error') }}</span>
            <button type="button" id="f15_close_alert" aria-label="Close">×</button>
        </div>
    @endif
    <div class="row" style="">
        <div class="col-md-4 justify-content-center" style="">
            <div class="form-group">
                {!! Form::text(
                'date_range',
                date('Y-m-d'),
                [
                'placeholder' => __('lang_v1.select_a_date'),
                'class' => 'form-control',
                'id' => 'form_15_date_range',
                'autocomplete' => 'off',
                ],
                ) !!}
            </div>
        </div>
        <div class="col-md-8 row" style="padding-top: 0; margin-top: 0; display: flex; justify-content: end; gap: 20px">
            <div class="form-group">
                <button class="btn btn-success btn-sm" id="printButton">Print&nbsp;<i class="fa fa-print"
                                                                                      aria-hidden="true"></i></button>
            </div>
        </div>
    </div>
    <hr>
    <div class="row" id="f15_print_area">
        <div class="col-md-12">
            <div class="row" style="margin: 5px 0;">
                <div class="col-sm-12 text-center">

                    <h3>{{ $business_name }}</h3>
                    <h5>Filling Station <br></h5>
                </div>
                <div class="col-sm-12" style="display: flex; justify-content: start; gap: 10px; align-items: center">
                    <h3 style="">F15</h3>
                    <h5 style="font-size: 15px;font-weight: bolder;margin-top: 8px;">Form No. &nbsp;
                        <span id="15f_form_no"></span></h5>
                </div>
                <div class="f15-summary-grid">
                    <div class="f15-summary-field">
                        <label>Balance in Hand</label>
                        <input type="number" class="form-control form-input" placeholder="0.00" step="0.01">
                    </div>
                    <div class="f15-summary-field">
                        <label>Received</label>
                        <input type="number" class="form-control form-input" placeholder="0.00" step="0.01">
                    </div>
                    <div class="f15-summary-field">
                        <label>Balance Stock Note</label>
                        <input type="number" class="form-control form-input" placeholder="0.00" step="0.01">
                    </div>
                    <div class="f15-summary-field">
                        <label>Other Payments</label>
                        <input type="number" class="form-control form-input" placeholder="0.00" step="0.01">
                    </div>
                    <div class="f15-summary-field">
                        <label>Balance in Hand</label>
                        <input type="number" class="form-control form-input" placeholder="0.00" step="0.01">
                    </div>
                    <div class="f15-summary-field">
                        <label>For the Sale Price</label>
                        <input type="number" class="form-control form-input" placeholder="0.00" step="0.01">
                    </div>
                    <div class="f15-summary-field">
                        <label>Sale Maximum Limit</label>
                        <input type="number" class="form-control form-input" placeholder="0.00" step="0.01">
                    </div>
                    <div class="f15-summary-field">
                        <label>Minimum</label>
                        <input type="number" class="form-control form-input" placeholder="0.00" step="0.01">
                    </div>
                </div>
            </div>
            <table id="form_f15_table">
                <colgroup>
                    <col class="f15-description-col">
                    <col class="f15-equal-data-col">
                    <col class="f15-equal-data-col">
                    <col class="f15-equal-data-col">
                    <col class="f15-equal-data-col">
                </colgroup>
                <thead>
                <tr>
                    <th>Description</th>
                    <th>Ref Book No</th>
                    <th>Up to Previous Date<br><small>(Rs / Cts)</small></th>
                    <th>Today<br><small>(Rs / Cts)</small></th>
                    <th>As of Today<br><small>(Rs / Cts)</small></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Store Purchase</td>
                    <td id="store_purchase_book_no"></td>
                    <td class="text-right store_purchase_previous"></td>
                    <td class="text-right store_purchase_today"></td>
                    <td class="text-right store_purchase_total"></td>
                </tr>
                <tr>
                    <td>Direct Purchase</td>
                    <td id="direct_purchase_book_no"></td>
                    <td class="text-right direct_purchase_previous"></td>
                    <td class="text-right direct_purchase_today"></td>
                    <td class="text-right direct_purchase_total"></td>
                </tr>
                <tr>
                    <td>Sub Total</td>
                    <td></td>
                    <td class="text-right sub_total_previous"></td>
                    <td class="text-right sub_total_today"></td>
                    <td class="text-right sub_total_total"></td>
                </tr>
                <tr>
                    <td>Price Increment</td>
                    <td id="price_increment_form_numbers"></td>
                    <td class="text-right price_increment_previous"></td>
                    <td class="text-right price_increment_today"></td>
                    <td class="text-right price_increment_total"></td>
                </tr>
                <tr>
                    <td>Changes</td>
                    <td></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                </tr>
                <tr class="total-row">
                    <td>Total</td>
                    <td></td>
                    <td class="text-right total_purchase_previous"></td>
                    <td class="text-right total_purchase_today"></td>
                    <td class="text-right total_purchase_total"></td>
                </tr>
                <tr>
                    <td>Opening Stock</td>
                    <td id="opening_stock_f22_book"></td>
                    <td class="text-right opening_stock_previous"></td>
                    <td class="text-right opening_stock_today"></td>
                    <td class="text-right opening_stock_total"></td>
                </tr>
                <tr>
                    <td>Grand Total</td>
                    <td></td>
                    <td class="text-right grand_total1_previous"></td>
                    <td class="text-right grand_total1_today"></td>
                    <td class="text-right grand_total1_total"></td>
                </tr>
                <tr>
                    <td class="indent-1">Cash Sale</td>
                    <td rowspan="3" id="form_9a_number"></td>
                    <td class="text-right cash_previous"></td>
                    <td class="text-right cash_today"></td>
                    <td class="text-right cash_total"></td>
                </tr>
                <tr>
                    <td class="indent-1">Card Sale</td>
                    <td class="text-right card_previous"></td>
                    <td class="text-right card_today"></td>
                    <td class="text-right card_total"></td>
                </tr>
                <tr>
                    <td class="indent-1">Credit Sale</td>
                    <td class="text-right credit_previous"></td>
                    <td class="text-right credit_today"></td>
                    <td class="text-right credit_total"></td>
                </tr>
                <tr class="total-row">
                    <td>Total</td>
                    <td></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                </tr>
                <tr>
                    <td>Changes (18)</td>
                    <td></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                </tr>
                <tr>
                    <td class="indent-1">Price Reduction</td>
                    <td id="price_reduction_form_numbers"></td>
                    <td class="text-right price_reduction_previous"></td>
                    <td class="text-right price_reduction_today"></td>
                    <td class="text-right price_reduction_total"></td>
                </tr>
                <tr>
                    <td class="indent-1">Damaged</td>
                    <td></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                </tr>
                <tr>
                    <td class="indent-1">Others</td>
                    <td></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                </tr>
                <tr>
                    <td>Total Return</td>
                    <td></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                </tr>
                <tr class="total-row">
                    <td>Total Sale</td>
                    <td></td>
                    <td class="text-right total_sale_previous"></td>
                    <td class="text-right total_sale_today"></td>
                    <td class="text-right total_sale_total"></td>
                </tr>
                <tr>
                    <td>Balance Stock in Sale Price</td>
                    <td></td>
                    <td class="text-right balance_stock_previous"></td>
                    <td class="text-right balance_stock_today"></td>
                    <td class="text-right balance_stock_total"></td>
                </tr>
                <tr class="grand-total-row total-row">
                    <td>Grand Total</td>
                    <td></td>
                    <td class="text-right grand_total2_previous"></td>
                    <td class="text-right grand_total2_today"></td>
                    <td class="text-right grand_total2_total"></td>
                </tr>
                </tbody>
            </table>
            <div class="note-container">
                <div class="form-group" style="margin-top: 80px;">
                    <textarea rows="3" cols="160" class="form-control" required style="padding: 15px;">Note </textarea>
                </div>
            </div>
            <table width="100%" style="margin-top: 40px;" class="no-border-table">
                <tr>
                    <td align="center" width="50%">.............................. <br> Checked By</td>
                    <td align="center" width="50%">.............................. <br> Manager</td>
                </tr>
            </table>
        </div>

    </div>
</section>
