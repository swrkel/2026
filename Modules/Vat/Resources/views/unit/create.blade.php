<div class="modal-dialog modal-md" role="document">
  <div class="modal-content vat-unit-modal-content">

    {!! Form::open(['url' => action('\Modules\Vat\Http\Controllers\VatUnitController@store'), 'method' => 'post', 'id' => 'unit_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'unit.add_unit' )</h4>
    </div>

    <div class="modal-body">
      <div class="alert alert-info" style="margin-bottom:15px;">
        <i class="fa fa-info-circle"></i> Please enter the VAT unit details carefully.
      </div>
      <div class="row">
        <div class="form-group col-sm-12">
          {!! Form::label('actual_name', __( 'unit.name' ) . ':*') !!}
          {!! Form::text('actual_name', null, ['class' => 'form-control input-lg', 'required', 'placeholder' => __( 'unit.name'
          )]); !!}
        </div>
        <div class="form-group col-sm-12">
          {!! Form::label('allow_decimal', __( 'unit.allow_decimal' ) . ':*') !!}
          {!! Form::select('allow_decimal', ['1' => __('messages.yes'), '0' => __('messages.no')], null, ['placeholder'
          => __( 'messages.please_select' ), 'required', 'class' => 'form-control input-lg select2']); !!}
        </div>
        
      </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-success btn-lg">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default btn-lg" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->