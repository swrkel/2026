@php
    $settings = json_decode($pump_operator->dashboard_settings, true);
    if (!is_array($settings)) {
        $settings = [];
    }

    $settingDefaults = [
        'created_at' => @format_datetime(date('Y-m-d H:i')),
        'user_added' => $pump_operator->name ?? '',
        'credit_sales_direct_to_customer' => 'no',
        'show_bulk_pumps' => 'no',
        'meter_sales_compulsory' => 'no',
        'enter_cash_denominations' => 'no',
        'enter_card_numbers' => 'no',
        'card_amount_to_enter' => 'bulk',
        'logoff_time' => '',
        'logoff' => 'no',
        'bill_prefix' => '',
        'starting_bill_number' => '',
        'pumper_ledger_update' => 'no',
    ];

    foreach (['credit_sales_direct_to_customer','show_bulk_pumps','meter_sales_compulsory','enter_cash_denominations','enter_card_numbers','logoff','pumper_ledger_update'] as $field) {
        if (array_key_exists($field, $settings)) {
            $value = strtolower((string) $settings[$field]);
            $settings[$field] = in_array($value, ['yes', '1', 'true', 'on'], true) ? 'yes' : 'no';
        }
    }

    if (($settings['card_amount_to_enter'] ?? '') === 'individual') {
        $settings['card_amount_to_enter'] = 'one_by_one';
    }

    $settings = array_merge($settingDefaults, $settings);
@endphp

