@extends('layouts.app')
@section('title', 'Doc Management Upload')

@section('css')
 
<style>
    #airport_table > tbody > tr > td{
        vertical-align: middle;
    }
    /* Force searchable select2 input to be visible in this modal */
    #airport_form_modal .select2-container .select2-search--dropdown {
        display: block !important;
    }
</style>
@endsection

@section('content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h5 class="page-title pull-left">Doc Management Upload</h5>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li>Doc Management </li>
                    <li><span>Upload</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner">
   
 @component('components.widget', ['class' => '', 'title' => 'Document Upload'])
    <div class="row" style="margin-bottom: 4px;">
        <button type="button" class="btn btn-primary btn-modal pull-right" id="create_airport">
            <i class="fa fa-plus"></i>
            Add
        </button>
    </div>
    <div class="row">
         
            
            <div class="table-responsive">
           <table class="table table-bordered table-striped"  id="doc_upload_table" style ="width:100%;">
            <thead>
              
                
                <tr>
                  <th >
                       Action
                    </th>
                    <th >
                        Date & Time
                    </th>
                    <th >
                      Doc No
                    </th>
                    <th >
                      From Location
                    </th>
                    <th >
                      To Location
                    </th>
                    <th >
                      Doc Category
                    </th>

                    <th >
                     Originator
                    </th>
                     <th >
                      Document Type
                    </th> 
                    <th >
                       Purpose
                    </th>
                    <th >
                   Referred to
                    </th>
                    <th >
                       Note
                    </th>
                    
                    
                     <!--  <th class="notexport">
                         @lang('lang_v1.action')
                    </th> -->
                </tr>
               
            </thead>
             
         
       
        </table>
        </div>
    </div>
@endcomponent
<div class="modal fade" id="airport_form_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" style=" width: 100% !important;">
    <div class="modal-dialog" role="document" style=" width: 60% !important;">
        {!! Form::open(['url' => action('\Modules\DocManagement\Http\Controllers\DocManagementController@store'), 'method' => 'post', 'id' => 'airport_form','enctype'=>"multipart/form-data",'files' => true ]) !!}
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="airport_title">Doc Uploader</h4>
                </div>
                <div class="modal-body">
        <div class="row">
          <div class="col-md-3">
          <div class="form-group">
            {!! Form::label('date_added', __('Date & Time')) !!}
            <div class="input-group">
                 <span class="input-group-addon">
                <i class="fa fa-calendar"></i>
              </span>
               {!! Form::text('date_added', now()->format('m/d/Y h:i A'), [
                                'class' => 'form-control',
                                'id' => 'date_added',
                                'required',
                                'readonly' => 'readonly',
                                'placeholder' => __('Date & Time'),
                            ]) !!}
            </div>
          </div>
        </div>
        @if(!empty($show_business_location_dropdown))
        <div class="col-md-3">
          <div class="form-group">
            {!! Form::label('from_location', __('From Location')) !!}
            <div class="input-group">
                 <span class="input-group-addon">
                <i class="fa fa-map-marker"></i>
              </span>
             {!! Form::select('from_location', $business_locations, $default_location, [
                                'class' => 'form-control select2',
                                'id' => 'from_location',
                                'required',
                                'placeholder' => __('From Location'),
                            ]) !!}
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            {!! Form::label('to_location', __('To Location')) !!}
            <div class="input-group">
                 <span class="input-group-addon">
                <i class="fa fa-map-marker"></i>
              </span>
             {!! Form::select('to_location', $business_locations, $default_to_location, [
                                'class' => 'form-control select2',
                                'id' => 'to_location',
                                'required',
                                'placeholder' => __('To Location'),
                            ]) !!}
            </div>
          </div>
        </div>
        @endif
        <div class="col-md-3">
          <div class="form-group">
            {!! Form::label('Doc No', __('Doc No')) !!}
            <div class="input-group">
                 <span class="input-group-addon">
                <i class="fa fa-file"></i>
              </span>
             {!! Form::text('doc_number',  $newId, [
                                'class' => 'form-control',
                                'id' => 'doc_no',
                                'required',
                                 'readonly' => 'readonly',
                                'placeholder' => __('Doc no'),
                            ]) !!}
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
           {!! Form::label('type', __('Orginator')) !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-envelope"></i>
              </span>
                {!! Form::text('orginator', $default_originator, [
                                'class' => 'form-control',
                                'id' => 'orginator',
                                'required',
                                'placeholder' => __('Orginator'),
                            ]) !!}
            </div>
          </div>
        </div>
        </div>
    <div class="row">
          <div class="col-md-4   ">
          <div class="form-group">
           {!! Form::label('document_category', __('Document Category')) !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-folder-open"></i>
              </span>
                {!! Form::select('document_category[]', $docCategories, null, [
                                'class' => 'form-control select2',
                                'id' => 'document_category',
                                'required',
                                'multiple' => 'multiple',
                                'style' => 'width: 100%;',
                            ]) !!}
            </div>
          </div>
        </div>
          <div class="col-md-4   ">
          <div class="form-group">
             {!! Form::label('type', __('Document Type')) !!}
            <div class="input-group">
                 <span class="input-group-addon">
                <i class="fa fa-envelope"></i>
              </span>
              {!! Form::select('document_type', $docTypes, null, [
                            'class' => 'form-control select2',
                            'id' => 'document_type',
                            'placeholder' => __('Document Type'),
                            
                            ]) !!}
            </div>
          </div>
        </div>
        <div class="col-md-4   ">
          <div class="form-group">
           {!! Form::label('type', __('Purpose')) !!}
            <div class="input-group">
                 <span class="input-group-addon">
                <i class="fa fa-envelope"></i>
              </span>
                  {!! Form::select('purpose', $docPurpose, !empty($type) ? $type : null, [
                    'class' => 'form-control select2',
                    'id' => 'purpose',
                    'placeholder' => __('Purpose'),
                     
                    ]) !!}
            </div>
          </div>
        </div>
        <div class="col-md-4   ">
          <div class="form-group">
           {!! Form::label('type', __('Referred to')) !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-envelope"></i>
              </span>
                {!! Form::select('referred[]', $docReferred, !empty($type) ? $type : null, [
                'class' => 'form-control select2',
                'id' => 'referred',
                
                'multiple' => 'multiple', // Add the multiple attribute
                ]) !!}
            </div>
          </div>
        </div>
    </div>   
    <div class="row">
            <div class="col-md-4">
           <div class="form-group">
                {!! Form::label('image[]',__('Upload Document')) !!}
                {!! Form::file('image[]', ['id' => 'image','accept' => '.doc,.docx,.xls,.xlsx,.pdf,.jpeg,.jpg,.png', 'multiple' => 'multiple']); !!}
                <label for="image" id="file-size-label-limit">Allowed: Word, Excel, PDF, JPEG, PNG</label>
                <label for="image" id="file-size-label"></label>
            </div>
            </div>
            <div class="col-md-4   ">
          <div class="form-group">
            {!! Form::label('status', __('Doc status') . ':*') !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-envelope"></i>
              </span>
              {!! Form::select('status', $docStatuses ?? [], null, ['placeholder'
                  => __( 'contact.none' ), 'required', 'class' => 'form-control']); !!}
            </div>
          </div>
        </div>
        <div class="form-group col-sm-4">
            {!! Form::label('additional_service', __( 'Note' ) . ':*') !!}
            <div class="input-group">
            {!! Form::textarea('note', null, [
            'id' => 'note',
            'class' => 'form-control',
            'rows' => 4,
            'required',
            'placeholder' => __('Note'),
            ]) !!}
        </div>
        </div> 
    </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
    </div>
</div>
         

 {!! Form::close() !!}
    </div>
</div>
 
 <div class="modal fade" id="referredto_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" >
    <div class="modal-dialog" role="document" style=" width: 50% !important;">
        {!! Form::open(['url' => action('\Modules\DocManagement\Http\Controllers\DocManagementController@update_referred'), 'method' => 'post', 'id' => 'referredto_form','enctype'=>"multipart/form-data",'files' => true ]) !!}
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="airport_title">Referred To</h4>
                </div>
                <div class="modal-body">
        <div class="row">
         
        <div class="col-md-6" >
          <div class="form-group">
            {!! Form::label('Doc No', __('Doc No')) !!}
            <div class="input-group" >
                 <span class="input-group-addon">
                <i class="fa fa-file"></i>
              </span>
             {!! Form::text('doc_number', 'doc', [
                                'class' => 'form-control',
                                'id' => 'doc_number',
                                'required',
                                 'readonly' => 'readonly',
                                'placeholder' => __('Doc no'),
                            ]) !!}
            </div>
          </div>
        </div>
          <div class="col-md-6">
           <div class="form-group">
            {!! Form::label('Forwarded with', __('Forwarded with')) !!}
            <div class="input-group">
                 <span class="input-group-addon">
                <i class="fa fa-file"></i>
              </span>
              {!! Form::select('docForwardwith', $docForwardwith, !empty($docTypes) ? $docTypes : null, [
                            'class' => 'form-control select2',
                            'id' => 'docForwardwith',
                            'placeholder' => __('Forwarded with'),
                            
                            ]) !!}
            </div>
            </div>
          </div>
        </div>
    
     <div class="row">
         
        @if(!empty($show_business_location_dropdown))
        <div class="col-md-6" >
          <div class="form-group">
            {!! Form::label('Doc No', __('Location')) !!}
            <div class="input-group" >
                 <span class="input-group-addon">
                <i class="fa fa-location-arrow"></i>
              </span>
            {!! Form::select('locations', $business_locations, !empty($docTypes) ? $docTypes : null, [
                            'class' => 'form-control select2',
                            'id' => 'locations',
                            'placeholder' => __('Location'),
                            
                            ]) !!}
            </div>
          </div>
        </div>
        @endif
          <div class="col-md-6">
             <div class="form-group">
           {!! Form::label('type', __('Referred to')) !!}
            <div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-envelope"></i>
              </span>
                {!! Form::select('referred_to[]', $docReferred, !empty($type) ? $type : null, [
                'class' => 'form-control select2',
                'id' => 'referred_to',
                
                'multiple' => 'multiple', // Add the multiple attribute
                ]) !!}
            </div>
          </div>
          </div>
        </div>
         <div class="row">
                  <div class="form-group col-sm-6">
            {!! Form::label('additional_service', __( 'Note' ) . ':*') !!}
            <div class="input-group">
            {!! Form::textarea('note', null, [
            'id' => 'note',
            'class' => 'form-control',
            'rows' => 4,
            'required',
            'placeholder' => __('Note'),
            ]) !!}
        </div>
        </div> 
           </div>   
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
    </div>
</div>
 {!! Form::close() !!}
    </div>
</div>
</section>

<!-- /.content --> 
 
<script>

    // Assuming you have included jQuery and the necessary libraries for toast notifications

    $(document).ready(function () {
        $(document).ready(function() {
    $('#designation_toggle').change(function() {
        if ($(this).is(':checked')) {
            $('#designations').prop('disabled', false);
        } else {
            $('#designations').prop('disabled', true);
        }
    });
});
       $('#airport_form').on('submit', function (event) {
            
            event.preventDefault(); // Prevent the default form submission

            // Perform AJAX request
            $.ajax({
                url: $(this).attr('action'),
                method: $(this).attr('method'),
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response && response.success) {
                        toastr.success(response.msg || 'Data saved successfully.');
                        location.reload();
                    } else {
                        toastr.error((response && (response.error || response.msg)) || 'Error occurred. Please try again.');
                    }
                },
                error: function (xhr, status, error) {
                    var response = xhr.responseJSON || {};
                    toastr.error(response.error || response.msg || 'Error occurred. Please try again.');
                }
            });
            
        });
        
         $('#referredto_form').on('submit', function (event) {
            
            event.preventDefault(); // Prevent the default form submission

            // Perform AJAX request
            $.ajax({
                url: $(this).attr('action'),
                method: $(this).attr('method'),
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response && response.success) {
                        toastr.success(response.msg || 'Data saved successfully.');
                        location.reload();
                    } else {
                        toastr.error((response && (response.error || response.msg)) || 'Error occurred. Please try again.');
                    }
                },
                error: function (xhr, status, error) {
                    var response = xhr.responseJSON || {};
                    toastr.error(response.error || response.msg || 'Error occurred. Please try again.');
                }
            });
            
        });
    });
</script>

<script>

  

$(document).ready(function() {
  $('#create_airport').click(() => {
    $('#airport_form_modal').modal('show');
});

  // Ensure location/referred dropdowns in modal always support typing/search.
  $('#airport_form_modal').on('shown.bs.modal', function () {
    $('#from_location, #to_location, #referred').each(function () {
      if ($(this).hasClass('select2-hidden-accessible')) {
        $(this).select2('destroy');
      }
    });

    $('#from_location, #to_location, #referred').select2({
      width: '100%',
      dropdownParent: $('#airport_form_modal'),
      minimumResultsForSearch: 0
    });
  });

  // Referred-to modal multi select should also stay searchable.
  $('#referredto_modal').on('shown.bs.modal', function () {
    $('#referred_to').each(function () {
      if ($(this).hasClass('select2-hidden-accessible')) {
        $(this).select2('destroy');
      }
    });

    $('#referred_to').select2({
      width: '100%',
      dropdownParent: $('#referredto_modal'),
      minimumResultsForSearch: 0
    });
  });

  $(document).ready(function() {
    initializeDataTable();
});
function initializeDataTable() {
    $('#doc_upload_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ action('\Modules\DocManagement\Http\Controllers\DocManagementController@index') }}"
        },
        columnDefs: [{
            targets: 1,
            searchable: false
        }],
        order: [[1, 'desc']],
        columns: [
            { data: 'action', name: 'action' },
            { data: 'created_at', name: 'created_at' },
            { data: 'doc_no', name: 'doc_no' },
            { data: 'from_location', name: 'from_location' },
            { data: 'to_location', name: 'to_location' },
            { data: 'document_category', name: 'document_category' },
            { data: 'originator', name: 'originator' },
            { data: 'document_type', name: 'document_type' },
            // Add additional columns here
            { data: 'purpose', name: 'purpose' },
            { data: 'referred_to', name: 'referred_to' },
            { data: 'note', name: 'note' },
        ],
        createdRow: function(row, data, dataIndex) {
            // Add any custom row creation logic here
        },
        initComplete: function () {}
    });
}
    
    $('#image').change(function() {
        var files = $(this)[0].files;
        var fileSizeLabel = '';
        var exceedsLimit = false;
        for (var i = 0; i < files.length; i++) {
            var fileSize = files[i].size / 1024; // Convert file size to KB
            if (fileSize > 200) {
                fileSizeLabel += '<span style="color: red;">File ' + (i+1) + ': ' + fileSize.toFixed(2) + ' KB (Exceeds 200 KB limit)</span><br>';
                exceedsLimit = true;
            } else {
                fileSizeLabel += 'File ' + (i+1) + ': ' + fileSize.toFixed(2) + ' KB<br>';
            }
        }
        $('#file-size-label').html(fileSizeLabel);
        if (exceedsLimit) {
            $('#image').val(''); // Clear the file input value
        }
    });
});
     
    
    
</script>
<style>
  .nav-tabs-custom>.nav-tabs>li.active a{
    color:#3c8dbc;
  }
  .nav-tabs-custom>.nav-tabs>li.active a:hover{
    color:#3c8dbc;
  }
</style>
@endsection

@section('javascript')
@endsection
