@extends('poultry::layouts.app')
@section('title', __('poultry::lang.harvest'))

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.harvest')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.harvest.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.record_harvest')</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.date')</th>
                <th>@lang('poultry::lang.batch')</th>
                <th>@lang('poultry::lang.type')</th>
                <th class="text-right">@lang('poultry::lang.birds')</th>
                <th class="text-right">@lang('poultry::lang.weight_kg')</th>
                <th class="text-right">@lang('poultry::lang.avg_weight_kg')</th>
                <th>@lang('poultry::lang.buyer')</th>
                <th class="text-right">@lang('poultry::lang.value')</th>
                <th>@lang('poultry::lang.stock_posted')</th>
            </tr></thead>
            <tbody>
            @forelse ($harvests as $h)
                <tr>
                    <td>{{ optional($h->harvest_date)->format('Y-m-d') }}</td>
                    <td>{{ optional($h->batch)->batch_code }}</td>
                    <td>{{ \Modules\Poultry\Entities\Harvest::TYPES[$h->harvest_type] ?? $h->harvest_type }}</td>
                    <td class="text-right">{{ number_format($h->birds_qty) }}</td>
                    <td class="text-right">{{ number_format($h->total_weight_kg, 2) }}</td>
                    <td class="text-right">{{ number_format($h->avg_weight_kg, 3) }}</td>
                    <td>{{ optional($h->buyer)->name ?: '-' }}</td>
                    <td class="text-right">{{ number_format($h->total_value, 2) }}</td>
                    <td><span class="label label-{{ $h->is_posted ? 'success' : 'default' }}">
                        {{ $h->is_posted ? __('poultry::lang.yes') : __('poultry::lang.no') }}</span></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $harvests->appends(request()->query())->links() }}
    </div>
</div>
@endsection
