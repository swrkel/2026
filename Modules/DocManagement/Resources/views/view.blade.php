@extends('layouts.app')
@section('title', 'View Document')

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h5 class="page-title pull-left">View Document</h5>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="{{ url('DocManagement/documet') }}">Doc Management</a></li>
                    <li><span>View</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content main-content-inner">
    @component('components.widget', ['class' => '', 'title' => 'Document #' . $doc->doc_no])
    <div class="row">
        <div class="col-md-6">
            <table class="table table-bordered">
                <tr><th>Doc No</th><td>{{ $doc->doc_no }}</td></tr>
                <tr><th>Originator</th><td>{{ $doc->originator }}</td></tr>
                <tr><th>Document Type</th><td>{{ $doc->document_type }}</td></tr>
                <tr><th>Purpose</th><td>{{ $doc->purpose }}</td></tr>
                <tr><th>Referred To</th><td>{{ $doc->referred_to }}</td></tr>
                <tr><th>Status</th><td>{{ $doc->status }}</td></tr>
                <tr><th>Note</th><td>{{ $doc->note }}</td></tr>
                <tr><th>Date</th><td>{{ $doc->created_at }}</td></tr>
            </table>
        </div>
        @if(!empty($doc->attachment_items))
        <div class="col-md-6">
            <h4>Attached Documents</h4>
            @foreach($doc->attachment_items as $attachment)
                <div style="margin-bottom: 12px;">
                    <a href="{{ $attachment['download_url'] }}">{{ $attachment['name'] }}</a>
                </div>
            @endforeach
        </div>
        @endif
    </div>
    <div class="row" style="margin-top:15px;">
        <div class="col-md-12">
            <a href="{{ url('DocManagement/documet') }}" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <a href="{{ route('doc.print', $doc->doc_no) }}" class="btn btn-success" target="_blank">
                <i class="glyphicon glyphicon-print"></i> Print
            </a>
        </div>
    </div>
    @endcomponent
</section>
@endsection
