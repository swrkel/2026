@extends('tailoring::layouts.app')
@section('page_title', ucfirst(str_replace('_',' ', 'job_cards')))
@section('tailoring_content')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">{{ ucfirst(str_replace('_',' ', 'job_cards')) }}</h3></div>
    <div class="box-body">
        <div class="btn-group m-b-10">
            <button class="btn btn-primary">Add</button>
            <button class="btn btn-default">Export</button>
            <button class="btn btn-default">Print</button>
        </div>
        <table class="table table-bordered table-striped tailoring-table"><thead><tr><th>Date</th><th>Reference</th><th>Description</th><th>Status</th><th>Action</th></tr></thead><tbody><tr><td colspan="5" class="text-center">No data available</td></tr></tbody></table>
    </div>
</div>
@endsection
