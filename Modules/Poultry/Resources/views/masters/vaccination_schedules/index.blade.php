@extends('poultry::layouts.app')
@section('title', __('poultry::lang.vaccination_schedules'))
@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.vaccination_schedules')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.vaccination-schedules.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.add')</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th class="text-right">@lang('poultry::lang.age_days')</th>
                <th>@lang('poultry::lang.name')</th><th>@lang('poultry::lang.disease')</th>
                <th>@lang('poultry::lang.bird_type')</th><th>@lang('poultry::lang.breed')</th>
                <th>@lang('poultry::lang.route')</th><th>@lang('poultry::lang.mandatory')</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="text-right"><strong>{{ $row->age_days }}</strong></td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->disease ?: '-' }}</td>
                    <td>{{ $row->bird_type }}</td>
                    <td>{{ optional($row->breed)->name ?: __('poultry::lang.all_breeds') }}</td>
                    <td>{{ \Modules\Poultry\Entities\VaccinationSchedule::ROUTES[$row->route] ?? $row->route }}</td>
                    <td>{{ $row->is_mandatory ? __('poultry::lang.yes') : __('poultry::lang.no') }}</td>
                    <td><a href="{{ route('poultry.vaccination-schedules.edit', $row->id) }}"
                           class="btn btn-xs btn-default"><i class="fa fa-edit"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
