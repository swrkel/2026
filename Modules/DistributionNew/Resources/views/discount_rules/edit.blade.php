@extends('layouts.app')
@section('content')
<section class="content-header"><h1>{{ __('messages.edit') }} {{ __('distributionnew::lang.discount_rules') }}</h1></section>
<section class="content"><div class="box disnew-pos-card"><div class="box-body"><form method="POST" action="{{ route('distribution-new.discount_rules.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::discount_rules.form')
</form></div></div></section>
@endsection
