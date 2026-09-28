                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.fleet' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'fleet.access', in_array('fleet.access',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.fleet.access' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'fleet.edit_trip_category', in_array('fleet.edit_trip_category',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'fleet::lang.fleet.edit_trip_category' ) }}
                                </label>
                            </div>
                        </div>
                        
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit.fleet_opening_balance', in_array('edit.fleet_opening_balance',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit.fleet_opening_balance' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'fleet.add_actual_meter', in_array('fleet.add_actual_meter',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.fleet.add_actual_meter' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'fuel_management', in_array('fuel_management',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.fleet.fuel_management' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_fuel_type', in_array('edit_fuel_type',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.fleet.edit_fuel_type' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'list_trip_operations', in_array('list_trip_operations',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.fleet.list_trip_operations' ) }}
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'edit_fleet', in_array('edit_fleet',
                                    $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.fleet.edit_fleet' ) }}
                                </label>
                            </div>
                        </div>
                        
                        
                    </div>
                </div>
                <hr class="blue-hr">
                
                @if(!empty($get_permissions['routes']) && $get_permissions['routes'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.routes' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'fleet.routes.edit', in_array('fleet.routes.edit',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'fleet.routes.delete', in_array('fleet.routes.delete',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.delete') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif
                
                @if(!empty($get_permissions['drivers']) && $get_permissions['drivers'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.drivers' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'fleet.drivers.edit', in_array('fleet.drivers.edit',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'fleet.drivers.delete', in_array('fleet.drivers.delete',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.delete') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif
                
                @if(!empty($get_permissions['helpers']) && $get_permissions['helpers'])
                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.helpers' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'fleet.helpers.edit', in_array('fleet.helpers.edit',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.edit') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'fleet.helpers.delete', in_array('fleet.helpers.delete',
                                    $role_permissions) ,
                                    [ 'class' => 'input-icheck']); !!} {{ __('role.delete') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
                @endif
