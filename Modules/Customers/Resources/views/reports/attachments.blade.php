@extends('layouts.app')
@section('title', __('customers::lang.customer_attachments_report'))
@section('content')
@include('customers::reports.partials.simple_table', [
    'title' => __('customers::lang.customer_attachments_report'),
    'subtitle' => __('customers::lang.customer_attachments_report_subtitle'),
    'headers' => [__('customers::lang.date_time'), __('customers::lang.customer'), __('customers::lang.file_name'), __('customers::lang.user'), __('customers::lang.remarks')],
    'rows' => $rows,
    'type' => 'attachments'
])
@endsection
