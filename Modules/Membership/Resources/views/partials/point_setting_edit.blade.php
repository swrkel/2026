<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\Membership\Http\Controllers\MembershipSettingController@updatePointSetting', $pointSetting->id),
                        'method' => 'put', 'id' => 'edit_point_setting_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.edit_point_setting')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('membership_business_type_id', __('membership::lang.business_type') . ':*') !!}
                        {!! Form::select('membership_business_type_id', $businessTypes->pluck('business_type', 'id'), $pointSetting->membership_business_type_id,
                            ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('enable_reward_point', 1, true, ['disabled' => 'disabled']) !!}
                                <strong>@lang('membership::lang.enable_reward_point')</strong>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('reward_point_display_name', __('membership::lang.reward_point_display_name') . ':') !!}
                        {!! Form::text('reward_point_display_name', 'Reward Points',
                            ['class' => 'form-control', 'readonly' => 'readonly']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('reward_point_percent', __('membership::lang.reward_point_percent') . ':*') !!}
                        {!! Form::number('reward_point_percent', $pointSetting->reward_point_percent,
                            ['class' => 'form-control', 'required', 'step' => '0.01', 'min' => '0']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('min_bill_total_to_earn', __('membership::lang.min_bill_total_to_earn') . ':*') !!}
                        {!! Form::number('min_bill_total_to_earn', $pointSetting->min_bill_total_to_earn,
                            ['class' => 'form-control', 'required', 'step' => '0.01', 'min' => '0']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('max_points_per_bill', __('membership::lang.max_points_per_bill') . ':*') !!}
                        {!! Form::number('max_points_per_bill', $pointSetting->max_points_per_bill,
                            ['class' => 'form-control', 'required', 'min' => '0']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('min_bill_total_to_redeem', __('membership::lang.min_bill_total_to_redeem') . ':*') !!}
                        {!! Form::number('min_bill_total_to_redeem', $pointSetting->min_bill_total_to_redeem,
                            ['class' => 'form-control', 'required', 'step' => '0.01', 'min' => '0']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('min_redeem_point', __('membership::lang.min_redeem_point') . ':*') !!}
                        {!! Form::number('min_redeem_point', $pointSetting->min_redeem_point,
                            ['class' => 'form-control', 'required', 'min' => '0']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('max_redeem_point_per_bill', __('membership::lang.max_redeem_point_per_bill') . ':*') !!}
                        {!! Form::number('max_redeem_point_per_bill', $pointSetting->max_redeem_point_per_bill,
                            ['class' => 'form-control', 'required', 'min' => '0']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h4>@lang('membership::lang.reward_point_expiry_period')</h4>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('expiry_period_months', __('membership::lang.months') . ':') !!}
                        {!! Form::number('expiry_period_months', $pointSetting->expiry_period_months,
                            ['class' => 'form-control', 'min' => '0', 'placeholder' => __('membership::lang.months')]) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
