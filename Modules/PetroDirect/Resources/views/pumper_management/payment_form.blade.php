@extends('layouts.app')
@section('title', $payment_type == 'excess' ? __('petrodirect::lang.pay_excess_commission') : __('petrodirect::lang.recover_shortage'))
@section('content')
<section class="content-header"><h1>{{ $payment_type == 'excess' ? __('petrodirect::lang.pay_excess_commission') : __('petrodirect::lang.recover_shortage') }}</h1></section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['route' => $payment_type == 'excess' ? 'petrodirect.pumper-management.pay-excess' : 'petrodirect.pumper-management.recover-shortage', 'method' => 'post']) !!}
        <div class="row">
            <div class="col-md-6"><div class="form-group">{!! Form::label('pump_operator_id', __('petrodirect::lang.pump_operator') . ':*') !!}{!! Form::select('pump_operator_id', $operators, request('pump_operator_id'), ['class' => 'form-control select2', 'required', 'placeholder' => __('petrodirect::lang.please_select'), 'style' => 'width:100%;']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('payment_amount', __('petrodirect::lang.amount') . ':*') !!}{!! Form::text('payment_amount', null, ['class' => 'form-control input_number', 'required']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('date_and_time', __('petrodirect::lang.date_time')) !!}{!! Form::input('datetime-local', 'date_and_time', date('Y-m-d\TH:i'), ['class' => 'form-control']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('collection_form_no', __('petrodirect::lang.collection_form_no')) !!}{!! Form::text('collection_form_no', null, ['class' => 'form-control']) !!}</div></div>
            <div class="col-md-12"><div class="form-group">{!! Form::label('note', __('petrodirect::lang.note')) !!}{!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3]) !!}</div></div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <a href="{{ route('petrodirect.pumper-management.index', ['tab' => $payment_type]) }}" class="btn btn-default">@lang('messages.close')</a>
        </div>
        {!! Form::close() !!}
    @endcomponent
</section>
@stop
@section('javascript')<script>$(function(){ if($.fn.select2){ $('.select2').select2(); } });</script>@stop
