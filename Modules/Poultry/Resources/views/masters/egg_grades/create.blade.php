@extends('poultry::layouts.app')
@section('title', __('poultry::lang.add').' '.__('poultry::lang.egg_grade'))
@section('content')
<form method="POST" action="{{ route('poultry.egg-grades.store') }}">
    @csrf
    <div class="row"><div class="col-md-8 col-md-offset-2"><div class="box box-primary">
        <div class="box-body">@include('poultry::masters.egg_grades._form')</div>
        <div class="box-footer">
            <button class="btn btn-primary">@lang('poultry::lang.save')</button>
            <a href="{{ route('poultry.egg-grades.index') }}" class="btn btn-link">@lang('poultry::lang.cancel')</a>
        </div>
    </div></div></div>
</form>
@endsection
