            
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'user.roles' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'roles.view', in_array('roles.view', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.view_role' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'roles.create', in_array('roles.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.add_role' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'roles.update', in_array('roles.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.edit_role' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'roles.delete', in_array('roles.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.delete_role' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
