            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'superadmin::lang.asset_module' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'asset.view_all_maintenance', in_array('asset.view_all_maintenance', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.asset.view_all_maintenance' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'asset.update', in_array('asset.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.asset.update' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'asset.delete', in_array('asset.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.asset.delete' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'asset.create', in_array('asset.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.asset.create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'asset.view_own_maintenance', in_array('asset.view_own_maintenance', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.asset.view_own_maintenance' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'asset.view', in_array('asset.view', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.asset.view' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
