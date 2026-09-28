                <div class="row check_group">
                    <div class="col-md-1">
                        {{--
                         | S678-RETIRE: this section is no longer Petro's.
                         |
                         | Its permissions are enforced by PetroPD, PetroGeneral,
                         | PetroDirect, PumperDashboard, EVCharging, DailyCollectionSW
                         | and core, so it now shows for any business running any fuel
                         | module. The heading follows the ownership.
                         |
                         | role.petro is kept as the fallback so a deployment whose
                         | language files have not been updated still shows the old
                         | label rather than a blank heading.
                        --}}
                        <h4><label>{{ __('role.fuel_operations') === 'role.fuel_operations' ? __('role.petro') : __('role.fuel_operations') }}</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">
                            <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'daily_shortage.edit', in_array('daily_shortage.edit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.daily_shortage.edit' ) }}
                                </label>
                            </div>
                        </div>
                        
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'daily_card.edit', in_array('daily_card.edit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.daily_card.edit' ) }}
                                </label>
                            </div>
                        </div>
                        
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'daily_collection.edit', in_array('daily_collection.edit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.daily_collection.edit' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_sms_notifications', in_array('petro_sms_notifications',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.petro_sms_notifications' ) }}
                                </label>
                            </div>
                        </div>
                        
                         <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'add_day_end_settlement', in_array('add_day_end_settlement',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.day_end_settlement' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_day_end_settlement', in_array('edit_day_end_settlement',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.edit_day_end_settlement' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'bulk_assign_pumps', in_array('bulk_assign_pumps',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.assign_pumps' ) }}
                                </label>
                            </div>
                        </div>
                        
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro.access', in_array('petro.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.petro.access' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'fuel_tank.edit', in_array('fuel_tank.edit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.fuel_tank.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'meter_resetting_tab', in_array('meter_resetting_tab',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.meter_resetting_tab' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'add_dip_resetting', in_array('add_dip_resetting',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.add_dip_resetting' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'dipmanagement.edit', in_array('dipmanagement.edit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.dipmanagement_edit' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'dipmanagement.delete', in_array('dipmanagement.delete',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.dipmanagement_delete' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'dipmanagement.add_dip_chart', in_array('dipmanagement.add_dip_chart',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.dipmanagement_add_dip_chart' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'dipmanagement.edit_dip_chart', in_array('dipmanagement.edit_dip_chart',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.dipmanagement_edit_dip_chart' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'dipmanagement.delete_dip_chart', in_array('dipmanagement.delete_dip_chart',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.dipmanagement_delete_dip_chart' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_other_income_prices', in_array('edit_other_income_prices',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.edit_other_income_prices' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'daily_collection.delete', in_array('daily_collection.delete',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.daily_collection.delete' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_pumper_opening_balance', in_array('edit_pumper_opening_balance',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.edit_pumper_opening_balance' ) }}
                                </label>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <hr class="blue-hr">

                @if(!empty($get_permissions['settlement']) && $get_permissions['settlement'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'petro::lang.settlement' )</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">

                            <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}

                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'settlement.edit', in_array('settlement.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.edit_settlement' ) }}
                                </label>
                            </div>
                        </div>
                       @if(!empty($get_permissions['delete_settlement']) && $get_permissions['delete_settlement'])
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'settlement.delete', in_array('settlement.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.delete_settlement' ) }}
                                </label>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'reset_dip', in_array('reset_dip', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.reset_dip' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'manual_discount', in_array('manual_discount', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.manual_discount' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['pump_operator_dashboard']) && $get_permissions['pump_operator_dashboard'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang('lang_v1.pump_operator_dashboard')</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">
                            <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.dashboard', in_array('pumper_dashboard.dashboard', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.dashboard')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.receive_pump', in_array('pumper_dashboard.receive_pump', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.receive_pump')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.payments', in_array('pumper_dashboard.payments', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.payments')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.other_sales', in_array('pumper_dashboard.other_sales', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.other_sales')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.list_other_sales', in_array('pumper_dashboard.list_other_sales', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.list_other_sales')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.day_entries', in_array('pumper_dashboard.day_entries', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.day_entries')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.close_pump', in_array('pumper_dashboard.close_pump', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.close_pump')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.close_shift', in_array('pumper_dashboard.close_shift', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.close_shift')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.payment_summary', in_array('pumper_dashboard.payment_summary', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.payment_summary')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.current_meter', in_array('pumper_dashboard.current_meter', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.enter_current_meter')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.unload_stock', in_array('pumper_dashboard.unload_stock', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.unload_stock')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.unload_stock_details', in_array('pumper_dashboard.unload_stock_details', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.unload_stock_details')</label></div></div>
                        <div class="col-md-12"><div class="checkbox"><label>{!! Form::checkbox('permissions[]', 'pumper_dashboard.meters_with_payments', in_array('pumper_dashboard.meters_with_payments', $role_permissions), [ 'class' => 'input-icheck']) !!} @lang('petro::lang.meters_with_payments')</label></div></div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['pump_operator']) && $get_permissions['pump_operator'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'petro::lang.pump_operator' )</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">

                            <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}

                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'pum_operator.active_inactive',
                                    in_array('pum_operator.active_inactive', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.pum_operator.active_inactive' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'pump_operator.dashboard', in_array('pump_operator.dashboard',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.pump_operator.dashboard' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'pumper_dashboard_settings', in_array('pumper_dashboard_settings',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.pumper_dashboard_settings' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'pump_operator.main_system', in_array('pump_operator.main_system',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.main_system' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'pump_operator.access_code', in_array('pump_operator.access_code',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.pump_operator.access_code' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['daily_pump_status']) && $get_permissions['daily_pump_status'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'petro::lang.daily_pump_status' )</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">

                            <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}

                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'daily_pump_status.edit', in_array('daily_pump_status.edit',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'daily_pump_status.delete', in_array('daily_pump_status.delete',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif
