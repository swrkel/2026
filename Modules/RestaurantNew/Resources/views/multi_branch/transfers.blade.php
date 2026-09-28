@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::multi_branch.branch_transfers'))

@section('content')
<div class="rn-page-header"><h1>{{ __('restaurantnew::multi_branch.branch_transfers') }}</h1></div>
@include('restaurantnew::components.list-toolbar')
<div class="rn-table-wrap">
    <table class="table table-bordered table-striped rn-datatable" id="restaurant-new-branch-transfers-table">
        <thead>
            <tr>
                <th>{{ __('restaurantnew::multi_branch.transfer_no') }}</th>
                <th>{{ __('restaurantnew::multi_branch.from_branch') }}</th>
                <th>{{ __('restaurantnew::multi_branch.to_branch') }}</th>
                <th>{{ __('restaurantnew::multi_branch.status') }}</th>
                <th>{{ __('restaurantnew::multi_branch.requested_date') }}</th>
                <th>{{ __('restaurantnew::multi_branch.action') }}</th>
            </tr>
        </thead>
    </table>
</div>
@endsection
