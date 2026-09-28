@extends('poultry::layouts.app')
@section('title', __('poultry::lang.hatchery'))

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.hatch_sets')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.hatchery.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.set_eggs')</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.set_code')</th>
                <th>@lang('poultry::lang.set_date')</th>
                <th class="text-right">@lang('poultry::lang.eggs_set')</th>
                <th class="text-right">@lang('poultry::lang.fertile')</th>
                <th class="text-right">@lang('poultry::lang.hatched')</th>
                <th class="text-right">@lang('poultry::lang.fertility')</th>
                <th class="text-right">@lang('poultry::lang.hatchability')</th>
                <th class="text-right">@lang('poultry::lang.hatch_of_set')</th>
                <th>@lang('poultry::lang.status')</th>
            </tr></thead>
            <tbody>
            @forelse ($sets as $s)
                <tr>
                    <td><a href="{{ route('poultry.hatchery.show', $s->id) }}">{{ $s->set_code }}</a></td>
                    <td>{{ optional($s->set_date)->format('Y-m-d') }}</td>
                    <td class="text-right">{{ number_format($s->eggs_set) }}</td>
                    <td class="text-right">{{ number_format($s->fertile_eggs) }}</td>
                    <td class="text-right">{{ number_format($s->chicks_hatched) }}</td>
                    <td class="text-right">{{ $s->fertility_pct !== null ? $s->fertility_pct.'%' : '-' }}</td>
                    <td class="text-right">{{ $s->hatchability_pct !== null ? $s->hatchability_pct.'%' : '-' }}</td>
                    <td class="text-right">{{ $s->hatch_of_set_pct !== null ? $s->hatch_of_set_pct.'%' : '-' }}</td>
                    <td><span class="label label-default">{{ $statuses[$s->status] ?? $s->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $sets->appends(request()->query())->links() }}
    </div>
</div>
@endsection
