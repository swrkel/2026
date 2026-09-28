@extends('stocktransfernew::layouts.app')
@section('stocktransfernew_content')
@include('stocktransfernew::partials.header',['title'=>'Stock In Transit','subtitle'=>'Transfers dispatched but not yet received'])
@include('stocktransfernew::partials.list_toolbar')
<div class="stn-card">
    @include('stocktransfernew::transfers.partials.table',['transfers'=>$transfers])
    {{ $transfers->links() }}
</div>
@endsection
