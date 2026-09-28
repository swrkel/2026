@extends('layouts.app')
@section('title', __('purchase::lang.purchase_reports'))

@section('content')
<section class="content-header"><h1>@lang('purchase::lang.purchase_reports')</h1></section>
<section class="content">
    @include('purchase::reports.partials.report_cards')
</section>
@endsection
