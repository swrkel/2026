@extends('layouts.app')
@section('title', 'Edit Trip Commissions')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Edit Trip Commissions'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
<form method="POST" action="{{ route('distributionnew.trip_commissions.update', $record->id) }}">@csrf @method('PUT')
@include('distributionnew::trip_commissions.form')
<div class="text-right"><a href="{{ route('distributionnew.trip_commissions.index') }}" class="btn btn-default">Back</a> <button class="btn btn-primary">Update</button></div>
</form></div></section>
@endsection
