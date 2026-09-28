
@extends('layouts.app')
@section('title', __('realtimeentries::lang.list_settlement'))

@section('content')

@php
    $settings = !empty($settings) ? json_decode($settings->dashboard_settings,true) : [];
@endphp




<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('realtimeentries::lang.real_time_entries')</a></li>
                    <li><span>@lang( 'realtimeentries::lang.real_time_settings')</span></li>
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
                 {!! Form::open(['url' => action('\Modules\RealTimeEntries\Http\Controllers\RealTimeEntriesController@store_settings'), 'method' =>'post', 'id' =>'add_settings_form' ]) !!}
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('created_at', __( 'realtimeentries::lang.date_time' ) . ':*') !!}
                        {!! Form::text('created_at', !empty($settings) ? $settings['created_at'] : @format_datetime(date('Y-m-d H:i')), ['class' =>
                        'form-control', 'required', 'readonly',
                        'placeholder' => __(
                        'realtimeentries::lang.date_time' ) ]) !!}
                        <input type="hidden" name="user_added" value="{{auth()->user()->username}}">
                        <input type="hidden" name="is_admin" value="1">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('show_bulk_pumps', __( 'realtimeentries::lang.show_bulk_pumps' ) . ':*') !!}
                        {!! Form::select('show_bulk_pumps', ['no' => __('messages.no'),'yes' => __('messages.yes')], !empty($settings) ? $settings['show_bulk_pumps'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.please_select' ), 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                
                {{-- <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('credit_sales_direct_to_customer', __( 'realtimeentries::lang.credit_sales_direct_to_customer' ) . ':*') !!}
                        {!! Form::select('credit_sales_direct_to_customer', ['no' => __('messages.no'),'yes' => __('messages.yes')], !empty($settings) ? $settings['credit_sales_direct_to_customer'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.please_select' ), 'style' => 'width: 100%;']) !!}
                    </div>
                </div> --}}
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('meter_sales_compulsory', __( 'realtimeentries::lang.meter_sales_compulsory' ) . ':*') !!}
                        {!! Form::select('meter_sales_compulsory', ['no' => __('messages.no'),'yes' => __('messages.yes')], (!empty($settings) && !empty($settings['meter_sales_compulsory']) ) ? $settings['meter_sales_compulsory'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.please_select' ), 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                
            </div>
            <div class="row">
                
                {{-- <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('logoff_time', __( 'realtimeentries::lang.logoff_time' ) . ':*') !!}
                        {!! Form::text('logoff_time', !empty($settings['logoff_time']) ? $settings['logoff_time'] : '', ['class' =>
                        'form-control', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.logoff_time' ) ]) !!}
                    </div>
                </div> --}}
                {{-- <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('logoff', __( 'realtimeentries::lang.logoff' ) . ':*') !!}
                        {!! Form::text('logoff', !empty($settings['logoff']) ? $settings['logoff'] : '', ['class' =>
                        'form-control', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.logoff' ) ]) !!}
                    </div>
                </div> --}}
                
                 {{-- <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('meter_sales_compulsory', __( 'realtimeentries::lang.meter_sales_compulsory' ) . ':*') !!}
                        {!! Form::select('meter_sales_compulsory', ['no' => __('messages.no'),'yes' => __('messages.yes')], (!empty($settings) && !empty($settings['meter_sales_compulsory']) ) ? $settings['meter_sales_compulsory'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.please_select' ), 'style' => 'width: 100%;']) !!}
                    </div>
                </div> --}}
                 <div class="clearfix"></div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('enter_cash_denominations', __( 'realtimeentries::lang.enter_cash_denominations' ) . ':*') !!}
                        {!! Form::select('enter_cash_denominations', ['no' => __('realtimeentries::lang.bulk_cash_disable'),'yes' => __('realtimeentries::lang.bulk_cash_enable')], (!empty($settings) && !empty($settings['enter_cash_denominations']) ) ? $settings['enter_cash_denominations'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.please_select' ), 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('card_amount_to_enter', __( 'realtimeentries::lang.card_amount_to_enter' ) . ':*') !!}
                        {!! Form::select('card_amount_to_enter', ['bulk' => __('realtimeentries::lang.bulk'),'one_by_one' => __('realtimeentries::lang.one_by_one')], (!empty($settings) && !empty($settings['card_amount_to_enter']) ) ? $settings['card_amount_to_enter'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.please_select' ), 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- <div class="form-group">
                        {!! Form::label('pump_should_not_show_if_shift_is_open', __( 'realtimeentries::lang.pump_should_not_show_if_shift_is_open' ) . ':*') !!}
                        {!! Form::select('pump_should_not_show_if_shift_is_open', ['no' => __('messages.no'),'yes' => __('messages.yes')], (!empty($settings) && !empty($settings['pump_should_not_show_if_shift_is_open']) ) ? $settings['pump_should_not_show_if_shift_is_open'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'realtimeentries::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div> -->
                    <div class="form-group">
                        {!! Form::label('enter_card_numbers', __( 'realtimeentries::lang.enter_card_numbers' ) . ':*') !!}
                        {!! Form::select(
                            'enter_card_numbers',
                            ['no' => __('messages.no'), 'yes' => __('messages.yes')],
                            (!empty($settings) && !empty($settings['enter_card_numbers'])) ? $settings['enter_card_numbers'] : null,
                            [
                                'class' => 'form-control select2',
                                'required',
                                'placeholder' => __( 'realtimeentries::lang.please_select' ),
                                'style' => 'width: 100%;'
                            ]
                        ) !!}
                    </div>

                </div> 
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('real_time_update_customer_ledger', 'Update the customer Ledger with the Real Time Entries:*') !!}
                        {!! Form::select(
                            'real_time_update_customer_ledger',
                            ['no' => __('messages.no'), 'yes' => __('messages.yes')],
                            (!empty($settings) && !empty($settings['real_time_update_customer_ledger'])) ? $settings['real_time_update_customer_ledger'] : 'no',
                            [
                                'class' => 'form-control select2',
                                'required',
                                'placeholder' => __( 'realtimeentries::lang.please_select' ),
                                'style' => 'width: 100%;'
                            ]
                        ) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('real_time_update_account_books', 'Update the Account Books with the Real Time Entries:*') !!}
                        {!! Form::select(
                            'real_time_update_account_books',
                            ['no' => __('messages.no'), 'yes' => __('messages.yes')],
                            (!empty($settings) && !empty($settings['real_time_update_account_books'])) ? $settings['real_time_update_account_books'] : 'no',
                            [
                                'class' => 'form-control select2',
                                'required',
                                'placeholder' => __( 'realtimeentries::lang.please_select' ),
                                'style' => 'width: 100%;'
                            ]
                        ) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('real_time_entry_payments_in_accounts', 'Real Time Entry Payments in Accounts:*') !!}
                        {!! Form::select(
                            'real_time_entry_payments_in_accounts',
                            ['day_entry' => 'Day Entry', 'settlement' => 'Settlement'],
                            (!empty($settings) && !empty($settings['real_time_entry_payments_in_accounts'])) ? $settings['real_time_entry_payments_in_accounts'] : 'day_entry',
                            [
                                'class' => 'form-control select2',
                                'required',
                                'placeholder' => __( 'realtimeentries::lang.please_select' ),
                                'style' => 'width: 100%;'
                            ]
                        ) !!}
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
                                    <th>@lang('realtimeentries::lang.date_time')</th>
                                    {{-- <th>@lang('realtimeentries::lang.credit_sales_direct_to_customer')</th> --}}
                                    <th>@lang('realtimeentries::lang.show_bulk_pumps')</th>
                                    {{-- <th>@lang('realtimeentries::lang.logoff_time')</th>
                                    <th>@lang('realtimeentries::lang.logoff')</th> --}}
                                    <th>@lang('realtimeentries::lang.enter_cash_denominations')</th>
                                     <th>@lang('realtimeentries::lang.user')</th>
                                     <th>Enter Card Numbers</th>
                                     <th>Update customer Ledger</th>
                                     <th>Update Account Books</th>
                                     <th>Real Time Entry Payments in Accounts</th>
                                     <!-- <th>@lang('realtimeentries::lang.pump_should_not_show_if_shift_is_open')</th> -->
                                </tr>
                            </thead>
                            
                            
                            @if(empty($settings))
                                <tr>
                                    <td class="text-center" colspan="9">
                                        @lang('realtimeentries::lang.no_settings_added')
                                    </td>
                                </tr>
                            @endif
                            
                            @if(!empty($settings))
                            
                                <tr>
                                    <td>
                                        {{$settings['created_at']}}
                                    </td>

                                    {{-- <td>
                                        {{$settings['credit_sales_direct_to_customer']}}
                                    </td> --}}
                                    
                                    <td>
                                        {{$settings['show_bulk_pumps']}}
                                    </td>
                                    
                                    
                                    {{-- <td>
                                        {{ !empty($settings['logoff_time']) ?$settings['logoff_time'] : ''}}
                                    </td>
                                    <td>
                                        {{ !empty($settings['logoff_time']) ? $settings['logoff']: ''}}
                                    </td> --}}
                                    
                                     <td>
                                        {{ !empty($settings['enter_cash_denominations']) ? $settings['enter_cash_denominations']: ''}}
                                     </td>
                                    
                                    <td>
                                        {{$settings['user_added']}}
                                    </td>
                                    <td>
    {{ !empty($settings['enter_card_numbers']) && $settings['enter_card_numbers'] == 'yes'
        ? __('messages.yes')
        : __('messages.no') }}
</td>
                                    <td>
                                        {{ !empty($settings['real_time_update_customer_ledger']) && $settings['real_time_update_customer_ledger'] == 'yes' ? __('messages.yes') : __('messages.no') }}
                                    </td>
                                    <td>
                                        {{ !empty($settings['real_time_update_account_books']) && $settings['real_time_update_account_books'] == 'yes' ? __('messages.yes') : __('messages.no') }}
                                    </td>
                                    <td>
                                        {{ (!empty($settings['real_time_entry_payments_in_accounts']) && $settings['real_time_entry_payments_in_accounts'] == 'settlement') ? 'Settlement' : 'Day Entry' }}
                                    </td>

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