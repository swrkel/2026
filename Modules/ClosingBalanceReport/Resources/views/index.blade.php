@extends('layouts.app')

@section('title', __('closingbalancereport::lang.module_name'))

@push('css')
<style>
    /* Remove excessive gap under the page heading on this report */
    .closing-balance-report-page .content-header {
        padding: 8px 15px 4px 15px;
        margin-bottom: 4px;
    }

    .closing-balance-report-page #closing-balance-report-page {
        padding-top: 4px;
    }

    .closing-balance-report-page #closing-balance-report-page .cb-card:first-of-type {
        margin-top: 0;
    }

    #closing-balance-report-page .cb-toolbar .btn {
        background: #8f2d82 !important;
        border-color: #8f2d82 !important;
        color: #fff !important;
        margin: 0;
        border-radius: 0;
        padding: 8px 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-right: 1px solid #7a246f;
    }

    #closing-balance-report-page .cb-toolbar {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    #closing-balance-report-page .cb-toolbar .btn:first-child {
        border-radius: 3px 0 0 3px;
    }

    #closing-balance-report-page .cb-toolbar .btn:last-child {
        margin-right: 0;
        border-right: none;
        border-radius: 0 3px 3px 0;
    }

    #closing-balance-report-page .cb-toolbar .btn:hover,
    #closing-balance-report-page .cb-toolbar .btn:focus {
        color: #fff !important;
        background: #7a246f !important;
        border-color: #7a246f !important;
    }

    #closing-balance-report-page .section-title {
        color: #c0392b;
        font-weight: 600;
        margin-top: 16px;
        margin-bottom: 12px;
    }

    #closing-balance-report-page #financial_summary_table th,
    #closing-balance-report-page #financial_summary_table td {
        text-align: center;
        vertical-align: middle;
        padding: 6px 8px;
    }

    #closing-balance-report-page .cb-footer {
        margin-top: 12px;
        font-size: 12px;
        color: #555;
    }

    #closing-balance-report-page .cb-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        padding: 16px 18px 14px;
        margin-bottom: 14px;
        border-radius: 4px;
    }

    #closing-balance-report-page .cb-toolbar-holder {
        gap: 12px;
        margin-bottom: 6px;
    }

    /* Compact tables */
    #closing-balance-report-page table.dataTable tbody th,
    #closing-balance-report-page table.dataTable tbody td,
    #closing-balance-report-page table.dataTable thead th {
        padding: 6px 8px !important;
        line-height: 1.2 !important;
    }

    #closing-balance-report-page .dataTables_length,
    #closing-balance-report-page .dataTables_filter {
        margin-bottom: 6px;
    }

    #closing-balance-report-page .dt-buttons {
        float: none !important;
        display: inline-flex !important;
        flex-wrap: wrap;
        justify-content: center;
    }

    #closing-balance-report-page .dt-buttons .dt-button {
        background: #8f2d82 !important;
        border-color: #8f2d82 !important;
        color: #fff !important;
        padding: 8px 12px !important;
        box-shadow: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        margin: 0 !important;
        border-radius: 0 !important;
        border-right: 1px solid #7a246f !important;
    }

    #closing-balance-report-page .dt-buttons .dt-button:first-child {
        border-radius: 3px 0 0 3px !important;
    }

    #closing-balance-report-page .dt-buttons .dt-button:last-child {
        margin-right: 0 !important;
        border-right: none !important;
        border-radius: 0 3px 3px 0 !important;
    }

    #closing-balance-report-page .table-responsive {
        margin-bottom: 0;
    }

    #closing-balance-report-page .dataTables_wrapper {
        overflow: visible;
    }

    #closing-balance-report-page .table-responsive {
        overflow: visible !important;
    }

    /* Print-only elements - hidden on screen */
    .print-only {
        display: none;
    }

    @media print {
        /* Hide page header */
        .closing-balance-report-page .content-header {
            display: none !important;
        }

        /* Keep business / location / date card visible for the report */
        .cb-card.d-flex.flex-wrap {
            display: none !important;
        }

        /* Top filters accordion, product filters, toolbars, search rows */
        #closing-balance-report-page .cb-hide-print,
        #closing-balance-report-page .cb-controls-row,
        .filters,
        .box-body {
            display: none !important;
        }

        /* Toolbar buttons (scoped — avoid breaking DataTables print popup) */
        #closing-balance-report-page .cb-toolbar,
        #closing-balance-report-page .cb-toolbar-main,
        #closing-balance-report-page .dt-buttons,
        #closing-balance-report-page .dt-button {
            display: none !important;
        }

        /* Hide all DataTables UI controls on main page */
        #closing-balance-report-page .dataTables_filter,
        #closing-balance-report-page .dataTables_length,
        #closing-balance-report-page .dataTables_info,
        #closing-balance-report-page .dataTables_paginate,
        #closing-balance-report-page .dataTables_wrapper .row:first-child,
        #closing-balance-report-page .dataTables_wrapper .row:last-child {
            display: none !important;
        }

        /* Show print-only header */
        .print-only {
            display: block !important;
        }

        /* Print header styling */
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .print-header h2 {
            margin: 5px 0;
            font-size: 18pt;
            font-weight: bold;
        }

        .print-header .print-meta {
            font-size: 11pt;
            margin: 3px 0;
        }

        /* Section headings */
        #closing-balance-report-page .section-title {
            font-size: 14pt !important;
            font-weight: bold !important;
            color: #000 !important;
            margin-top: 16px !important;
            margin-bottom: 8px !important;
            page-break-after: avoid !important;
        }

        /* Table styles */
        #closing-balance-report-page table,
        #closing-balance-report-page th,
        #closing-balance-report-page td {
            border: 1px solid #000 !important;
            page-break-inside: avoid;
        }

        #closing-balance-report-page th,
        #closing-balance-report-page td {
            padding: 6px !important;
        }

        #closing-balance-report-page thead {
            display: table-header-group;
        }

        #closing-balance-report-page tfoot {
            display: table-footer-group;
            font-weight: bold;
        }

        /* Remove card styling in print */
        #closing-balance-report-page .cb-card {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 8px 0 !important;
            margin-bottom: 0 !important;
        }

        /* Business meta card: readable in print */
        #closing-balance-report-page .cb-card.text-center {
            border-bottom: 1px solid #000 !important;
            margin-bottom: 10px !important;
            padding-bottom: 10px !important;
        }

        /* Footer styling */
        #closing-balance-report-page .cb-footer {
            display: block !important;
            text-align: center;
            font-size: 10pt;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
        }

        /* Page break control */
        section.cb-card {
            page-break-inside: avoid;
        }
    }

    #closing-balance-report-page .dt-button-collection {
        z-index: 999999 !important;
    }

    #closing-balance-report-page .cb-controls-row {
        margin-bottom: 6px;
        gap: 12px;
        width: 100%;
    }

    #closing-balance-report-page .dataTables_length label,
    #closing-balance-report-page .dataTables_filter label {
        margin-bottom: 0;
    }

    #closing-balance-report-page .dataTables_filter input {
        height: 32px;
    }

    #closing-balance-report-page .dt-buttons-host {
        flex: 1 1 auto;
        text-align: center;
    }
