@extends('stocktransfernew::layouts.app')
@section('stocktransfernew_content')
@include('stocktransfernew::partials.header',['title'=>'Transfer Register','subtitle'=>'Date-wise and status-wise stock transfer register'])
@include('stocktransfernew::partials.list_toolbar')
<div class="stn-card">
    @include('stocktransfernew::transfers.partials.table',['transfers'=>$transfers])
    {{ $transfers->links() }}
</div>
@endsection
