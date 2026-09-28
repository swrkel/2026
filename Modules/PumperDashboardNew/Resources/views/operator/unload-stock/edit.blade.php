@extends('pumperdashboardnew::layouts.operator')
@section('title','Edit Unload Stock')
@section('pone_content')
<div class="pone-page-head"><div><div class="pone-page-kicker">Controlled Edit</div><h1>Edit {{ $unload->receipt_number }}</h1><p>All changes are audited before the shift source is made available to Petro PD-New.</p></div><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.unload-stock.show',$unload) }}">Back</a></div>
<form method="post" action="{{ route('pumper-dashboard-new.operator.unload-stock.update',$unload) }}" data-confirm-message="Save these unload-stock changes?" data-processing-text="Updating unload stock…">@csrf @method('PUT') @include('pumperdashboardnew::operator.unload-stock._form')</form>
@endsection
