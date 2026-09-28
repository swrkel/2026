@extends('pos::layouts.app', ['title' => __('pos::messages.shift_summary')])
@section('pos_content')
@php($cp = session('business.currency_precision', 2))
<div class="box box-primary pos-table-card"><div class="box-body"><div class="row pos-kpi-row ch-kpi-row">
@foreach(['opening'=>'opening_amount','cash_sales'=>'cash_sales','card_sales'=>'card_sales','credit_sales'=>'credit_sales','cash_in'=>'cash_in','cash_out'=>'cash_out','refunds'=>'refunds','expected'=>'expected_closing_amount','actual'=>'actual_closing_amount','variance'=>'variance'] as $key=>$label)
<div class="col-md-3 col-sm-6"><div class="pos-kpi-card ch-card-accent ch-accent-blue"><span>{{ __('pos::messages.'.$label) }}</span><strong>{{ number_format($summary[$key] ?? 0, $cp) }}</strong></div></div>
@endforeach
<div class="col-md-3 col-sm-6"><div class="pos-kpi-card ch-card-accent ch-accent-green"><span>{{ __('pos::messages.sales_count') }}</span><strong>{{ $summary['sales_count'] ?? 0 }}</strong></div></div>
</div><div class="text-right"><button class="btn btn-default pos-print-page"><i class="fa fa-print"></i> {{ __('pos::messages.print') }}</button></div></div></div>
@endsection
