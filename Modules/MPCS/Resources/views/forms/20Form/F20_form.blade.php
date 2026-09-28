<?php
if(!isset($is_ajax)){
    ?>
{{-- @extends('layouts.app') --}}
@extends($layout)
@section('title', 'F20 form')

@section('content')
    <!-- Main content -->
    <section class="content" style="padding-left: 0; padding-right: 0">
        <?php
}
?>


        <div class="page-title-area">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div class="breadcrumbs-area clearfix">
                        <h4 class="page-title pull-left">FORM F20</h4>
                        <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                            <li><a href="#">F20</a></li>
                            <li><span>Last Record</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @php
                    $canViewF20 = auth()->user()->can('f20_form')
                        || auth()->user()->can('F20_form')
                        || auth()->user()->can('F21_form')
                        || auth()->user()->can('f21c_form')
                        || auth()->user()->can('f16a_form');
                @endphp
                <div class="settlement_tabs" id="mpcs_f20_tabs" data-mpcs-tabs>
                    @if ($canViewF20)
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="active" role="presentation">
                            <a href="#f20_form_details_tab" class="f20-page-tab" data-toggle="tab" role="tab">
                                <i class="fa fa-file-text-o"></i> <strong>20 Form Details</strong>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#f20_form_settings_tab" class="f20-page-tab" data-toggle="tab" role="tab">
                                <i class="fa fa-cog"></i> <strong>20 Form Settings</strong>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active in" id="f20_form_details_tab" role="tabpanel">
                            @include('mpcs::forms.20Form.20_form')
                        </div>
                        <div class="tab-pane" id="f20_form_settings_tab" role="tabpanel">
                            @include('mpcs::forms.20Form.list_f20')
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>


        @if (empty($is_ajax))
    </section>
    <!-- /.content -->
