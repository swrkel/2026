@extends('distributionnew::layouts.app')
@section('title', __('distributionnew::messages.dashboard'))
@section('page_actions')<a class="disnew-btn" href="{{ route('distributionnew.sales-orders.create') }}">+ New Sales Order</a>@endsection
@section('module_content')
<div class="disnew-grid">
@foreach($cards as $key => $value)<div class="disnew-kpi"><span>{{ ucwords(str_replace('_',' ', $key)) }}</span><strong>{{ $value }}</strong></div>@endforeach
</div>
<div class="disnew-card"><h4>Distribution New Workflow</h4><p>Sales Order → Sales Invoice → Loading → Vehicle Stock → Delivery / Unloading → Reports.</p></div>
@endsection
