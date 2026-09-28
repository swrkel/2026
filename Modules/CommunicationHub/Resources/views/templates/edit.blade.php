@extends('communicationhub::layout')
@section('communicationhub_title', 'Edit Template')
@section('communicationhub_content')
@include('communicationhub::templates.form', ['template' => $template, 'action' => route('communicationhub.templates.update',$template), 'method' => 'PUT'])
@endsection
