@extends('layouts.app')
@section('title', __('Stock Report'))

<style>
    .main-footer {
        display: none !important;
    }
</style>

@php
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
@endphp

@if (!empty($reports_footer) && !empty($reports_footer->value))
        <style>
            .stock-report-admin-footer {
                margin-top: 30px;
                width: 100%;
                text-align: left;
                font-size: 12px;
                color: #333;
                padding: 10px 0 0 10px;
                border-top: 1px solid #eee;
                z-index: 9999;
            }

            @media print {
                .stock-report-admin-footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    margin-top: 0;
                    page-break-inside: avoid;
                    z-index: 9999;
                }
            }
        </style>
    @endif

    <style>
    @media print {

        @page {
            size: A5 portrait;
            margin: 10mm;
        }

        #form_f10_print_area {
            width: 100%;
            page-break-after: avoid;
        }


        .no-print {
            display: none !important;
        }

    }
  
</style>

<style>
    /* Stock report table compact layout - S711 follow-up UI changes. */
    #dis_stock_transfer_table thead th {
        font-size: calc(100% - 1pt);
        line-height: 1.12;
        vertical-align: middle;
        white-space: normal;
    }

    #dis_stock_transfer_table .stock-heading-lines > span,
    #dis_stock_transfer_table .stock-qty-stack > span,
    #dis_stock_transfer_table .stock-product-cell > span {
        display: block;
    }

    #dis_stock_transfer_table .stock-product-cell .stock-product-sku {
        margin-top: 2px;
        font-size: 90%;
        line-height: 1.1;
        color: #666;
        white-space: nowrap;
    }

    /* Prevent any pre-initialisation flash of the internal searchable SKU field. */
    #dis_stock_transfer_table .stock-internal-sku-column {
        display: none !important;
    }

    #dis_stock_transfer_table .stock-qty-stack .stock-bonus-qty {
        margin-top: 2px;
        padding-top: 2px;
        border-top: 1px solid #e5e5e5;
    }

    #dis_stock_transfer_table th.stock-starting-qty-col,
    #dis_stock_transfer_table td.stock-starting-qty-col {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
    }

    #dis_stock_transfer_table th.stock-purchase-qty-col,
    #dis_stock_transfer_table td.stock-purchase-qty-col {
        width: 85px !important;
        min-width: 85px !important;
        max-width: 85px !important;
    }

    #dis_stock_transfer_table th.stock-purchase-return-col,
    #dis_stock_transfer_table td.stock-purchase-return-col {
        width: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
    }

    #dis_stock_transfer_table th.stock-adjustment-return-col,
    #dis_stock_transfer_table td.stock-adjustment-return-col {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
    }

    #dis_stock_transfer_table td.stock-starting-qty-col,
    #dis_stock_transfer_table td.stock-purchase-qty-col,
    #dis_stock_transfer_table td.stock-purchase-return-col,
    #dis_stock_transfer_table td.stock-adjustment-return-col {
        text-align: right;
        vertical-align: middle;
    }
</style>

@section('content')

    <section class="content-header">
        <div class="row">
            <div class="col-md-12 dip_tab">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        <li class="@if (empty(session('status.tab'))) active @endif" style="margin-left: 20px;">
                            <a style="font-size:13px;" href="#transfer_list" id="transfer-list-link" data-toggle="tab">
                                <i class="fa fa-list"></i>
                                <strong>Stock Transactions – Qty</strong>
                            </a>
                        </li>
                         
                    </ul>
                </div>
            </div>
        </div>
         

        <div class="tab-content" style="margin-top:20px;">
            <!-- Stock Transfer List -->
            <div class="tab-pane @if (empty(session('status.tab'))) active @endif" id="transfer_list">
                @include('stockreports::partials.show')
            </div>

            
        </div>
        <div class="modal fade" id="view_transfer_modal" tabindex="-1"></div>
        <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>
    </section>

    @if (!empty($reports_footer) && !empty($reports_footer->value))
        <div id="stock_report_page_footer" class="stock-report-admin-footer stock-report-page-footer">
            {!! $reports_footer->value !!}
        </div>
    @endif

@endsection

@section('javascript')
    <script>
        const reportFooterHtml = @json(!empty($reports_footer) ? $reports_footer->value : '');

        function appendStockReportModalFooter($modal) {
            if (!reportFooterHtml || !$modal.length || $modal.find('.stock-report-modal-footer').length) {
                return;
            }

            $modal.find('.modal-body').append(
                '<div class="stock-report-admin-footer stock-report-modal-footer">' +
                    reportFooterHtml +
                '</div>'
            );
        }

        $(document).on('shown.bs.modal', '.view_modal, #view_transfer_modal', function() {
            appendStockReportModalFooter($(this));
        });

        // S740: Stock Transaction Report-owned View handler.
        // Use the current report URL itself instead of core transaction show
        // pages. Core pages may require unrelated Sales/Purchase permissions
        // and were returning HTTP 403 for users who can legitimately access
        // this report.
        $(document)
            .off('click.stockreportsView', '.stockreport-view-transaction')
            .on('click.stockreportsView', '.stockreport-view-transaction', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var transactionId = parseInt($(this).attr('data-transaction-id'), 10);
                var $modal = $('#view_transfer_modal');

                if (!transactionId || !$modal.length) {
                    toastr.error('Unable to open the transaction view.');
                    return;
                }

                $modal.html(
                    '<div class="modal-dialog modal-lg" role="document">' +
                        '<div class="modal-content">' +
                            '<div class="modal-body text-center" style="padding:35px;">' +
                                '<i class="fa fa-spinner fa-spin fa-2x"></i>' +
                                '<div style="margin-top:10px;">Loading transaction...</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                ).modal('show');

                $.ajax({
                    url: window.location.pathname,
                    type: 'GET',
                    dataType: 'html',
                    data: {
                        stock_report_view: 1,
                        transaction_id: transactionId
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).done(function(html) {
                    $modal.html(html);
                    appendStockReportModalFooter($modal);
                    $modal.modal('show');
                }).fail(function(xhr) {
                    $modal.modal('hide').empty();

                    var message = 'Unable to open the transaction view.';
                    if (xhr.status === 404) {
                        message = 'Transaction not found for this business.';
                    } else if (xhr.status === 422) {
                        message = 'Unable to load this transaction.';
                    }

                    toastr.error(message);
                });
            });

        // View payment modal handler
        $(document).on('click', '.view_payment_modal', function(e) {
            e.preventDefault();
            var container = $('.payment_modal');

            $.ajax({
                url: $(this).attr('href'),
                dataType: 'html',
                success: function(result) {
                    $(container).html(result).modal('show');
                }
            });
        });

        
        $(document).ready(function() {
let table;
let skuFilterTimer = null;
window.sa_manualDate = false;

const stockReportDateRangeSettings = $.extend(true, {}, window.dateRangeSettings || dateRangeSettings);
stockReportDateRangeSettings.autoUpdateInput = true;
stockReportDateRangeSettings.startDate = moment().startOf('month');
stockReportDateRangeSettings.endDate = moment().endOf('month');

// Initialize date range picker once
$(document).ready(function() {
    initDateRangePicker();
    initDataTable();
    setupFilterEvents();
    
    // Set default date filter on page load
    setDefaultDateFilter();
});

function setDefaultDateFilter() {
    if ($('#sa_date_range').length > 0) {
        // Set default to current month
        const start = moment().startOf('month');
        const end = moment().endOf('month');
        
        // Update the date range picker
        $('#sa_date_range').data('daterangepicker').setStartDate(start);
        $('#sa_date_range').data('daterangepicker').setEndDate(end);
        
        // Update the input field
        $('#sa_date_range').val(
            start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
        );
        
        // Set manual date flag and trigger table load
        window.sa_manualDate = true;
        
        // Note: The table will load with these defaults via initDataTable()
    }
}

function initDateRangePicker() {
    if ($('#sa_date_range').length > 0) {
        // Configure default date range (current month)
        const defaultStart = moment().startOf('month');
        const defaultEnd = moment().endOf('month');
        
        stockReportDateRangeSettings.startDate = defaultStart;
        stockReportDateRangeSettings.endDate = defaultEnd;
        
        $('#sa_date_range').daterangepicker(
            stockReportDateRangeSettings,
            function(start, end) {
                $('#sa_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                window.sa_manualDate = true;
                
                // Trigger table reload when date range is applied
                reloadTable();
            }
        ).on('show.daterangepicker', function() {
            window.sa_manualDate = false;
        }).on('cancel.daterangepicker', function() {
            $('#sa_date_range').val('');
            reloadTable();
        });
        
        // Set initial value
        $('#sa_date_range').val(
            defaultStart.format(moment_date_format) + ' - ' + defaultEnd.format(moment_date_format)
        );
    }
}
function setupFilterEvents() {
    $('.location-filter, .product-type-filter, .category-filter, .sub-category-filter, .brand-filter, .unit_id, .store_id, .product-filter').on('change', function() {
        reloadTable();
    });

    $('.sku-filter').on('keyup', function() {
        clearTimeout(skuFilterTimer);
        skuFilterTimer = setTimeout(function() {
            reloadTable();
        }, 300);
    });
}
function initDataTable() {
    table = $('#dis_stock_transfer_table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        searchDelay: 350,
        ajax: {
            url: "{{ action('\Modules\StockReports\Http\Controllers\StockReportsController@index') }}",
            data: function(d) {
                // Add filter parameters to DataTable request
                // If date range is empty, use current month as default
                let dateRange = $('#sa_date_range').val();
                if (!dateRange || dateRange.trim() === '') {
                    const defaultStart = moment().startOf('month');
                    const defaultEnd = moment().endOf('month');
                    dateRange = defaultStart.format(moment_date_format) + ' - ' + defaultEnd.format(moment_date_format);
                    $('#sa_date_range').val(dateRange);
                }
                
                d.date_range = dateRange;
                d.location_id = $('.location-filter').val();
                d.product_type = $('.product-type-filter').val();
                d.category_id = $('.category-filter').val();
                d.sub_category_id = $('.sub-category-filter').val();
                d.brand_id = $('.brand-filter').val();
                d.product_id = $('.product-filter').val();
                d.sku = $('.sku-filter').val();
                d.unit_id = $('.unit_id').val();
                d.store_id = $('.store_id').val();
            }
        },
        dom: '<"row margin-bottom-20 text-center"<"col-sm-12"B><"col-sm-5 pr-50 text-align-start"f><"col-sm-7"l> r>tip',
        buttons: [
            {
                extend: 'csv',
                footer: true,
                text: '<i class="fa fa-file"></i> Export to CSV',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible:not(:first-child)'
                }
            },
            {
                extend: 'excel',
                footer: true,
                text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible:not(:first-child)'
                }
            },
            {
                extend: 'pdf',
                footer: true,
                text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible:not(:first-child)'
                }
            },
            {
                extend: 'print',
                footer: true,
                text: '<i class="fa fa-print"></i> Print Preview',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible:not(:first-child)',
                    modifier: {
                        page: 'current'
                    }
                },
                customize: function(win) {
                    appendReportPrintFooter(win, false);
                }
            },
            {
                extend: 'print',
                footer: true,
                text: '<i class="fa fa-print"></i> Print All',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible:not(:first-child)',
                    modifier: {
                        search: 'applied',
                        order: 'applied',
                        page: 'all'
                    }
                },
                customize: function(win) {
                    appendReportPrintFooter(win, true);
                }
            },
            {
                extend: 'colvis',
                footer: true,
                text: '<i class="fa fa-columns"></i> Column Visibility',
                className: 'btn btn-default btn-sm',
                columns: ':not(.noVis)'
            }
        ],
        columns: [
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            },
            {
                data: 'date_time',
                name: 'date_time'
            },
            {
                data: 'product',
                name: 'product',
                render: function(data, type, row) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    var product = escapeStockReportHtml(data || '');
                    var sku = escapeStockReportHtml(row.sku || '');

                    return '<div class="stock-product-cell">' +
                        '<span class="stock-product-name">' + product + '</span>' +
                        '<span class="stock-product-sku">SKU: ' + (sku || '-') + '</span>' +
                        '</div>';
                }
            },
            {
                // Hidden internal column keeps both the main DataTables search and
                // the dedicated SKU filter working after the visible SKU column is removed.
                data: 'sku',
                name: 'sku',
                visible: false,
                searchable: true,
                orderable: false,
                className: 'noVis stock-internal-sku-column'
            },
            {
                data: 'from_store',
                name: 'from_store'
            },
            {
                data: 'description',
                name: 'description'
            },
            {
                data: 'opening_qty',
                name: 'opening_qty',
                className: 'stock-starting-qty-col text-right',
                width: '70px'
            },
            {
                data: 'purchase_qty',
                name: 'purchase_qty',
                className: 'stock-purchase-qty-col text-right',
                width: '85px',
                render: function(data, type, row) {
                    if (type !== 'display') {
                        return data || 0;
                    }

                    return '<div class="stock-qty-stack">' +
                        '<span class="stock-purchase-qty">' + (data || '0') + '</span>' +
                        '<span class="stock-bonus-qty">' + (row.bonus_qty || '0') + '</span>' +
                        '</div>';
                }
            },
            {
                data: 'purchase_return_qty',
                name: 'purchase_return_qty',
                className: 'stock-purchase-return-col text-right',
                width: '60px'
            },
            {
                data: 'stock_adjustment_qty',
                name: 'stock_adjustment_qty',
                className: 'stock-adjustment-return-col text-right',
                width: '70px'
            },
            {
                data: 'sold_qty',
                name: 'sold_qty'
            },
            {
                data: 'sales_return_qty',
                name: 'sales_return_qty'
            },            
            {
                data: 'balance_qty',
                name: 'balance_qty',
                className: 'text-right'
            },
            {
                data: 'section_name',
                name: 'section_name',
                visible: false,
                className: 'noVis'
            }
        ],
        order: [[13, 'asc'], [1, 'asc']],  // Order by category then date
        drawCallback: function(settings) {
            var api = this.api();
            var rows = api.rows({page: 'current'}).nodes();
            var data = api.rows({page: 'current'}).data().toArray();
            var lastSection = null;
            var currentSectionTotals = null;

            function createSectionHeader(sectionName) {
                return '<tr class="group-header" style="background-color: #e8f4fd; font-weight: bold;">' +
                    '<td colspan="12" style="padding: 8px 10px; font-size: 13px;">' +
                    '<i class="fa fa-folder-open"></i> ' + sectionName +
                    '</td></tr>';
            }

            function createSectionSummary(sectionName, totals) {
                return '<tr class="group-summary" style="background-color: #f7fbff; font-weight: bold;">' +
                    '<td colspan="5" style="padding: 8px 10px;">Section Summary: ' + sectionName + '</td>' +
                    '<td class="text-right">' + formatQtyForSummary(totals.opening_qty) + '</td>' +
                    '<td class="text-right"><div class="stock-qty-stack">' +
                        '<span class="stock-purchase-qty">' + formatQtyForSummary(totals.purchase_qty) + '</span>' +
                        '<span class="stock-bonus-qty">' + formatQtyForSummary(totals.bonus_qty) + '</span>' +
                    '</div></td>' +
                    '<td class="text-right">' + formatQtyForSummary(totals.purchase_return_qty) + '</td>' +
                    '<td class="text-right">' + formatQtyForSummary(totals.stock_adjustment_qty) + '</td>' +
                    '<td class="text-right">' + formatQtyForSummary(totals.sold_qty) + '</td>' +
                    '<td class="text-right">' + formatQtyForSummary(totals.sales_return_qty) + '</td>' +
                    '<td class="text-right">' + formatQtyForSummary(totals.balance_qty) + '</td>' +
                    '</tr>';
            }

            $.each(data, function(i, rowData) {
                var sectionName = rowData.section_name || 'Uncategorized';
                if (lastSection !== sectionName) {
                    if (lastSection !== null) {
                        $(rows).eq(i).before(createSectionSummary(lastSection, currentSectionTotals));
                    }

                    currentSectionTotals = {
                        opening_qty: 0,
                        purchase_qty: 0,
                        bonus_qty: 0,
                        purchase_return_qty: 0,
                        stock_adjustment_qty: 0,
                        sold_qty: 0,
                        sales_return_qty: 0,
                        balance_qty: 0
                    };

                    $(rows).eq(i).before(createSectionHeader(sectionName));
                    lastSection = sectionName;
                }

                currentSectionTotals.opening_qty += parseQtyForSummary(rowData.opening_qty);
                currentSectionTotals.purchase_qty += parseQtyForSummary(rowData.purchase_qty);
                currentSectionTotals.bonus_qty += parseQtyForSummary(rowData.bonus_qty);
                currentSectionTotals.purchase_return_qty += parseQtyForSummary(rowData.purchase_return_qty);
                currentSectionTotals.stock_adjustment_qty += parseQtyForSummary(rowData.stock_adjustment_qty);
                currentSectionTotals.sold_qty += parseQtyForSummary(rowData.sold_qty);
                currentSectionTotals.sales_return_qty += parseQtyForSummary(rowData.sales_return_qty);
                currentSectionTotals.balance_qty += parseQtyForSummary(rowData.balance_qty);
            });

            if (lastSection !== null && currentSectionTotals !== null) {
                $(rows).last().after(createSectionSummary(lastSection, currentSectionTotals));
            }
        }
    });
}

function escapeStockReportHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function formatQtyForSummary(value) {
    return Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function parseQtyForSummary(value) {
    if (value === null || value === undefined) {
        return 0;
    }

    if (typeof value === 'number') {
        return value;
    }

    var textValue = $('<div>').html(value).text().replace(/,/g, '').trim();
    if (!textValue) {
        return 0;
    }

    return parseFloat(textValue) || 0;
}

function appendReportPrintFooter(win, includeAllRows) {
    $(win.document.body).find('h1').css('text-align', 'center');
    $(win.document.body).find('h1').css('font-size', '25px');

    const selectedDateRange = $('#sa_date_range').val() || '';
    const subtitle = '<div style="text-align:center; margin-bottom:10px; font-size:12px;">' +
        'Stock Transactions - Qty' +
        (selectedDateRange ? ' | Date Range: ' + selectedDateRange : '') +
        (includeAllRows ? ' | Print All' : ' | Print Preview') +
        '</div>';

    $(subtitle).insertAfter($(win.document.body).find('h1'));

    if (reportFooterHtml) {
        $(win.document.body).append(
            '<div class="stock-report-admin-footer" style="margin-top:30px; width:100%; font-size:12px; color:#333; border-top:1px solid #eee; padding-top:10px;">' +
            reportFooterHtml +
            '</div>'
        );
    }
}

// Function to reload table with filters
function reloadTable() {
    if (table) {
        table.ajax.reload();
    }
}

// Handle date range change via the apply event
$('#sa_date_range').on('apply.daterangepicker', function(ev, picker) {
    reloadTable();
});

// Optional: Clear filter functionality
function clearFilters() {
    $('.location-filter, .product-type-filter, .category-filter, .sub-category-filter, .brand-filter,.unit_id, .store_id, .product-filter, .sku-filter').val('');
    $('#sa_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
    $('#sa_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));
    reloadTable();
}
        });
    </script>
@endsection
