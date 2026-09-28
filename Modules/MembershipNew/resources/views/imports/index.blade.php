@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Import Templates';
@endphp
@section('membership-content')
@foreach($templates as $name => $headers)
<div class="mn-panel" style="margin-bottom:14px">
    <h3>{{ ucwords(str_replace('_', ' ', $name)) }}</h3>
    <p>CSV headers:</p>
    <pre>{{ implode(',', $headers) }}</pre>
</div>
@endforeach
@endsection
