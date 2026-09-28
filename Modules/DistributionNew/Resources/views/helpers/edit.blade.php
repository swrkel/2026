@extends('layouts.app')
@section('title', 'Edit Helpers')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Edit Helpers'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.helpers.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::helpers.form')
<div class="text-right"><a href="{{ route('distributionnew.helpers.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Update</button></div>
</form></div></section>
@endsection
