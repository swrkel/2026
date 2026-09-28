                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang('superadmin::lang.bakery_module'):</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'bakery_login', in_array('bakery_login', $role_permissions), [
                                        'class' => 'input-icheck',
                                    ]) !!} {{ __('role.bakery_login') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_add_loading',
                                        in_array('bakery_add_loading', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_add_loading') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_edit_loading',
                                        in_array('bakery_edit_loading', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_edit_loading') }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_returns',
                                        in_array('bakery_returns', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_returns') }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_edit_user',
                                        in_array('bakery_edit_user', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_edit_user') }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_add_due_amount',
                                        in_array('bakery_add_due_amount', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_add_due_amount') }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_make_payment',
                                        in_array('bakery_make_payment', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_make_payment') }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_add_user',
                                        in_array('bakery_add_user', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_add_user') }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_list_loading',
                                        in_array('bakery_list_loading', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_list_loading') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <h5 style="margin-top: 15px; margin-bottom: 10px;">{{ __('role.bakery_settings') }}</h5>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox(
                                        'permissions[]',
                                        'bakery_settings_show_vehicle_opening_balance',
                                        in_array('bakery_settings_show_vehicle_opening_balance', $role_permissions),
                                        ['class' => 'input-icheck'],
                                    ) !!} {{ __('role.bakery_settings_show_vehicle_opening_balance') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
