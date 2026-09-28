            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.pos' ):</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'pos.edit_pos_tax', in_array('pos.edit_pos_tax', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.pos.edit_pos_tax' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'pos.edit_total_tax', in_array('pos.edit_total_tax', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.pos.edit_total_tax' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'pos.edit_discount', in_array('pos.edit_discount', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.pos.edit_discount' ) }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'pos.edit_shipping', in_array('pos.edit_shipping', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.pos.edit_shipping' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
