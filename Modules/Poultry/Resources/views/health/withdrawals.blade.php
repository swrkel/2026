@extends('poultry::layouts.app')
@section('title', __('poultry::lang.active_withdrawals'))

@section('content')
<div class="box box-danger">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-ban"></i> @lang('poultry::lang.active_withdrawals')</h3>
    </div>
    <div class="box-body">
        <p class="text-muted">@lang('poultry::lang.withdrawal_warning')</p>
        <table class="table table-bordered table-condensed">
            <thead><tr>
                <th>@lang('poultry::lang.batch')</th>
                <th>@lang('poultry::lang.medication')</th>
                <th>@lang('poultry::lang.started_on')</th>
                <th>@lang('poultry::lang.ended_on')</th>
                <th>@lang('poultry::lang.clear_on')</th>
                <th class="text-right">@lang('poultry::lang.days_remaining')</th>
            </tr></thead>
            <tbody>
            @forelse ($withdrawals as $w)
                <tr>
                    <td>{{ optional($w->batch)->batch_code }}</td>
                    <td>{{ $w->name }}</td>
                    <td>{{ optional($w->started_on)->format('Y-m-d') }}</td>
                    <td>{{ optional($w->ended_on)->format('Y-m-d') ?: '-' }}</td>
                    <td><strong>{{ optional($w->withdrawal_until)->format('Y-m-d') }}</strong></td>
                    <td class="text-right"><span class="label label-danger">{{ $w->days_remaining }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">@lang('poultry::lang.none_active')</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
