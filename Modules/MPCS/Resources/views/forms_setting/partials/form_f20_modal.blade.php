<div class="modal-dialog" role="document" style="width: 45%;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'mpcs::lang.f20_settings' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('F20_form_sn', __( 'lang_v1.transaction_date' ) . ':*') !!}
                        {!! Form::text('F20_form_sn', !empty($setting) ? $setting->F20_form_sn :null, ['class' => 'form-control', 'id' => 'F20_form_sn',
                        'disabled', 'required', 'placeholder' => __( 'lang_v1.transaction_date' ) ]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('F20_form_sn', __( 'mpcs::lang.form_starting_number' ) . ':*') !!}
                        {!! Form::text('F20_form_sn', !empty($setting) ? $setting->F20_form_sn :null, ['class' => 'form-control', 'id' => 'F20_form_sn',
                        'disabled', 'placeholder' => __( 'mpcs::lang.form_starting_number' ) ]); !!}
                    </div>
                </div>
            </div>

        </div>

        <div class="clearfix"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
