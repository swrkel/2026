@extends('layouts.app')
@section('title', 'Add Vehicle Maintenance')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Add Vehicle Maintenance'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.maintenance.store') }}">@csrf
@include('distributionnew::maintenance.form')
<div class="text-right"><a href="{{ route('distributionnew.maintenance.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Save</button></div>
</form></div></section>
@endsection
