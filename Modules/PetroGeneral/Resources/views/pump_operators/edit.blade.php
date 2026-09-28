<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorController@update', $pump_operator->id), 'method' =>
        'put',
        'id' =>
        'add_pumps_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.edit_pump_operator' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('name', __( 'petrogeneral::lang.name' ) . ':*') !!}
                            {!! Form::text('name', $pump_operator->name, ['class' => 'form-control name', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.name' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address', __( 'petrogeneral::lang.address' ) . ':*') !!}
                            {!! Form::text('address', $pump_operator->address, ['class' => 'form-control address', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.address' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('mobile', __( 'petrogeneral::lang.mobile' ) . ':*') !!}
                            {!! Form::text('mobile', $pump_operator->mobile, ['class' => 'form-control mobile input_number', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.mobile' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('landline', __( 'petrogeneral::lang.landline' )) !!}
                            {!! Form::text('landline', $pump_operator->landline, ['class' => 'form-control landline input_number',
                            'placeholder' => __(
                            'petrogeneral::lang.landline' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('dob', __( 'petrogeneral::lang.dob' ) . ':*') !!}
                            {!! Form::text('dob', $pump_operator->dob, ['class' => 'form-control dob', 'required', 'readonly',
                            'placeholder' => __(
                            'petrogeneral::lang.dob' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('cnic', __( 'petrogeneral::lang.cnic' ) . ':*') !!}
                            {!! Form::text('cnic', $pump_operator->cnic, ['class' => 'form-control cnic input_number', 'required', 'readonly',
                            'placeholder' => __(
                            'petrogeneral::lang.cnic' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('email', __( 'petrogeneral::lang.email' ) . ':*') !!}
                            {!! Form::email('email', !empty($user) ? $user->email : null, ['class' => 'form-control email ', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.email' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('username', __( 'petrogeneral::lang.username' ) . ':*') !!}
                            {!! Form::text('username', !empty($user) ? $user->username : ($fallback_username ?? null), ['class' => 'form-control username ', 'required', 'readonly' => !empty($user),
                            'placeholder' => __(
                            'petrogeneral::lang.username' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('password', __( 'petrogeneral::lang.password' ) . ':*') !!}
                            {!! Form::text('password', !empty($user) ? $user->pump_operator_passcode : ($generate_passcode ?? null),['class' => 'form-control', 'required', 'readonly' => !empty($user), 'placeholder' => __(
                            'business.password' ) ]); !!}
                        </div>
                    </div>
                    
                    
                    @if(auth()->user()->can('edit_pumper_opening_balance'))
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('opening_balance', __( 'petrogeneral::lang.opening_balance' )) !!}
                            {!! Form::text('opening_balance', !empty($transaction) ? $transaction->final_total : 0, ['class' => 'form-control opening_balance
                            input_number',
                            'placeholder' => __(
                            'petrogeneral::lang.opening_balance' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6 hide opening_balance_type_div">
                        <div class="form-group">
                            {!! Form::label('opening_balance_type', __( 'petrogeneral::lang.opening_balance_type' ) . ':*') !!}
                            {!! Form::select('opening_balance_type', ['shortage' => 'Shortage', 'excess' => 'Excess'],
                            !empty($transaction) ? $transaction->sub_type : null , ['class' => 'form-control select2
                            opening_balance_type','required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                    
                    @endif


                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('location_id', __( 'petrogeneral::lang.location' ) . ':*') !!}
                            {!! Form::select('location_id', $locations, $pump_operator->location_id , ['class' => 'form-control select2
                            fuel_tank_location', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('commission_type', __( 'petrogeneral::lang.commission_type' ) . ':*') !!}
                            {!! Form::select('commission_type', ['none' => 'None','fixed' => 'Fixed', 'percentage' => 'Percentage'], $pump_operator->commission_type
                            , ['class' => 'form-control select2
                            commission_type', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                    <div class="col-md-6 hide commission_ap_div">
                        <div class="form-group">
                            {!! Form::label('commission_ap', __( 'petrogeneral::lang.commission_percentage' ) . ':*', [ 'class'
                            =>
                            'commission_percentage hide']) !!}
                            {!! Form::label('commission_ap', __( 'petrogeneral::lang.commission_fixed' ) . ':*', [ 'class' =>
                            'commission_fixed hide']) !!}
                            {!! Form::text('commission_ap', $pump_operator->commission_ap, ['class' => 'form-control input_number
                            commission_ap', 'placeholder' => __(
                            'petrogeneral::lang.commission_ap' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __( 'petrogeneral::lang.transaction_date' ) . ':*') !!}
                            {!! Form::date('transaction_date', !empty($transaction) ? date('Y-m-d',strtotime($transaction->transaction_date)) : date('Y-m-d',strtotime($pump_operator->updated_at)), ['class' => 'form-control transaction_date', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.transaction_date' ) ]); !!}
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('is_default', __( 'petrogeneral::lang.is_default' ) . ':*') !!}
                            {!! Form::select('is_default', ['0' => __('messages.no'),'1' => __('messages.yes')], $pump_operator->is_default , ['class' => 'form-control select2
                            fuel_tank_location', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                    
                     <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('can_fullscreen', __( 'petrogeneral::lang.can_fullscreen' ) . ':*') !!}
                            {!! Form::select('can_fullscreen', ['0' => __('messages.no'),'1' => __('messages.yes')], $pump_operator->can_fullscreen , ['class' => 'form-control select2
                            fuel_tank_location', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::hidden('hide_in_direct_settlement_if_pending_shifts', 0) !!}
                                {!! Form::checkbox('hide_in_direct_settlement_if_pending_shifts', 1, !empty($pump_operator->hide_in_direct_settlement_if_pending_shifts), ['id' => 'hide_in_direct_settlement_if_pending_shifts']) !!}
                                Do not show in the Direct Settlement, if any Assigned Shifts are Pending in the Settlements
                            </label>
                        </div>
                    </div>
                    
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        $(document).on('change', '.opening_balance', function(e) {
            if(parseFloat($(this).val()) >= 0 ){
                $('.opening_balance_type_div').removeClass('hide');
            }else{
                toastr.error('Please enter none negative number');
                $('.opening_balance_type_div').addClass('hide');
                $(this).val('');
            }
        });
        
        $('.commission_type').change(function(){
            if($(this).val() == 'none' || $(this).val() == ''){
                $('.commission_ap_div').addClass('hide');
            }else{
                $('.commission_ap_div').removeClass('hide');
            }
            if($(this).val() == 'percentage'){
                $('.commission_percentage').removeClass('hide');
            }else{
                $('.commission_percentage').addClass('hide');
            }
            if($(this).val() == 'fixed'){
                $('.commission_fixed').removeClass('hide');
            }else{
                $('.commission_fixed').addClass('hide');
            }
        });
        $('.location_id').select2();
        $('.dob').datepicker();

        $('#username').change(function(){
            let username = $(this).val();
            $.ajax({
                method: 'get',
                url:"{{ route('petrogeneral.pump_operators.check_username') }}",
                data: { username },
                success: function(result) {
                    if(!result.success){
                        toastr.error(result.msg);
                        $(this).val('');
                    }
                },
            });

        })

        $('#password').change(function(){
            let passcode = $(this).val();
            $.ajax({
                method: 'get',
                url:"{{ route('petrogeneral.pump_operators.check_passcode') }}",
                data: { passcode },
                success: function(result) {
                    if(!result.success){
                        toastr.error(result.msg);
                        $(this).val('');
                    }
                },
            });

        })
        
        $(".opening_balance").trigger('change');
        
    </script>