<div class="modal-dialog" role="document" style="width: 75%">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            @if(!empty(session()->get('from_admin')))
                <a href="{{action('Auth\PumpOperatorLoginController@logout', ['main_system' => true])}}"
                    class="btn btn-sm pull-right" 
                    style=" background-color: brown; color: #fff; margin-right: 10px; width: 15%; font-size:1.1vw">@lang('petropd::lang.back')</a>
            @endif
    
            <h4 class="modal-title">@lang( 'petropd::lang.pumper_dashboard_settings' )</h4>
        </div>

        <div class="modal-body">
            
            {!! Form::open(['url' => action('\Modules\PetroPD\Http\Controllers\PDOperatorController@store_settings@store_settings'), 'method' =>'post', 'id' =>'add_settings_form' ]) !!}
            <input type="hidden" name="is_admin" value="1">
            <input type="hidden" name="apply_to_all_operators" value="1">
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('created_at', __( 'petropd::lang.date_time' ) . ':*') !!}
                        {!! Form::text('created_at', !empty($settings) ? $settings['created_at'] : @format_datetime(date('Y-m-d H:i')), ['class' =>
                        'form-control', 'required', 'readonly',
                        'placeholder' => __(
                        'petropd::lang.date_time' ) ]); !!}
                    </div>
                </div>
                
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('user_added', __( 'petropd::lang.user' ) . ':*') !!}
                        
                        {!! Form::text('user_added', !empty($settings) ? $settings['user_added'] : $pump_operator->name, ['class' =>
                        'form-control', 'required', 'readonly',
                        'placeholder' => __(
                        'petropd::lang.user_added' ) ]); !!}
                    </div>
                </div>
                
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('show_bulk_pumps', __( 'petropd::lang.show_bulk_pumps' ) . ':*') !!}
                        {!! Form::select('show_bulk_pumps', ['no' => __('messages.no'),'yes' => __('messages.yes')], !empty($settings) ? $settings['show_bulk_pumps'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('credit_sales_direct_to_customer', __( 'petropd::lang.credit_sales_direct_to_customer' ) . ':*') !!}
                        {!! Form::select('credit_sales_direct_to_customer', ['no' => __('messages.no'),'yes' => __('messages.yes')], !empty($settings) ? $settings['credit_sales_direct_to_customer'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {{-- IS2010: a duration in minutes, not a clock time. See setting_dash.blade.php. --}}
                        {!! Form::label('logoff_time', __( 'petropd::lang.logoff_time' ) . ' (minutes of inactivity):*') !!}
                        @php
                            $logoff_raw = (string) ($settings['logoff_time'] ?? '');
                            $logoff_minutes = '';
                            if ($logoff_raw !== '') {
                                if (strpos($logoff_raw, ':') !== false) {
                                    [$lh, $lm] = array_pad(explode(':', $logoff_raw), 2, 0);
                                    $logoff_minutes = ((int) $lh * 60) + (int) $lm;
                                } else {
                                    $logoff_minutes = (int) $logoff_raw;
                                }
                                if ((int) $logoff_minutes <= 0) {
                                    $logoff_minutes = '';
                                }
                            }
                        @endphp
                        {!! Form::input('number', 'logoff_time', $logoff_minutes, ['class' => 'form-control', 'required', 'min' => '1', 'step' => '1', 'inputmode' => 'numeric', 'placeholder' => 'e.g. 30']); !!}
                        <small class="text-muted">Log the pumper out after this many minutes with no activity.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('logoff', __( 'petropd::lang.logoff' ) . ':*') !!}
                        {!! Form::select('logoff', ['no' => __('messages.no'), 'yes' => __('messages.yes')], !empty($settings['logoff']) ? $settings['logoff'] : 'no', ['class' => 'form-control select2', 'required', 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                <div class="clearfix"></div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('meter_sales_compulsory', __( 'petropd::lang.meter_sales_compulsory' ) . ':*') !!}
                        {!! Form::select('meter_sales_compulsory', ['no' => __('messages.no'),'yes' => __('messages.yes')], (!empty($settings) && !empty($settings['meter_sales_compulsory']) ) ? $settings['meter_sales_compulsory'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('enter_cash_denominations', __( 'petropd::lang.enter_cash_denominations' ) . ':*') !!}
                        {!! Form::select('enter_cash_denominations', ['no' => __('petropd::lang.bulk_cash_disable'),'yes' => __('petropd::lang.bulk_cash_enable')], (!empty($settings) && !empty($settings['enter_cash_denominations']) ) ? $settings['enter_cash_denominations'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                 <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('card_amount_to_enter', __( 'petropd::lang.card_amount_to_enter' ) . ':*') !!}
                        {!! Form::select('card_amount_to_enter', ['bulk' => __('petropd::lang.bulk'),'one_by_one' => __('petropd::lang.one_by_one')], (!empty($settings) && !empty($settings['card_amount_to_enter']) ) ? $settings['card_amount_to_enter'] : null , ['class' => 'form-control select2', 'required',
                        'placeholder' => __(
                        'petropd::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('pumper_ledger_update', 'Update the customer Ledger with the Pumper Dashboard:*') !!}
                        {!! Form::select(
                            'pumper_ledger_update',
                            ['no' => __('messages.no'), 'yes' => __('messages.yes')],
                            (!empty($settings) && !empty($settings['pumper_ledger_update'])) ? $settings['pumper_ledger_update'] : 'no',
                            [
                                'class' => 'form-control select2',
                                'required',
                                'placeholder' => __( 'petropd::lang.please_select' ),
                                'style' => 'width: 100%;'
                            ]
                        ) !!}
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
                                    <th>@lang('petropd::lang.date_time')</th>
                                    <th>@lang('petropd::lang.credit_sales_direct_to_customer')</th>
                                    <th>@lang('petropd::lang.show_bulk_pumps')</th>
                                    <th>@lang('petropd::lang.logoff_time')</th>
                                    <th>@lang('petropd::lang.logoff')</th>
                                    <th>@lang('petropd::lang.enter_cash_denominations')</th>
                                    <th>@lang('petropd::lang.user')</th>
                                    <th>Update customer Ledger with Pumper Dashboard</th>
                                </tr>
                            </thead>
                            
                            
                            @if(empty($settings))
                                <tr>
                                    <td class="text-center" colspan="8">
                                        @lang('petropd::lang.no_settings_added')
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
                                    <td>
                                        {{ !empty($settings['pumper_ledger_update']) && $settings['pumper_ledger_update'] == 'yes'
                                            ? __('messages.yes')
                                            : __('messages.no') }}
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