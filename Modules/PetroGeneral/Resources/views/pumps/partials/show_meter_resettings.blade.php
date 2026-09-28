<div class="modal-dialog" role="document" style="width: 65%;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.meter_resettings' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('date_and_time', __( 'petrogeneral::lang.date_and_time' )) !!}: {{$meter_resettings->date_and_time}}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('meter_reset_ref_no', __( 'petrogeneral::lang.meter_reset_ref_no' )) !!}: {{$meter_resettings->meter_reset_ref_no}}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('location_name', __( 'petrogeneral::lang.location_name' )) !!}: {{$meter_resettings->location_name}}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('pump_no', __( 'petrogeneral::lang.pump_no' )) !!}: {{$meter_resettings->pump_no}}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('tank', __( 'petrogeneral::lang.tank' )) !!}: {{$meter_resettings->fuel_tank_number}}
                        </div>
                    </div>
                   
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('last_meter', __( 'petrogeneral::lang.last_meter' )) !!}: {{$meter_resettings->last_meter}}
                        </div>
                    </div>
                   
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('new_reset_meter', __( 'petrogeneral::lang.new_reset_meter' )) !!}: {{$meter_resettings->new_reset_meter}}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('created_by', __( 'petrogeneral::lang.created_by' )) !!}: {{$meter_resettings->username}}
                        </div>
                    </div>
                   
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('reason', __( 'petrogeneral::lang.reason' )) !!}: {{$meter_resettings->reason}}
                        </div>
                    </div>
                   
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
