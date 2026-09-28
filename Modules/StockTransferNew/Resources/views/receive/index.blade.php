@extends('stocktransfernew::layouts.app')
@section('stocktransfernew_content')
@include('stocktransfernew::partials.header',['title'=>'Receive Queue','subtitle'=>'In-transit transfers waiting receive confirmation'])
@include('stocktransfernew::partials.list_toolbar')
<div class="stn-card"><div class="stn-card-header"><strong>Receive Queue</strong></div><div class="stn-card-body">@include('stocktransfernew::transfers.partials.table',['transfers'=>$transfers])</div></div>
@endsection
