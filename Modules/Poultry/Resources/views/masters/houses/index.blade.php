@extends('poultry::layouts.app')
@section('title', __('poultry::lang.houses'))
@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.houses')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.houses.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.add')</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <form method="GET" class="form-inline" style="margin-bottom:12px">
            <select name="farm_id" class="form-control input-sm" onchange="this.form.submit()">
                <option value="">@lang('poultry::lang.all_farms')</option>
                @foreach ($farms as $id => $name)
                    <option value="{{ $id }}" {{ request('farm_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </form>
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.name')</th><th>@lang('poultry::lang.farm')</th>
                <th>@lang('poultry::lang.housing_type')</th>
                <th class="text-right">@lang('poultry::lang.capacity')</th>
                <th>@lang('poultry::lang.current_batch')</th>
                <th class="text-right">@lang('poultry::lang.density')</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ optional($row->farm)->name }}</td>
                    <td>{{ \Modules\Poultry\Entities\House::TYPES[$row->housing_type] ?? $row->housing_type }}</td>
                    <td class="text-right">{{ number_format($row->capacity) }}</td>
                    <td>
                        @if ($row->activeBatch)
                            <a href="{{ route('poultry.batch.show', $row->activeBatch->id) }}">
                                {{ $row->activeBatch->batch_code }}</a>
                        @else
                            <span class="text-muted">@lang('poultry::lang.vacant')</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $row->stocking_density ?? '-' }}</td>
                    <td><a href="{{ route('poultry.houses.edit', $row->id) }}" class="btn btn-xs btn-default">
                        <i class="fa fa-edit"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $rows->appends(request()->query())->links() }}
    </div>
</div>
@endsection
