@extends('pumperdashboardnew::layouts.operator')
@section('title','Unload Stock')
@section('pone_content')
<div class="pone-page-head"><div><div class="pone-page-kicker">Stock Receipt</div><h1>Unload Stock</h1><p>Shift {{ $shift->shift_number }}</p></div><div class="pone-actions"><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.unload-stock.index') }}">Unload Details</a><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.dashboard') }}">Dashboard</a></div></div>
<form method="post" action="{{ route('pumper-dashboard-new.operator.unload-stock.store') }}" data-processing-text="Saving unload stock…">@csrf @include('pumperdashboardnew::operator.unload-stock._form')</form>
@endsection
