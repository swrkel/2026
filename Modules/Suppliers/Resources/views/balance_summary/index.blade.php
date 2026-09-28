@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.balance_summary'))

@section('suppliers_content')
<section class="content-header"><h1>@lang('suppliers::lang.balance_summary')</h1></section>
<section class="content">
    @include('suppliers::ledger.partials.filters')
    @include('suppliers::ledger.partials.summary-cards', ['summary' => $summary])
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/suppliers/js/suppliers/balance-summary/index.js') }}"></script>
@endsection
