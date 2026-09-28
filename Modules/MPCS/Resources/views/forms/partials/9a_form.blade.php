<style>
    /* ===== SCOPED PRINT FORM LOOK ===== */
    /* Scope all "paper" styles to avoid breaking the rest of the application */
    #form_9a_print_area {
        font-family: "Times New Roman", serif;
        font-size: 12px;
        position: relative; /* For absolute positioning of F9A box */
    }

    #form_9a_print_area table {
        border-collapse: collapse !important;
        width: 100%;
    }

    #form_9a_print_area .table {
        border: none;
    }

    /* Thinner inner grid lines; outer outlines stay darker */
    #form_9a_print_area table.table-bordered {
        border: 1.5px solid #000 !important;
    }

    #form_9a_print_area .table-bordered th,
    #form_9a_print_area .table-bordered td {
        border: 0.5px solid #000 !important;
    }

    #form_9a_print_area th,
    #form_9a_print_area td {
        padding: 3px 4px !important;
        vertical-align: middle !important;
    }

    #form_9a_print_area th {
        font-weight: normal;
        text-align: center;
    }

    #form_9a_print_area td {
        text-align: right;
    }

    #form_9a_print_area .text-left {
        text-align: left !important;
    }

    #form_9a_print_area .text-center {
        text-align: center !important;
    }

    #form_9a_print_area .text-right {
        text-align: right !important;
    }

    /* INPUTS LOOK LIKE PAPER */
    #form_9a_print_area input.form-control {
        border: none !important;
        border-bottom: 1px solid #000 !important;
        border-radius: 0 !important;
        height: 18px !important;
        font-size: 12px !important;
        padding: 0 3px !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    /* HEADERS */
    #form_9a_print_area .form-title {
        font-size: 14px;
        font-weight: bold;
        text-align: center;
    }

    /* Match Select2 single select appearance (outside form print area) */
    .select2-like {
        height: 37px !important;
        padding: 6px 12px !important;
        border: 1px solid !important;
        border-radius: 4px !important;
        background-color: #fff !important;
        cursor: pointer;
    }

    .select2-like:focus {
        box-shadow: none !important;
    }

    /* Remove underline / input-style line inside table cells */
    #form_9a_print_area #form_15a9ab_payments_table .form-control {
        border: none !important;
        box-shadow: none !important;
        outline: none !important;
    }

    #form_9a_print_area #form_15a9ab_payments_table .form-control:focus {
        border: none !important;
        box-shadow: none !important;
    }



    /* ===== PRINT STYLES ===== */
    @page {
        size: A4 landscape;
        margin: 5mm;
    }

    @media print {
        /* ========== HIDE UNNEEDED UI; KEEP FORM AT TOP ========== */
        /* Hide everything by default, then reveal the form area */
        body * {
            visibility: hidden !important;
            
        }
        #form_9a_print_area,
        #form_9a_print_area * {
            visibility: visible !important;
        }

        /* Keep form in normal flow so all sections can paginate */
        #form_9a_print_area {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            right: auto !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            page-break-inside: auto !important;
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
        }
        
        /* Hide filters section - MULTIPLE SELECTORS FOR COMPLETE HIDING */
        #filters_section,
        .no-print,
        .box-header,
        .box.box-default,
        .box-default,
        #location_filter,
        #9a_date_ranges,
        .select2,
        .select2-container,
        .dropdown,
        section.content > .row.no-print,
        section.content > .row:first-child {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            width: 0 !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Hide buttons and tools */
        .btn,
        .box-tools,
        .print_report,
        #print_div,
        button {
            display: none !important;
            visibility: hidden !important;
        }
        
        /* Hide modals */
        .modal,
        .modal-backdrop {
            display: none !important;
        }
        
        /* Hide settings tab */
        #f9a_form_settings_tab {
            display: none !important;
        }
        
        /* ========== SHOW ONLY THE FORM CONTENT ========== */
        
        /* Reset body and html */
        html, body {
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: white !important;
            font-family: Arial, sans-serif !important;
            font-size: 10.5pt !important;
        }
        
        /* Make content wrapper full width */
        .content-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
            background: white !important;
        }
        .content-wrapper,
        section.content {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Full width content */
        .content,
        .container-fluid,
        section.content {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        section.content { margin-top: 0 !important; padding-top: 0 !important; }
        
        /* Show the form tab */
        #f9a_form_tab {
            display: block !important;
        }
        
        /* Remove box styling */
        .box,
        .box-primary,
        .card,
        .card-body,
        .box-body {
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            background: white !important;
        }
        
        /* Print content container */
        #print_content {
            display: block !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        /* ========== LAYOUT FOR FORM SECTIONS ========== */
        
        /* Rows - flex for side-by-side sections */
        .row {
            display: flex !important;
            flex-wrap: wrap !important;
            width: 100% !important;
            margin: 0 !important;
        }
        
        /* Bottom row with Receipts and Payments side-by-side */
        #receipts_payments_row,
        #print_content > .col-md-12 > .row:nth-child(2) {
            display: flex !important;
            flex-wrap: wrap !important;
        }

        /* Header spacing and alignment */
        .form-9a-header-row,
        .form-9a-meta-row {
            align-items: center !important;
            margin: 0 0 6px 0 !important;
            padding: 0 2px !important;
        }

        .form-9a-meta-text {
            font-size: 10.5pt !important;
            line-height: 1.3 !important;
        }

        #print_content {
            margin-top: 0 !important;
        }

        #form_9a_print_area {
            padding-top: 0 !important;
        }
        
        /* Columns */
        .col-md-12 { flex: 0 0 100% !important; max-width: 100% !important; padding: 2px !important; }
        .col-md-10 { flex: 0 0 83% !important; max-width: 83% !important; padding: 2px !important; }
        .col-md-6 { flex: 0 0 50% !important; max-width: 50% !important; padding: 2px !important; }
        .col-md-3 { flex: 0 0 25% !important; max-width: 25% !important; padding: 2px !important; }
        .col-md-2 { flex: 0 0 17% !important; max-width: 17% !important; padding: 2px !important; }
        
        /* ========== TABLE STYLES ========== */
        
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10.5pt !important;
            page-break-inside: avoid !important;
        }
        
        table.table-bordered {
            border: 1.5px solid #000 !important;
        }

        th, td,
        .table-bordered th,
        .table-bordered td {
            border: 0.5px solid #000 !important;
            padding: 2px 4px !important;
            font-size: 10pt !important;
            line-height: 1.2 !important;
        }
        
        th {
            background-color: #f5f5f5 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .table-responsive {
            overflow: visible !important;
        }
        
        /* ========== INPUT STYLES ========== */
        
        input.form-control {
            border: none !important;
            border-bottom: 1px solid #000 !important;
            background: transparent !important;
            font-size: 10pt !important;
            height: auto !important;
            padding: 1px 2px !important;
        }
        
        /* ========== HEADERS ========== */
        
        h4 {
            font-size: 13pt !important;
            margin: 2px 0 !important;
        }
        
        h5 {
            font-size: 10pt !important;
            margin: 2px 0 !important;
        }
        
        /* Form groups */
        .form-group {
            margin-bottom: 2px !important;
        }
    }
