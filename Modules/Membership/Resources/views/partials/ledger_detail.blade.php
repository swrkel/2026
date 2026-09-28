<!-- app css -->

@if (!empty($for_pdf))
    <link rel="stylesheet" href="{{ asset('css/app.css?v=' . $asset_v) }}">
@endif
<style>
    .bg_color {
        background: #357ca5;
        font-size: 20px;
        color: #fff;
    }

    .bg-aqua {
        background: #8F3A84;
    }

    .text-center {
        text-align: center;
    }

    #ledger_table th {
        background: #357ca5;
        color: #fff;
    }

    #ledger_table>tbody>tr:nth-child(2n+1)>td,
    #ledger_table>tbody>tr:nth-child(2n+1)>th {
        background-color: rgba(89, 129, 255, 0.3);
    }
</style>

@php
    $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
    use App\TransactionPayment;
    use App\Transaction;
@endphp
<div class="col-md-12 col-sm-12 @if (!empty($for_pdf)) width-100 text-center @endif">
    <p class="text-center"><strong>{{ optional($contact->business)->name ?? '-' }}</strong><br>{{ optional($location_details)->city ?? '' }},
        {{ optional($location_details)->state ?? '' }}<br>{!! optional($location_details)->mobile ?? '' !!}</p>
    <hr>
</div>
<div class="col-md-6 col-sm-6 col-xs-6 @if (!empty($for_pdf)) width-50 f-left @endif">
    <p class="bg_color" style="width: 40%">@lang('lang_v1.to'):</p>
    <p><strong>{{ $contact->name }}</strong><br> {!! $contact->contact_address !!} @if (!empty($contact->email))
            <br>@lang('business.email'): {{ $contact->email }}
        @endif
        <br>@lang('contact.mobile'): {{ $contact->mobile }}
        @if (!empty($contact->tax_number))
            <br>@lang('contact.tax_no'): {{ $contact->tax_number }}
        @endif
    </p>
</div>
<div class="col-md-6 col-sm-6 col-xs-6 text-right align-right @if (!empty($for_pdf)) width-50 f-left @endif">
    <p class=" bg_color" style="margin-top: @if (!empty($for_pdf)) 20px @else 0px @endif; font-weight: 500;">
        @lang('lang_v1.account_summary')</p>
    <table
            class="table table-condensed text-left align-left no-border @if (!empty($for_pdf)) table-pdf @endif">
        <tr>
            <td>@lang('lang_v1.opening_balance') / @lang('contact.fleet_opening_balance')</td>
            <td> <span id="opening_balance">0.00</span>
            </td>
        </tr>
        <tr>
            <td>@lang('lang_v1.total_sales')</td>
            <td>
                <span id="total_sales"></span>
            </td>
        </tr>
        <tr>
            <td>@lang('sale.total_paid')</td>
            <td>
                <span id="total_paid"></span>
            </td>
        </tr>
        <tr>
            <td>@lang('membership::lang.total_points_earned')</td>
            <td>
                <span id="total_earned_points"></span>
            </td>
        </tr>
        <tr>
            <td>@lang('membership::lang.total_points_redeemed')</td>
            <td>
                <span id="total_redeemed_points"></span>
            </td>
        </tr>
        <tr>
            <td>@lang('membership::lang.current_points_balance')</td>
            <td>
                <span id="balance_points"></span>
            </td>
        </tr>
    </table>
