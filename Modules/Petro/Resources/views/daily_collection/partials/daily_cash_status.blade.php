@php
use App\Business;
$business_id = request()->session()->get('user.business_id');
$business = Business::find($business_id);
$businessLocation = DB::table('business_locations')->where('business_id', $business_id)->first();
$businessCurrencyPrecise = $business->currency_precision ?? 2; 
@endphp

<style>
    .cash-details {
        padding: 10px;
        height: 100%;
    }

    .cash-heading {
        font-weight: bold;
        margin-bottom: 15px;
        font-size: 16px;
        color: #333;
    }

    .status-details {
        display: flex;
        gap: 15px;
    }

    .status-details > div {
        flex: 1;
    }

    .status-details p {
        margin: 0 0 8px;
        font-size: 14px;
        color: #333;
    }

    #cash_given_users div,
    #cash_given_dates div,
    #cash_given_amounts div {
        margin-bottom: 12px;
        font-size: 13px;
    }

    #cash_given_amounts div {
        text-align: right;
        padding-right: 8px;
    }

    #cash_given_total {
        font-size: 14px;
        margin-top: 5px;
        text-align: right;
        padding-right: 8px;
        font-weight: bold;
        color: #000;
    }

    .mt-4 {
        margin-top: 1rem !important;
    }

    .mt-2 {
        margin-top: 0.5rem !important;
    }
</style>

<style>
    /* Professional transaction card styling */
    .transaction-card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        border-radius: 6px;
        background-color: #fff;
        overflow: hidden;
    }

    .transaction-header {
        border-bottom: 1px solid #e5e7eb;
        padding: 10px 16px;
        font-weight: 600;
        font-size: 15px;
        color: #111827;
        background-color: #f9fafb;
    }

    .transaction-list {
        max-height: 320px; /* optional scroll area */
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #d1d5db #f3f4f6;
    }

    .transaction-list::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .transaction-list::-webkit-scrollbar-track {
        background: #f3f4f6;
        border-radius: 3px;
    }

    .transaction-list::-webkit-scrollbar-thumb {
        background-color: #d1d5db;
        border-radius: 3px;
    }

    .transaction-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s ease;
    }

    .transaction-item:last-child {
        border-bottom: none;
    }

    .transaction-item:hover {
        background-color: #f9fafb;
    }

    .transaction-user {
        flex: 1;
        font-weight: 500;
        color: #111827;
        display: flex;
        align-items: center;
        min-width: 0; /* ensures text truncation works */
    }

    .transaction-user-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #e5e7eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        color: #6b7280;
        font-size: 12px;
        font-weight: 600;
        flex-shrink: 0;
    }

    .transaction-amount {
        width: 100px;
        text-align: right;
        font-weight: 600;
        color: #1e40af;
        font-family: 'Roboto Mono', monospace;
        white-space: nowrap;
    }

    .transaction-date {
        width: 120px;
        text-align: right;
        font-size: 13px;
        color: #6b7280;
        white-space: nowrap;
    }

    .badge-light-blue {
        background-color: #eff6ff;
        color: #1d4ed8;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 9999px;
        font-size: 12px;
    }

    .text-blue-600 {
        color: #2563eb;
    }

    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
