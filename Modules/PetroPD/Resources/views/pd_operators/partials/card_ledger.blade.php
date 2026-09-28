{{--
    8035 - PD Card Ledger.

    Card takings are collected by the operator but the money arrives later from
    the bank. Until now there was nowhere to record which of those payments had
    actually landed, so a card recorded weeks ago looked exactly like one
    received yesterday.

    This tab lists every card slip with a Payment Received status and totals
    what is still outstanding.

    Once a card is marked received it cannot be changed back without the
    petro_pd.card_ledger_override permission - a received payment is a
    financial assertion, and being able to quietly reverse it would defeat the
    purpose of keeping the record.
--}}

<div class="row">
    <div class="col-md-12">

        {{-- The four summary cards --}}
        <div class="row" id="pd_card_ledger_summary">
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <div class="info-box-content">
                        <span class="info-box-text">Date Range</span>
                        <span class="info-box-number" id="pd_cl_range" style="font-size:15px;">—</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <div class="info-box-content">
                        <span class="info-box-text">Total Card Sales</span>
                        <span class="info-box-number" id="pd_cl_total">0.00</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <div class="info-box-content">
                        <span class="info-box-text">Total Received Payments</span>
                        <span class="info-box-number" id="pd_cl_received" style="color:#00a65a;">0.00</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <div class="info-box-content">
                        <span class="info-box-text">Total Pending Payments</span>
                        <span class="info-box-number" id="pd_cl_pending" style="color:#dd4b39;">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="box box-default">
            <div class="box-body">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('pd_cl_date_range', 'Date Range:') !!}
                        {!! Form::text('pd_cl_date_range', null, [
                            'class'       => 'form-control',
                            'id'          => 'pd_cl_date_range',
                            'readonly',
                            'placeholder' => 'Select a date range',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('pd_cl_operator', 'Operator:') !!}
                        {!! Form::select('pd_cl_operator', $pump_operators ?? [], null, [
                            'class'       => 'form-control select2',
                            'id'          => 'pd_cl_operator',
                            'placeholder' => 'All',
                            'style'       => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('pd_cl_shift', 'Shift Number:') !!}
                        {!! Form::text('pd_cl_shift', null, [
                            'class'       => 'form-control',
                            'id'          => 'pd_cl_shift',
                            'placeholder' => 'All',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('pd_cl_slip', 'Slip Number:') !!}
                        {!! Form::text('pd_cl_slip', null, [
                            'class'       => 'form-control',
                            'id'          => 'pd_cl_slip',
                            'placeholder' => 'All',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('pd_cl_received', 'Payment Received:') !!}
                        <select class="form-control" id="pd_cl_received" style="width:100%">
                            <option value="">All</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-block" id="pd_cl_apply">
                            <i class="fa fa-filter"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- The ledger --}}
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="pd_card_ledger_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Operator</th>
                        <th>Shift Number</th>
                        <th>Slip Number</th>
                        <th class="text-right">Amount</th>
                        <th>Payment Received</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

<script>
$(function () {

    var ledgerTable = null;

    function filters() {
        var range = $('#pd_cl_date_range').val() || '';
        var start = '';
        var end   = '';

        if (range.indexOf('~') !== -1) {
            var parts = range.split('~');
            start = $.trim(parts[0]);
            end   = $.trim(parts[1]);
        }

        return {
            start_date:       start,
            end_date:         end,
            pump_operator_id: $('#pd_cl_operator').val() || '',
            shift_id:         $('#pd_cl_shift').val() || '',
            slip_no:          $('#pd_cl_slip').val() || '',
            payment_received: $('#pd_cl_received').val()
        };
    }

    function loadTotals() {
        /*
         | The totals come from the SERVER, over the same filters as the table.
         |
         | Adding up the visible rows would be wrong the moment the table is
         | paged - the operator would see the totals for one page rather than
         | for the range they asked about.
         */
        var data = filters();
        data.totals = 1;

        $.ajax({
            method: 'GET',
            url: '/petropd/card-ledger',
            data: data,
            success: function (result) {
                if (!result.success) return;

                function money(v) {
                    return parseFloat(v || 0).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                $('#pd_cl_range').text(result.date_range || '—');
                $('#pd_cl_total').text(money(result.total));
                $('#pd_cl_received').text(money(result.received));
                $('#pd_cl_pending').text(money(result.pending));
            }
        });
    }

    function initLedger() {
        if (ledgerTable) {
            ledgerTable.ajax.reload();
            loadTotals();
            return;
        }

        ledgerTable = $('#pd_card_ledger_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/petropd/card-ledger',
                data: function (d) {
                    $.extend(d, filters());
                }
            },
            columns: [
                { data: 'action',           name: 'action', orderable: false, searchable: false },
                { data: 'date',             name: 'daily_cards.created_at' },
                { data: 'time',             name: 'time', orderable: false, searchable: false },
                { data: 'operator_name',    name: 'pump_operators.name' },
                { data: 'shift_no',         name: 'petro_shifts.shift_no' },
                { data: 'slip_no',          name: 'daily_cards.slip_no' },
                { data: 'amount',           name: 'daily_cards.amount', className: 'text-right' },
                { data: 'payment_received', name: 'daily_cards.payment_received' }
            ],
            order: [[1, 'desc']],
            drawCallback: function () {
                if (typeof __currency_convert_recursively === 'function') {
                    __currency_convert_recursively($('#pd_card_ledger_table'));
                }
            }
        });

        loadTotals();
    }

    // The tab is only built when it is first opened - a server-side table that
    // nobody has looked at should not be querying the database.
    $(document).on('shown.bs.tab', 'a[href="#card_ledger"]', function () {
        initLedger();
    });

    $(document).off('click.pdClApply').on('click.pdClApply', '#pd_cl_apply', function () {
        if (ledgerTable) {
            ledgerTable.ajax.reload();
        } else {
            initLedger();
        }

        loadTotals();
    });

    // Date range - defaults to this week, as specified.
    if ($.fn.daterangepicker) {
        $('#pd_cl_date_range').daterangepicker({
            autoUpdateInput: false,
            startDate: moment().startOf('week'),
            endDate: moment().endOf('week'),
            locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' }
        }, function (start, end) {
            $('#pd_cl_date_range').val(start.format('YYYY-MM-DD') + ' ~ ' + end.format('YYYY-MM-DD'));
        });

        $('#pd_cl_date_range').on('cancel.daterangepicker', function () {
            $(this).val('');
        });

        $('#pd_cl_date_range').val(
            moment().startOf('week').format('YYYY-MM-DD') + ' ~ ' +
            moment().endOf('week').format('YYYY-MM-DD')
        );
    }

    // ---- Change the status ------------------------------------------------
    $(document).off('click.pdClEdit').on('click.pdClEdit', '.pd-card-ledger-edit', function () {
        var $btn     = $(this);
        var id       = $btn.data('id');
        var received = parseInt($btn.data('received'), 10) || 0;

        var next = received === 1 ? 0 : 1;
        var word = next === 1 ? 'received' : 'NOT received';

        if (!window.confirm('Mark this payment as ' + word + '?')) return;

        $.ajax({
            method: 'POST',
            url: '/petropd/card-ledger/' + id,
            data: {
                _method: 'PUT',
                _token: $('meta[name="csrf-token"]').attr('content'),
                payment_received: next
            },
            success: function (result) {
                if (!result.success) {
                    toastr.error(result.msg || 'Could not update the status.');
                    return;
                }

                toastr.success(result.msg);

                if (ledgerTable) ledgerTable.ajax.reload(null, false);
                loadTotals();
            },
            error: function () {
                toastr.error('Could not update the status.');
            }
        });
    });

});
</script>
