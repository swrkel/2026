@extends('layouts.app')
@section('title', __('petropd::lang.list_pd_settlement'))

@section('content')
<style>
    /* S533: stable full-width List PD Settlement layout. */
    #petropd-list-page,
    #petropd-list-panel,
    #petropd-list-panel .box,
    #petropd-list-panel .box-body,
    #petropd-list-panel .dataTables_wrapper {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box;
    }

    #petropd-list-panel .box-body {
        padding-top: 8px !important;
        overflow: visible !important;
    }

    .petropd-list-table-wrap {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        overflow-x: visible;
        overflow-y: visible;
        box-sizing: border-box;
    }

    #list_pd_settlement {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        table-layout: fixed !important;
        margin: 0 !important;
    }

    #list_pd_settlement th,
    #list_pd_settlement td {
        box-sizing: border-box !important;
        font-size: 13px !important;
        line-height: 1.25 !important;
        padding: 6px 5px !important;
        vertical-align: middle !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-break: normal !important;
        text-overflow: clip !important;
    }

    #list_pd_settlement th {
        text-align: center !important;
        font-weight: 600 !important;
    }

    #list_pd_settlement th:nth-child(1),
    #list_pd_settlement td:nth-child(1) { width: 11% !important; }
    #list_pd_settlement th:nth-child(2),
    #list_pd_settlement td:nth-child(2) { width: 7% !important; text-align: center !important; }
    #list_pd_settlement th:nth-child(3),
    #list_pd_settlement td:nth-child(3) { width: 8% !important; text-align: center !important; }
    #list_pd_settlement th:nth-child(4),
    #list_pd_settlement td:nth-child(4) { width: 8% !important; text-align: center !important; }
    #list_pd_settlement th:nth-child(5),
    #list_pd_settlement td:nth-child(5) { width: 5% !important; text-align: center !important; }
    #list_pd_settlement th:nth-child(6),
    #list_pd_settlement td:nth-child(6) { width: 12% !important; }
    #list_pd_settlement th:nth-child(7),
    #list_pd_settlement td:nth-child(7) { width: 8% !important; text-align: center !important; }
    #list_pd_settlement th:nth-child(8),
    #list_pd_settlement td:nth-child(8) { width: 11% !important; }
    #list_pd_settlement th:nth-child(9),
    #list_pd_settlement td:nth-child(9) { width: 8% !important; }
    #list_pd_settlement th:nth-child(10),
    #list_pd_settlement td:nth-child(10) { width: 6% !important; }
    #list_pd_settlement th:nth-child(11),
    #list_pd_settlement td:nth-child(11) {
        width: 9% !important;
        text-align: right !important;
        white-space: nowrap !important;
    }
    #list_pd_settlement th:nth-child(12),
    #list_pd_settlement td:nth-child(12) { width: 7% !important; }

    #list_pd_settlement td:first-child {
        overflow: visible !important;
        text-align: center !important;
    }

    #list_pd_settlement .petropd-settlement-action-shell {
        display: inline-flex !important;
        align-items: stretch !important;
        justify-content: center !important;
        width: 100% !important;
        max-width: 145px;
        min-width: 0 !important;
        white-space: normal !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    #list_pd_settlement .petropd-settlement-primary-action,
    #list_pd_settlement .petropd-settlement-action-trigger {
        min-height: 32px !important;
        min-width: 0 !important;
        padding: 5px 7px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 12px !important;
        line-height: 1.15 !important;
        white-space: normal !important;
    }

    #list_pd_settlement .petropd-settlement-primary-action {
        flex: 1 1 auto;
    }

    #list_pd_settlement .petropd-settlement-primary-action + .petropd-settlement-action-trigger {
        flex: 0 0 30px;
        padding-left: 6px !important;
        padding-right: 6px !important;
        border-left-color: rgba(255, 255, 255, 0.35);
    }

    #list_pd_settlement .petropd-status-badge {
        display: inline-block;
        min-width: 66px;
        padding: 6px 8px;
        border-radius: 4px;
        color: #fff !important;
        font-size: 12px !important;
        font-weight: 600;
        line-height: 1.1;
        text-align: center;
        white-space: nowrap !important;
    }

    #list_pd_settlement .petropd-status-completed { background: #28a745 !important; }
    #list_pd_settlement .petropd-status-pending { background: #dc3545 !important; }
    #list_pd_settlement .petropd-status-editing { background: #f39c12 !important; }

    /* Keep exports, entries and search on one line. */
    #petropd-list-panel .petropd-list-toolbar {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        gap: 7px;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 40px;
        margin: 0 0 7px 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    #petropd-list-panel .petropd-list-toolbar .dt-buttons {
        display: inline-flex !important;
        flex: 0 0 auto !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        gap: 5px;
        margin: 0 !important;
        float: none !important;
    }

    #petropd-list-panel .petropd-list-toolbar .dt-button,
    #petropd-list-panel .petropd-list-toolbar .btn {
        flex: 0 0 auto !important;
        margin: 0 !important;
        padding: 6px 9px !important;
        min-height: 32px;
        font-size: 12px !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
    }

    #petropd-list-panel .petropd-list-toolbar .dataTables_length,
    #petropd-list-panel .petropd-list-toolbar .dataTables_filter {
        float: none !important;
        flex: 0 0 auto !important;
        margin: 0 !important;
        padding: 0 !important;
        white-space: nowrap !important;
    }

    #petropd-list-panel .petropd-list-toolbar .dataTables_length {
        margin-left: auto !important;
    }

    #petropd-list-panel .petropd-list-toolbar .dataTables_length label,
    #petropd-list-panel .petropd-list-toolbar .dataTables_filter label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px;
        margin: 0 !important;
        font-size: 12px !important;
        font-weight: 400 !important;
    }

    #petropd-list-panel .petropd-list-toolbar .dataTables_length select {
        width: 70px !important;
        min-width: 70px !important;
        height: 32px !important;
        margin: 0 !important;
    }

    #petropd-list-panel .petropd-list-toolbar .dataTables_filter input {
        width: 185px !important;
        min-width: 150px !important;
        height: 32px !important;
        margin: 0 !important;
    }

    #petropd-list-panel .dataTables_processing {
        margin-top: 0 !important;
    }

    #petropd-list-panel .petropd-list-footer {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 12px;
        margin-top: 8px !important;
    }

    #petropd-list-panel .petropd-list-footer .dataTables_info,
    #petropd-list-panel .petropd-list-footer .dataTables_paginate {
        float: none !important;
        margin: 0 !important;
        padding-top: 0 !important;
    }

    @media (max-width: 1199px) {
        .petropd-list-table-wrap {
            overflow-x: auto !important;
        }

        #list_pd_settlement {
            min-width: 1080px !important;
        }
    }
