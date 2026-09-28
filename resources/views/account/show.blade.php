@extends('layouts.app')
@section('title', __('account.account_book'))

@section('content')
    <!-- Content Header (Page header) -->

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('account.account_book')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="{{ action('AccountController@index') }}">@lang('lang_v1.payment_accounts')</a></li>
                        <li><span>@lang('account.account_book')</span></li>
                    </ul>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="pull-right">
                    <button id="realize-cheque-btn" class="btn btn-success" style="margin-right: 10px;">
                        <i class="fa fa-check-circle"></i>
                        @if(($account->name ?? '') === 'Issued Post Dated Cheques')
                            Cheque to Realize from Issued Post Dated Cheques Account
                        @else
                            Cheque to Realize from Post Dated Cheques Account
                        @endif
                    </button>
                    <button id="back-to-main-account" class="btn btn-primary" style="display: none;">
                        <i class="fa fa-arrow-left"></i> Back to Main Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content main-content-inner">

        <div class="row">
            <div class="col-sm-3 col-xs-3">
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
            <div class="col-sm-9 col-xs-9">
                <div class="box box-solid">
                    <div class="box-body">

                        @component('components.filters', ['title' => __('report.filters')])
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('date_based_on', __('account.date_based_on') . ':') !!}
                                        {!! Form::select(
                                            'date_based_on',
                                            ['transaction_date' => __('account.transaction_date'), 'cheque_date' => __('account.cheque_date')],
                                            null,
                                            ['class' => 'form-control select2', 'id' => 'date_based_on'],
                                        ) !!}
                                    </div>
                                </div>

                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('transaction_date_range', __('report.date_range') . ':') !!}
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                            {!! Form::text('transaction_date_range', null, [
                                                'class' => 'form-control',
                                                'id' => 'transaction_date_range',
                                                'readonly',
                                                'placeholder' => __('report.date_range'),
                                            ]) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('transaction_customer', __('report.customer') . ':') !!}
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-exchange"></i></span>
                                            {!! Form::select('transaction_customer', $customers, null, [
                                                'class' => 'form-control select2',
                                                'placeholder' => __('lang_v1.all'),
                                                'id' => 'transaction_customer',
                                            ]) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('transaction_supplier', __('report.supplier') . ':') !!}
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-exchange"></i></span>
                                            {!! Form::select('transaction_supplier', $suppliers, null, [
                                                'class' => 'form-control select2',
                                                'placeholder' => __('lang_v1.all'),
                                                'id' => 'transaction_supplier',
                                            ]) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        {!! Form::label('customer_amount', __('lang_v1.amount') . ':') !!}
                                        <div class="input-group">
                                            <span class="input-group-addon"><i
                                                    class="fa fa-exchange"></i></span><!-- @eng START 13/2 -->
                                            {!! Form::select('customer_amount', [], null, [
                                                'class' => 'form-control select2',
                                                'style' => 'width: 100%;',
                                                'placeholder' => __('lang_v1.all'),
                                                'id' => 'customer_amount',
                                            ]) !!}
                                        </div> <!-- @eng END 13/2 -->
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        {!! Form::label('transaction_type', __('account.transaction_type') . ':') !!}
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-exchange"></i></span>
                                            {!! Form::select(
                                                'transaction_type',
                                                ['' => __('messages.all'), 'debit' => __('account.debit'), 'credit' => __('account.credit')],
                                                '',
                                                ['class' => 'form-control select2'],
                                            ) !!}
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
                                                    'class' => 'form-control select2',
                                                    'placeholder' => __('lang_v1.all'),
                                                ]) !!}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @if ($account->parent_account_id != $card_account_id)
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                {!! Form::label('customer_cheque_no', __('lang_v1.customer_cheque_number') . ':') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon"><i
                                                            class="fa fa-exchange"></i></span><!-- @eng START 13/2 -->
                                                    {!! Form::select('customer_cheque_no', [], null, [
                                                        'class' => 'form-control select2',
                                                        'style' => 'width: 100%;',
                                                        'placeholder' => __('lang_v1.all'),
                                                        'id' => 'customer_cheque_no',
                                                    ]) !!}
                                                </div><!-- @eng END 13/2 -->
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                {!! Form::label('slip_no', __('petro::lang.slip_no') . ':') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon"><i
                                                            class="fa fa-exchange"></i></span><!-- @eng START 13/2 -->
                                                    {!! Form::select('slip_no', $slipNos, null, [
                                                        'class' => 'form-control select2',
                                                        'style' => 'width: 100%;',
                                                        'placeholder' => __('lang_v1.all'),
                                                        'id' => 'slip_no',
                                                    ]) !!}
                                                </div><!-- @eng END 13/2 -->
                                            </div>
                                        </div>
                                    @endif
                                    <!--  -->
                                @endif
                            </div>
                        @endcomponent
                    </div>
                </div>
            </div>
        </div>

        <!-- @eng START 12/2 -->
        <style>
            .the_blue_bg {
                background-color: #E6EBF6;
            }

            .the_purple_bg {
                background-color: #EDE2F6;
            }

            /* Style for deleted expense entries - red font color with strikethrough */
            .deleted-expense-row {
                color: red !important;
                text-decoration: line-through !important;
            }
            .deleted-expense-row td {
                color: red !important;
                text-decoration: line-through !important;
            }
            .deleted-expense-row td * {
                color: red !important;
                text-decoration: line-through !important;
            }
            table#account_book tbody tr.deleted-expense-row td {
                color: red !important;
                text-decoration: line-through !important;
            }
            table#account_book tbody tr.deleted-expense-row td span,
            table#account_book tbody tr.deleted-expense-row td a {
                color: red !important;
                text-decoration: line-through !important;
            }

            /* IS1752: Account Book tables must remain fully accessible on every
               screen size. DataTables owns the inner scroll body; this outer
               wrapper is a safe fallback before and after DataTables initialises. */
            .account-book-table-scroll {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: auto !important;
                overflow-y: visible !important;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 8px;
            }

            #account_book,
            #account_book_wrapper .dataTables_scrollHead table,
            #account_book_wrapper .dataTables_scrollBody table,
            #account_book_wrapper .dataTables_scrollFoot table {
                min-width: 1450px !important;
                width: 100% !important;
                table-layout: auto !important;
            }

            #account_book_wrapper,
            #account_book_wrapper .dataTables_scroll,
            #account_book_wrapper .dataTables_scrollHead,
            #account_book_wrapper .dataTables_scrollBody,
            #account_book_wrapper .dataTables_scrollFoot {
                width: 100% !important;
                max-width: 100% !important;
            }

            #account_book thead th,
            #account_book tbody td,
            #account_book tfoot td {
                vertical-align: middle !important;
            }

            #account_book thead th {
                white-space: nowrap !important;
            }

            #account_book tbody td {
                white-space: nowrap;
            }

            #account_book tbody td:nth-child(4) {
                white-space: normal !important;
                min-width: 260px;
            }

            @if ($id == $card_account_id || $account->parent_account_id == $card_account_id)
                /* IS1752 Card Account Book: reduce data-row height only on card
                   account books; no amount, query or accounting logic changes. */
                #account_book tbody td {
                    padding: 3px 6px !important;
                    line-height: 1.15 !important;
                    font-size: 11px !important;
                }

                #account_book tbody td p,
                #account_book tbody td div,
                #account_book tbody td span {
                    margin-top: 0 !important;
                    margin-bottom: 0 !important;
                    line-height: 1.15 !important;
                }

                #account_book tbody .btn,
                #account_book tbody .dropdown-toggle {
                    min-height: 24px !important;
                    height: 24px !important;
                    padding: 2px 7px !important;
                    line-height: 1.15 !important;
                }
            @endif
        </style>
        <?php
        $creditClassName = '';
        $debitClassName = '';
        $accTypeName = optional($account->account_type)->name;
        if (strpos($accTypeName, 'Assets') !== false || strpos($accTypeName, 'Expenses') !== false) {
            // @eng 13/2
            $debitClassName = 'the_blue_bg';
            $creditClassName = 'the_purple_bg';
        } else {
            $debitClassName = 'the_purple_bg';
            $creditClassName = 'the_blue_bg';
        }
        ?>
        <hr>
        <!-- @eng END 12/2 -->
        <div class="row">
            <div class="col-sm-12">
                <div class="box">
                    <div class="box-body">

                        @can('account.access')
                            <div class="table-responsive account-book-table-scroll">
                                <div id="hiddenDiv"></div>
                                <table class="table table-bordered table-striped" id="account_book">
                                    <thead>
                                        <tr>
                                            <th>@lang('messages.action')</th>
                                            <th>@lang('messages.date')</th>
                                            <th>@lang('lang_v1.transaction_date')</th>
                                            <th>@lang('lang_v1.description')</th>
                                            @if($account->parent_account_id == $card_account_id)
                                                <th>@lang('petro::lang.slip_no')</th>
                                            @endif
                                            <th>@lang('lang_v1.cheque_number')</th>
                                            <th>@lang('lang_v1.pd_cheque_date')</th>
                                            <th>@lang('lang_v1.opening_balance')</th>
                                            <th>@lang('account.debit')</th>
                                            <th>@lang('account.credit')</th>
                                            <th>@lang('lang_v1.remaining_balance')</th>
                                            <!-- added transaction date column by virtual it professional referance docs number 7338 -->

                                            <th>@lang('account.reconcile_status')</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="{{ $account->parent_account_id == $card_account_id ? 8 : 7 }}"><strong>@lang('sale.total'):</strong></td>
                                            <td><span id="footer_debit_total" class="display_currency"
                                                    data-currency_symbol="true"></span></td>
                                            <td><span id="footer_credit_total" class="display_currency"
                                                    data-currency_symbol="true"></span></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
        </div>

        <div class="modal fade" id="noteModal" role="dialog" aria-labelledby="gridSystemModalLabel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">@lang('lang_v1.note')</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <!-- Modal Body -->
                    <div class="modal-body">
                        <p id="noteContent" class="text-center text-bold"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="cancel_cheque_note_modal" tabindex="-1" role="dialog"></div>
        <div class="modal fade at_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade account_model" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

    </section>
    <!-- /.content -->

