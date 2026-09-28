@extends('layouts.app')
@section('title', __('petrodirect::lang.list_settlement'))

@section('content')

@include('petrodirect::partials.global_tab_standard')

<style>
    /* IS1770: List Direct Settlements must use the complete available page width. */
    .direct-settlement-list-wrap {
        position: relative;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 8px;
    }

    #list_settlement {
        width: 100% !important;
        min-width: 1120px;
        table-layout: fixed;
        margin-bottom: 0 !important;
    }

    #list_settlement th,
    #list_settlement td {
        font-size: 12px !important;
        line-height: 1.35 !important;
        vertical-align: middle !important;
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: normal;
        padding: 8px 6px !important;
    }

    #list_settlement thead th {
        text-align: center;
        font-weight: 700;
    }

    #list_settlement tbody td:nth-child(11),
    #list_settlement thead th:nth-child(11) {
        text-align: right;
    }

    #list_settlement tbody td:first-child,
    #list_settlement thead th:first-child {
        text-align: center;
        overflow: visible !important;
    }

    #list_settlement tbody td:first-child > .btn-group > .dropdown-toggle {
        min-height: 34px !important;
        padding: 7px 10px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        font-size: 12px !important;
    }

    #list_settlement tbody td:first-child > .btn-group > .dropdown-toggle .caret {
        margin-left: 6px;
    }

    #list_settlement .label,
    #list_settlement .badge,
    #list_settlement .btn {
        font-size: 12px !important;
    }

    #list_settlement_wrapper {
        width: 100%;
        overflow: visible !important;
    }

    #list_settlement_wrapper > .row {
        margin-left: 0;
        margin-right: 0;
    }

    #list_settlement_wrapper .dataTables_filter input {
        max-width: 220px;
    }

    /* Action menus are temporarily moved to body while open so the horizontal
       table wrapper cannot clip View / Print / Edit / Edit No Change. */
    body > .direct-settlement-floating-menu {
        position: fixed !important;
        display: block !important;
        min-width: 190px;
        z-index: 10050 !important;
        max-height: calc(100vh - 20px);
        overflow-y: auto;
    }


    /* LA-1092: global theme rules must not hide the Print action. */
    ul.direct-settlement-action-menu > li.direct-settlement-print-item,
    ul.direct-settlement-action-menu > li.direct-settlement-print-item > a.print_settlement_button,
    body > ul.direct-settlement-floating-menu > li.direct-settlement-print-item,
    body > ul.direct-settlement-floating-menu > li.direct-settlement-print-item > a.print_settlement_button {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }


    /* S571: compact List Direct Settlement card and keep every control in one row. */
    #direct-settlement-list-card > .box,
    #direct-settlement-list-card .box-body {
        margin-bottom: 0 !important;
        min-height: 0 !important;
    }

    #direct-settlement-list-card .box-header {
        display: none !important;
        min-height: 0 !important;
        height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 0 !important;
    }

    #direct-settlement-list-card .box-body {
        padding: 12px 14px 14px !important;
    }

    .direct-settlement-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px 14px;
        width: 100%;
        min-height: 48px;
        margin: 0 0 12px;
        padding: 0;
    }

    .direct-settlement-toolbar-left,
    .direct-settlement-toolbar-right {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        min-width: 0;
    }

    .direct-settlement-toolbar-left {
        flex: 1 1 auto;
    }

    .direct-settlement-toolbar-right {
        flex: 0 1 auto;
        margin-left: auto;
        justify-content: flex-end;
    }

    #direct_settlement_toolbar .dt-buttons,
    #direct_settlement_toolbar .dataTables_length,
    #direct_settlement_toolbar .dataTables_filter {
        float: none !important;
        width: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #direct_settlement_toolbar .dt-buttons {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    #direct_settlement_toolbar .dt-buttons .btn,
    #direct_settlement_toolbar .direct-settlement-add-button {
        min-height: 38px;
        margin: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }

    #direct_settlement_toolbar .dataTables_length label,
    #direct_settlement_toolbar .dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin: 0 !important;
        white-space: nowrap;
    }

    #direct_settlement_toolbar .dataTables_filter input {
        width: 170px !important;
        max-width: 170px !important;
        min-height: 38px;
        margin-left: 0 !important;
    }

    #direct_settlement_toolbar .dataTables_length select {
        min-height: 38px;
        margin: 0 2px;
    }


    /*
     * Petro Direct List Settlement - global functional-button standard.
     * This page uses DataTables toolbar buttons rather than Bootstrap page tabs,
     * so it needs a scoped equivalent of the ERP compact height/colour standard.
     */
    #direct_settlement_toolbar .dt-buttons .btn,
    #direct_settlement_toolbar .dt-buttons .dt-button,
    #direct_settlement_toolbar .direct-settlement-add-button {
        box-sizing: border-box !important;
        height: 42px !important;
        min-height: 42px !important;
        padding: 0 16px !important;
        border-radius: 8px !important;
        border-width: 1px !important;
        color: #ffffff !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        line-height: 40px !important;
        text-shadow: none !important;
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.14) !important;
        white-space: nowrap !important;
    }

    #direct_settlement_toolbar .dt-buttons .buttons-csv {
        background: linear-gradient(135deg, #0f766e, #06b6d4) !important;
        border-color: #0f766e !important;
    }

    #direct_settlement_toolbar .dt-buttons .buttons-excel {
        background: linear-gradient(135deg, #15803d, #22c55e) !important;
        border-color: #15803d !important;
    }

    #direct_settlement_toolbar .dt-buttons .buttons-colvis {
        background: linear-gradient(135deg, #7c3aed, #2563eb) !important;
        border-color: #7c3aed !important;
    }

    #direct_settlement_toolbar .dt-buttons .buttons-pdf {
        background: linear-gradient(135deg, #dc2626, #f97316) !important;
        border-color: #dc2626 !important;
    }

    #direct_settlement_toolbar .dt-buttons .buttons-print {
        background: linear-gradient(135deg, #334155, #0f172a) !important;
        border-color: #334155 !important;
    }

    #direct_settlement_toolbar .direct-settlement-add-button {
        background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
        border-color: #2563eb !important;
    }

    #direct_settlement_toolbar .dt-buttons .btn:hover,
    #direct_settlement_toolbar .dt-buttons .btn:focus,
    #direct_settlement_toolbar .dt-buttons .dt-button:hover,
    #direct_settlement_toolbar .dt-buttons .dt-button:focus,
    #direct_settlement_toolbar .direct-settlement-add-button:hover,
    #direct_settlement_toolbar .direct-settlement-add-button:focus {
        color: #ffffff !important;
        filter: brightness(1.08) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.20) !important;
    }

    #direct_settlement_toolbar .dataTables_length select,
    #direct_settlement_toolbar .dataTables_filter input {
        height: 42px !important;
        min-height: 42px !important;
        border-radius: 8px !important;
    }

    @media (max-width: 767px) {
        #direct_settlement_toolbar .dt-buttons .btn,
        #direct_settlement_toolbar .dt-buttons .dt-button,
        #direct_settlement_toolbar .direct-settlement-add-button {
            height: 40px !important;
            min-height: 40px !important;
            padding: 0 12px !important;
            font-size: 13px !important;
            line-height: 38px !important;
        }
    }

    #list_settlement_wrapper > .direct-settlement-empty-control-row {
        display: none !important;
        height: 0 !important;
        min-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    #list_settlement_wrapper > .row {
        min-height: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    #list_settlement_wrapper .dataTables_info,
    #list_settlement_wrapper .dataTables_paginate {
        margin-top: 10px !important;
        padding-top: 0 !important;
    }

    .direct-settlement-status {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        min-width: 78px;
        min-height: 28px;
        padding: 5px 10px !important;
        border-radius: 14px !important;
        color: #fff !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        white-space: nowrap !important;
    }

    .direct-settlement-status-completed { background: #16a34a !important; }
    .direct-settlement-status-editing   { background: #f59e0b !important; }
    .direct-settlement-status-pending   { background: #dc2626 !important; }

    /* IS2265: compact Note action with full-note hover and click detail modal. */
    #list_settlement .direct-settlement-note-btn {
        min-width: 64px;
        min-height: 28px;
        border-radius: 6px;
        font-weight: 700;
        white-space: nowrap !important;
    }

    #list_settlement .direct-settlement-note-export-text {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    .direct-settlement-note-tooltip .tooltip-inner {
        max-width: 420px !important;
        text-align: left !important;
        white-space: pre-wrap !important;
        overflow-wrap: anywhere !important;
    }

    #direct_settlement_note_modal .direct-settlement-note-content {
        min-height: 90px;
        padding: 14px 16px;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        background: #f8fafc;
        color: #1f2937;
        font-size: 14px;
        line-height: 1.55;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    @media (max-width: 991px) {
        #list_settlement {
            min-width: 1120px;
        }
    }