</style>

<section class="content" style="padding:0px;">

    <div class="row no-print" id="filters_section">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3" id="location_filter">
                    <div class="form-group">
                        {!! Form::label('form_9a_location_id', __('purchase.business_location') . ':') !!}

                        {!! Form::select('form_9a_location_id', $business_locations, $default_location_id ?? null, [
                            'id' => 'form_9a_location_id',
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}


                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('form_16a_date', __('report.date') . ':') !!}
                        <div class="dropdown">


                            {!! Form::text(
                                'date_range',
                                @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month'),
                                [
                                    'placeholder' => __('lang_v1.select_a_date_range'),
                                    'class' => 'form-control select2-like',
                                    'id' => '9a_date_ranges',
                                    'readonly',
                                ],
                            ) !!}

                        </div>
                    </div>

                </div>
                <div class="col-md-6" style="text-align: right; padding-top: 25px;">
                    <button class="btn btn-primary print_report" id="print_div">
                        <i class="fa fa-print"></i> @lang('messages.print')</button>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row" style="margin-top: 0px;">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                {{-- f9a-sheet carries the shared type scale and card treatment; see the
                     style block in form_9a.blade.php. --}}
                <div class="col-md-12 f9a-sheet" id="form_9a_print_area" style="padding: 0px;">

                    <div class="row" id="print_content" style="margin: 0px;">
                        <div class="col-md-12" style="padding: 0px;">
                            <!-- Header Area -->
                             <div style="position: relative; padding: 10px 0; width: 100%;">
                                 <!-- F9A Corner Box -->
                                 <div style="position: absolute; right: 5px; top: 5px; border: 2.5px solid #000; padding: 10px 20px; background-color: #fff; z-index: 10;">
                                     <h4 style="margin: 0; font-weight: bold; font-size: 22px;">F9A</h4>
                                 </div>
 
                                 <div class="text-center" style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                     <h4 style="margin: 0px; font-weight: bold; font-size: 16px;">Business Location</h4>
                                     <h4 class="form-title" id="business_location_name_header" style="margin: 4px 0 0 0; font-weight: bold; font-size: 24px; color: #000;">{{ $default_location_name ?? 'Location Name' }}</h4>
                                     
                                     <div style="margin-top: 15px; font-size: 14px; font-weight: bold;">
                                         Date Range From <span id="date_range_from" style="padding: 0 15px; border-bottom: 1px solid #000;">xxxx</span> To <span id="date_range_to" style="padding: 0 15px; border-bottom: 1px solid #000;">xxxx</span>
                                     </div>
                                     
                                     <div class="form-title" style="margin-top: 10px; font-size: 20px; text-decoration: underline;">Daily Cash & Sales Report</div>
                                 </div>
                             </div>

                            <!-- Meta Info Row (Form No) -->
                            <div class="row" style="margin: 10px 0 5px 0;">
                                <div class="col-md-6 text-left" style="font-size: 14px; font-weight: bold;">
                                    Form No: <span id="form_number" style="padding: 0 10px; border-bottom: 1px dashed #000;"></span>
                                </div>
                            </div>

                            <!-- Table Section -->
                            <div class="row" style="margin: 0px; margin-top: 5px;">
                                <div class="col-md-12">
                                    <div class="table-responsive f9a-sales-table-scroll">
                                        <table class="table table-bordered table-striped table-condensed" id="form_9a_sales_table"
                                            style="font-size: 10px;">
                                            <thead id="sales_table_head">
                                                <!-- Will be populated dynamically by JS -->
                                            </thead>

                                            <tbody id="sales_table_body">
                                                <!-- Will be populated dynamically by JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="f9a-sales-slider" class="no-print" aria-label="Horizontal table slider">
                                        <input id="f9a-sales-slider-input" type="range" min="0" max="1000" value="0" step="1" aria-label="Scroll Daily Cash and Sales Report horizontally">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- Receipts & Payments — inside print area so @media print shows them -->
                    <div class="row" id="receipts_payments_row" style="margin: 10px 0;">
                        <div class="col-md-6" style="padding-left: 0;">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="form_15a9ab_receipts_table" style="font-size: 10px;">
                                    <colgroup>
                                        <col style="width:20%">
                                        <col style="width:40%">
                                        <col style="width:20%">
                                        <col style="width:20%">
                                    </colgroup>
                                    <thead>
                                        <tr style="background-color: #f9f9f9;">
                                            <th colspan="4" class="text-center" style="font-size: 12px; font-weight: bold;">Receipts Section</th>
                                        </tr>
                                        <tr>
                                            <th>Previous Day</th>
                                            <th>Description</th>
                                            <th>Today</th>
                                            <th>Total as of Today</th>
                                        </tr>
                                    </thead>

                                    <tbody id="receipts_subcat_body">
                                        <!-- Will be populated dynamically by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold" style="background-color: #f9f9f9;">
                                            <td id="receipts_total_prev"></td>
                                            <td class="text-center">Total</td>
                                            <td id="receipts_total_today"></td>
                                            <td id="receipts_total_total"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <!-- Numbered Items -->
                            <div class="form-group" style="margin-bottom: 20px; font-size: 10px;">
                                <div style="margin-bottom: 10px;">
                                    1. Cash Bill No ................<br>
                                    2. Deposit Bill No ................<br>
                                    3. Credit Bill No ................<br>
                                    4. MPCS Branches ................<br>
                                    Date ..............
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" style="padding-right: 0;">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="form_15a9ab_payments_table" style="font-size: 10px;">
                                    <colgroup>
                                        <col style="width:25%">
                                        <col style="width:35%">
                                        <col style="width:20%">
                                        <col style="width:20%">
                                    </colgroup>
                                    <thead>
                                        <tr style="background-color: #f9f9f9;">
                                            <th colspan="4" class="text-center" style="font-size: 12px; font-weight: bold;">Payments</th>
                                        </tr>
                                        <tr>
                                            <th class="align-middle text-center">Previous Day</th>
                                            <th class="align-middle text-center">Description</th>
                                            <th class="align-middle text-center">Today</th>
                                            <th class="align-middle text-center">Total as of Today</th>
                                        </tr>
                                    </thead>
                                    <tbody id="payments_table_body">
                                        <!-- Will be populated dynamically by JS -->
                                    </tbody>
                                </table>

                            </div>
                            <div class="mt-3 mb-4">
                                <span id="textdetail"></span>
                            </div>
                            <div class="signature-area" style="margin-top: 20px;">
                                <div class="row" style="margin-top:20px;">
                                    <div class="col-md-6 text-center" style="font-size: 10px;">
                                        ........................................<br>
                                        Cashier Signature
                                    </div>
                                    <div class="col-md-6 text-center" style="font-size: 10px;">
                                        ........................................<br>
                                        Store Keeper Signature
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end receipts_payments_row -->
                </div>
                <!-- end form_9a_print_area -->
            @endcomponent
        </div>
    </div>
</section>
