@extends('layouts.app')
@section('title', 'My Health Operational Analytics')
@section('content')
<section class="content-header"><h1>My Health <small>Operational Analytics</small></h1></section>
<section class="content">
    @include('myhealthmembers::analytics._filters')
    <div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Doctor Workload</h3></div><div class="box-body">@include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['operations']['doctor_workload'] ?? []])</div></div>
    <div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Laboratory Status</h3></div><div class="box-body">@include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['operations']['lab_status'] ?? []])</div></div>
    <div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Theatre Status</h3></div><div class="box-body">@include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['operations']['theatre_status'] ?? []])</div></div>
</section>
@endsection
