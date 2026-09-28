@extends('layouts.app')
@section('title', __('purchase::dashboard.purchase_dashboard'))

@section('content')
<section class="content-header">
    <h1>@lang('purchase::dashboard.purchase_dashboard')</h1>
</section>

<section class="content purchase-dashboard">
    @include('purchase::dashboard.partials.filters')

    <div class="row">
        @include('purchase::dashboard.widgets.summary_cards')
    </div>

    <div class="row">
        @include('purchase::dashboard.widgets.outstanding_payables')
        @include('purchase::dashboard.widgets.recent_purchases')
    </div>
</section>
@endsection

@section('javascript')
@include('purchase::layouts.runtime')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/dashboard/purchase-dashboard.js')) !!}</script>
@endsection
