@extends('poultry::layouts.app')
@section('title', __('poultry::lang.mortality'))
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.mortality')</h3></div>
            <div class="box-body table-responsive">
                <form method="GET" class="form-inline" style="margin-bottom:15px">
                    <input type="date" name="from" value="{{ $from }}" class="form-control input-sm">
                    <input type="date" name="to" value="{{ $to }}" class="form-control input-sm">
                    <select name="batch_id" class="form-control input-sm">
                        <option value="">@lang('poultry::lang.all_batches')</option>
                        @foreach ($batches as $id => $code)
                            <option value="{{ $id }}" {{ request('batch_id') == $id ? 'selected' : '' }}>{{ $code }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-default btn-sm"><i class="fa fa-filter"></i> @lang('poultry::lang.filter')</button>
                </form>

                <table class="table table-bordered table-striped table-condensed">
                    <thead><tr>
                        <th>@lang('poultry::lang.date')</th><th>@lang('poultry::lang.batch')</th>
                        <th class="text-right">@lang('poultry::lang.mortality')</th>
                        <th class="text-right">@lang('poultry::lang.culls')</th>
                        <th class="text-right">@lang('poultry::lang.total')</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td>{{ $r->record_date }}</td>
                            <td>{{ optional($r->batch)->batch_code }}</td>
                            <td class="text-right">{{ $r->mortality }}</td>
                            <td class="text-right">{{ $r->culls }}</td>
                            <td class="text-right"><strong>{{ $r->mortality + $r->culls }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">@lang('poultry::lang.no_records')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-danger">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.by_cause')</h3></div>
            <div class="box-body">
                <table class="table table-condensed">
                    <tbody>
                    @forelse ($byCause as $c)
                        <tr>
                            <td>{{ $c->mortality_cause }}</td>
                            <td class="text-right"><strong>{{ number_format($c->total) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td class="text-muted text-center">@lang('poultry::lang.no_cause_recorded')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
