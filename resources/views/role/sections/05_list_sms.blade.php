                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang('lang_v1.list_sms'):</label></h4>
                    </div>
                    <div class="col-md-2">
                        <div class="checkbox">
                            <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'sms_ledger', in_array('sms_ledger', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('lang_v1.sms_ledger') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'sms_delivery_report',
                                        in_array('sms_delivery_report', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('lang_v1.sms_delivery_report') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'sms_list_sms',
                                        in_array('sms_list_sms', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('lang_v1.sms_list_sms') }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'sms_history',
                                        in_array('sms_history', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('lang_v1.sms_history') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
