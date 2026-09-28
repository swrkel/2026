@extends('poultry::layouts.app')
@section('title', __('poultry::lang.daily_records'))

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('poultry::lang.daily_records')</h3>
        <div class="box-tools">
            <a href="{{ route('poultry.daily.entry') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-pencil"></i> @lang('poultry::lang.daily_entry')</a>
        </div>
    </div>
    <div class="box-body">
        @include('poultry::partials.date_filter')

        <div class="table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.date')</th>
                <th>@lang('poultry::lang.batch')</th>
                <th class="text-right">@lang('poultry::lang.age_days')</th>
                <th class="text-right">@lang('poultry::lang.mortality')</th>
                <th class="text-right">@lang('poultry::lang.culls')</th>
                <th class="text-right">@lang('poultry::lang.feed_kg')</th>
                <th class="text-right">@lang('poultry::lang.water_l')</th>
                <th class="text-right">@lang('poultry::lang.avg_weight_g')</th>
                <th>@lang('poultry::lang.mortality_cause')</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse ($records as $r)
                <tr>
                    <td>{{ optional($r->record_date)->format('Y-m-d') }}</td>
                    <td>{{ optional($r->batch)->batch_code }}</td>
                    <td class="text-right">{{ $r->age_days }}</td>
                    <td class="text-right">{{ $r->mortality }}</td>
                    <td class="text-right">{{ $r->culls }}</td>
                    <td class="text-right">{{ number_format($r->feed_kg, 2) }}</td>
                    <td class="text-right">{{ number_format($r->water_litres, 2) }}</td>
                    <td class="text-right">{{ $r->avg_weight_g ?: '-' }}</td>
                    <td>{{ $r->mortality_cause ?: '-' }}</td>
                    <td>
                        <button class="btn btn-xs btn-danger btn-del-daily" data-id="{{ $r->id }}">
                            <i class="fa fa-trash"></i></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $records->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    $('.btn-del-daily').on('click', function () {
        if (!confirm('@lang('poultry::lang.confirm_delete_daily')')) { return; }
        var id = $(this).data('id');
        $.ajax({ url: '{{ url('poultry/daily') }}/' + id, type: 'DELETE' })
            .done(function () { location.reload(); });
    });
});
</script>
@endsection
