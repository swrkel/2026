<div class="pos-workspace-header box box-solid">
    <div class="box-body">
        <div class="row">
            <div class="col-md-2"><span>{{ __('pos::page_003.register') }}</span><strong>{{ $context['register']->name ?? __('pos::page_003.not_selected') }}</strong></div>
            <div class="col-md-2"><span>{{ __('pos::page_003.shift') }}</span><strong>{{ $context['shift']->session_no ?? __('pos::page_003.no_open_shift') }}</strong></div>
            <div class="col-md-2"><span>{{ __('pos::page_003.cashier') }}</span><strong>{{ $context['cashier_name'] }}</strong></div>
            <div class="col-md-2"><span>{{ __('pos::page_003.location') }}</span><strong>{{ $context['business_location_id'] ?? '-' }}</strong></div>
            <div class="col-md-2"><span>{{ __('pos::page_003.date') }}</span><strong>{{ $context['business_date'] }}</strong></div>
            <div class="col-md-2"><span>{{ __('pos::page_003.time') }}</span><strong>{{ $context['business_time'] }}</strong></div>
        </div>
    </div>
</div>
