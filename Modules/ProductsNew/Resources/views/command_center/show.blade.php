@extends('productsnew::layouts.app')
@section('title', __('productsnew::lang.product_command_center'))
@section('productsnew_content')
<section class="content-header productsnew-page-header">
    <h1>@lang('productsnew::lang.product_command_center')</h1>
    <p class="text-muted">{{ $workspace['product']->name ?? '' }}</p>
</section>
<section class="content productsnew-command-center">
    @include('productsnew::command_center.partials.workspace', ['workspace' => $workspace])
</section>
@endsection
