            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'superadmin::lang.shipping_module' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'shipping.helpers.edit', in_array('shipping.helpers.edit', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.shipping.helpers.edit' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'shipping.helpers.delete', in_array('shipping.helpers.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.shipping.helpers.delete' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'shipping.access', in_array('shipping.access', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.shipping.access' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'shipping.shipper_tracking_no.add', in_array('shipping.shipper_tracking_no.add', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.shipping.shipper_tracking_no.add' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'shipping.shipper_tracking_no.edit', in_array('shipping.shipper_tracking_no.edit', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.shipping.shipper_tracking_no.edit' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
