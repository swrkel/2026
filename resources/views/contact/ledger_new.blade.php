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
    use Modules\Petro\Entities\CustomerPayment;
    use App\TransactionPayment;
    use App\AccountTransaction;
    use App\Transaction;
    //dd($ledger_transactions);
    $formatLedgerDateTime = function ($value) {
        if (empty($value)) {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Exception $e) {
            return $value;
        }
    };
    $formatLedgerDate = function ($value) {
        if (empty($value)) {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value;
        }
    };
@endphp
<div class="col-md-12 col-sm-12 @if (!empty($for_pdf)) width-100 text-center @endif">
    <p class="text-center"><strong>{{ $contact->business->name }}</strong><br>{{ $location_details->city }},
        {{ $location_details->state }}<br>{!! $location_details->mobile !!}</p>
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
            <td>@lang('lang_v1.beginning_balance')</td>
            <td>{{ @num_format($ledger_details['beginning_balance']) }}
            </td>
        </tr>

        @if ($contact->type == 'customer' || $contact->type == 'both')
            <tr>
                <td>@lang('lang_v1.total_sales')</td>
                <td>
                    <span id="total_sales"></span>
                </td>
            </tr>
        @endif


        <tr>
            <td>@lang('sale.total_paid')</td>
            <td>
                <span id="total_paid"></span>
            </td>
        </tr>

        <tr>
            <td>@lang('lang_v1.cheque_return')</td>
            <td>
                <span id="cheque_return"></span>
            </td>
        </tr>

        <tr>
            <td>@lang('lang_v1.cheque_return_charges')</td>
            <td>
                <span id="cheque_return_charges"></span>
            </td>
        </tr>

        <tr>
            <td>@lang('lang_v1.loan_to_customer')</td>
            <td>
                <span id="loan_to_customer"></span>
            </td>
        </tr>

        <tr>
            <td>@lang('lang_v1.interest_charged')</td>
            <td>
                <span id="interest_charged"></span>
            </td>
        </tr>


        <tr>
            <td><strong>@lang('lang_v1.balance_due')</strong></td>
            <td><span id="balance_due">0.00</span></td>
        </tr>
    </table>
