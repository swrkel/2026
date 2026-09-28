            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'lang_v1.crm' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'crm.view', in_array('crm.view', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.create', in_array('crm.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.update', in_array('crm.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.update' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'crm.delete', in_array('crm.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.crm.delete' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
