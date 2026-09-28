@extends('layouts.app')
@section('title', 'Add Helpers')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Add Helpers'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.helpers.store') }}">@csrf
@include('distributionnew::helpers.form')
<div class="text-right"><a href="{{ route('distributionnew.helpers.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Save</button></div>
</form></div></section>
@endsection
