@php
    // The controller already resolved the active tenant/business. Do not read a
    // possibly stale shared session again inside Settlement Preview.
    $business_id = (int) (($business->id ?? null) ?: ($settlement->business_id ?? 0));
    $business_details = !empty($business) ? $business : \App\Business::where('id', $business_id)->first();
    $currency_precision = !empty($business_details->currency_precision) ? (int) $business_details->currency_precision : 2;

    $money = function ($value) use ($currency_precision) {
        return number_format((float) ($value ?? 0), $currency_precision, '.', ',');
    };

    $qty = function ($value, $precision = 3) {
        return number_format((float) ($value ?? 0), $precision, '.', ',');
    };

    $safeAccountName = function ($id) use ($business_id) {
        if (empty($id)) { return ''; }
        $account = \App\Account::where('id', $id)->where('business_id', $business_id)->first();
        return !empty($account) ? $account->name : '';
    };

    $safeContactName = function ($id) use ($business_id) {
        if (empty($id)) { return ''; }
        $contact = \App\Contact::where('id', $id)->where('business_id', $business_id)->first();
        return !empty($contact) ? $contact->name : '';
    };

    $safeProductName = function ($id) use ($business_id) {
        if (empty($id)) { return ''; }
        $product = \App\Product::where('id', $id)->where('business_id', $business_id)->first();
        return !empty($product) ? $product->name : '';
    };

    $safeProductSku = function ($id) use ($business_id) {
        if (empty($id)) { return ''; }
        $product = \App\Product::where('id', $id)->where('business_id', $business_id)->first();
        return !empty($product) ? $product->sku : '';
    };

    $settlement_display_no = !empty($settlement->settlement_no) ? $settlement->settlement_no : $settlement->id;
@endphp

<div class="modal-dialog" role="document" style="width: 90%; max-width: 1400px;">
    <div class="modal-content pd-preview-modal">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{ $settlement_display_no }}</h4>
        </div>

        <div class="modal-body">
            <style>
                .pd-preview-modal .pd-preview-title { font-weight: 700; margin: 12px 0; font-size: 18px; text-align: center; }
                .pd-preview-modal .pd-section-title { color: #a94442; font-weight: 700; margin-top: 18px; }
                .pd-preview-modal .pd-money, .pd-preview-modal .pd-qty, .pd-preview-modal .pd-action-col { text-align: right !important; white-space: nowrap; }
                .pd-preview-modal .pd-action-col { width: 130px; }
                .pd-preview-modal .pd-empty { text-align: center; color: #777; }
                .pd-preview-modal .table > tbody > tr > td, .pd-preview-modal .table > thead > tr > th, .pd-preview-modal .table > tfoot > tr > th, .pd-preview-modal .table > tfoot > tr > td { vertical-align: middle; }
                .pd-preview-modal .pd-preview-actions .btn { margin: 1px 2px; }
                .pd-preview-modal .pd-total-row th, .pd-preview-modal .pd-total-row td { font-weight: 700; }
                .pd-preview-modal .table-responsive { border: 0; }
            </style>

            <div class="pd-preview-title">Settlement Preview - Confirm Before Finalizing</div>

            <h4 class="pd-section-title">@lang('petrodirect::lang.meter_sale')</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('petrodirect::lang.code')</th>
                            <th>@lang('petrodirect::lang.products')</th>
                            <th>@lang('petrodirect::lang.pump')</th>
                            <th class="pd-qty">@lang('petrodirect::lang.starting_meter')</th>
                            <th class="pd-qty">@lang('petrodirect::lang.closing_meter')</th>
                            <th class="pd-money">@lang('petrodirect::lang.unit_price')</th>
                            <th class="pd-qty">@lang('petrodirect::lang.sold_qty')</th>
                            <th class="pd-qty">@lang('petrodirect::lang.testing_qty')</th>
                            <th class="pd-money">@lang('petrodirect::lang.total')</th>
                            <th class="pd-action-col">@lang('petrodirect::lang.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $preview_meter_total = 0; @endphp
                        @forelse ($settlement->meter_sales as $item)
                            @php
                                $pump = \Modules\PetroDirect\Entities\Pump::where('id', $item->pump_id)->where('business_id', $business_id)->first();
                                $sold_qty = (($item->closing_meter ?? 0) - ($item->starting_meter ?? 0)) - ($item->testing_qty ?? 0);
                                if (!empty($pump) && !empty($pump->bulk_sale_meter)) { $sold_qty = $item->qty ?? 0; }
                                if ($sold_qty < 0) { $sold_qty = 0; }
                                $line_total = $item->discount_amount ?? $item->sub_total ?? 0;
                                if ($line_total < 0) { $line_total = abs($line_total); }
                                $preview_meter_total += $line_total;
                            @endphp
                            <tr>
                                <td>{{ $safeProductSku($item->product_id) }}</td>
                                <td>{{ $safeProductName($item->product_id) }}</td>
                                <td>{{ !empty($pump) ? $pump->pump_no : '' }}</td>
                                <td class="pd-qty">{{ $qty($item->starting_meter, 3) }}</td>
                                <td class="pd-qty">{{ $qty($item->closing_meter, 3) }}</td>
                                <td class="pd-money">{{ $money($item->price) }}</td>
                                <td class="pd-qty">{{ $qty($sold_qty, 3) }}</td>
                                <td class="pd-qty">{{ $qty($item->testing_qty, 3) }}</td>
                                <td class="pd-money">{{ $money($line_total) }}</td>
                                <td class="pd-action-col pd-preview-actions">
                                    <button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#meter_sale_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button>
                                    <button type="button" class="btn btn-xs btn-danger pd-preview-delete-payment" data-href="{{ url('petrodirect/settlement/delete-meter-sale/' . $item->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="pd-empty">No meter sales added.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="pd-total-row"><th colspan="8" class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($preview_meter_total) }}</th><th></th></tr></tfoot>
                </table>
            </div>

            <h4 class="pd-section-title">@lang('petrodirect::lang.other_sale')</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>@lang('petrodirect::lang.code')</th><th>@lang('petrodirect::lang.products')</th><th class="pd-money">@lang('petrodirect::lang.unit_price')</th><th class="pd-qty">@lang('petrodirect::lang.qty')</th><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead>
                    <tbody>
                        @php $preview_other_sale_total = 0; @endphp
                        @forelse ($settlement->other_sales as $ot_item)
                            @php $amount = ($ot_item->sub_total ?? 0) - ($ot_item->discount_amount ?? 0); $preview_other_sale_total += $amount; @endphp
                            <tr>
                                <td>{{ $safeProductSku($ot_item->product_id) }}</td><td>{{ $safeProductName($ot_item->product_id) }}</td><td class="pd-money">{{ $money($ot_item->price) }}</td><td class="pd-qty">{{ $qty($ot_item->qty, 3) }}</td><td class="pd-money">{{ $money($amount) }}</td>
                                <td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#other_sale_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger pd-preview-delete-payment" data-href="{{ url('petrodirect/settlement/delete-other-sale/' . $ot_item->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="pd-empty">No other sales added.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="pd-total-row"><th colspan="4" class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($preview_other_sale_total) }}</th><th></th></tr></tfoot>
                </table>
            </div>

            <h4 class="pd-section-title">@lang('petrodirect::lang.other_income')</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>@lang('petrodirect::lang.service')</th><th class="pd-qty">@lang('petrodirect::lang.qty')</th><th>@lang('petrodirect::lang.reason')</th><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead>
                    <tbody>
                        @php $preview_other_income_total = 0; @endphp
                        @forelse ($settlement->other_incomes as $other_income_item)
                            @php $preview_other_income_total += ($other_income_item->sub_total ?? 0); @endphp
                            <tr><td>{{ $safeProductName($other_income_item->product_id) }}</td><td class="pd-qty">{{ $qty($other_income_item->qty, 3) }}</td><td>{{ $other_income_item->reason ?? '' }}</td><td class="pd-money">{{ $money($other_income_item->sub_total) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#other_income_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger pd-preview-delete-payment" data-href="{{ url('petrodirect/settlement/delete-other-income/' . $other_income_item->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                        @empty
                            <tr><td colspan="5" class="pd-empty">No other income added.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="pd-total-row"><th colspan="3" class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($preview_other_income_total) }}</th><th></th></tr></tfoot>
                </table>
            </div>

            <h4 class="pd-section-title">@lang('petrodirect::lang.customer_payment')</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>@lang('petrodirect::lang.customer')</th><th>@lang('petrodirect::lang.payment_method')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead>
                    <tbody>
                        @php $preview_customer_payment_total = 0; @endphp
                        @forelse (($customer_payments_tab ?? collect()) as $customer_payment_item)
                            @php $preview_customer_payment_total += ($customer_payment_item->sub_total ?? 0); @endphp
                            <tr><td>{{ $customer_payment_item->customer_name ?? '' }}</td><td>{{ ucfirst($customer_payment_item->payment_method ?? '') }}</td><td class="pd-money">{{ $money($customer_payment_item->sub_total) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#customer_payment_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger pd-preview-delete-payment" data-href="{{ url('petrodirect/settlement/delete-customer-payment/' . ($customer_payment_item->id ?? 0)) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                        @empty
                            <tr><td colspan="4" class="pd-empty">No customer payments added.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="pd-total-row"><th colspan="2" class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($preview_customer_payment_total) }}</th><th></th></tr></tfoot>
                </table>
            </div>

            <hr>
            <div class="pd-preview-title">@lang('petrodirect::lang.payment_details')</div>

            {{-- Loan Payments --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.loan_payments')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.loan_account')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->loan_payments as $loan)
                    <tr><td>{{ $safeAccountName($loan->loan_account) }}</td><td class="pd-money">{{ $money($loan->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#loan_payments_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_loan_payment" data-href="{{ url('petrodirect/settlement/payment/delete-loan-payment/' . $loan->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="3" class="pd-empty">No loan payments added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->loan_payments->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Drawing Payments --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.drawing_payments')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.account')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->drawings_payments as $drawing)
                    <tr><td>{{ $safeAccountName($drawing->loan_account) }}</td><td class="pd-money">{{ $money($drawing->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#drawing_payments_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_drawing_payment" data-href="{{ url('petrodirect/settlement/payment/delete-drawing-payment/' . $drawing->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="3" class="pd-empty">No drawing payments added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->drawings_payments->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Cash --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.cash')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.customer')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th>@lang('petrodirect::lang.note')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->cash_payments as $cash)
                    <tr><td>{{ $safeContactName($cash->customer_id) }}</td><td class="pd-money">{{ $money($cash->amount) }}</td><td>{{ $cash->note ?? '' }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#cash_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_cash_payment" data-href="{{ url('petrodirect/settlement/payment/delete-cash-payment/' . $cash->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="4" class="pd-empty">No cash payments added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->cash_payments->sum('amount')) }}</th><th colspan="2"></th></tr></tfoot></table></div>

            {{-- Cash Deposit --}}
            @php
                $cash_deposits_to_display = $settlement->cash_deposits;
                if ($cash_deposits_to_display->isEmpty()) {
                    $cash_deposits_to_display = \Modules\PetroDirect\Entities\SettlementCashDeposit::where('business_id', $business_id)
                        ->whereIn('settlement_no', [$settlement->settlement_no, $settlement->id])
                        ->get();
                }
            @endphp
            <h4 class="pd-section-title">@lang('petrodirect::lang.cash_deposit')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.bank')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($cash_deposits_to_display as $cash_deposit)
                    <tr><td>{{ $safeAccountName($cash_deposit->bank_id) }}</td><td class="pd-money">{{ $money($cash_deposit->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#cash_deposit_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_cash_deposit" data-href="{{ url('petrodirect/settlement/payment/delete-cash-deposit/' . $cash_deposit->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="3" class="pd-empty">No cash deposits added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($cash_deposits_to_display->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Cards --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.cards')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.customer')</th><th>@lang('petrodirect::lang.card_number')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->card_payments as $card)
                    <tr><td>{{ $safeContactName($card->customer_id) }}</td><td>{{ $card->card_number }}</td><td class="pd-money">{{ $money($card->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#cards_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_card_payment" data-href="{{ url('petrodirect/settlement/payment/delete-card-payment/' . $card->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="4" class="pd-empty">No card payments added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th colspan="2" class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->card_payments->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Cheques --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.cheques')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.customer')</th><th>@lang('petrodirect::lang.bank_name')</th><th>@lang('petrodirect::lang.cheque_number')</th><th>@lang('petrodirect::lang.cheque_date')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->cheque_payments as $cheque)
                    <tr><td>{{ $safeContactName($cheque->customer_id) }}</td><td>{{ $cheque->bank_name }}</td><td>{{ $cheque->cheque_number }}</td><td>{{ $cheque->cheque_date }}</td><td class="pd-money">{{ $money($cheque->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#cheques_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_cheque_payment" data-href="{{ url('petrodirect/settlement/payment/delete-cheque-payment/' . $cheque->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="6" class="pd-empty">No cheque payments added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th colspan="4" class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->cheque_payments->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Credit Sales --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.credit_sales')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.customer')</th><th>@lang('petrodirect::lang.order_number')</th><th>@lang('petrodirect::lang.order_date')</th><th>@lang('petrodirect::lang.product')</th><th class="pd-qty">@lang('petrodirect::lang.qty')</th><th class="pd-money">@lang('petrodirect::lang.sub_total')</th><th class="pd-money">@lang('petrodirect::lang.discount_total')</th><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->credit_sale_payments as $credit_sale)
                    <tr><td>{{ $safeContactName($credit_sale->customer_id) }}</td><td>{{ $credit_sale->order_number }}</td><td>{{ $credit_sale->order_date }}</td><td>{{ $safeProductName($credit_sale->product_id) }}</td><td class="pd-qty">{{ $qty($credit_sale->qty, 3) }}</td><td class="pd-money">{{ $money($credit_sale->amount) }}</td><td class="pd-money">{{ $money($credit_sale->total_discount) }}</td><td class="pd-money">{{ $money(($credit_sale->amount ?? 0) - ($credit_sale->total_discount ?? 0)) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#credit_sales_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="{{ url('petrodirect/settlement/payment/delete-credit-sale-payment/' . $credit_sale->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="9" class="pd-empty">No credit sales added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th colspan="5" class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->credit_sale_payments->sum('amount')) }}</th><th class="pd-money">{{ $money($settlement->credit_sale_payments->sum('total_discount')) }}</th><th class="pd-money">{{ $money($settlement->credit_sale_payments->sum('amount') - $settlement->credit_sale_payments->sum('total_discount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Expense --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.expense')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.expense_number')</th><th>@lang('petrodirect::lang.reference_no')</th><th>@lang('petrodirect::lang.reason')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->expense_payments as $expense)
                    <tr><td>{{ $expense->expense_number }}</td><td>{{ $expense->reference_no }}</td><td>{{ $expense->reason ?? '' }}</td><td class="pd-money">{{ $money($expense->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#expense_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_expense_payment" data-href="{{ url('petrodirect/settlement/payment/delete-expense-payment/' . $expense->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="5" class="pd-empty">No expenses added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th colspan="3" class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->expense_payments->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Shortage --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.shortage')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.note')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->shortage_payments as $shortage)
                    <tr><td>{{ $shortage->note ?? '' }}</td><td class="pd-money">{{ $money($shortage->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#shortage_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_shortage_payment" data-href="{{ url('petrodirect/settlement/payment/delete-shortage-payment/' . $shortage->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="3" class="pd-empty">No shortage payments added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->shortage_payments->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Excess --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.excess')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.note')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->excess_payments as $excess)
                    <tr><td>{{ $excess->note ?? '' }}</td><td class="pd-money">{{ $money($excess->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#excess_tab"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_excess_payment" data-href="{{ url('petrodirect/settlement/payment/delete-excess-payment/' . $excess->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="3" class="pd-empty">No excess payments added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->excess_payments->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            {{-- Customer Loans --}}
            <h4 class="pd-section-title">@lang('petrodirect::lang.customer_loans')</h4>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>@lang('petrodirect::lang.customer')</th><th class="pd-money">@lang('petrodirect::lang.amount')</th><th class="pd-action-col">@lang('petrodirect::lang.action')</th></tr></thead><tbody>
                @forelse ($settlement->customer_loans as $loan)
                    <tr><td>{{ $safeContactName($loan->customer_id) }}</td><td class="pd-money">{{ $money($loan->amount) }}</td><td class="pd-action-col pd-preview-actions"><button type="button" class="btn btn-xs btn-primary pd-preview-edit-payment" data-tab="#settlement_customer_loans"><i class="fa fa-edit"></i> @lang('messages.edit')</button><button type="button" class="btn btn-xs btn-danger delete_customer_loans_payment" data-href="{{ url('petrodirect/settlement/payment/delete-customer-loans/' . $loan->id) }}"><i class="fa fa-trash"></i> @lang('messages.delete')</button></td></tr>
                @empty <tr><td colspan="3" class="pd-empty">No customer loans added.</td></tr> @endforelse
            </tbody><tfoot><tr class="pd-total-row"><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($settlement->customer_loans->sum('amount')) }}</th><th></th></tr></tfoot></table></div>

            @php
                $cash_deposits_total = $cash_deposits_to_display->sum('amount');
                $payment_grand_total = $settlement->loan_payments->sum('amount')
                    + $settlement->drawings_payments->sum('amount')
                    + $settlement->cash_payments->sum('amount')
                    + $cash_deposits_total
                    + $settlement->card_payments->sum('amount')
                    + $settlement->cheque_payments->sum('amount')
                    + ($settlement->credit_sale_payments->sum('amount') - $settlement->credit_sale_payments->sum('total_discount'))
                    + $settlement->expense_payments->sum('amount')
                    + $settlement->shortage_payments->sum('amount')
                    + $settlement->excess_payments->sum('amount')
                    + $settlement->customer_loans->sum('amount');
            @endphp
            <div class="table-responsive">
                <table class="table table-bordered table-striped"><tfoot><tr class="pd-total-row"><th class="pd-money">@lang('petrodirect::lang.total')</th><th class="pd-money">{{ $money($payment_grand_total) }}</th></tr></tfoot></table>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="modal-footer">
            <button type="button" id="confirm_settlement_preview_details" class="btn btn-primary pull-left" style="background-color: #007bff; color: #ffffff; border-color: #006fe6;">Confirm all the entered details are correct</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<script>
(function ($) {
    $(document).off('click.pd_preview_edit', '.pd-preview-edit-payment').on('click.pd_preview_edit', '.pd-preview-edit-payment', function () {
        var target = $(this).data('tab');
        $('.preview_settlement').modal('hide');
        setTimeout(function () {
            if (target) {
                var $link = $('.add_payment a[href="' + target + '"], .add_payment a[data-pd-target="' + target + '"]').first();
                if ($link.length) {
                    $link.trigger('click');
                    try { $link.tab('show'); } catch (e) {}
                }
            }
            $('.add_payment').modal('show');
        }, 300);
    });

    $(document).off('click.pd_preview_delete_generic', '.pd-preview-delete-payment').on('click.pd_preview_delete_generic', '.pd-preview-delete-payment', function () {
        var $button = $(this);
        var url = $button.data('href');
        if (!url) { return false; }
        if (!confirm('Are you sure you want to delete this record?')) { return false; }
        $.ajax({
            method: 'DELETE',
            url: url,
            data: { is_edit: $('#is_edit').val() || 0, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (result) {
                if (result && result.success === false) {
                    toastr.error(result.msg || 'Something went wrong.');
                    return;
                }
                toastr.success((result && result.msg) ? result.msg : 'Deleted successfully.');
                $button.closest('tr').remove();
            },
            error: function () {
                toastr.error('Something went wrong. Please try again later.');
            }
        });
        return false;
    });
})(jQuery);
</script>
