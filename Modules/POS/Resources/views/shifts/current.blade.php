@extends('pos::layouts.app', ['title' => __('pos::messages.current_shift')])
@section('pos_content')
@php($cp = session('business.currency_precision', 2))
<div class="box box-primary pos-table-card"><div class="box-body">
@if($shift)
<div class="row pos-kpi-row ch-kpi-row">
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-blue"><span>{{ __('pos::messages.shift_no') }}</span><strong>{{ $shift->session_no ?? $shift->id }}</strong><small>{{ $shift->opened_at ?? '-' }}</small></div></div>
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-green"><span>{{ __('pos::messages.cash_sales') }}</span><strong>{{ number_format($summary['cash_sales'] ?? 0, $cp) }}</strong><small>{{ __('pos::messages.sales_today') }}</small></div></div>
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-purple"><span>{{ __('pos::messages.expected_closing_amount') }}</span><strong>{{ number_format($summary['expected'] ?? 0, $cp) }}</strong><small>{{ __('pos::messages.cash_drawer') }}</small></div></div>
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-orange"><span>{{ __('pos::messages.sales_count') }}</span><strong>{{ $summary['sales_count'] ?? 0 }}</strong><small>{{ __('pos::messages.recent_transactions') }}</small></div></div>
</div>
<div class="text-right"><a href="{{ route('pos.cash_drawer.cash_in_form') }}" class="btn btn-success"><i class="fa fa-plus"></i> {{ __('pos::messages.cash_in') }}</a> <a href="{{ route('pos.cash_drawer.cash_out_form') }}" class="btn btn-warning"><i class="fa fa-minus"></i> {{ __('pos::messages.cash_out') }}</a> <a href="{{ route('pos.shifts.close_form', $shift->id) }}" class="btn btn-danger pos-large-save"><i class="fa fa-stop"></i> {{ __('pos::messages.close_shift') }}</a></div>
@else <div class="text-center pos-empty-state"><p class="text-muted">{{ __('pos::messages.no_open_shift') }}</p><a href="{{ route('pos.shifts.open_form') }}" class="btn btn-success pos-large-save"><i class="fa fa-play"></i> {{ __('pos::messages.open_shift') }}</a></div> @endif
</div></div>
@endsection
