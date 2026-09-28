@extends('layouts.app')
@section('content')
<section class="content-header"><h1>{{ __('distributionnew::lang.promotions') }}</h1></section>
<section class="content"><div class="box disnew-pos-card"><div class="box-body"><form method="POST" action="{{ route('distribution-new.promotions.store') }}">@csrf
@include('distributionnew::promotions.form')
</form></div></div></section>
@endsection
