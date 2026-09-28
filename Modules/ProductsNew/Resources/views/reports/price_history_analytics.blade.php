@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="productsnew-page"><div class="productsnew-header"><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
@include('productsnew::reports._filters')
@include('productsnew::reports._standard_table')
</div>
@endsection