</style>



<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrodirect::lang.petro')</a></li>
                    <li><span>@lang( 'petrodirect::lang.mange_list_settlement') </span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner direct-settlement-list-page">
    @if(!empty($message)) {!! $message !!} @endif
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('petrodirect::lang.pump_operator').':') !!}
                        {!! Form::select('pump_operator', $pump_operators, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all')]); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('petrodirect::lang.settlement_number').':') !!}
                        {!! Form::select('settlement_no', $settlement_nos, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all')]); !!}
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

    <div id="direct-settlement-list-card">
    @component('components.widget', ['class' => 'box-primary'])
    <div id="direct_settlement_toolbar" class="direct-settlement-toolbar">
        <div class="direct-settlement-toolbar-left"></div>
        <div class="direct-settlement-toolbar-right">
            <a class="btn btn-primary direct-settlement-add-button" href="{{ route('petrodirect.settlement.create') }}">
                <i class="fa fa-plus"></i> @lang('messages.add')
            </a>
        </div>
    </div>
    <div class="direct-settlement-list-wrap">
        <table class="table table-bordered table-striped" id="list_settlement" style="width:100%">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petrodirect::lang.status')</th>
                    <th>@lang('petrodirect::lang.settlement_date')</th>
                    <th>@lang('petrodirect::lang.settlement_no')</th>
                    <th>@lang('petrodirect::lang.shift_number')</th>
                    <th>@lang('petrodirect::lang.pump_operator_name')</th>
                    <th>@lang('petrodirect::lang.pumps')</th>
                    <th>@lang('petrodirect::lang.location')</th>
                    <th>@lang('petrodirect::lang.shift')</th>
                    <th>@lang('petrodirect::lang.note')</th> 
                    <th>@lang('petrodirect::lang.total_amnt')</th>
                    <th>@lang('petrodirect::lang.added_user')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent
    </div>

    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" id="direct_settlement_note_modal" tabindex="-1" role="dialog" aria-labelledby="direct_settlement_note_modal_title">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="direct_settlement_note_modal_title">
                        <i class="fa fa-sticky-note"></i> @lang('petrodirect::lang.note')
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="direct-settlement-note-content"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>

    <div id="settlement_print" class="container"></div>
</section>
<!-- /.content -->

@endsection
@section('javascript')
<script type="text/javascript">
    $(document).ready( function(){
    var columns = [
            { data: 'action', searchable: false, orderable: false },
            { data: 'status', name: 'status' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'settlement_no', name: 'settlement_no' },
            { data: 'shift_number', name: 'settlements.work_shift', defaultContent: '' },
            { data: 'pump_operator_name', name: 'pump_operators.name' },
            { data: 'pump_nos', name: 'pump_nos', searchable: false },
            { data: 'location_name', name: 'business_locations.name' },
            { data: 'shift', name: 'shift', searchable: false},
            { data: 'note', name: 'settlements.note' },
            { data: 'total_amount', name: 'total_amount' },
            { data: 'created_by',searchable: false, name: 'created_by' }
        ];
  
    var directSettlementMenuState = {
        owner: null,
        menu: null
    };

    function restoreDirectSettlementActionMenu() {
        if (!directSettlementMenuState.owner || !directSettlementMenuState.menu) {
            return;
        }

        directSettlementMenuState.menu
            .removeClass('direct-settlement-floating-menu')
            .removeAttr('style')
            .appendTo(directSettlementMenuState.owner);

        directSettlementMenuState.owner = null;
        directSettlementMenuState.menu = null;
    }

    function positionDirectSettlementActionMenu($group) {
        restoreDirectSettlementActionMenu();

        var $menu = $group.children('.dropdown-menu').first();
        var $button = $group.children('.dropdown-toggle').first();
        if (!$menu.length || !$button.length) {
            return;
        }

        var rect = $button.get(0).getBoundingClientRect();

        /*
         * MA-002: measure the REAL menu size.
         *
         * outerHeight() was being read while the menu was still inside a
         * closed dropdown, so jQuery returned 0 and the code fell back to a
         * 170px estimate. With five items (View, Print, Edit, Edit No Change,
         * Mechanical Meter) the menu is taller than that, so the
         * "would it overflow the viewport" test passed when it should have
         * failed and the last items were pushed off-screen.
         *
         * Render it off-screen but measurable, take the real numbers, then put
         * it back. visibility:hidden keeps it invisible while still giving it
         * a real box.
         */
        var measured = $menu.get(0).style.cssText;
        $menu.css({
            display: 'block',
            visibility: 'hidden',
            position: 'fixed',
            top: '-10000px',
            left: '-10000px',
            maxHeight: 'none'
        });
        var menuWidth = Math.max($menu.outerWidth() || 190, 190);
        var estimatedHeight = Math.max($menu.outerHeight() || 170, 170);
        $menu.get(0).style.cssText = measured;

        var left = Math.min(
            Math.max(8, rect.left),
            Math.max(8, window.innerWidth - menuWidth - 8)
        );
        var top = rect.bottom + 2;

        var spaceBelow = window.innerHeight - rect.bottom - 8;
        var spaceAbove = rect.top - 8;

        if (estimatedHeight > spaceBelow) {
            if (spaceAbove >= estimatedHeight) {
                // Enough room above: flip the menu up.
                top = Math.max(8, rect.top - estimatedHeight - 2);
            } else {
                /*
                 * Not enough room either way. Anchor to whichever side has
                 * more space and let the menu scroll, rather than letting it
                 * run off the bottom of the viewport where the last items
                 * become unreachable.
                 */
                /*
                 | LA: the menu appeared far from its button.
                 |
                 | This used to set top = 8, pinning the menu to the TOP of the
                 | viewport whenever neither side had room. With the table near the
                 | top of the page the menu then rendered a long way from the
                 | Actions button it belongs to - exactly what was reported.
                 |
                 | The menu now stays anchored to the button in both directions and
                 | SCROLLS instead of being relocated. Growing upward it is placed
                 | so its bottom edge meets the button; growing downward its top
                 | edge meets the button. Either way the menu visibly belongs to the
                 | control that opened it.
                 */
                if (spaceAbove > spaceBelow) {
                    var upwardHeight = Math.max(120, spaceAbove);

                    // Bottom edge against the button, never above the viewport.
                    top = Math.max(8, rect.top - upwardHeight - 2);

                    $menu.data('directSettlementMaxHeight', upwardHeight);
                } else {
                    top = rect.bottom + 2;
                    $menu.data('directSettlementMaxHeight', Math.max(120, spaceBelow));
                }
            }
        }

        directSettlementMenuState.owner = $group;
        directSettlementMenuState.menu = $menu;

        $menu
            .appendTo(document.body)
            .addClass('direct-settlement-floating-menu')
            .css({
                top: top + 'px',
                left: left + 'px',
                right: 'auto',
                maxHeight: ($menu.data('directSettlementMaxHeight')
                    ? $menu.data('directSettlementMaxHeight') + 'px'
                    : ''),
                overflowY: ($menu.data('directSettlementMaxHeight') ? 'auto' : '')
            });

        $menu.removeData('directSettlementMaxHeight');
    }

    function adjustDirectSettlementTable() {
        if ($.fn.dataTable && $.fn.dataTable.isDataTable('#list_settlement')) {
            try {
                $('#list_settlement').DataTable().columns.adjust();
            } catch (error) {
                // DataTables can be between draws while the sidebar is animating.
            }
        }
    }

    function organizeDirectSettlementToolbar() {
        var $wrapper = $('#list_settlement_wrapper');
        var $toolbar = $('#direct_settlement_toolbar');
        if (!$wrapper.length || !$toolbar.length) {
            return;
        }

        var $left = $toolbar.children('.direct-settlement-toolbar-left');
        var $right = $toolbar.children('.direct-settlement-toolbar-right');
        var $buttons = $wrapper.find('.dt-buttons').first();
        var $length = $wrapper.find('.dataTables_length').first();
        var $filter = $wrapper.find('.dataTables_filter').first();
        var $add = $right.children('.direct-settlement-add-button');

        if ($buttons.length) {
            $buttons.detach().appendTo($left);
        }
        if ($length.length) {
            $length.detach().appendTo($right);
        }
        if ($filter.length) {
            $filter.detach().appendTo($right);
        }
        if ($add.length) {
            $add.detach().appendTo($right);
        }

        $wrapper.children('.row').each(function () {
            var $row = $(this);
            var hasControls = $row.find(
                '.dt-buttons, .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate'
            ).length > 0;
            var hasTable = $row.find('table').length > 0;
            $row.toggleClass('direct-settlement-empty-control-row', !hasControls && !hasTable && $.trim($row.text()) === '');
        });
    }

    list_settlement = $('#list_settlement').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        responsive: false,
        stateSave: false,
        deferRender: true,
        pageLength: 25,
        order: [[2, 'desc']],
        ajax: {
            url: '{{route('petrodirect.settlement.index')}}',
            data: function(d) {
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
        },
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, width: '82px' },
            { targets: 1, width: '78px' },
            { targets: 2, width: '92px' },
            { targets: 3, width: '92px' },
            { targets: 4, width: '58px' },
            { targets: 5, width: '118px' },
            { targets: 6, width: '92px' },
            { targets: 7, width: '132px' },
            { targets: 8, width: '78px' },
            { targets: 9, width: '120px' },
            { targets: 10, width: '105px', className: 'text-right' },
            { targets: 11, width: '85px' }
        ],
        columns: columns,
        fnDrawCallback: function(oSettings) {
            restoreDirectSettlementActionMenu();
            organizeDirectSettlementToolbar();

            var $noteButtons = $('#list_settlement .direct-settlement-note-btn');
            if ($.fn.tooltip && $noteButtons.length) {
                $noteButtons.tooltip({
                    container: 'body',
                    placement: 'top',
                    trigger: 'hover focus',
                    html: false,
                    template: '<div class="tooltip direct-settlement-note-tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
                });
            }

            total_amount = 0.00;
            $("#list_settlement tbody tr").each(function(){
                let number = $(this).find("td").eq(-2).text();
                if (number !== 'No data available in table') {
                    total_amount += parseFloat(number.replace(/,/g, ''));
                }
            });
            
            total_amount = total_amount === 0 ? '0.00' : parseFloat(total_amount).toLocaleString(undefined, {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                          useGrouping: true
                        });
            $('#list_settlement tbody tr.footer-total').remove();
            $('#list_settlement tbody').append(
                '<tr class="bg-gray font-17 footer-total text-center">' +
                '<td>Total</td>' +
                '<td></td><td></td><td></td><td></td><td></td><td></td>' +
                '<td></td><td></td><td></td>' +
                '<td>' + total_amount + '</td>' +
                '<td></td>' +
                '</tr>'
            );
        },
    });

    $('#list_settlement')
        .off('shown.bs.dropdown.directSettlementActions')
        .on('shown.bs.dropdown.directSettlementActions', '.btn-group', function () {
            positionDirectSettlementActionMenu($(this));
        })
        .off('hide.bs.dropdown.directSettlementActions')
        .on('hide.bs.dropdown.directSettlementActions', '.btn-group', function () {
            restoreDirectSettlementActionMenu();
        });

    $(document)
        .off('click.directSettlementFloatingMenu', 'body > .direct-settlement-floating-menu a')
        .on('click.directSettlementFloatingMenu', 'body > .direct-settlement-floating-menu a', function () {
            setTimeout(restoreDirectSettlementActionMenu, 0);
        })
        .off('click.directSettlementSidebarAdjust',
            '.sidebar-toggle, [data-toggle="push-menu"], [data-widget="pushmenu"], .main-sidebar button, .main-sidebar a')
        .on('click.directSettlementSidebarAdjust',
            '.sidebar-toggle, [data-toggle="push-menu"], [data-widget="pushmenu"], .main-sidebar button, .main-sidebar a',
            function () {
                setTimeout(adjustDirectSettlementTable, 50);
                setTimeout(adjustDirectSettlementTable, 300);
                setTimeout(adjustDirectSettlementTable, 650);
            });

    $(window)
        .off('resize.directSettlementList')
        .on('resize.directSettlementList', function () {
            restoreDirectSettlementActionMenu();
            adjustDirectSettlementTable();
        });

    if (window.ResizeObserver) {
        var directSettlementResizeTarget = document.querySelector('.content-wrapper')
            || document.querySelector('.main-content-inner');

        if (directSettlementResizeTarget) {
            window.__directSettlementResizeObserver = new ResizeObserver(function () {
                adjustDirectSettlementTable();
            });
            window.__directSettlementResizeObserver.observe(directSettlementResizeTarget);
        }
    }

    organizeDirectSettlementToolbar();
    setTimeout(function () {
        organizeDirectSettlementToolbar();
        adjustDirectSettlementTable();
    }, 0);
    setTimeout(function () {
        organizeDirectSettlementToolbar();
        adjustDirectSettlementTable();
    }, 300);

    $('#location_id, #pump_operator, #pump_operator, #settlement_no, #type, #expense_date_range').change(function(){
        list_settlement.ajax.reload();
    });

    $(document)
        .off('click.directSettlementNote', '.direct-settlement-note-btn')
        .on('click.directSettlementNote', '.direct-settlement-note-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var note = $(this).attr('data-note') || '';
            $('#direct_settlement_note_modal .direct-settlement-note-content').text(note);
            $('#direct_settlement_note_modal').modal('show');
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
                        list_settlement.ajax.reload();
                    },
                });
            }
        });
    });

    $(document).on('click', 'a.delete_reference_button', function(e) {
		var page_details = $(this).closest('div.page_details')
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
                console.log(href);
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            page_details.remove();
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                        list_settlement.ajax.reload();
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


//save settlement
$(document).on('click', '.print_settlement_button', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $button = $(this);
    var url = $button.data('href') || $button.attr('href');

    if (!url || $button.data('printing')) {
        return;
    }

    $button.data('printing', true);

    // Open the window during the user click so browser popup protection does
    // not block the settlement print after the AJAX response returns.
    var printWindow = window.open('', 'Print-Settlement');
    if (printWindow) {
        printWindow.document.open();
        printWindow.document.write('<html><body>Loading settlement print...</body></html>');
        printWindow.document.close();
    }

    $.ajax({
        method: 'get',
        url: url,
        data: {},
        success: function(result) {
            $('#settlement_print').html(result);
            var divToPrint = document.getElementById('settlement_print');

            if (!printWindow) {
                toastr.error('Please allow pop-ups to print the settlement.');
                return;
            }

            printWindow.document.open();
            printWindow.document.write(
                '<html><head><title>Settlement</title></head>' +
                '<body onload="window.print()">' + divToPrint.innerHTML + '</body></html>'
            );
            printWindow.document.close();
            printWindow.focus();
        },
        error: function(xhr) {
            if (printWindow) {
                printWindow.close();
            }

            var message = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Unable to load the settlement print.';
            toastr.error(message);
        },
        complete: function() {
            $button.removeData('printing');
        }
    });
});


// Mechanical Meter uses a page-owned modal loader so global btn-modal handlers cannot block it.
$(document)
    .off('click.settlementMechanicalMeter', 'a.mechanical-meter-settlement-button')
    .on('click.settlementMechanicalMeter', 'a.mechanical-meter-settlement-button', function (e) {
        e.preventDefault();

        var $link = $(this);
        var url = $link.data('href') || $link.attr('href');
        var $modal = $('.settlement_modal').first();

        if (!url || $link.data('loading')) {
            return;
        }

        $link.data('loading', true).attr('aria-disabled', 'true');
        $modal.empty().load(url, function (response, status, xhr) {
            $link.removeData('loading').removeAttr('aria-disabled');

            if (status === 'error') {
                $modal.empty();
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Unable to open Mechanical Meter details.';
                toastr.error(message);
                return;
            }

            $modal.modal('show');
        });
    });

$('#settlement_print').css('visibility', 'hidden');
</script>
@endsection