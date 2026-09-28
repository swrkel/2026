            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.brand' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'brand.view', in_array('brand.view', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'brand.create', in_array('brand.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'brand.update', in_array('brand.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.update' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'brand.delete', in_array('brand.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.brand.delete' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
