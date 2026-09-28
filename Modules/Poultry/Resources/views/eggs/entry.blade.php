@extends('poultry::layouts.app')
@section('title', __('poultry::lang.egg_collection'))

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.egg_collection')</h3>
        <div class="box-tools">
            <form method="GET" class="form-inline">
                <input type="date" name="date" value="{{ $date }}" class="form-control input-sm"
                       max="{{ date('Y-m-d') }}" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <div class="box-body">
        @if ($batches->isEmpty())
            <p class="text-muted">@lang('poultry::lang.no_laying_batches')</p>
        @endif

        @foreach ($batches as $batch)
            <div class="panel panel-default egg-batch" data-batch="{{ $batch->id }}">
                <div class="panel-heading">
                    <strong>{{ $batch->batch_code }}</strong>
                    <small class="text-muted">
                        {{ optional($batch->house)->name }} &middot;
                        @lang('poultry::lang.week_of_lay') {{ $batch->week_of_lay }} &middot;
                        {{ number_format($batch->current_qty) }} @lang('poultry::lang.hens')
                    </small>
                    @if ($batch->is_under_withdrawal)
                        <span class="label label-danger pull-right">
                            <i class="fa fa-ban"></i> @lang('poultry::lang.under_withdrawal')</span>
                    @endif
                    <span class="pull-right hen-day-display" style="margin-right:10px"></span>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>@lang('poultry::lang.slot')</label>
                            <select class="form-control fld-slot">
                                @foreach ($slots as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>@lang('poultry::lang.location')</label>
                            <select class="form-control fld-location">
                                @foreach ($locations as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <table class="table table-condensed">
                        <thead><tr>
                            <th>@lang('poultry::lang.grade')</th>
                            <th style="width:140px">@lang('poultry::lang.qty')</th>
                            <th style="width:140px">@lang('poultry::lang.weight_kg')</th>
                            <th style="width:120px"></th>
                        </tr></thead>
                        <tbody>
                        @foreach ($grades as $grade)
                            <tr class="egg-grade-row" data-grade="{{ $grade->id }}">
                                <td>
                                    {{ $grade->name }}
                                    @if (! $grade->is_stocked)
                                        <small class="text-muted" title="@lang('poultry::lang.grade_not_mapped')">
                                            <i class="fa fa-info-circle"></i></small>
                                    @endif
                                </td>
                                <td><input type="number" min="0" class="form-control input-sm fld-qty" value="0"></td>
                                <td><input type="number" step="0.001" min="0" class="form-control input-sm fld-weight"></td>
                                <td>
                                    <button class="btn btn-sm btn-primary btn-save-egg">
                                        <i class="fa fa-check"></i></button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="egg-feedback"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    $('.btn-save-egg').on('click', function (e) {
        e.preventDefault();

        var $row   = $(this).closest('.egg-grade-row');
        var $panel = $(this).closest('.egg-batch');
        var $out   = $panel.find('.egg-feedback');
        var $btn   = $(this);

        $btn.prop('disabled', true);

        $.post('{{ route('poultry.egg.store') }}', {
            batch_id:        $panel.data('batch'),
            collection_date: '{{ $date }}',
            slot:            $panel.find('.fld-slot').val(),
            location_id:     $panel.find('.fld-location').val(),
            grade_id:        $row.data('grade'),
            qty:             $row.find('.fld-qty').val() || 0,
            weight_kg:       $row.find('.fld-weight').val() || null
        }).done(function (res) {
            // A collection that was recorded but not posted to stock is still
            // a success - the reason is shown so the operator understands why.
            var level = res.success ? (res.posted ? 'success' : 'warning') : 'danger';
            $out.html('<div class="alert alert-' + level + '" style="margin:8px 0 0">' + res.msg + '</div>');

            if (res.hen_day_pct !== undefined) {
                $panel.find('.hen-day-display').html(
                    '<span class="label label-info">@lang('poultry::lang.hen_day'): ' + res.hen_day_pct + '%</span>'
                );
            }
        }).fail(function () {
            $out.html('<div class="alert alert-danger" style="margin:8px 0 0">Save failed.</div>');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endsection
