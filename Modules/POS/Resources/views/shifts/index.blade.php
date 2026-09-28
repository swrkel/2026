@extends('layouts.app')
@section('title', $title ?? __('pos::messages.shifts'))
@section('content')
@include('pos::partials.erp-standard-styles')
<section class="content pos-erp-standard-ui communication-hub-ui">
<div class="ch-shell">
    <div class="ch-hero pos-module-hero">
        <div>
            <div class="ch-eyebrow">POS Module</div>
            <h1>{{ $title ?? __('pos::messages.shifts') }}</h1>
            <p>Open, monitor and close POS register shifts.</p>
        </div>
        <div class="ch-quick-actions">
            <a href="{{ url('/pos-module') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
            <a href="{{ route('pos.shifts.open_form') }}" class="btn btn-success btn-sm"><i class="fa fa-play"></i> {{ __('pos::messages.open_shift') }}</a>
        </div>
    </div>
    @if(!empty($pageWarning))
        <div class="alert alert-warning"><i class="fa fa-warning"></i> {{ $pageWarning }}</div>
    @endif
@php($cp = session('business.currency_precision', 2))
<div class="row pos-kpi-row ch-kpi-row">
    <div class="col-md-3 col-sm-6"><div class="pos-kpi-card ch-card-accent ch-accent-blue"><span>{{ __('pos::messages.open_shifts') }}</span><strong>{{ $dashboard['open_shifts'] ?? 0 }}</strong><small>{{ __('pos::messages.current_shift') }}</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="pos-kpi-card ch-card-accent ch-accent-green"><span>{{ __('pos::messages.closed_today') }}</span><strong>{{ $dashboard['closed_today'] ?? 0 }}</strong><small>{{ __('pos::messages.shift_history') }}</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="pos-kpi-card ch-card-accent ch-accent-purple"><span>{{ __('pos::messages.cash_in_today') }}</span><strong>{{ number_format($dashboard['cash_in_today'] ?? 0, $cp) }}</strong><small>{{ __('pos::messages.cash_drawer') }}</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="pos-kpi-card ch-card-accent ch-accent-orange"><span>{{ __('pos::messages.cash_out_today') }}</span><strong>{{ number_format($dashboard['cash_out_today'] ?? 0, $cp) }}</strong><small>{{ __('pos::messages.cash_drawer') }}</small></div></div>
</div>
<div class="pos-toolbar-card box box-solid"><div class="box-body pos-toolbar"><input type="text" class="form-control pos-instant-search" data-target="#pos-shift-table" placeholder="{{ __('pos::messages.search') }}"><div class="pos-toolbar-right"><a href="{{ route('pos.shifts.open_form') }}" class="btn btn-success"><i class="fa fa-play"></i> {{ __('pos::messages.open_shift') }}</a><a href="{{ route('pos.shifts.current') }}" class="btn btn-info"><i class="fa fa-clock-o"></i> {{ __('pos::messages.current_shift') }}</a><button class="btn btn-default pos-print" data-table="#pos-shift-table"><i class="fa fa-print"></i> {{ __('pos::messages.print') }}</button></div></div></div>
<div class="box box-primary pos-table-card"><div class="box-body table-responsive"><table class="table table-bordered table-striped pos-standard-table" id="pos-shift-table"><thead><tr><th class="no-print">{{ __('pos::messages.action') }}</th><th>{{ __('pos::messages.shift_no') }}</th><th>{{ __('pos::messages.register') }}</th><th>{{ __('pos::messages.opened_at') }}</th><th>{{ __('pos::messages.closed_at') }}</th><th>{{ __('pos::messages.opening_amount') }}</th><th>{{ __('pos::messages.actual_closing_amount') }}</th><th>{{ __('pos::messages.variance') }}</th><th>{{ __('pos::messages.approval') }}</th><th>{{ __('pos::messages.status') }}</th></tr></thead><tbody>
@forelse($shifts as $shift)
<tr><td class="no-print"><div class="btn-group"><button class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">{{ __('pos::messages.action') }} <span class="caret"></span></button><ul class="dropdown-menu"><li><a href="{{ route('pos.shifts.summary', data_get($shift, 'id')) }}"><i class="fa fa-list-alt"></i> {{ __('pos::messages.summary') }}</a></li>@if((data_get($shift, 'status') ?? '') === 'open')<li><a href="{{ route('pos.shifts.close_form', data_get($shift, 'id')) }}"><i class="fa fa-stop"></i> {{ __('pos::messages.close_shift') }}</a></li>@endif</ul></div></td><td>{{ data_get($shift, 'session_no') ?? data_get($shift, 'id') }}</td><td>{{ data_get($shift, 'register_name') ?? '-' }}</td><td>{{ data_get($shift, 'opened_at') ?? '-' }}</td><td>{{ data_get($shift, 'closed_at') ?? '-' }}</td><td class="text-right">{{ number_format((float)(data_get($shift, 'opening_amount') ?? 0), $cp) }}</td><td class="text-right">{{ number_format((float)(data_get($shift, 'actual_closing_amount') ?? 0), $cp) }}</td><td class="text-right {{ (float)(data_get($shift, 'variance_amount') ?? 0) != 0 ? 'text-red' : '' }}">{{ number_format((float)(data_get($shift, 'variance_amount') ?? 0), $cp) }}</td><td><span class="label label-{{ (data_get($shift, 'approval_status') ?? 'approved') === 'pending' ? 'warning' : 'success' }}">{{ ucfirst(data_get($shift, 'approval_status') ?? 'approved') }}</span></td><td><span class="label label-{{ (data_get($shift, 'status') ?? '') === 'open' ? 'success' : 'default' }}">{{ ucfirst(data_get($shift, 'status') ?? '-') }}</span></td></tr>
@empty<tr><td colspan="10" class="text-center text-muted">{{ __('pos::messages.no_records_found') }}</td></tr>@endforelse
</tbody></table>@if(method_exists($shifts, 'links')) {{ $shifts->links() }} @endif</div></div>
</div>
</section>
@endsection
