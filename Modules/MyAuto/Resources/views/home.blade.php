@extends('layouts.app')

@section('content')
    <style>
        body {
            font-size: 12px;
        }

        .home {
            max-width: 600px;
            width: 100%;
            margin: auto;
            background: #fff;
            padding: 8px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .top div {
            font-weight: bold;
        }

        .top .number {
            color: blue;
            /* margin-right: 2px; */
        }

        .top .date-number {
            color: red;
            margin-right: 2px;
        }

        .auto-no,
        .good-day-user-name,
        .date,
        .today-mileage {
            text-align: center;
            font-weight: bold;
            margin: 4px 0 6px;
            font-size: 200%;
        }

        .today-mileage {
            text-align: right;
            font-weight: bold;
            color: red;
            margin-bottom: 6px;
        }

        .row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 6px;
        }

        .box {
            padding: 4px;
            text-align: center;
            min-height: 38px;
            border: none;
        }

        .box label {
            display: block;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            margin-bottom: 2px;
        }

        .box input {
            height: 34px;
            text-align: center;
            font-size: 12px;
            border: 1px solid black;
        }

        .value-box {
            border: 1px solid #000;
            padding: 6px;
            height: 33px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            width: 125px;
            margin: 0 auto;
        }

        .divider {
            border-top: 3px solid green;
            margin: 8px 0;
        }

        /* ===== BOTTOM SECTION ===== */

        .bottom {
            margin: 40px auto 0;
            width: 100%;
        }


        /* Headers */
        .header-pink,
        .header-brown {
            color: #fff;
            font-weight: bold;
            padding: 8px 20px;
            font-size: 14px;
            margin-bottom: 18px;
            /* center header */
            display: block;
            text-align: center;
            display: inline-block;

        }

        /* Left column */
        .bottom-3>div:first-child {
            text-align: left;
        }

        /* Center column */
        .bottom-3>div:nth-child(2) {
            text-align: center;
        }

        /* Right column */
        .bottom-3>div:last-child {
            text-align: right;
        }

        .header-pink {
            background: #c83be6;
        }

        .header-brown {
            background: #7b1e1e;
        }

        /* Detail rows */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            font-size: 14px;
            /* remove bias */
        }

        /* Value box */
        .value {
            width: 110px;
            height: 32px;
            border: 1px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 50px;
        }


        .col-labels {
            display: grid;
            grid-template-columns: 1fr 90px 90px;
            gap: 6px;
            margin-bottom: 6px;
            font-weight: bold;
            align-items: center;
        }

        .row-2-centered {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            width: 100%;
        }

        /* BOTTOM GRID */
        .bottom-3 {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            /* column-gap: 16px; */
            align-items: start;
        }

        /* CENTER BUTTON COLUMN */
        .center-col {
            display: flex;
            justify-content: center;
        }

        /* FIX MODAL TYPOGRAPHY */
        .modal {
            font-size: 14px;
        }

        .modal .modal-body {
            padding-top: 20px;
        }

        /* Modal headers */
        .modal .section-title {
            font-size: 13px;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .modal .section-value {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .container {
            max-width: 520px;
            width: 100%;
            margin: auto;
            background: #fff;
            padding: 8px;
        }

        .auto-number-lg {
            font-size: 200%;
        }

        .dashboard-top-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .my-auto-subscription-panel {
            border: 1px solid #e5e7eb;
            background: #fafafa;
            padding: 12px 14px;
            margin: 8px 0 14px;
        }

        .my-auto-subscription-meta {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 6px;
        }

        @media (max-width: 480px) {
            .auto-no,
            .good-day-user-name,
            .date,
            .today-mileage {
                font-size: 130%;
            }

            .row-3 {
                grid-template-columns: repeat(2, 1fr);
            }

            .row-2-centered {
                grid-template-columns: 1fr;
            }

            .bottom-3 {
                grid-template-columns: 1fr;
            }

            .bottom-col {
                text-align: left !important;
            }

            .value {
                width: 90px;
                margin-left: 0;
            }

            .box input,
            .box .value-box {
                width: 100%;
            }

            .center-col {
                justify-content: flex-start;
                margin: 8px 0;
            }

            .top {
                flex-wrap: wrap;
                gap: 6px;
            }

            .dashboard-top-actions {
                justify-content: flex-start;
            }
        }
    </style>
    <div id="home-page">

        <div class="home">

            <div class="top" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div class="good-day-user-name"> Good Day {{ $setting->user_name }}</div> <!--this is 1 -->
                    <div class="date"> Date: {{ \Carbon\Carbon::now()->format('d-m-Y H:i') }}</div><!--this is 2 -->
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: bold; margin-bottom: 8px; color: #1f2937;">
                        @if(!is_null($subscription_remaining_days))
                            Subscription Remaining Days: {{ $subscription_remaining_days }}
                        @elseif(!empty($waiting_subscription))
                            Subscription Status: Pending Activation
                        @else
                            Subscription Remaining Days: 0
                        @endif
                    </div>
                    <div class="dashboard-top-actions">
                        <button type="button" id="change-passcode-btn" class="btn btn-primary btn-sm">
                            <i class="fa fa-lock"></i> Update Passcode
                        </button>
                        <a href="{{ action('Auth\LoginController@logout') }}" class="btn btn-warning btn-sm">
                            <i class="fa fa-sign-out"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <div class="auto-no">{{ $setting->auto_number }}</div> <!--this is 3 -->

            <div class="my-auto-subscription-panel">
                <div class="my-auto-subscription-meta">
                    Selected package:
                    <strong id="selected_my_auto_package_name">No package selected</strong>
                </div>
                <div class="my-auto-subscription-meta">
                    Subscription cycle:
                    <strong id="selected_my_auto_subscription_cycle">Not selected</strong>
                </div>
                <div class="my-auto-subscription-meta">
                    Amount to Auto load:
                    <strong id="selected_my_auto_amount_to_auto_load">Not entered</strong>
                </div>
                <button type="button" class="btn btn-warning btn-sm" id="open_my_auto_subscription_modal">
                    Add Subscription
                </button>
            </div>

            <div class="today-mileage">
                Today Mileage:
                <span id="today-mileage-val-top">
                    {{ number_format($todayLog->current_meter ? $todayLog->current_meter - $todayLog->starting_meter : 0, 2) }}
                </span>
            </div>
            <div class="mt-3 text-right">
                <button id="save-auto-btn" class="btn btn-success" style="font-size:16px; padding: 8px 13px;">
                    Save
                </button>
            </div>


            <!-- TRIP / METER -->
            <div class="row-2-centered">
                <div class="box" style="flex: 1; max-width: 180px;">
                    <label>This Trip Income</label> <!--this is 14 -->
                    <input id="trip-income-input" type="text" class="decimal-input trip-income-input auto-input"
                        value="{{ number_format($todayLog->trip_income ?? 0, 2, '.', '') }}" data-log-id="{{ $todayLog->id }}" data-field="trip_income">
                </div>

                <div class="box" style="flex: 1; max-width: 180px;">
                    <label>Current Meter</label> <!--this is 13 (duplicate of top value, read-only style) -->
                    <input id="current-meter-input" type="text" inputmode="decimal" class="decimal-input auto-input"
                        value="{{ $todayLog->current_meter ? number_format($todayLog->current_meter, 2) : '' }}"
                        data-prev-meter="{{ $todayLog->current_meter ? number_format($todayLog->current_meter, 2, '.', '') : '' }}"
                        data-log-id="{{ $todayLog->id }}" data-field="current_meter" />
                </div>
            </div>

            <div class="row-3">
                <div class="box">
                    <label>Income</label> <!--this is 10 (cumulative income) -->
                    <div id="income" class="value-box">
                        {{ number_format($todayLog->trip_income ?? 0, 2) }}
                    </div>
                </div>

                <div class="box">
                    <label>Expenses</label> <!--this is 11 (cumulative expenses) -->
                    <div id="expenses" class="value-box">
                        {{ number_format($todayLog->expense_1 + $todayLog->expense_2 + $todayLog->expense_3 + $todayLog->expense_4 + $todayLog->expense_5, 2) }}
                    </div>
                </div>

                <div class="box">
                    <label>Profit</label> <!--this is 12 (income - expenses) -->
                    <div id="profit" class="value-box">
                        {{ number_format(($todayLog->trip_income ?? 0) - ($todayLog->expense_1 + $todayLog->expense_2 + $todayLog->expense_3 + $todayLog->expense_4 + $todayLog->expense_5), 2) }}
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <!-- EXPENSE INPUTS -->
            <div class="row-3">
                <div class="box">
                    <label>Petrol / Diesel</label> <!--this is 15 -->
                    <input type="text" class="decimal-input auto-input" value="{{ number_format($todayLog->expense_1 ?? 0, 2, '.', '') }}" data-log-id="{{ $todayLog->id }}"
                        data-field="expense_1">
                </div>
                <div class="box">
                    <label>Oil</label> <!--this is 16 -->
                    <input type="text" class="decimal-input auto-input" value="{{ number_format($todayLog->expense_2 ?? 0, 2, '.', '') }}" data-log-id="{{ $todayLog->id }}"
                        data-field="expense_2">
                </div>
                <div class="box">
                    <label>Repairs</label> <!--this is 17 -->
                    <input type="text" class="decimal-input auto-input" value="{{ number_format($todayLog->expense_3 ?? 0, 2, '.', '') }}" data-log-id="{{ $todayLog->id }}"
                        data-field="expense_3">
                </div>
            </div>

            <div class="row-3">
                <div class="box">
                    <label>Meals</label> <!--this is 18 -->
                    <input type="text" class="decimal-input auto-input" value="{{ number_format($todayLog->expense_4 ?? 0, 2, '.', '') }}" data-log-id="{{ $todayLog->id }}"
                        data-field="expense_4">
                </div>
                <div class="box">
                    <label>Others</label> <!--this is 19 -->
                    <input type="text" class="decimal-input others-input auto-input" value="{{ number_format($todayLog->expense_5 ?? 0, 2, '.', '') }}"
                        data-log-id="{{ $todayLog->id }}" data-field="expense_5">
                    <input type="hidden" id="others-note" value="">
                </div>
                <div class="box">
                    <label>Starting Meter</label> <!--this is 4 -->
                    <div class="value-box" id="starting-meter">{{ number_format($todayLog->starting_meter, 2) }}</div>
                </div>
            </div>

            <!-- BOTTOM SECTIONS -->
            <div class="bottom bottom-3">

                <!-- LEFT : TODAY -->
                <div>
                    <div class="bottom-col">

                        <div id="today-income-btn" class="btn btn-sm header-pink mt-2">
                            <span>Today Income Details</span>
                        </div>

                        <div style="position: relative; left: 50px; width: 110px; text-align:center !important; font-weight:bold; margin-bottom:15px; margin-left: 76px;">
                            Today
                        </div>

                        <div class="detail-row">
                            Income
                            <div class="value" id="today-income">
                                {{ number_format($todayLog->trip_income ?? 0, 2) }}
                            </div>
                        </div>

                        <div class="detail-row">
                            Expenses
                            <div class="value" id="today-expenses">
                                {{ number_format(
                                    $todayLog->expense_1 + $todayLog->expense_2 + $todayLog->expense_3 + $todayLog->expense_4 + $todayLog->expense_5,
                                    2,
                                ) }}
                            </div>
                        </div>

                        <div class="detail-row">
                            Profit
                            <div class="value" id="today-profit">
                                {{ number_format(
                                    ($todayLog->trip_income ?? 0) -
                                        ($todayLog->expense_1 +
                                            $todayLog->expense_2 +
                                            $todayLog->expense_3 +
                                            $todayLog->expense_4 +
                                            $todayLog->expense_5),
                                    2,
                                ) }}
                            </div>
                        </div>

                        <div class="detail-row">
                            Current Meter
                            <div class="value" id="today-current-meter">
                                {{ number_format($todayLog->current_meter ?? 0, 2) }}
                            </div>
                        </div>

                        <div class="detail-row">
                            Mileage
                            <div class="value" id="today-mileage-val">
                                {{ number_format($todayLog->current_meter ? $todayLog->current_meter - $todayLog->starting_meter : 0, 2) }}
                            </div>
                        </div>

                    </div>
                </div>

                <!-- MIDDLE : PAST DETAILS -->
                <div class="center-col">
                    <div id="past-details-btn" class="btn btn-sm btn-info">
                        Past Details
                    </div>
                </div>

                <!-- RIGHT : TOTAL -->
                <div>
                    <div class="bottom-col">

                        <div id="today-expense-btn" class="btn btn-sm header-brown mt-2">
                            <span>Today Expense Details</span>
                        </div>

                        <div style="text-align:center !important; font-weight:bold; margin-bottom:15px; width: 110px; margin-left: 50px;">
                            Total
                        </div>

                        <div class="detail-row">
                            <div class="value" id="total-income">
                                {{ number_format($totalIncome, 2) }}
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="value" id="total-expense">
                                {{ number_format($totalExpense, 2) }}
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="value" id="total-profit">
                                {{ number_format($totalIncome - $totalExpense, 2) }}
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="value" id="total-current-meter">
                                {{ number_format($todayLog->current_meter ?? 0, 2) }}
                            </div>
                        </div>

                        <div class="detail-row">
                            <div class="value" id="total-mileage">
                                {{ number_format(($todayLog->current_meter ?? $todayLog->starting_meter) - $setting->starting_meter, 2) }}
                            </div> <!-- this is 22: current meter minus starting meter -->
                        </div>

                    </div>
                </div>

            </div>

        </div>

        <!-- Today Income Modal -->
        <div class="modal fade" id="todayIncomeModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content shadow-lg border border-success rounded-3">

                    <div class="modal-header py-2">
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">

                        <!-- TOP HEADER ROW -->
                        <div class="row mb-4">

                            <div class="col-md-4">
                                <div class="section-title text-muted text-uppercase">Today</div>
                                <div class="section-value" id="modal-today-date">{{ now()->format('d-m-Y') }}</div>
                            </div>

                            <div class="col-md-4 font-weight-bold">
                                Income Details
                            </div>

                            <div class="col-md-4 text-right">
                                <div class="section-title text-muted text-uppercase">Total</div>
                                <div class="font-weight-bold text-success">
                                    <span id="modal-total">{{ number_format($incomeTotal, 2) }}</span>
                                </div>
                            </div>

                        </div>

                        <!-- TABLE -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Starting Meter</th>
                                        <th>Closing Meter</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="income-details-body">
                                    @foreach ($incomeTransactions as $t)
                                        <tr>
                                            <td>{{ $t->created_at->format('d-m-Y H:i') }}</td>
                                            <td>{{ number_format($todayLog->starting_meter ?? 0, 2) }}</td>
                                            <td>{{ number_format($todayLog->current_meter ?? 0, 2) }}</td>
                                            <td class="text-success fw-bold">{{ number_format($t->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Close
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Total Expense Modal -->
        <div class="modal fade" id="todayExpenseModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content shadow-lg border border-primary rounded-3">

                    <div class="modal-header py-2">
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">

                        <!-- TOP HEADER ROW -->
                        <div class="row mb-4">

                            <div class="col-md-4">
                                <div class="section-title text-muted text-uppercase">Today</div>
                                <div class="section-value" id="expense-date">{{ now()->format('d-m-Y') }}</div>
                            </div>

                            <div class="col-md-4 font-weight-bold">
                                Expense Details
                            </div>

                        </div>

                        <!-- TABLE -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Starting Meter</th>
                                        <th>Closing Meter</th>
                                        <th>Expense Type</th>
                                        <th class="text-right">Amount</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody id="expense-details-body">
                                    @foreach ($expenseTransactions as $exp)
                                        <tr>
                                            <td>{{ $exp->created_at->format('d-m-Y H:i') }}</td>
                                            <td>{{ number_format($todayLog->starting_meter ?? 0, 2) }}</td>
                                            <td>{{ number_format($todayLog->current_meter ?? 0, 2) }}</td>
                                            <td>{{ $expenseMap[$exp->expense_field] ?? 'Unknown' }}</td>
                                            <td class="text-end fw-bold text-primary">{{ number_format($exp->amount, 2) }}
                                            </td>
                                            <td>
                                                @if ($exp->note && trim($exp->note) !== '')
                                                    <button class="btn btn-sm btn-outline-primary view-note-btn"
                                                        data-note="{{ e($exp->note) }}">Note</button>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Close
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="modal fade" id="noteModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border border-primary rounded-3">
                    <div class="modal-header">
                        <h5 class="modal-title">Expense Note</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p id="note-content" class="mb-0"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Past Details Modal -->
        <div class="modal fade" id="pastDetailsModal" tabindex="-1" aria-labelledby="pastDetailsModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content shadow-lg border-0 rounded-3">

                    <div class="modal-header">
                        <h5 class="modal-title" id="pastDetailsModalLabel">
                            <i class="fas fa-history me-2"></i>
                            Past Details
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">

                        <!-- Filters -->
                        <div class="row" style="margin-bottom: 12px;">
                            <div class="col-md-2" style="padding-right: 5px;">
                                <select class="form-control select2" id="history-type-filter" style="height: 34px;">
                                    <option value="all">All</option>
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                            <div class="col-md-4" style="padding-right: 5px; padding-left: 5px;">
                                {!! Form::text('date_range', null, [
                                    'class' => 'form-control',
                                    'readonly',
                                    'id' => 'history-date-range',
                                    'placeholder' => __('lang_v1.select_a_date_range'),
                                    'style' => 'height: 34px;',
                                ]) !!}
                                <input type="hidden" id="start_date" value="">
                                <input type="hidden" id="end_date" value="">
                            </div>
                            <div class="col-md-3" style="padding-right: 5px; padding-left: 5px;">
                                <input type="text" id="history-search" class="form-control" placeholder="Search..." style="height: 34px;">
                            </div>
                            <div class="col-md-3" style="padding-left: 5px;">
                                <div class="input-group" style="height: 34px;">
                                    <span class="input-group-addon" style="height: 34px; line-height: 22px;">Show</span>
                                    <select class="form-control" id="history-per-page" style="height: 34px;">
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="500">500</option>
                                    </select>
                                    <span class="input-group-addon" style="height: 34px; line-height: 22px;">entries</span>
                                </div>
                            </div>
                        </div>

                        <!-- History Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle" id="history-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Starting Meter</th>
                                        <th>Closing Meter</th>
                                        <th>Type</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Net Profit</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody id="history-body">
                                    <!-- Rows loaded dynamically -->
                                </tbody>
                            </table>
                            <div id="history-pagination" class="mt-3 text-center"></div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>

        <div class="modal fade" id="myAutoSubscriptionModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 430px;">
                <div class="modal-content">
                    <form method="POST" action="{{ route('myauto.subscription.startCheckout') }}" id="my_auto_subscription_form">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fa fa-credit-card"></i> Add Subscription</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>My Auto Number</label>
                                <input type="text" class="form-control" value="{{ $setting->auto_number }}" readonly>
                            </div>
                            <div class="form-group">
                                <label>Package</label>
                                <select class="form-control select2" name="package_id" id="my_auto_package_id" style="width: 100%;">
                                    <option value="">Please select</option>
                                    @foreach ($my_auto_packages as $package_id => $package_name)
                                        <option value="{{ $package_id }}">{{ $package_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Subscription Cycle</label>
                                <select class="form-control select2" name="subscription_cycle" id="my_auto_subscription_cycle" style="width: 100%;">
                                    <option value="">Please select</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Biannually">Biannually</option>
                                    <option value="Annually">Annually</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount to Auto load</label>
                                <input type="text" class="form-control input_number" name="amount_to_auto_load" id="my_auto_amount_to_auto_load" placeholder="Enter amount">
                            </div>
                            <div id="my-auto-subscription-error" class="text-danger" style="display:none;"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Continue</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Passcode Modal -->
        <div class="modal fade" id="changePasscodeModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa fa-lock"></i> Change Passcode</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>New Passcode</label>
                            <input type="password" id="new-passcode" class="form-control" maxlength="8" placeholder="Enter new passcode (4-8 chars)" inputmode="numeric">
                        </div>
                        <div class="form-group">
                            <label>Re-confirm Passcode</label>
                            <input type="password" id="confirm-passcode" class="form-control" maxlength="8" placeholder="Re-enter passcode" inputmode="numeric">
                        </div>
                        <div id="passcode-change-error" class="text-danger" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="save-passcode-btn" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- LOCK SCREEN OVERLAY -->
        <div id="lock-screen"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; color: #fff; text-align: center; padding-top: 15vh; backdrop-filter: blur(5px);">
            <div
                style="max-width: 400px; margin: auto; background: #222; padding: 40px; border-radius: 15px; border: 1px solid #444; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <h2 style="margin-bottom: 20px; font-weight: bold;">My Auto Home</h2>
                <div class="auto-no" style="margin-bottom: 30px; font-size: 32px; color: #ffca28;">
                    {{ $setting->auto_number }}</div>

                <p style="margin-bottom: 20px; color: #aaa;">Dashboard is locked after 5 mins of inactivity.</p>

                <div class="form-group">
                    <input type="password" id="passcode-input" class="form-control" placeholder="Enter Passcode"
                        style="text-align: center; font-size: 24px; height: 60px; border-radius: 10px; background: #333; color: #fff; border: 1px solid #555;"
                        maxlength="8" inputmode="numeric">
                </div>

                <button id="unlock-btn" class="btn btn-success btn-lg btn-block"
                    style="height: 60px; font-size: 20px; font-weight: bold; border-radius: 10px; margin-top: 20px;">
                    Login / Unlock
                </button>

                <p id="lock-error" style="color: #ff5252; margin-top: 15px; display: none;"></p>
            </div>
        </div>
    </div>
@endsection

        @section('javascript')
            <script>
                $('.select2').select2();

                $('#my_auto_package_id, #my_auto_subscription_cycle').select2({
                    width: '100%',
                    dropdownParent: $('#myAutoSubscriptionModal')
                });

                function renderPagination(pagination) {
                    const container = document.getElementById('history-pagination');
                    container.innerHTML = '';

                    for (let i = 1; i <= pagination.last_page; i++) {
                        container.innerHTML += `
                    <button 
                        class="btn btn-sm ${i === pagination.current_page ? 'btn-primary' : 'btn-outline-primary'} mx-1"
                        onclick="loadHistoryData(${i})">
                        ${i}
                    </button>
                `;
                    }
                }

                let currentPage = 1;

                function loadHistoryData(page = 1) {
                    currentPage = page;

                    const type = document.getElementById('history-type-filter').value;
                    const search = document.getElementById('history-search').value;
                    const start = document.getElementById('start_date').value;
                    const end = document.getElementById('end_date').value;
                    const perPage = document.getElementById('history-per-page').value;

                    const params = new URLSearchParams({
                        type,
                        search,
                        start,
                        end,
                        page,
                        per_page: perPage
                    });

                    fetch(`{{ route('myauto.pastDetails', $todayLog->id) }}?${params}`)
                        .then(res => res.json())
                        .then(response => {
                            const tbody = document.getElementById('history-body');
                            tbody.innerHTML = '';

                            response.data.forEach(row => {
                                tbody.innerHTML += `
                            <tr>
                                <td>${row.date}</td>
                                <td>${row.starting_meter}</td>
                                <td>${row.closing_meter}</td>
                                <td>${row.type}</td>
                                <td class="text-end">${row.amount}</td>
                                <td class="text-end">${row.net_profit}</td>
                                <td>${row.note ?? '-'}</td>
                            </tr>
                        `;
                            });

                            renderPagination(response.pagination);
                        });
                }

                function debounce(func, delay = 600) {
                    let timeout;
                    return function(...args) {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), delay);
                    };
                }

                if ($('#history-date-range').length === 1) {
                    $('#history-date-range').daterangepicker({
                        ranges: ranges,
                        autoUpdateInput: false,
                        locale: {
                            format: moment_date_format
                        }
                    });

                    $('#history-date-range').on('apply.daterangepicker', function(ev, picker) {
                        $(this).val(
                            picker.startDate.format(moment_date_format) + ' ~ ' + picker.endDate.format(
                                moment_date_format)
                        );
                        $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
                        $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
                        loadHistoryData();
                    });

                    $('#history-date-range').on('cancel.daterangepicker', function(ev, picker) {
                        $(this).val('');
                        $('#start_date').val('');
                        $('#end_date').val('');
                        loadHistoryData();
                    });
                }

                document.getElementById('past-details-btn').addEventListener('click', function() {
                    $('#pastDetailsModal').modal('show');
                    
                    // Set default date range to this week
                    const startOfWeek = moment().startOf('week').format(moment_date_format);
                    const endOfWeek = moment().endOf('week').format(moment_date_format);
                    const startOfWeekISO = moment().startOf('week').format('YYYY-MM-DD');
                    const endOfWeekISO = moment().endOf('week').format('YYYY-MM-DD');
                    
                    $('#history-date-range').val(startOfWeek + ' ~ ' + endOfWeek);
                    $('#start_date').val(startOfWeekISO);
                    $('#end_date').val(endOfWeekISO);
                    
                    loadHistoryData(1);
                });

                $('#save-passcode-btn').on('click', function() {
                    const newPasscode = $('#new-passcode').val().trim();
                    const confirmPasscode = $('#confirm-passcode').val().trim();
                    const errorDiv = $('#passcode-change-error');

                    errorDiv.hide().text('');

                    if (!newPasscode) {
                        errorDiv.text('New passcode is required.').show();
                        return;
                    }

                    if (newPasscode.length < 4 || newPasscode.length > 8) {
                        errorDiv.text('Passcode must be between 4 and 8 characters.').show();
                        return;
                    }

                    if (newPasscode !== confirmPasscode) {
                        errorDiv.text('Passcodes do not match.').show();
                        return;
                    }

                    $.ajax({
                        url: "{{ route('myauto.changePasscode') }}",
                        method: 'POST',
                        data: {
                            passcode: newPasscode,
                            passcode_confirmation: confirmPasscode,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function() {
                            $('#changePasscodeModal').modal('hide');
                            $('#new-passcode, #confirm-passcode').val('');
                            toastr.success('Passcode changed successfully.');
                        },
                        error: function(xhr) {
                            const msg = xhr.responseJSON?.message ?? 'Failed to change passcode.';
                            errorDiv.text(msg).show();
                        }
                    });
                });

                document.getElementById('history-search').addEventListener('input', debounce(() => loadHistoryData(1), 500));
                $('#history-type-filter').on('change', () => loadHistoryData(1));
                document.getElementById('history-per-page').addEventListener('change', () => loadHistoryData(1));

                $('#save-auto-btn').on('click', function() {
                    const $btn = $(this);

                    // Prevent double-click and show loading state
                    if ($btn.prop('disabled')) return;

                    $btn.prop('disabled', true)
                       .html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                    const inputs = document.querySelectorAll('.auto-input');

                    const payload = {
                        log_id: inputs[0].dataset.logId,
                        fields: {},
                        notes: {}
                    };

                    let hasChanges = false;

                    inputs.forEach(i => {
                        const rawValue = (i.value || '').replace(/,/g, '').trim();
                        const field = i.dataset.field;

                        if (field === 'current_meter') {
                            const prevMeter = parseFloat(i.dataset.prevMeter || '0');
                            const val = rawValue === '' ? prevMeter : parseFloat(rawValue);

                            if (!isNaN(val)) {
                                payload.fields[field] = val.toFixed(2);
                                if (val !== prevMeter) {
                                    hasChanges = true;
                                }
                            }
                        } else {
                            if (rawValue !== '') {
                                const val = parseFloat(rawValue);

                                if (!isNaN(val) && val >= 0) {
                                    payload.fields[field] = val.toFixed(2);
                                    hasChanges = true;
                                }
                            }

                            if (field === 'expense_5') {
                                const note = document.getElementById('others-note').value.trim();
                                if (note) {
                                    payload.notes['expense_5'] = note;
                                }
                            }
                        }
                    });

                    if (!hasChanges || Object.keys(payload.fields).length === 0) {
                        $btn.prop('disabled', false).html('Save');
                        return toastr.warning('Please enter a changed value before saving.');
                    }

                    fetch("{{ route('myauto.updateMultiple') }}", {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(async response => {
                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.msg || data.message || 'Something went wrong while saving');
                        }

                        return data;
                    })
                    .then(data => {
                        $btn.prop('disabled', false).html('Save');

                        if (!data.success) {
                            toastr.error(data.msg || 'Something went wrong while saving');
                            return;
                        }

                        toastr.success(data.msg);

                        inputs.forEach(i => {
                            const field = i.dataset.field;
                            if (data.fieldValues && Object.prototype.hasOwnProperty.call(data.fieldValues, field)) {
                                i.value = data.fieldValues[field];
                            }
                        });

                        document.getElementById('current-meter-input').dataset.prevMeter = data.fieldValues.current_meter;
                        document.getElementById('others-note').value = payload.notes.expense_5 || '';

                        $('#income').text(data.todayIncome);
                        $('#expenses').text(data.todayExpense);
                        $('#profit').text(data.todayProfit);
                        $('#today-income').text(data.todayIncome);
                        $('#today-expenses').text(data.todayExpense);
                        $('#today-profit').text(data.todayProfit);
                        $('#today-current-meter').text(data.currentMeter);
                        $('#total-current-meter').text(data.currentMeter);
                        $('#today-mileage-val').text(data.todayMileage);
                        $('#today-mileage-val-top').text(data.todayMileage);
                        $('#total-income').text(data.totalIncome);
                        $('#total-expense').text(data.totalExpense);
                        $('#total-profit').text(data.totalProfit);
                        $('#total-mileage').text(data.totalMileage);

                        refreshIncomeModal();
                        refreshExpenseModal();
                    })
                    .catch(error => {
                        $btn.prop('disabled', false).html('Save');
                        console.error('Save error:', error);
                        toastr.error(error.message || 'Something went wrong while saving');
                    });
                });



                $(document).on('click', '#change-passcode-btn, #update-passcode-btn', function() {
                    $('#passcode-change-error').hide().text('');
                    $('#new-passcode, #confirm-passcode').val('');
                    $('#changePasscodeModal').modal('show');
                });

                $('#open_my_auto_subscription_modal').on('click', function() {
                    @if ($my_auto_packages->isEmpty())
                        toastr.error('No My Auto packages are available.');
                        return;
                    @endif

                    $('#my-auto-subscription-error').hide().text('');
                    $('#myAutoSubscriptionModal').modal('show');
                });

                $('#my_auto_subscription_form').on('submit', function(e) {
                    const packageId = $('#my_auto_package_id').val();
                    const packageName = $('#my_auto_package_id option:selected').text();
                    const subscriptionCycle = $('#my_auto_subscription_cycle').val();
                    const amountToAutoLoad = $('#my_auto_amount_to_auto_load').val();
                    const errorDiv = $('#my-auto-subscription-error');

                    errorDiv.hide().text('');

                    if (!packageId) {
                        e.preventDefault();
                        errorDiv.text('Please select a package.').show();
                        return;
                    }

                    if (!subscriptionCycle) {
                        e.preventDefault();
                        errorDiv.text('Please select subscription cycle.').show();
                        return;
                    }

                    $('#selected_my_auto_package_name').text(packageName);
                    $('#selected_my_auto_subscription_cycle').text(subscriptionCycle);
                    $('#selected_my_auto_amount_to_auto_load').text(amountToAutoLoad || 'Not entered');
                });

                $('#new-passcode, #confirm-passcode').on('input', function() {
                    const newPasscode = $('#new-passcode').val();
                    const confirmPasscode = $('#confirm-passcode').val();
                    const errorDiv = $('#passcode-change-error');

                    if (confirmPasscode && newPasscode !== confirmPasscode) {
                        errorDiv.text('Passcodes do not match.').show();
                    } else {
                        errorDiv.hide().text('');
                    }
                });

                // Function to refresh income modal data
                function refreshIncomeModal() {
                    const logId = document.querySelector('.auto-input').dataset.logId;
                    
                    fetch(`{{ route('myauto.todayIncomeDetails', $todayLog->id) }}`)
                        .then(res => res.json())
                        .then(data => {
                            const tbody = document.getElementById('income-details-body');
                            tbody.innerHTML = '';
                            
                            data.details.forEach(item => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${item.date_time}</td>
                                        <td>${item.starting_meter}</td>
                                        <td>${item.closing_meter}</td>
                                        <td class="text-success fw-bold">${item.amount}</td>
                                    </tr>
                                `;
                            });
                            
                            document.getElementById('modal-total').textContent = data.total;
                        });
                }

                // Function to refresh expense modal data
                function refreshExpenseModal() {
                    const logId = document.querySelector('.auto-input').dataset.logId;
                    
                    fetch(`{{ route('myauto.todayExpenseDetails', $todayLog->id) }}`)
                        .then(res => res.json())
                        .then(data => {
                            const tbody = document.getElementById('expense-details-body');
                            tbody.innerHTML = '';
                            
                            data.expenses.forEach(item => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${item.date_time}</td>
                                        <td>${item.starting_meter}</td>
                                        <td>${item.closing_meter}</td>
                                        <td>${item.type}</td>
                                        <td class="text-end fw-bold text-primary">${item.amount}</td>
                                        <td>
                                            ${item.note && item.note.trim() !== '' 
                                                ? `<button class="btn btn-sm btn-outline-primary view-note-btn" data-note="${item.note}">Note</button>`
                                                : '-'
                                            }
                                        </td>
                                    </tr>
                                `;
                            });
                        });
                }

                document.getElementById('today-income-btn').addEventListener('click', function() {
                    $('#todayIncomeModal').modal('show');
                    refreshIncomeModal();
                });

                document.querySelector('.others-input').addEventListener('blur', function(e) {
                    const val = parseFloat((e.target.value || '').replace(/,/g, ''));
                    if (!isNaN(val) && val > 0) {
                        const noteField = document.getElementById('others-note');
                        if (!noteField.value) {
                            const note = prompt('Please enter details for the "Others" expense:');
                            if (note !== null) {
                                noteField.value = note.trim();
                            }
                        }
                    }
                });

                document.getElementById('today-expense-btn').addEventListener('click', function() {
                    $('#todayExpenseModal').modal('show');
                    refreshExpenseModal();
                });

                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('view-note-btn')) {
                        const note = e.target.getAttribute('data-note');
                        document.getElementById('note-content').textContent = note;
                        $('#noteModal').modal('show');
                    }
                });

                // --- INACTIVITY LOCK LOGIC ---
                let inactivityTime = 0;
                const LOCK_TIMEOUT = 5 * 60; // 5 minutes in seconds

                function resetInactivityTimer() {
                    inactivityTime = 0;
                }

                setInterval(function() {
                    if (sessionStorage.getItem('myauto_locked') === 'true') {
                        showLockScreen();
                        return;
                    }

                    inactivityTime++;
                    if (inactivityTime >= LOCK_TIMEOUT) {
                        lockDashboard();
                    }
                }, 1000);


                // Reset timer on any user interaction
                ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evt => {
                    document.addEventListener(evt, resetInactivityTimer, true);
                });

                function lockDashboard() {
                    sessionStorage.setItem('myauto_locked', 'true');
                    showLockScreen();
                }

                function showLockScreen() {
                    if ($('#lock-screen').is(':visible')) return;
                    $('#lock-screen').fadeIn();
                    $('#passcode-input').focus();
                }

                function unlockDashboard() {
                    const passcode = $('#passcode-input').val();

                    $.ajax({
                        url: "{{ route('myauto.verifyPasscode') }}",
                        method: "POST",
                        data: {
                            passcode: passcode,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(data) {
                            if (data.success) {
                                sessionStorage.removeItem('myauto_locked');
                                $('#lock-screen').fadeOut();
                                $('#lock-error').hide();
                                $('#passcode-input').val('');
                                resetInactivityTimer();
                            } else {
                                $('#lock-error').text(data.msg || 'Invalid passcode').show();
                                $('#passcode-input').val('').focus();
                            }
                        },
                        error: function() {
                            $('#lock-error').text('Error verifying passcode. Please try again.').show();
                        }
                    });
                }

                $('#unlock-btn').on('click', unlockDashboard);
                $('#passcode-input').on('keypress', function(e) {
                    if (e.which == 13) unlockDashboard();
                });

                // Check if already locked on page load
                if (sessionStorage.getItem('myauto_locked') === 'true') {
                    showLockScreen();
                }
            </script>
        @endsection
