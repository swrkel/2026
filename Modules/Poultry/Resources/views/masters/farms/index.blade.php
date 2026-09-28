@extends('poultry::layouts.app')
@section('title', __('poultry::lang.farms'))

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.farms')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.farms.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.add')</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.name')</th><th>@lang('poultry::lang.code')</th>
                <th>@lang('poultry::lang.city')</th>
                <th class="text-right">@lang('poultry::lang.houses')</th>
                <th>@lang('poultry::lang.status')</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->code ?: '-' }}</td>
                    <td>{{ $row->city ?: '-' }}</td>
                    <td class="text-right">{{ $row->houses_count }}</td>
                    <td><span class="label label-{{ $row->is_active ? 'success' : 'default' }}">
                        {{ $row->is_active ? __('poultry::lang.active') : __('poultry::lang.inactive') }}</span></td>
                    <td>
                        <a href="{{ route('poultry.farms.edit', $row->id) }}" class="btn btn-xs btn-default">
                            <i class="fa fa-edit"></i></a>
                    </td>
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
