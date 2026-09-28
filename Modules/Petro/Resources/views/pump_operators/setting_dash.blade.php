
@extends('layouts.app')
@section('title', __('petro::lang.list_settlement'))

@section('content')

@php
    // CODEX FIX: Merge defaults to prevent undefined array key errors
    $defaults = [
        'credit_sales_direct_to_customer' => 'no',
        'show_bulk_pumps' => 'no',
        'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i'),
        'user_added' => '',
        'logoff_time' => '',
        'logoff' => '',
        'meter_sales_compulsory' => 'no',
        'enter_cash_denominations' => 'no',
        'card_amount_to_enter' => 'bulk',
        'enter_card_numbers' => 'no',
        'bill_prefix' => '',
        'starting_bill_number' => '',
    ];
    $settings = !empty($settings) ? json_decode($settings->dashboard_settings, true) : [];
    $settings = is_array($settings) ? array_merge($defaults, $settings) : $defaults;
@endphp




<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petro::lang.petro')</a></li>
                    <li><span>@lang( 'petro::lang.dashboard_settings')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner">
    @if(!empty($message)) {!! $message !!} @endif
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                 {!! Form::open(['url' => action('\Modules\Petro\Http\Controllers\PumpOperatorController@store_settings'), 'method' =>'post', 'id' =>'add_settings_form' ]) !!}
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('created_at', __( 'petro::lang.date_time' ) . ':*') !!}
                        {!! Form::text('created_at', data_get($settings, 'created_at', \Carbon\Carbon::now()->format('Y-m-d H:i')), ['class' =>
                        'form-control', 'required', 'readonly',
                        'placeholder' => __(
                        'petro::lang.date_time' ) ]); !!}
                        <input type="hidden" name="user_added" value="{{auth()->user()->username}}">
                        <input type="hidden" name="is_admin" value="1">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('show_bulk_pumps', __( 'petro::lang.show_bulk_pumps' ) . ':*') !!}
                        {!! Form::select('show_bulk_pumps', ['no' => __('messages.no'),'yes' => __('messages.yes')], data_get($settings, 'show_bulk_pumps', 'no'), ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('credit_sales_direct_to_customer', __( 'petro::lang.credit_sales_direct_to_customer' ) . ':*') !!}
                        {{-- CODEX FIX: Use data_get with default 'no' to prevent undefined array key error --}}
                        {!! Form::select('credit_sales_direct_to_customer', ['no' => __('messages.no'),'yes' => __('messages.yes')], data_get($settings, 'credit_sales_direct_to_customer', 'no'), ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
            </div>
            <div class="row">
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('logoff_time', __( 'petro::lang.logoff_time' ) . ':*') !!}
                        {!! Form::text('logoff_time', !empty($settings['logoff_time']) ? $settings['logoff_time'] : '', ['class' =>
                        'form-control', 'required',
                        'placeholder' => __(
                        'petro::lang.logoff_time' ) ]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('logoff', __( 'petro::lang.logoff' ) . ':*') !!}
                        {!! Form::text('logoff', !empty($settings['logoff']) ? $settings['logoff'] : '', ['class' =>
                        'form-control', 'required',
                        'placeholder' => __(
                        'petro::lang.logoff' ) ]); !!}
                    </div>
                </div>
                
                 <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('meter_sales_compulsory', __( 'petro::lang.meter_sales_compulsory' ) . ':*') !!}
                        {!! Form::select('meter_sales_compulsory', ['no' => __('messages.no'),'yes' => __('messages.yes')], (!empty($settings) && !empty($settings['meter_sales_compulsory']) ) ? $settings['meter_sales_compulsory'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                 <div class="clearfix"></div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('enter_cash_denominations', __( 'petro::lang.enter_cash_denominations' ) . ':*') !!}
                        {!! Form::select('enter_cash_denominations', ['no' => __('petro::lang.bulk_cash_disable'),'yes' => __('petro::lang.bulk_cash_enable')], (!empty($settings) && !empty($settings['enter_cash_denominations']) ) ? $settings['enter_cash_denominations'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('card_amount_to_enter', __( 'petro::lang.card_amount_to_enter' ) . ':*') !!}
                        {!! Form::select('card_amount_to_enter', ['bulk' => __('petro::lang.bulk'),'one_by_one' => __('petro::lang.one_by_one')], (!empty($settings) && !empty($settings['card_amount_to_enter']) ) ? $settings['card_amount_to_enter'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- <div class="form-group">
                        {!! Form::label('pump_should_not_show_if_shift_is_open', __( 'petro::lang.pump_should_not_show_if_shift_is_open' ) . ':*') !!}
                        {!! Form::select('pump_should_not_show_if_shift_is_open', ['no' => __('messages.no'),'yes' => __('messages.yes')], (!empty($settings) && !empty($settings['pump_should_not_show_if_shift_is_open']) ) ? $settings['pump_should_not_show_if_shift_is_open'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div> -->
                    <div class="form-group">
                        {!! Form::label('enter_card_numbers', __( 'petro::lang.enter_card_numbers' ) . ':*') !!}
                        {!! Form::select(
                            'enter_card_numbers',
                            ['no' => __('messages.no'), 'yes' => __('messages.yes')],
                            (!empty($settings) && !empty($settings['enter_card_numbers'])) ? $settings['enter_card_numbers'] : null,
                            [
                                'class' => 'form-control select2',
                                'required',
                                'placeholder' => __( 'petro::lang.please_select' ),
                                'style' => 'width: 100%;'
                            ]
                        ) !!}
                    </div>

                    <div class="form-group">
                        {!! Form::label('bill_prefix', 'Bill Prefix:') !!}
                        {!! Form::text('bill_prefix', !empty($settings['bill_prefix']) ? $settings['bill_prefix'] : null, [
                            'class' => 'form-control',
                            'placeholder' => 'Bill Prefix'
                        ]) !!}
                    </div>

                    <div class="form-group">
                        {!! Form::label('starting_bill_number', 'Starting Bill Number:') !!}
                        {!! Form::number('starting_bill_number', !empty($settings['starting_bill_number']) ? $settings['starting_bill_number'] : null, [
                            'class' => 'form-control',
                            'placeholder' => 'Starting Bill Number'
                        ]) !!}
                    </div>

                </div> 
                <br><br><button type="submit" class="btn btn-primary" style="float: right; margin-right:50px; margin-bottom:10px">@lang( 'messages.save' )</button> 
                
            </div>
        {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
    
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('petro::lang.date_time')</th>
                                    <th>@lang('petro::lang.credit_sales_direct_to_customer')</th>
                                    <th>@lang('petro::lang.show_bulk_pumps')</th>
                                    <th>@lang('petro::lang.logoff_time')</th>
                                    <th>@lang('petro::lang.logoff')</th>
                                    <th>@lang('petro::lang.enter_cash_denominations')</th>
                                     <th>@lang('petro::lang.user')</th>
                                     <th>@lang('petro::lang.enter_card_numbers')</th>
                                     <th>Bill Prefix</th>
                                     <th>Starting Bill Number</th>

                                     <!-- <th>@lang('petro::lang.pump_should_not_show_if_shift_is_open')</th> -->
                                </tr>
                            </thead>
                            
                            
                            @if(empty($settings))
                                <tr>
                                    <td class="text-center" colspan="9">
                                        @lang('petro::lang.no_settings_added')
                                    </td>
                                </tr>
                            @endif
                            
                            @if(!empty($settings))
                            
                                {{-- CODEX FIX: Use data_get for all table display values to prevent undefined key errors --}}
                                <tr>
                                    <td>
                                        {{ data_get($settings, 'created_at', '') }}
                                    </td>

                                    <td>
                                        {{ data_get($settings, 'credit_sales_direct_to_customer', 'no') }}
                                    </td>
                                    
                                    <td>
                                        {{ data_get($settings, 'show_bulk_pumps', 'no') }}
                                    </td>
                                    
                                    
                                    <td>
                                        {{ data_get($settings, 'logoff_time', '') }}
                                    </td>
                                    <td>
                                        {{ data_get($settings, 'logoff', '') }}
                                    </td>
                                    
                                     <td>
                                        {{ data_get($settings, 'enter_cash_denominations', 'no') }}
                                    </td>
                                    
                                    <td>
                                        {{ data_get($settings, 'user_added', '') }}
                                    </td>
                                    <td>
    {{ !empty($settings['enter_card_numbers']) && $settings['enter_card_numbers'] == 'yes'
        ? __('messages.yes')
        : __('messages.no') }}
</td>
                                    <td>{{ data_get($settings, 'bill_prefix', '') }}</td>
                                    <td>{{ data_get($settings, 'starting_bill_number', '') }}</td>

                                    <!-- <td>
                                        {{ !empty($settings['pump_should_not_show_if_shift_is_open']) ? $settings['pump_should_not_show_if_shift_is_open'] : '' }}
                                    </td> -->
                                </tr>
                            @endif
                        </table>
    </div>
    @endcomponent
</section>
<!-- /.content -->

@endsection
@section('javascript')
<script type="text/javascript">
    $(".select2").select2();
</script>
@endsection