@endsection
<style>
    .dataTables_empty {
        color: {{ App\System::getProperty('not_enalbed_module_user_color') ?? '#000000' }};
        font-size: {{ App\System::getProperty('not_enalbed_module_user_font_size') ?? '14' }}px;
        text-align: center;
        /* Center-align the text for better aesthetics */
        font-family: Arial, sans-serif;
        /* Ensure a consistent font family */
        line-height: 1.5;
        /* Improve readability with proper line height */
    }
</style>
@section('javascript')
    <script>
        // Modified by Engr. Alex -- task 7889: apply default date range from account settings
        function applyDefaultDateRange(callback) {
            $.ajax({
                method: 'GET',
                url: '/accounting-module/account-settings/default-date-range',
                dataType: 'json',
                success: function(result) {
                    var start = moment().subtract(30, 'days');
                    var end   = moment();
                    if (result.success && result.current && result.current.start_date && result.current.end_date) {
                        start = moment(result.current.start_date);
                        end   = moment(result.current.end_date);
                    }
                    try {
                        var lastDeposit = JSON.parse(localStorage.getItem('account_book_last_deposit') || '{}');
                        var currentAccountId = '{{ $account->id }}';
                        var cardParentAccountId = '{{ $card_account_id }}';
                        var accountIds = (lastDeposit.account_ids || []).map(String);
                        var depositDate = lastDeposit.date ? moment(lastDeposit.date, 'YYYY-MM-DD') : null;
                        var isRecent = lastDeposit.stored_at && moment().diff(moment(lastDeposit.stored_at), 'minutes') <= 60;
                        var matchesAccount = accountIds.indexOf(String(currentAccountId)) !== -1 ||
                            (String(currentAccountId) === String(cardParentAccountId) && lastDeposit.deposit_type === 'card');

                        if (isRecent && matchesAccount && depositDate && depositDate.isValid()) {
                            if (depositDate.isBefore(start, 'day')) {
                                start = depositDate.clone();
                            }
                            if (depositDate.isAfter(end, 'day')) {
                                end = depositDate.clone();
                            }
                        }
                    } catch (e) {}
                    if (typeof callback === 'function') callback(start, end);
                },
                error: function() {
                    if (typeof callback === 'function') callback(moment().subtract(30, 'days'), moment());
                }
            });
        }

        $(document).ready(function() {
            // Check if we came from a main account and show back button
            var mainAccountId = sessionStorage.getItem('main_account_id');
            var mainAccountName = sessionStorage.getItem('main_account_name');

            if (mainAccountId && mainAccountName) {
                $('#back-to-main-account').show().text('Back to ' + mainAccountName);
            }

            // Handle back button click
            $('#back-to-main-account').on('click', function() {
                if (mainAccountId) {
                    // Clear session storage
                    sessionStorage.removeItem('main_account_id');
                    sessionStorage.removeItem('main_account_name');

                    // Navigate back to main account
                    window.location.href = '{{ action('AccountController@show', ['ACCOUNT_ID']) }}'.replace(
                        'ACCOUNT_ID', mainAccountId);
                }
            });

            $(document).on('click', '.note-button', function(event) {
                event.preventDefault(); // Prevent default anchor behavior

                var url = $(this).attr('href'); // Get the href value

                $.ajax({
                    method: 'GET',
                    dataType: 'html',
                    url: url,
                    success: function(response) {

                        $("#cancel_cheque_note_modal").html(response).modal('show');
                    }
                });
            });


            $(document).on('click', '.note_btn', function(e) {
                let note = $(this).data('string');
                $("#noteContent").html(note);
                $("#noteModal").modal('show');

            });


            var body = document.getElementsByTagName("body")[0];
            body.className += " sidebar-collapse";

            update_account_balance();
            // update_description();

            // @eng START 19/2
            $.ajax({
                method: 'get',
                url: '/customer-payment-information/all/cheque_no',
                data: {},
                success: function(result) {
                    $('#customer_cheque_no').populate(result.data);
                },
            });
            $.ajax({
                method: 'get',
                url: '/customer-payment-information/all/amount',
                data: {},
                success: function(result) {
                    $('#customer_amount').populate(result.data);
                },
            });
            // @eng END 19/2

            // Modified by Engr. Alex -- task 7889: use saved default date range instead of hardcoded month
            applyDefaultDateRange(function(defaultStart, defaultEnd) {
                dateRangeSettings.startDate = defaultStart;
                dateRangeSettings.endDate   = defaultEnd;

                $('#transaction_date_range').daterangepicker(
                    dateRangeSettings,
                    function(start, end, label) {
                        if (label === 'Custom Date Range') {
                            // Show the modal for manual input
                            $('.custom_date_typing_modal').modal('show');
                        } else {
                            // Set the selected range and reload
                            $('#transaction_date_range').val(
                                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                            );
                            account_book.order([
                                [2, 'asc']
                            ]);
                            account_book.ajax.reload();
                        }
                    }
                );

                $('#transaction_date_range').val(
                    defaultStart.format(moment_date_format) + ' ~ ' + defaultEnd.format(moment_date_format)
                );
                account_book.ajax.reload();
            });




            // Account Book
            account_book = $('#account_book').DataTable({
                language: {
                    "emptyTable": "@if (!$account_access) {{ App\System::getProperty('not_enalbed_module_user_message') }} @else @lang('account.no_data_available_in_table') @endif"
                },
                processing: true,
                serverSide: false,
                pageLength: 25,
                stateSave: false,
                scrollX: true,
                scrollCollapse: true,
                autoWidth: false,
                responsive: false,
                order: [
                    [2, 'asc']
                ],
                ajax: {
                    url: '{{ route('finance.account.book-data', ['id' => $account->id]) }}',
                    data: function(d) {
                        var start = '';
                        var end = '';
                        if ($('#transaction_date_range').val()) {
                            start = $('input#transaction_date_range').data('daterangepicker').startDate
                                .format('YYYY-MM-DD');
                            end = $('input#transaction_date_range').data('daterangepicker').endDate
                                .format('YYYY-MM-DD');
                        }

                        d.start_date = start;
                        d.end_date = end;
                        d.type = $('#transaction_type').val();
                        d.customer = $('#transaction_customer').val();
                        d.supplier = $('#transaction_supplier').val();
                        d.amount = $('#customer_amount').val();
                        d.customer_cheque_no = $('#customer_cheque_no').val();
                        d.is_iframe = "{{ $is_iframe }}";
                        d.date_based_on = $("#date_based_on").val();



                        if ($('#card_type').length) {
                            d.card_type = $('#card_type').val();
                        }
                        if ($('#cheque_number').length) {
                            d.cheque_number = $('#cheque_number').val();
                        }


                        if ($('#slip_no').length) {
                            d.slip_no = $('#slip_no').val();
                        }
                    },
                    dataSrc: function(json) {
                        var rows = json && Array.isArray(json.data) ? json.data : [];

                        // Defensive schema normalization. Older/legacy account-book
                        // responses did not always include realize_date, which caused
                        // DataTables warning tn/4 and stopped the table from rendering.
                        rows.forEach(function(row) {
                            if (typeof row.realize_date === 'undefined' || row.realize_date === null) {
                                row.realize_date = row.transaction_date || row.operation_date || '';
                            }
                        });

                        return rows;
                    },
                    error: function(xhr, error, thrown) {
                        // Ignore abort errors - these happen when DataTables cancels a previous request
                        // (e.g., when date range changes quickly)
                        if (error === 'abort' || xhr.status === 0) {
                            return;
                        }
                        
                        // Only show error for actual failures
                        console.error('DataTables Ajax Error:', error);
                        console.error('Response:', xhr.responseText);
                        console.error('Status:', xhr.status);
                        alert('Error loading account book data. Please check the console for details.');
                    }
                },



                // added realize date for transaction date value by virtual it professional referance docs number 7338
                aaSorting: [
                    [2, 'asc']
                ],
                "ordering": true,
                "searching": true,
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false
                    },
                    {
                        data: 'operation_date',
                        name: 'operation_date'
                    },
                    {
                        data: 'realize_date',
                        name: 'realize_date',
                        defaultContent: ''
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    @if($account->parent_account_id == $card_account_id)
                    {
                        data: 'slip_no',
                        name: 'slip_no',
                        orderable: false,
                        searchable: true
                    },
                    @endif
                    {
                        data: 'cheque_number',
                        name: 'cheque_number'
                    },
                    {
                        data: 'cheque_date',
                        name: 'cheque_date'
                    },
                    {
                        data: 'opening_balance',
                        name: 'opening_balance'
                    },
                    {
                        data: 'debit',
                        name: 'amount',
                        className: '<?php echo $debitClassName; ?>',
                        render: function(data, type, row) {
                            return `<span class="debit_col" data-deleted="${row.new_deleted_at ?? ''}">${data}</span>`;
                        }
                    },
                    {
                        data: 'credit',
                        name: 'amount',
                        className: '<?php echo $creditClassName; ?>',
                        render: function(data, type, row) {
                            return `<span class="credit_col" data-deleted="${row.new_deleted_at ?? ''}">${data}</span>`;
                        }
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                        searchable: false
                    },
                    {
                        data: 'reconcile_status',
                        name: 'reconcile_status',
                        searchable: false,
                        sortable: false
                    },
                    // Hidden column to ensure stable time-wise ordering within same date
                    {
                        data: 'created_at',
                        name: 'created_at',
                        visible: false,
                        searchable: false
                    },
                    // Hidden column to identify deleted expense entries
                    {
                        data: 'is_deleted_expense',
                        name: 'is_deleted_expense',
                        visible: false,
                        searchable: false
                    }
                ],
                @include('layouts.partials.datatable_export_button') "rowCallback": function(row, data) {
                    // Check if this is a deleted expense entry using the helper column
                    var isDeleted = data.is_deleted_expense === '1' || data.is_deleted_expense === 1 || data.is_deleted_expense === true;
                    if (isDeleted) {
                        $(row).addClass('deleted-expense-row');
                        // Also add inline style as fallback with strikethrough
                        $(row).css({
                            'color': 'red',
                            'text-decoration': 'line-through'
                        });
                        $(row).find('td').css({
                            'color': 'red',
                            'text-decoration': 'line-through'
                        });
                        $(row).find('td *').css({
                            'color': 'red',
                            'text-decoration': 'line-through'
                        });
                    }
                },
                "fnDrawCallback": function(oSettings) {
                    if ($('#footer_debit_total').length) {
                        var debit_total = sum_table_col($('#account_book'), 'debit_col');
                        $('#footer_debit_total').attr('data-orig-value', debit_total).data('orig-value', debit_total).text(debit_total);
                        var credit_total = sum_table_col($('#account_book'), 'credit_col');
                        console.log('hey');
                        console.log(credit_total);
                        $('#footer_credit_total').attr('data-orig-value', credit_total).data('orig-value', credit_total).text(credit_total);
                    }
                    __currency_convert_recursively($('#account_book'));

                    // Re-align the wide header/body/footer after every Ajax draw.
                    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#account_book')) {
                        setTimeout(function () {
                            $('#account_book').DataTable().columns.adjust();
                        }, 0);
                    }
                }
            });

            function sum_table_col(table, class_name) {
                var total = 0;
                table.find('tbody tr').each(function() {
                    if ($(this).hasClass('deleted-expense-row') || $(this).hasClass('deleted-row')) {
                        return;
                    }
                    var val = 0;
                    var element_with_orig = $(this).find('.' + class_name + '[data-orig-value]');
                    if (element_with_orig.length) {
                        val = parseFloat(element_with_orig.attr('data-orig-value')) || 0;
                    } else {
                        var element = $(this).find('.' + class_name);
                        if (element.length) {
                            var clean_text = element.text().replace(/[^0-9.-]/g, '');
                            val = parseFloat(clean_text) || 0;
                        }
                    }
                    total += val;
                });
                return total;
            }

            // $('#transaction_type').change( function(){
            //   account_book.ajax.reload();
            // });
            // $('#transaction_date_range').on('cancel.daterangepicker', function(ev, picker) {
            //   $('#transaction_date_range').val('');
            //   account_book.ajax.reload();
            // });
            $('#date_based_on, #transaction_type, #transaction_customer, #transaction_supplier, #customer_amount, #customer_cheque_no')
                .change(function() {
                    account_book.ajax.reload();
                });

            // Clear date range and reload table
            $('#transaction_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#transaction_date_range').val('');
                account_book.ajax.reload();
            });

            // Reload table when date range changes
            $('#transaction_date_range').on('apply.daterangepicker', function(ev, picker) {
                account_book.ajax.reload();
            });
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
                                // update_description();

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
                url: '{{ action('AccountController@getAccountBalance', [$account->id]) }}',
                dataType: "json",
                success: function(data) {
                    $('span#account_balance').text(__currency_trans_from_en(data.balance, true));
                }
            });
        }




        //select box

        $.fn.populate = function(data, callable = null) {
            $(this).empty()
            $(this).append(`<option value="">All</option>`)
            data.forEach(item => {
                $(this).append(`<option value="${item}">${callable?callable(item):item}</option>`)
            })
        }

        $('#transaction_customer').change(function() {
            if ($('#transaction_customer').val()) {
                $.ajax({
                    method: 'get',
                    url: '/customer-payment-information/' + $(this).val() + '/amount',
                    data: {},
                    success: function(result) {
                        // Add an option for "All"
                        $('#customer_amount').append($('<option>', {
                            value: '',
                            text: 'All'
                        }));

                        // Populate the select element with data
                        $('#customer_amount').populate(result.data, (item) => parseFloat(item).toFixed(
                            2));
                    },
                });

                $.ajax({
                    method: 'get',
                    url: '/customer-payment-information/' + $(this).val() + '/cheque_no',
                    data: {},
                    success: function(result) {
                        $('#customer_cheque_no').populate(result.data);
                    },
                });
            }
            account_book.ajax.reload();
        })
        // @eng START 19/2
        $('#customer_cheque_no').change(function() {

            var param = $("#customer_cheque_no option:selected").text() == 'All' ? 'all' : $(this).val();
            console.log(param, ' is param');

            $.ajax({
                method: 'get',
                url: '/customer-info-for/cheque_no/' + param,
                data: {},
                success: function(result) {
                    // $('#transaction_customer').populate(result.data);
                    var newCustomerOptions = [];

                    newCustomerOptions.push($('<option></option>').attr("value", "").text("All"));

                    for (const [key, value] of Object.entries(result.data)) {
                        newCustomerOptions.push($('<option></option>').attr("value", key).text(value));
                        console.log(`${key}: ${value}`);
                    }
                    $("#transaction_customer").empty().append(newCustomerOptions);

                    var newAmounts = [];
                    newAmounts.push($('<option></option>').attr("value", "").text("All"));
                    for (let i = 0; i < result.amounts.length; i++) {
                        newAmounts.push($('<option></option>').attr('value', result.amounts[i]).text(
                            result.amounts[i]));
                    }
                    $("#customer_amount").empty();
                    $("#customer_amount").append(newAmounts).append($('<option></option>').text('All'));

                    account_book.ajax.reload();
                },
            });
            // }
        })
        $('#custom_date_apply_button').on('click', function() {
            let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
            let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

            if (startDate.length === 10 && endDate.length === 10) {
                let formattedStartDate = moment(startDate).format(moment_date_format);
                let formattedEndDate = moment(endDate).format(moment_date_format);

                $('#transaction_date_range').val(
                    formattedStartDate + ' ~ ' + formattedEndDate
                );
                $("#report_date_range").text("Date Range: " + $("#transaction_date_range").val());

                $('#transaction_date_range').data('daterangepicker').setStartDate(moment(startDate));
                $('#transaction_date_range').data('daterangepicker').setEndDate(moment(endDate));

                $('.custom_date_typing_modal').modal('hide');
                // populateProductCategories();
                account_book.order([
                    [2, 'asc']
                ]);
                account_book.ajax.reload();
            } else {
                alert("Please select both start and end dates.");
            }
        });

        $('#transaction_date_range').on('apply.daterangepicker', function(ev, picker) {
            if (picker.chosenLabel === 'Custom Date Range') {
                $('.custom_date_typing_modal').modal('show');
            } else {
                $(this).val(picker.startDate.format(moment_date_format) + ' ~ ' + picker.endDate.format(
                    moment_date_format));
                account_book.order([
                    [2, 'asc']
                ]);
                account_book.ajax.reload();
            }
        });

        $('#customer_amount').change(function() {
            var param = $("#customer_amount option:selected").text() == 'All' ? 'all' : $(this).val();
            console.log(param, ' is param');

            $.ajax({
                method: 'get',
                url: '/customer-info-for/amount/' + param,
                data: {},
                success: function(result) {
                    var newCustomerOptions = [];

                    newCustomerOptions.push($('<option></option>').attr("value", "").text("All"));
                    for (const [key, value] of Object.entries(result.data)) {
                        newCustomerOptions.push($('<option></option>').attr("value", key).text(value));
                        console.log(`${key}: ${value}`);
                    }
                    $("#transaction_customer").empty().append(newCustomerOptions);

                    var newCheques = [];
                    newCheques.push($('<option></option>').attr("value", "").text("All"));
                    for (let i = 0; i < result.cheques.length; i++) {
                        newCheques.push($('<option></option>').attr('value', result.cheques[i]).text(
                            result.cheques[i]));
                    }
                    $("#customer_cheque_no").empty();
                    $("#customer_cheque_no").append(newCheques).append($('<option></option>').text(
                        'All'));

                    account_book.ajax.reload();
                },
            });
            // }
        })
        // @eng END 19/2

        // Cheque to Realize button handler - IS 867
        $(document).on('click', '#realize-cheque-btn', function(e) {
            e.preventDefault();
            
            var btn = $(this);
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
            
            $.ajax({
                method: 'GET',
                url: '/accounting-module/realize-cheque-deposit',
                dataType: 'html',
                success: function(result) {
                    btn.prop('disabled', false).html(originalHtml);
                    
                    // Use the existing account_model modal container like other modals on this page
                    $('.account_model').html(result).modal('show');
                },
                error: function(xhr, status, error) {
                    btn.prop('disabled', false).html(originalHtml);
                    
                    var errorMsg = 'Failed to load realize cheque form. ';
                    if (xhr.status === 403) {
                        errorMsg += 'Access denied.';
                    } else if (xhr.status === 404) {
                        errorMsg += 'Route not found.';
                    } else if (xhr.status === 500) {
                        errorMsg += 'Server error.';
                    }
                    
                    toastr.error(errorMsg);
                }
            });
        });
    </script>
@endsection