</style>


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li>
                        <a href="#">@lang('petro::lang.daily_cash_status')</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content" style="padding: 4px!important;" id="daily-cash-status-section">
    <div class="row">
        <div class="col-md-12">

            <!-- Filters -->
            @component('components.filters', ['title' => __('report.filters')])
                <div class="row justify-content-between align-items-end">
                    
                    <!-- Date Picker -->
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group d-flex align-items-center gap-2">
                            {!! Form::label('cash_transaction_date', __('petro::lang.date') . ':', ['class' => 'mb-0']) !!}
                            {!! Form::text('cash_transaction_date', \Carbon\Carbon::now()->format('Y-m-d'), [
                                'class' => 'form-control date-picker',
                                'placeholder' => __('petro::lang.select_a_date'),
                                'autocomplete' => 'off',
                                'id' => 'cash_transaction_date',
                                'style' => 'width: auto; max-width: 100%;',
                            ]) !!}
                        </div>
                    </div>

                    <!-- Shift -->
                    <div class="col-md-3 col-sm-12">
                        <div class="col-12 d-flex gap-4 items-center" style="align-items: center; justify-content: space-evenly;">
                            <div class="form-group">
                                {!! Form::label('shift', __('petro::lang.shift') . ':') !!}
                                {!! Form::select('shift', array_combine($dailyCashShiftNumbers, $dailyCashShiftNumbers), null, [
                                    'class' => 'form-control',
                                    'placeholder' => 'Select Shift No',
                                    'id' => 'shift',
                                ]) !!}
                            </div>
                            <i class="fa fa-refresh cursor-pointer" id="refreshShift"></i>
                            <p id="refreshShiftText"></p>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="col-md-4 col-sm-12">
                        <p style="text-align: end; font-size: 16px; font-weight: 600;">
                            Daily Cash Status No: 1
                        </p>
                    </div>
                </div>
            @endcomponent

            <!-- Balance Display -->
            <div style="margin-top:10px; border:1px solid #2974A6; padding:40px; border-radius:9px; position:relative;">
                <div class="row d-flex flex-wrap overflow-none" style="gap: 10px; width: 100%;">

                    <!-- Cash Collection -->
                    <div class="flex-fill">
                        <div class="form-group">
                            {!! Form::label('cash_collection', __('petro::lang.cash_collection') . ':', ['class' => 'status-label']) !!}
                            {!! Form::text('cash_collection', null, [
                                'class' => 'form-control status-text',
                                'id' => 'cash_collection',
                                'readonly' => true,
                                'oninput' => 'calculateBalance()',
                            ]) !!}
                        </div>
                    </div>

                    <!-- Customer Payment Cash -->
                    <div class="flex-fill">
                        <div class="form-group" style="width: 170px;">
                            {!! Form::label('customer_payment_cash', __('petro::lang.customer_payment_cash') . ':', ['class' => 'status-label']) !!}
                            {!! Form::text('customer_payment_cash', null, [
                                'class' => 'form-control status-text',
                                'id' => 'customer_payment_cash',
                                'readonly' => true,
                                'oninput' => 'calculateBalance()',
                            ]) !!}
                        </div>
                    </div>

                    <!-- Cash Expenses -->
                    <div class="flex-fill">
                        <div class="form-group">
                            {!! Form::label('cash_expenses', __('petro::lang.cash_expense') . ':', ['class' => 'status-label']) !!}
                            {!! Form::text('cash_expenses', null, [
                                'class' => 'form-control status-text',
                                'id' => 'cash_expense',
                                'readonly' => true,
                                'oninput' => 'calculateBalance()',
                            ]) !!}
                        </div>
                    </div>

                    <!-- Cash Deposit -->
                    <div class="flex-fill">
                        <div class="form-group">
                            {!! Form::label('cash_deposit', __('petro::lang.cash_deposit') . ':', ['class' => 'status-label']) !!}
                            {!! Form::text('cash_deposit', null, [
                                'class' => 'form-control status-text',
                                'id' => 'cash_deposit',
                                'readonly' => true,
                                'oninput' => 'calculateBalance()',
                            ]) !!}
                        </div>
                    </div>

                    <!-- Cash Total Given -->
                    <div class="flex-fill">
                        <div class="form-group">
                            {!! Form::label('cash_total_given', __('petro::lang.cash_total_given') . ':', ['class' => 'status-label']) !!}
                            {!! Form::text('cash_total_given', null, [
                                'class' => 'form-control status-text',
                                'id' => 'cash_total_given',
                                'readonly' => true,
                                'oninput' => 'calculateBalance()',
                            ]) !!}
                        </div>
                    </div>

                    <!-- Balance in Hand -->
                    <div class="flex-fill">
                        <div class="form-group">
                            {!! Form::label('balance_in_hand', __('petro::lang.balance_in_hand') . ':', [
                                'class' => 'status-label',
                                'style' => 'color: #0000FF;',
                            ]) !!}
                            {!! Form::text('balance_in_hand', null, [
                                'class' => 'form-control status-text',
                                'readonly' => true,
                                'id' => 'balance_in_hand',
                                'style' => 'font-weight: bold;',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <!-- Cash to Settle Button -->
                <div class="row" style="margin-top: 20px; margin-left: 10px;">
                    <div class="form-group">
                        <button type="button" class="btn mt-2" style="background-color: #005A9C; color: aliceblue;" id="cashToSettleBtn" >
                            <i class="fas fa-coins mr-2"></i> {{ __('petro::lang.cash_to_settle') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Section -->
        <div id="printSection" style="display: none;">
            <h2 style="color: #005A9C; text-align: center;">Cash Settlement Report</h2>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Field</th>
                    <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Amount</th>
                </tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">Cash Collection</td><td id="print_cash_collection" style="border:1px solid #ddd;padding:8px;"></td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">Customer Payment Cash</td><td id="print_customer_payment_cash" style="border:1px solid #ddd;padding:8px;"></td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">Cash Expenses</td><td id="print_cash_expense" style="border:1px solid #ddd;padding:8px;"></td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">Cash Deposit</td><td id="print_cash_deposit" style="border:1px solid #ddd;padding:8px;"></td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">Cash Total Given</td><td id="print_cash_total_given" style="border:1px solid #ddd;padding:8px;"></td></tr>
                <tr>
                    <td style="border:1px solid #ddd;padding:8px; font-weight:bold; color:#0000FF;">Balance in Hand</td>
                    <td id="print_balance_in_hand" style="border:1px solid #ddd;padding:8px; font-weight:bold;"></td>
                </tr>
            </table>
            <div style="margin-top: 20px; text-align: center; font-style: italic;">
                Printed on: <span id="print_date"></span>
            </div>
        </div>
    </div>

    <!-- Cash Details & Transactions -->
    <div class="row" style="margin-top: 20px;">
        <!-- Pump Operator Cash -->
        <div class="col-md-3 cash-details" style="border-right:1px solid #71ADBC;">
            <p class="cash-heading">Detail Cash Collection</p>
            <div class="status-details">
                <div>
                    <p>Pump Operator</p>
                    <div id="operator_names"><p>No operators assigned.</p></div>
                    <p>Total</p>
                </div>
                <div>
                    <p>Amount</p>
                    <div id="per_operator_total"></div>
                    <p id="pump_operators_total" style="color:brown;font-weight:bolder"></p>
                </div>
            </div>
        </div>

        <!-- Customer Payments -->
        <div class="col-md-3 cash-details" style="border-right:1px solid #71ADBC;">
            <p class="cash-heading">Detail Customer Payments</p>
            <div class="status-details">
                <div>
                    <p>Customer</p>
                    <div class="mt-4" id="customers_names"></div>
                    <p>Total</p>
                </div>
                <div>
                    <p>Amount</p>
                    <div class="mt-4" id="customer_expense"></div>
                    <p class="mt-2" id="customer_total" style="color:brown;font-weight:bolder"></p>
                </div>
            </div>
        </div>

        <!-- Cash Expenses -->
        <div class="col-md-3 cash-details">
            <p class="cash-heading">Cash Expenses</p>
            <div class="status-details">
                <div>
                    <p>Expenses</p>
                    <div class="mt-4" id="cash_expense_name"></div>
                    <p>Total</p>
                </div>
                <div>
                    <p>Amount</p>
                    <div class="mt-4" id="cash_expense_cost"></div>
                    <p class="mt-2" id="cash_expense_total" style="color:brown;font-weight:bolder"></p>
                </div>
            </div>
        </div>

        <!-- Cash Given Amount -->
        <div class="col-md-3 cash-details" style="border-left:1px solid #e5e7eb; background:#f9fafb; padding:14px 20px 20px 20px;">
            <div class="d-flex justify-content-between align-items-center mb-4" style="justify-content:center!important;">
                <h5 style="font-weight:bold; font-size:larger;">
                    <i class="fas fa-money-bill-wave mr-2 text-blue-600"></i> Cash Given Amount
                </h5>
                <span class="badge badge-light-blue" id="transaction-count">0</span>
            </div>

            <div class="transaction-card bg-white rounded-lg shadow-xs" style="border:1px solid #e5e7eb;">
                <!-- Header -->
                <div class="transaction-header d-flex px-4 py-3 border-bottom" style="background:#f3f4f6;">
                    <div class="flex-fill text-xs text-gray-500 uppercase">Issued By</div>
                    <div class="text-right text-xs text-gray-500 uppercase" style="width:100px;">Amount</div>
                    <div class="text-right text-xs text-gray-500 uppercase" style="width:120px;">Date</div>
                </div>

                <!-- List -->
                <div class="transaction-list" style="max-height:250px; overflow-y:auto;">
                    <div id="cash_given_transactions">
                        <!-- Empty state -->
                        <div class="text-center py-5">
                            <i class="fas fa-money-bill-transfer fa-2x text-gray-300 mb-3"></i>
                            <p class="text-gray-500 mb-0">No cash disbursements</p>
                            <small class="text-gray-400">Transactions will appear here</small>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="transaction-footer d-flex px-4 py-3 border-top" style="background:#f3f4f6;">
                    <div class="flex-fill text-gray-700">Total Disbursed</div>
                    <div id="cash_given_total" class="font-semibold text-blue-600" style="width:100px;text-align:right;">0.00</div>
                    <div style="width:120px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Close Shift -->
    <div>
        <button class="close-btn" id="close-shift-btn">Close Shift</button>
    </div>

    <!-- Print Alert Modal -->
    <div class="modal fade" id="printAlertModal" tabindex="-1" role="dialog" aria-labelledby="printAlertLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="printAlertLabel"><i class="fa fa-print mr-2"></i> Printing Report</h5>
                </div>
                <div class="modal-body text-center">
                    <img src="/images/printing.png" alt="Printing" style="width:60px; margin-bottom:15px;">
                    <p>Your report is being prepared for printing.<br>Please wait until the print dialog appears.</p>
                </div>
            </div>
        </div>
    </div>
</section>


<script>
    function updateTransactionsDisplay() {
        const container = $('#cash_given_transactions');
        const totalElement = $('#cash_given_total');
        const countElement = $('#transaction-count');
        const currentShift = $('#shift').val();

        const transactions = cashGivenTransactions.filter(t => t.shiftNumber == currentShift);

        container.empty();

        if (transactions.length === 0) {
            container.append(`
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-money-bill-transfer fa-2x text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 mb-0">No cash disbursements</p>
                    <small class="text-gray-400">Transactions will appear here</small>
                </div>
            `);

            totalElement.text('0.00');
            countElement.text('0');
            return;
        }

        // Sort by date (newest first)
        transactions.sort((a, b) => new Date(b.datetime) - new Date(a.datetime));

        let total = 0;

        transactions.forEach(t => {
            const date = new Date(t.datetime);
            const formattedDate = date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });
            const formattedTime = date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            total += parseFloat(t.amount);

            const username = t.user?.name || t.user || 'Unknown';
            const initials = username.split(' ').map(n => n[0]).join('').toUpperCase();

            container.append(`
                <div class="transaction-item">
                    <div class="transaction-user">
                        <span class="transaction-user-avatar">${initials}</span>
                        ${username}
                    </div>
                    <div class="transaction-amount">
                        ${Number(t.amount).toLocaleString(undefined, {
                            minimumFractionDigits: {{ $businessCurrencyPrecise ?? 2 }},
                            maximumFractionDigits: {{ $businessCurrencyPrecise ?? 2 }}
                        })}
                    </div>
                    <div class="transaction-date">
                        <div>${formattedDate}</div>
                        <small class="text-gray-400">${formattedTime}</small>
                    </div>
                </div>
            `);
        });

        totalElement.text(
            total.toLocaleString(undefined, {
                minimumFractionDigits: {{ $businessCurrencyPrecise ?? 2 }},
                maximumFractionDigits: {{ $businessCurrencyPrecise ?? 2 }}
            })
        );
        countElement.text(transactions.length);
    }

    // ✅ Call function after DOM is ready
    $(document).ready(function() {
        updateTransactionsDisplay();
        $('#shift').change(updateTransactionsDisplay);
    });
</script>


<script>
    // Global variables
    let cashGivenTransactions = JSON.parse(localStorage.getItem('cashGivenTransactions')) || [];
    let isPrinting = false;
    let shiftData = {};
    let transactionsForPrinting = [];

    // Helper: parse numeric safely
    function toNumber(value) {
        if (!value) return 0;
        // Remove ALL non-numeric except . and -
        value = value.toString().replace(/,/g, '').trim();
        return parseFloat(value) || 0;
    }


    // Initialize form
    function initializeForm() {
        const shiftNumber = $('#shift').val();
        if (!shiftNumber) return;

        // Clear display
        $('#cash_given_users, #cash_given_dates, #cash_given_amounts').empty();
        $('#cash_given_total').text('');

        // Load shift data
        const storedData = localStorage.getItem(`shift_${shiftNumber}_data`);
        if (storedData) {
            shiftData = JSON.parse(storedData);
            $('#cash_total_given').val(shiftData.accumulatedBalance || '0.00');
        } else {
            shiftData = {
                shiftNumber,
                accumulatedBalance: 0,
                values: {
                    cash_collection: 0,
                    customer_payment_cash: 0,
                    cash_expense: 0,
                    cash_deposit: 0,
                    cash_total_given: 0,
                    balance_in_hand: 0
                },
                transactions: []
            };
        }

        // Reset inputs
        $('#cash_collection, #customer_payment_cash, #cash_expense, #cash_deposit').val('0.00');
        $('#balance_in_hand').val('');
    }

    function saveShiftData() {
        const shiftNumber = $('#shift').val();
        if (!shiftNumber) return;

        shiftData.values = {
            cash_collection: toNumber($('#cash_collection').val()),
            customer_payment_cash: toNumber($('#customer_payment_cash').val()),
            cash_expense: toNumber($('#cash_expense').val()),
            cash_deposit: toNumber($('#cash_deposit').val()),
            cash_total_given: toNumber($('#cash_total_given').val()),
            balance_in_hand: toNumber($('#balance_in_hand').val())
        };

        localStorage.setItem(`shift_${shiftNumber}_data`, JSON.stringify(shiftData));
    }

    function loadShiftData() {
        const shiftNumber = $('#shift').val();
        if (!shiftNumber) return;

        const savedData = localStorage.getItem(`shift_${shiftNumber}_data`);
        if (savedData) {
            shiftData = JSON.parse(savedData);

            // Update fields
            $('#cash_collection').val(shiftData.values.cash_collection.toFixed({{ $businessCurrencyPrecise }}));
            $('#customer_payment_cash').val(shiftData.values.customer_payment_cash.toFixed({{ $businessCurrencyPrecise }}));
            $('#cash_expense').val(shiftData.values.cash_expense.toFixed({{ $businessCurrencyPrecise }}));
            $('#cash_deposit').val(shiftData.values.cash_deposit.toFixed({{ $businessCurrencyPrecise }}));
            $('#cash_total_given').val(shiftData.values.cash_total_given.toFixed({{ $businessCurrencyPrecise }}));
            $('#balance_in_hand').val(shiftData.values.balance_in_hand.toFixed({{ $businessCurrencyPrecise }}));

            cashGivenTransactions = shiftData.transactions || [];
            updateCashGivenDisplay();
        }
    }

    function addTransaction(transaction) {
        cashGivenTransactions.push(transaction);
        shiftData.transactions = cashGivenTransactions;
        saveShiftData();
        updateCashGivenDisplay();
    }

    // Display transactions
    function updateCashGivenDisplay() {
        const currentShift = $('#shift').val();
        const transactions = cashGivenTransactions.filter(t => t.shiftNumber == currentShift);
        const container = $('#cash_given_transactions');
        const totalElement = $('#cash_given_total');

        transactionsForPrinting = []; // reset before rebuild
        container.empty();

        if (!transactions.length) {
            container.append('<div class="text-center py-5">No transactions</div>');
            totalElement.text('0.00');
            return;
        }

        transactions.sort((a, b) => new Date(b.datetime) - new Date(a.datetime));

        let total = 0;
        transactions.forEach(t => {
            const date = new Date(t.datetime);
            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});

            total += toNumber(t.amount);

            transactionsForPrinting.push({
                user: t.user,
                amount: t.amount,
                date: formattedDate
            });

            container.append(`
                <div class="transaction-item">
                    <div class="transaction-user">${t.user}</div>
                    <div class="transaction-amount">${toNumber(t.amount).toFixed({{ $businessCurrencyPrecise }})}</div>
                    <div class="transaction-date">${formattedDate}</div>
                </div>
            `);
        });

        totalElement.text(total.toFixed({{ $businessCurrencyPrecise }}));
        shiftData.transactions = cashGivenTransactions;
        saveShiftData();
    }

    // Balance calculation
    function calculateBalance() {
        const cashCollection = toNumber($('#cash_collection').val());
        const customerPaymentCash = toNumber($('#customer_payment_cash').val());
        const cashExpenses = toNumber($('#cash_expense').val());
        const cashDeposit = toNumber($('#cash_deposit').val());
        const cashTotalGiven = toNumber($('#cash_total_given').val());

        const balance = (cashCollection + customerPaymentCash) - (cashExpenses + cashDeposit + cashTotalGiven);
        const balanceValue = balance.toFixed({{ $businessCurrencyPrecise }});

        $('#balance_in_hand').val(balanceValue);

        const shiftNumber = $('#shift').val();
        if (shiftNumber) {
            localStorage.setItem(`balance_shift_${shiftNumber}`, balanceValue);
        }

        return balance;
    }

    // Load saved balance
    function loadBalance() {
        const shiftNumber = $('#shift').val();
        if (shiftNumber) {
            const savedBalance = localStorage.getItem(`balance_shift_${shiftNumber}`);
            if (savedBalance) {
                $('#balance_in_hand').val(savedBalance);
            }
        }
    }

    // Print helpers
    function showPrintAlert(callback) {
        $('#printAlertModal').modal('show');
        setTimeout(() => {
            callback();
            $('#printAlertModal').modal('hide');
        }, 1200);
    }

    function printReceipt(values, totalGiven, finalBalance) {
        $('#print_cash_collection').text(values.cashCollection.toFixed({{ $businessCurrencyPrecise }}));
        $('#print_customer_payment_cash').text(values.customerPaymentCash.toFixed({{ $businessCurrencyPrecise }}));
        $('#print_cash_expense').text(values.cashExpenses.toFixed({{ $businessCurrencyPrecise }}));
        $('#print_cash_deposit').text(values.cashDeposit.toFixed({{ $businessCurrencyPrecise }}));
        $('#print_cash_total_given').text(totalGiven.toFixed({{ $businessCurrencyPrecise }}));
        $('#print_balance_in_hand').text(finalBalance.toFixed({{ $businessCurrencyPrecise }}));
        $('#print_date').text(new Date().toLocaleString());

        const printSection = $('#printSection');
        printSection.show();
        setTimeout(() => {
            window.print();
            printSection.hide();
        }, 100);
    }

    // Settlement button
    $('#cashToSettleBtn').click(function() {
        const values = {
            cashCollection: toNumber($('#cash_collection').val()),
            customerPaymentCash: toNumber($('#customer_payment_cash').val()),
            cashExpenses: toNumber($('#cash_expense').val()),
            cashDeposit: toNumber($('#cash_deposit').val()),
            cashTotalGiven: toNumber($('#cash_total_given').val())
        };


        const currentBalance = (values.cashCollection + values.customerPaymentCash) -
                               (values.cashExpenses + values.cashDeposit + values.cashTotalGiven);

                            //    console.log(values.cashCollection,values.customerPaymentCash,values.cashExpenses,values.cashDeposit,currentBalance,values.cashTotalGiven,'currentBalance');

        $('#balance_in_hand').val(currentBalance.toFixed({{ $businessCurrencyPrecise }}));

        if (confirm(`Settle amount: ${currentBalance.toFixed(2)}\nConfirm settlement?`)) {
            const newTransaction = {
                shiftNumber: $('#shift').val(),
                user: "{{ Auth::user()->name }}",
                datetime: new Date().toISOString(),
                amount: currentBalance,
                details: values
            };

            cashGivenTransactions.push(newTransaction);
            localStorage.setItem('cashGivenTransactions', JSON.stringify(cashGivenTransactions));

            const newCashTotalGiven = values.cashTotalGiven + currentBalance;
            $('#cash_total_given').val(newCashTotalGiven.toFixed({{ $businessCurrencyPrecise }}));

            const finalBalance = (values.cashCollection + values.customerPaymentCash) -
                                 (values.cashExpenses + values.cashDeposit + newCashTotalGiven);

            $('#balance_in_hand').val(finalBalance.toFixed({{ $businessCurrencyPrecise }}));

            updateCashGivenDisplay();
            printReceipt(values, newCashTotalGiven, finalBalance);

            toastr.success('Settlement completed successfully');
        }
    });

    // Ready
    $(document).ready(function() {
        initializeForm();
        updateCashGivenDisplay();
        loadBalance();

        $('#shift').change(() => {
            
            initializeForm();
            setTimeout(calculateBalance, 200);
        });

        $('#cash_collection, #customer_payment_cash, #cash_expense, #cash_deposit, #cash_total_given')
            .on('input change', calculateBalance);
    });
</script>


<script>
    let cashCollectionStatusData = {};

    $(document).ready(function () {
        // Load data when shift changes
        $('#shift').on('change', function () {
            let shift = $(this).val();

            if (shift) {
                $.ajax({
                    url: "{{ route('petro.daily.cash.status.data') }}",
                    type: 'GET',
                    data: { shift: shift },
                    success: function (response) {
                        cashCollectionStatusData = response;

                        // Cash collection
                        if (response.cash_collection !== undefined) {
                            $('input[name="cash_collection"]').val(response.cash_collection);
                        } else {
                            $('input[name="cash_collection"]').val('');
                        }

                        // Pump Operators
                        if (response.operators) {
                            const operators = response.operators.operators_payment || [];
                            const totalAmount = response.operators.total_amount || 0;

                            let nameHtml = '';
                            let amountHtml = '';

                            if (operators.length > 0) {
                                operators.forEach(op => {
                                    nameHtml += `<p>${op.name}</p>`;
                                    amountHtml += `<p>${formatCurrency(op.total_amount)}</p>`;
                                });
                            } else {
                                nameHtml = '<p>No operators assigned.</p>';
                            }

                            $('#operator_names').html(nameHtml);
                            $('#per_operator_total').html(amountHtml);
                            $('#pump_operators_total').html(`<strong>${formatCurrency(totalAmount)}</strong>`);
                        }

                        // Top summary
                        $('#cash_collection').val(formatCurrency(response.cash_collection || 0));
                        $('#other_income_cash').val(formatCurrency(response.other_income_cash || 0));
                        $('#customer_payment_cash').val(formatCurrency(response.customer_payment_list?.total || 0));
                        $('#cash_expense').val(formatCurrency(response.cash_expenses || 0));
                        $('#cash_deposit').val(formatCurrency(response.cash_deposit || 0));
                        $('#balance_in_hand').val(formatCurrency(response.balance_in_hand || 0));

                        // Pump Operators total (col 1)
                        let pump_total = formatCurrency(response.operators?.total_amount || 0);
                        $('#pump_operators_total').text(pump_total);

                        // Customers (col 2)
                        let customer = formatCurrency(response.customer_payment_list?.total || 0);
                        $('#customer_total').text(customer);
                        $('#customers_names').empty();
                        $('#customer_expense').empty();

                        (response.customer_payment_list?.list || []).forEach(item => {
                            $('#customers_names').append(`<p>${item.customer_name}</p>`);
                            $('#customer_expense').append(`<p>${formatCurrency(item.amount)}</p>`);
                        });

                        // Expenses (col 3)
                        let cash_expense = formatCurrency(response.expense_total?.expense_all || 0);
                        $('#cash_expense_total').text(cash_expense);
                        $('#cash_expense_total2').text(cash_expense);
                        $('#cash_expense_name').empty();
                        $('#cash_expense_cost').empty();

                        (response.expense_total?.expenses || []).forEach(item => {
                            $('#cash_expense_name').append(`<p>${item.expense_name}</p>`);
                            $('#cash_expense_cost').append(`<p>${formatCurrency(item.amount)}</p>`);
                        });
                    },
                    error: function () {
                        $('input[name="cash_collection"]').val('');
                        alert('Error fetching cash collection data.');
                    }
                });
            } else {
                $('input[name="cash_collection"]').val('');
            }
        });

        // Refresh shift list
        $("#refreshShift").click(function () {
            $('#refreshShiftText').text('refreshing...');
            $.ajax({
                url: "{{ action('\Modules\Petro\Http\Controllers\DailyShiftController@fetchOpenShift') }}",
                type: 'GET',
                success: function (response) {
                    $('#refreshShiftText').empty();
                    const $select = $('#shift');
                    $select.empty();
                    $select.append('<option value="">Select an option</option>');
                    $.each(response, function (key, value) {
                        $select.append('<option value="' + value + '">' + value + '</option>');
                    });
                },
                error: function () {
                    alert('Error fetching shift data.');
                }
            });
        });

        // Close shift
        $('#close-shift-btn').on('click', function () {
            if (confirm('Close this shift?')) {
                const shiftId = $('#shift').val();

                if (!shiftId) {
                    alert('Please select a shift number first.');
                    return;
                }

                localStorage.removeItem(`shift_${shiftId}`);
                localStorage.removeItem('cashGivenTransactions');

                $('#close-shift-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> closing...');

                $.ajax({
                    url: "{{ route('petro.daily_shift_status.close') }}",
                    method: 'POST',
                    data: {
                        shift_id: shiftId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        $(`#shift option[value='${shiftId}']`).remove();
                        toastr.success('Shift Saved Successfully');
                        $('#close-shift-btn').prop('disabled', false).html('Close Shift');
                        buildAndPrintShiftReport();
                        // window.location.reload();
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.message || 'Failed to close shift.');
                    }
                });
            }
        });
    });

    function formatCurrency(val) {
        const num = parseFloat(val);
        if (isNaN(num)) return '0.00';
        return num.toLocaleString(undefined, {
            minimumFractionDigits: {{ $businessCurrencyPrecise }},
            maximumFractionDigits: {{ $businessCurrencyPrecise }}
        });
    }
