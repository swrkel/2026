@extends('poultry::layouts.app')
@section('title', $batch->batch_code)
@section('subtitle', \Modules\Poultry\Entities\Batch::BIRD_TYPES[$batch->bird_type] ?? '')

@section('content')

@if ($withdrawal)
<div class="alert alert-danger">
    <h4><i class="fa fa-ban"></i> @lang('poultry::lang.under_withdrawal')</h4>
    {{ $withdrawal->name }} &mdash; @lang('poultry::lang.clear_on')
    <strong>{{ optional($withdrawal->withdrawal_until)->format('Y-m-d') }}</strong>
    ({{ $withdrawal->days_remaining }} @lang('poultry::lang.days_remaining')).
    @lang('poultry::lang.withdrawal_warning')
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('poultry::lang.performance')</h3>
                <div class="box-tools">
                    @if ($batch->is_open)
                        @if ($batch->bird_type === 'pullet')
                            <a href="{{ route('poultry.batch.transfer.form', $batch->id) }}" class="btn btn-warning btn-sm">
                                <i class="fa fa-exchange"></i> @lang('poultry::lang.transfer_to_lay')</a>
                        @endif
                        <a href="{{ route('poultry.batch.edit', $batch->id) }}" class="btn btn-default btn-sm">
                            <i class="fa fa-edit"></i></a>
                    @endif
                </div>
            </div>
            <div class="box-body">
                <div class="row text-center">
                    @php
                        $kpis = [
                            ['label' => __('poultry::lang.age_days'),      'value' => $summary['age_days']],
                            ['label' => __('poultry::lang.birds'),         'value' => number_format($summary['head_count'])],
                            ['label' => __('poultry::lang.mortality_pct'), 'value' => $summary['mortality_pct'].'%'],
                            ['label' => __('poultry::lang.feed_kg'),       'value' => number_format($summary['total_feed_kg'], 1)],
                            ['label' => __('poultry::lang.fcr'),           'value' => $summary['fcr'] ?? '-'],
                            ['label' => $batch->bird_type === 'broiler' ? __('poultry::lang.epef') : __('poultry::lang.hen_day'),
                             'value' => $batch->bird_type === 'broiler'
                                ? ($summary['epef'] ?? '-')
                                : (($summary['hen_day_pct'] ?? null) !== null ? $summary['hen_day_pct'].'%' : '-')],
                        ];
                    @endphp
                    @foreach ($kpis as $kpi)
                        <div class="col-md-2 col-xs-4" style="margin-bottom:10px">
                            <div class="description-block" style="border:1px solid #f4f4f4;padding:8px">
                                <h4 style="margin:0">{{ $kpi['value'] }}</h4>
                                <small class="text-muted">{{ $kpi['label'] }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.recent_daily_records')</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-condensed table-striped">
                    <thead><tr>
                        <th>@lang('poultry::lang.date')</th>
                        <th class="text-right">@lang('poultry::lang.age_days')</th>
                        <th class="text-right">@lang('poultry::lang.mortality')</th>
                        <th class="text-right">@lang('poultry::lang.culls')</th>
                        <th class="text-right">@lang('poultry::lang.feed_kg')</th>
                        <th class="text-right">@lang('poultry::lang.avg_weight_g')</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($recent as $record)
                        <tr>
                            <td>{{ optional($record->record_date)->format('Y-m-d') }}</td>
                            <td class="text-right">{{ $record->age_days }}</td>
                            <td class="text-right">{{ $record->mortality }}</td>
                            <td class="text-right">{{ $record->culls }}</td>
                            <td class="text-right">{{ number_format($record->feed_kg, 2) }}</td>
                            <td class="text-right">{{ $record->avg_weight_g ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('poultry::lang.vaccination_schedule')</h3>
                <div class="box-tools">
                    <a href="{{ route('poultry.health.batch', $batch->id) }}" class="btn btn-xs btn-default">
                        @lang('poultry::lang.manage')</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-condensed">
                    <thead><tr>
                        <th>@lang('poultry::lang.vaccine')</th>
                        <th class="text-right">@lang('poultry::lang.age_days')</th>
                        <th>@lang('poultry::lang.due')</th>
                        <th>@lang('poultry::lang.status')</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($vaccination as $row)
                        <tr class="{{ $row['is_overdue'] ? 'danger' : '' }}">
                            <td>{{ $row['schedule']->name }}</td>
                            <td class="text-right">{{ $row['age_days'] }}</td>
                            <td>{{ $row['due_date'] }}</td>
                            <td>
                                @if ($row['is_done'])
                                    <span class="label label-success">@lang('poultry::lang.done')</span>
                                @elseif ($row['is_overdue'])
                                    <span class="label label-danger">@lang('poultry::lang.overdue')</span>
                                @else
                                    <span class="label label-default">@lang('poultry::lang.pending')</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">@lang('poultry::lang.no_schedule')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.details')</h3></div>
            <div class="box-body">
                <dl class="dl-horizontal" style="margin-bottom:0">
                    <dt>@lang('poultry::lang.farm')</dt><dd>{{ optional($batch->farm)->name }}</dd>
                    <dt>@lang('poultry::lang.house')</dt><dd>{{ optional($batch->house)->name }}</dd>
                    <dt>@lang('poultry::lang.breed')</dt><dd>{{ optional($batch->breed)->name ?: '-' }}</dd>
                    <dt>@lang('poultry::lang.placed')</dt><dd>{{ optional($batch->placement_date)->format('Y-m-d') }}</dd>
                    <dt>@lang('poultry::lang.placed_qty')</dt><dd>{{ number_format($batch->initial_qty) }}</dd>
                    <dt>@lang('poultry::lang.supplier')</dt><dd>{{ optional($batch->supplier)->name ?: '-' }}</dd>
                    <dt>@lang('poultry::lang.status')</dt><dd>{{ \Modules\Poultry\Entities\Batch::STATUSES[$batch->status] ?? '' }}</dd>
                </dl>
            </div>
        </div>

        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('poultry::lang.costing')</h3>
                <span class="label label-default pull-right">
                    {{ $costing['treatment'] === 'amortising_asset'
                        ? __('poultry::lang.amortising_asset')
                        : __('poultry::lang.work_in_progress') }}
                </span>
            </div>
            <div class="box-body">
                <table class="table table-condensed" style="margin-bottom:0">
                    @foreach ($costing as $key => $value)
                        @if ($key !== 'treatment' && $value !== null)
                            <tr>
                                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                                <td class="text-right">{{ is_numeric($value) ? number_format($value, 2) : $value }}</td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.cost_breakdown')</h3></div>
            <div class="box-body">
                @forelse ($breakdown['lines'] as $line)
                    <div style="margin-bottom:8px">
                        <div>
                            <span>{{ $line['label'] }}</span>
                            <span class="pull-right">{{ number_format($line['amount'], 2) }} ({{ $line['pct'] }}%)</span>
                        </div>
                        <div class="progress progress-xs" style="margin:4px 0 0">
                            <div class="progress-bar progress-bar-primary" style="width: {{ $line['pct'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">@lang('poultry::lang.no_costs_yet')</p>
                @endforelse
                <hr style="margin:8px 0">
                <strong>@lang('poultry::lang.total')</strong>
                <strong class="pull-right">{{ number_format($breakdown['total'], 2) }}</strong>
            </div>
        </div>

        @if ($batch->is_open)
        <form method="POST" action="{{ route('poultry.batch.close', $batch->id) }}"
              onsubmit="return confirm('@lang('poultry::lang.confirm_close')')">
            @csrf
            <button class="btn btn-danger btn-block"><i class="fa fa-lock"></i> @lang('poultry::lang.close_batch')</button>
        </form>
        @endif
    </div>
</div>
@endsection
