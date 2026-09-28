@extends('poultry::layouts.app')
@section('title', __('poultry::lang.health'))

@section('content')
<div class="row">
    <div class="col-md-7">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('poultry::lang.vaccinations_due')</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-condensed table-striped">
                    <thead><tr>
                        <th>@lang('poultry::lang.batch')</th>
                        <th>@lang('poultry::lang.vaccine')</th>
                        <th>@lang('poultry::lang.due')</th>
                        <th>@lang('poultry::lang.status')</th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    @forelse ($due as $row)
                        <tr class="{{ $row['is_overdue'] ? 'danger' : '' }}">
                            <td>{{ $row['batch']->batch_code }}</td>
                            <td>{{ $row['schedule']->name }}</td>
                            <td>{{ $row['due_date'] }}</td>
                            <td>
                                @if ($row['is_overdue'])
                                    <span class="label label-danger">@lang('poultry::lang.overdue')</span>
                                @else
                                    <span class="label label-default">
                                        {{ $row['days_until'] }} @lang('poultry::lang.days')</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('poultry.health.batch', $row['batch']->id) }}"
                                   class="btn btn-xs btn-primary">@lang('poultry::lang.record')</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">@lang('poultry::lang.nothing_due')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-ban"></i> @lang('poultry::lang.active_withdrawals')</h3>
            </div>
            <div class="box-body">
                <p class="text-muted small">@lang('poultry::lang.withdrawal_warning')</p>
                <table class="table table-condensed">
                    <tbody>
                    @forelse ($withdrawals as $w)
                        <tr>
                            <td><strong>{{ optional($w->batch)->batch_code }}</strong><br>
                                <small class="text-muted">{{ $w->name }}</small></td>
                            <td class="text-right">
                                {{ optional($w->withdrawal_until)->format('Y-m-d') }}<br>
                                <span class="label label-danger">{{ $w->days_remaining }}
                                    @lang('poultry::lang.days')</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-muted text-center">@lang('poultry::lang.none_active')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.batches')</h3></div>
            <div class="box-body">
                <div class="list-group" style="margin-bottom:0">
                    @foreach ($batches as $batch)
                        <a href="{{ route('poultry.health.batch', $batch->id) }}" class="list-group-item">
                            {{ $batch->batch_code }}
                            <small class="text-muted pull-right">
                                @lang('poultry::lang.age_days') {{ $batch->age_days }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