</style>

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petropd::lang.petro_pd')</a></li>
                    <li><span>@lang('petropd::lang.list_pd_settlement')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner" id="petropd-list-page">
    @if(!empty($message)) {!! $message !!} @endif
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petropd::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('petropd::lang.pump_operator').':') !!}
                        {!! Form::select('pump_operator', $pump_operators, null, ['class' => 'form-control select2', 'placeholder' => __('petropd::lang.all')]); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('petropd::lang.settlement_number').':') !!}
                        {!! Form::select('settlement_no', $settlement_nos, null, ['class' => 'form-control select2', 'placeholder' => __('petropd::lang.all')]); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'expense_date_range', 'readonly']); !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div id="petropd-list-panel">
    @component('components.widget', ['class' => 'box-primary'])
    <div class="petropd-list-table-wrap">
        <table class="table table-bordered table-striped nowrap" id="list_pd_settlement">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petropd::lang.status')</th>
                    <th>@lang('petropd::lang.settlement_date')</th>
                    <th>@lang('petropd::lang.settlement_no')</th>
                    <th>@lang('petropd::lang.shift_number')</th>
                    <th>Pump<br>Operator</th>
                    <th>@lang('petropd::lang.pumps')</th>
                    <th>@lang('petropd::lang.location')</th>
                    <th>@lang('petropd::lang.work_shift')</th>
                    <th>@lang('petropd::lang.note')</th>
                    <th>@lang('petropd::lang.total_amnt')</th>
                    <th>@lang('petropd::lang.added_user')</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray footer-total">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right"><strong>@lang('sale.total'):</strong></td>
                    <td class="text-right"><strong><span id="footer_total_amount">0.00</span></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent
    </div>

    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div id="settlement_print" class="container"></div>
</section>
<!-- /.content -->

