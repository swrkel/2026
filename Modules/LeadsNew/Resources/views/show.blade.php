@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::messages.leads_new'))
@section('leadsnew_content')
<section class="content-header leads-new-header"><h1><i class="fa fa-lightbulb-o"></i> {{ __('leadsnew::messages.leads_new') }}</h1></section>
<section class="content leads-new-page">
    <div class="ln-panel"><div class="ln-panel-body">
        <p>{{ __('leadsnew::messages.standalone_module') }}</p>
        <a href="{{ url('/leads-new') }}" class="btn ln-btn-primary">{{ __('leadsnew::messages.dashboard') }}</a>
    </div></div>
</section>
@endsection
@section('css')<link rel="stylesheet" href="{{ asset('Modules/LeadsNew/Resources/assets/css/leads_new.css') }}">@endsection
