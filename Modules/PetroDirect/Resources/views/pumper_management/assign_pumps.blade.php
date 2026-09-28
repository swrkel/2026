@extends('layouts.app')
@section('title', __('petrodirect::lang.assign_pumps'))
@section('content')
<section class="content-header"><h1>@lang('petrodirect::lang.assign_pumps')</h1></section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['route' => 'petrodirect.pumper-management.assign-pumps', 'method' => 'post']) !!}
        <div class="row">
            <div class="col-md-6"><div class="form-group">{!! Form::label('pump_operator_id', __('petrodirect::lang.pump_operator') . ':*') !!}{!! Form::select('pump_operator_id', $operators, request('pump_operator_id'), ['class' => 'form-control select2', 'required', 'placeholder' => __('petrodirect::lang.please_select'), 'style' => 'width:100%;']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('pump_id', __('petrodirect::lang.pump') . ':*') !!}{!! Form::select('pump_id', $pumps, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('petrodirect::lang.please_select'), 'style' => 'width:100%;']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('shift_number', __('petrodirect::lang.shift_no')) !!}{!! Form::text('shift_number', null, ['class' => 'form-control']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('assigned_date', __('petrodirect::lang.date')) !!}{!! Form::date('assigned_date', date('Y-m-d'), ['class' => 'form-control']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('status', __('petrodirect::lang.status')) !!}{!! Form::select('status', ['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'], 'open', ['class' => 'form-control select2', 'style' => 'width:100%;']) !!}</div></div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <a href="{{ route('petrodirect.pumper-management.index', ['tab' => 'assignments']) }}" class="btn btn-default">@lang('messages.close')</a>
        </div>
        {!! Form::close() !!}
    @endcomponent
</section>
@stop
@section('javascript')<script>$(function(){ if($.fn.select2){ $('.select2').select2(); } });</script>@stop
