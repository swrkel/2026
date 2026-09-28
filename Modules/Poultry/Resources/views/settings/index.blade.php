@extends('poultry::layouts.app')
@section('title', __('poultry::lang.settings'))

@section('content')
<form method="POST" action="{{ route('poultry.settings.update') }}">
    @csrf
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('poultry::lang.operational_defaults')</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>@lang('poultry::lang.point_of_lay_week')</label>
                        <input type="number" name="point_of_lay_week" class="form-control" min="1" max="40"
                               value="{{ $settings['point_of_lay_week'] }}">
                        <span class="help-block">@lang('poultry::lang.point_of_lay_help')</span>
                    </div>
                    <div class="form-group">
                        <label>@lang('poultry::lang.laying_cycle_weeks')</label>
                        <input type="number" name="laying_cycle_weeks" class="form-control" min="1" max="200"
                               value="{{ $settings['laying_cycle_weeks'] }}">
                        <span class="help-block">@lang('poultry::lang.laying_cycle_help')</span>
                    </div>
                    <div class="form-group">
                        <label>@lang('poultry::lang.egg_tray_size')</label>
                        <input type="number" name="egg_tray_size" class="form-control" min="1" max="100"
                               value="{{ $settings['egg_tray_size'] }}">
                    </div>
                    <div class="form-group">
                        <label>@lang('poultry::lang.weight_sample_size')</label>
                        <input type="number" name="weight_sample_size" class="form-control" min="1"
                               value="{{ $settings['weight_sample_size'] }}">
                    </div>
                    <div class="form-group">
                        <label>@lang('poultry::lang.mortality_alert_pct')</label>
                        <input type="number" step="0.01" name="mortality_alert_pct" class="form-control"
                               min="0" max="100" value="{{ $settings['mortality_alert_pct'] }}">
                        <span class="help-block">@lang('poultry::lang.mortality_alert_help')</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('poultry::lang.catalogue_mapping')</h3>
                </div>
                <div class="box-body">
                    {{-- These point the module at the tenant's existing product
                         categories. Without them the item dropdowns on the feed,
                         health, harvest and hatchery screens come back empty. --}}
                    <div class="alert alert-info">@lang('poultry::lang.catalogue_mapping_help')</div>

                    @php
                        $mappings = [
                            'feed_category_ids'       => __('poultry::lang.feed_categories'),
                            'medication_category_ids' => __('poultry::lang.medication_categories'),
                            'vaccine_category_ids'    => __('poultry::lang.vaccine_categories'),
                            'egg_category_ids'        => __('poultry::lang.egg_categories'),
                            'live_bird_category_ids'  => __('poultry::lang.live_bird_categories'),
                            'chick_category_ids'      => __('poultry::lang.chick_categories'),
                        ];
                    @endphp
                    @foreach ($mappings as $key => $label)
                        <div class="form-group">
                            <label>{{ $label }}</label>
                            <input type="text" name="{{ $key }}_csv" class="form-control category-csv"
                                   data-target="{{ $key }}"
                                   value="{{ is_array($settings[$key]) ? implode(',', $settings[$key]) : $settings[$key] }}"
                                   placeholder="@lang('poultry::lang.category_ids_placeholder')">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('poultry::lang.integration_status')</h3>
                </div>
                <div class="box-body">
                    <p>
                        @lang('poultry::lang.stock_integration'):
                        <span class="label label-{{ $stockEnabled ? 'success' : 'default' }}">
                            {{ $stockEnabled ? __('poultry::lang.enabled') : __('poultry::lang.disabled') }}</span>
                    </p>
                    <p>
                        @lang('poultry::lang.ledger_integration'):
                        <span class="label label-{{ $ledgerEnabled ? 'success' : 'default' }}">
                            {{ $ledgerEnabled ? __('poultry::lang.enabled') : __('poultry::lang.disabled') }}</span>
                    </p>
                    <p class="text-muted small">@lang('poultry::lang.integration_help')</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row"><div class="col-md-12">
        <button class="btn btn-primary"><i class="fa fa-check"></i> @lang('poultry::lang.save')</button>
    </div></div>

    <div id="category-hidden-inputs"></div>
</form>
@endsection

@section('javascript')
<script>
$(function () {
    // Category ids are entered as a comma separated list but submitted as an
    // array, which is what the validator and the Setting store expect.
    $('form').on('submit', function () {
        var $holder = $('#category-hidden-inputs').empty();

        $('.category-csv').each(function () {
            var target = $(this).data('target');
            var values = ($(this).val() || '').split(',')
                .map(function (v) { return v.trim(); })
                .filter(function (v) { return v !== ''; });

            values.forEach(function (v) {
                $holder.append($('<input>').attr({ type: 'hidden', name: target + '[]' }).val(v));
            });

            $(this).prop('disabled', true);
        });
    });
});
</script>
@endsection
