@extends('poultry::layouts.app')
@section('title', __('poultry::lang.performance'))
@section('content')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.performance')</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.batch')</th><th>@lang('poultry::lang.breed')</th>
                <th class="text-right">@lang('poultry::lang.age_days')</th>
                <th class="text-right">@lang('poultry::lang.birds')</th>
                <th class="text-right">@lang('poultry::lang.mortality_pct')</th>
                <th class="text-right">@lang('poultry::lang.liveability')</th>
                <th class="text-right">@lang('poultry::lang.feed_kg')</th>
                <th class="text-right">@lang('poultry::lang.fcr')</th>
                <th class="text-right">@lang('poultry::lang.target_fcr')</th>
                <th class="text-right">@lang('poultry::lang.variance')</th>
                <th class="text-right">@lang('poultry::lang.epef')</th>
                <th class="text-right">@lang('poultry::lang.hen_day')</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><a href="{{ route('poultry.batch.show', $row['batch']->id) }}">{{ $row['batch']->batch_code }}</a></td>
                    <td>{{ optional($row['batch']->breed)->name ?: '-' }}</td>
                    <td class="text-right">{{ $row['age_days'] }}</td>
                    <td class="text-right">{{ number_format($row['head_count']) }}</td>
                    <td class="text-right {{ $row['mortality_pct'] > 5 ? 'text-red' : '' }}">{{ $row['mortality_pct'] }}%</td>
                    <td class="text-right">{{ $row['liveability_pct'] }}%</td>
                    <td class="text-right">{{ number_format($row['total_feed_kg'], 1) }}</td>
                    <td class="text-right"><strong>{{ $row['fcr'] ?? '-' }}</strong></td>
                    <td class="text-right">{{ $row['target_fcr'] ?? '-' }}</td>
                    <td class="text-right {{ ($row['fcr_variance'] ?? 0) > 0 ? 'text-red' : 'text-green' }}">
                        {{ $row['fcr_variance'] !== null ? sprintf('%+.3f', $row['fcr_variance']) : '-' }}
                    </td>
                    <td class="text-right">{{ $row['epef'] ?? '-' }}</td>
                    <td class="text-right">{{ $row['hen_day_pct'] !== null ? $row['hen_day_pct'].'%' : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="text-muted small">@lang('poultry::lang.fcr_variance_note')</p>
    </div>
</div>
@endsection
