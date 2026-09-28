@extends('layouts.app')
@section('title', 'Membership Reports')

@section('content')
<section class="content">
    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">Membership Reports</h4>
                </div>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('membership_report_date_from', 'Date From') !!}
                    {!! Form::text('membership_report_date_from', null, ['class' => 'form-control datepicker', 'placeholder' => 'YYYY-MM-DD']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('membership_report_date_to', 'Date To') !!}
                    {!! Form::text('membership_report_date_to', null, ['class' => 'form-control datepicker', 'placeholder' => 'YYYY-MM-DD']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('membership_report_region_id', 'Region') !!}
                    {!! Form::select('membership_report_region_id', $regions, null, ['class' => 'form-control select2', 'placeholder' => __('messages.all'), 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('membership_report_status_id', 'Status') !!}
                    {!! Form::select('membership_report_status_id', $statuses, null, ['class' => 'form-control select2', 'placeholder' => __('messages.all'), 'style' => 'width:100%']) !!}
                </div>
            </div>
        </div>
    @endcomponent

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#member_register_report_tab" data-toggle="tab">Member Register</a></li>
            <li><a href="#points_summary_report_tab" data-toggle="tab">Points Summary</a></li>
            <li><a href="#card_issue_report_tab" data-toggle="tab">Card Issue Report</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="member_register_report_tab">
                @include('membership::reports.partials.member_register_table')
            </div>
            <div class="tab-pane" id="points_summary_report_tab">
                @include('membership::reports.partials.points_summary_table')
            </div>
            <div class="tab-pane" id="card_issue_report_tab">
                @include('membership::reports.partials.card_issue_table')
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ Module::asset('membership:js/reports/member_register_report.js') }}"></script>
<script src="{{ Module::asset('membership:js/reports/points_summary_report.js') }}"></script>
<script src="{{ Module::asset('membership:js/reports/card_issue_report.js') }}"></script>
@endsection
