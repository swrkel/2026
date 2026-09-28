@extends('pumperdashboardnew::layouts.operator')
@section('title','Other Sales')
@section('pone_content')
<div class="pone-page-head"><div><div class="pone-page-kicker">Non-Fuel Sales</div><h1>Add Other Sale</h1><p>Shift {{ $shift->shift_number }}</p></div><div class="pone-actions"><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.other-sales.index') }}">List Other Sales</a><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.dashboard') }}">Dashboard</a></div></div>
<form method="post" action="{{ route('pumper-dashboard-new.operator.other-sales.store') }}" data-processing-text="Saving other sale…">@csrf @include('pumperdashboardnew::operator.other-sales._form')</form>
@endsection