@endsection
@section('javascript')
    @endif
    @include('mpcs::partials.safe_tabs')
    <script type="text/javascript">
        $(document).ready(function () {
            // Store product IDs from the initial page load
            const productIds = @json(array_keys($products->toArray()));
            
            // FIX: Format functions with proper decimal places
            function formatQty(value) {
                return Number(value).toLocaleString('en-US', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
            }
            function formatPrice(value) {
                return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }


            /* IS2316 #1: use the same DataTables print pipeline as the
             * report toolbar.  This keeps the selected date/search/column
             * visibility in sync and, importantly, does not re-add products
             * with no transactions for the selected date. */
            window.openF20PrintPreview = function () {
                if (window.f20DataTable && $.fn.DataTable && $.fn.DataTable.isDataTable('#form_20_table_data')) {
                    var printButton = window.f20DataTable.button('.buttons-print');
                    if (printButton && printButton.any()) {
                        printButton.trigger();
                        return false;
                    }
                }

                // DataTables may be unavailable on an installation with an older
                // asset bundle.  The page print CSS still prints only the report
                // and now respects the already hidden no-transaction columns.
                window.print();
                return false;
            };

            /*
             * IS2218: F20 headings are configured product headings, while the
             * AJAX rows are date-filtered sales. Previously that left products
             * with no sales on the selected date visible as empty/zero columns.
             *
             * A product column is visible only when that product exists in the
             * selected date/form-type response. The configured product list
             * itself is not changed, so selecting another date can immediately
             * show the product again when it has sales on that date.
             */
            function setF20VisibleProducts(visibleProductIds) {
                const visible = new Set((visibleProductIds || []).map(function (id) {
                    return String(id);
                }));

                /*
                 * IS2346 #1:
                 * The live F20 report must contain only products that actually
                 * have a sale for the selected date/type.  Keep the configured
                 * product list untouched, but hide its unsold columns in the
                 * screen table as well as in every export/print path.
                 *
                 * Use the data-product-id attribute instead of positional CSS so
                 * the two-row header, body and three footer rows always stay in
                 * sync.  DataTables is also told the same visibility state when
                 * it already exists; initF20DataTable() applies it again during
                 * reinitialisation so a stale DataTables column state cannot
                 * re-show an unsold product or hide a sold one.
                 */
                window.f20ActiveProductIds = visible;

                $('.f20-product-col').each(function () {
                    const productId = String($(this).data('product-id'));
                    const show = visible.has(productId);
                    $(this).css({
                        display: show ? 'table-cell' : 'none',
                        visibility: show ? 'visible' : 'hidden'
                    });
                });

                if (window.f20DataTable && $.fn.DataTable && $.fn.DataTable.isDataTable('#form_20_table_data')) {
                    productIds.forEach(function (productId, i) {
                        try {
                            window.f20DataTable.column(i + 2).visible(visible.has(String(productId)), false);
                        } catch (e) {
                            // Older DataTables builds can safely use the raw table.
                        }
                    });
                    try {
                        window.f20DataTable.columns.adjust().draw(false);
                    } catch (e) {}
                }

                window.setTimeout(window.syncF20PageSlider, 0);
            }

            function destroyF20DataTable() {
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#form_20_table_data')) {
                    try {
                        $('#form_20_table_data').DataTable().destroy();
                    } catch (e) {
                        console.warn('Unable to destroy previous F20 DataTable instance.', e);
                    }
                }
                window.f20DataTable = null;
                $('#f20-standard-tools').empty();
            }

            window.syncF20ReportUrl = function () {
                if (!window.history || !window.history.replaceState || typeof URL === 'undefined') return;
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('form_date', $('#form_20_date_range_data').val() || '');
                    url.searchParams.set('form_type', $('#form_type_select').val() || 'All');
                    window.history.replaceState({}, document.title, url.toString());
                } catch (e) {
                    // Do not interrupt report loading in older browsers.
                }
            };

            function f20ColumnHasTransactions(index) {
                if (index < 2) return true;
                var productId = String(productIds[index - 2] || '');
                return !!(window.f20ActiveProductIds && window.f20ActiveProductIds.has(productId));
            }

            function f20ExportColumn(index) {
                if (!f20ColumnHasTransactions(index)) return false;
                if (index === 0 && String($('#form_type_select').val() || 'All') === 'All') return false;

                if (window.f20DataTable) {
                    try {
                        return window.f20DataTable.column(index).visible();
                    } catch (e) {}
                }
                return true;
            }

            function f20ExportTitle() {
                var dateText = String($('#form_20_date_range_data').val() || '').replace(/[^0-9A-Za-z_-]+/g, '_');
                var typeText = String($('#form_type_select').val() || 'All').replace(/[^0-9A-Za-z_-]+/g, '_');
                return 'F20_Form_' + dateText + '_' + typeText;
            }

            function f20ReportMetaHtml() {
                var formNo = $('<div>').text($('#form_no1').text() || '').html();
                var selectedDate = $('<div>').text($('#form_20_date_range_data').val() || '').html();
                var formType = $('<div>').text($('#form_type_select').val() || 'All').html();
                return '<div style="display:flex;justify-content:space-between;gap:12px;font-size:11px;font-weight:700;margin:0 0 8px;">' +
                    '<span>Form No: ' + formNo + '</span>' +
                    '<span>Date: ' + selectedDate + ' &nbsp; | &nbsp; Type: ' + formType + '</span></div>';
            }

            function f20ShareUrl() {
                window.syncF20ReportUrl();
                return window.location.href;
            }

            function initF20DataTable() {
                if (!$.fn.DataTable || !$.fn.dataTable || !$.fn.dataTable.Buttons) {
                    $('#f20-standard-tools').html('<span class="text-muted"><i class="fa fa-info-circle"></i> Report export tools are unavailable in the current asset bundle.</span>');
                    return;
                }

                destroyF20DataTable();

                // IS2342 #1: never initialise the live F20 table with product
                // columns hidden. Selected-date filtering is applied only to
                // export/print through f20ExportColumn().
                var buttons = [
                    {
                        extend: 'colvis',
                        text: '<i class="fa fa-columns"></i> Column Visibility',
                        columns: function(index) { return f20ColumnHasTransactions(index); }
                    },
                    {
                        extend: 'csv',
                        text: '<i class="fa fa-file-text-o"></i> Export CSV',
                        title: f20ExportTitle,
                        footer: true,
                        exportOptions: { columns: f20ExportColumn, modifier: { search: 'applied' } }
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel-o"></i> Export Excel',
                        title: f20ExportTitle,
                        footer: true,
                        exportOptions: { columns: f20ExportColumn, modifier: { search: 'applied' } }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf-o"></i> PDF',
                        title: f20ExportTitle,
                        orientation: 'landscape',
                        pageSize: 'A4',
                        footer: true,
                        exportOptions: { columns: f20ExportColumn, modifier: { search: 'applied' } },
                        customize: function(doc) {
                            doc.defaultStyle.fontSize = 8;
                            doc.styles.tableHeader.fontSize = 8;
                            doc.pageMargins = [18, 22, 18, 22];
                            doc.content.push({
                                margin: [0, 28, 0, 0],
                                columns: [
                                    { text: '', width: '*' },
                                    { text: '____________________________\nManager Signature', alignment: 'center', bold: true, width: 150 }
                                ]
                            });
                        }
                    },
                    {
                        extend: 'print',
                        className: 'buttons-print',
                        text: '<i class="fa fa-print"></i> Print',
                        title: '',
                        footer: true,
                        exportOptions: { columns: f20ExportColumn, modifier: { search: 'applied' } },
                        customize: function(win) {
                            var $doc = $(win.document);
                            var $body = $doc.find('body');
                            var $table = $body.find('table').first();
                            var columnCount = $table.find('thead tr:first th').length || $table.find('thead th').length || 1;
                            var fontSize = columnCount > 14 ? '7px' : (columnCount > 10 ? '8px' : '9px');
                            var locationText = $('.f20_location_name').map(function() {
                                return $.trim($(this).text());
                            }).get().filter(Boolean).join(', ');
                            var formNo = $('<div>').text($('#form_no1').text() || '-').html();
                            var selectedDate = $('<div>').text($('#form_20_date_range_data').val() || '-').html();
                            var formType = $('<div>').text($('#form_type_select').val() || 'All').html();
                            var safeLocation = $('<div>').text(locationText || '').html();

                            $doc.find('head').append(
                                '<style>' +
                                '@page{size:A4 landscape;margin:8mm;}' +
                                'html,body{margin:0!important;padding:0!important;background:#fff!important;color:#000!important;font-family:Arial,Helvetica,sans-serif!important;}' +
                                '.f20-print-sheet{width:100%!important;margin:0 auto!important;color:#000!important;}' +
                                '.f20-print-heading{text-align:center;margin:0 0 7px;color:#000!important;}' +
                                '.f20-print-heading .location{font-size:15px;font-weight:700;line-height:1.25;}' +
                                '.f20-print-heading .title{font-size:18px;font-weight:700;line-height:1.25;margin-top:2px;}' +
                                '.f20-print-heading .subtitle{font-size:12px;font-weight:600;line-height:1.25;margin-top:2px;}' +
                                '.f20-print-meta{display:flex;justify-content:space-between;gap:12px;font-size:10px;font-weight:700;margin:0 0 8px;border-bottom:1px solid #000;padding-bottom:5px;}' +
                                'table.dataTable{width:100%!important;max-width:100%!important;margin:0 auto!important;border-collapse:collapse!important;table-layout:fixed!important;color:#000!important;}' +
                                'table.dataTable thead{display:table-header-group!important;}' +
                                'table.dataTable th,table.dataTable td{font-size:' + fontSize + '!important;line-height:1.15!important;padding:3px 2px!important;border:1px solid #000!important;white-space:normal!important;overflow-wrap:anywhere!important;word-break:normal!important;color:#000!important;text-align:center!important;vertical-align:middle!important;}' +
                                'table.dataTable thead th{font-weight:700!important;background:#fff!important;}' +
                                'table.dataTable tfoot td{font-weight:700!important;background:#fff!important;}' +
                                'table.dataTable tr{page-break-inside:avoid!important;}' +
                                '.f20-print-signature{display:flex;justify-content:flex-end;margin-top:28px;page-break-inside:avoid;color:#000!important;}' +
                                '.f20-print-signature-box{width:230px;text-align:center;font-size:11px;font-weight:700;}' +
                                '.f20-print-signature-line{border-top:1px solid #000;margin-bottom:6px;}' +
                                '</style>'
                            );

                            $body.find('h1').remove();
                            $body.children().wrapAll('<div class="f20-print-sheet"></div>');
                            var $sheet = $body.find('.f20-print-sheet');
                            $sheet.prepend(
                                '<div class="f20-print-heading">' +
                                    '<div class="location">' + safeLocation + '</div>' +
                                    '<div class="title">F20 Form</div>' +
                                    '<div class="subtitle">Filling Station Stock Sale Summary</div>' +
                                '</div>' +
                                '<div class="f20-print-meta">' +
                                    '<span>Form No: ' + formNo + '</span>' +
                                    '<span>Date: ' + selectedDate + '</span>' +
                                    '<span>Type: ' + formType + '</span>' +
                                '</div>'
                            );
                            $sheet.append(
                                '<div class="f20-print-signature"><div class="f20-print-signature-box">' +
                                    '<div class="f20-print-signature-line"></div>Manager Signature' +
                                '</div></div>'
                            );
                        }
                    },
                    {
                        text: '<i class="fa fa-envelope"></i> Email',
                        className: 'f20-email-button',
                        action: function() {
                            var subject = encodeURIComponent('F20 Form - ' + ($('#form_20_date_range_data').val() || ''));
                            var body = encodeURIComponent('F20 report link:\n' + f20ShareUrl());
                            window.location.href = 'mailto:?subject=' + subject + '&body=' + body;
                        }
                    },
                    {
                        text: '<i class="fa fa-whatsapp"></i> WhatsApp',
                        className: 'f20-whatsapp-button',
                        action: function() {
                            var text = encodeURIComponent('F20 Form - ' + ($('#form_20_date_range_data').val() || '') + '\n' + f20ShareUrl());
                            window.open('https://wa.me/?text=' + text, '_blank', 'noopener');
                        }
                    }
                ];

                var productVisibilityDefs = productIds.map(function (productId, i) {
                    return {
                        targets: i + 2,
                        visible: !!(window.f20ActiveProductIds && window.f20ActiveProductIds.has(String(productId)))
                    };
                });

                window.f20DataTable = $('#form_20_table_data').DataTable({
                    ordering: false,
                    searching: true,
                    columnDefs: productVisibilityDefs,
                    paging: true,
                    info: true,
                    autoWidth: false,
                    pageLength: 25,
                    lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
                    dom: 'Blfrtip',
                    buttons: buttons,
                    language: { emptyTable: window.f20EmptyMessage || 'No data available for selected filters' }
                });

                // The product report itself must remain horizontally scrollable, but
                // the standard controls should stay visible at the top of the page.
                var $wrapper = $('#form_20_table_data_wrapper');
                var $tools = $('#f20-standard-tools').empty();
                $wrapper.find('.dt-buttons').appendTo($tools);
                $wrapper.find('.dataTables_filter').appendTo($tools);
                $wrapper.find('.dataTables_length').appendTo($tools);

                // Re-apply the selected-date sold-product set after DataTables
                // has rebuilt its column DOM/state.
                window.f20DataTable.columns.adjust().draw(false);
                setF20VisibleProducts(Array.from(window.f20ActiveProductIds || []));
                window.setTimeout(window.syncF20PageSlider, 0);
            }

            /*
             * IS2349 #4: keep one horizontal control only. The native scrollbar on
             * #f20-table-scroll is sufficient, so the extra range slider and its
             * bidirectional sync listeners were removed. Keep this lightweight
             * compatibility hook because older reload callbacks call it after the
             * table is rebuilt.
             */
            window.syncF20PageSlider = function () {
                if (window.f20DataTable && $.fn.DataTable && $.fn.DataTable.isDataTable('#form_20_table_data')) {
                    try {
                        window.f20DataTable.columns.adjust();
                    } catch (e) {}
                }
            };

            $(window).off('resize.f20PageSlider').on('resize.f20PageSlider', function () {
                window.syncF20PageSlider();
            });

            window.reloadTable = function () {
            // Function to reload table data via AJAX
                // IS2337 #1 regression fix: DataTables owns/caches the tbody after
                // initialization. Destroy it BEFORE replacing rows for a new date;
                // destroying it afterwards can restore the old cached rows/hidden
                // columns and leave only Bill/Settlement visible.
                destroyF20DataTable();

                const formDateRange = $('#form_20_date_range_data').val();
                const formType = $('#form_type_select').val();
                const isCredit = formType === 'Credit';
                const isCash = formType === 'Cash';
                // Only hide bill column when form type is "All" or empty
                const shouldHideBill = !formType || formType === 'All';

                window.syncF20ReportUrl();

                // Show loading indicator - FIX: colspan is 2 + productIds.length
                $('#form_20_table_data tbody').html(
                    `<tr><td colspan="${2 + productIds.length}" class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Loading data...
                    </td></tr>`
                );

                $.ajax({
                    url: '/mpcs/get-form-20-datas', // Endpoint to fetch data
                    method: 'GET',
                    data: {
                        form_date_range: formDateRange,
                        form_type: formType,
                    },
                    success: function (response) {
                    // Clear existing table rows
                    $('#form_20_table_data tbody').empty();

                    // IS2337 #1: derive visible products from the actual row
                    // details first, then merge totals keys as a fallback. Some
                    // tenant data sets contain valid detail rows while the totals
                    // object is sparse; relying only on totals hid all product data.
                    const visibleProductIdSet = new Set();
                    if (response.products && Array.isArray(response.products)) {
                        response.products.forEach(function (productGroup) {
                            (productGroup.details || []).forEach(function (detail) {
                                if (!detail || detail.product_id === undefined || detail.product_id === null) {
                                    return;
                                }

                                // IS2346 #1: a configured product is considered
                                // sold only when the selected-date response carries
                                // a non-zero sale quantity or amount.  Zero meter/
                                // placeholder rows must not create empty columns.
                                const qty = Math.abs(parseFloat(detail.qty) || 0);
                                const amount = Math.abs(parseFloat(detail.amount) || 0);
                                if (qty > 0 || amount > 0) {
                                    visibleProductIdSet.add(String(detail.product_id));
                                }
                            });
                        });
                    }
                    if (response.totals && typeof response.totals === 'object') {
                        Object.keys(response.totals).forEach(function (productId) {
                            const total = response.totals[productId] || {};
                            const qty = Math.abs(parseFloat(total.qty) || 0);
                            const amount = Math.abs(parseFloat(total.amount) || 0);
                            if (qty > 0 || amount > 0) {
                                visibleProductIdSet.add(String(productId));
                            }
                        });
                    }
                    const visibleProductIds = Array.from(visibleProductIdSet);

                    // Check if response.products exists and is iterable
                    if (response.products && Array.isArray(response.products) && response.products.length > 0) {
                        window.f20EmptyMessage = '';
                        const totalQuantities = {};
                        productIds.forEach(productId => {
                            totalQuantities[productId] = 0;
                        });

                        // Loop through each product block
                        response.products.forEach(productGroup => {
                            let billNo = '';
                            if (isCredit) {
                                // For Credit: use bill_no_display if available, otherwise use bill_no
                                billNo = (productGroup.bill_no_display !== undefined && productGroup.bill_no_display !== null && productGroup.bill_no_display !== '') 
                                    ? productGroup.bill_no_display 
                                    : (productGroup.bill_no || '-');
                            } else if (isCash) {
                                // For Cash: use bill_no
                                billNo = (productGroup.bill_no !== undefined && productGroup.bill_no !== null && productGroup.bill_no !== '') 
                                    ? productGroup.bill_no 
                                    : '-';
                            } else {
                                // For All: empty or use bill_no if available
                                billNo = (productGroup.bill_no !== undefined && productGroup.bill_no !== null && productGroup.bill_no !== '') 
                                    ? productGroup.bill_no 
                                    : '';
                            }
                            let settlementNo = productGroup.settlement_no || '';
                            const qtyMap = {};
                            productGroup.details.forEach(d => {
                                const pid = String(d.product_id);
                                const q = parseFloat(d.qty) || 0;
                                qtyMap[pid] = (qtyMap[pid] || 0) + q;
                            });

                            // FIX: Removed extra empty <td> cell - now only Bill No, Settlement No, then product columns
                            let rowHtml = `
                            <tr class="table-row">
                                <td class="bill-no-col">${billNo}</td>
                                <td>${settlementNo}</td>`;
                            productIds.forEach(pid => {
                                const qty = qtyMap[String(pid)] !== undefined ? qtyMap[String(pid)] : '';
                                if (qty !== '') {
                                    totalQuantities[pid] += parseFloat(qty);
                                }
                                rowHtml += `<td class="f20-product-col" data-product-id="${pid}" style="text-align: right">${qty !== '' ? formatQty(qty) : ''}</td>`;
                            });
                            rowHtml += `</tr>`;
                            $('#form_20_table_data tbody').append(rowHtml);
                        });
                        setF20VisibleProducts(visibleProductIds);
                        toggleBillColumn(shouldHideBill);
                        requestAnimationFrame(window.syncF20PageSlider);

                        // Render totals using data attributes - FIX: Match new footer structure
                        if (response.totals && typeof response.totals === 'object') {
                            productIds.forEach(productId => {
                                const totalData = response.totals[productId] || response.totals[String(productId)] || { qty: 0, unit_price: 0, amount: 0 };
                                
                                // Update cells by data-product-id
                                $(`.total-qty[data-product-id="${productId}"]`).text(formatQty(totalData.qty)).css('text-align', 'right');
                                $(`.total-price[data-product-id="${productId}"]`).text(formatPrice(totalData.unit_price)).css('text-align', 'right');
                                $(`.total-amount[data-product-id="${productId}"]`).text(formatPrice(totalData.amount)).css('text-align', 'right');
                            });
                        }

                        initF20DataTable();
                    } else {
                        // Keep the report toolset available even when the selected
                        // date has no rows; DataTables will render the empty message.
                        $('#form_20_table_data tbody').empty();
                        window.f20EmptyMessage = response.message || 'No data available for selected filters';
                        $('.total-qty, .total-price, .total-amount').text('');
                        setF20VisibleProducts([]);
                        toggleBillColumn(shouldHideBill);
                        initF20DataTable();
                        requestAnimationFrame(window.syncF20PageSlider);
                    }
                },
                            error: function (xhr, status, error) {
                                console.error("Error fetching data:", error);
                                console.error("Response:", xhr.responseText);
                                console.error("Status:", xhr.status);
                                
                                let errorMessage = 'Error loading data. Please try again.';
                                
                                // Try to parse error response for more details
                                try {
                                    let response = JSON.parse(xhr.responseText);
                                    if (response.message) {
                                        errorMessage = response.message;
                                    } else if (response.error) {
                                        errorMessage = response.error;
                                    }
                                } catch (e) {
                                    // If response is not JSON, check for specific error codes
                                    if (xhr.status === 500) {
                                        errorMessage = 'Server error occurred. Please check the logs.';
                                    } else if (xhr.status === 404) {
                                        errorMessage = 'Endpoint not found. Please check the route configuration.';
                                    } else if (xhr.status === 403) {
                                        errorMessage = 'Access denied. Please check your permissions.';
                                    } else if (xhr.status === 0) {
                                        errorMessage = 'Network error. Please check your connection.';
                                    }
                                }
                                
                                $('#form_20_table_data tbody').html(
                                    `<tr><td colspan="${2 + productIds.length}" class="text-center text-danger">
                                        <i class="fa fa-exclamation-triangle"></i> ${errorMessage}
                                    </td></tr>`
                                );
                                $('.total-qty, .total-price, .total-amount').text('');
                                toggleBillColumn(shouldHideBill);
                        requestAnimationFrame(window.syncF20PageSlider);
                                
                                // Show user-friendly toastr notification
                                if (typeof toastr !== 'undefined') {
                                    toastr.error(errorMessage);
                                }
                            },
                        });
                };

            function toggleBillColumn(hide) {
                const $billCells = $('.bill-no-col');
                // Keep column width stable: never remove the column, only hide its content.
                $billCells.css('display', 'table-cell');
                $billCells.css('visibility', hide ? 'hidden' : 'visible');
            }

            // Listen for changes in date range and form type - OPTIMIZED
            $('#form_type_select').on('change', function () {
                // Debounce to prevent multiple rapid calls
                clearTimeout(window.f20ReloadTimer);
                window.f20ReloadTimer = setTimeout(function() {
                    reloadTable();
                }, 300); // 300ms delay for better performance
            });

            // Initial load of the table
            reloadTable();
        });

        // FIX: Keep formatAmount for backward compatibility
        function formatAmount(value) {
            return Number(value).toLocaleString('en-US', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
        }

        $('#form_20_date_range_data').daterangepicker({
            singleDatePicker: true, // For selecting a single date
            showDropdowns: true, // To show the dropdown for predefined date ranges
            locale: {
                format: 'YYYY-MM-DD', // Adjust the date format according to your needs
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            }
        }, function(start, end, label) {
            if (label === 'Custom Date Range') {
                // Show the modal for manual input
                $('.custom_date_typing_modal').modal('show');
            } else {
                // Set the input value when a date is selected
                $('#form_20_date_range_data').val(start.format('YYYY-MM-DD'));
            }
            if (typeof window.syncF20ReportUrl === 'function') {
                window.syncF20ReportUrl();
            }
            // IMMEDIATE form number update - independent of table reload
            if (typeof window.fetchFormNumber === 'function') {
                window.fetchFormNumber();
            }
            
            // Reload table data separately to avoid conflicts
            setTimeout(function() {
                reloadTable();
            }, 50);
        });

        $('#custom_date_apply_button').on('click', function () {
            let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
            let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

            if (startDate.length === 10 && endDate.length === 10) {
                let formattedStartDate = moment(startDate).format(moment_date_format);
                let formattedEndDate = moment(endDate).format(moment_date_format);
                let fullRange = formattedStartDate + ' ~ ' + formattedEndDate;

                // === Update #9c_date_range if it exists ===
                if ($('#form_20_date_range_data').length) {
                    $('#form_20_date_range_data').val(fullRange);
                    $('#form_20_date_range_data').data('daterangepicker').setStartDate(moment(startDate));
                    $('#form_20_date_range_data').data('daterangepicker').setEndDate(moment(endDate));
                    $("#report_date_range").text("Date Range: " + fullRange);
                    if (typeof window.syncF20ReportUrl === 'function') window.syncF20ReportUrl();
                    reloadTable();
                }
                // Hide the modal
                $('.custom_date_typing_modal').modal('hide');
            } else {
                alert("Please select both start and end dates.");
            }
        });

        // Reset the field when the cancel button is clicked
        $('#form_20_date_range_data').on('cancel.daterangepicker', function(ev, picker) {
            $('#product_sr_date_filter').val('');
        });

        // Preserve a date supplied in the report URL (Email/WhatsApp sharing)
        // instead of forcing the picker back to today after page load.
        var initialF20Date = String($('#form_20_date_range_data').val() || '').trim();
        var initialF20Moment = moment(initialF20Date, ['YYYY-MM-DD', moment_date_format], true);
        if (initialF20Moment.isValid()) {
            $('#form_20_date_range_data').data('daterangepicker').setStartDate(initialF20Moment);
            $('#form_20_date_range_data').data('daterangepicker').setEndDate(initialF20Moment);
        }

        $('.from_date').text(initialF20Date);
        $('.to_date').text(initialF20Date);


        $('#f14b_location_id option:eq(1)').attr('selected', true);
        $('#20_location_id option:eq(1)').attr('selected', true);


        document.addEventListener("DOMContentLoaded", function() {
            let table = document.getElementById("form_20_table_data");
            let formNoElement = document.getElementById("form_no1");

            if (!table || !formNoElement) {
                console.error("Table or form_no1 element not found!");
                return;
            }

            function updateFormNumber() {
                let tableHeight = table.scrollHeight; // Get the table's height in pixels
                let A4Height = 1122; // Approx. A4 page height in pixels (for 96 DPI screens)

                // let pages = Math.ceil(tableHeight / A4Height); // Calculate number of pages
                {{--let baseFormNumber = formNoElement.dataset--}}
                {{--    .formNo; // Get original form number (store it in a data attribute)--}}

                {{--if (!baseFormNumber) {--}}
                {{--    baseFormNumber = "{{ $F20_form_sn }}"; // Get the form number from Blade (fallback)--}}
                {{--    formNoElement.dataset.formNo = baseFormNumber; // Store for later use--}}
                {{--}--}}

                {{--formNoElement.textContent = baseFormNumber + '-' + pages; // Update form number--}}
            }

            // Run the function on page load
            updateFormNumber();

            // If content changes dynamically, listen for changes
            new MutationObserver(updateFormNumber).observe(table, {
                childList: true,
                subtree: true
            });
        });
    </script>
    @if (empty($is_ajax))
    @endsection
@endif
