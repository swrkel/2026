@extends('layouts.app')
@section('title', 'My Health Executive Summary')
@section('content')
<section class="content-header"><h1>My Health <small>Executive Summary</small></h1></section>
<section class="content">
    @include('myhealthmembers::analytics._filters')
    @include('myhealthmembers::analytics._kpi_cards')
</section>
@endsection
