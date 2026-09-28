@extends('customers::layouts.app')
@section('title', 'Customer Approval Audit')
@section('content')
<section class="content-header"><h1>Customer Approval Audit</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive">@include('customers::workflow.history.table', ['history' => $history])</div></div></section>
@endsection
