@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::messages.edit_lead'))
@section('leadsnew_content')
<section class="content-header leads-new-header">
    <h1><i class="fa fa-pencil"></i> {{ __('leadsnew::messages.edit_lead') }} - {{ $lead->lead_no }}</h1>
    <div class="breadcrumb-note"><a href="{{ url('/leads-new/leads') }}">{{ __('leadsnew::messages.leads') }}</a> / {{ __('leadsnew::messages.edit') }}</div>
</section>
<section class="content leads-new-page">
    <form method="post" action="{{ url('/leads-new/leads/' . $lead->id) }}">@csrf @method('PUT')
        <div class="ln-form-card">
            @include('leadsnew::leads.partials.form', ['lead' => $lead])
            <div class="text-right">
                <a href="{{ url('/leads-new/leads') }}" class="btn btn-default">{{ __('leadsnew::messages.cancel') }}</a>
                <button class="btn ln-btn-primary"><i class="fa fa-save"></i> {{ __('leadsnew::messages.update') }}</button>
            </div>
        </div>
    </form>
</section>
@endsection
@section('css')<link rel="stylesheet" href="{{ asset('Modules/LeadsNew/Resources/assets/css/leads_new.css') }}">@endsection
