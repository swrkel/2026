@extends('layouts.app')
@section('title', 'Edit Vehicle Documents')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Edit Vehicle Documents'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.vehicle_documents.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::vehicle_documents.form')
<div class="text-right"><a href="{{ route('distributionnew.vehicle_documents.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Update</button></div>
</form></div></section>
@endsection
