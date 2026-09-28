@extends('poultry::layouts.app')
@section('title', __('poultry::lang.add').' '.__('poultry::lang.house'))
@section('content')
<form method="POST" action="{{ route('poultry.houses.store') }}">
    @csrf
    <div class="row"><div class="col-md-8 col-md-offset-2"><div class="box box-primary">
        <div class="box-body">@include('poultry::masters.houses._form')</div>
        <div class="box-footer">
            <button class="btn btn-primary">@lang('poultry::lang.save')</button>
            <a href="{{ route('poultry.houses.index') }}" class="btn btn-link">@lang('poultry::lang.cancel')</a>
        </div>
    </div></div></div>
</form>
@endsection
