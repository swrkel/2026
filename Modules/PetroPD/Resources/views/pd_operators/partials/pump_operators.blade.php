<!-- Main content -->
<style>
    #list_pump_operators_table {
        width: 100% !important;
    }

    /* PD Operators Pump Operators table - compact two-line headings (22 Sep 2026). */
    #list_pump_operators_table thead th {
        font-size: calc(1em - 1pt) !important;
        line-height: 1.15 !important;
        vertical-align: middle !important;
    }
    #list_pump_operators_table thead th.petropd-compact-col {
        white-space: normal !important;
        text-align: center !important;
    }
    #list_pump_operators_table th.petropd-w-balance,
    #list_pump_operators_table td.petropd-w-balance { width: 64px !important; max-width: 64px !important; }
    #list_pump_operators_table th.petropd-w-period,
    #list_pump_operators_table td.petropd-w-period { width: 64px !important; max-width: 64px !important; }
    #list_pump_operators_table th.petropd-w-fuel-qty,
    #list_pump_operators_table td.petropd-w-fuel-qty { width: 76px !important; max-width: 76px !important; }
    #list_pump_operators_table th.petropd-w-fuel-sale,
    #list_pump_operators_table td.petropd-w-fuel-sale { width: 76px !important; max-width: 76px !important; }
    #list_pump_operators_table th.petropd-w-commission-type,
    #list_pump_operators_table td.petropd-w-commission-type { width: 72px !important; max-width: 72px !important; }
    #list_pump_operators_table th.petropd-w-commission-rate,
    #list_pump_operators_table td.petropd-w-commission-rate { width: 72px !important; max-width: 72px !important; }
    #list_pump_operators_table th.petropd-w-commission-amount,
    #list_pump_operators_table td.petropd-w-commission-amount { width: 78px !important; max-width: 78px !important; }
    #list_pump_operators_table th.petropd-w-excess,
    #list_pump_operators_table td.petropd-w-excess { width: 68px !important; max-width: 68px !important; }
    #list_pump_operators_table th.petropd-w-short,
    #list_pump_operators_table td.petropd-w-short { width: 68px !important; max-width: 68px !important; }

    /*
    |--------------------------------------------------------------------------
    | PD Operators - prevent monetary values overlapping adjacent columns
    |--------------------------------------------------------------------------
    | Keep the requested compact/two-line headings, but do not cap the body
    | cells to the small heading width. Large values must stay fully inside
    | their own column; the responsive wrapper will scroll horizontally when
    | the screen cannot accommodate the resulting content width.
    */
    #list_pump_operators_table {
        table-layout: auto !important;
        min-width: 100% !important;
    }

    #list_pump_operators_table tbody td.petropd-w-balance,
    #list_pump_operators_table tbody td.petropd-w-period {
        width: auto !important;
        min-width: 118px !important;
        max-width: none !important;
        white-space: nowrap !important;
        text-align: right !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
        box-sizing: border-box !important;
    }

    #list_pump_operators_table tbody td.petropd-w-fuel-sale,
    #list_pump_operators_table tbody td.petropd-w-commission-amount,
    #list_pump_operators_table tbody td.petropd-w-excess,
    #list_pump_operators_table tbody td.petropd-w-short {
        width: auto !important;
        min-width: 100px !important;
        max-width: none !important;
        white-space: nowrap !important;
        text-align: right !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
        box-sizing: border-box !important;
    }

    #list_pump_operators_table tbody td.petropd-w-fuel-qty,
    #list_pump_operators_table tbody td.petropd-w-commission-rate {
        max-width: none !important;
        white-space: nowrap !important;
        text-align: right !important;
        box-sizing: border-box !important;
    }


    /* PDSEP-010 / Global ERP Action Dropdown Emergency Fix
       Prevent DataTables/table wrappers/toolbars from cutting action menus. */
    .erp-action-dropdown-menu {
        z-index: 2147483647 !important;
        display: block !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
        box-shadow: 0 8px 24px rgba(0,0,0,.18) !important;
        border-radius: 8px !important;
    }

    /* S390: do not force all table wrappers to overflow visible because it hides the
       PD Operators right-side columns on smaller screens. Action dropdowns are already
       moved to <body>, so the table itself can scroll horizontally safely. */
    .dataTables_wrapper,
    .box-body,
    .box,
    .content,
    .tab-content,
    .tab-pane {
        overflow: visible !important;
    }
    #list_pump_operators_table_wrapper .dataTables_scrollBody {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }
    .petropd-s390-table-wrap {
        overflow-x: auto !important;
        overflow-y: visible !important;
        width: 100% !important;
    }

