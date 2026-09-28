@extends('layouts.app')
@section('title', 'Edit Document')

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h5 class="page-title pull-left">Edit Document</h5>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="{{ url('DocManagement/documet') }}">Doc Management</a></li>
                    <li><span>Edit</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content main-content-inner">
    @component('components.widget', ['class' => '', 'title' => 'Edit Document #' . $doc->doc_no])
    {!! Form::open(['url' => action('\Modules\DocManagement\Http\Controllers\DocManagementController@updateDoc', $doc->doc_no), 'method' => 'POST', 'id' => 'edit_doc_form', 'files' => true, 'enctype' => 'multipart/form-data']) !!}
    <div class="row">
        @if(!empty($show_business_location_dropdown))
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('from_location', 'From Location') !!}
                {!! Form::select('from_location', $business_locations, $selected_from_location, ['class' => 'form-control select2', 'required', 'placeholder' => 'Select From Location']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('to_location', 'To Location') !!}
                {!! Form::select('to_location', $business_locations, $selected_to_location, ['class' => 'form-control select2', 'required', 'placeholder' => 'Select To Location']) !!}
            </div>
        </div>
        @endif
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('originator', 'Originator') !!}
                {!! Form::text('originator', $doc->originator, ['class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('document_type', 'Document Type') !!}
                {!! Form::select('document_type', $docTypes, $doc->document_type, ['class' => 'form-control select2', 'placeholder' => 'Select Type']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('purpose', 'Purpose') !!}
                {!! Form::select('purpose', $docPurpose, $doc->purpose, ['class' => 'form-control select2', 'placeholder' => 'Select Purpose']) !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('referred_to', 'Referred To') !!}
                {!! Form::select('referred_to[]', $docReferred, $selected_referred ?? [], ['class' => 'form-control select2', 'id' => 'edit_referred_to', 'multiple' => 'multiple']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('status', 'Status') !!}
                {!! Form::select('status', $docStatuses ?? [], $doc->status, ['class' => 'form-control select2', 'placeholder' => 'Select Status']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('note', 'Note') !!}
                {!! Form::textarea('note', $doc->note, ['class' => 'form-control', 'rows' => 3]) !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('attachments', 'Upload Documents') !!}
                {!! Form::file('attachments[]', ['class' => 'form-control', 'multiple' => 'multiple', 'accept' => '.doc,.docx,.xls,.xlsx,.pdf,.jpeg,.jpg,.png']) !!}
                <p class="help-block">Allowed: Word, Excel, PDF, JPEG, PNG</p>
            </div>
        </div>
    </div>
    @if(!empty($doc->attachment_items))
    <div class="row">
        <div class="col-md-12">
            <h4>Current Attachments</h4>
            @foreach($doc->attachment_items as $attachment)
                <p><a href="{{ $attachment['download_url'] }}">{{ $attachment['name'] }}</a></p>
            @endforeach
        </div>
    </div>
    @endif
    <div class="row" style="margin-top:10px;">
        <div class="col-md-12">
            <a href="{{ url('DocManagement/documet') }}" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Save
            </button>
        </div>
    </div>
    {!! Form::close() !!}
    @endcomponent
</section>

<script>
$(document).ready(function() {
    $('#edit_referred_to').select2({
        width: '100%',
        minimumResultsForSearch: 0
    });

    $('#edit_doc_form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    setTimeout(function() { window.location.href = '{{ url("DocManagement/documet") }}'; }, 1500);
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function() {
                toastr.error('Something went wrong.');
            }
        });
    });
});
</script>
@endsection
