@extends('poultry::layouts.app')
@section('title', __('poultry::lang.daily_entry'))

@section('content')
{{--
    Built for phone use in a shed. One row per active batch, all saved
    independently, so a dropped connection loses one row rather than the lot.
    Rows already entered for this date are pre-filled and will update.
--}}
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.daily_entry')</h3>
        <div class="box-tools">
            <form method="GET" class="form-inline">
                <input type="date" name="date" value="{{ $date }}" class="form-control input-sm"
                       max="{{ date('Y-m-d') }}" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <div class="box-body">
        @if ($batches->isEmpty())
            <p class="text-muted">@lang('poultry::lang.no_active_batches')</p>
        @endif

        @foreach ($batches as $batch)
            @php $row = $existing[$batch->id] ?? null; @endphp
            <div class="panel panel-default poultry-daily-row" data-batch="{{ $batch->id }}">
                <div class="panel-heading">
                    <strong>{{ $batch->batch_code }}</strong>
                    <small class="text-muted">
                        {{ optional($batch->house)->name }} &middot;
                        @lang('poultry::lang.age_days') {{ $batch->ageInDays($date) }} &middot;
                        <span class="batch-qty">{{ number_format($batch->current_qty) }}</span> @lang('poultry::lang.birds')
                    </small>
                    @if ($row)
                        <span class="label label-info pull-right">@lang('poultry::lang.already_entered')</span>
                    @endif
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-2 col-xs-6 form-group">
                            <label>@lang('poultry::lang.mortality')</label>
                            <input type="number" min="0" class="form-control fld-mortality"
                                   value="{{ $row->mortality ?? 0 }}">
                        </div>
                        <div class="col-md-2 col-xs-6 form-group">
                            <label>@lang('poultry::lang.culls')</label>
                            <input type="number" min="0" class="form-control fld-culls" value="{{ $row->culls ?? 0 }}">
                        </div>
                        <div class="col-md-2 col-xs-6 form-group">
                            <label>@lang('poultry::lang.feed_kg')</label>
                            <input type="number" step="0.01" min="0" class="form-control fld-feed"
                                   value="{{ $row->feed_kg ?? 0 }}">
                        </div>
                        <div class="col-md-2 col-xs-6 form-group">
                            <label>@lang('poultry::lang.water_l')</label>
                            <input type="number" step="0.01" min="0" class="form-control fld-water"
                                   value="{{ $row->water_litres ?? 0 }}">
                        </div>
                        <div class="col-md-2 col-xs-6 form-group">
                            <label>@lang('poultry::lang.avg_weight_g')</label>
                            <input type="number" step="0.01" min="0" class="form-control fld-weight"
                                   value="{{ $row->avg_weight_g ?? '' }}">
                        </div>
                        <div class="col-md-2 col-xs-6 form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block btn-save-daily">
                                <i class="fa fa-check"></i> @lang('poultry::lang.save')</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>@lang('poultry::lang.mortality_cause')</label>
                            <input type="text" class="form-control fld-cause" value="{{ $row->mortality_cause ?? '' }}">
                        </div>
                        <div class="col-md-8 form-group">
                            <label>@lang('poultry::lang.notes')</label>
                            <input type="text" class="form-control fld-notes" value="{{ $row->notes ?? '' }}">
                        </div>
                    </div>
                    <div class="daily-feedback"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    $('.btn-save-daily').on('click', function (e) {
        e.preventDefault();

        var $panel = $(this).closest('.poultry-daily-row');
        var $btn   = $(this);
        var $out   = $panel.find('.daily-feedback');

        $btn.prop('disabled', true);
        $out.html('');

        $.post('{{ route('poultry.daily.store') }}', {
            batch_id:        $panel.data('batch'),
            record_date:     '{{ $date }}',
            mortality:       $panel.find('.fld-mortality').val() || 0,
            culls:           $panel.find('.fld-culls').val() || 0,
            feed_kg:         $panel.find('.fld-feed').val() || 0,
            water_litres:    $panel.find('.fld-water').val() || 0,
            avg_weight_g:    $panel.find('.fld-weight').val() || null,
            mortality_cause: $panel.find('.fld-cause').val(),
            notes:           $panel.find('.fld-notes').val()
        }).done(function (res) {
            $out.html('<div class="alert alert-' + (res.success ? 'success' : 'danger') + '" '
                + 'style="margin:8px 0 0">' + res.msg + '</div>');

            if (res.success && res.current_qty !== undefined) {
                $panel.find('.batch-qty').text(Number(res.current_qty).toLocaleString());
            }
        }).fail(function (xhr) {
            var msg = 'Save failed.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).join(' ');
            }
            $out.html('<div class="alert alert-danger" style="margin:8px 0 0">' + msg + '</div>');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endsection
