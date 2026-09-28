<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroPD\Http\Controllers\PDOperatorController@update', $pump_operator->id), 'method' =>
        'put',
        'id' =>
        'add_pumps_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petropd::lang.edit_pump_operator' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('name', __( 'petropd::lang.name' ) . ':*') !!}
                            {!! Form::text('name', $pump_operator->name, ['class' => 'form-control name', 'required',
                            'placeholder' => __(
                            'petropd::lang.name' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address', __( 'petropd::lang.address' ) . ':*') !!}
                            {!! Form::text('address', $pump_operator->address, ['class' => 'form-control address', 'required',
                            'placeholder' => __(
                            'petropd::lang.address' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('mobile', __( 'petropd::lang.mobile' ) . ':*') !!}
                            {!! Form::text('mobile', $pump_operator->mobile, ['class' => 'form-control mobile input_number', 'required',
                            'placeholder' => __(
                            'petropd::lang.mobile' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('landline', __( 'petropd::lang.landline' )) !!}
                            {!! Form::text('landline', $pump_operator->landline, ['class' => 'form-control landline input_number',
                            'placeholder' => __(
                            'petropd::lang.landline' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('dob', __( 'petropd::lang.dob' ) . ':*') !!}
                            {!! Form::text('dob', $pump_operator->dob, ['class' => 'form-control dob', 'required', 'readonly',
                            'placeholder' => __(
                            'petropd::lang.dob' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('cnic', __( 'petropd::lang.cnic' ) . ':*') !!}
                            {!! Form::text('cnic', $pump_operator->cnic, ['class' => 'form-control cnic input_number', 'required', 'readonly',
                            'placeholder' => __(
                            'petropd::lang.cnic' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('email', __( 'petropd::lang.email' ) . ':*') !!}
                            {!! Form::email('email', !empty($user) ? $user->email : null, ['class' => 'form-control email ', 'required',
                            'placeholder' => __(
                            'petropd::lang.email' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('username', __( 'petropd::lang.username' ) . ':*') !!}
                            {!! Form::text('username', !empty($user) ? $user->username : ($fallback_username ?? null), ['class' => 'form-control username ', 'required', 'readonly' => !empty($user),
                            'placeholder' => __(
                            'petropd::lang.username' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('password', __( 'petropd::lang.password' ) . ':*') !!}
                            {{--
                                MA-002: the passcode is READONLY except for the
                                business admin.

                                It carried 'readonly' => !empty($user). On an edit
                                form $user always exists, so it was readonly for
                                everyone, always - including the admin.

                                Now the admin can change it and nobody else can.

                                THE ADMIN TEST IS THE SYSTEM'S OWN. Util::is_admin
                                checks hasRole('Admin#' . $business_id), which is
                                how the sidebar, the auth provider and the role
                                controller all decide this. I did not invent a new
                                rule.

                                THE SERVER SIDE ALREADY MATCHES: update() reads the
                                field, checks the new passcode is not used by
                                another operator in the same business, and saves
                                it. Left blank it keeps the existing passcode, so
                                clearing the field cannot wipe a login.
                            --}}
                            @php
                                /*
                                 * The business id falls back to the user's own,
                                 * exactly as SidebarPermissionUtil does. Without
                                 * that fallback an empty session would silently
                                 * make even the admin readonly.
                                 */
                                $pdAdminBusinessId = (int) (session('user.business_id')
                                    ?: (auth()->check() ? auth()->user()->business_id : 0));

                                $pdIsBusinessAdmin = auth()->check()
                                    && $pdAdminBusinessId > 0
                                    && auth()->user()->hasRole('Admin#' . $pdAdminBusinessId);
                            @endphp
                            {!! Form::text('password', !empty($user) ? $user->pump_operator_passcode : ($generate_passcode ?? null),['class' => 'form-control', 'required', 'readonly' => ! $pdIsBusinessAdmin,  'placeholder' => __(
                            'business.password' ) ]); !!}
                        </div>
                    </div>
                    
                    
                    @if(auth()->user()->can('edit_pumper_opening_balance'))
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('opening_balance', __( 'petropd::lang.opening_balance' )) !!}
                            {!! Form::text('opening_balance', !empty($transaction) ? $transaction->final_total : 0, ['class' => 'form-control opening_balance
                            input_number',
                            'placeholder' => __(
                            'petropd::lang.opening_balance' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6 hide opening_balance_type_div">
                        <div class="form-group">
                            {!! Form::label('opening_balance_type', __( 'petropd::lang.opening_balance_type' ) . ':*') !!}
                            {!! Form::select('opening_balance_type', ['shortage' => 'Shortage', 'excess' => 'Excess'],
                            !empty($transaction) ? $transaction->sub_type : null , ['class' => 'form-control select2
                            opening_balance_type','required',
                            'placeholder' => __(
                            'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                    
                    @endif


                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('location_id', __( 'petropd::lang.location' ) . ':*') !!}
                            {!! Form::select('location_id', $locations, $pump_operator->location_id , ['class' => 'form-control select2
                            fuel_tank_location', 'required',
                            'placeholder' => __(
                            'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('commission_type', __( 'petropd::lang.commission_type' ) . ':*') !!}
                            {!! Form::select('commission_type', ['none' => 'None','fixed' => 'Fixed', 'percentage' => 'Percentage'], $pump_operator->commission_type
                            , ['class' => 'form-control select2
                            commission_type', 'required',
                            'placeholder' => __(
                            'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                    <div class="col-md-6 hide commission_ap_div">
                        <div class="form-group">
                            {!! Form::label('commission_ap', __( 'petropd::lang.commission_percentage' ) . ':*', [ 'class'
                            =>
                            'commission_percentage hide']) !!}
                            {!! Form::label('commission_ap', __( 'petropd::lang.commission_fixed' ) . ':*', [ 'class' =>
                            'commission_fixed hide']) !!}
                            {!! Form::text('commission_ap', $pump_operator->commission_ap, ['class' => 'form-control input_number
                            commission_ap', 'placeholder' => __(
                            'petropd::lang.commission_ap' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __( 'petropd::lang.transaction_date' ) . ':*') !!}
                            {!! Form::date('transaction_date', !empty($transaction) ? date('Y-m-d',strtotime($transaction->transaction_date)) : date('Y-m-d',strtotime($pump_operator->updated_at)), ['class' => 'form-control transaction_date', 'required',
                            'placeholder' => __(
                            'petropd::lang.transaction_date' ) ]); !!}
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('is_default', __( 'petropd::lang.is_default' ) . ':*') !!}
                            {!! Form::select('is_default', ['0' => __('messages.no'),'1' => __('messages.yes')], $pump_operator->is_default , ['class' => 'form-control select2
                            fuel_tank_location', 'required',
                            'placeholder' => __(
                            'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                    
                     <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('can_fullscreen', __( 'petropd::lang.can_fullscreen' ) . ':*') !!}
                            {!! Form::select('can_fullscreen', ['0' => __('messages.no'),'1' => __('messages.yes')], $pump_operator->can_fullscreen , ['class' => 'form-control select2
                            fuel_tank_location', 'required',
                            'placeholder' => __(
                            'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
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
            <div class="col-md-12">
                <div class="checkbox">
                    <label>{!! Form::checkbox('is_petro_pd_only', 1, old('is_petro_pd_only', (bool) ($pump_operator->is_petro_pd_only ?? false))) !!} <strong>Petro PD only</strong></label>
                    <p class="help-block">This operator remains available in Petro PD and is excluded from Petro Direct, Petro and Settlement SW.</p>
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
        
        function refreshPetroPdCommissionFields() {
            var $form = $('#add_pumps_form');
            var type = $form.find('.commission_type').val();
            var $amount = $form.find('.commission_ap');

            if (type === 'none' || type === '') {
                $form.find('.commission_ap_div').addClass('hide');
                $amount.prop('required', false);
            } else {
                $form.find('.commission_ap_div').removeClass('hide');
                $amount.prop('required', true);
            }

            $form.find('.commission_percentage').toggleClass('hide', type !== 'percentage');
            $form.find('.commission_fixed').toggleClass('hide', type !== 'fixed');
        }

        $('.commission_type').off('change.s774Commission').on('change.s774Commission', refreshPetroPdCommissionFields);
        refreshPetroPdCommissionFields();
        $('.location_id').select2();
        $('.dob').datepicker();

        $('#username').change(function(){
            let username = $(this).val();
            $.ajax({
                method: 'get',
                url:"{{action('\Modules\PetroPD\Http\Controllers\PDOperatorController@checUsername')}}",
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
                url:"{{action('\Modules\PetroPD\Http\Controllers\PDOperatorController@checPasscode')}}",
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
