@extends('layouts.app')
@section('title', __('messages.view') . ' ' . __('petrodirect::lang.pump_operator'))
@section('content')
<section class="content-header"><h1>@lang('petrodirect::lang.pump_operator')</h1></section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => $pump_operator->name])
        <div class="row">
            <div class="col-md-6"><strong>@lang('petrodirect::lang.mobile'):</strong> {{ $pump_operator->mobile }}</div>
            <div class="col-md-6"><strong>@lang('petrodirect::lang.location'):</strong> {{ $pump_operator->location_name }}</div>
            <div class="col-md-6"><strong>@lang('petrodirect::lang.email'):</strong> {{ $pump_operator->email }}</div>
            <div class="col-md-6"><strong>@lang('petrodirect::lang.status'):</strong> {{ $pump_operator->active ? __('business.is_active') : __('lang_v1.inactive') }}</div>
        </div>
        <br>
        <a href="{{ route('petrodirect.pumper-management.index') }}" class="btn btn-default">@lang('messages.back')</a>
    @endcomponent
</section>
@stop
