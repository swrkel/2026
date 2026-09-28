<div class="modal-dialog" role="document">
    <div class="modal-content">
  
      {!! Form::open(['id' => 'add_booking_room' ]) !!}
  
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">@lang('messages.add') @lang('hms::lang.rooms')</h4>
      </div>
  
      <div class="row modal-body">
        <div class="form-group col-sm-6">
            {!! Form::label('name', __('hms::lang.type') . '*') !!}
            {!! Form::select('type', $types, '', [
                'class' => 'form-control',
                'required',
                'id' => 'type',
                'placeholder' => __('hms::lang.type'),
            ]) !!}
        </div>
       <div class="modify_field "> 
         <div class="form-group col-sm-3">
            {!! Form::label('no_of_adult', __('hms::lang.no_of_adult') . '*') !!}
            {!! Form::select('no_of_adult', [], '', [
              'class' => 'form-control',
              'required',
              'id' => 'no_of_adult',
              'placeholder' => __('hms::lang.no_of_adult'),
            ]) !!}
          </div>
          <div class="form-group col-sm-3">
              {!! Form::label('no_of_child', __('hms::lang.no_of_child') . '*') !!}
              {!! Form::select('no_of_child', [], '', [
                'class' => 'form-control',
                'required',
                'id' => 'no_of_child',
                'placeholder' => __('hms::lang.no_of_child'),
              ]) !!}
          </div>
          <div class="form-group col-sm-12">
            {!! Form::label('room_no', __('hms::lang.room_no') . '*') !!}
            {!! Form::select('room_no', [], '', [
                'class' => 'form-control',
                'required',
                'id' => 'room_no',
                'placeholder' => __('hms::lang.room_no'),
            ]) !!}
          </div>
       </div>
      </div>
  
      <div class="modal-footer">
        <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white btn pull-left all-p-bt " data-dismiss="modal" >@lang( 'messages.close' )</button>
        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white btn btn-primary pull-right all-p-bt " style="padding: 8px; background-color: #0000FF; color: white; ">@lang( 'messages.save' )</button>
      </div>
  
      {!! Form::close() !!}
  
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->