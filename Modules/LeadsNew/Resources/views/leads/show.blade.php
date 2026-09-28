@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::messages.view_lead'))
@section('leadsnew_content')
<section class="content-header leads-new-header">
    <h1><i class="fa fa-eye"></i> {{ __('leadsnew::messages.lead_details') }} - {{ $lead->lead_no }}</h1>
    <div class="breadcrumb-note"><a href="{{ url('/leads-new/leads') }}">{{ __('leadsnew::messages.leads') }}</a> / {{ __('leadsnew::messages.view') }}</div>
</section>
<section class="content leads-new-page">
    <div class="ln-toolbar">
        <div>
            <a href="{{ url('/leads-new/leads') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> {{ __('leadsnew::messages.back') }}</a>
            <a href="{{ url('/leads-new/leads/' . $lead->id . '/edit') }}" class="btn ln-btn-primary"><i class="fa fa-pencil"></i> {{ __('leadsnew::messages.edit') }}</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="ln-panel">
                <div class="ln-panel-header"><h3 class="ln-panel-title">{{ __('leadsnew::messages.lead_information') }}</h3></div>
                <div class="ln-panel-body">
                    <table class="table table-bordered">
                        <tr><th style="width:220px">{{ __('leadsnew::messages.lead_no') }}</th><td>{{ $lead->lead_no }}</td></tr>
                        <tr><th>{{ __('leadsnew::messages.name') }}</th><td>{{ $lead->name }}</td></tr>
                        <tr><th>{{ __('leadsnew::messages.mobile') }}</th><td>{{ $lead->mobile ?: '-' }}</td></tr>
                        <tr><th>{{ __('leadsnew::messages.email') }}</th><td>{{ $lead->email ?: '-' }}</td></tr>
                        <tr><th>{{ __('leadsnew::messages.source') }}</th><td>{{ $lead->source ?: '-' }}</td></tr>
                        <tr><th>{{ __('leadsnew::messages.status') }}</th><td><span class="ln-badge status-{{ strtolower(str_replace(' ', '-', $lead->status ?: 'new')) }}">{{ $lead->status ?: 'New' }}</span></td></tr>
                        <tr><th>{{ __('leadsnew::messages.priority') }}</th><td>{{ $lead->priority ?: '-' }}</td></tr>
                        <tr><th>{{ __('leadsnew::messages.notes') }}</th><td>{!! nl2br(e($lead->note ?: '-')) !!}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ln-panel">
                <div class="ln-panel-header"><h3 class="ln-panel-title">{{ __('leadsnew::messages.actions') }}</h3></div>
                <div class="ln-panel-body">
                    <a href="{{ url('/leads-new/followups?lead_id=' . $lead->id) }}" class="btn btn-info btn-block"><i class="fa fa-calendar"></i> {{ __('leadsnew::messages.followups') }}</a>
                    <a href="{{ url('/leads-new/documents?lead_id=' . $lead->id) }}" class="btn btn-default btn-block"><i class="fa fa-file"></i> {{ __('leadsnew::messages.documents') }}</a>
                    <form method="post" action="{{ url('/leads-new/leads/' . $lead->id . '/duplicate') }}">@csrf
                        <button class="btn btn-warning btn-block"><i class="fa fa-copy"></i> {{ __('leadsnew::messages.duplicate') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('css')<link rel="stylesheet" href="{{ asset('Modules/LeadsNew/Resources/assets/css/leads_new.css') }}">@endsection
