<div class="col-md-12">
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('cheque_customer_id', __('petrogeneral::lang.customer').':') !!}
                {!! Form::select('cheque_customer_id', $credit_customers, null, ['class' => 'form-control select2', 'style' => 'width: 100%;']); !!}
            </div>
        </div>
       
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('bank_name', __( 'petrogeneral::lang.bank_name' ) ) !!}
                {!! Form::text('bank_name', null, ['class' => 'form-control cheque_fields
                bank_name',
                'placeholder' => __(
                'petrogeneral::lang.bank_name' ) ]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('cheque_date', __( 'petrogeneral::lang.cheque_date' ) ) !!}
                {!! Form::text('cheque_date', null, ['class' => 'form-control cheque_fields
                cheque_date',
                'placeholder' => __(
                'petrogeneral::lang.cheque_date' ) ]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('cheque_number', __( 'petrogeneral::lang.cheque_number' ) ) !!}
                {!! Form::text('cheque_number', null, ['class' => 'form-control cheque_fields input_number
                cheque_number',
                'placeholder' => __(
                'petrogeneral::lang.cheque_number' ) ]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('cheque_amount', __( 'petrogeneral::lang.amount' ) ) !!}
                {!! Form::text('cheque_amount', null, ['class' => 'form-control cheque_fields cust_input_number
                cheque_amount', 'required',
                'placeholder' => __(
                'petrogeneral::lang.amount' ) ]); !!}
            </div>
        </div>
        
        @php
                    
                $business_id = request()
                    ->session()
                    ->get('user.business_id');
                
                $pacakge_details = [];
                    
                $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
                if (!empty($subscription)) {
                    $pacakge_details = $subscription->package_details;
                }
            
            @endphp
            
            @if(!empty($pacakge_details['show_post_dated_cheque']))
              <div class="col-md-4 text-center" >
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('post_dated_cheque', '1', false,
                            [ 'class' => 'input-icheck cheque_fields','id' => 'cheque_post_dated_cheque']); !!} {{ __( 'account.post_dated_cheque' ) }}
                        </label>
                    </div>
                </div>
            @endif

        
        <div class="col-md-6">
            <div class="form-group">
              {!! Form::label("cheque_note", __('lang_v1.payment_note') . ':') !!}
              {!! Form::textarea("cheque_note", null, ['class' => 'form-control cash_fields', 'rows' => 3]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-primary cheque_add"
            style="margin-top: 23px;">@lang('messages.add')</button>
        </div>
    </div>
</div>
<br><br>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="cheque_table">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.cusotmer_name' )</th>
                    <th>@lang('petrogeneral::lang.bank_name' )</th>
                    <th>@lang('petrogeneral::lang.cheque_number' )</th>
                    <th>@lang('petrogeneral::lang.cheque_date' )</th>
                    <th>@lang('petrogeneral::lang.amount' )</th>
                    <th>@lang('lang_v1.note') </th>
                    <th>@lang('petrogeneral::lang.action' )</th>
                </tr>
            </thead>
            <tbody id="cheque_table_body">
                @php
                    $cheque_total = $settlement_cheque_payments->sum('amount');
                @endphp
                @foreach ($settlement_cheque_payments as $cheque_payment)
                    <tr class="paymt-{{ $cheque_payment->customer_payment_id }}">
                        <td>{{$cheque_payment->customer_name}}</td>
                        <td>{{$cheque_payment->bank_name}}</td>
                        <td>{{$cheque_payment->cheque_number}}</td>
                        <td>{{@format_date($cheque_payment->cheque_date)}}</td>
                        <td class="cheque_amount">{{number_format($cheque_payment->amount, $currency_precision)}}</td>
                        <td>{{$cheque_payment->note}}</td>
                        <td>
                        @if(!empty($cheque_payment->pump_payment_id))
                            <button type="button" class="btn btn-xs btn-primary btn-modal edit_payment_btn" 
                                data-href="/petro-general/pump-operators/payment/{{ $cheque_payment->pump_payment_id }}/edit"
                                title="@lang('messages.edit')">
                                <i class="fa fa-edit"></i>
                            </button>
                        @endif
                        <button type="button" class="btn btn-xs btn-danger delete_cheque_payment" data-pump-payment-id="{{ $cheque_payment->pump_payment_id ?? '' }}" data-href="/petro-general/settlement/payment/delete-cheque-payment/{{$cheque_payment->id}}"><i
                                    class="fa fa-times"></i></button>
                    </td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold;">@lang('petrogeneral::lang.total') :</td>
                    <td style="text-align: left; font-weight: bold;" class="cheque_total">
                       {{number_format($cheque_total, $currency_precision)}}</td>
                </tr>
                <input type="hidden" value="{{$cheque_total}}" name="cheque_total" id="cheque_total">
            </tfoot>
        </table>
    </div>
</div>




<script>
    $(document).ready(function(){
        $("#cheque_customer_id").val($("#cheque_customer_id option:eq(0)").val()).trigger('change');
        $('#cheque_date').datepicker("setDate", new Date());
    });
</script>

<script>
    function updateChequePaymentRow(paymentId, newAmount, oldAmount) {
        var $row = null;
        var oldAmt = parseFloat(oldAmount.toString().replace(/,/g, ''));
        $('#cheque_table tbody tr').each(function() {
            var rowAmount = parseFloat($(this).find('.cheque_amount').text().replace(/,/g, ''));
            if (Math.abs(rowAmount - oldAmt) < 0.01) {
                $row = $(this);
                return false;
            }
        });
        if (!$row || $row.length === 0) {
            $row = $('#cheque_table tbody tr.paymt-' + paymentId);
        }
        if ($row && $row.length > 0) {
            $row.find('.cheque_amount').text(__number_f(newAmount, false, false, __currency_precision));
            calculateTotal('#cheque_table', '.cheque_amount', '.cheque_total');
        }
    }

    $(document).on('payment:updated', function(e, response) {
        if (response.payment_type === 'cheque') {
            updateChequePaymentRow(response.payment_id, response.payment_amount, response.old_amount);
        }
    });
</script>