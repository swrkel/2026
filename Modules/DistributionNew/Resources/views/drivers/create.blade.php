@extends('layouts.app')
@section('title', 'Add Drivers')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Add Drivers'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.drivers.store') }}">@csrf
@include('distributionnew::drivers.form')
<div class="text-right"><a href="{{ route('distributionnew.drivers.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Save</button></div>
</form></div></section>
@endsection
