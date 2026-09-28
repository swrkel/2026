@extends('poultry::layouts.app')
@section('title', __('poultry::lang.health').' - '.$batch->batch_code)

@section('content')
@if ($withdrawal)
<div class="alert alert-danger">
    <i class="fa fa-ban"></i> <strong>@lang('poultry::lang.under_withdrawal')</strong> &mdash;
    {{ $withdrawal->name }}, @lang('poultry::lang.clear_on')
    {{ optional($withdrawal->withdrawal_until)->format('Y-m-d') }}.
    @lang('poultry::lang.withdrawal_warning')
</div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.record_vaccination')</h3></div>
            <div class="box-body">
                <div class="form-group">
                    <label>@lang('poultry::lang.schedule_row')</label>
                    <select id="v_schedule" class="form-control">
                        <option value="">@lang('poultry::lang.ad_hoc')</option>
                        @foreach ($schedule as $row)
                            @if (! $row['is_done'])
                                <option value="{{ $row['schedule']->id }}"
                                        data-name="{{ $row['schedule']->name }}"
                                        data-route="{{ $row['schedule']->route }}"
                                        data-dose="{{ $row['schedule']->dose }}">
                                    {{ $row['schedule']->name }} (@lang('poultry::lang.day') {{ $row['age_days'] }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.vaccine') *</label>
                    <input type="text" id="v_name" class="form-control">
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.date') *</label>
                        <input type="date" id="v_date" class="form-control"
                               value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.birds_covered')</label>
                        <input type="number" id="v_birds" class="form-control" value="{{ $batch->current_qty }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.route')</label>
                        <select id="v_route" class="form-control">
                            @foreach ($routes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.dose')</label>
                        <input type="text" id="v_dose" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.item')</label>
                        <select id="v_variation" class="form-control">
                            <option value="">--</option>
                            @foreach ($items as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>@lang('poultry::lang.qty_used')</label>
                        <input type="number" step="0.001" id="v_qty" class="form-control">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>@lang('poultry::lang.cost')</label>
                        <input type="number" step="0.01" id="v_cost" class="form-control">
                    </div>
                </div>
                <div id="v_feedback"></div>
            </div>
            <div class="box-footer">
                <button class="btn btn-primary" id="btn-vaccination">@lang('poultry::lang.save')</button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.record_treatment')</h3></div>
            <div class="box-body">
                <div class="form-group">
                    <label>@lang('poultry::lang.medication') *</label>
                    <input type="text" id="t_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.diagnosis')</label>
                    <input type="text" id="t_diagnosis" class="form-control">
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.started_on') *</label>
                        <input type="date" id="t_start" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.ended_on')</label>
                        <input type="date" id="t_end" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-red">@lang('poultry::lang.withdrawal_days') *</label>
                        <input type="number" min="0" id="t_withdrawal" class="form-control" value="0">
                        <span class="help-block">@lang('poultry::lang.withdrawal_help')</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.dosage')</label>
                        <input type="text" id="t_dosage" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.item')</label>
                        <select id="t_variation" class="form-control">
                            <option value="">--</option>
                            @foreach ($items as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>@lang('poultry::lang.qty_used')</label>
                        <input type="number" step="0.001" id="t_qty" class="form-control">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>@lang('poultry::lang.cost')</label>
                        <input type="number" step="0.01" id="t_cost" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.vet_name')</label>
                    <input type="text" id="t_vet" class="form-control">
                </div>
                <div id="t_feedback"></div>
            </div>
            <div class="box-footer">
                <button class="btn btn-warning" id="btn-treatment">@lang('poultry::lang.save')</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.vaccination_history')</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-condensed">
                    <thead><tr><th>@lang('poultry::lang.date')</th><th>@lang('poultry::lang.vaccine')</th>
                        <th class="text-right">@lang('poultry::lang.birds_covered')</th></tr></thead>
                    <tbody>
                    @forelse ($vaccinations as $v)
                        <tr>
                            <td>{{ optional($v->administered_on)->format('Y-m-d') }}</td>
                            <td>{{ $v->name }}</td>
                            <td class="text-right">{{ number_format($v->birds_covered) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">@lang('poultry::lang.no_records')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.treatment_history')</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-condensed">
                    <thead><tr><th>@lang('poultry::lang.started_on')</th><th>@lang('poultry::lang.medication')</th>
                        <th>@lang('poultry::lang.clear_on')</th></tr></thead>
                    <tbody>
                    @forelse ($treatments as $t)
                        <tr>
                            <td>{{ optional($t->started_on)->format('Y-m-d') }}</td>
                            <td>{{ $t->name }}</td>
                            <td>
                                {{ optional($t->withdrawal_until)->format('Y-m-d') ?: '-' }}
                                @if ($t->days_remaining > 0)
                                    <span class="label label-danger">{{ $t->days_remaining }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">@lang('poultry::lang.no_records')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    // Picking a scheduled row pre-fills the vaccine name, route and dose.
    $('#v_schedule').on('change', function () {
        var opt = $(this).find('option:selected');
        if (opt.val()) {
            $('#v_name').val(opt.data('name'));
            $('#v_route').val(opt.data('route'));
            $('#v_dose').val(opt.data('dose'));
        }
    });

    function post(url, payload, $out, onDone) {
        $.post(url, payload).done(function (res) {
            $out.html('<div class="alert alert-' + (res.success ? 'success' : 'danger') + '">' + res.msg + '</div>');
            if (res.success && onDone) { onDone(res); }
        }).fail(function (xhr) {
            var msg = 'Save failed.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).join(' ');
            }
            $out.html('<div class="alert alert-danger">' + msg + '</div>');
        });
    }

    $('#btn-vaccination').on('click', function (e) {
        e.preventDefault();
        post('{{ route('poultry.health.vaccination.store') }}', {
            batch_id: {{ $batch->id }},
            schedule_id: $('#v_schedule').val() || null,
            name: $('#v_name').val(),
            administered_on: $('#v_date').val(),
            birds_covered: $('#v_birds').val(),
            route: $('#v_route').val(),
            dose: $('#v_dose').val(),
            variation_id: $('#v_variation').val() || null,
            location_id: null,
            qty_used: $('#v_qty').val() || 0,
            total_cost: $('#v_cost').val() || 0
        }, $('#v_feedback'), function () { setTimeout(function () { location.reload(); }, 1200); });
    });

    $('#btn-treatment').on('click', function (e) {
        e.preventDefault();
        post('{{ route('poultry.health.treatment.store') }}', {
            batch_id: {{ $batch->id }},
            name: $('#t_name').val(),
            diagnosis: $('#t_diagnosis').val(),
            started_on: $('#t_start').val(),
            ended_on: $('#t_end').val() || null,
            withdrawal_days: $('#t_withdrawal').val() || 0,
            dosage: $('#t_dosage').val(),
            vet_name: $('#t_vet').val(),
            variation_id: $('#t_variation').val() || null,
            qty_used: $('#t_qty').val() || 0,
            total_cost: $('#t_cost').val() || 0
        }, $('#t_feedback'), function () { setTimeout(function () { location.reload(); }, 2000); });
    });
});
</script>
@endsection
