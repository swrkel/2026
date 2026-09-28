            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'superadmin::lang.hms_module' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'hms.manage_amenities', in_array('hms.manage_amenities', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.manage_amenities' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'hms.manage_extra', in_array('hms.manage_extra', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.manage_extra' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'hms.edit_booking', in_array('hms.edit_booking', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.edit_booking' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'hms.add_booking', in_array('hms.add_booking', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.add_booking' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'hms.manage_coupon', in_array('hms.manage_coupon', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.manage_coupon' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'hms.manage_rooms', in_array('hms.manage_rooms', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.manage_rooms' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'hms.manage_price', in_array('hms.manage_price', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.manage_price' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'hms.manage_unavailable', in_array('hms.manage_unavailable', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.manage_unavailable' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'hms.access', in_array('hms.access', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.hms.access' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
