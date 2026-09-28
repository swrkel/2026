{{--
    MA-002: extracted from business/manage.blade.php, which was 14,602 lines.

    The lines below are BYTE-IDENTICAL to the original - nothing was rewritten,
    reindented or reordered. The parent file @includes this at exactly the same
    point, so the rendered page is unchanged.

    Only blocks whose own tags balance were moved. Three blocks in the original
    do not close their divs within their own boundaries, so they stay in the
    parent rather than risk moving markup that depends on what surrounds it.
--}}
                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.sales_agent_module')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.sales_agent_module')</label>
                                </div>
                                
                                <div class="col-md-1">
                                    <label></label>
                                    {!! Form::hidden('sales_agent_module', 0) !!}
                                    {!! Form::checkbox('sales_agent_module', 1, !empty($manage_module_enable['sales_agent_module']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select sales_agent_module', 'id' => 'sales_agent_module_checkbox']) !!}
                                </div>
                                
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['sales_agent_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['sales_agent_expiry_date']))
                                        @if(strtotime($module_activation_data['sales_agent_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['sales_agent_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('sales_agent_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['sales_agent_interval']) ?
                                    $module_activation_data['sales_agent_interval'] : 'Years', [
                                                'id' => 'sales_agent_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::number('sales_agent_length', !empty($module_activation_data['sales_agent_length']) ?
                                    $module_activation_data['sales_agent_length'] : 1, ['class' => 'form-control act_length', 'id' => 'sales_agent_length', 'min' => 1]) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('sales_agent_activated_on', !empty($module_activation_data['sales_agent_activated_on']) ?
                                    $module_activation_data['sales_agent_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'sales_agent_activated_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('sales_agent_expiry_date', !empty($module_activation_data['sales_agent_expiry_date']) ?
                                    $module_activation_data['sales_agent_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'sales_agent_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('sales_agent_price', !empty($module_activation_data['sales_agent_price']) ?
                                    $module_activation_data['sales_agent_price'] : null, ['class' => 'form-control', 'id' => 'sales_agent_price']) !!}
                                </div>
                            </div>
                            <div class="row sales_agent_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[sales_agent_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['sales_agent_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['sales_agent_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                            <label>
                                                {!! Form::checkbox('module_permission_location[mf_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['mf_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['mf_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.real_time_entries')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.real_time_entries')</label>
                                </div>
                                
                                <div class="col-md-1">
                                    <label></label>
                                    {!! Form::hidden('real_time_entries', 0) !!}
                                    {!! Form::checkbox('real_time_entries', 1, !empty($manage_module_enable['real_time_entries']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select real_time_entries', 'id' => 'real_time_entries_checkbox']) !!}
                                </div>
                                
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['real_time_entries_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['real_time_entries_expiry_date']))
                                        @if(strtotime($module_activation_data['real_time_entries_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['real_time_entries_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('mf_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['real_time_entries_interval']) ?
                                    $module_activation_data['real_time_entries_interval'] : 'Years', [
                                                'id' => 'mf_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::number('mf_length', !empty($module_activation_data['real_time_entries_length']) ?
                                    $module_activation_data['real_time_entries_length'] : 1, ['class' => 'form-control act_length', 'id' => 'mf_length', 'min' => 1]) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('real_time_entries_activated_on', !empty($module_activation_data['real_time_entries_activated_on']) ?
                                    $module_activation_data['real_time_entries_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'mf_activated_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('real_time_entries_expiry_date', !empty($module_activation_data['real_time_entries_expiry_date']) ?
                                    $module_activation_data['real_time_entries_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'real_time_entries_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('real_time_entries_price', !empty($module_activation_data['real_time_entries_price']) ?
                                    $module_activation_data['real_time_entries_price'] : null, ['class' => 'form-control', 'id' => 'real_time_entries_price']) !!}
                                </div>
                            </div>
                            <div class="row real_time_entries_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[real_time_entries_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['real_time_entries_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['real_time_entries_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>@lang('superadmin::lang.stock_adjustment')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2"><b>Module Name</b></div>
                                <div class="col-md-1"><b>Enable</b></div>
                                <div class="col-md-1"><b>Status</b></div>
                                <div class="col-md-1"><b>Interval</b></div>
                                <div class="col-md-1"><b>Interval Length</b></div>
                                <div class="col-md-2"><b>Activated on</b></div>
                                <div class="col-md-2"><b>Expiry</b></div>
                                <div class="col-md-2 text-center"><h5><b>Module Price</b></h5></div>
                            </div>

                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.stock_adjustment')</label>
                                </div>

                                <div class="col-md-1">
                                    {!! Form::hidden('stock_adjustment', 0) !!}
                                    {!! Form::checkbox('stock_adjustment', 1, !empty($manage_module_enable['stock_adjustment']) ? true : false, [
                                        'class' => 'input-icheck-red ch_select stock_adjustment',
                                        'id' => 'stock_adjustment_checkbox',
                                    ]) !!}
                                    {{-- Default enabled --}}
                                </div>

                                <div class="col-md-1">
                                    @if(empty($module_activation_data['sa_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['sa_expiry_date']))
                                        @if(strtotime($module_activation_data['sa_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['sa_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>

                                <div class="col-md-1">
                                    {!! Form::select('sa_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'],
                                    !empty($module_activation_data['sa_interval']) ? $module_activation_data['sa_interval'] : 'Years', [
                                        'id' => 'sa_interval',
                                        'class' => 'form-control act_interval',
                                    ]) !!}
                                </div>

                                <div class="col-md-1">
                                    {!! Form::number('sa_length',
                                    !empty($module_activation_data['sa_length']) ? $module_activation_data['sa_length'] : 1,
                                    ['class' => 'form-control act_length', 'id' => 'sa_length', 'min' => 1]) !!}
                                </div>

                                <div class="col-md-2">
                                    {!! Form::date('sa_activated_on',
                                    !empty($module_activation_data['sa_activated_on']) ? $module_activation_data['sa_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'),
                                    ['class' => 'form-control act_on', 'id' => 'sa_activated_on']) !!}
                                </div>

                                <div class="col-md-2">
                                    {!! Form::date('sa_expiry_date',
                                    !empty($module_activation_data['sa_expiry_date']) ? $module_activation_data['sa_expiry_date'] : null,
                                    ['class' => 'form-control act_expiry', 'id' => 'sa_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>

                                <div class="col-md-2">
                                    {!! Form::text('sa_price',
                                    !empty($module_activation_data['sa_price']) ? $module_activation_data['sa_price'] : null,
                                    ['class' => 'form-control', 'id' => 'sa_price']) !!}
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-4">
                                    {!! Form::checkbox('do_not_show_delete_button', 1, !empty($manage_module_enable['do_not_show_delete_button']) ?
                                    $manage_module_enable['do_not_show_delete_button'] : null, ['class' => 'input-icheck-red ch_select
                                    accounting_module', 'id'=>'do_not_show_delete_button'])
                                    !!} <label class="search_label">@lang('superadmin::lang.do_not_show_delete_button')</label>
                                </div>
                            </div>
                            <hr>

                            {{-- Locations --}}
                            <div class="row sa_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'):</b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __('role.select_all') }}
                                        </label>
                                    </div>
                                </div>

                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox(
                                                    'module_permission_location[sa_module]['.$location->id.']',
                                                    1,
                                                    !empty($module_permission_locations_value['sa_module']->locations)
                                                        ? array_key_exists($location->id, $module_permission_locations_value['sa_module']->locations)
                                                        : false,
                                                    ['class' => 'input-icheck-red ch_select location_checkbox']
                                                ) !!}
                                                {{ $location->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                        </div>
                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>@lang('superadmin::lang.pump_operator_dashboard')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2"><b>Module Name</b></div>
                                <div class="col-md-1"><b>Enable</b></div>
                                <div class="col-md-1"><b>Status</b></div>
                                <div class="col-md-1"><b>Interval</b></div>
                                <div class="col-md-1"><b>Interval Length</b></div>
                                <div class="col-md-2"><b>Activated on</b></div>
                                <div class="col-md-2"><b>Expiry</b></div>
                                <div class="col-md-2 text-center"><h5><b>Module Price</b></h5></div>
                            </div>

                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.pump_operator_dashboard')</label>
                                </div>

                                <div class="col-md-1">
                                    <label></label>
                                    {!! Form::hidden('pump_operator_module', 0) !!}
                                    {!! Form::checkbox('pump_operator_module', 1, !empty($manage_module_enable['pump_operator_module']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select pump_operator_module', 'id' => 'pump_operator_module_checkbox']) !!}
                                </div>

                                <div class="col-md-1">
                                    @if(empty($module_activation_data['pump_operator_expiry_date']))
                                        <span class="badge badge-danger">not set</span>
                                    @else
                                        @if(strtotime($module_activation_data['pump_operator_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @else
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                    @endif
                                </div>

                                <div class="col-md-1">
                                    {!! Form::select('pump_operator_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'],
                                    !empty($module_activation_data['pump_operator_interval']) ?
                                    $module_activation_data['pump_operator_interval'] : 'Years', [
                                        'id' => 'pump_operator_interval',
                                        'class' => 'form-control act_interval',
                                        'style' => 'width:100%'
                                    ]) !!}
                                </div>

                                <div class="col-md-1">
                                    {!! Form::number('pump_operator_length', !empty($module_activation_data['pump_operator_length']) ?
                                    $module_activation_data['pump_operator_length'] : 1, [
                                        'class' => 'form-control act_length',
                                        'id' => 'pump_operator_length',
                                        'min' => 1
                                    ]) !!}
                                </div>

                                <div class="col-md-2">
                                    {!! Form::date('pump_operator_activated_on', !empty($module_activation_data['pump_operator_activated_on']) ?
                                    $module_activation_data['pump_operator_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'),
                                    ['class' => 'form-control act_on', 'id' => 'pump_operator_activated_on']) !!}
                                </div>

                                <div class="col-md-2">
                                    {!! Form::date('pump_operator_expiry_date', !empty($module_activation_data['pump_operator_expiry_date']) ?
                                    $module_activation_data['pump_operator_expiry_date'] : null,
                                    ['class' => 'form-control act_expiry', 'id' => 'pump_operator_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>

                                <div class="col-md-2">
                                    {!! Form::text('pump_operator_price', !empty($module_activation_data['pump_operator_price']) ?
                                    $module_activation_data['pump_operator_price'] : null, ['class' => 'form-control', 'id' => 'pump_operator_price']) !!}
                                </div>
                            </div>

                            <hr>

                            <div class="row pump_operator_permissions check_group">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __('role.select_all') }}
                                        </label>
                                    </div>
                                </div>

                                <br>

                                @php
                                    $pump_permissions = [
                                        'receive_pump' => 'Receive Pump',
                                        'payments' => 'Payments',
                                        'other_sales' => 'Other Sales',
                                        'list_other_sales' => 'List Other Sales',
                                        'day_entries' => 'Day Entries',
                                        'close_pumps' => 'Close Pumps',
                                        'close_shift' => 'Close Shift',
                                        'payment_summary' => 'Payment Summary',
                                        'enter_current_meter' => 'Enter Current Meter',
                                        'unload_stock' => 'Unload Stock',
                                        'unload_stock_details' => 'Unload Stock Details',
                                        'meters_with_payments' => 'Meters with Payments'
                                    ];
                                @endphp

                                @foreach ($pump_permissions as $key => $label)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox("module_permission[pump_operator_module][$key]", 1,
                                                    !empty($module_permission_value['pump_operator_module']) &&
                                                    array_key_exists($key, $module_permission_value['pump_operator_module']),
                                                    ['class' => 'input-icheck-red ch_select permission_checkbox']
                                                ) !!}
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <hr>

                            <div class="row pump_operator_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'):</b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __('role.select_all') }}
                                        </label>
                                    </div>
                                </div>

                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox("module_permission_location[pump_operator_module][$location->id]", 1,
                                                    !empty($module_permission_locations_value['pump_operator_module']->locations) &&
                                                    array_key_exists($location->id, $module_permission_locations_value['pump_operator_module']->locations),
                                                    ['class' => 'input-icheck-red ch_select location_checkbox']
                                                ) !!}
                                                {{ $location->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.pos2')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.pos2')</label>
                                </div>
                                
                                <div class="col-md-1">
                                    <label></label>
                                    {!! Form::hidden('pos2', 0) !!}
                                    {!! Form::checkbox('pos2', 1, !empty($manage_module_enable['pos2']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select pos2', 'id' => 'pos2_checkbox']) !!}
                                </div>
                                
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['pos2_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['pos2_expiry_date']))
                                        @if(strtotime($module_activation_data['pos2_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['pos2_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('mf_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['pos2_interval']) ?
                                    $module_activation_data['pos2_interval'] : 'Years', [
                                                'id' => 'mf_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::number('mf_length', !empty($module_activation_data['pos2_length']) ?
                                    $module_activation_data['pos2_length'] : 1, ['class' => 'form-control act_length', 'id' => 'mf_length', 'min' => 1]) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('pos2_activated_on', !empty($module_activation_data['pos2_activated_on']) ?
                                    $module_activation_data['pos2_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'mf_activated_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('pos2_expiry_date', !empty($module_activation_data['pos2_expiry_date']) ?
                                    $module_activation_data['pos2_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'pos2_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('pos2_price', !empty($module_activation_data['pos2_price']) ?
                                    $module_activation_data['pos2_price'] : null, ['class' => 'form-control', 'id' => 'pos2_price']) !!}
                                </div>
                            </div>
                            <div class="row pos2_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[pos2_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['pos2_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['pos2_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>


                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.stock_report')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.stock_report')</label>
                                </div>
                                
                                <div class="col-md-1">
                                    <label></label>
                                    {!! Form::hidden('stock_report', 0) !!}
                                    {!! Form::checkbox('stock_report', 1, !empty($manage_module_enable['stock_report']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select stock_Report', 'id' => 'stock_report_checkbox']) !!}
                                </div>
                                
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['stock_report_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['stock_report_expiry_date']))
                                        @if(strtotime($module_activation_data['stock_report_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['stock_report_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('mf_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['stock_report_interval']) ?
                                    $module_activation_data['stock_report_interval'] : 'Years', [
                                                'id' => 'mf_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::number('mf_length', !empty($module_activation_data['stock_report_length']) ?
                                    $module_activation_data['stock_report_length'] : 1, ['class' => 'form-control act_length', 'id' => 'mf_length', 'min' => 1]) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('stock_report_activated_on', !empty($module_activation_data['stock_report_activated_on']) ?
                                    $module_activation_data['stock_report_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'mf_activated_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('stock_report_expiry_date', !empty($module_activation_data['stock_report_expiry_date']) ?
                                    $module_activation_data['stock_report_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'stock_report_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('stock_report_price', !empty($module_activation_data['stock_report_price']) ?
                                    $module_activation_data['stock_report_price'] : null, ['class' => 'form-control', 'id' => 'stock_report_price']) !!}
                                </div>
                            </div>
                            <div class="row stock_report_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[stock_report_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['stock_report_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['stock_report_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.my_auto')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.my_auto')</label>
                                </div>
                                
                                <div class="col-md-1">
                                    <label></label>
                                    {!! Form::hidden('my_auto', 0) !!}
                                    {!! Form::checkbox('my_auto', 1, !empty($manage_module_enable['my_auto']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select my_auto', 'id' => 'my_auto_checkbox']) !!}
                                </div>
                                
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['my_auto_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['my_auto_expiry_date']))
                                        @if(strtotime($module_activation_data['my_auto_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['my_auto_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('mf_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['my_auto_interval']) ?
                                    $module_activation_data['my_auto_interval'] : 'Years', [
                                                'id' => 'mf_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::number('mf_length', !empty($module_activation_data['my_auto_length']) ?
                                    $module_activation_data['my_auto_length'] : 1, ['class' => 'form-control act_length', 'id' => 'mf_length', 'min' => 1]) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('my_auto_activated_on', !empty($module_activation_data['my_auto_activated_on']) ?
                                    $module_activation_data['my_auto_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'mf_activated_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('my_auto_expiry_date', !empty($module_activation_data['my_auto_expiry_date']) ?
                                    $module_activation_data['my_auto_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'my_auto_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('my_auto_price', !empty($module_activation_data['my_auto_price']) ?
                                    $module_activation_data['my_auto_price'] : null, ['class' => 'form-control', 'id' => 'my_auto_price']) !!}
                                </div>
                            </div>
                            <div class="row my_auto_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[my_auto_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['my_auto_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['my_auto_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="search_label">Agent to Login to the System</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::hidden('my_auto_agent_login', 0) !!}
                                    {!! Form::checkbox('my_auto_agent_login', 1, !empty($manage_module_enable['my_auto_agent_login']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.access_account')</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                              <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.access_account')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('access_account', 0) !!}
                                    {!! Form::checkbox('access_account', 1, !empty($manage_module_enable['access_account']) ? true
                                    :
                                    false, ['class' => 'input-icheck-red ch_select accounting_module'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['ac_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['ac_expiry_date']))
                                        @if(strtotime($module_activation_data['ac_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['ac_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('ac_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['ac_interval']) ?
                                    $module_activation_data['ac_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('ac_length', !empty($module_activation_data['ac_length']) ?
                                    $module_activation_data['ac_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('ac_activated_on', !empty($module_activation_data['ac_activated_on']) ?
                                    $module_activation_data['ac_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('ac_expiry_date', !empty($module_activation_data['ac_expiry_date']) ?
                                    $module_activation_data['ac_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('ac_price', !empty($module_activation_data['ac_price']) ?
                                    $module_activation_data['ac_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('fixed_assets', 1, !empty($manage_module_enable['fixed_assets']) ?
                                    $manage_module_enable['fixed_assets'] : null, ['class' => 'input-icheck-red ch_select
                                    accounting_module'])
                                    !!} <label class="search_label">@lang('superadmin::lang.fixed_assets')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('zero_previous_accounting_values', 1, !empty($manage_module_enable['zero_previous_accounting_values']) ?
                                    $manage_module_enable['zero_previous_accounting_values'] : null, ['class' => 'input-icheck-red ch_select
                                    accounting_module'])
                                    !!} <label class="search_label">@lang('superadmin::lang.zero_previous_accounting_values')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::checkbox('acc_no_manually', 1, !empty($manage_module_enable['acc_no_manually']) ?
                                    $manage_module_enable['acc_no_manually'] : null, ['class' => 'input-icheck-red ch_select
                                    accounting_module'])
                                    !!} <label class="search_label">@lang('superadmin::lang.acc_no_manually')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::checkbox('edit_ob', 1, !empty($manage_module_enable['edit_ob']) ?
                                    $manage_module_enable['edit_ob'] : null, ['class' => 'input-icheck-red ch_select
                                    accounting_module'])
                                    !!} <label class="search_label">@lang('superadmin::lang.edit_opening_balance')</label>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('realize_cheque', 1, !empty($manage_module_enable['realize_cheque']) ?
                                    $manage_module_enable['realize_cheque'] : null, ['class' => 'input-icheck-red ch_select
                                    accounting_module'])
                                    !!} <label class="search_label">@lang('account.realize_cheque')</label>
                                </div>
                                
                            </div>
                            <hr>
                            <div class="row accounting_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!!
                                                Form::checkbox('module_permission_location[accounting_module]['.$location->id.']',
                                                1,
                                                !empty($module_permission_locations_value['accounting_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['accounting_module']->locations) : false,
                                                ['class' => 'input-icheck-red ch_select']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row" style="margin-bottom: 20px">
                                @if(!empty($account_nos))
                                <div class="card-body" style="margin-bottom: 10px;background-color: #ffffff;">
                                    <h5 class="mb-0 text-black">
                                        <label class="search_label">@lang('account.account_numbers')</label>
                                        <i class="fa fa-angle-down rotate-icon pull-right"></i>
                                    </h5>
                                    
                                    <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    {!! Form::label('account_type', __( 'account.account_type' ) .":") !!}
                                                </div>
                                            </div>
                                          <div class="col-md-4">  
                                            <div class="form-group">
                                              {!! Form::label('prefix', __( 'lang_v1.prefix' ) .":*") !!}
                                            </div>
                                          </div>
                                        <div class="col-md-4">
                                          <div class="form-group">
                                              {!! Form::label('account_number', __( 'account.account_number' ) .":*") !!}
                                          </div>
                                        </div>
                                            
                                        </div>
                                    
                                    @foreach($account_nos as $acc_no)
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <select name="account_nos[{{$acc_no->id}}][account_type]" class="form-control select2" id="account_type">
                                                        <option>@lang('messages.please_select')</option>
                                                        @foreach($account_types as $account_type)
                                                        <optgroup label="{{$account_type->name}}">
                                                            <option value="{{$account_type->id}}" @if($acc_no->account_type == $account_type->id)
                                                                selected @endif >{{$account_type->name}}</option>
                                                            @foreach($account_type->sub_types as $sub_type)
                                                            <option value="{{$sub_type->id}}" @if($acc_no->account_type == $sub_type->id) selected
                                                                @endif >{{$sub_type->name}}</option>
                                                            @endforeach
                                                        </optgroup>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                          <div class="col-md-4">  
                                            <div class="form-group">
                                              {!! Form::text('account_nos['.$acc_no->id.'][prefix]', $acc_no->prefix, ['class' => 'form-control', 'required','placeholder' => __( 'lang_v1.prefix' ) ]) !!}
                                           </div>
                                          </div>
                                        <div class="col-md-4">
                                          <div class="form-group">
                                              {!! Form::text('account_nos['.$acc_no->id.'][account_number]', $acc_no->account_number, ['class' => 'form-control', 'required','placeholder' => __( 'account.account_number' ) ]) !!}
                                          </div>
                                        </div>
                                            
                                        </div>
                                    @endforeach
                                    
                                </div>
                                @endif
                                <!--Accordion wrapper-->
                                <div class="accordion md-accordion" id="accordionEx1" role="tablist"
                                     aria-multiselectable="true">

                                    <div class="card">
                                        <div class="card-header" role="tab" id="headingTwo1">
                                            <a class="collapsed" data-toggle="collapse" data-parent="#accordionEx1"
                                               href="#collapseTwo1" aria-expanded="false" aria-controls="collapseTwo1">
                                                <h5 class="mb-0 text-black">
                                                    <label class="search_label">@lang('superadmin::lang.manage_accounts')</label>
                                                    <i class="fa fa-angle-down rotate-icon pull-right"></i>
                                                </h5>
                                            </a>
                                        </div>
                                        <div id="collapseTwo1" class="collapse" role="tabpanel" aria-labelledby="headingTwo1"
                                             data-parent="#accordionEx1">
                                            <div class="card-body" style="margin-bottom: 10px;">
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                
                                                @foreach ($accounts as $account)
                                                    <div class="col-md-6">
                                                        <div class="checkbox">
                                                            <label>
                                                                {!!
                                                                Form::checkbox('accounts_enabled['.$account->id.']', 1,
                                                                $account->visible,['class' => 'input-icheck-red ch_select']) !!}
                                                                {{$account->name}}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                </div>
                                                <hr>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                         </div>
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.post_dated_cheque')</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('account.post_dated_cheque')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('post_dated_cheque', 0) !!}
                                    {!! Form::checkbox('post_dated_cheque', 1, !empty($manage_module_enable['post_dated_cheque']) ?
                                            $manage_module_enable['post_dated_cheque'] : null, ['class' => 'input-icheck-red ch_select
                                            accounting_module'])
                                            !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['post_dated_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['post_dated_expiry_date']))
                                        @if(strtotime($module_activation_data['post_dated_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['post_dated_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('post_dated_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['post_dated_interval']) ?
                                    $module_activation_data['post_dated_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('post_dated_length', !empty($module_activation_data['post_dated_length']) ?
                                    $module_activation_data['post_dated_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('post_dated_activated_on', !empty($module_activation_data['post_dated_activated_on']) ?
                                    $module_activation_data['post_dated_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('post_dated_expiry_date', !empty($module_activation_data['post_dated_expiry_date']) ?
                                    $module_activation_data['post_dated_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('post_dated_price', !empty($module_activation_data['post_dated_price']) ?
                                    $module_activation_data['post_dated_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                              
                            <div class="check_group">
                                <div class="row">
                                    <div class="check_group">
                                        <div class="col-md-3">
                                            <label>
                                                <input type="checkbox" class="check_all input-icheck-red">{{ __( 'role.select_all' ) }}
                                            </label>
                                        </div>
                                        
                                        <div class="clearfix"></div>
                                        
                                        <div class="col-md-4">
                                        {!! Form::hidden('add_pd_cheque', 0) !!}
                                            {!! Form::checkbox('add_pd_cheque', 1, !empty($manage_module_enable['add_pd_cheque']) ?
                                            $manage_module_enable['add_pd_cheque'] : null, ['class' => 'input-icheck-red ch_select
                                            accounting_module'])
                                            !!} <label class="search_label">@lang('account.add_pd_cheques')</label>
                                        </div>
                                       
                                        <div class="col-md-4">
                                            {!! Form::checkbox('show_post_dated_cheque', 1, !empty($manage_module_enable['show_post_dated_cheque']) ?
                                            $manage_module_enable['show_post_dated_cheque'] : null, ['class' => 'input-icheck-red ch_select
                                            accounting_module'])
                                            !!} <label class="search_label">@lang('account.show_post_dated_cheque')</label>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            {!! Form::checkbox('update_post_dated_cheque', 1,
                                            !empty($manage_module_enable['update_post_dated_cheque']) ? true : false, ['class' =>
                                            'input-icheck-red
                                            ch_select']) !!}<label
                                                    class="search_label">@lang('superadmin::lang.update_post_dated_cheque')</label>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                           
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('deposits.deposits_module')</h4>
                            <hr>
                          </div>
                          <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('deposits.deposits_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('deposits_module', 0) !!}
                                    {!! Form::checkbox('deposits_module', 1, !empty($manage_module_enable['deposits_module']) ? true
                                    :
                                    false, ['class' => 'input-icheck-red ch_select'])
                                    !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['deposits_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['deposits_expiry_date']))
                                        @if(strtotime($module_activation_data['deposits_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['deposits_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('deposits_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['deposits_interval']) ?
                                    $module_activation_data['deposits_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('deposits_length', !empty($module_activation_data['deposits_length']) ?
                                    $module_activation_data['deposits_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('deposits_activated_on', !empty($module_activation_data['deposits_activated_on']) ?
                                    $module_activation_data['deposits_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('deposits_expiry_date', !empty($module_activation_data['deposits_expiry_date']) ?
                                    $module_activation_data['deposits_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('deposits_price', !empty($module_activation_data['deposits_price']) ?
                                    $module_activation_data['deposits_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                         </div>
                    </div>