</div>
<div class="col-md-12 col-sm-12 @if (!empty($for_pdf)) width-100 @endif">
    <p style="text-align: center !important; float: left; width: 100%;"><strong>@lang('lang_v1.ledger_table_heading', ['start_date' => $start_date, 'end_date' => $end_date])</strong></p>

    <table class="table table-striped @if (!empty($for_pdf)) table-pdf td-border @endif" id="ledger_table">
        <thead>
        <tr class="row-border">
            <th>@lang('lang_v1.date')</th>
            <th>@lang('lang_v1.system_date')</th>
            <th>@lang('sale.location')</th>
            <th>@lang('lang_v1.description')</th>
            <th>@lang('membership::lang.bill_amount')</th>
            <th>@lang('membership::lang.points_earned')</th>
            <th>@lang('membership::lang.points_redeemed')</th>
            <th>@lang('membership::lang.points_balance')</th>
            <th>@lang('lang_v1.payment_method')</th>
        </tr>
        </thead>
        <tbody>
        @php
            $balance = $ledger_details['beginning_balance'];
        @endphp
        @php
            // Account summary values are now pre-calculated by MemberLedgerSummaryService
            // These variables are kept for backward compatibility but are not used for the summary
            $opening_balance = $ledger_details['beginning_balance'] ?? 0;
            $sales = $ledger_details['total_sales'] ?? 0;
            $paid = $ledger_details['total_paid'] ?? 0;
            $total_earned_points_display = $ledger_details['total_earned_points'] ?? 0;
            $total_redeemed_points_display = $ledger_details['total_redeemed_points'] ?? 0;
            // Initialize point balance with the initial balance before start_date
            $point_balance = isset($ledger_details['initial_point_balance']) ? $ledger_details['initial_point_balance'] : 0;
        @endphp

        @foreach ($ledger_transactions as $one)
            @if (empty($one->deleted_at))
                @php
                    $status = null;
                    $pmt_methods = null;
                    $notes = null;
                    $transaction = null;
                    $membershipPoint = null;
                    $pointBalanceDisplay = $point_balance; // Default to current balance
                    // Default bill amount from transaction - use final_total for sales, amount for payments
                    $billAmountDisplay = 0;
                    if (in_array($one->type, ['sell', 'fpos_sale', 'settlement', 'direct_customer_loan', 'property_sale', 'route_operation'])) {
                        // For sales, try to get final_total from transaction
                        $billAmountDisplay = $one->amount ?? 0;
                        if ($one->id) {
                            $tempTransaction = Transaction::find($one->id);
                            if ($tempTransaction && $tempTransaction->final_total) {
                                $billAmountDisplay = $tempTransaction->final_total;
                            }
                        }
                    } else {
                        $billAmountDisplay = $one->amount ?? 0;
                    }
                    $pointsEarnedDisplay = $one->rp_earned ?? 0;
                    $pointsRedeemedDisplay = $one->rp_redeemed ?? 0;
                    $paymentMethodDisplay = '';
                    
                    if ($one->id) {
                        $transaction = Transaction::find($one->id);
                        $membershipPoint = null;
                        
                        // First, try to get membership point if transaction has rp_point_id
                        if ($transaction && $transaction->rp_point_id) {
                            $membershipPoint = \Modules\Membership\Entities\MembershipPoint::find($transaction->rp_point_id);
                        }
                        
                        // If not found by rp_point_id, try to find by matching criteria
                        if (!$membershipPoint && isset($member) && $transaction) {
                            $business_id = request()->session()->get('user.business_id');
                            
                            // membership_points.member_id can be either MembershipMember id or Contact id
                            // Try both possibilities
                            $memberIds = [$member->id];
                            if ($member->contact_id) {
                                $memberIds[] = $member->contact_id;
                            }
                            
                            // Try to find by member_id (MembershipMember or Contact) and date
                            $query = \Modules\Membership\Entities\MembershipPoint::where('business_id', $business_id)
                                ->whereIn('member_id', $memberIds)
                                ->whereDate('date', $one->date);
                            
                            // If transaction has invoice_no or ref_no, try to match by bill_number
                            if ($transaction->invoice_no || $transaction->ref_no) {
                                $billNumber = $transaction->invoice_no ?? $transaction->ref_no;
                                $query->where('bill_number', $billNumber);
                            }
                            
                            $membershipPoint = $query->orderBy('date', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
                            
                            // If still not found, try without bill_number match (just date and member)
                            if (!$membershipPoint) {
                                $membershipPoint = \Modules\Membership\Entities\MembershipPoint::where('business_id', $business_id)
                                    ->whereIn('member_id', $memberIds)
                                    ->whereDate('date', $one->date)
                                    ->orderBy('date', 'desc')
                                    ->orderBy('id', 'desc')
                                    ->first();
                            }
                            
                            // If still not found, try to find the latest membership_point for this member before or on this date
                            if (!$membershipPoint) {
                                $membershipPoint = \Modules\Membership\Entities\MembershipPoint::where('business_id', $business_id)
                                    ->whereIn('member_id', $memberIds)
                                    ->whereDate('date', '<=', $one->date)
                                    ->orderBy('date', 'desc')
                                    ->orderBy('id', 'desc')
                                    ->first();
                            }
                        }
                        
                        if ($membershipPoint) {
                            // Use data from membership_points table
                            $billAmountDisplay = $membershipPoint->amount;
                            $pointsEarnedDisplay = $membershipPoint->earned_points;
                            $pointsRedeemedDisplay = $membershipPoint->redeemed_points;
                            $pointBalanceDisplay = $membershipPoint->point_balance;
                            $point_balance = $membershipPoint->point_balance; // Update running balance to match membership_points table
                            
                            // Accumulate totals for account summary - DISABLED: Now using pre-calculated values
                            // $total_earned_points_display += $pointsEarnedDisplay;
                            // $total_redeemed_points_display += $pointsRedeemedDisplay;
                            // Point balance is cumulative - the final value will be used in the summary
                            
                            // Get payment method from membership_points payment_details
                            if ($membershipPoint->payment_details) {
                                $paymentDetails = json_decode($membershipPoint->payment_details, true);
                                if (is_array($paymentDetails)) {
                                    $paymentMethodDisplay = implode(', ', $paymentDetails);
                                } else {
                                    $paymentMethodDisplay = $membershipPoint->payment_details;
                                }
                            }
                        } else {
                            // If membership point not found, use transaction data as fallback
                            $earned = $one->rp_earned ?? 0;
                            $redeemed = $one->rp_redeemed ?? 0;
                            $pointBalanceDisplay = 0; // No membership_point record found
                            
                            // Accumulate totals for account summary - DISABLED: Now using pre-calculated values
                            // $total_earned_points_display += $earned;
                            // $total_redeemed_points_display += $redeemed;
                        }
                        
                        // Get payment method from transaction payments if not set from membership_points
                        if (empty($paymentMethodDisplay) && $transaction) {
                            $pmt_methods = TransactionPayment::leftjoin(
                                'accounts',
                                'transaction_payments.account_id',
                                'accounts.id',
                            )
                                ->where('transaction_payments.transaction_id', $transaction->id)
                                ->select(['transaction_payments.*', 'accounts.name as account_name'])
                                ->withTrashed()
                                ->first();
                            
                            if ($pmt_methods) {
                                $paymentMethodDisplay = ucfirst(str_replace('_', ' ', $pmt_methods->method));
                                if (strtolower($pmt_methods->method) == 'bank' || 
                                    strtolower($pmt_methods->method) == 'bank_transfer' || 
                                    strtolower($pmt_methods->method) == 'cheque' || 
                                    strtolower($pmt_methods->method) == 'direct_bank_deposit') {
                                    if (!empty($pmt_methods->account_name)) {
                                        $paymentMethodDisplay .= ' - ' . $pmt_methods->account_name;
                                    }
                                    if (!empty($pmt_methods->cheque_number)) {
                                        $paymentMethodDisplay .= ' (Cheque: ' . $pmt_methods->cheque_number . ')';
                                    }
                                }
                            }
                        }
                    }
                @endphp

                @php
                    if (!empty($transaction_amounts) && $one->amount != $transaction_amounts) {
                        continue;
                    }
                @endphp

                @php
                    if (!empty($transaction_type)) {
                        if (
                            ($transaction_type == 'debit' &&
                                !in_array($one->type, [
                                    'fleet_opening_balance',
                                    'cheque_return',
                                    'vat_price_adjustment',
                                    'property_sale',
                                    'route_operation',
                                    'expense',
                                    'sell',
                                    'fpos_sale',
                                    'settlement',
                                    'direct_customer_loan',
                                    'opening_balance',
                                ])) ||
                            ($transaction_type == 'credit' &&
                                in_array($one->type, [
                                    'fleet_opening_balance',
                                    'cheque_return',
                                    'vat_price_adjustment',
                                    'property_sale',
                                    'route_operation',
                                    'expense',
                                    'sell',
                                    'fpos_sale',
                                    'settlement',
                                    'direct_customer_loan',
                                    'opening_balance',
                                ]))
                        ) {
                            continue;
                        }
                    }
                @endphp

                @if ($one->type == 'fleet_opening_balance')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance += $one->amount;
                            $opening_balance += $one->amount;
                        }
                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        $type = __('contact.fleet_opening_balance');
                        $description = $type . '<br>' . __('contact.invoice_no') . ' ' . $one->invoice_no;
                    @endphp
                @elseif($one->type == 'cheque_return')
                    @php

                        if (empty($one->deleted_at)) {
                            $balance += $one->amount;
                            $cheque_returns += $one->amount;
                            $cheque_return_charges += $one->ch_charges;
                        }

                        $debit = $one->amount;
                        $credit = '';

                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        $type = __('contact.cheque_return');
                        $description = $type;
                    @endphp
                @elseif($one->type == 'vat_price_adjustment')
                    @php

                        if (empty($one->deleted_at)) {
                            $balance += $one->amount;
                            // $sales += $one->amount; // DISABLED: Now using pre-calculated values
                        }

                        if ($one->amount < 0) {
                            $debit = '';
                            $credit = abs($one->amount);
                        } else {
                            $debit = abs($one->amount);
                            $credit = '';
                        }

                        $type = __('account.price_adjusted');
                        $description = $type . '<br>' . __('contact.invoice_no') . ' ' . $one->invoice_no;
                    @endphp
                @elseif($one->type == 'property_sale')
                    @php
                        if (empty($one->deleted_at)) {
                            // $sales += $one->amount; // DISABLED: Now using pre-calculated values
                            $balance += $one->amount;
                        }

                        $debit = $one->amount;
                        $credit = '';

                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        $type = __('contact.property_sale');
                        $pumper_name = '';
                        if (!empty($transaction->pump_operator_id)) {
                            $pumper = Modules\Petro\Entities\PumpOperator::find($transaction->pump_operator_id);
                            $pumper_name = !empty($pumper) ? $pumper->name : '';
                        }

                        $description = "
                            <b>Invoice No:</b> {$one->invoice_no} <br>
                            <b>Pumper:</b> {$pumper_name} <br>
                            <b>Ref No / Veh. No:</b> {$transaction->ref_no} <br>
                            <b>Order No:</b> {$transaction->order_no}
                        ";
                    @endphp
                @elseif($one->type == 'route_operation')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance += $one->amount;
                            // $sales += $one->amount; // DISABLED: Now using pre-calculated values
                        }

                        $debit = $one->amount;
                        $credit = '';

                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        $type = __('contact.route_operation');
                        $description = $type . '<br>' . __('contact.ro_no') . ' ' . $one->invoice_no;
                    @endphp
                @elseif($one->type == 'expense')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance += $one->amount;
                            // $sales += $one->amount; // DISABLED: Now using pre-calculated values
                        }

                        $debit = $one->amount;
                        $credit = '';

                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        $description = $type;
                        $type = __('contact.expenses');
                    @endphp
                @elseif(
                    $one->type == 'sell' ||
                        $one->type == 'fpos_sale' ||
                        $one->type == 'settlement' ||
                        $one->type == 'direct_customer_loan')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance += $one->amount;
                        }

                        $debit = $one->amount;
                        $credit = '';

                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        if ($one->type == 'settlement') {
                            $loans += $one->amount;
                            $type = __('petro::lang.customer_loans');
                            $description = $type . '<br>' . __('contact.invoice_no') . ' ' . $one->invoice_no;

                            if (!empty($transaction) && !empty($transaction->transaction_note)) {
                                $notes = '<b>Note</b><br>' . nl2br($transaction->transaction_note);
                            }
                        } elseif ($one->type == 'direct_customer_loan') {
                            $loans += $one->amount;
                            $type = __('lang_v1.direct_loan_to_customer');
                            $description = $type;

                            if (!empty($transaction) && !empty($transaction->transaction_note)) {
                                $description = $type;
                                $notes = '<b>Note</b><br>' . nl2br($transaction->transaction_note);
                            }

                            if (!empty($transaction) && !empty($transaction->invoice_no)) {
                                $description .= '<br><b>Ref: </b>' . $transaction->invoice_no;
                            }
                        } else {
                            // $sales += $one->amount; // DISABLED: Now using pre-calculated values
                            $type = __('contact.sale');
                            $description = $type . '<br>' . __('contact.invoice_no') . ' ' . $one->invoice_no;
                        }
                    @endphp
                @elseif($one->type == 'opening_balance')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance += $one->amount;
                            $opening_balance += $one->amount;
                        }

                        $debit = $one->amount;
                        $credit = '';

                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        $type = __('contact.opening_balance');
                        $description = $type;
                    @endphp
                @elseif($one->type == 'sell_return')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance -= $one->amount;
                            $returns += $one->amount;
                        }

                        $debit = '';
                        $credit = $one->amount;

                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        $type = __('contact.sell_return');
                        $description = $type . '<br>' . __('contact.invoice_no') . ' ' . $one->invoice_no;
                    @endphp
                @elseif($one->type == 'payment')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance -= $one->amount;
                            // $paid += $one->amount; // DISABLED: Now using pre-calculated values
                        }

                        $debit = '';
                        $credit = $one->amount;
                        if ($one->payment_status != 'paid' && !empty($one->airticket_no)) {
                            $debit = $one->amount;
                            $credit = '';
                        }

                        $status = (string) view('sell.partials.payment_status', [
                            'payment_status' => $one->payment_status,
                            'id' => $one->id,
                        ]);

                        $type = __('contact.payment');

                        if ($one->payment_status != 'paid' && !empty($one->airticket_no)) {
                            $type = __('contact.sale');
                            $balance += $one->amount;
                        }

                        $description =
                            $type . '<br>' . __('contact.ref_no') . ' ' . $one->invoice_no . $one->airticket_no;

                        $pmt_methods = TransactionPayment::leftjoin(
                            'accounts',
                            'transaction_payments.account_id',
                            'accounts.id',
                        )
                            ->where('transaction_payments.id', $one->payment_row)
                            ->select(['transaction_payments.*', 'accounts.name as account_name'])
                            ->withTrashed()
                            ->first();
                        $payment_ref_no = !empty($pmt_methods) ? $pmt_methods->payment_ref_no : '';

                        $txn_ids = TransactionPayment::where('payment_ref_no', $payment_ref_no)
                            ->pluck('transaction_id')
                            ->toArray();
                        $transactions = Transaction::whereIn('id', $txn_ids)
                            ->distinct('invoice_no')
                            ->pluck('invoice_no')
                            ->toArray();

                        if (!empty($transactions)) {
                            $description .=
                                '<br><b>' .
                                __('lang_v1.related_invoices') .
                                ':</b> ' .
                                implode(', ', $transactions);
                        }
                    @endphp
                @elseif($one->type == 'customer_payment')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance -= $one->amount;
                            // $paid += $one->amount; // DISABLED: Now using pre-calculated values
                        }

                        $debit = '';
                        $credit = $one->amount;

                        $type = __('contact.settlement_sale');
                        $description = "
                                        <b>Invoice No:</b> {$one->invoice_no} <br>
                                        <b>Page:</b> Customer Page
                                        ";
                    @endphp
                @elseif($one->type == 'sale')

                @elseif($one->type == 'ledger_discount')
                    @php
                        if (empty($one->deleted_at)) {
                            $balance -= $one->amount;
                            // $paid += $one->amount; // DISABLED: Now using pre-calculated values
                        }
                        $debit = '';
                        $credit = $one->amount;

                        $type = __('sale.discount');
                        $description = $type . '<br>' . __('sale.discount_no') . ' ' . $transaction->invoice_no;
                    @endphp
                @else
                    @if ($one->type == 'refund')
                        @php
                            $type = 'Payment Refund';
                            $description = $type;
                        @endphp
                    @else
                        @php
                            $type = $one->type;
                            $description = $type;
                        @endphp
                    @endif
                @endif

                @php
                    $page = '';
                @endphp
                @if ($one->paid_in_type == 'customer_page')
                    @php
                        $page =
                            $page .
                            "<br><b>Page</b>
                        <br>i. VAT Statement
                        <br>ii. Customer Statement
                        <br>iii. Customer page / action / Pay due Amount
                    ";
                    @endphp
                @elseif($one->paid_in_type == 'customer_bulk')
                    @php
                        $page = $page . '<br><b>Page</b> Customer Payment Bulk page';
                    @endphp
                @elseif($one->paid_in_type == 'all_sale_page')
                    @php
                        $page =
                            $page .
                            "<br><b>Page</b>
                        <br>i. Sale Module / List Sales / action / Pay due Amount
                        <br>ii. Sale Module / List POS / action / Pay due Amount
                    ";
                    @endphp
                @endif
                @if (!empty($one->deleted_at) && ($one->type == 'customer_payment' || $one->type == 'payment'))
                    @php
                        // Set up variables for deleted transactions
                        $deletedTransaction = null;
                        $deletedMembershipPoint = null;
                        $deletedBillAmount = $one->amount ?? 0;
                        $deletedPointsEarned = $one->rp_earned ?? 0;
                        $deletedPointsRedeemed = $one->rp_redeemed ?? 0;
                        $deletedPointBalance = $point_balance;
                        $deletedPaymentMethod = '';
                        
                        if ($one->id) {
                            $deletedTransaction = Transaction::withTrashed()->find($one->id);
                            if ($deletedTransaction && $deletedTransaction->rp_point_id) {
                                $deletedMembershipPoint = MembershipPoint::find($deletedTransaction->rp_point_id);
                                if ($deletedMembershipPoint) {
                                    $deletedBillAmount = $deletedMembershipPoint->amount;
                                    $deletedPointsEarned = $deletedMembershipPoint->earned_points;
                                    $deletedPointsRedeemed = $deletedMembershipPoint->redeemed_points;
                                    $deletedPointBalance = $deletedMembershipPoint->point_balance;
                                    
                                    if ($deletedMembershipPoint->payment_details) {
                                        $paymentDetails = json_decode($deletedMembershipPoint->payment_details, true);
                                        if (is_array($paymentDetails)) {
                                            $deletedPaymentMethod = implode(', ', $paymentDetails);
                                        } else {
                                            $deletedPaymentMethod = $deletedMembershipPoint->payment_details;
                                        }
                                    }
                                }
                            }
                            
                            // Get payment method from transaction payments
                            if (empty($deletedPaymentMethod) && $deletedTransaction) {
                                $deletedPmtMethods = TransactionPayment::leftjoin(
                                    'accounts',
                                    'transaction_payments.account_id',
                                    'accounts.id',
                                )
                                    ->where('transaction_payments.transaction_id', $deletedTransaction->id)
                                    ->select(['transaction_payments.*', 'accounts.name as account_name'])
                                    ->withTrashed()
                                    ->first();
                                
                                if ($deletedPmtMethods) {
                                    $deletedPaymentMethod = ucfirst(str_replace('_', ' ', $deletedPmtMethods->method));
                                    if (strtolower($deletedPmtMethods->method) == 'bank' || 
                                        strtolower($deletedPmtMethods->method) == 'bank_transfer' || 
                                        strtolower($deletedPmtMethods->method) == 'cheque' || 
                                        strtolower($deletedPmtMethods->method) == 'direct_bank_deposit') {
                                        if (!empty($deletedPmtMethods->account_name)) {
                                            $deletedPaymentMethod .= ' - ' . $deletedPmtMethods->account_name;
                                        }
                                        if (!empty($deletedPmtMethods->cheque_number)) {
                                            $deletedPaymentMethod .= ' (Cheque: ' . $deletedPmtMethods->cheque_number . ')';
                                        }
                                    }
                                }
                            }
                        }
                    @endphp
                    <tr>
                        <td>
                            {{ @format_date($one->date) }}
                        </td>

                        <td>
                            {{ @format_datetime($one->created_at) }}
                        </td>

                        <td>
                            {{ $one->location_name }}
                        </td>

                        <td>
                            @php
                                $user = null;
                                if (!empty($one->deleted_at) && !empty($one->deleted_by)) {
                                    $user = \App\User::find($one->deleted_by);
                                }
                            @endphp

                            @if (!empty($one->invoice_no))
                                <b>Invoice No:</b> {{ $one->invoice_no }}<br>
                            @endif
                            @if (!empty($deletedTransaction) && !empty($deletedTransaction->order_no))
                                <b>Order No:</b> {{ $deletedTransaction->order_no }}<br>
                            @endif

                            @if (!empty($one->is_settlement_customer_payment) && !empty($one->pump_operator))
                                <b>Pump Operator:</b> {{ $one->pump_operator }}<br>
                            @endif

                            @if (!empty($deletedTransaction) && !empty($deletedTransaction->ref_no) && $one->type != 'payment')
                                <b>Pay Ref. No:</b> {{ $deletedTransaction->ref_no }}<br>
                            @endif

                            <b>Page:</b> Customer Page
                        </td>
                        <td>
                            {{ @num_format($deletedBillAmount) }}
                        </td>

                        <td>
                            {{ @num_format($deletedPointsEarned) }}
                        </td>

                        <td>
                            {{ @num_format($deletedPointsRedeemed) }}
                        </td>

                        <td>
                            {{ @num_format($deletedPointBalance) }}
                        </td>

                        <td>
                            @if (!empty($pmt_methods))
                                {{ ucfirst(str_replace('_', ' ', $pmt_methods->method)) }}

                                @if (strtolower($pmt_methods->method) == 'bank' ||
                                        strtolower($pmt_methods->method) == 'bank_transfer' ||
                                        strtolower($pmt_methods->method) == 'cheque' ||
                                        strtolower($pmt_methods->method) == 'direct_bank_deposit')
                                    <br>
                                    @if (!empty($pmt_methods->account_name))
                                        {!! __('contact.bank_name') . ' ' . $pmt_methods->account_name . '<br>' !!}
                                    @endif

                                    @if (!empty($pmt_methods->cheque_number))
                                        {!! __('contact.cheque_number') . ' ' . $pmt_methods->cheque_number . '<br>' !!}
                                    @endif

                                    @if (!empty($pmt_methods->cheque_date))
                                        {!! __('contact.cheque_date') . ' ' . @format_date($pmt_methods->cheque_date) . '<br>' !!}
                                    @endif
                                @endif
                            @endif
                        </td>
                    </tr>

                @else
                    <tr @if (!empty($one->deleted_at)) class="deleted-row" @endif>
                        <td>
                            {{ @format_date($one->date) }}
                        </td>

                        <td>
                            {{ @format_datetime($one->created_at) }}
                        </td>

                        <td>
                            {{ $one->location_name }}
                        </td>

                        <td>
                            @php
                                $user = null;
                                if (!empty($one->deleted_at)) {
                                    if (!empty($one->deleted_by)) {
                                        $user = \App\User::find($one->deleted_by);
                                    }
                                }
                            @endphp
                            @if (!empty($one->invoice_no) && $one->type == 'payment')
                                <b>Payment Ref No:</b> {{ $one->invoice_no }}<br>
                            @else
                                <b>Invoice No:</b> {{ $one->invoice_no }}<br>
                            @endif

                            @if (!empty($one->page))
                                <b>Page:</b> {{ $one->page }}<br>
                            @endif

                            @if (!empty($one->note))
                                <b>Note:</b> {{ $one->note }}<br>
                            @endif

                            @if(!empty($one->bill_no) || !empty($one->statement_no))
                                <b>Bill No:</b>
                                @if(!empty($one->bill_no) && !empty($one->statement_no))
                                    {{ $one->bill_no }} / {{ $one->statement_no }}
                                @elseif(!empty($one->bill_no))
                                    {{ $one->bill_no }}
                                @else
                                    {{ $one->statement_no }}
                                @endif
                                <br>
                            @endif

                            @if (!empty($one->is_settlement_customer_payment) && !empty($one->pump_operator))
                                <b>Pumper:</b> {{ $one->pump_operator }}<br>
                            @endif

                            @if (!empty($transaction) && !empty($transaction->ref_no) && $one->type != 'payment')
                                <b>Ref No / Veh. No:</b> {{ $transaction->ref_no }}<br>
                            @endif

                            @if (!empty($transaction) && !empty($transaction->order_no))
                                <b>Order No:</b> {{ $transaction->order_no }} <br>
                            @endif

                        </td>
                        <td>
                            {{ @num_format($billAmountDisplay) }}
                        </td>

                        <td>
                            {{ @num_format($pointsEarnedDisplay) }}
                        </td>

                        <td>
                            {{ @num_format($pointsRedeemedDisplay) }}
                        </td>

                        <td>
                            {{ @num_format($pointBalanceDisplay) }}
                        </td>

                        <td>
                            @if (!empty($paymentMethodDisplay))
                                {{ $paymentMethodDisplay }}
                            @elseif (!empty($pmt_methods))
                                {{ ucfirst(str_replace('_', ' ', $pmt_methods->method)) }}

                                @if (strtolower($pmt_methods->method) == 'bank' ||
                                        strtolower($pmt_methods->method) == 'bank_transfer' ||
                                        strtolower($pmt_methods->method) == 'cheque' ||
                                        strtolower($pmt_methods->method) == 'direct_bank_deposit')
                                    <br>
                                    @if (!empty($pmt_methods->account_name))
                                        {!! __('contact.bank_name') . ' ' . $pmt_methods->account_name . '<br>' !!}
                                    @endif

                                    @if (!empty($pmt_methods->cheque_number))
                                        {!! __('contact.cheque_number') . ' ' . $pmt_methods->cheque_number . '<br>' !!}
                                    @endif

                                    @if (!empty($pmt_methods->cheque_date))
                                        {!! __('contact.cheque_date') . ' ' . @format_date($pmt_methods->cheque_date) . '<br>' !!}
                                    @endif
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endif
                @if ($one->transaction_type == 'hms_booking')
                    @php
                        // Set up variables for HMS booking transactions
                        $hmsTransaction = null;
                        $hmsMembershipPoint = null;
                        $hmsBillAmount = $one->amount ?? 0;
                        $hmsPointsEarned = $one->rp_earned ?? 0;
                        $hmsPointsRedeemed = $one->rp_redeemed ?? 0;
                        $hmsPointBalance = $point_balance;
                        $hmsPaymentMethod = '';
                        
                        if ($one->id) {
                            $hmsTransaction = Transaction::find($one->id);
                            if ($hmsTransaction && $hmsTransaction->rp_point_id) {
                                $hmsMembershipPoint = MembershipPoint::find($hmsTransaction->rp_point_id);
                                if ($hmsMembershipPoint) {
                                    $hmsBillAmount = $hmsMembershipPoint->amount;
                                    $hmsPointsEarned = $hmsMembershipPoint->earned_points;
                                    $hmsPointsRedeemed = $hmsMembershipPoint->redeemed_points;
                                    $hmsPointBalance = $hmsMembershipPoint->point_balance;
                                    
                                    if ($hmsMembershipPoint->payment_details) {
                                        $paymentDetails = json_decode($hmsMembershipPoint->payment_details, true);
                                        if (is_array($paymentDetails)) {
                                            $hmsPaymentMethod = implode(', ', $paymentDetails);
                                        } else {
                                            $hmsPaymentMethod = $hmsMembershipPoint->payment_details;
                                        }
                                    }
                                }
                            }
                            
                            // Get payment method from transaction payments
                            if (empty($hmsPaymentMethod) && $hmsTransaction) {
                                $hmsPmtMethods = TransactionPayment::leftjoin(
                                    'accounts',
                                    'transaction_payments.account_id',
                                    'accounts.id',
                                )
                                    ->where('transaction_payments.transaction_id', $hmsTransaction->id)
                                    ->select(['transaction_payments.*', 'accounts.name as account_name'])
                                    ->withTrashed()
                                    ->first();
                                
                                if ($hmsPmtMethods) {
                                    $hmsPaymentMethod = ucfirst(str_replace('_', ' ', $hmsPmtMethods->method));
                                    if (strtolower($hmsPmtMethods->method) == 'bank' || 
                                        strtolower($hmsPmtMethods->method) == 'bank_transfer' || 
                                        strtolower($hmsPmtMethods->method) == 'cheque' || 
                                        strtolower($hmsPmtMethods->method) == 'direct_bank_deposit') {
                                        if (!empty($hmsPmtMethods->account_name)) {
                                            $hmsPaymentMethod .= ' - ' . $hmsPmtMethods->account_name;
                                        }
                                        if (!empty($hmsPmtMethods->cheque_number)) {
                                            $hmsPaymentMethod .= ' (Cheque: ' . $hmsPmtMethods->cheque_number . ')';
                                        }
                                    }
                                }
                            }
                        }
                    @endphp
                    @if ($one->payment_status == 'paid')
                        <tr>
                            <td>
                                {{ @format_date($one->date) }}
                            </td>

                            <td>
                                {{ @format_datetime($one->created_at) }}
                            </td>

                            <td>
                                {{ $one->location_name }}
                            </td>

                            <td>
                                <!--{!! $description !!}-->
                                @if (!empty($hmsTransaction) && !empty($hmsTransaction->ref_no))
                                    <b>Booking No:</b> {{ $hmsTransaction->ref_no }} <br>
                                @endif
                            </td>

                        <td>
                            {{ @num_format($hmsBillAmount) }}
                        </td>

                        <td>
                            {{ @num_format($hmsPointsEarned) }}
                        </td>

                        <td>
                            {{ @num_format($hmsPointsRedeemed) }}
                        </td>

                        <td>
                            {{ @num_format($hmsPointBalance) }}
                        </td>

                            <td>
                                @if (!empty($hmsPaymentMethod))
                                    {{ $hmsPaymentMethod }}
                                @elseif (!empty($hmsPmtMethods))
                                    {{ ucfirst(str_replace('_', ' ', $hmsPmtMethods->method)) }}

                                    @if (strtolower($hmsPmtMethods->method) == 'bank' ||
                                            strtolower($hmsPmtMethods->method) == 'bank_transfer' ||
                                            strtolower($hmsPmtMethods->method) == 'cheque' ||
                                            strtolower($hmsPmtMethods->method) == 'direct_bank_deposit')
                                        <br>
                                        @if (!empty($hmsPmtMethods->account_name))
                                            {!! __('contact.bank_name') . ' ' . $hmsPmtMethods->account_name . '<br>' !!}
                                        @endif

                                        @if (!empty($hmsPmtMethods->cheque_number))
                                            {!! __('contact.cheque_number') . ' ' . $hmsPmtMethods->cheque_number . '<br>' !!}
                                        @endif

                                        @if (!empty($hmsPmtMethods->cheque_date))
                                            {!! __('contact.cheque_date') . ' ' . @format_date($hmsPmtMethods->cheque_date) . '<br>' !!}
                                        @endif
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endif
                @endif
            @endif
        @endforeach
        </tbody>
    </table>
