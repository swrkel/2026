<div class="modal-dialog" role="document" style="width: 70%;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@saveResettingDip'),
        'method' =>
        'post',
        'id' =>
        'dip_resetting_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.add_meter_reset' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('meter_reset_ref_no', __( 'petrogeneral::lang.meter_reset_ref_no' ) . ':*') !!}
                            {!! Form::text('meter_reset_ref_no', $ref_no, ['class' => 'form-control meter_reset_ref_no',
                            'required', 'readonly',
                            'placeholder' => __(
                            'petrogeneral::lang.meter_reset_ref_no' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('date_and_time', __( 'petrogeneral::lang.date_and_time' ) . ':*') !!}
                            {!! Form::text('date_and_time', null, ['class' => 'form-control date_and_time', 'required', 'readonly',
                            'placeholder' => __(
                            'petrogeneral::lang.date_and_time' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('location_id', __( 'petrogeneral::lang.location' ) . ':*') !!}
                            {!! Form::select('meter_reset_location_id', $business_locations, null , ['class' => 'form-control
                            select2
                            fuel_tank_location', 'required', 'id' => 'meter_reset_location_id',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('pumps', __('petrogeneral::lang.pumps') . ':') !!}
                            {!! Form::select('meter_reset_pumps', $pumps, null, ['class' => 'form-control select2', 'placeholder'
                            => __('petrogeneral::lang.please_select'), 'id' => 'add_reset_pump_id', 'style' => 'width:100%']); !!}
                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('meter_resettings_product_name', __( 'petrogeneral::lang.product_name' ) . ':') !!}
                            {!! Form::text('meter_resettings_product_name', null, ['class' => 'form-control meter_resettings_product_name', 'required',
                            'readonly',
                            'placeholder' => __(
                            'petrogeneral::lang.product_name' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('meter_resettings_tank_name', __( 'petrogeneral::lang.tank_name' ) . ':') !!}
                            {!! Form::text('meter_resettings_tank_name', null, ['class' => 'form-control meter_resettings_tank_name', 'required',
                            'readonly',
                            'placeholder' => __(
                            'petrogeneral::lang.tank_name' ) ]); !!}
                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('last_meter_current_meter', __( 'petrogeneral::lang.last_meter_current_meter' ) . ':') !!}
                            {!! Form::text('last_meter_current_meter', null, ['class' => 'form-control last_meter_current_meter input_number',
                            'required', 'readonly',
                            'placeholder' => __(
                            'petrogeneral::lang.last_meter_current_meter' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('reset_new_meter', __( 'petrogeneral::lang.reset_new_meter' ) . ':*') !!}
                            {!! Form::text('new_reset_meter', null, ['class' => 'form-control reset_new_meter input_number', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.reset_new_meter' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('reason', __( 'petrogeneral::lang.reason' ) . ':') !!}
                            {!! Form::textarea('meter_resettings_reason', null, ['class' => 'form-control reason',  'rows' =>
                            4,
                            'placeholder' => __(
                            'petrogeneral::lang.reason' ) ]); !!}
                        </div>
                    </div>

                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary add_meter_resetting_btn">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        $('#date_and_time').datepicker("setDate", new Date());
        $('#transaction_date').datepicker("setDate", new Date());
        $('#add_reset_tank_id').select2();
        $('#location_id').select2();

        
        $('#add_reset_pump_id').change(function(){
            var pumpId = $(this).val();

            if (!pumpId) {
                $('#meter_resettings_product_name').val('');
                $('#meter_resettings_tank_name').val('');
                $('#last_meter_current_meter').val('');
                return;
            }

            $.ajax({
                method: 'get',
                url: '{{action('\Modules\PetroGeneral\Http\Controllers\MeterResettingController@getPumpDetails')}}',
                data: { pump_id : pumpId },
                success: function(result) {
                    if (!result) {
                        return;
                    }

                    $('#meter_resettings_product_name').val(result.product_name);
                    $('#meter_resettings_tank_name').val(result.fuel_tank_number);

                    /*
                     * S 680 - was result.last_meter_reading, which is the
                     * pumps.last_meter_reading column. That column is only
                     * written by a meter reset, so on a pump that has been
                     * trading but never reset it reads 0.0000 however much
                     * fuel has passed through it - which is what the ticket
                     * reported.
                     *
                     * current_meter is resolved server-side the same way
                     * settlement resolves it: latest meter sale's reset value,
                     * then its closing meter, then the pump column.
                     */
                    var currentMeter = (typeof result.current_meter !== 'undefined' && result.current_meter !== null)
                        ? result.current_meter
                        : result.last_meter_reading;

                    $('#last_meter_current_meter').val(currentMeter);
                },
                error: function() {
                    $('#meter_resettings_product_name').val('');
                    $('#meter_resettings_tank_name').val('');
                    $('#last_meter_current_meter').val('');
                }
            });
        });
    </script>