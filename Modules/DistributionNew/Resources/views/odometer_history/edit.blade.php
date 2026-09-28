@extends('layouts.app')
@section('title', 'Edit Odometer History')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Edit Odometer History'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.odometer_history.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::odometer_history.form')
<div class="text-right"><a href="{{ route('distributionnew.odometer_history.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Update</button></div>
</form></div></section>
@endsection
