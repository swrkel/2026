@extends('layouts.app')
@section('title', 'Edit Drivers')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Edit Drivers'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.drivers.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::drivers.form')
<div class="text-right"><a href="{{ route('distributionnew.drivers.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Update</button></div>
</form></div></section>
@endsection
