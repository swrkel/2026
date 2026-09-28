<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\Modules\Distribution\Http\Controllers\DistributionVehiclesController@store'),
      'method' => 'post', 'id' => 'vehicles_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <h4 class="modal-title">Add Vehicle</h4>
    </div>

    <div class="modal-body">
      <div class="row">

        <div class="form-group col-sm-12">
          {!! Form::label('date_time', 'Date & Time' . ':') !!}
          {!! Form::text('date_time', date('Y-m-d H:i:s'), [
              'class' => 'form-control',
              'disabled'
          ]) !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('vehicle_no', 'Vehicle No' . ':*') !!}
          {!! Form::text('vehicle_no', null, [
              'class' => 'form-control',
              'required',
              'placeholder' => 'Vehicle Number'
          ]) !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('vehicle_type', 'Vehicle Type' . ':') !!}
          {!! Form::text('vehicle_type', null, [
              'class' => 'form-control',
              'placeholder' => 'Type'
          ]) !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('vehicle_brand', 'Vehicle Brand' . ':') !!}
          {!! Form::text('vehicle_brand', null, [
              'class' => 'form-control',
              'placeholder' => 'Brand'
          ]) !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('vehicle_model', 'Vehicle Model' . ':') !!}
          {!! Form::text('vehicle_model', null, [
              'class' => 'form-control',
              'placeholder' => 'Model'
          ]) !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('revenue_license_renewal_date', 'Revenue License Renewal Date' . ':') !!}
          {!! Form::date('revenue_license_renewal_date', null, [
              'class' => 'form-control'
          ]) !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('starting_meter', 'Starting Meter' . ':*') !!}
          {!! Form::number('starting_meter', null, [
              'class' => 'form-control',
              'required',
              'placeholder' => 'Starting Meter'
          ]) !!}
        </div>

      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>

    {!! Form::close() !!}

  </div>
</div>
