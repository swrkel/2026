@extends('communicationhub::layout')
@section('communicationhub_title', 'Add Template')
@section('communicationhub_content')
@include('communicationhub::templates.form', ['template' => null, 'action' => route('communicationhub.templates.store'), 'method' => 'POST'])
@endsection
