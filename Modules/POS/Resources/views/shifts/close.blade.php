@extends('pos::layouts.app', ['title' => __('pos::messages.close_shift')])
@section('pos_content')
@php($s = $summary['session'] ?? null) @php($cp = session('business.currency_precision', 2))
<div class="box box-primary pos-table-card"><div class="box-body"><form method="POST" action="{{ route('pos.shifts.close', $s->id ?? 0) }}">@csrf
<div class="row pos-kpi-row ch-kpi-row">
@foreach(['opening'=>'opening_amount','cash_sales'=>'cash_sales','cash_in'=>'cash_in','cash_out'=>'cash_out'] as $key=>$label)
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-blue"><span>{{ __('pos::messages.'.$label) }}</span><strong>{{ number_format($summary[$key] ?? 0, $cp) }}</strong></div></div>
@endforeach
</div>
<div class="row pos-kpi-row ch-kpi-row">
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-purple"><span>{{ __('pos::messages.card_sales') }}</span><strong>{{ number_format($summary['card_sales'] ?? 0, $cp) }}</strong></div></div>
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-orange"><span>{{ __('pos::messages.credit_sales') }}</span><strong>{{ number_format($summary['credit_sales'] ?? 0, $cp) }}</strong></div></div>
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-red"><span>{{ __('pos::messages.refunds') }}</span><strong>{{ number_format($summary['refunds'] ?? 0, $cp) }}</strong></div></div>
<div class="col-md-3"><div class="pos-kpi-card ch-card-accent ch-accent-green"><span>{{ __('pos::messages.expected_closing_amount') }}</span><strong>{{ number_format($summary['expected'] ?? 0, $cp) }}</strong></div></div>
</div>
<div class="row"><div class="col-md-4"><label>{{ __('pos::messages.actual_closing_amount') }}</label><input type="number" step="0.0001" name="actual_closing_amount" class="form-control text-right pos-calc-variance" data-expected="{{ $summary['expected'] ?? 0 }}" required></div><div class="col-md-4"><label>{{ __('pos::messages.variance') }}</label><input type="text" class="form-control text-right pos-variance-output" readonly></div><div class="col-md-4"><label>{{ __('pos::messages.closed_at') }}</label><input type="text" name="closed_at" class="form-control pos-datetimepicker" value="{{ now()->format('Y-m-d H:i:s') }}"></div></div>
<div class="form-group"><label>{{ __('pos::messages.note') }}</label><textarea name="closing_note" rows="3" class="form-control"></textarea></div><div class="text-right"><button class="btn btn-danger pos-large-save"><i class="fa fa-stop"></i> {{ __('pos::messages.close_shift') }}</button></div>
</form></div></div>
@endsection
