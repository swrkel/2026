{{--
    Payments — 8048.

    The summary bar, then a payment type, then the fields that type needs.

    Eleven types share one form because they mostly want the same three things:
    an amount, a note, and sometimes a customer. Eleven separate forms would be
    eleven places for the same bug.
--}}

<div class="sw-section" id="sw_sec_payments">

    <div class="sw-section-head" data-toggle="collapse" data-target="#sw_body_payments">
        <i class="fa fa-chevron-down sw-caret"></i>
        <span>@lang('sw::lang.payments')</span>
        <span class="sw-section-total" id="sw_pay_head_total">0.00</span>
    </div>

    <div class="collapse in" id="sw_body_payments">
        <div class="sw-section-body">

            {{-- What the operator is carrying before anything is paid. --}}
            <div class="row sw-pay-summary">
                <div class="col-md-2 text-center">
                    <div class="sw-pay-label">@lang('sw::lang.current_short')</div>
                    <div class="sw-pay-value" id="sw_cur_short">0.00</div>
                </div>
                <div class="col-md-2 text-center">
                    <div class="sw-pay-label">@lang('sw::lang.current_excess')</div>
                    <div class="sw-pay-value" id="sw_cur_excess">0.00</div>
                </div>
                <div class="col-md-3 text-center">
                    <div class="sw-pay-label">@lang('sw::lang.daily_cash')</div>
                    <div class="sw-pay-value" id="sw_daily_cash">0.00</div>
                </div>
                <div class="col-md-3 text-center">
                    <div class="sw-pay-label">@lang('sw::lang.daily_credit_sales')</div>
                    <div class="sw-pay-value" id="sw_daily_credit">0.00</div>
                </div>
                <div class="col-md-2 text-center">
                    <div class="sw-pay-label">@lang('sw::lang.commission_amount')</div>
                    <div class="sw-pay-value" id="sw_commission">0.00</div>
                </div>
            </div>

            {{--
                S752: keep the settlement actions beside the authoritative
                totals.  This row is visible as soon as Payments is opened and
                remains sticky while the operator reviews the payment lines.
            --}}
            <div class="row sw-pay-totals sw-settlement-save-bar" id="sw_st_save_dock" aria-live="polite">
                <div class="col-md-3">
                    <strong>@lang('sw::lang.total_amount'):</strong>
                    <span id="sw_total_amount">0.00</span>
                </div>
                <div class="col-md-3">
                    <strong>@lang('sw::lang.total_paid'):</strong>
                    <span id="sw_total_paid">0.00</span>
                </div>
                <div class="col-md-3">
                    {{-- Negative means the operator is owed. Coloured, because a
                         balance that should be zero and is not is the thing
                         worth noticing on this screen. --}}
                    <strong>@lang('sw::lang.balance'):</strong>
                    <span id="sw_balance">0.00</span>
                </div>
                <div class="col-md-3 sw-settlement-save-actions">
                    <button type="button" class="btn btn-primary" id="sw_st_save_btn" disabled>
                        <i class="fa fa-save"></i>
                        <span class="sw-save-label">@lang('sw::lang.save_settlement')</span>
                    </button>
                    <a href="{{ route('sw.settlements.index', [], false) }}" class="btn btn-default" id="sw_st_cancel_btn">
                        @lang('messages.cancel')
                    </a>
                    <div class="sw-settlement-save-hint" id="sw_st_save_hint">
                        @lang('sw::lang.settlement_balance_must_be_zero')
                    </div>
                </div>
            </div>

            <div class="sw-pay-types" role="tablist" aria-label="{{ __('sw::lang.payments') }}">
                @php
                    $swPayTypes = [
                        ['cash', __('sw::lang.cash'), 'sw-pt-cash', 'fa-money'],
                        ['cash_deposit', __('sw::lang.cash_deposit'), 'sw-pt-deposit', 'fa-credit-card'],
                        ['card', __('sw::lang.cards'), 'sw-pt-card', 'fa-credit-card'],
                        ['cheque', __('sw::lang.cheques'), 'sw-pt-cheque', 'fa-pencil'],
                        ['expense', __('sw::lang.expenses'), 'sw-pt-expense', 'fa-bell'],
                        ['shortage', __('sw::lang.shortage'), 'sw-pt-shortage', 'fa-arrow-down'],
                        ['excess', __('sw::lang.excess'), 'sw-pt-excess', 'fa-arrow-up'],
                        ['credit_sale', __('sw::lang.credit_sales'), 'sw-pt-credit', 'fa-credit-card'],
                        ['loan_payment', __('sw::lang.loan_payments'), 'sw-pt-loan', 'fa-credit-card'],
                        ['owners_drawing', __('sw::lang.owners_drawings'), 'sw-pt-drawing', 'fa-credit-card'],
                        ['loan_to_customer', __('sw::lang.loan_to_customer'), 'sw-pt-loanout', 'fa-credit-card'],
                    ];
                @endphp

                @foreach ($swPayTypes as [$key, $label, $cls, $icon])
                    <button type="button" class="sw-pay-btn {{ $cls }}" data-type="{{ $key }}" role="tab" aria-selected="false" aria-pressed="false">
                        <i class="fa {{ $icon }}"></i> {{ $label }}
                    </button>
                @endforeach
            </div>

            {{--
                IS2235: Credit Sale belongs INSIDE Payments.
                The old build rendered the detailed Credit Sale form as a separate
                top-level settlement section, while the Payments -> Credit Sales
                button showed only the generic Customer / Amount / Note controls.
                Keep one authoritative detailed form and render it here.
            --}}
            @include('sw::settlements.partials.credit_sales', ['embedded_in_payments' => true])

            <div class="alert alert-info" id="sw_cash_source_notice" style="display:none;margin:14px 0 0">
                <i class="fa fa-lock"></i>
                @lang('sw::lang.cash_follows_daily_shift_payment')
            </div>

            <div class="row sw-pay-entry" style="margin-top:14px">
                {{-- Shown only for the types that need a customer. --}}
                <div class="col-md-3 sw-pay-field" data-for="customer" style="display:none">
                    <div class="form-group">
                        {!! Form::label('sw_pay_customer', __('sw::lang.customer') . ':') !!}
                        <select class="form-control sw-select2" id="sw_pay_customer" style="width:100%">
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3 sw-pay-field" data-for="expense_category" style="display:none">
                    <div class="form-group">
                        {!! Form::label('sw_pay_expense_category', __('sw::lang.expense_category') . ':') !!}
                        {!! Form::select('sw_pay_expense_category', $expense_categories ?? [], null, [
                            'class' => 'form-control sw-select2',
                            'id' => 'sw_pay_expense_category',
                            'style' => 'width:100%',
                            'placeholder' => __('messages.please_select'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3 sw-pay-field" data-for="account" style="display:none">
                    <div class="form-group">
                        <label for="sw_pay_account" id="sw_pay_account_label">@lang('sw::lang.account'):</label>
                        <select class="form-control sw-select2" id="sw_pay_account" style="width:100%">
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('sw_pay_amount', __('sw::lang.amount') . ':') !!}
                        <input type="number" step="0.01" class="form-control text-right"
                               id="sw_pay_amount" placeholder="0.00">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_pay_note', __('sw::lang.payment_note') . ':') !!}
                        <textarea class="form-control" id="sw_pay_note" rows="2"></textarea>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-block" id="sw_pay_add">
                            @lang('messages.add')
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive" id="sw_pay_table_wrap" style="margin-top:10px">
                <table class="table table-bordered table-condensed sw-lines-table" id="sw_pay_table">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.type')</th>
                            <th>@lang('sw::lang.customer')</th>
                            <th>@lang('sw::lang.account')</th>
                            <th>@lang('sw::lang.expense_category')</th>
                            <th class="text-right">@lang('sw::lang.amount')</th>
                            <th>@lang('sw::lang.payment_note')</th>
                            <th class="text-center">@lang('messages.action')</th>
                        </tr>
                    </thead>
                    {{--
                        S732 final: keep exactly ONE visible tbody.  Only rows for
                        the currently selected payment type are ever rendered into
                        the DOM.  All payment types are still preserved separately
                        in #sw_pay_hidden_inputs for final submission.
                    --}}
                    <tbody id="sw_pay_rows">
                        <tr class="sw-pay-empty">
                            <td colspan="7" class="text-center text-muted" style="padding:16px">
                                @lang('sw::lang.no_payments_yet')
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray">
                            <td colspan="4" class="text-right"><strong>@lang('sw::lang.total_paid')</strong></td>
                            <td class="text-right"><strong id="sw_pay_total">0.00</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{--
                Keep submission fields separate from the currently visible payment-type
                table. This lets the UI show only the selected type without dropping
                payments entered under the other payment types when the settlement is
                finally submitted.
            --}}
            <div id="sw_pay_hidden_inputs" style="display:none"></div>

        </div>
    </div>
</div>
