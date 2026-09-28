@extends('stocktransfernew::layouts.app')
@section('stocktransfernew_content')
@include('stocktransfernew::partials.header',['title'=>'Pending Approvals','subtitle'=>'Approve or reject submitted transfers'])
@include('stocktransfernew::partials.list_toolbar')
<div class="stn-card"><div class="stn-card-header"><strong>Pending Approvals</strong></div><div class="stn-card-body">@include('stocktransfernew::transfers.partials.table',['transfers'=>$transfers])</div></div>
@endsection
