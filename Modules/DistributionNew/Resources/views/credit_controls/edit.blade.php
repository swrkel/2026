@extends('layouts.app')
@section('content')
<section class="content-header"><h1>{{ __('messages.edit') }} {{ __('distributionnew::lang.credit_controls') }}</h1></section>
<section class="content"><div class="box disnew-pos-card"><div class="box-body"><form method="POST" action="{{ route('distribution-new.credit_controls.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::credit_controls.form')
</form></div></div></section>
@endsection
