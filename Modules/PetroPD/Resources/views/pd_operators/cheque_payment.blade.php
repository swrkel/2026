{!! Form::open(['url' => action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorPaymentController@saveChequePayment'), 'method' =>'post']) !!}
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_id', __('petropd::lang.customer').':') !!}
                    {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2',
                    'style' => 'width: 100%;', 'required' ]); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cheque_bank', __('petropd::lang.bank').':') !!}
                    {!! Form::select('cheque_bank', $bank_accounts, null, ['class' => 'form-control select2',
                    'style' => 'width: 100%;', 'required' ]); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cheque_number', __( 'petropd::lang.cheque_number' ) ) !!}
                    {!! Form::text('cheque_number', null, ['class' => 'form-control cheque_number',
                    'placeholder' => __(
                    'petropd::lang.cheque_number' ) , 'required' ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cheque_date', __( 'petropd::lang.cheque_date' ) ) !!}
                    {!! Form::date('cheque_date', null, ['class' => 'form-control
                    cheque_date', 'placeholder' => __('petropd::lang.cheque_date' ), 'required' ]); !!}
                </div>
            </div>
            
             <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('amount', __( 'petropd::lang.amount' ) ) !!}
                    {!! Form::text('amount', null, ['class' => 'form-control amount',
                    'placeholder' => __(
                    'petropd::lang.amount' ) , 'required' ]); !!}
                </div>
            </div>

            @php
                $collection_form_no = "";
            @endphp
            @if(session('status'))
                @php
                    $output = session('status');
                    if($output['success']){
                        $collection_form_no = $output['collection_form_no'] ?? "";
                    }
                @endphp
            @endif
            <input type="hidden" class="collection_form_no" name="collection_form_no" value="{{ $collection_form_no }}">
            
        </div>
    </div>
</div>

<div class="row">
     <div class="col-md-2 pull-right">
       <button type="submit" class="btn btn-danger pull-right other_sale_finalize"
            style="margin-top: 23px;">@lang('petropd::lang.finalize')</button>
    </div>
</div>
{!! Form::close() !!}