</style>
<section class="content">
    <div class="text-right" style="margin-bottom:12px;">
        <a href="{{ route('petropd.pumper-login-attempt-history') }}" class="btn btn-info">
            <i class="fa fa-history"></i> Block / Unblock History
        </a>
    </div>
    @if (!empty($pumperLoginAttempts) && $pumperLoginAttempts->count() > 0)
        <h4 class="box-title text-center">Currently Blocked Pumper Dashboard Login Access</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Company Number</th>
                        <th>Last Entered Passcode</th>
                        <th>Login Attempts</th>
                        <th>Status</th>
                        <th>Last Attempted Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($pumperLoginAttempts as $pumperLoginAttempt)
                        <tr>
                            <td>{{ $pumperLoginAttempt->ip_address }}</td>
                            <td>{{ $pumperLoginAttempt->company_number }}</td>
                            <td>{{ $pumperLoginAttempt->last_entered_passcode }}</td>
                            <td>{{ $pumperLoginAttempt->attempt_count }}</td>
                            <td>{{ $pumperLoginAttempt->status }}</td>
                            <td>{{ \Carbon\Carbon::parse($pumperLoginAttempt->updated_at)->format('Y-m-d H:i:s') }}</td>
                            <td><a href="{{ route('petropd.unblock-pumper-login-attempt', $pumperLoginAttempt->id) }}"
                                    class="btn btn-primary">Unblock</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @component('components.filters', ['title' => __('report.filters'), 'id' => 'pd_operators'])
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, $default_location, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('petropd::lang.pump_operator') . ':') !!}
                        {!! Form::select('pump_operator', $pump_operators, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petropd::lang.all'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('petropd::lang.settlement_no') . ':') !!}
                        {!! Form::select('settlement_no', $settlement_nos, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petropd::lang.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text(
                            'date_range',
                            \Carbon\Carbon::now()->format('Y-m-d') . ' ~ ' . \Carbon\Carbon::now()->format('Y-m-d'),
                            [
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control',
                                'id' => 'expense_date_range',
                                'readonly',
                            ],
                        ) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('type', __('petropd::lang.type') . ':') !!}
                        {!! Form::select(
                            'type',
                            [
                                'commission' => __('petropd::lang.commission'),
                                'excess' => __('petropd::lang.excess'),
                                'shortage' => __('petropd::lang.shortage'),
                            ],
                            null,
                            [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('lang_v1.all'),
                            ],
                        ) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('status', __('petropd::lang.status') . ':') !!}
                        {!! Form::select(
                            'status',
                            ['inactive' => __('petropd::lang.inactive'), 'active' => __('petropd::lang.active')],
                            null,
                            [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'placeholder' => __('lang_v1.all'),
                            ],
                        ) !!}
                    </div>
                </div>
            </div>
            
        </div>
    @endcomponent


    @component('components.widget', [
        'class' => 'box-primary',
        'title' => __('petropd::lang.all_your_list_pump_operators'),
    ])
<div class="row" style="margin-bottom: 25px;">
    <div class="col-md-12 text-right">

        @if(\Illuminate\Support\Facades\Route::has('petropd.pd-operators.create'))
            <a href="{{ route('petropd.pd-operators.create') }}"
               class="btn btn-primary js-petropd-modal"
               data-container=".pump_operator_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')
            </a>
        @else
            <button type="button" class="btn btn-primary" disabled title="PD Operator create route is not available">
                <i class="fa fa-plus"></i> @lang('messages.add')
            </button>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('petropd.pd-operators.import'))
            <a href="{{ route('petropd.pd-operators.import') }}"
               class="btn btn-success">
                <i class="fa fa-download"></i>
                @lang('petropd::lang.import')
            </a>
        @endif

    </div>
</div>
        <div class="table-responsive petropd-s390-table-wrap">
            <table class="table table-bordered table-striped nowrap" id="list_pump_operators_table">
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th class="petropd-compact-col petropd-w-balance">Current<br>Balance</th>
                        <th class="petropd-compact-col petropd-w-period">Period<br>Balance</th>
                        <th>@lang('petropd::lang.pump_operator')</th>
                        <th>@lang('petropd::lang.location')</th>
                        <th class="petropd-compact-col petropd-w-fuel-qty">Sold Fuel Qty<br>Qty</th>
                        <th class="petropd-compact-col petropd-w-fuel-sale">Sale Amount<br>Fuel</th>
                        <th class="petropd-compact-col petropd-w-commission-type">Commission<br>Type</th>
                        <th class="petropd-compact-col petropd-w-commission-rate">Commission<br>Amount</th>
                        <th class="petropd-compact-col petropd-w-commission-amount">Commission<br>Earned</th>
                        <th class="petropd-compact-col petropd-w-excess">Excess<br>Amount</th>
                        <th class="petropd-compact-col petropd-w-short">Short<br>Amount</th>

                    </tr>
                </thead>

                <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="footer_sold_fuel_qty" data-currency_symbol="false"></span>
                        </td>
                        <td><span class="display_currency" id="footer_sale_amount_fuel" data-currency_symbol="true"></span>
                        </td>
                        <td></td>
                        <td></td>
                        <td><span class="display_currency" id="footer_commission_amount" data-currency_symbol="true"></span>
                        </td>
                        <td><span class="display_currency" id="footer_excess_amount" data-currency_symbol="true"></span>
                        </td>
                        <td><span class="display_currency" id="footer_short_amount" data-currency_symbol="true"></span></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent

</section>


<script>
    /**
     * PDSEP-010 / Global ERP Action Dropdown Emergency Fix
     *
     * Problem:
     * Bootstrap dropdowns inside DataTables are clipped by table/toolbars/scroll wrappers,
     * especially for the top rows and bottom rows.
     *
     * Fix:
     * When any table action dropdown opens, temporarily move the menu to <body>,
     * calculate its exact screen position, and return it to its original row when closed.
     * This keeps all menu items visible without changing controllers/routes.
     */
    (function ($) {
        'use strict';

        function restoreErpDropdown($group) {
            var $menu = $group.data('erpActionDropdownMenu');
            var $placeholder = $group.data('erpActionDropdownPlaceholder');

            if ($menu && $menu.length && $placeholder && $placeholder.length) {
                $menu.removeClass('erp-action-dropdown-menu');
                $menu.removeAttr('style');
                $placeholder.replaceWith($menu);
            }

            $group.removeData('erpActionDropdownMenu');
            $group.removeData('erpActionDropdownPlaceholder');
        }

        function positionErpDropdown($group) {
            var $button = $group.find('[data-toggle="dropdown"]').first();
            var $menu = $group.data('erpActionDropdownMenu');

            if (!$button.length || !$menu || !$menu.length) {
                return;
            }

            var buttonOffset = $button.offset();
            var buttonHeight = $button.outerHeight();
            var buttonWidth = $button.outerWidth();
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();
            var scrollTop = $(window).scrollTop();
            var scrollLeft = $(window).scrollLeft();

            var menuWidth = Math.max($menu.outerWidth(), buttonWidth, 220);

            $menu.css({
                position: 'absolute',
                display: 'block',
                visibility: 'hidden',
                width: menuWidth + 'px',
                'z-index': 2147483647
            });

            var menuHeight = $menu.outerHeight();
            var topDown = buttonOffset.top + buttonHeight + 2;
            var topUp = buttonOffset.top - menuHeight - 2;

            var top;
            if ((topDown - scrollTop + menuHeight) > (windowHeight - 10) && topUp > scrollTop) {
                top = topUp;
            } else {
                top = topDown;
            }

            var left = buttonOffset.left;
            if ((left - scrollLeft + menuWidth) > (windowWidth - 10)) {
                left = scrollLeft + windowWidth - menuWidth - 10;
            }
            if (left < scrollLeft + 5) {
                left = scrollLeft + 5;
            }

            $menu.css({
                top: top + 'px',
                left: left + 'px',
                width: menuWidth + 'px',
                visibility: 'visible'
            });
        }

        $(document).on('shown.bs.dropdown', '.table .btn-group, .dataTable .btn-group', function () {
            var $group = $(this);
            var $menu = $group.find('> .dropdown-menu').first();

            if (!$menu.length) {
                return;
            }

            restoreErpDropdown($group);

            var $placeholder = $('<span class="erp-action-dropdown-placeholder" style="display:none"></span>');
            $menu.after($placeholder);

            $group.data('erpActionDropdownMenu', $menu);
            $group.data('erpActionDropdownPlaceholder', $placeholder);

            $('body').append($menu);
            $menu.addClass('erp-action-dropdown-menu');

            positionErpDropdown($group);
        });

        $(document).on('hide.bs.dropdown', '.table .btn-group, .dataTable .btn-group', function () {
            restoreErpDropdown($(this));
        });

        $(window).on('scroll resize', function () {
            $('.open').each(function () {
                var $group = $(this);
                if ($group.data('erpActionDropdownMenu')) {
                    positionErpDropdown($group);
                }
            });
        });
    })(jQuery);
</script>

<!-- /.content -->
