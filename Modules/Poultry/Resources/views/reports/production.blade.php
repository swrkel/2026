@extends('poultry::layouts.app')
@section('title', __('poultry::lang.production'))
@section('content')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.production')</h3></div>
    <div class="box-body">
        <form method="GET" class="form-inline" style="margin-bottom:15px">
            <input type="date" name="from" value="{{ $from }}" class="form-control input-sm">
            <input type="date" name="to" value="{{ $to }}" class="form-control input-sm">
            <button class="btn btn-default btn-sm"><i class="fa fa-filter"></i> @lang('poultry::lang.filter')</button>
        </form>

        @forelse ($rows as $row)
            <div class="box box-default">
                <div class="box-header with-border">
                    <h4 class="box-title">{{ $row['batch']->batch_code }}</h4>
                    <span class="pull-right">
                        @lang('poultry::lang.avg_hen_day'):
                        <strong>{{ $row['avg_hen_day'] }}%</strong>
                    </span>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-condensed">
                        <thead><tr>
                            <th>@lang('poultry::lang.grade')</th>
                            <th class="text-right">@lang('poultry::lang.qty')</th>
                            <th class="text-right">@lang('poultry::lang.trays')</th>
                            <th class="text-right">@lang('poultry::lang.weight_kg')</th>
                        </tr></thead>
                        <tbody>
                        @php $traySize = \Modules\Poultry\Entities\Setting::get('egg_tray_size', 30); @endphp
                        @foreach ($row['by_grade'] as $g)
                            <tr>
                                <td>{{ optional($g->grade)->name }}</td>
                                <td class="text-right">{{ number_format($g->total_qty) }}</td>
                                <td class="text-right">{{ $traySize > 0 ? number_format($g->total_qty / $traySize, 1) : '-' }}</td>
                                <td class="text-right">{{ $g->total_weight ? number_format($g->total_weight, 2) : '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="text-muted">@lang('poultry::lang.no_records')</p>
        @endforelse
    </div>
</div>
@endsection
