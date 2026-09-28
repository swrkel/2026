@extends('layouts.app')
@section('content')
<section class="content-header"><h1>{{ __('messages.edit') }} {{ __('distributionnew::lang.price_lists') }}</h1></section>
<section class="content"><div class="box disnew-pos-card"><div class="box-body"><form method="POST" action="{{ route('distribution-new.price_lists.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::price_lists.form')
</form></div></div></section>
@endsection
