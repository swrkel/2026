@extends('poultry::layouts.app')
@section('title', __('poultry::lang.egg_grades'))
@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.egg_grades')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.egg-grades.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.add')</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.name')</th><th>@lang('poultry::lang.code')</th>
                <th>@lang('poultry::lang.weight_range_g')</th>
                <th>@lang('poultry::lang.sell_as')</th>
                <th>@lang('poultry::lang.saleable')</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->code ?: '-' }}</td>
                    <td>{{ $row->min_weight_g ?: '-' }} &ndash; {{ $row->max_weight_g ?: '-' }}</td>
                    <td>
                        @if ($row->variation)
                            {{ optional($row->variation->product)->name }}
                        @else
                            <span class="text-muted">@lang('poultry::lang.not_stocked')</span>
                        @endif
                    </td>
                    <td><span class="label label-{{ $row->is_saleable ? 'success' : 'default' }}">
                        {{ $row->is_saleable ? __('poultry::lang.yes') : __('poultry::lang.no') }}</span></td>
                    <td><a href="{{ route('poultry.egg-grades.edit', $row->id) }}" class="btn btn-xs btn-default">
                        <i class="fa fa-edit"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