@endsection
@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){

        function petroPdExportButtons(title) {
            return [
                {extend: 'csv', text: '<i class="fa fa-file"></i> Export to CSV', className: 'btn btn-default btn-sm', title: title, exportOptions: {columns: ':visible:not(.notexport)'}},
                {extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> Export to Excel', className: 'btn btn-default btn-sm', title: title, exportOptions: {columns: ':visible:not(.notexport)'}},
                {extend: 'colvis', text: '<i class="fa fa-columns"></i> Column Visibility', className: 'btn btn-default btn-sm', columns: ':not(.notexport)'},
                {extend: 'pdf', text: '<i class="fa fa-file-pdf-o"></i> Export to PDF', className: 'btn btn-default btn-sm', title: title, exportOptions: {columns: ':visible:not(.notexport)'}},
                {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm', title: title, exportOptions: {columns: ':visible:not(.notexport)'}}
            ];
        }

        var columns = [
            { data: 'action', searchable: false, orderable: false, width: '11%', className: 'petropd-action-cell text-center' },
            { data: 'status', name: 'status', width: '7%', className: 'text-center' },
            { data: 'transaction_date', name: 'transaction_date', width: '8%', className: 'text-center' },
            { data: 'settlement_no', name: 'settlement_no', width: '8%', className: 'text-center' },
            { data: 'shift_number', name: 'pump_operator_assignments.shift_number', width: '5%', className: 'text-center' },
            { data: 'pump_operator_name', name: 'pump_operators.name', width: '12%' },
            { data: 'pump_nos', name: 'pump_nos', searchable: false, width: '8%', className: 'text-center' },
            { data: 'location_name', name: 'location_name', searchable: true, width: '11%' },
            { data: 'shift', name: 'shift', searchable: false, width: '8%' },
            { data: 'note', name: 'note', width: '6%' },
            { data: 'total_amount', name: 'total_amount', width: '9%', className: 'text-right' },
            { data: 'created_by', searchable: false, name: 'created_by', width: '7%' }
        ];

        list_pd_settlement = $('#list_pd_settlement').DataTable({
            processing: true,
            serverSide: true,
            scrollX: false,
            autoWidth: false,
            dom: "<'petropd-list-toolbar'Blf>rt<'petropd-list-footer'ip>",
            buttons: petroPdExportButtons('List PD Settlement'),
            order: [],
            // IS1841: saved DataTables state is shared by every browser tab.
            // It allowed one tab to restore an old result position/filter while
            // another tab showed the current settlements. Always request the
            // live first page from the server instead.
            stateSave: false,
            displayStart: 0,
            deferRender: true,
            searchDelay: 250,
            ajax: {
                url: '{{ route('petropd.list-pd-settlement') }}',
                type: 'GET',
                dataType: 'json',
                cache: false,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: function(d) {
                    var pageParams = new URLSearchParams(window.location.search);
                    d.saved_settlement_id = pageParams.get('saved_settlement_id') || '';
                    d._ts = Date.now();
                    d.location_id = $('select#location_id').val();
                    d.pump_operator = $('select#pump_operator').val();
                    d.settlement_no = $('select#settlement_no').val();
                    d.start_date = $('input#expense_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#expense_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                },
                error: function(xhr) {
                    var message = 'Unable to load PD settlements.';
                    if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) {
                        message = xhr.responseJSON.message || xhr.responseJSON.msg;
                    }
                    console.error('Petro PD settlement list AJAX error', xhr.status, xhr.responseText);
                    if (window.toastr) {
                        toastr.error(message);
                    }
                }
            },
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
                    visible: true,
                    width: '11%',
                    className: 'petropd-action-cell text-center'
                },
                { targets: 1, width: '7%', className: 'text-center' },
                { targets: 2, width: '8%', className: 'text-center' },
                { targets: 3, width: '8%', className: 'text-center' },
                { targets: 4, width: '5%', className: 'text-center' },
                { targets: 5, width: '12%' },
                { targets: 6, width: '8%', className: 'text-center' },
                { targets: 7, width: '11%' },
                { targets: 8, width: '8%' },
                { targets: 9, width: '6%' },
                { targets: 10, width: '9%', className: 'text-right' },
                { targets: 11, width: '7%' }
            ],
            columns: columns,
            initComplete: function() {
                var api = this.api();
                api.column(0).visible(true, false);
                api.columns.adjust();
            },
            fnDrawCallback: function(oSettings) {
                var api = this.api();
                // Action is a mandatory operational column. Keep it visible even
                // when an old DataTables/local browser state tried to hide it.
                api.column(0).visible(true, false);
                api.columns.adjust();

                var total_amount = 0.00;
                api.rows({page: 'current'}).every(function(){
                    var row = this.data() || {};
                    var number = row.total_amount || 0;
                    if (typeof number === 'string') {
                        number = number.replace(/,/g, '').replace(/[^0-9.\-]/g, '');
                    }
                    var parsed = parseFloat(number);
                    if (!isNaN(parsed)) {
                        total_amount += parsed;
                    }
                });

                total_amount = parseFloat(total_amount || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                    useGrouping: true
                });

                $('#footer_total_amount').text(total_amount);
            },
        });

        function adjustPetroPdSettlementTable() {
            if (!list_pd_settlement) {
                return;
            }

            window.requestAnimationFrame(function() {
                list_pd_settlement.columns.adjust();
            });
        }

        var petroPdResizeTimer = null;
        function schedulePetroPdSettlementTableAdjust() {
            window.clearTimeout(petroPdResizeTimer);
            petroPdResizeTimer = window.setTimeout(adjustPetroPdSettlementTable, 80);
            window.setTimeout(adjustPetroPdSettlementTable, 280);
        }

        $(window)
            .off('resize.petropdListSettlement')
            .on('resize.petropdListSettlement', schedulePetroPdSettlementTableAdjust);

        $(document)
            .off('click.petropdListSettlementSidebar', '[data-toggle="push-menu"], [data-toggle="offcanvas"], .sidebar-toggle, #sidebar-toggle, .side-bar-toggle')
            .on('click.petropdListSettlementSidebar', '[data-toggle="push-menu"], [data-toggle="offcanvas"], .sidebar-toggle, #sidebar-toggle, .side-bar-toggle', schedulePetroPdSettlementTableAdjust);

        $('.main-sidebar, .content-wrapper, .main-content-inner')
            .off('transitionend.petropdListSettlement')
            .on('transitionend.petropdListSettlement', schedulePetroPdSettlementTableAdjust);

        if (window.ResizeObserver) {
            var petroPdLastPanelWidth = null;
            var petroPdListResizeObserver = new ResizeObserver(function(entries) {
                if (!entries.length) {
                    return;
                }

                var currentWidth = Math.round(entries[0].contentRect.width || 0);
                if (currentWidth > 0 && currentWidth !== petroPdLastPanelWidth) {
                    petroPdLastPanelWidth = currentWidth;
                    schedulePetroPdSettlementTableAdjust();
                }
            });
            var petroPdListPanel = document.getElementById('petropd-list-panel');
            if (petroPdListPanel) {
                petroPdListResizeObserver.observe(petroPdListPanel);
            }
        }

        schedulePetroPdSettlementTableAdjust();

        // Keep long-open or background tabs synchronized after another tab
        // saves/finalizes a settlement.
        var petroPdLastLiveReload = 0;
        function reloadPetroPdSettlementList() {
            var now = Date.now();
            if (!list_pd_settlement || now - petroPdLastLiveReload < 750) {
                return;
            }

            petroPdLastLiveReload = now;
            list_pd_settlement.ajax.reload(null, true);
        }

        $(window)
            .off('focus.petropdLiveSettlementList pageshow.petropdLiveSettlementList')
            .on('focus.petropdLiveSettlementList pageshow.petropdLiveSettlementList', reloadPetroPdSettlementList);

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                reloadPetroPdSettlementList();
            }
        });

        $('#location_id, #pump_operator, #settlement_no, #type, #expense_date_range').change(function(){
            list_pd_settlement.ajax.reload(null, true);
        });

        $(document).on('click', 'a.delete_settlement_button', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).attr('href');
                    var data = $(this).serialize();
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                            list_pd_settlement.ajax.reload();
                        },
                    });
                }
            });
        });
    });

    $(document).on('click', '.edit_contact_button', function(e) {
        e.preventDefault();
        $('div.pump_operator_modal').load($(this).attr('href'), function() {
            $(this).modal('show');
        });
    });

    $('#location_id').select2();

    $(document).on('click', '.print_settlement_button', function(e) {
        e.preventDefault();
        var url = $(this).data('href');

        if (!url) {
            toastr.error('Print URL not found');
            return false;
        }

        $.ajax({
            method: 'get',
            url: url,
            data: {},
            success: function(result) {
                $('#settlement_print').html(result);

                var divToPrint = document.getElementById('settlement_print');
                var newWin = window.open('', 'Print-Ledger');

                if (!newWin) {
                    toastr.error('Please allow pop-ups to print the settlement.');
                    return;
                }

                // Write only the AJAX print fragment. Do not place literal
                // closing BODY/HTML tags inside this page's JavaScript because
                // global HTML-response injectors can mistake them for the
                // current document's real closing tag.
                newWin.document.open();
                newWin.document.write(divToPrint.innerHTML);
                newWin.document.close();

                // Allow linked styles to finish loading before printing.
                window.setTimeout(function() {
                    if (newWin.closed) {
                        return;
                    }
                    newWin.focus();
                    newWin.print();
                }, 500);
            },
            error: function(xhr, status, error) {
                console.error('Print error:', error, xhr.responseText);
                var errorMessage = "Something went wrong while loading print view.";
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.msg) {
                        errorMessage = response.msg;
                    }
                } catch (e) {
                    // Use default error message
                }
                toastr.error(errorMessage);
            }
        });
    });

    $('#settlement_print').css('visibility', 'hidden');
</script>

{{-- Petro PD List Settlement action menu visibility/functionality fix --}}
@include('petropd::partials.pd_settlement_action_dropdown_fix')
@endsection