</script>


<script>
function buildAndPrintShiftReport() {
    showPrintAlert(function () {
        const operators = cashCollectionStatusData.operators?.operators_payment || [];
        const operatorTotal = cashCollectionStatusData.operators?.total_amount || 0;

        const expenses = cashCollectionStatusData.expense_total?.expenses || [];
        const expenseTotal = cashCollectionStatusData.expense_total?.expense_all || 0;

        const customerPayments = cashCollectionStatusData.customer_payment_list?.list || [];
        const customerTotal = cashCollectionStatusData.customer_payment_list?.total || 0;

        const shiftNumber = cashCollectionStatusData.shifts || 'N/A';
        const balanceInHand = cashCollectionStatusData.balance_in_hand || 0;

        const cashCollection = cashCollectionStatusData.cash_collection || 0;
        const customerPaymentCash = cashCollectionStatusData.customer_payment || 0;
        const cashExpense = cashCollectionStatusData.cash_expenses || 0;
        const cashDeposit = cashCollectionStatusData.cash_deposit || 0;
        const totalCashGiven = cashCollectionStatusData.cash_total_given || 0;
        const otherIncomeCash = cashCollectionStatusData.other_income_cash || 0;

        const today = new Date().toLocaleString();

        // ✅ Summary section
        let topSummaryHTML = `
            <div style="display:flex;gap:10px;padding:10px;font-family:Arial,sans-serif;font-size:14px;">
                <div style="flex:1;text-align:center;">
                    <label>Cash Collection:</label>
                    <input type="text" value="${formatCurrency(cashCollection)}" readonly style="background:#eee;border:1px solid #ccc;padding:5px;" />
                </div>
                <div style="flex:1;text-align:center;">
                    <label>Customer Payment - Cash:</label>
                    <input type="text" value="${formatCurrency(customerPaymentCash)}" readonly style="background:#eee;border:1px solid #ccc;padding:5px;" />
                </div>
                <div style="flex:1;text-align:center;">
                    <label>Cash Expenses:</label>
                    <input type="text" value="${formatCurrency(cashExpense)}" readonly style="background:#eee;border:1px solid #ccc;padding:5px;" />
                </div>
                <div style="flex:1;text-align:center;">
                    <label>Cash Deposit:</label>
                    <input type="text" value="${formatCurrency(cashDeposit)}" readonly style="background:#eee;border:1px solid #ccc;padding:5px;" />
                </div>
                <div style="flex:1;text-align:center;">
                    <label>Cash Total Given:</label>
                    <input type="text" value="${formatCurrency(totalCashGiven)}" readonly style="background:#eee;border:1px solid #ccc;padding:5px;" />
                </div>
                <div style="flex:1;text-align:center;">
                    <label style="color:blue;">Balance In Hand:</label>
                    <input type="text" value="${formatCurrency(balanceInHand)}" readonly style="background:#eee;border:1px solid #ccc;padding:5px;" />
                </div>
            </div>
        `;

        // ✅ Operators table
        let operatorHTML = operators.map(op => `
            <tr>
                <td>${op.name}</td>
                <td style="text-align:right;">${formatCurrency(op.total_amount)}</td>
            </tr>`).join('');

        // ✅ Expenses table
        let expenseHTML = expenses.map(e => `
            <tr>
                <td>${e.expense_name}</td>
                <td style="text-align:right;">${formatCurrency(e.amount)}</td>
            </tr>`).join('');

        // ✅ Customer table
        let customerHTML = customerPayments.map(c => `
            <tr>
                <td>${c.customer_name} (${c.shift_number || ''})</td>
                <td style="text-align:right;">${formatCurrency(c.amount)}</td>
            </tr>`).join('');

        // ✅ Cash Given transactions
        let transactionHTML = '';
        if (typeof transactionsForPrinting === 'undefined' || transactionsForPrinting.length === 0) {
            transactionHTML = `<tr><td colspan="3" class="no-transactions">No transactions</td></tr>`;
        } else {
            transactionsForPrinting.forEach(t => {
                transactionHTML += `
                    <tr>
                        <td>${t.user}</td>
                        <td style="text-align:right;">${formatCurrency(t.amount)}</td>
                        <td>${t.date}</td>
                    </tr>`;
            });
            // ✅ Total row
            transactionHTML += `
                <tr>
                    <td><strong>Total</strong></td>
                    <td style="text-align:right;"><strong>${formatCurrency(totalCashGiven)}</strong></td>
                    <td></td>
                </tr>`;
        }

        const businessLocation = "{{ $businessLocation->name ?? 'N/A' }}";

        const html = `
            <div style="width:100%;font-family:Arial;font-size:12px;">
                <h1 style="text-align:center;margin-bottom:5px;">${businessLocation}</h1>
                <h2 style="text-align:center;margin-bottom:5px;">Shift Report</h2>
                <p style="text-align:center;margin:0 0 20px;">Shift No: <strong>${shiftNumber}</strong></p>
                <table style="width:100%;margin-bottom:20px;">
                    <tr><td><strong>Date:</strong> ${today}</td></tr>
                </table>

                ${topSummaryHTML}

                <div class="columns">
                    <div class="column">
                        <div class="column-header">Detail Cash Collection</div>
                        <table class="column-table">
                            <tr><th>Pump Operator</th><th>Amount</th></tr>
                            ${operatorHTML}
                            <tr><td class="label">Total</td><td class="total">${formatCurrency(operatorTotal)}</td></tr>
                        </table>
                    </div>
                    <div class="column">
                        <div class="column-header">Detail Customer Payments</div>
                        <table class="column-table">
                            <tr><th>Customer</th><th>Amount</th></tr>
                            ${customerHTML}
                            <tr><td class="label">Total</td><td class="total">${formatCurrency(customerTotal)}</td></tr>
                        </table>
                    </div>
                    <div class="column">
                        <div class="column-header">Cash Expenses</div>
                        <table class="column-table">
                            <tr><th>Expenses</th><th>Amount</th></tr>
                            ${expenseHTML}
                            <tr><td class="label">Total</td><td class="total">${formatCurrency(expenseTotal)}</td></tr>
                        </table>
                    </div>
                    <div class="column">
                        <div class="column-header">Cash Given</div>
                        <table class="cash-given-table">
                            <tr><th>Issued By</th><th>Amount</th><th>Date</th></tr>
                            ${transactionHTML}
                        </table>
                    </div>
                </div>
            </div>
        `;

        // ✅ Open print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>Shift Report</title>
                <style>
                    @media print {
                        @page { size: A3 landscape; margin: 10mm; }
                        body { font-family: Arial, sans-serif; font-size: 11px; padding: 10px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 4px; }
                    }
                    .columns { display:flex; gap:32px; margin-top:32px; }
                    .column { flex:1; background:#fafbfc; border:1px solid #e0e0e0; border-radius:6px; padding:16px; }
                    .column-header { font-size:14pt; font-weight:bold; margin-bottom:12px; text-align:center; }
                    .column-table th, .column-table td, .cash-given-table th, .cash-given-table td { padding:4px 8px; font-size:11pt; }
                    .total { color:#b22222; font-weight:bold; text-align:right; }
                    .no-transactions { color:#888; font-style:italic; text-align:center; }
                </style>
            </head>
            <body>
                ${html}
                <script>
                    window.onload = function() {
                        setTimeout(() => { window.print(); window.close(); }, 300);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    });
}

</script>






         

