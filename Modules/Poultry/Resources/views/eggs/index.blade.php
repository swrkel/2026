@extends('poultry::layouts.app')
@section('title', __('poultry::lang.egg_collections'))

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.egg_collections')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.egg.entry') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> @lang('poultry::lang.record_collection')</a>
        </div>
    </div>
    <div class="box-body">
        @include('poultry::partials.date_filter')

        <div class="table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.date')</th>
                <th>@lang('poultry::lang.batch')</th>
                <th>@lang('poultry::lang.slot')</th>
                <th>@lang('poultry::lang.grade')</th>
                <th class="text-right">@lang('poultry::lang.qty')</th>
                <th class="text-right">@lang('poultry::lang.trays')</th>
                <th class="text-right">@lang('poultry::lang.weight_kg')</th>
                <th>@lang('poultry::lang.stock_posted')</th>
            </tr></thead>
            <tbody>
            @forelse ($collections as $c)
                <tr>
                    <td>{{ optional($c->collection_date)->format('Y-m-d') }}</td>
                    <td>{{ optional($c->batch)->batch_code }}</td>
                    <td>{{ \Modules\Poultry\Entities\EggCollection::SLOTS[$c->slot] ?? $c->slot }}</td>
                    <td>{{ optional($c->grade)->name }}</td>
                    <td class="text-right">{{ number_format($c->qty) }}</td>
                    <td class="text-right">{{ $c->trays ?? '-' }}</td>
                    <td class="text-right">{{ $c->weight_kg ? number_format($c->weight_kg, 3) : '-' }}</td>
                    <td>
                        @if ($c->is_posted)
                            <span class="label label-success">@lang('poultry::lang.yes')</span>
                        @else
                            <span class="label label-default">@lang('poultry::lang.no')</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $collections->appends(request()->query())->links() }}
    </div>
</div>
@endsection
