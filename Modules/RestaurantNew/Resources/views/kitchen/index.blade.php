@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::lang.kitchen_display'))

@section('content')
<section class="content-header restaurantnew-header">
    <h1>@lang('restaurantnew::lang.kitchen_display')</h1>
</section>
<section class="content restaurantnew-kitchen-screen">
    <div class="box box-solid restaurantnew-pos-card">
        <div class="box-header with-border restaurantnew-toolbar">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" id="restaurantnew_kitchen_search" class="form-control" placeholder="@lang('restaurantnew::lang.search_ticket_item')">
                </div>
                <div class="col-md-3">
                    <select id="restaurantnew_kitchen_status" class="form-control">
                        <option value="">@lang('restaurantnew::lang.all_statuses')</option>
                        <option value="new">@lang('restaurantnew::lang.new')</option>
                        <option value="preparing">@lang('restaurantnew::lang.preparing')</option>
                        <option value="completed">@lang('restaurantnew::lang.completed')</option>
                    </select>
                </div>
                <div class="col-md-6 text-right">
                    <button class="btn btn-primary" id="restaurantnew_refresh_kitchen">@lang('restaurantnew::lang.refresh')</button>
                </div>
            </div>
        </div>
        <div class="box-body">
            <div id="restaurantnew_kitchen_board" class="restaurantnew-kitchen-board">
                @include('restaurantnew::kitchen.partials.ticket-cards', ['tickets' => $tickets])
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('Modules/RestaurantNew/Resources/assets/js/restaurantnew_kitchen.js') }}"></script>
@endsection
