@php
    $settings = json_decode($pump_operator->dashboard_settings,true);
@endphp

<div class="modal-dialog" role="document" style="width: 75%">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            @if(!empty(session()->get('from_admin')))
                <a href="{{action('Auth\PumpOperatorLoginController@logout', ['main_system' => true])}}"
                    class="btn btn-sm pull-right" 
                    style=" background-color: brown; color: #fff; margin-right: 10px; width: 15%; font-size:1.1vw">@lang('petrogeneral::lang.back')</a>
            @endif
    
            <h4 class="modal-title">@lang( 'petrogeneral::lang.pumper_dashboard_settings' )</h4>
        </div>

        <div class="modal-body">
            
            {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorController@store_settings'), 'method' =>'post', 'id' =>'add_settings_form' ]) !!}
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('created_at', __( 'petrogeneral::lang.date_time' ) . ':*') !!}
                        {!! Form::text('created_at', !empty($settings) ? $settings['created_at'] : @format_datetime(date('Y-m-d H:i')), ['class' =>
                        'form-control', 'required', 'readonly',
                        'placeholder' => __(
                        'petrogeneral::lang.date_time' ) ]); !!}
                    </div>
                </div>
                
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('user_added', __( 'petrogeneral::lang.user' ) . ':*') !!}
                        
                        {!! Form::text('user_added', !empty($settings) ? $settings['user_added'] : $pump_operator->name, ['class' =>
                        'form-control', 'required', 'readonly',
                        'placeholder' => __(
                        'petrogeneral::lang.user_added' ) ]); !!}
                    </div>
                </div>
                
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('show_bulk_pumps', __( 'petrogeneral::lang.show_bulk_pumps' ) . ':*') !!}
                        {!! Form::select('show_bulk_pumps', ['no' => __('messages.no'),'yes' => __('messages.yes')], !empty($settings) ? $settings['show_bulk_pumps'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('credit_sales_direct_to_customer', __( 'petrogeneral::lang.credit_sales_direct_to_customer' ) . ':*') !!}
                        {!! Form::select('credit_sales_direct_to_customer', ['no' => __('messages.no'),'yes' => __('messages.yes')], !empty($settings) ? $settings['credit_sales_direct_to_customer'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('logoff_time', __( 'petrogeneral::lang.logoff_time' ) . ':*') !!}
                        {!! Form::text('logoff_time', !empty($settings['logoff_time']) ? $settings['logoff_time'] : '', ['class' =>
                        'form-control', 'required',
                        'placeholder' => __(
                        'petrogeneral::lang.logoff_time' ) ]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('logoff', __( 'petrogeneral::lang.logoff' ) . ':*') !!}
                        {!! Form::text('logoff', !empty($settings['logoff']) ? $settings['logoff'] : '', ['class' =>
                        'form-control', 'required',
                        'placeholder' => __(
                        'petrogeneral::lang.logoff' ) ]); !!}
                    </div>
                </div>
                
                <div class="clearfix"></div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('meter_sales_compulsory', __( 'petrogeneral::lang.meter_sales_compulsory' ) . ':*') !!}
                        {!! Form::select('meter_sales_compulsory', ['no' => __('messages.no'),'yes' => __('messages.yes')], (!empty($settings) && !empty($settings['meter_sales_compulsory']) ) ? $settings['meter_sales_compulsory'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('enter_cash_denominations', __( 'petrogeneral::lang.enter_cash_denominations' ) . ':*') !!}
                        {!! Form::select('enter_cash_denominations', ['no' => __('petrogeneral::lang.bulk_cash_disable'),'yes' => __('petrogeneral::lang.bulk_cash_enable')], (!empty($settings) && !empty($settings['enter_cash_denominations']) ) ? $settings['enter_cash_denominations'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                 <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('card_amount_to_enter', __( 'petrogeneral::lang.card_amount_to_enter' ) . ':*') !!}
                        {!! Form::select('card_amount_to_enter', ['bulk' => __('petrogeneral::lang.bulk'),'one_by_one' => __('petrogeneral::lang.one_by_one')], (!empty($settings) && !empty($settings['card_amount_to_enter']) ) ? $settings['card_amount_to_enter'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                <br><br><button type="submit" class="btn btn-primary" style="float: right; margin-right:50px; margin-bottom:10px">@lang( 'messages.save' )</button> 
                
            </div>
        {!! Form::close() !!}
            
            <div class="row">
                <div class="col-sm-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('petrogeneral::lang.date_time')</th>
                                    <th>@lang('petrogeneral::lang.credit_sales_direct_to_customer')</th>
                                    <th>@lang('petrogeneral::lang.show_bulk_pumps')</th>
                                    <th>@lang('petrogeneral::lang.logoff_time')</th>
                                    <th>@lang('petrogeneral::lang.logoff')</th>
                                    <th>@lang('petrogeneral::lang.enter_cash_denominations')</th>
                                    <th>@lang('petrogeneral::lang.user')</th>
                                </tr>
                            </thead>
                            
                            
                            @if(empty($settings))
                                <tr>
                                    <td class="text-center" colspan="7">
                                        @lang('petrogeneral::lang.no_settings_added')
                                    </td>
                                </tr>
                            @endif
                            
                            @if(!empty($settings))
                            
                                <tr>
                                    <td>
                                        {{$settings['created_at']}}
                                    </td>
                                    
                                    <td>
                                        {{$settings['credit_sales_direct_to_customer']}}
                                    </td>
                                    
                                    <td>
                                        {{$settings['show_bulk_pumps']}}
                                    </td>
                                    
                                    <td>
                                        {{ !empty($settings['logoff_time']) ?$settings['logoff_time'] : ''}}
                                    </td>
                                    <td>
                                        {{ !empty($settings['logoff_time']) ? $settings['logoff']: ''}}
                                    </td>
                                    
                                    <td>
                                        {{ !empty($settings['enter_cash_denominations']) ? $settings['enter_cash_denominations']: ''}}
                                    </td>
                                    
                                    <td>
                                        {{$settings['user_added']}}
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>



    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
$(".select2").select2();
</script>