<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.view_point_setting')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>@lang('membership::lang.business_type'):</strong>
                    <p>{{ $pointSetting->businessType ? $pointSetting->businessType->business_type : '-' }}</p>
                </div>
                <div class="col-md-6">
                    <strong>@lang('membership::lang.enable_reward_point'):</strong>
                    <p>@lang('messages.yes')</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <strong>@lang('membership::lang.reward_point_display_name'):</strong>
                    <p>Reward Points</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <strong>@lang('membership::lang.reward_point_percent'):</strong>
                    <p>{{ $pointSetting->reward_point_percent }}%</p>
                </div>
                <div class="col-md-4">
                    <strong>@lang('membership::lang.min_bill_total_to_earn'):</strong>
                    <p>{{ number_format($pointSetting->min_bill_total_to_earn, 2) }}</p>
                </div>
                <div class="col-md-4">
                    <strong>@lang('membership::lang.max_points_per_bill'):</strong>
                    <p>{{ $pointSetting->max_points_per_bill }}</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <strong>@lang('membership::lang.min_bill_total_to_redeem'):</strong>
                    <p>{{ number_format($pointSetting->min_bill_total_to_redeem, 2) }}</p>
                </div>
                <div class="col-md-4">
                    <strong>@lang('membership::lang.min_redeem_point'):</strong>
                    <p>{{ $pointSetting->min_redeem_point }}</p>
                </div>
                <div class="col-md-4">
                    <strong>@lang('membership::lang.max_redeem_point_per_bill'):</strong>
                    <p>{{ $pointSetting->max_redeem_point_per_bill }}</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <strong>@lang('membership::lang.reward_point_expiry_period'):</strong>
                    <p>
                        @if($pointSetting->expiry_period_years)
                            {{ $pointSetting->expiry_period_years }} @lang('membership::lang.years')
                        @endif
                        @if($pointSetting->expiry_period_months)
                            {{ $pointSetting->expiry_period_months }} @lang('membership::lang.months')
                        @endif
                        @if(!$pointSetting->expiry_period_years && !$pointSetting->expiry_period_months)
                            -
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
