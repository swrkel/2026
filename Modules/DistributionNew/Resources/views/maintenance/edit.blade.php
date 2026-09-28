@extends('layouts.app')
@section('title', 'Edit Vehicle Maintenance')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Edit Vehicle Maintenance'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.maintenance.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::maintenance.form')
<div class="text-right"><a href="{{ route('distributionnew.maintenance.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Update</button></div>
</form></div></section>
@endsection
