@extends('poultry::layouts.app')
@section('title', $set->set_code)

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.details')</h3></div>
            <div class="box-body">
                <dl class="dl-horizontal" style="margin-bottom:0">
                    <dt>@lang('poultry::lang.set_date')</dt><dd>{{ optional($set->set_date)->format('Y-m-d') }}</dd>
                    <dt>@lang('poultry::lang.eggs_set')</dt><dd>{{ number_format($set->eggs_set) }}</dd>
                    <dt>@lang('poultry::lang.source_batch')</dt><dd>{{ optional($set->sourceBatch)->batch_code ?: '-' }}</dd>
                    <dt>@lang('poultry::lang.status')</dt>
                    <dd>{{ \Modules\Poultry\Entities\HatchSet::STATUSES[$set->status] ?? $set->status }}</dd>
                    <dt>@lang('poultry::lang.fertility')</dt><dd>{{ $set->fertility_pct ?? '-' }}%</dd>
                    <dt>@lang('poultry::lang.hatchability')</dt><dd>{{ $set->hatchability_pct ?? '-' }}%</dd>
                    <dt>@lang('poultry::lang.hatch_of_set')</dt><dd>{{ $set->hatch_of_set_pct ?? '-' }}%</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.candling')</h3></div>
            <div class="box-body">
                <div class="form-group">
                    <label>@lang('poultry::lang.candling_date')</label>
                    <input type="date" id="c_date" class="form-control"
                           value="{{ optional($set->candling_date)->format('Y-m-d') ?: date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.fertile_eggs')</label>
                    <input type="number" id="c_fertile" class="form-control" min="0"
                           max="{{ $set->eggs_set }}" value="{{ $set->fertile_eggs }}">
                </div>
                <div id="c_feedback"></div>
            </div>
            <div class="box-footer">
                <button class="btn btn-warning btn-block" id="btn-candle">@lang('poultry::lang.save')</button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-success">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.hatch')</h3></div>
            <div class="box-body">
                <div class="form-group">
                    <label>@lang('poultry::lang.hatch_date')</label>
                    <input type="date" id="h_date" class="form-control"
                           value="{{ optional($set->hatch_date)->format('Y-m-d') ?: date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.chicks_hatched')</label>
                    <input type="number" id="h_hatched" class="form-control" min="0"
                           max="{{ $set->eggs_set }}" value="{{ $set->chicks_hatched }}">
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.saleable_chicks')</label>
                    <input type="number" id="h_saleable" class="form-control" min="0"
                           value="{{ $set->saleable_chicks }}">
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.location')</label>
                    <select id="h_location" class="form-control">
                        <option value="">--</option>
                        @foreach ($locations as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="h_feedback"></div>
            </div>
            <div class="box-footer">
                <button class="btn btn-success btn-block" id="btn-hatch">@lang('poultry::lang.save')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    function post(url, payload, $out) {
        $.post(url, payload).done(function (res) {
            $out.html('<div class="alert alert-' + (res.success ? 'success' : 'danger') + '">' + res.msg + '</div>');
            if (res.success) { setTimeout(function () { location.reload(); }, 1500); }
        }).fail(function () {
            $out.html('<div class="alert alert-danger">Save failed.</div>');
        });
    }

    $('#btn-candle').on('click', function (e) {
        e.preventDefault();
        post('{{ route('poultry.hatchery.candle', $set->id) }}', {
            candling_date: $('#c_date').val(),
            fertile_eggs:  $('#c_fertile').val()
        }, $('#c_feedback'));
    });

    $('#btn-hatch').on('click', function (e) {
        e.preventDefault();
        post('{{ route('poultry.hatchery.hatch', $set->id) }}', {
            hatch_date:      $('#h_date').val(),
            chicks_hatched:  $('#h_hatched').val(),
            saleable_chicks: $('#h_saleable').val(),
            location_id:     $('#h_location').val() || null
        }, $('#h_feedback'));
    });
});
</script>
@endsection
