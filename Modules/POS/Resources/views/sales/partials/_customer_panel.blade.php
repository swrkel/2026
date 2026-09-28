<div class="box box-info pos-panel pos-customer-panel">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-user"></i> {{ __('pos::page_003.customer') }}</h3>
    </div>
    <div class="box-body">
        <div class="input-group">
            <input type="text" id="pos_customer_search" class="form-control" placeholder="{{ __('pos::page_003.search_customer_or_walk_in') }}">
            <span class="input-group-btn">
                <button type="button" class="btn btn-default" id="pos_walkin_btn">{{ __('pos::page_003.walk_in') }}</button>
            </span>
        </div>
        <div id="pos_customer_results" class="pos-customer-results"></div>
        <div class="pos-selected-customer">
            <span>{{ __('pos::page_003.selected_customer') }}</span>
            <strong id="pos_selected_customer_name">{{ $cart['customer_name'] ?? __('pos::page_003.walk_in_customer') }}</strong>
        </div>
    </div>
</div>

{{-- S365: Customer search/selection is integrated with the standalone Customers module. --}}
