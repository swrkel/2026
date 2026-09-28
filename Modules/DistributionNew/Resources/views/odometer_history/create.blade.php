@extends('layouts.app')
@section('title', 'Add Odometer History')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Add Odometer History'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.odometer_history.store') }}">@csrf
@include('distributionnew::odometer_history.form')
<div class="text-right"><a href="{{ route('distributionnew.odometer_history.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Save</button></div>
</form></div></section>
@endsection
