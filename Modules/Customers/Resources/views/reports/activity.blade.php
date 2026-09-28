@extends('layouts.app')
@section('title', __('customers::lang.customer_activity_report'))
@section('content')
@include('customers::reports.partials.simple_table', [
    'title' => __('customers::lang.customer_activity_report'),
    'subtitle' => __('customers::lang.customer_activity_report_subtitle'),
    'headers' => [__('customers::lang.date_time'), __('customers::lang.customer'), __('customers::lang.action'), __('customers::lang.user'), __('customers::lang.description')],
    'rows' => $rows,
    'type' => 'activity'
])
@endsection
