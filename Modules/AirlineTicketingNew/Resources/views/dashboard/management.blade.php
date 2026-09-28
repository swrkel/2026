@extends('airlineticketingnew::layouts.app')
@section('atn-title','Management Dashboard')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-kpi-grid">
@foreach(['today_sales'=>'Today Sales','today_collections'=>'Today Collections','today_refunds'=>'Today Refunds','today_tickets'=>'Today Tickets','receivables'=>'Receivables','payables'=>'Payables','bsp_due'=>'BSP Due','net_profit'=>'Net Profit'] as $key=>$label)
<div class="atn-kpi"><div class="atn-kpi-label">{{ $label }}</div><div class="atn-kpi-value">{{ number_format((float)$metrics[$key],4) }}</div></div>
@endforeach
</div>
@endsection
