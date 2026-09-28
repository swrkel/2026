@extends('poultry::layouts.app')
@section('title', __('poultry::lang.feed_consumption'))

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('poultry::lang.feed_consumption')</h3>
                <div class="box-tools">
                    <a href="{{ route('poultry.feed.issue.form') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> @lang('poultry::lang.feed_issue')</a>
                </div>
            </div>
            <div class="box-body">
                @include('poultry::partials.date_filter')

                <div class="table-responsive">
                <table class="table table-bordered table-striped table-condensed">
                    <thead><tr>
                        <th>@lang('poultry::lang.date')</th>
                        <th>@lang('poultry::lang.batch')</th>
                        <th>@lang('poultry::lang.item')</th>
                        <th class="text-right">@lang('poultry::lang.qty')</th>
                        <th class="text-right">@lang('poultry::lang.cost')</th>
                        <th>@lang('poultry::lang.stock_posted')</th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    @forelse ($consumptions as $c)
                        <tr>
                            <td>{{ optional($c->consumption_date)->format('Y-m-d') }}</td>
                            <td>{{ optional($c->batch)->batch_code }}</td>
                            <td>{{ optional(optional($c->variation)->product)->name ?: '-' }}</td>
                            <td class="text-right">{{ number_format($c->qty, 3) }}</td>
                            <td class="text-right">{{ number_format($c->total_cost, 2) }}</td>
                            <td>
                                <span class="label label-{{ $c->is_posted ? 'success' : 'default' }}">
                                    {{ $c->is_posted ? __('poultry::lang.yes') : __('poultry::lang.no') }}</span>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-danger btn-reverse-feed" data-id="{{ $c->id }}"
                                        title="@lang('poultry::lang.reverse')"><i class="fa fa-undo"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
                {{ $consumptions->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.by_batch')</h3></div>
            <div class="box-body">
                <table class="table table-condensed">
                    <thead><tr>
                        <th>@lang('poultry::lang.batch')</th>
                        <th class="text-right">@lang('poultry::lang.feed_kg')</th>
                        <th class="text-right">@lang('poultry::lang.cost')</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($summary as $s)
                        <tr>
                            <td>{{ optional($s->batch)->batch_code }}</td>
                            <td class="text-right">{{ number_format($s->total_qty, 1) }}</td>
                            <td class="text-right">{{ number_format($s->total_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">@lang('poultry::lang.no_records')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    $('.btn-reverse-feed').on('click', function () {
        if (!confirm('@lang('poultry::lang.confirm_reverse_feed')')) { return; }
        $.ajax({ url: '{{ url('poultry/feed') }}/' + $(this).data('id'), type: 'DELETE' })
            .done(function (res) { alert(res.msg); location.reload(); });
    });
});
</script>
@endsection
