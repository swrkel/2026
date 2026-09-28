@extends('expensesnew::layouts.app')
@section('content')
<div class="expnew-page">
    <div class="expnew-toolbar">
        <h4>{{ $page_title ?? __('expensesnew::lang.enterprise_cost_engine') }}</h4>
        <div class="expnew-actions">
            <button class="btn btn-primary">@lang('expensesnew::lang.search')</button>
            <button class="btn btn-success">@lang('expensesnew::lang.export')</button>
        </div>
    </div>
    <div class="expnew-card-grid">
        <div class="expnew-card"><span>Total Cost</span><strong>0.00</strong></div>
        <div class="expnew-card"><span>Allocated</span><strong>0.00</strong></div>
        <div class="expnew-card"><span>Unallocated</span><strong>0.00</strong></div>
        <div class="expnew-card"><span>Profitability</span><strong>0.00</strong></div>
    </div>
</div>
@endsection
