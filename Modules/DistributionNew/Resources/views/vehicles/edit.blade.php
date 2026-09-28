@extends('layouts.app')
@section('title', __('distributionnew::lang.edit_vehicle'))
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => __('distributionnew::lang.edit_vehicle')])
<section class="content distributionnew-page">
    @include('distributionnew::vehicles.partials.form', ['action' => route('distributionnew.vehicles.update', $vehicle->id), 'method' => 'PUT', 'vehicle' => $vehicle])
</section>
@endsection
