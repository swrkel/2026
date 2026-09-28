@extends('membershipnew::layouts.app')
@php
    $title = 'Edit Member';
@endphp
@section('membership-content')
<div class="mn-panel"><form method="POST" action="{{ route('membership-new.members.update', $member->id) }}">@method('PUT')@include('membershipnew::members._form')</form></div>
@endsection
