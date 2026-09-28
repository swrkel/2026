@extends('layouts.app')
@section('title', __('purchase::lang.supplier_outstanding_report'))

@section('content')
<section class="content-header"><h1>@lang('purchase::lang.supplier_outstanding_report')</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            @include('purchase::reports.partials.filters')
            @include('purchase::reports.supplier_outstanding.table')
        </div>
    </div>
</section>
@endsection

@section('javascript')
@include('purchase::layouts.runtime')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/reports/supplier-outstanding.js')) !!}</script>
@endsection
