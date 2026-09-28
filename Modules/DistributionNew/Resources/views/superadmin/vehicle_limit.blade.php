@extends('layouts.app')
@section('title', __('distributionnew::lang.distribution_new_vehicle_limit'))
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => __('distributionnew::lang.distribution_new_vehicle_limit')])
<section class="content distributionnew-page">
<div class="disnew-pos-card">
<form method="POST" action="{{ route('superadmin.distributionnew.vehicle-limit.update', $businessId) }}">
    @csrf @method('PUT')
    <div class="row">
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.vehicle_limit') }}</label><input type="number" min="0" name="vehicle_limit" class="form-control" value="{{ old('vehicle_limit', $limit->vehicle_limit) }}"></div></div>
        <div class="col-md-3"><label>&nbsp;</label><div class="checkbox"><label><input type="checkbox" name="allow_unlimited" value="1" {{ old('allow_unlimited', $limit->allow_unlimited) ? 'checked' : '' }}> {{ __('distributionnew::lang.allow_unlimited') }}</label></div></div>
        <div class="col-md-6"><div class="form-group"><label>{{ __('distributionnew::lang.note') }}</label><input name="note" class="form-control" value="{{ old('note', $limit->note) }}"></div></div>
    </div>
    <button class="btn btn-primary">{{ __('distributionnew::lang.save') }}</button>
</form>
</div>
</section>
@endsection
