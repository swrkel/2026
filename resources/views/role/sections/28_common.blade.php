
            <div class="row check_group">
                <div class="col-md-1">
                    <h4><label>@lang( 'role.tax_rate' )</label></h4>
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
                                {!! Form::checkbox('permissions[]', 'tax_rate.view', in_array('tax_rate.view', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.view' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'tax_rate.create', in_array('tax_rate.create', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.create' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'tax_rate.update', in_array('tax_rate.update', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.update' ) }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('permissions[]', 'tax_rate.delete', in_array('tax_rate.delete', $role_permissions),
                                [ 'class' => 'input-icheck']); !!} {{ __( 'role.tax_rate.delete' ) }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="blue-hr">
            
