@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-card"><div class="pn-card-header"><strong>Product Master Report</strong></div><div class="pn-card-body">@include('productsnew::products.partials.table',['products'=>$rows]){{ $rows->links() }}</div></div>
@endsection
