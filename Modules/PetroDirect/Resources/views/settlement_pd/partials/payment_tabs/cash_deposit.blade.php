@php



    $cashdenoms = $settlement->cash_denomination;

    if(!empty($cashdenoms)){

        $cashdenoms = json_decode($cashdenoms,true);

    }else{

        $cashdenoms = [];

    }



@endphp





<div class="col-md-12">

    <div class="row">

        <div class="col-md-3">

            <div class="form-group">

                {!! Form::label('cash_deposit_bank', __('petrodirect::lang.bank').':') !!}

                {!! Form::select('cash_deposit_bank', $bank_accounts, null, ['class' => 'form-control

                select2', 'style' => 'width: 100%;']); !!}

            </div>

        </div>

        <div class="col-md-3">

            <div class="form-group">

                {!! Form::label('cash_deposit_amount', __( 'petrodirect::lang.amount' ) ) !!}

                {!! Form::text('cash_deposit_amount', null, ['class' => 'form-control cash_deposit_fields cust_input_number

                cash_deposit_amount', 'required',

                'placeholder' => __(

                'petrodirect::lang.amount' ) ]); !!}

            </div>

        </div>

        <div class="col-md-3">

            <div class="form-group">

                {!! Form::label('cash_deposit_account', __( 'petrodirect::lang.receipt_no' ) ) !!}

                {!! Form::text('cash_deposit_account', null, ['class' => 'form-control cash_deposit_fields cust_input_number

                cash_deposit_account', 'required',

                'placeholder' => __(

                'petrodirect::lang.receipt_no' ) ]); !!}

            </div>

        </div>

        

        <div class="col-md-3">

            <div class="form-group">

                {!! Form::label('cash_deposit_time', __( 'petrodirect::lang.time' ) ) !!}

               {!! Form::input('datetime-local', 'cash_deposit_time', null, [

                    'class' => 'form-control cash_deposit_fields',

                    'required',

                    'placeholder' => __('petrodirect::lang.time')

                ]) !!}



            </div>

        </div>

        

        

        <div class="col-md-1">

            <button type="button" class="btn btn-primary cash_deposit_add"

            style="margin-top: 23px;">@lang('messages.add')</button>

        </div>

        

    </div>

    

</div>

<br><br>



<div class="row" style="margin-top: 10px">

    <div class="col-md-12">

        <table class="table table-bordered table-striped" id="cash_deposit_table">

            <thead>

                <tr>

                    <th>@lang('petrodirect::lang.bank' )</th>

                    <th>@lang('petrodirect::lang.receipt_no' )</th>

                    <th>@lang('petrodirect::lang.amount' )</th>

                    <th>@lang('petrodirect::lang.time' )</th>

                    <th>@lang('petrodirect::lang.action' )</th>

                </tr>

            </thead>

            <tbody id="cash_deposit_table_body">

                @php

                    $cash_total = $settlement_cash_deposits->sum('amount');

                @endphp

                @foreach ($settlement_cash_deposits as $cash_payment)

                    <tr class="paymt-{{ $cash_payment->customer_payment_id }}">

                        <td>{{$cash_payment->bank_name}}</td>

                        <td>{{$cash_payment->account_no}}</td>

                        <td class="cash_deposit_amount">{{number_format($cash_payment->amount, $currency_precision)}}</td>

                        <td>{{@format_datetime($cash_payment->time_deposited)}}</td>

                        <td><button type="button" class="btn btn-xs btn-danger delete_cash_payment" data-href="/petrodirect/settlement/payment/delete-cash-deposit/{{$cash_payment->id}}"><i

                                    class="fa fa-times"></i></button></td>

                    </tr>

                @endforeach

               

            </tbody>



            <tfoot>

                <tr>

                    <td style="text-align: right; font-weight: bold;">@lang('petrodirect::lang.settlement_cash_deposit_total') :</td>

                    <td style="text-align: left; font-weight: bold;" class="cash_deposit_total">

                    {{number_format($cash_total, $currency_precision)}}</td>

                </tr>

                <input type="hidden" value="{{$cash_total}}" name="cash_deposit_total" id="cash_deposit_total">

            </tfoot>

        </table>

    </div>

</div>







<script>

    $('#cash_deposit_bank').select2();

    $(document).ready(function(){

        

        $("#cash_deposit_bank").val($("#cash_deposit_bank option:eq(0)").val()).trigger('change');

        

        

    });

</script>