<div class="modal-dialog" role="document" style="width: 70%;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\DailyCardController@update',[$data->id]), 'method' =>
        'put',
        'id' =>
        'add_cards_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.add_collection' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                @php
                    $cardPaymentType = !empty($data->card_number) || !empty($data->slip_no) ? 'one_by_one' : 'bulk';
                @endphp
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('card_pmt_type', __('petrogeneral::lang.type')) . ':' !!}
                            <select id="card_pmt_type" name="card_pmt_type" class="form-control">
                                <option value="bulk" {{ $cardPaymentType === 'bulk' ? 'selected' : '' }}>
                                    {{ __('petrogeneral::lang.bulk') }}
                                </option>
                                <option value="one_by_one" {{ $cardPaymentType === 'one_by_one' ? 'selected' : '' }}>
                                    {{ __('petrogeneral::lang.one_by_one') }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date', __( 'petrogeneral::lang.transaction_date' ) . ':*') !!}
                            {!! Form::text('date', @format_date($data->date), ['class' => 'form-control transaction_date', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.transaction_date' ), 'readonly' ]); !!}
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('collection_no', __( 'petrogeneral::lang.collection_form_no' ) . ':*') !!}
                            {!! Form::text('collection_no', $data->collection_no, ['class' => 'form-control collection_form_no', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.collection_form_no' ), 'readonly' ]); !!}
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('pump_operator', __( 'petrogeneral::lang.pump_operator' ) . ':*') !!}
                            {!! Form::select('pump_operator_id', $pump_operators, $data->pump_operator_id , ['class' => 'form-control select2
                            pump_operator', 'required', 'id' => 'pump_operator_id',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;','required']); !!}
                        </div>
                    </div>
                                
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('customer_id', __('petrogeneral::lang.customer').':') !!}
                            {!! Form::select('customer_id', $customers, $data->customer_id, ['class' => 'form-control select2', 'style' => 'width: 100%;','required']); !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('card_type', __('petrogeneral::lang.card_type').':') !!}
                            {!! Form::select('card_type', $card_types, $data->card_type, ['class' => 'form-control card_fields
                            select2', 'style' => 'width: 100%;', 'placeholder' => __('petrogeneral::lang.please_select' ) ,'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-3 bulk-sensitive-field">
                        <div class="form-group">
                            {!! Form::label('card_number', __( 'petrogeneral::lang.card_number' ) ) !!}
                            {!! Form::text('card_number', $data->card_number, ['class' => 'form-control card_fields input_number
                            card_number',
                            'placeholder' => __(
                            'petrogeneral::lang.card_number' ),'required' ]); !!}
                        </div> 
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('amount', __( 'petrogeneral::lang.amount' ) ) !!}
                            {!! Form::text('amount', $data->amount, ['class' => 'form-control card_fields cust_input_number
                            amount', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.amount' ) ,'required']); !!}
                        </div>
                    </div>
                    
                     <div class="col-md-3 bulk-sensitive-field">
                        <div class="form-group">
                            {!! Form::label('slip_no', __( 'petrogeneral::lang.slip_no' ) ) !!}
                            {!! Form::text('slip_no', $data->slip_no, ['class' => 'form-control card_fields 
                            slip_no', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.slip_no' ),'required' ]); !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                    
                    <div class="col-md-6">
                        <div class="form-group">
                          {!! Form::label("card_note", __('lang_v1.payment_note') . ':') !!}
                          {!! Form::textarea("card_note", $data->card_note, ['class' => 'form-control cash_fields', 'rows' => 3]); !!}
                        </div>
                    </div>
                   
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang( 'messages.update' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        $('.select2').select2();

    </script>
<script>
    $(document).ready(function(){
        $("#customer_id").val($("#customer_id option:eq(0)").val()).trigger('change');
        
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('card_pmt_type');
        const cardFields = document.querySelectorAll('.bulk-sensitive-field');
        const cardInputs = document.querySelectorAll('.bulk-sensitive-field input, .bulk-sensitive-field select');

        function toggleCardFields() {
            if (!typeSelect) {
                return;
            }

            const isBulk = typeSelect.value === 'bulk';
            cardFields.forEach(el => el.style.display = isBulk ? 'none' : '');
            cardInputs.forEach(el => el.required = !isBulk);
        }

        if (typeSelect) {
            toggleCardFields();
            typeSelect.addEventListener('change', toggleCardFields);
        }
    });
</script>
