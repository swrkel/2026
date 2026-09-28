@extends('myhealthmembers::portal.layout')
@section('title', 'Health Timeline')
@section('content')
<div class="mh-card"><div class="mh-card-header">My Health Timeline</div><div class="mh-card-body">
@include('myhealthmembers::portal.partials.timeline', ['timeline' => $timeline ?? collect()])
</div></div>
@endsection
