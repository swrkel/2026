<style>
/*
 * IS2015: the Action menu once it has been moved to <body>.
 *
 * Detached, it is no longer inside the page's table wrapper, so the surface,
 * border and shadow must be restated here or it renders as bare list items over
 * the page. Positioning is set inline by the script further down this file,
 * which has the toggle's viewport rectangle to work from.
 */
.f10-action-menu-detached {
    position: fixed;
    z-index: 1065;
    display: block;
    margin: 0;
    padding: 5px 0;
    box-sizing: border-box;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, .15);
    border-radius: 4px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
    list-style: none;
}
.f10-action-menu-detached > li,
.f10-action-menu-detached > li > a {
    display: block;
    float: none !important;
    width: 100%;
    box-sizing: border-box;
}
.f10-action-menu-detached > li > a {
    padding: 6px 16px;
    clear: both;
    color: #333;
    font-weight: 400;
    line-height: 1.42857143;
    white-space: nowrap;
    text-decoration: none;
}
.f10-action-menu-detached > li > a:hover,
.f10-action-menu-detached > li > a:focus {
    background-color: #f5f5f5;
    color: #262626;
}
</style>
<style>

    .content-wrapper,
    .content,
    .main-content,
    section.content,
    .box,
    .box-primary,
    .box-body,
    .page-container {
        background-color: #ffffff !important;
        background: #ffffff !important;
    }
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    .page-container {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .main-content {
        flex: 1;
        width: 100%;
    }
    
    /* Ensure content sections take full width */
    .content-section {
        width: 100%;
        padding: 0 15px; /* Optional: add horizontal padding if needed */
        box-sizing: border-box;
    }
    
    /* Make sure rows don't have negative margins */
    .content-section .row {
        margin-left: 0;
        margin-right: 0;
    }
    
    /* Ensure the box takes full width */
    .box {
        width: 100%;
    }
    
    .box-primary {
        width: 100%;
    }
    
    .box-body {
        padding: 15px;
        width: 100%;
        overflow-x: auto; /* Allow horizontal scroll on small screens */
    }
    
    /* Make table full width */
    #f10_receipts_table {
        width: 100% !important;
    }
    
    /* DataTables wrapper should be full width */
    .dataTables_wrapper {
        width: 100% !important;
    }

    #f10_page_footer {
        margin-top: auto;
        width: 100%;
        text-align: left;
        font-size: 12px;
        color: #333;
        padding: 10px 0 0 10px;
        border-top: 1px solid #eee;
        box-sizing: border-box;
    }
    
    /* Button row styling */
    .dt-buttons {
        display: inline-flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 0 !important;
        margin-bottom: 15px !important;
        float: none !important;
    }
    
    .dt-buttons .btn {
        background: #8f2d82 !important;
        border-color: #8f2d82 !important;
        color: #fff !important;
        margin: 0 !important;
        border-radius: 0 !important;
        padding: 8px 12px !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        border-right: 1px solid #7a246f !important;
        box-shadow: none !important;
    }
    
    .dt-buttons .btn:first-child {
        border-radius: 3px 0 0 3px !important;
    }
    
    .dt-buttons .btn:last-child {
        margin-right: 0 !important;
        border-right: none !important;
        border-radius: 0 3px 3px 0 !important;
    }
    
    .dt-buttons .btn i {
        margin-right: 0 !important;
    }
    
    .dt-buttons .btn:hover,
    .dt-buttons .btn:focus {
        color: #fff !important;
        background: #7a246f !important;
        border-color: #7a246f !important;
    }
    
    /* DataTables search box styling */
    .dataTables_filter {
        margin-bottom: 15px !important;
        float: right !important;
    }
    
    /* Ensure filters take full width */
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 15px;
        width: 100%;
        margin-bottom: 20px;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    /* Remove any container constraints */
    .container, .container-fluid {
        width: 100%;
        max-width: 100%;
        padding-left: 0;
        padding-right: 0;
    }

    /* Position footer at bottom of normal webpage */
    .reports-footer-content {
        margin-top: 50px;
        padding: 20px 0;
        border-top: 1px solid #ddd;
        text-align: center;
    }

    /* Ensure reports footer is visible during print */
    @media print {
        .main-footer {
            display: block !important;
        }
        
        /* Add 5px margin to all content during print */
        body {
            margin: 5px !important;
            padding: 5px !important;
            padding-bottom: 65px !important; /* 60px for footer + 5px margin */
        }
        
        .reports-footer-content,
        #f10_page_footer.reports-footer-content {
            display: block !important;
            visibility: visible !important;
            position: fixed !important;
            bottom: 5px !important;
            left: 5px !important;
            right: 5px !important;
            width: auto !important;
            margin: 0 !important;
            padding: 10px 0 !important;
            border-top: 1px solid #ddd !important;
            text-align: center !important;
            page-break-inside: avoid !important;
            background-color: #fff !important;
        }
        
        /* Ensure main content respects margins */
        .page-container {
            margin: 0 5px !important;
            padding: 0 !important;
        }
        
        /* Hide unnecessary elements during print */
        .no-print {
            display: none !important;
        }
    }
