@extends('layouts.app')
@section('title', __('customers::lang.customer_documents_report'))
@section('content')
@include('customers::reports.partials.simple_table', [
    'title' => __('customers::lang.customer_documents_report'),
    'subtitle' => __('customers::lang.customer_documents_report_subtitle'),
    'headers' => [__('customers::lang.customer'), __('customers::lang.document_title'), __('customers::lang.category'), __('customers::lang.issue_date'), __('customers::lang.expiry_date'), __('customers::lang.status')],
    'rows' => $rows,
    'type' => 'documents'
])
@endsection
