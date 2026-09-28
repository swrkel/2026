                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>@lang( 'role.customer' )</label></h4>
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
                                    {!! Form::checkbox('permissions[]', 'customer.view', in_array('customer.view', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.view' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'customer.create', in_array('customer.create', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.create' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'customer.update', in_array('customer.update', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.update' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'customer.delete', in_array('customer.delete', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'role.customer.delete' ) }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('permissions[]', 'customer_pay_due', in_array('customer_pay_due', $role_permissions),
                                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.customer_pay_due' ) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
