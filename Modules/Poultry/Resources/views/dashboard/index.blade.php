@extends('poultry::layouts.app')

@section('title', __('poultry::lang.dashboard'))

@section('content')

<div class="row">
    @php
        $tiles = [
            ['label' => __('poultry::lang.farms'),          'value' => $stats['farms'],           'icon' => 'fa-home',    'bg' => 'aqua'],
            ['label' => __('poultry::lang.active_batches'), 'value' => $stats['active_batches'],  'icon' => 'fa-list-alt','bg' => 'green'],
            ['label' => __('poultry::lang.total_birds'),    'value' => number_format($stats['total_birds']), 'icon' => 'fa-dove', 'bg' => 'yellow'],
            ['label' => __('poultry::lang.eggs_today'),     'value' => number_format($stats['eggs_today']),  'icon' => 'fa-circle-o', 'bg' => 'blue'],
        ];
    @endphp
    @foreach ($tiles as $tile)
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-{{ $tile['bg'] }}">
                <div class="inner">
                    <h3>{{ $tile['value'] }}</h3>
                    <p>{{ $tile['label'] }}</p>
                </div>
                <div class="icon"><i class="fa {{ $tile['icon'] }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

@if (count($alerts))
<div class="row">
    <div class="col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> @lang('poultry::lang.alerts')</h3>
            </div>
            <div class="box-body">
                @foreach ($alerts as $alert)
                    <div class="alert alert-{{ $alert['level'] }}" style="margin-bottom:8px">
                        <strong>{{ $alert['batch'] }}</strong> &mdash; {{ $alert['message'] }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('poultry::lang.active_batches')</h3>
                <div class="box-tools">
                    <a href="{{ route('poultry.batch.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> @lang('poultry::lang.place_batch')
                    </a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>@lang('poultry::lang.batch')</th>
                            <th>@lang('poultry::lang.house')</th>
                            <th class="text-right">@lang('poultry::lang.age_days')</th>
                            <th class="text-right">@lang('poultry::lang.birds')</th>
                            <th class="text-right">@lang('poultry::lang.mortality_pct')</th>
                            <th class="text-right">@lang('poultry::lang.feed_kg')</th>
                            <th class="text-right">@lang('poultry::lang.fcr')</th>
                            <th class="text-right">@lang('poultry::lang.hen_day')</th>
                            <th class="text-right">@lang('poultry::lang.epef')</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($summaries as $row)
                        @php $batch = $row['batch']; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('poultry.batch.show', $batch->id) }}">{{ $batch->batch_code }}</a>
                                <small class="text-muted">{{ \Modules\Poultry\Entities\Batch::BIRD_TYPES[$batch->bird_type] ?? '' }}</small>
                            </td>
                            <td>{{ optional($batch->house)->name }}</td>
                            <td class="text-right">{{ $row['age_days'] }}</td>
                            <td class="text-right">{{ number_format($row['head_count']) }}</td>
                            <td class="text-right {{ $row['mortality_pct'] > 5 ? 'text-red' : '' }}">
                                {{ $row['mortality_pct'] }}%
                            </td>
                            <td class="text-right">{{ number_format($row['total_feed_kg'], 1) }}</td>
                            <td class="text-right">{{ $row['fcr'] ?? '-' }}</td>
                            <td class="text-right">{{ $row['hen_day_pct'] !== null ? $row['hen_day_pct'].'%' : '-' }}</td>
                            <td class="text-right">{{ $row['epef'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">
                            @lang('poultry::lang.no_active_batches')
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if (count($withdrawals))
<div class="row">
    <div class="col-md-12">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-ban"></i> @lang('poultry::lang.active_withdrawals')</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">@lang('poultry::lang.withdrawal_warning')</p>
                <table class="table table-condensed">
                    <thead><tr>
                        <th>@lang('poultry::lang.batch')</th>
                        <th>@lang('poultry::lang.medication')</th>
                        <th>@lang('poultry::lang.clear_on')</th>
                        <th class="text-right">@lang('poultry::lang.days_remaining')</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($withdrawals as $w)
                        <tr>
                            <td>{{ optional($w->batch)->batch_code }}</td>
                            <td>{{ $w->name }}</td>
                            <td>{{ optional($w->withdrawal_until)->format('Y-m-d') }}</td>
                            <td class="text-right"><span class="label label-danger">{{ $w->days_remaining }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
