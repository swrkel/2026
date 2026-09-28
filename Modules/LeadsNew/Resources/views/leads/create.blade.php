@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::messages.add_lead'))
@section('leadsnew_content')
<section class="content-header leads-new-header">
    <h1><i class="fa fa-plus-circle"></i> {{ __('leadsnew::messages.add_lead') }}</h1>
    <div class="breadcrumb-note"><a href="{{ url('/leads-new') }}">{{ __('leadsnew::messages.dashboard') }}</a> / {{ __('leadsnew::messages.add_lead') }}</div>
</section>
<section class="content leads-new-page">
    @if(!empty($missingTables ?? []))
        <div class="ln-setup-alert">
            <div class="ln-setup-icon"><i class="fa fa-database"></i></div>
            <div>
                <strong>{{ __('leadsnew::messages.database_setup_pending_title') }}</strong><br>
                <span>{{ __('leadsnew::messages.database_setup_pending_clean') }}</span>
            </div>
        </div>
    @endif

    <form method="post" action="{{ url('/leads-new/leads') }}">@csrf
        <div class="ln-form-card">
            @include('leadsnew::leads.partials.form', ['lead' => null, 'leadNo' => $leadNo])
            <div class="text-right">
                <a href="{{ url('/leads-new/leads') }}" class="btn btn-default">{{ __('leadsnew::messages.cancel') }}</a>
                <button class="btn ln-btn-primary"><i class="fa fa-save"></i> {{ __('leadsnew::messages.save') }}</button>
            </div>
        </div>
    </form>
</section>
@endsection
@section('css')<link rel="stylesheet" href="{{ asset('Modules/LeadsNew/Resources/assets/css/leads_new.css') }}">@endsection
