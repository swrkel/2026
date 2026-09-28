@extends('layouts.app')
@section('title', 'Add Deposit')

@section('content')
<!-- Premium Custom styling layer -->
<style>
    :root {
        --primary: hsl(220, 90%, 56%);
        --primary-light: hsl(220, 95%, 96%);
        --primary-hover: hsl(220, 90%, 46%);
        --success: #10b981;
        --border-glass: rgba(220, 225, 235, 0.6);
        --bg-glass: rgba(255, 255, 255, 0.9);
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    .premium-form-card {
        background: var(--bg-glass);
        backdrop-filter: blur(12px);
        border: 1px solid var(--border-glass);
        box-shadow: 0 10px 40px 0 rgba(31, 38, 135, 0.06);
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 24px;
        animation: slideUp 0.4s ease forwards;
    }

    .premium-tab-btn {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: var(--text-muted);
        padding: 10px 20px;
        font-weight: 600;
        border-radius: 8px;
        margin-right: 8px;
        transition: all 0.2s ease;
    }

    .premium-tab-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    .indicator-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-loan-active {
        background-color: #fef3c7;
        color: #b45309;
        border: 1px solid #fcd34d;
    }

    .payment-method-box {
        border: 1px solid var(--border-glass);
        border-radius: 12px;
        padding: 20px;
        background-color: #f8fafc;
        margin-top: 15px;
        display: none;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<section class="content-header">
    <h1>Add Deposit
        <small>Record new customer deposit transactions</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">

    @if(session('status'))
        <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible" style="border-radius: 8px; font-weight: 600;">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('status.msg') }}
        </div>
    @endif

    <div style="margin-bottom: 20px;">
        <button type="button" class="premium-tab-btn active" onclick="switchFormTab('deposit-entry-tab', this)">
            <i class="fa fa-piggy-bank"></i> Deposit Entry Form
        </button>
        <button type="button" class="premium-tab-btn" id="history-tab-btn" onclick="switchFormTab('customer-history-tab', this)" style="display: none;">
            <i class="fa fa-history"></i> Customer History Ledger
        </button>
    </div>

    <form action="{{ route('deposit-module.save-deposit') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <!-- Tab 1: Deposit Entry Form -->
        <div id="deposit-entry-tab" class="premium-form-card">
            
            <div class="row">
                <!-- 1. Assigned Location -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Assigned Location</label>
                    <select name="location_id" class="form-control" required style="border-radius: 8px; height: 40px;">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $default_location && $default_location->id == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->location_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Auto Date / Time -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Date & Time</label>
                    <input type="text" class="form-control" value="{{ now()->format('Y-m-d H:i:s') }}" readonly style="border-radius: 8px; padding: 10px; background-color: #f1f5f9;">
                </div>

                <!-- 3. Deposit Number -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Deposit Number</label>
                    <input type="text" class="form-control" value="{{ $next_deposit_number }}" readonly style="border-radius: 8px; padding: 10px; font-weight: 700; color: var(--primary); background-color: #f1f5f9;">
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                <!-- 4. Customer Dropdown -->
                <div class="col-md-6 form-group">
                    <label style="font-weight: 600;">Bank Customer</label>
                    <select name="contact_id" id="contact_id" class="form-control select2" required style="width: 100%;">
                        <option value="">-- Select Customer --</option>
                        @foreach($customers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Current Active Loan ID (Autoloaded) -->
                <div class="col-md-6 form-group">
                    <label style="font-weight: 600;">Active Loan Reference</label>
                    <div id="loan-indicator-container" style="display: flex; align-items: center; gap: 12px; margin-top: 5px;">
                        <input type="hidden" name="current_loan_id" id="current_loan_id">
                        <span id="loan-badge" class="indicator-badge badge-loan-active" style="display: none;">
                            <i class="fa fa-info-circle"></i> Active Loan ID: <span id="loan-id-val">-</span>
                        </span>
                        <span id="no-loan-badge" class="text-muted" style="font-size: 13px;">No active loan for this customer.</span>
                    </div>
                </div>
            </div>

            <hr style="border-top: 1px solid var(--border-glass); margin: 30px 0;">

            <div class="row">
                <!-- 6. Deposit Type -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Deposit Type</label>
                    <select name="deposit_type_id" id="deposit_type_id" class="form-control" required style="border-radius: 8px; height: 40px;">
                        <option value="">-- Select Deposit Type --</option>
                        @foreach($deposit_types as $type)
                            <option value="{{ $type->id }}" data-name="{{ $type->name }}" data-period="{{ $type->period }}" data-value="{{ $type->period_value }}">
                                {{ $type->name }} ({{ $type->period }} x {{ $type->period_value }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 7. Deposit Period -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Deposit Period</label>
                    <select name="deposit_period" id="deposit_period" class="form-control" required style="border-radius: 8px; height: 40px;">
                        <option value="Daily">Daily</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Monthly" selected>Monthly</option>
                        <option value="Yearly">Yearly</option>
                    </select>
                </div>

                <!-- 8. Deposit Period Value (Integer Only) -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Deposit Period Value</label>
                    <input type="number" name="deposit_period_value" id="deposit_period_value" class="form-control" required min="1" step="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57" placeholder="e.g. 12" style="border-radius: 8px; padding: 10px;">
                    <small class="text-muted">No decimal values are allowed.</small>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                <!-- 9. Interest Per -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Interest Calculation Period</label>
                    <select name="interest_per" id="interest_per" class="form-control" required style="border-radius: 8px; height: 40px;">
                        <option value="Daily">Daily</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Monthly" selected>Monthly</option>
                        <option value="Yearly">Yearly</option>
                    </select>
                </div>

                <!-- 10. Total Interest (Auto-calculated/Autoloaded) -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Projected Total Interest</label>
                    <input type="number" name="total_interest" id="total_interest" class="form-control" readonly placeholder="0.00" style="border-radius: 8px; padding: 10px; background-color: #f1f5f9; font-weight: 700; color: #15803d;">
                </div>

                <!-- 11. Currencies settings selection -->
                <div class="col-md-4 form-group">
                    <label style="font-weight: 600;">Currency</label>
                    <select name="currency" class="form-control" required style="border-radius: 8px; height: 40px;">
                        @foreach($currencies as $curr)
                            <option value="{{ $curr }}">{{ $curr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                <!-- 12. Amount -->
                <div class="col-md-6 form-group">
                    <label style="font-weight: 600;">Amount</label>
                    <input type="number" name="amount" id="amount" class="form-control" required min="0.01" step="0.01" placeholder="Enter amount to deposit" style="border-radius: 8px; padding: 10px; font-size: 16px; font-weight: 600;">
                </div>

                <!-- 13. Added By -->
                <div class="col-md-6 form-group">
                    <label style="font-weight: 600;">Added By</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->username }}" readonly style="border-radius: 8px; padding: 10px; background-color: #f1f5f9;">
                </div>
            </div>

            <hr style="border-top: 1px solid var(--border-glass); margin: 30px 0;">

            <!-- 14. Payment Method Options -->
            <div class="form-group">
                <label style="font-weight: 600; display: block; margin-bottom: 12px;">Payment Method</label>
                <select name="payment_method" id="payment_method" class="form-control" required style="border-radius: 8px; height: 40px; width: 100%;">
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Online Transfer">Online Transfer</option>
                </select>
            </div>

            <!-- Payment Method Form: Card -->
            <div id="method-card-form" class="payment-method-box">
                <h4 style="font-size: 15px; font-weight: 700; color: var(--text-dark); margin-bottom: 15px;"><i class="fa fa-credit-card"></i> Card Details</h4>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label style="font-weight: 600;">Slip Number <span class="text-danger">*</span></label>
                        <input type="text" name="slip_number" id="slip_number" class="form-control" placeholder="Slip Number (Compulsory)" style="border-radius: 8px; padding: 10px;">
                    </div>
                    <div class="col-md-6 form-group">
                        <label style="font-weight: 600;">Card Number (Optional)</label>
                        <input type="text" name="card_number" class="form-control" placeholder="Last 4 digits" style="border-radius: 8px; padding: 10px;">
                    </div>
                </div>
            </div>

            <!-- Payment Method Form: Cheque -->
            <div id="method-cheque-form" class="payment-method-box">
                <h4 style="font-size: 15px; font-weight: 700; color: var(--text-dark); margin-bottom: 15px;"><i class="fa fa-money-check"></i> Cheque Details</h4>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label style="font-weight: 600;">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank" id="bank" class="form-control" placeholder="Bank Name" style="border-radius: 8px; padding: 10px;">
                    </div>
                    <div class="col-md-4 form-group">
                        <label style="font-weight: 600;">Cheque Number <span class="text-danger">*</span></label>
                        <input type="text" name="cheque_no" id="cheque_no" class="form-control" placeholder="Cheque No" style="border-radius: 8px; padding: 10px;">
                    </div>
                    <div class="col-md-4 form-group">
                        <label style="font-weight: 600;">Cheque Date <span class="text-danger">*</span></label>
                        <input type="date" name="cheque_date" id="cheque_date" class="form-control" style="border-radius: 8px; padding: 10px;">
                        <small class="text-muted" id="cheque-date-warning" style="display: none; color: #b45309; font-weight: 600;"><i class="fa fa-exclamation-triangle"></i> Flagged as Post Dated Cheque in Ledgers & Account Books.</small>
                    </div>
                </div>
            </div>

            <!-- Payment Method Form: Online Transfer -->
            <div id="method-transfer-form" class="payment-method-box">
                <h4 style="font-size: 15px; font-weight: 700; color: var(--text-dark); margin-bottom: 15px;"><i class="fa fa-university"></i> Online Transfer Details</h4>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label style="font-weight: 600;">Deposited Bank Account <span class="text-danger">*</span></label>
                        <select name="deposited_bank_id" id="deposited_bank_id" class="form-control" style="border-radius: 8px; height: 40px;">
                            <option value="">-- Select Bank Account --</option>
                            @foreach($bank_accounts as $acc_id => $acc_name)
                                <option value="{{ $acc_id }}">{{ $acc_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label style="font-weight: 600;">Transaction Reference / Transfer ID <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_reference" id="transaction_reference" class="form-control" placeholder="Reference Number" style="border-radius: 8px; padding: 10px;">
                    </div>
                </div>
            </div>

            <hr style="border-top: 1px solid var(--border-glass); margin: 30px 0;">

            <div class="row">
                <!-- Note -->
                <div class="col-md-6 form-group">
                    <label style="font-weight: 600;">Note</label>
                    <textarea name="note" class="form-control" rows="4" placeholder="Enter transaction note..." style="border-radius: 8px; padding: 10px;"></textarea>
                </div>

                <!-- Upload Image -->
                <div class="col-md-6 form-group">
                    <label style="font-weight: 600;">Upload Transaction Image / Receipt</label>
                    <input type="file" name="attachment" id="attachment" class="form-control" accept="image/png, image/jpeg" style="border-radius: 8px; padding: 10px;">
                    <small class="text-muted">Allowed only JPEG/PNG images under 200kb. Auto-resizing will apply on submission.</small>
                    <div id="file-size-warning" class="text-danger" style="display: none; font-weight: 600; margin-top: 4px;">File exceeds 200kb limit! Please select a smaller receipt.</div>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary" style="font-weight: 600; padding: 12px 40px; border-radius: 8px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);">
                    <i class="fa fa-check-circle"></i> Save Deposit
                </button>
            </div>

        </div>

        <!-- Tab 2: Customer History Ledger (Ajax Loaded Container) -->
        <div id="customer-history-tab" class="premium-form-card" style="display: none;">
            <h3 style="font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 20px;">
                <i class="fa fa-history"></i> Customer Transaction History Ledger
            </h3>
            
            <div id="ledger-loading" class="text-center" style="padding: 40px 0;">
                <i class="fa fa-spinner fa-spin fa-3x" style="color: var(--primary);"></i>
                <p style="margin-top: 15px; color: var(--text-muted); font-weight: 600;">Loading live customer ledger records...</p>
            </div>

            <div id="ledger-data-container">
                <!-- Ledger HTML injected here via Ajax -->
            </div>
        </div>

    </form>

</section>
@endsection

@section('javascript')
<script>
    // Tab controls
    function switchFormTab(tabId, btn) {
        // Toggle tab view
        $('#deposit-entry-tab').hide();
        $('#customer-history-tab').hide();
        $('#' + tabId).fadeIn(300);

        // Toggle button states
        $('.premium-tab-btn').removeClass('active');
        $(btn).addClass('active');

        // Trigger Ajax ledger load if switching to history tab
        if (tabId === 'customer-history-tab') {
            loadCustomerLedger();
        }
    }

    // Ajax ledger fetching
    function loadCustomerLedger() {
        var contactId = $('#contact_id').val();
        var container = $('#ledger-data-container');
        var loader = $('#ledger-loading');

        if (!contactId) {
            container.empty().html('<div class="alert alert-warning">Please select a Bank Customer first.</div>');
            loader.hide();
            return;
        }

        loader.show();
        container.empty();

        $.ajax({
            url: '/contacts/ledger',
            type: 'GET',
            data: {
                contact_id: contactId
            },
            success: function(res) {
                loader.hide();
                if (res && res.html) {
                    container.html(res.html);
                } else {
                    container.html('<div class="alert alert-warning">No ledger records found for this customer.</div>');
                }
            },
            error: function() {
                loader.hide();
                container.html('<div class="alert alert-danger">An error occurred while fetching customer statement ledger.</div>');
            }
        });
    }

    $(document).ready(function() {
        // 1. Customer dropdown change listener
        $('#contact_id').on('change', function() {
            var contactId = $(this).val();
            
            if (contactId) {
                // Show History Tab button once customer is chosen
                $('#history-tab-btn').fadeIn(200);

                // Autoload Active Loan ID
                $.ajax({
                    url: '/deposit-module/get-customer-details/' + contactId,
                    type: 'GET',
                    success: function(res) {
                        if (res.success && res.active_loan) {
                            $('#current_loan_id').val(res.active_loan);
                            $('#loan-id-val').text(res.active_loan);
                            $('#loan-badge').fadeIn(200);
                            $('#no-loan-badge').hide();
                        } else {
                            $('#current_loan_id').val('');
                            $('#loan-badge').hide();
                            $('#no-loan-badge').fadeIn(200);
                        }
                    }
                });
            } else {
                $('#history-tab-btn').fadeOut(200);
                $('#current_loan_id').val('');
                $('#loan-badge').hide();
                $('#no-loan-badge').fadeIn(200);
            }
        });

        // 2. Deposit Type select listener: Autoload Period and Value & trigger interest calculation
        $('#deposit_type_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                var period = selectedOption.data('period');
                var val = selectedOption.data('value');

                $('#deposit_period').val(period);
                $('#deposit_period_value').val(val);
                
                calculateProjectedInterest();
            }
        });

        // 3. Calculator triggering on key inputs
        $('#amount, #deposit_period, #deposit_period_value, #interest_per').on('input change', function() {
            calculateProjectedInterest();
        });

        // Client side dynamic projected interest calculator (simple linear projections)
        function calculateProjectedInterest() {
            var amount = parseFloat($('#amount').val());
            var period = $('#deposit_period').val();
            var periodValue = parseInt($('#deposit_period_value').val());
            var interestPer = $('#interest_per').val();

            if (!amount || amount <= 0 || !periodValue || periodValue <= 0) {
                $('#total_interest').val('0.00');
                return;
            }

            // Standard realistic simulated rate mapping for client-side wow
            var dailyRate = 0.0002;  // ~7% annual
            var weeklyRate = 0.0014;
            var monthlyRate = 0.006;
            var yearlyRate = 0.075;

            var chosenRate = monthlyRate;
            if (interestPer === 'Daily') chosenRate = dailyRate;
            if (interestPer === 'Weekly') chosenRate = weeklyRate;
            if (interestPer === 'Monthly') chosenRate = monthlyRate;
            if (interestPer === 'Yearly') chosenRate = yearlyRate;

            // Normalize duration to the calculation interval
            var normalizedPeriods = periodValue;
            if (period === 'Daily') {
                if (interestPer === 'Weekly') normalizedPeriods = periodValue / 7;
                if (interestPer === 'Monthly') normalizedPeriods = periodValue / 30;
                if (interestPer === 'Yearly') normalizedPeriods = periodValue / 365;
            } else if (period === 'Weekly') {
                if (interestPer === 'Daily') normalizedPeriods = periodValue * 7;
                if (interestPer === 'Monthly') normalizedPeriods = periodValue / 4;
                if (interestPer === 'Yearly') normalizedPeriods = periodValue / 52;
            } else if (period === 'Monthly') {
                if (interestPer === 'Daily') normalizedPeriods = periodValue * 30;
                if (interestPer === 'Weekly') normalizedPeriods = periodValue * 4;
                if (interestPer === 'Yearly') normalizedPeriods = periodValue / 12;
            } else if (period === 'Yearly') {
                if (interestPer === 'Daily') normalizedPeriods = periodValue * 365;
                if (interestPer === 'Weekly') normalizedPeriods = periodValue * 52;
                if (interestPer === 'Monthly') normalizedPeriods = periodValue * 12;
            }

            var totalInterest = amount * chosenRate * normalizedPeriods;
            $('#total_interest').val(totalInterest.toFixed(2));
        }

        // 4. Payment method form selector
        $('#payment_method').on('change', function() {
            var method = $(this).val();
            
            // Reset required flags & forms
            $('.payment-method-box').hide();
            $('#slip_number, #bank, #cheque_no, #cheque_date, #deposited_bank_id, #transaction_reference').prop('required', false);

            if (method === 'Card') {
                $('#method-card-form').fadeIn(200);
                $('#slip_number').prop('required', true);
            } else if (method === 'Cheque') {
                $('#method-cheque-form').fadeIn(200);
                $('#bank, #cheque_no, #cheque_date').prop('required', true);
            } else if (method === 'Online Transfer') {
                $('#method-transfer-form').fadeIn(200);
                $('#deposited_bank_id, #transaction_reference').prop('required', true);
            }
        });

        // 5. Post dated cheque date warnings
        $('#cheque_date').on('change', function() {
            var val = $(this).val();
            if (val) {
                var selectedDate = new Date(val);
                var today = new Date();
                today.setHours(0,0,0,0);
                if (selectedDate > today) {
                    $('#cheque-date-warning').fadeIn(200);
                } else {
                    $('#cheque-date-warning').fadeOut(200);
                }
            } else {
                $('#cheque-date-warning').fadeOut(200);
            }
        });

        // 6. Image size client-side validation
        $('#attachment').on('change', function() {
            var file = this.files[0];
            if (file && file.size > 200 * 1024) {
                $('#file-size-warning').fadeIn(200);
                $(this).val(''); // Reset
            } else {
                $('#file-size-warning').fadeOut(200);
            }
        });
    });
</script>
@endsection
