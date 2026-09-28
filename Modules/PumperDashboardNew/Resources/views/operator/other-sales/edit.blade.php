@extends('pumperdashboardnew::layouts.operator')
@section('title','Edit Other Sale')
@section('pone_content')
<div class="pone-page-head"><div><div class="pone-page-kicker">Controlled Edit</div><h1>Edit {{ $sale->sale_number }}</h1><p>Every changed value and the edit reason are retained.</p></div><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.other-sales.show',$sale) }}">Back</a></div>
<form method="post" action="{{ route('pumper-dashboard-new.operator.other-sales.update',$sale) }}" data-confirm-message="Save these changes to the other sale?" data-processing-text="Updating other sale…">@csrf @method('PUT') @include('pumperdashboardnew::operator.other-sales._form')</form>
@endsection
