@php

    $business_id = session()->get('user.business_id');

    $business_details = App\Business::find($business_id);

    $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;

@endphp

<div class="modal-dialog" role="document" style="width: 85%;">

    <div class="modal-content">

        {!! Form::open([
            'url' => route('settlement-sw.store'),
            'method' => 'post',
            'id' => 'settlement_form',
        ]) !!}



        <div class="modal-header">

            {{-- 

            @ModifiedBy Afes oktavianus

            @DateBy 31-05-2021

            @Task  3350

         --}}



            <h4 class="modal-title pull-left" style="padding-right: 25px">@lang('settlementsw::lang.add_payment')</h4>

            @if (!isset($provider) && $provider != 'SET_SW')
                <h4 class="modal-title pull-left" style="padding-right: 25px">@lang('settlementsw::lang.settlement_no'):
                    {{ $settlement->settlement_no }}</h4>

                <h4 class="modal-title pull-left" style="padding-right: 25px">Shift No : {{ $show_shift_no }}</h4>



                <h4 class="modal-title">@lang('settlementsw::lang.date'): {{ $settlement->transaction_date }}</h4>
            @endif

            <button type="submit" class="btn btn-danger pull-right" data-dismiss="modal">@lang('settlementsw::lang.back')</button>

        </div>



        <div class="modal-body">

            <div class="col-md-12">

                <div class="row">

                    @if (!isset($provider) && $provider != 'SET_SW')
                        <div class="col-md-2 text-center">

                            <b>@lang('settlementsw::lang.pump_operator')</b> <br>

                            {{ isset($pump_operator->name) ? $pump_operator->name : '' }}

                        </div>
                    @endif

                    <div class="col-md-2 text-center">

                        <b>@lang('settlementsw::lang.current_short')</b> <br>



                        {{ @num_format($operator_bal > 0 ? abs($operator_bal) : 0) }}

                    </div>

                    <div class="col-md-2 text-center">

                        <b>@lang('settlementsw::lang.current_excess')</b> <br>



                        {{ @num_format($operator_bal < 0 ? abs($operator_bal) : 0) }}

                    </div>

                    <div class="col-md-2 text-center">

                        <b>@lang('settlementsw::lang.daily_collections')</b> <br>

                        {{ @num_format($total_daily_collection) }}

                    </div>

                    <div class="col-md-2 text-center">

                        <b>@lang('settlementsw::lang.daily_vouchers')</b> <br>

                        {{ @num_format(0) }}

                    </div>

                    <div class="col-md-2 text-center">

                        <b>@lang('settlementsw::lang.commision_ammount')</b> <br>

                        {{ isset($pump_operator->total_commision) ? @num_format($pump_operator->total_commision) : 0 }}

                    </div>

                </div>

                @php

                    $total_paid = !empty($total_paid) ? $total_paid : 0;
                    $total_balance = $total_amount - $total_paid;

                @endphp

                <br><br>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <!-- Show Balance to Operator button only if balance is > 0 -->
                        <button type="button" id="balance_to_operator_btn"
                            class="btn @if ($total_balance == 0) hide @endif"
                            style="background-color: purple; color: white;">
                            Balance to Operator
                        </button>
                    </div>
                </div>
                <br><br>

                <div class="row">

                    <div class="col-md-3 text-center text-red">

                        <b>@lang('settlementsw::lang.total_amount'): </b>

                        <span class="total_amount">{{ @num_format($total_amount) }}</span>

                    </div>

                    <div class="col-md-3 text-center text-red">

                        <b>@lang('settlementsw::lang.total_paid'): </b>

                        <span class="total_paid">{{ @num_format($total_paid) }}</span>

                    </div>



                    <div class="col-md-3 text-center text-red">

                        <b>@lang('settlementsw::lang.balance'): </b>



                        <span class="total_balance">{{ @num_format($total_balance) }}</span>

                    </div>

                    <div class="col-md-3 text-center text-red">

                    </div>




                    <!-- <div class="col-md-3 text-center text-red"> -->

                    <!--<button type="button" id="settlement_save_btn" style="margin-left: 45px;"-->

                    <!--   class="btn btn-primary pull-left @if (!empty($total_balance) && $total_balance == 0)
