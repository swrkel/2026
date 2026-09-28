@extends('layouts.app')
@section('title', __('distributionnew::lang.notification_preferences'))
@section('content')
<section class="content-header"><h1>{{ __('distributionnew::lang.notification_preferences') }}</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            {!! Form::open(['route' => 'distribution-new.settings.notification-preferences.store']) !!}
            <div class="row">
                <div class="col-md-3">{!! Form::label('event_key', __('distributionnew::lang.event_key')) !!}{!! Form::text('event_key', null, ['class'=>'form-control', 'required']) !!}</div>
                <div class="col-md-3">{!! Form::label('officer_group_key', __('distributionnew::lang.officer_group')) !!}{!! Form::text('officer_group_key', null, ['class'=>'form-control']) !!}</div>
                <div class="col-md-2"><label>{!! Form::checkbox('sms_enabled', 1, true) !!} SMS</label></div>
                <div class="col-md-2"><label>{!! Form::checkbox('in_app_enabled', 1, true) !!} In App</label></div>
                <div class="col-md-2"><button class="btn btn-primary btn-block" style="margin-top:24px;">{{ __('messages.save') }}</button></div>
            </div>
            {!! Form::close() !!}
            <hr>
            <table class="table table-bordered table-striped"><thead><tr><th>Event</th><th>Officer Group</th><th>SMS</th><th>In App</th></tr></thead><tbody>
            @foreach($preferences as $preference)<tr><td>{{ $preference->event_key }}</td><td>{{ $preference->officer_group_key }}</td><td>{{ $preference->sms_enabled ? 'Yes' : 'No' }}</td><td>{{ $preference->in_app_enabled ? 'Yes' : 'No' }}</td></tr>@endforeach
            </tbody></table>
        </div>
    </div>
</section>
@endsection
