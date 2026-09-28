<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\Modules\ReportsCustomized\Http\Controllers\ReportsCustomizedSettingsController@store'), 'method' =>
    'post', 'id' => 'gramaseva_vasama_form' ])
    !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">LIOC Statement - Add Settings</h4>
    </div>

    {{-- Modified by Engr. Alex -- task 7882: Issue 3 - add Date (auto, read-only) and Description Constant Details fields --}}
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('date_display', __('Date')) !!}
        {!! Form::text('date_display', null, ['class' => 'form-control', 'readonly', 'id' => 'prefix_date_display', 'placeholder' => 'Current Date & Time']) !!}
        <script>document.getElementById('prefix_date_display').value = new Date().toLocaleString();</script>
      </div>

      <div class="form-group">
        {!! Form::label('prefix', __( 'Prefix' )) !!}
        {!! Form::text('prefix', null, ['class' => 'form-control', 'required', 'placeholder' => __(
        'prefix' ), 'id' => 'prefix']);
        !!}
      </div>

      <div class="form-group">
        {!! Form::label('start_number', __( 'Statement Starting Number' )) !!}
        {!! Form::text('start_number', $maxStartNumbers, ['class' => 'form-control', 'required','readonly', 'placeholder' => __(
        'Start Number' ), 'id' => 'start_number']);
        !!}
      </div>

      <div class="form-group">
        {!! Form::label('constant_value', __( 'Constant Value' )) !!}
        {!! Form::text('constant_value', null, ['class' => 'form-control', 'required', 'placeholder' => __(
        'Constant Value' ), 'id' => 'constant_value']);
        !!}
      </div>

      <div class="form-group">
        {!! Form::label('description_constant_details', __('Description Constant Details')) !!}
        {!! Form::textarea('description_constant_details', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Description Constant Details', 'id' => 'description_constant_details']) !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary" id="save_gramaseva_vasama_btn">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