</div>

@php
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
@endphp

@if (!empty($reports_footer))
    <style>
        #footer {
            display: none;
        }

        @media print {
            #footer {
                display: block !important;
                position: fixed;
                bottom: -1mm;
                width: 100%;
                text-align: center;
                font-size: 12px;
                color: #333;
            }
        }
    </style>

    <div id="footer">
        {{ $reports_footer->value }}
    </div>
@endif
<!-- This will be printed -->
<section class="invoice print_section" id="ledger_print">
</section>

<script>
    @php
        // Use pre-calculated values from the MemberLedgerSummaryService
        // These values are passed from the controller via $accountSummary
        $openingBalance = isset($accountSummary) ? $accountSummary['opening_balance'] : ($ledger_details['beginning_balance'] ?? 0);
        $totalSales = isset($accountSummary) ? $accountSummary['total_sales'] : ($ledger_details['total_sales'] ?? 0);
        $totalPaid = isset($accountSummary) ? $accountSummary['total_paid'] : ($ledger_details['total_paid'] ?? 0);
        $totalEarnedPoints = isset($accountSummary) ? $accountSummary['total_points_earned'] : ($ledger_details['total_earned_points'] ?? 0);
        $totalRedeemedPoints = isset($accountSummary) ? $accountSummary['total_points_redeemed'] : ($ledger_details['total_redeemed_points'] ?? 0);
        $finalPointBalance = isset($accountSummary) ? $accountSummary['current_points_balance'] : ($ledger_details['final_point_balance'] ?? 0);
    @endphp
    $("#opening_balance").html("{{ @num_format($openingBalance) }}");
    $("#total_sales").html("{{ @num_format($totalSales) }}");
    $("#total_paid").html("{{ @num_format($totalPaid) }}");
    $('#total_earned_points').html("{{ @num_format($totalEarnedPoints) }}");
    $('#total_redeemed_points').html("{{ @num_format($totalRedeemedPoints) }}");
    $('#balance_points').html("{{ @num_format($finalPointBalance) }}");
</script>