hide
@endif">@lang('messages.save')</button>-->

                    <!-- <button type="button" id="settlement_save_btn" style="margin-left: 45px;"

                     class="btn btn-primary @if ($total_balance != 0) {{ 'hide' }} @endif pull-left">@lang('messages.save')</button>

                     <button data-href="{{ route('settlement-sw.add-payment.preview', [$settlement->id]) }}" class="btn-modal btn btn-success pull-right" id="payment_review_btn" data-container=".preview_settlement"> @lang('settlementsw::lang.preview')</button>

               </div> -->

                    <div class="col-md-3 text-center text-red">
                        <!-- Show Save button only if balance is zero -->
                        <button type="button" id="settlement_save_btn" style="margin-left: 45px;"
                            class="btn btn-primary @if ($total_balance != 0) hide @endif pull-left">
                            @lang('messages.save')
                        </button>

                        <button
                            data-href="{{ route('settlement-sw.add-payment.preview', [$settlement->id]) }}"
                            class="btn-modal btn btn-success pull-right" id="payment_review_btn"
                            data-container=".preview_settlement">
                            @lang('settlementsw::lang.preview')
                        </button>
                    </div>



                </div>

            </div>

            <input type="hidden" name="settlement_id" value="{{ $settlement->settlementt_no }}">

            <input type="hidden" name="total_balance" id="total_balance"
                value="{{ !empty($total_balance) ? $total_balance : 0 }}">

            <input type="hidden" name="total_amount" id="total_amount"
                value="{{ !empty($total_amount) ? $total_amount : 0 }}">

            <input type="hidden" name="total_paid" id="total_paid" value="{{ !empty($total_paid) ? $total_paid : 0 }}">

            <br><br>

            <div class="clearfix"></div>

            <div style="margin-top: 20px;">

                @include('settlementsw::swsettlement.payments.tabs')

            </div>



            <div class="clearfix"></div>

            {!! Form::close() !!}

            <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">

            </div>



            <div class="modal-footer">

                @if ($is_settlement_page == 0)
                    <button type="submit" class="btn btn-default" data-dismiss="modal">

                        @lang('settlementsw::lang.back')

                    </button>
                @endif

            </div>

        </div><!-- /.modal-content -->

    </div><!-- /.modal-dialog -->

    
<script>
    window.SettlementSwPage = window.SettlementSwPage || {};
    window.SettlementSwPage.total_balance = @json($total_balance ?? 0);
    window.SettlementSwPage.total_excess = @json($total_excess ?? 0);
    window.SettlementSwRoutes = Object.assign(window.SettlementSwRoutes || {}, {
        cashPaymentSave: @json(route('settlement-sw.add-payment.cash-payment.save')),
        cashPaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/cash-payment')) + '/',
        cashDepositSave: @json(route('settlement-sw.add-payment.cash-deposit.save')),
        cashDepositDeleteBase: @json(url('/settlement-sw/sw-add-payment/cash-deposit')) + '/',
        cardPaymentSave: @json(route('settlement-sw.add-payment.card-payment.save')),
        cardPaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/card-payment')) + '/',
        chequePaymentSave: @json(route('settlement-sw.add-payment.cheque-payment.save')),
        chequePaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/cheque-payment')) + '/',
        creditSalePaymentSave: @json(route('settlement-sw.add-payment.credit-sale-payment.save')),
        creditSalePaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/credit-sale-payment')) + '/',
        customerLoanSave: @json(route('settlement-sw.add-payment.customer-loan.save')),
        customerLoanDeleteBase: @json(url('/settlement-sw/sw-add-payment/customer-loan')) + '/',
        loanPaymentSave: @json(route('settlement-sw.add-payment.loan-payment.save')),
        loanPaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/loan-payment')) + '/',
        drawingPaymentSave: @json(route('settlement-sw.add-payment.drawing-payment.save')),
        drawingPaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/drawing-payment')) + '/',
        expensePaymentSave: @json(route('settlement-sw.add-payment.expense-payment.save')),
        expensePaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/expense-payment')) + '/',
        shortagePaymentSave: @json(route('settlement-sw.add-payment.shortage-payment.save')),
        shortagePaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/shortage-payment')) + '/',
        excessPaymentSave: @json(route('settlement-sw.add-payment.excess-payment.save')),
        excessPaymentDeleteBase: @json(url('/settlement-sw/sw-add-payment/excess-payment')) + '/',
        productPrice: @json(route('settlement-sw.add-payment.product-price')),
        customerDetailsBase: @json(url('/settlement-sw/sw-add-payment/customer-details')) + '/'
    });
</script>
<script src="{{ asset('modules/settlementsw/js/swsettlement/payments-add-payment.js') }}"></script>

