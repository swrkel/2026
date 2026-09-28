@extends('restaurantnew::layouts.app')
@section('restaurantnew_content')
@include('restaurantnew::setup.partials.header', ['title' => __('restaurantnew::lang.restaurant_settings')])
<section class="content restaurant-new-setup">
    @include('restaurantnew::partials.toolbar')
    <div class="row rn-kpi-row">
        @foreach($counts as $label => $value)
            <div class="col-md-3 col-sm-6">
                <div class="info-box rn-info-box">
                    <span class="info-box-icon"><i class="fa fa-check-square-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ ucwords(str_replace('_',' ', $label)) }}</span>
                        <span class="info-box-number">{{ $value }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <form method="POST" action="{{ route('restaurant-new.settings.update') }}" class="rn-card-form">
        @csrf
        <div class="box box-primary rn-pos-box">
            <div class="box-header with-border"><h3 class="box-title">@lang('restaurantnew::lang.general_settings')</h3></div>
            <div class="box-body row">
                <div class="form-group col-md-4"><label>@lang('restaurantnew::lang.restaurant_name')</label><input name="restaurant_name" value="{{ $settings['restaurant_name'] ?? '' }}" class="form-control"></div>
                <div class="form-group col-md-4"><label>@lang('restaurantnew::lang.service_charge_percent')</label><input type="number" step="0.0001" name="service_charge_percent" value="{{ $settings['service_charge_percent'] ?? '0' }}" class="form-control"></div>
                <div class="form-group col-md-4"><label>@lang('restaurantnew::lang.default_order_type')</label><select name="default_order_type" class="form-control"><option value="dine_in">Dine In</option><option value="takeaway">Takeaway</option><option value="delivery">Delivery</option></select></div>
                <div class="form-group col-md-12"><label>@lang('restaurantnew::lang.bill_footer_note')</label><textarea name="bill_footer_note" class="form-control" rows="3">{{ $settings['bill_footer_note'] ?? '' }}</textarea></div>
                <div class="form-group col-md-4"><label><input type="checkbox" name="enable_kot" value="1" {{ !empty($settings['enable_kot']) ? 'checked' : '' }}> @lang('restaurantnew::lang.enable_kot')</label></div>
                <div class="form-group col-md-4"><label><input type="checkbox" name="enable_service_charge" value="1" {{ !empty($settings['enable_service_charge']) ? 'checked' : '' }}> @lang('restaurantnew::lang.enable_service_charge')</label></div>
                <div class="form-group col-md-4"><label><input type="checkbox" name="enable_table_qr" value="1" {{ !empty($settings['enable_table_qr']) ? 'checked' : '' }}> @lang('restaurantnew::lang.enable_table_qr')</label></div>
            </div>
            <div class="box-footer">@include('restaurantnew::setup.partials.form-actions', ['cancelUrl' => route('restaurant-new.dashboard')])</div>
        </div>
    </form>
</section>
@endsection
