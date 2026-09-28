@extends('layouts.app')
@section('title', __('account.account_book'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('account.account_book')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-sm-4 col-xs-6">
                <div class="box box-solid">
                    <div class="box-body">
                        <table class="table">
                            <tr>
                                <th>@lang('account.account_name'): </th>
                                <td>{{ $account->name }}</td>
                            </tr>
                            <tr>
                                <th>@lang('lang_v1.account_type'):</th>
                                <td>
                                    @if (!empty($account->account_type->parent_account))
                                        {{ $account->account_type->parent_account->name }} -
                                    @endif {{ $account->account_type->name ?? '' }}
                                </td>
                            </tr>
                            <tr>
                                <th>@lang('account.account_number'):</th>
                                <td>{{ $account->account_number }}</td>
                            </tr>
                            <tr>
                                <th>@lang('lang_v1.balance'):</th>
                                <td><span id="account_balance"></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-8 col-xs-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title"> <i class="fa fa-filter" aria-hidden="true"></i> @lang('report.filters'):</h3>
                    </div>
                    <div class="box-body">
                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('transaction_date_range', __('report.date_range') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    {!! Form::text('transaction_date_range', null, [
                                        'class' => 'form-control',
                                        'readonly',
                                        'placeholder' => __('report.date_range'),
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                        @if ($id == $card_account_id)
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('card_type', __('lang_v1.card_type') . ':') !!}
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-exchange"></i></span>
                                        {!! Form::select('card_type', $card_type_accounts, null, [
                                            'class' => 'form-control',
                                            'placeholder' => __('lang_v1.all'),
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('transaction_type', __('account.transaction_type') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-exchange"></i></span>
                                    {!! Form::select(
                                        'transaction_type',
                                        ['' => __('messages.all'), 'debit' => __('account.debit'), 'credit' => __('account.credit')],
                                        '',
                                        ['class' => 'form-control'],
                                    ) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="box">
                    <div class="box-body">
                        @can('account.access')
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="account_book">
                                    <thead>
                                        <tr>
                                            <th>@lang('lang_v1.sub_account_number')</th>
                                            <th>@lang('account.sub_account_name')</th>
                                            <th>@lang('lang_v1.balance')</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                                            <td><span id="footer_total_balance" class="display_currency"
                                                    data-currency_symbol="true"></span></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>


            <div class="modal fade at_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
            </div>
            <div class="modal fade account_model" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
            </div>

    </section>
    <!-- /.content -->

@endsection

@section('javascript')
    <script>
        // Modified by Engr. Alex -- task 7889: apply default date range from account settings
        function applyDefaultDateRange(callback) {
            $.ajax({
                method: 'GET',
                url: '/finance/account-settings/default-date-range',
                dataType: 'json',
                success: function (result) {
                    var start = moment().subtract(6, 'days');
                    var end   = moment();

                    // Use saved start_date and end_date directly
                    if (result.success && result.current && result.current.start_date && result.current.end_date) {
                        start = moment(result.current.start_date);
                        end   = moment(result.current.end_date);
                    }

                    if (typeof callback === 'function') callback(start, end);
                },
                error: function () {
                    if (typeof callback === 'function') callback(moment().subtract(6, 'days'), moment());
                }
            });
        }

        $(document).ready(function() {
            update_account_balance();

            applyDefaultDateRange(function (defaultStart, defaultEnd) {
                dateRangeSettings.startDate = defaultStart;
                dateRangeSettings.endDate   = defaultEnd;

                $('#transaction_date_range').daterangepicker(
                    dateRangeSettings,
                    function(start, end) {
                        $('#transaction_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                            moment_date_format));
                        account_book.ajax.reload();
                    }
                );

                $('#transaction_date_range').val(
                    defaultStart.format(moment_date_format) + ' ~ ' + defaultEnd.format(moment_date_format)
                );

                account_book.ajax.reload();
            });

            $(document)
                .off('click.financeReconcile', 'button.finance_reconcile_status_btn')
                .on('click.financeReconcile', 'button.finance_reconcile_status_btn', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    var $button = $(this);
                    if ($button.data('finance-reconcile-busy')) {
                        return;
                    }

                    var href = $button.data('href');
                    var targetStatus = parseInt($button.data('target-status'), 10) === 0 ? 0 : 1;
                    $button.data('finance-reconcile-busy', true).prop('disabled', true);

                    $.ajax({
                        method: 'get',
                        url: href,
                        dataType: 'json',
                        cache: false,
                        data: { target_status: targetStatus },
                        success: function(result) {
                            if (result && result.success === true && parseInt(result.status, 10) === targetStatus) {
                                toastr.success(result.msg || 'Reconciliation status saved successfully.');
                                account_book.ajax.reload(null, false);
                                return;
                            }

                            var reason = (result && (result.reason || result.msg))
                                ? (result.reason || result.msg)
                                : 'Finance could not change the reconciliation status.';
                            var steps = result && Array.isArray(result.steps) ? result.steps : [];
                            toastr.error(reason + (steps.length ? ' Steps: ' + steps.join(' ') : ''));
                        },
                        error: function(xhr) {
                            var result = xhr && xhr.responseJSON ? xhr.responseJSON : {};
                            var reason = result.reason || result.msg || 'The server could not save the reconciliation status.';
                            var steps = Array.isArray(result.steps) ? result.steps : [];
                            toastr.error(reason + (steps.length ? ' Steps: ' + steps.join(' ') : ''));
                        },
                        complete: function() {
                            $button.data('finance-reconcile-busy', false).prop('disabled', false);
                        }
                    });
                });


            // Account Book


            account_book = $('#account_book').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('finance.list-accounts.live.main_account_book.data', ['id' => $account->id], false) }}',
                    data: function(d) {
                        var start = '';
                        var end = '';
                        if ($('#transaction_date_range').val()) {
                            start = $('input#transaction_date_range').data('daterangepicker').startDate
                                .format('YYYY-MM-DD');
                            end = $('input#transaction_date_range').data('daterangepicker').endDate
                                .format('YYYY-MM-DD');
                        }
                        var transaction_type = $('select#transaction_type').val();
                        d.start_date = start;
                        d.end_date = end;
                        d.type = transaction_type;
                    }
                },
                ordering: true,
                searching: true,
                columns: [{
                        data: 'account_number',
                        name: 'account_number'
                    },
                    {
                        data: 'name',
                        name: 'name',
                        render: function(data, type, row) {
                            return '<a href="' +
                                @json(route('finance.list-accounts.live.account_book.show', ['id' => 'ACCOUNT_ID'], false)).replace(
                                    'ACCOUNT_ID', row.id) +
                                '" class="account-link" data-account-id="' + row.id + '">' + data +
                                '</a>';
                        }
                    },
                    {
                        data: 'balance',
                        name: 'balance'
                    },
                ],
                order: [
                    [0, "desc"],
                    [1, "desc"],
                    [2, "asc"],
                    [3, "asc"]
                ], // default sorting
                @include('layouts.partials.datatable_export_button')
                fnDrawCallback: function(oSettings) {
                    var total = sum_table_col($('#account_book'), 'balance');
                    $('#footer_total_balance').text(total);
                    __currency_convert_recursively($('#account_book'));
                }
            });




            $('#transaction_type').change(function() {
                account_book.ajax.reload();
            });
            $('#transaction_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#transaction_date_range').val('');
                account_book.ajax.reload();
            });

        });

        $('#card_type').change(function() {
            account_book.ajax.reload();
        })

        // Handle click on individual account links
        $(document).on('click', '.account-link', function(e) {
            e.preventDefault();
            var accountId = $(this).data('account-id');
            var accountName = $(this).text();
            var mainAccountId = {{ $account->id }};

            // Store the main account ID in session storage for navigation back
            sessionStorage.setItem('main_account_id', mainAccountId);
            sessionStorage.setItem('main_account_name', '{{ $account->name }}');

            // Open the individual account ledger
            window.location.href = @json(route('finance.list-accounts.live.account_book.show', ['id' => 'ACCOUNT_ID'], false)).replace('ACCOUNT_ID',
                accountId);
        });
        $(document).on('click', 'a.delete_account_transaction', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    $.ajax({
                        url: href,
                        method: 'DELETE',
                        dataType: "json",
                        success: function(result) {
                            if (result.success === true) {
                                toastr.success(result.msg);
                                account_book.ajax.reload();
                                update_account_balance();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        function update_account_balance(argument) {
            $('span#account_balance').html('<i class="fa fa-refresh fa-spin"></i>');
            $.ajax({
                url: '{{ url('/finance/main-account-balance/' . $account->id) }}',
                dataType: "json",
                success: function(data) {
                    console.log(data);
                    $('span#account_balance').text(__currency_trans_from_en(data.balance, true));
                }
            });
        }
    </script>
@endsection