</style>
@endpush

@push('javascript')
@endpush

@section('content')
<div class="closing-balance-report-page">
<section class="content-header">
    <h1>{{ __('closingbalancereport::lang.module_name') }}</h1>
</section>

<section class="content" id="closing-balance-report-page"  style="margin-top: -55px">
    
    <div class="cb-card text-center">
        <h3 class="mb-1">{{ $business->name ?? '' }}</h3>
        <div id="cb_selected_location" class="text-muted">
            {{ $locations[$defaultLocation] ?? __('closingbalancereport::lang.all') }}
        </div>
         <div class="print-meta" style="margin-bottom: 10px;">
             <span id="print_dates"></span>
             @if (!empty($systemToken))
                 <div id="cb_system_token_meta">{{ $systemToken }}</div>
             @else
                 <div id="cb_system_token_meta" class="hide"></div>
             @endif
        </div>
    </div>


       
  

    <div class="row cb-hide-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('closingbalancereport::lang.filters')])
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('closing_balance_date_range', __('closingbalancereport::lang.date_range') . ':') !!}
                        {!! Form::text('closing_balance_date_range', null, ['class' => 'form-control', 'readonly', 'id' => 'closing_balance_date_range']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('closing_balance_location', __('closingbalancereport::lang.business_location') . ':') !!}
                        {!! Form::select('closing_balance_location', $locations, $defaultLocation, ['class' => 'form-control select2', 'id' => 'closing_balance_location', 'style' => 'width:100%']) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <section id="fs-section" class="cb-card">
        <div class="text-center mb-3 cb-hide-print">
            <div class="cb-toolbar cb-toolbar-main">
                <button class="btn btn-sm" id="cb_export_csv"><i class="fa fa-file-text-o"></i> Export to CSV</button>
                <button class="btn btn-sm" id="cb_export_excel"><i class="fa fa-file-excel-o"></i> Export to Excel</button>
                <button class="btn btn-sm" id="cb_toggle_columns"><i class="fa fa-table"></i> Column Visibility</button>
                <button class="btn btn-sm" id="cb_export_pdf_top" type="button"><i class="fa fa-file-pdf-o"></i> Export to PDF</button>
                <button class="btn btn-sm" id="cb_print"><i class="fa fa-print"></i> Print</button>
            </div>
        </div>

        <h4 class="section-title">{{ __('closingbalancereport::lang.financial_summary') }}</h4>
        <div class="table-responsive">
            <table class="table table-bordered" id="financial_summary_table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Cash</th>
                        <th>Cards</th>
                        <th>Cheques</th>
                        <th>Credit Sales</th>
                        <th>Purchases</th>
                        <th>Expenses</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Previous Day BF Balance</td>
                        <td class="text-right" id="prev_cash">0.00</td>
                        <td class="text-right" id="prev_card">0.00</td>
                        <td class="text-right" id="prev_cheque">0.00</td>
                        <td class="text-right" id="prev_credit_sales">0.00</td>
                        <td class="text-right" id="prev_purchases">0.00</td>
                        <td class="text-right" id="prev_expenses">0.00</td>
                    </tr>
                    <tr>
                        <td>Today</td>
                        <td class="text-right" id="today_cash">0.00</td>
                        <td class="text-right" id="today_card">0.00</td>
                        <td class="text-right" id="today_cheque">0.00</td>
                        <td class="text-right" id="today_credit_sales">0.00</td>
                        <td class="text-right" id="today_purchases">0.00</td>
                        <td class="text-right" id="today_expenses">0.00</td>
                    </tr>
                    <tr>
                        <td>Balance</td>
                        <td class="text-right" id="balance_cash">0.00</td>
                        <td class="text-right" id="balance_card">0.00</td>
                        <td class="text-right" id="balance_cheque">0.00</td>
                        <td class="text-right" id="balance_credit_sales">0.00</td>
                        <td class="text-right" id="balance_purchases">0.00</td>
                        <td class="text-right" id="balance_expenses">0.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="sales-section" class="cb-card">
        <div class="text-center mb-3 cb-hide-print">
            <div class="cb-toolbar" id="sales-buttons"></div>
        </div>

        <h4 class="section-title">{{ __('closingbalancereport::lang.sales') }}</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="sales_table" style="width:100%">
                <thead>
                    <tr>
                        <th>Product Sub category</th>
                        <th>Qty Sold</th>
                        <th>Sold Amount (After Discount)</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th class="text-right">Total</th>
                        <th class="text-right" id="sales_total_qty">0.00</th>
                        <th class="text-right" id="sales_total_amount">0.00</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section id="product-section" class="cb-card">
        <div class="row cb-hide-print">
            @php
                $categoryOptions = ['' => __('closingbalancereport::lang.all')] + $categories->toArray();
                $subCategoryOptions = ['' => __('closingbalancereport::lang.all')] + $subCategories->toArray();
            @endphp
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('product_category_filter', __('closingbalancereport::lang.product_category') . ':') !!}
                    {!! Form::select('product_category_filter', $categoryOptions, '', ['class' => 'form-control select2', 'id' => 'product_category_filter', 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('product_sub_category_filter', __('closingbalancereport::lang.product_sub_category') . ':') !!}
                    {!! Form::select('product_sub_category_filter', $subCategoryOptions, '', ['class' => 'form-control select2', 'id' => 'product_sub_category_filter', 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('product_filter', __('closingbalancereport::lang.product') . ':') !!}
                    {!! Form::select('product_filter', ['' => __('closingbalancereport::lang.all')], '', ['class' => 'form-control select2', 'id' => 'product_filter', 'style' => 'width:100%']) !!}
                </div>
            </div>
        </div>

        <div class="text-center mb-3 cb-hide-print">
            <div class="cb-toolbar" id="product-buttons"></div>
        </div>

        <h4 class="section-title">{{ __('closingbalancereport::lang.product_report_summary') }}</h4>

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="product_summary_table" style="width:100%">
                <thead>
                    <tr>
                        <th>Product Sub category</th>
                        <th>Product</th>
                        <th>Product Code</th>
                        <th>Opening Qty</th>
                        <th>Purchase / Sales Returned Qty</th>
                        <th>Sold / Purchase Returned Qty</th>
                        <th>Balance Qty</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th class="text-right">Total</th>
                        <th></th>
                        <th></th>
                        <th class="text-right" id="product_total_opening">0.00</th>
                        <th class="text-right" id="product_total_in">0.00</th>
                        <th class="text-right" id="product_total_out">0.00</th>
                        <th class="text-right" id="product_total_balance">0.00</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div class="cb-footer" id="cb_footer_content">
        {{ $systemFooter }}
    </div>
</section>
@endsection

@section('javascript')
<script>
    /**
     * DataTables "Print" opens a separate window — inject the same meta + system footer as the full report.
     */
    function closingBalanceSectionPrintCustomize(win, sectionTitle) {
        const $body = $(win.document.body);
        $body.prepend(
            $('<h2></h2>').text(sectionTitle).css({
                'text-align': 'center',
                'margin': '0 0 12px',
                'font-size': '16px',
                'font-weight': 'bold'
            })
        );
        // The mandatory five-row header and Super Admin footer are injected
        // by the application-wide DataTables print wrapper.
    }

    $(document).ready(function() {
        let salesTable;
        let productTable;
        let financialSummaryTable;

        const dateInput = $('#closing_balance_date_range');
        const locationInput = $('#closing_balance_location');
        const categoryInput = $('#product_category_filter');
        const subCategoryInput = $('#product_sub_category_filter');
        const productInput = $('#product_filter');

        dateInput.daterangepicker({
            startDate: moment(),
            endDate: moment(),
            locale: {
                format: moment_date_format,
                cancelLabel: LANG.clear
            },
            ranges: {
                "{{ __('closingbalancereport::lang.today') }}": [moment(), moment()],
                "{{ __('closingbalancereport::lang.yesterday') }}": [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                "{{ __('closingbalancereport::lang.custom_date_range') }}": [moment().startOf('month'), moment().endOf('month')]
            }
        }, function(start, end) {
            dateInput.val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            refreshDateLabel();
            reloadAll();
        });
        dateInput.val(moment().format(moment_date_format) + ' ~ ' + moment().format(moment_date_format));
        refreshDateLabel();

        const selectAllLabel = "{{ __('closingbalancereport::lang.all') }}";

        $('.select2').select2({
            allowClear: false,
            placeholder: null,
            minimumResultsForSearch: 0,
            width: '100%'
        });

        function getRange() {
            const picker = dateInput.data('daterangepicker');
            return {
                start: picker.startDate.format('YYYY-MM-DD'),
                end: picker.endDate.format('YYYY-MM-DD')
            };
        }

        function reloadAll() {
            refreshLocationLabel();
            loadSummary();
            if (salesTable) {
                salesTable.ajax.reload();
            }
            if (productTable) {
                productTable.ajax.reload();
            }
        }

        locationInput.on('change', reloadAll);

        categoryInput.on('change', function() {
            const categoryId = $(this).val();
            loadSubCategories(categoryId);
            productInput.val(null).trigger('change');
            reloadAll();
        });

        subCategoryInput.on('change', function() {
            productInput.val(null).trigger('change');
            reloadAll();
        });

        productInput.on('change', reloadAll);

        function refreshLocationLabel() {
            const selected = locationInput.find('option:selected').text();
            $('#cb_selected_location').text(selected || "{{ __('closingbalancereport::lang.all') }}");
            $('#cb_selected_location_meta').text(selected || "{{ __('closingbalancereport::lang.all') }}");
            // Sync print header
            $('#print_location').text(selected || "{{ __('closingbalancereport::lang.all') }}");
        }
        function refreshDateLabel() {
            $('#cb_selected_dates').text(dateInput.val());
            // Sync print header
            $('#print_dates').text(dateInput.val());
        }
        refreshDateLabel();

        function formatNumber(value) {
            const num = parseFloat(value || 0);
            return __currency_trans_from_en(num, true);
        }

        function updateSummaryRow(prefix, data) {
            $('#'+prefix+'_cash').text(formatNumber(data.cash));
            $('#'+prefix+'_card').text(formatNumber(data.card));
            $('#'+prefix+'_cheque').text(formatNumber(data.cheque));
            $('#'+prefix+'_credit_sales').text(formatNumber(data.credit_sales));
            $('#'+prefix+'_purchases').text(formatNumber(data.purchases));
            $('#'+prefix+'_expenses').text(formatNumber(data.expenses));
        }

        function loadSummary() {
            const range = getRange();
            $.get("{{ route('closing-balance-report.summary') }}", {
                start_date: range.start,
                end_date: range.end,
                location_id: locationInput.val()
            }, function(res) {
                updateSummaryRow('prev', res.previous || {});
                updateSummaryRow('today', res.current || {});
                updateSummaryRow('balance', res.balance || {});
            });
        }

        function loadSubCategories(categoryId) {
            $.get("{{ route('closing-balance-report.sub-categories') }}", {
                category_id: categoryId
            }, function(data) {
                const currentVal = subCategoryInput.val();
                subCategoryInput.empty();
                subCategoryInput.append(new Option(selectAllLabel, '', !currentVal, !currentVal));
                data.forEach(function(item) {
                    subCategoryInput.append(new Option(item.name, item.id, false, false));
                });
                if (currentVal && subCategoryInput.find(`option[value="${currentVal}"]`).length) {
                    subCategoryInput.val(currentVal).trigger('change');
                } else {
                    subCategoryInput.val('').trigger('change');
                }
            });
        }

        productInput.select2({
            placeholder: null,
            allowClear: false,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('closing-balance-report.products') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    const range = getRange();
                    return {
                        q: params.term || '',
                        category_id: categoryInput.val(),
                        sub_category_id: subCategoryInput.val(),
                        location_id: locationInput.val(),
                        start_date: range.start,
                        end_date: range.end
                    };
                },
                processResults: function(data) {
                    return data;
                }
            }
        });

        // Initialize Financial Summary table with DataTables for column visibility
        financialSummaryTable = $('#financial_summary_table').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            dom: 't',  // Only show table, hide buttons
            buttons: [
                { 
                    extend: 'colvis', 
                    className: 'btn btn-sm btn-primary', 
                    text: '<i class="fa fa-table"></i> Column Visibility' 
                }
            ]
        });

        salesTable = $('#sales_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('closing-balance-report.sales') }}",
                data: function(d) {
                    const range = getRange();
                    d.start_date = range.start;
                    d.end_date = range.end;
                    d.location_id = locationInput.val();
                }
            },
            columns: [
                { data: 'sub_category', name: 'sub_category' },
                { data: 'qty_sold', name: 'qty_sold', className: 'text-right' },
                { data: 'sold_amount', name: 'sold_amount', className: 'text-right' },
            ],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'colvis', className: 'btn btn-sm btn-primary', text: '<i class="fa fa-table"></i> Column Visibility' },
                { text: '<i class="fa fa-file-pdf-o"></i> Export to PDF', className: 'btn btn-sm btn-primary', action: function() { exportClosingBalanceReportAsPDF(); } },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-primary',
                    text: '<i class="fa fa-print"></i> Print',
                    footer: true,
                    customize: function(win) {
                        closingBalanceSectionPrintCustomize(win, @json(__('closingbalancereport::lang.sales')));
                    }
                }
            ],
            initComplete: function() {
                const wrapper = $('#sales_table_wrapper');
                
                // Move DataTables buttons into the pre-existing toolbar container
                const buttons = salesTable.buttons().container();
                $('#sales-buttons').empty().append(buttons);
                
                // Create controls row for show entries and search, append AFTER the table
                const controls = $('<div class="cb-controls-row d-flex align-items-center justify-content-between flex-wrap"></div>');
                wrapper.find('.dataTables_length').appendTo(controls);
                wrapper.find('.dataTables_filter').appendTo(controls);
                controls.prependTo(wrapper);
            },
            drawCallback: function() {
                const summary = salesTable.ajax.json().summary || {};
                $('#sales_total_qty').text(summary.qty || '0.00');
                $('#sales_total_amount').text(summary.amount || '0.00');
            }
        });

        productTable = $('#product_summary_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('closing-balance-report.product-summary') }}",
                data: function(d) {
                    const range = getRange();
                    d.start_date = range.start;
                    d.end_date = range.end;
                    d.location_id = locationInput.val();
                    d.category_id = categoryInput.val();
                    d.sub_category_id = subCategoryInput.val();
                    d.product_id = productInput.val();
                }
            },
            columns: [
                { data: 'sub_category', name: 'sub_category' },
                { data: 'product', name: 'product' },
                { data: 'code', name: 'code' },
                { data: 'opening_qty', name: 'opening_qty', className: 'text-right' },
                { data: 'in_qty', name: 'in_qty', className: 'text-right' },
                { data: 'out_qty', name: 'out_qty', className: 'text-right' },
                { data: 'balance_qty', name: 'balance_qty', className: 'text-right' },
            ],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'colvis', className: 'btn btn-sm btn-primary', text: '<i class="fa fa-table"></i> Column Visibility' },
                { text: '<i class="fa fa-file-pdf-o"></i> Export to PDF', className: 'btn btn-sm btn-primary', action: function() { exportClosingBalanceReportAsPDF(); } },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-primary',
                    text: '<i class="fa fa-print"></i> Print',
                    footer: true,
                    customize: function(win) {
                        closingBalanceSectionPrintCustomize(win, @json(__('closingbalancereport::lang.product_report_summary')));
                    }
                }
            ],
            initComplete: function() {
                const wrapper = $('#product_summary_table_wrapper');
                
                // Move DataTables buttons into the pre-existing toolbar container
                const buttons = productTable.buttons().container();
                $('#product-buttons').empty().append(buttons);
                
                // Create controls row for show entries and search, append AFTER the table
                const controls = $('<div class="cb-controls-row d-flex align-items-center justify-content-between flex-wrap"></div>');
                wrapper.find('.dataTables_length').appendTo(controls);
                wrapper.find('.dataTables_filter').appendTo(controls);
                controls.prependTo(wrapper);
            },
            drawCallback: function() {
                const summary = productTable.ajax.json().summary || {};
                $('#product_total_opening').text(summary.opening || '0.00');
                $('#product_total_in').text(summary.in_qty || '0.00');
                $('#product_total_out').text(summary.out_qty || '0.00');
                $('#product_total_balance').text(summary.balance_qty || '0.00');
            }
        });

        function buildTableDataForExport() {
            const sections = [];
            sections.push({ title: "{{ __('closingbalancereport::lang.financial_summary') }}", table: '#financial_summary_table' });
        sections.push({ title: "{{ __('closingbalancereport::lang.sales') }}", table: '#sales_table' });
        sections.push({ title: "{{ __('closingbalancereport::lang.product_report_summary') }}", table: '#product_summary_table' });
        return sections;
        }

    function exportTablesAsCSV(isExcel = false) {
        const sections = buildTableDataForExport();
        const meta = [
            '"{{ __('closingbalancereport::lang.module_name') }}"',
            `"{{ __('business.business') }}","${$('#cb_business_name').text().trim()}"`,
            `"{{ __('closingbalancereport::lang.business_location') }}","${$('#cb_selected_location_meta').text().trim()}"`,
            `"{{ __('closingbalancereport::lang.date_range') }}","${$('#cb_selected_dates').text().trim()}"`
        ];
        let csvContent = meta.join('\n') + '\n\n';
        sections.forEach((section) => {
            csvContent += '"' + section.title + '"\n';
            $(section.table).find('thead tr').each(function() {
                const row = [];
                $(this).find('th').each(function() {
                    row.push('"' + ($(this).text().trim()) + '"');
                });
                csvContent += row.join(',') + '\n';
            });
            $(section.table).find('tbody tr').each(function() {
                const row = [];
                $(this).find('th,td').each(function() {
                    row.push('"' + ($(this).text().trim()) + '"');
                });
                csvContent += row.join(',') + '\n';
            });
            // include totals from tfoot if present
            $(section.table).find('tfoot tr').each(function() {
                const row = [];
                $(this).find('th,td').each(function() {
                    row.push('"' + ($(this).text().trim()) + '"');
                });
                csvContent += row.join(',') + '\n';
            });
            csvContent += '\n';
        });
        csvContent += '"{{ $systemFooter }}"\n';

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = isExcel ? 'closing_balance_report.xls' : 'closing_balance_report.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        /**
         * Export all Closing Balance sections through the global PDF bridge.
         */
        function exportClosingBalanceReportAsPDF() {
            const button = document.getElementById('cb_export_pdf_top');
            if (button) button.disabled = true;

            if (!window.erpGlobalPdf) {
                if (button) button.disabled = false;
                alert('Global PDF service is unavailable. Please refresh and try again.');
                return;
            }

            window.erpGlobalPdf.exportTables(buildTableDataForExport(), {
                filename: 'closing_balance_report.pdf',
                page_title: @json(__('closingbalancereport::lang.module_name')),
                location_id: locationInput.val() || '',
                business_location: locationInput.find('option:selected').text() || @json(__('closingbalancereport::lang.all')),
                date_range: dateInput.val(),
                page_size: 'A4',
                orientation: 'L'
            }).catch(function(error) {
                console.error('[Global PDF Export]', error);
                alert('PDF export failed. Please try again.');
            }).then(function() {
                if (button) button.disabled = false;
            });
        }

        // Alias for backward compatibility (section-level exports can still use this)
        function exportTablesAsPDF() {
            exportClosingBalanceReportAsPDF();
        }

        $('#cb_export_csv').on('click', function() {
            exportTablesAsCSV(false);
        });

        $('#cb_export_excel').on('click', function() {
            exportTablesAsCSV(true);
        });

        // Top toolbar PDF export button - exports the FULL report (all 3 sections)
        $(document).on('click', '#cb_export_pdf_top', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[Global PDF Export] Top toolbar button clicked');
            exportClosingBalanceReportAsPDF();
        });

        $('#cb_print').on('click', function() {
            window.print();
        });

        $('#cb_toggle_columns').on('click', function(e) {
            e.stopPropagation();
            
            // Store button position
            const $btn = $(this);
            const btnOffset = $btn.offset();
            const btnHeight = $btn.outerHeight();
            
            // Trigger the DataTables column visibility dropdown
            financialSummaryTable.button('.buttons-colvis').trigger();
            
            // Reposition the dropdown below the button
            setTimeout(function() {
                const $dropdown = $('.dt-button-collection');
                if ($dropdown.length) {
                    $dropdown.css({
                        position: 'absolute',
                        top: btnOffset.top + btnHeight + 5 + 'px',
                        left: btnOffset.left + 'px'
                    });
                }
            }, 10);
        });

        $('#closing-balance-report-page').on('click', '.dt-button', function() {
            $('#closing-balance-report-page .dt-button-collection').not($(this).siblings('.dt-button-collection')).hide();
        });

        // Force purple styling on Financial Summary buttons to bypass cache issues
        $('.cb-toolbar .btn').each(function() {
            $(this).css({
                'background-color': '#8f2d82',
                'border-color': '#8f2d82',
                'color': '#fff'
            });
        });

        refreshLocationLabel();
        loadSummary();
    });
</script>
</div>
@endsection
