@extends('layouts.app')
@section('title', __('petro::lang.list_settlement'))

@section('content')

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
    #list_settlement thead th:nth-child(11),
    #list_settlement tfoot th:nth-child(11) {
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

    #list_settlement tfoot th {
        background: #f2f2f2;
        font-weight: 700;
    }

    /* IS1782: render the selected row action menu at the button position,
       outside the DataTable scroll container, so it can never fall to the
       bottom of the page or be clipped by horizontal scrolling. */
    #list_settlement .direct-settlement-action-group {
        position: relative;
    }

    body > .direct-settlement-floating-menu {
        position: fixed !important;
        display: block !important;
        visibility: visible;
        opacity: 1;
        float: none !important;
        min-width: 190px;
        max-width: min(280px, calc(100vw - 16px));
        margin: 0 !important;
        z-index: 10050 !important;
        max-height: calc(100vh - 20px);
        overflow-x: hidden;
        overflow-y: auto;
        transform: none !important;
    }

    body > .direct-settlement-floating-menu > li > a {
        display: block;
        width: 100%;
        padding: 7px 14px;
        text-align: left;
        white-space: nowrap;
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
                    <li><a href="#">@lang('petro::lang.petro')</a></li>
                    <li><span>@lang( 'petro::lang.mange_list_settlement') </span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner">
    @if(!empty($message)) {!! $message !!} @endif
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petro::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('petro::lang.pump_operator').':') !!}
                        {!! Form::select('pump_operator', $pump_operators, null, ['class' => 'form-control select2', 'placeholder' => __('petro::lang.all')]); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('petro::lang.settlement_number').':') !!}
                        {!! Form::select('settlement_no', $settlement_nos, null, ['class' => 'form-control select2', 'placeholder' => __('petro::lang.all')]); !!}
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

    @component('components.widget', ['class' => 'box-primary'])
    @slot('tool')
    <div class="box-tools pull-right ">
            <a class="btn  btn-primary" href="{{action('\Modules\Petro\Http\Controllers\SettlementController@create')}}">
                <i class="fa fa-plus"></i> @lang('messages.add')</a>
    </div>
    @endslot
    <div class="direct-settlement-list-wrap">
        <table class="table table-bordered table-striped" id="list_settlement" style="width:100%">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petro::lang.status')</th>
                    <th>@lang('petro::lang.settlement_date')</th>
                    <th>@lang('petro::lang.settlement_no')</th>
                    <th>@lang('petro::lang.shift_number')</th>
                    <th>@lang('petro::lang.pump_operator_name')</th>
                    <th>@lang('petro::lang.pumps')</th>
                    <th>@lang('petro::lang.location')</th>
                    <th>@lang('petro::lang.shift')</th>
                    <th>@lang('petro::lang.note')</th> 
                    <th>@lang('petro::lang.total_amnt')</th>
                    <th>@lang('petro::lang.added_user')</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray footer-total">
                    <th>@lang('petro::lang.total')</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="list-settlement-page-total text-right">0.00</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
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
            { data: 'shift_number', name: 'pump_operator_assignments.shift_number' },
            { data: 'pump_operator_name', name: 'pump_operators.name' },
            { data: 'pump_nos', name: 'pump_nos', searchable: false },
            { data: 'location_name', name: 'business_locations.name' },
            { data: 'shift', name: 'shift', searchable: false},
            { data: 'note', name: 'note' },
            { data: 'total_amount', name: 'total_amount' },
            { data: 'created_by',searchable: false, name: 'created_by' }
        ];
  
    var directSettlementMenuState = {
        owner: null,
        button: null,
        menu: null
    };

    function closeDirectSettlementActionMenu() {
        var state = directSettlementMenuState;
        if (!state.menu || !state.menu.length) {
            state.owner = null;
            state.button = null;
            state.menu = null;
            return;
        }

        state.menu
            .removeClass('direct-settlement-floating-menu')
            .removeAttr('style');

        if (state.owner && state.owner.length && $.contains(document, state.owner.get(0))) {
            state.menu.appendTo(state.owner);
            state.owner.removeClass('open direct-settlement-menu-open');
        } else {
            // DataTables may redraw while a menu is open. Do not leave an
            // orphaned menu attached at the bottom of document.body.
            state.menu.remove();
        }

        if (state.button && state.button.length) {
            state.button.attr('aria-expanded', 'false');
        }

        state.owner = null;
        state.button = null;
        state.menu = null;
    }

    function positionDirectSettlementActionMenu() {
        var state = directSettlementMenuState;
        if (!state.button || !state.button.length || !state.menu || !state.menu.length) {
            return;
        }

        var buttonElement = state.button.get(0);
        if (!buttonElement || !$.contains(document, buttonElement)) {
            closeDirectSettlementActionMenu();
            return;
        }

        var rect = buttonElement.getBoundingClientRect();
        var viewportPadding = 8;

        state.menu.css({
            top: '0px',
            left: '0px',
            right: 'auto',
            visibility: 'hidden'
        });

        var menuWidth = Math.max(state.menu.outerWidth() || 190, 190);
        var menuHeight = Math.max(state.menu.outerHeight() || 1, 1);
        var left = rect.left;
        var top = rect.bottom + 3;

        if (left + menuWidth > window.innerWidth - viewportPadding) {
            left = rect.right - menuWidth;
        }
        left = Math.max(viewportPadding, Math.min(left, window.innerWidth - menuWidth - viewportPadding));

        if (top + menuHeight > window.innerHeight - viewportPadding) {
            var aboveTop = rect.top - menuHeight - 3;
            top = aboveTop >= viewportPadding
                ? aboveTop
                : Math.max(viewportPadding, window.innerHeight - menuHeight - viewportPadding);
        }

        state.menu.css({
            top: Math.round(top) + 'px',
            left: Math.round(left) + 'px',
            right: 'auto',
            visibility: 'visible'
        });
    }

    function openDirectSettlementActionMenu($button) {
        var $owner = $button.closest('.direct-settlement-action-group');
        var $menu = $owner.children('.direct-settlement-action-menu').first();

        if (!$owner.length || !$menu.length) {
            return;
        }

        if (
            directSettlementMenuState.button
            && directSettlementMenuState.button.get(0) === $button.get(0)
        ) {
            closeDirectSettlementActionMenu();
            return;
        }

        closeDirectSettlementActionMenu();

        directSettlementMenuState.owner = $owner;
        directSettlementMenuState.button = $button;
        directSettlementMenuState.menu = $menu;

        $owner.addClass('open direct-settlement-menu-open');
        $button.attr('aria-expanded', 'true');
        $menu
            .appendTo(document.body)
            .addClass('direct-settlement-floating-menu');

        positionDirectSettlementActionMenu();
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

    function directSettlementNumericValue(value) {
        var text = $('<div>').html(value == null ? '' : value).text();
        var parsed = parseFloat(String(text).replace(/[^0-9.\-]/g, ''));

        return isFinite(parsed) ? parsed : 0;
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
            url: '{{action('\Modules\Petro\Http\Controllers\SettlementController@index')}}',
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
        drawCallback: function() {
            closeDirectSettlementActionMenu();

            var api = this.api();
            var pageTotal = 0;

            api.column(10, { page: 'current' }).data().each(function(value) {
                pageTotal += directSettlementNumericValue(value);
            });

            $(api.column(10).footer()).html(
                pageTotal.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                    useGrouping: true
                })
            );

            adjustDirectSettlementTable();
        },
    });

    $(document)
        .off('click.is1782DirectSettlementAction', '#list_settlement .direct-settlement-action-toggle')
        .on('click.is1782DirectSettlementAction', '#list_settlement .direct-settlement-action-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openDirectSettlementActionMenu($(this));
        })
        .off('click.is1782DirectSettlementMenu', 'body > .direct-settlement-floating-menu')
        .on('click.is1782DirectSettlementMenu', 'body > .direct-settlement-floating-menu', function (event) {
            event.stopPropagation();
        })
        .off('click.is1782DirectSettlementMenuLink', 'body > .direct-settlement-floating-menu a')
        .on('click.is1782DirectSettlementMenuLink', 'body > .direct-settlement-floating-menu a', function () {
            setTimeout(closeDirectSettlementActionMenu, 0);
        })
        .off('click.is1782DirectSettlementOutside')
        .on('click.is1782DirectSettlementOutside', function (event) {
            if ($(event.target).closest('.direct-settlement-action-toggle, body > .direct-settlement-floating-menu').length) {
                return;
            }
            closeDirectSettlementActionMenu();
        })
        .off('keydown.is1782DirectSettlementActions')
        .on('keydown.is1782DirectSettlementActions', function (event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
                closeDirectSettlementActionMenu();
            }
        })
        .off(
            'click.directSettlementSidebarAdjust',
            '.sidebar-toggle, [data-toggle="push-menu"], [data-widget="pushmenu"], .main-sidebar button, .main-sidebar a'
        )
        .on(
            'click.directSettlementSidebarAdjust',
            '.sidebar-toggle, [data-toggle="push-menu"], [data-widget="pushmenu"], .main-sidebar button, .main-sidebar a',
            function () {
                closeDirectSettlementActionMenu();
                setTimeout(adjustDirectSettlementTable, 50);
                setTimeout(adjustDirectSettlementTable, 300);
                setTimeout(adjustDirectSettlementTable, 650);
            }
        );

    $('.direct-settlement-list-wrap')
        .off('scroll.is1782DirectSettlementActions')
        .on('scroll.is1782DirectSettlementActions', closeDirectSettlementActionMenu);

    $(window)
        .off('resize.directSettlementList scroll.is1782DirectSettlementActions')
        .on('resize.directSettlementList', function () {
            closeDirectSettlementActionMenu();
            adjustDirectSettlementTable();
        })
        .on('scroll.is1782DirectSettlementActions', closeDirectSettlementActionMenu);

    if (window.ResizeObserver) {
        var directSettlementResizeTarget = document.querySelector('.content-wrapper')
            || document.querySelector('.main-content-inner');

        if (directSettlementResizeTarget) {
            window.__petroDirectSettlementResizeObserver = new ResizeObserver(function () {
                adjustDirectSettlementTable();
            });
            window.__petroDirectSettlementResizeObserver.observe(directSettlementResizeTarget);
        }
    }

    setTimeout(adjustDirectSettlementTable, 0);
    setTimeout(adjustDirectSettlementTable, 300);

    $('#location_id, #pump_operator, #pump_operator, #settlement_no, #type, #expense_date_range').change(function(){
        list_settlement.ajax.reload();
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


/**
 * List Direct Settlement actions are handled locally on this page.
 * This avoids depending on a global .btn-modal binding and also gives both
 * actions a normal href fallback when JavaScript is unavailable.
 */
$(document)
    .off('click.petroSettlementView', 'a.view_settlement_button')
    .on('click.petroSettlementView', 'a.view_settlement_button', function (e) {
        e.preventDefault();

        var $button = $(this);
        var url = $button.data('href') || $button.attr('href');
        var $modal = $('.settlement_modal');

        if (!url || $button.data('loading')) {
            return;
        }

        $button.data('loading', true);
        $modal.empty();

        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html'
        }).done(function (result) {
            $modal.html(result).modal('show');
        }).fail(function (xhr) {
            var message = xhr.status === 404
                ? 'The selected settlement could not be found.'
                : 'Unable to open the settlement. Please try again.';
            toastr.error(message);
        }).always(function () {
            $button.removeData('loading');
        });
    });

$(document)
    .off('click.petroSettlementPrint', 'a.print_settlement_button')
    .on('click.petroSettlementPrint', 'a.print_settlement_button', function (e) {
        e.preventDefault();

        var $button = $(this);
        var url = $button.data('href') || $button.attr('href');

        if (!url || $button.data('loading')) {
            return;
        }

        // Open the print window during the user click so popup blockers do not
        // reject it after the AJAX request has completed.
        var printWindow = window.open('', 'Petro-Settlement-Print');
        if (!printWindow) {
            toastr.error('Please allow pop-ups to print the settlement.');
            return;
        }

        $button.data('loading', true);
        printWindow.document.open();
        printWindow.document.write('<html><body><p style="font-family:Arial,sans-serif;padding:20px;">Loading settlement...</p></body></html>');
        printWindow.document.close();

        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html'
        }).done(function (result) {
            $('#settlement_print').html(result);

            printWindow.document.open();
            printWindow.document.write(
                '<html><head><title>Settlement</title></head>' +
                '<body onload="setTimeout(function(){window.focus();window.print();},300)">' +
                result +
                '</body></html>'
            );
            printWindow.document.close();
        }).fail(function (xhr) {
            printWindow.close();
            var message = xhr.status === 404
                ? 'The selected settlement could not be found.'
                : 'Unable to prepare the settlement for printing. Please try again.';
            toastr.error(message);
        }).always(function () {
            $button.removeData('loading');
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