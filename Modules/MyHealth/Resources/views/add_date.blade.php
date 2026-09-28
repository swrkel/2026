<style>
    .tox-menubar{
        display: none !important;
    }
    .tox-toolbar-overlord{
        display: none !important;
    }
    .tox-statusbar{
        display: none !important;
    }
</style>
<div class="modal-dialog" role="document" style="width: 65%">
    <div class="modal-content">
  
      <style>
        .select2 {
          width: 100% !important;
        }
      </style>
      {!! Form::open(['url' => action('\Modules\Member\Http\Controllers\SugerReadingController@store'), 'method' =>
      'post', 'id' => 'suggestion_form', 'enctype' => 'multipart/form-data' ])
      !!}
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Remark</h4>
      </div>
  
     <div class="modal-body">
    <div class="col-md-12">
        <div class="form-group">
            {!! Form::label('remarks', __( 'member::lang.remarks' )) !!}
            {!! Form::textarea('remarks', '', [
                'class' => 'form-control',
                'placeholder' => __('member::lang.remarks'),
                'readonly' => 'readonly' // Correctly placed outside of the placeholder
            ]); !!}
        </div>
        
        @php
            $date_field_name = 'new';
            $data_field = [];
            $today = \Carbon\Carbon::now()->format('Y-m-d');

            // Create a date array for today's date
            $data_field = createDateArray($today);
        @endphp
<fieldset>
  <div class="row">
    <div class="col-md-6 p-0">
      <label class="text-center w-100 l-date">Year</label>
      <div class="field-inline-block w-100 text-center">
      <input type="text" pattern="[0-9]*" maxlength="1" size="1" class="date-field form-control"  placeholder="Y" name="{{$date_feild_name}}[y][]"  >
      <input type="text" pattern="[0-9]*" maxlength="1" size="1" class="date-field form-control" placeholder="Y" name="{{$date_feild_name}}[y][]"  />
      <input type="text" pattern="[0-9]*" maxlength="1" size="1" class="date-field form-control" placeholder="Y" name="{{$date_feild_name}}[y][]"  />
      <input type="text" pattern="[0-9]*" maxlength="1" size="1" class="date-field form-control" placeholder="Y" name="{{$date_feild_name}}[y][]"  />
      </div>
    </div>
       
    <div class="col-md-3 p-0">
      <label class="text-center w-100 l-date">Month</label>
      <div class="field-inline-block w-100 text-center">
        <input type="text" pattern="[0-9]*" maxlength="1" size="1" class="date-field form-control" placeholder="M" name="{{$date_feild_name}}[m][]"  />
        <input type="text" pattern="[0-9]*" maxlength="1" size="1" class="date-field form-control" placeholder="M" name="{{$date_feild_name}}[m][]"  />
      </div>
    </div>
    <div class="col-md-3 p-0">
        <label class="text-center w-100 l-date">Date</label>
        <div class="field-inline-block w-100 text-center">
      
       <input type="text" pattern="[0-9]*" maxlength="1" size="1" class="date-field form-control" placeholder="D" name="{{$date_feild_name}}[d][]"  />
       <input type="text" pattern="[0-9]*" maxlength="1" size="1" class="date-field form-control" placeholder="D" name="{{$date_feild_name}}[d][]" />
      </div>
    </div>
  </div>
  </fieldset>
  
    </div>
</div>
      <div class="clearfix"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
      </div>
  
  
    </div> 
  </div> 
  
  <script>
     if ($('#details').length) {
          tinymce.init({
              selector: 'textarea#details',
          });
      }
  </script>