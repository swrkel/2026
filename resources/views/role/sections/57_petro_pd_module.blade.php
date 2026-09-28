                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Petro PD</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd.access', in_array('petro_pd.access', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Petro PD Access
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.view_operators', in_array('petro_pd.view_operators', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} View Pump Operators
                                </label>
                            </div>
                        </div>
                        {{--
                         | S678: Assign Pumps.
                         |
                         | The Assign button on Daily Pump Status is gated on
                         | bulk_assign_pumps, and so are the pump-assignment routes
                         | (EnsurePetroPDAccess::isPumpAssignmentRequest). Until now that
                         | permission was only offered in 56_enable_petro_module, which
                         | renders only when the business has the enable_petro_module
                         | package flag.
                         |
                         | A Petro PD business does not have that flag, so the checkbox
                         | never appeared and the permission could not be granted at all -
                         | the button stayed hidden no matter what the role was given.
                         | Offering it here makes it grantable to a PD role without
                         | switching enable_petro_module on.
                         |
                         | Same permission name, deliberately: a role that already holds
                         | it from the Petro section keeps working, and both checkboxes
                         | tick together.
                        --}}
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'bulk_assign_pumps', in_array('bulk_assign_pumps', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Assign Pumps (Daily Pump Status)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.view_report', in_array('petro_pd.view_report', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} View Reports
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.list_settlement', in_array('petro_pd.list_settlement', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} View PD Settlement List
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.create_settlement', in_array('petro_pd.create_settlement', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Create PD Settlement
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.edit_settlement', in_array('petro_pd.edit_settlement', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Edit PD Settlement
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.delete_settlement', in_array('petro_pd.delete_settlement', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Delete PD Settlement
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.manual_entry', in_array('petro_pd.manual_entry', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Manual Entry in Settlement
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.meter_sale_tab', in_array('petro_pd.meter_sale_tab', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Meter Sale Tab
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.other_sale_tab', in_array('petro_pd.other_sale_tab', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Other Sale Tab
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.other_income_tab', in_array('petro_pd.other_income_tab', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Other Income Tab
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.customer_payment_tab', in_array('petro_pd.customer_payment_tab', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Customer Payment Tab
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd.payment_tab', in_array('petro_pd.payment_tab', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Payment Tab
                                </label>
                            </div>
                        </div>
                        @if(!empty($get_permissions['petro_pd_petro_sms_notifications']) && $get_permissions['petro_pd_petro_sms_notifications'])
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_sms_notifications', in_array('petro_pd_sms_notifications', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'petro::lang.petro_sms_notifications' ) }}
                                </label>
                            </div>
                        </div>
                        @endif
                        @if(!empty($get_permissions['petro_pd_petro_whatsapp']) && $get_permissions['petro_pd_petro_whatsapp'])
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_whatsapp', in_array('petro_pd_whatsapp', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} Petro PD WhatsApp Notifications
                                </label>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <hr class="blue-hr">

                @if(!empty($get_permissions['petro_pd_daily_collection']) && $get_permissions['petro_pd_daily_collection'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Daily Collection</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_daily_collection.edit', in_array('petro_pd_daily_collection.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_daily_collection.delete', in_array('petro_pd_daily_collection.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_settlement']) && $get_permissions['petro_pd_settlement'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Settlement</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">
                            <input type="checkbox" class="check_all input-icheck"> {{ __( 'role.select_all' ) }}
                        </div>
                    </div>
                    <div class="col-md-9">
                        @if(!empty($get_permissions['petro_pd_edit_settlement']) && $get_permissions['petro_pd_edit_settlement'])
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_settlement.edit', in_array('petro_pd_settlement.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        @endif
                        @if(!empty($get_permissions['petro_pd_delete_settlement']) && $get_permissions['petro_pd_delete_settlement'])
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_settlement.delete', in_array('petro_pd_settlement.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <hr class="blue-hr">
                @endif


                @if(!empty($get_permissions['petro_pd_dip_management']) && $get_permissions['petro_pd_dip_management'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Dip Management</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_dip_management.edit', in_array('petro_pd_dip_management.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_dip_management.delete', in_array('petro_pd_dip_management.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_pump_management']) && $get_permissions['petro_pd_pump_management'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Pump Management</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_pump_management.edit', in_array('petro_pd_pump_management.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_pump_management.delete', in_array('petro_pd_pump_management.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_meter_resetting']) && $get_permissions['petro_pd_meter_resetting'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Meter Resetting</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_meter_resetting.edit', in_array('petro_pd_meter_resetting.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_meter_resetting.delete', in_array('petro_pd_meter_resetting.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_meter_reading']) && $get_permissions['petro_pd_meter_reading'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Meter Reading</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_meter_reading.edit', in_array('petro_pd_meter_reading.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_meter_reading.delete', in_array('petro_pd_meter_reading.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_pumper_management']) && $get_permissions['petro_pd_pumper_management'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Pumper Management</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_pumper_management.edit', in_array('petro_pd_pumper_management.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_pumper_management.delete', in_array('petro_pd_pumper_management.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_tank_transfer']) && $get_permissions['petro_pd_tank_transfer'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Tank Transfer</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_tank_transfer.edit', in_array('petro_pd_tank_transfer.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_tank_transfer.delete', in_array('petro_pd_tank_transfer.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_day_end_settlement']) && $get_permissions['petro_pd_day_end_settlement'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Day End Settlement</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_day_end_settlement.edit', in_array('petro_pd_day_end_settlement.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_day_end_settlement.delete', in_array('petro_pd_day_end_settlement.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_daily_collection_sw']) && $get_permissions['petro_pd_daily_collection_sw'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Daily Collection SW</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_daily_collection_sw.edit', in_array('petro_pd_daily_collection_sw.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_daily_collection_sw.delete', in_array('petro_pd_daily_collection_sw.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_petro_activity_report']) && $get_permissions['petro_pd_petro_activity_report'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Petro Activity Report</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_petro_activity_report.edit', in_array('petro_pd_petro_activity_report.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_petro_activity_report.delete', in_array('petro_pd_petro_activity_report.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_tanks_transaction_details']) && $get_permissions['petro_pd_tanks_transaction_details'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Tanks Transaction Details</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_tanks_transaction_details.edit', in_array('petro_pd_tanks_transaction_details.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_tanks_transaction_details.delete', in_array('petro_pd_tanks_transaction_details.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_tanks_transaction_summary']) && $get_permissions['petro_pd_tanks_transaction_summary'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Tanks Transaction Summary</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_tanks_transaction_summary.edit', in_array('petro_pd_tanks_transaction_summary.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_tanks_transaction_summary.delete', in_array('petro_pd_tanks_transaction_summary.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif

                @if(!empty($get_permissions['petro_pd_blocked_pump_operators']) && $get_permissions['petro_pd_blocked_pump_operators'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Blocked Pump Operators</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'petro_pd_blocked_pump_operators.edit', in_array('petro_pd_blocked_pump_operators.edit', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'petro_pd_blocked_pump_operators.delete', in_array('petro_pd_blocked_pump_operators.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.delete' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif
