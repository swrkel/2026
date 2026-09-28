@extends('poultry::layouts.app')
@section('title', __('poultry::lang.breeds'))
@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.breeds')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.breeds.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.add')</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.name')</th><th>@lang('poultry::lang.bird_type')</th>
                <th class="text-right">@lang('poultry::lang.target_fcr')</th>
                <th>@lang('poultry::lang.has_standard')</th>
                <th>@lang('poultry::lang.status')</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ \Modules\Poultry\Entities\Breed::BIRD_TYPES[$row->bird_type] ?? $row->bird_type }}</td>
                    <td class="text-right">{{ $row->target_fcr ?: '-' }}</td>
                    <td>{{ $row->standard_curve ? __('poultry::lang.yes') : __('poultry::lang.no') }}</td>
                    <td><span class="label label-{{ $row->is_active ? 'success' : 'default' }}">
                        {{ $row->is_active ? __('poultry::lang.active') : __('poultry::lang.inactive') }}</span></td>
                    <td><a href="{{ route('poultry.breeds.edit', $row->id) }}" class="btn btn-xs btn-default">
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
