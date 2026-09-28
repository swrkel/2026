@extends('layouts.app')
@section('title', __('account.account_book'))

@section('content')
    @php
        // IS2248 #3: keep the Finance Account Book view safe even when an older
        // cached/compatibility route reaches it without the newer date variables.
        // The authoritative controller supplies these values; this is a final
        // view-level guard so an Account Book can never fail with an undefined
        // variable HTTP 500.
        $financeAccountBookStartDate = !empty($account_book_start_date ?? null)
            ? $account_book_start_date
            : now()->startOfMonth()->format('Y-m-d');
        $financeAccountBookEndDate = !empty($account_book_end_date ?? null)
            ? $account_book_end_date
            : now()->endOfMonth()->format('Y-m-d');
    @endphp
    <!-- Content Header (Page header) -->

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('account.account_book')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="{{ route('finance.list-accounts.live') }}">@lang('lang_v1.payment_accounts')</a></li>
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
    <section class="content main-content-inner account-book-page">

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


            /* Account Book: keep every visible column inside the page width. */
            html, body, .account-book-page {
                scroll-behavior: auto !important;
                overflow-anchor: none !important;
            }

            .account-book-page,
            .account-book-page > .row,
            .account-book-page [class*="col-"],
            .account-book-page .box,
            .account-book-page .box-body,
            .account-book-page #account_book_wrapper {
                min-width: 0 !important;
                max-width: 100% !important;
            }

            .account-book-table-scroll {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: hidden !important;
                overflow-y: visible !important;
                padding-bottom: 4px;
            }

            #account_book,
            #account_book_wrapper table,
            #account_book_wrapper .dataTables_scrollHead table,
            #account_book_wrapper .dataTables_scrollBody table,
            #account_book_wrapper .dataTables_scrollFoot table {
                min-width: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                table-layout: fixed !important;
            }

            #account_book_wrapper,
            #account_book_wrapper .dataTables_scroll,
            #account_book_wrapper .dataTables_scrollHead,
            #account_book_wrapper .dataTables_scrollBody,
            #account_book_wrapper .dataTables_scrollFoot {
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: hidden !important;
            }

            #account_book thead th,
            #account_book tbody td,
            #account_book tfoot td {
                min-width: 0 !important;
                padding: 5px 6px !important;
                vertical-align: middle !important;
                box-sizing: border-box !important;
            }

            #account_book thead th {
                white-space: normal !important;
                overflow-wrap: anywhere;
                text-align: center !important;
                line-height: 1.15 !important;
                font-size: 13px !important;
            }

            #account_book tbody td {
                white-space: normal !important;
                overflow-wrap: anywhere;
                word-break: normal;
                line-height: 1.25 !important;
                font-size: 14px !important;
            }

            /*
             * 11 Sep 2026 - Professional Account Book layout.
             * Keep the whole book inside the available screen width, but give
             * dates and money enough room to remain readable. Percentages total
             * 100% for both normal and Card/Slip account books so DataTables does
             * not redistribute widths unpredictably.
             */
            #account_book.no-slip-column th.account-book-date,
            #account_book.no-slip-column td.account-book-date {
                width: 8.5% !important;
            }

            #account_book.no-slip-column th.account-book-transaction-date,
            #account_book.no-slip-column td.account-book-transaction-date {
                width: 8.5% !important;
            }

            #account_book.has-slip-column th.account-book-date,
            #account_book.has-slip-column td.account-book-date,
            #account_book.has-slip-column th.account-book-transaction-date,
            #account_book.has-slip-column td.account-book-transaction-date {
                width: 8% !important;
            }

            #account_book th.account-book-date,
            #account_book td.account-book-date,
            #account_book th.account-book-transaction-date,
            #account_book td.account-book-transaction-date {
                text-align: center !important;
                white-space: nowrap !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
                overflow: hidden !important;
            }

            #account_book.no-slip-column th.account-book-description,
            #account_book.no-slip-column td.account-book-description {
                width: 21.5% !important;
            }

            #account_book.has-slip-column th.account-book-description,
            #account_book.has-slip-column td.account-book-description {
                width: 18.5% !important;
            }

            #account_book th.account-book-description,
            #account_book td.account-book-description {
                text-align: left !important;
                white-space: normal !important;
                overflow-wrap: break-word !important;
                word-break: normal !important;
            }

            #account_book.has-slip-column th.account-book-slip,
            #account_book.has-slip-column td.account-book-slip {
                width: 6.5% !important;
                text-align: center !important;
            }

            #account_book.no-slip-column th.account-book-cheque,
            #account_book.no-slip-column td.account-book-cheque {
                width: 8% !important;
            }

            #account_book.has-slip-column th.account-book-cheque,
            #account_book.has-slip-column td.account-book-cheque {
                width: 7% !important;
            }

            #account_book th.account-book-cheque,
            #account_book td.account-book-cheque {
                text-align: center !important;
                overflow-wrap: break-word !important;
                word-break: normal !important;
            }

            #account_book.no-slip-column th.account-book-pd-date,
            #account_book.no-slip-column td.account-book-pd-date {
                width: 7% !important;
            }

            #account_book.has-slip-column th.account-book-pd-date,
            #account_book.has-slip-column td.account-book-pd-date {
                width: 6.5% !important;
            }

            #account_book th.account-book-pd-date,
            #account_book td.account-book-pd-date {
                text-align: center !important;
                white-space: nowrap !important;
                overflow-wrap: normal !important;
            }

            #account_book th.account-book-amount,
            #account_book td.account-book-amount {
                min-width: 0 !important;
                white-space: nowrap !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
                text-align: right !important;
                font-variant-numeric: tabular-nums;
            }

            #account_book th.account-book-amount {
                text-align: center !important;
            }

            #account_book.no-slip-column th.account-book-opening,
            #account_book.no-slip-column td.account-book-opening,
            #account_book.no-slip-column th.account-book-balance,
            #account_book.no-slip-column td.account-book-balance {
                width: 11.75% !important;
            }

            #account_book.no-slip-column th.account-book-debit,
            #account_book.no-slip-column td.account-book-debit,
            #account_book.no-slip-column th.account-book-credit,
            #account_book.no-slip-column td.account-book-credit {
                width: 11.5% !important;
            }

            #account_book.has-slip-column th.account-book-opening,
            #account_book.has-slip-column td.account-book-opening,
            #account_book.has-slip-column th.account-book-balance,
            #account_book.has-slip-column td.account-book-balance {
                width: 11.75% !important;
            }

            #account_book.has-slip-column th.account-book-debit,
            #account_book.has-slip-column td.account-book-debit,
            #account_book.has-slip-column th.account-book-credit,
            #account_book.has-slip-column td.account-book-credit {
                width: 11% !important;
            }

            #account_book .account-book-heading-line {
                display: block;
                white-space: nowrap !important;
            }

            /* Keep DataTables sort glyphs away from the heading text. */
            #account_book thead th.sorting,
            #account_book thead th.sorting_asc,
            #account_book thead th.sorting_desc {
                position: relative;
                padding-right: 24px !important;
            }

            #account_book thead th.sorting:before,
            #account_book thead th.sorting:after,
            #account_book thead th.sorting_asc:before,
            #account_book thead th.sorting_asc:after,
            #account_book thead th.sorting_desc:before,
            #account_book thead th.sorting_desc:after {
                right: 6px !important;
            }

            #account_book .account-book-note-wrap {
                display: block;
                margin-top: 6px;
                text-align: left;
            }

            #account_book .account-book-note-wrap .note_btn {
                min-height: 28px !important;
                padding: 4px 10px !important;
                font-size: 13.5px !important;
                line-height: 1.15 !important;
                white-space: nowrap !important;
            }

            #account_book .account-book-reconcile-wrap {
                display: block;
                margin-top: 5px;
                text-align: right;
            }

            #account_book .finance_reconcile_status_btn {
                min-height: 24px !important;
                padding: 2px 7px !important;
                line-height: 1.15 !important;
                white-space: nowrap !important;
                font-size: 12px !important;
            }

            #account_book .finance_reconcile_status_btn[disabled] {
                opacity: 0.65;
                cursor: wait;
            }

            #account_book td.account-book-amount .display_currency,
            #account_book td.account-book-amount .debit_col,
            #account_book td.account-book-amount .credit_col {
                display: inline-block;
                max-width: 100%;
                white-space: nowrap !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
            }

            #account_book .account-book-date-time {
                display: inline-block;
                max-width: 100%;
                text-align: center;
                line-height: 1.2;
            }

            #account_book .account-book-date-time .account-book-date-value,
            #account_book .account-book-date-time .account-book-time-value {
                display: block;
                white-space: nowrap !important;
            }

            #account_book .account-book-date-time small,
            #account_book .account-book-time-value {
                margin-top: 3px;
                font-size: clamp(12px, 0.72vw, 14px) !important;
                line-height: 1.1 !important;
                color: #687386;
                font-weight: 500;
            }

            /* Professional typography: readable without forcing awkward wraps. */
            #account_book thead th {
                font-size: clamp(13px, 0.78vw, 15px) !important;
                font-weight: 700 !important;
                line-height: 1.18 !important;
                letter-spacing: 0 !important;
                padding: 9px 8px !important;
                vertical-align: middle !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
            }

            #account_book tbody td,
            #account_book tfoot td {
                font-size: clamp(14px, 0.86vw, 16px) !important;
                line-height: 1.28 !important;
                padding: 9px 8px !important;
                vertical-align: middle !important;
            }

            #account_book tbody td.account-book-description {
                line-height: 1.35 !important;
            }

            #account_book tbody td.account-book-amount {
                font-size: clamp(14px, 0.88vw, 16.5px) !important;
                font-weight: 500;
            }

            /* Keep the Account Book fitted to the actual browser/screen width. */
            .account-book-page .box-body {
                overflow-x: hidden !important;
            }

            @media (max-width: 1366px) {
                #account_book thead th {
                    font-size: 12.5px !important;
                    padding: 7px 5px !important;
                }

                #account_book tbody td,
                #account_book tfoot td {
                    font-size: 13.5px !important;
                    padding: 8px 5px !important;
                }

                #account_book .account-book-date-time small,
                #account_book .account-book-time-value {
                    font-size: 11.5px !important;
                }
            }

            #account_book_wrapper .dataTables_length,
            #account_book_wrapper .dataTables_filter,
            #account_book_wrapper .dt-buttons {
                max-width: 100% !important;
            }

            @if ($id == $card_account_id || $account->parent_account_id == $card_account_id)
                /* Card Account Books only: compact rows without changing data. */
                #account_book tbody td {
                    padding: 3px 6px !important;
                    line-height: 1.15 !important;
                    font-size: 14px !important;
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
                            <div id="finance_reconcile_error" class="alert alert-danger" style="display:none; margin-bottom: 12px;">
                                <strong id="finance_reconcile_error_reason"></strong>
                                <ol id="finance_reconcile_error_steps" style="margin: 8px 0 0 20px; padding-left: 16px;"></ol>
                            </div>
                            <div class="table-responsive account-book-table-scroll">
                                <div id="hiddenDiv"></div>
                                <table class="table table-bordered table-striped {{ $account->parent_account_id == $card_account_id ? 'has-slip-column' : 'no-slip-column' }}" id="account_book" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th class="account-book-date">@lang('messages.date')</th>
                                            <th class="account-book-transaction-date"><span class="account-book-heading-line">Transaction</span><span class="account-book-heading-line">Date</span></th>
                                            <th class="account-book-description">@lang('lang_v1.description')</th>
                                            @if($account->parent_account_id == $card_account_id)
                                                <th class="account-book-slip">@lang('petro::lang.slip_no')</th>
                                            @endif
                                            <th class="account-book-cheque"><span class="account-book-heading-line">Card / Cheque</span><span class="account-book-heading-line">Number</span></th>
                                            <th class="account-book-pd-date"><span class="account-book-heading-line">PD/Cheque</span><span class="account-book-heading-line">Date</span></th>
                                            <th class="account-book-amount account-book-opening"><span class="account-book-heading-line">Opening</span><span class="account-book-heading-line">Balance</span></th>
                                            <th class="account-book-amount account-book-debit">@lang('account.debit')</th>
                                            <th class="account-book-amount account-book-credit">@lang('account.credit')</th>
                                            <th class="account-book-amount account-book-balance"><span class="account-book-heading-line">Remaining</span><span class="account-book-heading-line">Balance</span></th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="{{ $account->parent_account_id == $card_account_id ? 7 : 6 }}"><strong>@lang('sale.total'):</strong></td>
                                            <td><span id="footer_debit_total" class="display_currency"
                                                    data-currency_symbol="true"></span></td>
                                            <td><span id="footer_credit_total" class="display_currency"
                                                    data-currency_symbol="true"></span></td>
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
        // The default Account Book range is resolved by the Finance controller.


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
                    window.location.href = @json(route('finance.list-accounts.live.account_book.show', ['id' => 'ACCOUNT_ID'], false))
                        .replace('ACCOUNT_ID', encodeURIComponent(mainAccountId));
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


            var financeCanReconcile = @json(auth()->user()->can('account.reconcile'));
            var financeCanUnreconcile = @json(auth()->user()->can('account.unreconcile'));
            var financeReconcileLabel = @json(__('account.reconcile'));
            var financeReconciledLabel = @json(__('account.reconciled'));

            function financeReconcileButtonHtml(row) {
                var transactionId = parseInt(row && row.account_transaction_id ? row.account_transaction_id : 0, 10);
                if (!transactionId) {
                    return '';
                }

                var status = parseInt(row.reconcile_state || 0, 10) === 1 ? 1 : 0;
                if (status === 0 && !financeCanReconcile) {
                    return '';
                }
                if (status === 1 && !financeCanUnreconcile) {
                    return '';
                }

                var targetStatus = status === 1 ? 0 : 1;
                var background = status === 1 ? '#28a745' : '#FEA61E';
                var icon = status === 1 ? 'fa-check' : 'fa-times';
                var label = status === 1 ? financeReconciledLabel : financeReconcileLabel;

                return '<span class="account-book-reconcile-wrap">' +
                    '<button type="button" class="btn btn-xs hide-in-iframe finance_reconcile_status_btn" ' +
                    'style="background:' + background + '; color:#fff;" ' +
                    'data-href="/finance/reconcile/' + transactionId + '?target_status=' + targetStatus + '" ' +
                    'data-target-status="' + targetStatus + '" ' +
                    'data-transaction-id="' + transactionId + '">' +
                    '<i class="fa ' + icon + '"></i> ' + $('<div>').text(label).html() +
                    '</button></span>';
            }

            function financeShowReconcileError(payload) {
                payload = payload || {};
                var reason = payload.reason || payload.msg || 'Finance could not change the reconciliation status for this entry.';
                var steps = Array.isArray(payload.steps) ? payload.steps : [
                    'Refresh the Account Book and retry once.',
                    'If the entry belongs to a finalized Bank Reconciliation, reopen that reconciliation first.',
                    'If the issue continues, contact the administrator with the affected transaction details.'
                ];

                $('#finance_reconcile_error_reason').text(reason);
                var $steps = $('#finance_reconcile_error_steps').empty();
                steps.forEach(function(step) {
                    $('<li>').text(step).appendTo($steps);
                });
                $('#finance_reconcile_error').stop(true, true).fadeIn(120);

                if (typeof toastr !== 'undefined') {
                    toastr.error(reason);
                }
            }

            function financeClearReconcileError() {
                $('#finance_reconcile_error').hide();
                $('#finance_reconcile_error_reason').text('');
                $('#finance_reconcile_error_steps').empty();
            }

            // Finance owns this class. Do not reuse the generic
            // reconcile_status_btn class: older/global handlers can otherwise
            // issue a second toggle request and flip the row back immediately.
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
                    financeClearReconcileError();

                    $.ajax({
                        method: 'GET',
                        url: href,
                        dataType: 'json',
                        cache: false,
                        data: {
                            target_status: targetStatus
                        },
                        success: function(result) {
                            if (result && result.success === true && parseInt(result.status, 10) === targetStatus) {
                                if (typeof toastr !== 'undefined') {
                                    toastr.success(result.msg || 'Reconciliation status saved successfully.');
                                }
                                if (typeof account_book !== 'undefined' && account_book && account_book.ajax) {
                                    account_book.ajax.reload(null, false);
                                }
                                return;
                            }

                            financeShowReconcileError(result || {});
                        },
                        error: function(xhr) {
                            financeShowReconcileError(
                                (xhr && xhr.responseJSON) ? xhr.responseJSON : {
                                    reason: 'The server could not save the reconciliation status.',
                                    steps: [
                                        'Refresh the Account Book and retry once.',
                                        'If this entry is part of a finalized Bank Reconciliation, reopen it first.',
                                        'If the issue continues, ask the administrator to check the Laravel log.'
                                    ]
                                }
                            );
                        },
                        complete: function() {
                            $button.data('finance-reconcile-busy', false).prop('disabled', false);
                        }
                    });
                });


            // Customer and supplier lists can contain thousands of records.
            // Search them through the Finance endpoint instead of downloading
            // every contact before the Account Book table starts.
            function initialiseAccountBookContactFilter(selector, contactType) {
                var $select = $(selector);
                if (!$select.length || !$.fn.select2) {
                    return;
                }

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.empty().append(new Option(@json(__('messages.all')), '', true, true));
                $select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: @json(__('messages.all')),
                    minimumInputLength: 0,
                    ajax: {
                        url: '{{ route('finance.list-accounts.live.account_book.contact-options', [], false) }}',
                        dataType: 'json',
                        delay: 250,
                        cache: true,
                        data: function(params) {
                            return {
                                type: contactType,
                                q: params.term || ''
                            };
                        },
                        processResults: function(response) {
                            return response && response.results ? response : {results: []};
                        }
                    }
                });
            }

            initialiseAccountBookContactFilter('#transaction_customer', 'customer');
            initialiseAccountBookContactFilter('#transaction_supplier', 'supplier');

            // The balance is returned with the first paginated data response.
            // Avoid a second full account scan during initial page load.
            // update_description();

            // Load large filter lists only when the user opens the related
            // filter. They must not compete with the first Account Book request.
            var accountBookFilterOptionsLoaded = {
                cheque: false,
                amount: false
            };

            function loadAccountBookFilterOptions(type) {
                if (accountBookFilterOptionsLoaded[type]) {
                    return;
                }
                accountBookFilterOptionsLoaded[type] = true;

                var isCheque = type === 'cheque';
                $.ajax({
                    /*
                     * MA-002: a failed filter must not block the page.
                     *
                     * This loads an optional dropdown from
                     * /customer-payment-information/... which is a CORE
                     * CONTACTS url (CustomerPaymentController -> contact_module).
                     * When Contacts is switched off in Manage Side Bar the
                     * request is refused and the page showed
                     *   "This module has been disabled ... (contact_module)"
                     * while someone was working in Finance.
                     *
                     * These populate FILTERS. Losing one leaves a dropdown
                     * empty; nothing else depends on it.
                     */
                    error: function () {},

                    method: 'get',
                    url: isCheque
                        ? '/customer-payment-information/all/cheque_no'
                        : '/customer-payment-information/all/amount',
                    dataType: 'json',
                    timeout: 15000,
                    success: function(result) {
                        var target = isCheque ? $('#customer_cheque_no') : $('#customer_amount');
                        if (result && result.data) {
                            target.populate(result.data);
                        }
                    },
                    error: function() {
                        // The filter is optional; leave the existing options usable.
                        accountBookFilterOptionsLoaded[type] = false;
                    }
                });
            }

            $('#customer_cheque_no').one('select2:opening focus', function() {
                loadAccountBookFilterOptions('cheque');
            });
            $('#customer_amount').one('select2:opening focus', function() {
                loadAccountBookFilterOptions('amount');
            });

            // The controller supplies only the latest saved range. Initialise the
            // picker synchronously and let DataTables send its first request at once.
            var defaultStart = moment('{{ $financeAccountBookStartDate }}', 'YYYY-MM-DD');
            var defaultEnd = moment('{{ $financeAccountBookEndDate }}', 'YYYY-MM-DD');
            if (!defaultStart.isValid()) {
                defaultStart = moment().subtract(30, 'days');
            }
            if (!defaultEnd.isValid()) {
                defaultEnd = moment();
            }

            dateRangeSettings.startDate = defaultStart;
            dateRangeSettings.endDate = defaultEnd;
            $('#transaction_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end, label) {
                    if (label === 'Custom Date Range') {
                        $('.custom_date_typing_modal').modal('show');
                        return;
                    }

                    $('#transaction_date_range').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    if ($.fn.DataTable.isDataTable('#account_book')) {
                        account_book.order([[0, 'asc']]).ajax.reload(null, true);
                    }
                }
            );
            $('#transaction_date_range').val(
                defaultStart.format(moment_date_format) + ' ~ ' + defaultEnd.format(moment_date_format)
            );




            // Account Book
            // IS8036: every export asks whether to use only the current
            // DataTables page or every record matching the active Account Book
            // filters. The existing CSV/Excel/PDF/Print actions are preserved;
            // this wrapper only controls how many server-side rows are loaded
            // immediately before the export runs.
            function installAccountBookExportScopePrompt(table) {
                if (!table || !table.buttons) {
                    return;
                }

                var exportSelector = '.buttons-csv, .buttons-excel, .buttons-pdf, .buttons-print, .buttons-copy';
                $(table.buttons().nodes()).filter(exportSelector).each(function() {
                    var node = this;
                    var buttonApi = table.button(node);
                    var originalAction = buttonApi.action();

                    if (!originalAction || $(node).data('finance-export-scope-bound')) {
                        return;
                    }
                    $(node).data('finance-export-scope-bound', true);

                    buttonApi.action(function(e, dt, button, config) {
                        var buttonContext = this;
                        swal({
                            title: 'Export Account Book',
                            text: 'Select which records should be exported.',
                            icon: 'info',
                            buttons: {
                                cancel: 'Cancel',
                                current: {
                                    text: 'Only Current Page Records',
                                    value: 'current'
                                },
                                all: {
                                    text: 'Show All Records',
                                    value: 'all'
                                }
                            }
                        }).then(function(scope) {
                            if (scope === 'current') {
                                originalAction.call(buttonContext, e, dt, button, config);
                                return;
                            }
                            if (scope !== 'all') {
                                return;
                            }

                            var oldLength = dt.page.len();
                            var oldPage = dt.page();
                            var restore = function() {
                                dt.one('draw.financeExportRestore', function() {
                                    if (oldPage > 0 && dt.page.info().pages > oldPage) {
                                        dt.page(oldPage).draw('page');
                                    }
                                });
                                dt.page.len(oldLength).draw(false);
                            };

                            dt.one('draw.financeExportAll', function() {
                                // The full filtered dataset is now in the browser.
                                // Run the module's existing export action unchanged,
                                // then restore the user's previous page length/page.
                                originalAction.call(buttonContext, e, dt, button, config);
                                setTimeout(restore, 0);
                            });
                            dt.page.len(-1).draw(false);
                        });
                    });
                });
            }

            account_book = $('#account_book').DataTable({
                language: {
                    "emptyTable": "@if (!$account_access) {{ App\System::getProperty('not_enalbed_module_user_message') }} @else @lang('account.no_data_available_in_table') @endif"
                },
                processing: true,
                serverSide: true,
                /*
                 * MA-002 (item 3): searching was slow because EVERY KEYSTROKE
                 * went to the server.
                 *
                 * With serverSide on, DataTables sends a fresh request for each
                 * search, and that request runs the full account book query -
                 * six joins and three subqueries - then rebuilds the running
                 * balance. On an account with only ~75 entries that work is
                 * entirely wasted: the whole book is smaller than a single page
                 * of most tables.
                 *
                 * searchDelay is raised from 350ms to 600ms so a person typing
                 * "cheque" fires ONE request instead of six. That alone removes
                 * most of the wait, because the cost was never the row count -
                 * it was doing the expensive query once per character.
                 *
                 * I have NOT switched serverSide off. It is right for the large
                 * accounts on this system - the Finished Goods book already
                 * holds over a hundred rows and grows daily - and turning it off
                 * would load every row into the browser on those. The fix is to
                 * stop asking six times, not to change how the data is fetched.
                 */
                deferRender: true,
                searchDelay: 600,
                pageLength: 25,
                lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
                stateSave: false,
                scrollX: false,
                scrollCollapse: false,
                autoWidth: false,
                responsive: false,
                order: [
                    [0, 'asc']
                ],
                ajax: {
                    // Keep this request on the current tenant host. Generating an
                    // absolute URL from a stale APP_URL/config cache can send the
                    // AJAX call to the central domain and return HTML/404 instead
                    // of the DataTables JSON payload.
                    url: '{{ route('finance.list-accounts.live.account_book.data', ['id' => (int) $account->id], false) }}',
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    timeout: 30000,
                    data: function(d) {
                        var start = '';
                        var end = '';
                        var picker = $('input#transaction_date_range').data('daterangepicker');
                        if ($('#transaction_date_range').val() && picker) {
                            start = picker.startDate.format('YYYY-MM-DD');
                            end = picker.endDate.format('YYYY-MM-DD');
                        } else {
                            start = '{{ $financeAccountBookStartDate }}';
                            end = '{{ $financeAccountBookEndDate }}';
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
                        // The selected account is business/location-owned. Send its
                        // location explicitly so the Finance endpoint can apply the
                        // same scope even when the browser session has another active location.
                        d.location_id = '{{ (int) ($account->location_id ?? 0) }}';

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
                        window.financeAccountBookTotals = (json && json.totals) ? json.totals : {};
                        window.financeAccountBookScope = (json && json.scope) ? json.scope : {};

                        rows.forEach(function(row) {
                            if (typeof row.realize_date === 'undefined' || row.realize_date === null) {
                                row.realize_date = row.operation_date || '';
                            }

                            // The visible Reconcile Status column remains
                            // removed. reconcile_state is metadata used only by
                            // the Debit/Credit reconciliation button.
                            if (typeof row.reconcile_state === 'undefined' || row.reconcile_state === null || row.reconcile_state === '') {
                                row.reconcile_state = 0;
                            }
                            if (typeof row.reconcile_status === 'undefined' || row.reconcile_status === null) {
                                row.reconcile_status = '';
                            }

                            if (!row.account_transaction_id && row.DT_RowId) {
                                var idMatch = String(row.DT_RowId).match(/account_transaction_(\d+)/);
                                if (idMatch) {
                                    row.account_transaction_id = parseInt(idMatch[1], 10);
                                }
                            }
                        });

                        return rows;
                    },
                    error: function(xhr, error, thrown) {
                        if (error === 'abort' || xhr.status === 0) {
                            return;
                        }

                        console.error('Finance Account Book Ajax Error:', error, thrown);
                        console.error('Response:', xhr.responseText);
                        console.error('Status:', xhr.status);

                        var message = 'Unable to load Account Book data.';
                        var contentType = String(xhr.getResponseHeader('Content-Type') || '').toLowerCase();

                        if (xhr.status === 401 || xhr.status === 419) {
                            message = 'Your login session has expired. Please refresh the page and sign in again.';
                        } else if (xhr.status === 403 && xhr.responseJSON && xhr.responseJSON.msg) {
                            message = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else if (contentType.indexOf('text/html') !== -1) {
                            message = 'The server returned a web page instead of Account Book data. Please refresh once after deployment.';
                        }
                        var columnCount = $('#account_book thead th').length || 1;
                        $('#account_book_processing').hide();
                        $('#account_book tbody').html(
                            '<tr class="odd"><td valign="top" colspan="' + columnCount +
                            '" class="dataTables_empty text-danger">' + $('<div>').text(message).html() + '</td></tr>'
                        );
                    }
                },



                // added realize date for transaction date value by virtual it professional referance docs number 7338
                aaSorting: [
                    [0, 'asc']
                ],
                "ordering": true,
                "searching": true,
                columns: [{
                        data: 'operation_date',
                        name: 'operation_date',
                        className: 'account-book-date',
                        render: function(data, type, row) {
                            if (type !== 'display') {
                                return row.operation_date_raw || data || '';
                            }

                            var dateText = data || '';
                            var timeText = row.operation_time || '';
                            var safeDate = $('<div>').text(dateText).html();
                            var safeTime = $('<div>').text(timeText).html();

                            if (!timeText) {
                                return safeDate;
                            }

                            return '<span class="account-book-date-time">' +
                                '<span class="account-book-date-value">' + safeDate + '</span>' +
                                '<small class="account-book-time-value">' + safeTime + '</small>' +
                                '</span>';
                        }
                    },
                    {
                        data: 'realize_date',
                        name: 'realize_date',
                        defaultContent: '',
                        className: 'account-book-transaction-date'
                    },
                    {
                        data: 'description',
                        name: 'description',
                        className: 'account-book-description',
                        render: function(data, type, row) {
                            if (type !== 'display') {
                                return data || '';
                            }

                            var description = data || '-';
                            var noteButton = row.note_button || '';
                            return description + (noteButton
                                ? '<div class="account-book-note-wrap">' + noteButton + '</div>'
                                : '');
                        }
                    },
                    @if($account->parent_account_id == $card_account_id)
                    {
                        data: 'slip_no',
                        name: 'slip_no',
                        orderable: false,
                        searchable: true,
                        className: 'account-book-slip'
                    },
                    @endif
                    {
                        data: 'cheque_number',
                        name: 'cheque_number',
                        className: 'account-book-cheque'
                    },
                    {
                        data: 'cheque_date',
                        name: 'cheque_date',
                        className: 'account-book-pd-date'
                    },
                    {
                        data: 'opening_balance',
                        name: 'opening_balance',
                        className: 'account-book-amount account-book-opening'
                    },
                    {
                        data: 'debit',
                        name: 'amount',
                        className: '<?php echo $debitClassName; ?> account-book-amount account-book-debit',
                        render: function(data, type, row) {
                            if (type !== 'display') {
                                return data || '';
                            }

                            var amount = data || '';
                            var reconcileButton = amount ? financeReconcileButtonHtml(row) : '';
                            return `<span class="debit_col" data-deleted="${row.new_deleted_at ?? ''}">${amount}</span>${reconcileButton}`;
                        }
                    },
                    {
                        data: 'credit',
                        name: 'amount',
                        className: '<?php echo $creditClassName; ?> account-book-amount account-book-credit',
                        render: function(data, type, row) {
                            if (type !== 'display') {
                                return data || '';
                            }

                            var amount = data || '';
                            var reconcileButton = amount ? financeReconcileButtonHtml(row) : '';
                            return `<span class="credit_col" data-deleted="${row.new_deleted_at ?? ''}">${amount}</span>${reconcileButton}`;
                        }
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                        searchable: false,
                        className: 'account-book-amount account-book-balance'
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
                    var totals = window.financeAccountBookTotals || {};
                    if ($('#footer_debit_total').length) {
                        var debit_total = parseFloat(totals.debit || 0);
                        var credit_total = parseFloat(totals.credit || 0);
                        $('#footer_debit_total').attr('data-orig-value', debit_total).data('orig-value', debit_total).text(debit_total);
                        $('#footer_credit_total').attr('data-orig-value', credit_total).data('orig-value', credit_total).text(credit_total);
                    }
                    if (typeof totals.ending_balance !== 'undefined') {
                        $('span#account_balance').text(__currency_trans_from_en(totals.ending_balance, true));
                    }
                    __currency_convert_recursively($('#account_book'));

                    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#account_book')) {
                        setTimeout(function () {
                            $('#account_book').DataTable().columns.adjust();
                        }, 0);
                    }
                }
            });

            // Buttons are created during DataTable initialisation; defer one tick
            // so the standard module export buttons are available to wrap.
            setTimeout(function() {
                installAccountBookExportScopePrompt(account_book);

                // Re-fit the table whenever the browser width or application
                // sidebar width changes. This keeps all visible columns inside
                // the screen without enabling a horizontal scrollbar.
                var financeAccountBookResizeTimer = null;
                var fitFinanceAccountBookToScreen = function() {
                    clearTimeout(financeAccountBookResizeTimer);
                    financeAccountBookResizeTimer = setTimeout(function() {
                        if ($.fn.dataTable && $.fn.dataTable.isDataTable('#account_book')) {
                            account_book.columns.adjust();
                        }
                    }, 80);
                };

                $(window).off('resize.financeAccountBook').on('resize.financeAccountBook', fitFinanceAccountBookToScreen);
                $(document).off('click.financeAccountBookFit', '.sidebar-toggle, [data-toggle="push-menu"]')
                    .on('click.financeAccountBookFit', '.sidebar-toggle, [data-toggle="push-menu"]', function() {
                        setTimeout(fitFinanceAccountBookToScreen, 350);
                    });
                fitFinanceAccountBookToScreen();
            }, 0);


            // IS2241 #2: Reconcile Status is intentionally not shown in Account Books.

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
                url: '{{ route('finance.list-accounts.live.account_book.balance', ['id' => $account->id], false) }}',
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
                    /*
                     * MA-002: a failed filter must not block the page.
                     *
                     * This loads an optional dropdown from
                     * /customer-payment-information/... which is a CORE
                     * CONTACTS url (CustomerPaymentController -> contact_module).
                     * When Contacts is switched off in Manage Side Bar the
                     * request is refused and the page showed
                     *   "This module has been disabled ... (contact_module)"
                     * while someone was working in Finance.
                     *
                     * These populate FILTERS. Losing one leaves a dropdown
                     * empty; nothing else depends on it.
                     */
                    error: function () {},

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
                    /*
                     * MA-002: a failed filter must not block the page.
                     *
                     * This loads an optional dropdown from
                     * /customer-payment-information/... which is a CORE
                     * CONTACTS url (CustomerPaymentController -> contact_module).
                     * When Contacts is switched off in Manage Side Bar the
                     * request is refused and the page showed
                     *   "This module has been disabled ... (contact_module)"
                     * while someone was working in Finance.
                     *
                     * These populate FILTERS. Losing one leaves a dropdown
                     * empty; nothing else depends on it.
                     */
                    error: function () {},

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
                    [0, 'asc']
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
                    [0, 'asc']
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
                url: '/finance/realize-cheque-deposit',
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
