@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::lang.administration_centre'))
@section('leadsnew_subtitle', 'Manage module master data, lead stages, sources, territories and communication templates.')

@section('leadsnew_content')
    <div class="ln-card-grid">
        <a class="ln-report-card" href="{{ url('/leads-new/settings') }}"><span class="report-icon"><i class="fa fa-tags"></i></span><strong>@lang('leadsnew::lang.status_manager')</strong><span>Maintain the statuses used throughout the lead workflow.</span></a>
        <a class="ln-report-card" href="{{ url('/leads-new/campaigns') }}"><span class="report-icon"><i class="fa fa-bullhorn"></i></span><strong>@lang('leadsnew::lang.source_manager')</strong><span>Review lead acquisition sources and campaign structures.</span></a>
        <a class="ln-report-card" href="{{ url('/leads-new/territories') }}"><span class="report-icon"><i class="fa fa-map-marker"></i></span><strong>@lang('leadsnew::lang.territory_manager')</strong><span>Organize lead territories for the active business.</span></a>
        <a class="ln-report-card" href="{{ url('/leads-new/templates') }}"><span class="report-icon"><i class="fa fa-envelope-o"></i></span><strong>@lang('leadsnew::lang.template_manager')</strong><span>Maintain reusable message templates for lead communications.</span></a>
    </div>
@endsection
