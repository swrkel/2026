@extends('communicationhub::layout')
@section('communicationhub_title', 'Add Provider')
@section('communicationhub_content')
@include('communicationhub::providers.form', ['provider' => null, 'action' => route('communicationhub.providers.store'), 'method' => 'POST'])
@endsection