</style>

<div class="page-container">
    <!-- Header Section -->
    <div class="content-section">
        <div class="row" style="margin:0;">
            <div class="col-md-12 text-center" style="margin-bottom:20px; padding:0;">
                <h3 style="font-weight:bold; font-size:24px;">
                    {{ session('business.name') }}
                </h3>
                <div id="selected_date_range_display" style="font-size:16px; margin-top:5px;">
                    {{ @format_date('first day of this month') }} - {{ @format_date('last day of this month') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Filter Section -->
        <div class="content-section">
            <div class="filter-row">
                <div class="filter-group">
                    {!! Form::label('f10_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select(
                        'f10_location_id',
                        $business_locations,
                        count($business_locations) == 1 ? $business_locations->keys()->first() : null,
                        [
                            'id' => 'f10_location_id',
                            'class' => 'form-control select2',
                        ],
                    ) !!}
                </div>
                <div class="filter-group">
                    <label>Manager</label>
                    <select id="manager_dropdown" class="form-control select2">
                        <option value="">All Managers</option>
                        @foreach ($managers->where('status', 'Active') as $manager)
                            <option value="{{ $manager->id }}">
                                {{ $manager->manager_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>F10 Number</label>
                    <select id="f10_number_dropdown" class="form-control select2">
                        <option value="">All Numbers</option>
                        @foreach ($f10_numbers ?? [] as $number)
                            <option value="{{ $number }}">{{ $number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label>Document No</label>
                    <select id="document_no_dropdown" class="form-control select2">
                        @if (!empty($opening_numbers))
                            <option value="{{ $opening_numbers->document_no }}">
                                {{ $opening_numbers->document_no }}
                            </option>
                        @endif
                    </select>
                </div>
                <div class="filter-group">
                    <label>Cashier / User</label>
                    {!! Form::select('cashier_id', $cashiers, null, [
                        'id' => 'cashier_dropdown',
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                    ]) !!}
                </div>
                <div class="filter-group">
                    {!! Form::label('form_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text(
                        'form_10_date_range_list',
                        @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month'),
                        [
                            'class' => 'form-control',
                            'id' => 'form_10_date_range_list',
                            'readonly',
                        ],
                    ) !!}
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="content-section">
            <div class="row" style="margin:0;">
                <div class="col-md-12" style="padding:0;">
                    <div class="box box-primary" style="width:100%;">
                        <div class="box-body" style="padding:15px; overflow-x:auto;">
                            <table class="table table-bordered table-striped" id="f10_receipts_table" style="width:100% !important;">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Date</th>
                                        <th>Business Location</th>
                                        <th>Manager</th>
                                        <th>F10 Form No</th>
                                        <th>Document Number</th>
                                        <th>User - Received</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    @php
        $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
        $reports_footer_html = !empty($reports_footer->value) ? $reports_footer->value : '';
        $reports_footer_text = trim(preg_replace('/\s+/', ' ', strip_tags($reports_footer_html)));
    @endphp
    @if (!empty($reports_footer) && !empty($reports_footer->value))
        <div id="f10_page_footer" class="reports-footer-content">
            {!! $reports_footer->value !!}
        </div>
    @endif
</div>

<!-- F10 Form View Modal -->
<div class="modal fade" id="f10ViewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">F10 Form Details</h4>
            </div>
            <div class="modal-body">
                <div id="f10-modal-loading" class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Loading...</p>
                </div>
                <div id="f10-modal-content" style="display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-solid">
                                <div class="box-header">
                                    <h3 class="box-title">Form Information</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-bordered table-striped">
                                                <tr>
                                                    <th width="30%">Form No:</th>
                                                    <td id="modal_form_no"></td>
                                                </tr>
                                                <tr>
                                                    <th>Date:</th>
                                                    <td id="modal_form_date"></td>
                                                </tr>
                                                <tr>
                                                    <th>Business Location:</th>
                                                    <td id="modal_location_name"></td>
                                                </tr>
                                                <tr>
                                                    <th>Manager:</th>
                                                    <td id="modal_manager_name"></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-bordered table-striped">
                                                <tr>
                                                    <th width="30%">Document No:</th>
                                                    <td id="modal_document_no"></td>
                                                </tr>
                                                <tr>
                                                    <th>Total Amount:</th>
                                                    <td id="modal_total_amount"></td>
                                                </tr>
                                                <tr>
                                                    <th>Cash Amount:</th>
                                                    <td id="modal_cash_amount"></td>
                                                </tr>
                                                <tr>
                                                    <th>Bank Amount:</th>
                                                    <td id="modal_bank_amount"></td>
                                                </tr>
                                                <tr>
                                                    <th>Cheque Amount:</th>
                                                    <td id="modal_cheque_amount"></td>
                                                </tr>
                                                <tr>
                                                    <th>Card Amount:</th>
                                                    <td id="modal_card_amount"></td>
                                                </tr>
                                                <tr>
                                                    <th>Created By:</th>
                                                    <td id="modal_added_by"></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#f10_receipts_table').hide();
        $('#datatable-loading').show();

        function initSelect2() {
            if (typeof $.fn.select2 !== 'undefined') {
                try {
                    $('.select2').select2({
                        width: '100%'
                    });
                    return true;
                } catch (e) {
                    return false;
                }
            }
            return false;
        }

        if (!initSelect2()) {
            console.log('Select2 not available, trying again in 500ms');
            setTimeout(function() {
                if (!initSelect2()) {
                    console.error('Select2 failed to load after retry');
                }
            }, 500);
        }

        function initDateRangePicker() {
            if (typeof $.fn.daterangepicker !== 'undefined') {
                try {
                    $('#form_10_date_range_list').daterangepicker({
                        locale: {
                            format: 'YYYY-MM-DD'
                        }
                    });
                    return true;
                } catch (e) {
                    console.error('DateRangePicker initialization error:', e);
                    return false;
                }
            }
            return false;
        }

        if (!initDateRangePicker()) {
            setTimeout(function() {
                if (!initDateRangePicker()) {
                    console.error('DateRangePicker failed to load after retry');
                }
            }, 500);
        }

        if (typeof $.fn.daterangepicker !== 'undefined') {
            $('#form_10_date_range_list').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
        }

        function initDataTable() {
            if (typeof $.fn.DataTable !== 'undefined') {
                try {
                    var table = $('#f10_receipts_table').DataTable({
                        processing: true,
                        serverSide: true,
                        dom: '<"row margin-bottom-20 text-center"<"col-sm-12"B><"col-sm-5 text-align-start"f><"col-sm-7"l> r>tip',
                        buttons: [
                            {
                                extend: 'colvis',
                                className: 'btn btn-sm btn-default',
                            },
                            {
                                extend: 'csv',
                                footer: true,
                                text: '<i class="fa fa-file"></i> Export to CSV',
                                className: 'btn btn-sm btn-default',
                                exportOptions: {
                                    columns: function (idx, data, node) {
                                        var table = $(node).closest('table').DataTable();
                                        return table.column(idx).visible() && !$(node).hasClass('notexport');
                                    }
                                }
                            },
                            {
                                extend: 'excel',
                                footer: true,
                                text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                                className: 'btn btn-sm btn-default',
                                exportOptions: {
                                    columns: function (idx, data, node) {
                                        var table = $(node).closest('table').DataTable();
                                        return table.column(idx).visible() && !$(node).hasClass('notexport');
                                    }
                                }
                            },
                            {
                                extend: 'pdf',
                                footer: true,
                                text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                                className: 'btn btn-sm btn-default',
                                messageBottom: @json($reports_footer_text),
                                exportOptions: {
                                    columns: function (idx, data, node) {
                                        var table = $(node).closest('table').DataTable();
                                        return table.column(idx).visible() && !$(node).hasClass('notexport');
                                    }
                                }
                            },
                            {
                                extend: 'print',
                                footer: true,
                                text: '<i class="fa fa-print"></i> Print',
                                className: 'btn btn-sm btn-default',
                                messageBottom: @json($reports_footer_text),
                                exportOptions: {
                                    columns: function (idx, data, node) {
                                        var table = $(node).closest('table').DataTable();
                                        return table.column(idx).visible() && !$(node).hasClass('notexport');
                                    }
                                },
                                customize: function (win) {
                                    $(win.document.body).find('h1').css('text-align', 'center');
                                    $(win.document.body).find('h1').css('font-size', '25px');
                                },
                            }
                        ],
                        ajax: {
                            url: '/mpcs/get-form-f10-list',
                            data: function(d) {
                                var date_range = $('#form_10_date_range_list').val().split(' ~ ');
                                var start_date = date_range[0];
                                var end_date = date_range[1];

                                if (start_date && start_date.includes('/')) {
                                    var parts = start_date.split('/');
                                    start_date = parts[2] + '-' + parts[0] + '-' + parts[1];
                                }

                                if (end_date && end_date.includes('/')) {
                                    var parts = end_date.split('/');
                                    end_date = parts[2] + '-' + parts[0] + '-' + parts[1];
                                }
                                
                                d.start_date = start_date;
                                d.end_date = end_date;
                                d.location_id = $('#f10_location_id').val();
                                d.form_no = $('#f10_number_dropdown').val();
                                d.cashier_id = $('#cashier_dropdown').val();
                                d.manager_id = $('#manager_dropdown').val();
                                d.document_no = $('#document_no_dropdown').val();
                            },
                            error: function(xhr, error, thrown) {
                                console.log('AJAX error:', xhr, error, thrown);
                                $('#datatable-loading').hide();
                                $('#f10_receipts_table').show();
                                $('#f10_receipts_table').html('<tr><td colspan="7" class="text-center">Error loading data. Please try again.</td></tr>');
                            }
                        },
                        columns: [
                            {
                                data: 'action',
                                name: 'action',
                                orderable: false,
                                searchable: false
                            },
                            {
                                data: 'form_date',
                                name: 'form_date',
                                title: 'Date'
                            },
                            {
                                data: 'location_name',
                                name: 'location_name',
                                title: 'Business Location'
                            },
                            {
                                data: 'manager_name',
                                name: 'manager_name',
                                title: 'Manager',
                                defaultContent: '',
                                render: function(data, type, row) {
                                    return data || '';
                                }
                            },
                            {
                                data: 'form_no',
                                name: 'form_no',
                                title: 'F10 Form No'
                            },
                            {
                                data: 'document_no',
                                name: 'document_no',
                                title: 'Document Number',
                                defaultContent: '',
                                render: function(data, type, row) {
                                    return data || '';
                                }
                            },
                            {
                                data: 'added_by',
                                name: 'added_by',
                                title: 'User - Received'
                            }
                        ],
                        order: [[1, 'desc']], 
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                        language: {
                            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
                        },
                        initComplete: function() {
                            $('#datatable-loading').hide();
                            $('#f10_receipts_table').show();
                            $('.dt-buttons').addClass('btn-group').css({
                                'margin-bottom': '10px',
                                'display': 'flex',
                                'flex-wrap': 'wrap',
                                'gap': '5px'
                            });
                            
                            $('.dt-buttons .btn').css({
                                'margin-right': '5px',
                                'border-radius': '3px',
                                'padding': '6px 12px',
                                'font-size': '14px'
                            });
                        }
                    });

                    /*
                     * IS2015: the Action menu opened as an empty sliver.
                     *
                     * View and Print are always rendered (see
                     * F10FormController::getF10FormList), so nothing was missing -
                     * the menu was being CLIPPED by the scrollable table wrapper,
                     * which sets overflow-x: auto with overflow-y: hidden. A menu
                     * opening downward out of the first row is cut at the bottom
                     * edge, which is the thin white box in the ticket.
                     *
                     * Lifting the wrapper's overflow is not an option: Action is a
                     * column in a wide table that genuinely needs to scroll
                     * sideways. Moving the open menu to <body> and positioning it
                     * against the viewport removes every ancestor from the
                     * question. Same approach already proven on the Supplier
                     * Payments and Purchase Entries lists.
                     */
                    var F10ActionMenu = {
                        selector: '.f10-action-group',

                        bind: function () {
                            $(document).off('.f10ActionMenu');
                            $(window).off('.f10ActionMenu');

                            F10ActionMenu.closeAll();

                            $(document).on('show.bs.dropdown.f10ActionMenu', F10ActionMenu.selector, function () {
                                var $current = $(this);
                                F10ActionMenu.restore($current);
                                F10ActionMenu.closeAll($current);
                            });

                            $(document).on('shown.bs.dropdown.f10ActionMenu', F10ActionMenu.selector, function () {
                                F10ActionMenu.detach($(this));
                            });

                            $(document).on(
                                'hide.bs.dropdown.f10ActionMenu hidden.bs.dropdown.f10ActionMenu',
                                F10ActionMenu.selector,
                                function () { F10ActionMenu.restore($(this)); }
                            );

                            // A detached menu is placed against the viewport, so any
                            // movement leaves it pointing at the wrong row.
                            $(window).on('resize.f10ActionMenu scroll.f10ActionMenu', function () {
                                F10ActionMenu.closeAll();
                            });

                            // Paging/search replaces every row; a menu still on
                            // <body> would outlive its owner.
                            $('#f10_receipts_table')
                                .off('draw.dt.f10ActionMenu preXhr.dt.f10ActionMenu')
                                .on('draw.dt.f10ActionMenu preXhr.dt.f10ActionMenu', function () {
                                    F10ActionMenu.closeAll();
                                });
                        },

                        detach: function ($group) {
                            var $toggle = $group.children('.dropdown-toggle');
                            var $menu = $group.children('.f10-action-menu');

                            if (!$toggle.length || !$menu.length || $menu.parent().is('body')) {
                                return;
                            }

                            var rect = $toggle.get(0).getBoundingClientRect();
                            var vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
                            var vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
                            var gap = 10;
                            var menuWidth = Math.min(200, Math.max(150, vw - (gap * 2)));

                            $group.data('f10-detached-menu', $menu);
                            $menu.data('f10-menu-owner', $group.get(0));

                            $menu.addClass('f10-action-menu-detached')
                                .appendTo(document.body)
                                .css({
                                    display: 'block', position: 'fixed', visibility: 'hidden',
                                    top: 0, left: 0, right: 'auto',
                                    width: menuWidth, minWidth: menuWidth, maxWidth: menuWidth,
                                    maxHeight: 'none'
                                });

                            var natural = $menu.get(0).scrollHeight + 2;
                            var below = Math.max(0, vh - rect.bottom - gap);
                            var above = Math.max(0, rect.top - gap);
                            var full = Math.max(120, vh - (gap * 2));
                            var maxHeight, top;

                            if (natural <= below) {
                                maxHeight = below; top = rect.bottom + 4;
                            } else if (natural <= above) {
                                maxHeight = above; top = Math.max(gap, rect.top - natural - 4);
                            } else {
                                maxHeight = full; top = gap;
                            }

                            maxHeight = Math.min(maxHeight, full);

                            var left = Math.min(
                                Math.max(gap, rect.left),
                                Math.max(gap, vw - menuWidth - gap)
                            );

                            $menu.css({
                                top: Math.round(top),
                                left: Math.round(left),
                                maxHeight: Math.round(maxHeight),
                                overflowX: 'hidden',
                                // Always auto: content is then clipped to the box
                                // however wrong the height estimate turns out to be.
                                overflowY: 'auto',
                                visibility: 'visible'
                            });
                        },

                        closeAll: function ($except) {
                            $(F10ActionMenu.selector).each(function () {
                                var $group = $(this);
                                if ($except && $except.length && $group.get(0) === $except.get(0)) { return; }
                                $group.removeClass('open');
                                $group.children('.dropdown-toggle').attr('aria-expanded', 'false');
                                F10ActionMenu.restore($group);
                            });

                            $('body > .f10-action-menu-detached').each(function () {
                                var $menu = $(this);
                                var owner = $menu.data('f10-menu-owner');
                                if (owner && document.documentElement.contains(owner)) {
                                    F10ActionMenu.restore($(owner));
                                } else {
                                    $menu.remove();
                                }
                            });
                        },

                        restore: function ($group) {
                            var $menu = $group.data('f10-detached-menu');
                            if (!$menu || !$menu.length) { return; }

                            $menu.removeClass('f10-action-menu-detached')
                                .removeData('f10-menu-owner')
                                .removeAttr('style')
                                .appendTo($group);

                            $group.removeData('f10-detached-menu');
                        }
                    };

                    F10ActionMenu.bind();

                    $('#f10_location_id, #manager_dropdown, #f10_number_dropdown, #document_no_dropdown, #cashier_dropdown')
                        .change(function() {
                            $('#f10_receipts_table').hide();
                            $('#datatable-loading').show();
                            $('#loading-message').text('Loading...');
                            table.ajax.reload(function() {
                                $('#datatable-loading').hide();
                                $('#f10_receipts_table').show();
                            });
                        });
                    $('#form_10_date_range_list').on('apply.daterangepicker', function(ev, picker) {
                        // Update the display div with selected date range
                        var selectedRange = $('#form_10_date_range_list').val();
                        $('#selected_date_range_display').text(selectedRange.replace(' ~ ', ' - '));
                        
                        $('#f10_receipts_table').hide();
                        $('#datatable-loading').show();
                        $('#loading-message').text('Loading...');
                        table.ajax.reload(function() {
                            $('#datatable-loading').hide();
                            $('#f10_receipts_table').show();
                        });
                    });

                    // Handle view button click for individual F10 forms
                    $(document).on('click', '.view_f10_form', function(e) {
                        e.preventDefault();
                        
                        var formId = $(this).data('id');
                        
                        // Show loading state
                        $('#f10-modal-loading').show();
                        $('#f10-modal-content').hide();
                        
                        // Reset modal content
                        $('#modal_form_no').text('');
                        $('#modal_form_date').text('');
                        $('#modal_location_name').text('');
                        $('#modal_manager_name').text('');
                        $('#modal_document_no').text('');
                        $('#modal_total_amount').text('');
                        $('#modal_cash_amount').text('');
                        $('#modal_bank_amount').text('');
                        $('#modal_cheque_amount').text('');
                        $('#modal_card_amount').text('');
                        $('#modal_added_by').text('');
                        
                        // Fetch form details
                        $.ajax({
                            url: '/mpcs/get-f10-form-details/' + formId,
                            type: 'GET',
                            success: function(response) {
                                if (response.success) {
                                    var header = response.header;
                                    
                                    // Populate modal with data
                                    $('#modal_form_no').text(header.form_no || 'N/A');
                                    $('#modal_form_date').text(header.form_date || 'N/A');
                                    $('#modal_location_name').text(header.location_name || 'N/A');
                                    $('#modal_manager_name').text(header.manager_name || 'N/A');
                                    $('#modal_document_no').text('N/A'); // Document no not stored in F10 headers
                                    $('#modal_total_amount').text(header.total_amount ? parseFloat(header.total_amount).toFixed(2) : '0.00');
                                    $('#modal_cash_amount').text(header.cash_amount ? parseFloat(header.cash_amount).toFixed(2) : '0.00');
                                    $('#modal_bank_amount').text(header.bank_amount ? parseFloat(header.bank_amount).toFixed(2) : '0.00');
                                    $('#modal_cheque_amount').text(header.cheque_amount ? parseFloat(header.cheque_amount).toFixed(2) : '0.00');
                                    $('#modal_card_amount').text(header.card_amount ? parseFloat(header.card_amount).toFixed(2) : '0.00');
                                    $('#modal_added_by').text(header.added_by || 'N/A');
                                    
                                    // Hide loading and show content
                                    $('#f10-modal-loading').hide();
                                    $('#f10-modal-content').show();
                                    
                                    // Show modal
                                    $('#f10ViewModal').modal('show');
                                } else {
                                    alert('Error: ' + response.msg);
                                    $('#f10-modal-loading').hide();
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', xhr, status, error);
                                alert('Error loading form details. Please try again.');
                                $('#f10-modal-loading').hide();
                            }
                        });
                    });

                    // Handle print button click for individual F10 forms
                    /*
                     * IS2026: Print now uses the SERVER template.
                     *
                     * This handler previously fetched the receipt over AJAX and
                     * rebuilt the whole document in JavaScript (createF10FormHtml
                     * below), writing its own A5 stylesheet into a popup. That is
                     * why the printed output did not match the requested layout:
                     * the proper template already exists at
                     * Resources/views/forms/partials/print_f10_receipt.blade.php
                     * and is served by /mpcs/F10/receipt/{id}/print, but the list
                     * never called it.
                     *
                     * That server view is exactly the target layout in the ticket
                     * - business name heading, "F10 - CASH RECEIPT", the
                     * #/Description/Amount table, and the Prepared By / Checked By
                     * / Manager signature row - and it already carries the IS2015
                     * page-margin work.
                     *
                     * Opening the route directly also means one place defines the
                     * receipt. The JS builder is left in the file but is no longer
                     * reachable from here; it should be deleted once this is
                     * confirmed in production.
                     */
                    $(document).on('click', '.print_f10_form', function (e) {
                        e.preventDefault();

                        var formId = $(this).data('id');

                        if (!formId) {
                            alert('This receipt has no id and cannot be printed.');
                            return;
                        }

                        // The print view triggers window.print() itself.
                        window.open('/mpcs/F10/receipt/' + formId + '/print', '_blank');
                    });
                    
                    // Function to create F10 form HTML
                    function createF10FormHtml(header, details) {
                        var totalAmount = parseFloat(header.total_amount || 0);
                        var cashAmount = parseFloat(header.cash_amount || 0);
                        var bankAmount = parseFloat(header.bank_amount || 0);
                        var chequeAmount = parseFloat(header.cheque_amount || 0);
                        var cardAmount = parseFloat(header.card_amount || 0);
                        
                        return `
                            <div class="form-container">
                                <div class="text-center" style="margin-bottom:25px; position:relative;">
                                    <h3 style="margin:5px 0;font-weight:bold;">
                                        Cash Receipt
                                    </h3>
                                    <div style="position:absolute; right:20px; top:-10px; font-size:24px; font-weight:bold;">
                                        F10
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <div style="font-size:16px; margin-bottom: 10px;">
                                        <strong>F 10 Number:</strong> ${header.form_no || 'N/A'}
                                    </div>
                                    <div style="font-size:16px;">
                                        <strong>Document No:</strong> ${header.document_no || 'N/A'}
                                        <span style="float: right;">
                                            <strong>Date & Time:</strong> ${header.form_date || 'N/A'}
                                        </span>
                                    </div>
                                </div>
                                
                                <div style="font-size:16px; line-height:2.5; margin-bottom:30px;">
                                    <strong>Received</strong>
                                    <span style="margin-left:8px;">${header.currency_prefix || ''}</span>
                                    <span style="margin-left:5px;">${numberToWords(totalAmount)} Only</span>
                                    <strong style="margin-left:20px;">from</strong>
                                    <strong style="margin-left:8px;">Mr./ Mrs. / Miss</strong>
                                    <span style="margin-left:10px;">${header.manager_name || 'N/A'}</span>
                                    <strong style="margin-left:20px;">for</strong>
                                    <span style="margin-left:10px;">${header.location_name || 'N/A'}</span>
                                </div>
                                
                                <div style="width: 100%;">
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="font-size: 16px;">1. Cash</td>
                                            <td style="text-align: right; font-size: 16px;">${cashAmount.toFixed(2)}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 16px;">2. Bank / ATM Deposits</td>
                                            <td style="text-align: right; font-size: 16px;">${bankAmount.toFixed(2)}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 16px;">3. Cheques</td>
                                            <td style="text-align: right; font-size: 16px;">${chequeAmount.toFixed(2)}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 16px;">4. Credit Voucher</td>
                                            <td style="text-align: right; font-size: 16px;">${cardAmount.toFixed(2)}</td>
                                        </tr>
                                        <tr style="font-weight: bold;">
                                            <td style="font-size: 18px; color: red;">Total</td>
                                            <td style="text-align: right; font-weight: bold; font-size: 18px;">${totalAmount.toFixed(2)}</td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div style="margin-top: 60px;">
                                    <div style="display: flex; width: 100%;">
                                        <div style="width: 50%; text-align: center;">
                                            <div class="signature-line"></div>
                                            <p style="font-weight: bold;">Signature of the Manager</p>
                                        </div>
                                        <div style="width: 50%; text-align: center;">
                                            <div class="signature-line"></div>
                                            <p style="font-weight: bold;">Signature of the Cashier</p>
                                        </div>
                                    </div>
                                </div>
                                
                                @if (!empty($reports_footer) && !empty($reports_footer->value))
                                    <div class="footer">
                                        {!! $reports_footer->value !!}
                                    </div>
                                @endif
                            </div>
                        `;
                    }
                    
                    // Helper function to convert number to words
                    function numberToWords(num) {
                        if (num === 0 || isNaN(num)) return "Zero";

                        var ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                            'Seventeen', 'Eighteen', 'Nineteen'
                        ];

                        var tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty',
                            'Seventy', 'Eighty', 'Ninety'
                        ];

                        function convertLessThanThousand(n) {
                            if (n === 0) return '';
                            if (n < 20) return ones[n];
                            if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
                            return ones[Math.floor(n / 100)] + ' Hundred' +
                                (n % 100 ? ' ' + convertLessThanThousand(n % 100) : '');
                        }

                        function convertNumber(n) {
                            var word = '';

                            if (n >= 1000000000) {
                                word += convertLessThanThousand(Math.floor(n / 1000000000)) + ' Billion ';
                                n %= 1000000000;
                            }

                            if (n >= 1000000) {
                                word += convertLessThanThousand(Math.floor(n / 1000000)) + ' Million ';
                                n %= 1000000;
                            }

                            if (n >= 1000) {
                                word += convertLessThanThousand(Math.floor(n / 1000)) + ' Thousand ';
                                n %= 1000;
                            }

                            if (n > 0) {
                                word += convertLessThanThousand(n);
                            }

                            return word.trim();
                        }

                        var parts = num.toString().split('.');
                        var whole = parseInt(parts[0]);
                        var cents = parts[1] ? parseInt(parts[1].substring(0, 2)) : 0;

                        var words = convertNumber(whole);

                        if (cents > 0) {
                            words += ' and ' + convertNumber(cents) + ' Cents';
                        }

                        return words;
                    }

                    return true;
                } catch (e) {
                    console.error('DataTable initialization error:', e);
                    return false;
                }
            }
            return false;
        }

        if (!initDataTable()) {
            var retryCount = 0;
            var maxRetries = 5;

            function retryDataTable() {
                retryCount++;
                console.log(`Retry attempt ${retryCount} for DataTable`);

                if (initDataTable()) {
                    return true;
                } else if (retryCount < maxRetries) {
                    setTimeout(retryDataTable, retryCount * 500);
                } else {
                    console.log('Attempting to load DataTable from CDN...');
                    $('#loading-message').text('Loading DataTables...');
                    
                    $.getScript('https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js')
                        .done(function() {
                            $.getScript('https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js')
                                .done(function() {
                                    $.getScript('https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js')
                                        .done(function() {
                                            $.getScript('https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js')
                                                .done(function() {
                                                    $.getScript('https://cdn.datatables.net/buttons/2.3.6/js/buttons.colVis.min.js')
                                                        .done(function() {
                                                            setTimeout(function() {
                                                                if (initDataTable()) {
                                                                    $('#datatable-loading').hide();
                                                                    $('#f10_receipts_table').show();
                                                                }
                                                            }, 500);
                                                        });
                                                });
                                        });
                                });
                        })
                        .fail(function() {
                            $('#loading-message').text('Error loading. Please refresh.');
                        });
                }
            }

            setTimeout(retryDataTable, 1000);
        }
    });
</script>