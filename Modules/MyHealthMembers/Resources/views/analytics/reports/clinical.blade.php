@extends('layouts.app')
@section('title', 'My Health Clinical Analytics')
@section('content')
<section class="content-header"><h1>My Health <small>Clinical Analytics</small></h1></section>
<section class="content">
    @include('myhealthmembers::analytics._filters')
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Top Diagnoses</h3></div><div class="box-body">@include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['clinical']['top_diagnoses'] ?? []])</div></div>
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Most Prescribed Medicines</h3></div><div class="box-body">@include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['clinical']['top_medicines'] ?? []])</div></div>
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Top Laboratory Tests</h3></div><div class="box-body">@include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['clinical']['top_lab_tests'] ?? []])</div></div>
</section>
@endsection
