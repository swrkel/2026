@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::multi_branch.title'))

@section('content')
<div class="rn-command rn-multi-branch">
    <div class="rn-page-header">
        <h1>{{ __('restaurantnew::multi_branch.title') }}</h1>
        <p>{{ __('restaurantnew::multi_branch.subtitle') }}</p>
    </div>
    <div class="rn-card-grid rn-card-grid-4">
        <div class="rn-card"><span>{{ __('restaurantnew::multi_branch.active_branches') }}</span><strong id="rn-active-branches">0</strong></div>
        <div class="rn-card"><span>{{ __('restaurantnew::multi_branch.central_kitchens') }}</span><strong id="rn-central-kitchens">0</strong></div>
        <div class="rn-card"><span>{{ __('restaurantnew::multi_branch.open_transfers') }}</span><strong id="rn-open-transfers">0</strong></div>
        <div class="rn-card"><span>{{ __('restaurantnew::multi_branch.shortage_alerts') }}</span><strong id="rn-shortage-alerts">0</strong></div>
    </div>
    <div class="rn-panel">
        <h3>{{ __('restaurantnew::multi_branch.workflow') }}</h3>
        <p>{{ __('restaurantnew::multi_branch.workflow_note') }}</p>
    </div>
</div>
@endsection
