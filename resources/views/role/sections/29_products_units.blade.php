            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.unit' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'unit.view', in_array('unit.view', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'unit.create', in_array('unit.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'unit.update', in_array('unit.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.update' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'unit.delete', in_array('unit.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.unit.delete' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
