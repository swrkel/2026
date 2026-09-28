@extends('layouts.app')
@section('title', __('distributionnew::lang.add_vehicle'))
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => __('distributionnew::lang.add_vehicle')])
<section class="content distributionnew-page">
    @if(!$limitState['allowed'])
        <div class="alert alert-warning">{{ __('distributionnew::lang.vehicle_limit_reached') }}</div>
    @endif
    @include('distributionnew::vehicles.partials.form', ['action' => route('distributionnew.vehicles.store'), 'method' => 'POST', 'vehicle' => null])
</section>
@endsection
