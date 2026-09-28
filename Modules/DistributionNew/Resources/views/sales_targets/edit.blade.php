@extends('layouts.app')
@section('content')
<section class="content-header"><h1>{{ __('messages.edit') }} {{ __('distributionnew::lang.sales_targets') }}</h1></section>
<section class="content"><div class="box disnew-pos-card"><div class="box-body"><form method="POST" action="{{ route('distribution-new.sales_targets.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::sales_targets.form')
</form></div></div></section>
@endsection
