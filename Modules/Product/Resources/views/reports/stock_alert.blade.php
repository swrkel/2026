@extends('product::layouts.app', ['title'=>__('product::stock.stock_alert'), 'heading'=>__('product::stock.stock_alert')])
@section('product_content')
<div class="alert alert-info">@lang('product::stock.stock_alert_report_ready')</div>
@endsection
