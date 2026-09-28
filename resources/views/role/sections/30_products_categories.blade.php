            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'category.category' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'category.view', in_array('category.view', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'category.create', in_array('category.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'category.update', in_array('category.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.update' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'category.delete', in_array('category.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.category.delete' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
