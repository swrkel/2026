@extends('membershipnew::layouts.app')
@php
    $title = 'Add Member';
@endphp
@section('membership-content')
<div class="mn-panel"><form method="POST" action="{{ route('membership-new.members.store') }}">@include('membershipnew::members._form')</form></div>
@endsection
