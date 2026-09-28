@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::reservation.reservations'))
@section('content')
<section class="content-header restaurantnew-page-header"><h1>@lang('restaurantnew::reservation.reservations')</h1></section>
<section class="content">
    <div class="box box-solid rn-box">
        <div class="box-header with-border rn-toolbar">
            <div class="rn-toolbar-left"><input type="text" class="form-control input-sm" placeholder="@lang('restaurantnew::reservation.search')"></div>
            <div class="rn-toolbar-right"><button class="btn btn-primary btn-sm">@lang('restaurantnew::reservation.add_new')</button></div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped restaurantnew-datatable">
                <thead><tr><th>@lang('restaurantnew::reservation.date')</th><th>@lang('restaurantnew::reservation.name')</th><th>@lang('restaurantnew::reservation.status')</th><th>@lang('restaurantnew::reservation.action')</th></tr></thead>
                <tbody><tr><td colspan="4" class="text-center text-muted">@lang('restaurantnew::reservation.no_records_loaded')</td></tr></tbody>
            </table>
        </div>
    </div>
</section>
@endsection
