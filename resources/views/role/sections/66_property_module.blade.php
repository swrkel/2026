                <div class="row">
                    <div class="col-md-3">
                        <h4><label>@lang( 'role.property' )</label></h4>
                    </div>
                </div>
                <hr class="blue-hr">
                
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.property_purchase' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'property.purchase.view', in_array('property.purchase.view',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.view') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.purchase.create', in_array('property.purchase.create',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.create') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.purchase.edit', in_array('property.purchase.edit',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.purchase.delete', in_array('property.purchase.delete',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.delete') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.property_list' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'property.list.view', in_array('property.list.view', $role_permissions)
                                    ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.view') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.list.create', in_array('property.list.create',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.create') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.list.edit', in_array('property.list.edit', $role_permissions)
                                    ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.list.delete', in_array('property.list.delete',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.delete') }}
                                </label>
                            </div>
                        </div>




                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property_finalize.edit', in_array('property_finalize.edit',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.property_finalize.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property_account_settings.edit',
                                    in_array('property_account_settings.edit', $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.property_account_settings.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property_penalty.delete', in_array('property_penalty.delete',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.property_penalty.delete') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'list_easy_payments.access', in_array('list_easy_payments.access',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.list_easy_payments.access') }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.approve_commission', in_array('property.approve_commission',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.property.approve_commission') }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.update_sale_commission', in_array('property.update_sale_commission',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.property.update_sale_commission') }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.update_commission_status', in_array('property.update_commission_status',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.property.update_commission_status') }}
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
                <hr class="blue-hr">
                
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.property_settings' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'property.settings.access', in_array('property.settings.access',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.access') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.settings.unit', in_array('property.settings.unit',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.unit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.settings.tax', in_array('property.settings.tax',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.tax') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.property_customer' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'property.customer.view', in_array('property.customer.view',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.view') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.customer.create', in_array('property.customer.create',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.create') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.customer.edit', in_array('property.customer.edit',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.customer.delete', in_array('property.customer.delete',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.delete') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.project_dashboard' ) </label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'property.project_dashboard.sell_land_blocks',
                                    in_array('property.project_dashboard.sell_land_blocks', $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.sell_land_blocks') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.project_dashboard.customer_payments',
                                    in_array('property.project_dashboard.customer_payments', $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.customer_payments') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'dashboard.change',
                                    in_array('dashboard.change', $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} Permission to edit Prices in Sales dashboard
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                
                @if(!empty($get_permissions['current_sale']) && $get_permissions['current_sale'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.current_sale' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'property.current_sale.edit', in_array('property.current_sale.edit',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.current_sale.view', in_array('property.current_sale.view',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.view') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.current_sale.close.create',
                                    in_array('property.current_sale.close.create', $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.close.create') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.current_sale.close.edit',
                                    in_array('property.current_sale.close.edit', $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.close.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'property.add_new_sale', in_array('property.add_new_sale',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.add_new_sale') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif
