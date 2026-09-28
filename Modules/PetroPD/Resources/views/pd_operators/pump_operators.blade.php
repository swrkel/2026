<!-- Main content -->
<style>
    /* ZIP 313 - Force Add Pump Operator modal to more compact professional width. */
    .view_modal .modal-dialog.petropd-operator-dialog,
    .modal .modal-dialog.petropd-operator-dialog {
        width: 620px !important;
        max-width: 620px !important;
        margin: 30px auto !important;
    }
    .view_modal .petropd-operator-modal-content {
        max-width: 620px !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }
    @media (max-width: 767px) {
        .view_modal .modal-dialog.petropd-operator-dialog,
        .modal .modal-dialog.petropd-operator-dialog {
            width: 96vw !important;
            max-width: 96vw !important;
            margin: 10px auto !important;
        }
    }

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

    /* ZIP 309 - PetroPD Pump Operators page modal/action stability */
    .petropd-pump-operator-tools .btn {
        border-radius: 8px !important;
        font-weight: 700 !important;
        margin-left: 6px !important;
    }
    .petropd-pump-operator-tools .btn i {
        margin-right: 4px !important;
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
        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right petropd-pump-operator-tools">
                    <button type="button"
                            class="btn btn-primary btn-modal petropd-open-pump-operator-modal"
                            data-href="{{ route('petropd.pd-operators.create') }}"
                            data-container=".view_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </button>

                    <button type="button"
                            class="btn btn-default btn-modal petropd-open-pump-operator-modal"
                            data-href="{{ route('petropd.pd-operators.import') }}"
                            data-container=".view_modal">
                        <i class="fa fa-download"></i> @lang('petropd::lang.import')
                    </button>
                </div>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="list_pump_operators_table">
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
                        <td colspan="4"><strong>@lang('sale.total'):</strong></td>
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
<!-- /.content -->

<script type="text/javascript">
    /* ZIP 309 - PetroPD standalone Pump Operator Add/Edit modal stability. */
    (function($) {
        function getModal(container) {
            var $modal = $(container || '.view_modal');
            if (!$modal.length) {
                $('body').append('<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>');
                $modal = $('.view_modal').last();
            }
            return $modal;
        }

        $(document).off('click.petropdPumpOperatorModal', '.petropd-open-pump-operator-modal, .js-petropd-modal')
            .on('click.petropdPumpOperatorModal', '.petropd-open-pump-operator-modal, .js-petropd-modal', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $button = $(this);
                var href = $button.data('href') || $button.attr('href');
                var container = $button.data('container') || '.view_modal';
                var $modal = getModal(container);

                if (!href || href === '#') {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Form URL is missing.');
                    }
                    return false;
                }

                $button.prop('disabled', true).addClass('disabled');
                $modal.html('<div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-body text-center" style="padding:35px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br>Loading...</div></div></div>');
                $modal.modal({backdrop: true, keyboard: true, show: true});

                $.ajax({
                    url: href,
                    method: 'GET',
                    dataType: 'html',
                    cache: false,
                    success: function(result) {
                        $modal.html(result);
                        $modal.modal('show');
                    },
                    error: function(xhr) {
                        $modal.modal('hide');
                        var msg = 'Unable to open Pump Operator form.';
                        if (xhr.status === 403) {
                            msg = 'Unauthorized action. Please check user role permissions.';
                        } else if (xhr.status === 404) {
                            msg = 'Pump Operator form route was not found.';
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.error(msg);
                        } else {
                            alert(msg);
                        }
                    },
                    complete: function() {
                        $button.prop('disabled', false).removeClass('disabled');
                    }
                });

                return false;
            });

        $(document).off('click.petropdPumpOperatorClose', '.petropd-pump-operator-close, .petropd-operator-close-btn, .petropd-operator-footer [data-dismiss="modal"], .petropd-operator-header [data-dismiss="modal"]')
            .on('click.petropdPumpOperatorClose', '.petropd-pump-operator-close, .petropd-operator-close-btn, .petropd-operator-footer [data-dismiss="modal"], .petropd-operator-header [data-dismiss="modal"]', function(e) {
                e.preventDefault();
                var $modal = $(this).closest('.modal');
                if (!$modal.length) {
                    $modal = $('.view_modal:visible, .modal:visible').first();
                }
                if ($modal.length && $.fn.modal) {
                    $modal.modal('hide');
                }
                setTimeout(function() {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }, 150);
                return false;
            });
    })(jQuery);
</script>
