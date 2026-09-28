<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\Modules\LeadsNew\Http\Controllers\TownController@update', $town->id), 'method' =>
    'PUT', 'id' => 'town_form' ])
    !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'leadsnew::lang.edit_town' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('date', __( 'leadsnew::lang.date' )) !!}
        {!! Form::text('date', date('m/d/Y', strtotime($town->date)), ['class' => 'form-control', 'required',
        'placeholder' => __( 'leadsnew::lang.date' ), 'id' => 'date']);
        !!}
      </div>

      <div class="form-group">
        {!! Form::label('district_id', __( 'leadsnew::lang.district' )) !!}
        {!! Form::select('district_id', $districts, $town->district_id, ['class' => 'form-control select2', 'style' => 'width: 100%;',
        'required',
        'placeholder' => __(
        'leadsnew::lang.please_select' ), 'id' => 'district_id']);
        !!}
      </div>

      <div class="form-group">
        {!! Form::label('name', __( 'leadsnew::lang.name' )) !!}
        {!! Form::text('name', $town->name, ['class' => 'form-control', 'required', 'placeholder' => __(
        'leadsnew::lang.name' )]);
        !!}
      </div>


    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary" id="save_town_btn">@lang( 'leadsnew::lang.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
  $('#date').datepicker({
        format: 'mm/dd/yyyy'
    });
  $('.select2').select2();
</script>