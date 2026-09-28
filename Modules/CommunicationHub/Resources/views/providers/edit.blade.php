@extends('communicationhub::layout')
@section('communicationhub_title', 'Edit Provider')
@section('communicationhub_content')
@include('communicationhub::providers.form', ['provider' => $provider, 'action' => route('communicationhub.providers.update',$provider), 'method' => 'PUT'])
@endsection
