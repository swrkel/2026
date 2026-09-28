@extends('poultry::layouts.app')
@section('title', __('poultry::lang.batches'))

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.batches')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.batch.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.place_batch')</a>
        </div>
    </div>

    <div class="box-body">
        <form method="GET" class="form-inline" style="margin-bottom:15px">
            <select name="farm_id" class="form-control input-sm">
                <option value="">@lang('poultry::lang.all_farms')</option>
                @foreach ($farms as $id => $name)
                    <option value="{{ $id }}" {{ ($filters['farm_id'] ?? null) == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <select name="bird_type" class="form-control input-sm">
                <option value="">@lang('poultry::lang.all_types')</option>
                @foreach ($birdTypes as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['bird_type'] ?? null) == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="form-control input-sm">
                <option value="">@lang('poultry::lang.all_statuses')</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['status'] ?? null) == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-default btn-sm"><i class="fa fa-filter"></i> @lang('poultry::lang.filter')</button>
        </form>

        <div class="table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.batch')</th>
                <th>@lang('poultry::lang.type')</th>
                <th>@lang('poultry::lang.farm')</th>
                <th>@lang('poultry::lang.house')</th>
                <th>@lang('poultry::lang.placed')</th>
                <th class="text-right">@lang('poultry::lang.age_days')</th>
                <th class="text-right">@lang('poultry::lang.placed_qty')</th>
                <th class="text-right">@lang('poultry::lang.current_qty')</th>
                <th class="text-right">@lang('poultry::lang.mortality_pct')</th>
                <th>@lang('poultry::lang.status')</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse ($batches as $batch)
                <tr>
                    <td><a href="{{ route('poultry.batch.show', $batch->id) }}">{{ $batch->batch_code }}</a></td>
                    <td>{{ $birdTypes[$batch->bird_type] ?? $batch->bird_type }}</td>
                    <td>{{ optional($batch->farm)->name }}</td>
                    <td>{{ optional($batch->house)->name }}</td>
                    <td>{{ optional($batch->placement_date)->format('Y-m-d') }}</td>
                    <td class="text-right">{{ $batch->age_days }}</td>
                    <td class="text-right">{{ number_format($batch->initial_qty) }}</td>
                    <td class="text-right">{{ number_format($batch->current_qty) }}</td>
                    <td class="text-right {{ $batch->mortality_pct > 5 ? 'text-red' : '' }}">{{ $batch->mortality_pct }}%</td>
                    <td>
                        <span class="label label-{{ $batch->status === 'active' ? 'success' : ($batch->status === 'closed' ? 'default' : 'warning') }}">
                            {{ $statuses[$batch->status] ?? $batch->status }}
                        </span>
                        @if ($batch->is_under_withdrawal)
                            <span class="label label-danger" title="@lang('poultry::lang.under_withdrawal')">
                                <i class="fa fa-ban"></i></span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('poultry.batch.show', $batch->id) }}" class="btn btn-xs btn-info">
                            <i class="fa fa-eye"></i></a>
                        @if ($batch->is_open)
                            <a href="{{ route('poultry.batch.edit', $batch->id) }}" class="btn btn-xs btn-default">
                                <i class="fa fa-edit"></i></a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>

        {{ $batches->appends(request()->query())->links() }}
    </div>
</div>
@endsection
