@extends('stocktransfernew::layouts.app')
@section('stocktransfernew_content')
@include('stocktransfernew::partials.header',['title'=>'Stock Transfers','subtitle'=>'Create, submit, approve, dispatch and receive transfers'])
@include('stocktransfernew::partials.list_toolbar')
<div class="stn-card"><div class="stn-card-header"><strong>Stock Transfers</strong><a class="stn-btn stn-btn-primary" href="{{ route('stock-transfer-new.transfers.create') }}">New Transfer</a></div><div class="stn-card-body">@include('stocktransfernew::transfers.partials.table',['transfers'=>$transfers])</div></div>
@endsection