</div>
<div class="col-md-12 col-sm-12 @if (!empty($for_pdf)) width-100 @endif">
    <p style="text-align: center !important; float: left; width: 100%;"><strong>@lang('lang_v1.ledger_table_heading', ['start_date' => $start_date, 'end_date' => $end_date])</strong></p>

    <table class="table table-striped @if (!empty($for_pdf)) table-pdf td-border @endif" id="ledger_table">
        <thead>
            <tr class="row-border">
                <th>@lang('lang_v1.system_date')</th>
                <th>@lang('lang_v1.transaction_date')</th>
                <th>@lang('lang_v1.description')</th>
                <th>@lang('lang_v1.type')</th>
                <th>@lang('sale.location')</th>
                <th>@lang('sale.payment_status')</th>
                <th>@lang('account.debit')</th>
                <th>@lang('account.credit')</th>
                <th>@lang('lang_v1.balance')</th>
                <th>@lang('lang_v1.payment_method')</th>
            </tr>
        </thead>
        <tbody>
            @php
                $balance = $ledger_details['beginning_balance'];
            @endphp
            <tr class="bf-balance-row">
                <td class="row-border"></td>
                <td class="row-border" data-order="0">
                    @if (!empty($ledger_details['opening_date']))
                        {{ @format_date($ledger_details['opening_date']) }}
                    @endif
                </td>
                <td class="row-border">B/F Balance</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="row-border">
                    {{ number_format($ledger_details['beginning_balance'], $currency_precision, session('currency')['decimal_separator'], session('currency')['thousand_separator']) }}
                </td>
                <td></td>
            </tr>
            @php
                $opening_balance = 0;
                $sales = 0;
                $paid = 0;
                $returns = 0;
                $returns_paid = 0;
                $loans = 0;
                $cheque_returns = 0;
                $cheque_return_charges = 0;
                $interest_charged = 0;
            @endphp

            @foreach ($ledger_transactions as $one)
                @if (empty($one->deleted_at))
                    @php
                        $status = null;
                        $pmt_methods = null;
                        $notes = null;
                        $transaction = null;
                        if ($one->id) {
                            $transaction = Transaction::find($one->id);
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
                                        'cheque_return_charges',
                                        'vat_price_adjustment',
                                        'property_sale',
                                        'route_operation',
                                        'expense',
                                        'sell',
                                        'fpos_sale',
                                        'sale',
                                        'settlement',
                                        'direct_customer_loan',
                                        'opening_balance',
                                    ])) ||
                                ($transaction_type == 'credit' &&
                                    in_array($one->type, [
                                        'fleet_opening_balance',
                                        'cheque_return',
                                        'cheque_return_charges',
                                        'vat_price_adjustment',
                                        'property_sale',
                                        'route_operation',
                                        'expense',
                                        'sell',
                                        'fpos_sale',
                                        'sale',
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

                            $debit = $one->amount;
                            $credit = '';

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
                            }

                            $debit = $one->amount;
                            $credit = '';

                            $status = (string) view('sell.partials.payment_status', [
                                'payment_status' => $one->payment_status,
                                'id' => $one->id,
                            ]);

                            $type = __('contact.cheque_return');
                            // IS1485: Description should only show Cheque Return.
                            $description = 'Cheque Return';
                            if (empty($pmt_methods)) {
                                $pmt_methods = TransactionPayment::leftjoin('accounts', 'transaction_payments.account_id', 'accounts.id')
                                    ->where(function ($q) use ($one) {
                                        if (!empty($one->payment_row)) {
                                            $q->where('transaction_payments.id', $one->payment_row);
                                        }
                                        $q->orWhere('transaction_payments.transaction_id', $one->id);
                                    })
                                    ->select(['transaction_payments.*', 'accounts.name as account_name'])
                                    ->withTrashed()
                                    ->first();
                            }

                            // IS1483: Some cheque returns are posted through account_transactions/contact_ledgers
                            // without a transaction_payments row. Use the cheque return account transaction
                            // as the payment-method source so Ledger shows Cheque, Bank Name, Cheque No.,
                            // Cheque Date and Cheque Return Date in the Payment Method column.
                            if (empty($pmt_methods)) {
                                $cheque_account_txn = AccountTransaction::leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
                                    ->where('account_transactions.transaction_id', $one->id)
                                    ->where(function ($q) {
                                        $q->whereNotNull('account_transactions.cheque_number')
                                          ->orWhereNotNull('account_transactions.bank_name')
                                          ->orWhereNotNull('account_transactions.cheque_date');
                                    })
                                    ->select([
                                        'account_transactions.cheque_number',
                                        'account_transactions.bank_name',
                                        'account_transactions.cheque_date',
                                        'accounts.name as account_name',
                                    ])
                                    ->orderBy('account_transactions.id', 'asc')
                                    ->first();

                                if (!empty($cheque_account_txn)) {
                                    $pmt_methods = (object) [
                                        'method' => 'cheque',
                                        'bank_name' => $cheque_account_txn->bank_name,
                                        'cheque_number' => $cheque_account_txn->cheque_number,
                                        'cheque_date' => $cheque_account_txn->cheque_date,
                                        'account_name' => $cheque_account_txn->account_name,
                                    ];
                                }
                            }
                        @endphp
                    @elseif($one->type == 'cheque_return_charges')
                        @php

                            if (empty($one->deleted_at)) {
                                $balance += $one->amount;
                                $cheque_return_charges += $one->amount;
                            }

                            $debit = $one->amount;
                            $credit = '';

                            $type = __('lang_v1.cheque_return_charges');
                            $description = $type;
                        @endphp
                    @elseif($one->type == 'vat_price_adjustment')
                        @php

                            if (empty($one->deleted_at)) {
                                $balance += $one->amount;
                                $sales += $one->amount;
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
                                $sales += $one->amount;
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
                                $sales += $one->amount;
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
                                $sales += $one->amount;
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
                            $one->type == 'sale' ||
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
                            } elseif (!empty($one->sub_type) && $one->sub_type == 'vat_invoice') {
                                $sales += $one->amount;
                                $type = 'VAT Invoice';
                                $description = $one->note;
                            } else {
                                $sales += $one->amount;
                                $type = __('contact.sale');
                                $description = $type . '<br>' . __('contact.invoice_no') . ' ' . $one->invoice_no;
                            }
                        @endphp
                    @elseif($one->type == 'ledger')
                        @php
                            if ($one->acc_transaction_type == 'debit') {
                                if (empty($one->deleted_at)) {
                                    $balance += $one->amount;
                                    $sales += $one->amount;
                                }
                                $debit = $one->amount;
                                $credit = '';
                            } else {
                                if (empty($one->deleted_at)) {
                                    $balance -= $one->amount;
                                    $paid += $one->amount;
                                }
                                $debit = '';
                                $credit = $one->amount;
                            }
                            $type = __('account.journal_entry');
                            if ($type == 'account.journal_entry') {
                                $type = 'Journal Entry';
                            }
                            $description = 'Journal Entry' . (!empty($one->note) ? ' with Note: ' . $one->note : '');
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
                                $paid += $one->amount;
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
                            $is_customer_bulk_payment = (($one->paid_in_type ?? null) === 'customer_bulk');

                            // For customer bulk payments, do not show bill-wise invoice breakdown in ledger.
                            if (!$is_customer_bulk_payment) {
                                $txn_ids = TransactionPayment::where('payment_ref_no', $payment_ref_no)
                                    ->pluck('transaction_id')
                                    ->toArray();
                                $transactions = Transaction::whereIn('id', $txn_ids)
                                    ->distinct('invoice_no')
                                    ->pluck('invoice_no')
                                    ->toArray();
                                $related_transaction_types = Transaction::whereIn('id', $txn_ids)
                                    ->pluck('type')
                                    ->filter()
                                    ->unique()
                                    ->toArray();

                                if (!empty($transactions)) {
                                    $description .=
                                        '<br><b>' .
                                        __('lang_v1.related_invoices') .
                                        ':</b> ' .
                                        implode(', ', $transactions);
                                }
                                if (in_array('fpos_sale', $related_transaction_types, true) || in_array('tpos_sale', $related_transaction_types, true)) {
                                    $description .= '<br><b>Source:</b> POS Payment';
                                }
                            }
                        @endphp
                    @elseif($one->type == 'customer_payment')
                        @php
                            if (empty($one->deleted_at)) {
                                $balance -= $one->amount;
                                $paid += $one->amount;
                            }

                            $debit = '';
                            $credit = $one->amount;

                            $type = __('contact.settlement_sale');
                            $description = "
                                            <b>Invoice No:</b> {$one->invoice_no} <br>
                                            <b>Page:</b> Customer Page
                                            ";
                        @endphp
                    @elseif($one->type == 'ledger_discount')
                        @php
                            if (empty($one->deleted_at)) {
                                $balance -= $one->amount;
                                $paid += $one->amount;
                            }
                            $debit = '';
                            $credit = $one->amount;

                            $type = __('sale.discount');
                            $description = $type . '<br>' . __('sale.discount_no') . ' ' . $transaction->invoice_no;
                        @endphp
                    @elseif ($one->type == 'points_redeemed')
                        @php
                            if (empty($one->deleted_at)) {
                                $balance -= $one->amount;
                                $paid += $one->amount;
                            }
                            $debit = '';
                            $credit = $one->amount;
                            $status = (string) view('sell.partials.payment_status', [
                                'payment_status' => $one->payment_status,
                                'id' => $one->id,
                            ]);
                            $type = __('membership::lang.points_redeemed');
                            $description = $type . '<br>' . __('contact.invoice_no') . ' ' . $one->invoice_no;
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
                        <tr>
                            <td>
                                @if(!empty($one->updated_at) && $one->updated_at != $one->created_at)
                                    {{ $formatLedgerDateTime($one->updated_at) }}
                                @else
                                    {{ $formatLedgerDateTime($one->created_at) }}
                                @endif
                            </td>

                            <td>
                                {{ $formatLedgerDate($one->date) }}
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

                                {{-- @if (!empty($one->is_settlement_customer_payment))
                                    <b>Settlement No:</b> {{ $one->invoice_no }}<br>
                                @endif --}}
                                @if (!empty($transaction) && !empty($transaction->order_no))
                                    <b>Order No:</b> {{ $transaction->order_no }}<br>
                                @endif

                                @if (!empty($one->is_settlement_customer_payment) && !empty($one->pump_operator))
                                    <b>Pumper:</b> {{ $one->pump_operator }}<br>
                                @endif

                                @if (!empty($transaction) && !empty($transaction->ref_no) && $one->type != 'payment')
                                    <b>Pay Ref. No:</b> {{ $transaction->ref_no }}<br>
                                @endif



                                {{-- @if (!empty($one->deleted_at) && empty($user))
                                    <b class="text-danger">@lang('lang_v1.deleted')</b><br>
                                @endif

                                @if (!empty($user) && !empty($one->deleted_at))
                                    <b class="text-danger">@lang('lang_v1.deleted_by'):</b>{{ $user->username }} <br>
                                @endif --}}

                                <b>Page:</b> Customer Page
                            </td>

                            <td>
                                {{ $type }}
                            </td>

                            <td>
                                {{ $one->location_name }}
                            </td>

                            <td>
                                @if (!empty($status))
                                    {!! $status !!}
                                @endif
                            </td>

                            {{-- Debit column --}}
                            <td>
                                @if (!empty($one->is_reversed) && $one->is_reversed)
                                    @if (!empty($debit) && $debit != 0)
                                        <span class="text-danger">{{ @num_format(abs($debit)) }}</span>
                                    @endif
                                @else
                                    @if (!empty($credit) && $credit < 0)
                                        {{ abs($credit) }}
                                    @endif

                                    {{ !empty($debit) && $debit > 0 ? @num_format($debit) : '' }}
                                @endif
                            </td>

                            {{-- Credit column --}}
                            <td>
                                @if (!empty($one->is_reversed) && $one->is_reversed)
                                    @if (!empty($credit) && $credit != 0)
                                        <span class="text-danger">{{ @num_format(abs($credit)) }}</span>
                                    @endif
                                @else
                                    @if (!empty($debit) && $debit < 0)
                                        {{ abs($debit) }}
                                    @endif

                                    {{ !empty($credit) && $credit > 0 ? @num_format($credit) : '' }}
                                @endif
                            </td>

                            <td>
                                {{ @num_format($balance) }}
                            </td>

                            <td>
                                @if (empty($pmt_methods) && !empty($one->account_id))
                                    {{ ucfirst(str_replace('_', ' ', $one->account_id)) }}
                                @elseif (!empty($pmt_methods))
                                    {{ ucfirst(str_replace('_', ' ', $pmt_methods->method)) }}

                                    @if (strtolower($pmt_methods->method) == 'bank' ||
                                            strtolower($pmt_methods->method) == 'bank_transfer' ||
                                            strtolower($pmt_methods->method) == 'cheque' ||
                                            strtolower($pmt_methods->method) == 'direct_bank_deposit')
                                        <br>
                                        @if (!empty($pmt_methods->bank_name) || !empty($pmt_methods->account_name))
                                            {!! __('contact.bank_name') . ' ' . ($pmt_methods->bank_name ?? $pmt_methods->account_name) . '<br>' !!}
                                        @endif

                                        @if (!empty($pmt_methods->cheque_number))
                                            {!! __('contact.cheque_number') . ' ' . $pmt_methods->cheque_number . '<br>' !!}
                                        @endif

                                        @if (!empty($pmt_methods->cheque_date))
                                            {!! __('contact.cheque_date') . ' ' . @format_date($pmt_methods->cheque_date) . '<br>' !!}
                                        @endif
                                        @if (($one->type ?? '') == 'cheque_return' && !empty($one->date))
                                            {!! __('lang_v1.cheque_return_date') . ' ' . @format_date($one->date) . '<br>' !!}
                                        @endif
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @else
                        <tr @if (!empty($one->deleted_at)) class="deleted-row" @endif @if(!empty($one->sub_type) && $one->sub_type == 'vat_invoice') style="color: #d4af37 !important; font-weight: bold;" @endif>
                            <td>
                                @if(!empty($one->updated_at) && $one->updated_at != $one->created_at)
                                    {{ $formatLedgerDateTime($one->updated_at) }}
                                @else
                                    {{ $formatLedgerDateTime($one->created_at) }}
                                @endif
                            </td>

                            <td>
                                {{ $formatLedgerDate($one->date) }}
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
                                @if(!empty($one->sub_type) && $one->sub_type == 'vat_invoice')
                                    {{ $one->note }}
                                @elseif($one->type == 'ledger')
                                    Journal Entry{{ !empty($one->note) ? ' with Note: ' . $one->note : '' }}<br>
                                @else
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
                                @endif

                                {{-- @if (!empty($one->bill_no) || !empty($one->statement_no))
                                    <b>Bill No:</b>
                                    @if (!empty($one->bill_no) && !empty($one->statement_no))
                                        {{ $one->bill_no }} / {{ $one->statement_no }}
                                    @elseif(!empty($one->bill_no))
                                        {{ $one->bill_no }}
                                    @else
                                        {{ $one->statement_no }}
                                    @endif
                                    <br>
                                @endif --}}

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
                                {{ $type }}
                            </td>

                            <td>
                                {{ $one->location_name }}
                            </td>

                            <td>
                                @if (!empty($status))
                                    {!! $status !!}
                                @endif
                            </td>
                            <td>
                                @if (!empty($one->is_reversed) && $one->is_reversed)
                                    @if (!empty($debit) && $debit != 0)
                                        <span class="text-danger">{{ @num_format(abs($debit)) }}</span>
                                    @endif
                                @else
                                    @if (!empty($credit) && $credit < 0)
                                        {{ abs($credit) }}
                                    @endif

                                    {{ !empty($debit) && $debit > 0 ? @num_format($debit) : '' }}
                                @endif
                            </td>

                            {{-- Credit column --}}
                            <td>
                                @if (!empty($one->is_reversed) && $one->is_reversed)
                                    @if (!empty($credit) && $credit != 0)
                                        <span class="text-danger">{{ @num_format(abs($credit)) }}</span>
                                    @endif
                                @else
                                    @if (!empty($debit) && $debit < 0)
                                        {{ abs($debit) }}
                                    @endif

                                    {{ !empty($credit) && $credit > 0 ? @num_format($credit) : '' }}
                                @endif
                            </td>


                            <td>
                                {{ @num_format($balance) }}
                            </td>

                            <td>
                                @if (empty($pmt_methods) && !empty($one->account_id))
                                    {{ ucfirst(str_replace('_', ' ', $one->account_id)) }}
                                @elseif (!empty($pmt_methods))
                                    {{ ucfirst(str_replace('_', ' ', $pmt_methods->method)) }}

                                    @if (strtolower($pmt_methods->method) == 'bank' ||
                                            strtolower($pmt_methods->method) == 'bank_transfer' ||
                                            strtolower($pmt_methods->method) == 'cheque' ||
                                            strtolower($pmt_methods->method) == 'direct_bank_deposit')
                                        <br>
                                        @if (!empty($pmt_methods->bank_name) || !empty($pmt_methods->account_name))
                                            {!! __('contact.bank_name') . ' ' . ($pmt_methods->bank_name ?? $pmt_methods->account_name) . '<br>' !!}
                                        @endif

                                        @if (!empty($pmt_methods->cheque_number))
                                            {!! __('contact.cheque_number') . ' ' . $pmt_methods->cheque_number . '<br>' !!}
                                        @endif

                                        @if (!empty($pmt_methods->cheque_date))
                                            {!! __('contact.cheque_date') . ' ' . @format_date($pmt_methods->cheque_date) . '<br>' !!}
                                        @endif
                                        @if (($one->type ?? '') == 'cheque_return' && !empty($one->date))
                                            {!! __('lang_v1.cheque_return_date') . ' ' . @format_date($one->date) . '<br>' !!}
                                        @endif
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if ($one->transaction_type == 'hms_booking')
                        @if ($one->payment_status == 'paid')
                            <tr>
                                <td>
                                    @if(!empty($one->updated_at) && $one->updated_at != $one->created_at)
                                        {{ $formatLedgerDateTime($one->updated_at) }}
                                    @else
                                        {{ $formatLedgerDateTime($one->created_at) }}
                                    @endif
                                </td>

                                <td>
                                    {{ $formatLedgerDate($one->date) }}
                                </td>

                                <td>
                                    <!--{!! $description !!}-->
                                    @if (!empty($transaction) && !empty($transaction->ref_no))
                                        <b>Booking No:</b> {{ $transaction->ref_no }} <br>
                                    @endif
                                </td>

                                <td>
                                    @if ($type == 'Payment')
                                        Booking
                                    @endif
                                </td>


                                <td>
                                    {{ $one->location_name }}
                                </td>

                                <td>
                                    @if (!empty($status))
                                        {!! $status !!}
                                    @endif
                                </td>

                                <td>
                                    @if (!empty($debit) && $debit < 0)
                                        {{ abs($debit) }}
                                    @endif

                                    {{ !empty($credit) && $credit > 0 ? @num_format($credit) : '' }}
                                </td>

                                <td>
                                    <!-- nothing -->
                                </td>

                                <td>
                                    {{ @num_format($balance) }}
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
    $("#balance_due").html("{{ @num_format($balance) }}");
    $("#opening_balance").html("{{ @num_format($opening_balance) }}");
    $("#total_sales").html("{{ @num_format($sales) }}");
    $("#total_paid").html("{{ @num_format($paid) }}");

    $("#cheque_return").html("{{ @num_format($cheque_returns) }}");
    $("#loan_to_customer").html("{{ @num_format($loans) }}");
    $("#cheque_return_charges").html("{{ @num_format($cheque_return_charges) }}");
    $("#interest_charged").html("{{ @num_format($interest_charged) }}");
</script>
