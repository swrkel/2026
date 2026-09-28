@extends('membershipnew::layouts.app')
@php
    $title = 'View Plans';
@endphp
@section('membership-content')
<div class="mn-panel"><pre>{{ json_encode($record, JSON_PRETTY_PRINT) }}</pre></div>
@endsection
