@extends('layouts.app')
@section('title', 'F15 Daily Report - New')
@section('content')
<section class="content" style="padding-block:10px;">
    @include('mpcs::forms.partials.f15_daily_report_content')
</section>
@endsection
@section('javascript')
    @include('mpcs::forms.partials.f15_daily_report_scripts')
@endsection
